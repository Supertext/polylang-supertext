<?php

namespace Comotive\Util;

use LBWP\Module\Frontend\OutputFilter;
use WP_Error;
use LBWP\Util\String;
use LBWP\Core as LbwpCore;

/**
 * Utility functions for WordPress
 * @author Michael Sebel <michael@comotive.ch>
 */
class WordPress
{

  /**
   * Registers a taxonomy
   * @param string $slug the slug of the taxonomy
   * @param string $singular singular name
   * @param string $plural plural name
   * @param string $letter letter after "Übergeordnete" and "Neue" -> Could be "n" or "s"
   * @param array $config override the configuration with this array
   * @param string $types the types to be assigned (defaults to "post", can be an array)
   */
  public static function registerTaxonomy($slug, $singular, $plural, $letter = '', $config = array(), $types = 'post')
  {
    $defaults = array(
      'hierarchical' => true,
      'public' => true,
      'show_ui' => true,
      'show_tagcloud' => false,
      'labels' => array(
        'name' => $singular,
        'singular_name' => $singular,
        'search_items' => $plural . ' suchen',
        'popular_items' => '',
        'all_items' => 'Alle ' . $plural,
        'view_item' => $singular . ' ansehen',
        'parent_item' => 'Übergeordnete' . $letter . ' ' . $singular,
        'parent_item_colon' => 'Übergeordnete' . $letter . ' ' . $singular . ':',
        'edit_item' => $singular . ' bearbeiten',
        'update_item' => $singular . ' speichern',
        'add_new_item' => 'Neue' . $letter . ' ' . $singular . ' hinzufügen',
        'new_item_name' => 'Neue' . $letter . ' ' . $singular,
        'separate_items_with_commas' => $plural . ' durch Komma trennen',
        'add_or_remove_items' => $plural . ' hinzufügen oder entfernen',
        'menu_name' => $plural
      )
    );

    // Deep merge the defaults
    $mergedConfig = array();
    foreach ($defaults as $key => $value) {
      if (is_array($value) && isset($config[$key])) {
        $mergedConfig[$key] = array_merge($defaults[$key], $config[$key]);
      } else {
        $mergedConfig[$key] = $value;
      }
    }

    // Add configs that are not in defaults
    foreach ($config as $key => $value) {
      if (!isset($mergedConfig[$key])) {
        $mergedConfig[$key] = $value;
      }
    }

    register_taxonomy($slug, $types, $mergedConfig);
  }

  /**
   * Registering a post type
   * @param string $type slug of the type
   * @param string $singular singular name
   * @param string $plural plural name
   * @param array $config can override the defaults of this function (array_merge)
   */
  public static function registerType($type, $singular, $plural, $config = array())
  {
    $defaults = array(
      'labels' => array(
        'name' => $plural,
        'singular_name' => $plural,
        'add_new' =>  __('Erstellen'),
        'add_new_item' =>  'Neues ' . $singular . ' erfassen',
        'edit_item' =>  'Bearbeite ' . $singular,
        'new_item' =>  'Neues ' . $singular,
        'view_item' =>  $singular . ' ansehen',
        'search_items' =>  $singular . ' suchen',
        'not_found' =>  'Keine ' . $plural . ' gefunden',
        'not_found_in_trash' =>  'Keine ' . $plural . ' im Papierkorb gefunden',
        'parent_item_colon' => ''
      ),
      'public' => true,
      'has_archive' => true
    );

    // Deep merge the defaults
    $mergedConfig = array();
    foreach ($defaults as $key => $value) {
      if (is_array($value) && isset($config[$key])) {
        $mergedConfig[$key] = array_merge($defaults[$key], $config[$key]);
      } else {
        $mergedConfig[$key] = $value;
      }
    }

    // Add configs that are not in defaults
    foreach ($config as $key => $value) {
      if (!isset($mergedConfig[$key])) {
        $mergedConfig[$key] = $value;
      }
    }

    register_post_type($type, $mergedConfig);
  }

