<?php

namespace Supertext\Api;


use Supertext\Helper\Constant;
use Supertext\Helper\ProofreadMeta;
use Supertext\Helper\TranslationMeta;
use Supertext\Helper\IWriteBackMeta;

class WriteBack
{
  /**
   * JSON request data
   * @var object
   */
  private $json = null;

  /**
   * Library
   * @var null|\Supertext\Helper\Library
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
  private $contentData = null;

  /**
   * @param $json
   * @param \Supertext\Helper\Library $library
   */
  public function __construct($json, $library)
  {
    $this->json = $json;
    $this->library = $library;
  }

  /**
   * Validates the reference data
   * @return array|null
   */
  public function isReferenceValid()
  {
    $sourcePostIds = $this->getSourcePostIds();

    $referenceData = hex2bin(Constant::REFERENCE_BITMASK);
    foreach ($sourcePostIds as $sourcePostId) {
      $targetPostId = $this->library->getMultilang()->getPostInLanguage($sourcePostId, $this->getTargetLanguageCode());
      $writeBackMeta = $this->getWriteBackMeta($targetPostId);
      $referenceHash = $writeBackMeta->getReferenceHash();
      $referenceData ^= hex2bin($referenceHash);
    }

    return $this->json->ReferenceData === bin2hex($referenceData);
  }

  /**
   * @return null|string
   */

  public function getTargetLanguageCode()
  {
    if ($this->targetLanguage == null) {
      $this->targetLanguage = $this->library->toPolyCode($this->json->TargetLang);
    }
    return $this->targetLanguage;
  }

  /**
   * @return array|null
   */
  public function getContentData(){
    if($this->contentData == null){
      $groups = $this->json->Groups;

      $this->contentData = Wrapper::buildContentData($groups);
    }

    return $this->contentData;
  }

  /**
   * @return array|null
   */
  public function getSourcePostIds(){
    if($this->postIds == null){
      $this->postIds = array_keys($this->getContentData());
    }

    return $this->postIds;
  }

  /**
   * @return int the order id
   */
  public function getOrderId(){
    return intval($this->json->Id);
  }

  /**
   * Get the order type
   * @return string the order type: "proofread" or "translation"
   */
  public function getOrderType(){
    $apiSettings = $this->library->getSettingOption(Constant::SETTING_API);
    $proofreadService = !empty($apiSettings['serviceTypePr']) ? $apiSettings['serviceTypePr'] : Constant::DEFAULT_SERVICE_TYPE_PR;
    $type = 'translation';

    if($this->getOrderServiceId() === intval($proofreadService)){
      $type = 'proofreading';
    }

    return $type;
  }

  /**
   * @return int the order service type id
   */
  public function getOrderServiceId(){
    return intval($this->json->ServiceTypeId);
  }

  /**
   * Get the metas based on the order type
   * @param int $id the post id
   * @return IWriteBackMeta
   */
  public function getWriteBackMeta($id){
    $orderType = $this->getOrderType();

    switch($orderType){
      case 'proofreading':
        return ProofreadMeta::of($id);
      case 'translation':
        return TranslationMeta::of($id);
    }
  }
}