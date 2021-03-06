<?php

namespace Supertext\TextAccessors;


use Supertext\Helper\Constant;
use Supertext\Helper\Library;
use Supertext\Helper\TextProcessor;

class VisualComposerTextAccessor extends PostTextAccessor implements IAddDefaultSettings
{
  /**
   * @var Library library
   */
  private $library;

  /**
   * @param TextProcessor $textProcessor
   * @param $library
   */
  public function __construct($textProcessor, $library)
  {
    parent::__construct($textProcessor);

    $this->library = $library;
  }

  /**
   * Adds default settings
   */
  public function addDefaultSettings()
  {
    $shortcodeSettings = $this->library->getSettingOption(Constant::SETTING_SHORTCODES);

    $shortcodeSettings['vc_[^\s|\]]+'] = array(
      'content_encoding' => null,
      'attributes' => array(
        array('name' => 'text', 'encoding' => ''),
        array('name' => 'title', 'encoding' => ''),
      )
    );

    $shortcodeSettings['vc_raw_html'] = array(
      'content_encoding' => 'rawurl,base64',
      'attributes' => array()
    );

    $this->library->saveSettingOption(Constant::SETTING_SHORTCODES, $shortcodeSettings);
  }
}