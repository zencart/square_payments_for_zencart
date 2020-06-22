# Technical Notes regarding the Square Payment Module for Zen Cart

## Accessing the Sandbox

1. Login to your Square account dashboard
2. Choose "Apps" from the main navigation menu (down the left side).
This screen lists the Apps you've added/integrated to your Square account. If you haven't created one yet, see the README for instructions.
3. Click on your app for your ZC Integration to open the Sandbox Dashboard
 - make sure the slider is set to Sandbox mode (not Production)
4. Click on OAuth and update the URL to your current test site URL's `square_handler.php`, so that Square's responses and oauth handshakes will work. Click Save.
5. To open the Sandbox Seller Dashboard to view test transaction activity, click on the big round left-arrow button in the nav above the OAuth nav choice, and then in bottom center click Open next to the "Default test site" location. Pictures of these screens, and more instructions, can be found at: https://developer.squareup.com/docs/testing/sandbox

## Module Configuration
To enable additional configuration fields for sandbox testing, open the Edit screen for your Square module in your Zen Cart admin panel, and add `&sandbox=1` to the end of the URL and press Enter. Fill in the fields (and the additional fields) with Sandbox credentials, and select the Sandbox mode radio button, and save.

## Testing Resources

For card numbers to use for testing, and special ways to test for specific responses/errors, see: 
https://developer.squareup.com/docs/testing/test-values#sandbox-payments


### Testing Checklist:
- purchase in default currency
- purchase in alternate currency (be sure things show correctly in ZC admin, but charges in Square are in appropriate currency, and exchange rates match)
- purchase over $1,000 to ensure commas are not a problem
- Do a Refund
- Do two Authorization purchases
- Do a Capture on one
- Do a Void on the other
- Simulate failures:
 - communication failure to Handler script (rename file, for example)
 - AVS failures
 - auth failures
 - capture failures
 - refund failures
 - void failures

- Check logs for PHP errors

