<?php


if( !defined( 'ABSPATH' ) ) exit;


function rp_ptr_add_rate( $methods ) {
	$methods[] = 'rp_ptr_shipping';
	return $methods;
}


class rp_ptr_shipping extends WC_Shipping_Method {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	function __construct() {
		$this->id 					= 'rp_ptrs';
		$this->method_title 		= __( 'Premium Table Rate', 'wpr-ptr-shipping' );
		$this->table_rate_option	= 'rp_ptr_shipping';
		$this->method_description 	= __( 'Table rates let you define a standard rate per item, or per order.', 'wpr-ptr-shipping' );

		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );

		$this->init();
	}

	/**
	 * init function.
	 *
	 * @access public
	 * @return void
	 */
	function init() {
		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables
		$this->title 			= $this->get_option( 'title' );
		$this->method 			= $this->get_option( 'method' );
		$this->apply_when 		= $this->get_option( 'apply_when' );
		$this->type 			= $this->get_option( 'type' );
		$this->tax_status 		= $this->get_option( 'tax_status' );
		$this->handleMaxOrder 	= $this->get_option( 'handleMaxOrder' );
		$this->handleMinOrder 	= $this->get_option( 'handleMinOrder' );
	}

    /**
     * Initialise Gateway Settings Form Fields
     *
     * @access public
     * @return void
     */
	function init_form_fields() {
		global $woocommerce;

		$this->form_fields = apply_filters('rp_ptr_form_fields', array(
		'enabled'		=> array(
			'title'			=> __( 'Enable/Disable', 'wpr-ptr-shipping' ),
			'type'			=> 'checkbox',
			'label'			=> __( 'Enable this shipping method', 'wpr-ptr-shipping' ),
			'default'		=> 'no',
			),

		'title'		=> array(
			'title'			=> __( 'Method Title', 'wpr-ptr-shipping' ),
			'type'			=> 'text',
			'description'	=> __( 'This controls the title which the user sees during checkout.', 'wpr-ptr-shipping' ),
			'default'		=> __( 'Table Rate', 'wpr-ptr-shipping' ),
			'desc_tip'		=> true
			),
		'method'		=> array(
			'title'			=> __( 'Calculate Method?', 'wpr-ptr-shipping' ),
			'type'			=> 'select',
			'default'		=> 'price',
			'description'	=> __( 'This controls how the shipping is calculated. Example - price or weight.', 'wpr-ptr-shipping' ),
			'desc_tip'		=> true,
			'options'		=> array(
				'price'		=> __( 'Price', 'wpr-ptr-shipping' ),
				'weight'	=> __( 'Weight', 'wpr-ptr-shipping' ),
				),
			),
		'apply_when'	=> array(
			'title'			=> __( 'Calculate Discounts?', 'wpr-ptr-shipping' ),
			'type'			=> 'select',
			'default'		=> 'before',
			'description'	=> __( 'This controls if the shipping is calculated before any applied discounts or after they are applied.', 'wpr-ptr-shipping' ),
			'desc_tip'		=> true,
			'options'		=> array(
				'before'	=> __( 'Before Discount', 'wpr-ptr-shipping' ),
				'after'		=> __( 'After Discount', 'wpr-ptr-shipping' ),
				),
			),
		'tax_status'	 => array(
			'title'			=> __( 'Tax Status', 'wpr-ptr-shipping' ),
			'type'			=> 'select',
			'default'		=> 'taxable',
			'options'		=> array(
				'taxable'	=> __( 'Taxable', 'wpr-ptr-shipping' ),
				'none'		=> __( 'None', 'wpr-ptr-shipping' ),
				),
			),
		'handleMinOrder' => array(
			'title'			=> __( 'Order under Min value', 'wpr-ptr-shipping' ),
			'type'			=> 'select',
			'description'	=> __( 'This controls how you handle orders that are under your min order amount on your table.', 'wpr-ptr-shipping' ),
			'desc_tip'		=> true,
			'default'		=> 'na',
			'options'		=> array(
				'na'		=> __( 'Not Available', 'wpr-ptr-shipping' ),
				'free'		=> __( 'Free Shipping', 'wpr-ptr-shipping' ),
				'same'		=>	__( 'Same as bottom Value', 'wpr-ptr-shipping')
				),
			),
		'handleMaxOrder' => array(
			'title'			=> __( 'Order over Max value', 'wpr-ptr-shipping' ),
			'type'			=> 'select',
			'description'	=> __( 'This controls how you handle orders that are over your max order amount on your table.', 'wpr-ptr-shipping' ),
			'desc_tip'		=> true,
			'default'		=> 'na',
			'options'		=> array(
				'na' 		=> __( 'Not Available', 'wpr-ptr-shipping' ),
				'free'		=> __( 'Free Shipping', 'wpr-ptr-shipping' ),
				'same'		=>	__( 'Same as top Value', 'wpr-ptr-shipping')
				),
			),
		));
	}


	/**
	 * calculate_shipping function.
	 *
	 * @access public
	 * @param array   $package (default: array())
	 * @return void
	 */
	function calculate_shipping( $package = array() ) {

		// Set the variables to 0
		//$virtualPrice 	= 0;
		//$shipping_cost 	= 0;
		//$discount_total = 0.00;

		// Get Table rate shipping information
		
		//$theCountry 	= WC()->customer->get_shipping_country(); // Finds the country that you are going to ship to
		//$taxable 		= ( $this->get_option( 'tax_status' ) == 'taxable' ) ? true : false;
		//$maxOrder 		= $this->get_option( 'handleMaxOrder' );
		//$minOrder 		= $this->get_option( 'handleMinOrder' );

		$cart = WC()->session->cart;

		$calcAmount = 0;
		foreach( $cart as $key => $product ) {
			$theProduct = new WC_Product( $product['product_id'] );
			if ( $this->get_option( 'method' ) == "price") {
				
				if( ! $theProduct->is_virtual() ) {
					$calcAmount += $product['line_total'];	
				}

			} else if ( $this->get_option( 'method' ) == "weight" ) {
				$calcAmount += $theProduct->get_weight();
			}
		}

		if ( ( $this->get_option( 'method' ) == "price") && ( $this->get_option( 'apply_when' ) == "after" ) ) {
			$cart->get_cart_discount_total();







	


			$rate = apply_filters( 'wpr_table_rate', array(
				'id'		=> $this->id,
				'label'		=> $this->title,
				'cost'		=> $calcAmount,//$shipping_costs,
				'calc_tax'	=> 'per_order'
			) );

			$this->add_rate( $rate );


		}
	}
}

