<?php

class CAP_Alert {

	protected $url;
	protected $option_name = 'bc_rave_alert';

	function __construct() {
		$bc_rave_network_settings = get_site_option( 'ravealert_network_settings' );

		// Construct the URL from the network settings and the UNIX timestamp
		$url = $bc_rave_network_settings['ravealert_xml_feedurl'] . '?t=' . time();
		$this->url = $url;
	}

	public function get_alert(){

		//Load XML File and get values
		
		//$url = 'http://www.getrave.com/cap/bellevuecollege/channel3';
		$xml = simplexml_load_file( $this->url );
		//$xml = simplexml_load_file($url) or die("Rave Alert Error: Cannot create object.");
		
		if ( !empty( $xml ) ) {
			$identifier  = (string)$xml->identifier;
			$msg_type    = (string)$xml->msgType;
			$event       = (string)$xml->info->event;
			$description = (string)$xml->info->description;
			$headline    = (string)$xml->info->headline;
			$effective   = strtotime($xml->info->effective); // No use for us since the expiration time is calculated basis on the sent time by CAP.
			$sent        = strtotime($xml->sent);
			$expires     = strtotime($xml->info->expires);
			$severity    = (string)$xml->info->severity;
	
			//Get current time
			$time = time();

			//Test to see if current time is between effective time and expire time, and alert hasn't been cancelled
			if ( 'cancel' !== strtolower( $msg_type ) && ( $time > $sent ) && ( $time < $expires ) ) {
				$alert = array();

				//If true, print HTML using event and description info
				$alert["identifier"]  = $identifier;
				$alert["description"] = $description;
				$alert["headline"]    = $headline;
				$alert["event"]       = $event;
				$alert["severity"]    = $severity;

				if ( 'minor' === strtolower( $severity ) ) {
					$alert["class"]   = "alert alert-info";
				} else {
					$alert["class"]   = "alert alert-danger";
				}
				return $alert;
			}
		}
		return false;
	}


	public function store_db_alert( $alert ) {
		if ( add_site_option( $this->option_name, $alert ) ) {
			// self::clear_kinsta_cache();
			return 'Option Created';
		} elseif ( update_site_option( $this->option_name, $alert ) ) {
			// self::clear_kinsta_cache();
			return 'Option Updated';
		} else {
			return 'Option Not Updated';
		}
	}

	public function get_db_alert( ) {
		$alert = get_site_option( $this->option_name, 'Nothing in DB' );
		return $alert;
	}

	// This function should probably go somewhere else...
	public static function clear_kinsta_cache() {
		// @file_get_contents( network_home_url() . 'kinsta-clear-cache/wp-json/rave/v1/alerts/' ); -- non-functional at this time. Cache is cleared via a cachebuster query
	}
} 