<?php

/**
 * If a user needs help, he can find a way to contact our support within this page
 */
class ParcelloSupportView {
  public function render() {
?>
    <div class="parcello__canvas">
      <h1>
        <?php echo __('Is there anything we can help you with?', 'parcello') ?>
      </h1>
      <p>
        <strong>
          <?php echo __('If you have trouble using this plugin, please feel free to drop us a message', 'parcello') ?>
        </strong>
      </p>
      <p>
        <a href="mailto: support.wordpress@parcello.org">support.wordpress@parcello.org</a>
      </p>
      <p>
        <?php echo __('Of course your data will always be private you can read about how we\'re managing Data within our <a href="https://www.parcello.org/privacy">data policies</a>', 'parcello') ?>
      </p>
    </div>
<?php
  }
}
