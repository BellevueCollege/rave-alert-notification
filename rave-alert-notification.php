<?php
/*
Plugin Name: Rave Alert Notification
Plugin URI: https://github.com/BellevueCollege/rave-alert-notification
Description: Sends Rave Alert notification to Bellevue College WordPress sites.
Author: Bellevue College IT Services
Version: 1.7.3
Author URI: https://www.bellevuecollege.edu
GitHub Plugin URI: bellevuecollege/rave-alert-notification
*/

require_once('alert-config.php');
require_once('rave-alert-api.php');

/*
 * Enqueue Ajax scripts
 * Script calls Ajax after x amount of miliseconds to keep page updating every x miliseconds
 */
function enqueue_ajax() {
    $network_settings = get_site_option( 'ravealert_network_settings' );
    $url = $network_settings['ravealert_xml_feedurl']; //get alert URL
    $data = cap_parse($url); //retrieve alert data
    $rest_url = network_site_url('/wp-json/rave/v' . Rave_Alert_API::$rest_version) . '/alerts/';
    $more_info_message = returnMoreInfoMsg();

    //Get college open message: returns an array of description and class
    $open_message_data = getOpenMsg();
    $open_message_desc = isset( $open_message_data['description'] ) ? $open_message_data['description'] : null;
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
    wp_enqueue_script( 'rave-alert-ajax', plugin_dir_url( __FILE__ ) . 'js/rave-alert-ajax.js#asyncdeferload', array('jquery'), '1.7.3', true );
    wp_add_inline_script( 'rave-alert-ajax', $rest_variables, 'before' );

}
add_action( 'wp_enqueue_scripts', 'enqueue_ajax' );

/*
 * Putting the College open message functionality to run outside of cron job
 */


//add_action('wp_footer','getOpenMsg',100);
$new_data = getOpenMsg();
//error_log(print_r(get_current_site(),true));

/*
 * Updates the current message if xml feed description is empty and high alert flag is set to true
 */

function getOpenMsg()
{
    $network_settings = get_site_option( 'ravealert_network_settings' );
    $high_alert = $network_settings['high_alert'];
    $open_message = $network_settings['ravealert_college_openmessage'];
    $url = $network_settings['ravealert_xml_feedurl'];
    $new_data = cap_parse($url);

    $returnArray = array();
    if(!isset($new_data) || !isset($new_data["description"]) || empty($new_data["description"])) // if the description is empty or not set
    {
        if($high_alert == "true")
        {
            if($open_message)
            {
                $returnArray["description"] = $open_message;
                $returnArray["class"] = "alert alert-success";
            }
            else
            {
                $returnArray["description"] = "";
                $returnArray["class"] = "";
            }
            $new_display_message = trim($returnArray["description"]);
            $class =  trim($returnArray["class"]);
            updateCurrentMsg($new_display_message,$class);
        }
    }

    return $returnArray;
}// End of getOpenMsg function

/*
 *	Cron Job for RaveAlert.
 */

add_filter('cron_schedules', 'new_interval');


// add once every 1 minute interval to wp schedules
function new_interval($interval) {
    $interval['minutes_1'] = array('interval' => 1*60, 'display' => 'Once every 1 minute');
    return $interval;
}

/*
 * Removing the my_cron and adding rave_cron
 */
 wp_clear_scheduled_hook( 'my_cron'); 

if ( ! wp_next_scheduled( 'rave_cron' ) ) { 
    wp_schedule_event( time(), 'minutes_1', 'rave_cron' ); 
}

add_action( 'rave_cron', 'myCronFunction' );

function myCronFunction()
{
    //error_log("############################CRON TAB is Running #######################");

    if ( is_main_site() ) // will run only for home site
    {
        $network_settings = get_site_option( 'ravealert_network_settings' );
        $url = $network_settings['ravealert_xml_feedurl'];//;get_template_directory() . '/inc/alert-notification/channel1.xml';
        
        $xml_data = cap_parse($url);
    
        $getHtml = returnHtml($xml_data);

        $return_post_id = createRavePost($xml_data);
        if($return_post_id)
        {
            
        }
        wp_clear_scheduled_hook('rave_cron');
    }

}

/*
 * Parses the xml feed through CAP
 */

function cap_parse($url){

    //Load XML File and get values
    $returnArray = array();
    //$url = 'http://www.getrave.com/cap/bellevuecollege/channel3';
    $xml = @simplexml_load_file($url);
    //$xml = simplexml_load_file($url) or die("Rave Alert Error: Cannot create object.");
    
    if(!empty($xml))
    {       
        $identifier = $xml->identifier;
        $msgType = $xml->msgType;
        $event = $xml->info->event;
        $description=$xml->info->description;
        $headline =$xml->info->headline;
        $effective=strtotime($xml->info->effective); // No use for us since the expiration time is calculated basis on the sent time by CAP.
        $sent = strtotime($xml->sent);
        $expires=strtotime($xml->info->expires);
        $severity = $xml->info->severity;

        //Get current time
        $time = time();
        //Test to see if current time is between effective time and expire time
        if (strtolower($msgType) != 'cancel' &&   ($time > $sent) && ($time < $expires)) {
            //If true, print HTML using event and description info
            $returnArray["identifier"] = $identifier;
            $returnArray["description"] = $description;
            if(strtolower($severity) == 'minor')
                $returnArray["class"] = "alert alert-info";
            else
                $returnArray["class"] = "alert alert-danger";
            $returnArray["headline"] = $headline;
            $returnArray["event"] = $event;
            $returnArray["severity"] = $severity;
        }

    }
    return $returnArray;
} //End of cap_parse function

