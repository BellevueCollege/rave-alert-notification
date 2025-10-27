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
					$alert["class"]   = "bg-warning text-bg-warning";
				} else {
					$alert["class"]   = "text-white bg-danger text-bg-danger";
				}
				return $alert;
			}
		}
		return false;
	}


	public function store_db_alert( $alert ) {
		if ( add_site_option( $this->option_name, $alert ) ) {
			self::clear_kinsta_cache();
			bc_rave_log( 'New alert saved to DB' );
			return 'Option Created';
		} elseif ( update_site_option( $this->option_name, $alert ) ) {
			self::clear_kinsta_cache();
			bc_rave_log( 'Existing alert updated in DB' );
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
		bc_rave_log( 'Trying to clear Kinsta Cache with URL: ' .  network_site_url('/kinsta-clear-cache-all') );
		$response = wp_remote_get( network_site_url('/kinsta-clear-cache-all'), [
			'sslverify' => false, 
			'timeout'   => 5
		] );

		bc_rave_log( 'Response Code: ' . $response['response']['code'] );

		if ( is_wp_error( $response ) ) {
			bc_rave_log( 'Cache Not Cleared: ' . $response->get_error_message(), true );
			return null;
		}

		if ( 200 !== $response['response']['code'] ) {
			bc_rave_log( 'Cache Not Cleared: ' . $response['response']['message'], true );
			return null;
		}

		if ( false === strpos( $response['body'], 'Cache has been cleared' ) ) {
			bc_rave_log( 'Cache Not Cleared: ' . $response['body'], true );
			return null;
		}

		bc_rave_log( 'Cache Cleared!' );
	}
} 