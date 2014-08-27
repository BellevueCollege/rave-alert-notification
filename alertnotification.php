<?php
/*
Plugin Name: Rave Alert Notification
Plugin URI: https://github.com/BellevueCollege/rave-alert-notification
Description: Sends Rave Alert notification to Bellevue College WordPress sites.
Author: Bellevue College Technology Development and Communications
Version: 1.1.0.1
Author URI: http://www.bellevuecollege.edu
*/

require_once("alert-config.php");


/*
 * Putting the College open message functionality to run outside of cron job
 * */

$new_data = getOpenMsg();

add_action( 'init', 'test_start_buffer', 0, 0 );

function test_start_buffer(){
    ob_start( 'test_get_buffer' );
}
/*
 * Appends rave alert current message to the start of body tag.
 */
function test_get_buffer( $buffer){

    $rave_message = get_site_option('ravealert_currentMsg');
    $rave_class = get_site_option('ravealert_classCurrentMsg');
    if($rave_message!="")
    {
        $rave_html = "<div class='container'>
                        <div class='row'>
                             <div class='span12 top-spacing30'>
                                  <div id='alertmessage' class='".$rave_class."'>".$rave_message."
                                  </div>
                             </div>
                        </div>
                    </div>
                      ";
        preg_match('#<body.+>#',$buffer,$matches);
        if(isset($matches[0]) && !empty($matches[0]))
        {
            $concat_html = $matches[0].$rave_html;//Appending the rave alert message right after the start of body tag.
            if ( ! is_admin() && ! is_login_page())
                return preg_replace( '#<body.+>#', $concat_html, $buffer);
        }
    }
    return $buffer;

}
function is_login_page() {
    return in_array($GLOBALS['pagenow'], array('wp-login.php', 'wp-register.php'));
}
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
            //Clear the cache if there is a new message
            $check = compareCurrentNewMessage($new_display_message);
            if($check)
            {
                $cache_cleared = clearCache();
                if(!$cache_cleared)
                    error_log("ERROR: CACHE IS NOT BEING CLEARED");
            }
            updateCurrentMsg($new_display_message,$class);
        }
    }

    return $returnArray;
}// End of getOpenMsg function

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
/*
 * Parses the xml feed through CAP
 */
function cap_parse($url){

    //Load XML File and get values
    $returnArray = array();
    $xml = @simplexml_load_file($url);
    if($xml)
    {

        $event = $xml->info->event;
        $description=$xml->info->description;
        $headline =$xml->info->headline;
        $effective=strtotime($xml->info->effective); // No use for us since the expiration time is calculated basis on the sent time by CAP.
        $sent = strtotime($xml->sent);
        $expires=strtotime($xml->info->expires);

        //Get current time
        $time = time();
        //Test to see if current time is between effective time and expire time
        if (($time > $sent) && ($time < $expires)) {
            //If true, print HTML using event and description info
            $returnArray["description"] = $description;
            $returnArray["class"] = "alert alert-danger alert-error";
            $returnArray["headline"] = $headline;
            $returnArray["event"] = $event;
        }

    }
    return $returnArray;
} //End of cap_parse function

/*
 *  Updates current message and clear cache
 */
function returnHtmlNClearCache($new_data)
{
    $html = "";
    $class = "";//Class for the display message
    if(!empty($new_data["description"]))
    {
        $class = $new_data["class"];
        $html = "<div class='container'>
                    <div class='row'>
                     <div class='span12 top-spacing30'>
                        <div id='alertmessage' class='".$new_data["class"]."' >
                        <h1>".$new_data["event"]."</h1>
                        <p>".$new_data["headline"]."</p>
                        <p>".$new_data["description"]."</p></div>
                      </div>
                    </div>
                 </div>";
    }
    $new_display_message = !empty($new_data["event"]) ?  "<h3>".$new_data["event"]."</h3><p>".$new_data["headline"]."</p><p>".$new_data["description"]."</p>" : "";

//Clear the cache if there is a new message
    $check = compareCurrentNewMessage($new_display_message);
    if($check)
    {
        $cache_cleared = clearCache();
        if(!$cache_cleared)
            error_log("ERROR: CACHE IS NOT BEING CLEARED");
    }
    //Updated the new message and the class variable in the database
    updateCurrentMsg($new_display_message,$class);

    return $html;
}// end of returnHtmlNClearCache function

function clearCache()
{
    $network_settings = get_site_option( 'ravealert_network_settings' );
    $clearCacheCommand = $network_settings['ravealert_clearCacheCommand'];
    $clearCacheCommand = base64_decode($clearCacheCommand);
    if($clearCacheCommand)
    {
        $returnValue= returnContentsOfUrl($clearCacheCommand);
        if($returnValue)
        {
            $returnJsonDecodedString = json_decode($returnValue,true);
            foreach($returnJsonDecodedString as $key=>$value)
            {
                if($value["return_value"] != 0)
                {
                    error_log("\n"."Error: Server ".$key." returns ".$value["return_value"]." while running command ".$value["command_run"]."\n");
                    return false;
                }
                else
                {
                    //error_log("\n"." Success: Server ".$key." returns ".$value["return_value"]." while running command ".$value["command_run"]."\n");
                    return true;
                }
            }

        }
    }
    return false;
}

function compareCurrentNewMessage($new_message)
{
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
    if($currentMsg != $new_message)
    {
        return true;
    }
    return false;
}
function returnContentsOfUrl($url)
{
    $arg = array ( 'method' => 'GET');
    $output = wp_remote_request ( $url , $arg );
    return $output["body"];
}
/*
 * Update current message
 */
function updateCurrentMsg($new_display_message,$class)
{
    update_site_option("ravealert_currentMsg", $new_display_message);
    update_site_option("ravealert_classCurrentMsg", $class);
}
?>
