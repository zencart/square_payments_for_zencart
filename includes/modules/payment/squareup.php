<?php
/**
 * Square payment module
 *
 *
 *
 * @package squareup
 * @copyright Copyright 2003-2017 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: Author: Chris Brown <drbyte@zen-cart.com> New in v1.5.6 $
 */
/**
 * Square Payment module class
 */
class squareup extends base {
  /**
   * $code determines the internal 'code' name used to designate "this" payment module
   *
   * @var string
   */
  var $code;
  /**
   * $moduleVersion is the plugin version number
   */
  var $moduleVersion = '0.10';
  /**
   * $title is the displayed name for this payment method
   *
   * @var string
   */
  var $title;
  /**
   * $description is admin-display details for this payment method
   *
   * @var string
   */
  var $description;
  /**
   * $enabled determines whether this module shows or not... in catalog.
   *
   * @var boolean
   */
  var $enabled;

  /**
   * $sort_order determines the display-order of this module to customers
   */
  var $sort_order;
  /**
   * $commError and $commErrNo are CURL communication error details for debug purposes
   */
  var $commError, $commErrNo;
  /**
   * transaction vars hold the IDs of the completed payment
   */
  var $transaction_id, $transaction_messages, $auth_code;
  /**
   * internal vars
   */
  private $avs_codes, $cvv_codes;


  /**
   * Constructor
   */
  function __construct() {
    global $order;

    require DIR_FS_CATALOG . DIR_WS_CLASSES . 'vendors/square/connect/autoload.php';

    $this->code = 'squareup';
    $this->title = MODULE_PAYMENT_SQUAREUP_TEXT_CATALOG_TITLE; // Payment module title in Catalog
    if (IS_ADMIN_FLAG === true) {
      $this->title = MODULE_PAYMENT_SQUAREUP_TEXT_ADMIN_TITLE;
      if (defined('MODULE_PAYMENT_SQUAREUP_STATUS')) {
        if (MODULE_PAYMENT_SQUAREUP_APPLICATION_ID == '') $this->title .= '<span class="alert"> (not configured; API details needed)</span>';
        if (MODULE_PAYMENT_SQUAREUP_TESTING_MODE == 'Sandbox') $this->title .= '<span class="alert"> (Sandbox mode)</span>';
        
        $new_version_details = plugin_version_check_for_updates(2071, $this->moduleVersion);
        if ($new_version_details !== false) {
            $this->title .= '<span class="alert">' . ' - NOTE: A NEW VERSION OF THIS PLUGIN IS AVAILABLE. <a href="' . $new_version_details['link'] . '" target="_blank">[Details]</a>' . '</span>';
        }
      }
    }

    $this->description = 'SquareUp ' . $this->moduleVersion . '<br>' . MODULE_PAYMENT_SQUAREUP_TEXT_DESCRIPTION;
    $this->enabled = ((MODULE_PAYMENT_SQUAREUP_STATUS == 'True') ? true : false);
    $this->sort_order = MODULE_PAYMENT_SQUAREUP_SORT_ORDER;

    // determine order-status for transactions
    if ((int)MODULE_PAYMENT_SQUAREUP_ORDER_STATUS_ID > 0) {
      $this->order_status = MODULE_PAYMENT_SQUAREUP_ORDER_STATUS_ID;
    }
    // Reset order status to pending if capture pending:
    if (MODULE_PAYMENT_SQUAREUP_TRANSACTION_TYPE == 'authorize') {
      $this->order_status = 1;
    }

    $this->_logDir = DIR_FS_LOGS;

    // check for zone compliance and any other conditionals
    if (is_object($order)) $this->update_status();

  }


