<?php

namespace Supertext\Polylang\Api;


use Supertext\Polylang\Helper\Constant;
use Supertext\Polylang\Helper\TranslationMeta;

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
  private $contentData = null;

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
   * Validates the reference data
   * @return array|null
   */
  public function isReferenceValid()
  {
    $sourcePostIds = $this->getSourcePostIds();

    $referenceData = hex2bin(Constant::REFERENCE_BITMASK);
    foreach ($sourcePostIds as $sourcePostId) {
      $targetPostId = Multilang::getPostInLanguage($sourcePostId, $this->getTargetLanguageCode());
      $referenceHash = TranslationMeta::of($targetPostId)->get(TranslationMeta::IN_TRANSLATION_REFERENCE_HASH);
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

      if(strpos($this->json->ReferenceData, '-') !== false){
        $groups = $this->convertGroupsFromLegacyFormat($groups);
      }

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
   * Old to new format. Can be removed with next version.
   * @return array|null
   */
  private function convertGroupsFromLegacyFormat($groups)
  {
    $refData = explode('-', $this->json->ReferenceData, 2);
    $postId = $refData[0];

    foreach($groups as &$group){

      if($group->GroupId == 'media'){
        foreach($group->items as &$item){
          $item->Id = str_replace('attachment__', '', $item->Id);
        }
      }

      $group->GroupId = $postId.Wrapper::KEY_SEPARATOR.$group->GroupId;
    }

    return $groups;
  }
}