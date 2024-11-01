<?php
/**
 * Plugin Name: WC First Data Payment Gateway
 * Plugin URI: https://virtina.com/extensions/woocommerce-extensions/
 * Description: WC First Data Payment Gateway addon adds a payment option for customers to pay with their Credit Cards.
 * Version: 1.3
 * Author: Virtina | Jinesh.P.V
 * Author URI: https://virtina.com/
 * Requires at least: 4.5
 * Tested up to: 4.7.3
**/
add_action( 'plugins_loaded', 'fdpg_woocommerce_plugin_init', 0 );

function fdpg_woocommerce_plugin_init() {
	
	if ( !class_exists( 'WC_Payment_Gateway' ) ) 
  		return;

	/**
	* FirstData Payment Gateway class
	*/
	class VTWC_FirstData extends WC_Payment_Gateway {
		
		protected $msg = array();
	  
		public function __construct() {
		
			$this->id               	= 'firstdata';
			$this->method_title    	 = __( 'First Data', 'vtwc_fistdata' );
			$this->icon             	= WP_PLUGIN_URL . "/" . plugin_basename( dirname( __FILE__ ) ) . '/images/first_data.png';
			$this->has_fields       	= false;
			$this->init_form_fields();
			$this->init_settings();
			$this->title            	= $this->settings['title'];
			$this->description      	= $this->settings['description'];
			$this->gateway_id       	= $this->settings['gateway_id'];
			$this->mode             	= $this->settings['working_mode'];
			$this->debug             	= $this->settings['debug_mode'];
			$this->terminal_password  	= $this->settings['terminal_password'];
			$this->hmackey  			= $this->settings['hmackey'];
			$this->keyid  				= $this->settings['keyid'];
			$this->success_message  	= $this->settings['success_message'];
			$this->failed_message   	= $this->settings['failed_message'];
			$this->liveurl          	= 'https://api.globalgatewaye4.firstdata.com/transaction/v12';
			$this->testurl          	= 'https://api.demo.globalgatewaye4.firstdata.com/transaction/v12';
			$this->msg['message']   	= "";
			$this->msg['class']     	= "";
												
			// Lets check for SSL
			add_action( 'admin_notices', array( $this,	'do_ssl_check' ) );
		
			if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
				 add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
			} else {
				add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
			}
		
			add_action( 'woocommerce_receipt_first_data', array( &$this, 'vtwc_receipt_page' ) );
			add_action( 'woocommerce_credit_card_form_fields', array( &$this, 'vtwc_woocommerce_credit_card_form_fields' ) );
		}
		
		public function init_form_fields() {
		
			$this->form_fields = array(
			'enabled'      => array(
				  'title'        => __( 'Enable/Disable', 'vtwc_fistdata' ),
				  'type'         => 'checkbox',
				  'label'        => __( 'Enable First Data Payment Module.', 'vtwc_fistdata' ),
				  'default'      => 'no' ),
			'title'        => array(
				  'title'        => __( 'Title:', 'vtwc_fistdata' ),
				  'type'         => 'text',
				  'description'  => __( 'This controls the title which the user sees during checkout.', 'vtwc_fistdata' ),
				  'default'      => __( 'First Data', 'vtwc_fistdata' ) ),
			'description'  => array(
				  'title'        => __( 'Description:', 'vtwc_fistdata' ),
				  'type'         => 'textarea',
				  'description'  => __( 'This controls the description which the user sees during checkout.', 'vtwc_fistdata' ),
				  'default'      => __( 'Pay securely by Credit or Debit Card through First Data Secure Servers.', 'vtwc_fistdata' ) ),
			'gateway_id'     => array(
				  'title'        => __( 'Gateway ID', 'vtwc_fistdata' ),
				  'type'         => 'text',
				  'description'  => __( 'This is API Gateway ID ') ),
			'terminal_password' => array(
				  'title'        => __( 'Terminal Password', 'vtwc_fistdata' ),
				  'type'         => 'text',
				  'description'  =>  __( 'API Terminal Password', 'vtwc_fistdata' ) ),
			'hmackey' => array(
				  'title'        => __( 'HMAC Key', 'vtwc_fistdata' ),
				  'type'         => 'text',
				  'description'  =>  __( 'API HMAC Key', 'vtwc_fistdata' ) ),
			'keyid' => array(
				  'title'        => __( 'Key ID', 'vtwc_fistdata' ),
				  'type'         => 'text',
				  'description'  =>  __( 'API Key ID', 'vtwc_fistdata' ) ),
			'success_message' => array(
				  'title'        => __( 'Transaction Success Message', 'vtwc_fistdata' ),
				  'type'         => 'textarea',
				  'description'=>  __( 'Message to be displayed on successful transaction.', 'vtwc_fistdata' ),
				  'default'      => __( 'Your payment has been procssed successfully.', 'vtwc_fistdata' ) ),
			'failed_message'  => array(
				  'title'        => __( 'Transaction Failed Message', 'vtwc_fistdata' ),
				  'type'         => 'textarea',
				  'description'  =>  __( 'Message to be displayed on failed transaction.', 'vtwc_fistdata' ),
				  'default'      => __( 'Your transaction has been declined.', 'vtwc_fistdata' ) ),
			'working_mode'    => array(
				  'title'        => __( 'API Mode'),
				  'type'         => 'select',
				  'options'      => array( 'false' => 'Live Mode', 'true' => 'Test/Sandbox Mode' ),
				  'description'  => "Live/Test Mode" ),
			'debug_mode'    => array(
				  'title'        => __( 'Debug Mode'),
				  'type'         => 'select',
				  'options'      => array( 'true' => 'Yes', 'false' => 'No' ),
				  'description'  => "Debug Mode" )
			 );
		}
		  
		/**
		* Admin Panel Options
		* - Options for bits like 'title' and availability on a country-by-country basis
		**/
		
		public function admin_options() {
		
			echo '<h3>'.__( 'WC First Data Payment Gateway', 'vtwc_fistdata' ).'</h3>';
			echo '<p>'.__( 'First Data is most popular payment gateway for online payment processing' ).'</p>';
			echo '<table class="form-table">';
			$this->generate_settings_html();
			echo '</table>';
		}
		
		// Check if we are forcing SSL on checkout pages

		public function do_ssl_check() {
			if( $this->enabled == "yes" ) {
				if( get_option( 'woocommerce_force_ssl_checkout' ) == "no" ) {
					echo "<div class=\"error\"><p>". sprintf( __( "<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>" ), $this->method_title, admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) ."</p></div>";	
				}
			}	
		}
	  
		/**
		*  There are no payment fields for First Data, but want to show the description if set.
		**/
		
		public function payment_fields() {
			
			$month = $year = '';
			
			if ( $this->description ) 
				echo wpautop( wptexturize( $this->description ) );

			echo '<style>.woocommerce-checkout #payment div.payment_box label {font-family: inherit; font-weight: 300; text-transform: uppercase; display: block;} .woocommerce-checkout #payment div.payment_box input { border: 1px solid #ccc;border-radius: 3px; -o-border-radius: 3px; -ms-border-radius: 3px; -moz-border-radius: 3px; border-radius: 3px; font-size: 15px; line-height: 45px; padding: 0 10px; width: 100%;}</style>';	
			
			$this->credit_card_form();
		}
		
		/*
		* Filter hook for credit card form
		*/
		  
		public function vtwc_woocommerce_credit_card_form_fields( $default_fields, $form_id = null ) {

			$default_args = array(
				'fields_have_names' => true, // Some gateways like stripe don't need names as the form is tokenized
			);
			$args = wp_parse_args( $default_fields, apply_filters( 'woocommerce_credit_card_form_args', $default_args, $this->id ) );

			return $default_fields;
		}

		/*
		* Basic Card validation
		*/
	  
		public function validate_fields() {
			
			global $woocommerce; 
			
			if ( !$this->is_empty_credit_card( $_POST[esc_attr( $this->id ) . '-card-number'] ) ) 
				wc_add_notice( '<strong>Credit Card Number</strong> ' . __( 'is a required field.', 'vtwc_fistdata' ), 'error' );
				
			elseif ( !$this->is_valid_credit_card( $_POST[esc_attr( $this->id ) . '-card-number'] ) ) 
				wc_add_notice( '<strong>Credit Card Number</strong> ' . __( 'is not a valid credit card number.', 'vtwc_fistdata' ), 'error' );
				
			if ( !$this->is_empty_expire_date( $_POST[esc_attr( $this->id ) . '-card-expiry'] ) )    
				wc_add_notice( '<strong>Card Expiry Date</strong> ' . __( 'is a required field.', 'vtwc_fistdata' ), 'error' );
				
			elseif ( !$this->is_valid_expire_date( $_POST[esc_attr( $this->id ) . '-card-expiry'] ) ) 
				wc_add_notice( '<strong>Card Expiry Date</strong> ' . __( 'is not a valid expiry date.', 'vtwc_fistdata' ), 'error' );
				
			if ( !$this->is_empty_ccv_nmber( $_POST[esc_attr( $this->id ) . '-card-cvc'] ) ) 
				wc_add_notice( '<strong>CCV Number</strong> ' . __( 'is a required field.', 'vtwc_fistdata' ), 'error' );
		}
  
		/*
		* Check whether the card number number is empty
		*/

		private function is_empty_credit_card( $credit_card ) {
		    
			if ( empty( $credit_card ) )
				return false;
				
			return true; 	
		}
		/*
		* Check whether the card number number is valid
		*/

		private function is_valid_credit_card( $credit_card ) {
		    
			$credit_card = preg_replace( '/(?<=\d)\s+(?=\d)/', '', trim( $credit_card ) );
			
			$number = preg_replace( '/[^0-9]+/', '', $credit_card );
			$strlen = strlen( $number );
			$sum    = 0;
			
			if ( $strlen < 13 )
				return false; 
			
			for ( $i=0; $i < $strlen; $i++ ) {
				$digit = substr( $number, $strlen - $i - 1, 1 );
				
				if( $i % 2 == 1 ) {
					
					$sub_total = $digit * 2;
					
					if( $sub_total > 9 ) {
						$sub_total = 1 + ( $sub_total - 10 );
					}
				} else {
					$sub_total = $digit;
				}
				$sum += $sub_total;
			}
			
			if ( $sum > 0 AND $sum % 10 == 0 )
				return true; 
			
			return false; 
		}
		
		/*
		* Check expiry date is empty
		*/
		
		private function is_empty_expire_date( $ccexp_expiry ) {
			
			$ccexp_expiry = str_replace( ' / ', '', $ccexp_expiry );
			
			if ( is_numeric( $ccexp_expiry ) && ( strlen( $ccexp_expiry ) == 4 ) ){
				return true;
			}

			return false;
		}
		
		/*
		* Check expiry date is valid
		*/
		
		private function is_valid_expire_date( $ccexp_expiry ) {
			
			$month = $year = '';
			$month = substr( $ccexp_expiry , 0, 2 );
			$year = substr( $ccexp_expiry , 5, 7 );
			$year = '20'. $year;
			
			if( $month > 12 ) {
				return false;
			} 
			
			if ( date( "Y-m-d", strtotime( $year . "-" . $month . "-01" ) ) > date( "Y-m-d" ) ) {
				return true;
			}

			return false;
		}
		
		/*
		* Check whether the ccv number is empty
		*/
		
		private function is_empty_ccv_nmber( $ccv_number ) {
			
			$length = strlen( $ccv_number );
			
			return is_numeric( $ccv_number ) AND $length > 2 AND $length < 5;
		}
		
		/**
		* Receipt Page
		**/
		
		public function vtwc_receipt_page( $order ) {
			
			echo '<p>'.__( 'Thank you for your order.', 'vtwc_fistdata' ).'</p>';
		}
		
		/**
		* Process the payment and return the result
		**/
		
		function process_payment( $order_id ) {

			global $woocommerce;
			require( dirname( __FILE__ ) . '/' . 'vt-first-data-class.php' );
			$order = new WC_Order( $order_id );
			
			if( $this->mode == 'true' ) {
				$process_url = $this->testurl;
			} else {
				$process_url = $this->liveurl;
			}
			
			define( 'VTWC_API_URL', $process_url );
			define( 'VTWC_GATEWAY_ID', $this->gateway_id );
			define( 'VTWC_TERMINAL_PASSWORD', $this->terminal_password );
			define( 'VTWC_HMAC_KEY', $this->hmackey );
			define( 'VTWC_KEY_ID', $this->keyid );
			define( 'VTWC_DEBUG', $this->debug ); 

			$credit_card = ( !empty( $_POST[esc_attr( $this->id ) . '-card-number'] ) ) ? strip_tags( str_replace( "'", "`", strip_tags( $_POST[esc_attr( $this->id ) . '-card-number'] ) ) ) : '';
			$credit_card = preg_replace( '/(?<=\d)\s+(?=\d)/', '', trim( $credit_card ) );
			$ccexp_expiry = ( !empty( $_POST[esc_attr( $this->id ) . '-card-expiry'] ) ) ? strip_tags( str_replace( "'", "`", strip_tags( $_POST[esc_attr( $this->id ) . '-card-expiry'] ) ) ) : '';
			$cc_expiry = str_replace( ' / ', '', $ccexp_expiry );
			$ccexp_year = ( !empty( $_POST[esc_attr( $this->id ) . '-card-cvc'] ) ) ? strip_tags( str_replace( "'", "`", strip_tags( $_POST[esc_attr( $this->id ) . '-card-cvc'] ) ) ) : '';
			$transact_id = mktime() . "-" . rand( 1, 999 );
			$amount = number_format( $order->order_total, 2 );
			
			$request = array(
								'transaction_type' => "00",
								'amount' => $amount,
								'cc_expiry' => trim( $cc_expiry ),
								'cc_number' => trim( $credit_card ),
								'cardholder_name' => trim( $_POST['billing_first_name'] . ' ' . $_POST['billing_last_name'] ),
								'reference_no' => ''
							);
			
            $firstdata = new FirstData(); 

            if( $firstdata->request( $request ) ) {
				
				if( $firstdata->response->transaction_approved == '1' ){
				
					if ($order->status != 'completed') {
						$order->payment_complete();
						$woocommerce->cart->empty_cart();
						
						$order->add_order_note( $this->success_message . ' Transaction ID: ' . $firstdata->response->authorization_num );
						unset( $_SESSION['order_awaiting_payment'] );
					}
					
					return array(
									'result'   => 'success',
									'redirect'  => $order->get_checkout_order_received_url()
								);
				} else {
				
					$order->add_order_note( $this->failed_message . $firstdata->response->exact_resp_code );
					wc_add_notice( __( '(Transaction Error) ' . $firstdata->response->exact_resp_code, 'vtwc_fistdata' ) );
				}
			} else {
        
				$order->add_order_note( $this->failed_message );
				$order->update_status( 'failed' );
				
				wc_add_notice( __( '(Transaction Error) Error processing payment.', 'vtwc_fistdata' ) ); 
			}
		}
	}

	/**
	* Add this Gateway to WooCommerce
	**/
	
	function woocommerce_add_firstdata_gateway( $methods ) {
		$methods[] = 'VTWC_FirstData';
		return $methods;
	}

	add_filter( 'woocommerce_payment_gateways', 'woocommerce_add_firstdata_gateway' );
	
	function vtwc_woocommerce_addon_activate() {

		if( !function_exists( 'curl_exec' ) ) {
			wp_die( '<pre>This plugin requires PHP CURL library installled in order to be activated </pre>' );
		}
	}
	register_activation_hook( __FILE__, 'vtwc_woocommerce_addon_activate' );
	
	/*Plugin Settings Link*/
	function vtwc_woocommerce_settings_link( $links ) {
		
		$settings_link = '<a href="admin.php?page=wc-settings&tab=checkout&section=firstdata">' . __( 'Settings' ) . '</a>';
		array_push( $links, $settings_link );
		return $links;
	}
	$plugin = plugin_basename( __FILE__ );
	add_filter( "plugin_action_links_$plugin", 'vtwc_woocommerce_settings_link' );
}