  /**
   * @param string $fileId id from $_FILES
   * @param bool $validateImage makes sure, it's an image
   * @param int $validateHeight if set, makes sure, that the image is at least the given height
   * @param int $validateWidth if set, makes sure, that the image is at least the given height
   * @return int attachment id of the uploaded item
   */
  public static function uploadAttachment($fileId, $validateImage, $validateHeight = 0, $validateWidth = 0)
  {
    if (!function_exists('wp_generate_attachment_metadata')){
      require_once(ABSPATH . 'wp-admin/includes/image.php');
      require_once(ABSPATH . 'wp-admin/includes/file.php');
      require_once(ABSPATH . 'wp-admin/includes/media.php');
    }

    // Check for images, if needed, and return 0 if no image
    if ($validateImage) {
      $file = $_FILES[$fileId];
      if (!File::isImage($file['name']) || !File::isImageMime($file['type'])) {
        return 0;
      }
    }

    // Get image dimensions, if needed and validate them
    if ($validateHeight > 0 || $validateWidth > 0) {
      list($imageWidth, $imageHeight) = getimagesize($file['tmp_name']);
      if ($validateHeight > 0 && $imageHeight < $validateHeight) {
        return 0;
      }
      if ($validateWidth > 0 && $imageWidth < $validateWidth) {
        return 0;
      }
    }

    // Run the update
    $result = media_handle_upload($fileId, 0);

    // Check for errors
    if ($result instanceof WP_Error) {
      return 0;
    }

    return intval($result);
  }

  /**
   * @param string $html
   * @return string html with ssl links
   */
  public static function handleSslLinks($html)
  {
    if ($_SERVER['HTTPS']) {
      $core = LbwpCore::getInstance();
      $cdnBucket = $core->getCdnName();
      $replacements = array(
        array('http://' . $cdnBucket, 'https://' . $core->getSslCdnName()),
        array('http://' . OutputFilter::CLOUDFRONT_DOMAIN, 'https://' . OutputFilter::CLOUDFRONT_DOMAIN),
        array('http://' . LBWP_HOST, 'https://' . LBWP_HOST)
      );

      foreach ($replacements as $replacement) {
        $html = str_replace($replacement[0], $replacement[1], $html);
        $replacement[0] = str_replace('/', '\/', $replacement[0]);
        $replacement[1] = str_replace('/', '\/', $replacement[1]);
        $html = str_replace($replacement[0], $replacement[1], $html);
      }
    }

    return $html;
  }

  /**
   * @param string $name post_name
   * @param string $posttype post_type
   * @return int the found id
   */
  public static function getPostIdByName($name, $posttype)
  {
    $wpdb = self::getDb();
    // Get id by simple query
    $sql = 'SELECT ID FROM {sql:postTable} WHERE post_type = {postType} AND post_name = {postName}';
    $postId = $wpdb->get_var(String::prepareSql($sql, array(
      'postTable' => $wpdb->posts,
      'postType' => $posttype,
      'postName' => $name
    )));

    return intval($postId);
  }

  /**
   * @param string $key the option key
   * @return array the encoded option or false
   */
  public static function getJsonOption($key)
  {
    $result = json_decode(get_option($key, false), true);
    return ($result == NULL) ? false : $result;
  }

  /**
   * @param string $key the option key
   * @param array $value the jsonizable object
   */
  public static function updateJsonOption($key, $value)
  {
    update_option($key, json_encode($value));
  }

  /**
   * Sets correct heads for json output
   * @param array $result the array that should be send via json
   */
  public static function sendJsonResponse($result)
  {
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
  }

  /**
   * @param string $pageId backend page parameter
   * @return string the name or an error message
   */
  public static function getBackendPageName($pageId)
  {
    global $submenu;

    foreach ($submenu as $items) {
      foreach ($items as $item) {
        if ($item[2] == $pageId) {
          return $item[0];
        }
      }
    }

    return 'could not resolve page name';
  }

  /**
   * Caching wrapper for wpNavMenu
   * @param array $config the menu config
   * @param int $cacheTime the cache time
   * @return string html code of the menu
   */
  public static function wpNavMenu($config, $cacheTime = 300)
  {
    // Try to get the menu from cache
    $key = $config['theme_location'] . '_' . md5(json_encode($config) . '_' . md5($_SERVER['REQUEST_URI']));
    $html = wp_cache_get($key, 'wpNavMenu');

    if ($html !== false) {
      return $html;
    }

    // Not from cache, generate it
    $config['echo'] = 0;
    $html = wp_nav_menu($config);
    wp_cache_set($key, $html, 'wpNavMenu', $cacheTime);
    return $html;
  }

