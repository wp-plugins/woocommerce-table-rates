<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// this is the URL our updater / license checker pings. This should be the URL of the site with EDD installed
if ( ! defined( 'WPR_STORE_URL' ) )
	define( 'WPR_STORE_URL', 'http://wp-ronin.com' ); // you should use your own CONSTANT name, and be sure to replace it throughout this file





// the name of your product. This should match the download name in EDD exactly
define( 'WPR_PTR_ITEM_NAME', 'WooCommerce – Premium Table Rate Shipping' ); // you should use your own CONSTANT name, and be sure to replace it throughout this file

if( !class_exists( 'EDD_SL_Plugin_Updater' ) ) {
	// load our custom updater
	include( dirname( __FILE__ ) . '/includes/EDD_SL_Plugin_Updater.php' );
}

function wpr_ptr_plugin_updater() {

	// retrieve our license key from the DB
	$license_key = trim( get_option( 'wpr_license_key["ptr"]' ) );

	// setup the updater
	$edd_updater = new EDD_SL_Plugin_Updater( WPR_STORE_URL, __FILE__, array( 
			'version' 	=> '1.0', 				// current version number
			'license' 	=> $license_key, 		// license key (used get_option above to retrieve from DB)
			'item_name' => WPR_PTR_ITEM_NAME, 	// name of this plugin
			'author' 	=> 'WP Ronin'  // author of this plugin
		)
	);

}
add_action( 'admin_init', 'wpr_ptr_plugin_updater' );




if ( !function_exists( 'wpr_license_menu' ) ) {
	function wpr_license_menu() {
		add_plugins_page( 'WP Ronin Plugin License', 'WP Ronin Plugin License', 'manage_options', 'wpr-licenses', 'wpr_license_page' );
	}
	add_action('admin_menu', 'wpr_license_menu');
}

if ( !function_exists( 'wpr_license_page' ) ) {
	function wpr_license_page() {

		?>
		<div class="wrap">
			<h2><?php _e('WP Ronin Plugin License Options'); ?></h2>
			<form method="post" action="options.php">
				<?php wp_nonce_field( 'wpr_nonce', 'wpr_nonce' ); ?>
				<table class="form-table">
					<tbody>

						<?php do_action( 'wpr_add_license_field' ); ?>

					</tbody>
				</table>	
				<?php submit_button(); ?>
			
			</form>
		<?php
	}
}

function wpr_ptr_option() {
	$license 	= get_option( 'wpr_license_key' );
	$status 	= get_option( 'wpr_ptr_license_status' );

	settings_fields('wpr_license');
	?>
	<tr valign="top">	
		<th scope="row" valign="top">
			<?php _e('WooCommerce – Premium Table Rate Shipping'); ?>
		</th>
		<td>
			<input id="wpr_license_key[ptr]" name="wpr_license_key[ptr]" type="text" class="regular-text" placeholder="Enter License Number" value="<?php esc_attr_e( $license["ptr"] ); ?>" <?php if( false !== $license && $status !== false && $status == 'valid' ) { ?> style="border: 1px solid green;" <?php } ?> />
			<input id="wpr_license[ptr]" name="wpr_license[ptr]" type="hidden" value="wpr_ptr_license_status" />
			<?php 
			if( "" !== $license["ptr"] ) {  
				if( $status !== false && $status == 'valid' ) { 
			?>
					<?php //wp_nonce_field( 'wpr_nonce', 'wpr_nonce' ); ?>
					<input type="submit" class="button-secondary" name="edd_ptr_license_deactivate" value="<?php _e('Deactivate License'); ?>"/>
				<?php } else {
					//wp_nonce_field( 'wpr_nonce', 'wpr_nonce' ); ?>
					<input type="submit" class="button-secondary" name="edd_ptr_license_activate" value="<?php _e('Activate License'); ?>"/>
				<?php } ?>
			<?php } ?>
		</td>
	</tr>
	<?php 


}
add_action('wpr_add_license_field', 'wpr_ptr_option' );


if ( !function_exists( 'wpr_register_option' ) ) {
	function wpr_register_option() {
		// creates our settings in the options table
		register_setting('wpr_license', 'wpr_license_key', 'wpr_sanitize_license' );
		register_setting('wpr_license', 'wpr_license' );
	}
	add_action('admin_init', 'wpr_register_option');
}

