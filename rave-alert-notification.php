<?php
/*
Plugin Name: Rave Alert Notification
Plugin URI: https://github.com/BellevueCollege/rave-alert-notification
Description: Sends Rave Alert notification to Bellevue College WordPress sites.
Author: Bellevue College IT Services
Version: 1.11
Author URI: https://www.bellevuecollege.edu
GitHub Plugin URI: bellevuecollege/rave-alert-notification
Text Domain: rave-alert-notification
*/

// Load Settings
$bc_rave_network_settings = get_site_option( 'ravealert_network_settings' );
$bc_rave_network_settings = is_array( $bc_rave_network_settings ) ? $bc_rave_network_settings : array();

// Load Classes
require_once( 'classes/class-cap-alert.php' );
require_once( 'classes/class-open-message.php' );
require_once( 'alert-config.php' );
require_once( 'rave-alert-api.php' );

/**
 * Instantiate API for Alerts on the Main Site
 */
if ( is_main_site() ) {
	$rave_alert_api = new Rave_Alert_API();
}

/**
 * Create BC Alert CPT if archive type is set to CPT in the network settings
 */
if ( is_main_site() && 'true' === $bc_rave_network_settings['ravealert_do_archive'] && 'cpt' === $bc_rave_network_settings['ravealert_archive_type'] ) {
	require_once('post-types/bc-alert.php');
}

/**
 * Insert Alert Web Component Template into Page Header
 */
add_action( 'wp_head', function() {
	?>
	<template id="bc-alert-template">
		<div id="ravealertheader">
			<div class="container py-3">
				<div class="row" id="ravealertemergencyrow" hidden>
					<div id="ravealerticon" class="col-sm-2">
						<span class="glyphicon glyphicon-warning-sign fa-solid fa-triangle-exclamation fa-5x" aria-hidden="true"></span>
					</div>
					<div class="col-sm-10">
						<div id="ravealertmessage" class="bc-rave-alert-message">
							<h2 id="ravealertevent" class="bc-rave-alert-heading"></h2>
							<p id="ravealertcontent" class="bc-rave-alert-content"></p>
						</div>
					</div>
				</div>
				<div class="row" id="ravealertmanualrow" hidden>
				</div>
			</div>
		</div>
	</template>
	<?php
} );

/**
 * Add Alert Component to the Top of the Body
 */
add_action( 'wp_body_open', function() {
	?>
	<bc-emergency-alert id="bc-emergency-alert" hidden></bc-emergency-alert>
	<?php
} );

/*
 * Enqueue Ajax scripts
 * Script calls Ajax after x amount of miliseconds to keep page updating every x miliseconds
 */
function bc_rave_enqueue_ajax() {
    $rest_url           = esc_url( network_site_url( '/wp-json/rave/v' . Rave_Alert_API::$rest_version ) . '/' );
    //Get college open message: returns an array of description and class
    $open_message_data  = Open_Message::get_message();
    $open_message_desc  = isset( $open_message_data['description'] ) ?  addslashes( stripslashes( wp_kses_post( $open_message_data['description'] ) ) )  : null;
    $open_message_class = isset( $open_message_data['class'] ) ?  addslashes( stripslashes( esc_attr( $open_message_data['class'] ) ) ) : null;

    //checks if current site is the homepage
    $current_site = get_site_url() . '/';
    $homepage_site = network_home_url();
    $is_homepage = ( is_main_site() && ( is_front_page() || 'home' === get_post_type() ) ? true : false );

    $rest_variables = 'var rave_alert_settings = {
                                                    rest_url: "' . $rest_url . '", 
                                                    more_info_url: "' . bc_rave_return_more_info_link() . '",
                                                    open_message_desc: "' . $open_message_desc . '",
                                                    open_message_class: "' . $open_message_class . '",
                                                    is_homepage: "' . $is_homepage . '"
                                                };';
    wp_enqueue_script( 'rave-alert-ajax', plugin_dir_url( __FILE__ ) . 'dist/index.js', array(), '1.11.0--dev1', array( 'strategy' => 'defer' ) );
    wp_add_inline_script( 'rave-alert-ajax', $rest_variables, 'before' );

}
add_action( 'wp_enqueue_scripts', 'bc_rave_enqueue_ajax' );


/**
 * Create a New Cron Interval for Every 1 Minute
 * 
 * @param array $interval
 * @return array
 */
function bc_rave_interval( $interval ) {
	$interval['minutes_1'] = array(
		'interval' => 1 * 60,
		'display' => 'Once every 1 minute'
	);
	return $interval;
}
add_filter('cron_schedules', 'bc_rave_interval');

/**
 * Schedule Cron Event on the Main Site Only
 */
if ( is_main_site() ) {
	if ( ! wp_next_scheduled( 'rave_cron' ) ) { 
		wp_schedule_event( time(), 'minutes_1', 'rave_cron' ); 
	}
	add_action( 'rave_cron', 'bc_rave_cron' );
}

/**
 * Cron Event
 */
function bc_rave_cron() {
	if ( is_main_site() ) {
		$alert      = new CAP_Alert();
		$alert_data = $alert->get_alert();
		$alert_data_string = print_r( $alert_data, true );
		bc_rave_log( "CRON- Get Alert: $alert_data_string" );
		$response   = $alert->store_db_alert( $alert_data );
		bc_rave_log( "CRON- Store Alert: $response" );
		$return_post_id = bc_rave_create_rave_post( $alert_data );
		bc_rave_log( "CRON- Create Post: $return_post_id" );
	}
}

/* 
 * Return 'More information' link
 */

