jQuery("document").ready(function(){
    jQuery(".crowd_fund_cart_btn").on("click",function(){
        var _this = this;
        var flag = false;
        alertify.confirm(
            "Item Ready To FriendFund! <br> <span class='subtext'>If you want to FriendFund multiple items <a href='cart/'>go to cart page</a></span>",
            function (e) {
                if (e === 1) {

                    var data = {
                        "action": "cfn_crowd_fund_cart",
                        "type": "crowd_fund_product",
                        "product_id": jQuery(_this).attr("data-product_id"),
                        "quantity": jQuery(_this).attr("data-quantity"),
                        "product_type": jQuery(_this).attr("data-type"),
                        "single": 1,
                    };
                    jQuery.post(ajax_crowd.ajax_url, data, function(response) {
                        if(response.result === "success"){
                            window.location = response.redirect;
                        }
                    },"json");
                    flag = false;
                } else if(e === 2){
                    var data = {
                        "action": "cfn_crowd_fund_cart",
                        "type": "crowd_fund_product",
                        "product_id": jQuery(_this).attr("data-product_id"),
                        "quantity": jQuery(_this).attr("data-quantity"),
                        "product_type": jQuery(_this).attr("data-type"),
                        "single": 2,
                    };
                    jQuery.post(ajax_crowd.ajax_url, data, function(response) {
                        if(response.result === "success"){
                            window.location = response.redirect;
                        }
                    },"json");
                    flag = false;
                }else if(e === 3){

                   return false;
                } else {
                     if(jQuery(".single-pitchinpal").length > 0){
                         jQuery(".single_add_to_cart_button").trigger('click');
                     }else{
                         jQuery(".add_to_cart_button[data-product_id="+jQuery(_this).attr("data-product_id")+"]").trigger('click');
                     }
                 }
             }
         );
         return false;
    });
    alertify.set({ labels: { ok: "FriendFund ONLY this one item", mok: "FriendFund With Entire Cart", cancel: "Keep Shopping", cnl: "Cancel" } });
});
