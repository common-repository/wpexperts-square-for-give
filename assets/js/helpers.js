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

