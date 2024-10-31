<?php

/**
 * This view renders the main Layout of Parcello
 * It shows a sidebar and a content area.
 */
class AdminView {

  /**
   * Checks if given subpage should be displayed
   * @param $slug slug of page
   */
  public function subpage_is($slug) {
    return $_GET['subpage'] == $slug;
  }

  /**
   * Renders the admin View
   * @param $parcello -> Parcello instance
   */
  public function render($parcello) {
    global $wp_version;
?>
    <div class="parcello__wrapper">
      <div class="parcello__header">
        <div class="parcello__logo_wrapper">
          <img class="parcello__logo" src="<?php echo esc_html($parcello->plugin_directory_url) . 'assets/parcello-logo-dark.svg' ?>" />
          <span class="parcello__logo_addon">business</span>
        </div>
      </div>

      <?php
      // Of course we'll not allowing people to use an to old version of Wordpress.
      if (version_compare($wp_version, '4.7', '<')) {
        echo '<p>' . sprintf(
          __('This plugin requires WordPress 4.7 or newer. You are on version %1$s. Please <a href="%2$s">upgrade</a>.', 'parcello'),
          esc_html($wp_version),
          esc_url(admin_url('update-core.php'))
        ) . '</p>';
      } else {
        require_once($parcello->plugin_directory . '/components/sidebar.php');
        $sidebar = new ParcelloSideBar($parcello);
        $sidebar->render();
      ?>
        <div id="parcello__app" class="parcello__page_wrapper">
          <div class="notice notice-error hide-if-js">
            <p><strong><?php echo esc_html_e('This tool requires that JavaScript be enabled to work.', 'parcello'); ?></strong></p>
          </div>
          <?php
          // If the user has not connected his parcello account so far he'll be prompted to do so
          if (!$parcello->is_connected()) {
            require_once($parcello->plugin_directory . '/view/connect.php');
            $connect_view = new ConnectView();
            $connect_view->render($parcello);
          } else {
            // If the user has connected his parcello account we'll show the admin pages
          ?>
            <article>
              <?php
              if (!isset($_GET['subpage']) or $this->subpage_is('overview')) {
                require_once($parcello->plugin_directory . '/view/overview.php');
                $overview = new ParcelloOverView($parcello);
                $overview->render();
              } elseif ($this->subpage_is('tracking')) {
                require_once($parcello->plugin_directory . '/view/tracking.php');
                $tracking_view = new ParcelloTrackingView();
                $tracking_view->render($parcello);
              } elseif ($this->subpage_is('custom_css')) {
                require_once($parcello->plugin_directory . '/view/custom_css.php');
                $custom_css_view = new ParcelloCustomCssView();
                $custom_css_view->render($parcello);
              } elseif ($this->subpage_is('support')) {
                require_once($parcello->plugin_directory . '/view/support.php');
                $support_view = new ParcelloSupportView();
                $support_view->render($parcello);
              } elseif ($this->subpage_is('settings')) {
                require_once($parcello->plugin_directory . '/view/settings.php');
                $settings_view = new ParcelloSettingsView($parcello);
                $settings_view->render($parcello);
              }
              ?>
            </article>
          <?php
          }
          ?>
        </div>
      <?php
      } // version_compare()
      ?>
    </div>
<?php
  }
}
