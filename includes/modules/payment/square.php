<?php
/**
 * Square payments module
 * www.squareup.com
 *
 * Integrated using SquareConnect PHP SDK v2.1.0
 *
 * @package square
 * @copyright Copyright 2003-2017 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: Author: Chris Brown <drbyte@zen-cart.com> New in v1.5.6 $
 */

if (!defined('TABLE_SQUARE_PAYMENTS')) define('TABLE_SQUARE_PAYMENTS', 'square_payments');

/**
 * Square Payments module class
 */
class square extends base
{
    /**
     * $code determines the internal 'code' name used to designate "this" payment module
     *
     * @var string
     */
    var $code;
    /**
     * $moduleVersion is the plugin version number
     */
    var $moduleVersion = '0.40';
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
     * the primary currency enabled in this gateway's merchant account (only 1 is supported; all others are converted)
     */
    private $gateway_currency;


    /**
     * Constructor
     */
    function __construct()
    {
        global $order;

        require DIR_FS_CATALOG . DIR_WS_CLASSES . 'vendors/square/connect/autoload.php';

        $this->code        = 'square';
        $this->enabled     = ((MODULE_PAYMENT_SQUARE_STATUS == 'True') ? true : false);
        $this->sort_order  = MODULE_PAYMENT_SQUARE_SORT_ORDER;
        $this->title       = MODULE_PAYMENT_SQUARE_TEXT_CATALOG_TITLE; // Payment module title in Catalog
        $this->description = 'Square ' . $this->moduleVersion . '<br>' . MODULE_PAYMENT_SQUARE_TEXT_DESCRIPTION;
        if (IS_ADMIN_FLAG === true) {
            $this->title = MODULE_PAYMENT_SQUARE_TEXT_ADMIN_TITLE;
            if (defined('MODULE_PAYMENT_SQUARE_STATUS')) {
                if (MODULE_PAYMENT_SQUARE_APPLICATION_ID == '') $this->title .= '<span class="alert"> (not configured; API details needed)</span>';
                if (MODULE_PAYMENT_SQUARE_ACCESS_TOKEN == '') {
                    $this->title       .= '<span class="alert"> (Access Token needed)</span>';
                    $this->description .= "\n" . '<br><br>' . sprintf(MODULE_PAYMENT_SQUARE_TEXT_NEED_ACCESS_TOKEN, $this->getAuthorizeURL());
                }
                if (MODULE_PAYMENT_SQUARE_TESTING_MODE == 'Sandbox') $this->title .= '<span class="alert"> (Sandbox mode)</span>';
                $new_version_details = plugin_version_check_for_updates(2071, $this->moduleVersion);
                if ($new_version_details !== false) {
                    $this->title .= '<span class="alert">' . ' - NOTE: A NEW VERSION OF THIS PLUGIN IS AVAILABLE. <a href="' . $new_version_details['link'] . '" target="_blank">[Details]</a>' . '</span>';
                }
            }
        }

        // determine order-status for transactions
        if ((int)MODULE_PAYMENT_SQUARE_ORDER_STATUS_ID > 0) {
            $this->order_status = MODULE_PAYMENT_SQUARE_ORDER_STATUS_ID;
        }
        // Reset order status to pending if capture pending:
        if (MODULE_PAYMENT_SQUARE_TRANSACTION_TYPE == 'authorize') {
            $this->order_status = 1;
        }

        $this->_logDir = DIR_FS_LOGS;

        // module can't work without a token; must be configured with OAUTH refreshable token
        if ((MODULE_PAYMENT_SQUARE_ACCESS_TOKEN == '' || MODULE_PAYMENT_SQUARE_REFRESH_EXPIRES_AT == '') && MODULE_PAYMENT_SQUARE_TESTING_MODE == 'Live') {
            $this->enabled = false;
        }

        // set the currency for the gateway (others will be converted to this one before submission) // ISO 4217 format
        $this->gateway_currency = MODULE_PAYMENT_SQUARE_CURRENCY;

        // check for zone compliance and any other conditionals
        if ($this->enabled && is_object($order)) $this->update_status();
    }


