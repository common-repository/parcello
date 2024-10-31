<?php

/**
 * This Class wrapps the parcello functions
 */
class ParcelloFunctions {
  private $parcello;

  public function __construct($parcello) {
    $this->parcello = $parcello;
  }

  /**
   * When a new order has saved we'll check if the tracking number is set
   * and syncronize Tracking information with parcello
   */
  public function on_order_was_saved() {
    if ($this->parcello->is_connected()) {
      if (isset($_POST['parcello']['metabox_form_nonce']) && wp_verify_nonce($_POST['parcello']['metabox_form_nonce'], 'parcello_metabox_form_nonce')) {
        if (isset($_POST['parcello']['shipments'])) {
          write_log("An Order was saved and there are Shipments so we'll try to track it");

          // Sanitizing an array of Shipment field and cast it into an array
          $shipments = array_map(function ($item) {
            return array_map('sanitize_text_field', $item);
          }, (array) $_POST['parcello']['shipments']);

          $order = new WC_Order($_POST['post_ID']);

          if (count($shipments) > 0) {
            $custom_field = $order->get_meta('_parcello_shipments');

            if (isset($custom_field) && $custom_field != '') {
              $order->update_meta_data('_parcello_shipments', json_encode($shipments));
            } else {
              $order->add_meta_data('_parcello_shipments', json_encode($shipments));
            }
            $order->save_meta_data();
          } else {
            $order->delete_meta_data('_parcello_shipments');
            $order->save_meta_data();
          }
        }
      }
    }
  }

  /**
   * If a new Order is made we'll track a new shipping
   * @param string Order Id
   */
  function add_or_update_tracking($order_id) {
    if ($this->parcello->is_connected()) {
      write_log("The order $order_id will be updated or initially tracked");
      // First we're retreiving the Order
      $order = new WC_Order($order_id);

      // Check if the order is older than 7 days
      if( $order ) {
        $order_date = $order->get_date_created();
        $diff = date_diff(new DateTime(), $order_date);

        // Check if the order is older than 7 days
        if($diff->days < 7){

          // Than we'll figure out what method to use for tracking
          switch (get_option('parcello_tracking_method')) {
            case 'germanized':
              write_log("For tracking we'll use the Germanized Method");
              $this->track_shipment_with_germanized($order);
              break;
            case 'meta_fields':
              write_log("For tracking we'll use the Meta fields Method");
              $this->track_shipment_with_meta_fields($order);
              break;
            default:
              write_log("For tracking we'll use the Parcello Method");
              $this->track_shipment_with_parcello($order);
              break;
          }
        }
      }
    }
  }

  /**
   * Tracks a new Shipment using Meta Fields
   */
  public function track_shipment_with_meta_fields($order) {
    if ($this->parcello->is_connected() && get_option('parcello_tracking_method') == 'meta_fields') {
      $address = $this->get_formatted_address_from_order($order);

      $post_metafield_carrier = $this->remove_special_char(get_post_meta($order->get_id(), get_option('parcello_tracking_carrier_field')));
      $post_metafield_tracking_code = get_post_meta($order->get_id(), get_option('parcello_tracking_id_field'));

      $carrier = null;
      $tracking_number = null;

      if (isset($post_metafield_carrier[0])) {
        $carrier = $post_metafield_carrier[0];
      }

      if (isset($post_metafield_tracking_code[0])) {
        $tracking_number = $post_metafield_tracking_code[0];
      }

      write_log("We'll track the following Shipment");
      write_log(json_encode(array(
        "email" => $order->get_billing_email(),
        "order_id" => $order->get_id(),
        "address" => $address,
        "carrier" => $carrier,
        "tracking_number" => $tracking_number
      )));

      $this->parcello->api->add_or_update_shipment(
        $order->get_billing_email(),
        $order->get_id(),
        $address,
        $carrier,
        $tracking_number
      );
    }
  }

