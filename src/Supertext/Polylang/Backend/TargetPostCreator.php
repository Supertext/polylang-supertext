<?php

namespace Supertext\Polylang\Backend;

/**
 * Class TargetPostCreator Workaround class to create new posts. No function call found that would duplicate posts correctly using polylang.
 * @package Supertext\Polylang\Backend
 */
class TargetPostCreator
{
  /**
   * new post url query argument flag
   */
  const IS_TRANSLATION_QUERY_PARAMETER = 'sttr_is_translation';

  /**
   * @var bool
   */
  private $isNewTargetPostId;

  public function __construct()
  {
    add_action('current_screen', array($this, 'checkIfIsNewTargetPostId'));
    add_action('admin_footer', array($this, 'cacheNewTargetPostId'));
  }

  /**
   * @param $screen
   */
  public function checkIfIsNewTargetPostId($screen){
    if($screen->base !== 'post'){
      $this->isNewTargetPostId = false;
      return;
    }

    $screenAction = empty($screen->action) ? empty($_GET['action']) ? '' : $_GET['action'] : $screen->action;

    if($screenAction !== 'add' || !isset($_GET[self::IS_TRANSLATION_QUERY_PARAMETER])){
      $this->isNewTargetPostId = false;
      return;
    }

    $this->isNewTargetPostId = true;
  }

  public function cacheNewTargetPostId(){
    if(!$this->isNewTargetPostId){
      return;
    }

    set_transient($this->getKey($_GET['from_post']), get_the_ID(), 10 * MINUTE_IN_SECONDS);
  }

  /**
   * @param $sourcePostId
   * @param $postType
   * @param $newLanguage
   * @return int
   * @throws PostCreationException
   */
  public function createNewPost($sourcePostId, $postType, $newLanguage)
  {
    $newPostUrl = $this->createNewPostUrl($sourcePostId, $postType, $newLanguage);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
    curl_setopt($ch, CURLOPT_URL, $newPostUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIE, $this->getCookiesString());

    curl_exec($ch);

    $info = curl_getinfo($ch);

    curl_close($ch);

    if ($info['http_code'] !== 200) {
      throw new PostCreationException();
    }

    $cachedIdKey = $this->getKey($sourcePostId);
    $targetPostId = get_transient($cachedIdKey);
    delete_transient($cachedIdKey);

    return $targetPostId;
  }

  /**
   * @param $sourcePostId
   * @return string
   */
  private function getKey($sourcePostId)
  {
    return "sttr_new_post_for_$sourcePostId";
  }

  /**
   * @param $sourcePostId
   * @param $postType
   * @param $newLanguage
   * @return string
   */
  private function createNewPostUrl($sourcePostId, $postType, $newLanguage)
  {
    $newPostUrl = add_query_arg(
      array(
        'post_type' => $postType,
        'from_post' => $sourcePostId,
        'new_lang' => $newLanguage,
        self::IS_TRANSLATION_QUERY_PARAMETER => 1
      ),
      admin_url('post-new.php')
    );
    return $newPostUrl;
  }

  /**
   * @return string
   */
  private function getCookiesString()
  {
    $cookiesString = '';
    foreach ($_COOKIE as $name => $value) {
      if ($cookiesString) {
        $cookiesString .= ';';
      }
      $cookiesString .= $name . '=' . addslashes($value);
    }
    return $cookiesString;
  }
}