    function update_status()
    {
        global $order, $db;
        if ($this->enabled == false || (int)MODULE_PAYMENT_SQUARE_ZONE == 0) {
            return;
        }
        $check_flag = false;
        $sql        = "SELECT zone_id FROM " . TABLE_ZONES_TO_GEO_ZONES . " WHERE geo_zone_id = '" . (int)MODULE_PAYMENT_SQUARE_ZONE . "' AND zone_country_id = '" . (int)$order->billing['country']['id'] . "' ORDER BY zone_id";
        $checks     = $db->Execute($sql);
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

    function javascript_validation()
    {
        return '';
    }

    function selection()
    {
        // helper for auto-selecting the radio-button next to this module so the user doesn't have to make that choice
        $onFocus = ' onfocus="methodSelect(\'pmt-' . $this->code . '\')"';

        $selection = [
            'id'     => $this->code,
            'module' => MODULE_PAYMENT_SQUARE_TEXT_CATALOG_TITLE,
            'fields' => [
                [
                    'title' => MODULE_PAYMENT_SQUARE_TEXT_CREDIT_CARD_NUMBER,
                    'field' => '<div id="' . $this->code . '_cc-number"></div><div id="sq-card-brand"></div>',
                ],
                [
                    'title' => MODULE_PAYMENT_SQUARE_TEXT_CVV,
                    'field' => '<div id="' . $this->code . '_cc-cvv"></div>',
                ],
                [
                    'title' => MODULE_PAYMENT_SQUARE_TEXT_CREDIT_CARD_EXPIRES,
                    'field' => '<div id="' . $this->code . '_cc-expires"></div>',
                ],
                [
                    'title' => MODULE_PAYMENT_SQUARE_TEXT_CREDIT_CARD_POSTCODE,
                    'field' => '<div id="' . $this->code . '_cc-postcode"></div>',
                ],
                [
                    'field' => '<div id="card-errors" class="alert error"></div>',
                ],
                [
                    'title' => '',
                    'field' => '<input type="hidden" id="card-nonce" name="nonce">' .
                        '<input type="hidden" id="card-type" name="' . $this->code . '_cc_type">' .
                        '<input type="hidden" id="card-four" name="' . $this->code . '_cc_four">' .
                        '<input type="hidden" id="card-exp" name="' . $this->code . '_cc_exp">',
                ],
            ],
        ];

        return $selection;
    }

    function pre_confirmation_check()
    {
        global $messageStack;
        if (!isset($_POST['nonce']) || trim($_POST['nonce']) == '') {
            $messageStack->add_session('checkout_payment', MODULE_PAYMENT_SQUARE_ERROR_INVALID_CARD_DATA, 'error');
            zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false));
        }
    }

    function confirmation()
    {
        $confirmation = [
            'fields' => [
                [
                    'title' => MODULE_PAYMENT_SQUARE_TEXT_CREDIT_CARD_TYPE,
                    'field' => zen_output_string_protected($_POST[$this->code . '_cc_type']),
                ],
                [
                    'title' => MODULE_PAYMENT_SQUARE_TEXT_CREDIT_CARD_NUMBER,
                    'field' => zen_output_string_protected($_POST[$this->code . '_cc_four']),
                ],
                [
                    'title' => MODULE_PAYMENT_SQUARE_TEXT_CREDIT_CARD_EXPIRES,
                    'field' => zen_output_string_protected($_POST[$this->code . '_cc_exp']),
                ],
            ],
        ];

        return $confirmation;
    }

    function process_button()
    {
        $process_button_string = zen_draw_hidden_field($this->code . '_nonce', $_POST['nonce']);
        $process_button_string .= zen_draw_hidden_field('cc_type', zen_output_string_protected($_POST[$this->code . '_cc_type']));
        $process_button_string .= zen_draw_hidden_field('cc_four', zen_output_string_protected($_POST[$this->code . '_cc_four']));
        $process_button_string .= zen_draw_hidden_field('cc_expires', zen_output_string_protected($_POST[$this->code . '_cc_exp']));

        return $process_button_string;
    }

    function before_process()
    {
        global $messageStack, $order, $currencies;

        if (!isset($_POST[$this->code . '_nonce']) || trim($_POST[$this->code . '_nonce']) == '') {
            $messageStack->add_session('checkout_payment', MODULE_PAYMENT_SQUARE_ERROR_INVALID_CARD_DATA, 'error');
            zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false));
        }

        $order->info['cc_type']   = zen_output_string_protected($_POST['cc_type']);
        $order->info['cc_number'] = zen_output_string_protected($_POST['cc_four']);
        if (!strpos($order->info['cc_number'], 'XX')) {
            $order->info['cc_number'] = 'XXXX' . zen_output_string_protected(substr($_POST['cc_four'], -4));
        }
        $order->info['cc_expires'] = zen_output_string_protected($_POST['cc_expires']);
        $order->info['cc_cvv']     = '***';

        $payment_amount   = $order->info['total'];
        $currency_code    = strtoupper($order->info['currency']);
        $currency_comment = '';

        // force conversion to Square Account's currency:
        if ($order->info['currency'] != $this->gateway_currency) {
            global $currencies;
            $payment_amount   = number_format($order->info['total'] * $currencies->get_value($this->gateway_currency), 2);
            $currency_code    = $this->gateway_currency;
            $currency_comment = '(Converted from: ' . number_format($order->info['total'] * $order->info['currency_value'], 2) . ' ' . $order->info['currency'] . ')';
            // @TODO - if Square adds support for transmission of tax and shipping amounts, these may need recalculation here too
        }

        $billing_address = [
            'address_line'                    => $order->billing['street_address'],
            'address_line_2'                  => $order->billing['suburb'],
            'locality'                        => $order->billing['city'],
            'administrative_district_level_1' => zen_get_zone_code($order->billing['country']['id'], $order->billing['zone_id'], $order->billing['state']),
            'postal_code'                     => $order->billing['postcode'],
            'country'                         => $order->billing['country']['iso_code_2'],
            'last_name'                       => $order->billing['lastname'],
            'organization'                    => $order->billing['company'],
        ];
        if ($order->delivery !== false && isset($order->delivery['street_address'])) {
            $shipping_address = [
                'address_line'                    => $order->delivery['street_address'],
                'address_line_2'                  => $order->delivery['suburb'],
                'locality'                        => $order->delivery['city'],
                'administrative_district_level_1' => zen_get_zone_code($order->delivery['country']['id'], $order->delivery['zone_id'], $order->delivery['state']),
                'postal_code'                     => $order->delivery['postcode'],
                'country'                         => $order->delivery['country']['iso_code_2'],
                'last_name'                       => $order->delivery['lastname'],
                'organization'                    => $order->delivery['company'],
            ];
        }

        $request_body = [
            'idempotency_key'     => uniqid(),
            'card_nonce'          => $_POST[$this->code . '_nonce'],
            'amount_money'        => [
                'amount'   => $this->convert_to_cents($payment_amount),
                'currency' => $currency_code,
            ],
            'delay_capture'       => (bool)(MODULE_PAYMENT_SQUARE_TRANSACTION_TYPE === 'authorize'),
            'reference_id'        => (string)(substr(zen_session_id(), 0, 40)), // 40 char max
            'note'                => substr(htmlentities(trim($currency_comment . ' ' . STORE_NAME)), 0, 60), // 60 char max
            'customer_id'         => $_SESSION['customer_id'],
            'buyer_email_address' => $order->customer['email_address'],
            'billing_address'     => $billing_address,
        ];
        if (!empty($shipping_address)) {
            $request_body['shipping_address'] = $shipping_address;
        }

        $this->getAccessToken();
        $location_id  = $this->getLocationID();
        $api_instance = new \SquareConnect\Api\TransactionsApi();
        $body         = new \SquareConnect\Model\ChargeRequest($request_body);
        if (MODULE_PAYMENT_SQUARE_TESTING_MODE == 'Live') {
            $body->offsetSet('integration_id', 'sqi_' . 'b6ff0cd7acc14f7a' . 'b24200041d066ba6'); // required
        }
        try {
            $result        = $api_instance->charge($location_id, $body);
            $errors_object = $result->getErrors();
            $transaction   = $result->getTransaction();
        } catch (\SquareConnect\ApiException $e) {
            $errors_object = $e->getResponseBody()->errors;
            $this->logTransactionData(['id' => 'FATAL ERROR'], $request_body, print_r($e->getResponseBody(), true));
            trigger_error("Square Connect error. \nResponse Body:\n" . print_r($e->getResponseBody(), true) . "\nResponse Headers:\n" . print_r($e->getResponseHeaders(), true), E_USER_NOTICE);
            $messageStack->add_session('checkout_payment', MODULE_PAYMENT_SQUARE_TEXT_COMM_ERROR, 'error');
            zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false));
        }

        // log the response data
        $this->logTransactionData($transaction, $request_body, (string)$errors_object);

        // analyze the response
        if (count($errors_object)) {
            $msg = $this->parse_error_response($errors_object);
            $messageStack->add_session('checkout_payment', MODULE_PAYMENT_SQUARE_TEXT_ERROR . ' [' . $msg . ']', 'error');
            zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false));
        }

        if (!empty($transaction->getId())) {
            $tenders                = $transaction->getTenders();
            $this->auth_code        = $tenders[0]['id']; // since Square doesn't supply an auth code, we use the tender-id instead, since it is required for submitting refunds
            $this->transaction_id   = $transaction->getId();
            $this->transaction_date = $transaction->getCreatedAt();

            return true;
        }

        // if we get here, send a generic 'declined' message response
        $messageStack->add_session('checkout_payment', MODULE_PAYMENT_SQUARE_ERROR_DECLINED, 'error');
        zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false));
    }

    /**
     * Update the order-status history data with the transaction id and tender id from the transaction.
     *
     * @return boolean
     */
    function after_process()
    {
        global $insert_id, $db, $order, $currencies;
        $currency_comment = '';
        if ($order->info['currency'] != $this->gateway_currency) {
            $currency_comment = "\n(" . number_format($order->info['total'] * $currencies->get_value($this->gateway_currency), 2) . ' ' . $this->gateway_currency . ')';
        }
        $sql = "insert into " . TABLE_ORDERS_STATUS_HISTORY . " (comments, orders_id, orders_status_id, customer_notified, date_added) values (:orderComments, :orderID, :orderStatus, -1, now() )";
        $sql = $db->bindVars($sql, ':orderComments', 'Credit Card payment.  TransID: ' . $this->transaction_id . "\nTender ID: " . $this->auth_code . "\n" . $this->transaction_date . $currency_comment, 'string');
        $sql = $db->bindVars($sql, ':orderID', $insert_id, 'integer');
        $sql = $db->bindVars($sql, ':orderStatus', $this->order_status, 'integer');
        $db->Execute($sql);

        $sql_data_array = [
            'order_id'       => $insert_id,
            'location_id'    => $this->getLocationID(),
            'transaction_id' => $this->transaction_id,
            'tender_id'      => $this->auth_code,
            'created_at'     => 'now()',
        ];
        zen_db_perform(TABLE_SQUARE_PAYMENTS, $sql_data_array);

        return true;
    }

    /**
     * fetch original transaction details, live
     *
     * @param $order_id
     * @return \SquareConnect\Model\Transaction
     */
    function lookupTransactionForOrder($order_id)
    {
        global $db;
        $sql    = "SELECT order_id, location_id, transaction_id, tender_id from " . TABLE_SQUARE_PAYMENTS . " WHERE order_id = " . (int)$order_id . " order by id LIMIT 1";
        $result = $db->Execute($sql);
        if ($result->EOF) {
            $transaction = new \SquareConnect\Model\Transaction;
        } else {
            $this->getAccessToken();
            $location_id = $result->fields['location_id'];
            if (empty($location_id)) $location_id = $this->getLocationID();
            $api_instance = new \SquareConnect\Api\TransactionsApi();
            try {
                $result        = $api_instance->retrieveTransaction($location_id, $result->fields['transaction_id']);
                $errors_object = $result->getErrors();
                $transaction   = $result->getTransaction();
            } catch (\SquareConnect\ApiException $e) {
                $errors_object = $e->getResponseBody()->errors;
                $transaction   = new \SquareConnect\Model\Transaction;
            }
        }

        return $transaction;
    }

    /**
     * Prepare admin-page components
     *
     * @param int $order_id
     * @return string
     */
    function admin_notification($order_id)
    {
        global $currencies;
        $transaction = $this->lookupTransactionForOrder($order_id);
        $output      = '';
        require(DIR_FS_CATALOG . DIR_WS_MODULES . 'payment/square_support/square_admin_notification.php');

        return $output;
    }


