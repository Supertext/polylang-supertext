<?php

namespace Supertext\Polylang\Helper;

/**
 * This class wrapps all constant configurations
 * @package Supertext\Polylang\Helper
 * @author Michael Sebel <michael@comotive.ch>
 */
class Constant
{
  /**
   * @var string the used API base url
   */
  const API_URL = self::LIVE_API;
  /**
   * @var string development api endpoints
   */
  const DEV_API = 'https://dev.supertext.ch/api/v1/';
  /**
   * @var string live api endpoints
   */
  const LIVE_API = 'https://www.supertext.ch/api/v1/';
  /**
   * @var string the settings option
   */
  const SETTINGS_OPTION = 'polylang_supertext_settings';
  /**
   * @var string name of the subsetting for user mapping
   */
  const SETTING_USER_MAP = 'userMap';
  /**
   * @var string name of the subsetting for language mapping
   */
  const SETTING_LANGUAGE_MAP = 'languageMap';
  /**
   * @var string name of the subsetting for language mapping
   */
  const SETTING_WORKING = 'isWorking';
  /**
   * @var string the default supertext api user
   */
  const DEFAULT_API_USER = 'public_user';
}