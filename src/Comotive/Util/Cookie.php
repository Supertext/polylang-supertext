<?php

namespace Comotive\Util;

/**
 * Stop worrying about setcookie/times and cookie names. store everything here in this handy
 * helper class within a cookie that's valid for-virtually-ever. It does only handle basic datatypes
 * @author Michael Sebel <michael@comotive.ch>
 */
class Cookie
{
  /**
   * @var array Local data array
   */
  private static $data = array();
  /**
   * @var int The offset time where to cookie will expire
   */
  private static $offset = 0;

  /**
   * Initializes the empty cookie if it doesn't exist
   */
  private static function initialize()
  {
    if (self::$offset == 0)
      self::$offset = time() + (5 * 365 * 24 * 3600);
    if (!isset($_COOKIE['lbwpcookie'])) {
      setcookie('lbwpcookie',serialize(array()),self::$offset,'/');
    } else {
      self::$data = unserialize(stripslashes($_COOKIE['lbwpcookie']));
    }
    // make sure to delete a possible deprecation cookie (if we're in root)
    if ($_SERVER['SCRIPT_NAME'] != '/' && $_SERVER['SCRIPT_NAME'] != '/index.php')
      setcookie('lbwpcookie',serialize(self::$data),time()-86400);
  }

  /**
   * Pushes a new or overwrites an existing value to the cookie
   * @param string $key the key to store the value in
   * @param string $value the value to be stored
   */
  public static function set($key,$value)
  {
    self::initialize();
    self::$data[$key] = $value;
    setcookie('lbwpcookie',serialize(self::$data),self::$offset,'/');
  }

  /**
   * Gets the information of a specific key
   * @param string $key The key you want to get the value of
   * @param mixed $default default value, if the key is not found
   * @return mixed the value of the stored key
   */
  public static function get($key,$default = false)
  {
    self::initialize();
    if (isset(self::$data[$key])) {
      return self::$data[$key];
    } else {
      return $default;
    }
  }
}