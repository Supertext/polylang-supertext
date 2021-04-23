<?php

namespace Supertext\Helper;

/**
 * Class ProofreadMeta
 * @package Supertext\Plugin\Helper
 */
class ProofreadMeta extends PostMeta
{
  const PROOFREAD = 'proofread';
  const IN_PROOFREADING = 'inProofreading';
  const IN_PROOFREADING_REFERENCE_HASH = 'inProofreadingRefHash';
  const SOURCE_LANGUAGE_CODE = 'sourceLanguageCode';
  const PROOFREAD_DATE = 'proofreadDate';
  const META_DATA = 'metaData';

  private static $proofreadingProperties = '_sttr_proofreading_properties';

  public static function of($postId)
  {
    return new ProofreadMeta($postId, self::$proofreadingProperties);
  }

  public function getOrderType()
  {
    return self::PROOFREAD;
  }

  public function getReferenceHash()
  {
    return $this->get(self::IN_PROOFREADING_REFERENCE_HASH);
  }

  public function getContentMetaData()
  {
    return $this->get(self::META_DATA);
  }

  public function getSuccessLogEntry()
  {
    return __('proofreading saved successfully', 'supertext');
  }

  public function isInProgress()
  {
    return $this->is(self::IN_PROOFREADING);
  }

  public function markAsComplete()
  {
    // All good, set translation flag false
    $this->set(self::IN_PROOFREADING, false);
    $this->set(self::PROOFREAD_DATE, get_post_field('post_modified', $this->postId));
  }
}