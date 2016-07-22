<?php

namespace Supertext\Polylang\Api;


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
  private $referenceData = null;

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
    $referenceData = $this->getReferenceData() ;

    if ($referenceData == null) {
      return array('code' => 403, 'message' => 'Error: reference is invalid.');
    }

    $translationData = $this->getTranslationData();
    $translationDataPostIds = array_keys($translationData);
    $postIds = $this->getPostIds();

    if(sort($translationDataPostIds) != sort($postIds)){
      return array('code' => 400, 'message' => 'Error: translation data are missing.');
    }

    return null;
  }

  /**
   * @return array|null
   */
  public function getReferenceData()
  {
    if ($this->referenceData == null) {
      $this->referenceData = $this->library->getReferenceData($this->json->ReferenceData);
    }

    return $this->referenceData;
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
   * @return mixed
   */
  public function getPostIds()
  {
    $referenceData = $this->getReferenceData();
    return $referenceData['postIds'];
  }

  /**
   * Removes the reference data
   */
  public function removeReferenceData(){
    $this->library->removeReferenceData($this->json->ReferenceData);
  }
}