<?php

/**
 * Class Tribe__CLI__WooCommerce__Orders_Generator
 *
 * A modified version of the class found in 75nineteen/order-simulator-woocommerce
 *
 * @link https://github.com/75nineteen/order-simulator-woocommerce
 * @link https://raw.githubusercontent.com/75nineteen/order-simulator-woocommerce/master/woocommerce-order-simulator.php
 */
class Tribe__CLI__WooCommerce__Orders_Generator {

	private $users = array();
	public $settings = array();

	public function generate_orders( $count, array $product_ids, array $args = array() ) {
		global $wpdb, $woocommerce;

		set_time_limit( 0 );

		$woocommerce->init();
		$woocommerce->frontend_includes();

		$session_class = apply_filters( 'woocommerce_session_handler', 'WC_Session_Handler' );

		// Class instances
		require_once WC()->plugin_path() . '/includes/abstracts/abstract-wc-session.php';
		$woocommerce->session  = new WC_Session_Handler();
		$woocommerce->cart     = new WC_Cart();                                    // Cart class, stores the cart contents
		$woocommerce->customer = new WC_Customer();                                // Customer class, handles data such as customer location

		$woocommerce->countries = new WC_Countries();
		$woocommerce->checkout  = new WC_Checkout();
		//$woocommerce->product_factory = new WC_Product_Factory();                      // Product Factory to create new product instances
		$woocommerce->order_factory = new WC_Order_Factory();                        // Order Factory to create new order instances
		$woocommerce->integrations  = new WC_Integrations();                         // Integrations class


		// clear cart
		if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			define( 'WOOCOMMERCE_CHECKOUT', true );
		}

		$woocommerce->cart->empty_cart();

