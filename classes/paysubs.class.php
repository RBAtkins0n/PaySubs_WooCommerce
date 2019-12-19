<?php
/*
 * Copyright (c) 2019 PayGate (Pty) Ltd
 *
 * Author: App Inlet (Pty) Ltd
 *
 * Released under the GNU General Public License
 *
 * PaySubs Payment Gateway
 *
 * Provides a PaySubs Payment Gateway
 *
 * @class       woocommerce_gateway_paysubs
 * @package     WooCommerce
 * @category    Payment Gateways
 * @author      PaySubs
 */
class WC_Gateway_PaySubs extends WC_Payment_Gateway
{

    public function __construct()
    {
        global $woocommerce;

        $this->id                 = 'paysubs';
        $this->icon               = apply_filters( 'woocommerce_paysubs_icon', $this->plugin_url() . '/assets/images/icon.png' );
        $this->method_description = sprintf( __( 'This sends the user to %sPayGate%s to enter their payment information.', 'woocommerce_gateway_paysubs' ), '<a href="https://www.paygate.co.za/">', '</a>' );
        $this->has_fields         = true;
        $this->method_title       = "PayGate via PaySubs";
        $this->debug_email        = get_option( 'admin_email' );
        $this->wc_version         = get_option( 'woocommerce_db_version' );
        $this->url                = 'https://www.vcs.co.za/vvonline/vcspay.aspx';

        // Load the form fields
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();

        // Get setting values
        $this->title       = $this->settings['title'];
        $this->description = $this->settings['description'];

        // Hooks
        add_action( 'woocommerce_receipt_paysubs', array( &$this, 'receipt_page' ) );

        $this->notify_url = home_url( '/' );

        if ( $this->enabled == 'yes' ) {
            add_action( 'init', array( &$this, 'response_handler' ) );
        }

        if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '<' ) ) {
            add_action( 'init', array( $this, 'response_handler' ) );
            $this->callback_url = home_url( '/' );
        } else {
            add_action( 'woocommerce_api_wc_gateway_paysubs', array( $this, 'response_handler' ) );
            $this->callback_url = $woocommerce->api_request_url( get_class( $this ) );
            $this->notify_url   = add_query_arg( 'wc-api', 'WC_Gateway_PaySubs', home_url( '/' ) );
        }
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'valid-paysubs-response', array( $this, 'successful_request' ) );
    }

    public function init_form_fields()
    {

        $this->form_fields = array(
            'enabled'     => array(
                'title'       => __( 'Enable/Disable', 'woocommerce_gateway_paysubs' ),
                'label'       => __( 'Enable PaySubs Payment Gateway', 'woocommerce_gateway_paysubs' ),
                'type'        => 'checkbox',
                'description' => 'Whether this gateway is enabled in WooCommerce or not.',
                'default'     => 'yes',
            ),

            'title'       => array(
                'title'       => __( 'Title', 'woocommerce_gateway_paysubs' ),
                'type'        => 'text',
                'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce_gateway_paysubs' ),
                'default'     => __( 'PayGate via PaySubs', 'woocommerce_gateway_paysubs' ),
                'css'         => "width: 300px;",
            ),
            'description' => array(
                'title'       => __( 'Description', 'woocommerce_gateway_paysubs' ),
                'type'        => 'textarea',
                'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce_gateway_paysubs' ),
                'default'     => __( 'Pay via PayGate', 'woocommerce_gateway_paysubs' ),
            ),
            'terminal_id' => array(
                'title'       => __( 'Terminal ID', 'woocommerce_gateway_paysubs' ),
                'type'        => 'text',
                'description' => __( 'This is the terminal ID, received from PayGate.', 'woocommerce_gateway_paysubs' ),
                'default'     => '',
            ),
            'md5'         => array(
                'title'       => __( 'MD5Hash', 'woocommerce_gateway_paysubs' ),
                'label'       => __( 'Use MD5Hash', 'woocommerce_gateway_paysubs' ),
                'type'        => 'checkbox',
                'description' => 'Whether this gateway uses MD5 hash or not.',
                'default'     => 'yes',
            ),
            'recurring'   => array(
                'title'       => __( 'Recurring', 'woocommerce_gateway_paysubs' ),
                'label'       => __( 'Enable recurring', 'woocommerce_gateway_paysubs' ),
                'type'        => 'checkbox',
                'description' => 'Whether this gateway has recurring enabled.',
                'default'     => 'yes',
            ),
            'frequency'   => array(
                'title'       => __( 'Payment Frequency', 'woocommerce_gateway_paysubs' ),
                'label'       => __( 'Choose Payment Frequency', 'woocommerce_gateway_paysubs' ),
                'type'        => 'select',
                'description' => 'Choose you desired payment frequency (recurring must be enabled).',
                'default'     => 'M',
                'options'     => array(
                    'D' => 'Daily',
                    'W' => 'Weekly',
                    'M' => 'Monthly',
                    'Q' => 'Quarterly',
                    '6' => 'Bi-annually',
                    'Y' => 'Yearly',
                ),
            ),
            'loggingmode' => array(
                'title'       => __( 'Logging', 'woocommerce_gateway_paysubs' ),
                'label'       => __( 'Enable logging', 'woocommerce_gateway_paysubs' ),
                'type'        => 'checkbox',
                'description' => 'Whether this gateway has logging enabled.',
                'default'     => 'no',
            ),
            'md5key'      => array(
                'title'       => __( 'MD5 Key', 'woocommerce_gateway_paysubs' ),
                'type'        => 'text',
                'description' => __( 'This is the MD5 Key that PaySubs will use to hash the transaction parameters, this must be sent to support@paygate.co.za to enable MD5 hash functionality (MD5Hash must be enabled).', 'woocommerce_gateway_paysubs' ),
                'default'     => 'secret',
            ),
            'order_text'  => array(
                'title'       => __( 'Order Button Text', 'woocommerce_gateway_paysubs' ),
                'type'        => 'text',
                'description' => __( 'What text should appear on the order button', 'woocommerce_gateway_paysubs' ),
                'default'     => 'Proceed to PayGate',
            ),
        );

    } // End init_form_fields()

    /**
     * Get the plugin URL
     *
     * @since 1.0.0
     */
    public function plugin_url()
    {
        if ( isset( $this->plugin_url ) ) {
            return $this->plugin_url;
        }

        if ( is_ssl() ) {
            return $this->plugin_url = str_replace( 'http://', 'https://', WP_PLUGIN_URL ) . "/" . plugin_basename( dirname( dirname( __FILE__ ) ) );
        } else {
            return $this->plugin_url = WP_PLUGIN_URL . "/" . plugin_basename( dirname( dirname( __FILE__ ) ) );
        }
    } // End plugin_url()

    /**
     * URL gateway
     *
     */
    public function get_gateway_url()
    {
        return $this->url;
    }

    /**
     * Admin Panel Options
     *
     * @since 1.0.0
     */
    public function admin_options()
    {
        // Make sure to empty the log file if not in test mode.
        if ( $this->settings['loggingmode'] != 'yes' ) {
            $this->log( '' );
            $this->log( '', true );
        }
        ?>
        <h3><?php _e( 'PayGate via PaySubs', 'woocommerce_gateway_paysubs' );?></h3>
        <p><?php printf( __( 'PayGate works by sending the user to %sPayGate%s to enter their payment information.', 'woocommerce_gateway_paysubs' ), '<a href="https://www.paygate.co.za/">', '</a>' );?></p>
        <table class="form-table"><?php
// Generate the HTML For the settings form.
        $this->generate_settings_html();
        ?>
            <tr valign="top">
                <td colspan="2">

                </td>
            </tr>
        </table><!--/.form-table-->
        <?php
} // End admin_options()

    /**
     * There are no payment fields for PaySubs, but we want to show the description if set.
     *
     * @since 1.0.0
     */
    public function payment_fields()
    {
        if ( isset( $this->settings['description'] ) && ( '' != $this->settings['description'] ) ) {
            echo wpautop( wptexturize( $this->settings['description'] ) );
        }
    } // End payment_fields()

    /**
     * Generate the PayGate button link.
     *
     * @since 1.0.0
     */
    public function generate_paysubs_form( $order_id )
    {
        global $woocommerce;

        $order = new WC_Order( $order_id );

        $fields = $this->prepare_form_fields( $order );

        $paysubs_args_array = array();

        foreach ( $fields as $key => $value ) {
            $paysubs_args_array[] = '<input type="hidden" name="' . $key . '" value="' . $value . '" />';
        }

        return '<form action="' . $this->url . '" method="post" id="paysubs_payment_form">
                ' . implode( '', $paysubs_args_array ) . ' <input type="submit" class="button button-alt" id="submit_paysubs_payment_form" value="' . $this->settings['order_text'] . '" /> <a class="button cancel" href="' . $order->get_cancel_order_url() . '">' . __( 'Cancel order &amp; restore cart', 'wc_paysubs' ) . '</a>
                <script type="text/javascript">
                    jQuery(function(){
                        jQuery("body").unblock(
                            {
                                message: "<img src=\"' . $woocommerce->plugin_url() . '/assets/images/ajax-loader.gif\" alt=\"Redirecting...\" />' . __( 'Thank you for your order. We are now redirecting you to PayGate to make payment.', 'wc_paysubs' ) . '",
                                overlayCSS:
                                {
                                    background: "#fff",
                                    opacity: 0.6
                                },
                                css: {
                                    padding:        20,
                                    textAlign:      "center",
                                    color:          "#555",
                                    border:         "3px solid #aaa",
                                    backgroundColor:"#fff",
                                    cursor:         "wait"
                                }
                            });
                    });
                </script>
            </form>';

        $order->add_order_note( __( 'Customer was redirected to PaySubs.', 'wc_paysubs' ) );
    } // End generate_paysubs_form()

    /**
     * Process the payment and return the result.
     *
     * @since 1.0.0
     */
    public function process_payment( $order_id )
    {
        global $woocommerce;
        $order = wc_get_order( $order_id );
        return array(
            'result'   => 'success',
            'redirect' => $order->get_checkout_payment_url( true ),
        );
    }

    /**
     * Receipt page.
     *
     * Display text and a button to direct the user to the payment screen.
     *
     * @since 1.0.0
     */
    public function receipt_page( $order )
    {
        global $woocommerce;
        $r = $this->generate_paysubs_form( $order );
        echo $r;
        echo '<script type="text/javascript">
                    jQuery(function(){
                        jQuery("body").block(
                            {
                                message: "' . __( 'Thank you for your order. We are now redirecting you to PayGate to make payment.', 'wc_paysubs' ) . '",
                                overlayCSS:
                                {
                                    background: "#fff",
                                    opacity: 0.6
                                },
                                css: {
                                    padding:        20,
                                    textAlign:      "center",
                                    color:          "#555",
                                    border:         "3px solid #aaa",
                                    backgroundColor:"#fff",
                                    cursor:         "wait"
                                }
                            });
                        jQuery( "#submit_paysubs_payment_form" ).click();
                    });
                </script>';
    }

    /**
     * prepare_form_fields()
     *
     * Prepare the fields to be submitted to PayGate.
     *
     * @param object $order
     * @return array
     */
    public function prepare_form_fields( $order )
    {
        global $woocommerce;

        if(!session_id()) {
            session_start();
        }

        $amount       = ( WC()->version < '2.7.0' ) ? $order->order_total : $order->get_total();
        $currency     = get_option( 'woocommerce_currency' );
        $order_id     = ( WC()->version < '2.7.0' ) ? $order->id : $order->get_id();
        $_SESSION['orderID'] = $order_id;
        $order_number = trim( str_replace( '#', '', $order->get_order_number() ) );
        $order_email  = ( WC()->version < '2.7.0' ) ? $order->billing_email : $order->get_billing_email();
        $order_key    = ( WC()->version < '2.7.0' ) ? $order->order_key : $order->get_order_key();
        $_SESSION['order_key'] = $order_key;

        if ( $this->has_previously_failed( $order ) ) {
            if ( $post_count = get_post_meta( $order_id, '_woocommerce_gateway_paysubs_failed_attempts', true ) ) {
                $order_id = $order_id . '-' . $post_count;
            } else {
                $order_id = $order_id . '-1';

                update_post_meta( $order_id, '_woocommerce_gateway_paysubs_failed_attempts', 1 );
            }
        }

        $mytheme_timezone = get_option( 'timezone_string' );
        date_default_timezone_set( $mytheme_timezone );

        $params = array(
            'p1'           => $this->settings['terminal_id'],
            'p2'           => $order_number . ' ' . date( "h:i:s" ),
            'p3'           => sprintf( __( '%s purchase, Order # %d', 'woocommerce_gateway_paysubs' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), $order_number ),
            'p4'           => $amount,
            'p5'           => $currency,
            'p10'          => htmlspecialchars_decode( urldecode( $order->get_cancel_order_url() ) ),
            'm_3'          => $order_id,
            'URLsProvided' => 'Y',
            'ApprovedURL'  => $this->callback_url,
            'DeclinedURL'  => $this->callback_url,
            'm_2'          => $order_key,
        );

        if ( 'yes' === $this->settings['recurring'] ) {
            $params['p6'] = 'U'; // repeats
            $params['p7'] = $this->settings['frequency']; // interval
        }

        $hash           = $this->getPaySubsHash( $params );
        $params['hash'] = $hash;

        return $params;
    }

    /**
     * Calculate the MD5 hash for the order form fields
     *
     * @param array $params
     * @return string
     */
    public function getPaySubsHash( $params )
    {
        if ( $this->settings['md5'] == 'yes' ) {
            $hash = $params['p1'];
            $hash .= $params['p2'];
            $hash .= $params['p3'];
            $hash .= $params['p4'];
            $hash .= $params['p5'];
            if ( 'yes' === $this->settings['recurring'] ) {
                $hash .= $params['p6'];
                $hash .= $params['p7'];
            }
            $hash .= $params['p10'];
            $hash .= $params['m_2'];
            $hash .= $params['m_3'] . $this->settings['md5key'];
            $hash = md5( $hash );
            return $hash;
        }
        return "";
    }

    /**
     * Check if order has previously failed
     *
     * @param object $order
     * @return bool
     */
    public function has_previously_failed( $order )
    {
        if ( 'failed' === $order->get_status() ) {
            return true;
        }
        return false;
    }

    /**
     * Response Handler
     */
    public function response_handler()
    {
        global $woocommerce;

        // Clean
        @ob_clean();

        // Header
        header( 'HTTP/1.1 200 OK' );
        if ( !empty( $_POST ) && isset( $_POST['p2'] ) ) {
            $_POST = stripslashes_deep( $_POST );

            if ( $this->perform_response_callback( $_POST ) ) {
                do_action( 'valid-paysubs-response', sanitize_text_field( $_POST['m_3'] ) );
            }
        } else {
            if(!session_id()) {
                session_start();
            }
            // check transaction status
            $orderID = $_SESSION['orderID'];

            $order     = new WC_Order( $orderID );

            $error_message = 'Transaction was not successful.';
            $this->log( $error_message );

            $order->update_status( 'failed', 'Payment failed via PaySubs. Empty POST.' );
            wp_redirect( $this->get_return_url( $order ) );
            exit;
        }
    }

    /**
     * Check PaySubs response validity.
     *
     * @param array $data
     * @since 1.0.0
     */
    public function perform_response_callback( $data )
    {
        global $woocommerce;

        $has_error     = false;
        $error_message = '';
        $is_done       = false;
        $orderID       = explode( '-', $data['m_3'] );

        $order_key = esc_attr( $data['m_2'] );
        $order     = new WC_Order( $orderID[0] );

        $data_string = '';

        $this->log( "\n" . '----------' . "\n" . 'PaySubs response received' );

        // check transaction status
        if ( !empty( $data['p3'] ) && substr( $data['p3'], 6, 8 ) != 'APPROVED' && !$has_error && !$is_done ) {
            $has_error = true;

            $error_message = 'Transaction was not successful.';
            $this->log( $error_message );

            $order->update_status( 'failed', sprintf( __( 'Payment failed via PaySubs. Response: %s', 'wc_paysubs' ), esc_attr( $data['p3'] ) ) );
            wp_redirect( $this->get_return_url( $order ) );
            exit;
        }

        // Get data sent by the gateway
        if ( !$has_error && !$is_done ) {
            $this->log( 'Get posted data' );

            $this->log( 'PaySubs Data: ' . print_r( $data, true ) );

            if ( $data === false ) {
                $has_error     = true;
                $error_message = 'Bad access on page.';
            }
        }

        // Get internal order and verify it hasn't already been processed
        if ( !$has_error && !$is_done ) {

            $this->log( "Purchase:\n" . print_r( $order, true ) );

            // Check if order has already been processed
            if ( $order->get_status() == 'completed' ) {
                $this->log( 'Order has already been processed' );
                $is_done = true;
            }
        }

        // Check data against internal order
        if ( !$has_error && !$is_done ) {
            $this->log( 'Check data against internal order' );
            $this->log( 'Total from PaySubs: ' . $data['p6'] );
            $this->log( 'Total stored internally: ' . $order->get_total() );

            // Check order amount
            if ( !$this->amounts_equal( $data['p6'], $order->get_total() ) ) {
                $has_error     = true;
                $error_message = 'Order totals don\'t match.';
            }
            // Check session ID
            elseif ( $data['m_2'] != $order->get_order_key() ) {
                $has_error     = true;
                $error_message = 'Order key mismatch.';
            }
        }

        // If an error occurred
        if ( $has_error ) {
            $this->log( 'Error occurred: ' . $error_message );
            $is_done = false;
        } else {
            $this->log( 'Transaction completed.' );
            $is_done = true;

            // Payment completed
            $order->payment_complete();
            $order->add_order_note( sprintf( __( 'Payment via PaySubs completed. Response: %s', 'wc_paysubs' ), $data['p3'] ) );

            // Empty the Cart
            $woocommerce->cart->empty_cart();
        }

        // Close log
        $this->log( '', true );

        return $is_done;
    } // End check_response_is_valid()

    /**
     * log()
     *
     * Log system processes.
     *
     * @since 1.0.0
     */
    public function log( $message )
    {
        if ( 'yes' === $this->settings['loggingmode'] ) {
            if ( !property_exists( $this, 'logger' ) && !$this->logger ) {
                $this->logger = new WC_Logger();
            }
            $this->logger->add( 'paysubs', $message );
        }
    }

    public function amounts_equal( $amount1, $amount2 )
    {
        return !( abs( floatval( $amount1 ) - floatval( $amount2 ) ) > 0.01 );
    }

    /**
     * Successful Payment!
     *
     * @since 1.0.0
     */
    public function successful_request( $m_3 = 0 )
    {
        global $woocommerce;

        $order_id = explode( '-', $m_3 );
        $order_id = $order_id[0];

        if ( !$order_id ) {
            return false;
        }

        // remove any failed attempts to reveal real order id
        $order_id = preg_replace( '/-.+/', '', $order_id );

        $order = new WC_Order( $order_id );

        wp_redirect( $this->get_return_url( $order ) );
        exit;
    } // End successful_request()

} // End Class
?>