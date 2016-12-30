<?php

namespace Supertext\Polylang\Backend;

use Supertext\Polylang\Api\Multilang;
use Supertext\Polylang\Api\Wrapper;
use Supertext\Polylang\Helper\Constant;
use Supertext\Polylang\Helper\PostMeta;

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

    add_action('wp_ajax_sttr_getPostTranslationInfo', array($this, 'getPostTranslationInfo'));
    add_action('wp_ajax_sttr_getPostRawData', array($this, 'getPostRawData'));
    add_action('wp_ajax_sttr_getPostTranslationData', array($this, 'getPostTranslationData'));
    add_action('wp_ajax_sttr_getOffer', array($this, 'getOffer'));
    add_action('wp_ajax_sttr_createOrder', array($this, 'createOrder'));
    add_action('wp_ajax_sttr_sendPostChanges', array($this, 'sendPostChanges'));
  }

  /**
   * Gets translation information about posts
   */
  public function getPostTranslationInfo()
  {
    $translationInfo = array();
    $postIds = $_GET['postIds'];

    foreach ($postIds as $postId) {
      $post = get_post($postId);
      $translationInfo[] = array(
        'id' => $postId,
        'title' => $post->post_title,
        'languageCode' => Multilang::getPostLanguage($postId),
        'meta' => PostMeta::from($postId)->getPublicProperties(),
        'isDraft' => $post->post_status == 'draft',
        'unfinishedTranslations' => $this->getUnfinishedTranslations($postId),
        'translatableFieldGroups' => $this->contentProvider->getTranslatableFieldGroups($postId)
      );
    }

    self::returnResponse(200, $translationInfo);
  }

  /**
   * Gets the raw data of a posts
   */
  public function getPostRawData()
  {
    $postId = $_GET['postId'];
    $content = $this->contentProvider->getRawData(get_post($postId));
    self::returnResponse(200, $content);
  }

  /**
   * Gets the translation data of a post
   */
  public function getPostTranslationData()
  {
    $postId = $_GET['postId'];
    $translatableFieldGroups = $_POST['translatableContents'];
    $content = $this->contentProvider->getTranslationData(get_post($postId), $translatableFieldGroups[$postId]);
    self::returnResponse(200, $content);
  }

  /**
   * Gets the offer
   */
  public function getOffer()
  {
    $translationData = $this->getTranslationData($_POST['translatableContents']);

    try {
      $quote = Wrapper::getQuote(
        $this->library->getApiClient(),
        $this->library->toSuperCode($_POST['orderSourceLanguage']),
        $this->library->toSuperCode($_POST['orderTargetLanguage']),
        $translationData
      );

      self::returnResponse(200, $quote);
    } catch (\Exception $e) {
      self::returnResponse(500, $e->getMessage());
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
    $sourcePostIds = array_keys($translatableContents);
    $additionalInformation = $_POST['comment'] . ' Posts:' . implode(', ', $sourcePostIds);
    $referenceHashes = $this->createReferenceHashes($sourcePostIds);

    try {

      $order = Wrapper::createOrder(
        $this->library->getApiClient(),
        get_bloginfo('name') . ' - ' . count($sourcePostIds) . ' post(s)' ,
        $this->library->toSuperCode($sourceLanguage),
        $this->library->toSuperCode($targetLanguage),
        $translationData,
        $_POST['translationType'],
        $additionalInformation,
        $referenceHashes[0],
        admin_url( 'admin-ajax.php' ) . '?action=sttr_callback'
      );

      $this->ProcessTargetPosts($order, $sourcePostIds, $sourceLanguage, $targetLanguage, $referenceHashes);

      $result = array(
        'message' => '
          ' . __('The order has been placed successfully.', 'polylang-supertext') . '<br />
          ' . sprintf(__('Your order number is %s.', 'polylang-supertext'), $order->Id) . '<br />
          ' . sprintf(__('The post will be translated by %s.', 'polylang-supertext'), date_i18n('D, d. F H:i', strtotime($order->Deadline)))
      );

      self::returnResponse(200, $result);
    } catch (\Exception $e) {
      foreach ($sourcePostIds as $sourcePostId) {
        $this->log->addEntry($sourcePostId, $e->getMessage());
      }

      self::returnResponse(500, $e->getMessage());
    }
  }

  /**
   * Send post changes to supertext
   */
  public function sendPostChanges()
  {
    $targetPostId = $_GET['targetPostId'];
    $targetPost = get_post($targetPostId);
    $postMeta = PostMeta::from($targetPostId);
    $sourceLanguageCode = $postMeta->get(PostMeta::SOURCE_LANGUAGE_CODE);
    $translatableFieldGroups = $this->contentProvider->getTranslatableFieldGroups($targetPostId);
    $selectedTranslatableFieldGroups = array();
    foreach($translatableFieldGroups as $id => $translatableFieldGroup){
      $selectedTranslatableFieldGroups[$id] = array('fields' => array());
      foreach($translatableFieldGroup['fields'] as $field){
        $selectedTranslatableFieldGroups[$id]['fields'][$field['name']] = 'on';
      }
    }

    try {

      Wrapper::sendPostChanges(
        $this->library->getApiClient(),
        $this->library->toSuperCode($sourceLanguageCode),
        $this->library->toSuperCode(Multilang::getPostLanguage($targetPostId)),
        $postMeta->get(PostMeta::TRANSLATION_DATA),
        $this->contentProvider->getTranslationData($targetPost, $selectedTranslatableFieldGroups)
      );

      $postMeta->set(PostMeta::TRANSLATION_DATE, $targetPost->post_modified);

      $result = array(
        'message' => __('The changes have been sent successfully.', 'polylang-supertext')
      );

      self::returnResponse(200, $result);
    } catch (\Exception $e) {
      self::returnResponse(500, $e->getMessage());
    }
  }

  /**
   * @param $translatableContents
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
   * @param $sourcePostIds
   * @param $sourceLanguage
   * @param $targetLanguage
   * @param $referenceHashes
   */
  private function ProcessTargetPosts($order, $sourcePostIds, $sourceLanguage, $targetLanguage, $referenceHashes)
  {
    foreach ($sourcePostIds as $sourcePostId) {
      $targetPost = $this->getTargetPost($sourcePostId, $sourceLanguage, $targetLanguage);

      $message = sprintf(
        __('Translation order into %s has been placed successfully. Your order number is %s.', 'polylang-supertext'),
        $this->getLanguageName($targetLanguage),
        $order->Id
      );

      $this->log->addEntry($sourcePostId, $message);
      $this->log->addOrderId($sourcePostId, $order->Id);
      $this->log->addOrderId($targetPost->ID, $order->Id);

      $postMeta = PostMeta::from($targetPost->ID);
      $postMeta->set(PostMeta::IN_TRANSLATION, true);
      $postMeta->set(PostMeta::IN_TRANSLATION_REFERENCE_HASH, $referenceHashes[$sourcePostId]);
      $postMeta->set(PostMeta::SOURCE_LANGUAGE_CODE, $sourceLanguage);
    }
  }

  /**
   * @param string $polyCode slug to search
   * @return string name of the $key language
   */
  private function getLanguageName($polyCode)
  {
    // Get the supertext key
    $superCode = $this->library->toSuperCode($polyCode);
    return __($superCode, 'polylang-supertext-langs');
  }

  /**
   * @param $sourcePostId
   * @param $sourceLanguage
   * @param $targetLanguage
   * @return array|null|\WP_Post
   */
  private function getTargetPost($sourcePostId, $sourceLanguage, $targetLanguage)
  {
    $targetPostId = Multilang::getPostInLanguage($sourcePostId, $targetLanguage);

    if ($targetPostId == null) {
      $targetPost = $this->createTargetPost($sourcePostId, $sourceLanguage, $targetLanguage);
      $this->log->addEntry($targetPostId, __('The post to be translated has been created.', 'polylang-supertext'));
      return $targetPost;
    }

    return get_post($targetPostId);
  }

  /**
   * @param $sourcePostId
   * @param $sourceLanguage
   * @param $targetLanguage
   * @return array|null|\WP_Post
   * @internal param $options
   */
  private function createTargetPost($sourcePostId, $sourceLanguage, $targetLanguage)
  {
    $targetPostId = self::createNewPostFrom($sourcePostId);
    $targetPost = get_post($targetPostId);

    self::addImageAttachments($sourcePostId, $targetPostId, $sourceLanguage, $targetLanguage);
    self::copyPostMetas($sourcePostId, $targetPostId, $targetLanguage);

    wp_update_post($targetPost);

    self::setLanguage($sourcePostId, $targetPostId, $sourceLanguage, $targetLanguage);

    return $targetPost;
  }

  /**
   * @param $sourcePostId
   * @return int|\WP_Error
   */
  private static function createNewPostFrom($sourcePostId)
  {
    $sourcePost = get_post($sourcePostId);

    $targetPostData = array(
      'post_author' => wp_get_current_user()->ID,
      'post_mime_type' => $sourcePost->post_mime_type,
      'post_password' => $sourcePost->post_password,
      'post_status' => self::TRANSLATION_POST_STATUS,
      'post_title' => $sourcePost->post_title . ' [' . __('In translation', 'polylang-supertext') . '...]',
      'post_type' => $sourcePost->post_type,
      'menu_order' => $sourcePost->menu_order,
      'comment_status' => $sourcePost->comment_status,
      'ping_status' => $sourcePost->ping_status,
    );

    return wp_insert_post($targetPostData);
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
   * @param $sourcePostId
   * @param $targetPostId
   * @param $target_lang
   */
  private static function copyPostMetas($sourcePostId, $targetPostId, $target_lang)
  {
    global $polylang;

    if (empty($polylang)) {
      return;
    }

    $polylang->sync->copy_taxonomies($sourcePostId, $targetPostId, $target_lang);
    $polylang->sync->copy_post_metas($sourcePostId, $targetPostId, $target_lang);
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
   * @param $code
   * @param $body
   */
  private static function returnResponse($code, $body)
  {
    header('Content-Type: application/json');
    http_response_code($code);
    echo json_encode($body);
    die();
  }

  /**
   * @param array $sourcePostIds
   * @return array
   */
  private function createReferenceHashes($sourcePostIds)
  {
    $referenceHashes = array();

    $referenceData = hex2bin(Constant::REFERENCE_BITMASK);
    foreach ($sourcePostIds as $sourcePostId) {
      $referenceHash = openssl_random_pseudo_bytes(32);
      $referenceData ^= $referenceHash;
      $referenceHashes[$sourcePostId] = bin2hex($referenceHash);
    }

    $referenceHashes[0] = bin2hex($referenceData);

    return $referenceHashes;
  }

  /**
   * @param $sourcePostId
   * @return array
   */
  private function getUnfinishedTranslations($sourcePostId)
  {
    $unfinishedTranslations = array();

    $languages = Multilang::getLanguages();
    foreach ($languages as $language) {
      $targetPostId = Multilang::getPostInLanguage($sourcePostId, $language->slug);

      if ($targetPostId == null || $targetPostId == $sourcePostId || !PostMeta::from($targetPostId)->is(PostMeta::IN_TRANSLATION)) {
        continue;
      }

      $unfinishedTranslations[$language->slug] = array(
        'orderId' => $this->log->getLastOrderId($targetPostId)
      );
    }

    return $unfinishedTranslations;
  }
}
