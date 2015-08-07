<?php

namespace Comotive\Helper;

use wpdb;
use Comotive\Util\String;
use Comotive\Util\Date;

/**
 * This helper can be instantiated to add one or more metaboxes to a posttype,
 * adding fields of different types to it, and it handels saving for these
 * field automatically. Displaying is handled globally by a CSS singleton.
 * @author Michael Sebel <michael.sebel@blogwerk.com>
 */
class Metabox
{
  /**
   * @var wpdb the database connection if needed
   */
  protected $wpdb;
  /**
   * @var array the fields to work with
   */
  protected $fields = array();
  /**
   * @var array used while saving. Stores the error messages
   */
  protected $errors = array();
  /**
   * @var string the identifier, to get the metabox instance by post type
   */
  protected $posttype = '';
  /**
   * @var array an array of all metabox helper instances
   */
  protected static $instances = array();
  /**
   * @var array the templates fille in templates.php (constructor)
   */
  protected $templates = array();
  /**
   * @var int the version of this class
   */
  const VERSION = 7;

  /**
   * Creates the helper object
   * @throws Exception if the id is already used
   */
  protected function __construct($posttype)
  {
    global $wpdb;
    $this->posttype = $posttype;
    $this->wpdb = $wpdb;

    $this->setTemplates();

    // If this is the first instance, register js/css files for enqueuement
    if (count(self::$instances) == 0) {
      $this->enqueueAssets();
    }

    // Register saving the data when a post is saved
    add_action('save_post_' . $posttype, array($this, 'saveMetabox'));
  }

  /**
   * Set the default tempaltes
   */
  protected function setTemplates()
  {
    $this->templates['description'] = '
      <div class="mbh-item-normal">
        <div class="mbh-title"><label for="{fieldId}">{title}</label></div>
        <div class="mbh-field {fieldClass}">
          <div class="mbh-input">{input}</div>
          <div class="mbh-description"><label for="{fieldId}">{description}</label></div>
        </div>
      </div>
    ';

    $this->templates['description_full'] = '
      <div class="mbh-item-full">
        <div class="mbh-title"><label for="{fieldId}">{title}</label></div>
        <div class="mbh-field {fieldClass}">
          <div class="mbh-input">{input}</div>
          <div class="mbh-description"><label for="{fieldId}">{description}</label></div>
        </div>
      </div>
    ';

    $this->templates['short'] = '
      <div class="mbh-item-normal">
        <div class="mbh-title"><label for="{fieldId}">{title}</label></div>
        <div class="mbh-field {fieldClass}">
          <div class="mbh-input">{input}</div>
        </div>
      </div>
    ';

    $this->templates['short_full'] = '
      <div class="mbh-item-full">
        <div class="mbh-title"><label for="{fieldId}">{title}</label></div>
        <div class="mbh-field {fieldClass}">
          <div class="mbh-input">{input}</div>
        </div>
      </div>
    ';

    $this->templates['short_input_list'] = '
      <div class="mbh-item-normal list">
        <div class="mbh-title"><label for="{fieldId}">{title}</label></div>
        <div class="mbh-field {fieldClass}">
          <div class="mbh-input" style="{fieldStyle}">{input}</div>
        </div>
      </div>
    ';

    $this->templates['empty'] = '
      <div class="mbh-item-normal">{html}</div>
    ';

    $this->templates['emptyFull'] = '
      <div class="mbh-item-full">{html}</div>
    ';

    $this->templates['media'] = '
      <div class="mbh-item-normal">
        <div class="mbh-title"><label for="{fieldId}">{title}</label></div>
        <div class="mbh-field {fieldClass}">
          {media}
        </div>
      </div>
    ';

  }

  /**
   * Enqueue the assets if needed (only on edit dialogs)
   */
  protected function enqueueAssets()
  {
    // Only include in post.php and post-new.php
    if (String::startsWith($_SERVER['SCRIPT_NAME'], '/wp-admin/post')) {
      wp_enqueue_script(
        'metabox-helper-backend-js',
        '/wp-content/plugins/lbwp/resources/js/metabox-helper.js',
        array('jquery'),
        self::VERSION
      );
      wp_enqueue_script(
        'metabox-helper-image-uploader-js',
        '/wp-content/plugins/lbwp/resources/js/image-uploader.js',
        array('jquery', 'media-upload'/*, 'media-views'*/),
        self::VERSION
      );
      wp_enqueue_style(
        'metabox-helper-backend-css',
        '/wp-content/plugins/lbwp/resources/css/metabox-helper.css',
        array(),
        self::VERSION
      );

      add_action('admin_enqueue_scripts', function () {
        wp_enqueue_script('chosen-sortable-js', plugins_url('/js/chosen/chosen.sortable.jquery.js', __DIR__), array('chosen-js'));
        wp_enqueue_script('chosen-js');
        wp_enqueue_style('chosen-css');
      });
    }
  }

  /**
   * @param string $id the id of the metabox (must be unique within the helper)
   * @param string $title the title of the metabox
   * @param string $context the context (normal, advanced (default))
   * @param string $priority the priority (default, high, core)
   */
  public function addMetabox($id, $title, $context = 'advanced', $priority = 'default')
  {
    add_meta_box($this->posttype . '__' . $id, $title, array($this, 'displayBox'), $this->posttype, $context, $priority, $id);
  }