  function update_status() {
    global $order, $db;
    if ($this->enabled == false || (int)MODULE_PAYMENT_SQUAREUP_ZONE == 0) {
      return;
    }
    $check_flag = false;
    $sql = "SELECT zone_id FROM " . TABLE_ZONES_TO_GEO_ZONES . " WHERE geo_zone_id = '" . (int)MODULE_PAYMENT_SQUAREUP_ZONE . "' AND zone_country_id = '" . (int)$order->billing['country']['id'] . "' ORDER BY zone_id";
    $checks = $db->Execute($sql);
    foreach ($checks as $check) {
      if ($check['zone_id'] < 1) {
        $check_flag = true;
        break;
      } elseif ($check['zone_id'] == $order->billing['zone_id']) {
        $check_flag = true;
        break;
      }
    }
    if ($check_flag == false) {
      $this->enabled = false;
    }
  }
  function javascript_validation() {
    return '';
  }
  function selection() {  
    // helper for auto-selecting the radio-button next to this module so the user doesn't have to make that choice
    $onFocus = ' onfocus="methodSelect(\'pmt-' . $this->code . '\')"';

    $selection = array(
        'id' => $this->code,
        'module' => MODULE_PAYMENT_SQUAREUP_TEXT_CATALOG_TITLE,
        'fields' => array(
            array(
                'title' => MODULE_PAYMENT_SQUAREUP_TEXT_CREDIT_CARD_NUMBER,
                'field' => '<div id="' . $this->code . '_cc-number"></div><div id="sq-card-brand"></div>',
            ),
            array(
                'title' => MODULE_PAYMENT_SQUAREUP_TEXT_CVV,
                'field' => '<div id="' . $this->code . '_cc-cvv"></div>',
            ),
            array(
                'title' => MODULE_PAYMENT_SQUAREUP_TEXT_CREDIT_CARD_EXPIRES,
                'field' => '<div id="' . $this->code . '_cc-expires"></div>',
            ),
            array(
                'title' => MODULE_PAYMENT_SQUAREUP_TEXT_CREDIT_CARD_POSTCODE,
                'field' => '<div id="' . $this->code . '_cc-postcode"></div>',
            ),
            array(
                'field' => '<div id="card-errors" class="alert error"></div>',
            ),
            array(
                'title' => '',
                'field' => '<input type="hidden" id="card-nonce" name="nonce">' .
                '<input type="hidden" id="card-type" name="' . $this->code . '_cc_type">' .
                '<input type="hidden" id="card-four" name="' . $this->code . '_cc_number">' .
                '<input type="hidden" id="card-exp" name="' . $this->code . '_cc_exp">',
            ),
        ),
    );
    return $selection;
  }

