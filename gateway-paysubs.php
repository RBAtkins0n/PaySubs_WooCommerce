<?php
/**
 * Plugin Name: PayGate PaySubs plugin for WooCommerce
 * Plugin URI: https://github.com/PayGate/PaySubs_WooCommerce
 * Description: Accept payments for WooCommerce using PayGate's PaySubs service
 * Version: 1.0.5
 * Author: PayGate (Pty) Ltd
 * Author URI: https://www.paygate.co.za/
 * Developer: App Inlet (Pty) Ltd
 * Developer URI: https://www.appinlet.com/
 *
 * WC requires at least: 3.0
 * WC tested up to: 4.7
 *
 * Copyright: © 2020 PayGate (Pty) Ltd.
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
    if ( !class_exists( 'WC_Payment_Gateway' ) ) {
        return;
    }
    require_once plugin_basename( 'classes/paysubs.class.php' );
    add_filter( 'woocommerce_payment_gateways', 'woocommerce_paysubs_add_gateway' );

    require_once 'classes/updater.class.php';

    if ( is_admin() ) {
        // note the use of is_admin() to double check that this is happening in the admin
        $config = [
            'slug'               => plugin_basename( __FILE__ ),
            'proper_folder_name' => 'woocommerce-gateway-paysubs',
            'api_url'            => 'https://api.github.com/repos/PayGate/PaySubs_WooCommerce',
            'raw_url'            => 'https://raw.githubusercontent.com/PayGate/PaySubs_WooCommerce/master',
            'github_url'         => 'https://github.com/PayGate/PaySubs_WooCommerce',
            'zip_url'            => 'https://github.com/PayGate/PaySubs_WooCommerce/archive/master.zip',
            'homepage'           => 'https://github.com/PayGate/PaySubs_WooCommerce',
            'sslverify'          => true,
            'requires'           => '4.0',
            'tested'             => '5.5.3',
            'readme'             => 'README.md',
            'access_token'       => '',
        ];

        new WP_GitHub_Updater_PS1( $config );

    }
} // End woocommerce_paysubs_init()

function woocommerce_paysubs_add_gateway( $methods )
{
    $methods[] = 'WC_Gateway_PaySubs';
    return $methods;
} // End woocommerce_paysubs_add_gateway()
