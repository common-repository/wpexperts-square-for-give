<?php
// Exit if accessed directly
if (!defined('ABSPATH'))
    exit;

if (!class_exists('GAS_Give_Square_fonly')) {

    class GAS_Give_Square_fonly {
        /*
         * frontend current form id
         */

        private $form_id = 0;
        public $api_client;

        /**
         * Class Constructor
         */  
        public function __construct() {
            //add sqaure payment in admin
            add_filter('give_payment_gateways', array($this, 'gas_give_square_payment_gateways'));
            //show square settings in form
          
            //Internal Ajax Call from connect to square button
            add_filter('give_metabox_form_data_settings', array($this, 'gas_add_square_product_settings_tab'));
            //show general square settings 
            add_filter('give_get_sections_gateways', array($this, 'gas_add_square_settings_tab'));
            add_filter('give_get_settings_gateways', array($this, 'gas_add_square_settings_fields'));
            //square payment form
            //add square script and style
            add_action('give_before_cc_fields', array($this, 'gas_square_sqpayment_script'));
            add_action('wp_enqueue_scripts', array($this, 'gas_square_scripts'));
           
            add_action('give_square_cc_form', array($this, 'gas_give_square_cc_form'));
            
            //process payment
            add_action('give_gateway_square', array($this, 'gas_give_gateway_square_process'));
            // if no keys are updated in general settings
            if (!$this->gas_check_keys())
                add_action('give_payment_mode_select', array($this, 'gas_frontend_form_id_func'), 3);
            if (!$this->gas_check_keys()) {
                add_action('admin_notices', array($this, 'gas_no_keys_admin_notices'));
            }
            

            add_action('wp_ajax_my_action', array($this, 'gas_prepare_connection_call'));
            add_action('wp_ajax_my_dc_action', array($this, 'gas_disconnect_square'));
            add_action('wp_ajax_oauth_status_update', array($this, 'gas_post_oauth_status_update'));
            add_action('wp_ajax_get_form_id_give_square', array($this, 'get_form_id_give_square_handler'));
			
			add_action("wp_ajax_gas_get_square_keys", array($this, 'gas_get_square_keysact'));
			add_action("wp_ajax_nopriv_gas_get_square_keys", array($this, 'gas_get_square_keysact'));

            add_action('wp_ajax_gas_location_dropdown', array($this, 'gas_location_dropdown'));
            $this->gas_general_settings_token_renewal();
            
        }
		
		public function gas_get_square_keysact(){
			
			if($_POST['action'] == 'gas_get_square_keys'){
				$gas_get_square_keys = $this->gas_get_square_keys($_POST['action']);
				$gas_get_square_keys['css_styles'] = $this->gas_get_input_styles();
				
				$result = json_encode($gas_get_square_keys);
				echo $result;
			}
			die();
			
		}

 
        public function gas_prepare_connection_call(){
            /* Creating Redirect URL with sliced parameters */
            $url_identifiers = $_REQUEST;
            $url_identifiers['action'] = $url_identifiers['url_action'];
            $url_identifiers['oauth_version'] = 2;
            $admin_url = sanitize_text_field($_REQUEST['admin_url']);
            $redirect_url = add_query_arg( $url_identifiers, admin_url($admin_url) );
            $redirect_url = wp_nonce_url( $redirect_url, 'connect_give_square', 'give_square_token_nonce' );
            /* Creating Redirect URL with sliced parameters */

            /* Preparing payload and sending to middle server */
            $middle_server_payload = array(
                'redirect' => urlencode($redirect_url),
                'scope' => 'MERCHANT_PROFILE_READ PAYMENTS_READ PAYMENTS_WRITE INVENTORY_WRITE CUSTOMERS_READ CUSTOMERS_WRITE',
                'post' => sanitize_text_field($_REQUEST['post']),
                'plug' =>  GAS_GIVE_SQUARE_PLUGIN_NAME,
                'app_name' => GAS_GIVE_SQUARE_APP_NAME,
                'oauth_version' => 2,
                'request_type' => 'authorization' 
            );
 
            $middle_server_url = add_query_arg($middle_server_payload, GAS_MIDDLE_SERVER_URL);
            /* Preparing payload and sending to middle server */
            
                die($middle_server_url);
            $query_arg = array(
                'app_name'    => GAS_GIVE_SQUARE_APP_NAME,
                'disconnect_give_square' => 1,
                'plug' => GAS_GIVE_SQUARE_PLUGIN_NAME 
            );
            $query_arg = array_merge($url_identifiers,$query_arg);
            $disconnect_url = wp_nonce_url(admin_url($admin_url), 'disconnect_give_square', 'give_square_token_nonce' );
            $disconnect_url = add_query_arg($query_arg, $disconnect_url);

            wp_die($middle_server_url);

        } 

        function gas_check_give_square_expiry($expires_at){

            if(isset($expires_at) && !empty($expires_at)) {

                $date_time = explode('t', strtolower($expires_at));
                $date_time[1] = str_replace("z", "", $date_time[1]);
                $expires_at = strtotime($date_time[0] . ' ' . $date_time[1]);
                $today = strtotime("now");

                if ($today >= $expires_at) {

                    return "expired";

                } else {

                    return "active";

                }

            }
        }

        function gas_renew_give_square_token($expires_at, $refresh_access_token) {

           $expiry_status = $this->gas_check_give_square_expiry($expires_at);

           if($expiry_status == 'expired'){

                $oauth_connect_url = GAS_MIDDLE_SERVER_URL;

                $args_renew = array(

                    'body' => array( 
                        'refresh_token' => $refresh_access_token,
                        'app_name' => GAS_GIVE_SQUARE_APP_NAME,
                        'oauth_version' => 2,
                        'request_type' => 'renew_token'
                    ),
                    'timeout' => 45

                );

                $oauth_response = wp_remote_post( $oauth_connect_url, $args_renew );
                return json_decode($oauth_response['body']);
           }
            
        }


        function gas_general_settings_token_renewal(){

            $give_settings = get_option('give_settings', array());
        
            if(isset($give_settings['square_token_expires_at']) && isset($give_settings['square_refresh_token'])){


                $expires_at = $give_settings['square_token_expires_at'];
                $refresh_access_token = $give_settings['square_refresh_token'];
                       
                $decoded_response = $this->gas_renew_give_square_token($expires_at, $refresh_access_token);
               
                if(isset($decoded_response)){
    
                    $give_settings['square_token_expires_at'] = $decoded_response->expires_at;
                    $give_settings['square_refresh_token'] = $decoded_response->refresh_token;
                    $give_settings['square_token'] = $decoded_response->access_token;
                    
                    update_option('give_settings', $give_settings);
            
                }
            }
        }

        public function gas_location_dropdown(){

            if( $_REQUEST['post']){
                global $wpdb;
				$var = new stdClass;
                $wp_giveform_meta = $wpdb->get_results("SELECT meta_key, meta_value FROM wp_give_formmeta WHERE form_id = ". $_REQUEST['post']);
					
                    foreach($wp_giveform_meta as $record){

                        if($record->meta_key == 'gs_live_locations'){
							$var->gs_live_locations = $record->meta_value;
                        }
						
						if($record->meta_key == 'square_locationid'){
							$var->square_locationid = $record->meta_value;
						}
                    } 
					
					wp_die(json_encode($var));
				
            }else{

                $give_settings = get_option('give_settings', array());
				
                $locations = json_encode($give_settings);
				
                wp_die($locations);
            }
                            
        }

        public function gas_disconnect_square(){

            $url_identifiers = $_REQUEST;
            $admin_url = sanitize_text_field($_REQUEST['admin_url']);
            $url_identifiers['action'] = sanitize_text_field($_REQUEST['url_action']);
            $query_arg = array(
                'disconnect_give_square' => 1,
                'app_name'    => GAS_GIVE_SQUARE_APP_NAME                
            );
            $query_arg = array_merge($url_identifiers,$query_arg);
            $disconnect_url = wp_nonce_url(admin_url($admin_url), 'disconnect_give_square', 'give_square_token_nonce' );
            $disconnect_url = add_query_arg($query_arg, $disconnect_url);

            die($disconnect_url);

        }

        public function gas_give_square_payment_gateways($gateways) {
            $gateways['square'] = array(
                'admin_label' => esc_html(__('Square', 'give-square')),
                'checkout_label' => esc_html(__('Square', 'give-square')),
            );

            return $gateways;
        }

        public function get_form_id_give_square_handler(){
			
			if($_REQUEST['action'] == 'get_form_id_give_square' && !empty($_REQUEST['formid'])){
				set_transient( 'give_form_id', $_REQUEST['formid'] , 900 );
				die();
            }
		}
        public function gas_post_oauth_status_update(){

            if($_REQUEST['url_action'] == 'edit' && !empty($_REQUEST['post']) && $_REQUEST['admin_url'] == 'post.php'){
                 
                $post_id = sanitize_text_field($_REQUEST['post']);
                $square_token = get_post_meta($post_id, 'square_token', true);
                $square_btn_auth = get_post_meta($post_id, 'square_btn_auth', true);
                $oauth_preferences = get_post_meta($post_id, 'oauth_preferences', true);

                $data['square_token'] = $square_token !== '' ? $square_token : false;
                $data['square_btn_auth'] = $square_btn_auth !== '' ? $square_btn_auth : false;
                $data['oauth_preferences'] = $oauth_preferences !== '' ? $oauth_preferences : false;
    
                wp_die(json_encode($data));  
            }
            
        }

        public function gas_square_settings_fields() {
            $settings = array(
                array(
                    'name' => esc_html(__('Mode', 'give-square')),
                    'desc' => sprintf(__('Get square account keys from <a href="%s" target="_blank">here</a>.', 'give-square'), esc_url('https://connect.squareup.com/apps')),
                    'id' => 'square_mode',
                    'type' => 'radio_inline',
                    'default' => 'test',
                    'options' => array(
                        'test' => esc_html(__('Test', 'give-square')),
                        'live' => esc_html(__('Live', 'give-square'))
                    )
                ),
                array(
                    'name' => esc_html(__('Billing Details', 'give-square')),
                    'desc' => esc_html(__('This option will enable the billing details section for Square which requires the donor\'s address to complete the donation.', 'give-square')),
                    'id' => 'square_billing_details',
                    'type' => 'radio_inline',
                    'default' => 'disabled',
                    'options' => array(
                        'enabled' => esc_html(__('Enabled', 'give-square')),
                        'disabled' => esc_html(__('Disabled', 'give-square')),
                    )
                ),
                array(
                    'name' => esc_html(__('Test Application ID', 'give-square')),
                    'id' => 'square_test_appid',
                    'type' => 'text'
                ),
                array(
                    'name' => esc_html(__('Test Token', 'give-square')),
                    'id' => 'square_test_token',
                    'type' => 'text'
                ),
                array(
                    'name' => esc_html(__('Test Location ID', 'give-square')),
                    'id' => 'square_test_locationid',
                    'type' => 'text'
                ),
                array(
                    'name' => __('', 'give-square'),
                    'id' => 'square_connect_button',
                    'type' => 'text'
                ),
                array(
                    'name' => esc_html(__('Live Location ID', 'give-square')),
                    'id' => 'square_locationid',
                    'type' => 'select'
                )
            );
            return $settings;
        }

        public function gas_square_product_settings_fields() {

            $settings = array(
                array(
                    'name' => esc_html(__('Mode', 'give-square')),
                    'desc' => sprintf(__('Get square account keys from <a href="%s" target="_blank">here</a>.', 'give-square'), esc_url('https://connect.squareup.com/apps')),
                    'id' => 'square_mode',
                    'type' => 'radio_inline',
                    'default' => 'test',
                    'options' => array(
                        'test' => esc_html(__('Test', 'give-square')),
                        'live' => esc_html(__('Live', 'give-square'))
                    )
                ),
                array(
                    'name' => esc_html(__('Billing Details', 'give-square')),
                    'desc' => esc_html(__('This option will enable the billing details section for Square which requires the donor\'s address to complete the donation.', 'give-square')),
                    'id' => 'square_billing_details',
                    'type' => 'radio_inline',
                    'default' => 'disabled',
                    'options' => array(
                        'enabled' => esc_html(__('Enabled', 'give-square')),
                        'disabled' => esc_html(__('Disabled', 'give-square')),
                    )
                ),
                array(
                    'name' => esc_html(__('OAuth Preference', 'give-square')),
                    'desc' => esc_html(__('Select individual setting to use seperate Square Application with this form', 'give-square')),
                    'id' => 'oauth_preferences',
                    'class' => 'oauth_preferences',
                    'type' => 'radio_inline',
                    'default' => 'custom',
                    'options' => array(
                        'general' => esc_html(__('General', 'give-square')),
                        'custom' => esc_html(__('Individual', 'give-square')),
                    )
                ),
                array(
                    'name' => esc_html(__('Test Application ID', 'give-square')),
                    'id' => 'square_test_appid',
                    'type' => 'text'
                ),
                array(
                    'name' => esc_html(__('Test Token', 'give-square')),
                    'id' => 'square_test_token',
                    'type' => 'text'
                ),
                array(
                    'name' => esc_html(__('Test Location ID', 'give-square')),
                    'id' => 'square_test_locationid',
                    'type' => 'text'
                ),
                array(
                    'name' => __('', 'give-square'),
                    'id' => 'square_connect_button',
                    'type' => 'text'
                ),
                array(
                    'name' => esc_html(__('Live Location ID', 'give-square')),
                    'id' => 'square_locationid',
                    'type' => 'select'
                )
            );


            $no_show_auth_pref_settings = array(
                array(
                    'name' => esc_html(__('Mode', 'give-square')),
                    'desc' => sprintf(__('Get square account keys from <a href="%s" target="_blank">here</a>.', 'give-square'), esc_url('https://connect.squareup.com/apps')),
                    'id' => 'square_mode',
                    'type' => 'radio_inline',
                    'default' => 'test',
                    'options' => array(
                        'test' => esc_html(__('Test', 'give-square')),
                        'live' => esc_html(__('Live', 'give-square'))
                    )
                ),
                array(
                    'name' => esc_html(__('Billing Details', 'give-square')),
                    'desc' => esc_html(__('This option will enable the billing details section for Square which requires the donor\'s address to complete the donation.', 'give-square')),
                    'id' => 'square_billing_details',
                    'type' => 'radio_inline',
                    'default' => 'disabled',
                    'options' => array(
                        'enabled' => esc_html(__('Enabled', 'give-square')),
                        'disabled' => esc_html(__('Disabled', 'give-square')),
                    )
                ),
                array(
                    'name' => esc_html(__('Test Application ID', 'give-square')),
                    'id' => 'square_test_appid',
                    'type' => 'text'
                ),
                array(
                    'name' => esc_html(__('Test Token', 'give-square')),
                    'id' => 'square_test_token',
                    'type' => 'text'
                ),
                array(
                    'name' => esc_html(__('Test Location ID', 'give-square')),
                    'id' => 'square_test_locationid',
                    'type' => 'text'
                ),
                array(
                    'name' => __('', 'give-square'),
                    'id' => 'square_connect_button',
                    'type' => 'text'
                ),
                array(
                    'name' => esc_html(__('Live Location ID', 'give-square')),
                    'id' => 'square_locationid',
                    'type' => 'select'
                )
            );

            $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $current_url = explode('/', $current_url);
            $current_url = explode('?', $current_url[4]);

            if($current_url[0] == 'edit.php' || $current_url[0] == 'post.php'){
                return $settings;
           
            }else{

                return $no_show_auth_pref_settings;
            }
        }

        public function gas_add_square_product_settings_tab($settings) {
            if (give_is_gateway_active('square')) {
                $settings['square_options'] = array(
                    'id' => 'square_options',
                    'title' => esc_html(__('Square', 'give-square')),
                    'icon-html' => '<span class="give-icon give-icon-purse"></span>',
                    'fields' => $this->gas_square_product_settings_fields()
                );
            }
            return $settings;
        }

        public function gas_add_square_settings_tab($sections) {
            if (give_is_gateway_active('square'))
                $sections['square'] = esc_html(__('Square', 'give-square'));
            return $sections;
        }

        public function gas_add_square_settings_fields($settings) {
            $current_section = give_get_current_setting_section();
            if ($current_section == 'square') {
                $settings = $this->gas_square_settings_fields();

                array_unshift($settings, array(
                    'type' => 'title',
                    'id' => 'give_title_gateway_settings_4',
                ));

                array_push($settings, array(
                    'type' => 'sectionend',
                    'id' => 'give_title_gateway_settings_4',
                ));
            }
            return $settings;
        }

        public function gas_custom_form_token_renewal($form_id){

            $refresh_access_token = get_post_meta($form_id, 'square_refresh_token', true);
            $expires_at = get_post_meta($form_id, 'square_token_expires_at', true);

            $decoded_response = $this->gas_renew_give_square_token($expires_at, $refresh_access_token);
            if(isset($decoded_response)){
                if(isset($decoded_response->access_token) && isset($decoded_response->expires_at) && isset($decoded_response->refresh_token)) {

                    update_post_meta($form_id, 'square_token', $decoded_response->access_token);
                    update_post_meta($form_id, 'square_token_expires_at', $decoded_response->expires_at);
                    update_post_meta($form_id, 'square_refresh_token', $decoded_response->refresh_token);
                }
            }
        } 


        public function gas_get_square_keys($form_id) {
            $this->gas_custom_form_token_renewal($form_id);
            $square_keys = array(
                'token' => '',
                'appid' => '',
                'locid' => '',
                'billing_details' => ''
            );

            //get form keys
            $mode = get_post_meta($form_id, 'square_mode', true);
            $oauth_preference = get_post_meta($form_id, 'oauth_preferences', true);

            if($oauth_preference == 'custom'){
                
                if ($mode == 'test') {
                    $square_keys ['token'] = get_post_meta($form_id, 'square_test_token', true);
                    $square_keys ['appid'] = get_post_meta($form_id, 'square_test_appid', true);
                    $square_keys ['locid'] = get_post_meta($form_id, 'square_test_locationid', true);
                } else {
                    $square_keys ['token'] = get_post_meta($form_id, 'square_token', true);
                    $square_keys ['appid'] = get_post_meta($form_id, 'square_appid', true);
                    $square_keys ['locid'] = get_post_meta($form_id, 'square_locationid', true);
                }
				$square_keys['mode'] = get_post_meta($form_id, 'square_mode', true);

                $square_keys['billing_details'] = get_post_meta($form_id, 'square_billing_details', true);
            } 

            if ($square_keys['token'] == '' || $square_keys['appid'] == '' || $square_keys['locid'] == '') {
                //get general keys
                $give_settings = get_option('give_settings', array());
                if (isset($give_settings['square_mode'])) {
                    $mode = $give_settings['square_mode'];
                    if ($mode == 'test') {
                        $square_keys ['token'] = $give_settings['square_test_token'];
                        $square_keys ['appid'] = $give_settings['square_test_appid'];
                        $square_keys ['locid'] = $give_settings['square_test_locationid'];
                    } else {
                        $square_keys ['token'] = $give_settings['square_token'];
                        $square_keys ['appid'] = $give_settings['square_appid'];
                        $square_keys ['locid'] = $give_settings['square_locationid'];
                    }
					$square_keys['mode'] = $give_settings['square_mode'];
                    if (isset($square_keys['billing_details']) && $square_keys['billing_details'] == '') {
                        $square_keys['billing_details'] = $give_settings['square_billing_details'];
                    }
                }
            } 

            return $square_keys;
        }

        public function gas_general_Settings_keys($square_keys = null) {
            //get general keys
            $give_settings = get_option('give_settings', array());
            if (isset($give_settings['square_mode'])) {
                $mode = $give_settings['square_mode'];

                if ($mode == 'test') {
                    $square_keys ['token'] = isset($give_settings['square_test_token']);
                    $square_keys ['appid'] = isset($give_settings['square_test_appid']);
                    $square_keys ['locid'] = isset($give_settings['square_test_locationid']);
                } else {
                    $square_keys ['token'] = isset($give_settings['square_token']);
                    $square_keys ['appid'] = isset($give_settings['square_appid']);
                    $square_keys ['locid'] = isset($give_settings['square_locationid']);
                }

                if (isset($square_keys['billing_details']) && $square_keys['billing_details'] == '') {
                    $square_keys['billing_details'] = $give_settings['square_billing_details'];
                }
            }
            return $square_keys;
        }

        public function gas_check_keys($form_id = null) {
            if (null != $form_id)
                $keys = $this->gas_get_square_keys($form_id);
            else
                $keys = $this->gas_general_Settings_keys();

            if (isset($keys['token']) && !empty($keys['token']) && isset($keys['appid']) && !empty($keys['appid']) && isset($keys['locid']) && !empty($keys['locid']))
                return true;
            else
                return false;
        }

        public function gas_give_square_cc_form($form_id) {
            $square_keys = $this->gas_get_square_keys($form_id);
			
			 $field_css = $this->gas_get_input_styles();
            require GAS_GIVE_SQUARE_PLUGIN_PATH . 'includes/views/give-square-form.php';
            
              ?>

              <input type="hidden" class="application_id_<?=esc_attr($form_id)?>" value="<?=esc_attr($square_keys['appid'])?>" />
              <input type="hidden" class="location_id_<?=esc_attr($form_id)?>" value="<?=esc_attr($square_keys['locid'])?>" />
              <input type="hidden" class="css_styles_<?=esc_attr($form_id)?>" value='<?=@esc_attr($field_css)?>' />
              
            <?php
            
             
        }

        public function gas_square_scripts() {
			$handle = 'give-square-paymentformadminjs';
			$list = 'enqueued';
			
			
			
            //check if square active
            if (give_is_gateway_active('square') and !wp_script_is( $handle, $list )) {
				
				
				
				wp_register_script('give-square-sqpaymentform',  GAS_GIVE_SQUARE_PLUGIN_URL . 'assets/js/sqpaymentform.js', '', '', true);
				wp_localize_script( 'give-square-sqpaymentform', 'sqpaymentform',
					array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) 
				);
				
				wp_enqueue_script('give-square-sqpaymentform');
				
				
                wp_enqueue_style('give-square-style', GAS_GIVE_SQUARE_PLUGIN_URL . 'assets/style/style.css');

                $give_settings = get_option('give_settings', array());
                
                if (isset($give_settings['square_mode'])) {
                    $mode = $give_settings['square_mode'];
                }
				$form_id = get_transient('give_form_id');
				 
				
				$square_keys = $this->gas_get_square_keys($form_id);
				
				if( is_plugin_active('woocommerce/woocommerce.php') && is_plugin_active('woocommerce-square/woocommerce-square.php') ) {
					if(!is_checkout()){
						if ($square_keys['mode'] == 'test') {
							wp_register_script('give-square-paymentform', 'https://sandbox.web.squarecdn.com/v1/square.js', '', '', true);
							wp_enqueue_script('give-square-paymentform');
						} else {
							wp_register_script('give-square-paymentform', 'https://web.squarecdn.com/v1/square.js', '', '', true);
							wp_enqueue_script('give-square-paymentform');
						}
					}
				}
				else {
					if ($square_keys['mode'] == 'test') {
						wp_register_script('give-square-paymentform', 'https://sandbox.web.squarecdn.com/v1/square.js', '', '', true);
						wp_enqueue_script('give-square-paymentform');
					} else {
						wp_register_script('give-square-paymentform', 'https://web.squarecdn.com/v1/square.js', '', '', true);
						wp_enqueue_script('give-square-paymentform');
					}
				}
				
             wp_register_script('give-square-paymentformadminjs',  GAS_GIVE_SQUARE_PLUGIN_URL . 'assets/js/sqpaymentload.js', '', '', true);
             wp_localize_script( 'give-square-paymentformadminjs', 'give_square_keys', array(
            'location_id' => $square_keys['locid'],
            'appid' => $square_keys['appid'],
            'form_id' => $form_id,
            ));
            wp_enqueue_script('give-square-paymentformadminjs');
			
			
			delete_transient('give_form_id');	
				
            }
        }

       public function gas_square_sqpayment_script($form_id) {
		   
			set_transient( 'give_form_id', $form_id, 900 );
            ob_start();
            $square_keys = $this->gas_get_square_keys($form_id);
            $square_keys['form_id'] = esc_attr($form_id);
            $square_keys['gas_get_input_styles'] = $this->gas_get_input_styles();
            return ob_get_contents();
        }

        public function gas_get_input_styles() {
            $styles = array(
                array(
                    'fontSize' => '16px',
                    'padding' => '0.5em',
                    'backgroundColor' => '#fdfdfd'
                )
            );

            return wp_json_encode($styles);
        }

        public function gas_give_gateway_square_process($purchase_data) {
			
            try {
                $payment_data = array(
                    'price' => $purchase_data['price'],
                    'give_form_title' => $purchase_data['post_data']['give-form-title'],
                    'give_form_id' => intval($purchase_data['post_data']['give-form-id']),
                    'give_price_id' => isset($purchase_data['post_data']['give-price-id']) ? $purchase_data['post_data']['give-price-id'] : '',
                    'date' => $purchase_data['date'],
                    'user_email' => $purchase_data['user_email'],
                    'purchase_key' => $purchase_data['purchase_key'],
                    'currency' => give_get_currency($purchase_data['post_data']['give-form-id'], $purchase_data),
                    'user_info' => $purchase_data['user_info'],
                    'status' => 'pending',
                    'gateway' => 'square'
                );
                // record the payment
                $payment_id = give_insert_payment($payment_data);
				
				
                if (empty($payment_id)) {
                    // Record the error.
                    give_record_gateway_error(__('Payment Error', 'give-square'), sprintf(__('Payment creation failed before square charge. Payment data: %s', 'give-square'), json_encode($payment_data)), $payment);
                    // Problems? Send back.
                    // give_send_back_to_checkout('?payment-mode=' . $purchase_data['post_data']['give-gateway']);
                }

                $square_keys = $this->gas_get_square_keys($purchase_data['post_data']['give-form-id']);
			
				if($square_keys['mode'] == 'live') {
					$host = "https://connect.squareup.com";
				} else {
					$host = "https://connect.squareupsandbox.com";
				}
				$api_config = new \SquareConnect\Configuration();
				$api_config->setHost($host);
				$api_config->setAccessToken($square_keys['token']);
				$this->api_client = new \SquareConnect\ApiClient($api_config);
				
                //get square nonce
                // $card_nonce = isset($purchase_data['post_data']['give_square_nonce']) ? $purchase_data['post_data']['give_square_nonce'] : null;
				$card_nonce = isset($_POST['card-nonce']) ? $_POST['card-nonce'] : null;

                $payments_api = new \SquareConnect\Api\PaymentsApi($this->api_client);
                $location_id = $square_keys['locid'];
                $nonce = $card_nonce;
                $amount = round($purchase_data['price'], 2) * 100;
                $body = new \SquareConnect\Model\CreatePaymentRequest();
                $amountMoney = new \SquareConnect\Model\Money();
                $amountMoney->setAmount($amount);
                $amountMoney->setCurrency(give_get_currency($purchase_data['post_data']['give-form-id'], $purchase_data));
                $body->setSourceId($nonce);
                $body->setAmountMoney($amountMoney);
                $body->setLocationId($location_id);
                $body->setIdempotencyKey((string) time());


                $transaction = $payments_api->createPayment($body);

                $transactionData = json_decode($transaction, true);

                
                if (isset($transactionData['payment']['id'])) {
                    $transactionId = $transactionData['payment']['id'];

                    give_update_payment_status($payment_id, 'publish');
                    give_insert_payment_note($payment_id, sprintf(__('Square Transaction ID: %s', 'give'), $transactionId));
                    // give_offline_send_admin_notice($payment_id);
                    // give_offline_send_donor_instructions($payment_id);
                    give_send_to_success_page();
                }

            } catch (\SquareConnect\ApiException $ex) {
                $errors = $ex->getResponseBody()->errors;
                $message = '';
                foreach ($errors as $error) {
                    $message = $error->detail;
                    if (isset($error->field))
                        $message = $error->field . ' - ' . $error->detail;
                    }
                    
                    
                give_record_gateway_error(esc_html_e('Square Error', 'give-square'), $message);
                
                give_set_error('square-error', $message);
                give_insert_payment_note($payment_id, sprintf(__('square-error: %s', 'give'), $message));
                // if errors are present, send the user back to the donation form so they can be corrected
                give_send_back_to_checkout('?payment-mode=' . $purchase_data['post_data']['give-gateway']);
            }
        }

         public function gas_no_keys_admin_notices() {
            
            if(!empty($give_forms->posts) ) {
            	foreach ($give_forms->posts as $key => $form_id) {
        		    if(!$this->gas_check_keys($form_id->ID)){
            			$class = 'notice notice-error';
            			$message = __('Please add Square keys on ', 'give-square');
            			$oauth_preferences = get_post_meta($form_id->ID, 'oauth_preferences', true);
            			
            			if($oauth_preferences == 'custom'){
            			    
            				$link = sprintf('<a href="' . admin_url() . 'post.php?post='.$form_id->ID.'&action=edit&give_tab=square_options">') . esc_html(__('Square setting page', 'give-square')) . '</a>';
            			} else {
            				$link = sprintf('<a href="' . admin_url() . 'edit.php?post_type=give_forms&page=give-settings&tab=gateways&section=square">') . esc_html(__('Square setting page', 'give-square')) . '</a>';
            			}
            			printf(sprintf('<div class="%1$s"><p>%2$s %3$s</p></div>', esc_attr($class), esc_html($message) , $link));
            			return;
        		    }
    	        }
            }
        }

        public function gas_frontend_form_id_func($form_id) {
			
            //if (!is_admin() && !$this->gas_check_keys($form_id))
              //  add_filter('give_enabled_payment_gateways', array($this, 'gas_filter_gateways_new'), 3, 2);
        }

        public function gas_filter_gateways_new($gateway_list, $form_id) {
            if (isset($gateway_list['square']))
                unset($gateway_list['square']);
            return $form_id;
        }
    }
}