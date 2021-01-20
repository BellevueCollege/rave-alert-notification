<?php
/*
Plugin Name: Rave Alert Notification
Plugin URI: https://github.com/BellevueCollege/rave-alert-notification
Description: Sends Rave Alert notification to Bellevue College WordPress sites.
Author: Bellevue College IT Services
Version: 1.8.0.1-b2
Author URI: https://www.bellevuecollege.edu
GitHub Plugin URI: bellevuecollege/rave-alert-notification
*/

require_once('classes/class-cap-alert.php');
require_once('classes/class-open-message.php');
require_once('alert-config.php');
require_once('rave-alert-api.php');

/*
 * Enqueue Ajax scripts
 * Script calls Ajax after x amount of miliseconds to keep page updating every x miliseconds
 */
function bc_rave_enqueue_ajax() {
    $rest_url           = network_site_url( '/wp-json/rave/v' . Rave_Alert_API::$rest_version ) . '/alerts/';
    $more_info_message  = bc_rave_return_more_info_link();

    //Get college open message: returns an array of description and class
    $open_message_data  = Open_Message::get_message();
    $open_message_desc  = isset( $open_message_data['description'] ) ? $open_message_data['description'] : null;
    $open_message_class = isset( $open_message_data['class'] ) ? $open_message_data['class'] : null;

    //checks if current site is the homepage
    $current_site = get_site_url() . '/';
    $homepage_site = network_home_url();
    $is_homepage = ( $current_site == $homepage_site ? true : false );

    $rest_variables = 'var rest_php_variables = {
                                                    rest_url: "' . $rest_url . '", 
                                                    more_info_message: "' . $more_info_message . '",
                                                    open_message_desc: "' . addslashes(stripslashes($open_message_desc)) . '",
                                                    open_message_class: "' . addslashes(stripslashes($open_message_class)) . '",
                                                    is_homepage: "' . $is_homepage . '"
                                                };';
    wp_enqueue_script( 'rave-alert-ajax', plugin_dir_url( __FILE__ ) . 'js/rave-alert-ajax.js#asyncdeferload', array('jquery'), '1.8b2', true );
    wp_add_inline_script( 'rave-alert-ajax', $rest_variables, 'before' );

}
add_action( 'wp_enqueue_scripts', 'bc_rave_enqueue_ajax' );


/*
 *	Cron Job for RaveAlert.
 */
add_filter('cron_schedules', 'bc_rave_interval');

// add once every 1 minute interval to wp schedules
function bc_rave_interval( $interval ) {
	$interval['minutes_1'] = array('interval' => 1*60, 'display' => 'Once every 1 minute');
	return $interval;
}

if ( ! wp_next_scheduled( 'rave_cron' ) ) { 
	wp_schedule_event( time(), 'minutes_1', 'rave_cron' ); 
}

add_action( 'rave_cron', 'bc_rave_cron' );

function bc_rave_cron() {
	
	if ( is_main_site() ) {
		//error_log("############################ CRON TAB is Running #######################");

		$bc_rave_alert = new CAP_Alert();
		$alert_data = $bc_rave_alert->get_alert();
		$response = $bc_rave_alert->store_db_alert( $alert_data );

		$return_post_id = bc_rave_create_rave_post( $alert_data );

		wp_clear_scheduled_hook( 'rave_cron' );
		//error_log("############################ CRON TAB is Finished #######################");
	}
}

/* 
 * Return 'More information' link
 */

function bc_rave_return_more_info_link(){
	/* Load settings */
	$network_settings = get_site_option( 'ravealert_network_settings' );
	$ravealert_do_archive = $network_settings['ravealert_do_archive'];
	$more_info_site_id = $network_settings['ravealert_archive_site'];

	$more_info_message = "";

	/* Check if archive site is set */
	if ( $more_info_site_id != "" && $ravealert_do_archive == "true") {
		$more_info_site = get_blog_details( $more_info_site_id )->path;
		$more_info_message = "<a href='" . $more_info_site . "'>More Information.</a>";
	}

	return $more_info_message;
}



/*
* Create new post
*/
function bc_rave_create_rave_post( $xml_data ) {
	$post_return_value = 0;
	//error_log("identifier :".$xml_data["identifier"]);

	if (
		 isset( $xml_data ) && 
		 isset( $xml_data["event"] ) && 
		 isset( $xml_data["headline"] ) && 
		 isset( $xml_data["description"] ) && 
		 isset( $xml_data["identifier"] ) ) {

		$event = $xml_data["event"];
		$headline = $xml_data["headline"];
		$description = $xml_data["description"];
		$identifier = $xml_data["identifier"];
		$network_settings = get_site_option( 'ravealert_network_settings' );
		$check_to_archive = $network_settings['ravealert_do_archive'];
		$archive_blog_id = $network_settings['ravealert_archive_site'];
		
		if ( $check_to_archive == "true" ) {
			switch_to_blog( $archive_blog_id );
			global $wpdb;

			//Check if slug exists
			//error_log('Does Slug Exist?');
			$query = new WP_Query( array( 'name' => $identifier ) ); if ( $query->post_count === 0 ) {// I am unique!}

			$description = $headline . "<!--more-->" . $description;
			$post_args = array(
				'post_name'   => $identifier,
				'post_title'  => $event,
				'post_content'  => $description,
				'post_status'   => 'publish',
				'post_author'   => 1,
			);
			$post_return_value = wp_insert_post( $post_args );
			//error_log('POST CREATED');

		}
		restore_current_blog();

		}

	}

	return $post_return_value;
}

?>