  /**
   * Callback used internally to display a metabox with a certain boxId
   * @param WP_Post the post object on which the metabox is placed
   * @param string $id the boxId used at addMetabox
   */
  public function displayBox($post, $id)
  {
    $html = '';

    // Error messages?
    if (is_array($_SESSION['metabox_errors_' . $this->posttype][$id['args']])) {
      $html .= '<ul class="mbh-error-list">';
      foreach ($_SESSION['metabox_errors_' . $this->posttype][$id['args']] as $message) {
        $html .= '<li>' . $message . '</li>';
        unset($_SESSION['metabox_errors_' . $this->posttype][$id['args']]);
      }
      $html .= '</ul>';
    }

    // Display the fields
    if (is_array($this->fields[$id['args']])) {
      foreach ($this->fields[$id['args']] as $field) {
        $field['args']['post'] = $post;
        $html .= call_user_func($field['display'], $field['args']);
      }
    } else {
      $html .= '<p>Dieser Metabox sind noch keine Felder zugewiesen.</p>';
    }

    echo $html;
  }

  /**
   * Save all registered fields
   * @param int $postId the post it that's being saved
   */
  public function saveMetabox($postId)
  {

    if (
      defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ||
      (isset($_REQUEST['action']) && $_REQUEST['action'] == 'inline-save') ||
      isset($_REQUEST['bulk_edit'])
    ) {
      return;
    }

    // If we save a revision, change the revision post id to the original, to make the preview work
    if ($origPostId = wp_is_post_revision($postId)) {
      $postId = $origPostId;
    }

    foreach ($this->fields as $box => $fields) {
      foreach ($fields as $field) {
        callUserFunctionWithSafeArguments($field['save'], array($postId, $field, $box));
      }
    }

    if (count($this->errors) > 0) {
      $_SESSION['metabox_errors_' . $this->posttype] = $this->errors;
    }
  }

  /**
   * @param string $boxId the box id in which to store the error
   * @param string $text the error message
   */
  public function addError($boxId, $text)
  {
    if (!isset($this->errors[$boxId])) {
      $this->errors[$boxId] = array();
    }
    $this->errors[$boxId][] = $text;
  }

  /**
   * @param string $key the key to use as meta key in the postmeta table
   * @param string $boxId the $id given in addMetabox
   * @param array $args the arguments to provide to the callbacks
   * @param callable $displayCb is called on displaying the metabox
   * @param callable $saveCb is called on saving the metabox information
   */
  public function addField($key, $boxId, array $args, $displayCb, $saveCb)
  {
    // Create a namespace for the box, if not exists
    if (!isset($this->fields[$boxId])) {
      $this->fields[$boxId] = array();
    }

    // Put the key into the args and save the field for later display/save
    $args['key'] = $key;
    $this->fields[$boxId][] = array(
      'key' => $key,
      'args' => $args,
      'display' => $displayCb,
      'save' => $saveCb
    );
  }

  /**
   * Helper for adding an input text field (one liner)
   * @param string $boxId the metabox to display the field
   * @param string $text the heading text
   * @param array $args additional arguments: description, width
   */
  public function addHeading($boxId, $text, $args = array())
  {
    $html = '<h4>' . $text . '</h4>';
    $key = md5(microtime());
    $this->addHtml($key, $boxId, $html, $args);
  }

  /**
   * Helper for adding an input text field (one liner)
   * @param string $boxId the metabox to display the field
   * @param string $text the heading text
   * @param array $args additional arguments: description, width
   */
  public function addParagraph($boxId, $text, $args = array())
  {
    $html = '<p>' . $text . '</p>';
    $key = md5(microtime());
    $this->addHtml($key, $boxId, $html, $args);
  }

  /**
   * Helper for adding an input text field (one liner)
   * @param string $key the key to store the metadata in
   * @param string $boxId the metabox to display the field
   * @param string $html HTML code to display
   * @param array $args additional arguments: description, width
   */
  public function addHtml($key, $boxId, $html, $args = array())
  {
    $args['html'] = $html;
    $this->addField($key, $boxId, $args, array($this, 'displayHtml'), array($this, 'saveVoid'));
  }

  /**
   * Helper for adding an input text field (one liner)
   * @param string $key the key to store the metadata in
   * @param string $boxId the metabox to display the field
   * @param string $title the title of the field besides the input
   * @param array $args additional arguments: description, width
   */
  public function addInputText($key, $boxId, $title, $args = array())
  {
    // Add the title as an argument
    $args['title'] = $title;

    // Add the field
    $this->addField(
      $key, $boxId, $args,
      array($this, 'displayInputText'),
      array($this, 'saveTextField')
    );
  }

  /**
   * Helper for adding an input text field (one liner)
   * @param string $key the key to store the metadata in
   * @param string $boxId the metabox to display the field
   * @param array $postTypes the title of the field besides the input
   * @param array $args additional arguments: description, width
   */
  public function addAssignPostsField($key, $boxId, $postTypes = array('post'), $args = array())
  {
    $args['types'] = $postTypes;

    // Add the field
    $this->addField(
      $key, $boxId, $args,
      array($this, 'displayAssignPostsField'),
      array($this, 'saveAssignPostsField')
    );

    // Ajax callback for auto completion
    add_action('wp_ajax_mbhAssignPostsData', function ($args) use ($args) {
      self::ajaxAssignPostsData($args['types']);
    });
  }

  /**
   * Helper for adding an input text field (one liner)
   * @param string $key the key to store the metadata in
   * @param string $boxId the metabox to display the field
   * @param string $title the title of the field besides the input
   * @param string $format the format in which the field should be saved (Date::*_DATE*)
   * @param array $args additional arguments: description, showTime = false
   */
  public function addDate($key, $boxId, $title, $format = Date::EU_DATE, $args = array())
  {
    // Add the title as an argument
    $args['title'] = $title;
    $args['format'] = $format;

    // Add the field
    $this->addField(
      $key, $boxId, $args,
      array($this, 'displayDateField'),
      array($this, 'saveDateField')
    );
  }

