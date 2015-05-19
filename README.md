rave-alert-notification

=======================

Contributors: Bellevue College
Tags: Alert, Notification
Tested for: 3.9.1
Stable tag: 1
License:
License URI:


== Description ==
This plugin parses the CAP(Common Access Protocol) xml feed to extract the description and applies the logic to calculate the time range for which the message will be displayed.
It adds the message right after the body tag. In addition to displaying the message, it also clears the cache and creates a post of the message on the specified website in the configuration.


== Configuration ==
Settings page allows to set following variables:
High Alert Flag
College Open Message
XML Feed URL
Clear Cache Url
Create Archive for rave alerts
Archive Site

The settings will be network wide.
Plugin uses Twitter Bootstrap. Here is the url:
http://getbootstrap.com/css/


== Installation ==
1. Download plugin files from https://github.com/BellevueCollege/rave-alert-notification.
2. Place the plugin folder in your `wp-content/plugins/` directory and activate it.
3. Set the CAP Inbound and CAP outbound channels.
4. Go to the network settings page to set the fields.


== Changelog ==
= v1 =
* Initial version