  /**
   * Tracks a new Shipment using Germanized
   */
  private function track_shipment_with_germanized($order) {
    write_log('We\'ll track a shipment via GERMANIZED');
    $address = $this->get_formatted_address_from_order($order);
    write_log('Address: ' . $address);

    // Maybe a User has Germanized. if so we'll us it's shipment API
    if ($this->is_germanized_installed()) {
      $gz_shipments = wc_gzd_get_shipments_by_order($order->get_id());
      write_log('Shippments: ');
      // write_log($gz_shipments);
    }

    if (isset($gz_shipments) && count($gz_shipments) > 0) {
      write_log("There are shipments so we'll try to sync all of them to parcello");
      // Since it could be that a User has multiple packages for one order we'll add all of them to parcello
      foreach ($gz_shipments as $key => $shipment) {
        $label = $shipment->get_label();
        $shipment_order_id = $order->get_id();

        // If an Order has more then one shipment we'll add a suffix _[id of shipment]
        if ($key > 0) {
          $shipment_order_id = $order->get_id() . '_' . ($key + 1);
        }

        $tracking_number = null;
        $carrier = null;

        // Some Packages does not have a label so we've to add it optionally
        if (isset($label) && $label !== false) {
          $tracking_number = $label->get_number();
          $carrier = $label->get_shipping_provider();
        } else {
          $tracking_number = $shipment->get_tracking_id();
          $carrier = explode('-', $shipment->get_shipping_provider())[0];
        }

        write_log(json_encode(array(
          "email" => $order->get_billing_email(),
          "order_id" => $shipment_order_id,
          "address" => $address,
          "carrier" => $carrier,
          "tracking_number" => $tracking_number
        )));

        $this->parcello->api->add_or_update_shipment(
          $order->get_billing_email(),
          $shipment_order_id,
          $address,
          $carrier,
          $tracking_number
        );
      }

      // Remove Tracking ID since it will be resolved by Germanized
      $order->delete_meta_data('_parcello_tracking_id');
      $order->save_meta_data();
    }
  }

  /**
   * Tracks a new Shipment using Parcellos own widget
   */
  private function track_shipment_with_parcello($order) {

    $address = $this->get_formatted_address_from_order($order);

    $parcello_shipments = json_decode($order->get_meta('_parcello_shipments'), true);

    $response = null;

    foreach ($parcello_shipments as $key => $shipment) {
      $shipment_order_id = $order->get_id();

      if ($key > 0) {
        $shipment_order_id = $order->get_id() . '_' . ($key + 1);
      }


      write_log('########## SHIPMENT #######');
      write_log($shipment);

      $response = $this->parcello->api->add_or_update_shipment(
        $order->get_billing_email(),
        $shipment_order_id,
        $address,
        $shipment['carrier'],
        $shipment['tracking_code']
      );
      write_log($response);
      $response = $response;
    }

    $custom_field = $order->get_meta('_parcello_tracking_url');

    if ($custom_field != '') {
      $order->update_meta_data('_parcello_tracking_url', $response->data->default_tracking_url);
    } else {
      $order->add_meta_data('_parcello_tracking_url', $response->data->default_tracking_url);
    }
    $order->save_meta_data();
  }

  /**
   * Save new Token
   */
  public function update_token() {
    if (isset($_POST['parcello_connect_account_nonce']) && wp_verify_nonce($_POST['parcello_connect_account_nonce'], 'parcello_connect_account_form_nonce')) {

      $token = base64_encode($_POST['parcello']['token']);

      $this->add_option_if_not_exist('parcello_token', $token);

      wp_redirect(esc_url_raw(add_query_arg(
        array(
          'parcello_connected' => "successful",
          'token' => get_option("parcello_token")
        ),
        admin_url('admin.php?page=' . $this->parcello->plugin_name)
      )));
      exit;
    } else {
      wp_die('Invalid nonce specified', 'Error', array(
        'response' => 403,
        'back_link' => 'admin.php?page=' . $this->parcello->plugin_name,
      ));
    }
  }

  /**
   * Updates the custom css of the tracking page
   */
  public function update_custom_css() {
    if ($this->parcello->is_connected()) {
      if (isset($_POST['parcello_update_custom_css_nonce']) && wp_verify_nonce($_POST['parcello_update_custom_css_nonce'], 'parcello_update_custom_css_form_nonce')) {

        $css = stripcslashes(sanitize_textarea_field($_POST['parcello']['css']));

        $this->parcello->api->set_custom_css($css);

        wp_redirect(esc_url_raw(add_query_arg(
          array(
            'subpage' => "custom_css",
            'custom_css_added' => "successful",
          ),
          admin_url('admin.php?page=' . $this->parcello->plugin_name)
        )));
        exit;
      } else {
        wp_die('Invalid nonce specified', 'Error', array(
          'response' => 403,
          'back_link' => 'admin.php?page=' . $this->parcello->plugin_name,
        ));
      }
    }
  }

