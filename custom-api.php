<?php
// Include the payment callback file

/**
 * Plugin Name: QOSPAY Callback URL
 * Description: QOSPAY Callback URL plugin that enables you to receive the callback response from the checkout.
 * Author: QOS Developer Team
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

// Ensure WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}

// Load WC
if (!class_exists('WooCommerce')) {
    include_once ABSPATH . 'wp-content/plugins/woocommerce/woocommerce.php';
}


// Register the REST API endpoint for the checkout callback
add_action( 'rest_api_init', function () {
    register_rest_route( 'qosapi/v1', 'checkout-callback/(?P<order_id>\d+)', array(
      'methods' => 'GET',
      'callback' => 'update_order_status_callback',
      'permission_callback' => '__return_true',
    ) );
  } );
  

// Define the callback function to update the order status
function update_order_status_callback( $request) {
    $status = $_GET['status'];
    $transref = $_GET['transref'];
    $order_id = absint($request->get_param('order_id'));
    global $woocommerce;

    // Check if $order_id is valid
    if (!$order_id) {
        // Handle invalid order ID error
        exit("Invalid order ID.");
    }

    // Update the order status based on the status parameter in the callback URL
    if ($status === "SUCCESS") {
        // Update order status to "completed"
        echo $status . " " . $transref, ' it succeed here';
        $order = wc_get_order($order_id);
        $order->update_status('completed', true);
        $order->add_order_note(__('Payment is still pending on Qos Payment', 'qos-woocommerce'));
        $order->add_order_note('Qos Payment transaction Id: ' . $transref);
        $order->add_order_note(__('Nom: '.$order->get_billing_first_name(). '<br>Prénom: '.$order->get_billing_last_name() .'<br>Numero: '.$order->get_billing_phone() .'<br>Email: '.$order->get_billing_email(). 'qos-woocommerce'));
        $customer_note = __('Your payment was successful, we are now <strong>processing</strong> your order.', 'qos-woocommerce');
        $order->add_order_note($customer_note, 1);
    } else {
        // Update order status to "failed"
        echo $status . " " . $transref, ' it failed here';
        $order = wc_get_order($order_id);
        $order->update_status('failed', true);
        $order->add_order_note(__('The order payment failed on qospay', 'qospay-woocommerce'));
        $order->add_order_note('Qos Payment transaction Id: ' . $transref);
        $customer_note = __('Your payment <strong>failed</strong>. ', 'qospay-woocommerce');
        $customer_note .= __('Please, try funding your account.', 'qospay-woocommerce');
        $order->add_order_note($customer_note, 1);
    }
        // Redirect the customer to the view order page
    $redirect_url = get_permalink(get_option('woocommerce_myaccount_page_id')) . 'view-order/' . $order_id;
    wp_redirect($redirect_url);
    exit;
}
