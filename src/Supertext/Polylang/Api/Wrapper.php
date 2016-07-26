<?php

namespace Supertext\Polylang\Api;

use Supertext\Polylang\Core;
use Supertext\Polylang\Helper\Constant;

/**
 * Wrapper class for external api calls to supertext
 * @package Supertext\Polylang\Api
 * @author Michael Hadorn <michael.hadorn@blogwerk.com> (initial)
 * @author Michael Sebel <michael@comotive.ch> (refactoring)
 */
class Wrapper
{
  /**
   * Separator used to concatenate array keys in order to flatten data array
   */
  const KEY_SEPARATOR = '__';

  /**
   * @param ApiConnection $connection
   * @param string $lang polylang language code
   * @return array mappings for this language code
   * @throws ApiConnectionException
   * @throws ApiDataException
   */
  public static function getLanguageMapping($connection, $lang)
  {
    $httpResult = $connection->postRequest('translation/LanguageMapping/' . $lang);
    $json = json_decode($httpResult);

    if(empty($json->Languages)){
      throw new ApiDataException(sprintf(__('Languages mapped to <b>%s</b> missing', 'polylang-supertext'), $lang));
    }

    $result = array();

    foreach ($json->Languages as $entry) {
      $result[(string)$entry->Code] = (string)$entry->Name;
    }

    return $result;
  }

  /**
   * @param ApiConnection $connection
   * @param string $sourceLanguage polylang source language
   * @param string $targetLanguage polylang target language
   * @param array $data data to be quoted for translation
   * @throws ApiConnectionException
   * @return array
   */
  public static function getQuote($connection, $sourceLanguage, $targetLanguage, $data)
  {
    $json = array(
      'ContentType' => 'text/html',
      'Currency' => 'eur', //not used by Supertext API, API returns prices in currency of authenticated user
      'Groups' => self::buildSupertextData($data),
      'SourceLang' => $sourceLanguage,
      'TargetLang' => $targetLanguage
    );

    $httpResult = $connection->postRequest('translation/quote', json_encode($json), true);
    $json = json_decode($httpResult);

    if($json->WordCount == 0){
      return array('options' => array());
    }

    $result = array();

    foreach ($json->Options as $option) {
      $deliveryOptions = array();

      foreach ($option->DeliveryOptions as $do) {
        $deliveryOptions[] = array(
          'id' => $do->DeliveryId,
          'name' => trim($do->Name),
          'price' => $json->CurrencySymbol . ' ' . number_format($do->Price, 2, '.', "'"),
          'date' => date_i18n('D, d. F H:i', strtotime($do->DeliveryDate)));
      }

      $result['options'][] = array(
        'id' => $option->OrderTypeId,
        'name' => trim($option->Name),
        'items' => $deliveryOptions
      );
    }

    return $result;
  }

  /**
   * @param ApiConnection $connection
   * @param string $title the title of the translations
   * @param string $sourceLanguage supertext source language
   * @param string $targetLanguage supertext target language
   * @param array $data data to be translated
   * @param string $translationType the supertext product id
   * @param string $comment
   * @param string $referenceData
   * @param string $callback the callback url
   * @return array|object api result info
   * @throws ApiConnectionException
   * @throws ApiDataException
   */
  public static function createOrder($connection, $title, $sourceLanguage, $targetLanguage, $data, $translationType, $comment, $referenceData, $callback)
  {
    $product = explode(':', $translationType);

    $json = array(
      'PluginName' => 'polylang-supertext',
      'PluginVersion' => SUPERTEXT_PLUGIN_VERSION,
      'InstallationName' => get_bloginfo('name'),
      'CallbackUrl' => $callback,
      'ContentType' => 'text/html',
      'Currency' => 'eur',
      'DeliveryId' => $product[1],
      'OrderName' => $title,
      'OrderTypeId' => $product[0],
      'ReferenceData' => $referenceData,
      'Referrer' => 'WordPress Polylang Plugin',
      'SourceLang' => $sourceLanguage,
      'TargetLang' => $targetLanguage,
      'AdditionalInformation' => $comment,
      'Groups' => self::buildSupertextData($data)
    );

    $httpResult = $connection->postRequest('translation/order', json_encode($json), true);
    $json = json_decode($httpResult);

    if (empty($json->Deadline) || empty($json->Id)) {
      throw new ApiDataException(_('Could not create an order with Supertext.', 'polylang-supertext'));
    }

    return $json;
  }

  /**
   * Convert the given data to supertext specific arrays
   * @param array $data
   * @return array
   */
  public static function buildSupertextData($data)
  {
    $result = array();
    foreach ($data as $postId => $groups) {
      foreach ($groups as $groupId => $group) {
        $result[] = array(
          'GroupId' => $postId . self::KEY_SEPARATOR . $groupId,
          'Items' => self::getGroupItems($group, '')
        );
      }
    }

    return $result;
  }

  /**
   * Convert the given supertext specific array to plugin translation data array
   * @param $groups
   * @return array
   */
  public static function buildTranslationData($groups){
    $result = array();

    foreach($groups as $group){
      $keys = explode(self::KEY_SEPARATOR, $group->GroupId);
      $postId = $keys[0];

      if(!isset($result[$postId])){
        $result[$postId] = array();
      }

      $result[$postId][$keys[1]] = self::getGroupArray($group->Items);
    }

    return $result;
  }

  /**
   * Gets the items
   * @param $group
   * @param $keyPrefix
   * @return array
   */
  private static function getGroupItems($group, $keyPrefix)
  {
    $items = array();

    foreach($group as $key => $value){
      if(is_array($value)){
        $items = array_merge($items, self::getGroupItems($value, $keyPrefix.$key.self::KEY_SEPARATOR));
        continue;
      }

      $items[] = array(
        'Content' => $value,
        'Id' => $keyPrefix.$key
      );
    }

    return $items;
  }

  /**
   * Converts the items to array
   * @param $items
   * @return array
   */
  private static function getGroupArray($items)
  {
    $groupArray = array();

    foreach($items as $item){
      $keys = explode(self::KEY_SEPARATOR, $item->Id);
      $lastKeyIndex = count($keys)-1;
      $currentArray = &$groupArray;

      foreach($keys as $index => $key){
        if($index === $lastKeyIndex){
          $currentArray[$key] = $item->Content;
          break;
        }

        if(!isset($groupArray[$key])){
          $currentArray[$key] = array();
        }

        $currentArray = &$currentArray[$key];
      }
    }

    return $groupArray;
  }
}