/* 
 * Return 'More information' link
 */

function returnMoreInfoMsg(){
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
 *  Updates current message
 */
function returnHtml($new_data)
{
    $html = "";
    $class = ""; //Class for the display message
    if(!empty($new_data["description"]))
    {
        $class = $new_data["class"]; 
    }

    $rave_transient = get_transient('rave_alert_data'); //get transient
    $more_info_message = returnMoreInfoMsg();

    //if transient is false then return nothing, if true then decode transient data into $rave_transient_data
    ( (false === $rave_transient) ? '' : $rave_transient_data = json_decode($rave_transient, true) );

    //Output alert via output buffer into $rave_rest_message
    ob_start(); 
    ?>
    <div class='col-sm-2'>
        <span class='glyphicon glyphicon-warning-sign' aria-hidden='true'></span>
    </div>
    <div class='col-sm-10'>
        <div id='ravealertmessage'>
            <h2 id='ravealertevent'><?php echo (!isset($rave_transient_data) ? '' : $rave_transient_data['info']['event']); ?> </h2>
            <p> <?php echo (!isset($rave_transient_data) ? '' : $rave_transient_data['info']['headline']); ?> <?php echo $more_info_message ?> </p>
        </div>
    </div> 
    <?php
    //save alert and then output into $new_display_message
    $rave_rest_message = ob_get_clean();
    
    /* Alert Message Stored to Database */
    $new_display_message = !empty($new_data["event"]) ? $rave_rest_message : "";

    // Set severity
    $severity = !empty($new_data['severity']) ? $new_data['severity'] : '' ;
 

    $ravealert_severity = get_site_option('ravealert_severity','empty');
        
    if($ravealert_severity == 'empty')
    {
       add_site_option( "ravealert_severity", (string)$severity );        
    }
    else
    {
       update_site_option("ravealert_severity",(string)$severity);       
    }

    //Updated the new message and the class variable in the database
    updateCurrentMsg($new_display_message,$class);

    return $html;
}// end of returnHtml function

/*
 * Check to see if current message matches, true if no match, false if a match
 * 
 */ 
// function compareCurrentNewMessage($new_message)
// {
//     $currentMsg = get_site_option('ravealert_currentMsg');
//     $classForMsg = get_site_option('ravealert_classCurrentMsg');
    
//     //if ravealert_currentMsg doesn't exist, then add it
//     if( !$currentMsg )
//     {
//         add_site_option( "ravealert_currentMsg", "" );
//     }
//     //if ravealert_classCurrentMsg doesn't exist, then add it
//     if( !$classForMsg )
//     {
//         add_site_option( "ravealert_classCurrentMsg", "" );
//     }

//     if( $currentMsg != $new_message) 
//     {
//         return true;
//     }     
//     return false;
// }

/*
 * Update current message
 */
function updateCurrentMsg($new_display_message,$class)
{
    update_site_option("ravealert_currentMsg", $new_display_message); //Update with new message from returnHtml() using cap_parse xml data
    update_site_option("ravealert_classCurrentMsg", $class);
    
}


/*
* Create new post
*/
function createRavePost($xml_data)
{
    $post_return_value = 0;
    //error_log("identifier :".$xml_data["identifier"]);
     if(isset($xml_data) && isset($xml_data["event"]) && isset($xml_data["headline"]) && isset($xml_data["description"]) && isset($xml_data["identifier"]))
     {
        $event = $xml_data["event"];
        $headline = $xml_data["headline"];
        $description = $xml_data["description"];
        $identifier = $xml_data["identifier"];
        $network_settings = get_site_option( 'ravealert_network_settings' );
        $check_to_archive = $network_settings['ravealert_do_archive'];
        $archive_blog_id = $network_settings['ravealert_archive_site'];
        global $blog_id;
        //$current_blog_id = get_current_blog_id();
        // error_log(" current blog id :".$blog_id);
        //error_log("archive blog id:".$archive_blog_id);
        //get the blog id onto which the archive posts needs to be created.
        if($check_to_archive == "true") //&& $blog_id == $archive_blog_id) //&& strcmp($blog_id , $archive_blog_id) == 0 )
        {
           //if($current_blog_id == $archive_blog_id )
             switch_to_blog( $archive_blog_id );
        // Check if the post already exists. Creat a new post if it does not exist.
          global $wpdb;

          $post_id_exists = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_name=%s LIMIT 1", $identifier))  ;
          //error_log("post id of the existing post :".$post_id_exists);
            if(!$post_id_exists)
            {
                $description = $headline . "<!--more-->" . $description;
                $post_args = array(
                    'post_name'   => $identifier,
                    'post_title'  => $event,
                    'post_content'  => $description,
                    'post_status'   => 'publish',
                    'post_author'   => 1,
                );
                $post_return_value = wp_insert_post( $post_args );
                //error_log("post return value:".$post_return_value);
                // if($post_return_value)
                // {
                //     $post_args = array(
                //     'ID'          => $post_return_value,
                //     'post_name'   => $identifier,
                //         );

                //     wp_update_post($post_args);
                // }
            }
        restore_current_blog();

        }

    }

     return $post_return_value;
}

?>
