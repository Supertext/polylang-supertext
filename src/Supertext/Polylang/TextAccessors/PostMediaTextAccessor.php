<?php

namespace Supertext\Polylang\TextAccessors;

use Supertext\Polylang\Api\Multilang;
use Supertext\Polylang\Helper\Library;

/**
 * Class PostMediaTextAccessor
 * @package Supertext\Polylang\TextAccessors
 */
class PostMediaTextAccessor implements ITextAccessor
{
  const POST_TYPE = 'attachment';

  /**
   * @var Library library
   */
  protected $library;

  /**
   * @param $library
   */
  public function __construct($library)
  {
    $this->library = $library;
  }

  /**
   * Gets the content accessors name
   * @return string
   */
  public function getName()
  {
    return __('Media', 'polylang-supertext');
  }

  /**
   * @param $postId
   * @return array
   */
  public function getTranslatableFields($postId)
  {
    $translatableFields = array();

    $translatableFields[] = array(
      'title' => __('Image captions', 'polylang-supertext'),
      'name' => 'post_image',
      'checkedPerDefault' => true
    );

    return $translatableFields;
  }

  /**
   * @param $post
   * @return array
   */
  public function getRawTexts($post)
  {
    return get_children(
      array(
        'post_parent' => $post->ID,
        'post_type' => self::POST_TYPE,
        'post_mime_type' => 'image'
      )
    );
  }

  /**
   * @param $post
   * @param $selectedTranslatableFields
   * @return array
   */
  public function getTexts($post, $selectedTranslatableFields)
  {
    $texts = array();

    if (!$selectedTranslatableFields['post_image']) {
      return $texts;
    }

    if ($post->post_type === self::POST_TYPE) {
      return array('image_alt' => get_post_meta($post->ID, '_wp_attachment_image_alt', true));
    }

    $texts = $this->addAttachmentTexts($post, $texts);

    $texts = $this->addUnattachedAttachmentTexts($post, $texts);

    return $texts;
  }

  /**
   * @param $post
   * @param $texts
   */
  public function setTexts($post, $texts)
  {
    if ($post->post_type === self::POST_TYPE) {
      $this->updateImageAlt($post->ID, $texts['image_alt']);
      return;
    }

    foreach ($texts as $sourceAttachmentId => $text) {

      $sourceLanguage = Multilang::getPostLanguage($sourceAttachmentId);
      $targetLanguage = Multilang::getPostLanguage($post->ID);
      $targetAttachmentId = Multilang::getPostInLanguage($sourceAttachmentId, $targetLanguage);

      if ($targetAttachmentId == null) {
        $targetAttachmentId = $this->createTargetAttachment($post, $sourceAttachmentId, $sourceLanguage, $targetLanguage);
      }

      $this->updateTargetAttachment($targetAttachmentId, $text);
    }
  }

  /**
   * @param $post
   * @param $texts
   */
  private function addAttachmentTexts($post, $texts)
  {
    $attachments = get_children(
      array(
        'post_parent' => $post->ID,
        'post_type' => self::POST_TYPE,
        'post_mime_type' => 'image',
        'orderby' => 'menu_order ASC, ID',
        'order' => 'DESC'
      )
    );

    foreach ($attachments as $attachment) {
      $texts[$attachment->ID] = array(
        'post_title' => $attachment->post_title,
        'post_content' => $attachment->post_content,
        'post_excerpt' => $attachment->post_excerpt,
        'image_alt' => get_post_meta($attachment->ID, '_wp_attachment_image_alt', true)
      );
    }

    return $texts;
  }

  /**
   * @param $post
   * @param $texts
   */
  private function addUnattachedAttachmentTexts($post, $texts)
  {
    $hasMatches = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $post->post_content, $matches) > 0;

    if (!$hasMatches) {
      return $texts;
    }

    $siteHost = parse_url(site_url())['host'];

    foreach ($matches[1] as $imageSrc) {
      $url = $imageSrc;
      $parsedUrl = parse_url($imageSrc);

      if (empty($parsedUrl['scheme']) && empty($parsedUrl['host'])) {
        $url = site_url($imageSrc);
      } else if ($parsedUrl['host'] != $siteHost) {
        continue;
      }

      $attachmentId = attachment_url_to_postid($url);

      if ($attachmentId == null) {
        continue;
      }

      $attachmentId = Multilang::getPostInLanguage($attachmentId, Multilang::getPostLanguage($post->ID));

      if (isset($texts[$attachmentId])) {
        continue;
      }

      $attachment = get_post($attachmentId);

      $texts[$attachment->ID] = array(
        'post_title' => $attachment->post_title,
        'post_content' => $attachment->post_content,
        'post_excerpt' => $attachment->post_excerpt,
        'image_alt' => get_post_meta($attachment->ID, '_wp_attachment_image_alt', true)
      );
    }

    return $texts;
  }

  /**
   * @param $post
   * @param $sourceAttachmentId
   * @param $sourceLanguage
   * @param $targetLanguage
   * @return array
   */
  private function createTargetAttachment($post, $sourceAttachmentId, $sourceLanguage, $targetLanguage)
  {
    $sourceAttachment = get_post($sourceAttachmentId);
    $newTargetAttachment = $sourceAttachment;
    $newTargetAttachment->ID = null;
    $newTargetAttachment->post_parent = $post->ID;
    $targetAttachmentId = wp_insert_attachment($newTargetAttachment);

    foreach (array('_wp_attachment_metadata', '_wp_attached_file', '_wp_attachment_image_alt') as $key) {
      if ($meta = get_post_meta($sourceAttachmentId, $key, true)) {
        add_post_meta($targetAttachmentId, $key, $meta);
      }
    }

    $this->library->setLanguage($sourceAttachmentId, $targetAttachmentId, $sourceLanguage, $targetLanguage);
    return $targetAttachmentId;
  }

  /**
   * @param $targetAttachmentId
   * @param $text
   */
  private function updateTargetAttachment($targetAttachmentId, $text)
  {
    $targetAttachment = get_post($targetAttachmentId);

    foreach ($text as $key => $value) {
      if ($key === 'image_alt') {
        $this->updateImageAlt($targetAttachment->ID, $value);
        continue;
      }

      $targetAttachment->{$key} = html_entity_decode($value, ENT_COMPAT | ENT_HTML401, 'UTF-8');
    }

    wp_update_post($targetAttachment);
  }

  private function updateImageAlt($postId, $value){
    update_post_meta(
      $postId,
      '_wp_attachment_image_alt',
      addslashes(html_entity_decode($value, ENT_COMPAT | ENT_HTML401, 'UTF-8'))
    );
  }
}