jQuery(document).ready(function(){

    const current_url = fetch_url_params(window.location.href); /* Getting Current Page URL */

    /* OAuth preference radio buttons */

    jQuery('.oauth_preferences').change(function(){

        if(jQuery(this).val() == 'general'){

            jQuery('.square_connect_button_field').hide(600);
            jQuery('.square_locationid_field').hide(600);
            // alert(jQuery('#square_locationid option').length);
            
        }else{ 

            square_oauth_btn_update();

            jQuery('.square_connect_button_field').show(600);
            if(jQuery('#square_locationid option').length !== 0){
                jQuery('.square_locationid_field').show(600);
            }

        } 

    });
 

    
    /* If General Settings Page */
    if(typeof current_url.post_type !== 'undefined' && current_url.post_type == 'give_forms' && current_url.page == 'give-settings' && current_url.section == 'square'){

        if(typeof gs_script !== 'undefined' && gs_script.connected !== null && gs_script.token !== null){
                            
            init_square_disconnect_button();

        }else if(typeof gs_script !== 'undefined' && gs_script.connected == null && gs_script.token !== null){
            
            init_square_button(true, 'general');

        }else{

            init_square_button(false, 'general');

        }
 
    }


    if(current_url.post !== null && current_url.url_action == 'edit'){

        let url_params = fetch_url_params(window.location.href);
        url_params.action = 'oauth_status_update'

        jQuery.get(ajaxurl, url_params, function(response){

            let data = JSON.parse(response);
            if(data.oauth_preferences !== 'general'){
                square_oauth_btn_update();
            }

        });

    }

    let square_oauth_btn_update = () => {

        jQuery('#square_locationid').empty();
        let url_params = fetch_url_params(window.location.href);
        url_params.action = 'oauth_status_update'

        jQuery.get(ajaxurl, url_params, function(response){
            let data = JSON.parse(response);

            if(data.square_token == false && data.square_btn_auth == false){

                init_square_button();
        
            } 

            if(data.square_token !== false && data.square_btn_auth !== false){
                
                init_square_disconnect_button();

            }

            if(data.square_token !== false && data.square_btn_auth == false){

                init_square_button(true);
    
            }

        });
    
    }

   

});