		for ( $x = 0; $x < $count; $x ++ ) {
			$cart               = array();
			$order_min_quantity = $this->settings['min_order_products'];
			$order_max_quantity = $this->settings['max_order_products'];
			$num_products       = rand( $order_min_quantity, $order_max_quantity );
			$create_user        = false;

			if ( $this->settings['create_users'] ) {
				$create_user = ( rand( 1, 100 ) <= 50 ) ? true : false;
			}

			if ( $create_user ) {
				$user_id = self::create_user();
			} else {
				$user_id = self::get_random_user();
			}

			// add random products to cart
			for ( $i = 0; $i < $num_products; $i ++ ) {
				$idx        = rand( 0, count( $product_ids ) - 1 );
				$product_id = $product_ids[ $idx ];
				$woocommerce->cart->add_to_cart( $product_id, 1 );
			}

			// process checkout
			$data     = array(
				'billing_country'    => get_user_meta( $user_id, 'billing_country', true ),
				'billing_first_name' => get_user_meta( $user_id, 'billing_first_name', true ),
				'billing_last_name'  => get_user_meta( $user_id, 'billing_last_name', true ),
				'billing_company'    => '',
				'billing_address_1'  => get_user_meta( $user_id, 'billing_address_1', true ),
				'billing_address_2'  => '',
				'billing_city'       => get_user_meta( $user_id, 'billing_city', true ),
				'billing_state'      => get_user_meta( $user_id, 'billing_state', true ),
				'billing_postcode'   => get_user_meta( $user_id, 'billing_postcode', true ),
				'billing_email'      => get_user_meta( $user_id, 'billing_email', true ),
				'billing_phone'      => get_user_meta( $user_id, 'billing_phone', true ),

				'shipping_country'    => get_user_meta( $user_id, 'shipping_country', true ),
				'shipping_first_name' => get_user_meta( $user_id, 'shipping_first_name', true ),
				'shipping_last_name'  => get_user_meta( $user_id, 'shipping_last_name', true ),
				'shipping_company'    => '',
				'shipping_address_1'  => get_user_meta( $user_id, 'shipping_address_1', true ),
				'shipping_address_2'  => '',
				'shipping_city'       => get_user_meta( $user_id, 'shipping_city', true ),
				'shipping_state'      => get_user_meta( $user_id, 'shipping_state', true ),
				'shipping_postcode'   => get_user_meta( $user_id, 'shipping_postcode', true ),
				'shipping_email'      => get_user_meta( $user_id, 'shipping_email', true ),
				'shipping_phone'      => get_user_meta( $user_id, 'shipping_phone', true ),
			);
			$checkout = new WC_Checkout();

			$woocommerce->cart->calculate_totals();

			$order_id = $checkout->create_order( $data );

			if ( $order_id ) {
				update_post_meta( $order_id, '_payment_method', 'bacs' );
				update_post_meta( $order_id, '_payment_method_title', 'Bacs' );

				update_post_meta( $order_id, '_shipping_method', 'free_shipping' );
				update_post_meta( $order_id, '_shipping_method_title', 'Free Shipping' );

				update_post_meta( $order_id, '_customer_user', absint( $user_id ) );

				foreach ( $data as $key => $value ) {
					update_post_meta( $order_id, '_' . $key, $value );
				}

				do_action( 'woocommerce_checkout_order_processed', $order_id, $data );

				$order = new WC_Order( $order_id );

				// figure out the order status
				$status         = 'completed';
				$rand           = mt_rand( 1, 100 );
				$completed_pct  = $this->settings['order_completed_pct']; // e.g. 90
				$processing_pct = $completed_pct + $this->settings['order_processing_pct']; // e.g. 90 + 5
				$failed_pct     = $processing_pct + $this->settings['order_failed_pct']; // e.g. 95 + 5

				if ( $this->settings['order_completed_pct'] > 0 && $rand <= $completed_pct ) {
					$status = 'completed';
				} elseif ( $this->settings['order_processing_pct'] > 0 && $rand <= $processing_pct ) {
					$status = 'processing';
				} elseif ( $this->settings['order_failed_pct'] > 0 && $rand <= $failed_pct ) {
					$status = 'failed';
				}

				if ( $status == 'failed' ) {
					$order->update_status( $status );
				} else {
					$order->payment_complete();
					$order->update_status( $status );
				}
			}

			// clear cart
			$woocommerce->cart->empty_cart();
		}
	}

	public function create_user() {
		global $wpdb;

		$user_id = 0;

		do {
			$user_row = $wpdb->get_row( "SELECT * FROM fakenames ORDER BY RAND() LIMIT 1" );

			$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}users WHERE user_login = '{$user_row->username}'" );

			$unique = ( $count == 0 ) ? true : false;
		} while ( ! $unique );


		$user = array(
			'user_login' => $user_row->username,
			'user_pass'  => '75nineteen',
			'user_email' => $user_row->emailaddress,
			'first_name' => $user_row->givenname,
			'last_name'  => $user_row->surname,
			'role'       => 'customer',
		);

		$user_id = wp_insert_user( $user );

		// billing/shipping address
		$meta = array(
			'billing_country'     => $user_row->country,
			'billing_first_name'  => $user_row->givenname,
			'billing_last_name'   => $user_row->surname,
			'billing_address_1'   => $user_row->streetaddress,
			'billing_city'        => $user_row->city,
			'billing_state'       => $user_row->state,
			'billing_postcode'    => $user_row->zipcode,
			'billing_email'       => $user_row->emailaddress,
			'billing_phone'       => $user_row->telephonenumber,
			'shipping_country'    => $user_row->country,
			'shipping_first_name' => $user_row->givenname,
			'shipping_last_name'  => $user_row->surname,
			'shipping_address_1'  => $user_row->streetaddress,
			'shipping_city'       => $user_row->city,
			'shipping_state'      => $user_row->state,
			'shipping_postcode'   => $user_row->zipcode,
			'shipping_email'      => $user_row->emailaddress,
			'shipping_phone'      => $user_row->telephonenumber,
		);

		foreach ( $meta as $key => $value ) {
			update_user_meta( $user_id, $key, $value );
		}

		return $user_id;
	}

	public function get_random_user() {
		if ( ! $this->users ) {
			$this->users = get_users( array( 'role' => 'Subscriber', 'fields' => 'ID' ) );
		}

		$length = count( $this->users );
		$idx    = rand( 0, $length - 1 );

		return $this->users[ $idx ];
	}
}