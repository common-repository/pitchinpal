<?php class crowd_fund_now {
    public function __construct() {

        $this->product_types = array('simple' => 'Simple Products','variable'=>'Variable Products');
        $this->cfn_text = 'FriendFund It';
        $this->cfn_text0 = 'Crowdfund Your Cart!';
        $this->cfn_text1 = 'FriendFund™ This Cart';
        $this->cfn_text11 = 'Get friends and family to pitch in with PitchinPal!';
        $this->cfn_auto = get_option('cfn_auto');
        $this->cfn_category = get_option('cfn_category');

        //$this->uri_crowdfund = "http://pipdev.pitchinpal.com/cart/data/";
        $this->uri_crowdfund = "https://pitchinpal.com/cart/data/";
        $this->store_identifier = get_option( 'woocommerce_pitchinpal_settings' );

        if(isset($this->cfn_auto) && $this->cfn_auto == 'true'){
            add_action ('woocommerce_after_add_to_cart_button',array($this,'cfn_after_add_to_cart_form_add_single'),99);
            if(isset($this->cfn_category) && $this->cfn_category == 'true'){
                add_action('woocommerce_after_shop_loop_item',array($this,'cfn_after_add_to_cart_form_add_listing'),99);
            }
            add_action ('woocommerce_after_cart_totals',array($this,'cfn_crowd_fund_cartform_add'),99);
        }
        add_action( 'wp_ajax_cfn_crowd_fund_cart', array($this,'cfn_crowd_fund_cart') );
        add_action( 'wp_ajax_nopriv_cfn_crowd_fund_cart', array($this,'cfn_crowd_fund_cart') );

        add_filter( 'woocommerce_get_sections_products', array($this,'cfn_add_section' ));
        add_filter( 'woocommerce_get_settings_products', array($this,'cfn_all_settings'),10,2);
        add_filter( 'woocommerce_add_to_cart_redirect',array($this,'cfn_add_to_cart_redirect_check'));
        add_action( 'wp_enqueue_scripts', array($this, 'cfn_scripts'));
    }
    public function cfn_add_section( $sections ) {
        $sections['crowd_fund_now'] = __( 'PitchinPal FriendFund™ Now','woocommerce');
        return $sections;
    }
    public function cfn_all_settings( $settings, $current_section ) {
        if ( $current_section == 'crowd_fund_now' ) {
            return array(
               array(
                   'name' => __( 'FriendFund™ Now Options', 'woocommerce' ),
                   'type' => 'title',
                   'desc' => __( 'Choose as you wish to represent your button to the customer','woocommerce' ),
                   'id' => 'cfn'
               ),

               array(
                   'name' => __( 'Dispaly FriendFund™ Button ', 'woocommerce' ),
                   'desc_tip' => __( 'By Default Add Button After Add To Cart In Single Product View', 'woocommerce' ),
                   'id' => 'cfn_auto',
                   'type' => 'select',
                   'class' =>'chosen_select',
                   'options' => array('true' => 'Yes','false'=>'No')
               ),
               array(
                   'name' => __( 'Show FriendFund™ button in product listing page?', 'woocommerce' ),
                   'desc_tip' => __( 'Show FriendFund™ Button in category level', 'woocommerce' ),
                   'id' => 'cfn_category',
                   'type' => 'select',
                   'class' =>'chosen_select',
                   'options' => array('true' => 'Yes','false'=>'No')
               ),

               array( 'type' => 'sectionend', 'id' => 'crowd_fund_now' ),

            );
        }else {
            return $settings;
        }
    }
    public function cfn_add_to_cart_redirect_check($url){
        if(isset($_REQUEST['crowd_fund_now']) && $_REQUEST['crowd_fund_now'] == true){
            $redirect_op = get_option('crowd_fund_now_redirect');
            if($redirect_op == 'cart'){
                return WC()->cart->get_cart_url();
            } else if($redirect_op == 'checkout'){
                return WC()->cart->get_checkout_url();
            }

        }
        return $url;
    }

    public function cfn_crowd_fund_cart(){
        if($_REQUEST['type'] == "crowd_fund_product"){
            if($_REQUEST['single'] == 1){
                WC()->cart->empty_cart();
            }

            if($_REQUEST['product_type'] == "simple"){
                WC()->cart->add_to_cart( $_REQUEST['product_id'], $_REQUEST['quantity'] );
            }elseif($_REQUEST['product_type'] == "variable"){
                $variables = array();
                foreach ($_REQUEST['variables'] as $key => $value) {
                    //explode("attribute_",$value['name'])[1]
                    $variables[$value['name']] = $value['value'];
                }
                WC()->cart->add_to_cart($_REQUEST['product_id'], $_REQUEST['quantity'], $_REQUEST['variation_id'], $variables);
            }
            if (! defined( 'WOOCOMMERCE_CART' )) {
                define( 'WOOCOMMERCE_CART', true );
            }
            WC()->cart->calculate_totals();
            //WC()->cart->calculate_shipping();
        }

        $items = WC()->cart->get_cart();

        $basket = array();
        if ( sizeof( $items ) > 0 ) {
          foreach ( $items as $item ) {
            if ( $item['quantity'] ) {
              $product = wc_get_product( $item['product_id'] );
              //$order->add_product( $product, $item['quantity'] );
              $item_name 	= $product->get_title();
              $variations = array();

              $item_cost = $product->get_price( $item['product_id'], false );
              if ( $item['variation_id'] ){
                  $vp = new WC_Product_Variation( $item['variation_id'] );
                  $item_cost = $vp ->regular_price;

                  $tickets = new WC_Product_Variable( $item['product_id']);
                  $variables = $tickets->get_variation_attributes();
                  foreach ($variables as $key => $value) {
                    $key = strtolower($key);
                    if($item['variation']['attribute_'.$key]){
                      $variations[$key] = $item['variation']['attribute_'.$key];
                    }else {
                      continue;
                    }
                  }
              }
              $sku = '';
              if ( $product->get_sku() ) {
                $sku = '['.$product->get_sku().']';
              }
              $image_id = $product->get_image_id();
              $item = array (
                'id' => $item['product_id'],
                'name'=> $item_name,
                'url_pro'=> get_permalink( $item['product_id'] ),
                'image' => wp_get_attachment_url( $image_id ),
                'sku' => $sku,
                'quantity' => $item['quantity'],
                'price' => $item_cost,
                'type'  => $product->product_type,
                'variation_id' => ($item['variation_id'])?$item['variation_id']:0,
                'variables' => ($item['variation_id'])?$variations:0,

              );
              $basket['items'][] = $item;
            }

          }
        }

        $basket['amount'] = WC()->cart->total;
        $basket['total_tax'] = WC()->cart->tax_total;
        $basket['total_shipping'] = WC()->cart->shipping_total;
        $basket['store_identifier'] = $this->store_identifier['store_identifier'];
        $basket['base_url'] = home_url()."/wc-api/WC_Gateway_Pitchinpal";

        $gateway_url = $this->uri_crowdfund;
        $q_str = http_build_query($basket);
        //Remove cart
        WC()->cart->empty_cart();
        echo json_encode(
            array(
               'result' 	=> 'success',
               'redirect'	=> $gateway_url.$q_str
            )
        );
        die();
    }
    public function cfn_crowd_fund_cartform_add(){
        $button_name = $this->cfn_text1.'<br/><i class="small">'.$this->cfn_text11."</i>";
        $form = '<div class="wc-proceed-to-checkout"><a class="button alt wc-forward" id="crowd_fund_cart_btn">'.$button_name.'</a></div>';
        $form .= '<style>#crowd_fund_cart_btn i.small{font-size:12px;}</style><script>

            jQuery("document").ready(function(){
                jQuery("#crowd_fund_cart_btn").on("click",function(){

                    var data = {
            			"action": "cfn_crowd_fund_cart",
            			"type": "crowd_fund_cart"
            		};
                    jQuery.post("'.admin_url('admin-ajax.php').'", data, function(response) {
                        if(response.result === "success"){
                            window.location = response.redirect;
                        }
            		},"json");
                    return false;
                });

            });
            </script>
        ';
        echo $form;
    }
    public function cfn_after_add_to_cart_form_add_single(){
        global $product;

        $button_name = $this->cfn_text;//.'<br/><i style="font-size:12px">'.$this->cfn_text0."</i>";

        $quantity = isset( $quantity ) ? $quantity : 1;
        $style = ($product->product_type == "variable")?"margin-top:5px;margin-bottom:5px":"margin-right:5px;margin-left:5px";
        if($product->product_type == "variable"){
            $class = "form.variations_form[data-product_id='".$product->id."']";
            echo '<button data-type="'.$product->product_type.'" data-quantity="1" data-product_id="'.$product->id.'" type="submit" style="'.$style.'" class="crowd_fund_cart_btn_single single_add_to_cart_button single-pitchinpal button alt">'.$button_name.'</button>';
            echo '<script>
            jQuery("document").ready(function(){
                var variations = Array()

                var variation_id = 0;
                jQuery("'.$class.'").change(function(){
                    variations = Array();
                    variation_id = 0;
                    var cfn_product_variations = jQuery("'.$class.'").attr("data-product_variations");
                    cfn_product_variations = JSON.parse(cfn_product_variations);
                    var cfn_product_id = jQuery("'.$class.' input[name=\"variation_id\"").val();

                    for(var i = 0; i < cfn_product_variations.length; i++){
                        if(cfn_product_id == cfn_product_variations[i]["variation_id"]){
                            if(cfn_product_variations[i]["is_in_stock"] === false){
                                jQuery(".crowd_fund_cart_btn_single.").hide();
                            }
                        }

                    }


                    var value = jQuery("input.input-text.qty.text").val();
                    jQuery(".crowd_fund_cart_btn_single").attr("data-quantity",value);
                    var datas = jQuery("'.$class.'").serializeArray();
                    jQuery.each( datas, function( key, value ) {
                            if(value["name"] != "quantity" && value["name"] != "add-to-cart" && value["name"] != "product_id" ){
                                if(value["name"] === "variation_id"){
                                    variation_id = value["value"];
                                }else{
                                    var jsonArg1 = new Object();
                                    jsonArg1.name = value["name"];
                                    jsonArg1.value = value["value"];
                                    variations.push(jsonArg1);
                                }
                            }
                    });

                });
                jQuery(".crowd_fund_cart_btn_single").on("click",function(){
                    var _this = this;
                    var flag = false;
                    alertify.confirm("Item Ready To FriendFund! <br> <span class=\'subtext\'>If you want to FriendFund multiple items <a href=\'cart/\'>go to cart page</a></span>", function (e) {

                        if (e === 1) {

                            var data = {
                                "action": "cfn_crowd_fund_cart",
                                "type": "crowd_fund_product",
                                "product_id": jQuery(_this).attr("data-product_id"),
                                "quantity": jQuery(_this).attr("data-quantity"),
                                "product_type": jQuery(_this).attr("data-type"),
                                "variables": variations,
                                "variation_id": variation_id,
                                "single": 1,

                            };
                            jQuery.post(ajax_crowd.ajax_url, data, function(response) {
                                if(response.result === "success"){
                                    window.location = response.redirect;
                                }
                            },"json");
                        } else if(e === 2){

                            var data = {
                                "action": "cfn_crowd_fund_cart",
                                "type": "crowd_fund_product",
                                "product_id": jQuery(_this).attr("data-product_id"),
                                "quantity": jQuery(_this).attr("data-quantity"),
                                "product_type": jQuery(_this).attr("data-type"),
                                "variables": variations,
                                "variation_id": variation_id,
                                "single": 2,

                            };
                            jQuery.post(ajax_crowd.ajax_url, data, function(response) {
                                if(response.result === "success"){
                                    window.location = response.redirect;
                                }
                            },"json");
                            flag = false;
                        }  else if(e === 3){

                            return false;
                        } else {
                            jQuery(".single_add_to_cart_button").trigger("click");
                        }
                    });
                    return false;
                });
            });
            </script>
            ';
        }else{
            $class = "form.cart";
            echo '<button data-type="'.$product->product_type.'" data-quantity="1" data-product_id="'.$product->id.'" type="submit" style="'.$style.'" class="crowd_fund_cart_btn single_add_to_cart_button single-pitchinpal button alt">'.$button_name.'</button>';
            echo '<script>
            jQuery("document").ready(function(){
                jQuery("'.$class.' input[name=quantity]").change(function(){
                    var value = jQuery("input.input-text.qty.text").val();
                    jQuery(".crowd_fund_cart_btn").attr("data-quantity",value);
                });
            });
            </script>
            ';
        }

    }
    public function cfn_after_add_to_cart_form_add_listing(){
        global $product;

        $button_name = $this->cfn_text; //.'<br/><i style="font-size:12px">'.$this->cfn_text0."</i>";

        $quantity = isset( $quantity ) ? $quantity : 1;
        $add_to_cart = $product->is_purchasable()? 'add_to_cart_button': '';
        $crowd = ($product->product_type == "simple") ? "crowd_fund_cart_btn" : "";
        $ajax = ($product->product_type == "simple") ? "ajax_add_to_cart" : "";
        $cls = $crowd.' product_type_'.$product->product_type.' button';

        echo '<a rel="nofollow" href="'.$product->add_to_cart_url().'" data-type="'.$product->product_type.'" data-quantity="'.$quantity.'" data-product_id="'.$product->id.'" data-product_sku="'.$product->get_sku().'" class="'.$cls.'">'.$button_name.'</a>';
    }

    public function cfn_scripts(){
        $myDataArray = array(
        	'ajax_url' => admin_url( 'admin-ajax.php' ),
        );
        wp_enqueue_style( 'pitchinpal_modal', plugins_url( '../assets/css/alertify.core.css', __FILE__ ));
        wp_enqueue_style( 'pitchinpal_modal_theme', plugins_url( '../assets/css/alertify.bootstrap.css', __FILE__ ));
        wp_enqueue_script( 'pitchinpal_modal', plugins_url( '../assets/js/alertify.js', __FILE__ ), array('jquery'));
        wp_enqueue_script( 'pitchinpal_custom', plugins_url( '../assets/js/custom.js', __FILE__ ), array('jquery'));
        wp_localize_script( "pitchinpal_custom", "ajax_crowd", $myDataArray );
    }

}
if(in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins')))) {
    $crowd_fund_now = new crowd_fund_now;
} else {
    add_action( 'admin_notices', 'crowd_fund_notice' );
}

function crowd_fund_notice() {
  echo '<div class="error"><p><strong> <i> Woocommerce FriendFund_It Button </i> </strong> Requires <a href="'.admin_url( 'plugin-install.php?tab=plugin-information&plugin=woocommerce').'"> <strong> <u>Woocommerce</u></strong>  </a> To Be Installed And Activated </p></div>';
}
