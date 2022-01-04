<?php

namespace Supertext\Api;

use Supertext\Helper\Constant;

/**
 * Wrapper class for external api calls to supertext
 * @package Supertext\Api
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
   * @param ApiClient $apiClient
   * @param $languageCode
   * @param $languageName
   * @return array mappings for this language code
   * @throws ApiConnectionException
   * @throws ApiDataException
   */
  public static function getLanguageMapping($apiClient, $languageCode, $languageName)
  {
    $languageCodeHyphenPosition = strpos($languageCode, '-');
    $shortLanguageCode = $languageCodeHyphenPosition > 0 ? substr($languageCode, 0, $languageCodeHyphenPosition) : $languageCode;
   
    $httpResult = $apiClient->postRequest('v1/translation/LanguageMapping/' . $shortLanguageCode);

    $json = json_decode($httpResult);

    if (empty($json)) {
      throw new ApiDataException(sprintf(__('Supertext doesn\'t support <b>%s</b>.', 'supertext'), $languageCode));
    }

    if (empty($json->Languages)) {
      return array(
        $languageCode => $languageName
      );
    }

    $result = array();

    foreach ($json->Languages as $entry) {
      $result[(string)$entry->Code] = (string)$entry->Title;
    }

    return $result;
  }

  /**
   * @param ApiClient $apiClient
   * @param string $sourceLanguage polylang source language
   * @param string $targetLanguage polylang target language
   * @param array $data data to be quoted for translation
   * @param $serviceType
   * @return array
   * @throws ApiConnectionException
   * @throws ApiDataException
   */
  public static function getQuote($apiClient, $sourceLanguage, $targetLanguage, $data, $serviceType)
  {
    $json = array(
      'ContentType' => 'text/html',
      'Currency' => 'eur', //not used by Supertext API, API returns prices in currency of authenticated user
      'Groups' => self::buildSupertextData($data),
      'SourceLang' => $sourceLanguage,
      'TargetLang' => $targetLanguage,
      'ServiceTypeId' => $serviceType
    );

    $httpResult = $apiClient->postRequest('v1/translation/quote', json_encode($json), true);
    $json = json_decode($httpResult);

    if ($json->WordCount == 0) {
      throw new ApiDataException(__('There is no content to be translated.', 'supertext'));
    }

    if (empty($json->Options)) {
      throw new ApiDataException(
        sprintf(
          __('The quotes are missing (%s -> %s). Please contact Supertext.', 'supertext'),
          __($sourceLanguage, 'supertext-langs'),
          __($targetLanguage, 'supertext-langs')
        )
      );
    }

    $result = array(
      'wordCount' => $json->WordCount,
      'language' => __($targetLanguage, 'supertext-langs'),
      'options' => array()
    );

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
        'id' => $option->OrderTypeConfigurationId,
        'name' => trim($option->Name),
        'items' => $deliveryOptions
      );
    }

    return $result;
  }
//TODO refactor function signature
  /**
   * @param ApiClient $apiClient
   * @param string $title the title of the translations
   * @param string $sourceLanguage supertext source language
   * @param string $targetLanguage supertext target language
   * @param array $data data to be translated
   * @param string $translationType the supertext product id
   * @param string $additionalInformation
   * @param string $referenceData
   * @param string $callback the callback url
   * @param $serviceType
   * @return array|object api result info
   * @throws ApiConnectionException
   * @throws ApiDataException
   */
  public static function createOrder($apiClient, $title, $sourceLanguage, $targetLanguage, $data, $translationType, $additionalInformation, $referenceData, $callback, $serviceType)
  {
    $product = explode(':', $translationType);
    $blogName = get_bloginfo('name');
    $systemName = substr($blogName, 0, min(Constant::MAX_SYSTEM_NAME_LENGTH, strlen($blogName)));

    $json = array(
      'CallbackUrl' => $callback,
      'ContentType' => 'text/html',
      'Currency' => 'eur',
      'DeliveryId' => $product[1],
      'OrderName' => $title,
      'OrderTypeConfigurationId' => $product[0],
      'ReferenceData' => $referenceData,
      'Referrer' => 'WordPress Supertext Plugin',
      'SystemName' => $systemName,
      'SystemVersion' => get_bloginfo('version'),
      'ComponentName' => 'supertext',
      'ComponentVersion' => SUPERTEXT_PLUGIN_VERSION,
      'SourceLang' => $sourceLanguage,
      'TargetLang' => $targetLanguage,
      'AdditionalInformation' => $additionalInformation,
      'Groups' => self::buildSupertextData($data),
      'ServiceTypeId' => $serviceType
    );

    $httpResult = $apiClient->postRequest('v1.1/translation/order', json_encode($json), true);
    $json = json_decode($httpResult);
    $order = $json[0];

    if (empty($order->Deadline) || empty($order->Id)) {
      throw new ApiDataException(_('Could not create an order with Supertext.', 'supertext'));
    }

    return  $order;
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
  public static function buildContentData($groups)
  {
    $result = array();

    foreach ($groups as $group) {
      $keys = explode(self::KEY_SEPARATOR, $group->GroupId);
      $postId = $keys[0];

      if (!isset($result[$postId])) {
        $result[$postId] = array();
      }

      $result[$postId][$keys[1]] = self::getGroupArray($group->Items);
    }

    return $result;
  }

  /**
   * @param ApiClient $apiClient
   * @param $lastOrderId
   * @param $sourceLanguage
   * @param $targetLanguage
   * @param $oldData
   * @param $newData
   * @return array|mixed|object
   * @throws ApiConnectionException
   */
  public static function sendSyncRequest($apiClient, $lastOrderId, $sourceLanguage, $targetLanguage, $oldData, $newData)
  {
    $json = array(
      'referenceOrderId' => $lastOrderId,
      'SourceLang' => $sourceLanguage,
      'TargetLang' => $targetLanguage,
      'OldFinalContentGroups' => self::buildSupertextData($oldData),
      'NewFinalContentGroups' => self::buildSupertextData($newData)
    );

    $apiClient->postRequest('v1/translationmemory/syncrequests', json_encode($json), true);
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

    foreach ($group as $key => $value) {
      if (is_array($value)) {
        $items = array_merge($items, self::getGroupItems($value, $keyPrefix . $key . self::KEY_SEPARATOR));
        continue;
      }

      $items[] = array(
        'Content' => $value,
        'Id' => $keyPrefix . $key
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

    foreach ($items as $item) {
      $keys = explode(self::KEY_SEPARATOR, $item->Id);
      $lastKeyIndex = count($keys) - 1;
      $currentArray = &$groupArray;

      foreach ($keys as $index => $key) {
        if ($index === $lastKeyIndex) {
          $currentArray[$key] = $item->Content;
          break;
        }

        if (!isset($currentArray[$key])) {
          $currentArray[$key] = array();
        }

        $currentArray = &$currentArray[$key];
      }
    }

    return $groupArray;
  }
}