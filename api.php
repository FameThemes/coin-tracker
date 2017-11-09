<?php

// Run an anonymous function in to the REST API init hook

function coin_tracker_endpoint() {

    // Set up the required information
    $namespace = 'coin/';
    $endpoint = '(?P<coin_code>\w+)';
    $args = array(
        // How the endpoint needs to be called
        'methods' => 'GET',
        // The callback function
        'callback' => 'coin_tracker_get_tracker_api',
    );
    // Add the route 'coin/<coin_code>' to the WP REST API
    register_rest_route( $namespace, $endpoint, $args );
}


add_action( 'rest_api_init', 'coin_tracker_endpoint');



function coin_tracker_get_tracker_api( WP_REST_Request $request )
{

    $code = $request->get_param('coin_code');
    return coin_tracker_get_data( 20, $code );

}
