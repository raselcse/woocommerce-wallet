jQuery(document).ready(function( $ ) {
 
 var column_settings = [
	{ "width": "50px", "targets": 0 },
	{ "width": "100px", "targets": 1 },
	{ "width": "50px", "targets": 2 },
	{ "width": "80px", "targets": 3 },
	{ "width": "100px", "targets": 4 },
	//{ "width": "80px", "targets": 5 },
	{"className": "dt-center", "targets": "_all"}
  ];
 $('#cash-wallet-transaction').DataTable({

			"processing":true,

			"serverSide":true,

			"searching": false,

			"iDisplayLength": 10,

			"bLengthChange": false,

			"paging": true,

			"language": {

				"lengthMenu": "Display _MENU_ records per page",

				"zeroRecords": "No Data Found",

				//"info": "Visar _START_  - _END_ av totalt _TOTAL_ medlemmar",

				"infoEmpty": "No records available",

				"infoFiltered": "(filtered from _MAX_ total records)"

			},
			"columnDefs": column_settings,
			"order": [[ 0, "desc" ]],
			"ajax":{

				url:wallet_param.ajax_url,

				type:"post",

				data : {

					'action' : 'get_user_transaction',
					'wallet_type': 'cash'
				}

			}

		});


		$('#site-wallet-transaction').DataTable({

			"processing":true,

			"serverSide":true,

			"searching": false,

			"iDisplayLength": 10,

			"bLengthChange": false,

			"paging": true,

			"language": {

				"lengthMenu": "Display _MENU_ records per page",

				"zeroRecords": "No Data Found",

				//"info": "Visar _START_  - _END_ av totalt _TOTAL_ medlemmar",

				"infoEmpty": "No records available",

				"infoFiltered": "(filtered from _MAX_ total records)"

			},
			"columnDefs": column_settings,
			"order": [[ 0, "desc" ]],
			"ajax":{

				url:wallet_param.ajax_url,

				type:"post",

				data : {

					'action' : 'get_user_transaction',
					'wallet_type': 'site'
				}

			}

		});


		// Disable scroll when focused on a number input.
		$('.modal-content form').on('focus', 'input[type=number]', function(e) {
			$(this).on('wheel', function(e) {
				e.preventDefault();
			});
		});

		  // Disable up and down keys.
		  $('.modal-content form').on('keydown', 'input[type=number]', function(e) {
			if ( e.which == 38 || e.which == 40 )
				e.preventDefault();
		}); 
   


});