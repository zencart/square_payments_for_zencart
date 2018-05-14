# Square Payment Module for Zen Cart


0.77 - June 23, 2017 - First Release - based on Square Connect PHP SDK 2.2.0
	 - Requires PHP 7.2 or 7.1, 7.0, 5.6, 5.5, or 5.4
	 - Requires Zen Cart v1.5.4 or v1.5.5

0.80 - June 24, 2017 - Improvements to initial setup.

0.81 - June 28, 2017 - Change made to accommodate a Square SDK Locations API limitation.

0.82 - July 31, 2017 - Fix a small currency conversion error. (Not triggered unless store is multi-currency)

0.90 - Aug 14, 2017 - Fix problem with automatic token refresh; and updated to Square Connect SDK v2.2.1

0.91 - Dec 18, 2017 - Fix compatibility with OnePageCheckout plugin. Also updated to Square Connect SDK v2.5.1

0.92 - Dec 28, 2017 - Update auto-refresh of admin page when getting an Access Token


 	Now when a token is obtained after pressing the green button in the Admin, the page should auto-refresh within 5 seconds to indicate that the token was successfully applied.


0.93 - May 2018 - Compatibility updates. This module REQUIRES PHP 5.4 or newer. Preferably 5.6 or 7.1. Specifically: PHP 5.3 is NOT supported by this module; however, since some stores still use this ancient version, it will throw errors if modern PHP coding styles are found. Forcing it back to old style for awhile longer.
Also changed network connection parameters, to remove CURL followlocation, due to incompatibility with a few sites.