  /**
   * Helper for adding an input text field (one liner)
   * @param string $taxonomy the key to store the metadata in
   * @param string $boxId the metabox to display the field
   * @param string $title the title of the field besides the input
   * @param array $args additional arguments: description, display (radio|checkbox), numberOfVisibleTerms
   */
  public function addTaxonomy($taxonomy, $boxId, $title, $args = array())
  {
    // Add the title as an argument
    $args['title'] = $title;

    $key = 'taxonomy_' . $taxonomy;

    $args['taxonomy'] = $taxonomy;

    $args = wp_parse_args($args, array(
      'display' => 'checkbox',
      'numberOfVisibleTerms' => 5,
      'sortable' => false, // chosen option
    ));

    // Add the field
    $this->addField(
      $key, $boxId, $args,
      array($this, 'displayTaxonomy'),
      array($this, 'saveTaxonomy')
    );
  }

  /**
   * Helper for adding a checkbox input field (one liner)
   * @param string $key the key to store the metadata in
   * @param string $boxId the metabox to display the field
   * @param string $title the title of the field besides the input
   * @param array $args additional arguments: description, width
   */
  public function addCheckbox($key, $boxId, $title, $args = array())
  {
    // Add the title as an argument
    $args['title'] = $title;
    $args['template'] = 'short';

    // Add the field
    $this->addField(
      $key, $boxId, $args,
      array($this, 'displayCheckbox'),
      array($this, 'saveCheckboxField')
    );
  }

  /**
   * Helper for adding a media button upload
   *
   * additional arguments:
   *  - uploaderButtonText
   *  - removeMediaText
   *  - mediaContainerCallback
   *
   * @param string $key the key to store the metadata in
   * @param string $boxId the metabox to display the field
   * @param string $title the title of the field besides the input
   * @param array $args additional arguments: description, width
   */
  public function addMediaUploadField($key, $boxId, $title, $args = array())
  {
    $args['title'] = $title;
    if (!isset($args['uploaderButtonText'])) {
      $args['uploaderButtonText'] = __('Bild wählen');
    }
    if (!isset($args['removeMediaText'])) {
      $args['removeMediaText'] = __('Bild entfernen');
    }
    if (!isset($args['mediaContainerCallback']) || !is_callable($args['mediaContainerCallback'])) {
      $args['mediaContainerCallback'] = array($this, 'mediaContainerCallback');
    }
    $args['class'] .= 'wide';

    // Add the field
    $this->addField(
      $key, $boxId, $args,
      array($this, 'displayMediaUploadField'),
      array($this, 'saveTextField')
    );
  }

  /**
   * Add a chosen.js Dropdown. Any options in http://harvesthq.github.io/chosen/options.html can
   * be passed directly in the $args parameter.
   * The 'multiple' = (true|false) option will also add the [] to the select name and
   * save the meta_values as an array (with preserved order).
   *
   * You can provide items with
   *  - 'items' => array($value => array('title' => $title, 'data' => array('url' => $editUrl )))
   * or with
   *  - a callable 'itemsCallback' returning the same structure. This becomes necessary,
   * if the data is not yet available during instantiation, because it has to be before the
   * 'save_post' hook.
   *
   * @param $key
   * @param $boxId
   * @param $title
   * @param array $args
   */
  public function addDropdown($key, $boxId, $title, $args = array())
  {
    $args['title'] = $title;
    if (!isset($args['sortable']) && isset($args['multiple']) && $args['multiple'] == true) {
      $args['sortable'] = true;
    }

    $saveCallback = array($this, 'saveDropdown');
    if (isset($args['saveCallback']) && is_callable($args['saveCallback'])) {
      $saveCallback = $args['saveCallback'];
    }

    // Add the field
    $this->addField(
      $key, $boxId, $args,
      array($this, 'displayDropdown'),
      $saveCallback
    );
  }

  /**
   * Helper for adding an input text area
   * @param string $key the key to store the metadata in
   * @param string $boxId the metabox to display the field
   * @param string $title the title of the field besides the input
   * @param int $height the area height in pixel (without px)
   * @param array $args additional arguments: description, width (Default 100%)
   */
  public function addTextarea($key, $boxId, $title, $height, $args = array())
  {
    // Add the title as an argument
    $args['title'] = $title;
    $args['height'] = $height;

    // Add the field
    $this->addField(
      $key, $boxId, $args,
      array($this, 'displayTextarea'),
      array($this, 'saveTextField')
    );
  }

  /**
   * Helper for adding a wysiwyg editor
   * @param string $key the key to store the metadata in
   * @param string $boxId the metabox to display the field
   * @param string $title the title of the field besides the input
   * @param int $rows number of rows, at least 5, recommended 10
   * @param array $args additional arguments: description, width (Default 100%)
   */
  public function addEditor($key, $boxId, $title, $rows, $args = array())
  {
    // Validate number of rows
    if ($rows < 5) {
      $rows = 5;
    }

    // Add the title as an argument
    $args['title'] = $title;
    $args['rows'] = $rows;

    // add a identifying (non-unique) css class
    $args['class'] .= ' editor ';

    // Add the field
    $this->addField(
      $key, $boxId, $args,
      array($this, 'displayEditor'),
      array($this, 'saveTextField')
    );
  }

  /**
   * Inline callback to save a normal textfield
   * @param int $postId the id of the post to save to
   * @param array $field all the fields information
   * @param string $boxId the metabox id
   */
  public function saveTextField($postId, $field, $boxId)
  {
    // Validate and save the field
    $value = $_POST[$postId . '_' . $field['key']];
    $value = stripslashes(trim($value));

    // Check if the field is required
    if (isset($field['args']['required']) && $field['args']['required'] && strlen($value) == 0) {
      $this->addError($boxId, 'Bitte füllen Sie das Feld "' . $field['args']['title'] . '" aus.');
      return;
    }

    // Save the meta data to the database
    update_post_meta($postId, $field['key'], $value);
  }

