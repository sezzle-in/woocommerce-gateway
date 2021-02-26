=== Sezzle Woocommerce Payment ===
Contributors: rishipyth
Tags: sezzle, installments, payments, paylater
Requires at least: 5.3.2
Version: 4.0.3
Stable tag: 4.0.1
Tested up to: 5.5.3
Requires PHP: 5.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Sezzle is a payment gateway for letting your customers buy now and pay later.

## Installation

1. Signup for Sezzle at `https://dashboard.sezzle.in/merchant/signup/`. Login to your dashboard and keep your API Keys page open. You will need it in step `6`.
2. Make sure you have WooCommerce plugin installed.
3. Install the Sezzle Payment plugin and activate.
4. Go to admin > WooCommerce > Settings > Payments > Sezzle.
5. Fill the form according to the instructions given in the form and save it.


### Your store is ready to use Sezzle as a payment gateway.

## Restrict Sezzle based on user roles
Make sure Sezzle Gateway plugin is `active` in Wordpress admin.

#### Hide Sezzle Payment Gateway
If you want to hide Sezzle's payment gateway based on user roles

1. Add the following function to your code:

`
function restrict_sezzle_pay($available_gateways) {
    if (is_admin()) {
        return $available_gateways;
    }
    unset($available_gateways['sezzlepay']);
    return $available_gateways;
}
`

2. Call the following filter `inside` the user's access deciding code:

`
add_filter('woocommerce_available_payment_gateways', 'restrict_sezzle_pay');
`

#### Hide Sezzle Product Widget
If you want to hide Sezzle's product widget based on user's roles

1. Call the following action `inside` the user's access deciding code:

`
remove_action('woocommerce_single_product_summary', 'add_sezzle_product_banner');
`

### Example code with `woocommerce-memberships` plugin:

If you are using `woocommerce-memberships` to deal with user roles and restrictions, you can use the following code to hide Sezzle gateway and product widget based on user's role like so:

`
$user_id = 1;
$plan_id = 42;
$plan = get_post($plan_id);
// If user does not belong to the plan
if(!wc_memberships_is_user_member($user_id, $plan)) {
    // hide the gateway
    add_filter('woocommerce_available_payment_gateways', 'restrict_sezzle_pay'); // make sure restrict_sezzle_pay function is available
    // hide the product widget
    remove_action('woocommerce_single_product_summary', 'add_sezzle_widget_in_product_page');
}
`

### Notes
1. Read about `woocommerce_available_payment_gateways` hook [here](http://hookr.io/filters/woocommerce_available_payment_gateways/).

For more information, please visit [Sezzle Docs](https://docs.sezzle.in/#woocommerce).

== Changelog ==

= 3.1.4 =
* FIX: Sqaure and Stripe Payment Method Form blocking.
* FEATURE: Ability to turn on/off installment widget plan from Sezzle settings.

= 3.1.3 =
* FIX: Multiple Installment Widget.

= 3.1.2 =
* FEATURE: Installment Plan Widget under Sezzle Payment Option in Checkout Page.
* FIX: Admin check added in gateway hiding function.

= 3.1.1 =
* FIX: Failing of sudden orders being already captured.
* FEATURE: Ability to turn on/off logging.

= 3.1.0 =
* MODIFY: Transaction Mode added instead of Sezzle API URL.

= 3.0.5 =
* FIX: Undefined index:Authorization during redirection to Sezzle.

= 3.0.4 =
* MODIFY: Updated User Guide.

= 3.0.3 =
* MODIFY: Updated Widget Script URL.

= 3.0.2 =
* FIX: Order key property access through function instead of direct access.

= 3.0.1 =
* FIX: Return URL from Sezzle Checkout changed to Checkout URL of merchant website.
* FEATURE: Added logs for checking API functions.
* FIX: Check payment capture status before capturing the payment so that already captured orders does not fall into the process.

= 3.0.0 =
* FIX: Downgraded to previous stable version due to some conflicts arising in few versions.
* MODIFY: Delayed capture has been removed.
* MODIFY: Widget in Cart has been removed.

= 2.0.9 =
* FIX: Added check to include settings class when not available.

= 2.0.8 =
* MODIFY: Wordpress support version has been changed to 4.4.0 or higher.

= 2.0.7 =
* FEATURE: Hiding of Sezzle Pay based on cart total.
* FEATURE: Sezzle Widget and Sezzle Payment merged into one plugin.
* FIX : Amount converted to cents while refund.

= 2.0.6 =
* FIX: Page hanging issue during order status change for other payment methods.

= 2.0.5 =
* FIX: Security fix and quality improvements.

= 2.0.4 =
* FEATURE: Delayed Capture.
* FEATURE: Sezzle Widget for Cart Page.
* FEATURE: New settings for managing Sezzle Widget.

== Upgrade Notice ==

= 3.1.4 =
* This will fix the Sqaure and Stripe Payment Method Form blocking issue in the Checkout Page.
* User can decide on turning on/off the installment widget plan from Sezzle settings in WooCommerce Dashboard.

= 3.1.3 =
* User will not see multiple installment widget on changing shipping addresses.

= 3.1.2 =
* User will be able to visualize Sezzle installment plan while in Checkout Page.
* Admin Check added for Gateway removal process.

= 3.1.1 =
* Orders will not get failed if it is found captured.
* User can turn on/off Sezzle logging.

= 3.1.0 =
* User can select between LIVE and SANDBOX mode instead of adding the URL.

= 3.0.5 =
* The fix is on the logging of data and mainly linked to checkout process. Checkout should work fine now for those who were experiencing issues while redirection.

= 3.0.4 =
* Updated User Guide.

= 3.0.3 =
* Sezzle will control the configuration of widget if you upgrade to this version.

= 3.0.2 =
* This is reflected in the order confirmation url.

= 3.0.1 =
* When user will try to get back from Sezzle Checkout, they will get a seamless redirection to Checkout Page.
* Logs will be generated from now onwards to track the Checkout Flow.
* Capture status will be checked programmatically to avoid recapturing of captured orders.

= 3.0.0 =
* Downgraded to previous stable version due to some conflicts arising in few versions.
* Delayed capture has been removed.
* Widget in Cart has been removed.

= 2.0.8 =
* Wordpress support version has been changed to 4.4.0 or higher.

= 2.0.7 =
* If you have a requirement of hiding Sezzle Pay based on cart total, upgrade to this version.
* Fixes on amount conversion during refund.
* Conflict issue with Aweber plugin and as a result, Sezzle Widget and Sezzle Payment merged into one plugin. No need to activate two plugins now. Only one will show up as Sezzle WooCommerce Payment.

= 2.0.6 =
* This version fixes a major bug.  Upgrade immediately.

= 2.0.5 =
* This version fixes security bug.  Upgrade immediately.

= 2.0.4 =
This version has the following major features included.
* Delayed Capture.
* Sezzle Widget for Cart Page.
* New settings for managing Sezzle Widget.
