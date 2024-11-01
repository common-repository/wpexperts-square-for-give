<?php
/**
 * Plugin Name: WPExperts Square For GiveWP
 * Plugin URI: https://wpexperts.io/
 * Description: Give WP Square plugin is a WordPress plugin that allows users to donate from their give-donation form using Square payment gateway. This will help you to add Square payment option to your form created through the Give - Donation plugin. So users will be able to donate via Credit Card, this payment will process through your Square account.
 * Version: 1.3
 * Author: wpexpertsio
 * Author URI: https://wpexperts.io/
 * Developer: wpexpertsio
 * Developer URI: https://wpexperts.io/
 * Text Domain: give-square
 */
// Exit if accessed directly
if (!defined('ABSPATH'))
    exit;
if (!class_exists('GAS_Give_Square_Payment')) {
    class GAS_Give_Square_Payment {

        public function __construct() {
            /**
             * check for give wp
             */
            add_action( 'admin_enqueue_scripts', array($this, 'gas_load_admin_assets'), 999 );
            add_action( 'admin_init', array($this, 'gas_square_access_token_success') );
            add_action( 'admin_notices', array($this, 'gas_button_auth_notice') );
        }

        function gas_button_auth_notice() {

            $give_settings = get_option('give_settings', array());
            
                if(@$give_settings['square_btn_auth'] !== 'true'){

                    $class = 'notice notice-error';
                    $message = __( 'Immediately Connect your Square Account as we have introduced easy way to Auth without entering Application / Token details from Square manually.', 'gs_square' );
                    
                    printf(sprintf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) )); 
                }
        }

        public function gas_check_for_disconnect(){
                
                if ( function_exists( 'wp_verify_nonce' ) && ! wp_verify_nonce( $_GET['give_square_token_nonce'], 'disconnect_give_square' ) ) {
                    wp_die("Looks like the URL is malformed!");
                }

                if(!empty($_REQUEST['post'])){
                    
                    $post_id = sanitize_text_field($_GET['post']);
                    
                    delete_post_meta($post_id,'gs_live_token_details');
                    delete_post_meta($post_id,'gs_live_locations');

                    delete_post_meta($post_id,'square_token');
                    delete_post_meta($post_id,'square_appid');
                    delete_post_meta($post_id,'square_locationid');
                    delete_post_meta($post_id,'square_btn_auth');
                    delete_post_meta($post_id,'square_refresh_token');
                    delete_post_meta($post_id,'square_token_expires_at');
                    
                    $query_args = array (
                        'post' => sanitize_text_field($_REQUEST['post']),
                        'action' => sanitize_text_field($_REQUEST['action']),
                        'give_tab' => sanitize_text_field($_REQUEST['give_tab'])
                    );

                    $initial_page = add_query_arg($query_args, admin_url('post.php'));
                    $this->gas_revoke_square_access_token($initial_page);
                    wp_redirect($initial_page);
                    exit;

                } else { 

                    $give_settings = get_option('give_settings', array());
                    unset($give_settings['gs_live_token_details']);
                    unset($give_settings['gs_live_locations']);
                    unset($give_settings['square_token']);
                    unset($give_settings['square_appid']);
                    unset($give_settings['square_locationid']);
                    unset($give_settings['square_btn_auth']);
                    unset($give_settings['square_refresh_token']);
                    unset($give_settings['square_token_expires_at']);

                    update_option('give_settings', $give_settings);
                    
                    $query_args = array (
                        'post_type' => sanitize_text_field($_REQUEST['post_type']),
                        'page' => sanitize_text_field($_REQUEST['page']),
                        'tab' => sanitize_text_field($_REQUEST['tab']),
                        'section' => sanitize_text_field($_REQUEST['section'])
                    );

                    $initial_page = add_query_arg($query_args, admin_url('edit.php'));
                    $this->gas_revoke_square_access_token($initial_page);
                    wp_redirect($initial_page);
                    exit;
                }
        }

        public function gas_square_access_token_success(){

            if(!empty($_REQUEST['disconnect_give_square'])){

                $this->gas_check_for_disconnect();
                exit;
            }

            if(!empty($_REQUEST['access_token']) and !empty($_REQUEST['token_type']) and !empty($_REQUEST['give_square_token_nonce']) and
                $_REQUEST['token_type'] == 'bearer') {
 
                if ( function_exists( 'wp_verify_nonce' ) && ! wp_verify_nonce( $_GET['give_square_token_nonce'], 'connect_give_square' ) ) {
                    wp_die("Looks like the URL is malformed!");
                }

                $locations = $this->gas_get_location(sanitize_text_field($_REQUEST['access_token']));            
				$loc = $locations->locations;

                if(isset($_GET['post'])){
                    $post_id = sanitize_text_field($_REQUEST['post']);

                    update_post_meta($post_id,'gs_live_token_details', sanitize_text_field($_REQUEST));
                    update_post_meta($post_id,'gs_live_locations', json_encode($loc));

                    update_post_meta($post_id,'square_token', sanitize_text_field($_REQUEST['access_token']));
                    update_post_meta($post_id,'square_appid', sanitize_text_field($_REQUEST['appid']));
                    update_post_meta($post_id,'square_locationid', $loc[0]->id);
                    update_post_meta($post_id,'square_refresh_token', sanitize_text_field($_REQUEST['refresh_token']));
                    update_post_meta($post_id,'square_token_expires_at', sanitize_key($_REQUEST['expires_at']));
                    update_post_meta($post_id,'square_btn_auth', 'true');

                    $query_args = array (
                        'post' => sanitize_text_field($_REQUEST['post']),
                        'action' => sanitize_text_field($_REQUEST['action']),
                        'give_tab' => sanitize_text_field($_REQUEST['give_tab'])
                    );

                    $initial_page = add_query_arg($query_args, admin_url('post.php'));

                    wp_redirect($initial_page);
                    exit;

                }else{ 
                   
                    $give_settings = get_option('give_settings', array());
                    $give_settings['square_appid'] = sanitize_text_field($_REQUEST['appid']);
                    $give_settings['square_token'] = sanitize_text_field($_REQUEST['access_token']);
                    $give_settings['gs_live_token_details'] = json_encode(sanitize_text_field($_REQUEST));
                    $give_settings['gs_live_locations'] = json_encode($loc);
                    $give_settings['square_locationid'] = $loc[0]->id;
                    $give_settings['square_btn_auth'] = 'true';
                    $give_settings['square_refresh_token'] = sanitize_text_field($_REQUEST['refresh_token']);
                    $give_settings['square_token_expires_at'] = sanitize_key($_REQUEST['expires_at']);
    
                    update_option('give_settings', $give_settings);

                    $query_args = array (
                        
                        'post_type' => sanitize_text_field($_REQUEST['post_type']),
                        'page' => sanitize_text_field($_REQUEST['page']),
                        'tab' => sanitize_text_field($_REQUEST['tab']),
                        'section' => sanitize_text_field($_REQUEST['section'])
                    );

                    $initial_page = add_query_arg($query_args, admin_url('edit.php'));
                    wp_redirect($initial_page);
                    exit;
                }
            }
        }

        public function gas_revoke_square_access_token($redirect_url){

            $oauth_connect_url = GAS_MIDDLE_SERVER_URL;

            $args_renew = array(

                'body' => array(
            
                    'site_url'    => $redirect_url,
                    'oauth_version' => 2,
                    'request_type' => 'revoke_token'
                ),
                'timeout' => 45

            );

            $oauth_response = wp_remote_post( $oauth_connect_url, $args_renew );
            wp_redirect( $redirect_url);
            exit;

        }

        public function gas_get_location($token){
            $url = esc_url('https://connect.squareup.com/v2/locations');
            $headers = array(
                'Authorization' => 'Bearer '.$token, // Use verbose mode in cURL to determine the format you want for this header
                'Content-Type'  => 'application/json;',
                'token'  => $token,
            );
            $method = "GET";
            $args = array('');
            $response = $this->gas_wp_remote_wcsrs($url,$args,$method,$headers);
            return $response;
        }

        public function gas_wp_remote_wcsrs($url,$args,$method,$headers){
            $token = $headers['token'];
            unset($headers['token']);
            $request = array(
                'headers' => $headers,
                'method'  => $method,
            );

            if ( $method == 'GET' && ! empty( $args ) && is_array( $args ) ) {
                $url = add_query_arg( $args, $url );
            } else {
                $request['body'] = json_encode( $args );
            }

            $response = wp_remote_request( $url, $request );
            $decoded_response = json_decode( wp_remote_retrieve_body( $response ) );

            return $decoded_response;
        } 

        public function gas_square_plugin_dependencies() {
			
            if (!class_exists('Give') || !$this->gas_is_allowed_countries_for_give() || !$this->gas_is_allowed_currencies_for_give()) {
                
                add_action('admin_notices', array($this, 'gas_admin_notices'));

            } else {

                define("GAS_GIVE_SQUARE_PLUGIN_PATH", plugin_dir_path(__FILE__));
                define("GAS_GIVE_SQUARE_PLUGIN_URL", plugin_dir_url(__FILE__));

                $plugin_data = get_plugin_data( __FILE__, $markup = true, $translate = true );
                define("GAS_GIVE_SQUARE_PLUGIN_NAME", sanitize_title($plugin_data['Name']));
                define("GAS_GIVE_SQUARE_APP_NAME", 'GiveIntegration');
                define("GAS_MIDDLE_SERVER_URL", esc_url('https://connect.apiexperts.io')); 
                              
                    require_once( GAS_GIVE_SQUARE_PLUGIN_PATH . 'lib/square-sdk/autoload.php' );
                    /**
                     * give square class
                      */ 
					 
                    require_once( GAS_GIVE_SQUARE_PLUGIN_PATH . 'includes/class-give-square.php' );
					
					 new GAS_Give_Square_fonly();
					add_action('deactivated_plugin', array($this, 'gas_detect_plugin_deactivation_give_square'));
            }

        }

        function gas_load_admin_assets() {

            
            wp_enqueue_style( 'gs_style', plugins_url('assets/style/style.css',__FILE__ ));
            wp_enqueue_script( 'gs_script_helper', plugins_url('assets/js/helpers.js',__FILE__ ));
            wp_enqueue_script( 'gs_oauth_btn_functions', plugins_url('assets/js/oauth_button_functions.js',__FILE__ ));
            wp_enqueue_script( 'gs_script', plugins_url('assets/js/scripts.js',__FILE__ ));


            $give_settings = get_option('give_settings', array());
            $is_connected = null;
            $token = null;
            if(isset($give_settings['square_btn_auth'])){

                $is_connected = $give_settings['square_btn_auth'];

            }

            if(isset($give_settings['square_token'])){

                $token = $give_settings['square_token'];

            }
   
            $array_opts = array (
                'connected' => $is_connected,
                'token' => $token
            );

            wp_localize_script( 'gs_script', 'gs_script', $array_opts);

        }

        public function gas_is_allowed_countries_for_give() {

            if (in_array('give/give.php', apply_filters('active_plugins', get_option('active_plugins')))) {
                if (
                        'US' == give_get_country() ||
                        'CA' == give_get_country() ||
                        'JP' == give_get_country() ||
                        'AU' == give_get_country() ||
                        'IE' == give_get_country() ||
                        'FR' == give_get_country() ||
                        'GB' == give_get_country()
                ) {
                    return true;
                } else {
                    return false;
                }
            }
        }

        public function gas_detect_plugin_deactivation_give_square() {
            if (in_array('woosquare/woocommerce-square-integration.php', apply_filters('active_plugins', get_option('active_plugins')))) {
                delete_option('fs_accounts');
            }
        }

        public function gas_is_allowed_currencies_for_give() {
            if (in_array('give/give.php', apply_filters('active_plugins', get_option('active_plugins')))) {
                if (
                        'USD' == give_get_currency() ||
                        'CAD' == give_get_currency() ||
                        'JPY' == give_get_currency() ||
                        'AUD' == give_get_currency() ||
                        'EUR' == give_get_currency() ||
                        'GBP' == give_get_currency()
                ) {
                    return true;
                } else {
                    return false;
                }
            }
        } 

        public function gas_admin_notices() {
            $class = 'notice notice-error';

            if (!class_exists('Give')) {
                $messages[] = esc_html(__('Give Square Payment requires Give WP to be installed and active.', 'give-square'));
            }

            if ($this->gas_is_allowed_currencies_for_give() == false) {
                $messages[] = esc_html(__('To enable Give Square Payment. Give Currency must be USD,CAD,AUD,JPY,EUR,GBP', 'give-square'));
            }

            if ($this->gas_is_allowed_countries_for_give() == false) {
                $messages[] = sprintf(__('To enable Give Square Payment requires that the <a href="%s">base country/region</a> is the United States, United Kingdom, Japan, Ireland, Canada or Australia.', 'give-square'), admin_url('edit.php?post_type=give_forms&page=give-settings&tab=general'));
            }

            if (!empty($messages) and is_array($messages)) {
                foreach ($messages as $message) {
                    printf(sprintf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), $message));
                }
            }
        }

    }

    
}

add_action('plugins_loaded', 'load_payment_class',999);

function load_payment_class() {

	$instance = new GAS_Give_Square_Payment();
	// global $qu_fs;
	// if (qu_fs()->can_use_premium_code()) {
		// add_action('init', array($instance,'gas_square_plugin_dependencies'),0);
		$instance->gas_square_plugin_dependencies(); 
	// }

}           