  /**
   * save the taxonomy terms (or delete them)
   * @param int $postId
   * @param array $field
   * @param string $boxId
   */
  public function saveTaxonomy($postId, $field, $boxId)
  {
    $taxonomy = $field['args']['taxonomy'];
    if (isset($_POST['taxonomies'][$taxonomy]) && is_array($_POST['taxonomies'][$taxonomy])) {
      $terms = $_POST['taxonomies'][$taxonomy];
      $terms = array_map('intval', $terms);
      wp_set_object_terms($postId, $terms, $taxonomy);
    }elseif (isset($_POST['taxonomies'][$taxonomy]) && is_numeric($_POST['taxonomies'][$taxonomy])){
      $termId = intval($_POST['taxonomies'][$taxonomy]);
      $terms = array();
      if($termId>0){
        $terms = array($termId);
      }

      wp_set_object_terms($postId, $terms, $taxonomy);
    }else{
      // delete the terms, if no $_POST data was found (but the metabox taxonomy field was configured)
      wp_set_object_terms($postId, array(), $taxonomy);
    }
  }

  /**
   * Save the dropdown values, accepts arrays (if multiple was specified) and
   * stores them in order with add_post_meta $unique=false.
   * @param $postId
   * @param $field
   * @param $boxId
   * @return array|string
   */
  public function saveDropdown($postId, $field, $boxId)
  {
    // Validate and save the field
    $value = $_POST[$postId . '_' . $field['key']];

    if (isset($field['args']['multiple']) && $field['args']['multiple'] && is_array($value)) {
      $value = array_map('stripslashes', $value);
    } else {
      $value = stripslashes(trim($value));
    }

    // Check if the field is required
    if (isset($field['args']['required']) && $field['args']['required'] && empty($value)) {
      $this->addError($boxId, 'Bitte füllen Sie das Feld "' . $field['args']['title'] . '" aus.');
      return;
    }

    if (is_array($value)) {
      delete_post_meta($postId, $field['key']);
      foreach ($value as $item) {
        add_post_meta($postId, $field['key'], $item, false);
      }
    } else {
      // Save the meta data to the database
      update_post_meta($postId, $field['key'], $value);
    }
    return $value;
  }

  /**
   * Inline callback to save a normal textfield
   * @param int $postId the id of the post to save to
   * @param array $field all the fields information
   * @param string $boxId the metabox id
   */
  public function saveDateField($postId, $field, $boxId)
  {
    // Validate and save the field
    $value = $_POST[$postId . '_' . $field['key']];
    $value = stripslashes(trim($value));

    // Validate the input with EU_FORMAT_DATE
    if (!String::check_date($value, Date::EU_FORMAT_DATE)) {
      $value = ''; // Make the error pop up
    }

    // Check if the field is required
    if (isset($field['args']['required']) && $field['args']['required'] && strlen($value) == 0) {
      $this->addError($boxId, 'Bitte füllen Sie das Feld "' . $field['args']['title'] . '" aus.');
    } else {
      // If everything OK, convert to the desired format
      if (strlen($value) > 0) {
        $value = Date::convert_date(
          Date::EU_DATE,
          $field['args']['format'],
          $value
        );
      }
    }

    // Save the meta data to the database
    update_post_meta($postId, $field['key'], $value);
  }

  /**
   * Inline callback to save a  checkbox value
   * @param int $postId the id of the post to save to
   * @param array $field all the fields information
   * @param string $boxId the metabox id
   */
  public function saveCheckboxField($postId, $field, $boxId)
  {
    // Validate and save the field
    $key = $postId . '_' . $field['key'];
    $value = isset($_POST[$key]) && $_POST[$key] == 'on' ? 'on' : false;

    // Check if the field is required
    if (isset($field['args']['required']) && $field['args']['required'] && strlen($value) == 0) {
      $this->addError($boxId, 'Bitte füllen Sie das Feld "' . $field['args']['title'] . '" aus.');
      return;
    }

    // Save the meta data to the database
    update_post_meta($postId, $field['key'], $value);
  }

  /**
   * Inline callback to save nothing
   * @param int $postId the id of the post to save to
   * @param array $field all the fields information
   * @param string $boxId the metabox id
   */
  public function saveVoid($postId, $field, $boxId)
  {

  }

  /**
   * Saves the assigned posts
   * @param int $postId the post that is saved
   * @param array $field the field data
   * @param string $boxId the metabox id
   */
  public function saveAssignPostsField($postId, $field, $boxId)
  {
    $posts = $_POST['assignedPostsId'];
    if (!is_array($posts)) {
      $posts = array();
    }

    update_post_meta($postId, $field['key'], $posts);
  }

  /**
   * Inline callback to display a normal textfield
   * @param array $args the arguments to display the input textfield
   * @return string HTML code to display the field
   */
  public function displayInputText($args)
  {
    $key = $args['post']->ID . '_' . $args['key'];
    $html = $this->getTemplate($args, $key);

    // Get the current value
    $value = get_post_meta($args['post']->ID, $args['key'], true);
    if (strlen($value) == 0 && isset($args['default'])) {
      $value = $args['default'];
    }

    // Default width
    $width = $this->getWidth($args);
    if ($width == 'none') {
      $width = '75%;';
    }

    $attr = ' style="width:' . $width . ';"';
    if (isset($args['required']) && $args['required']) {
      $attr .= ' required="required"';
    }

    // Replace in the input field
    $input = '
      <input type="text" id="' . $key . '" name="' . $key . '" value="' . esc_attr($value) . '"' . $attr . ' />
    ';
    $html = str_replace('{input}', $input, $html);
    return $html;
  }

