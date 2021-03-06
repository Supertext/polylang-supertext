<?php
use Supertext\Helper\Constant;
use Supertext\Api\Wrapper;

/** @var \Supertext\Helper\Library $library */
$languageMappings = $library->getSettingOption(Constant::SETTING_LANGUAGE_MAP);

// Laod Languages from Polylang to match with supertext api
$htmlLanguageDropdown = '';
$languages = $library->getMultilang()->getLanguages();

// Create the language matcher dropdown
foreach ($languages as $language) {
  // Get anonymous wrapper to get languages
  try {
    $stMapping = Wrapper::getLanguageMapping($library->getApiClient(), $language->slug, $language->name);
  } catch (Exception $e) {
    echo '
        <div class="updated fade error">
        <p>
        ' . $e->getMessage() . '
        </p>
        </div>
      ';
    continue;
  }

  $languageDropdown = '';
  foreach ($stMapping as $languageCode => $languageName) {
    $selected = '';
    if (isset($languageMappings[$language->slug]) && $languageMappings[$language->slug] == $languageCode) {
      $selected = ' selected';
    }
    $languageDropdown .= '<option value="' . $languageCode . '"' . $selected . '>' . $languageName . '</option>';
  }
  $languageDropdown = '
    <select name="sel_st_language_' . $language->slug . '" id="sel_st_language_' . $language->slug . '">
      <option value="">' . __('Please select', 'supertext') . '...</option>
      ' . $languageDropdown . '
    </select>';

  $htmlLanguageDropdown .= '
  <tr>
    <td>' . $language->name . '</td>
    <td>' . $languageDropdown . '</td>
  </tr>';
}

// Wenn gar keine Mehrsprachigkeit vorhanden ist melden
if (count($languages) > 0) {
  $translateTool = 'Polylang';
  if ($library->isWPMLActivated()) {
    $translateTool = 'WPML';
  }
  echo '
    <div class="postbox postbox_admin">
      <div class="inside">
        <h3>' . __('Language settings', 'supertext') . '</h3>
        <table border="0" cellpadding="0" margin="0">
        <thead>
        <tr>
            <th width="200">' . $translateTool . '</th>
            <th>Supertext</th>
          </tr>
        </thead>
        <tbody>
        ' . $htmlLanguageDropdown . '
        </tbody>
      </table>
      </div>
    </div>
  ';
} else {
  // Error if no languages are configured
  echo  __('Please configure the languages within the translation plugin.', 'supertext');
}
