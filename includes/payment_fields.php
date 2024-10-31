<div id="have_funds" style="display : none">
<p class="form-row" >
          <label>Your Unique PitchinPal™ Key <span class="required">*</span>
      <input class="input-text" style="width:180px;" type="text" size="16" maxlength="16" id = "pitchinpal_number" name="pitchinpal_number" />
      <input type='hidden' id="set_go_crowdfund" name='go_crowdfund' value='0' />
    </label>
    <span style="font-size : 11px;">*you can find this under your profile at PitchinPal.com</span>

</p>

<!--  credit card processing here -->
<div id = "credit-card-pitchinpal" style="display : none">
  <input type="hidden" id="pitchinpal-creditcard-box" name="pitchinpal_creditcard_box" value="0">
  <p class="form-row" >
    <label>Card Number <span class="required">*</span>
      <input class="input-text" style="width:180px;" type="text" size="16" maxlength="16" name="billing_creditcard" />
    </label>
  </p>
  <div class="clear"></div>
  <p class="form-row form-row-first">
    <label>Expiration Month <span class="required">*</span>
    <select name="billing_expdatemonth">
            <option value=01> 1 - January</option><option value=02> 2 - February</option><option value=03> 3 - March</option><option value=04> 4 - April</option><option value=05> 5 - May</option>
            <option value=06> 6 - June</option><option value=07> 7 - July</option><option value=08> 8 - August</option>
            <option value=09> 9 - September</option><option value=10>10 - October</option><option value=11>11 - November</option><option value=12>12 - December</option>
    </select>
    </label>
  </p>
  <div class="clear"></div>
  <p class="form-row form-row-second">
    <label>Expiration Year  <span class="required">*</span>
      <select name="billing_expdateyear">
      <?php
          $today = (int)date("y", time());
        $today1 = (int)date("Y", time());
          for($i = 0; $i < 15; $i++)
          {
      ?>
              '<option value="<?php echo $today; ?>"><?php echo $today1; ?></option>
      <?php
              $today++;
          $today1++;
          }
      ?>
      </select>
  </label>
  </p>
  <div class="clear"></div>
  <p class="form-row">
    <label>Card CVV <span class="required">*</span>
      <input class="input-text" style="width:100px;" type="text" size="5" maxlength="5" name="billing_cvv" /></p><div class="clear"></div>
    </label>
  </p>
</div>
<p class="form-row" id="order_btn"></p>
<hr/>

</div>
<!--  credit card processing end here -->


<style type="text/css">

  #have_funds #place_order{
    float: none !important;
  }
  #have_crowdfund, #go_crowdfund{
    width: 100%;
  }
  label[for="payment_method_pitchinpal"]{
    width: 95% !important;
  }
  @media (max-width: 600px) {
    label[for="payment_method_pitchinpal"]{
      width: 90% !important;
    }
  }
  @media (max-width: 480px) {
     .woocommerce #respond input#submit, .woocommerce a.button, .woocommerce button.button, .woocommerce input.button{
        font-size:70% !important;
     }

  }

</style>
<div>
  <p class="form-row form-row-first">
    <button class="button alt" name="woocommerce_checkout_have_crowdfund" id="have_crowdfund">Have PitchinPal FriendFunds</button>
    <label class="" style="font-size:12px;">I have PitchinPal FriendFunds and  I am ready to complete my purchase.</label>
  </p>
  <p class="form-row form-row-last">
    <button class="button alt" name="woocommerce_checkout_go_crowdfund" id="go_crowdfund" data-value="Go Crowdfund">Need PitchinPal FriendFunds</button>
    <label class="" style="font-size:12px;">I want to FriendFund With PitchinPal.</label>
   </p>
</div>

<script type="text/javascript">
jQuery(document).ready(function(){

  jQuery("input[name='payment_method']").on("click",function(){
      if(jQuery(this).val() === "pitchinpal"){
          $order_btn = jQuery(".place-order").html();
          jQuery("div.place-order").hide();
          jQuery("#order_btn").addClass("place-order").html($order_btn);
          jQuery("#order_btn #place_order").attr("disabled", "true");
      }else{
          jQuery("#order_btn").removeClass("place-order").html("");
          jQuery("div.place-order").show();
      }

  });


  jQuery("#go_crowdfund").on("click",function(){
      jQuery("#set_go_crowdfund").val("1");
      jQuery("#credit-card-pitchinpal").css({display : 'none'});
      jQuery("#pitchinpal-creditcard-box").val("0");
  });
  jQuery("#have_crowdfund").on("click",function(){
      jQuery("#have_funds").css({display : 'block'});
      return false;
  });

  jQuery("#place_order").on("click",function(){
      jQuery("#set_go_crowdfund").val("0");
  });
  jQuery("form[name='checkout']").on("click","#pitchinpal-creditcard",function(){
    //if(jQuery(this).is(':checked')){
      jQuery("#credit-card-pitchinpal").css({display : 'block'});
      jQuery("#pitchinpal-creditcard-box").val("1");

    // }else{
    //   jQuery("#credit-card-pitchinpal").css({display : 'none'});
    // }
    return true;
  });
  jQuery("form[name='checkout']").on("click","#go_crowdfund_continue",function(){
      jQuery("#set_go_crowdfund").val("1");
      jQuery("#credit-card-pitchinpal").css({display : 'none'});
      jQuery("#pitchinpal-creditcard-box").val("0");
  });

  jQuery("#pitchinpal_number").on("input",function(){
  if(jQuery(this).val().length > 5){
        jQuery("#place_order").removeAttr("disabled");
  }else{
      jQuery("#place_order").attr("disabled", "true");
  }
  });
});
</script>

<div class="clear"></div>
