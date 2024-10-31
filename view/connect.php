<?php

/**
 * If a user has not connected his account the connect view will be shown
 */
class ConnectView {
  public function render() {
    $connect_account_nonce = wp_create_nonce('parcello_connect_account_form_nonce');
?>
    <div class="parcello__canvas">
      <article class="parcello__connect_view">

        <h2 class="parcello__title">
          <?php echo __('Hey there, it\'s parcello here ✌️', 'parcello') ?>
        </h2>

        <h3 class="parcello__connect_view__title">
          <?php echo __('Keep going and connect your parcello account', 'parcello') ?>
        </h3>
        <p class="parcello__connect_view__description">
          <?php echo __('For that simply follow these instructions', 'parcello') ?>
        </p>

        <a href="https://business.parcello.org/profile/integrations?ref=wp-plugin&redirect=<?php echo urlencode(admin_url("admin.php?page=" . $this->plugin_name, 'https')); ?>" class="parcello__button" target="_blank">
          <span class="dashicons dashicons-external"></span> <?php echo __('Go to parcello integration page', 'parcello') ?>
        </a>

        <div class="parcello__space parcello__space--30"></div>

        <h3>
          <span class="dashicons dashicons-clipboard"></span> &nbsp; <?php echo __('Copy your token you\'ll see there', 'parcello') ?>
        </h3>

        <div class="parcello__space parcello__space--30"></div>

        <form class="parcello__form" method="post" action="<?php echo esc_html(admin_url('admin-post.php')); ?>">
          <h2>
            <?php echo __('And drop that API-Token here', 'parcello') ?>
          </h2>

          <div class="parcello__form_row">
            <input type="password" name="parcello[token]" placeholder="<?php echo __('API-Token', 'parcello') ?>" />

            <input type="hidden" name="action" value="parcello_form_response">

            <input type="hidden" name="parcello_connect_account_nonce" value="<?php echo $connect_account_nonce ?>" />
            <?php
            submit_button(__("Connect", 'parcello'));
            ?>
          </div>
        </form>
      </article>
    </div>
<?php
  }
}
