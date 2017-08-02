=== Jannes & Mannes Social Media Auto Publisher ===
Contributors: jannesmannes
Tags: social media, social, facebook, linkedin, twitter, yammer, publisher
Requires at least: 3.0.1
Tested up to: 4.7
Stable tag: 4.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin lets you automatically publish your posts to connected social media.

== Description ==

This plugin lets you automatically publish your posts to connected social media.

== Installation ==

1. Activate the plugin
2. Create a Facebook App and copy your Facebook App ID and Facebook App Secret
3. Go to the Auto Publisher section in your dashboard, paste the FB App Id and FB App Secret in the form field and save the settings page
4. Create a LinkedIn App (https://www.linkedin.com/developer/apps) and copy your LinkedIn Client ID and LinkedIn Client Secret
5. Go to the Auto Publisher section in your dashboard, paste the LinkedIn Client Id and LinkedIn Client Secret in the form field and save the settings page
6. Go to "Connected accounts" under de Auto Publisher section and click "Login with Facebook" and "Login with LinkedIn"
7. Go back to "Auto Publisher" and select to which pages and profiles you wish to publish your posts
6. You are done

== Frequently Asked Questions ==

= Where can I create a Yammer app? =

Here: https://developer.yammer.com/v1.0/docs/yammer-partners

== Changelog ==

= 0.5.2 =
Add missing files

= 0.5.1 =
Version number

= 0.5 =
- Schedule token expiry events
- Added a post meta box where you can check and un-check whether the post is already scheduled

= 0.4.2 =
Set the post is published status to "true" for all existing posts on activation so old posts wont be published automatically when you update them.

= 0.4.1 =
Added error logging

= 0.4 =
- Prevent double publishing by setting the posts published status to true directly after we schedule the publishes
- Save social media updates meta as custom post meta

= 0.3 =
Set message to _yoast_wpseo_opengraph-title if it is set, otherwise use the post title.

= 0.2 =
Updated tags

= 0.1 =
This is the first version of the plugin.
