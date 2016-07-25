<?php

namespace Supertext\Polylang\Api;


use Supertext\Polylang\Helper\Constant;

class WriteBack
{
  /**
   * JSON request data
   * @var array
   */
  private $json = null;

  /**
   * Library
   * @var null|\Supertext\Polylang\Helper\Library
   */
  private $library = null;

  /**
   * Reference data
   * @var null|array
   */
  private $postIds = null;

  /**
   * Target language
   * @var null|string
   */
  private $targetLanguage = null;

  /**
   * Translation data
   * @var null|array
   */
  private $translationData = null;

  /**
   * @param $json
   * @param \Supertext\Polylang\Helper\Library $library
   */
  public function __construct($json, $library)
  {
    $this->json = $json;
    $this->library = $library;
  }

  /**
   * Validates the request
   * @return array|null
   */
  public function validate()
  {
    $postIds = $this->getPostIds();

    $referenceData = hex2bin(Constant::REFERENCE_BITMASK);
    foreach ($postIds as $postId) {
      $translationPostId = Multilang::getPostInLanguage($postId, $this->getTargetLanguage());
      $referenceHash = get_post_meta($translationPostId, Constant::IN_TRANSLATION_REFERENCE_HASH, true);
      $referenceData ^= hex2bin($referenceHash);
    }

    if($this->json->ReferenceData !== bin2hex($referenceData)) {
      return array('code' => 403, 'message' => 'Error: reference is invalid.');
    }

    return null;
  }

  /**
   * @return null|string
   */
  public function getTargetLanguage()
  {
    if ($this->targetLanguage == null) {
      $this->targetLanguage = substr($this->json->TargetLang, 0, 2);
    }
    return $this->targetLanguage;
  }

  /**
   * @return array|null
   */
  public function getTranslationData(){
    if($this->translationData == null){
      $this->translationData = Wrapper::buildTranslationData($this->json->Groups);
    }

    return $this->translationData;
  }

  /**
   * @return array|null
   */
  public function getPostIds(){
    if($this->postIds == null){
      $this->postIds = array_keys($this->getTranslationData());
    }

    return $this->postIds;
  }
}