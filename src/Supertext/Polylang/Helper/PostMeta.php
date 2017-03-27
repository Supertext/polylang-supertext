<?php

namespace Supertext\Polylang\Helper;

abstract class PostMeta
{
  private $postId;
  private $metaKey;
  private $fields = null;

  protected function __construct($postId, $metaKey)
  {
    $this->postId = $postId;
    $this->metaKey = $metaKey;
  }

  /**
   * @param $key string the key to test
   * @return bool
   */
  public function is($key)
  {
    if($this->fields === null){
      $this->setFieldsFromPostMeta();
    }

    return isset($this->fields[$key]) && $this->fields[$key] === true;
  }

  /**
   * @param $keys array|string the key or array of keys to get the values of
   * @return array|mixed|null
   */
  public function get($keys)
  {
    if($this->fields === null){
      $this->setFieldsFromPostMeta();
    }

    if(is_string($keys)){
      return isset($this->fields[$keys]) ? $this->fields[$keys] : null;
    }

    $values = array();

    foreach($keys as $key){
      $values[$key] = isset($this->fields[$key]) ? $this->fields[$key] : null;
    }

    return $values;
  }

  /**
   * @param $key string the key to set
   * @param $value mixed the value to set
   */
  public function set($key, $value)
  {
    if($this->fields === null){
      $this->setFieldsFromPostMeta();
    }

    $this->fields[$key] = $value;
    update_post_meta($this->postId, $this->metaKey, $this->fields);
  }

  public function delete(){
    delete_post_meta($this->postId, $this->metaKey);
  }

  private function setFieldsFromPostMeta(){
    $this->fields = get_post_meta($this->postId, $this->metaKey, true);

    if($this->fields == null){
      $this->fields = array();
    }
  }
}