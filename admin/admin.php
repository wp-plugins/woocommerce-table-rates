<?php

if( !defined( 'ABSPATH' ) ) exit;


function rp_ptr_create_post_type() {
    $show_in_menu = current_user_can( 'manage_woocommerce' ) ? 'woocommerce' : true;

    register_post_type( 'rp_ptr_shipping',
        array(
            'labels' => array(
            'name'                => __( 'Table Rates', 'wpr-ptr-shipping' ),
            'singular_name'       => __( 'Table Rate', 'wpr-ptr-shipping' ),
            'menu_name'           => _x( 'Table Rate Shipping', 'Admin menu name', 'wpr-ptr-shipping' ),
            'add_new'             => __( 'Add Table Rate', 'wpr-ptr-shipping' ),
            'add_new_item'        => __( 'Add New Table', 'wpr-ptr-shipping' ),
            'edit'                => __( 'Edit', 'wpr-ptr-shipping' ),
            'edit_item'           => __( 'Edit Table', 'wpr-ptr-shipping' ),
            'new_item'            => __( 'New Table', 'wpr-ptr-shipping' ),
            'view'                => __( 'View Table Rates', 'wpr-ptr-shipping' ),
            'view_item'           => __( 'View Table Rate', 'wpr-ptr-shipping' ),
            'search_items'        => __( 'Search Table Rates', 'wpr-ptr-shipping' ),
            'not_found'           => __( 'No Table Rates found', 'wpr-ptr-shipping' ),
            'not_found_in_trash'  => __( 'No Table Rates found in trash', 'wpr-ptr-shipping' ),
            'parent'              => __( 'Parent Table Rate', 'wpr-ptr-shipping' )
        ),
        'public'        => true,
        'has_archive'   => false,
        'show_in_menu'  => $show_in_menu,
        'hierarchical'  => false,
        'supports'      => array( 'title', 'comments' )
    ));
}


function rp_ptr_add_columns( $columns ) {
	$new_columns = ( is_array( $columns ) ) ? $columns : array();
	unset( $new_columns['title'] );
	unset( $new_columns['date'] );
	unset( $new_columns['comments'] );

	//all of your columns will be added before the actions column on the Giftcard page

	$new_columns["title"]		= __( 'Title', 'wpr-ptr-shipping' );
	$new_columns["countries"]	= __( 'Countries', 'wpr-ptr-shipping' );
	$new_columns['date']		= __( 'Creation Date', 'wpr-ptr-shipping' );

	return $new_columns;
}

/**
 */
function rp_ptr_custom_columns( $column ) {
	global $post, $woocommerce;

	$theCountry = get_post_meta( $post->ID, '_rp_ptr_country', true );

	if ( $theCountry == "unselected" )
		$theCountry = 'All Unselected Countries';

	switch ( $column ) {

		case "countries" :
			echo '<div><strong>' . esc_html( $theCountry ) . '</strong><br />';
		break;
	}
}


/**
 * Sets up the new meta box for the creation of a gift card.
 * Removes the other three Meta Boxes that are not needed.
 *
 */
function rp_ptr_meta_boxes() {
	global $post;

	add_meta_box(
		'rp-ptr-woocommerce-data',
		__( 'Shipping Data', 'wpr-ptr-shipping' ),
		'rp_ptr_meta_box',
		'rp_ptr_shipping',
		'normal',
		'high'
	);

	remove_meta_box( 'woothemes-settings', 'rp_ptr_shipping' , 'normal' );
	remove_meta_box( 'commentstatusdiv', 'rp_ptr_shipping' , 'normal' );
	remove_meta_box( 'slugdiv', 'rp_ptr_shipping' , 'normal' );
	remove_meta_box( 'commentsdiv', 'rp_ptr_shipping' , 'normal' );
	remove_meta_box( 'postimagediv','rp_ptr_shipping','side' );

}


