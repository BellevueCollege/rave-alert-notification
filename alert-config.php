<?php

/*
 * Will add network wide settings for Rave Alert.
 */

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

            //store option values in a variable
            $network_settings = $_POST['network_settings'];

            //if choose to archive, throw error if archive site not chosen
            $submit_message = "";
            if ( isset($network_settings["ravealert_do_archive"]) && $network_settings["ravealert_do_archive"] == "true" && empty($network_settings["ravealert_archive_site"]) ) {
                //since archive site not chosen, change archive option back to false before saving options
                $network_settings["ravealert_do_archive"] = "false";
                $submit_message = '<div id="error" class="error fade"><p><strong>Select a site for the Rave Alert archive.</strong></p></div>';
            } else {    
                //otherwise assume it all went according to plan
                $submit_message = '<div id="message" class="updated fade"><p><strong>Rave Alert network settings updated!</strong></p></div>';
            }
            
            //use array map and WP function to sanitize option values
            $open_message = wp_kses_post($network_settings["ravealert_college_openmessage"]);
            $network_settings = array_map( 'sanitize_text_field', $network_settings );
            //reset the college open message since it was sanitized differently to allow HTML but strip anything not allowed in WP posts
            $network_settings["ravealert_college_openmessage"] = $open_message;
            
            //save option values
            update_site_option( 'ravealert_network_settings', $network_settings );
            
            echo $submit_message;
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
            $ravealert_severity = get_site_option('ravealert_severity');
            echo $ravealert_severity;
            $ravealert_college_openmessage = stripslashes($network_settings['ravealert_college_openmessage']);
            $ravealert_xml_feedurl = $network_settings['ravealert_xml_feedurl'];
            
            $archive_alert = $network_settings['ravealert_do_archive'];
            $true_archive_selected = "";
            $false_archive_selected = "";
            if($archive_alert == "true")
            {
                $true_archive_selected = "checked";
            } else {
                $false_archive_selected = "checked";
            }

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
                        <p><small>Accepts basic html and Bootstrap formatting as needed.</small></p>
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
                            Create Archive for Rave Alerts?
                    </th>
                    <td>
                        <label for="ravealert_do_archive_yes"><input type="radio" id="ravealert_do_archive_yes" name="network_settings[ravealert_do_archive]" value="true" <?php echo $true_archive_selected; ?> /> Yes</label>
                        <label for="ravealert_do_archive_no"><input type="radio" id="ravealert_do_archive_no" name="network_settings[ravealert_do_archive]" value="false" <?php echo $false_archive_selected; ?>/>  No</label>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">
                        <label for="archive_site">
                            Archive Site
                        </label>
                    </th>
                    <td>
                        <select name="network_settings[ravealert_archive_site]" id="archive_site">
                            <option value=""></option>
                        <?php $net_sites = wp_get_sites();
                            foreach( $net_sites as $net_site ) {
                                $site_selected = "";
                                if ( (null != $network_settings["ravealert_archive_site"]) && $net_site["blog_id"] == $network_settings["ravealert_archive_site"]) { 
                                    $site_selected = "selected";
                                }
                                $blog_detail = get_blog_details($net_site["blog_id"]);
                                $blog_name = $blog_detail->blogname;
                        ?>
                            <option value="<?php echo $net_site["blog_id"]; ?>" <?php echo $site_selected; ?>><?php echo $blog_name; ?></option>
                        <?php } ?>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">
                        <label>
                            Rave Alert Severity
                        </label>

                    </th>
                    <td>
                        <?php echo stripslashes($ravealert_severity); ?>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">
                        <label>
                            Latest Message Displayed
                        </label>

                    </th>
                    <td>
                        <?php echo stripslashes($ravealert_currentMsg); ?>
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