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
 * WC requires at least: 6.0
 * WC tested up to: 8.0
*/ 

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;

add_action('before_woocommerce_init', function() {
    if ( class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil') ) {
        // Enable block checkout + HPOS compatibility
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'cart_checkout_blocks',
            __FILE__,
            true
        );
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            __FILE__,
            true
        );
    }
});

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





add_action(
    'woocommerce_blocks_payment_method_type_registration',
    function ( \Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $registry ) {
        require_once __DIR__ . '/includes/class-wc-hexa-blocks.php';
        $registry->register( new WC_HexaPay_Blocks() );
    }
);


