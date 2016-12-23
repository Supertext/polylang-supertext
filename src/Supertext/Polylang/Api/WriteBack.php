<?php

namespace Supertext\Polylang\Api;


use Supertext\Polylang\Helper\Constant;
use Supertext\Polylang\Helper\PostMeta;

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
   * Validates the reference data
   * @return array|null
   */
  public function isReferenceValid()
  {
    if(strpos($this->json->ReferenceData, '-') !== false){
      return $this->isReferenceValidLegacy();
    }

    $postIds = $this->getPostIds();

    $referenceData = hex2bin(Constant::REFERENCE_BITMASK);
    foreach ($postIds as $postId) {
      $translationPostId = Multilang::getPostInLanguage($postId, $this->getTargetLanguage());
      $referenceHash = PostMeta::from($translationPostId)->get(PostMeta::IN_TRANSLATION_REFERENCE_HASH);
      $referenceData ^= hex2bin($referenceHash);
    }

    return $this->json->ReferenceData === bin2hex($referenceData);
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
      $groups = $this->json->Groups;

      if(strpos($this->json->ReferenceData, '-') !== false){
        $groups = $this->convertGroupsFromLegacyFormat($groups);
      }

      $this->translationData = Wrapper::buildTranslationData($groups);
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

  /**
   * Depricated, old reference check. Can be removed with next version.
   * @return array|null
   */
  private function isReferenceValidLegacy(){
    $refData = explode('-', $this->json->ReferenceData, 2);
    $postId = $refData[0];
    $secureToken = $refData[1];

    $translationPostId = Multilang::getPostInLanguage($postId, $this->getTargetLanguage());
    $referenceHash = PostMeta::from($translationPostId)->get(PostMeta::IN_TRANSLATION_REFERENCE_HASH);

    return !empty($referenceHash) && md5($referenceHash . $postId) === $secureToken;
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