  /**
   * Inline callback to display a date textfield with jquery date selector
   * @param array $args the arguments to display the input textfield
   * @return string HTML code to display the field
   */
  public function displayDateField($args)
  {
    // Make sure to load the jquery datepicker
    wp_enqueue_style('jquery-ui-datepicker-css', get_bloginfo('wpurl') . '/wp-content/plugins/blogwerk/css/ui.datepicker.css', array(), '1.0');
    wp_enqueue_script('jquery-ui-datepicker', get_bloginfo('wpurl') . '/wp-content/plugins/blogwerk/js/ui.datepicker.js', array('jquery', 'jquery-ui-core'));


    $key = $args['post']->ID . '_' . $args['key'];
    $html = $this->getTemplate($args, $key);

    // Get the current value
    $value = get_post_meta($args['post']->ID, $args['key'], true);
    if (strlen($value) == 0 && isset($args['default'])) {
      $value = $args['default'];
    }

    $attr = '';
    if (isset($args['required']) && $args['required']) {
      $attr .= ' required="required"';
    }

    // Convert the configured format to the display format EU_DATE
    if (strlen($value) > 0) {
      $value = Date::convert_date($args['format'], Date::EU_DATE, $value);
    }

    // Replace in the input field and add the js to use a picker
    $input = '
      <input type="text" id="' . $key . '" name="' . $key . '" class="mbh-datefield" value="' . esc_attr($value) . '"' . $attr . ' />
      <script type="text/javascript">
        jQuery(function() {
          jQuery("#' . $key . '").datepicker({
            dateFormat : "dd.mm.yy",
            dayNames : ["Sonntag", "Montag", "Dienstag", "Mittwoch", "Donnerstag", "Freitag", "Samstag"],
            dayNamesMin : ["So", "Mo", "Di", "Mi", "Do", "Fr", "Sa"],
            dayNamesShort : ["So", "Mo", "Di", "Mi", "Do", "Fr", "Sa"],
            monthNames : ["Januar", "Februar", "März", "April", "Mai", "Juni", "Juli", "August", "September", "Oktober", "November", "Dezember"],
            monthNamesShort : ["Jan", "Feb", "Mär", "Apr", "Mai", "Jun", "Jul", "Aug", "Sep", "Okt", "Nov", "Dez"],
            firstDay : 1
          });
        });
      </script>
    ';

    $html = str_replace('{input}', $input, $html);
    return $html;
  }

  /**
   * Displays a "field" (or rather an admin) to assign posts via ajax
   * autocomplete and lets the user sort them by drag and drop.
   * This will register a few needed javascript libraries.
   * Warning: Only once useable per post type, doesn't work with multiple use.
   * @param array $args the arguments
   * @return string html code to display the fields
   */
  public function displayAssignPostsField($args)
  {
    $html = '';

    wp_enqueue_script('jquery-ui-autocomplete');
    wp_enqueue_script('jquery-ui-sortable');

    // Add the field to add new posts via ajax
    $attr = ' style="width:' . $this->getWidth($args) . ';"';
    $html .= '
      <div class="mbh-title">
        Artikel hinzufügen:<br />
        <br />
        <br />
        Bereits verknüpfte Artikel:
      </div>
      <div class="mbh-field">
        <div class="mbh-input">
          <input type="text" value="" id="newAssignedItem" ' . $attr . '/>
        </div>
        <div class="mbh-description">
          Geben sie den Titel oder die ID des gewünschten Artikels ein und bestätigen Sie die Auswahl mit Enter.
        </div>
      </div>
    ';

    // Get the current items and print json (metabox-helper.js will display them)
    $posts = $value = get_post_meta($args['post']->ID, $args['key'], true);
    $items = array();

    // Generate JS items, if there are assigned posts
    if (is_array($posts) && count($posts)) {

      foreach ($posts as $postId) {
        $data = get_post(intval($postId));
        if (strlen($data->post_title) > 0) {
          $items[] = array(
            'id' => $data->ID,
            'value' => $data->post_title
          );
        }
      }
    }

    // Always print the JS variable, even if empty
    $html .= '
      <script type="text/javascript">
        var mbhAssignedPostsData = ' . json_encode($items) . ';
      </script>
      <div id="mbh-assign-posts-container"></div>
    ';

    // Display everything in an empty full item
    return str_replace('{html}', $html, $this->templates['empty']);
  }

  /**
   * Ajax callback for assign posts auto complete
   */
  public static function ajaxAssignPostsData($types)
  {
    $results = array();

    if (strlen($_GET['term']) > 0) {
      // Go directly to the database
      $sql = '
        SELECT ID, post_title FROM {sql:postTable}
        WHERE (ID = {postId} OR post_title LIKE {postTitle})
        AND post_type IN({sql:postTypes})
      ';

      global $wpdb;
      $posts = $wpdb->get_results(String::prepareSql($sql, array(
        'postTable' => $wpdb->posts,
        'postId' => intval($_GET['term']),
        'postTitle' => '%' . $_GET['term'] . '%',
        'postTypes' => '"' . implode('","', $types) . '"'
      )));

      foreach ($posts as $post) {
        $results[] = array(
          'id' => $post->ID,
          'value' => $post->post_title,
          'label' => $post->post_title,
        );
      }
    }

    header('Content-Type: application/json');
    echo json_encode($results);
    exit;
  }

