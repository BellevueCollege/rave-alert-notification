<?php

class Open_Message {
	static function get_message() {
		$network_settings = get_site_option( 'ravealert_network_settings' );
		$high_alert = $network_settings['high_alert'];
		$open_message = $network_settings['ravealert_college_openmessage'];
		$message = array();

		if ( $high_alert === 'true' ) {
			if ( $open_message ) {
				$message["description"] = $open_message;
				$message["class"] = "alert alert-success";
			} else {
				$message["description"] = "";
				$message["class"] = "";
			}
		}

		return $message;
	}// End of getOpenMsg function
}