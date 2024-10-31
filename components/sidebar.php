<?php

/**
 * A User can navigate through the app via sidebar
 */
class ParcelloSideBar {
  public $parcello;

  function __construct($parcello) {
    $this->parcello = $parcello;
  }

  /**
   * Indicated whether an sidebarlink should be active or not
   */
  public function is_active_subpage($subpage_slug, $true_if_not_set) {
    if (!isset($_GET['subpage'])) {
      if ($true_if_not_set) {
        return true;
      }
      return false;
    } else {
      if ($_GET['subpage'] == $subpage_slug) return true;
      return false;
    }
  }

  /**
   * Renders the active modifier on a sidebar link
   */
  public function is_active_subpage_link_echo($subpage_slug, $true_if_not_set = false) {
    if ($this->is_active_subpage($subpage_slug, $true_if_not_set)) {
      echo "parcello__sidebar-link--active";
    }
  }

  /**
   * Renders a sidebar link
   */
  public function render_sidebar_link($label, $subpage_slug, $default = false, $badge = false) {
    $url =  add_query_arg(array(
      'subpage' => $subpage_slug,
      'page' => $this->parcello->plugin_name,
    ), admin_url('admin.php'));
?>
    <a class="parcello__sidebar-link <?php $this->is_active_subpage_link_echo($subpage_slug, $default) ?>" href="<?php echo esc_url($url) ?>">
      <?php echo esc_html($label) ?>
      <?php
      if ($badge) {
      ?>
        <span class="parcello__badge parcello__badge--red "><?php echo esc_html($badge); ?></span>
      <?php
      }
      ?>
    </a>
  <?php
  }

  /**
   * Renders the HTML of our sidebar
   * @param $parcello -> Parcello instance
   */
  public function render() {
  ?>
    <div class="parcello__sidebar">
      <nav>
        <h2>
          <?php echo __('My Account', 'parcello') ?>
        </h2>
        <div class="parcello__space--25"></div>
        <?php
        // If the user has not connected his account we'll only show a connect item inside of the sidebar
        if (!$this->parcello->is_connected()) {
        ?>
          <a class="parcello__sidebar-link--active">
            <?php echo __('Connect Account', 'parcello') ?>
          </a>
        <?php
        } else {
          // If a user is connected to parcello we'll show full functionality
          $this->render_sidebar_link(
            __('Overview', 'parcello'),
            'overview',
            true
          );
        ?>

          <div class="parcello__space--25"></div>

          <?php
          $this->render_sidebar_link(
            __('Tracking Page', 'parcello'),
            'tracking',
            false,
            !!get_option('parcello_tracking_page') ? false : 1,
          );

          $this->render_sidebar_link(
            __('Widget Style', 'parcello'),
            'custom_css'
          );
          ?>

          <div class="parcello__space--25"></div>

          <a href="https://business.parcello.org/p/shipments?ref=wp-plugin" target="_blank">
            <span class="dashicons dashicons-external"></span> <?php echo __('All shipments', 'parcello') ?>
          </a>

          <a href="https://business.parcello.org/p/analytics?ref=wp-plugin" target="_blank">
            <span class="dashicons dashicons-external"></span> <?php echo __('Analytics', 'parcello') ?>
          </a>


          <a href="https://business.parcello.org/profile/email?ref=wp-plugin" target="_blank">
            <span class="dashicons dashicons-external"></span> <?php echo __('Email Setup', 'parcello') ?>
          </a>

          <div class="parcello__space--25"></div>

          <?php
          $this->render_sidebar_link(
            __('Settings', 'parcello'),
            'settings'
          );
          $this->render_sidebar_link(
            __('Support', 'parcello'),
            'support'
          );
          ?>

          <div class="parcello__space--25"></div>
          <a href="https://www.parcello.org/privacy?ref=wp-plugin" target="_blank">
            <span class="dashicons dashicons-external"></span> <?php echo __('Data Policy', 'parcello') ?>
          </a>
          <div class="parcello__space--25"></div>

          <a href="<?php echo add_query_arg(array(
                      'page' => $this->parcello->plugin_name,
                      'disconnect' => 'true',
                    ), admin_url('admin.php')); ?>">
            <?php echo __('Disconnect', 'parcello') ?>
          </a>

          <div class="parcello__space--25"></div>

          <small>Version: 1.0.7</small>
        <?php
        }
        ?>
      </nav>
    </div>
<?php
  }
}
