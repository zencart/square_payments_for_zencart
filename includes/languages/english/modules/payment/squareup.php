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
