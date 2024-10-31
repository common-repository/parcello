<?php

/**
 * This view allows users to change the tracking page
 */
class ParcelloSettingsView {
  public function __construct($parcello) {
    $this->parcello = $parcello;
    require_once($parcello->plugin_directory . '/parcello-functions.php');
    $this->functions = new ParcelloFunctions($this);
  }

  public function render() {
    $trackingMethod = get_option('parcello_tracking_method');
    $update_tracking_method_nonce = wp_create_nonce('parcello_update_tracking_method_form_nonce');
?>
    <div class="parcello__canvas">
      <form id="parcello_update_tracking_method_form" class="parcello__form" method="post" action="<?php echo esc_html(admin_url('admin-post.php')); ?>" >
        <h2>
          <?php echo __('Select your preferred tracking method', 'parcello') ?>
        </h2>
        <p>
          <?php echo __('Parcello integrates with several tracking environments. Per default we\'ll use Germanized if available', 'parcello') ?>
        </p>
        <p class="parcello__hint">
          <?php
          if (!$trackingMethod) {
            echo '<br><br>' . __('The plugin will determine the right tracking method automatically', 'parcello');
          }
          ?>
        </p>
        <input type="hidden" name="action" value="parcello_update_tracking_method"/>
        <input type="hidden" name="parcello_update_tracking_method_nonce" value="<?php echo $update_tracking_method_nonce ?>" />
        <div class="parcello__form_row">
          <select class="parcello__select" id="parcello_update_tracking_method_select" name='parcello[tracking_method]' data-trigger-submit>
            <option value='0'><?php echo __('Decide automatically', 'parcello'); ?></option>
            <?php
              if ($this->functions->is_germanized_installed()){
            ?>
            <option value='germanized' <?php selected($trackingMethod == 'germanized', true); ?>><?php echo __('Germanized', 'parcello') ?></option>
            <?php
              }
            ?>
            <option value='meta_fields' <?php selected($trackingMethod == 'meta_fields', true); ?>><?php echo __('Meta Fields', 'parcello') ?></option>
            <option value='parcello' <?php selected($trackingMethod == 'parcello', true); ?>><?php echo __('Parcello', 'parcello') ?></option>
          </select>
        </div>
        <?php
            if ($trackingMethod == 'meta_fields') {
              ?>
              <div class="parcello__form_row">
                <div class="parcello__form_column">
                  <label><?php echo __('ID Field', 'parcello') ?></label>
                  <input name="parcello[tracking_id_field]" type="text" value="<?php echo get_option('parcello_tracking_id_field') ?>"/>
                </div>
                <div class="parcello__form_column">
                  <label><?php echo __('Carrier Field', 'parcello') ?></label>
                  <input name="parcello[tracking_carrier_field]" type="text" value="<?php echo get_option('parcello_tracking_carrier_field') ?>"/>
                </div>
              </div>
              <?php
            }
          ?>
        <div class="parcello__space--20"></div>
          <button class="parcello__button" type="submit"><?php echo __('Save', 'parcello') ?></button>
          <div id="parcello_form_loader" class="hidden"></div>
          <strong id="parcello_form_feedback-success" class="hidden">
            <?php echo __('Tracking method was successfully updated', 'parcello') ?>
          </strong>
          <strong id="parcello_form_feedback-failure" class="hidden">
            <?php echo __('There was an error during Update', 'parcello') ?>
          </strong>
      </form>
    </div>
<?php
  }
}
