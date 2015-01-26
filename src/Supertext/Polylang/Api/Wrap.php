<?php

namespace Supertext\Polylang\Api;

/**
 * Warapper class for external api calls to supertext
 * @package Supertext\Polylang\Api
 */
class Wrapper
{
  protected $user;
  protected $apikey;
  protected $host;
  // protected $host = 'http://dev.supertext.ch/api/v1/'; // DEV
  // protected $host = 'https://www.supertext.ch/api/v1/'; // LIVE
  protected $instance;

  static private $api_singleton = array();

  public static function getInstance($instance, $user='public_user', $apikey='', $currency='chf') {
    // Pro user eine eigene Connection öffnen. Falls kein User übergeben wird, so wird eine Public (Guest) Connection erstellt.
    if (self::$api_singleton[$user] === null) {
      self::$api_singleton[$user] = new self($instance, $user, $apikey, $currency='chf');
    }
    return self::$api_singleton[$user];
  }

  private function __construct($instance, $user, $apikey, $currency='chf') {
    $this->user = $user;
    $this->apikey = $apikey;
    $this->currency = strtolower($currency);
    $this->instance = $instance;

    if (is_local()) {
      $this->host = 'http://dev.supertext.ch/api/v1/';
      // $this->host = 'https://www.supertext.ch/api/v1/';
    } else {
      $this->host = 'https://www.supertext.ch/api/v1/';
    }
  }

  public function get_languagemapping($lang) {
    $httpresult = $this->make_request('translation/LanguageMapping/'.$lang.'?communicationlang=de');
    $json = json_decode($httpresult);
    $result = array();
    //foreach($json->Languages->LanguageEntry as $entry) {
    if (!empty($json->Languages)) {
      foreach($json->Languages as $entry) {
        $result[(string)$entry->Iso] = (string)$entry->Name;
      }
    } else {
      echo '
      <div id="message" class="updated fade"><p>
        <b>Fehler</b> bei der Verbindung zu Supertext: Die Sprachen konnten nicht geladen werden.
      </p></div>';
    }

    return $result;
  }

  public function get_quote($source, $target, $data) {
    $json = array(
      'ContentType' => 'text/html',
      'Currency' => $this->currency,
      'Groups' => $this->rebuild_data_to_supertextapi($data),
      'SourceLang' => $this->instance->translate_lang_2_st_lang($source),
      'TargetLang' => $this->instance->translate_lang_2_st_lang($target)
    );

    $httpresult = $this->make_request('translation/quote?communicationlang=de', json_encode($json), true);
    $json = json_decode($httpresult);
    $result = array();

    if (!empty($json->Options)) {
      foreach($json->Options as $o) {
        foreach($o->DeliveryOptions as $do) {
          $result[$o->OrderTypeId.':'.$do->DeliveryId] = array(
            'name' => $o->Name.' in '.$do->Name,
            'price' => $do->Price,
            'date' => $do->DeliveryDate
          );
        }
      }
    } else {
      echo '
      <div id="message" class="updated fade"><p>
        <b>Fehler</b> bei der Verbindung zu Supertext: Die Preise können nicht angezeigt werden.
      </p></div>';
    }

    return $result;
  }

  public function make_order($source, $target, $title, $product_id, $data, $callback, $reference, $additional_information) {
    $product = explode(':', $product_id);
    $json = array(
      'CallbackUrl' => $callback,
      'ContentType' => 'text/html',
      'Currency' => $this->currency,
      'DeliveryId' => $product[1],
      'OrderName' => $title,
      'OrderTypeId' => $product[0],
      'ReferenceData' => $reference,
      'Referrer' => 'Blogwerk AG',
      'SourceLang' => $this->instance->translate_lang_2_st_lang($source),
      'TargetLang' => $this->instance->translate_lang_2_st_lang($target),
      'AdditionalInformation' => $additional_information,
      'Groups' => $this->rebuild_data_to_supertextapi($data)
    );

    $httpresult = $this->make_request('translation/order?communicationlang=de', json_encode($json), true);

    logMessageWithUser('Supertext API', 'Make Order', $httpresult, 'DEBUG');

    $json = json_decode($httpresult);

    // falls kein json vorhanden -> einfach plain text zurück geben -> ajax debug info
    if (is_null($json)) {
      $json = $httpresult;
    }

    // return $json->Deadline;
    return $json;
  }

  protected function make_request($path, $data='', $auth=false) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $this->host.$path);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // SSL ausschalten --> nur in Testumgebung
    // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    curl_setopt($ch, CURLOPT_HTTPHEADER, array (
        "Content-Type: application/json; charset=UTF-8"
    ));

    if ($data != '') {
      curl_setopt($ch, CURLOPT_POST, TRUE);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }

    if ($auth == true) {
      curl_setopt($ch, CURLOPT_USERPWD, $this->user.':'.$this->apikey);
    }

    curl_setopt($ch, CURLOPT_USERAGENT, 'SupertextHttpRequest');

    $str = curl_exec($ch);

    curl_close($ch);

    return $str;
  }

  /**
   *
   * @param array $data
   * @return string
   */
  public function rebuild_data_to_supertextapi($data) {
    $result = array();
    foreach($data as $key=>$value) {
      $group = array(
        'GroupId' => $key,
        'Items' => array()
      );
      if (is_array($value)) {
        foreach($value as $k=>$v) {
          $dataItem = array(
            'Content' => $v,
            'Id' => (string)$k
          );
          $this->checkServiceType($dataItem, $key, $k);
          $group['Items'][] = $dataItem;
        }
      }
      else {
        $group['Items'][] = array(
          'Content' => $value,
          'Id' => '0'
        );
      }
      $result[] = $group;
    }
    return $result;
  }

  /**
   * search the service name for services
   * @param $groupId GroupId Data for send to Supertext
   * @param $dataItem Array with Translation Data for Supertext per Item
   * @param $id id of the field (for services: excerpt_{instance_id})
   */
  protected function checkServiceType(&$dataItem, $groupId, $id)
  {
    if ($groupId == 'shares') {
      $shareId = substr($id, 8);
      $serviceInstance = getServiceInstanceFeature($shareId, 'signature');
      $signature = call_user_func($serviceInstance);
      $dataItem['Context'] = $signature['name'];
      $maxLength = intval($signature['msg_length']);
      if ($maxLength > 0) {
        $dataItem['MaxLength'] = $signature['msg_length'];
      }
    }
  }
}