  function pre_confirmation_check() {
    global $messageStack;
    if (!isset($_POST['nonce']) || trim($_POST['nonce']) == '') {
      $messageStack->add_session('checkout_payment', MODULE_PAYMENT_SQUAREUP_ERROR_INVALID_CARD_DATA, 'error');
      zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false));
    }
  }

  function confirmation() {
    $confirmation = array('fields' => array(array('title' => MODULE_PAYMENT_SQUAREUP_TEXT_CREDIT_CARD_TYPE,
                                                  'field' => zen_output_string_protected($_POST[$this->code . '_cc_type'])),
                                            array('title' => MODULE_PAYMENT_SQUAREUP_TEXT_CREDIT_CARD_NUMBER,
                                                  'field' => zen_output_string_protected($_POST[$this->code . '_cc_number'])),
                                            array('title' => MODULE_PAYMENT_SQUAREUP_TEXT_CREDIT_CARD_EXPIRES,
                                                  'field' => zen_output_string_protected($_POST[$this->code . '_cc_exp'])),
                                            ));
    return $confirmation;
  }

  function process_button() {
    $process_button_string = zen_draw_hidden_field($this->code . '_nonce', $_POST['nonce']);
    $process_button_string .= zen_draw_hidden_field('cc_type', zen_output_string_protected($_POST[$this->code . '_cc_type']));
    $process_button_string .= zen_draw_hidden_field('cc_number', zen_output_string_protected($_POST[$this->code . '_cc_number']));
    $process_button_string .= zen_draw_hidden_field('cc_expires', zen_output_string_protected($_POST[$this->code . '_cc_exp']));
    return $process_button_string;
  }

  function before_process() {
    global $messageStack, $order, $currencies;

    if (!isset($_POST[$this->code . '_nonce']) || trim($_POST[$this->code . '_nonce']) == '') {
      $messageStack->add_session('checkout_payment', MODULE_PAYMENT_SQUAREUP_ERROR_INVALID_CARD_DATA, 'error');
      zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false));
    }

    $order->info['cc_type'] = zen_output_string_protected($_POST['cc_type']);
    $order->info['cc_number']  = zen_output_string_protected($_POST['cc_number']);
    if (!strpos($order->info['cc_number'], 'XX')) {
      $order->info['cc_number']  = 'XXXX' . zen_output_string_protected(substr($_POST['cc_number'], -4));
    }
    $order->info['cc_expires'] = zen_output_string_protected($_POST['cc_expires']);
    $order->info['cc_cvv']     = '***';


    // @TODO - consider converting currencies if the gateway requires


    // format purchase amount
    $payment_amount = $order->info['total'];
    $decimal_places = $currencies->get_decimal_places($order->info['currency']);
    if ($decimal_places > 0) {
      $payment_amount = $payment_amount * pow(10, $decimal_places); // Future: Exponentiation Operator ** requires PHP 5.6
    }

    $billing_address = array(
        'address_line' => $order->billing['street_address'],
        'address_line_2' => $order->billing['suburb'],
        'locality' => $order->billing['city'],
        'administrative_district_level_1' => zen_get_zone_code($order->billing['country']['id'], $order->billing['zone_id'], $order->billing['state']),
        'postal_code' => $order->billing['postcode'],
        'country' => $order->billing['country']['iso_code_2'],
        'last_name' => $order->billing['lastname'],
        'organization' => $order->billing['company'],
        );
    if ($order->delivery !== false) {
        $shipping_address = array(
            'address_line' => $order->delivery['street_address'],
            'address_line_2' => $order->delivery['suburb'],
            'locality' => $order->delivery['city'],
            'administrative_district_level_1' => zen_get_zone_code($order->delivery['country']['id'], $order->delivery['zone_id'], $order->delivery['state']),
            'postal_code' => $order->delivery['postcode'],
            'country' => $order->delivery['country']['iso_code_2'],
            'last_name' => $order->delivery['lastname'],
            'organization' => $order->delivery['company'],
            );
    }

    $request_body = array (
      'card_nonce' => $_POST[$this->code . '_nonce'],
      'amount_money' => array (
        // Monetary amounts are specified in the smallest unit of the applicable currency. This amount is in cents. 
        'amount' => (int)$payment_amount,
        'currency' => strtoupper($order->info['currency']),
      ),
      'delay_capture' => (bool)(MODULE_PAYMENT_SQUAREUP_TRANSACTION_TYPE === 'authorize'),
      'reference_id' => strval(substr(zen_session_id(), 0, 40)), // 40 char max
      'integration_id' => 'sqi_' . 'b6ff0cd7acc14f7ab24200041d066ba6', // required 32 chars
      'note' => substr(htmlentities(STORE_NAME), 0, 60), // 60 char max
      'customer_id' => $_SESSION['customer_id'],
      'buyer_email_address' => $order->customer['email_address'],
      'billing_address' => $billing_address,
      'shipping_address' => $shipping_address,
      'idempotency_key' => uniqid(),
    );


    $this->token_refresh_check();

    $access_token = strval(MODULE_PAYMENT_SQUAREUP_TESTING_MODE == 'Live' ? MODULE_PAYMENT_SQUAREUP_ACCESS_TOKEN : MODULE_PAYMENT_SQUAREUP_SANDBOX_TOKEN);
    SquareConnect\Configuration::getDefaultConfiguration()->setAccessToken($access_token);//->setDebug(MODULE_PAYMENT_SQUAREUP_TESTING_MODE != 'Live');

    // Use or lookup location id
    $location_id = trim(strval(MODULE_PAYMENT_SQUAREUP_LOCATION_ID));
    if (empty($location_id)) {
        $location_api = new SquareConnect\Api\LocationApi();
        try {
            $result = $location_api->listLocations($access_token);
            $first_location = $result->getLocations()[0];
            $location_id = $first_location->getId();
            unset($result);
        } catch (Exception $e) {
            trigger_error('Exception when calling LocationApi->listLocations: ' . $e->getMessage(), E_USER_NOTICE);
        }
    }
    
    $transaction_api = new \SquareConnect\Api\TransactionApi();
    # The SDK throws an exception if a Connect endpoint responds with anything besides
    # a 200-level HTTP code. This block catches any exceptions that occur from the request.
    try {
      if (MODULE_PAYMENT_SQUAREUP_TESTING_MODE != 'Live') unset($request_body['integration_id']);
      $result = $transaction_api->charge($access_token, $location_id, $request_body);

      // // @TODO --  REMOVE DEBUG OUTPUT, and take action to store results
      // echo '<pre>';
      // print_r($result);
      // echo '</pre>';
      $errors_object = $result->getErrors(); // (getCode(), getDetail())
      $transaction = $result->getTransaction(); // getId()
    } catch (\SquareConnect\ApiException $e) {
        $this->logTransactionData(array('id'=>'FATAL ERROR'), $request_body, print_r($e->getResponseBody(), true));
        trigger_error("Square Connect error. \nResponse Body:\n".print_r($e->getResponseBody(), true) . "\nResponse Headers:\n" . print_r($e->getResponseHeaders(), true), E_USER_NOTICE);
        $messageStack->add_session('checkout_payment', MODULE_PAYMENT_SQUAREUP_TEXT_COMM_ERROR, 'error');
        zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false));
    }

    // log the response data
    $this->logTransactionData($transaction, $request_body, strval($errors_object));

    // analyze the response

    if (sizeof($errors_object)) {
        $msg = '';
        foreach($errors_object as $err) {
            $code = $err->getCode();
            $detail = $err->getDetail();
            $msg .= "$code: $detail\n<br>";
        }
        $this->transaction_messages = $msg;
        $messageStack->add_session('checkout_payment', MODULE_PAYMENT_SQUAREUP_TEXT_ERROR . ' [' . $msg . ']', 'error');
        zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false));
    }

    if (!empty($transaction->getId())) {
        $this->auth_code = $transaction->getId();
        $this->transaction_id = $transaction->getId() . ' ' . $transaction->getCreatedAt();
        return true;
    }

    // generic 'declined' message response
    $messageStack->add_session('checkout_payment', MODULE_PAYMENT_SQUAREUP_ERROR_DECLINED, 'error');
    zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false));
  }
  /**
   * Post-process activities. Updates the order-status history data with the auth code from the transaction.
   *
   * @return boolean
   */
  function after_process() {
    global $insert_id, $db, $order;
    $sql = "insert into " . TABLE_ORDERS_STATUS_HISTORY . " (comments, orders_id, orders_status_id, customer_notified, date_added) values (:orderComments, :orderID, :orderStatus, -1, now() )";
    $sql = $db->bindVars($sql, ':orderComments', 'Credit Card payment.  TransID: ' . $this->transaction_id, 'string');
    $sql = $db->bindVars($sql, ':orderID', $insert_id, 'integer');
    $sql = $db->bindVars($sql, ':orderStatus', $this->order_status, 'integer');
    $db->Execute($sql);
    return true;
  }




  // This should also be callable from a cron job
  function token_refresh_check() 
  {
    // @TODO:
    // - check that we have a token
    // - check its expiry date
    // - if expiry is less than 3 weeks away, refresh  (ie: refresh weekly)
    // - if can't refresh, disable module by setting status to false, and emailing storeowner to alert them
  }

  function getAuthorizeURL()
  {
    $url = 'https://connect.squareup.com/oauth2/authorize?';
    $params = http_build_query(
        array('client_id' => MODULE_PAYMENT_SQUAREUP_APPLICATION_ID, 
              'scope' => 'MERCHANT_PROFILE_READ PAYMENTS_WRITE',
              'state' => uniqid(),
              ));
    return $url . $params;
  }

  function getToken($code)
  {
    $url = 'https://connect.squareup.com/oauth2/token';
    $data = json_encode(
        array('client_id' => MODULE_PAYMENT_SQUAREUP_APPLICATION_ID, 
              'client_secret' => MODULE_PAYMENT_SQUAREUP_APPLICATION_SECRET,
              'code' => $code,
              ));
    // @TODO - send a POST

    // @TODO - get reply:

    $this->setAccessToken($response);

  }

  function setAccessToken($payload)
  {
/* 
  "access_token": "YOUR_ACCESS_TOKEN",
  "token_type": "bearer",
  "expires_at": "2016-08-10T19:42:08Z",
  "merchant_id": "YOUR_BUSINESS_ID"
*/
    // @TODO - store MODULE_PAYMENT_SQUAREUP_ACCESS_TOKEN and MODULE_PAYMENT_SQUAREUP_REFRESH_EXPIRES_AT

  }

  function getRefreshToken()
  {
    $url = 'https://connect.squareup.com/oauth2/clients/' . MODULE_PAYMENT_SQUAREUP_APPLICATION_ID . '/access-token/renew';
    $body = '{"access_token": "' . MODULE_PAYMENT_SQUAREUP_ACCESS_TOKEN . '"}';
    // @TODO POST 
    // $response = 
    $this->setAccessToken($response);
  }


  function check() {
    global $db;
    if (!isset($this->_check)) {
      $check_query = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_SQUAREUP_STATUS'");
      $this->_check = $check_query->RecordCount();
    }
    if ($this->_check > 0) $this->install(); // install any missing keys, if any
    return $this->_check;
  }
  function install() {
    global $db;

    if (!defined('MODULE_PAYMENT_SQUAREUP_STATUS')) $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable SquareUp Module', 'MODULE_PAYMENT_SQUAREUP_STATUS', 'True', 'Do you want to accept Square payments?', '6', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
    if (!defined('MODULE_PAYMENT_SQUAREUP_SORT_ORDER')) $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_SQUAREUP_SORT_ORDER', '0', 'Sort order of displaying payment options to the customer. Lowest is displayed first.', '6', '0', now())");
    if (!defined('MODULE_PAYMENT_SQUAREUP_ZONE')) $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Payment Zone', 'MODULE_PAYMENT_SQUAREUP_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '2', 'zen_get_zone_class_title', 'zen_cfg_pull_down_zone_classes(', now())");
    if (!defined('MODULE_PAYMENT_SQUAREUP_ORDER_STATUS_ID')) $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Order Status', 'MODULE_PAYMENT_SQUAREUP_ORDER_STATUS_ID', '2', 'Set the status of orders made with this payment module to this value', '6', '0', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
    if (!defined('MODULE_PAYMENT_SQUAREUP_TRANSACTION_TYPE')) $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Transaction Type', 'MODULE_PAYMENT_SQUAREUP_TRANSACTION_TYPE', 'purchase', 'Should payments be [authorized] only, or be completed [purchases]?', '6', '0', 'zen_cfg_select_option(array(\'authorize\', \'purchase\'), ', now())");
    if (!defined('MODULE_PAYMENT_SQUAREUP_APPLICATION_ID')) $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function) values ('Application ID', 'MODULE_PAYMENT_SQUAREUP_APPLICATION_ID', 'sq0idp-', 'Enter the Application ID from your App settings', '6', '0',  now(), 'zen_cfg_password_display')");
    if (!defined('MODULE_PAYMENT_SQUAREUP_APPLICATION_SECRET')) $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function) values ('Application Secret (OAuth)', 'MODULE_PAYMENT_SQUAREUP_APPLICATION_SECRET', 'sq0csp-', 'Enter the Application Secret from your App OAuth settings', '6', '0',  now(), 'zen_cfg_password_display')");
    if (!defined('MODULE_PAYMENT_SQUAREUP_LOCATION_ID')) $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function) values ('Location ID', 'MODULE_PAYMENT_SQUAREUP_LOCATION_ID', '', 'Enter the (Store) Location ID from your account settings. You can have multiple locations configured in your account; this setting lets you specify which location your sales should be attributed to.', '6', '0',  now(), 'zen_cfg_password_display')");
    if (!defined('MODULE_PAYMENT_SQUAREUP_LOGGING')) $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Log Mode', 'MODULE_PAYMENT_SQUAREUP_LOGGING', 'Log on Failures and Email on Failures', 'Would you like to enable debug mode?  A complete detailed log of failed transactions may be emailed to the store owner.', '6', '0', 'zen_cfg_select_option(array(\'Off\', \'Log Always\', \'Log on Failures\', \'Log Always and Email on Failures\', \'Log on Failures and Email on Failures\', \'Email Always\', \'Email on Failures\'), ', now())");
    // DEVELOPER USE ONLY
    if (!defined('MODULE_PAYMENT_SQUAREUP_ACCESS_TOKEN')) $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function) values ('Live Merchant Token', 'MODULE_PAYMENT_SQUAREUP_ACCESS_TOKEN', '', 'Enter the Access Token for Live transactions from your account settings', '6', '0',  now(), 'zen_cfg_password_display')");
    if (!defined('MODULE_PAYMENT_SQUAREUP_REFRESH_EXPIRES_AT')) $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function) values ('Square Refresh Token (read only)', 'MODULE_PAYMENT_SQUAREUP_REFRESH_EXPIRES_AT', '', 'DO NOT EDIT', '6', '0',  now(), 'zen_cfg_password_display')");
    if (!defined('MODULE_PAYMENT_SQUAREUP_SANDBOX_TOKEN')) $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function) values ('Sandbox Merchant Token', 'MODULE_PAYMENT_SQUAREUP_SANDBOX_TOKEN', 'sq0atb-nn_yQbQgZaA3VhFEykuYlQ', 'Enter the Sandbox Access Token from your account settings', '6', '0',  now(), 'zen_cfg_password_display')");
    if (!defined('MODULE_PAYMENT_SQUAREUP_TESTING_MODE')) $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Sandbox/Live Mode', 'MODULE_PAYMENT_SQUAREUP_TESTING_MODE', 'Live', 'Use [Live] for real transactions<br>Use [Sandbox] for developer testing', '6', '0', 'zen_cfg_select_option(array(\'Live\', \'Sandbox\'), ', now())");

  }
  function remove() {
    global $db;
    $db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key like 'MODULE\_PAYMENT\_SQUAREUP\_%'");
  }
  function keys() {
    return array(
       'MODULE_PAYMENT_SQUAREUP_STATUS',
       'MODULE_PAYMENT_SQUAREUP_SORT_ORDER',
       'MODULE_PAYMENT_SQUAREUP_ZONE',
       'MODULE_PAYMENT_SQUAREUP_TRANSACTION_TYPE',
       'MODULE_PAYMENT_SQUAREUP_ORDER_STATUS_ID',
       'MODULE_PAYMENT_SQUAREUP_APPLICATION_ID',
       'MODULE_PAYMENT_SQUAREUP_APPLICATION_SECRET',
       'MODULE_PAYMENT_SQUAREUP_LOCATION_ID',
       'MODULE_PAYMENT_SQUAREUP_LOGGING',
       // Developer use only
       'MODULE_PAYMENT_SQUAREUP_ACCESS_TOKEN',
       'MODULE_PAYMENT_SQUAREUP_SANDBOX_TOKEN',
       'MODULE_PAYMENT_SQUAREUP_TESTING_MODE',
     );
  }

  /**
   * Log transaction errors if enabled
   */
  private function logTransactionData($response, $payload, $errors) {
    global $db;

    $logMessage = date('M-d-Y h:i:s') .
                    "\n=================================\n\n" .
                    ($errors !='' ? 'Error Dump: ' . $errors . "\n\n" : '') .
                    'Transaction ID assigned: ' . $response['id'] . "\n" .
                    'Sent to Square: ' . print_r($payload, true) . "\n\n" .
                    'Results Received back from Square: ' . print_r($response, true) . "\n\n";

    if (strstr(MODULE_PAYMENT_SQUAREUP_LOGGING, 'Log Always') || ($errors != '' && strstr(MODULE_PAYMENT_SQUAREUP_LOGGING, 'Log on Failures'))) {
      $key = $response['id'] . '_' . time() . '_' . zen_create_random_value(4);
      $file = $this->_logDir . '/' . 'Squareup_' . $key . '.log';
      if ($fp = @fopen($file, 'a')) {
        fwrite($fp, $logMessage);
        fclose($fp);
      }
    }
    if (($errors != '' && stristr(MODULE_PAYMENT_SQUAREUP_LOGGING, 'Email on Failures')) || strstr(MODULE_PAYMENT_SQUAREUP_LOGGING, 'Email Always')) {
      zen_mail(STORE_NAME, STORE_OWNER_EMAIL_ADDRESS, 'Square Alert (customer transaction error) ' . date('M-d-Y h:i:s'), $logMessage, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS, array('EMAIL_MESSAGE_HTML'=>nl2br($logMessage)), 'debug');
    }
  }


}

