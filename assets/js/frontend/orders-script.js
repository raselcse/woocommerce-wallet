jQuery(function($){
    // $('a.<?php echo $action_slug; ?>').each( function(){
    //     $(this).attr('target','_blank');
    // })
    var modalHtml ='<div id="myModal" class="modal">'
                        + '<div class="modal-content">'
                        +  '<div id="loader" class="lds-dual-ring hidden overlay"></div>'
                        + '     <span class="close">&times;</span>'
                        + '     <h3 style="text-align:center">Refund request form</h3>'
                        + '     <form class="refund_reason_form" name="refund_reason_form" method="post">'
                        + '         <textarea placeholder="Reason for refund" id="refund_reason" name="refund_reason"></textarea>'
                        + '         <input type="button" id="refund_reason_submit" value="Send" >'
                        + '      </form>'
                        + '</div>'
                    + '</div>';
    $('body').append(modalHtml);     
        
    var success_modal = '<div class="modal fade" id="success" role="dialog">'
                            + '<div class="modal-dialog">'
                        
                                +'<div class="modal-content" style="border:none;border-radius: 5px;">'
                                    +'<div class="modal-header" style="border-top-left-radius: 5px;border-top-right-radius: 5px;">'
                                    +'<h4 class="modal-title text-center"><img class="icon_image" src="" alt=""></h4>'
                                    +'</div>'
                                    +' <div class="modal-body">'
                                    +'<p class="info_message" style="text-align:center;font-size:24px;font-weight:500;">Nice! Your balance withdraw request successfully send</p>'
                                        
                                    +'</div>'
                                    +'<div class="modal-footer">'
                                    +'<button type="button" class="btn btn-default btn-close" data-dismiss="modal">Close</button>'
                                    +'</div>'
                                +'</div>'
                            
                            +'</div>'
                        + '</div>';
if ($("#success.modal").length == 0){
    $('body').append(success_modal);
}
     
    
    var modal = document.getElementById("myModal");

    // Get the button that opens the modal
    var btn = document.getElementsByClassName("refund_request");

    // Get the <span> element that closes the modal
    var span = document.getElementsByClassName("close")[0];
    var order_id = 0;
    $('a.refund_request').on('click', function(e){
        e.preventDefault();
        order_id =  $(this).attr('order-id');
        
        modal.style.display = "block";
        console.log(order_id);
    })
 
    $(span).on('click', function(){
        
        modal.style.display = "none";
    })
    // When the user clicks anywhere outside of the modal, close it
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    $("#refund_reason_submit").click( function(e) {
        e.preventDefault(); 
   
        nonce = wallet_param.nonce,
        refund_request_message = $('#refund_reason').val();
        console.log(order_id);
        $.ajax({
           type : "post",
           dataType : "json",
           beforeSend: function () { // Before we send the request, remove the .hidden class from the spinner and default to inline-block.
            $('#loader').removeClass('hidden')
        },
           url : wallet_param.ajax_url,
           data : {
               action: "wpcb_wallet_refund_request", 
               order_id : order_id, 
               refund_reason: refund_request_message,
               nonce: nonce
            },
           
           success: function(response) {
            
              if(response.success == true) {
                modal.style.display = "none";
                $('#loader').addClass('hidden');
                $('#success .modal-header').css('background-color','#1ab394');
                $('#success img.icon_image').attr('src', wallet_param.success_icon);
                $('#success p.info_message').text('Nice! Your order refund request successfully send');
                $('#success p.info_message').css('color','#1ab394');
                $("#success").show();
                if (document.referrer !== document.location.href) {
                    setTimeout(function() {
                        document.location.reload()
                  }, 5000);
                }
                
              }
              else {
                modal.style.display = "none";
                $('#loader').addClass('hidden');
                $('#success .modal-header').css('background-color','#d75a4a');
                $('#success img.icon_image').attr('src', wallet_param.close_icon);
                $('#success p.info_message').text(response.error);
                $('#success p.info_message').css('color','#d75a4a');
                $("#success").show();
                
              }
           }
        });
     });
});



