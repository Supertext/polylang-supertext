<?php
use Supertext\Polylang\Helper\Constant;
use Comotive\Util\ArrayManipulation;

global $shortcode_tags;

$library = $context->getCore()->getLibrary();
$options = $library->getSettingOption();
$savedShortcodes = isset($options[Constant::SETTING_SHORTCODES]) ? ArrayManipulation::forceArray($options[Constant::SETTING_SHORTCODES]) : array();

?>
<div class="postbox postbox_admin">
  <div class="inside">
    <h3><?php _e('Translatable Shortcodes', 'polylang-supertext'); ?></h3>
    <table id="tblShortcodes">
      <thead>
      <tr>
        <th colspan="2"><?php _e('Shortcode', 'polylang-supertext'); ?></th>
        <th><?php _e('Attributes', 'polylang-supertext'); ?></th>
      </tr>
      </thead>
      <tbody>
      <?php
      foreach ($shortcode_tags as $key => $function) {
        $checkboxId = 'chkbx'.$key;
        $savedShortcodeAttributes = array();
        $checked = '';

        if(isset($savedShortcodes[$key])){
          $savedShortcodeAttributes = $savedShortcodes[$key];
          $checked = 'checked="checked"';
        }

        $inputs = count($savedShortcodeAttributes) > 0 ? '' : '<input type="text" class="shortcode-attribute-input" name="shortcodes['.$key.'][attributes][]" value="" />';

        foreach ($savedShortcodeAttributes as $savedShortcodeAttribute) {
          $inputs .= '<input type="text" class="shortcode-attribute-input" name="shortcodes['.$key.'][attributes][]" value="'.$savedShortcodeAttribute.'" />';

        }

        echo '
        <tr>
          <td><input type="checkbox" id="'.$checkboxId.'" class="shortcode-select-input" name="shortcodes['.$key.'][selected]" value="1" '.$checked.' /></td>
          <td><label for="'.$checkboxId.'">'.$key.'</label></td>
          <td>'.$inputs.'<input type="button" value="+" class="button button-highlighted shortcode-attribute-add-input" /></td>
        </tr>';
      }
      ?>
      </tbody>
    </table>
  </div>
</div>
<?php

$content = '<div class="polylang-supertext-shortcode" name="caption">
	<input type="hidden" name="id" value="attachment_145" />
	<input type="hidden" name="align" value="alignnone" />
	<input type="hidden" name="width" value="300" />
	<div name="enclosed">
		<a href="http://192.168.0.45/wp-content/uploads/2015/10/mountain4.jpg">
			<img src="http://192.168.0.45/wp-content/uploads/2015/10/mountain4-300x188.jpg" alt="test3" width="300" height="188" class="size-medium wp-image-145" />
		</a> test3
	</div>
