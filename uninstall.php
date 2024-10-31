<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
  die;
}

/**
 * Removes all Data since a user should have a clean Wordpress after uninstallation.
 */
function parcello_uninstall() {
  // Delete all options
  delete_option('parcello_token');
  delete_option('parcello_tracking_page');
  delete_option('parcello_tracking_id_field');
  delete_option('parcello_tracking_carrier_field');
}

parcello_uninstall();
