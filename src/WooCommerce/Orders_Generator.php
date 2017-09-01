<?php

/**
 * Class Tribe__CLI__WooCommerce__Orders_Generator
 *
 * A modified version of the class found in 75nineteen/order-simulator-woocommerce
 *
 * @link https://github.com/75nineteen/order-simulator-woocommerce
 * @link https://raw.githubusercontent.com/75nineteen/order-simulator-woocommerce/master/woocommerce-order-simulator.php
 */
class Tribe__Cli__WooCommerce__Orders_Generator {

	protected $users;

	public function generate_orders( $count, array $product_ids, array $args ) {
		/** @var WooCommerce $woocommerce */
		global $woocommerce;

		set_time_limit( 0 );

		$woocommerce->init();
		$woocommerce->frontend_includes();

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
			$order_min_quantity = $args['tickets_min'];
			$order_max_quantity = $args['tickets_max'];
			$num_products       = mt_rand( $order_min_quantity, $order_max_quantity );
			$create_user        = isset( $args['create_users'] ) ? (bool) $args['create_users'] : true;

			if ( $create_user ) {
				$user_id = $this->create_user();
			} else {
				$user_id = $this->get_random_user();
			}

			// add random products to cart
			for ( $i = 0; $i < $num_products; $i ++ ) {
				$idx        = mt_rand( 0, count( $product_ids ) - 1 );
				$product_id = $product_ids[ $idx ];
				$woocommerce->cart->add_to_cart( $product_id, 1 );
			}

			$available_gateways = $woocommerce->payment_gateways()->get_available_payment_gateways();
			$payment_method     = array_rand( $available_gateways );

			// process checkout
			$data           = array(
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

				'payment_method' => $payment_method,

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

				$status = $args['ticket_status'];

				// avoid sending emails
				update_post_meta( $order_id, '_tribe_mail_sent', true );

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
		$faker = Faker\Factory::create();
		$faker->addProvider( new Faker\Provider\en_US\Address( $faker ) );
		$faker->addProvider( new Faker\Provider\en_US\PhoneNumber( $faker ) );

		$user = array(
			'user_login' => $faker->userName,
			'user_pass'  => 'password',
			'user_email' => $faker->email,
			'first_name' => $faker->firstName,
			'last_name'  => $faker->lastName,
			'role'       => 'customer',
		);

		$user_id = wp_insert_user( $user );

		// billing/shipping address
		$meta = array(
			'billing_country'                          => $faker->country,
			'billing_first_name'                       => $faker->firstName,
			'billing_last_name'                        => $faker->lastName,
			'billing_address_1'                        => $faker->streetAddress,
			'billing_city'                             => $faker->city,
			'billing_state'                            => $faker->state,
			'billing_postcode'                         => $faker->postcode,
			'billing_email'                            => $faker->email,
			'billing_phone'                            => $faker->phoneNumber,
			'shipping_country'                         => $faker->country,
			'shipping_first_name'                      => $faker->firstName,
			'shipping_last_name'                       => $faker->lastName,
			'shipping_address_1'                       => $faker->streetAddress,
			'shipping_city'                            => $faker->city,
			'shipping_state'                           => $faker->state,
			'shipping_postcode'                        => $faker->postcode,
			'shipping_email'                           => $faker->email,
			'shipping_phone'                           => $faker->phoneNumber,
			Tribe__Cli__Meta_keys::$generated_meta_key => 1,
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

		if ( empty( $this->users ) ) {
			WP_CLI::error( __( 'At least on Subscriber user must exist if not creating users.', 'tribe-cli' ) );
		}

		$length = count( $this->users );
		$idx    = rand( 0, $length - 1 );

		return $this->users[ $idx ];
	}
}