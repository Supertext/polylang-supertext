<?php

namespace Supertext\Polylang\Helper;

use Supertext\Polylang\Api\Multilang;

/**
 * Class PostMediaContentAccessor
 * @package Supertext\Polylang\Helper
 */
class PostMediaContentAccessor implements IContentAccessor
{
  const KEY_SEPARATOR = '__';

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
      'default' => true
    );

    return array(
      'sourceName' => __('Media', 'polylang-supertext'),
      'fields' => $translatableFields
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

    if ($selectedTranslatableFields['post_image']) {
      $attachments = get_children(
        array(
          'post_parent' => $post->ID,
          'post_type' => 'attachment',
          'post_mime_type' => 'image',
          'orderby' => 'menu_order ASC, ID',
          'order' => 'DESC')
      );

      foreach ($attachments as $attachment) {
        $base_name = 'attachment' . self::KEY_SEPARATOR . $attachment->ID . self::KEY_SEPARATOR;
        $texts[$base_name . 'post_title'] = $attachment->post_title;
        $texts[$base_name . 'post_content'] = $attachment->post_content;
        $texts[$base_name . 'post_excerpt'] = $attachment->post_excerpt;
        $texts[$base_name . 'image_alt'] = get_post_meta($attachment->ID, '_wp_attachment_image_alt', true);
      }
    }

    return $texts;
  }

  /**
   * @param $post
   * @param $texts
   */
  public function setTexts($post, $texts)
  {
    $currentTargetAttachement = null;
    $currentSourceAttachmentId = null;

    foreach ($texts as $id => $text) {
      $keys = explode(self::KEY_SEPARATOR, $id);

      $sourceAttachmentId = intval($keys[1]);

      if ($sourceAttachmentId !== $currentSourceAttachmentId) {

        if (isset($currentTargetAttachement)) {
          wp_update_post($currentTargetAttachement);
        }

        $currentSourceAttachmentId = $sourceAttachmentId;
        $targetAttachmentId = Multilang::getPostInLanguage($sourceAttachmentId, Multilang::getPostLanguage($post->ID));
        $currentTargetAttachement = get_post($targetAttachmentId);
      }

      $postAttribute = $keys[2];

      if ($postAttribute === 'image_alt') {
        update_post_meta(
          $currentTargetAttachement->ID,
          '_wp_attachment_image_alt',
          addslashes(html_entity_decode($text, ENT_COMPAT | ENT_HTML401, 'UTF-8'))
        );
        continue;
      }

      $currentTargetAttachement->{$postAttribute} = html_entity_decode($text, ENT_COMPAT | ENT_HTML401, 'UTF-8');
    }

    if (isset($currentTargetAttachement)) {
      wp_update_post($currentTargetAttachement);
    }
  }
}