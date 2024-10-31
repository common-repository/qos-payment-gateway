<?php
// Include the payment callback file

/**
 * Plugin Name: QOSPAY
 * Description: QOSPAY allows businesses to securely receive online payments.
 * Author: QoS Developer Team
 * Version: 2.0.0
 * Requires at least: 4.4
 * Tested up to: 6.2
 * Stable tag: 2.1.8
 * Requires PHP: 5.6
 * Author URI: https://www.qosic.com
 */
if (!defined('ABSPATH')) {
    exit;
}

// $plugin_name = 'woocommerce/woocommerce.php';

// // Check if the plugin is not already active
// if (!is_plugin_active($plugin_name)) {
//     // Include the necessary plugin administration file
//     include_once ABSPATH . 'custom-api.php';
    
//     // Activate the plugin
//     activate_plugin($plugin_name);
// }

/*
* This action hook registers our PHP class as a WooCommerce payment gateway
*/
add_filter('woocommerce_payment_gateways', 'qos_add_gateway_class');
function qos_add_gateway_class($gateways)
{
    $gateways[] = 'WC_Qos_Gateway'; // your class name is here
    return $gateways;
}

/*
* The class itself, please note that it is inside plugins_loaded action hook
*/
add_action('plugins_loaded', 'misha_init_gateway_class');
/**
 * Summary of misha_init_gateway_class
 * @return void
 */
