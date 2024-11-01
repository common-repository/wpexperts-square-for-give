                
	jQuery(document).ready(function () {
		let url_params = (window.location.href);
        url_params.action = 'get_form_id_give_square',
        url_params.form_id = jQuery('input[name=give-form-id]').val();

        jQuery.ajax(
		{
			type: "post",
			dataType: "json",
			url: sqpaymentform.ajax_url,
			data : {action: "get_form_id_give_square", formid : jQuery('input[name=give-form-id]').val()},
			success: function(msg){
				console.log(msg);
			}
		});
		
		
		/* jQuery(document).ajaxSuccess(function(event,jqXHR,options) {
			
			if(options.data.split('&')[0] == 'action=give_load_gateway'){
				jQuery( ".script_by_ajax" ).each(function(index, item) {
					console.log(jQuery(item).val());
					var script = document.createElement( 'script' );
					script.setAttribute( "src", jQuery(item).val() );
					document.getElementsByTagName( "head" )[0].appendChild( script );
				});
				console.log(options.data.split('&')[0]);
			}
		}); */
	});



			