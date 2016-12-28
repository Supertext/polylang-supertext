<?php
use Comotive\Util\Date;
use Supertext\Polylang\Helper\PostMeta;
?>
<strong><?php _e('Status', 'polylang-supertext'); ?></strong>
<p>
  <table>
  <tr>
    <td><?php _e('In translation', 'polylang-supertext'); ?></td>
    <td><?php $postMeta->is(PostMeta::IN_TRANSLATION) ? _e('yes', 'polylang-supertext') : _e('no', 'polylang-supertext'); ?></td>
  </tr>
  <tr>
    <td>Translated from</td>
    <td><?php _e($library->mapLanguage($postMeta->get(PostMeta::SOURCE_LANGUAGE)), 'polylang-supertext-langs'); ?></td>
  </tr>

</table>
</p>
<strong><?php _e('Log', 'polylang-supertext'); ?></strong>
<div class="sttr-log-container">
  <?php

  foreach ($logEntries as $entry) {
    $datetime = '
            ' . Date::getTime(Date::EU_DATE, $entry['datetime']) . ',
            ' . Date::getTime(Date::EU_TIME, $entry['datetime']) . '
          ';
    echo '<p><strong>' . $datetime . '</strong>: ' . $entry['message'] . '</p>';
  }
  ?>
</div>
