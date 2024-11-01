/* Initialize update/connect button on general and custom form page */

let init_square_button = (update = false, request_from = false) =>
{
    let url_params = fetch_url_params(window.location.href); 

        let connect_to_square_button_html;
        
        if(update == true){ 

            connect_to_square_button_html = '<button id="gs_connect_square_btn" class="wc-square-connect-button"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 44 44" width="30" height="30"><path fill="#FFFFFF" d="M36.65 0h-29.296c-4.061 0-7.354 3.292-7.354 7.354v29.296c0 4.062 3.293 7.354 7.354 7.354h29.296c4.062 0 7.354-3.292 7.354-7.354v-29.296c.001-4.062-3.291-7.354-7.354-7.354zm-.646 33.685c0 1.282-1.039 2.32-2.32 2.32h-23.359c-1.282 0-2.321-1.038-2.321-2.32v-23.36c0-1.282 1.039-2.321 2.321-2.321h23.359c1.281 0 2.32 1.039 2.32 2.321v23.36z" /><path fill="#FFFFFF" d="M17.333 28.003c-.736 0-1.332-.6-1.332-1.339v-9.324c0-.739.596-1.339 1.332-1.339h9.338c.738 0 1.332.6 1.332 1.339v9.324c0 .739-.594 1.339-1.332 1.339h-9.338z" /></svg><span>Update Square Connection</span></button>';
        
        }else{ 

            connect_to_square_button_html = '<button id="gs_connect_square_btn" class="wc-square-connect-button"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 44 44" width="30" height="30"><path fill="#FFFFFF" d="M36.65 0h-29.296c-4.061 0-7.354 3.292-7.354 7.354v29.296c0 4.062 3.293 7.354 7.354 7.354h29.296c4.062 0 7.354-3.292 7.354-7.354v-29.296c.001-4.062-3.291-7.354-7.354-7.354zm-.646 33.685c0 1.282-1.039 2.32-2.32 2.32h-23.359c-1.282 0-2.321-1.038-2.321-2.32v-23.36c0-1.282 1.039-2.321 2.321-2.321h23.359c1.281 0 2.32 1.039 2.32 2.321v23.36z" /><path fill="#FFFFFF" d="M17.333 28.003c-.736 0-1.332-.6-1.332-1.339v-9.324c0-.739.596-1.339 1.332-1.339h9.338c.738 0 1.332.6 1.332 1.339v9.324c0 .739-.594 1.339-1.332 1.339h-9.338z" /></svg><span>Connect with Square</span></button>';
        }
        
        jQuery('#square_connect_button').replaceWith(connect_to_square_button_html);
        
        jQuery('#gs_connect_square_btn').on('click', init_square_connection);
        jQuery('.square_locationid_field').hide();
        
        jQuery("#square_connect_button").show();
        jQuery(".square_connect_button_field").show();
        if(request_from == 'general'){
            jQuery('#square_locationid').parent().parent().hide();
        }


}
 
let init_square_connection = (event) => {

    event.preventDefault(); 

    let data = fetch_url_params(window.location.href);
    data.action = 'my_action'

    jQuery.get(ajaxurl, data, function(response) {
        window.location = response;
    });

}


/* Initialize disconnect button on general and custom form page */

let init_square_disconnect_button = () =>
{

   
    let url_params = fetch_url_params(window.location.href);
         
        let connect_to_square_button_html = '<button id="gs_connect_square_btn" class="wc-square-connect-button"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 44 44" width="30" height="30"><path fill="#FFFFFF" d="M36.65 0h-29.296c-4.061 0-7.354 3.292-7.354 7.354v29.296c0 4.062 3.293 7.354 7.354 7.354h29.296c4.062 0 7.354-3.292 7.354-7.354v-29.296c.001-4.062-3.291-7.354-7.354-7.354zm-.646 33.685c0 1.282-1.039 2.32-2.32 2.32h-23.359c-1.282 0-2.321-1.038-2.321-2.32v-23.36c0-1.282 1.039-2.321 2.321-2.321h23.359c1.281 0 2.32 1.039 2.32 2.321v23.36z" /><path fill="#FFFFFF" d="M17.333 28.003c-.736 0-1.332-.6-1.332-1.339v-9.324c0-.739.596-1.339 1.332-1.339h9.338c.738 0 1.332.6 1.332 1.339v9.324c0 .739-.594 1.339-1.332 1.339h-9.338z" /></svg><span> Disconnect Square </span></button>';
        jQuery('#square_connect_button').replaceWith( connect_to_square_button_html );
        jQuery('#gs_connect_square_btn').on('click', disconnect_square_connection);
        jQuery('.square_connect_button_field').show();
        jQuery('#square_connect_button').show();
        add_locations_dropdown();

}

/* Disconnect Square Connection | Handles both General and Custom connection */

let disconnect_square_connection = () => {

    event.preventDefault();

    let data = fetch_url_params(window.location.href);
    data.action = 'my_dc_action'

    jQuery.get(ajaxurl, data, function(response) {
        window.location = response;
    });
 
}


/* Add Location dropdown to both Settings and Specific Form page */

let add_locations_dropdown = () => { 
    

    let url_params = fetch_url_params(window.location.href);
    url_params.action = 'gas_location_dropdown'
    jQuery.get(ajaxurl, url_params, function(response) {

        let locations = JSON.parse(response);
		
		
        let locationsps = JSON.parse(locations.gs_live_locations);
		
        locationsps.forEach(function(element) {
         
            if(locations.square_locationid == element.id){
            var o = new Option(element.name, element.id, true , true);
			} else {
                  var o = new Option(element.name, element.id);
                }
            jQuery("#square_locationid").append(o);

        });
        
        jQuery('.square_locationid_field').show();
        jQuery('#square_locationid').parent().parent().show();

    });

}