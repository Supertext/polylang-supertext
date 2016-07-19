<?php

namespace Supertext\Polylang\Backend;

use Supertext\Polylang\Api\Multilang;
use Supertext\Polylang\Api\Wrapper;
use Supertext\Polylang\Helper\Constant;

/**
 * Provided ajax request handlers
 * @package Supertext\Polylang\Backend
 */
class AjaxRequestHandler
{
  const TRANSLATION_POST_STATUS = 'draft';

  /**
   * @var \Supertext\Polylang\Helper\Library
   */
  private $library;

  /**
   * @var Log
   */
  private $log;

  /**
   * @var ContentProvider
   */
  private $contentProvider;

  /**
   * @param \Supertext\Polylang\Helper\Library $library
   * @param Log $log
   * @param ContentProvider $contentProvider
   */
  public function __construct($library, $log, $contentProvider)
  {
    $this->library = $library;
    $this->log = $log;
    $this->contentProvider = $contentProvider;

    add_action('wp_ajax_sttr_getPostTranslationData', array($this, 'getPostTranslationData'));
    add_action('wp_ajax_sttr_getOffer', array($this, 'getOffer'));
    add_action('wp_ajax_sttr_createOrder', array($this, 'createOrder'));
  }

  /**
   * Gets translation information about posts
   */
  public function getPostTranslationData()
  {
    $translationInfo = array();
    $postIds = $_GET['posts'];

    foreach ($postIds as $postId) {
      $post = get_post($postId);
      $translationInfo[] = array(
        'id' => $postId,
        'title' => $post->post_title,
        'languageCode' => Multilang::getPostLanguage($postId),
        'isInTranslation' => get_post_meta($postId, Constant::IN_TRANSLATION_FLAG, true) == 1,
        'isDraft' => $post->post_status == 'draft',
        'translatableFieldGroups' => $this->contentProvider->getTranslatableFieldGroups($postId)
      );
    }

    self::setJsonOutput('success', $translationInfo);
  }

  /**
   * Gets the offer
   */
  public function getOffer()
  {
    $translationData = $this->getTranslationData($_POST['translatableContents']);

    try{
      $quote = Wrapper::getQuote(
        $this->library->getApiConnection(),
        $this->library->mapLanguage($_POST['orderSourceLanguage']),
        $this->library->mapLanguage($_POST['orderTargetLanguage']),
        $translationData
      );

      self::setJsonOutput('success', $quote);
    }catch (\Exception $e){
      self::setJsonOutput('error', $e->getMessage());
    }
  }

  /**
   * Creates the order
   */
  public function createOrder()
  {
    $translatableContents = $_POST['translatableContents'];
    $sourceLanguage = $_POST['orderSourceLanguage'];
    $targetLanguage = $_POST['orderTargetLanguage'];
    $translationData = $this->getTranslationData($translatableContents);
    $postIds = array_keys($translatableContents);
    $postIdList = implode('_', $postIds);

    try{
      $randomSalt = bin2hex(openssl_random_pseudo_bytes(32));
      $referenceHash = hash('sha256', $postIdList . $randomSalt);

      $order = Wrapper::createOrder(
        $this->library->getApiConnection(),
        get_bloginfo('name') . ' - ' . $postIdList,
        $this->library->mapLanguage($sourceLanguage),
        $this->library->mapLanguage($targetLanguage),
        $translationData,
        $_POST['translationType'],
        $_POST['comment'],
        $referenceHash,
        SUPERTEXT_POLYLANG_RESOURCE_URL . '/scripts/api/callback.php'
      );

      $this->ProcessTranslationPosts($order, $postIds, $sourceLanguage, $targetLanguage);

      $this->library->saveReferenceData($referenceHash, array(
        'randomSalt' => $randomSalt,
        'postIds' => $postIds
      ));

      $result = array(
        'message' => '
          ' . __('The order has been placed successfully.', 'polylang-supertext') . '<br />
          ' . sprintf(__('Your order number is %s.', 'polylang-supertext'), $order->Id) . '<br />
          ' . sprintf(__('The article will be translated by %s.', 'polylang-supertext'), date_i18n('D, d. F H:i', strtotime($order->Deadline)))
      );

      self::setJsonOutput('success', $result);
    }catch (\Exception $e){
      foreach($postIds as $postId){
        $this->log->addEntry($postId, $e->getMessage());
      }

      self::setJsonOutput('error', $e->getMessage());
    }
  }

  /**
   * @return array
   */
  private function getTranslationData($translatableContents)
  {
    $translationData = array();

    foreach ($translatableContents as $postId => $translatableFieldGroups) {
      $post = get_post($postId);
      $translationData[$postId] = $this->contentProvider->getTranslationData($post, $translatableFieldGroups);
    }

    return $translationData;
  }

  /**
   * @param $order
   * @param $postIds
   * @param $sourceLanguage
   * @param $targetLanguage
   */
  private function ProcessTranslationPosts($order, $postIds, $sourceLanguage, $targetLanguage)
  {
    foreach ($postIds as $postId) {
      $translationPostId = Multilang::getPostInLanguage($postId, $targetLanguage);

      if ($translationPostId == null) {
        $translationPost = $this->createTranslationPost($postId, $sourceLanguage, $targetLanguage);
        $translationPostId = $translationPost->ID;
        $this->log->addEntry($translationPostId, __('The article to be translated has been created.', 'polylang-supertext'));
      }

      $message = sprintf(
        __('Order for translation of article into %s has been placed successfully. Your order number is %s.', 'polylang-supertext'),
        $this->getLanguageName($targetLanguage),
        $order->Id
      );

      $this->log->addEntry($postId, $message);
      $this->log->addOrderId($postId, $order->Id);
      $this->log->addOrderId($translationPostId, $order->Id);

      update_post_meta($translationPostId, Constant::IN_TRANSLATION_FLAG, 1);
    }
  }

