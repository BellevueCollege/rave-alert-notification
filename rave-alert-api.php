<?php

/**
 * 
 * Rave Alert API
 * 
*/

class Rave_Alert_API {

    public function __construct() {
        add_action( 'rest_api_init' , array( $this,'rest_register_routes') ); //initiate REST API
    }

    public static $rest_version = '1'; //initiate version of this REST API

    /**
     * Rave Alerts Initiate REST API
     * Register the REST routes
    */

    //register rest endpoints and provide callback to the handler
    function rest_register_routes( ) {
        $version = self::$rest_version;
        $namespace = 'rave/v' . $version; //declares the home route of the REST API

        //registered route tells the API to respond to a given request with a callback function

        register_rest_route( $namespace, '/alerts/', array(
        'methods' => 'GET',
        'callback' => array( $this, 'rave_check_for_alert'),
        ) );

        register_rest_route( $namespace, '/alerts/(?P<identifier>\d+)', array(
        'methods' => 'GET',
        'callback' => array( $this, 'rave_return_alert_data'),
            'args' => array(
                'identifier' => array(
                'validate_callback' => function($param, $request, $key) {
                        return is_numeric( $param );
                    }
                ),
            ),
        ) );
    }

    /*
        * rave_check_for_alert() checks if rave_load_alert() returns an alert
        * @return identifier of the alert if active or false
    */
    public static function rave_check_for_alert() {

        $alert = self::rave_load_alert();

        if ( $alert ) {
            $alert_info = array(
                'identifier' => $alert->identifier,
                'severity' => $alert->info->severity
            ); 
            return $alert_info;
        } else {
            return false;
        }

    }

    /*
        * rave_load_alert() checks if alert is active, loads alert data and sets the alert transient
        * @return alert data via transient
    */
    public static function rave_load_alert() {

        $network_settings = get_site_option( 'ravealert_network_settings' );
        $url = $network_settings['ravealert_xml_feedurl'];
        $data = cap_parse($url);

        //check if alert is active: if $data returns empty it's false, if not empty then true
        $rest_valid_id = ( !empty($data) ? true : false );

        //constructs alert if alert is active
        if ( $rest_valid_id == true ) {
    
            //does transient exist?
            $rave_transient = get_transient('rave_alert_data'); //get transient
            
            //transient is empty/doesn't exist, so create new transient
            if ( false === $rave_transient) {
            
                //construct the alert
                $alert = array(
                    'identifier' => $data['identifier']->__toString(),
                    'info' => array(
                        'event' => $data['event']->__toString(),
                        'severity' => $data['severity']->__toString(),
                        'headline' => $data['headline']->__toString(),
                        'description' => $data['description']->__toString(),
                        'class' => $data['class'] //is a string
                    )
                );
                $alert_json = json_encode($alert);

                //set the transient for 60 seconds
                $set_transient = set_transient('rave_alert_data', $alert_json, 60);

                return json_decode($alert_json);
 
            } else { //returns transient data if there is a transient
                return json_decode($rave_transient);
            }
        }
        //returns nothing is alert is not active

    }

    /*
        * rave_return_alert_data() is a callback function passed into a registered route to validate and send alert data via REST
        * @param WP_REST_Request $request The request object being served from the registered route
        * @return WP_REST_Response $alert in JSON, an array of alert info
    */
    public static function rave_return_alert_data( WP_REST_Request $request) {
        $parameters = $request->get_url_params(); //get the URL parameters from the request
        $rave_identifier = $parameters['identifier']; //get only the identifier passed into URL parameters from the registered route
        
        if ( preg_match( '/^(\d{22})$/', $rave_identifier ) ) {
            $alert = self::rave_load_alert();

            // If the identifiers match
            if ( $rave_identifier === $alert->identifier ) {

                // send the data as a JSON response
                return new WP_Rest_Response($alert, 200);

            } else {
                return new WP_Error( 'alert_does_not_exist', __('The alert you are looking for does not exist'), array( 'status' => 404 ) );
            }
        }
        else {
            return new WP_Error( 'incorrect_alert_identifier', __('The alert identifier is incorrect or the wrong format'), array( 'status' => 400 ) );
        }          
    }
}

// Instantiate on the main site in network only
if ( is_main_site() ) {
	$rave_alert_api = new Rave_Alert_API();
}
