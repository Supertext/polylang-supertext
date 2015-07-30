<?php
use Supertext\Polylang\Helper\Constant;
use Supertext\Polylang\Settings\Page;
use Supertext\Polylang\Api\Multilang;
use Supertext\Polylang\Api\Wrapper;
use Comotive\Util\ArrayManipulation;

/** @var Page $context */
$library = $context->getCore()->getLibrary();
$options = $library->getSettingOption();
$languageMap = ArrayManipulation::forceArray($options[Constant::SETTING_LANGUAGE_MAP]);

// Laod Languages from Polylang to match with supertext api
$htmlLanguageDropdown = '';

// Create the language matcher dropdown
foreach (Multilang::getLanguages() as $language) {
  // Get anonymous wrapper to get languages
  $api = Wrapper::getInstance();
  $stMapping = $api->getLanguageMapping($language->slug);

  $languageDropdown = '';
  foreach ($stMapping as $languageCode => $languageName) {
    $selected = '';
    if ($languageMap[$language->slug] == $languageCode) {
      $selected = ' selected';
    }
    $languageDropdown .= '<option value="' . $languageCode . '"' . $selected . '>' . $languageName . '</option>';
  }
  $languageDropdown = '
    <select name="sel_st_language_' . $language->slug . '" id="sel_st_language_' . $language->slug . '">
      <option value="">Bitte wählen</option>
      ' . $languageDropdown . '
    </select>';

  $htmlLanguageDropdown .= '
  <tr>
    <td>' . $language->name . '</td>
    <td>' . $languageDropdown . '</td>
  </tr>';
}

// Wenn gar keine Mehrsprachigkeit vorhanden ist melden
if (strlen($languageDropdown) > 0) {
  echo '
    <div class="postbox postbox_admin">
      <div class="inside">
        <h3>' . __('Language settings', 'polylang-supertext') . '</h3>
        <table border="0" cellpadding="0" margin="0">
          <tr>
            <td width="200"><b>Polylang</b></td>
            <td><b>Supertext</b></td>
          </tr>
          ' . $htmlLanguageDropdown . '
      </table>
      </div>
    </div>
  ';
} else {
  // Error if no languages are configured
  echo  __('Please configure the languages within the polylang plugin.', 'polylang-supertext');
}