  /**
   * @param string $key slug to search
   * @return string name of the $key language
   */
  private function getLanguageName($key)
  {
    // Get the supertext key
    $stKey = $this->library->mapLanguage($key);
    return __($stKey, 'polylang-supertext-langs');
  }

  /**
   * @param $postId
   * @param $options
   * @return array|null|\WP_Post
   */
  private function createTranslationPost($postId, $sourceLanguage, $targetLanguage)
  {
    $translationPostId = self::createNewPostFrom($postId);
    $translationPost = get_post($translationPostId);

    self::addImageAttachments($postId, $translationPostId, $sourceLanguage, $targetLanguage);
    self::copyPostMetas($postId, $translationPostId, $targetLanguage);
    self::addInTranslationTexts($translationPost);

    wp_update_post($translationPost);

    self::setLanguage($postId, $translationPostId, $sourceLanguage, $targetLanguage);

    return $translationPost;
  }

  /**
   * @param $postId
   * @return int|\WP_Error
   */
  private static function createNewPostFrom($postId)
  {
    $post = get_post($postId);

    $translationPostData = array(
      'post_author' => wp_get_current_user()->ID,
      'post_mime_type' => $post->post_mime_type,
      'post_password' => $post->post_password,
      'post_status' => self::TRANSLATION_POST_STATUS,
      'post_title' => $post->post_title,
      'post_type' => $post->post_type,
      'menu_order' => $post->menu_order,
      'comment_status' => $post->comment_status,
      'ping_status' => $post->ping_status,
    );

    return wp_insert_post($translationPostData);
  }

  /**
   * @param $sourcePostId
   * @param $targetPostId
   * @param $sourceLang
   * @param $targetLang
   */
  private static function addImageAttachments($sourcePostId, $targetPostId, $sourceLang, $targetLang)
  {
    $sourceAttachments = get_children(array(
        'post_parent' => $sourcePostId,
        'post_type' => 'attachment',
        'post_mime_type' => 'image',
        'orderby' => 'menu_order ASC, ID',
        'order' => 'DESC')
    );

    foreach ($sourceAttachments as $sourceAttachment) {
      $sourceAttachmentId = $sourceAttachment->ID;
      $sourceAttachmentLink = get_post_meta($sourceAttachmentId, '_wp_attached_file', true);
      $sourceAttachmentMetadata = get_post_meta($sourceAttachmentId, '_wp_attached_file', true);

      $targetAttachmentId = Multilang::getPostInLanguage($sourceAttachmentId, $targetLang);

      if ($targetAttachmentId == null) {
        $targeAttachment = $sourceAttachment;
        $targeAttachment->ID = null;
        $targeAttachment->post_parent = $targetPostId;
        $targetAttachmentId = wp_insert_attachment($targeAttachment);
        add_post_meta($targetAttachmentId, '_wp_attachment_metadata', $sourceAttachmentMetadata);
        add_post_meta($targetAttachmentId, '_wp_attached_file', $sourceAttachmentLink);
        self::setLanguage($sourceAttachmentId, $targetAttachmentId, $sourceLang, $targetLang);
      } else {
        $targetAttachment = get_post($targetAttachmentId);
        $targetAttachment->post_parent = $targetPostId;
        wp_insert_attachment($targetAttachment);
      }
    }
  }

  /**
   * Copy post metas using polylang
   * @param $postId
   * @param $translationPostId
   * @param $target_lang
   */
  private static function copyPostMetas($postId, $translationPostId, $target_lang)
  {
    global $polylang;

    if (empty($polylang)) {
      return;
    }

    $polylang->sync->copy_taxonomies($postId, $translationPostId, $target_lang);
    $polylang->sync->copy_post_metas($postId, $translationPostId, $target_lang);
  }

  /**
   * @param $translationPost
   */
  private static function addInTranslationTexts($translationPost)
  {
    $translationPost->post_title = $translationPost->post_title . Constant::IN_TRANSLATION_TEXT;
  }

  /**
   * @param $sourcePostId
   * @param $targetPostId
   * @param $sourceLanguage
   * @param $targetLanguage
   */
  private static function setLanguage($sourcePostId, $targetPostId, $sourceLanguage, $targetLanguage)
  {
    Multilang::setPostLanguage($targetPostId, $targetLanguage);

    $postsLanguageMappings = array(
      $sourceLanguage => $sourcePostId,
      $targetLanguage => $targetPostId
    );

    foreach (Multilang::getLanguages() as $language) {
      $languagePostId = Multilang::getPostInLanguage($sourcePostId, $language->slug);
      if ($languagePostId) {
        $postsLanguageMappings[$language->slug] = $languagePostId;
      }
    }

    Multilang::savePostTranslations($postsLanguageMappings);
  }

  /**
   * @param string $responseType the response type
   * @param array $data data to be sent in body
   */
  private static function setJsonOutput($responseType, $data)
  {
    $json = array(
      'responseType' => $responseType,
      'body' => $data
    );
    header('Content-Type: application/json');
    echo json_encode($json);
    wp_die();
  }
}
