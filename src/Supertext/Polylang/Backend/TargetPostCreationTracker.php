<?php

namespace Supertext\Polylang\Backend;

/**
 * Class TargetPostCreationTracker Workaround class to create new posts. No function call found that would duplicate posts correctly using polylang.
 * @package Supertext\Polylang\Backend
 */
class TargetPostCreationTracker
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

    set_transient($this->getKey($_GET['from_post']), get_the_ID(), HOUR_IN_SECONDS);
  }

  /**
   * @param $sourcePostId
   * @return int
   */
  public function createNewPost($sourcePostId)
  {
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
}