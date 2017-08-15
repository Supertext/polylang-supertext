<?php

namespace Supertext\Polylang\Backend;

use Supertext\Polylang\Api\Multilang;
use Supertext\Polylang\Api\Wrapper;
use Supertext\Polylang\Helper\Constant;
use Supertext\Polylang\Helper\TranslationMeta;

/**
 * Provided ajax request handlers
 * @package Supertext\Polylang\Backend
 */
class AjaxRequestHandler
{
  /**
   * Post status for created target posts
   */
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
   * @var TargetPostCreationTracker
   */
  private $targetPostCreationTracker;

  /**
   * @param \Supertext\Polylang\Helper\Library $library
   * @param Log $log
   * @param ContentProvider $contentProvider
   * @param $targetPostCreationTracker TargetPostCreationTracker
   */
  public function __construct($library, $log, $contentProvider, $targetPostCreationTracker)
  {
    $this->library = $library;
    $this->log = $log;
    $this->contentProvider = $contentProvider;
    $this->targetPostCreationTracker = $targetPostCreationTracker;

    add_action('wp_ajax_sttr_getPostTranslationInfo', array($this, 'getPostTranslationInfoAjax'));
    add_action('wp_ajax_sttr_getPostRawData', array($this, 'getPostRawDataAjax'));
    add_action('wp_ajax_sttr_getPostContentData', array($this, 'getPostContentDataAjax'));
    add_action('wp_ajax_sttr_getOffer', array($this, 'getOfferAjax'));
    add_action('wp_ajax_sttr_getNewPostQueryParams', array($this, 'getNewPostQueryParamsAjax'));
    add_action('wp_ajax_sttr_createOrder', array($this, 'createOrderAjax'));
    add_action('wp_ajax_sttr_sendSyncRequest', array($this, 'sendSyncRequestAjax'));
  }

