# Square Payment Module for Zen Cart

## About

The Square payment plugin module allows you to connect your Square account to your online store.
You can also do refunds for online purchases directly from within the order screen in your store Admin.

The payment gateway is operated by Square, Inc, which also offers POS card-swipe readers to handle card-present transactions you can conduct from your phone or tablet in-store or mobile.

- Setup is quick: start accepting credit cards in less than 5 minutes, with your existing Square account.
- No monthly fees and no setup fees.
- PCI Compliant. Customer never leaves your store!
- Standard rates are 2.9% + $0.30 per transaction.
- Funds are deposited in your bank account in 1-2 business days.


## Compatibility

This module is compatible with Zen Cart® v1.5.4 and v1.5.5

This module works with PHP versions 7.2, 7.1, 7.0, 5.6, 5.5 and 5.4


## Requirements

1. You must be using SSL on your website
2. You will need a Square account, already validated and connected with your bank. You may create a Square merchant account at [squareup.com](https://squareup.com)
3. You need to create an App inside your Square account. See below for instructions.


## Creating an App inside your Square account
1. Login at https://connect.squareup.com/apps to view the apps you've connected to your account.
2. Click to create a New Application for your Zen Cart store to access. Give it a name, such as "Webstore", and click Create at the bottom of the page.
4. You will now be given access to some credentials. (If you already have a custom app and are trying to re-use it, you may need to revoke its token and regenerate one.)
5. Click on the OAuth tab. In the "**Redirect URL**" field, enter `https://your_store_url.com/square_handler.php` and click Save at the bottom. (Ensure the URL you enter points to the correct directory (or subdirectory) on your server.)
6. Now click the "Show Secret" button, and copy the Application Secret. You will need it in your store Admin.
7. Also click back on the Credentials tab, and copy the Application ID. You will need it in your store Admin.
8. See "Admin module configuration" below.



## Installation

### PHP Files
Simply upload these files from `files_to_upload` into the corresponding folders on your own store:

`/square_handler.php`

`/includes/classes/vendors/square` (whole directory)

`/includes/modules/payment/square.php`

`/includes/modules/payment/square_support/square_admin_notification.php`

`/includes/modules/payment/square_support/ZenCartChargeRequest.php`

`/includes/languages/english/modules/payment/square.php`

`/includes/modules/pages/checkout_payment/jscript_square.php`

**Note: You should NOT copy the README.md, LICENSE or CHANGELOG.md files to your live server.**

 
## Configure the Square Module in your store Admin
1. This module needs those 2 configuration settings from your Square account 


	(Login to https://squareup.com/dashboard/apps and click on My Apps in the upper-right corner of the page.  Next, if you have one, click the Manage App button next to your listed App to obtain the configuration settings. Alternatively, click the "Go To Developer Portal" button, or visit https://connect.squareup.com/apps to get there) :
   * Application ID (under "Credentials" tab)
   * Application Secret (under "OAuth" tab)  (NOTE: While you are there, make sure your Redirect URL has been set as described earlier)

2. In your Zen Cart Admin, go to the Modules->Payments menu, and click on Square. Then click Install. 

3. You will see where to enter your Application ID and Application Secret. After pasting those values into your Square module settings, click the Update button.

4. Below the Edit/Remove buttons for the module,  you will see an "ALERT:  Access Token Not Set" message, along with a reminder about the Redirect URL for your site and a green button, "Click Here to Login and Authorize Your Account."  This will NOT work if you do not have the Redirect URL correctly set.

5. Click the green button. It will take you to your Square account and ask you to login and authorize the app for your store. Click Authorize. (It may also just quickly open and close a window if you're already logged in to Square in the same browser.) It may look like it never left or did anything, but if you refresh the page, that green button and alert will go away.

6. Click Edit in the payment module settings, and find the Location ID drop-down. Choose one. If you have more than one choice, choose the location you want your Zen Cart store's payments to be associated with. Click Update.

7. Your checkout pages will now show a credit card payment option, powered by Square.


## Handling Refunds, Captures, Voids
### Refunds
When viewing a transaction in your store Admin, if it was paid using Square within the last 120 days, you will be shown the option to Refund some or all of the payment. Simply enter the amount you wish to refund. Then check the box to confirm, and click Submit. You will see a status message confirming the refund has been issued.

NOTE: You should do refunds via your store Admin. While you might be able to start a refund from your Square Dashboard, that will NOT automatically update your orders in Zen Cart to show the change in status.  But if you do the refund from within your Zen Cart Admin, the refund will show in the customer's order-status-history, which is a more friendly experience for them.

### Captures
If you've configured the module to "authorize only" instead of automatically capturing each "purchase", then you will also see an option to Capture the previously-authorized transaction. CAPTURES MUST BE DONE WITHIN 6 DAYS, or they will be voided automatically, releasing the hold on those funds for the customer.

NOTE: Captures can ONLY be done via your store Admin. You will not see uncaptured (authorize-only) transactions in your Square Dashboard.

### Voids
If you've configured the module to "authorize only", then you will see a Void option. This can be used to cancel an Authorization and release the "hold" on those funds back to the customer. 

NOTE: Voids can ONLY be done via your store Admin. You will not see authorize-only transactions in your Square Dashboard.


## Automatic Token Refreshing via cron
This is optional. For low-traffic stores, this is recommended.

In the initial setup, you clicked a "button" to activate the module by setting a Refresh Token. This token will expire in 45 days if no transactions are made. (30 days expiration, plus 15 days grace period.) After the 30 days you will see the "Get Access Token" button again in your store Admin, and will need to click it to reactivate the module as you did during initial setup.

With regular transaction activity the token will refresh itself, but this can come at the expense of a brief delay for random customers. To avoid the possibility of such delays, or expiration due to inactivity, we recommend that you set up a "cron job" on your server to automatically handle token refreshes without interfering with customer checkout activity.

To set up the cron job, go to the cron settings in your website hosting company's control panel, and create a new task to run every 3 days at 1am. The command for the task will be:

`php /home/user/your/path/to/wherever/is/square_handler.php`

Again, if your token expires, it can be manually recreated by clicking the green button to "login and authorize your account" as described in the Configuration section above.


## Testing
To perform test transactions, Square prefers that you simply set up a $1 product in your store, and test with a real credit card. You can refund the test transactions from inside your store Admin without any extra service fees.

(This module intentionally does not support the use of the Square Sandbox, at Square's request. Use your store's primary credentials as described earlier, and test using real credit cards and real transactions.)


## Support
If you are receiving errors from Square or are having problems configuring settings in your Square Account, contact Square Support.

If you are running into difficulty configuring the module in your store, or are getting "unusual" errors during checkout, report your issue on the Zen Cart Support Site at https://www.zen-cart.com/forum.php (create a free account profile in order to post)


### Common errors
1. `SQ-NONCE-FAILURE`: This can be caused by template differences, or javascript/jQuery conflicts in your checkout pages. 

	The module expects the template to have a button with `id="paymentSubmitButton"` ... or a `<div id="paymentSubmit">` containing an `<input type="image">` or `<input type="submit">`.

	If you've changed the checkout flow or altered the default IDs and DIVs in the `tpl_checkout_payment_default.php` template file (or whatever files your checkout flow uses), you may need to update your templates to add an `id="paymentSubmitButton"` or (worst case) change the jQuery selector in `jscript_square.php` to match whatever will uniquely identify the submit-button on your checkout payment template.


2. If you find the input fields for credit card numbers are flat with no text box to type into, this typically means you're missing the `jscript_square.php` file as described earlier in the Installation Instructions. Or you've got a firewall or browser plugin blocking access to Square's javascript code. Or you've got a plugin having jQuery conflicts.

3. You must always charge at least $1.00; transactions less than 1.00 will be rejected.

4. Cannot generate an Access Token.  
	If you re-used a pre-existing "application" in your Square Apps menu, go create a New Application. Some older applications may be based on an older API specification, or their tokens may not work without being regenerated. 

5. "The styling of the Square input fields is different from the rest of my payment input fields."  
	The module contains some CSS markup in the very bottom of the `/includes/modules/pages/checkout_payment/jscript_square.php` file, intended to create uniformity for the custom fields Square creates. For 99% of stores you will NOT need to change this; but in rare cases you may want to alter this CSS slightly to suit your needs. 


## Compatibility With Various Zen Cart Plugins

### One-Page Checkout by lat9 (version 1.2.1 or higher)
For One-Page Checkout to work, you must **copy** the `jscript_square.php` file from `/includes/modules/checkout_payment/` into the `/includes/modules/pages/checkout_one` folder.



---

_Copyright (c) 2017 Zen Ventures, LLC. All Rights reserved._