  /**
   * Inline callback to display a normal checkbox
   * @param array $args the arguments to display the input checkbox
   * @return string HTML code to display the field
   */
  public function displayCheckbox($args)
  {
    $key = $args['post']->ID . '_' . $args['key'];
    $template = '';
    if (isset($args['template'])) {
      $template = $args['template'];
    }

    $html = $this->getTemplate($args, $key, $template);

    if (isset($args['value'])) {
      $value = $args['value'];
    } else {
      // Get the current value
      $value = get_post_meta($args['post']->ID, $args['key'], true);
    }

    // override meta value with selected argument
    if (isset($args['selected'])) {
      $selected = checked($args['selected'], true, false);
    } else {
      $selected = checked($value, 'on', false);
    }

    if (empty($value)) {
      $value = 'on';
    }

    if (isset($args['name'])) {
      $name = $args['name'];
    } else {
      // Get the current value
      $name = $key;
    }

    $attr = ' style="width:' . $this->getWidth($args) . ';"';
    if (isset($args['required']) && $args['required']) {
      $attr .= ' required="required"';
    }
    $description = '';
    if (isset($args['description'])) {
      $description = $args['description'];
    }

    // Replace in the input field
    $input = '
      <input type="checkbox" id="' . $key . '" name="' . $name . '" value="' . $value . '" ' . $selected . $attr . ' />
      <label for="' . $key . '">' . $description . '</label>
    ';
    if ($template) {
      $html = str_replace('{input}', $input, $html);
    } else {
      $html = $input;
    }

    return $html;
  }

  /**
   * Inline callback to display a normal radio button
   * @param array $args the arguments to display the input checkbox
   * @return string HTML code to display the field
   */
  public function displayRadioButton($args)
  {
    $key = $args['post']->ID . '_' . $args['key'];
    $description = '';

    if (isset($args['value'])) {
      $value = $args['value'];
    } else {
      // Get the current value
      $value = get_post_meta($args['post']->ID, $args['key'], true);
    }
    if (isset($args['name'])) {
      $name = $args['name'];
    } else {
      // Get the current value
      $name = $key;
    }
    if (isset($args['description'])) {
      $description = $args['description'];
    }


    $attr = ' style="width:' . $this->getWidth($args) . ';"';
    if (isset($args['required']) && $args['required']) {
      $attr .= ' required="required"';
    }

    // Replace in the input field
    $html = '
      <input type="radio" id="' . $key . '" name="' . $name . '" value="' . $value . '" ' . checked($args['selected'], true, false) . $attr . ' />
      <label for="' . $key . '">' . $description . '</label>
    ';

    return $html;
  }


  /**
   * Display callback for displaying a taxonomy as a list of radio buttons or checkboxes
   * if $args['display'] is set to 'radio', the terms of the taxonomy will be
   * displayed as radio buttons.
   * The display callback automatically  sets a inline style height, depending on the
   * $args['numberOfVisibleTerms'] argument.
   *
   * @param array $args
   * @return string
   */
  public function displayTaxonomy($args)
  {

    $key = $args['post']->ID . '_' . $args['key'];
    $template = $this->getTemplate($args, $key, 'short_input_list');
    $inputs = '';
    $optionValues = array();
    $optionItems = array();
    $taxonomy = $args['taxonomy'];
    $terms = get_terms($taxonomy, array('hide_empty' => false));
    foreach ($terms as $term) {
      $inputArguments = array_merge($args, array(
        'key' => $args['key'] . '_' . $term->term_id,
        'name' => 'taxonomies[' . $term->taxonomy . '][]',
        'value' => $term->term_id,
        'selected' => has_term(intval($term->term_id), $term->taxonomy, $args['post']),
        'description' => $term->name,
        'width' => 'none' // quick-fix for new wp3.8 styles, is in effect invalid css
      ));
      if ($args['display'] == 'chosen') {
        $optionItems[$term->term_id] = array(
          'title' => $term->name,
          'data' => array(
            'url' => admin_url('edit-tags.php?action=edit&taxonomy=' . $taxonomy . '&tag_ID=' . $term->term_id
            )
          )
        );
        if (has_term(intval($term->term_id), $term->taxonomy, $args['post'])) {
          $optionValues[] = $term->term_id;
        }
      } else {
        if ($inputArguments['display'] == 'radio') {
          $inputs .= $this->displayRadioButton($inputArguments);
        } elseif ($inputArguments['display'] == 'checkbox') {
          $inputs .= $this->displayCheckbox($inputArguments);
        }
      }


    }
    if ($args['display'] == 'chosen') {
      $html = $this->displayDropdown(array_merge($args, array('items' => $optionItems, 'value' => $optionValues, 'name' => 'taxonomies[' . $taxonomy . ']')));
    } else {
      $fieldStyle = 'height:' . 22 * $args['numberOfVisibleTerms'] . 'px;';

      $html = str_replace('{input}', $inputs, $template);
      $html = str_replace('{fieldStyle}', $fieldStyle, $html);
    }
    return $html;
  }

  /**
   * Inline callback to display a media upload button
   * @param array $args the arguments to display the input checkbox
   * @return string HTML code to display the field
   */
  public function displayMediaUploadField($args)
  {
    $postId = $args['post']->ID;
    $key = $args['post']->ID . '_' . $args['key'];
    $html = $this->getTemplate($args, $key, 'media');

    $attachmentId = get_post_meta($postId, $args['key'], true);
    $attachment = null;
    if (intval($attachmentId)) {
      $attachment = get_post(intval($attachmentId));
    }

    $mediaContainer = callUserFunctionWithSafeArguments($args['mediaContainerCallback'], array($attachmentId, $postId, $attachment, $args));

    $input = '
      <div class="media-uploader">
        ' . $mediaContainer . '
        <input type="button" class="button" name="' . $postId . '-upload' . '" value="' . $args['uploaderButtonText'] . '"  />
        <input type="hidden" class="field-attachment-id" name="' . $key . '" value="' . intval($attachmentId) . '" />
      </div>
      <div class="media-uploader-links ' . (is_a($attachment, 'WP_Post') ? '' : 'hide') . '">
        <a href="#" class="mbhRemoveMedia">' . $args['removeMediaText'] . '</a>
      </div>
      <script>
        (function ($) {
          $(document).ready(function(){
            $(".media-uploader").wordpressAttachment(' . json_encode($args) . ');
          });
        }(jQuery));
      </script>
    ';

    $html = str_replace('{media}', $input, $html);
    return $html;
  }

