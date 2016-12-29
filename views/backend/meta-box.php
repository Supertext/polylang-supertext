<strong><?php _e('Status', 'polylang-supertext'); ?></strong>
<p>
<?php
if(!$status['isTranslation']){
  _e('This post is not a translation of another post', 'polylang-supertext');
}else if($status['isInTranslation']) {
  printf(__('This post is being translated from %s', 'polylang-supertext'), $status['sourceLanguage']);
}else{
  printf(__('This post was translated from %s', 'polylang-supertext'), $status['sourceLanguage']);
}
?>
</p>

<?php
if($status['isTranslation'] && !$status['isInTranslation']){
  $disabled = $status['hasChangedSinceLastTranslation'] ? '' : 'disabled';
  echo '<p><button type="button" class="button" '.$disabled.' onclick="Supertext.Polylang.sendPostChanges()">Send changes to Supertext</button></p>';
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
