<?php
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class WC_HexaPay_Blocks extends AbstractPaymentMethodType {
    protected $name = 'hexakode';
    protected $gateway;

    public function initialize() {
        $gateways = WC()->payment_gateways()->payment_gateways();
        $this->gateway = $gateways['hexakode'] ?? null;
    }

    public function is_active() {
        return true;
    }

    public function get_payment_method_script_handles() {
    wp_register_script(
        'wc-hexapay-blocks-integration',
        plugins_url('block/hexapay-block.js', __DIR__),
        ['wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-i18n'],
        null,
        true
    );

    $settings = get_option('woocommerce_hexakode_settings', []);
wp_add_inline_script(
    'wc-hexapay-blocks-integration',
    sprintf(
        'window.wc = window.wc || {}; window.wc.wcSettings = window.wc.wcSettings || {}; window.wc.wcSettings["hexakode_data"] = %s;',
        wp_json_encode([
            'title'       => $settings['title'] ?? 'Hexakode Payments',
            'description' => $settings['description'] ?? '',
            'ariaLabel'   => $settings['title'] ?? 'Hexakode Payments',
        ])
    ),
    'before'
);

    return ['wc-hexapay-blocks-integration'];
}

    public function get_payment_method_data() {
        $settings = get_option('woocommerce_hexakode_settings', []);
        return [
            'title'       => $settings['title'] ?? 'Hexakode Payments',
            'description' => $settings['description'] ?? '',
            'supports'    => ['products'],
            'ariaLabel'   => $settings['title'] ?? 'Hexakode Payments',
        ];
    }

    public function enqueue_payment_method_script() {
    wp_enqueue_script(
        'wc-hexapay-blocks-integration',
        plugins_url('block/hexapay-block.js', __DIR__),
        ['wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-i18n'],
        null,
        true
    );
}
}
