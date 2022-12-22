# Bellevue College WordPress Rave Alert Notification Plugin

## Description

This plugin parses the CAP (Common Alert Protocol) xml feed to extract the description and applies the logic to calculate the time range for which the message will be displayed.
It adds the message right after the body tag. Optionally it can also create a post (standard or CPT) of the message on the specified website in the configuration.

## Caching and Performance Considerations

This plugin puts significant load on your site, since every open page of the website will poll the API for active alerts every minute. When an alert is sent, this will trigger additional requests to alert-specific endpoints. 

Speed of alert dissemination has been prioritized, since the assumption is that this will be run in a robust environment with robust caching. Given the two overlapping caches, the plugin should be able to disseminate alerts to all users within 120 seconds of the alert appearing on the CAP feed. 

Load on the RAVE CAP feed is reduced by having the plugin load the feed server-side every 60 seconds using WP Cron, and saving the contents to a Network Option. The API endpoint further caches alert content to a transient. 

API Endpoints also include a required time parameter (I suggest this is a four-digit number representing UTC hours and minutes). This allows a server-side cache (or something like Cloudflare) to cache the response for the vast majority of requests. Ideally your server will only have to regenerate this once every minute, even with a large number of users.


## Configuration

Settings page allows to set following variables:
* Enable or Disable Manual Message
* Text of Manual Message
* CAP Feed URL (XML)
* Enable or Disable Archiving
* Archive Type
* Archive Site (if Archive Type is set to `post`)

This plugin is designed to be Network Activated on a WordPress multisite. It also expects FontAwesome or Glyphicons to be available.


## Installation

1. Download plugin files from https://github.com/BellevueCollege/rave-alert-notification.
2. Place the plugin folder in your `wp-content/plugins/` directory and activate it at the network level.
4. Go to the network settings page to set the fields.