/*


		

		

		

		

		// Finds all the posts that have table rates
		$posts_array = array();
		$args = array(
			'post_type'        => 'rp_ptr_shipping',
			'post_status'      => 'publish',
			'suppress_filters' => true );
		$posts_array = get_posts( $args );

		$countryActive = 0;

		$shipping_rates = array();

		//  Gets the shipping table that needs to be used
		foreach ($posts_array as $shippingTable) {
			$shippingCountry = get_post_meta( $shippingTable->ID, '_rp_ptr_country', true );

			if( $shippingCountry == $theCountry ) {
				$shipping_rates[] = get_post_meta( $shippingTable->ID, '_rp_ptr_shippingData', true );

				$countryActive = 1;
			}

			if ( $shippingCountry == "unselected" ) 
				$unselected_rates = get_post_meta( $shippingTable->ID, '_rp_ptr_shippingData', true );

		}

		if ( ( $countryActive == 0 ) && isset( $unselected_rates ) )
			$shipping_rates[] = $unselected_rates;

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// calulates shipping based off the price on the cart ////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		
		if ( $this->get_option( 'method' ) == "price") {

			// Determines what products are going to be shipped
			foreach ( $woocommerce->cart->get_cart() as $item ) {
				if ( ! $item['data']->is_virtual() ){
					$shipping_cost += $item['data']->get_price() * $item['quantity'];
				} else {
					$virtualPrice += $item['data']->get_price() * $item['quantity'];
				}
			}

			// Determines how much coupons are worth and returns a discount total
			if ( ! empty( $woocommerce->cart->applied_coupons ) ) {
				foreach ( $woocommerce->cart->applied_coupons as $key => $code ) {
					$coupon = new WC_Coupon( $code );

					$couponAmount = (float) $coupon->amount;

					switch ( $coupon->type ) {
						case "fixed_cart" :

							if ( $couponAmount > $totalPrice )
								$couponAmount = $totalPrice;

							$discount_total = (float) $discount_total - $couponAmount;
						break;

						case "percent" :
							$percent_discount = (float) round( ( $totalPrice * ( $couponAmount * 0.01 ) ) );

							if ( $percent_discount > $totalPrice )
								$percent_discount = $totalPrice;

							$discount_total = (float) $discount_total - $percent_discount;
						break;
					}
				}
			}

			if( $this->get_option( 'apply_when' ) == "after")
				$shipping_cost = $totalPrice + $discount_total; // Adds the discounted amount back to the shipping price

			$price = (float) $shipping_cost; //Sets the Price that we will calculate the shipping
			$shipping_costs = -1;
			$theFirst = 0;

			$lastShipping = end($shipping_rates);
			
			if( $price >= (float) $lastShipping['maxO'] ) {

				if ( $maxOrder == "free" ) $shipping_costs = 0;
				if ( $maxOrder == "same" ) $shipping_costs = (float) $lastShipping['rate'];

			} else {

				foreach ( $shipping_rates as $rates ) {

					if ( ( (float) $price < (float) $rates['minO'] )  && ( $theFirst == 0 ) ) {
						if ( $minOrder == "free" ) $shipping_costs = 0;
						if ( $minOrder == "na" ) break;
						if ( $minOrder == "same" ) $shipping_costs = (float) $rates['rate'];

						$theFirst = 1;
						break;
					}

					$shipping_costs = (float) $rates['rate'];
					if ( ( (float) $price >= (float) $rates['minO']) && ( (float) $price <= (float) $rates['maxO'] ) )
						break;

				}

			}

		} else if ( $this->get_option( 'method' ) == "weight" ) {

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// calulates shipping based off the weight of the products  //////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

			$weight = (float) $woocommerce->cart->cart_contents_weight; //Sets the Price that we will calculate the shipping
			$shipping_costs = -1;
			$theFirst = 0;

			foreach ( $shipping_rates as $rates ) {
				if ( ( $weight < (float) $rates['minO'] )  && ( $theFirst == 0 ) ) {
					$theFirst = 1;
					break;
				}

				$shipping_costs = (float) $rates['rate'];
				if ( ( $weight >= (float) $rates['minO']) && ( $weight <= (float) $rates['maxO'] ) )
					break;
			}

		}

		if ( ( $shipping_costs <> -1 ) || ( $this->get_option( 'method' ) == "weight" ) ) {


			$rate = array(
				'id'		=> $this->id,
				'label'		=> $this->title,
				'cost'		=> $shipping_costs,
				'calc_tax'	=> 'per_order'
			);
			$this->add_rate( $rate );
			
		}

	}

	function validate_shipping_table_field( $key ) {
		return false;
	}

*/


