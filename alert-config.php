<?php

/*
 * Will add network wide settings for Rave Alert.
 */

add_action( 'network_admin_menu', 'ravealert_network_menu_settings');

function ravealert_network_menu_settings(){

	add_menu_page ('Rave Alert', 'Rave Alert', 'manage_network', 'ravealert-settings', 'ravealert_network_settings');

}

function ravealert_network_settings() {

	if ( is_multisite() && current_user_can('manage_network' ) ) {

	?>
	<div class="wrap">

		<h1><?php _e( 'Rave Alert Settings', 'rave-alert-notification') ?></h1>

		<?php
		if ( isset( $_POST['action'] ) && $_POST['action'] === 'update_ravealert_settings' ) {

			if ( ! check_admin_referer( 'rave_save_network_settings', 'rave_settings_save' ) ) {
				echo '<div id="error" class="error fade"><p><strong>Security check failed. Please try again.</strong></p></div>';
				die;
			} 

			//store option values in a variable
			$bc_rave_network_settings = $_POST['network_settings'];

			//if choose to archive, throw error if archive site not chosen
			$submit_message = "";
			if ( isset( $bc_rave_network_settings["ravealert_do_archive"] ) && $bc_rave_network_settings["ravealert_do_archive"] === "true" && ( 
					empty( $bc_rave_network_settings["ravealert_archive_site"] ) &&
					empty( $bc_rave_network_settings["ravealert_archive_type"] )
				) ) {
				//since archive site not chosen, change archive option back to false before saving options
				$bc_rave_network_settings["ravealert_do_archive"] = "false";
				$submit_message = '<div id="error" class="error fade"><p><strong>Select a site for the Rave Alert archive.</strong></p></div>';
			} else {
				//otherwise assume it all went according to plan
				$submit_message = '<div id="message" class="updated fade"><p><strong>Rave Alert network settings updated!</strong></p></div>';
			}

			// Unset Archive Site if Archive Type is CPT
			if ( 'cpt' === $bc_rave_network_settings['ravealert_archive_type'] && "" !== $bc_rave_network_settings['ravealert_archive_site'] ) {
				$bc_rave_network_settings['ravealert_archive_site'] = '';
			}
			
			//use array map and WP function to sanitize option values
			$open_message = wp_kses_post( $bc_rave_network_settings["ravealert_college_openmessage"] );
			$bc_rave_network_settings = array_map( 'sanitize_text_field', $bc_rave_network_settings );

			//reset the college open message since it was sanitized differently to allow HTML but strip anything not allowed in WP posts
			$bc_rave_network_settings["ravealert_college_openmessage"] = $open_message;
			
			//save option values
			update_site_option( 'ravealert_network_settings', $bc_rave_network_settings );
			
			echo $submit_message;
		}

		?>

		<form method="post">
			<input type="hidden" name="action" value="update_ravealert_settings" />
			<?php
			$bc_rave_network_settings = get_site_option( 'ravealert_network_settings' );
			
			$high_alert = $bc_rave_network_settings['high_alert'];

			if( $high_alert == "true" ) {
				$trueSelected = "checked";
			} else {
				$falseSelected = "checked";
			}

			$ravealert_college_openmessage = stripslashes( $bc_rave_network_settings['ravealert_college_openmessage'] );

			$ravealert_xml_feedurl = $bc_rave_network_settings['ravealert_xml_feedurl'];
			
			$archive_alert = $bc_rave_network_settings['ravealert_do_archive'];
			if( $archive_alert == "true" ) {
				$true_archive_selected = "checked";
			} else {
				$false_archive_selected = "checked";
			}

			$archive_type = $bc_rave_network_settings['ravealert_archive_type'];
			if ( $archive_type === "post" ) {
				$ravealert_archive_type_post = "checked";
			} else if ( $archive_type === "cpt" ) {
				$ravealert_archive_type_cpt = "checked";
			}

			wp_nonce_field( 'rave_save_network_settings', 'rave_settings_save' );
			?>
			<h2>Manual Alert</h2>
			<table class="form-table">
				<tr valign="top">
					<th scope="row">
						<label for="high_alert">
							Display Manual Alert
						</label>
					</th>
					<td>
						<input type="radio" name="network_settings[high_alert]" value="true" <?php echo $trueSelected ?? ""; ?> /> True
						<input type="radio" name="network_settings[high_alert]" value="false" <?php echo $falseSelected ?? ""; ?>/>  False
						<p><small>Displays a manual alert on all sites in the network. Incoming Rave Alerts will override this message.</small></p>

					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="ravealert_college_openmessage">
							Manual Alert Message
						</label>
					</th>
					<td>
						<textarea name="network_settings[ravealert_college_openmessage]" cols="78" ><?php echo $ravealert_college_openmessage; ?></textarea>
						<p><small>Accepts basic html. Message should be wrapped in a div with the class col-xs-12, or other Bootstrap formatting as needed.</small></p>
					</td>
				</tr>
			</table>
			<hr>
			<h2>Automated Alert Settings</h2>
			<table class="form-table">
				<tr valign="top">
					<th scope="row">
						<label for="ravealert_xml_feedurl">
							XML Feed URL
						</label>
					</th>
					<td>
						<input name="network_settings[ravealert_xml_feedurl]" type='url' value='<?php echo $ravealert_xml_feedurl; ?>' style="width:100%;">
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
							Archive Rave Alerts?
					</th>
					<td>
						<label for="ravealert_do_archive_yes"><input type="radio" id="ravealert_do_archive_yes" name="network_settings[ravealert_do_archive]" value="true" <?php echo $true_archive_selected ?? ""; ?> /> Yes</label>
						<label for="ravealert_do_archive_no"><input type="radio" id="ravealert_do_archive_no" name="network_settings[ravealert_do_archive]" value="false" <?php echo $false_archive_selected ?? "";?>/>  No</label>
						<p><small>Incoming alerts will create Posts or CPTs if enabled.</small></p>
					</td>
					
				</tr>
				<tr valign="top">
					<th scope="row">
							Archive Type
					</th>
					<td>
						<label for="ravealert_archive_type_post"><input type="radio" id="ravealert_archive_type_post" name="network_settings[ravealert_archive_type]" value="post" <?php echo $ravealert_archive_type_post ?? ""; ?> /> Create Posts on a Site in the Network</label><br>
						<label for="ravealert_archive_type_cpt"><input type="radio" id="ravealert_archive_type_cpt" name="network_settings[ravealert_archive_type]" value="cpt" <?php echo $ravealert_archive_type_cpt ?? ""; ?>/> Enable CPT for Alerts on Root Site in Network</label>
						<p><small>If archiving is enabled, how do you want alerts to be archived?</small></p>
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
								if ( (null != $bc_rave_network_settings["ravealert_archive_site"]) && $net_site["blog_id"] == $bc_rave_network_settings["ravealert_archive_site"]) { 
									$site_selected = "selected";
								}
								$blog_detail = get_blog_details($net_site["blog_id"]);
								$blog_name = $blog_detail->blogname;
						?>
							<option value="<?php echo $net_site["blog_id"]; ?>" <?php echo $site_selected; ?>><?php echo $blog_name; ?></option>
						<?php } ?>
						</select>
						<p><small>If archiving to Posts is enabled, which site should they be sent to?</small></p>
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
