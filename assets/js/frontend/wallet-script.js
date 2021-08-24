jQuery(function($){
   

    var modal = document.getElementById("request_balance_withdraw_modal");


    // Get the <span> element that closes the modal
    var span = document.getElementsByClassName("close")[0];
    
    $('a#balance_withdrow_button').on('click', function(e){
        e.preventDefault();
        modal.style.display = "block";
    })
 
    $('.modal .close,.modal-footer .btn-close').on('click', function(){
        
        $('.modal').hide();
    })
    
    // When the user clicks anywhere outside of the modal, close it
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
            $('.modal').style.display = "none";
        }
    }
    $("#withdraw_method").change(function() {
        var val = $(this).val();
        if(val === "bkash") {
            $("#bkash-section").show();
            $("#bank-section").hide();
        }
        else if(val === "bank") {
            $("#bank-section").show();
            $("#bkash-section").hide();
        }
      });

    $("#balance_widrow_submit").click( function(e) {
        e.preventDefault(); 
        var withdraw_data =[];
        nonce = wallet_param.balance_withdraw_nonce,
        
        $.ajax({
            type : "post",
            dataType : "json",
            beforeSend: function () { // Before we send the request, remove the .hidden class from the spinner and default to inline-block.
                $('#loader').removeClass('hidden')
            },
           url : wallet_param.ajax_url,
           data : {
               action: "wpcb_wallet_balance_withdraw_request", 
               nonce: nonce,
               withdraw_amount: $('#withdraw_amount').val(),
               withdraw_method: $('#withdraw_method').val(),
               bkash_account_name :$('#bkash_account_name').val(),
               bkash_account_number   : $('#bkash_account_number').val(),
               bkash_account_type  : $('#bkash_account_type').val(),
               bank_name  : $('#bank_name').val(),
               bank_branch_name : $('#bank_branch_name').val(),
               bank_account_name : $('#bank_account_name').val(),
               bank_account_number: $('#bank_account_number').val(),
               others_note: $('#others_note').val(),
            },
           
           success: function(response) {
            
              if(response.success == true) {
                modal.style.display = "none";
                $('#loader').addClass('hidden')
                $("#success").show();
                 
                if (document.referrer !== document.location.href) {
                    setTimeout(function() {
                        document.location.reload()
                  }, 5000);
                }
                
              }
              else {
                modal.style.display = "none";
                $('#loader').addClass('hidden')
                
                $('#fail .modal-body .error_msg').empty().text(response.error);
                $("#fail").show();
                //location.reload();

              }
           }
           
        });
     });
});



