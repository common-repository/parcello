<?php

/**
 * This is the Overview of parcello
 * It renders
 */
class ParcelloLatestShipments {
  private $parcello;

  public function __construct($parcello) {
    $this->parcello = $parcello;
  }

  private function get_class_for_state($state) {
    $block = 'parcello__shipping_row_state';

    $classes = array(
      "ORDER_RECEIVED",
      "READY_TO_SHIP",
      "ON_THE_WAY",
      "LAST_MILE",
      "SHIPMENT_PROBLEM",
      "CLIENT_HAS_TO_PICKUP",
      "CLIENT_HAS_PACKAGE",
      "RETOUR"
    );

    return $block . '--' . $classes[$state];
  }

  private function get_label_for_state($state) {
    $labels = array(
      __("ORDER_RECEIVED", 'parcello'),
      __("READY_TO_SHIP", 'parcello'),
      __("ON_THE_WAY", 'parcello'),
      __("LAST_MILE", 'parcello'),
      __("SHIPMENT_PROBLEM", 'parcello'),
      __("CLIENT_HAS_TO_PICKUP", 'parcello'),
      __("CLIENT_HAS_PACKAGE", 'parcello'),
      __("RETOUR", 'parcello')
    );

    return $labels[$state];
  }

  /**
   * Renders the Overview Page HTML
   * @param $parcello Parcello
   */
  public function render() {
    $shipments = $this->parcello->api->get_shipments();
?>
    <h2>
      <?php echo __('Your last Shipments', 'parcello') ?>
    </h2>
    <div class="parcello__space--30"></div>
    <?php

    if (count($shipments->data->shipments) == 0) {
    ?>
      <p>
        <?php echo __('You have not shipped anything until yet', 'parcello') ?>
      </p>
      <?php
    } else {

      foreach ($shipments->data->shipments as $shipment) :
      ?>
        <div class="parcello__shipping_row" onclick="window.open( '<?php echo esc_url($shipment->tracking_url) ?>', '_blank').focus()">

          <div class="parcello__shipping_row_column">
            <p><?php echo esc_html($shipment->carrier); ?></p>
          </div>

          <div class="parcello__shipping_row_column">
            <h4><?php echo esc_html($shipment->trackingid); ?></h4>
            <span>
              <?php
              $date = new DateTime($shipment->path[0]->date);
              echo $date->format('d-m-Y H:i:s');
              ?>
            </span>
          </div>

          <div class="parcello__shipping_row_column">
            <h4>
              <?php echo esc_html($shipment->recipient_email) ?>
            </h4>
            <span onclick="event.stopPropagation()">
              <?php
              $order_id = explode("_", str_replace("o", '', $shipment->order_id))[1];

              $order = wc_get_order($order_id);
              if (!$order) {
                echo __('Not connected with an order', 'parcello');
              } else {
                echo __('Order Id', 'parcello');
                echo ': <a href="' . admin_url('post.php?post=' . absint($order->id) . '&action=edit') . '">' . $order->get_id() . '</a>';
              }
              ?>
            </span>
          </div>

          <div class="parcello__shipping_row_column">
            <span class="parcello__shipping_row_state <?php echo esc_html($this->get_class_for_state($shipment->current_state_b2b)) ?>">
              <?php echo esc_html($this->get_label_for_state($shipment->current_state_b2b)) ?>
            </span>
          </div>

        </div>
      <?php
      endforeach;
      ?>
      <div class="parcello__space--30"></div>
      <div class="parcello__action_bar">
        <a href="https://business.parcello.org/p/shipments">
          <?php echo __('See all shipments at Parcello', 'parcello') ?>
        </a>
      </div>
<?php
    }
  }
}
