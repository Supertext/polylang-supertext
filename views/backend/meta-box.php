<?php

if ($status['isInTranslation']) {
  echo '<strong>'. __('Status', 'polylang-supertext') . '</strong>';
  echo '<p>' . __('This post is being translated.', 'polylang-supertext') . '</p>';
} else if($status['isTranslation']) {
  echo '<strong>'. __('Status', 'polylang-supertext') . '</strong>';
  echo '<p>';
  echo __('This post is a translation.', 'polylang-supertext');
  echo $status['hasChangedSinceLastTranslation'] ? __(' It has been modified and doesn\'t match the original translation.', 'polylang-supertext') : '';
  echo '</p>';
  $disabled = $status['hasChangedSinceLastTranslation'] ? '' : 'disabled="disabled"';
  echo '<p><button type="button" class="button" ' . $disabled . ' onclick="Supertext.Polylang.sendSyncRequest()">'. __('Send changes to Supertext', 'polylang-supertext') .' </button></p>';
}

?>
<strong><?php _e('Log', 'polylang-supertext'); ?></strong>
<div class="sttr-log-container">
  <?php
  use Comotive\Util\Date;
  foreach ($logEntries as $entry) {
    $datetime = '
            ' . Date::getTime(Date::EU_DATE, $entry['datetime']) . ',
            ' . Date::getTime(Date::EU_TIME, $entry['datetime']) . '
          ';
    echo '<p><strong>' . $datetime . '</strong>: ' . $entry['message'] . '</p>';
  }
  ?>
</div>
