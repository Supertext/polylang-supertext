<?php
/**
 * Created by PhpStorm.
 * User: heinrich
 * Date: 14.10.15
 * Time: 09:31
 */
namespace Supertext\Polylang\Helper;


/**
 * The ACF Custom Field provider
 * @package Supertext\Polylang\Helper
 */
interface ICustomFieldProvider
{
  public function getPluginName();

  /**
   * @return array multidimensional list of custom fields defintions
   */
  public function getCustomFieldDefinitions();
}