function rp_ptr_meta_box( $post ) {
	global $woocommerce;

	wp_nonce_field( 'woocommerce_save_data', 'woocommerce_meta_nonce' );

	?>

	<style type="text/css">
		#edit-slug-box, #minor-publishing-actions { display:none }
	</style>

	<div id="giftcard_options" class="panel woocommerce_options_panel">
	<?php

	$rp_ptr_shippingData 	= get_post_meta( $post->ID, '_rp_ptr_shippingData', true );
	$countryCode	= get_post_meta( $post->ID, '_rp_ptr_country', true );
	$complete = '';

	$allCountries = WC()->countries->get_shipping_countries();

	echo 'Select the country for this table:';

	woocommerce_wp_select(
		array(
			'id'		=> 'rp_ptr_country',
			'title'		=> __( 'Specific Countries', 'wpr-ptr-shipping' ),
			'type'		=> 'multiselect',
			'class'		=> 'chosen_country',
			'css'		=> 'width: 450px; float: left;',
			'default'	=> '',
			'value'		=> $countryCode,
			'options'	=> $allCountries,
			'custom_attributes' => array(
					'data-placeholder' => __( 'Select some countries', 'woocommerce' )
			)
		)
	);
	
	echo '<div class="clear"></div>';

	if ( empty( $countryCode ) || ( $countryCode == "unselected" ) )
		$complete = ' selected=\"selected\"';

	echo '<script>
			jQuery(document).ready(function($){
				$("select.chosen_country").prepend("<option value=\"unselected\" ' . $complete . '>';
					_e( 'Unselected Countries', 'wpr-ptr-shipping' );
				echo '</option>");
			});
		</script>';

	$shippingInformation = new rp_ptr_shipping();
	
	$whichMethod = apply_filters( 'rp_ptr_calc_method', $shippingInformation->get_option( 'method' ));
	
	?>
	<table id="rp_table_rates" class="shippingrows widefat" cellspacing="0">
		<thead>
			<tr>
				<th class="check-column"><input type="checkbox"></th>
				<th><?php if ( $whichMethod == 'price' ) { _e( 'Min Price', 'wpr-ptr-shipping' ); } else { _e( 'Min Weight', 'wpr-ptr-shipping' ); } ?></th>
				<th><?php if ( $whichMethod == 'price' ) { _e( 'Max Price', 'wpr-ptr-shipping' ); } else { _e( 'Max Weight', 'wpr-ptr-shipping' ); } ?></th>
				<th><?php _e( 'Shipping Fee', 'wpr-ptr-shipping' ); ?></th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th colspan="4"><a href="#" class="add button" style="margin-left: 24px"><?php _e( '+ Add Rate', 'wpr-ptr-shipping' ); ?></a> <a href="#" class="remove button"><?php _e( 'Delete selected rates', 'wpr-ptr-shipping' ); ?></a></th>
			</tr>
		</tfoot>
		<tbody class="table_rates">
		<?php

		$i = -1;
		if ( $rp_ptr_shippingData ) {
			foreach ( $rp_ptr_shippingData as $rate ) {
				$i++;
				echo '<tr class="table_rate">
					<th class="check-column"><input type="checkbox" name="select" /></th>
					<td><input type="number" step="any" min="0" value="' . (float) esc_attr( $rate["minO"] ) . '" name="rp_ptr_shippingData[' . $i . '][minO]" style="width: 90%" class="rp_ptr_shippingData[' . $i . '][minO]" placeholder="'.__( '0.00', 'wpr-ptr-shipping' ).'" size="4" /></td>
					<td><input type="number" step="any" min="0" value="' . (float) esc_attr( $rate["maxO"] ) . '" name="rp_ptr_shippingData[' . $i . '][maxO]" style="width: 90%" class="rp_ptr_shippingData[' . $i . '][maxO]" placeholder="'.__( '0.00', 'wpr-ptr-shipping' ).'" size="4" /></td>
					<td><input type="number" step="any" min="0" value="' . (float) esc_attr( $rate["rate"] ) . '" name="rp_ptr_shippingData[' . $i . '][rate]" style="width: 90%" class="rp_ptr_shippingData[' . $i . '][rate]" placeholder="'.__( '0.00', 'wpr-ptr-shipping' ).'" size="4" /></td>
				</tr>';
			}
		}

		?>
		</tbody>
	</table>
	
	<script type="text/javascript">
		jQuery(function() {
			jQuery('#rp_table_rates').on( 'click', 'a.add', function(){
				var size = jQuery('#rp_table_rates tbody .table_rate').size();
				var previous = size - 1;
				jQuery('<tr class="table_rate">\
					<th class="check-column"><input type="checkbox" name="select" /></th>\
					<td><input type="number" step="any" min="0" name="rp_ptr_shippingData[' + size + '][minO]" style="width: 90%" class="rp_ptr_shippingData[' + size + '][minO]" placeholder="0.00" size="4" /></td>\
					<td><input type="number" step="any" min="0" name="rp_ptr_shippingData[' + size + '][maxO]" style="width: 90%" class="rp_ptr_shippingData[' + size + '][maxO]" placeholder="0.00" size="4" /></td>\
					<td><input type="number" step="any" min="0" name="rp_ptr_shippingData[' + size + '][rate]" style="width: 90%" class="rp_ptr_shippingData[' + size + '][rate]" placeholder="0.00" size="4" /></td>\
				</tr>').appendTo('table tbody');

				return false;
			});

			// Remove row
			jQuery('#rp_table_rates').on( 'click', 'a.remove', function(){
				var answer = confirm("<?php _e( 'Delete the selected rates?', 'wpr-ptr-shipping' ); ?>")
				if (answer) {
					jQuery('table tbody tr th.check-column input:checked').each(function(i, el){
						jQuery(el).closest('tr').remove();
					});
				}
				return false;
			});
		});
	</script>

	<?php do_action( 'rp_ptr_options' ); ?>

	</div>
	<?php
}


function rp_ptr_save_table( $post_id, $post ) {
	$rp_ptr_country = ( isset( $_REQUEST['rp_ptr_country'] ) ) ? $_REQUEST['rp_ptr_country'] : '0';
	$rp_ptr_table_data = isset( $_REQUEST['rp_ptr_shippingData'] ) ? $_REQUEST['rp_ptr_shippingData'] : array();

	update_post_meta( $post->ID, '_rp_ptr_country', $rp_ptr_country );
	if ( !empty( $rp_ptr_table_data ) ) {
		update_post_meta( $post->ID, '_rp_ptr_shippingData', $rp_ptr_table_data );
	}

	return $post->ID;
}

