<?php

/**
 * Hexakode Payments Gateway.
 *
 * Provides a Hexakode Payments Payment Gateway.
 *
 * @class       WC_Gateway_Hexapay
 * @extends     WC_Payment_Gateway
 * @version     2.1.0
 * @package     WooCommerce/Classes/Payment
 */
class WC_Gateway_Hexapay extends WC_Payment_Gateway {
	public $api_key;
    public $user_id;
    public $instructions;
    public $enable_for_methods;
    public $enable_for_virtual;
	public $test_api_key;
	public $is_test_mode;
	public $id;
	public $fee_structure;

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		
		// Setup general properties.
		$this->setup_properties();

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		$this->id = 'hexakode';

		// Get settings.
		$this->title              = $this->get_option( 'title' );
		$this->description        = $this->get_option( 'description' );
		$this->api_key        = $this->get_option( 'api_key' );
		$this->test_api_key        = $this->get_option( 'test_api_key' );
		$this->is_test_mode       = $this->get_option( 'is_test_mode' );
		$this->user_id      = $this->get_option( 'user_id' );
		$this->instructions       = $this->get_option( 'instructions' );
		$this->enable_for_methods = $this->get_option( 'enable_for_methods', array() );
		$this->enable_for_virtual = $this->get_option( 'enable_for_virtual', 'yes' ) === 'yes';
		$this->fee_structure = $this->get_option( 'fee_structure', 'customer_pays' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'handle_hexa_redirect' ), 10 );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ), 20 );
		
		add_filter( 'woocommerce_payment_complete_order_status', array( $this, 'change_payment_complete_order_status' ), 10, 3 );

		// Customer Emails.
		add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );

		// add_action( 'wp', array( $this, 'handle_hexa_redirect' ) );
	}

	/**
	 * Setup general properties for the gateway.
	 */
	protected function setup_properties() {
		$this->id                 = 'hexakode';
		$this->icon               = apply_filters( 'woocommerce_hex_icon', plugins_url('../assets/icon.png', __FILE__ ) );
		$this->method_title       = __( 'Hexakode Payments', 'hexa-pay-woo' );
		$this->method_description = __( 'Have your customers pay with Hexakode Payments.', 'hexa-pay-woo' );
		$this->api_key        =  __( 'Add API KEY.', 'hexa-pay-woo' );
		$this->test_api_key        =  __( 'Add Test API KEY.', 'hexa-pay-woo' );
		$this->user_id      = __( 'Add User Id.', 'hexa-pay-woo' );
		$this->has_fields         = false;
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'            => array(
				'title'       => __( 'Enable/Disable', 'hexa-pay-woo' ),
				'label'       => __( 'Enable Hexakode Payments', 'hexa-pay-woo' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no',
			),
			'is_test_mode'            => array(
				'title'       => __( 'Enable Test Mode', 'hexa-pay-woo' ),
				'label'       => __( 'Enable test mode (uncheck for live)', 'hexa-pay-woo' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no',
			),
			'title'              => array(
				'title'       => __( 'Title', 'hexa-pay-woo' ),
				'type'        => 'text',
				'description' => __( 'Hexakode Payment method description that the customer will see on your checkout.', 'hexa-pay-woo' ),
				'default'     => __( 'Hexakode Payments', 'hexa-pay-woo' ),
				'desc_tip'    => true,
			),
			'api_key'              => array(
				'title'       => __( 'API Key', 'hexa-pay-woo' ),
				'type'        => 'text',
				'description' => __( 'Add your api key from the dashboard', 'hexa-pay-woo' ),
				'desc_tip'    => true,
			),
			'test_api_key'              => array(
				'title'       => __( 'Test API Key', 'hexa-pay-woo' ),
				'type'        => 'text',
				'description' => __( 'Add your test api key from the dashboard', 'hexa-pay-woo' ),
				'desc_tip'    => true,
			),
			'user_id'              => array(
				'title'       => __( 'User Id', 'hexa-pay-woo' ),
				'type'        => 'text',
				'description' => __( 'Add your user_id from the dashboard', 'hexa-pay-woo' ),
				'desc_tip'    => true,
			),
			'fee_structure' => array(
    'title'       => __( 'Fee Structure', 'hexa-pay-woo' ),
    'type'        => 'select',
    'description' => __( 'Choose who pays the transaction fees.', 'hexa-pay-woo' ),
    'default'     => 'customer_pays',
    'desc_tip'    => true,
    'options'     => array(
        'customer_pays' => __( 'Customer Pays', 'hexa-pay-woo' ),
        'merchant_pays' => __( 'Merchant Pays', 'hexa-pay-woo' ),
        'split'         => __( 'Split', 'hexa-pay-woo' ),
    ),
),
			'description'        => array(
				'title'       => __( 'Description', 'hexa-pay-woo' ),
				'type'        => 'textarea',
				'description' => __( 'Hexakode Payment method description that the customer will see on your website.', 'hexa-pay-woo' ),
				'default'     => __( 'Hexakode Payments before delivery.', 'hexa-pay-woo' ),
				'desc_tip'    => true,
			),
			'instructions'       => array(
				'title'       => __( 'Instructions', 'hexa-pay-woo' ),
				'type'        => 'textarea',
				'description' => __( 'Instructions that will be added to the thank you page.', 'hexa-pay-woo' ),
				'default'     => __( 'Hexakode Payments before delivery.', 'hexa-pay-woo' ),
				'desc_tip'    => true,
			),
			'enable_for_methods' => array(
				'title'             => __( 'Enable for shipping methods', 'hexa-pay-woo' ),
				'type'              => 'multiselect',
				'class'             => 'wc-enhanced-select',
				'css'               => 'width: 400px;',
				'default'           => '',
				'description'       => __( 'If hex is only available for certain methods, set it up here. Leave blank to enable for all methods.', 'hexa-pay-woo' ),
				'options'           => $this->load_shipping_method_options(),
				'desc_tip'          => true,
				'custom_attributes' => array(
					'data-placeholder' => __( 'Select shipping methods', 'hexa-pay-woo' ),
				),
			),
			'enable_for_virtual' => array(
				'title'   => __( 'Accept for virtual orders', 'hexa-payments-woo' ),
				'label'   => __( 'Accept hexakode if the order is virtual', 'hexa-payments-woo' ),
				'type'    => 'checkbox',
				'default' => 'yes',
			),
		);
	}

	/**
	 * Check If The Gateway Is Available For Use.
	 *
	 * @return bool
	 */
	public function is_available() {
		$order          = null;
		$needs_shipping = false;

		// Test if shipping is needed first.
		if ( WC()->cart && WC()->cart->needs_shipping() ) {
			$needs_shipping = true;
		} elseif ( is_page( wc_get_page_id( 'checkout' ) ) && 0 < get_query_var( 'order-pay' ) ) {
			$order_id = absint( get_query_var( 'order-pay' ) );
			$order    = wc_get_order( $order_id );

			// Test if order needs shipping.
			if ( 0 < count( $order->get_items() ) ) {
				foreach ( $order->get_items() as $item ) {
					$_product = $item->get_product();
					if ( $_product && $_product->needs_shipping() ) {
						$needs_shipping = true;
						break;
					}
				}
			}
		}

		$needs_shipping = apply_filters( 'woocommerce_cart_needs_shipping', $needs_shipping );

		// Virtual order, with virtual disabled.
		if ( ! $this->enable_for_virtual && ! $needs_shipping ) {
			return false;
		}

		// Only apply if all packages are being shipped via chosen method, or order is virtual.
		if ( ! empty( $this->enable_for_methods ) && $needs_shipping ) {
			$order_shipping_items            = is_object( $order ) ? $order->get_shipping_methods() : false;
			$chosen_shipping_methods_session = WC()->session->get( 'chosen_shipping_methods' );

			if ( $order_shipping_items ) {
				$canonical_rate_ids = $this->get_canonical_order_shipping_item_rate_ids( $order_shipping_items );
			} else {
				$canonical_rate_ids = $this->get_canonical_package_rate_ids( $chosen_shipping_methods_session );
			}

			if ( ! count( $this->get_matching_rates( $canonical_rate_ids ) ) ) {
				return false;
			}
		}

		return parent::is_available();
	}

	/**
	 * Checks to see whether or not the admin settings are being accessed by the current request.
	 *
	 * @return bool
	 */
	private function is_accessing_settings() {
		if ( is_admin() ) {
			// phpcs:disable WordPress.Security.NonceVerification
			if ( ! isset( $_REQUEST['page'] ) || 'wc-settings' !== $_REQUEST['page'] ) {
				return false;
			}
			if ( ! isset( $_REQUEST['tab'] ) || 'checkout' !== $_REQUEST['tab'] ) {
				return false;
			}
			if ( ! isset( $_REQUEST['section'] ) || 'hexakode' !== $_REQUEST['section'] ) {
				return false;
			}
			// phpcs:enable WordPress.Security.NonceVerification

			return true;
		}

		return false;
	}

	/**
	 * Loads all of the shipping method options for the enable_for_methods field.
	 *
	 * @return array
	 */
	private function load_shipping_method_options() {
		// Since this is expensive, we only want to do it if we're actually on the settings page.
		if ( ! $this->is_accessing_settings() ) {
			return array();
		}

		$data_store = WC_Data_Store::load( 'shipping-zone' );
		$raw_zones  = $data_store->get_zones();

		foreach ( $raw_zones as $raw_zone ) {
			$zones[] = new WC_Shipping_Zone( $raw_zone );
		}

		$zones[] = new WC_Shipping_Zone( 0 );

		$options = array();
		foreach ( WC()->shipping()->load_shipping_methods() as $method ) {

			$options[ $method->get_method_title() ] = array();

			// Translators: %1$s shipping method name.
			$options[ $method->get_method_title() ][ $method->id ] = sprintf( __( 'Any &quot;%1$s&quot; method', 'hexa-payments-woo' ), $method->get_method_title() );

			foreach ( $zones as $zone ) {

				$shipping_method_instances = $zone->get_shipping_methods();

				foreach ( $shipping_method_instances as $shipping_method_instance_id => $shipping_method_instance ) {

					if ( $shipping_method_instance->id !== $method->id ) {
						continue;
					}

					$option_id = $shipping_method_instance->get_rate_id();

					// Translators: %1$s shipping method title, %2$s shipping method id.
					$option_instance_title = sprintf( __( '%1$s (#%2$s)', 'hexa-payments-woo' ), $shipping_method_instance->get_title(), $shipping_method_instance_id );

					// Translators: %1$s zone name, %2$s shipping method instance name.
					$option_title = sprintf( __( '%1$s &ndash; %2$s', 'hexa-payments-woo' ), $zone->get_id() ? $zone->get_zone_name() : __( 'Other locations', 'hexa-payments-woo' ), $option_instance_title );

					$options[ $method->get_method_title() ][ $option_id ] = $option_title;
				}
			}
		}

		return $options;
	}

	/**
	 * Converts the chosen rate IDs generated by Shipping Methods to a canonical 'method_id:instance_id' format.
	 *
	 * @since  3.4.0
	 *
	 * @param  array $order_shipping_items  Array of WC_Order_Item_Shipping objects.
	 * @return array $canonical_rate_ids    Rate IDs in a canonical format.
	 */
	private function get_canonical_order_shipping_item_rate_ids( $order_shipping_items ) {

		$canonical_rate_ids = array();

		foreach ( $order_shipping_items as $order_shipping_item ) {
			$canonical_rate_ids[] = $order_shipping_item->get_method_id() . ':' . $order_shipping_item->get_instance_id();
		}

		return $canonical_rate_ids;
	}

	/**
	 * Converts the chosen rate IDs generated by Shipping Methods to a canonical 'method_id:instance_id' format.
	 *
	 * @since  3.4.0
	 *
	 * @param  array $chosen_package_rate_ids Rate IDs as generated by shipping methods. Can be anything if a shipping method doesn't honor WC conventions.
	 * @return array $canonical_rate_ids  Rate IDs in a canonical format.
	 */
	private function get_canonical_package_rate_ids( $chosen_package_rate_ids ) {

		$shipping_packages  = WC()->shipping()->get_packages();
		$canonical_rate_ids = array();

		if ( ! empty( $chosen_package_rate_ids ) && is_array( $chosen_package_rate_ids ) ) {
			foreach ( $chosen_package_rate_ids as $package_key => $chosen_package_rate_id ) {
				if ( ! empty( $shipping_packages[ $package_key ]['rates'][ $chosen_package_rate_id ] ) ) {
					$chosen_rate          = $shipping_packages[ $package_key ]['rates'][ $chosen_package_rate_id ];
					$canonical_rate_ids[] = $chosen_rate->get_method_id() . ':' . $chosen_rate->get_instance_id();
				}
			}
		}

		return $canonical_rate_ids;
	}

	/**
	 * Indicates whether a rate exists in an array of canonically-formatted rate IDs that activates this gateway.
	 *
	 * @since  3.4.0
	 *
	 * @param array $rate_ids Rate ids to check.
	 * @return boolean
	 */
	private function get_matching_rates( $rate_ids ) {
		// First, match entries in 'method_id:instance_id' format. Then, match entries in 'method_id' format by stripping off the instance ID from the candidates.
		return array_unique( array_merge( array_intersect( $this->enable_for_methods, $rate_ids ), array_intersect( $this->enable_for_methods, array_unique( array_map( 'wc_get_string_before_colon', $rate_ids ) ) ) ) );
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( $order->get_total() > 0 ) {
			// Retrieve the redirect URL from your processing method
			$redirect_url = $this->hexakode_payment_processing( $order );
	
			if ( $redirect_url ) {
				return array(
					'result'   => 'success',
					'redirect' => $redirect_url,
				);
			} else {
				// Handle the case when redirect URL wasn't obtained
				wc_add_notice( __( 'Payment processing failed. Please try again.', 'hexa-pay-woo' ), 'error' );
				return;
			}
		} else {
			$order->payment_complete();
			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order ),
			);
		}
	
	}

	private function hexakode_payment_processing( $order ) {
		$api_key = $this->api_key; // Your API key for authentication
		$test_api_key = $this->test_api_key;
		$is_test_mode = $this->get_option( 'is_test_mode' );
		$user_id = $this->user_id;
		$order_id = $order->get_id(); // WooCommerce order ID
		$total_amount = number_format( $order->get_total(), 2, '.', '' ); // Order total
		$currency = $order->get_currency(); // Currency code
		$customer_email = $order->get_billing_email(); // Customer email
		$fee_structure = $this->fee_structure;
		$customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(); // Customer name
		$response_success_url = add_query_arg(
			array(
				'payment_status' => 'success',
				'order_id'       => $order_id,
			),
			$this->get_return_url( $order )
		);
		
		$response_fail_url = add_query_arg(
			array(
				'payment_status' => 'failure',
				'order_id'       => $order_id,
			),
			$this->get_return_url( $order )
		);
	
		// Prepare the data to send to your backend
		$payload = array(
			'order_id'      => $order_id,
			'total_amount'  => $total_amount,
			'currency'      => $currency,
			'fee_structure' => $fee_structure,
			'customer_email'=> $customer_email,
			'customer_name' => $customer_name,
			'success_url'   => $response_success_url,
			'fail_url'      => $response_fail_url,
			'user_id'       => $user_id,
		);

		$request_url = ( 'yes' === $is_test_mode )
    ? 'https://api.hexakode-invoicing.com/api/test/off-site/create-payment'
    : 'https://api.hexakode-invoicing.com/api/prod/off-site/create-payment';

$authorization_key = ( 'yes' === $is_test_mode ) ? $test_api_key : $api_key;
	
		// Send a POST request to the backend
		$response = wp_remote_post( $request_url, array(
			'method'    => 'POST',
			'timeout'   => 45,
			'headers'   => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $authorization_key
			),
			'body'      => wp_json_encode( $payload ),
		) );
	
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			wc_add_notice( __( 'Payment error: ', 'hexa-payments-woo' ) . $error_message, 'error' );
			return false;
		}
	
		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
	
		// If the request was successful (HTTP 200), parse the JSON response
		if ( $response_code === 200 ) {
			$json_data = json_decode( $response_body, true );
	
			// Check if our backend responded with { "redirect": "..." }
			if ( isset( $json_data['redirect'] ) ) {
				// Return the redirect URL to which we want the user to go
				return $json_data['redirect'];
			} else {
				wc_add_notice(
					__( 'Payment error: Missing redirect URL in response.', 'hexa-payments-woo' ),
					'error'
				);
				return false;
			}
		} else {
			wc_add_notice(
				__( 'Payment error: ', 'hexa-payments-woo' ) . $response_body,
				'error'
			);
			return false;
		}
}

	/**
 * Handle redirect after payment success or failure.
 */
