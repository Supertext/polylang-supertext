<?php

namespace Supertext\Polylang\Api;

class ApiClient
{
  /**
   * @var array Open api client per user
   */
  private static $apiClients = array();
  /**
   * @var string the host
   */
  private $host;
  /**
   * @var string given user
   */
  private $user;
  /**
   * @var string users API key
   */
  private $apiKey;
  /**
   * @var string the communication language
   */
  private $communicationLang;

  /**
   * @param string $host the host to connect to
   * @param string $user the supertext user name
   * @param string $apiKey the supertext api key
   */
  private function __construct($host, $user, $apiKey, $communicationLanguage)
  {
    $this->host = $host;
    $this->user = $user;
    $this->apiKey = $apiKey;
    $this->communicationLang = $communicationLanguage;
  }

  /**
   * @param $host
   * @param string $user
   * @param string $apiKey
   * @param string $communicationLanguage
   * @return Wrapper
   */
  public static function getInstance($host, $user, $apiKey, $communicationLanguage = 'de-CH')
  {
    $connectionKey = $host . $user;

    // Open connection for every user
    if (!isset(self::$apiClients[$connectionKey])) {
      self::$apiClients[$connectionKey] = new self($host, $user, $apiKey, $communicationLanguage);
    }
    return self::$apiClients[$connectionKey];
  }

  /**
   * @param string $path url to be posted to
   * @param string $data data to be posted
   * @param bool $auth if true, authenticate via api auth
   * @return string api plain text result
   * @throws ApiConnectionException
   */
  public function postRequest($path, $data = '', $auth = false)
  {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , 120);
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
      curl_setopt($ch, CURLOPT_USERPWD, $this->user . ':' . $this->apiKey);
    }

    $body = curl_exec($ch);

    $error = $this->getError($ch);

    if (!empty($error)) {
      throw new ApiConnectionException($error);
    }

    curl_close($ch);

    return $body;
  }

  /**
   * @param $ch
   * @return string
   */
  private function getError($ch)
  {
    $info = curl_getinfo($ch);
    $errno = curl_errno($ch);
    $error = '';

    if ($errno) {
      $error .= curl_strerror($errno);
    }

    //Should always be 200
    switch ($info['http_code']) {
      case 0:
      case 200:
        break;

      case 401:
        $error .= __('The Supertext Translation plugin could not login into the Supertext API. Please verify the entered account username and API-Key in the plugin settings.', 'polylang-supertext');
        break;

      default:
        $error .= __('HTTP-Request error occurred. Details: ', 'polylang-supertext') .
          $info['url'] .
          ' returned code ' .
          $info['http_code'];
        break;
    }

    return $error;
  }
}