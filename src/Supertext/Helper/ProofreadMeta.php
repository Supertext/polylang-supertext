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
}