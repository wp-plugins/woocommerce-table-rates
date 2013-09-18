<?php
/*
Plugin Name: WooCommerce Table Rates
Plugin URI: http://ryanpletcher.com
Description: Plugin for fixed rate shipping depending upon the cart amount in WooCommerce.
Version: 1.1.2
Author: Ryan Pletcher
Author URI: http://ryanpletcher.com
License: GPL2
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_action('plugins_loaded', 'woocommerce_tablerate_rp', 0);

function woocommerce_tablerate_rp() {
	if (!class_exists('WC_Shipping_Method'))
		return;
  
	function add_table_rate_rp($methods) {
		$methods[] = 'rp_tablerates';
		return $methods;
	}
  
	add_filter('woocommerce_shipping_methods', 'add_table_rate_rp');
  
	class rp_tablerates extends WC_Shipping_Method {
  
	/**
	* __construct function.
	*
	* @access public
	* @return void
	*/
	function __construct() {
		$this->id           = 'rp_table_rate';
		$this->id_int         = 'rp_int_table_rate';
		$this->method_title       = __( 'Table Rate', 'woocommerce' );
		$this->table_rate_option    = 'rp_wc_table_rates';
		$this->int_table_rate_option  = 'rp_wc_int_table_rates';
		$this->method_description   = __( 'Table rates let you define a standard rate per item, or per order.', 'woocommerce' );

		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_table_rates' ) );
		add_filter( 'woocommerce_settings_api_sanitized_fields_' . $this->id, array( $this, 'save_default_costs' ) );

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
		$this->title      = $this->get_option( 'title' );
		$this->int_title    = $this->get_option( 'International' );
		$this->availability   = $this->get_option( 'availability' );
		$this->countries    = $this->get_option( 'countries' );
		$this->local_countries  = $this->get_option( 'local_countries' );
		$this->type       = $this->get_option( 'type' );
		$this->tax_status   = $this->get_option( 'tax_status' );
		$this->region     = $this->get_option( 'region' );
		$this->min_order    = $this->get_option( 'min_order' );
		$this->max_order    = $this->get_option( 'max_order' );
		$this->shipping_rate  = $this->get_option( 'shipping_rate' );
		$this->international  = $this->get_option( 'international' );

		// Load Table rates
		$this->get_table_rates();
	}

	/**
	* Initialise Gateway Settings Form Fields
	*
	* @access public
	* @return void
	*/
    function init_form_fields() {
		global $woocommerce;

		$this->form_fields = array(
			'enabled' => array(
				'title'     	=> __( 'Enable/Disable', 'woocommerce' ),
				'type'      	=> 'checkbox',
				'label'     	=> __( 'Enable this shipping method', 'woocommerce' ),
				'default'   	=> 'no',
			),
			'title' => array(
				'title'     	=> __( 'Method Title', 'woocommerce' ),
				'type'      	=> 'text',
				'description' 	=> __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'default'   	=> __( 'Table Rate', 'woocommerce' ),
				'desc_tip'    	=> true
			),
			'availability' => array(
				'title'    		=> __( 'Availability', 'woocommerce' ),
				'type'      	=> 'select',
				'default'   	=> 'all',
				'class'     	=> 'availability',
				'options'   	=> array(
					'all'   	=> __( 'All allowed countries', 'woocommerce' ),
					'specific'  => __( 'Specific Countries', 'woocommerce' ),
				),
			),
			'countries' => array(
				'title'     	=> __( 'Specific Countries', 'woocommerce' ),
				'type'      	=> 'multiselect',
				'class'     	=> 'chosen_select',
				'css'     		=> 'width: 450px;',
				'default'   	=> '',
				'options'   	=> $woocommerce->countries->countries,
			),
			'tax_status' => array(
				'title'     => __( 'Tax Status', 'woocommerce' ),
				'type'      => 'select',
				'default'   => 'taxable',
				'options'   => array(
					'taxable' => __( 'Taxable', 'woocommerce' ),
					'none'    => __( 'None', 'woocommerce' ),
				),
			),
			'international' => array(
				'title'     => __( 'Enable/Disable Interational Table', 'woocommerce' ),
				'type'      => 'checkbox',
				'label'     => __( 'Enable the International shipping table rates method', 'woocommerce' ),
				'default'   => 'no',
			),
			'local_countries' => array(
				'title'     => __( 'Local Countries', 'woocommerce' ),
				'type'      => 'multiselect',
				'class'     => 'chosen_select',
				'css'     => 'width: 450px;',
				'default'   => '',
				'options'   => $woocommerce->countries->countries,
			),        
			'domestic_shipping_table' => array(
				'type'      => 'shipping_table'
			),
		);
	}


    /**
    * calculate_shipping function.
    *
    * @access public
    * @param array $package (default: array())
    * @return void
    */
    function calculate_shipping( $package = array() ) {
		global $woocommerce;
		  
		$this->rate = array();
		$this->int_rate = array();


		$localCountry = $this->get_option( 'local_countries' ); // "US";
		$allCountry = $this->get_option( 'countries' );
		$myCountry = $woocommerce->customer->get_shipping_country();

		$shipping_rates = get_option( 'rp_wc_table_rates' );
		$int_shipping_rates = get_option( 'rp_wc_int_table_rates' );

		$totalPrice = $woocommerce->cart->cart_contents_total;

		$virtualPrice = 0;

		foreach ( $woocommerce->cart->get_cart() as $item )
			if ( $item['data']->is_virtual() )
				$virtualPrice += $item['line_total'];

                $price = $totalPrice - $virtualPrice; //Sets the Price that we will calculate the shipping


		$shipping_costs = 0;
		  
		if( in_array($myCountry, $localCountry) || ( $this->get_option( 'international' ) == "no") ) {

			foreach ( $shipping_rates as $rates ) {
				if ( $price >= $rates[minO] && $price <= $rates[maxO] ) {
					$shipping_costs = $rates[shippingO];
					continue;
				}
			}
		} else if( !in_array($myCountry, $localCountry)) {
			foreach ( $int_shipping_rates as $int_rates ) { 
				if ( $price >= $int_rates[minO] && $price <= $int_rates[maxO] ) {
					$shipping_costs = $int_rates[shippingO];
					continue;
				}
			}
		}
      
		$rate = array(
			'id'        => $this->id,
			'label'     => $this->title,
			'cost'      => $shipping_costs,
			'calc_tax'  => 'per_order'
		);
      
		$this->add_rate( $rate );
      
    }
    
    /**
    * validate_additional_costs_field function.
    *
    * @access public
    * @param mixed $key
    * @return void
    */
    function validate_shipping_table_field( $key ) {
		return false;
    }

    /**
    * generate_domestic_shipping_table_html function.
    *
    * @access public
    * @return void
    */
    function generate_shipping_table_html() {
		global $woocommerce;
		ob_start();
    ?>
		<tr valign="top">
			<th scope="row" class="titledesc"><?php _e( 'Domestic Rates', 'woocommerce' ); ?>:</th>
			<td class="forminp" id="<?php echo $this->id; ?>_table_rates">
				<table class="shippingrows widefat" cellspacing="0">
					<thead>
						<tr>
							<th class="check-column"><input type="checkbox"></th>
							<th><?php _e( 'Min Price', 'woocommerce' ); ?> <a class="tips" data-tip="<?php _e( 'Min price for this shipping rate.', 'woocommerce' ); ?>">[?]</a></th>
							<th><?php _e( 'Max Price', 'woocommerce' ); ?> <a class="tips" data-tip="<?php _e( 'Max price for this shipping rate.', 'woocommerce' ); ?>">[?]</a></th>
							<th><?php _e( 'Shipping Fee', 'woocommerce' ); ?> <a class="tips" data-tip="<?php _e( 'Shipping price for this price range.', 'woocommerce' ); ?>">[?]</a></th>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<th colspan="4"><a href="#" class="add button"><?php _e( '+ Add Rate', 'woocommerce' ); ?></a> <a href="#" class="remove button"><?php _e( 'Delete selected rates', 'woocommerce' ); ?></a></th>
						</tr>
					</tfoot>
					<tbody class="table_rates">
				  
					<?php
						$i = -1;
						if ( $this->table_rates ) {
							foreach ( $this->table_rates as $class => $rate ) {
								$i++;
								echo '<tr class="table_rate">
									<th class="check-column"><input type="checkbox" name="select" /></th>
									<td><input type="number" step="any" min="0" value="' . esc_attr( $rate['minO'] ) . '" name="' . esc_attr( $this->id .'_minO[' . $i . ']' ) . '" class="' . esc_attr( $this->id .'field[' . $i . ']' ) . '" placeholder="'.__( '0.00', 'woocommerce' ).'" size="4" /></td>
									<td><input type="number" step="any" min="0" value="' . esc_attr( $rate['maxO'] ) . '" name="' . esc_attr( $this->id .'_maxO[' . $i . ']' ) . '" class="' . esc_attr( $this->id .'field[' . $i . ']' ) . '" placeholder="'.__( '0.00', 'woocommerce' ).'" size="4" /></td>
									<td><input type="number" step="any" min="0" value="' . esc_attr( $rate['shippingO'] ) . '" name="' . esc_attr( $this->id .'_shippingO[' . $i . ']' ) . '" class="' . esc_attr( $this->id .'field[' . $i . ']' ) . '" placeholder="'.__( '0.00', 'woocommerce' ).'" size="4" /></td>
								</tr>';
							}
						}
					?>
					</tbody>
				</table>
          
          
				<script type="text/javascript">
					jQuery(function() {
						jQuery('#<?php echo $this->id; ?>_table_rates').on( 'click', 'a.add', function(){
							var size = jQuery('#<?php echo $this->id; ?>_table_rates tbody .table_rate').size();
							var previous = size - 1;
							jQuery('<tr class="table_rate">\
								<th class="check-column"><input type="checkbox" name="select" /></th>\
								<td><input type="number" step="any" min="0" name="<?php echo $this->id; ?>_minO[' + size + ']" class="<?php echo $this->id; ?>field[' + size + ']" placeholder="0.00" size="4" /></td>\
								<td><input type="number" step="any" min="0" name="<?php echo $this->id; ?>_maxO[' + size + ']" class="<?php echo $this->id; ?>field[' + size + ']" placeholder="0.00" size="4" /></td>\
								<td><input type="number" step="any" min="0" name="<?php echo $this->id; ?>_shippingO[' + size + ']" class="<?php echo $this->id; ?>field[' + size + ']" placeholder="0.00" size="4" /></td>\
							</tr>').appendTo('#<?php echo $this->id; ?>_table_rates table tbody');
					
							return false;
						});

						// Remove row
						jQuery('#<?php echo $this->id; ?>_table_rates').on( 'click', 'a.remove', function(){
							var answer = confirm("<?php _e( 'Delete the selected rates?', 'woocommerce' ); ?>")
								if (answer) {
									jQuery('#<?php echo $this->id; ?>_table_rates table tbody tr th.check-column input:checked').each(function(i, el){
									jQuery(el).closest('tr').remove();
								});
							}
							return false;
						});
					});
				</script>
			</td>
		</tr>



		<tr valign="top">
			<th scope="row" class="titledesc"><?php _e( 'International Rates', 'woocommerce' ); ?>:</th>
			<td class="forminp" id="<?php echo $this->id_int; ?>_int_table_rates">
				<table class="shippingrows widefat" cellspacing="0">
					<thead>
						<tr>
							<th class="check-column"><input type="checkbox"></th>
							<th><?php _e( 'Min Price', 'woocommerce' ); ?> <a class="tips" data-tip="<?php _e( 'Min price for this shipping rate.', 'woocommerce' ); ?>">[?]</a></th>
							<th><?php _e( 'Max Price', 'woocommerce' ); ?> <a class="tips" data-tip="<?php _e( 'Max price for this shipping rate.', 'woocommerce' ); ?>">[?]</a></th>
							<th><?php _e( 'Shipping Fee', 'woocommerce' ); ?> <a class="tips" data-tip="<?php _e( 'Shipping price for this price range.', 'woocommerce' ); ?>">[?]</a></th>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<th colspan="4"><a href="#" class="add button"><?php _e( '+ Add Rate', 'woocommerce' ); ?></a> <a href="#" class="remove button"><?php _e( 'Delete selected rates', 'woocommerce' ); ?></a></th>
						</tr>
					</tfoot>
					<tbody class="int_table_rates">
				  
					<?php
						$i = -1;
						if ( $this->int_table_rates ) {
							foreach ( $this->int_table_rates as $class => $int_rate ) {
								$i++;

								echo '<tr class="int_table_rate">
									<th class="check-column"><input type="checkbox" name="select" /></th>
									<td><input type="number" step="any" min="0" value="' . esc_attr( $int_rate['minO'] ) . '" name="' . esc_attr( $this->id_int .'_minO[' . $i . ']' ) . '" class="' . esc_attr( $this->id_int .'field[' . $i . ']' ) . '" placeholder="'.__( '0.00', 'woocommerce' ).'" size="4" /></td>
									<td><input type="number" step="any" min="0" value="' . esc_attr( $int_rate['maxO'] ) . '" name="' . esc_attr( $this->id_int .'_maxO[' . $i . ']' ) . '" class="' . esc_attr( $this->id_int .'field[' . $i . ']' ) . '" placeholder="'.__( '0.00', 'woocommerce' ).'" size="4" /></td>
									<td><input type="number" step="any" min="0" value="' . esc_attr( $int_rate['shippingO'] ) . '" name="' . esc_attr( $this->id_int .'_shippingO[' . $i . ']' ) . '" class="' . esc_attr( $this->id_int .'field[' . $i . ']' ) . '" placeholder="'.__( '0.00', 'woocommerce' ).'" size="4" /></td>
								</tr>';
							}
						}
					?>
					</tbody>
				</table>
				<script type="text/javascript">
					jQuery(function() {

						jQuery('#<?php echo $this->id_int; ?>_int_table_rates').on( 'click', 'a.add', function(){

							var size = jQuery('#<?php echo $this->id_int; ?>_int_table_rates tbody .int_table_rate').size();
							var previous = size - 1;

							jQuery('<tr class="int_table_rate">\
								<th class="check-column"><input type="checkbox" name="select" /></th>\
								<td><input type="number" step="any" min="0" name="<?php echo $this->id_int; ?>_minO[' + size + ']" class="<?php echo $this->id_int; ?>field[' + size + ']" placeholder="0.00" size="4" /></td>\
								<td><input type="number" step="any" min="0" name="<?php echo $this->id_int; ?>_maxO[' + size + ']" class="<?php echo $this->id_int; ?>field[' + size + ']" placeholder="0.00" size="4" /></td>\
								<td><input type="number" step="any" min="0" name="<?php echo $this->id_int; ?>_shippingO[' + size + ']" class="<?php echo $this->id_int; ?>field[' + size + ']" placeholder="0.00" size="4" /></td>\
								</tr>').appendTo('#<?php echo $this->id_int; ?>_int_table_rates table tbody');

								return false;
						});

						// Remove row
						jQuery('#<?php echo $this->id_int; ?>_int_table_rates').on( 'click', 'a.remove', function(){
							var answer = confirm("<?php _e( 'Delete the selected rates?', 'woocommerce' ); ?>")
							if (answer) {
								jQuery('#<?php echo $this->id_int; ?>_int_table_rates table tbody tr th.check-column input:checked').each(function(i, el){
									jQuery(el).closest('tr').remove();
								});
							}
							return false;
						});

					});
				</script>
			</td>
		</tr>
        <input type="hidden" id="hdn1" value="yes" />
	<?php
		return ob_get_clean();
	}


	/**
	* process_table_rates function.
	*
	* @access public
	* @return void
	*/
	function process_table_rates() {
		// Save the rates
		$table_rate_minO  = array();
		$table_rate_maxO  = array();
		$table_rate_shippingO = array();
		$table_rates = array();

		if ( isset( $_POST[ $this->id . '_minO'] ) ) $table_rate_minO = array_map( 'woocommerce_clean', $_POST[ $this->id . '_minO'] );
		if ( isset( $_POST[ $this->id . '_maxO'] ) )  $table_rate_maxO  = array_map( 'woocommerce_clean', $_POST[ $this->id . '_maxO'] );
		if ( isset( $_POST[ $this->id . '_shippingO'] ) )   $table_rate_shippingO   = array_map( 'woocommerce_clean', $_POST[ $this->id . '_shippingO'] );

		// Get max key
		$values = $table_rate_shippingO;
		ksort( $values );
		$value = end( $values );
		$key = key( $values );

		for ( $i = 0; $i <= $key; $i++ ) {
				if ( isset( $table_rate_minO[ $i ] ) && isset( $table_rate_maxO[ $i ] ) && isset( $table_rate_shippingO[ $i ] ) ) {

					$table_rate_minO[$i] = number_format($table_rate_minO[$i], 2,  '.', '');
					$table_rate_maxO[$i] = number_format($table_rate_maxO[$i], 2,  '.', '');
					$table_rate_shippingO[$i] = number_format($table_rate_shippingO[$i], 2,  '.', '');

					if( $table_rate_minO[$i] > $table_rate_maxO[$i] ) {   // Swap Min and Max Values
						$tempMin = $table_rate_minO[$i];
						$table_rate_minO[$i] = $table_rate_maxO[$i];
						$table_rate_maxO[$i] = $tempMin;
					}



					// Add to table rates array
					$table_rates[ $i ] = array(
						'minO'    => $table_rate_minO[ $i ],
						'maxO'    => $table_rate_maxO[ $i ],
						'shippingO' => $table_rate_shippingO[ $i ],
					);
				}
			}

			$orderby = "minO"; //change this to whatever key you want from the array

			$sortArray = array();

			foreach($table_rates as $the_rates){
    			foreach($the_rates as $key=>$value){
        			if(!isset($sortArray[$key])){
            			$sortArray[$key] = array();
        			}
        			$sortArray[$key][] = $value;
    			}
			}

			array_multisort($sortArray[$orderby],SORT_ASC,$table_rates);
			
			update_option( $this->table_rate_option, $table_rates );





			$int_table_rate_minO  = array();
			$int_table_rate_maxO  = array();
			$int_table_rate_shippingO = array();
			$int_table_rates = array();

			if ( isset( $_POST[ $this->id_int . '_minO'] ) ) $int_table_rate_minO = array_map( 'woocommerce_clean', $_POST[ $this->id_int . '_minO'] );
			if ( isset( $_POST[ $this->id_int . '_maxO'] ) )  $int_table_rate_maxO  = array_map( 'woocommerce_clean', $_POST[ $this->id_int . '_maxO'] );
			if ( isset( $_POST[ $this->id_int . '_shippingO'] ) )   $int_table_rate_shippingO   = array_map( 'woocommerce_clean', $_POST[ $this->id_int . '_shippingO'] );

			// Get max key
			$int_values = $int_table_rate_shippingO;
			ksort( $int_values );
			$int_value = end( $int_values );
			$int_key = key( $int_values );

			for ( $i = 0; $i <= $int_key; $i++ ) {
				if ( isset( $int_table_rate_minO[ $i ] ) && isset( $int_table_rate_maxO[ $i ] ) && isset( $int_table_rate_shippingO[ $i ] ) ) {

					$int_table_rate_minO[$i] = number_format($int_table_rate_minO[$i], 2,  '.', '');
					$int_table_rate_maxO[$i] = number_format($int_table_rate_maxO[$i], 2,  '.', '');
					$int_table_rate_shippingO[$i] = number_format($int_table_rate_shippingO[$i], 2,  '.', '');

					if( $int_table_rate_minO[$i] > $int_table_rate_maxO[$i] ) {  // Swap Min and Max Values
						$int_tempMin = $int_table_rate_minO[$i];
						$int_table_rate_minO[$i] = $int_table_rate_maxO[$i];
						$int_table_rate_maxO[$i] = $int_tempMin;
					}

					// Add to table rates array
					$int_table_rates[ $i ] = array(
						'minO'    => $int_table_rate_minO[ $i ],
						'maxO'    => $int_table_rate_maxO[ $i ],
						'shippingO' => $int_table_rate_shippingO[ $i ],
					);
				}
			}

			$sortintArray = array();

			foreach($int_table_rates as $the_rates){
    			foreach($the_rates as $key=>$value){
        			if(!isset($sortIntArray[$key])){
            			$sortIntArray[$key] = array();
        			}
        			$sortIntArray[$key][] = $value;
    			}
			}

			array_multisort($sortIntArray[$orderby],SORT_ASC,$int_table_rates);

			update_option( $this->int_table_rate_option, $int_table_rates );			

			
			$this->get_table_rates();
		}

		/**
		* save_default_costs function.
		*
		* @access public
		* @param mixed $values
		* @return void
		*/
		function save_default_costs( $fields ) {
			$default_minO = woocommerce_clean( $_POST['default_minO'] );
			$default_maxO  = woocommerce_clean( $_POST['default_maxO'] );
			$default_shippingO  = woocommerce_clean( $_POST['default_shippingO'] );

			$fields['minO'] = $default_minO;
			$fields['maxO']  = $default_maxO;
			$fields['shippingO']  = $default_shippingO;

			return $fields;
		}
	
		/**
		* get_table_rates function.
		*
		* @access public
		* @return void
		*/
		function get_table_rates() {
			$this->table_rates = array_filter( (array) get_option( $this->table_rate_option ) );
			$this->int_table_rates = array_filter( (array) get_option( $this->int_table_rate_option ) );
		}
    
	}
}