  /**
   * @param int $termId the term id
   * @param string $taxonomy the taxonomy of the term
   * @return \stdClass highest term
   */
  public static function getHighestParent($termId, $taxonomy)
  {
    // start from the current term
    $parent = get_term_by('id', $termId, $taxonomy);

    // climb up the hierarchy until we reach a term with parent = '0'
    while ($parent->parent != '0') {
      $termId = $parent->parent;
      $parent = get_term_by('id', $termId, $taxonomy);
    }

    return $parent;
  }

  /**
   * @param string $template the template
   * @param mixed $part the part
   * @return string html code returned from get_template_part()
   */
  public static function returnTemplatePart($template, $part = null)
  {
    ob_start();
    get_template_part($template, $part);
    $content = ob_get_contents();
    ob_end_clean();
    return $content;
  }

  /**
   * @return \WP_Query
   */
  public static function getQuery()
  {
    global $wp_query;
    return $wp_query;
  }

  /**
   * @return \WP_Admin_Bar
   */
  public static function getAdminBar()
  {
    global $wp_admin_bar;
    return $wp_admin_bar;
  }

  /**
   * @return \WP_Object_Cache
   */
  public static function getObjectCache()
  {
    global $wp_object_cache;
    return $wp_object_cache;
  }

  /**
   * @return string
   */
  public static function getPageNow()
  {
    global $pagenow;
    return $pagenow;
  }

  /**
   * @return \wpdb
   */
  public static function getDb()
  {
    global $wpdb;
    return $wpdb;
  }

  /**
   * @return \WP_Rewrite
   */
  public static function getRewrite()
  {
    global $wp_rewrite;
    return $wp_rewrite;
  }

  /**
   * @return \WP_Roles
   */
  public static function getRoles()
  {
    global $wp_roles;
    return $wp_roles;
  }

  /**
   * @return mixed
   */
  public static function getUserRoles()
  {
    global $wp_user_roles;
    return $wp_user_roles;
  }

  /**
   * @return string
   */
  public static function getVersion()
  {
    global $wp_version;
    return $wp_version;
  }

  /**
   * @return \WP_Post
   */
  public static function getPost()
  {
    global $post;
    return $post;
  }

  /**
   * @return array|bool|null|object
   */
  public static function getComment()
  {
    global $comment;
    return $comment;
  }

  /**
   * @return array
   */
  public static function getComments()
  {
    global $comments;
    return $comments;
  }

  /**
   * @return mixed
   */
  public static function getCustomImageHeader()
  {
    global $custom_image_header;
    return $custom_image_header;
  }

  /**
   * @return array
   */
  public static function getShortcodeTags()
  {
    global $shortcode_tags;
    return $shortcode_tags;
  }

  /**
   * @return mixed
   */
  public static function getThemeDirectories()
  {
    global $wp_theme_directories;
    return $wp_theme_directories;
  }

  public static function getThemes()
  {
    global $wp_themes;
    return $wp_themes;
  }

  /**
   * @return mixed
   */
  public static function getLocale()
  {
    global $wp_locale;
    return $wp_locale;
  }

  /**
   * @return array|bool|mixed|string|void
   */
  public static function getMenu()
  {
    global $menu;
    return $menu;
  }

  public static function setMenu($newMenu)
  {
    global $menu;
    $menu = $newMenu;
  }

  /**
   * @return string
   */
  public static function getSubMenuFile()
  {
    global $submenu_file;
    return $submenu_file;
  }

  /**
   * @return array
   */
  public static function getSubMenu()
  {
    global $submenu;
    return $submenu;
  }

  /**
   * @param array $newSubbMenu
   */
  public static function setSubMenu($newSubbMenu = array())
  {
    global $submenu;
    $submenu = $newSubbMenu;
  }

  /**
   * @param string $file
   */
  public static function setSubMenuFile($file)
  {
    global $submenu_file;
    $submenu_file = $file;
  }

  /**
   * @return \WP_Scripts
   */
  public static function getScripts()
  {
    global $wp_scripts;
    return $wp_scripts;
  }

  /**
   * @return \WP_Styles
   */
  public static function getStyles()
  {
    global $wp_styles;
    return $wp_styles;
  }

  /**
   * @return mixed
   */
  public static function getl10n()
  {
    global $l10n;
    return $l10n;
  }

  /**
   * @return array
   */
  public static function getWidgets()
  {
    global $wp_registered_widgets;
    return $wp_registered_widgets;
  }

  /**
   * @return array
   */
  public static function getSidebars()
  {
    global $wp_registered_sidebars;
    return $wp_registered_sidebars;
  }
}