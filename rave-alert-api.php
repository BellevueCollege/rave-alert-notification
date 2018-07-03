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
     *
     * Register the REST routes
    */

    //register rest endpoints and provide callback to the handler
    function rest_register_routes( ) {
        $version = self::$rest_version;
        $namespace = 'rave/v' . $version; //declares the home route of the REST API

        //registered route tells the API to respond to a given request with a callback function
        //this is one route with one endpoint method GET requesting a parameter ID on the URL


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

        public static function rave_check_for_alert() {

            $alert = Rave_Alert_API::rave_load_alert();

            if ( $alert ) {
                return $alert->identifier;
            } else {
                return false;
            }

        }

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
                    
                    //error_log('_API_ False transient');

                    //construct the alert
                    $alert = array(
                        'identifier' => $data['identifier']->__toString(),
                        'info' => array(
                            'event' => $data['event']->__toString(),
                            'severity' => $data['severity']->__toString(),
                            'headline' => $data['headline']->__toString(),
                            'description' => $data['description']->__toString(),
                        )
                    );
                    $alert_json = json_encode($alert);
                    //set the transient
                    $set_transient = set_transient('rave_alert_data', $alert_json, 60);

                    return json_decode($alert_json);

                    //$set_transient ? error_log("_API_ Rest Transient Set") : error_log("_API_ Rest Transient NOT Set");
                    
                } else { //returns transient data if there is a transient

                    //error_log('_API_ Good transient');
                    return json_decode($rave_transient);
                }
            }

        }

    /*
        * rave_save_alert() is a callback function passed into a registered route
        * to send alert data via REST
        * @param WP_REST_Request $request The request object being served from the registered route
        * @return WP_REST_Response $data in JSON, an array of alert info
    */
    public static function rave_return_alert_data( WP_REST_Request $request) {
        $parameters = $request->get_url_params(); //get the URL parameters from the request
        $rave_identifier = $parameters['identifier']; //get only the identifier passed into URL parameters from the registered route
        
        if ( preg_match( '/^(\d{21})$/', $rave_identifier ) ) {
            $alert = Rave_Alert_API::rave_load_alert();
            //error_log(print_r($alert->identifier, true));

            // If the identifiers match
            if ( $rave_identifier === $alert->identifier ) {
                //error_log(print_r($data['identifier']->__toString(), true));
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

$rave_alert_api = new Rave_Alert_API();