if ( !function_exists( 'wpr_sanitize_license' ) ) {
	function wpr_sanitize_license( $new ) {

		$old = get_option( 'wpr_license_key' );
		$status = get_option( 'wpr_license' );

		foreach($new as $key => $value) {
			
			if( $old[$key] && $old[$key] != $new[$key] ) {
				
				delete_option( $status[$key] ); // new license has been entered, so must reactivate
			}
		}
		
		return $new;
	}
}

function wpr_ptr_activate_license() {

	// listen for our activate button to be clicked
	if( isset( $_POST['edd_ptr_license_activate'] ) ) {

		// run a quick security check 
	 	if( ! check_admin_referer( 'wpr_nonce', 'wpr_nonce' ) ) 	
			return; // get out if we didn't click the Activate button

		// retrieve the license from the database
		$license = get_option( 'wpr_license_key' );
	
		$license = trim( $license["ptr"] );
		

		// data to send in our API request
		$api_params = array( 
			'edd_action'=> 'activate_license', 
			'license' 	=> $license, 
			'item_name' => urlencode( WPR_PTR_ITEM_NAME ) // the name of our product in EDD
		);
		
		// Call the custom API.
		$response = wp_remote_get( add_query_arg( $api_params, WPR_STORE_URL ), array( 'timeout' => 15, 'sslverify' => false ) );

		// make sure the response came back okay
		if ( is_wp_error( $response ) )
			return false;

		// decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );
		
		// $license_data->license will be either "active" or "inactive"

		update_option( 'wpr_ptr_license_status', $license_data->license );

	}
}
add_action('admin_init', 'wpr_ptr_activate_license');


function wpr_ptr_deactivate_license() {

	// listen for our activate button to be clicked
	if( isset( $_POST['edd_ptr_license_deactivate'] ) ) {

		// run a quick security check 
	 	if( ! check_admin_referer( 'wpr_nonce', 'wpr_nonce' ) ) 	
			return; // get out if we didn't click the Activate button

		// retrieve the license from the database
		$license = get_option( 'wpr_license_key' );
	
		$license = trim( $license["ptr"] );
			

		// data to send in our API request
		$api_params = array( 
			'edd_action'=> 'deactivate_license', 
			'license' 	=> $license, 
			'item_name' => urlencode( WPR_PTR_ITEM_NAME ) // the name of our product in EDD
		);
		
		// Call the custom API.
		$response = wp_remote_get( add_query_arg( $api_params, WPR_STORE_URL ), array( 'timeout' => 15, 'sslverify' => false ) );

		// make sure the response came back okay
		if ( is_wp_error( $response ) )
			return false;

		// decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );
		
		// $license_data->license will be either "deactivated" or "failed"
		if( $license_data->license == 'deactivated' )
			delete_option( 'wpr_ptr_license_status' );

	}
}
add_action('admin_init', 'wpr_ptr_deactivate_license');


function wpr_ptr_check_license() {

	global $wp_version;

	$license = get_option( 'wpr_license_key' );
	
	$license = trim( $license["ptr"] );
		
	$api_params = array( 
		'edd_action' => 'check_license', 
		'license' => $license, 
		'item_name' => urlencode( WPR_PTR_ITEM_NAME ) 
	);

	// Call the custom API.
	$response = wp_remote_get( add_query_arg( $api_params, WPR_STORE_URL ), array( 'timeout' => 15, 'sslverify' => false ) );


	if ( is_wp_error( $response ) )
		return false;

	$license_data = json_decode( wp_remote_retrieve_body( $response ) );

	if( $license_data->license == 'valid' ) {
		echo 'valid'; exit;
		// this license is still valid
	} else {
		echo 'invalid'; exit;
		// this license is no longer valid
	}
}

/**
 * Load the Text Domain for i18n
 *
 * @return void
 * @access public
 */

function wp_ptr_loaddomain() {
	load_plugin_textdomain( 'wp-ptr-shipping', false, dirname( plugin_basename( __FILE__ ) ) . "/languages" );
}
add_action( 'init', 'wp_ptr_loaddomain' );
