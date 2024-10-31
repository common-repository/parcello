<?php

class ParcelloCustomCssView {
  public function render($parcello) {
    $update_custom_css_nonce = wp_create_nonce('parcello_update_custom_css_form_nonce');
    $custom_css = $parcello->api->get_custom_css();
?>
    <div class="parcello__canvas">
      <form class="parcello__form" method="post" action="<?php echo esc_html(admin_url('admin-post.php')); ?>">
        <h2>
          <?php echo __('Widget Style', 'parcello') ?>
        </h2>
        <?php if ($_GET['custom_css_added'] == 'successful') { ?>
          <div class="parcello__information parcello__information--successfull">
            <?php __('🎉 &nbsp; Css Added Successfully', 'parcello') ?>
          </div>
        <?php } ?>
        <p>
          <?php echo __('If you want to style the parcello widget you can use custom css', 'parcello') ?>
        </p>
        <div class="parcello__space parcello__space--30"></div>
        <input type="hidden" name="action" value="parcello_update_custom_css">
        <textarea id="css-textarea" name="parcello[css]" class="parcello__textarea"><?php echo $custom_css->data->css ?></textarea>
        <a class="parcello__demomode_url" target="_blank" href="<?php echo esc_html($custom_css->data->demo_url) ?>">Im Browser öffnen (Demomode)</a>
        <input type="hidden" name="parcello_update_custom_css_nonce" value="<?php echo $update_custom_css_nonce ?>" />
        <div class="parcello__form_row parcello__form_row--reverse">
          <?php
          submit_button(__('Save css', 'parcello'));
          ?>
          <p><?php echo __('Empty the cache of your browser if style did not change automatically', 'parcello') ?></p>
        </div>
      </form>
    </div>
<?php
  }
}
