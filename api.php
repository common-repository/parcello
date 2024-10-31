<?php

/**
 * Our customer install this Plugin to connect their parcello account with their WooCommerce shop
 * So we have to handle requests to the parcello rest api
 */
class ParcelloAPI {

  private $dev = false;
  private $TEST_URL = '';
  private $BASE_URL = "https://api-b2b.parcello.org/";

  /**
   * Returns the url selected by configuration
   */
  public function get_url($endpoint) {
    return $this->dev ? $this->TEST_URL . $endpoint : $this->BASE_URL . $endpoint;
  }

  /**
   * Updates the tracking page on parcello
   * @param string $page_id
   */
  public function set_tracking_page($page_id) {
    $this->post_to_api("tracking-page-url/", array(
      'url' => get_permalink($page_id)
    ));
  }

  /**
   * Retrieves the current state of email verification
   */
  public function get_email_settings() {
    $response = $this->get_from_api('emails-settings/');
    return $response;
  }

  /**
   * Retrieves the shipments tracked by parcello
   */
  public function get_shipments() {
    $response = $this->get_from_api('shipments?orderBy=createdAt&sort=desc&perPage=10&page=1');
    return $response;
  }

  /**
   * Retrieves the shipments tracked by parcello
   * @param string $id
   */
  public function get_single_shipment($id) {
    $response = $this->get_from_api('shipment/' . $id);
    return $response;
  }

  /**
   * Adds an shipment
   * @param string $email
   * @param string $order_id
   * @param string $formatted_address
   * @param string $carrier = null
   * @param string $tracking_number = null
   */
  public function add_or_update_shipment($email, $order_id, $formatted_address, $carrier = null, $tracking_number = null) {
    $response = $this->put_to_api(
      "shipment",
      array(
        'email' => $email,
        'order_id' => $order_id,
        'address' => array("formatted" => $formatted_address),
        'carrier' => $carrier,
        'tracking_id' => $tracking_number,
      )
    );

    if (isset($response['response']['code'])) {
      if ($response['response']['code'] == 200) {
        write_log('$response');
        write_log($response['response']['code']);
        write_log($response['body']);
        write_log("add_or_update_shipment");
        $body = json_decode($response['body']);
        write_log($body);
        if ($body->message == "Shipment Added.") {
          $order = wc_get_order($order_id);
          // The text for the note
          $tracking_url = $body->data->tracking_url;
          $note = 'Parcello: The shipment was added. Link: ' . $tracking_url;
          // Add the note
          $order->add_order_note($note);
          //$order->save();
        }
      }
    }

    return $response;
  }

  /**
   * Returns current custom css from parcello
   */
  public function get_custom_css() {
    $response = $this->get_from_api('custom-css/');
    return $response;
  }

  /**
   * Sets the custom css on parcello
   */
  public function set_custom_css($css) {
    $response = $this->post_to_api('custom-css/', array(
      "css" => $css
    ));
    return $response;
  }

  /**
   * Returns the Header value for Authorization
   */
  private function get_authorization_header() {
    return "Bearer " . base64_decode(get_option('parcello_token'));
  }

  /**
   * Sends a POST request to the parcello api
   * @param string $endpoint
   * @param array $body
   */
  private function post_to_api($endpoint, $body) {
    $url = $this->get_url($endpoint);

    $response = wp_remote_post($url, array(
      'method' => 'POST',
      'httpversion' => '1.0',
      'headers' => array(
        'Authorization' => $this->get_authorization_header(),
        'Content-Type' => 'application/json; charset=utf-8',
        'Referer' => '-'
      ),
      'redirection' => 100,
      'data_format' => 'body',
      'body' => json_encode($body),
      'cookies' => array()
    ));

    write_log('\n##########$response##########');
    write_log($response);
    write_log($this->get_authorization_header());

    if (is_wp_error($response)) {
    } else {
      return $response;
    }
  }

  /**
   * Sends a PUT request to the parcello api
   * @param string $endpoint
   * @param array $body
   */
  private function put_to_api($endpoint, $body) {
    $url = $this->get_url($endpoint);

    $response = wp_remote_request($url, array(
      'method' => 'PUT',
      'timeout' => 45,
      'redirection' => 5,
      'httpversion' => '2.0',
      'blocking' => true,
      'headers' => array(
        'Authorization' => $this->get_authorization_header(),
        'Content-Type' => 'application/json; charset=utf-8',
        'Referer' => '-',
      ),
      'data_format' => 'body',
      'body' => json_encode($body),
      'cookies' => array()
    ));

    if (is_wp_error($response)) {
      write_log($response);
    } else {
      return $response;
    }
  }

  /**
   * Retrieve Data from the Parcello API
   * @param string $endpoint
   */
  private function get_from_api($endpoint) {
    $url = $this->BASE_URL . $endpoint;
    $response = wp_remote_get($url, array(
      "headers" => array(
        "Authorization" => $this->get_authorization_header(),
        'Referer' => '-',
      )
    ));


    if (is_wp_error($response)) {
      write_log($response);
    } else {
      return json_decode($response['body']);
    }
  }
}
