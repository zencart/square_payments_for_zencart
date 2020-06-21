# Square Payment Module for Zen Cart

Version 1:
 - Requires PHP 7.4, 7.3, 7.2, 7.1, 7.0, 5.6, 5.5, or 5.4
 - Requires Zen Cart v1.5.4 or v1.5.5 or v1.5.6 or v1.5.7


0.77 - June 23, 2017 - First Release - based on Square Connect PHP SDK 2.2.0

0.80 - June 24, 2017 - Improvements to initial setup.

0.81 - June 28, 2017 - Change made to accommodate a Square SDK Locations API limitation.

0.82 - July 31, 2017 - Fix a small currency conversion error. (Not triggered unless store is multi-currency)

0.90 - Aug 14, 2017 - Fix problem with automatic token refresh; and updated to Square Connect SDK v2.2.1

0.91 - Dec 18, 2017 - Fix compatibility with OnePageCheckout plugin. Also updated to Square Connect SDK v2.5.1

0.92 - Dec 28, 2017 - Update auto-refresh of admin page when getting an Access Token


 	Now when a token is obtained after pressing the green button in the Admin, the page should auto-refresh within 5 seconds to indicate that the token was successfully applied. If it doesn't, simply refresh the page and the green button should go away.


0.93 - May 2018 - Compatibility updates. This module REQUIRES PHP 5.4 or newer. Preferably 5.6 or 7.1. Specifically: PHP 5.3 is NOT supported by this module.

0.94 - May 2018 - Simply updates the database storage configuration to allow for the newer longer transaction-id responses, since these are used to empower the in-Admin refund feature. 

0.95 - Nov 2018 - Fixed strict ['id'] error, Fixed currency conversion error if Square account currency was different from the Store's Default Currency. Also upgraded the SquareConnect SDK from 2.5.1 to 2.20180918.1.

0.96 - Dec 2018 - Fixed a jQuery error which could cause confusion on checkout payment page if checkout rules suddenly made the Square module unavailable. Also upgraded the SquareConnect SDK to 2.20181205.0.

0.97 - March 15 2019 - Updated to send headers forcing older 20181205 API version until code is refactored to use Square's new API endpoints introduced on 2019-03-13. Only files changed were the square_handler.php and the main square.php payment module file. (No change to the vendors folder.)

1.0 - March 15, 2020 - Updated to Square's 3.20200226.0 API spec, which uses new endpoints and improves OAuth token renewal process. (Will require a re-authorization of the module after upgrading to this version. It may not happen for up to 30 days.)
Also includes brief product/shipping details in transaction comments.

1.1 - April 8, 2020 - Updated to Square SDK version 3.20200325.0. Also fixed a token-refresh bug from v1.0

1.2 - June 21, 2020 - Fixed refund bug and capture bug in v1.1


---

###Upgrading

Upgrade instructions: Delete the server's /includes/classes/vendors/square directory and the /includes/modules/payment/square_support directory (You will replace these in a moment, but this deletes older obsolete files first). Then upload all the files from the files_to_upload directory, putting them into the same directory on the server as you find them in this zip.
