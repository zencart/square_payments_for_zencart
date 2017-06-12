<?php
/**
 * Square payment module language defines
 *
 * @package squareup
 * @copyright Copyright 2003-2017 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: Author: Chris Brown <drbyte@zen-cart.com> New in v1.5.6 $
 */

define('MODULE_PAYMENT_SQUAREUP_TEXT_DESCRIPTION', 'SquareUp Gateway module.<br><br>Allows you to accept PCI Compliant credit card payments without making the customer leave your store!<br>
       <a href="https://www.zen-cart.com/partners/squareup" target="_blank">Get more information, or Sign up for an account</a><br><br>
       <a href="https://squareup.com/login" target="_blank">Log In To Your SquareUp Account</a>');

define('MODULE_PAYMENT_SQUAREUP_TEXT_ADMIN_TITLE', 'SquareUp'); // Payment option title as displayed in the admin
define('MODULE_PAYMENT_SQUAREUP_TEXT_CATALOG_TITLE', 'Credit Card');  // Payment option title as displayed to the customer

define('MODULE_PAYMENT_SQUAREUP_TEXT_CREDIT_CARD_POSTCODE', 'Postal Code:');
define('MODULE_PAYMENT_SQUAREUP_TEXT_CREDIT_CARD_NUMBER', 'Card Number:');
define('MODULE_PAYMENT_SQUAREUP_TEXT_CREDIT_CARD_EXPIRES', 'Expiry Date:');
define('MODULE_PAYMENT_SQUAREUP_TEXT_CVV', 'CVV Number:');
define('MODULE_PAYMENT_SQUAREUP_TEXT_CREDIT_CARD_TYPE', 'Credit Card Type:');

define('MODULE_PAYMENT_SQUAREUP_TEXT_ERROR', "(SQ-ERR) Your transaction could not be completed because of an error: ");
define('MODULE_PAYMENT_SQUAREUP_TEXT_MISCONFIGURATION', "Your transaction could not be completed due to a misconfiguration in our store. Please report this error to the Store Owner: SQ-MISCONF");
define('MODULE_PAYMENT_SQUAREUP_TEXT_COMM_ERROR', 'Unable to process payment due to a communications error. You may try again or contact us for assistance.');
define('MODULE_PAYMENT_SQUAREUP_ERROR_INVALID_CARD_DATA', "We could not initiate your transaction because of a problem with the card data you entered. Please correct the card data, or report this error to the Store Owner: SQ-NONCE-FAILURE");
define('MODULE_PAYMENT_SQUAREUP_ERROR_DECLINED', 'Sorry, your payment could not be authorized. Please select an alternate method of payment.');

// Sandbox available at https://docs.connect.squareup.com/



// admin tools:
  define('MODULE_PAYMENT_SQUAREUP_ENTRY_REFUND_BUTTON_TEXT', 'Do Refund');
  define('MODULE_PAYMENT_SQUAREUP_TEXT_REFUND_CONFIRM_ERROR', 'Error: You requested to do a refund but did not check the Confirmation box.');
  define('MODULE_PAYMENT_SQUAREUP_TEXT_INVALID_REFUND_AMOUNT', 'Error: You requested a refund but entered an invalid amount.');
  define('MODULE_PAYMENT_SQUAREUP_TEXT_REFUND_INITIATED', 'Refund Initiated. ');
  define('MODULE_PAYMENT_SQUAREUP_TEXT_CAPTURE_CONFIRM_ERROR', 'Error: You requested to do a capture but did not check the Confirmation box.');
  define('MODULE_PAYMENT_SQUAREUP_ENTRY_CAPTURE_BUTTON_TEXT', 'Do Capture');
  define('MODULE_PAYMENT_SQUAREUP_TEXT_TRANS_ID_REQUIRED_ERROR', 'Error: You need to specify a Transaction ID.');
  define('MODULE_PAYMENT_SQUAREUP_TEXT_CAPT_INITIATED', 'Funds Capture initiated. Transaction ID: %s');
  define('MODULE_PAYMENT_SQUAREUP_ENTRY_VOID_BUTTON_TEXT', 'Do Void');
  define('MODULE_PAYMENT_SQUAREUP_TEXT_VOID_CONFIRM_ERROR', 'Error: You requested a Void but did not check the Confirmation box.');
  define('MODULE_PAYMENT_SQUAREUP_TEXT_VOID_INITIATED', 'Void Initiated. Transaction ID: %s');
  define('MODULE_PAYMENT_SQUAREUP_TEXT_UPDATE_FAILED', 'Sorry, the attempted transaction update failed unexpectedly. See logs for details.');


  define('MODULE_PAYMENT_SQUAREUP_ENTRY_REFUND_TITLE', '<strong>Refund Transactions</strong>');
  define('MODULE_PAYMENT_SQUAREUP_ENTRY_REFUND', 'You may refund money to the customer here:');
  define('MODULE_PAYMENT_SQUAREUP_TEXT_REFUND_CONFIRM_CHECK', 'Check this box to confirm your intent: ');
  define('MODULE_PAYMENT_SQUAREUP_ENTRY_REFUND_AMOUNT_TEXT', 'Enter the amount you wish to refund');
  define('MODULE_PAYMENT_SQUAREUP_ENTRY_REFUND_TENDER_ID', 'Enter the Tender ID:');
  define('MODULE_PAYMENT_SQUAREUP_ENTRY_REFUND_TRANS_ID', 'Enter the original Transaction ID:');
  define('MODULE_PAYMENT_SQUAREUP_ENTRY_REFUND_TEXT_COMMENTS', 'Notes (will show on Order History):');
  define('MODULE_PAYMENT_SQUAREUP_ENTRY_REFUND_DEFAULT_MESSAGE', 'Refund Issued');
  define('MODULE_PAYMENT_SQUAREUP_ENTRY_REFUND_SUFFIX', 'You may refund an order up to the original amount tendered. You must supply the original transaction ID and tender ID');

  define('MODULE_PAYMENT_SQUAREUP_ENTRY_CAPTURE_TITLE', '<strong>Capture Transactions</strong>');
  define('MODULE_PAYMENT_SQUAREUP_ENTRY_CAPTURE', 'You may capture previously-authorized funds here:');
  define('MODULE_PAYMENT_SQUAREUP_TEXT_CAPTURE_CONFIRM_CHECK', 'Check this box to confirm your intent: ');
  define('MODULE_PAYMENT_SQUAREUP_ENTRY_CAPTURE_TRANS_ID', 'Enter the original Transaction ID: ');
  define('MODULE_PAYMENT_SQUAREUP_ENTRY_CAPTURE_TEXT_COMMENTS', 'Notes (will show on Order History):');
  define('MODULE_PAYMENT_SQUAREUP_ENTRY_CAPTURE_DEFAULT_MESSAGE', '');
  define('MODULE_PAYMENT_SQUAREUP_ENTRY_CAPTURE_SUFFIX', 'Captures must be performed within 6 days of the original authorization. You may only capture an order ONCE.');

  define('MODULE_PAYMENT_SQUAREUP_ENTRY_VOID_TITLE', '<strong>Voiding Transactions</strong>');
  define('MODULE_PAYMENT_SQUAREUP_ENTRY_VOID', 'You may void an authorization which has not been captured.<br />Enter the uncaptured Transaction ID: ');
  define('MODULE_PAYMENT_SQUAREUP_TEXT_VOID_CONFIRM_CHECK', 'Check this box to confirm your intent:');
  define('MODULE_PAYMENT_SQUAREUP_ENTRY_VOID_TEXT_COMMENTS', 'Notes (will show on Order History):');
  define('MODULE_PAYMENT_SQUAREUP_ENTRY_VOID_DEFAULT_MESSAGE', 'Transaction Cancelled');
  define('MODULE_PAYMENT_SQUAREUP_ENTRY_VOID_SUFFIX', '');