function bc_rave_return_more_info_link() {

	// Load Settings.
	global $bc_rave_network_settings;

	// Check if archive is enabled.
	$do_archive = "true" === $bc_rave_network_settings['ravealert_do_archive'] ? true : false;
	$archive_type = $bc_rave_network_settings['ravealert_archive_type'];
	$more_info_site_id = "post" === $archive_type ? $bc_rave_network_settings['ravealert_archive_site'] : null;


	// Exit if Archive is Not Enabled or is Misconfigured.
	if ( ( '' === $more_info_site_id && 'post' === $archive_type ) || ! $do_archive ) {
		return '';
	}

	if ( 'post' === $archive_type ) {
		$more_info_site = get_blog_details( $more_info_site_id )->path;
		return $more_info_site;
	}

	if ( 'cpt' === $archive_type ) {
		$more_info_site = network_site_url('/blog/bc-alert');
		return $more_info_site;
	}

	if ( 'oho' === $archive_type ) {
		$more_info_site = network_site_url('/news/article');
		return $more_info_site;
	}

	return '';
}



/*
* Create New Post
*/
function bc_rave_create_rave_post( $xml_data ) {
	$post_return_value = 0;
	//error_log("identifier :".$xml_data["identifier"]);

	if (
			isset( $xml_data ) && 
			isset( $xml_data["event"] ) && 
			isset( $xml_data["headline"] ) && 
			isset( $xml_data["description"] ) && 
			isset( $xml_data["identifier"] )
		){

		$event            = $xml_data["event"];
		$headline         = $xml_data["headline"];
		$description      = $xml_data["description"];
		$identifier       = $xml_data["identifier"];
		$bc_rave_network_settings = get_site_option( 'ravealert_network_settings' );
		$check_to_archive = $bc_rave_network_settings['ravealert_do_archive'];
		$archive_type     = $bc_rave_network_settings['ravealert_archive_type'];
		$archive_blog_id  = "cpt" === $archive_type || "oho" === $archive_type ? SITE_ID_CURRENT_SITE : $bc_rave_network_settings['ravealert_archive_site'];
		global $wpdb;
		if ( "true" === $check_to_archive ) {
			switch_to_blog( $archive_blog_id );
				global $wpdb;

				$post_type = "cpt" === $archive_type ? 'bc-alert' : 
					( 'oho' === $archive_type ? $bc_rave_network_settings['ravealert_oho_news_cpt'] : 'post' );

				$query = new WP_Query(
					array(
						'name' => $identifier,
						'post_type' => $post_type,
					)
				);

				if ( $query->post_count === 0 ) {
					$description = "<p>$headline</p><!--more--><p>$description</p>";
					$post_args = array(
						'post_name'     => $identifier,
						'post_title'    => $event,
						'post_content'  => $description,
						'post_status'   => 'publish',
						'post_author'   => 1,
						'post_type'     => $post_type,
					);
					$post_return_value = wp_insert_post( $post_args );

					// If successful, populate custom fields for OHO News CPT
					if ( 'oho' === $archive_type && is_int( $post_return_value ) && $post_return_value > 0 ) {
						update_field('publish_date', current_time( 'Ymd' ), $post_return_value);
						update_field('summary', $headline, $post_return_value);
						//update_field('news_type', (int)$bc_rave_network_settings['ravealert_oho_news_term'], $post_return_value);
						wp_set_post_terms( $post_return_value, (int)$bc_rave_network_settings['ravealert_oho_news_term'], 'news_type' );

					}
				}
			restore_current_blog();
		}
	}

	return $post_return_value;
}


/**
 * Log Rave Alert Debug Info
 */
function bc_rave_log( $message, $webhook = false ) {

	global $bc_rave_network_settings;

	$currentDateTime = date('Y-m-d H:i:s', time());
	$prefix = "$currentDateTime - RAVE: ";
	if ( defined( 'RAVE_DEBUG' ) && true === RAVE_DEBUG ) {
		error_log( $prefix . $message . PHP_EOL, 3, 'wp-content/rave-alert.log' );
	}
	// Push via webhook to Teams
	if ( $webhook ) {
		$webhook_url = $bc_rave_network_settings['teams_error_webhook_url'];
		bc_rave_log( 'Webhook URL: ' . $webhook_url, false );
		$card_json = json_encode( array(
			'type' => 'message',
			'attachments' => array(
				array(
					'contentType' => 'application/vnd.microsoft.card.adaptive',
					'contentUrl' => null,
					'content' => array(
						'$schema' => 'http://adaptivecards.io/schemas/adaptive-card.json',
						'type' => 'AdaptiveCard',
						'version' => '1.2',
						'body' => array(
							array(
								'type' => 'TextBlock',
								'text' => "ERROR: WordPress Rave Alert Plugin",
								'size' => 'large',
							),
							array(
								'type' => 'TextBlock',
								'text' => "Error message: '$message'. Manual intervention needed to clear cache.",
								'size' => 'medium',
								'wrap' => true,
							),
							array(
								'type' => 'TextBlock',
								'text' => "Log Created: $currentDateTime",
								'size' => 'medium',
							),
							array(
								'type' => 'TextBlock',
								'text' => network_site_url('/'),
								'size' => 'medium',
								'wrap' => true,
							),
						)
					)
				)
			)
		));
		$response = wp_remote_post( $webhook_url, array(
			'headers' => array('Content-Type' => 'application/json'),
			'body' => $card_json,
		) );
		bc_rave_log( "Posted Webhook for Log. Response: " . print_r( $response, true ), false );
	}
}
