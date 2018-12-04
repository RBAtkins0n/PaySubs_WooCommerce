<?php
/**
 * Plugin Name: PayGate PaySubs plugin for WooCommerce
 * Plugin URI: https://www.paygate.co.za
 * Description: Accept payments for WooCommerce using PayGate's PaySubs service
 * Version: 1.0.2
 * Author: PayGate (Pty) Ltd
 * Author URI: https://www.paygate.co.za/
 * Developer: App Inlet (Pty) Ltd
 * Developer URI: https://www.appinlet.com/
 *
 * WC requires at least: 2.6
 * WC tested up to: 3.3
 *
 * Copyright: © 2018 PayGate (Pty) Ltd.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

/**
 * Required functions
 */
if ( !function_exists( 'woothemes_queue_update' ) ) {
    require_once 'woo-includes/woo-functions.php';
}

add_action( 'plugins_loaded', 'woocommerce_paysubs_init', 0 );

function woocommerce_paysubs_init()
{
    if ( !class_exists( 'WC_Payment_Gateway' ) ) {return;}
    require_once plugin_basename( 'classes/paysubs.class.php' );
    add_filter( 'woocommerce_payment_gateways', 'woocommerce_paysubs_add_gateway' );
} // End woocommerce_paysubs_init()

function woocommerce_paysubs_add_gateway( $methods )
{
    $methods[] = 'WC_Gateway_PaySubs';return $methods;
} // End woocommerce_paysubs_add_gateway()
