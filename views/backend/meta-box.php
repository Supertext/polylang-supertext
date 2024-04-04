<strong><?php _e('Log', 'supertext'); ?></strong>
<div class="sttr-log-container">
  <?php
  foreach ($logEntries as $entry) {
    echo '<p><strong>' . date_i18n(__('M j, Y @ H:i:s', 'supertext'), $entry['datetime']) . '</strong>: ' . $entry['message'] . '</p>';
  }
  ?>
</div>
