var loader =
  '<div class="blockUI suchx-blockUI" style="display:none"></div><div class="blockUI blockOverlay suchx-blockUI" style="z-index: 1000; border: none; margin: 0px; padding: 0px; width: 100%; height: 100%; top: 0px; left: 0px; background: rgb(255, 255, 255); opacity: 0.6; cursor: default; position: absolute;"></div>';

function surchx_validateCardNumber(number) {
  // console.log("surchx_validateCardNumber "+Stripe.card.validateCardNumber(number));
  return payform.validateCardNumber(number);
}
(function($) {
	$( window ).on( "load" , function() {
    surchx_transaction_events("stripe");
    surchx_transaction_address();
  });

  function surchx_transaction_events(payment) {
    $("body").on("keyup", "#" + payment + "_surchx_ccNo", function() {
      var ccNo = $("#" + payment + "_surchx_ccNo")
        .val()
        .replace(/\s+/g, "");
      // alert(ccNo);
      var postcode = 0;

      if (
        $("#ship-to-different-address-checkbox").length != 0 &&
        $(".shipping_address").is(":visible")
      ) {
        postcode = $("#shipping_postcode").val();
      } else {
        postcode = $("#billing_postcode").val();
      }
      if (surchx_validateCardNumber(ccNo)) {
        $("#" + payment + "_surchx_ccNo")
          .next()
          .remove();
        $("#" + payment + "_surchx_ccNo").css("color", "green");
        $("#" + payment + "_surchx_ccNo")
          .next("input:text")
          .focus();

        if (postcode.length >= 5) {
          surchx_add_transaction_fee_ajax(ccNo, postcode);
        }
      } else if (surchx_validateCardNumber(ccNo) == false) {
        $("#" + payment + "_surchx_ccNo")
          .next()
          .remove();
        $("#" + payment + "_surchx_ccNo").css("color", "red");
        $("#" + payment + "_surchx_ccNo").after(
          '<p style="color:red">Card number invalid.</p>'
        );
      } else {
        $("#" + payment + "_surchx_ccNo").removeAttr("style");
      }
    });

    $("body").on("blur", "#" + payment + "_surchx_ccNo", function() {
      var ccNo = $("#" + payment + "_surchx_ccNo")
        .val()
        .replace(/\s+/g, "");
      if (surchx_validateCardNumber(ccNo) == false) {
        $("#" + payment + "_surchx_ccNo")
          .next()
          .remove();
        $("#" + payment + "_surchx_ccNo").css("color", "red");
        $("#" + payment + "_surchx_ccNo").after(
          '<p style="color:red">Card number invalid.</p>'
        );
      }
    });

    $("body").on("keyup", "#" + payment + "_surchx_expdate", function() {
      var surchx_expdate = $("#" + payment + "_surchx_expdate")
        .val()
        .replace(/\s+/g, "");
      if (surchx_expdate.length == 7 || surchx_expdate.length == 8) {
        var expdate_object = surchx_expdate.split("/");
        if (expdate_object[0] > 12) {
          $("#" + payment + "_surchx_expdate")
            .next()
            .remove();
          $("#" + payment + "_surchx_expdate").css("color", "red");
          $("#" + payment + "_surchx_expdate").after(
            '<p style="color:red">Your Card is expired. Please check expiry date.</p>'
          );
          return;
        }
        var today = new Date();
        var expDate = new Date(expdate_object[1], expdate_object[0] - 1); // JS Date Month is 0-11 not 1-12 grrr
        if (today.getTime() > expDate.getTime()) {
          $("#" + payment + "_surchx_expdate")
            .next()
            .remove();
          $("#" + payment + "_surchx_expdate").css("color", "red");
          $("#" + payment + "_surchx_expdate").after(
            '<p style="color:red">Your Card is expired. Please check expiry date.</p>'
          );
        } else {
          $("#" + payment + "_surchx_expdate")
            .next()
            .remove();
          $("#" + payment + "_surchx_expdate").css("color", "green");
          $("#" + payment + "_surchx_cvv").focus();
        }
      }
    });

    $("body").on("change", 'input[name="payment_method"]', function() {
      var payment_method = $(this).val();
      $("#wc-" + payment_method + "-cc-form input").val("");
      surchx_stripe_remove_transaction_fee_ajax();
    });
    $("form.woocommerce-form-coupon").submit(function() {
      //alert();
      var ccNo = $("#" + payment + "_surchx_ccNo")
        .val()
        .replace(/\s+/g, "");
      //alert(ccNo);

      var postcode = 0;
      if (
        $("#ship-to-different-address-checkbox").length != 0 &&
        $(".shipping_address").is(":visible")
      ) {
        postcode = $("#shipping_postcode").val();
      } else {
        postcode = $("#billing_postcode").val();
      }
      //alert(postcode);
      if (surchx_validateCardNumber(ccNo)) {
        $("#" + payment + "_surchx_ccNo")
          .next()
          .remove();
        $("#" + payment + "_surchx_ccNo").css("color", "green");
        $("#" + payment + "_surchx_ccNo")
          .next("input:text")
          .focus();

        if (postcode.length >= 5) {
          setTimeout(function() {
            surchx_add_transaction_fee_ajax(ccNo, postcode);
          }, 1000);
        }
      } else if (surchx_validateCardNumber(ccNo) == false) {
        $("#" + payment + "_surchx_ccNo")
          .next()
          .remove();
        $("#" + payment + "_surchx_ccNo").css("color", "red");
        $("#" + payment + "_surchx_ccNo").after(
          '<p style="color:red">Card number invalid.</p>'
        );
      } else {
        $("#" + payment + "_surchx_ccNo").removeAttr("style");
      }
    });

    surchx_stripe_remove_transaction_fee_ajax();
  }

  function prventcoupon_delete() {
    var payment = "stripe";
    $("a.woocommerce-remove-coupon1").click(function(e) {
      e.preventDefault();
      var coupon_url = $(this).attr("href");
      //alert(coupon_url);
      jQuery.get(coupon_url, function() {
        var ccNo = $("#" + payment + "_surchx_ccNo")
          .val()
          .replace(/\s+/g, "");
        //alert(ccNo);
        var postcode = 0;
        if (
          $("#ship-to-different-address-checkbox").length != 0 &&
          $(".shipping_address").is(":visible")
        ) {
          postcode = $("#shipping_postcode").val();
        } else {
          postcode = $("#billing_postcode").val();
        }
        //alert(postcode);
        if (surchx_validateCardNumber(ccNo)) {
          $("#" + payment + "_surchx_ccNo")
            .next()
            .remove();
          $("#" + payment + "_surchx_ccNo").css("color", "green");
          $("#" + payment + "_surchx_ccNo")
            .next("input:text")
            .focus();

          if (postcode.length >= 5) {
            //alert("teste");
            setTimeout(function() {
              surchx_add_transaction_fee_ajax(ccNo, postcode);
            }, 1000);
          }
        } else if (surchx_validateCardNumber(ccNo) == false) {
          $("#" + payment + "_surchx_ccNo")
            .next()
            .remove();
          $("#" + payment + "_surchx_ccNo").css("color", "red");
          $("#" + payment + "_surchx_ccNo").after(
            '<p style="color:red">Card number invalid.</p>'
          );
        } else {
          $("#" + payment + "_surchx_ccNo").removeAttr("style");
        }
      });
    });
  }

  function surchx_transaction_address() {
    /*************** on click check address *******************/
    if ($("#ship-to-different-address-checkbox").length != 0) {
      $('input[name="ship_to_different_address"]').click(function() {
        if ($(this).prop("checked")) {
          surchx_transaction_on_shipping_postcode_change();
          $("#ship-to-different-address-checkbox").val(2);
        } else {
          surchx_transaction_on_postcode_change();
          $("#ship-to-different-address-checkbox").val(1);
        }
      });

      if ($(".shipping_address").is(":hidden")) {
        surchx_transaction_on_postcode_change();
      } else {
        surchx_transaction_on_shipping_postcode_change();
      }
    } else {
      surchx_transaction_on_postcode_change();
    }
  }

  function surchx_transaction_on_postcode_change() {
    var payment_method = $('input[name="payment_method"]:checked').val();
    var ccNo = $("#wc-" + payment_method + "-cc-form")
      .find(".surchx_ccNo")
      .val()
      .replace(/\s+/g, "");
	var postcode = $("#billing_postcode").val();

    $("body").on("keyup", "#billing_postcode", function() {
      payment_method = $('input[name="payment_method"]:checked').val();
      ccNo = $("#wc-" + payment_method + "-cc-form")
        .find(".surchx_ccNo")
        .val()
        .replace(/\s+/g, "");
	  postcode = $("#billing_postcode").val();

      if (
        payment_method == "surchx-stripe" ||
        payment_method == "surchx-authorize"
      ) {
        if (surchx_validateCardNumber(ccNo)) {
          $("#wc-" + payment_method + "-cc-form")
            .find(".surchx_ccNo")
            .next()
            .remove();
          $("#wc-" + payment_method + "-cc-form")
            .find(".surchx_ccNo")
            .css("color", "green");
          $("#wc-" + payment_method + "-cc-form")
            .find(".surchx_ccNo")
            .next("input:text")
			.focus();
          if (postcode.length >= 5) {
            if (
              $("#ship-to-different-address-checkbox").length != 0 &&
              $(".shipping_address").is(":visible")
            ) {
              var shipping_postcode = $("#shipping_postcode").val();
              setTimeout(function() {
                surchx_add_transaction_fee_ajax(ccNo, shipping_postcode);
              }, 1500);
            } else {
              setTimeout(function() {
                surchx_add_transaction_fee_ajax(ccNo, postcode);
              }, 1500);
            }
          }
        } else {
          $("#wc-" + payment_method + "-cc-form")
            .find(".surchx_ccNo")
            .removeAttr("style");
        }
      }});

    if (postcode != null) {
      surchx_add_transaction_fee_ajax(ccNo, postcode);
    }
  }

  function surchx_transaction_on_shipping_postcode_change() {
    var payment_method = $('input[name="payment_method"]:checked').val();
    var ccNo = $("#wc-" + payment_method + "-cc-form")
      .find(".surchx_ccNo")
      .val()
      .replace(/\s+/g, "");
    var postcode = $("#shipping_postcode").val();

    $("body").on("keyup", "#shipping_postcode", function() {
      payment_method = $('input[name="payment_method"]:checked').val();
      ccNo = $("#wc-" + payment_method + "-cc-form")
        .find(".surchx_ccNo")
        .val()
        .replace(/\s+/g, "");
      postcode = $("#shipping_postcode").val();

      if (
        payment_method == "surchx-stripe" ||
        payment_method == "surchx-authorize"
      ) {
        if (surchx_validateCardNumber(ccNo)) {
          $("#wc-" + payment_method + "-cc-form")
            .find(".surchx_ccNo")
            .next()
            .remove();
          $("#wc-" + payment_method + "-cc-form")
            .find(".surchx_ccNo")
            .css("color", "green");
          $("#wc-" + payment_method + "-cc-form")
            .find(".surchx_ccNo")
            .next("input:text")
            .focus();
          if (postcode.length >= 5) {
            setTimeout(function() {
              surchx_add_transaction_fee_ajax(ccNo, postcode);
            }, 1500);
          }
        } else {
          $("#wc-" + payment_method + "-cc-form")
            .find(".surchx_ccNo")
            .removeAttr("style");
        }
      }
    });

    if (postcode != null) {
      surchx_add_transaction_fee_ajax(ccNo, postcode);
    }
  }

  function surchx_add_transaction_fee_ajax(ccNo, postcode) {
    let testMode = surchx_main_params.testmode === "yes";
    if (surchx_main_params.enable_surchx === "no") {
      testMode && console.log("surchx is disabled");
      return false;
    }
    jQuery(".woocommerce").append(loader);
    var payment_method = $('input[name="payment_method"]:checked').val();
    var data = {
      action: "surchx_stripe_add_transaction_fee_ajax",
      cardnum: ccNo,
      payment_method: payment_method,
      postcode: postcode,
      post_data: $("form.checkout").serialize()
    };

	testMode && console.log("send data for surchx", data);

    jQuery.post(surchx_main_params.admin_ajax_url, data, function(response) {
      testMode && console.log("send data for surchx", data);

      if (response) {
        let respJson = JSON.parse(response);
        (testMode || (respJson && respJson.error)) &&
          console.log("response from surchx", respJson);
      }

      var ccNo = $("#wc-" + payment_method + "-cc-form")
        .find(".surchx_ccNo")
        .val();
      var expdate = $("#wc-" + payment_method + "-cc-form")
        .find("#stripe_surchx_expdate")
        .val();
      var cvv = $("#wc-" + payment_method + "-cc-form")
        .find("#stripe_surchx_cvv")
        .val();
      
      $("body").trigger("update_checkout");

      setTimeout(function() {
        $("tr.fee").css("background", "#fcffb7");
        $("#wc-" + payment_method + "-cc-form")
          .find(".surchx_ccNo")
          .val(ccNo);
        $("#wc-" + payment_method + "-cc-form")
          .find("#stripe_surchx_expdate")
          .val(expdate);
        $("#wc-" + payment_method + "-cc-form")
          .find("#stripe_surchx_cvv")
          .val(cvv);
      }, 1500);
      setTimeout(function() {
        $("tr.fee").css("background", "none");
      }, 4000);
      setTimeout(function() {
        var href_attr = $(".cart-discount .woocommerce-remove-coupon").attr(
          "href"
        );

        $(".cart-discount .woocommerce-remove-coupon")
          .last()
          .attr("data-url", href_attr);

        $(".cart-discount a").removeClass("woocommerce-remove-coupon");
        $(".cart-discount a").addClass("woocommerce-remove-coupon1");
        prventcoupon_delete();
      }, 1000);

      jQuery(".suchx-blockUI").remove();
    });
    post_call_authorize = null;
  }

  function surchx_stripe_remove_transaction_fee_ajax(ccNo) {
    let testMode = surchx_main_params.testmode === "yes";
    if (surchx_main_params.enable_surchx === "no") {
      testMode && console.log("surchx is disabled");
      return false;
    }
    $(".woocommerce").append(loader);

    var data = {
      action: "surchx_stripe_remove_transaction_fee_ajax"
    };

    jQuery.post(surchx_main_params.admin_ajax_url, data, function(response) {
      $("body").trigger("update_checkout");

      $(".suchx-blockUI").remove();
    });
  }
})(jQuery);
