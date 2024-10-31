<?php

/**
 * This view allows users to change the tracking page
 */
class ParcelloTrackingView {
  public function render($parcello) {
    $selectedPage = get_option('parcello_tracking_page');
    $update_tracking_page_nonce = wp_create_nonce('parcello_update_tracking_page_form_nonce');
?>
    <div class="parcello__canvas">
      <form id="parcello_update_tracking_page_form" class="parcello__form" method="post" action="<?php echo esc_html(admin_url('admin-post.php')); ?>" data-xhr>
        <h2>
          <?php echo __('1. Create your own tracking page', 'parcello') ?>
        </h2>
        <p>
          <?php echo __('On which page do you want to show the tracking information ?', 'parcello') ?>
        </p>

        <p>
          <?php echo __('If you do not have one already, create one and select it here. </br> Leaving this field blank will end in redirecting your customers to a default page', 'parcello') ?>
        </p>
        <p class="parcello__hint">
          <?php
          if (!$selectedPage) {
            echo '<br><br>' . __('Currently no page is selected', 'parcello');
          }
          ?>
        </p>
        <div class="parcello__form_row">
          <input type="hidden" name="action" value="parcello_update_tracking_page">
          <input type="hidden" name="parcello_update_tracking_page_nonce" value="<?php echo $update_tracking_page_nonce ?>" />
          <select class="parcello__select" id="parcello_update_tracking_page_select" name='parcello[tracking_page]' data-trigger-submit>
            <option value='0'><?php echo __('No tracking Page Selected', 'parcello'); ?></option>
            <?php $pages = get_pages(); ?>
            <?php foreach ($pages as $page) {
            ?>
              <option value='<?php echo $page->ID; ?>' <?php echo selected($selectedPage, $page->ID); ?>><?php echo $page->post_title; ?></option>
            <?php }; ?>
          </select>
          <div id="parcello_form_loader" class="hidden"></div>
          <strong id="parcello_form_feedback-success" class="hidden">
            <?php echo __('Tracking page was successfully updated', 'parcello') ?>
          </strong>
          <strong id="parcello_form_feedback-failure" class="hidden">
            <?php echo __('There was an error during Update', 'parcello') ?>
          </strong>
        </div>
      </form>
    </div>

    <div class="parcello__space--25"></div>

    <div class="parcello__canvas">

      <h2>
        <?php echo __('2. Integrate this Shortcode', 'parcello') ?>
      </h2>

      <p>
        <?php echo __('After you have created the tracking page put the following shortcode inside', 'parcello') ?>
      </p>

      <div id="copied-message" class="parcello__information hidden">
        <?php echo __('successfull copied to Clipboard 🎉', 'parcello') ?>
      </div>

      <pre class="parcello__code_box" onclick="navigator.clipboard.writeText('[<?php echo esc_html($parcello->shortcode) ?>]').then(() => document.getElementById('copied-message').classList.remove('hidden'));">[<?php echo esc_html($parcello->shortcode) ?>]</pre>

      <button class="parcello__clipboard_button" onclick="navigator.clipboard.writeText('[<?php echo esc_html($parcello->shortcode) ?>]').then(() => document.getElementById('copied-message').classList.remove('hidden'));">
        <span class="dashicons dashicons-clipboard"></span>
      </button>

      <p>
        <?php echo __('This will allow you to load the Parcello Tracking Frame.', 'parcello') ?>
      </p>

      <div class="parcello__information">
        <?php echo __('If you do not know how to use shortcuts please follow the guidence <a href="https://wordpress.com/support/wordpress-editor/blocks/shortcode-block/">here</a>', 'parcello') ?>
      </div>
    </div>
<?php
  }
}
