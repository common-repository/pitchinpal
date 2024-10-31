<?php
/*
Plugin Name: PitchinPal
Plugin URI: http://www.pitchinpal.com
Description: PitchinPal Payment gateway for woocommerce
Version: 1.0
Author: PitchinPal
Author URI: http://www.pitchinpal.com
*/

include( 'includes/buy.php' );
function WC_Gateway_Pitchinpal_activate(){
    add_option('cfn_auto','true');
    add_option('cfn_type',array('simple','variable'));
    add_option('cfn_category','false');

    return true;
}
register_activation_hook( __FILE__, 'WC_Gateway_Pitchinpal_activate' );

add_action('plugins_loaded', 'pitchinpal_init', 0);
function pitchinpal_init(){
  if(!class_exists('WC_Payment_Gateway')) return;

	class WC_Gateway_Pitchinpal extends WC_Payment_Gateway {

		/** @var boolean Whether or not logging is enabled */
		public static $log_enabled = false;

		/** @var WC_Logger Logger instance */
		public static $log = false;

		/**
		 * Constructor for the gateway.
		 */
		public function __construct() {
			$this->id                 = 'pitchinpal';
			$this->has_fields         = true;
			$this->order_button_text  = __( 'Pay with PitchinPal™ FriendFunds™', 'woocommerce' );
			$this->method_title       = __( 'PitchinPal™', 'woocommerce' );
			$this->method_description = sprintf( __( 'Have your customers pay with PitchinPal™(or by other means). Check the %ssystem status%s page for more details.', 'woocommerce' ), '<a href="' . admin_url( 'admin.php?page=wc-status' ) . '">', '</a>' );
			$this->supports           = array(
				'products'
			);

			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();

			// Define user set variables
            $this->uri_crowdfund = "https://pitchinpal.com/cart/data/";
            $this->uri_pitchinpal = 'https://pitchinpal.com/payment/process';

            // $this->uri_crowdfund = "http://pipdev.pitchinpal.com/cart/data/";
            // $this->uri_pitchinpal = 'http://pipdev.pitchinpal.com/payment/process';

            $this->title          = $this->get_option( 'title' );
			$this->description    = $this->get_option( 'description' );
			$this->debug          = 'yes' === $this->get_option( 'debug', 'no' );
			$this->store_identifier = $this->get_option( 'store_identifier' );
            $this->pitchinpal_url = $this->get_option( 'pitchinpal_url' );
			$this->notify_url        	= WC()->api_request_url( 'WC_Gateway_Pitchinpal' );
            $this->enable_credit_card = 0;
            self::$log_enabled    = $this->debug;
            add_action( 'woocommerce_api_wc_gateway_'.$this->id, array( $this, 'check_pitchinpal_response' ) );
			add_action( 'woocommerce_receipt_pitchinpal', array( $this, 'receipt_page' ) );
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            add_action( 'woocommerce_email_order_table_payment_method', array( $this, 'wc_add_payment_type_to_emails' ), 10, 3 );
            // Display the admin notification
		}

		/**
		 * Logging method
		 * @param  string $message
		 */
		public static function log( $message ) {
			if ( self::$log_enabled ) {
				if ( empty( self::$log ) ) {
					self::$log = new WC_Logger();
				}
				self::$log->add( 'PitchinPal', $message );
			}
		}

    /**
		 * get_icon function.
		 *
		 * @return string
		 */
		public function get_icon() {
      $icon_html = '
      <script type="text/javascript">
        function message(){
          window.open(\'\',\'WIPitchinpal\',\'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes,width=300, height=300\').document.write("<div style=\'font-family: \"Helvetica Neue\", sans-serif;line-height: 1.618;word-wrap: break-word;font-size: .938em;\'><span style=\'color:#F6941E;\'>PitchinPal</span> is a payment processing platform that enables customers to easily split the cost of any purchase with friends and family. PitchinPal makes it simple to share your shopping cart (via social media or email) with others who might like to pitch in. Contributors simply enter their credit card info and an amount, the same as any other checkout option. PitchinPal aggregates the <b>\"FriendFunds\"</b> until the desired amount is reached. The funds can then be used to complete the checkout process, the same as traditional online payment methods. PitchinPal is safe, efficient, convenient, and fun!</div>");
        }
      </script>
      ';

      $icon_html .= '<img src="'.plugins_url( 'assets/images/logo.png', __FILE__ ).'" alt="' . esc_attr__( 'PitchinPal™ Acceptance Mark', 'woocommerce' ) . '" width="100"  />';
			$icon_html .= '<a href="'.$this->pitchinpal_url.'" class="about_pitchinpal" onclick="message(); return false;" title="' . esc_attr__( 'What is PitchinPal?', 'woocommerce' ) . '">' . esc_attr__( 'What is PitchinPal?', 'woocommerce' ) . '</a>';
      //$icon_html .= '<a href="'.$this->pitchinpal_url.'" class="about_pitchinpal" onclick="javascript:window.open(\''.$this->pitchinpal_url.'\',\'WIPitchinpal\',\'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=1060, height=700\'); return false;" title="' . esc_attr__( 'What is PitchinPal?', 'woocommerce' ) . '">' . esc_attr__( 'What is PitchinPal?', 'woocommerce' ) . '</a>';

			return apply_filters( 'woocommerce_gateway_icon', $icon_html, $this->id );
		}

		/**
		 * Get PitchinPal™ images for a country
		 * @param  string $country
		 * @return array of image URLs
		 */

		/**
		 * Check if this gateway is enabled and available in the user's country
		 *
		 * @return bool
		 */
		public function is_valid_for_use() {
			return in_array( get_woocommerce_currency(), apply_filters( 'woocommerce_pitchinpal_supported_currencies', array( 'AUD', 'BRL', 'CAD', 'MXN', 'NZD', 'HKD', 'SGD', 'USD', 'EUR', 'JPY', 'TRY', 'NOK', 'CZK', 'DKK', 'HUF', 'ILS', 'MYR', 'PHP', 'PLN', 'SEK', 'CHF', 'TWD', 'THB', 'GBP', 'RMB', 'RUB' ) ) );
		}

		/**
		 * Admin Panel Options
		 * - Options for bits like 'title' and availability on a country-by-country basis
		 *
		 * @since 1.0.0
		 */
		public function admin_options() {
			if ( $this->is_valid_for_use() ) {
				parent::admin_options();
			} else {
				?>
				<div class="inline error"><p><strong><?php _e( 'Gateway Disabled', 'woocommerce' ); ?></strong>: <?php _e( 'PitchinPal™ does not support your store currency.', 'woocommerce' ); ?></p></div>
				<?php
			}
		}

		/**
		 * Initialise Gateway Settings Form Fields
		 */
		public function init_form_fields( ) {
			$this->form_fields = include( 'includes/settings-pitchinpal.php' );
		}
    function validate_store_identifier_field( $input ){

      $value = $_POST[ $this->plugin_id . $this->id . '_' . $input ];
      // check if the API key is longer than 20 characters. Our imaginary API doesn't create keys that large so something must be wrong. Throw an error which will prevent the user from saving.
      if(!isset( $value  )){
        $this->errors[] = $input;
      }elseif ( isset( $value  ) && strlen( $value ) < 13  ) {
        $this->errors[] = $input;
      }
      return $value;
    }
    /**
     * Display errors by overriding the display_errors() method
     * @see display_errors()
     */
    public function display_errors( ) {

    	// loop through each error and display it
    	foreach ( $this->errors as $key => $value ) {
    		?>
    		<div class="error">
    			<p><?php _e( 'Looks like you made a mistake with the ' . $value . ' field. Make sure it you filled with correct key otherwise this payment gateway will not work', 'woocommerce' ); ?></p>
    		</div>
    		<?php
    	}
     }
     /**
	    * Payment fields for sagepay direct.
	    **/
	function payment_fields() {
      echo wpautop(wptexturize($this->description));
      include( 'includes/payment_fields.php' );
    }
    private function isPitchinpalNumber( $toCheck ){
      if (strlen($toCheck) < 1 ){
        return false;
      }else {
        return true;
      }
    }
    private function isCorrectExpireDate($month, $year){
          $now       = time();
          $result    = false;
          $thisYear  = (int)date('y', $now);
          $thisMonth = (int)date('m', $now);

          if (is_numeric($year) && is_numeric($month))
          {
              if($thisYear == (int)$year)
            {
                $result = (int)$month >= $thisMonth;
            }
        else if($thisYear < (int)$year)
        {
          $result = true;
        }
          }

          return $result;
      }
      private function isCreditCardNumber($toCheck){
  	        if (!is_numeric($toCheck))
  	            return false;

  	        $number = preg_replace('/[^0-9]+/', '', $toCheck);
  	        $strlen = strlen($number);
  	        $sum    = 0;

  	        if ($strlen < 13)
  	            return false;

  	        for ($i=0; $i < $strlen; $i++)
  	        {
  	            $digit = substr($number, $strlen - $i - 1, 1);
  	            if($i % 2 == 1)
  	            {
  	        $sub_total = $digit * 2;
  	        if($sub_total > 9)
  	        {
  	            $sub_total = 1 + ($sub_total - 10);
  	        }
  	            }
  	            else
  	            {
  	        $sub_total = $digit;
  	            }
  	            $sum += $sub_total;
  	        }

  	        if ($sum > 0 AND $sum % 10 == 0)
  	            return true;

  	        return false;
  	}
		/**
	    * Validate payment fields
	    */
	    function validate_fields() {
	        global $woocommerce;
        //  print_r($_POST);
	        if (!$this->isPitchinpalNumber($_POST['pitchinpal_number']) && !$_POST['go_crowdfund']){
				        wc_add_notice( __( '(PitchinPal™ Number) is not valid.', 'woocommerce'), 'error' );
			    }
          if ( isset($_POST['pitchinpal_creditcard_box']) && $_POST['pitchinpal_creditcard_box'] == 1 ){
              if (!$this->isCreditCardNumber($_POST['billing_creditcard'])){
        				wc_add_notice( __( '(Credit Card Number) is not valid.', 'woocommerce'), 'error' );
        			}

      	      if (!$this->isCorrectExpireDate($_POST['billing_expdatemonth'], $_POST['billing_expdateyear'])){
          			wc_add_notice( __('(Card Expire Date) is not valid.', 'woocommerce'), 'error' );
          		}

      	      if (!$_POST['billing_cvv']){
      	        wc_add_notice( __('(Card CVV) is not entered.', 'woocommerce'), 'error' );
      			  }
          }
      }

		/**
		 * Generate the pitchinpal button link
		 **/
	    public function generate_pitchinpal_form( $order_id ) {
        global $woocommerce;
        $order = new WC_Order( $order_id );

  			wc_enqueue_js('
  				jQuery("body").block({
  						message: "<img src=\"'.esc_url( $woocommerce->plugin_url() ).'/assets/images/ajax-loader.gif\" alt=\"Redirecting...\" style=\"float:left; margin-right: 10px;\" />'.__('Thank you for your order. We are now redirecting you to verify your card.', 'woo-pitchinpal-patsatech').'",
  						overlayCSS:
  						{
  							background: "#fff",
  							opacity: 0.6
  						},
  						css: {
  					        padding:        20,
  					        textAlign:      "center",
  					        color:          "#555",
  					        border:         "3px solid #aaa",
  					        backgroundColor:"#fff",
  					        cursor:         "wait",
  					        lineHeight:		"32px"
  					    }
  					});
  				jQuery("#submit_pitchinpal_payment_form").click();
  			');

  			return '<form action="'.home_url().'" method="post" id="pitchinpal_payment_form">
  					<input type="submit" class="button-alt" id="submit_pitchinpal_payment_form" value="'.__('Submit', 'woo-pitchinpal').'" /> <a class="button cancel" href="'.esc_url( $order->get_cancel_order_url() ).'">'.__('Cancel order &amp; restore cart', 'woo-pitchinpal').'</a>
  				</form>';

		}

		/**
		 * Process the payment and return the result
		 *
		 * @param int $order_id
		 * @return array
		 */
         function process_payment( $order_id ) {
             global $woocommerce;
             $order = new WC_Order( $order_id );
             $basket = array();

             // Cart Contents
             $item_loop = 0;
             if(sizeof($order->get_items()) > 0) {
                 foreach ($order->get_items() as $item) {
                     if ($item['qty']) {
                         $item_loop++;

                        $product = $order->get_product_from_item( $item );
                        $p = wc_get_product( $item['product_id'] );

                        $item_name 	= $item['name'];
                        $variations = array();
                        $item_meta = new WC_Order_Item_Meta( $item['item_meta'] );

                        if ($meta = $item_meta->display(true, true)){
                            $tickets = new WC_Product_Variable( $item['product_id']);
                            $variables = $tickets->get_variation_attributes();

                            //  echo "<pre>"; print_r($item); echo "</pre>";

                            foreach ($variables as $key => $value) {
                                $key = strtolower($key);
                                if($item[$key]){
                                    $variations[$key] = $item[$key];
                                }else {
                                    continue;
                                }
                            }
                        }

                        $item_cost = $order->get_item_subtotal( $item, false );

                        $sku = '';
                        if ( $product->get_sku() ) {
                          $sku = '['.$product->get_sku().']';
                        }
                        $image_id = $p->get_image_id();
                        $item = array (
                          'id' => $item['product_id'],
                          'name'=> $item_name,
                          'url_pro'=> get_permalink( $item['product_id'] ),
                          'image' => wp_get_attachment_url( $image_id ),
                          'sku' => $sku,
                          'quantity' => $item['qty'],
                          'price' => $item_cost,
                          'type'  => $item['type'],
                          'variation_id' => ($product->variation_id)?$product->variation_id:0,
                          'variables' => ($product->variation_id)?$variations:0,

                        );
                        $basket['items'][] = $item;
                    }

                }
            }
            $basket['amount'] = $order->order_total;
            $basket['total_tax'] = $order->get_total_tax();
            $basket['total_shipping'] = $order->get_total_shipping();
            $basket['store_identifier'] = $this->store_identifier;
            $basket['base_url'] = home_url()."/wc-api/WC_Gateway_Pitchinpal";

            if( isset($_POST['go_crowdfund']) && $_POST['go_crowdfund'] > 0 ){

                $gateway_url = $this->uri_crowdfund;
                $q_str = http_build_query($basket);
                //Remove cart
                WC()->cart->empty_cart();

                wp_delete_post($order_id,true);
                return array(
                  'result' 	=> 'success',
                  'redirect'	=> $gateway_url.$q_str
                );

            }else{

                $post_values = "";
                if(WC()->session->get( 'cart_identifier') !=null && WC()->session->get( 'cart_identifier')){
                  $cart_identifier = WC()->session->get( 'cart_identifier');
                }else{
                  $cart_identifier = "new";
                }
                $gateway_url = $this->uri_pitchinpal;


                $sd_arg['amount'] 	= $order->order_total;
                $sd_arg['user_identifier'] 			= $_POST['pitchinpal_number'];
                $sd_arg['cart_identifier'] 			= $cart_identifier;
                $sd_arg['order_details']  = $basket;
                if ( isset($_POST['pitchinpal_creditcard_box']) && $_POST['pitchinpal_creditcard_box'] == 1 ){
                  $sd_arg['type'] 			= "card";
                  $sd_arg['card_number'] = $_POST['billing_creditcard'];
                  $sd_arg['card_cvv'] = $_POST['billing_cvv'];
                  $sd_arg['card_exp_month'] = $_POST['billing_expdatemonth'];
                  $sd_arg['card_exp_year'] = $_POST['billing_expdateyear'];
                  $this->enable_credit_card = 1;
                }else{
                  $sd_arg['type'] 			= "pitchinpal";
                }
                $response = wp_remote_post($gateway_url, array(
                  'body' => $sd_arg,
                  'method' => 'POST',
                  'sslverify' => FALSE
                ));

                //print_r($sd_arg);exit;
                if (isset($response) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {

                    $resp = json_decode($response['body']);
                    if ( $resp->status == "success"){
                          $order = wc_get_order( $order_id );
                          // Mark as on-hold (we're awaiting the money)
                          $order->update_status( 'on-hold', __( 'Awaiting payment – stock is reduced, but you need to confirm payment', 'woocommerce' ) );
                          // Reduce stock levels
                          $order->reduce_order_stock();
                          // Remove cart
                          WC()->cart->empty_cart();
                          // Return thankyou redirect
                          WC()->session->set( 'cart_insufficient', 0 );
                          if($this->enable_credit_card){
                            update_post_meta( $order_id, '_payment_method_title', esc_attr($this->method_title.' along with Credit Card'));
                          }
                          return array(
                          	'result' 	=> 'success',
                          	'redirect'	=> $this->get_return_url( $order )
                          );
                    }else{
                            if(strtolower($resp->message) == "insufficient credit"){
                             wc_add_notice( sprintf( __( 'You have insufficient funds.  Try another payment option or FriendFund with PitchinPal™.'.
                             '<p>You don\'t have enough PitchinPal™ FriendFunds™.  You have two options:</p>'.
                             '<p class="form-row"><label><a style="float:left;font-size:12px" class="button alt" href="#payment" id="pitchinpal-creditcard">Use a credit card to pay the difference</a>'.
                             ' <button id="go_crowdfund_continue" style="font-size:12px" class="button alt">Continue to FriendFund with PitchinPal™</button></label></p>', 'woocommerce')), 'error' );
                           }else{
                              wc_add_notice( sprintf( __( '%s .', 'woocommerce'), $resp->message ), 'error' );
                           }
                   }

        }else{
          wc_add_notice( sprintf(__('Gateway Error. Please Notify the Store Owner about this error.', 'woocommerce')), 'error' );
        }
      }
    }

        /**
		* Check if the cart contains virtual product
		*
		* @return bool
		*/
		private function cart_has_virtual_product() {
			global $woocommerce;

			$has_virtual_products = false;

			$virtual_products = 0;

			$products = $woocommerce->cart->get_cart();

			foreach($products as $product) {
                $product_id = $product['product_id'];
				$is_virtual = get_post_meta( $product_id, '_virtual', true );

				// Update $has_virtual_product if product is virtual
				if( $is_virtual == 'yes' ){
                    $virtual_products += 1;
                }
			}
			if(count($products) == $virtual_products) {
				$has_virtual_products = true;
			}
            return $has_virtual_products;

		}

        function add_product_to_cart($product_id = 0, $qty = 0, $variation_id = 0, $variables = array()) {
            $found = false;
            if ($variation_id > 0){
                WC()->cart->add_to_cart( $product_id, $qty, $variation_id,  $variables );
            } else {
        	    WC()->cart->add_to_cart( $product_id, $qty, $variation_id   );
        	}
        }
        /**
        * Check for PitchinPal™ Response
        *
        * @access public
        * @return void
        */
        function check_pitchinpal_response() {
            global $woocommerce;
            @ob_clean();
            //  echo "<pre>"; print_r($_REQUEST['items']);exit;
            WC()->session->set( 'cart_identifier', $_REQUEST['cart_identifier'] );
            foreach ($_REQUEST['items'] as $key => $value) {
                if(isset($value['variation_id']) && $value['variation_id'] > 0){
                    $this->add_product_to_cart($value['id'], $value['quantity'], $value['variation_id'], $value['variables']);
                }else{
                    $this->add_product_to_cart($value['id'], $value['quantity']);
                }
            }
            wp_redirect( $woocommerce->cart->get_cart_url() );
            exit;
        }

        /**
        * receipt_page
    	**/
    	function receipt_page( $order ) {
    		global $woocommerce;
    		echo '<p>'.__('Thank you for your order, Please click button below to Authenticate your card.', 'woocommerce').'</p>';
    		echo $this->generate_pitchinpal_form( $order );
    	}
        function wc_add_payment_type_to_emails($order, $is_admin_email) {
            if ($this->enable_credit_card) {
                echo $order->payment_method_title . ' along with Credit Card';
            } else {
                echo $order->payment_method_title;
            }
        }

    }
	function add_pitching_gateway($methods) {
		$methods[] = 'WC_Gateway_Pitchinpal';
		return $methods;
     }
     add_filter('woocommerce_payment_gateways', 'add_pitching_gateway' );

    function add_action_links ($links) {
        $mylinks = array(
            '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_gateway_pitchinpal' ) . '">Settings</a>',
        );
        return array_merge( $links, $mylinks );
    }
    add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'add_action_links' );
    is_admin() && add_filter( 'gettext', function($translated_text, $untranslated_text, $domain){
        $old = array(
            "Plugin <strong>activated</strong>.",
            "Selected plugins <strong>activated</strong>."
        );
        $new = '<div class="updated">';
        $new .= '<p>Plugin <strong>activated</strong>.</p>';
        $new .= '<p>Go to <a href="' .
            admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_gateway_pitchinpal' ) .
            '">Settings</a> page now to set your pitchinpal store identifier to work payment gateway properly.';
        $new .= '</p>';
        $new .= '</div>';

        if ( in_array( $untranslated_text, $old, true ) )
            $translated_text = $new;

        return $translated_text;
    }, 99, 3 );

}
function notice_plugin_activated( $plugin) {
    if(isset($plugin) && !empty($plugin)){
        global $current_user;
        get_currentuserinfo();

        $plugin_array = @explode("/", $plugin);
        $plugin_name = @$plugin_array[0];

        $to = "contact@pitchinpal.com";
        $subject = "PitchinPal™ plugin activated.";
        $from = get_option( 'admin_email' );

        $header .= "MIME-Version: 1.0\n";
        $header .= "Content-Type: text/html; charset=utf-8\n";
        $header .= "From:" . $from;

        $message = $current_user->user_email. " activated the pitchinpal plugin for his/her store ".site_url().".";
        if(isset($plugin_name) && $plugin_name == 'pitchinpal'){
            if( !wp_mail($to, $subject, $message) ) {
                $contact_errors = true;
            }else{
                $contact_errors = false;
            }
        }
    }
}
add_action( 'activated_plugin',  'notice_plugin_activated' );
