<?php

namespace Supertext\Polylang\Helper;


interface ISettingsAware
{
  public function getSettingsViewBundle();

  public function saveSettings($postData);
}