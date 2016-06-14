<?php

namespace Supertext\Polylang\Helper;


interface ISettingsAware
{
  public function getSettingsViewBundle();

  public function getPostDataKey();

  public function SaveSettings($postData);
}