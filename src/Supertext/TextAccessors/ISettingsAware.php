<?php

namespace Supertext\TextAccessors;

/**
 * Interface ISettingsAware
 * @package Supertext\TextAccessors
 */
interface ISettingsAware
{
  public function getSettingsViewBundle();

  public function saveSettings($postData);
}