// for backward compatibility with older ZC versions before v152 which didn't have this function:
if (!function_exists('plugin_version_check_for_updates')) {
  function plugin_version_check_for_updates($plugin_file_id = 0, $version_string_to_compare = '')
  {
    if ($plugin_file_id == 0) return FALSE;
    $new_version_available = FALSE;
    $lookup_index = 0;
    $url1 = 'https://plugins.zen-cart.com/versioncheck/'.(int)$plugin_file_id;
    $url2 = 'https://www.zen-cart.com/versioncheck/'.(int)$plugin_file_id;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$url1);
    curl_setopt($ch, CURLOPT_VERBOSE, 0);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 9);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 9);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Plugin Version Check [' . (int)$plugin_file_id . '] ' . HTTP_SERVER);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $errno = curl_errno($ch);

    if ($error > 0) {
      trigger_error('CURL error checking plugin versions: ' . $errno . ':' . $error . "\nTrying http instead.");
      curl_setopt($ch, CURLOPT_URL, str_replace('tps:', 'tp:', $url1));
      $response = curl_exec($ch);
      $error = curl_error($ch);
      $errno = curl_errno($ch);
    }
    if ($error > 0) {
      trigger_error('CURL error checking plugin versions: ' . $errno . ':' . $error . "\nTrying www instead.");
      curl_setopt($ch, CURLOPT_URL, str_replace('tps:', 'tp:', $url2));
      $response = curl_exec($ch);
      $error = curl_error($ch);
      $errno = curl_errno($ch);
    }
    curl_close($ch);
    if ($error > 0 || $response == '') {
      trigger_error('CURL error checking plugin versions: ' . $errno . ':' . $error . "\nTrying file_get_contents() instead.");
      $ctx = stream_context_create(array('http' => array('timeout' => 5)));
      $response = file_get_contents($url1, null, $ctx);
      if ($response === false) {
        trigger_error('file_get_contents() error checking plugin versions.' . "\nTrying http instead.");
        $response = file_get_contents(str_replace('tps:', 'tp:', $url1), null, $ctx);
      }
      if ($response === false) {
        trigger_error('file_get_contents() error checking plugin versions.' . "\nAborting.");
        return false;
      }
    }

    $data = json_decode($response, true);
    if (!$data || !is_array($data)) return false;
    // compare versions
    if (strcmp($data[$lookup_index]['latest_plugin_version'], $version_string_to_compare) > 0) $new_version_available = TRUE;
    // check whether present ZC version is compatible with the latest available plugin version
    if (!in_array('v'. PROJECT_VERSION_MAJOR . '.' . PROJECT_VERSION_MINOR, $data[$lookup_index]['zcversions'])) $new_version_available = FALSE;
    return ($new_version_available) ? $data[$lookup_index] : FALSE;
  }
}