  /**
   * Provide the HTML content of the media uploader field.
   * it is overridable with the 'mediaContainerCallback' argument. for the removeMedia to work it has to have
   * a 'wrapper' class somewhere...
   *
   * @param int $attachmentId
   * @param int $postId
   * @param WP_Post|null $attachment
   * @param array $args
   * @return string
   */
  public function mediaContainerCallback($attachmentId, $postId, $attachment, $args)
  {
    if (is_a($attachment, 'WP_Post')) {
      list($url, $width, $height, $crop) = wp_get_attachment_image_src($attachmentId, 'full');
      $container = '
        <div class="image-wrapper wrapper" style="padding: ' . number_format(100 * ($height / $width), 2, '.', '') . '% 0 0 0;">
          <img src="' . $url . '" />
        </div>
      ';
    } else {
      $container = '<div class="image-wrapper wrapper"></div>';
    }
    return $container;
  }

  /**
   * Display Dropdown callback: displays a harvesthq.github.io/chosen/ dropdown.
   * special arguments:
   *
   * - bool required
   * - bool multiple
   * - array | string value
   * - array items | callable itemsCallback
   * @param $args
   * @return mixed|string
   */
  public function displayDropdown($args)
  {
    $key = $args['post']->ID . '_' . $args['key'];
    $html = $this->getTemplate($args, $key);

    if (isset($args['name'])) {
      $name = $args['name'];
    } else {
      // Get the current value
      $name = $key;
    }

    $attr = '';
    if (isset($args['required'])) {
      $attr .= __checked_selected_helper($args['required'], true, false, 'required');
    }

    $singleValue = true;
    if (isset($args['multiple']) && $args['multiple']) {
      $attr .= ' multiple';

      $arrayNotation = '[]';
      if (substr($name, -strlen($arrayNotation)) !== $arrayNotation) {
        $name .= $arrayNotation;
      }
      $singleValue = false;
    }

    if (isset($args['value']) && (!$singleValue || is_string($args['value']))) {
      $value = $args['value'];
    } elseif (isset($args['value'][0]) && $singleValue) {
      $value = $args['value'][0];
    } else {
      // Get the current value
      $value = get_post_meta($args['post']->ID, $args['key'], $singleValue);
      if(!$singleValue){
        $value = array_filter($value);
      }
    }

    $items = $args['items'];
    if (isset($args['itemsCallback']) && is_callable($args['itemsCallback'])) {
      $items = callUserFunctionWithSafeArguments($args['itemsCallback'], array($args));
    }

    if (isset($args['allow_single_deselect']) && $args['allow_single_deselect'] == true) {
      $items = array(0=>array('title' => '')) + $items;
    }

    $options = self::convertDropdownItemsToOptions($items, $value, $singleValue);

    $select = '<select name="' . $name . '" id="' . $key . '" ' . $attr . '>' . implode('', $options) . '</select>';

    $chosenArguments = array_merge(array(
      'width' => '100%' // sets the width of the chosen container
    ), $args);

    $chosenKey = str_replace('-', '_', $key) . '_chosen';

    // only call the sortable method if it was requested
    $sortableCall = '';
    if($chosenArguments['sortable']!=false){
      $sortableCall = '.chosenSortable()';
    }

    // convert array to object recursively
    $chosenArgumentsObject = json_decode(json_encode($chosenArguments), FALSE);
    $chosenArgumentsJson = json_encode($chosenArgumentsObject);
    $select .= '
      <script>
        (function ($) {
          $(document).ready(function(){

            $("#' . $key . '").on("chosen:ready change", function(evt, params) {
              $("#' . $chosenKey . ' .search-choice").each(function(){

                // compare label to option text
                var label = $(this).text().trim();
                var options = $("#' . $key . '").find("option").filter(function(){
                  return $(this).text().trim() == label;
                });
                if(options.length > 0){
                  var option = options[0];
                  if($(option).data("url")){
                    if(!$(this).hasClass("has-link-action")){
                      $(".search-choice-close", this).before("<a class=\"search-choice-link\" href=\"" + $(option).data("url") + "\" ></a>");
                      $(this).addClass("has-link-action");
                    }
                  }
                  if($(option).data("image")){
                    if(!$(this).hasClass("has-image")){
                      $("span", this).before("<img class=\"search-choice-image\" src=\"" + $(option).data("image") + "\" />");
                      $(this).addClass("has-image");
                    }
                  }
                }
              });

              $("#' . $chosenKey . ' .search-choice-link").click(function(e){
                // chosen is registered on parent, stop the propagation, but don\'t prevent the default action (i.e. browswer link)
                e.stopPropagation();
              });
            });

            $("#' . $key . '").chosen(' . $chosenArgumentsJson . ')' . $sortableCall. ';
          });
        }(jQuery));
      </script>
    ';

    $html = str_replace('{input}', $select, $html);
    return $html;
  }

