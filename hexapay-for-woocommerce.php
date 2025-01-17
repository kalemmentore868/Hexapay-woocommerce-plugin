<?php

/**
 * Plugin Name: HexaPay for Woocommerce
 * Plugin URI: https://hexakodeagency.com
 * Author Name: Kalem Mentore
 * Author URI: https://hexakodeagency.com
 * Description: This plugin allows for credit/debit card payments.
 * Version: 0.1.0
 * License: 0.1.0
 * License URL: http://www.gnu.org/licenses/gpl-2.0.txt
 * text-domain: hexa-pay-woo
*/ 

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;

add_action( 'plugins_loaded', 'hexpay_payment_init', 11 );
add_filter( 'woocommerce_currencies', 'hexakode_add_tt_currencies' );
add_filter( 'woocommerce_currency_symbol', 'hexakode_add_tt_currencies_symbol', 10, 2 );
add_filter( 'woocommerce_payment_gateways', 'add_to_woo_hexpay_payment_gateway');

function hexpay_payment_init() {
    if( class_exists( 'WC_Payment_Gateway' ) ) {
		require_once plugin_dir_path( __FILE__ ) . '/includes/class-wc-payment-gateway-hexapay.php';
		require_once plugin_dir_path( __FILE__ ) . '/includes/hexapay-order-statuses.php';
	}
}

function add_to_woo_hexpay_payment_gateway( $gateways ) {
    $gateways[] = 'WC_Gateway_Hexapay';
    return $gateways;
}

function hexakode_add_tt_currencies( $currencies ) {
	$currencies['TT'] = __( 'Trinidadian Dollar', 'hexa-pay-woo' );
	return $currencies;
}

function hexakode_add_tt_currencies_symbol( $currency_symbol, $currency ) {
	switch ( $currency ) {
		case 'TT': 
			$currency_symbol = 'TT'; 
		break;
	}
	return $currency_symbol;
}

add_action( 'init', function() {
    add_rewrite_rule( '^hexakode-render-form/?$', 'index.php?hexakode_render_form=1', 'top' );
    add_rewrite_tag( '%hexakode_render_form%', '1' );
} );

add_action( 'template_redirect', function() {
    if ( get_query_var( 'hexakode_render_form' ) ) {
        $order_id = isset($_GET['hexakode_order']) ? absint($_GET['hexakode_order']) : 0;
        if ( $order_id ) {
            $form_html = get_transient( 'hexakode_form_' . $order_id );
            if ( $form_html ) {
                // Output the stored HTML form.
                header('Content-Type: text/html');
                echo $form_html;
                // Optionally delete the transient if not needed anymore.
                delete_transient( 'hexakode_form_' . $order_id );
                exit;
            }
        }
        wp_die('Invalid request.'); // Handle errors gracefully.
    }
});

