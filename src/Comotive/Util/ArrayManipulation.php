<?php

namespace Comotive\Util;

/**
 * Utility functions to work with arrays or modify or sort an array
 * @author Michael Sebel <michael@comotive.ch>
 */
class ArrayManipulation
{
  /**
   * Sorts an associative array by its "count" field
   * @param array $a the first author to compare
   * @param array $b the second author to compare
   * @return int usort return value 0, 1 or -1
   */
  public static function sortByCount($a, $b)
  {
    if ($a['count'] == $b['count']) {
      return 0;
    } else {
      if ($a['count'] > $b['count']) {
        return -1;
      } else {
        return 1;
      }
    }
  }

  /**
   * Create a new array with unmaintained primitive indexes, intval'ing every object
   * @param array $values
   * @return array the new values array
   */
  public static function getIntArray($values)
  {
    $newValues = array();
    foreach ($values as $value) {
      $newValues[] = intval($value);
    }

    return $newValues;
  }

  /**
   * A-Z array, can be used to stuff objects in to A-Z containers
   * @param bool $lowerCase tells if the assoc keys should be lower case chars
   * @return array an assoc array with A-Z as keys. contains an empty array for every letter
   */
  public static function getAtoZArray($lowerCase = false)
  {
    $result = array();
    $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    if ($lowerCase) {
      $letters = strtolower($letters);
    }

    for ($i = 0; $i < strlen($letters); ++$i) {
      $result[$letters[$i]] = array();
    }

    return $result;
  }

  /**
   * forces the $value to be an array, if not it returns an empty array
   * @param mixed $value input value
   * @return array $value if array, or an empty array
   */
  public static function forceArray($value)
  {
    if (!is_array($value)) {
      $value = array();
    }

    return $value;
  }

  /**
   * Merges an array and its subarrays
   * @return array
   */
  public static function deepMerge()
  {
    switch (func_num_args()) {
      case 0: // Nothing to merge
        return array();
        break;

      case 1: //Only one array to merge, so we return it
        return func_get_arg(0);
        break;

      case 2:
        //Here starts the magic
        $result = func_get_arg(0);
        foreach(func_get_arg(1) as $key=>$value) { //Go through every key of the second array
          if (is_array($value)) { // If the value is an array, that make a recursive call
            $result[$key] = self::deepMerge($result[$key], $value);
          }
          else { //Overwrite the value
            $result[$key] = $value;
          }
        }
        return $result;
        break;

      default: //There are more than two arrays so we call that function recursivly
        $result = func_get_arg(0);
        $max = func_num_args();
        for($i=1; $i<$max; $i++) {
          $arg = func_get_arg($i);
          $result = self::deepMerge($result, $arg);
        }
        return $result;
        break;
    }
  }
} 