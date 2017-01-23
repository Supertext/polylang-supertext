<?php
  if($status['isTranslation'] && $syncTranslationChanges){
?>
    <strong><?php _e('Status', 'polylang-supertext'); ?></strong>
    <p>
      <?php
        $status['isInTranslation'] ?
          _e('This post is currently being modified by Supertext.', 'polylang-supertext') :
          _e('This post is a translation.', 'polylang-supertext'); ?>
      <?php $status['hasChangedSinceLastTranslation'] && !$status['isInTranslation'] ? _e("Its content has been modified and doesn't match the original translation anymore.", 'polylang-supertext') : ''; ?>
    </p>
    <p><button type="button" class="button" <?php echo $status['hasChangedSinceLastTranslation'] && !$status['isInTranslation'] ? '' : 'disabled="disabled"'; ?> onclick="Supertext.Polylang.sendSyncRequest()"><?php _e('Send changes to Supertext', 'polylang-supertext'); ?></button></p>
<?php
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
