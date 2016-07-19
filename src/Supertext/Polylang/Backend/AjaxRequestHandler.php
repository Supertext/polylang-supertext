<?php

namespace Supertext\Polylang\Backend;


use Supertext\Polylang\Api\AbstractApiException;
use Supertext\Polylang\Api\Multilang;
use Supertext\Polylang\Api\Wrapper;
use Supertext\Polylang\Helper\Constant;

/**
 * Provided ajax request handlers
 * @package Supertext\Polylang\Backend
 * @author Michael Hadorn <michael.hadorn@blogwerk.com> (inline code)
 * @author Michael Sebel <michael@comotive.ch> (refactoring)
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
      $translationInfo[] = array(
        'id' => $postId,
        'title' => get_post($postId)->post_title,
        'languageCode' => Multilang::getPostLanguage($postId),
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
    $translatableContents = $_POST['translatableContents'];
    $translationData = array();

    foreach ($translatableContents as $postId => $translatableFieldGroups) {
      $post = get_post($postId);
      $translationData[$postId] = $this->contentProvider->getTranslationData($post, $translatableFieldGroups);
    }

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
    $translationData = array();
    $postIds = '';

    foreach ($translatableContents as $postId => $translatableFieldGroups) {
      $post = get_post($postId);
      $translationData[$postId] = $this->contentProvider->getTranslationData($post, $translatableFieldGroups);
      $postIds .= '_'.$postId;
    }

    try{
      $randomSalt = bin2hex(openssl_random_pseudo_bytes(32));
      $referenceHash = hash('sha256', $postIds . $randomSalt);

      $order = Wrapper::createOrder(
        $this->library->getApiConnection(),
        get_bloginfo('name') . ' - ' . $postIds,
        $this->library->mapLanguage($_POST['orderSourceLanguage']),
        $this->library->mapLanguage($_POST['orderTargetLanguage']),
        $translationData,
        $_POST['translationType'],
        $_POST['comment'],
        $referenceHash,
        SUPERTEXT_POLYLANG_RESOURCE_URL . '/scripts/api/callback.php'
      );


      /*if(empty($order->Deadline) || empty($order->Id)){
        self::setJsonOutput('error', _('Could not create an order with Supertext.', 'polylang-supertext'));
        $this->log->addEntry($post->ID, _('Could not create an order with Supertext.', 'polylang-supertext'));
        return;
      }*/

      self::setJsonOutput('success', $order);
    }catch (\Exception $e){
      self::setJsonOutput('error', $e->getMessage());
    }

    /*

    $order = $orderCreation['order'];

    if ($orderCreation['success'] && !empty($order->Deadline) && !empty($order->Id)) {
      $translationPostId = Multilang::getPostInLanguage($postId, $options['target_lang']);

      if ($translationPostId == null) {
        $translationPost = $this->createTranslationPost($post, $options);

        if ($translationPost === null) {
          self::setJsonOutput(
            'error', array(
              'reason' => __('Could not create a new post for the translation. You need to create the new post manually using Polylang.', ' polylang-supertext'),
            )
          );
          return;
        }

        $translationPostId = $translationPost->ID;
      }

      $output = '
        <p>
          ' . __('The order has been placed successfully.', 'polylang-supertext') . '<br />
          ' . sprintf(__('Your order number is %s.', 'polylang-supertext'), $order->Id) . '<br />
          ' . sprintf(
          __('The article will be translated by %s.', 'polylang-supertext'),
          date_i18n('D, d. F H:i', strtotime($order->Deadline))
        ) . '
        </p>
      ';

      // Log the success and the order id
      $message = sprintf(
        __('Order for translation of article into %s has been placed successfully. Your order number is %s.', 'polylang-supertext'),
        $this->getLanguageName($options['target_lang']),
        $order->Id
      );
      $this->log->addEntry($post->ID, $message);
      $this->log->addOrderId($post->ID, $order->Id);
      $this->log->addOrderId($translationPostId, $order->Id);

      update_post_meta($translationPostId, Constant::IN_TRANSLATION_FLAG, 1);
      update_post_meta($translationPostId, Constant::IN_TRANSLATION_REFERENCE_HASH, $translationReferenceHash);

      self::setJsonOutput(
        'success', array(
          'html' => $output,
        )
      );

    } else {
      // Error, couldn't create a correct order
      $this->log->addEntry($post->ID, $orderCreation['error']);

      self::setJsonOutput(
        'error', array(
          'reason' => _('Could not create an order with Supertext.', 'polylang-supertext') . ' ' . $orderCreation['error'],
        )
      );
    }*/
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
   * @return array translation info
   */
  private static function getTranslationOptions($data)
  {
    // Param zusammenstellen
    $options = array(
      'post_id' => $data['post_id'],
      'translatable_fields' => $data['translatable_fields'],
      'source_lang' => $data['source_lang'],
      'target_lang' => $data['target_lang'],
      'product_id' => isset($data['rad_translation_type']) ? $data['rad_translation_type'] : 0,
      'additional_information' => stripslashes($data['txt_comment']),
    );

    return $options;
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

  /**
   * @param $postId
   * @param $options
   * @return array|null|\WP_Post
   */
  private function createTranslationPost($post, $options)
  {
    $translationPostId = self::createNewPostFrom($post->ID);

    if ($translationPostId === 0) {
      return null;
    }

    $translationPost = get_post($translationPostId);

    self::addImageAttachments($post->ID, $translationPostId, $options['source_lang'], $options['target_lang']);

    self::copyPostMetas($post->ID, $translationPostId, $options['target_lang']);

    self::addInTranslationTexts($translationPost);

    wp_update_post($translationPost);

    self::setLanguage($post->ID, $translationPostId, $options['source_lang'], $options['target_lang']);

    $this->log->addEntry($translationPostId, __('The article to be translated has been created.', 'polylang-supertext'));

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
}