public function handle_hexa_redirect() {
    error_log('handle_hexa_redirect() triggered.');

    if ( isset( $_GET['payment_status'] ) && isset( $_GET['order_id'] ) ) {
        $order_id = intval( $_GET['order_id'] );
        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            echo '<p>' . esc_html__( 'Order not found.', 'hexa-pay-woo' ) . '</p>';
            return;
        }

        if ( $_GET['payment_status'] === 'success' ) {
            if ( $order->get_status() !== 'completed' ) {
                $order->payment_complete();
                $order->add_order_note( __( 'Payment completed via HexaPay.', 'hexa-pay-woo' ) );
                WC()->cart->empty_cart();
                echo '<p>' . esc_html__( 'Payment successful. Your order is completed.', 'hexa-pay-woo' ) . '</p>';
            }
        } elseif ( $_GET['payment_status'] === 'failure' ) {
            if ( $order->get_status() !== 'failed' ) {
                $order->update_status( 'failed', __( 'Payment failed via HexaPay.', 'hexa-pay-woo' ) );
                $order->add_order_note( __( 'Payment failed via HexaPay.', 'hexa-pay-woo' ) );
                echo '<p>' . esc_html__( 'Payment failed. Please try again or contact support.', 'hexa-pay-woo' ) . '</p>';
            }
        }
    }
}


	

	/**
	 * Output for the order received page.
	 */
	public function thankyou_page() {
		if ( $this->instructions ) {
			echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) );
		}

		wc_print_notices();
	}

	/**
	 * Change payment complete order status to completed for hexakode orders.
	 *
	 * @since  3.1.0
	 * @param  string         $status Current order status.
	 * @param  int            $order_id Order ID.
	 * @param  WC_Order|false $order Order object.
	 * @return string
	 */
	public function change_payment_complete_order_status( $status, $order_id = 0, $order = false ) {
		// if ( $order && 'Hexakode Payments' === $order->get_payment_method() ) {
		// 	$status = 'completed';
		// }
		return $status;
	}

	/**
	 * Add content to the WC emails.
	 *
	 * @param WC_Order $order Order object.
	 * @param bool     $sent_to_admin  Sent to admin.
	 * @param bool     $plain_text Email format: plain text or HTML.
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( $this->instructions && ! $sent_to_admin && $this->id === $order->get_payment_method() ) {
			echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) . PHP_EOL );
		}
	}
}
