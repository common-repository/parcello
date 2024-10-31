<?php

class ParcelloWidgetView {
  public function render($parcello) {
    $shipments = $parcello->api->get_shipments();
    $email_settings = $parcello->api->get_email_settings();
?>
    <div id="parcello_dashboard_widget">
      <?php
      if (isset($shipments->data->overall)) {
      ?>
      <h2>
        <?php printf(__('You Shipped %s Packages until yet', 'parcello'), $shipments->data->overall) ?>
      </h2>
      <?php
      }

      if ($email_settings->data->domain_status == "unverified") {
      ?>
        <p>
          <?php echo __('Email not verified', 'parcello') ?>
        </p>
      <?php
      }
      ?>
    </div>
<?php
  }
}
