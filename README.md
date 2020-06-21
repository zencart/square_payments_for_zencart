# Square Payment Module for Zen Cart

## About

The Square payment plugin module allows you to connect your Square account to your online store.
You can also do refunds for online purchases directly from within the order screen in your store Admin.

The payment gateway is operated by Square, Inc, which also offers POS card-swipe and chip readers to handle card-present transactions you can conduct from your phone or tablet in-store or mobile.

- Setup is quick: start accepting card payments in less than 5 minutes, with your existing Square account.
- No monthly fees and no setup fees.
- PCI Compliant. Customer never leaves your store!
- Standard rates are 2.9% + $0.30 per transaction for US and Canada businesses, and 2.5% for UK businesses. (Contact Square to negotiate.)
- Funds are deposited in your bank account in 1-2 business days.
- (If you are processing over $250,000 in annual sales, call (415) 805-9183 to connect with a US/Canada Square representative to discuss custom rates.)


## Compatibility

This module is compatible with Zen Cart® v1.5.4, v1.5.5, and v1.5.6

This module works with PHP versions 7.4, 7.3, 7.2, 7.1, 7.0, 5.6, 5.5 and 5.4


## Requirements

1. You will need a Square account, already validated and connected with your bank. You may create a Square merchant account at [squareup.com](https://squareup.com)
2. You need to create an App inside your Square account. See below for instructions.
3. You must be using SSL on your website


## Creating an App inside your Square account
1. Login at https://developer.squareup.com/apps to view the apps you've connected to your account.
2. Click to create a New Application for your Zen Cart store to access. Give it a name, such as "Webstore", and click Create at the bottom of the page.
3. You may be prompted to allow a multi-location upgrade to your account's API configuration. Proceed.
4. You will now be given access to some credentials. (If you already have a custom app and are trying to re-use it, you may need to revoke its token and regenerate one.)
5. Click on the OAuth tab. In the "**Redirect URL**" field, enter `https://your_store_url.com/square_handler.php` and click Save at the bottom. (Ensure the URL you enter points to the correct directory (or subdirectory) on your server.)
6. Now click the "Show Secret" button, and copy the Application Secret. You will need it in your store Admin.
7. Also click back on the Credentials tab, and copy the Application ID. You will need it in your store Admin.
8. See "Admin module configuration" below.



## Installation

### Installing Files To Your Zen Cart
Once you've downloaded the [Square Plugin](https://www.zen-cart.com/downloads.php?do=file&id=156) zip file, unzip it.

That will give you a folder with several sub-folders and files. Simply upload the files from `files_to_upload` into the corresponding folders on your own store:

`/square_handler.php` (to be clear, this goes in the same folder as your existing ipn_main_handler.php file)

`/includes/classes/vendors/square` (whole directory)

`/includes/modules/payment/square.php`

`/includes/modules/payment/square_support/square_admin_notification.php`

`/includes/modules/payment/square_support/ZenCartCreatePaymentRequest.php`

`/includes/languages/english/modules/payment/square.php`

`/includes/modules/pages/checkout_payment/jscript_square.php` (this file may be needed in alternate checkout flows too, see Compatibility section below)

**Note: You should NOT copy the README.md, LICENSE or CHANGELOG.md files to your live server.**


###Upgrading

Upgrade instructions: 

1. **Delete** the server's `/includes/classes/vendors/square` directory and the `/includes/modules/payment/square_support` directory (You will replace these in a moment, but this deletes older obsolete files first). 

2. Then upload all the files from the `files_to_upload` directory **just as you would in the Installation steps above**.

3. If you had placed extra copies of the `jscript_square.php` file in other checkout flow locations, re-copy that again using the updated file. (common example would be for the One-Page-Checkout addon, described later in this document)

4. Login to https://squareup.com/dashboard/apps and click on My Apps. Click the Manage App button next to your listed App.
 - Make sure you are in Production (otherwise transactions aren't real!!!)
 - Scroll down to the API Version settings, and make sure your Production API Version is at least version 2020-03-25 or later ... more specifically (in case this document is outdated), best to be sure it matches the version listed in the top of the /includes/modules/payment/square.php file.

 
## Configure the Square Module in your store Admin

1. This module needs 2 configuration settings from your Square account, from when you created your App inside Square:

	(Login to https://squareup.com/dashboard/apps and click on My Apps.  Next, if there is one, click the Manage App button next to your listed App to obtain the configuration settings. Alternatively, click the "Go To Developer Portal" button, or visit https://developer.squareup.com/apps to get there) :
   * Application ID (under "Credentials" tab)
   * Application Secret (under "OAuth" tab)  (NOTE: While you are there, make sure your Redirect URL has been set as described earlier)

2. In your Zen Cart Admin, go to the Modules->Payments menu, and click on Square. Then click Install. 

3. You will see where to enter your Application ID and Application Secret. After pasting those values, click the Update button. This will update the screen.

4. Below the Edit/Remove buttons for the module,  you will see an "ALERT:  Access Token Not Set" message, along with a reminder about the Redirect URL for your site and a green button, "Click Here to Login and Authorize Your Account."  This will NOT work if you do not have the Redirect URL correctly set in your Square account.

5. Click the green button. It will take you to your Square account and ask you to login and authorize the app for your store. Click Authorize. (If you're already logged in, it may also just quickly open and close a window.) It may look like it never left or did anything, but if you refresh the page, that green button and alert will go away. (If the green button remains after reloading the page, then you probably didn't put your Redirect URL into your Square account's OAuth settings page correctly. Go back and do that, then click the green button again.)

6. Click Edit in the payment module settings, and find the Location ID drop-down. Choose one. If you have more than one choice, choose the location you want your Zen Cart store's payments to be associated with. Click Update.


Done. Your checkout pages will now show a credit card payment option, powered by Square.


## Handling Refunds, Captures, Voids
### Refunds
When viewing a transaction in your store Admin, if it was paid using Square within the last 120 days, you will be shown the option to Refund some or all of the payment. Simply enter the amount you wish to refund. Then check the box to confirm, and click Submit. You will see a status message confirming the refund has been issued.

NOTE: You SHOULD do all refunds via your store Admin. While you might be able to start a refund from your Square Dashboard, that will NOT automatically update your orders in Zen Cart to show the change in status.  But if you do the refund from within your Zen Cart Admin, the refund will show in both places, as well as in the customer's order-status-history, which is a more friendly experience for them.

### Captures
If you've configured the module to "authorize only" instead of automatically capturing each "purchase", then you will also see an option to Capture the previously-authorized transaction. CAPTURES MUST BE DONE WITHIN 6 DAYS, or they will be voided automatically, releasing the hold on those funds for the customer.

NOTE: Captures can ONLY be done via your store Admin. You will not see uncaptured (authorize-only) transactions in your Square Dashboard.

### Voids
If you've configured the module to "authorize only", then you will see a Void option. This can be used to cancel an Authorization and release the "hold" on those funds back to the customer. 

NOTE: Voids can ONLY be done via your store Admin. You will not see authorize-only transactions in your Square Dashboard.


## Automatic Token Refreshing via cron
This is optional. For low-traffic stores, this is recommended.

In the initial setup, you clicked that "green button" to activate the module by setting what's called a Refresh Token. This token will expire in 45 days if no transactions are made. (30 days expiration, plus 15 days grace period.) After the 30 days you will see the green "Get Access Token" button again in your store Admin, and will need to click it to reactivate the module as you did during initial setup.

With regular transaction activity the token will refresh itself, but this can come at the expense of a brief delay for random customers. To avoid the possibility of such delays, or expiration due to inactivity, we recommend that you set up a "cron job" on your server to automatically handle token refreshes without interfering with customer checkout activity.

To set up the cron job, go to the cron settings in your website hosting company's control panel, and create a new task to run every 3 days at 1am. The command for the task will be:

`php /home/user/your/path/to/wherever/is/square_handler.php`

Again, if your token expires, Square will no longer show up during checkout for your customers. But you can fix it manually by clicking the green button to "login and authorize your account" as described in the Configuration section above.


## Testing
To perform test transactions, Square prefers that you simply set up a $1 product in your store, and test with a real credit card. You can refund the test transactions from inside your store Admin without any extra service fees.

(This module *intentionally* does not support the use of the Square Sandbox, at Square's request. Use your store's primary credentials as described earlier, and test using real credit cards and real transactions.)


## Support
If you are receiving errors from Square or are having problems configuring settings in your Square Account, contact Square Support.

If you are running into difficulty configuring the module in your store, or are getting "unusual" errors during checkout, report your issue on the Zen Cart Support Site at https://www.zen-cart.com/forum.php (create a free account profile in order to post)


### Common errors
1. `SQ-NONCE-FAILURE`: This can be caused by template differences, or javascript/jQuery conflicts in your checkout pages. 

	The module expects the checkout page to have a <form> element with a `name="checkout_payment"` in it.

	If you've changed the checkout flow such that `tpl_checkout_payment_default.php` template file (or whatever files your custom checkout flow uses), no longer has a form with that name, you may need to update your template accordingly.
	
	Additionally, some custom templates forget to copy the `jscript_framework.php` file from `/includes/templates/template_default/jscript/jscript_framework.php` to `/includes/templates/YOUR_TEMPLATE_FOLDER/jscript/jscript_framework.php`. Without this file some other (non-Square-related) checkout functions may break the javascript required for the Square module to work, thus resulting in `SQ-NONCE-FAILURE` errors. This can often be identified by finding "Undefined variable zcJS" in the browser's console error logs in the split second before the page refreshes after clicking submit.


2. If you find the input fields for credit card numbers are flat with no text box to type into, this typically means you're missing the `jscript_square.php` file as described earlier in the Installation Instructions. 

	Or you've got a firewall or browser plugin blocking access to Square's javascript code. 
	
	Or you've got a plugin having jQuery conflicts.

3. You must always charge at least $1.00; transactions less than 1.00 will be rejected.

4. Cannot generate an Access Token.  
	If you re-used a pre-existing "application" in your Square Apps menu, go create a New Application. Some older applications may be based on an older API specification, or their tokens may not work without being regenerated. You could click the option to regenerate a token for the old application; however, anything you've previously associated with that application may now be broken. Creating a new application for Zen Cart is the simplest approach.

5. "The styling of the Square input fields is different from the rest of my payment input fields."  
	The module contains some CSS markup in the very bottom of the `/includes/modules/pages/checkout_payment/jscript_square.php` file, intended to create uniformity for the custom fields Square creates. For 99% of stores you will NOT need to change this; but in rare cases you may want to alter this CSS slightly to suit your needs. 


### Problems with OAuth Token Generation
Common causes of problems with this:

1. Didn't upload `square_handler.php`

2. Uploaded `square_handler.php` into the wrong directory. It belongs in the "root" of your store's folders. (For reference, `ipn_main_handler.php` also exists in this directory.)

3. Didn't put the **OAuth Redirect URL** into Square's settings, or mistyped it. 


Less common, but possible causes:

1. You've got URL-rewriting rules set up in .htaccess or nginx, which are disallowing `square_handler.php` to be reached, probably because you're redirecting those requests to some other URL/file. Fix: allow `square_handler.php` to be accessed directly. You could clone any rules for `ipn_main_handler.php` for `square_handler.php` as a starting point.

2. You're running on a test site using a fake/unreachable URL (and therefore Square's servers can't reach it). Fix by using a real live website.

3. You're running behind a firewall that's got aggressive restrictions to files. There's nothing special required for `square_handler.php`: It's just a simple PHP file, and will be accessed via port 443 ... just like any other file. There should be no rule against it.



## Compatibility With Various Zen Cart Plugins

### One-Page Checkout by lat9
For One-Page Checkout to work, you must **copy** the `jscript_square.php` file from `/includes/modules/pages/checkout_payment/` into the `/includes/modules/pages/checkout_one/` folder.



---

_Copyright (c) 2020 Zen Ventures, LLC. All Rights reserved._