// SIMPLIFIED OAUTH TOKENIZATION
    function getAccessToken()
    {
        $this->token_refresh_check();
        $access_token = (string)(MODULE_PAYMENT_SQUARE_TESTING_MODE == 'Live' ? MODULE_PAYMENT_SQUARE_ACCESS_TOKEN : MODULE_PAYMENT_SQUARE_SANDBOX_TOKEN);

        // set token into Square Config for subsequent API calls
        SquareConnect\Configuration::getDefaultConfiguration()->setAccessToken($access_token);//->setDebug(MODULE_PAYMENT_SQUARE_TESTING_MODE != 'Live');

        return $access_token;
    }

    function isTokenExpired($difference = '')
    {
        if (MODULE_PAYMENT_SQUARE_REFRESH_EXPIRES_AT == '') return true;
        $expiry = new DateTime(MODULE_PAYMENT_SQUARE_REFRESH_EXPIRES_AT);  // formatted as '2016-08-10T19:42:08Z'

        // to be useful, we have to allow time for a customer to checkout. Opting generously for 1 hour here.
        if ($difference == '') $difference = '+1 hour';
        $now = new DateTime($difference);

        if ($expiry < $now) {
            return true;
        }

        return false;
    }

    // This should also be callable from a cron job
    function token_refresh_check()
    {
        if (MODULE_PAYMENT_SQUARE_APPLICATION_ID == '') return 'not configured';

        $token = MODULE_PAYMENT_SQUARE_ACCESS_TOKEN;

        // if we have no token, alert that we need to get one
        if (trim($token) == '') {
            if (IS_ADMIN_FLAG === true) {
                global $messageStack;
                $messageStack->add_session(sprintf(MODULE_PAYMENT_SQUARE_TEXT_NEED_ACCESS_TOKEN, $this->getAuthorizeURL()), 'error');
            }
            $this->disableDueToInvalidAccessToken();

            return 'failure';
        }

        // refreshes can't be done if the token has expired longer than 15 days.
        if ($this->isTokenExpired('-15 days')) {
            $this->disableDueToInvalidAccessToken();

            return 'failure';
        }

        // ideal refresh threshold is 3 weeks out
        $refresh_threshold = new DateTime('+3 weeks');

        // if expiry is less than (threshold) away, refresh  (ie: refresh weekly)
        $expiry = new DateTime(MODULE_PAYMENT_SQUARE_REFRESH_EXPIRES_AT);
        if ($expiry < $refresh_threshold) {
            $result = $this->getRefreshToken();
            if ($result) {
                return 'refreshed';
            } else {
                return 'not refreshed';
            }
        }

        return 'not expired';
    }

    function disableDueToInvalidAccessToken()
    {
        if (MODULE_PAYMENT_SQUARE_REFRESH_EXPIRES_AT == '' || MODULE_PAYMENT_SQUARE_ACCESS_TOKEN == '') return;
        global $db;
        $db->Execute("UPDATE " . TABLE_CONFIGURATION . " SET configuration_value = 'False' WHERE configuration_key = 'MODULE_PAYMENT_SQUARE_STATUS'");
        $msg = "This is an alert from your Zen Cart store.\n\nYour Square Payment Module access-token has expired, or cannot be refreshed automatically. Please login to your store Admin, go to the Payment Module settings, click on the Square module, and click the button to Re/Authorize your account.\n\nSquare Payments are disabled until a new valid token can be established.";
        $msg .= "\n\n" . ' The token expired on ' . MODULE_PAYMENT_SQUARE_REFRESH_EXPIRES_AT;
        zen_mail(STORE_OWNER_EMAIL_ADDRESS, STORE_OWNER_EMAIL_ADDRESS, 'Square Payment Module Problem: Critical', $msg, STORE_NAME, EMAIL_FROM, ['EMAIL_MESSAGE_HTML' => $msg], 'payment_module_error');
        trigger_error('Square Payment Module token expired' . (MODULE_PAYMENT_SQUARE_REFRESH_EXPIRES_AT != '' ? ' on ' . MODULE_PAYMENT_SQUARE_REFRESH_EXPIRES_AT : '') . '. Payment module has been disabled. Please login to Admin and re-authorize the module.',
            E_USER_ERROR);
    }

    function getRefreshToken()
    {
        $url  = 'https://connect.squareup.com/oauth2/clients/' . MODULE_PAYMENT_SQUARE_APPLICATION_ID . '/access-token/renew';
        $body = '{"access_token": "' . MODULE_PAYMENT_SQUARE_ACCESS_TOKEN . '"}';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_TIMEOUT, 9);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 9);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Zen Cart token refresh [' . preg_replace('#https?://#', '', HTTP_SERVER) . '] ');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        $errno    = curl_errno($ch);
        curl_close($ch);

        if ($error == 0) {
            $this->setAccessToken($response);

            return true;
        } else {
            error_log('Could not refresh Square token. Response: ' . "\n" . print_r($response, true) . "\n" . $errno . ' ' . $error . ' HTTP: ' . $httpcode);
        }

        return false;
    }

    function setAccessToken($json_payload)
    {
        global $db;
        $payload = json_decode($json_payload, true);
        if (!isset($payload['access_token']) || $payload['access_token'] == '') return false;
        $token   = preg_replace('[^0-9A-Za-z\-]', '', $payload['access_token']);
        $expires = preg_replace('[^0-9A-Za-z\-:]', '', $payload['expires_at']);
        $db->Execute("UPDATE " . TABLE_CONFIGURATION . " SET configuration_value = '" . $token . "' WHERE configuration_key = 'MODULE_PAYMENT_SQUARE_ACCESS_TOKEN'");
        $db->Execute("UPDATE " . TABLE_CONFIGURATION . " SET configuration_value = '" . $expires . "' WHERE configuration_key = 'MODULE_PAYMENT_SQUARE_REFRESH_EXPIRES_AT'");
    }


    function getAuthorizeURL()
    {
        $url    = 'https://connect.squareup.com/oauth2/authorize?';
        $params = http_build_query(
            [
                'client_id' => MODULE_PAYMENT_SQUARE_APPLICATION_ID,
                'scope'     => 'MERCHANT_PROFILE_READ PAYMENTS_WRITE PAYMENTS_READ',
                'state'     => uniqid(),
            ]);

        return $url . $params;
        // code=sq0abc-D1efG2HIJK345lmno6PqR78S9Tuv0WxY&response_type=code
    }

    function exchangeForToken($token_redeem_code)
    {
        $url  = 'https://connect.squareup.com/oauth2/token';
        $body = json_encode(
            [
                'client_id'     => MODULE_PAYMENT_SQUARE_APPLICATION_ID,
                'client_secret' => MODULE_PAYMENT_SQUARE_APPLICATION_SECRET,
                'code'          => $token_redeem_code,
            ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_TIMEOUT, 9);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 9);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Zen Cart token request [' . preg_replace('#https?://#', '', HTTP_SERVER) . '] ');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        $errno    = curl_errno($ch);
        curl_close($ch);
//error_log('SQUARE TOKEN EXCHANGE response: ' . "\n" . print_r($response, true) . "\n" . $errno . ' ' . $error . ' HTTP: ' . $httpcode);

        if ($error == 0) {
            $this->setAccessToken($response);
            echo 'Token set. You may now continue configuring the module. <script type="text/javascript">window.close()</script>';

            return true;
        } else {
            trigger_error('Could not exchange Square code for a token. HTTP ' . $httpcode . '. Error ' . $errno . ': ' . $error, E_USER_ERROR);
        }
    }

    function getLocationID()
    {
        $location_id = trim((string)MODULE_PAYMENT_SQUARE_LOCATION_ID);
        if ($position = strpos($location_id, ':[')) $location_id = substr(trim($location_id, ']'), $position + 2);
        if (empty($location_id)) {
            $locations = $this->getLocationsList();
            if ($locations == null) return '';
            $first_location = $locations[0];
            $location_id    = $first_location->getId();
        }

        return $location_id;
    }

    function getLocationsList()
    {
        if (MODULE_PAYMENT_SQUARE_ACCESS_TOKEN == '') return null;
        $this->getAccessToken();
        $api_instance = new SquareConnect\Api\LocationsApi();
        try {
            $result = $api_instance->listLocations();

            return $result->getLocations();
        } catch (Exception $e) {
            trigger_error('Exception when calling LocationsApi->listLocations: ' . $e->getMessage(), E_USER_NOTICE);

            return [];
        }
    }

    function getLocationsPulldownArray()
    {
        $locations = $this->getLocationsList();
        if (empty($locations)) return [];
        $locations_pulldown = [];
        foreach ($locations as $key => $value) {
            $locations_pulldown[] = ['id' => $value->getName() . ' :[' . $value->getId() . ']', 'text' => $value->getName()];
        }

        return $locations_pulldown;
    }

// format purchase amount
// Monetary amounts are specified in the smallest unit of the applicable currency. ie: for USD this amount is in cents.
    function convert_to_cents($amount, $currency = null)
    {
        global $currencies, $order;
        if (empty($currency)) $currency = (isset($order) && isset($order->info['currency'])) ? $order->info['currency'] : $this->gateway_currency;
        $decimal_places = $currencies->get_decimal_places($currency);

        // if this currency is "already" in cents, just use the amount directly
        if ((int)$decimal_places === 0) return (int)$amount;

        if (version_compare(PHP_VERSION, '5.6.0', '<')) {
            // old way
            return (int)($amount * pow(10, $decimal_places));
        }

        // modern way
        return (int)($amount * 10 ** $decimal_places);
    }


    function check()
    {
        global $db;
        if (!isset($this->_check)) {
            $check_query  = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_SQUARE_STATUS'");
            $this->_check = $check_query->RecordCount();
        }
        if ($this->_check > 0) $this->install(); // install any missing keys

        return $this->_check;
    }

    /** Install required configuration keys */
    function install()
    {
        global $db;

        if (!defined('MODULE_PAYMENT_SQUARE_STATUS')) $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Square Module', 'MODULE_PAYMENT_SQUARE_STATUS', 'True', 'Do you want to accept Square payments?', '6', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
        if (!defined('MODULE_PAYMENT_SQUARE_SORT_ORDER')) $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_SQUARE_SORT_ORDER', '0', 'Sort order of displaying payment options to the customer. Lowest is displayed first.', '6', '0', now())");
        if (!defined('MODULE_PAYMENT_SQUARE_ZONE')) $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Payment Zone', 'MODULE_PAYMENT_SQUARE_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '2', 'zen_get_zone_class_title', 'zen_cfg_pull_down_zone_classes(', now())");
        if (!defined('MODULE_PAYMENT_SQUARE_ORDER_STATUS_ID')) $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Order Status', 'MODULE_PAYMENT_SQUARE_ORDER_STATUS_ID', '2', 'Set the status of Paid orders made with this payment module to this value', '6', '0', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
        if (!defined('MODULE_PAYMENT_SQUARE_REFUNDED_ORDER_STATUS_ID')) $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Refunded Order Status', 'MODULE_PAYMENT_SQUARE_REFUNDED_ORDER_STATUS_ID', '1', 'Set the status of refunded orders to this value', '6', '0', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
        if (!defined('MODULE_PAYMENT_SQUARE_TRANSACTION_TYPE')) $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Transaction Type', 'MODULE_PAYMENT_SQUARE_TRANSACTION_TYPE', 'purchase', 'Should payments be [authorized] only, or be completed [purchases]?', '6', '0', 'zen_cfg_select_option(array(\'authorize\', \'purchase\'), ', now())");
        if (!defined('MODULE_PAYMENT_SQUARE_APPLICATION_ID')) $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function) values ('Application ID', 'MODULE_PAYMENT_SQUARE_APPLICATION_ID', 'sq0idp-', 'Enter the Application ID from your App settings', '6', '0',  now(), 'zen_cfg_password_display')");
        if (!defined('MODULE_PAYMENT_SQUARE_APPLICATION_SECRET')) $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function) values ('Application Secret (OAuth)', 'MODULE_PAYMENT_SQUARE_APPLICATION_SECRET', 'sq0csp-', 'Enter the Application Secret from your App OAuth settings', '6', '0',  now(), 'zen_cfg_password_display')");
        if (!defined('MODULE_PAYMENT_SQUARE_LOCATION_ID')) $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, set_function) values ('Location ID', 'MODULE_PAYMENT_SQUARE_LOCATION_ID', '', 'Enter the (Store) Location ID from your account settings. You can have multiple locations configured in your account; this setting lets you specify which location your sales should be attributed to.', '6', '0',  now(), 'zen_cfg_pull_down_square_locations(')");
        if (!defined('MODULE_PAYMENT_SQUARE_CURRENCY')) $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Currency Required', 'MODULE_PAYMENT_SQUARE_CURRENCY', 'USD', 'Which currency is your Square Account configured to accept?<br>(Purchases in any other currency will be pre-converted to this currency before submission using the exchange rates in your store admin.)', '6', '0', 'zen_cfg_select_option(array(\'USD\', \'CAD\', \'GBP\', \'AUD\'), ', now())");

        if (!defined('MODULE_PAYMENT_SQUARE_LOGGING')) $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Log Mode', 'MODULE_PAYMENT_SQUARE_LOGGING', 'Log on Failures and Email on Failures', 'Would you like to enable debug mode?  A complete detailed log of failed transactions may be emailed to the store owner.', '6', '0', 'zen_cfg_select_option(array(\'Off\', \'Log Always\', \'Log on Failures\', \'Log Always and Email on Failures\', \'Log on Failures and Email on Failures\', \'Email Always\', \'Email on Failures\'), ', now())");
        if (!defined('MODULE_PAYMENT_SQUARE_ACCESS_TOKEN')) $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function) values ('Live Merchant Token', 'MODULE_PAYMENT_SQUARE_ACCESS_TOKEN', '', 'Enter the Access Token for Live transactions from your account settings', '6', '0',  now(), 'zen_cfg_password_display')");
        if (!defined('MODULE_PAYMENT_SQUARE_REFRESH_EXPIRES_AT')) $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function) values ('Square Refresh Token (read only)', 'MODULE_PAYMENT_SQUARE_REFRESH_EXPIRES_AT', '', 'DO NOT EDIT', '6', '0',  now(), '')");
        // DEVELOPER USE ONLY
        if (!defined('MODULE_PAYMENT_SQUARE_SANDBOX_TOKEN')) $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function) values ('Sandbox Merchant Token', 'MODULE_PAYMENT_SQUARE_SANDBOX_TOKEN', 'sq0atb-nn_yQbQgZaA3VhFEykuYlQ', 'Enter the Sandbox Access Token from your account settings', '6', '0',  now(), 'zen_cfg_password_display')");
        if (!defined('MODULE_PAYMENT_SQUARE_TESTING_MODE')) $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Sandbox/Live Mode', 'MODULE_PAYMENT_SQUARE_TESTING_MODE', 'Live', 'Use [Live] for real transactions<br>Use [Sandbox] for developer testing', '6', '0', 'zen_cfg_select_option(array(\'Live\', \'Sandbox\'), ', now())");

        $this->tableCheckup();
    }

    function remove()
    {
        global $db;
        $db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key like 'MODULE\_PAYMENT\_SQUARE\_%'");
    }

    function keys()
    {
        $keys = [
            'MODULE_PAYMENT_SQUARE_STATUS',
            'MODULE_PAYMENT_SQUARE_SORT_ORDER',
            'MODULE_PAYMENT_SQUARE_ZONE',
            'MODULE_PAYMENT_SQUARE_TRANSACTION_TYPE',
            'MODULE_PAYMENT_SQUARE_ORDER_STATUS_ID',
            'MODULE_PAYMENT_SQUARE_REFUNDED_ORDER_STATUS_ID',
            'MODULE_PAYMENT_SQUARE_APPLICATION_ID',
            'MODULE_PAYMENT_SQUARE_APPLICATION_SECRET',
            'MODULE_PAYMENT_SQUARE_LOCATION_ID',
            'MODULE_PAYMENT_SQUARE_CURRENCY',
            'MODULE_PAYMENT_SQUARE_LOGGING',
        ];

        if (isset($_GET['sandbox'])) {
            // Developer use only
            $keys = array_merge($keys, [
                'MODULE_PAYMENT_SQUARE_ACCESS_TOKEN',
                'MODULE_PAYMENT_SQUARE_REFRESH_EXPIRES_AT',
                'MODULE_PAYMENT_SQUARE_TESTING_MODE',
                'MODULE_PAYMENT_SQUARE_SANDBOX_TOKEN',
            ]);
        }

        return $keys;
    }

    /**
     * Check and fix table structure if appropriate
     */
    function tableCheckup()
    {
        global $db, $sniffer;
        if (!$sniffer->table_exists(TABLE_SQUARE_PAYMENTS)) {
            $sql = "
            CREATE TABLE `" . TABLE_SQUARE_PAYMENTS . "` (
              `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
              `order_id` int(11) UNSIGNED NOT NULL,
              `location_id` varchar(40) NOT NULL,
              `transaction_id` varchar(40) NOT NULL,
              `tender_id` varchar(40),
              `action` varchar(40),
              `created_at` timestamp NOT NULL ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`)
            )";
            $db->Execute($sql);
        }
    }

    /**
     * Log transaction errors if enabled
     *
     * @param array $response
     * @param array $payload
     * @param string $errors
     */
    private function logTransactionData($response, $payload, $errors = '')
    {
        global $db;

        $logMessage = date('M-d-Y h:i:s') .
            "\n=================================\n\n" .
            ($errors != '' ? 'Error Dump: ' . $errors . "\n\n" : '') .
            'Transaction ID assigned: ' . $response['id'] . "\n" .
            'Sent to Square: ' . print_r($payload, true) . "\n\n" .
            'Results Received back from Square: ' . print_r($response, true) . "\n\n";

        if (strstr(MODULE_PAYMENT_SQUARE_LOGGING, 'Log Always') || ($errors != '' && strstr(MODULE_PAYMENT_SQUARE_LOGGING, 'Log on Failures'))) {
            $key  = $response['id'] . '_' . time() . '_' . zen_create_random_value(4);
            $file = $this->_logDir . '/' . 'Square_' . $key . '.log';
            if ($fp = @fopen($file, 'a')) {
                fwrite($fp, $logMessage);
                fclose($fp);
            }
        }
        if (($errors != '' && stristr(MODULE_PAYMENT_SQUARE_LOGGING, 'Email on Failures')) || strstr(MODULE_PAYMENT_SQUARE_LOGGING, 'Email Always')) {
            zen_mail(STORE_NAME, STORE_OWNER_EMAIL_ADDRESS, 'Square Alert (customer transaction error) ' . date('M-d-Y h:i:s'), $logMessage, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS,
                ['EMAIL_MESSAGE_HTML' => nl2br($logMessage)], 'debug');
        }
    }

    /**
     * Refund for a given transaction+tender
     */
    function _doRefund($oID, $amount = null, $currency_code = null)
    {
        global $db, $messageStack, $currencies;
        $new_order_status = $this->getNewOrderStatus($oID, 'capture', 'refund', (int)MODULE_PAYMENT_SQUARE_REFUNDED_ORDER_STATUS_ID);
        if ($new_order_status == 0) $new_order_status = 1;
        $proceedToRefund = true;
        if (isset($_POST['refconfirm']) && $_POST['refconfirm'] != 'on') {
            $messageStack->add_session(MODULE_PAYMENT_SQUARE_TEXT_REFUND_CONFIRM_ERROR, 'error');
            $proceedToRefund = false;
        }
        if (isset($_POST['buttonrefund']) && $_POST['buttonrefund'] == MODULE_PAYMENT_SQUARE_ENTRY_REFUND_BUTTON_TEXT) {
            $amount = preg_replace('/[^0-9.,]/', '', $_POST['refamt']);
            if (empty($amount)) {
                $messageStack->add_session(MODULE_PAYMENT_SQUARE_TEXT_INVALID_REFUND_AMOUNT, 'error');
                $proceedToRefund = false;
            }
        }
        if (!isset($_POST['trans_id']) || trim($_POST['trans_id']) == '') {
            $messageStack->add_session(MODULE_PAYMENT_SQUARE_TEXT_TRANS_ID_REQUIRED_ERROR, 'error');
            $proceedToRefund = false;
        }
        if (!$proceedToRefund) return false;

        $refundNote     = strip_tags(zen_db_input($_POST['refnote']));
        $transaction_id = htmlentities($_POST['trans_id']);
        $tender         = htmlentities($_POST['tender_id']);

        // handle currency exchange
        // @TODO - consider adding $order and doing currency lookup from $order->info
        if (empty($currency_code)) $currency_code = $this->gateway_currency;
        // @todo - change these to 'true' after fixing rounding issue
        $amount_formatted = number_format($amount, 2);
//        $amount_formatted = $currencies->format($amount, false, $currency_code);
//        $amount           = $currencies->rateAdjusted($amount, false, $currency_code);

        $refund_details = [
            'amount_money'    => [
                'amount'   => $this->convert_to_cents($amount, $currency_code),
                'currency' => $currency_code,
            ],
            'tender_id'       => $tender,
            'reason'          => substr(htmlentities(trim($refundNote)), 0, 60),
            'idempotency_key' => uniqid(),
        ];
        $request_body   = new \SquareConnect\Model\CreateRefundRequest($refund_details);
        $this->logTransactionData([['comment' => 'Creating refund request']], $refund_details);

        $this->getAccessToken();
        $location_id  = $this->getLocationID();
        $api_instance = new SquareConnect\Api\TransactionsApi();
        try {
            $result        = $api_instance->createRefund($location_id, $transaction_id, $request_body);
            $errors_object = $result->getErrors();
            $transaction   = $result->getRefund();
        } catch (\SquareConnect\ApiException $e) {
            $errors_object = $e->getResponseBody()->errors;
            $this->logTransactionData(['id' => 'FATAL ERROR'], $refund_details, print_r($e->getResponseBody(), true));
            trigger_error("Square Connect error (REFUNDING). \nResponse Body:\n" . print_r($e->getResponseBody(), true) . "\nResponse Headers:\n" . print_r($e->getResponseHeaders(), true), E_USER_NOTICE);
            $messageStack->add_session(MODULE_PAYMENT_SQUARE_TEXT_COMM_ERROR, 'error');
        }

        $this->logTransactionData($transaction, $refund_details, (string)$errors_object);

        if (count($errors_object)) {
            $msg = $this->parse_error_response($errors_object);
            $messageStack->add_session(MODULE_PAYMENT_SQUARE_TEXT_UPDATE_FAILED . ' [' . $msg . ']', 'error');

            return false;
        }

        // Success, so save the results
        $sql_data_array = [
            'orders_id'         => $oID,
            'orders_status_id'  => (int)$new_order_status,
            'date_added'        => 'now()',
            'comments'          => 'REFUND INITIATED.  Refund Amt: ' . $amount_formatted . "\n" . $refundNote,
            'customer_notified' => 0,
        ];
        zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
        $db->Execute("update " . TABLE_ORDERS . "
                  set orders_status = " . (int)$new_order_status . "
                  where orders_id = " . (int)$oID);
        $messageStack->add_session(sprintf(MODULE_PAYMENT_SQUARE_TEXT_REFUND_INITIATED . $transaction->getAmountMoney()), 'success');

        return true;
    }

    /**
     * Capture a previously-authorized transaction.
     */
    function _doCapt($oID, $type = 'Complete', $amount = null, $currency = null)
    {
        global $db, $messageStack;

        $new_order_status = $this->getNewOrderStatus($oID, 'void', 'capture', (int)MODULE_PAYMENT_SQUARE_ORDER_STATUS_ID);
        if ($new_order_status == 0) $new_order_status = 1;

        $captureNote      = strip_tags(zen_db_input($_POST['captnote']));
        $proceedToCapture = true;
        if (!isset($_POST['captconfirm']) || $_POST['captconfirm'] != 'on') {
            $messageStack->add_session(MODULE_PAYMENT_SQUARE_TEXT_CAPTURE_CONFIRM_ERROR, 'error');
            $proceedToCapture = false;
        }
        if (!isset($_POST['captauthid']) || trim($_POST['captauthid']) == '') {
            $messageStack->add_session(MODULE_PAYMENT_SQUARE_TEXT_TRANS_ID_REQUIRED_ERROR, 'error');
            $proceedToCapture = false;
        }
        if (!$proceedToCapture) return false;

        $transaction_id = $_POST['captauthid'];

        $this->getAccessToken();
        $location_id  = $this->getLocationID();
        $api_instance = new SquareConnect\Api\TransactionsApi();
        try {
            $result        = $api_instance->captureTransaction($location_id, $transaction_id);
            $errors_object = $result->getErrors();
        } catch (\SquareConnect\ApiException $e) {
            $errors_object = $e->getResponseBody()->errors;
            $this->logTransactionData(['id' => 'FATAL ERROR'], [], print_r($e->getResponseBody(), true));
            trigger_error("Square Connect error (CAPTURE attempt). \nResponse Body:\n" . print_r($e->getResponseBody(), true) . "\nResponse Headers:\n" . print_r($e->getResponseHeaders(), true), E_USER_NOTICE);
            $messageStack->add_session(MODULE_PAYMENT_SQUARE_TEXT_COMM_ERROR, 'error');
        }

        $this->logTransactionData(['capture request' => 'transaction ' . $transaction_id], [], (string)$errors_object);

        if (count($errors_object)) {
            $msg = $this->parse_error_response($errors_object);
            $messageStack->add_session(MODULE_PAYMENT_SQUARE_TEXT_UPDATE_FAILED . ' [' . $msg . ']', 'error');

            return false;
        }

        // Success, so save the results
        $sql_data_array = [
            'orders_id'         => (int)$oID,
            'orders_status_id'  => (int)$new_order_status,
            'date_added'        => 'now()',
            'comments'          => 'FUNDS COLLECTED. Trans ID: ' . $transaction_id . "\n" . 'Time: ' . date('Y-m-D h:i:s') . "\n" . $captureNote,
            'customer_notified' => 0,
        ];
        zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
        $db->Execute("update " . TABLE_ORDERS . "
                  set orders_status = " . (int)$new_order_status . "
                  where orders_id = " . (int)$oID);
        $messageStack->add_session(sprintf(MODULE_PAYMENT_SQUARE_TEXT_CAPT_INITIATED, $transaction_id), 'success');

        return true;
    }

    /**
     * Void an not-yet-captured authorized transaction.
     */
    function _doVoid($oID, $note = '')
    {
        global $db, $messageStack;

        $new_order_status = $this->getNewOrderStatus($oID, 'void', (int)MODULE_PAYMENT_SQUARE_REFUNDED_ORDER_STATUS_ID);
        if ($new_order_status == 0) $new_order_status = 1;
        $voidNote      = strip_tags(zen_db_input($_POST['voidnote'] . $note));
        $voidAuthID    = trim(strip_tags(zen_db_input($_POST['voidauthid'])));
        $proceedToVoid = true;
        if (isset($_POST['ordervoid']) && $_POST['ordervoid'] == MODULE_PAYMENT_SQUARE_ENTRY_VOID_BUTTON_TEXT) {
            if (isset($_POST['voidconfirm']) && $_POST['voidconfirm'] != 'on') {
                $messageStack->add_session(MODULE_PAYMENT_SQUARE_TEXT_VOID_CONFIRM_ERROR, 'error');
                $proceedToVoid = false;
            }
        }
        if ($voidAuthID == '') {
            $messageStack->add_session(MODULE_PAYMENT_SQUARE_TEXT_TRANS_ID_REQUIRED_ERROR, 'error');
            $proceedToVoid = false;
        }
        if (!$proceedToVoid) return false;

        $transaction_id = $voidAuthID;

        $this->getAccessToken();
        $location_id  = $this->getLocationID();
        $api_instance = new \SquareConnect\Api\TransactionsApi();
        try {
            $result        = $api_instance->voidTransaction($location_id, $transaction_id);
            $errors_object = $result->getErrors();
        } catch (\SquareConnect\ApiException $e) {
            $errors_object = $e->getResponseBody()->errors;
            $this->logTransactionData(['id' => 'FATAL ERROR'], [], print_r($e->getResponseBody(), true));
            trigger_error("Square Connect error (VOID attempt). \nResponse Body:\n" . print_r($e->getResponseBody(), true) . "\nResponse Headers:\n" . print_r($e->getResponseHeaders(), true), E_USER_NOTICE);
            $messageStack->add_session(MODULE_PAYMENT_SQUARE_TEXT_COMM_ERROR, 'error');
        }

        $this->logTransactionData(['void request' => 'transaction ' . $transaction_id], [], (string)$errors_object);

        if (count($errors_object)) {
            $msg = $this->parse_error_response($errors_object);
            $messageStack->add_session(MODULE_PAYMENT_SQUARE_TEXT_UPDATE_FAILED . ' [' . $msg . ']', 'error');

            return false;
        }
        // Success, so save the results
        $sql_data_array = [
            'orders_id'         => (int)$oID,
            'orders_status_id'  => (int)$new_order_status,
            'date_added'        => 'now()',
            'comments'          => 'VOIDED. Trans ID: ' . $transaction_id . "\n" . $voidNote,
            'customer_notified' => 0,
        ];
        zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
        $db->Execute("update " . TABLE_ORDERS . "
                  set orders_status = '" . (int)$new_order_status . "'
                  where orders_id = '" . (int)$oID . "'");
        $messageStack->add_session(sprintf(MODULE_PAYMENT_SQUARE_TEXT_VOID_INITIATED, $transaction_id), 'success');

        return true;
    }

    function getNewOrderStatus($order_id, $action, $default)
    {
        //global $order;
        //@TODO: fetch current order status and determine best status to set this to, based on $action

        return $default;
    }

    function parse_error_response($error_object)
    {
        $msg = '';
        foreach ($error_object as $err) {
            $code   = method_exists($err, 'getCode') ? $err->getCode() : $err->code;
            $detail = method_exists($err, 'getDetail') ? $err->getDetail() : $err->detail;
            $msg    .= "$code: $detail\n";
        }
        $msg = trim($msg, "\n");
        $msg = str_replace("\n", "\n<br>", $msg);

        $this->transaction_messages = $msg;

        return $msg;
    }

}

// helper for Square admin configuration: locations selector
function zen_cfg_pull_down_square_locations($location, $key = '')
{
    $name     = (($key) ? 'configuration[' . $key . ']' : 'configuration_value');
    $class    = new square;
    $pulldown = $class->getLocationsPulldownArray();

    return zen_draw_pull_down_menu($name, $pulldown, $location);
}

/////////////////////////////


// for backward compatibility with older ZC versions before v152 which didn't have this function:
if (!function_exists('plugin_version_check_for_updates')) {
    function plugin_version_check_for_updates($plugin_file_id = 0, $version_string_to_compare = '')
    {
        if ($plugin_file_id == 0) return false;
        $new_version_available = false;

        $lookup_index = 0;
        $url1         = 'https://plugins.zen-cart.com/versioncheck/' . (int)$plugin_file_id;
        $url2         = 'https://www.zen-cart.com/versioncheck/' . (int)$plugin_file_id;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url1);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 9);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 9);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Plugin Version Check [' . (int)$plugin_file_id . '] ' . HTTP_SERVER);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $error    = curl_error($ch);
        $errno    = curl_errno($ch);

        if ($error > 0) {
            trigger_error('CURL error checking plugin versions: ' . $errno . ':' . $error . "\nTrying http instead.");
            curl_setopt($ch, CURLOPT_URL, str_replace('tps:', 'tp:', $url1));
            $response = curl_exec($ch);
            $error    = curl_error($ch);
            $errno    = curl_errno($ch);
        }
        if ($error > 0) {
            trigger_error('CURL error checking plugin versions: ' . $errno . ':' . $error . "\nTrying www instead.");
            curl_setopt($ch, CURLOPT_URL, str_replace('tps:', 'tp:', $url2));
            $response = curl_exec($ch);
            $error    = curl_error($ch);
            $errno    = curl_errno($ch);
        }
        curl_close($ch);
        if ($error > 0 || $response == '') {
            trigger_error('CURL error checking plugin versions: ' . $errno . ':' . $error . "\nTrying file_get_contents() instead.");
            $ctx      = stream_context_create(['http' => ['timeout' => 5]]);
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
        if (strcmp($data[$lookup_index]['latest_plugin_version'], $version_string_to_compare) > 0) $new_version_available = true;
        // check whether present ZC version is compatible with the latest available plugin version
        if (!in_array('v' . PROJECT_VERSION_MAJOR . '.' . PROJECT_VERSION_MINOR, $data[$lookup_index]['zcversions'])) $new_version_available = false;

        return ($new_version_available) ? $data[$lookup_index] : false;
    }
}
