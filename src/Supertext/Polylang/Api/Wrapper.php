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
   * @var string given user
   */
  protected $user;
  /**
   * @var string users API key
   */
  protected $apikey;
  /**
   * @var string the API endpoint
   */
  protected $host = Constant::API_URL;
  /**
   * @var Library the library
   */
  protected $library;
  /**
   * @var string the communication language
   */
  protected $communicationLang = 'de-DE';
  /**
   * @var array Open api connections per user
   */
  static private $apiConnections = array();
  /**
   * @var string a reference hash to obfuscate post id info in callback
   */
  const REFERENCE_HASH = '0b7ff2942c3377be90f46673dc197bee';

  /**
   * @param string $user the supertext user name
   * @param string $apikey the supertext api key
   * @param string $currency the currency
   */
  protected function __construct($user, $apikey, $currency = 'eur')
  {
    $this->user = $user;
    $this->apikey = $apikey;
    $this->currency = strtolower($currency);
    $this->library = Core::getInstance()->getLibrary();
    $this->communicationLang = str_replace('_', '-', get_bloginfo('language'));
  }

  /**
   * @param string $user
   * @param string $apikey
   * @param string $currency
   * @return Wrapper
   */
  public static function getInstance($user = Constant::DEFAULT_API_USER, $apikey = '', $currency = 'eur')
  {
    // Open connection for every user
    if (!isset(self::$apiConnections[$user])) {
      self::$apiConnections[$user] = new self($user, $apikey, $currency);
    }
    return self::$apiConnections[$user];
  }

  /**
   * @param string $lang polylang language code
   * @return array mappings for this language code
   */
  public function getLanguageMapping($lang)
  {
    $httpResult = $this->postRequest('translation/LanguageMapping/' . $lang);
    $json = json_decode($httpResult);
    $result = array();
    if (!empty($json->Languages)) {
      foreach ($json->Languages as $entry) {
        $result[(string)$entry->Code] = (string)$entry->Name;
      }
    } else {
      echo '
      <div id="message" class="updated fade"><p>
        <b>Fehler</b> bei der Verbindung zu Supertext: Die Sprachen konnten nicht geladen werden.
      </p></div>';
    }

    return $result;
  }

  /**
   * @param string $source polylang source language
   * @param string $target polylang target language
   * @param array $data data to be quoted for translation
   * @return array
   */
  public function getQuote($source, $target, $data)
  {
    $json = array(
      'ContentType' => 'text/html',
      'Currency' => $this->currency,
      'Groups' => $this->buildSupertextData($data),
      'SourceLang' => $this->library->mapLanguage($source),
      'TargetLang' => $this->library->mapLanguage($target)
    );

    $httpresult = $this->postRequest('translation/quote', json_encode($json), true);
    $json = json_decode($httpresult);
    $result = array(
        'currency' => $json->Currency,
        'currencyName' => $json->CurrencySymbol,
        'options' => array()
    );

    if (!empty($json->Options)) {
      foreach ($json->Options as $o) {
        $deliveryOptions = array();

        foreach ($o->DeliveryOptions as $do) {
          $deliveryOptions[] = array(
              'id' => $do->DeliveryId,
              'name' => $do->Name,
              'price' =>  $do->Price,
              'date' =>  $do->DeliveryDate);
        }

        $result['options'][] = array(
            'id' => $o->OrderTypeId,
            'name' => $o->Name,
            'items' => $deliveryOptions
        );
      }
    }

    return $result;
  }

  /**
   * @param string $source source language in polylang
   * @param string $target target language in polylang
   * @param string $title the title of the translations
   * @param string $productId the supertext product id
   * @param array $data data to be translated
   * @param string $callback the callback url
   * @param string $reference
   * @param string$additionalInfo
   * @return array api result info
   */
  public function createOrder($source, $target, $title, $productId, $data, $callback, $reference, $additionalInfo)
  {
    $product = explode(':', $productId);
    $json = array(
      'CallbackUrl' => $callback,
      'ContentType' => 'text/html',
      'Currency' => $this->currency,
      'DeliveryId' => $product[1],
      'OrderName' => $title,
      'OrderTypeId' => $product[0],
      'ReferenceData' => $reference,
      'Referrer' => 'WordPress Polylang Plugin',
      'SourceLang' => $this->library->mapLanguage($source),
      'TargetLang' => $this->library->mapLanguage($target),
      'AdditionalInformation' => $additionalInfo,
      'Groups' => $this->buildSupertextData($data)
    );

    $httpResult = $this->postRequest('translation/order', json_encode($json), true);
    $json = json_decode($httpResult);

    // If json is not valid, return the result as debug info
    if (is_null($json)) {
      return $httpResult;
    }

    return $json;
  }

  /**
   * @param string $path url to be posted to
   * @param string $data data to be posted
   * @param bool $auth if true, authenticate via api auth
   * @return string api plain text result
   */
  protected function postRequest($path, $data = '', $auth = false)
  {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $this->host . $path);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'WordPress-Polylang-Plugin/HTTP');
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Content-Type: application/json; charset=UTF-8',
      'Accept-Language: ' . $this->communicationLang
    ));

    if ($data != '') {
      curl_setopt($ch, CURLOPT_POST, TRUE);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }

    if ($auth == true) {
      curl_setopt($ch, CURLOPT_USERPWD, $this->user . ':' . $this->apikey);
    }

    $str = curl_exec($ch);
    curl_close($ch);

    return $str;
  }

  /**
   * Convert the given data to supertext specific arrays
   * @param array $data
   * @return string
   */
  protected function buildSupertextData($data)
  {
    $result = array();
    foreach ($data as $key => $value) {
      $group = array(
        'GroupId' => $key,
        'Items' => array()
      );
      if (is_array($value)) {
        foreach ($value as $k => $v) {
          $dataItem = array(
            'Content' => $v,
            'Id' => (string)$k
          );
          $group['Items'][] = $dataItem;
        }
      } else {
        $group['Items'][] = array(
          'Content' => $value,
          'Id' => '0'
        );
      }
      $result[] = $group;
    }
    return $result;
  }
}