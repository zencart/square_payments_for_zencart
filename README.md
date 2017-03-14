# Square Payment Module for Zen Cart

## Merchant Account Requirements

Create your own Square merchant account at [squareup.com](https://squareup.com/i/EB9F4D1C)

## About

The Square payment gateway allows you to connect your SquareUp account to your online store.

The payment gateway is operated by Square, Inc, which also offers POS card-swipe readers to handle card-present transactions you can conduct from your phone or tablet in-store or mobile.


## Compatibility

This module is compatible with Zen Cart® v1.5.4 and v1.5.5

This module works with PHP 5 and PHP 7

## Requirements

1. You must be using SSL on your website
2. You will need a Square account, already validated and connected with your bank.
3. You need to create an App inside your Square account. See below for instructions.

## Creating an App inside your Square account
1. Login at https://connect.squareup.com/apps to view the apps you've connected to your account.
2. If you have not created an app for your webstore yet, click New Application
3. Give it a name, such as "My Webstore", and click Create at the bottom of the page.
4. You will now be given access to some credentials, which you will need to enter into your Square module in your store. See "Admin module configuration" below.



## Installation

### PHP Files
Simply upload these files into the corresponding folders on your own store:

`/includes/modules/payment/squareup.php`

`/includes/languages/english/modules/payment/squareup.php`

`/includes/modules/pages/checkout_payment/jscript_squareup.php`

**Note: You should not copy the README.md, LICENSE or changelog.txt files to your live server.**

 
### Admin module configuration
This module requires that you enter 2 configuration settings from your Square account:

Login to https://connect.squareup.com/apps to obtain them.

These settings are used to process __real__ transactions against your account:

* Application ID
* Live Access Token (Personal Access Token)


### Sandbox testing
To perform test transactions, obtain your sandbox credentials and enter them in your store Admin instead of the "live" credentials.

Login to https://connect.squareup.com/apps to obtain:
- Sandbox Application ID
- Sandbox Access Token


---

_Copyright (c) 2017 Zen Ventures, LLC. All Rights reserved._
