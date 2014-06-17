<?php
/*
Plugin Name: Rave Alert Notification
Plugin URI: https://github.com/BellevueCollege/rave-alert-notification
Description: Sends Rave Alert notification for Bellevue College Home page.
Author: Bellevue College Technology Development and Communications
Version: 0.1
Author URI: http://www.bellevuecollege.edu
*/

require_once("alert-config.php");

/*
 * Putting the College open message functionality to run outside of cron job
 * */

$new_data = getOpenMsg();

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
                $html = "<div class='alert alert-success'>".$open_message."</div>";
                $returnArray["description"] = $open_message;
                $returnArray["class"] = "alert alert-success";
            }
            else
            {
                $returnArray["description"] = "";
                $returnArray["class"] = "";
            }
            $new_display_message = trim($returnArray["class"]);
            $class =  trim($returnArray["class"]);
            updateCurrentMsg($new_display_message,$class);
        }
    }

    return $returnArray;
}

/*
	Cron Job for RaveAlert.
*/

add_filter('cron_schedules', 'new_interval');

// add once 1 minute interval to wp schedules
function new_interval($interval) {

    $interval['minutes_1'] = array('interval' => 1*60, 'display' => 'Once 1 minutes');

    return $interval;
}
add_action( 'my_cron', 'myCronFunction' );

if ( ! wp_next_scheduled( 'my_cron' ) ) {
    wp_schedule_event( time(), 'minutes_1', 'my_cron' );
}
else
{
    //error_log("my cron is already scheduled");
}
wp_cron();


function myCronFunction()
{
    //error_log("############################CRON TAB is Running #######################");
    $network_settings = get_site_option( 'ravealert_network_settings' );
    $url = $network_settings['ravealert_xml_feedurl'];//;get_template_directory() . '/inc/alert-notification/channel1.xml';
    $new_data = cap_parse($url);
    $getHtml = returnHtmlNClearCache($new_data);
}

function cap_parse($url){

    //Load XML File and get values
    $xml = simplexml_load_file($url);
    $event = $xml->info->event;
    $description=$xml->info->description;
    $effective=strtotime($xml->info->effective);
    $expires=strtotime($xml->info->expires);

    //Get current time
    $time = time();
    $returnArray = array();
    //Test to see if current time is between effective time and expire time
    if (($time > $effective) && ($time < $expires)) {
        //If true, print HTML using event and description info
        $returnArray["description"] = $description;
        $returnArray["class"] = "alert alert-error";
        //return "<div id=\"alert\"><h2>".$event."</h2><p>".$description."</p></div>";
        return $returnArray;

    }
}


function returnHtmlNClearCache($new_data)
{
    $html = "";
    $class = "";//Class for the display message
    error_log("new data :".print_r($new_data,true));
    if(!empty($new_data["description"]))
    {
        $class = $new_data["class"];
        $html = "<div id='alertmessage' class='".$new_data["class"]."'>".$new_data["description"]."</div>";
    }
    $network_settings = get_site_option( 'ravealert_network_settings' );
    //error_log("network_settings:".print_r($network_settings,true));
    $high_alert = $network_settings['high_alert'];
    $currentMsg = get_site_option('ravealert_currentMsg');
    $classForMsg = get_site_option('ravealert_classCurrentMsg');
    if(!$currentMsg)
    {
        add_site_option( "ravealert_currentMsg", "" );
    }
    if(!$classForMsg)
    {
        add_site_option( "ravealert_classCurrentMsg", "" );
    }
    $clearCacheCommand = $network_settings['ravealert_clearCacheCommand'];//error_log("clear cache command:".$clearCacheCommand);"sudo find /var/run/nginx-cache-bc -type f -exec rm {} \;";
    $clearCacheCommand = base64_decode($clearCacheCommand);
    $new_display_message = !empty($new_data["description"]) ? trim($new_data["description"]) : "";

//Clear the cache if there is a new message

    if($currentMsg != $new_display_message)
    {
        if($clearCacheCommand)
        {
            $returnValue= returnContentsOfUrl($clearCacheCommand);
            //error_log("clearing cache return value :".$returnValue);
            //var_dump($returnValue);
            if($returnValue)
            {
                //var_dump("==============================Inside if conditions");
                $returnJsonDecodedString = json_decode($returnValue,true);
                //error_log("returnJsonDecodedString :".print_r($returnJsonDecodedString,true));
                //var_dump($returnJsonDecodedString);
                foreach($returnJsonDecodedString as $key=>$value)
                {
                    //var_dump("inside for loop:".$value);
                    //$key will be the server name
                    if($value["return_value"] != 0)
                    {
                        error_log("\n"."Error: Server ".$key." returns ".$value["return_value"]." while running command ".$value["command_run"]."\n");
                    }
                    else
                    {
                        //error_log("\n"." Success: Server ".$key." returns ".$value["return_value"]." while running command ".$value["command_run"]."\n");
                    }
                }

            }
        }
    }
    //Updated the new message and the class variable in the database
    updateCurrentMsg($new_display_message,$class);

    return $html;
}
function returnContentsOfUrl($url)
{
    $arg = array ( 'method' => 'GET');
    $output = wp_remote_request ( $url , $arg );
    //error_log("output :".print_r($output["body"],true));
    return $output["body"];
}

function updateCurrentMsg($new_display_message,$class)
{
    update_site_option("ravealert_currentMsg", $new_display_message);
    update_site_option("ravealert_classCurrentMsg", $class);
}
?>