  /**
   * Gets translation information about posts
   */
  public function getPostTranslationInfoAjax()
  {
    $translationInfo = array();
    $postIds = $_GET['postIds'];

    foreach ($postIds as $postId) {
      $post = get_post($postId);
      $translationInfo[] = array(
        'id' => $postId,
        'title' => $post->post_title,
        'languageCode' => Multilang::getPostLanguage($postId),
        'meta' => TranslationMeta::of($postId)->get(array(
          TranslationMeta::IN_TRANSLATION,
          TranslationMeta::SOURCE_LANGUAGE_CODE
        )),
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
  public function getPostRawDataAjax()
  {
    $postId = $_GET['postId'];
    $content = $this->contentProvider->getRawData(get_post($postId));
    self::returnResponse(200, $content);
  }

  /**
   * Gets the translation data of a post
   */
  public function getPostContentDataAjax()
  {
    $postId = $_GET['postId'];
    $translatableFieldGroups = $_POST['translatableContents'];
    $content = $this->contentProvider->getContentData(get_post($postId), $translatableFieldGroups[$postId]);
    self::returnResponse(200, $content);
  }

  /**
   * Gets the offer
   */
  public function getOfferAjax()
  {
    $content = $this->getContent($_POST['translatableContents']);

    try {
      $quote = Wrapper::getQuote(
        $this->library->getApiClient(),
        $this->library->toSuperCode($_POST['orderSourceLanguage']),
        $this->library->toSuperCode($_POST['orderTargetLanguage']),
        $content['data'],
        $this->getServiceType()
      );

      self::returnResponse(200, $quote);
    } catch (\Exception $e) {
      self::returnResponse(500, $e->getMessage());
    }
  }

  public function getNewPostQueryParamsAjax(){
    $translatableContents = $_POST['translatableContents'];
    $targetLanguage = $_POST['orderTargetLanguage'];
    $sourcePostIds = array_keys($translatableContents);

    $result = array();
    foreach($sourcePostIds as $sourcePostId){
      $targetPostId = Multilang::getPostInLanguage($sourcePostId, $targetLanguage);

      if ($targetPostId != null) {
        continue;
      }

      $sourcePost = get_post($sourcePostId);

      array_push($result, array(
        'from_post' => $sourcePost->ID,
        'post_type' => $sourcePost->post_type,
        'new_lang' => $targetLanguage,
        TargetPostCreationTracker::IS_TRANSLATION_QUERY_PARAMETER  => 1
      ));
    }

    self::returnResponse(200, $result);
  }

  /**
   * Creates the order
   */
  public function createOrderAjax()
  {
    $translatableContents = $_POST['translatableContents'];
    $sourceLanguage = $_POST['orderSourceLanguage'];
    $targetLanguage = $_POST['orderTargetLanguage'];
    $content = $this->getContent($translatableContents);
    $sourcePostIds = array_keys($translatableContents);
    $additionalInformation = $_POST['comment'] . ' Posts: ' . implode(', ', $sourcePostIds);
    $referenceHashes = $this->createReferenceHashes($sourcePostIds);

    try {
      //create needed target posts
      $targetPostIds = $this->createTargetPosts($sourcePostIds, $sourceLanguage, $targetLanguage);

      //order
      $order = Wrapper::createOrder(
        $this->library->getApiClient(),
        get_bloginfo('name') . ' - ' . count($sourcePostIds) . ' post(s)' ,
        $this->library->toSuperCode($sourceLanguage),
        $this->library->toSuperCode($targetLanguage),
        $content['data'],
        $_POST['translationType'],
        $additionalInformation,
        $referenceHashes[0],
        admin_url( 'admin-ajax.php' ) . '?action=sttr_callback',
        $this->getServiceType()
      );

      //process posts
      $workflowSettings = $this->library->getSettingOption(Constant::SETTING_WORKFLOW);
      $this->processTargetPosts(
        $order,
        $sourcePostIds,
        $targetPostIds,
        $sourceLanguage,
        $targetLanguage,
        $referenceHashes,
        $content['metaData'],
        isset($workflowSettings['syncTranslationChanges']) && $workflowSettings['syncTranslationChanges']
      );

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
  public function sendSyncRequestAjax()
  {
    try {
      $this->sendSyncRequest(get_post($_GET['targetPostId']));
      self::returnResponse(200, array(
        'message' => __('The changes have been sent successfully.', 'polylang-supertext')
      ));
    } catch (\Exception $e) {
      self::returnResponse(500, $e->getMessage());
    }
  }

  /**
   * @param $translatableContents
   * @return array
   */
  private function getContent($translatableContents)
  {
    $contentData = array(
      'data' => array(),
      'metaData' => array()
    );

    foreach ($translatableContents as $postId => $translatableFieldGroups) {
      $post = get_post($postId);
      $contentData['data'][$postId] = $this->contentProvider->getContentData($post, $translatableFieldGroups);
      $contentData['metaData'][$postId] = $this->contentProvider->getContentMetaData($post, $translatableFieldGroups);
    }

    return $contentData;
  }

  /**
   * @param $postId
   * @return array
   */
  private function getAllTranslatableContent($postId)
  {
    $translatableContents = array($postId => array());
    $translatableFieldGroups = $this->contentProvider->getTranslatableFieldGroups($postId);
    foreach($translatableFieldGroups as $id => $translatableFieldGroup){
      $translatableContents[$postId][$id] = array('fields' => array());
      foreach($translatableFieldGroup['fields'] as $field){
        $translatableContents[$postId][$id]['fields'][$field['name']] = 'on';
      }
    }
    return $translatableContents;
  }

  /**
   * @param $sourcePostIds
   * @param $sourceLanguage
   * @param $targetLanguage
   * @return array
   */
  private function createTargetPosts($sourcePostIds, $sourceLanguage, $targetLanguage){
    $targetPostIds = array();

    foreach ($sourcePostIds as $sourcePostId) {
      $targetPostId = Multilang::getPostInLanguage($sourcePostId, $targetLanguage);

      if ($targetPostId != null) {
        $targetPostIds[$sourcePostId] = $targetPostId;
        continue;
      }

      $targetPostIds[$sourcePostId] = $this->createTargetPost($sourcePostId, $sourceLanguage, $targetLanguage);
    }

    return $targetPostIds;
  }

  /**
   * @param $sourcePostId
   * @param $sourceLanguage
   * @param $targetLanguage
   * @return array|null|\WP_Post
   * @throws PostCreationException
   */
  private function createTargetPost($sourcePostId, $sourceLanguage, $targetLanguage)
  {
    $sourcePost = get_post($sourcePostId);

    $targetPostId = $this->targetPostCreationTracker->createNewPost($sourcePost->ID);

    $this->library->setLanguage($sourcePostId, $targetPostId, $sourceLanguage, $targetLanguage);

    $targetPost = get_post($targetPostId);
    $targetPost->post_status = self::TRANSLATION_POST_STATUS;
    $targetPost->post_title = $sourcePost->post_title . ' [' . __('In translation', 'polylang-supertext') . '...]';
    wp_update_post($targetPost);

    return $targetPostId;
  }

  /**
   * @param $order
   * @param $sourcePostIds
   * @param $sourceLanguage
   * @param $targetLanguage
   * @param $referenceHashes
   * @param $metaData
   * @param $syncTranslationChanges
   */
  private function processTargetPosts($order, $sourcePostIds, $targetPostIds, $sourceLanguage, $targetLanguage, $referenceHashes, $metaData, $syncTranslationChanges)
  {
    foreach ($sourcePostIds as $sourcePostId) {
      $targetPost = get_post($targetPostIds[$sourcePostId]);

      $message = sprintf(
        __('Translation order into %s has been placed successfully. Your order number is %s.', 'polylang-supertext'),
        $this->getLanguageName($targetLanguage),
        $order->Id
      );

      $this->log->addEntry($sourcePostId, $message);
      $this->log->addOrderId($sourcePostId, $order->Id);
      $this->log->addOrderId($targetPost->ID, $order->Id);

      $meta = TranslationMeta::of($targetPost->ID);
      $meta->set(TranslationMeta::TRANSLATION, true);
      $meta->set(TranslationMeta::IN_TRANSLATION, true);
      $meta->set(TranslationMeta::IN_TRANSLATION_REFERENCE_HASH, $referenceHashes[$sourcePostId]);
      $meta->set(TranslationMeta::SOURCE_LANGUAGE_CODE, $sourceLanguage);
      $meta->set(TranslationMeta::META_DATA, $metaData[$sourcePostId]);

      $translationDate = $meta->get(TranslationMeta::TRANSLATION_DATE);
      if($syncTranslationChanges && $translationDate !== null && strtotime($translationDate) < strtotime($targetPost->post_modified)){
        try{
          $this->sendSyncRequest($targetPost);
        }catch (\Exception $e) {
          $this->log->addEntry($targetPost->ID, __('Post changes could not be sent to Supertext.', 'polylang-supertext'));
        }
      }
    }
  }

  /**
   * Send post changes to supertext
   * @param object $targetPost the post
   * @throws \Exception
   */
  public function sendSyncRequest($targetPost)
  {
    $targetPostId = $targetPost->ID;
    $meta = TranslationMeta::of($targetPostId);
    $sourceLanguageCode = $meta->get(TranslationMeta::SOURCE_LANGUAGE_CODE);
    $newTranslatableContent = $this->getAllTranslatableContent($targetPostId);
    $oldTranslatableContent = array();

    $revisions = wp_get_post_revisions($targetPostId);
    $translationDate = strtotime($meta->get(TranslationMeta::TRANSLATION_DATE));

    foreach ($revisions as $revision) {
      if (strtotime($revision->post_modified) == $translationDate) {
        $oldTranslatableContent = $this->getAllTranslatableContent($revision->ID);
        break;
      }
    }

    if (empty($oldTranslatableContent)) {
      throw new \Exception(__('Could not retrieve old version', 'polylang-supertext'));
    }

    Wrapper::sendSyncRequest(
      $this->library->getApiClient(),
      $this->log->getLastOrderId($targetPostId),
      $this->library->toSuperCode($sourceLanguageCode),
      $this->library->toSuperCode(Multilang::getPostLanguage($targetPostId)),
      $this->getContent($oldTranslatableContent)['data'],
      $this->getContent($newTranslatableContent)['data']
    );

    $meta->set(TranslationMeta::TRANSLATION_DATE, $targetPost->post_modified);
    $this->log->addEntry($targetPostId, __('Post changes have been sent to Supertext.', 'polylang-supertext'));
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

      if ($targetPostId == null || $targetPostId == $sourcePostId || !TranslationMeta::of($targetPostId)->is(TranslationMeta::IN_TRANSLATION)) {
        continue;
      }

      $unfinishedTranslations[$language->slug] = array(
        'orderId' => $this->log->getLastOrderId($targetPostId)
      );
    }

    return $unfinishedTranslations;
  }

  private function getServiceType()
  {
    $apiSettings = $this->library->getSettingOption(Constant::SETTING_API);
    $serviceType = !empty($apiSettings['serviceType']) ? $apiSettings['serviceType'] : Constant::DEFAULT_SERVICE_TYPE;
    return $serviceType;
  }
}
