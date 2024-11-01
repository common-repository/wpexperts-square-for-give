jQuery(document).ready(function(){

    let fetch_url_params = (url) => {

        /* Fetching Last URI with Parameters */
        let url_slices = url.split('/');
        let last_uri = url_slices[url_slices.length - 1];
 
        /* Fetching .php page name from last URI */
        let last_uri_slices = last_uri.split('?');
        let admin_url = last_uri_slices[0];

        /* Separating GET parameters from last URI */
        let url_params = {};
        let parts = last_uri.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {

            if(key == 'action') { 

                url_params['url_action'] = value;
 
            }else{

                url_params[key] = value;
            }

        });

        url_params.admin_url = admin_url;

        return url_params;
    }

    let init_square_connection = (event) => {
        event.preventDefault();

        let data = fetch_url_params(window.location.href);
        data.action = 'my_action'

        jQuery.get(ajaxurl, data, function(response) {
            window.location = response;
        });

    }


    /* Initialize update/connect button on general and custom form page */

    let init_square_button = (update = false, request_from = false) =>
    {
        let url_params = fetch_url_params(window.location.href); 
        if(url_params.admin_url !== 'post-new.php'){

            let connect_to_square_button_html;
            if(update == true){

                connect_to_square_button_html = '<button id="gs_connect_square_btn" class="wc-square-connect-button"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 44 44" width="30" height="30"><path fill="#FFFFFF" d="M36.65 0h-29.296c-4.061 0-7.354 3.292-7.354 7.354v29.296c0 4.062 3.293 7.354 7.354 7.354h29.296c4.062 0 7.354-3.292 7.354-7.354v-29.296c.001-4.062-3.291-7.354-7.354-7.354zm-.646 33.685c0 1.282-1.039 2.32-2.32 2.32h-23.359c-1.282 0-2.321-1.038-2.321-2.32v-23.36c0-1.282 1.039-2.321 2.321-2.321h23.359c1.281 0 2.32 1.039 2.32 2.321v23.36z" /><path fill="#FFFFFF" d="M17.333 28.003c-.736 0-1.332-.6-1.332-1.339v-9.324c0-.739.596-1.339 1.332-1.339h9.338c.738 0 1.332.6 1.332 1.339v9.324c0 .739-.594 1.339-1.332 1.339h-9.338z" /></svg><span>Update Square Connection</span></button>';
            
            }else{
                
                connect_to_square_button_html = '<button id="gs_connect_square_btn" class="wc-square-connect-button"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 44 44" width="30" height="30"><path fill="#FFFFFF" d="M36.65 0h-29.296c-4.061 0-7.354 3.292-7.354 7.354v29.296c0 4.062 3.293 7.354 7.354 7.354h29.296c4.062 0 7.354-3.292 7.354-7.354v-29.296c.001-4.062-3.291-7.354-7.354-7.354zm-.646 33.685c0 1.282-1.039 2.32-2.32 2.32h-23.359c-1.282 0-2.321-1.038-2.321-2.32v-23.36c0-1.282 1.039-2.321 2.321-2.321h23.359c1.281 0 2.32 1.039 2.32 2.321v23.36z" /><path fill="#FFFFFF" d="M17.333 28.003c-.736 0-1.332-.6-1.332-1.339v-9.324c0-.739.596-1.339 1.332-1.339h9.338c.738 0 1.332.6 1.332 1.339v9.324c0 .739-.594 1.339-1.332 1.339h-9.338z" /></svg><span>Connect with Square</span></button>';
            }

            jQuery('.square_connect_button_field').html(connect_to_square_button_html);
            jQuery('#square_connect_button').replaceWith( connect_to_square_button_html );
            jQuery('#gs_connect_square_btn').on('click', init_square_connection);
            jQuery('.Button_field').show();
            jQuery('#square_connect_button').show();
            jQuery('.square_locationid_field').hide();

            if(request_from == 'general'){
                $('#square_locationid').parent().parent().hide();
            }
            
            
        }

    }


    /* Initialize disconnect button on general and custom form page */

    let init_square_disconnect_button = () =>
    {

       
        let url_params = fetch_url_params(window.location.href);
        if(url_params.admin_url !== 'post-new.php'){
             
            let connect_to_square_button_html = '<button id="gs_connect_square_btn" class="wc-square-connect-button"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 44 44" width="30" height="30"><path fill="#FFFFFF" d="M36.65 0h-29.296c-4.061 0-7.354 3.292-7.354 7.354v29.296c0 4.062 3.293 7.354 7.354 7.354h29.296c4.062 0 7.354-3.292 7.354-7.354v-29.296c.001-4.062-3.291-7.354-7.354-7.354zm-.646 33.685c0 1.282-1.039 2.32-2.32 2.32h-23.359c-1.282 0-2.321-1.038-2.321-2.32v-23.36c0-1.282 1.039-2.321 2.321-2.321h23.359c1.281 0 2.32 1.039 2.32 2.321v23.36z" /><path fill="#FFFFFF" d="M17.333 28.003c-.736 0-1.332-.6-1.332-1.339v-9.324c0-.739.596-1.339 1.332-1.339h9.338c.738 0 1.332.6 1.332 1.339v9.324c0 .739-.594 1.339-1.332 1.339h-9.338z" /></svg><span> Disconnect Square </span></button>';
            jQuery('#square_connect_button').replaceWith( connect_to_square_button_html );
            jQuery('#gs_connect_square_btn').on('click', disconnect_square_connection);
            jQuery('#square_connect_button').show();
            add_locations_dropdown();
        }

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
             
            locations.forEach(function(element) {
    
                var o = new Option(element.location_details.nickname, element.id);
                jQuery("#square_locationid").append(o);

            });
            
            jQuery('.square_locationid_field').show();
            $('#square_locationid').parent().parent().show();

        });

    }


    /* Initialize OAuth Button on Specific Form page */

    let square_oauth_btn_update = () => {

        let url_params = fetch_url_params(window.location.href);
        url_params.action = 'oauth_status_update'

        jQuery.get(ajaxurl, url_params, function(response){
            let data = JSON.parse(response);

            if(data.oauth_preferences == 'general'){

                init_square_button();
    
            }

            if(data.square_token == false && data.square_btn_auth == false){

                init_square_button();
        
            }

            if(data.square_token == true && data.square_btn_auth == true){
                
                init_square_disconnect_button();

            }

            if(data.square_token == true && data.square_btn_auth == false){

                init_square_button(true);
    
            }

        });
    
    }

    /* OAuth preference radio buttons */

    jQuery('.oauth_preferences').change(function(){

        if(jQuery(this).val() == 'general'){

            jQuery('.square_connect_button_field').hide(600);
            jQuery('.square_locationid_field').hide(600);
            // alert(jQuery('#square_locationid option').length);
            
        }else{

            jQuery('.square_connect_button_field').show(600);
            if(jQuery('#square_locationid option').length !== 0){
                jQuery('.square_locationid_field').show(600);
            }

        }

    });

    if(jQuery("input[name='oauth_preferences']:checked").val() == 'custom'){

            jQuery('.square_connect_button_field').show(600);

    }

    /* Initialize OAuth Button on Settings Page */

    let url_params = fetch_url_params(window.location.href);

    if(url_params.post_type !== undefined && url_params.post_type == 'give_forms'){
        
        if(typeof gs_script !== 'undefined' && gs_script.connected !== 'false' && gs_script.token !== 'false'){
            
            init_square_disconnect_button();

        }else if(typeof gs_script !== 'undefined' && gs_script.connected == 'false' && gs_script.token !== 'false'){
            
            init_square_button(true, 'general');

        }else{

            init_square_button(false, 'general');

        }

    }

  
    square_oauth_btn_update();

});