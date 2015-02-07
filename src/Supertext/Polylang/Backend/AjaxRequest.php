<?php

namespace Supertext\Polylang\Backend;

use Comotive\Util\String;
use Supertext\Polylang\Api\Wrapper;
use Supertext\Polylang\Core;

/**
 * Provided ajax request handlers
 * @package Supertext\Polylang\Backend
 * @author Michael Hadorn <michael.hadorn@blogwerk.com> (inline code)
 * @author Michael Sebel <michael@comotive.ch> (refactoring)
 */
class AjaxRequest
{
  /**
   * @param string $output referenced output variable
   * @param string $state referenced request stated
   * @param string $optional referenced optional information
   * @param string $info additonal info (debug output)
   */
  public static function createOrder(&$output, &$state, &$optional, &$info = '')
  {
    // Call the API for prices
    $options = self::getTranslationOptions();
    $library = Core::getInstance()->getLibrary();
    $data = $library->getTranslationData($options['post_id'], $options['pattern']);
    $post = get_post($options['post_id']);
    $wrapper = $library->getUserWrapper();

    // Create the order
    $order = $wrapper->createOrder(
      $options['source_lang'],
      $options['target_lang'],
      get_bloginfo('name') . ' - ' . $post->post_title,
      $options['product_id'],
      $data,
      SUPERTEXT_POLYLANG_RESOURCE_URL . '/scripts/api/callback.php',
      $post->ID . '-' . md5(Wrapper::REFERENCE_HASH . $post->ID),
      $options['additional_information']
    );

    if (!empty($order->Deadline)) {
      $state = 'success';
      $output = '
        <br><br>
        <div class="updated fade">
          <p>
            ' . __('Die Bestellung wurde erfolgreich abgeschlossen.', 'polylang-supertext') . '
            ' . sprintf(
                  __('Der Text wird bis am %s Uhr übersetzt.', 'polylang-supertext'),
                  date_i18n('D, d. F H:i', strtotime($order->Deadline))
            ) . '
          </p>
        </div>
      ';
    }
  }

  /**
   * This was built by MHA by reference. No time to fix just yet, but it works.
   * @param string $output referenced output variable
   * @param string $state referenced request stated
   * @param string $optional referenced optional information
   */
  public static function getOffer(&$output, &$state, &$optional)
  {
    $optional['requestCounter'] = $_POST['requestCounter'];

    // Call the API for prices
    $options = self::getTranslationOptions();
    $library = Core::getInstance()->getLibrary();
    $data = $library->getTranslationData($options['post_id'], $options['pattern']);
    $wrapper = $library->getUserWrapper();
    // Call for prices
    $pricing = $wrapper->getQuote(
      $options['source_lang'],
      $options['target_lang'],
      $data
    );

    // output html zusammen stellen
    $foundPrice = false;
    $checked = ' checked';

    foreach ($pricing as $title => $item) {
      if (stristr($title, ':') === false) {
        continue;
      }
      $idWithType = str_replace(':', '_', $title);
      // Couldn't remove the onclick easily. Will be fixed/refactored in next release. Works but is semi-geil.
      $output .= '
        <tr onclick="jQuery(\'#rad_translation_type_' . $idWithType . '\').attr(\'checked\', \'checked\');">
          <td>
            <input type="radio" data-currency="' . $pricing['currency'] . '" name="rad_translation_type" id="rad_translation_type_' . $idWithType . '" value="' . $title . '"' . $checked . '>
          </td>
          <td>
            ' . $item['name'] . '
          </td>
          <td align="right" class="ti_deadline">
            ' . date_i18n('D, d. F H:i', strtotime($item['date'])) . '
          </td>
          <td align="right" class="ti_price">
            ' . $pricing['currency'] . ' ' . String::numberFormat($item['price'], 2) . '
          </td>
        </tr>
      ';
      // If found, set true and uncheck
      if (!$foundPrice) {
        $foundPrice = true;
        $checked = '';
      }
    }

    if ($foundPrice) {
      $output = '
      <table border="0" cellpadding="2" cellspacing="0" width="100%">
        <thead>
          <tr>
            <td width="20px">&nbsp;</td>
            <td width="200px"><strong>' . __('Dauer',' polylang-supertext') . '</strong></td>
            <td width="170px" align="right"><strong>' . __('Übersetzung erfolgt bis',' polylang-supertext') . '</strong></td>
            <td width="120px" align="right"><strong>' . __('Preis',' polylang-supertext') . '</strong></td>
          </tr>
        </thead>
        <tbody>
          ' . $output . '
        </tbody>
      </table>';
      $state = 'success';
    } else {
      $output = __('Für diese Übersetzung sind keine Angebote vorhanden.',' polylang-supertext');
      $state = 'no_data';
    }
  }

  /**
   * @return array translation info
   */
  protected function getTranslationOptions()
  {
    $options = array();
    foreach ($_POST as $field_name => $field_value) {
      // Search texts
      if (substr($field_name, 0, 3) == 'to_') {
        $field_name = substr($field_name, 3);
        $options[$field_name] = true;
      }
    }

    // Param zusammenstellen
    $options = array(
      'post_id' => $_POST['post_id'],
      'pattern' => $options,
      'source_lang' => $_POST['source_lang'],
      'target_lang' => $_POST['target_lang'],
      'product_id' => $_POST['rad_translation_type'],
      'additional_information' => stripslashes($_POST['txtComment']),
    );

    return $options;
  }

  /**
   * @param array $data data to be sent in body
   * @param string $state the state
   * @param string $info additional request information
   */
  public static function setJsonOutput($data, $state = 'success', $info = '')
  {
    $json = array(
      'head' => array(
        'status' => $state,
        'info' => $info
      ),
      'body' => $data
    );
    header('Content-Type: application/json');
    echo json_encode($json);
  }
}
