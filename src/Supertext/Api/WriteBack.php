<?php

namespace Supertext\Api;


use Supertext\Helper\Constant;
use Supertext\Helper\ProofreadMeta;
use Supertext\Helper\TranslationMeta;

class WriteBack
{
  /**
   * JSON request data
   * @var array
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
      $orderMeta = $this->getOrderTypeMeta($targetPostId);
      $referenceHash = $orderMeta['obj']->get($orderMeta['refHash']);
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
   * @param $id int the post id
   */
  public function getOrderTypeMeta($id){
    /* for testing:
    $referenceData = hex2bin(Constant::REFERENCE_BITMASK);
    $referenceHash = ProofreadMeta::of(438)->get(ProofreadMeta::IN_PROOFREADING_REFERENCE_HASH);
    $referenceData ^= hex2bin($referenceHash);
    var_dump(bin2hex($referenceData));
     */
    if($this->getOrderType() === 'proofreading'){
      $orderMetas = ProofreadMeta::of($id);

      $metaData = array(
        'obj' => $orderMetas,
        'type' => $orderMetas::PROOFREAD,
        'inStatus' => $orderMetas::IN_PROOFREADING,
        'refHash' => $orderMetas::IN_PROOFREADING_REFERENCE_HASH,
        'sourceLang' => $orderMetas::SOURCE_LANGUAGE_CODE,
        'date' => $orderMetas::PROOFREAD_DATE,
        'metaData' => $orderMetas::META_DATA
      );
    }else{
      $orderMetas = TranslationMeta::of($id);

      $metaData = array(
        'obj' => $orderMetas,
        'type' => $orderMetas::TRANSLATION,
        'inStatus' => $orderMetas::IN_TRANSLATION,
        'refHash' => $orderMetas::IN_TRANSLATION_REFERENCE_HASH,
        'sourceLang' => $orderMetas::SOURCE_LANGUAGE_CODE,
        'date' => $orderMetas::TRANSLATION_DATE,
        'metaData' => $orderMetas::META_DATA
      );
    }

    return $metaData;
  }
}