  /**
   * helper function for converting the supplied dropdown items to option tags for the chosen plugin
   *
   * @param $items
   * @param $value
   * @param bool $singleValue
   * @return array
   */
  public static function convertDropdownItemsToOptions($items, $value, $singleValue=false){
    $options = array();

    if (!$singleValue) {
      foreach ($value as $selectedValue) {
        $title = $items[$selectedValue];
        if (isset($items[$selectedValue]['title'])) {
          $title = $items[$selectedValue]['title'];
        }
        $dataAttributes = '';
        if (isset($items[$selectedValue]['data'])) {
          $data = $items[$selectedValue]['data'];
          foreach ($data as $dataKey => $dataValue) {
            $dataAttributes .= ' data-' . $dataKey . '="' . $dataValue . '"';
          }
        }
        $options[] = '<option value="' . $selectedValue . '" selected="selected" ' . $dataAttributes . '>' . $title . '</option>';
      }
    }

    foreach ($items as $itemValue => $item) {

      $dataAttributes = '';
      if (isset($item['data'])) {
        $data = $item['data'];
        foreach ($data as $dataKey => $dataValue) {
          $dataAttributes .= ' data-' . $dataKey . '="' . $dataValue . '"';
        }
      }

      $title = $item;
      if (isset($item['title'])) {
        $title = $item['title'];
      }
      if ($singleValue) {
        $selected = selected($itemValue, $value, false);
        $options[] = '<option value="' . $itemValue . '" ' . $selected . ' ' . $dataAttributes . '>' . $title . '</option>';
      } else {
        if (in_array($itemValue, $value)) {
          continue;
        } else {
          $options[] = '<option value="' . $itemValue . '" ' . $dataAttributes . '>' . $title . '</option>';
        }
      }
    }
    return $options;
  }

  /**
   * Inline callback to display a textarea
   * @param array $args the arguments to display the input textfield
   * @return string HTML code to display the field
   */
  public function displayTextarea($args)
  {
    $key = $args['post']->ID . '_' . $args['key'];
    $html = $this->getTemplate($args, $key);

    // Get the current value
    $value = get_post_meta($args['post']->ID, $args['key'], true);
    if (strlen($value) == 0 && isset($args['default'])) {
      $value = $args['default'];
    }

    // Default width
    $width = $this->getWidth($args);
    if ($width == 'none') {
      $width = '75%;';
    }

    $attr = ' style="width:' . $width . ';height:' . $args['height'] . 'px"';
    if (isset($args['required']) && $args['required']) {
      $attr .= ' required="required"';
    }

    // Replace in the input field
    $input = '
      <textarea id="' . $key . '" name="' . $key . '"' . $attr . '>' . $value . '</textarea>
    ';
    $html = str_replace('{input}', $input, $html);
    return $html;
  }

  /**
   * Inline callback to display an editor
   * @param array $args the arguments to display the input textfield
   * @return string HTML code to display the field
   */
  public function displayEditor($args)
  {
    $key = $args['post']->ID . '_' . $args['key'];
    $html = $this->getTemplate($args, $key);

    // Get the current value
    $value = get_post_meta($args['post']->ID, $args['key'], true);
    if (strlen($value) == 0 && isset($args['default'])) {
      $value = $args['default'];
    }

    // Replace in the input field
    $input = String::getWpEditor($value, $key, array(
      'textarea_rows' => $args['rows']
    ));
    $html = str_replace('{input}', $input, $html);
    return $html;
  }

  /**
   * @param array $args the arguments given
   * @return string HTML Code that should be displayed
   */
  public function displayHtml($args)
  {
    $key = $args['post']->ID . '_' . $args['key'];
    $html = $this->getTemplate($args, $key, 'empty');
    $html = str_replace('{html}', $args['html'], $html);

    return $html;
  }

  /**
   * Returns the width for the field using (and fixing) the width parameter
   * @param string $args the arguments of the displayed fields
   * @return string the width string in percent or px
   */
  public function getWidth($args)
  {
    $width = 'none'; // quick fix for the wp3.8 admin style
    if (isset($args['width'])) {
      $width = $args['width'];
      if (strstr($width, '%') === false && stristr($width, 'px') === false) {
        $width .= 'px';
      }
    }
    return $width;
  }

  /**
   * Gets the template based on argument configuration
   * @param array $args the arguments given the display function
   * @param string $key the key to identify the post
   * @param string $template a template if a specific is wanted
   * @return string HTML code
   */
  public function getTemplate($args, $key, $template = '')
  {
    if (strlen($template) > 0 && isset($this->templates[$template])) {
      $html = $this->templates[$template];
    } else {
      // Find the template by argument magic
      if (isset($args['full']) && $args['full']) {
        if (isset($args['description'])) {
          $html = $this->templates['description_full'];
          $html = str_replace('{description}', $args['description'], $html);
        } else {
          $html = $this->templates['short_full'];
        }
      } else {
        if (isset($args['description'])) {
          $html = $this->templates['description'];
          $html = str_replace('{description}', $args['description'], $html);
        } else {
          $html = $this->templates['short'];
        }
      }
    }

    // Put a required flag on the label
    if (isset($args['required']) && $args['required']) {
      $args['title'] .= ' <span class="required">*</span>';
    }
    // provide a css class defaulted to the key argument (non-unique)
    $args['class'] .= ' ' . $args['key'];


    // Put in the title
    $html = str_replace('{title}', $args['title'], $html);
    $html = str_replace('{fieldId}', $key, $html);
    $html = str_replace('{fieldClass}', $args['class'], $html);
    return $html;
  }

  /**
   * @param string $postType the id of the metabox helper to get
   * @return Metabox the instance of the helper
   */
  public static function get($postType)
  {
    if (!isset(self::$instances[$postType])) {
      self::$instances[$postType] = new self($postType);
    }
    return self::$instances[$postType];
  }
}