function misha_init_gateway_class()
{

    $qosKey = null;

    /**
     * Summary of WC_Qos_Gateway
     */
    class WC_Qos_Gateway extends WC_Payment_Gateway
    {
        
        /**
         * @var bool
         */
        private $testmode;
        /**
         * @var string
         */
        private $checkoutURL;

         /**
         * @var string
         */
        private $gettransactionstatus;

        /**
         * @var string
         */
        private $qosKey;

        /**
         * Summary of __construct
         */
        public function __construct()
        {
            $this->id = 'qos_woocommerce_gateway'; // payment gateway plugin ID
            $this->icon = plugins_url('assets/img/logo.PNG', __FILE__); // URL of the icon that will be displayed on checkout page near your gateway name
            $this->has_fields = false; // in case you need a custom credit card form
            $this->method_title = 'QOSPAY ';
            $this->title = 'QOSPAY';
            $this->method_description = 'QoS Gateway  allows businesses to safely receive payments by mobile money'; // will be displayed on the options page
            $this->supports = array('products');
            // Method with all the options fields
            $this->init_form_fields();
            // Load the settings.
            $this->init_settings();

            $this->enabled = $this->get_option('enabled');
            $this->testmode = 'no' === $this->get_option('testmode');
            $this->qosKey = $this->testmode ? "QCBJ137" : $this->get_option('production_qos_key');
            $this->checkoutURL = $this->testmode ? "http://74.208.84.251:9014/public/v1/initTransaction" : "https://b-card.qosic.net/public/v1/initTransaction";
            $this->gettransactionstatus = 'https://api.qosic.net/QosicBridge/checkout/v1/status';


            $this->init_form_fields();
            // Load the settings.
            $this->init_settings();
            $this->qospay_config = array();

            add_action('admin_notices', array($this, 'do_ssl_check'));
            // This action hook saves the settings
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));
            add_action('admin_notices', array($this, 'enable_check'));
            add_action('admin_notices', array($this, 'currency_check'));
            add_action('woocommerce_thankyou', 'check_transaction_status');
            add_action('woocommerce_thankyou', 'check_status_with_return_url');
            add_action('woocommerce_thankyou', 'custom_thankyou_page_redirect', 5, 1);
            add_action('woocommerce_receipt_qos_payment_gateway', 'process_payment');
            add_action( 'woocommerce_api_payment_complete', 'my_payment_complete_callback' );

   
            }

        public function init_form_fields()
        {
            $this->form_fields = include plugin_dir_path(__DIR__) . DIRECTORY_SEPARATOR . 'qos-payment-gateway' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'qospay-settings.php';

            // $this->form_fields = include plugin_dir_path(__DIR__) . '\qos-payment-gateway\admin\qospay-settings.php';
        }


        /**
         * To make sure that the currency used one the store
         * is the one we actually support
         */
        public function currency_check()
        {
            $currency = get_woocommerce_currency();
            if ($currency != 'XOF') {
                echo "<div class=\"error\"><p>" . sprintf(__('<strong>%s</strong> does not support the currency you are currently using. Please set the currency of your shop on XOF (FCFA) <a href="%s">here.</a>')), "</p></div>";
            }
        }

        /**
         * You will need it if you want your custom credit card form, Step 4 is about it
         */
        public function payment_fields()
        {
        }

        /*
        * Custom CSS and JS, in most cases required only when you decided to go with a custom credit card form
        */
        public function payment_scripts()
        {
        }

        /*
        * Fields validation, more in Step 5
        */
        public function validate_fields()
        {
        }



        // function add_custom_button_to_checkout()
        // {
        // }


        /**
         * Summary of process_payment
         * @param mixed $order_id
         * @return array
         */
        function process_payment($order_id)
        {
            global $woocommerce;
            if (!is_cart() && !is_checkout() && !isset($_GET['pay_for_order'])) {
                return;
            }
            if ('no' === $this->enabled) {
                return;
            }
            $time = new DateTime;
            $transref = $time->getTimestamp();
            $order = wc_get_order($order_id);
            $order_total = $order->get_total();
            $endpoint_url = $this->checkoutURL;
            $callback_url = home_url('/wp-json/qosapi/v1/checkout-callback/'. $order_id);

            echo $callback_url;

            $data = array(
                "type" => "all",
                "transref" => $transref,
                "qosKey" => $this->qosKey,
                "returnUrl" => $callback_url,
                "amountDetails" => array(
                    "totalAmount" => intval($order_total),
                    "currency" => "XOF"
                ),
                "saleDetails" => array(
                    "firstName" => $order->get_billing_first_name(),
                    "lastName" => $order->get_billing_last_name(),
                    "middleName" => "string",
                    "nameSuffix" => "string",
                    "title" => "string",
                    "address1" => $order->get_billing_address_1(),
                    "address2" => $order->get_billing_address_2(),
                    "address4" => "string",
                    "locality" => $order->get_billing_city(),
                    "administrativeArea" => $order->get_billing_state(),
                    "postalCode" => $order->get_billing_postcode(),
                    "country" => $order->get_billing_country(),
                    "district" => "string",
                    "buildingNumber" => "string",
                    "email" => $order->get_billing_email(),
                    "emailDomain" => "string",
                    "phoneNumber" => $order->get_billing_phone(),
                    "phoneType" => "cel"
                )
            );
            $json_data = json_encode($data);
            $headers = array(
                'Content-Type' => 'application/json',
                "Access-Control-Allow-Origin" => "*",
                "access-control-allow-credentials" => TRUE,
            );
            $response = wp_remote_post($endpoint_url, array(
                'method' => 'POST',
                'timeout' => 45,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking' => true,
                'sslverify' => false,
                'headers' => $headers,
                'body'    => $json_data,
                'cookies' => array()
            ));

            if (is_wp_error($response)) {
                $error_message = $response->get_error_message();
                echo "Something went wrong: $error_message";
                return array($response);
            }
            $response_body = json_decode(wp_remote_retrieve_body($response), true);
            $response_code = wp_remote_retrieve_response_code($response);
                if ($response_code == 200 && !empty($response_body['url'])) {  
                    // echo $callback_url;
                    $response = $this->check_transaction_status($transref, $this->qosKey);
                    $data = $response['data'];
                    $currentUrl = home_url('/wp-json/qosapi/v1/checkout-callback/'. $order_id);
                    // parse_str(parse_url($currentUrl, PHP_URL_QUERY), $params);
                    // print_r($params);
                    if (!$currentUrl ) {
                    if ($response['status'] == '00' && $data['status'] == 'SUCCESS') {
                    //     // Transaction was successful. Proceed with further processing.
                        $order->update_status('on-hold', true);
                        $order->add_order_note(__('Payment is still pending on Qos Payment', 'qos-woocommerce'));
                        $order->add_order_note('Qos Payment transaction Id: ' . $transref);
                        $order->add_order_note(__('Nom: '.$order->get_billing_first_name(). '<br>Prénom: '.$order->get_billing_last_name() .'<br>Numero: '.$order->get_billing_phone() .'<br>Email: '.$order->get_billing_email(). 'qos-woocommerce'));
                        $customer_note = __('Your payment was successful, we are now <strong>processing</strong> your order.', 'qos-woocommerce');
                        $order->add_order_note($customer_note, 1);
                        wc_add_notice($customer_note, 'success');
                        $woocommerce->cart->empty_cart();
                        
                     } 
                     elseif ($response['status'] == '00' && $data['status'] == 'PENDIND') {
                    //     // Transaction is still pending. Notify user and/or administrator.
                        $order->update_status('processing', true);
                        $order->add_order_note(__('Payment is still pending on Qos Payment', 'qos-woocommerce'));
                        $order->add_order_note('Qos Payment transaction Id: ' . $transref);
                        $order->add_order_note(__('Nom: '.$order->get_billing_first_name(). '<br>Prénom: '.$order->get_billing_last_name() .'<br>Numero: '.$order->get_billing_phone() .'<br>Email: '.$order->get_billing_email(). 'qos-woocommerce'));
                        $customer_note = __('Thank you for your order.<br>', 'qos-woocommerce');
                        $customer_note .= __('Your payment was successful, we are now <strong>processing</strong> your order.', 'qos-woocommerce');
                        $order->add_order_note($customer_note, 1);
                        wc_add_notice($customer_note, 'success');
                        $woocommerce->cart->empty_cart();
                      } else {
                        wc_add_notice('TIME OUT, PLEASE TRY AGAIN', 'error');
                        $order->add_order_note(__('The order payment failed on qospay', 'qospay-woocommerce'));
                        $order->add_order_note('Qos Payment transaction Id: ' . $transref);
                        $customer_note = __('Your payment <strong>failed</strong>. ', 'qospay-woocommerce');
                        $customer_note .= __('Please, try funding your account.', 'qospay-woocommerce');
                        $order->add_order_note($customer_note, 1);
                        wc_add_notice($customer_note, 'notice');
                      }
                   
                }  
            }

                $redirect_url = $response_body['url'];
                    return array(
                        'result' => 'success',
                        'redirect' => $redirect_url,
                    );
            }
   
       
    public function check_transaction_status($transref, $qosKey)
        {
            $rest_getstatut_url = $this->gettransactionstatus;
                $headers = array(
                    'Content-Type' => 'application/json',
                );
                $data = array(
                    'transref' => $transref,
                    'qosKey' => $qosKey
                );
                $response = wp_remote_post($rest_getstatut_url, array(
                    'method' => 'POST',
                    'timeout' => 45,
                    'redirection' => 5,
                    'httpversion' => '1.0',
                    'blocking' => true,
                    'sslverify' => false,
                    'headers' => $headers,
                    'body' => json_encode($data),
                    'cookies' => array(),
                ));

                $response_body = wp_remote_retrieve_body($response);
                $body = json_decode($response_body, true);
                return ($body);
        }




        public function webhook()
        {
        }
        // Check if we are forcing SSL on checkout pages
        public function do_ssl_check()
        {
            if ($this->enabled == "yes") {
                if (get_option('woocommerce_force_ssl_checkout') == "no") {
                    echo "<div class=\"error\"><p>" . sprintf(__("<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>", 'qos-woocommerce'), $this->method_title, admin_url('admin.php?page=wc-settings&tab=checkout')) . "</p></div>";
                }
            }
        }

        // Check if enable
        public function enable_check()
        {
            if ($this->enabled == "no") {
                echo '<div class="error"><p><strong>' . __('QoS Gateway is Disabled', 'qospay-woocommerce') . '</strong></p></div>';
                // echo "<div class=\"error\"><p>" . sprintf(__("<strong>QoS Gateway is Disabled</strong>", 'qos-woocommerce'), $this->method_title, admin_url('admin.php?page=wc-settings&tab=checkout')) . "</p></div>";
            }
        }
    }
}
