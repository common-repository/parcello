<?php

/**
 * A User can navigate through the app via sidebar
 */
class MetaBox {

  /**
   * @var Parcello
   */
  private $parcello;

  /**
   * @var ParcelloFunctions
   */
  private $functions;

  public function __construct($parcello) {
    $this->parcello = $parcello;
    require_once($parcello->plugin_directory . '/parcello-functions.php');
    $this->functions = new ParcelloFunctions($this);
  }


  /**
   * Renders the HTML of our sidebar
   * @param $parcello -> Parcello instance
   */
  public function render() {
    global $post;
    global $order;

    write_log('SCREEN');


    $order = new WC_Order($post->ID);

    $shipments = array();
    $custom_field = $order->get_meta('_parcello_shipments');

    write_log("#######('_parcello_shipments')");
    write_log($order->get_meta('_parcello_shipments'));
    if ($custom_field && $custom_field !==  '[""]') {
      $shipments = json_decode($custom_field, true);
    }
    write_log('#######count($shipments)');
    write_log(count($shipments));
    if (count($shipments) < 1) {
      $shipments[0] = array(
        'tracking_code' => null,
      );
    }

    $parcello_tracking_url = $order->get_meta('_parcello_tracking_url');

    $metabox_nonce = wp_create_nonce('parcello_metabox_form_nonce');
?>
    <style>
      #poststuff .parcello__meta_box_content_wrapper {
        padding: 15px;
      }

      #poststuff .parcello__meta_box_content_wrapper h2.parcello__meta_box_title {
        padding: 0;
        margin: 0;
        margin-bottom: 20px;
        font-size: 18px;
      }

      .parcello__input_wrapper {
        display: flex;
        flex-direction: column;
        margin-bottom: 15px;
      }

      .parcello__button {
        border-radius: 10px;
        border: 0;
        background-color: #000;
        color: #fff;
        padding: 10px 15px;
        text-decoration: none;
        transition: 300ms all ease-in-out;
        display: inline-block;
        border: 0;
        outline: none;
        margin-top: 10px;
      }

      .parcello__meta_box_shipping_row {
        margin-top: 20px;
      }

      .parcello__button:hover,
      .parcello__button:focus,
      .parcello__button:active {
        color: #fff;
        background-color: #000;
        text-decoration: none;
        border: 0;
        outline: none;
        box-shadow: none;
      }

      .parcello__form_row {
        background-color: #f7f7f7;
        padding: 15px;
      }

      .parcello__form_row h3 {
        margin-top: 0;
      }

      .parcello__meta_box_label {
        font-weight: bold;
        margin-bottom: 5px;
      }

      .parcello__space--30 {
        height: 30px;
      }

      .parcello__hint {
        font-size: 0.7em;
      }
    </style>
    <div class="parcello__meta_box_content_wrapper">
      <h2 class="parcello__meta_box_title"><?php echo __('Parcello Sendungen', 'parcello') ?></h2>
      <div id="parcello_shipments">
        <?php
        $tracking_placeholder = __('Tracking id', 'parcello');
        $carrier_placeholder = __('Carrier', 'parcello');

        foreach ($shipments as $key => $shipment) {
        ?>
          <div class="parcello__meta_box_shipping_row">
            <div class="parcello__form_row">
              <h3>
                <?php echo sprintf(__('Sendung %s', 'parcello'), $key + 1) ?>
              </h3>
              <div class="parcello__input_wrapper">
                <label class="parcello__meta_box_label">
                  <?php echo __('Tracking ID', 'parcello') ?>
                </label>
                <input name="parcello[shipments][<?php echo esc_html($key) ?>][tracking_code]" type="text" placeholder="<?php echo esc_html($tracking_placeholder) ?>" value="<?php echo $shipment['tracking_code'] ?>" />
              </div>
              <div class="parcello__input_wrapper">
                <label class="parcello__meta_box_label">
                  <?php echo __('Shipping Carrier', 'parcello') ?>
                </label>
                <input name="parcello[shipments][<?php echo esc_html($key) ?>][carrier]" type="text" placeholder="<?php echo esc_html($carrier_placeholder) ?>" value="<?php if (isset($shipment['carrier'])) echo ($shipment['carrier']) ?>" />
              </div>
            </div>
          </div>
        <?php
        }
        ?>
      </div>
      <script type="application/javascript">
        const shippments = <?php echo json_encode($shipments); ?>;

        jQuery(($) => {
          const addShippingRow = (shipment, index, Container) => {

            const Row = document.createElement('div');
            Row.className = 'parcello__meta_box_shipping_row';

            const FormRow = document.createElement('div');
            FormRow.className = 'parcello__form_row';

            const next_id = Container.children().length;
            console.log('next_id', next_id);

            const next_title = "<?php echo __('Sendung', 'parcello') ?> " + (next_id + 1);

            const Headline = document.createElement('h3');
            Headline.className = 'parcello__meta_box_title';
            Headline.innerText = next_title;

            const next_tracking_id_label = "<?php echo __('Tracking ID', 'parcello') ?>";


            const InputWrapper = document.createElement('div');
            InputWrapper.className = 'parcello__input_wrapper';

            const Label = document.createElement('label');
            Label.className = 'parcello__meta_box_label';

            const Input = document.createElement('input');
            Label.className = 'parcello__meta_box_label';
            Input.placeholder = "<?php echo esc_html($tracking_placeholder) ?>";
            Input.name = "parcello[shipments][" + next_id + "][tracking_code]";
            Input.type = 'text';

            InputWrapper.append(Headline);
            InputWrapper.append(Label);
            InputWrapper.append(Input);
            FormRow.append(InputWrapper);
            Row.append(FormRow);

            $("#parcello_shipments").append(Row);
          }

          const renderShippingRows = () => {
            const Container = $('#parcello_shipments');

            shippments.map((shipment, index) => {
              addShippingRow(shipment, index, Container);
            })
          }

          const addNewShippingRow = () => {
            shippments.push({
              tracking_id: null,
            })

            renderShippingRows(shippments);
          };

          $('#add_shipping_row').click(addNewShippingRow);
        })
      </script>
      <!-- <button id="add_shipping_row" type="button" class="parcello__button"><span style="margin-top: -1px;" class="dashicons dashicons-plus"></span>&nbsp;Weitere Sendung erstellen</button> -->
      <?php
      if (isset($parcello_tracking_url) && $parcello_tracking_url) {
      ?>
        <div class="parcello__space--30"></div>
        <a class="parcello__button" target="_blank" href="<?php echo esc_html($parcello_tracking_url); ?>">
          <span class="dashicons dashicons-external"></span> <?php echo __('Show parcello Widget', 'parcello') ?>
        </a>
      <?php
      }
      ?>
      <input type="hidden" name="parcello[metabox_form_nonce]" value="<?php echo $metabox_nonce ?>" />
    </div>
<?php
  }
}
