<?php
/*
Plugin Name: Sezzle WooCommerce Payment
Description: Buy Now Pay Later with Sezzle
Version: 3.0.5
Author: Sezzle
Author URI: https://www.sezzle.com/
Tested up to: 5.4.2
Copyright: © 2020 Sezzle
WC requires at least: 3.0.0
WC tested up to: 4.2.2

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

    add_action('plugins_loaded', 'woocommerce_sezzlepay_init');

    function woocommerce_sezzlepay_init()
    {
        if (!class_exists('WC_Payment_Gateway')) return;

        class WC_Gateway_Sezzlepay extends WC_Payment_Gateway
        {

            public static $log = false;
            private static $_instance = NULL;

            public static function instance()
            {
                if (is_null(self::$_instance)) {
                    self::$_instance = new self();
                }
                return self::$_instance;
            }

            public function __construct()
            {
                global $woocommerce;
                $this->id = 'sezzlepay';
                $this->method_title = __('Sezzle', 'woo_sezzlepay');
                $this->method_description = __('Buy Now and Pay Later with Sezzle Pay.', 'woo_sezzlepay');
                $this->supports = array('products', 'refunds');
                $this->description = __('Buy now, Pay later in 4 installments. 0% interest.', 'woo_sezzlepay');
                $this->get_sezzle_icon();
                $this->init_form_fields();
                $this->init_settings();
                $this->title = $this->get_option('title');

                add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
                add_action('woocommerce_api_' . strtolower(get_class($this)), array($this, 'sezzle_payment_callback'));
            }

            public static function log($message)
            {
                if (empty(self::$log)) {
                    self::$log = new WC_Logger();
                }
                self::$log->add('Sezzlepay', $message);
            }

            function getAuthTokenUrl()
            {
                return $this->get_base_url() . '/authentication';
            }

            function getOrderIdUrl($reference)
            {
                return $this->get_base_url() . '/orders' . '/' . $reference . '/save_order_id';
            }

            function checkoutRefundUrl($reference)
            {
                return $this->get_base_url() . '/orders' . '/' . $reference . '/refund';
            }

            function checkoutCompleteUrl($reference)
            {
                return $this->get_base_url() . '/checkouts' . '/' . $reference . '/complete';
            }

            function getSubmitCheckoutDetailsAndGetRedirectUrl()
            {
                return $this->get_base_url() . '/checkouts';
            }

            function ordersSubmitUrl()
            {
                return $this->get_base_url() . '/merchant_data' . '/woocommerce/merchant_orders';
            }

            function heartbeatUrl()
            {
                return $this->get_base_url() . '/merchant_data' . '/woocommerce/heartbeat';
            }

            function getOrderUrl($reference)
            {
                return $this->get_base_url() . '/orders/' . $reference;
            }

            function get_base_url()
            {
                $enabled_mode = $this->get_option('mode');
                if ($enabled_mode === 'sandbox') {
                    $url = 'https://sandbox.gateway.sezzle.in/v1';
                } else {
                    $url = 'https://gateway.sezzle.in/v1';
                }

                if (substr($url, -1) == '/') {
                    $url = substr($url, 0, -1);
                }

                return $url;
            }

            function get_sezzle_icon()
            {
                $enabled_mode = $this->get_option('mode');
                if ($enabled_mode === 'sandbox') {
                    $this->icon = 'https://cdn.shopify.com/s/files/applications/f898f4bc87df465198e1ce07cf07dcd6.png?height=24&1589946841';
                } else {
                    $this->icon = 'https://cdn.shopify.com/s/files/applications/cf8da439fdbc580ee9a666e47eb462de.png?height=24&1589947003';
                }
            }

            function init_form_fields()
            {
                $this->form_fields = array(
                    'enabled' => array(
                        'title' => __('Enable/Disable', 'woo_sezzlepay'),
                        'type' => 'checkbox',
                        'label' => __('Enable Sezzlepay', 'woo_sezzlepay'),
                        'default' => 'yes'
                    ),
                    'title' => array(
                        'title' => __('Title', 'woo_sezzlepay'),
                        'type' => 'text',
                        'description' => __('This controls the payment method title which the user sees during checkout.', 'woo_sezzlepay'),
                        'default' => __('Sezzle', 'woo_sezzlepay'),
                        'desc_tip' => true,
                    ),
                    'mode' => array(
                        'title' => __('Mode', 'woo_sezzlepay'),
                        'type' => 'select',
                        'description' => __('Sezzlepay sandbox can be used to test payments', 'woo_sezzlepay'),
                        'desc_tip' => true,
                        'required' => true,
                        'options' => array(
                            'live' => __('Live'),
                            'sandbox' => __('Sandbox')
                        ),
                        'default' => 'live',
                    ),
                    'merchant-id' => array(
                        'title' => __('Merchant ID', 'woo_sezzlepay'),
                        'type' => 'text',
                        'default' => '',
                        'description' => __('Look for your Sezzle merchant ID in your Sezzle Dashboard', 'woo_sezzlepay'),
                        'desc_tip' => true,
                    ),
                    'private-key' => array(
                        'title' => __('Private Key', 'woo_sezzlepay'),
                        'type' => 'text',
                        'default' => '',
                        'description' => __('Look for your Private Key or create one in your Sezzle Dashboard', 'woo_sezzlepay'),
                        'desc_tip' => true,
                    ),
                    'public-key' => array(
                        'title' => __('Public Key', 'woo_sezzlepay'),
                        'type' => 'text',
                        'default' => '',
                        'description' => __('Look for your Public Key or create one in your Sezzle Dashboard', 'woo_sezzlepay'),
                        'desc_tip' => true,
                    ),
                    // 'min-checkout-amount' => array(
                    //     'title' => __('Minimum Checkout Amount', 'woo_sezzlepay'),
                    //     'type' => 'number',
                    //     'default' => ''
                    // ),
                    'payment-option-availability' => array(
                        'title' => __('Payment option availability in other countries', 'woo_sezzlepay'),
                        'type' => 'checkbox',
                        'label' => __('Enable', 'woo_sezzlepay'),
                        'description' => __('Enable Sezzlepay gateway in countries other than India.', 'woo_sezzlepay'),
                        'desc_tip' => true,
                        'default' => 'yes'
                    ),
                    // 'show-product-page-widget' => array(
                    //     'title' => __('Show Sezzle widget in product pages', 'woo_sezzlepay'),
                    //     'type' => 'checkbox',
                    //     'description' => __('Show the sezzle widget under price label in product pages', 'woo_sezzlepay'),
                    //     'desc_tip' => true,
                    //     'default' => 'yes'
                    // ),
                );
            }

            function process_payment($order_id)
            {
                global $woocommerce;
                if (function_exists("wc_get_order")) {
                    $order = wc_get_order($order_id);
                } else {
                    $order = new WC_Order($order_id);
                }

                return $this->get_redirect_url($order);
            }

            function get_redirect_url($order)
            {
                $customer = $order->get_user();
                $uniqOrderId = uniqid() . "-" . $order->get_id();
                $order->set_transaction_id($uniqOrderId);
                $order->save();
                $body = array(
                    'amount_in_cents' => (int) (round($order->get_total(), 2) * 100),
                    'currency_code' => get_woocommerce_currency(),
                    'order_description' => (string) $uniqOrderId,
                    'order_reference_id' => (string) $uniqOrderId,
                    'display_order_reference_id' => (string) $order->get_id(),
                    'checkout_complete_url' => get_site_url() . '/?wc-api=WC_Gateway_Sezzlepay&key=' . $order->get_order_key(),
                );

                $body['checkout_cancel_url'] = wc_get_checkout_url();

                $body['customer_details'] = array(
                    'first_name' => $order->get_billing_first_name(),
                    'last_name' => $order->get_billing_last_name(),
                    'email' => $order->get_billing_email(),
                    'phone' => $order->get_billing_phone(),
                    'created_at' => $customer == FALSE ? null : $customer->user_registered,
                );

                $body['billing_address'] = array(
                    'street' => $order->get_billing_address_1(),
                    'street2' => $order->get_billing_address_2(),
                    'city' => $order->get_billing_city(),
                    'state' => $order->get_billing_state(),
                    'postal_code' => $order->get_billing_postcode(),
                    'country_code' => $order->get_billing_country(),
                    'phone' => $order->get_billing_phone(),
                );

                $body['shipping_address'] = array(
                    'street' => $order->get_shipping_address_1(),
                    'street2' => $order->get_shipping_address_2(),
                    'city' => $order->get_shipping_city(),
                    'state' => $order->get_shipping_state(),
                    'postal_code' => $order->get_shipping_postcode(),
                    'country_code' => $order->get_shipping_country(),
                );

                $body["items"] = array();
                if (count($order->get_items())) {
                    foreach ($order->get_items() as $item) {
                        if ($item['variation_id']) {
                            if (function_exists("wc_get_product")) {
                                $product = wc_get_product($item['variation_id']);
                            } else {
                                $product = new WC_Product($item['variation_id']);
                            }
                        } else {
                            if (function_exists("wc_get_product")) {
                                $product = wc_get_product($item['product_id']);
                            } else {
                                $product = new WC_Product($item['product_id']);
                            }
                        }
                        $itemData = array(
                            "name" => $item['name'],
                            "sku" => $product->get_sku(),
                            "quantity" => $item['qty'],
                            "price" => array(
                                "amount_in_cents" => (int) (round(($item['line_subtotal'] / $item['qty']), 2) * 100),
                                "currency" => get_woocommerce_currency()
                            )
                        );
                        array_push($body["items"], $itemData);
                    }
                }
                $body['merchant_completes'] = true;
                $this->log("Sezzle redirecting");
                $args = array(
                    'headers' => array(
                        'Authorization' => $this->get_sezzlepay_authorization_code(),
                        'Content-Type' => 'application/json',
                    ),
                    'body' => json_encode($body),
                    'timeout' => 80,
                    'redirection' => 35
                );

                $submitCheckoutDetailsAndGetRedirectUrl = $this->getSubmitCheckoutDetailsAndGetRedirectUrl();
                $response = wp_remote_post($submitCheckoutDetailsAndGetRedirectUrl, $args);
                $encodeResponseBody = wp_remote_retrieve_body($response);
                $this->dump_api_actions($submitCheckoutDetailsAndGetRedirectUrl, $args, $encodeResponseBody);
                $body = json_decode($encodeResponseBody);
                if (isset($body->checkout_url)) {
                    // save url to use later
                    update_post_meta($order->get_id(), 'sezzle_redirect_url', $body->checkout_url);
                    return array(
                        'result' => 'success',
                        'redirect' => $order->get_checkout_payment_url(true),
                    );
                } else {
                    $order->add_order_note(__('Unable to generate the transaction ID. Payment couldn\'t proceed.', 'woo_sezzlepay'));
                    wc_add_notice(__('Sorry, there was a problem preparing your payment.', 'woo_sezzlepay'), 'error');

                    return array(
                        'result' => 'failure',
                        'redirect' => $order->get_checkout_payment_url(true)
                    );
                }
            }

            public function dump_api_actions($url, $request = null, $response = null, $status_code = null)
            {
                $this->log($url);
                $this->log("Request Body");
                $this->log(json_encode($request));
                $this->log("Response Body");
                $this->log($response);
                $this->log($status_code);
            }

            function receipt_page($order_id)
            {
                global $woocommerce;
                if (function_exists("wc_get_order")) {
                    $order = wc_get_order($order_id);
                } else {
                    $order = new WC_Order($order_id);
                }

                // Get the order token
                $redirectUrl = get_post_meta($order_id, 'sezzle_redirect_url', true);

                // Update order status if it isn't already
?>
                <script>
                    var redirectUrl = <?php echo json_encode($redirectUrl); ?>;
                    window.location.replace(redirectUrl);
                </script>
<?php
            }

            function get_sezzlepay_authorization_code()
            {
                $body = array(
                    'public_key' => $this->get_option('public-key'),
                    'private_key' => $this->get_option('private-key')
                );
                $args = array(
                    'headers' => array(
                        'Content-Type' => 'application/json',
                    ),
                    'body' => json_encode($body),
                    'timeout' => 80,
                    'redirection' => 35
                );

                $authTokenURL = $this->getAuthTokenUrl();
                $response = wp_remote_post($authTokenURL, $args);
                $encodeResponseBody = wp_remote_retrieve_body($response);
                $this->dump_api_actions($authTokenURL, $args, $encodeResponseBody);
                $body = json_decode($encodeResponseBody);
                return "Bearer $body->token";
            }

            function isPaymentCaptured($reference)
            {
                $url = $this->getOrderUrl($reference);
                $args = array(
                    'headers' => array(
                        'Authorization' => $this->get_sezzlepay_authorization_code(),
                        'Content-Type' => 'application/json',
                    ),
                    'body' => null,
                    'timeout' => 80,
                    'redirection' => 35
                );

                $response = wp_remote_get($url, $args);
                $encodeResponseBody = wp_remote_retrieve_body($response);
                $this->dump_api_actions($url, $args, $encodeResponseBody);
                $response = json_decode($encodeResponseBody, true);
                if (isset($response["captured_at"]) && $response["captured_at"]) {
                    return true;
                }
                return false;
            }

            function sezzle_payment_callback()
            {
                global $woocommerce;
                $_REQUEST = stripslashes_deep($_REQUEST);
                $order_key = $_REQUEST['key'];
                $order_id = wc_get_order_id_by_order_key($order_key);
                if (function_exists("wc_get_order")) {
                    $order = wc_get_order($order_id);
                } else {
                    $order = new WC_Order($order_id);
                }
                $sezzle_reference_id = $order->get_transaction_id();
                $redirect_url = $this->get_return_url($order);
                if (!$this->isPaymentCaptured($sezzle_reference_id)) {


                    $args = array(
                        'headers' => array(
                            'Authorization' => $this->get_sezzlepay_authorization_code(),
                            'Content-Type' => 'application/json',
                        ),
                        'body' => null,
                        'timeout' => 80,
                        'redirection' => 35
                    );

                    $checkoutCompleteURL = $this->checkoutCompleteUrl($sezzle_reference_id);
                    $response = wp_remote_post($checkoutCompleteURL, $args);
                    $encodeResponseBody = wp_remote_retrieve_body($response);
                    $response_code = wp_remote_retrieve_response_code($response);
                    $this->dump_api_actions($checkoutCompleteURL, $args, $encodeResponseBody, $response_code);
                    if ($response_code == 200) {
                        $order->add_order_note(__('Payment approved by Sezzle successfully.', 'woo_sezzlepay'));
                        $order->payment_complete($sezzle_reference_id);
                        WC()->cart->empty_cart();
                        $redirect_url = $this->get_return_url($order);
                    } else {
                        // get the json body string
                        $body_string = wp_remote_retrieve_body($response);

                        // convert it into a json
                        $body = json_decode($body_string);

                        // if it is not a json
                        if (is_null($body)) {
                            // return a generic error
                            $order->add_order_note(__('The payment failed because of an unknown error. Please contact Sezzle from the Sezzle merchant dashboard.', 'woo_sezzlepay'));
                        } else {
                            // if the body is not valid json
                            if (!isset($body->id)) {
                                // return a generic error
                                $order->add_order_note(__("The payment failed because of an unknown error. Please contact Sezzle from the Sezzle merchant dashboard.", 'woo_sezzlepay'));
                            } else {
                                // show the message received from sezzle
                                $order->add_order_note(__(ucfirst("$body->id : $body->message"), 'woo_sezzlepay'));
                            }
                        }

                        $order->update_status('failed');
                        $redirect_url = wc_get_checkout_url();
                    }
                } else if (!$order->is_paid()) {
                    $order->payment_complete($sezzle_reference_id);
                    WC()->cart->empty_cart();
                    $redirect_url = $this->get_return_url($order);
                }
                wp_redirect($redirect_url);
            }

            public function process_refund($order_id, $amount = null, $reason = '')
            {
                if (function_exists("wc_get_order")) {
                    $order = wc_get_order($order_id);
                } else {
                    $order = new WC_Order($order_id);
                }
                $sezzle_reference_id = $order->get_transaction_id();
                $body = array(
                    'amount' => array(
                        'amount_in_cents' => (int) (round($amount, 2) * 100),
                        'currency' => get_woocommerce_currency(),
                    ),
                );
                $args = array(
                    'headers' => array(
                        'Authorization' => $this->get_sezzlepay_authorization_code(),
                        'Content-Type' => 'application/json',
                    ),
                    'body' => json_encode($body),
                    'timeout' => 80,
                    'redirection' => 35
                );
                $checkoutRefundUrl = $this->checkoutRefundUrl($sezzle_reference_id);
                $response = wp_remote_post($checkoutRefundUrl, $args);
                $encodeResponseBody = wp_remote_retrieve_body($response);
                $response_code = wp_remote_retrieve_response_code($response);
                $this->dump_api_actions($checkoutRefundUrl, $args, $encodeResponseBody, $response_code);

                if ($response_code == 200 || $response_code == 201) {
                    $order->add_order_note(sprintf(__('Refund of %s successfully sent to Sezzle.', 'woo_sezzlepay'), $amount));
                    return true;
                } else {
                    $order->add_order_note(sprintf(__('There was an error submitting the refund to Sezzle.', 'woo_sezzlepay')));
                    return false;
                }
            }

            function get_last_day_orders()
            {
                $yesterday = date('Y-m-d H:i:s', strtotime('-24 hours'));
                $orders = wc_get_orders(
                    array(
                        'type' => 'shop_order',
                        'status' => array('processing', 'completed'),
                        'limit' => -1,
                        'date_after' => "$yesterday"
                    )
                );
                return $orders;
            }

            function get_order_details_from_order($order)
            {
                $details = array();
                $details["order_number"] = $order->get_order_number();
                $details["payment_method"] = $order->get_payment_method();
                $details["amount"] = (int) (round($order->calculate_totals(), 2) * 100);
                $details["currency"] = get_woocommerce_currency();

                // Send the gateway reference too. This may be empty.
                $details["sezzle_reference"] = $order->get_transaction_id();

                // Send customer information
                $details["customer_email"] = $order->get_billing_email();
                $details["customer_phone"] = $order->get_billing_phone();
                $details["billing_address1"] = $order->get_billing_address_1();
                $details["billing_address2"] = $order->get_billing_address_2();
                $details["billing_city"] = $order->get_billing_city();
                $details["billing_state"] = $order->get_billing_state();
                $details["billing_postcode"] = $order->get_billing_postcode();
                $details["billing_country"] = $order->get_billing_country();
                $details["merchant_id"] = $this->get_option('merchant-id');
                return $details;
            }

            function get_order_details_from_orders($orders)
            {
                $orders_details = array();
                foreach ($orders as $order) {
                    $order_details = $this->get_order_details_from_order($order);
                    array_push($orders_details, $order_details);
                }
                return $orders_details;
            }

            function send_merchant_last_day_orders()
            {
                $orders = $this->get_last_day_orders();
                $orders_for_sezzle = $this->get_order_details_from_orders($orders);
                $args = array(
                    'headers' => array(
                        'Authorization' => $this->get_sezzlepay_authorization_code(),
                        'Content-Type' => 'application/json',
                    ),
                    'body' => json_encode($orders_for_sezzle)
                );
                $ordersSubmitUrl = $this->ordersSubmitUrl();
                $response = wp_remote_post($ordersSubmitUrl, $args);
                $encodeResponseBody = wp_remote_retrieve_body($response);
                $response_code = wp_remote_retrieve_response_code($response);
                $this->dump_api_actions($ordersSubmitUrl, $args, $encodeResponseBody, $response_code);

                if ($response_code == 204) {
                    $this->log("Orders sent to Sezzle, Response Code : $response_code");
                } else {
                    $error = print_r($response);
                    $this->log("Could not send orders to Sezzle, Error Response : $error");
                }
            }

            function send_daily_heartbeat()
            {
                $is_payment_active = $this->get_option('enabled') == "yes" ? true : false;
                $is_merchant_id_entered = strlen($this->get_option('merchant-id')) > 0 ? true : false;
                // $is_widget_active = $this->get_option('show-product-page-widget') == "yes" ? true : false;
                $is_widget_configured = strlen(explode('|', $this->get_option('target-path'))[0]) > 0 ? true : false;
                $is_public_key_entered = strlen($this->get_option('public-key')) > 0 ? true : false;
                $is_private_key_entered = strlen($this->get_option('private-key')) > 0 ? true : false;

                $data = array(
                    'is_payment_active' => $is_payment_active,
                    'active_mode' => $this->get_option('mode'),
                    'is_widget_active' => $is_widget_active,
                    'is_widget_configured' => $is_widget_configured,
                    'is_merchant_id_entered' => $is_merchant_id_entered,
                    'merchant_id' => $this->get_option('merchant-id'),
                );

                if ($is_public_key_entered && $is_private_key_entered && $is_merchant_id_entered) {
                    // send data
                    $args = array(
                        'headers' => array(
                            'Authorization' => $this->get_sezzlepay_authorization_code(),
                            'Content-Type' => 'application/json',
                        ),
                        'body' => json_encode($data)
                    );
                    $heartbeatUrl = $this->heartbeatUrl();
                    $response = wp_remote_post($heartbeatUrl, $args);
                    $encodeResponseBody = wp_remote_retrieve_body($response);
                    $response_code = wp_remote_retrieve_response_code($response);
                    $this->dump_api_actions($heartbeatUrl, $args, $encodeResponseBody, $response_code);

                    if ($response_code == 204) {
                        $this->log("Heartbeat sent to Sezzle, Response Code : $response_code");
                    } else {
                        $error = print_r($response);
                        $this->log("Could not send Heartbeat to Sezzle, Error Response : $error");
                    }
                } else {
                    $this->log("Could not send Heartbeat to Sezzle. Please set api keys.");
                }
            }
        }

        function add_sezzlepay_gateway($methods)
        {
            $methods[] = 'WC_Gateway_Sezzlepay';
            return $methods;
        }

        function remove_sezzlepay_gateway_based_on_billing_country($available_gateways)
        {
            global $woocommerce;
            $gateway = WC_Gateway_Sezzlepay::instance();
            $enableSezzlepayOutsideIN = $gateway->get_option('payment-option-availability') == 'yes' ? true : false;
            if (!$enableSezzlepayOutsideIN && $woocommerce->customer) {
                $countryCode = $woocommerce->customer->get_billing_country();
                $allowedCountryCodes = array('IN');
                if (!in_array($countryCode, $allowedCountryCodes, true)) {
                    unset($available_gateways['sezzlepay']);
                }
            }
            return $available_gateways;
        }

        /**
         * Remove Sezzle Pay based on checkout total
         *
         * @return array
         */
        function remove_sezzlepay_gateway_based_on_checkout_total($available_gateways)
        {
            global $woocommerce;
            $cart_total = $woocommerce->cart->total;
            $gateway = WC_Gateway_Sezzlepay::instance();
            $min_checkout_amount = $gateway->get_option('min-checkout-amount');
            if ($cart_total && $min_checkout_amount && ($cart_total < $min_checkout_amount)) {
                unset($available_gateways['sezzlepay']);
            }
            return $available_gateways;
        }

        add_filter('woocommerce_payment_gateways', 'add_sezzlepay_gateway');

        add_filter('woocommerce_available_payment_gateways', 'remove_sezzlepay_gateway_based_on_checkout_total');

        add_filter('woocommerce_available_payment_gateways', 'remove_sezzlepay_gateway_based_on_billing_country');

        add_action('woocommerce_single_product_summary', 'add_sezzle_product_banner');

        function add_sezzle_product_banner()
        {
            $gateway = WC_Gateway_Sezzlepay::instance();
            // $showWidget = $gateway->get_option('show-product-page-widget');
            // if ($showWidget == 'no') {
            //     return;
            // }
            $merchantID = $gateway->get_option('merchant-id');
            echo "
                <script type='text/javascript'>
                    Sezzle = {}
                    Sezzle.render = function () {
                        var script = document.createElement('script');
                        script.type = 'text/javascript';
                        script.src = 'https://widget.sezzle.in/v1/javascript/price-widget?uuid=$merchantID';
                        document.head.appendChild(script);
                    };
                    Sezzle.render();
                </script>
            ";
        }

        function sezzle_daily_data_send_event()
        {
            $gateway = WC_Gateway_Sezzlepay::instance();
            $gateway->send_daily_heartbeat();
            $gateway->send_merchant_last_day_orders();
        }

        add_action('sezzle_daily_data_send_event', 'sezzle_daily_data_send_event');
    }
}

// Activation hook - called when plugin is activated
register_activation_hook(__FILE__, 'sezzle_activated');
function sezzle_activated()
{
    // Schedule cron
    if (!wp_next_scheduled('sezzle_daily_data_send_event')) {
        wp_schedule_event(time(), 'daily', 'sezzle_daily_data_send_event');
    }
}

// Deactivation hook - called when plugin is deactivated
register_deactivation_hook(__FILE__, 'sezzle_deactivated');
function sezzle_deactivated()
{
    // Deactivate the cron
    wp_clear_scheduled_hook('sezzle_daily_data_send_event');
}
