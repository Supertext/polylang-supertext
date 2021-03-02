<?php
  if($status['isTranslation'] && $syncTranslationChanges){
?>
    <strong><?php _e('Status', 'supertext'); ?></strong>
    <p>
      <?php
        $status['isInTranslation'] ?
          _e('This post is currently being modified by Supertext.', 'supertext') :
          _e('This post is a translation.', 'supertext'); ?>
      <?php $status['hasChangedSinceLastTranslation'] && !$status['isInTranslation'] ? _e("Its content has been modified and doesn't match the original translation anymore.", 'supertext') : ''; ?>
    </p>
    <p><button type="button" class="button" <?php echo $status['hasChangedSinceLastTranslation'] && !$status['isInTranslation'] ? '' : 'disabled="disabled"'; ?> onclick="Supertext.Interface.sendSyncRequest()"><?php _e('Send changes to Supertext', 'supertext'); ?></button></p>
<?php
  }
?>
<strong><?php _e('Log', 'supertext'); ?></strong>
<div class="sttr-log-container">
  <?php
  foreach ($logEntries as $entry) {
    echo '<p><strong>' . date_i18n(__('M j, Y @ H:i:s', 'supertext'), $entry['datetime']) . '</strong>: ' . $entry['message'] . '</p>';
  }
  ?>
</div>