</div>
<div class="polylang-supertext-shortcode" name="vc_raw_html">
	<div name="enclosed">JTNDJTIxLS0lMjBCZWdpbiUyME1haWxDaGltcCUyMFNpZ251cCUyMEZvcm0lMjAtLSUzRSUwQSUzQ2RpdiUyMGlkJTNEJTIybWNfZW1iZWRfc2lnbnVwJTIyJTNFJTBBJTNDZm9ybSUyMGFjdGlvbiUzRCUyMiUyRiUyRmVmbGl6emVyLnVzOS5saXN0LW1hbmFnZS5jb20lMkZzdWJzY3JpYmUlMkZwb3N0JTNGdSUzRGQwMWMwZWY5ZGMzY2Y2ZWJkMGUxZjA1MzMlMjZhbXAlM0JpZCUzRDQ5Yzk1Njg1M2UlMjIlMjBtZXRob2QlM0QlMjJwb3N0JTIyJTIwaWQlM0QlMjJtYy1lbWJlZGRlZC1zdWJzY3JpYmUtZm9ybSUyMiUyMG5hbWUlM0QlMjJtYy1lbWJlZGRlZC1zdWJzY3JpYmUtZm9ybSUyMiUyMGNsYXNzJTNEJTIydmFsaWRhdGUlMjIlMjB0YXJnZXQlM0QlMjJfYmxhbmslMjIlMjBub3ZhbGlkYXRlJTNFJTBBJTIwJTIwJTIwJTIwJTNDZGl2JTIwaWQlM0QlMjJtY19lbWJlZF9zaWdudXBfc2Nyb2xsJTIyJTNFJTBBJTA5JTNDaDIlM0VOZXdzbGV0dGVyJTIwQW5tZWxkdW5nJTNDJTJGaDIlM0UlMEElM0NkaXYlMjBjbGFzcyUzRCUyMm1jLWZpZWxkLWdyb3VwJTIyJTNFJTBBJTA5JTNDbGFiZWwlMjBmb3IlM0QlMjJtY2UtRU1BSUwlMjIlM0VFbWFpbCUyMEFkcmVzc2UlMjAlM0MlMkZsYWJlbCUzRSUwQSUwOSUzQ2lucHV0JTIwdHlwZSUzRCUyMmVtYWlsJTIyJTIwdmFsdWUlM0QlMjIlMjIlMjBuYW1lJTNEJTIyRU1BSUwlMjIlMjBjbGFzcyUzRCUyMnJlcXVpcmVkJTIwZW1haWwlMjIlMjBpZCUzRCUyMm1jZS1FTUFJTCUyMiUzRSUwQSUzQyUyRmRpdiUzRSUwQSUwOSUzQ2RpdiUyMGlkJTNEJTIybWNlLXJlc3BvbnNlcyUyMiUyMGNsYXNzJTNEJTIyY2xlYXIlMjIlM0UlMEElMDklMDklM0NkaXYlMjBjbGFzcyUzRCUyMnJlc3BvbnNlJTIyJTIwaWQlM0QlMjJtY2UtZXJyb3ItcmVzcG9uc2UlMjIlMjBzdHlsZSUzRCUyMmRpc3BsYXklM0Fub25lJTIyJTNFJTNDJTJGZGl2JTNFJTBBJTA5JTA5JTNDZGl2JTIwY2xhc3MlM0QlMjJyZXNwb25zZSUyMiUyMGlkJTNEJTIybWNlLXN1Y2Nlc3MtcmVzcG9uc2UlMjIlMjBzdHlsZSUzRCUyMmRpc3BsYXklM0Fub25lJTIyJTNFJTNDJTJGZGl2JTNFJTBBJTA5JTNDJTJGZGl2JTNFJTIwJTIwJTIwJTIwJTNDJTIxLS0lMjByZWFsJTIwcGVvcGxlJTIwc2hvdWxkJTIwbm90JTIwZmlsbCUyMHRoaXMlMjBpbiUyMGFuZCUyMGV4cGVjdCUyMGdvb2QlMjB0aGluZ3MlMjAtJTIwZG8lMjBub3QlMjByZW1vdmUlMjB0aGlzJTIwb3IlMjByaXNrJTIwZm9ybSUyMGJvdCUyMHNpZ251cHMtLSUzRSUwQSUyMCUyMCUyMCUyMCUzQ2RpdiUyMHN0eWxlJTNEJTIycG9zaXRpb24lM0ElMjBhYnNvbHV0ZSUzQiUyMGxlZnQlM0ElMjAtNTAwMHB4JTNCJTIyJTNFJTNDaW5wdXQlMjB0eXBlJTNEJTIydGV4dCUyMiUyMG5hbWUlM0QlMjJiX2QwMWMwZWY5ZGMzY2Y2ZWJkMGUxZjA1MzNfNDljOTU2ODUzZSUyMiUyMHRhYmluZGV4JTNEJTIyLTElMjIlMjB2YWx1ZSUzRCUyMiUyMiUzRSUzQyUyRmRpdiUzRSUwQSUyMCUyMCUyMCUyMCUzQ2RpdiUyMGNsYXNzJTNEJTIyY2xlYXIlMjIlM0UlM0NpbnB1dCUyMHR5cGUlM0QlMjJzdWJtaXQlMjIlMjB2YWx1ZSUzRCUyMlN1YnNjcmliZSUyMiUyMG5hbWUlM0QlMjJzdWJzY3JpYmUlMjIlMjBpZCUzRCUyMm1jLWVtYmVkZGVkLXN1YnNjcmliZSUyMiUyMGNsYXNzJTNEJTIyYnV0dG9uJTIyJTNFJTNDJTJGZGl2JTNFJTBBJTIwJTIwJTIwJTIwJTNDJTJGZGl2JTNFJTBBJTNDJTJGZm9ybSUzRSUwQSUzQyUyRmRpdiUzRSUwQSUwQSUzQyUyMS0tRW5kJTIwbWNfZW1iZWRfc2lnbnVwLS0lM0U=
	</div>
</div>
<div class="polylang-supertext-shortcode" name="vc_row">
	<div name="enclosed">
		<div class="polylang-supertext-shortcode" name="vc_column">
			<div name="enclosed">
				<div class="polylang-supertext-shortcode" name="vc_column_text">
					<div name="enclosed">I am text block. Click edit button to change this text. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut elit tellus, luctus nec ullamcorper mattis, pulvinar dapibus leo.</div>
				</div>
				<div class="polylang-supertext-shortcode" name="vc_row_inner">
					<div name="enclosed">
						<div class="polylang-supertext-shortcode" name="vc_column_inner">
							<div name="enclosed">
								<div class="polylang-supertext-shortcode" name="vc_custom_heading"/>
								<div class="polylang-supertext-shortcode" name="vc_column_text">
									<div name="enclosed">Som eother test.</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>';

$result = $library->putShortcodesBack($content);

echo '<textarea>';
echo $result;
echo '</textarea>';

?>