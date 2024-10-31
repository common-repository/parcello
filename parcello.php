<?php

/**
 * Plugin Name: Parcello
 * Plugin URI: https://wordpress.org/plugins/parcello/
 * Description: Parcello let's your customers track their packages
 * Author: Parcello
 * Version: 1.0.7
 * Author URI: https://parcello.org
 * Text Domain: parcello
 * Domain Path: /languages
 * License: GPL2+
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) exit;


// Add Logging if it does not exist already
if (!function_exists('write_log')) {
  function write_log($log) {
    if (WP_DEBUG) {
      if (is_array($log) || is_object($log)) {
        error_log(print_r($log, true));
      } else {
        error_log($log);
      }
    }
  }
}

class Parcello {
  /**
   * The capability required to use this plugin.
   * Please don't change this directly. Use the "parcello_capabilities" filter instead.
   *
   * @var string
   */
  public $capability = 'manage_options';

  public $menu_id = null;
  public $api = null;

  /**
   * Shortcode
   */
  public $shortcode = 'from_parcello_with_love';

  /**
   * Default tracking page
   */
  public $tracking_page = null;

  /**
   * Name of the plugin
   * used for e.g. Translations
   */
  public $plugin_name = "parcello";

  /**
   * Place of plugin within WordPress
   */
  public $plugin_directory = WP_PLUGIN_DIR . '/parcello';

  /**
   * Url of Plugin from outside
   */
  public $plugin_directory_url = '';

  /**
   * The single instance of this plugin.
   * @access private
   * @var Parcello
   */
  private static $instance;

  /**
   * Constructor. Doesn't actually do anything as instance() creates the class instance.
   */
  private function __construct() {
  }

  /**
   * Creates a new instance of this class if one hasn't already been made
   * and then returns the single instance of this class.
   *
   * @return Parcello
   */
  public static function instance() {
    if (!isset(self::$instance)) {
      self::$instance = new Parcello;
      self::$instance->setup();
    }

    return self::$instance;
  }

  /**
   * Register all of the needed hooks and actions.
   */
  public function setup() {
    // If a User wants to disconnect we do not need to call the other functions
    if (isset($_GET['disconnect'])) {
      $this->disconnect();
    }
    // Initialize Parcello Functions
    require_once($this->plugin_directory . '/parcello-functions.php');
    $parcello_functions = new ParcelloFunctions($this);

    // Load Custom Translation
    add_filter('load_textdomain_mofile', array($this, 'load_translation'), 10, 2);

    // Add a new item to the Tools menu in the admin menu.
    add_action('admin_menu', array($this, 'add_admin_menu'));

    // Load the required JavaScript and CSS.
    add_action('admin_enqueue_scripts', array($this, 'admin_enqueues'));

    // Add a Widget to the Dashboard
    add_action('wp_dashboard_setup', function () {
      wp_add_dashboard_widget('parcello_widget', 'Parcello', array($this, 'parcello_widget_ui'));
    });

    // Add a meta Box for tracking id on Order
    add_action('add_meta_boxes', array($this, 'add_order_meta_box'), 10, 2);
    add_action('admin_notices', array($this, 'admin_notices'));

    // API and Forms
    add_action('admin_post_parcello_form_response', array($parcello_functions, 'update_token'));
    add_action('admin_post_parcello_update_custom_css', array($parcello_functions, 'update_custom_css'));
    add_action('wp_ajax_parcello_update_tracking_page', array($parcello_functions, 'update_tracking_page'));
    add_action('admin_post_parcello_update_tracking_method', array($parcello_functions, 'update_tracking_method'));
    add_action('wp_ajax_parcello_disconnect', array($this, 'disconnect'));

    // Add or Update Tracking on several Events
    add_action('woocommerce_new_order', array($parcello_functions, 'add_or_update_tracking'), 10, 1);
    add_action('woocommerce_update_order', array($parcello_functions, 'add_or_update_tracking'), 10, 1);
    if ($parcello_functions->get_tracking_method() === 'parcello') {
      add_action('save_post', array($parcello_functions, 'on_order_was_saved'), 1, 1);
    } else if ($parcello_functions->get_tracking_method() === 'meta_fields') {
      add_action('added_post_meta', array($parcello_functions, 'on_change_post_meta_fields'), 10, 4);
      add_action('updated_post_meta', array($parcello_functions, 'on_change_post_meta_fields'), 10, 4);
    }

    require_once($this->plugin_directory . '/api.php');
    $this->api = new ParcelloAPI();

    $this->plugin_directory_url = plugin_dir_url(__FILE__);
  }

  /**
   * Since we'll have multiple languages withing Germany we'll need to translate our plugin
   */
  public function load_translation($mofile, $domain) {
    if ($this->plugin_name === $domain && false !== strpos($mofile, WP_LANG_DIR . '/plugins/')) {
      $locale = apply_filters('plugin_locale', determine_locale(), $domain);
      $mofile = WP_PLUGIN_DIR . '/' . dirname(plugin_basename(__FILE__)) . '/languages/' . $locale . '.mo';
    }
    return $mofile;
  }

  /**
   * We'll need to give our Users several Hints via admin notices.
   */
  public function admin_notices() {
    // If curl is not installed we'll need to tell the user to install it
    if (!function_exists('curl_version')) {
      echo '
        <div class="notice notice-warning is-dismissible">
          <h3>' . __('Parcello requires curl to work properly', 'parcello') . '</h3>
          <p><a href="https://www.php.net/manual/de/book.curl.php">' . __('how to install curl', 'parcello') . '</a><p>
        </div>';
    }
  }

  /**
   * Adds a the new item to the admin menu.
   */
  public function add_admin_menu() {
    $this->menu_id = add_menu_page(
      _x('Parcello', 'admin page title', 'parcello'),
      _x('Parcello', 'admin menu entry title', 'parcello'),
      $this->capability,
      $this->plugin_name,
      array($this, 'parcello_ui'),
      plugin_dir_url(__FILE__) . 'assets/logo.svg'
    );
  }

  /**
   * Enqueues JavaScript file and stylesheets on the plugin's admin page.
   */
  public function admin_enqueues($hook_suffix) {
    if ($hook_suffix != $this->menu_id) {
      return;
    }

    wp_enqueue_script(
      $this->plugin_name,
      plugins_url('assets/script.js', __FILE__),
      array('wp-api-request', 'jquery')
    );

    wp_enqueue_style($this->plugin_name, plugins_url('assets/style.css', __FILE__));

    // We'll need the Code Editor on e.g. our custom style Page
    $cm_settings['codeEditor'] = wp_enqueue_code_editor(array('type' => 'text/css'));
    wp_localize_script('jquery', 'cm_settings', $cm_settings);

    wp_enqueue_script('wp-theme-plugin-editor');
    wp_enqueue_style('wp-codemirror');
  }


  /**
   * Removes the connection to parcello account
   */
  public function disconnect() {
    delete_option('parcello_token');
    wp_redirect(esc_url_raw(add_query_arg(
      array(
        'parcello_connected' => 'false'
      ),
      admin_url('admin.php?page=' . $this->plugin_name)
    )));
  }

  /**
   * Indicates whether parcello is connected or not
   */
  public function is_connected() {
    if (!get_option('parcello_token')) {
      return false;
    } else {
      return true;
    }
  }

  /**
   * The main Parcello UI.
   */
  public function parcello_ui() {
    require_once($this->plugin_directory . '/view/admin.php');
    $admin_view = new AdminView();
    $admin_view->render($this);
  }

  /**
   * The Widget View in Dashboard
   */
  function parcello_widget_ui() {
    require_once($this->plugin_directory . '/view/widget.php');
    $widget_view = new ParcelloWidgetView();
    $widget_view->render($this);
  }

  /**
   * On WooCommerces order page we'll add a Meta box to enable users to do things with parcello
   */
  function add_order_meta_box() {
    require_once($this->plugin_directory . '/parcello-functions.php');
    $parcello_functions = new ParcelloFunctions($this);
    if ($parcello_functions->get_tracking_method() === 'parcello') {
      require_once($this->plugin_directory . '/view/meta-box.php');

      add_meta_box(
        'parcello-meta-box',
        __('Parcello'),
        array(new MetaBox($this), 'render'),
        'woocommerce_page_wc-orders',
        'normal',
        'default'
      );
    }
  }

  /**
   * Returns the code for tracking shipments
   */
  function parcello_shortcode_ui() {
    $toReturn = '
      <!-- Parcello start -->
      <script async src="https://cdn.parcello.org/parcello-widget.min.js"></script>
      <div id="parcello-tracking"></div>
      <!-- Parcello end -->
    ';
    return $toReturn;
  }
}


/**
 * Returns the single instance of this plugin, creating one if needed.
 *
 * @return Parcello
 */
function parcello() {
  $parcello = Parcello::instance();
  add_shortcode($parcello->shortcode, array($parcello, 'parcello_shortcode_ui'));
}

/**
 * Initialize this plugin once all other plugins have finished loading.
 */
add_action('init', 'parcello');
