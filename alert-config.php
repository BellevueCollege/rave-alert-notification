<?php

/*
 * Will add network wide settings for Rave Alert.
 */
//add_action('network_admin_menu', 'function_name');
add_action( 'network_admin_menu', 'ravealert_network_menu_settings');

function ravealert_network_menu_settings(){

    add_menu_page ('Rave Alert', 'Rave Alert', 'manage_network', 'ravealert-settings', 'ravealert_network_settings');

}

function ravealert_network_settings() {

if (is_multisite() && current_user_can('manage_network'))  {

?>
<div class="wrap">

    <h2>Rave Alert Settings</h2>

    <?php

    if (isset($_POST['action']) && $_POST['action'] == 'update_ravealert_settings') {

        check_admin_referer('save_network_settings', 'my-network-plugin');

        //sample code from Professional WordPress book

        //store option values in a variable
        $network_settings = $_POST['network_settings'];
        $cacheCommand = $network_settings["ravealert_clearCacheCommand"];
        //error_log("clear cache com :".$cacheCommand);
        $cacheCommand = stripslashes($cacheCommand);
        $cacheCommand = base64_encode($cacheCommand);
        $network_settings["ravealert_clearCacheCommand"] = $cacheCommand;

        //use array map function to sanitize option values
        $network_settings = array_map( 'sanitize_text_field', $network_settings );

        //save option values
        update_site_option( 'ravealert_network_settings', $network_settings );

        //just assume it all went according to plan
        echo '<div id="message" class="updated fade"><p><strong>Rave Alert Network Settings Updated!</strong></p></div>';

    }//if POST

    ?>

    <form method="post">
        <input type="hidden" name="action" value="update_ravealert_settings" />
        <?php
        $network_settings = get_site_option( 'ravealert_network_settings' );
        //$holiday = $network_settings['holiday']; //holiday setting example from Professional WordPress
        $high_alert = $network_settings['high_alert'];

        $trueSelected = "";
        $falseSelected = "";
        if($high_alert == "true")
        {
            $trueSelected = "checked";
        }
        else
        {
            $falseSelected = "checked";
        }
        $ravealert_currentMsg = get_site_option('ravealert_currentMsg');
        $ravealert_clearCacheCommand = base64_decode($network_settings['ravealert_clearCacheCommand']);
        $ravealert_college_openmessage = $network_settings['ravealert_college_openmessage'];
        $ravealert_xml_feedurl = $network_settings['ravealert_xml_feedurl'];
        //error_log("reavealert current msg :".$ravealert_currentMsg);

        wp_nonce_field('save_network_settings', 'my-network-plugin');
        ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">
                    <label for="high_alert">
                        High Alert
                    </label>
                </th>
                <td>
                    <input type="radio" name="network_settings[high_alert]" value="true" <?php echo $trueSelected; ?> /> True
                    <input type="radio" name="network_settings[high_alert]" value="false" <?php echo $falseSelected; ?>/>  False
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">
                    <label for="ravealert_college_openmessage">
                        College Open Message
                    </label>
                </th>
                <td>
                    <textarea name="network_settings[ravealert_college_openmessage]" cols="78" ><?php echo $ravealert_college_openmessage; ?></textarea>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">
                    <label for="ravealert_xml_feedurl">
                        XML Feed URL
                    </label>
                </th>
                <td>
                    <textarea name="network_settings[ravealert_xml_feedurl]" cols="78"><?php echo $ravealert_xml_feedurl; ?></textarea>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">
                    <label for="ravealert_clearCacheCommand">
                        Clear Cache Url
                    </label>

                </th>
                <td>
                    <input type="text" size="80" name="network_settings[ravealert_clearCacheCommand]" value="<?php echo $ravealert_clearCacheCommand; ?>"/>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">
                    <label>
                        Latest Message Displayed
                    </label>

                </th>
                <td>
                    <?php echo $ravealert_currentMsg; ?>
                </td>
            </tr>
        </table>

        <p class="submit">
            <input type="submit" class="button-primary" name="update_ravealert_settings" value="Save Settings" />

        </p>
    </form>

    <?php

    } else {

        echo '<p>Please configure WP Multisite before using these settings.  In addition, this page can only be accessed by a super admin.</p>';
        /*Note: if your plugin is meant to be used also by single wordpress installations you would configure the settings page here, perhaps by calling a function.*/
    }

    ?>
</div>
<?php
}//settings page function
?>