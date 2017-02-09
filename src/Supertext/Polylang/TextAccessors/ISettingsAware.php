<?php

namespace Supertext\Polylang\TextAccessors;

/**
 * Interface ISettingsAware
 * @package Supertext\Polylang\TextAccessors
 */
interface ISettingsAware
{
  public function getSettingsViewBundle();

  public function saveSettings($postData);
}