  /**
   * Handles the change of a posts meta field
   */
  public function on_change_post_meta_fields($meta_id, $object_id, $meta_key, $_meta_value) {
    write_log('Meta fields changed');
    write_log($meta_key);
    if (get_option('parcello_tracking_method') == 'meta_fields') {
      $post = get_post($object_id);

      if ($post->post_type == 'shop_order') {
        if ($meta_key === get_option('parcello_tracking_id_field') || $meta_key === get_option('parcello_tracking_carrier_field')) {
          write_log('It was the carrier or code field');
          $this->add_or_update_tracking($object_id);
        }
      }
    }
  }

  /**
   * Checks whether Germanized is installed or not
   */
  public function is_germanized_installed() {
    return function_exists("wc_gzd_get_shipments_by_order");
  }

  /**
   * Returns the currently used tracking method
   */
  public function get_tracking_method() {
    return get_option('parcello_tracking_method');
  }

  /**
   * Update the tracking page and sync it with the parcello api
   */
  public function update_tracking_page() {
    if ($this->parcello->is_connected()) {
      if (isset($_POST['parcello_update_tracking_page_nonce']) && wp_verify_nonce($_POST['parcello_update_tracking_page_nonce'], 'parcello_update_tracking_page_form_nonce')) {

        $page_id = sanitize_key($_POST['parcello']['tracking_page']);

        $this->parcello->api->set_tracking_page($page_id);

        if (!get_option('parcello_tracking_page')) {
          add_option('parcello_tracking_page', $page_id);
        } else {
          update_option('parcello_tracking_page', $page_id);
        }

        wp_redirect(esc_url_raw(add_query_arg(
          array(
            'parcello_connected' => 'true'
          ),
          admin_url('admin.php?page=' . $this->parcello->plugin_name)
        )));

        do_action('parcello_tracking_page_updated', $page_id);
        exit;
      } else {
        wp_die(__('Invalid nonce specified', 'parcello'), __('Error', 'parcello'), array(
          'response' => 403,
          'back_link' => 'admin.php?page=' . $this->parcello->plugin_name,
        ));
      }
    }
  }

  /**
   * Update the tracking method
   */
  public function update_tracking_method() {
    if ($this->parcello->is_connected()) {
      if (isset($_POST['parcello_update_tracking_method_nonce']) && wp_verify_nonce($_POST['parcello_update_tracking_method_nonce'], 'parcello_update_tracking_method_form_nonce')) {

        $tracking_method = sanitize_key($_POST['parcello']['tracking_method']);

        $this->add_option_if_not_exist('parcello_tracking_method', $tracking_method);

        write_log('$tracking_method');
        write_log($tracking_method);

        if ($tracking_method == 'meta_fields') {
          $tracking_id_field = sanitize_key($_POST['parcello']['tracking_id_field']);
          $tracking_carrier_field = sanitize_key($_POST['parcello']['tracking_carrier_field']);

          $this->add_option_if_not_exist('parcello_tracking_id_field', $tracking_id_field);
          $this->add_option_if_not_exist('parcello_tracking_carrier_field', $tracking_carrier_field);
        } else {
          delete_option('parcello_tracking_id_field');
          delete_option('parcello_tracking_carrier_field');
        }

        wp_redirect(esc_url_raw(add_query_arg(
          array(
            'tracking_method_updated' => 'true'
          ),
          admin_url('admin.php?page=' . $this->parcello->plugin_name . '&subpage=settings')
        )));

        do_action('parcello_tracking_method_updated', $tracking_method);
        exit;
      } else {
        wp_die(__('Invalid nonce specified', 'parcello'), __('Error', 'parcello'), array(
          'response' => 403,
          'back_link' => 'admin.php?page=' . $this->parcello->plugin_name,
        ));
      }
    }
  }

  private function add_option_if_not_exist($key, $value) {
    if (null !== get_option($key)) {
      update_option($key, $value);
    } else {
      add_option($key, $value);
    }
  }

  /**
   * Returns the address of an order in a formatted version which can be used by parcello
   */
  private function get_formatted_address_from_order($order) {
    // After that, we're resolving the address the packet is send to
    if ($order->has_shipping_address()) {
      $address = $order->get_address('shipping');
    } else {
      $address = $order->get_address('billing');
    }

    // Since we want to send only the Data we'll need (DSGVO) we'll create our own formatted address
    $address = $address['address_1'] . ', ' . $address['postcode'] . ' ' . $address['city'];

    return $address;
  }

  /**
   * Removes all special charactert of a given string
   */
  private function remove_special_char($str) {
    $res = preg_replace('/[0-9\@\.\;\" "\_]+/', '', $str);
    return $res;
  }
}
