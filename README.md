# Square Payment Module for Zen Cart

## About

The Square payment gateway allows you to connect your SquareUp account to your online store.

The payment gateway is operated by Square, Inc, which also offers POS card-swipe readers to handle card-present transactions you can conduct from your phone or tablet in-store or mobile.


## Compatibility

This module is compatible with Zen Cart® v1.5.4 and v1.5.5

This module works with PHP 7 and is backwards compatible down to PHP 5.4

## Merchant Account Requirements

You must have a Square account. You may create a Square merchant account at [squareup.com](https://squareup.com/i/A7FE3E64)

## Requirements

1. You must be using SSL on your website
2. You will need a Square account, already validated and connected with your bank.
3. You need to create an App inside your Square account. See below for instructions.

## Creating an App inside your Square account
1. Login at https://connect.squareup.com/apps to view the apps you've connected to your account.
2. If you have not created an app for your webstore yet, click New Application
3. Give it a name, such as "Webstore", and click Create at the bottom of the page.
4. You will now be given access to some credentials.
5. Click on the OAuth tab. In the "**Redirect URL**" field, enter `https://your_store_url.com/square_handler.php` and click Save at the bottom
6. Now click the "Show Secret" button, and copy the Application Secret. You will need it in your store Admin.
7. Also click back on the Credentials tab, and copy the Application ID. You will need it in your store Admin.
8. See "Admin module configuration" below.



## Installation

### PHP Files
Simply upload these files into the corresponding folders on your own store:

`/square_handler.php`

`/includes/classes/vendors/square` (whole directory)

`/includes/modules/payment/squareup.php`

`/includes/modules/payment/square_support/squareup_admin_notification.php`

`/includes/languages/english/modules/payment/squareup.php`

`/includes/modules/pages/checkout_payment/jscript_squareup.php`

**Note: You should not copy the README.md, LICENSE or changelog.txt files to your live server.**

 
## Configure the Square Module in your store Admin
1. This module needs those 2 configuration settings from your Square account (login to https://connect.squareup.com/apps to obtain them):
  * Application ID (under "Credentials" tab)
  * Application Secret (under "OAuth" tab)

2. After pasting those values into your SquareUp module settings, click Save.

3. At the top of the displayed settings you will see a button about an Expired Token. Click that button. It will take you to your Square account and ask you to login and authorize the app for your store. Click Authorize. (It may also just quickly open and close a window if you're already logged in to Square in the same browser.)
After doing that, back in your store Admin, if you refresh the page, that button will go away

4. Click Edit in the payment module settings, and click on the Locations drop-down, and choose which location you want your Zen Cart store's payments to be associated with. Click Save.

5. Your checkout pages will now show a credit card payment option, powered by Square.

## Handling Refunds, Captures, Voids
### Refunds
When viewing a transaction in your store Admin, if it was paid using Square, you will be shown the option to Refund some or all of the payment. Simply enter the amount, and also the Transaction and Tender ID values shown in the order-comments for the original payment. Then check the box to confirm, and click Submit. You will see a status message confirming the refund has been issued.

### Captures
If you've configured the module to "authorize only" instead of automatically capturing each "purchase", then you will also see an option to Capture the previously-authorized transaction. Captures must be done within 6 days, or they will be voided automatically, releasing the hold on those funds for the customer.

### Voids
If you've configured the module to "authorize only", then you will see a Void option. This can be used to cancel an Authorization and release the "hold" on those funds back to the customer. 

## Automatic Token Refreshing via cron
This is optional.

The "button" you clicked earlier to activate the module by setting a Refresh Token must be used if your module's token expires. It will expire in 30 days if not used.
With regular transaction activity the token will refresh itself, but this can come at the expense of a brief delay for random customers. To avoid the possibility of such delays, we recommend that you set up a "cron job" on your server to automatically handle token refreshes without interfering with customer checkout activity.

To set up the cron job, go to the cron settings in your website hosting company's control panel, and create a new task to run every 3 days at 1am. The command for the task will be:

`php /home/user/your/path/to/wherever/is/square_handler.php`

If your token expires, it can be renewed within 15 days by simply making a purchase in your store using Square. If it has been expired for more than 15 days, your store will automatically disable the module until you go into the Admin and re-enable it and also click the button to re-authorize it.


## Testing
To perform test transactions, Square prefers that you simply set up a $1 product in your store, and test with a real credit card. You can refund the test transactions from inside your store Admin or from inside your Square account, without any extra service fees.

(This module intentionally does not support the use of the Square Sandbox, at Square's request. Use your store's primary credentials as described earlier, and test using real credit cards and real transactions.)


## Support
If you are receiving errors from Square or are having problems configuring settings in your Square Account, contact Square Support.

If you are running into difficulty configuring the module in your store, or are getting "unusual" errors during checkout, report your issue on the Zen Cart Support Site at https://www.zen-cart.com/forum.php (create a free account profile in order to post)

### Common errors
- "nonce" failures:  This is typically caused by javascript or jquery conflicts in your checkout pages
- the fields for credit card numbers are flat with no text box to type into: This typically means you're missing the jscript_squareup.php file as described earlier in the Installation instructions. Or you've got a firewall or browser plugin blocking access to Square's javascript code





---

_Copyright (c) 2017 Zen Ventures, LLC. All Rights reserved._
