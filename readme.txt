=== NextGen Public Image Uploader (PUP) ===
Contributors: Lagdonkey
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=G8PTNMEVCA5QY&lc=CA&item_name=NextGen%20Public%20Image%20Uploader&currency_code=CAD&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted
Tags: NextGen, Gallery, front-end, image, uploader, user, graphics, pictures, upload, moderation, queue, secure
Requires at least: Any
Tested up to: 3.6
Stable tag: 1.2.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin will allow frontend image uploads. It will intergrate with NextGen Gallery, but it can be used as a standalone uploader as well. 

== Description ==

NextGen Public Image Uploader is a plugin that is designed to intergrate with the popular gallery plugin, NextGen Gallery
for wordpress. While it is designed to intergrate with NGG, it can also be used as a standalone image uploading plugin as
well. This plugin was designed from the ground up, to be flexible, secure and stable. It boasts many features, including:

Thumbnail Generation. When uploading directly to a gallery, this plugin will not only update the gallery list, it will
also create thumbnails automatically. No extra work needed, galleries are automatically updated.  
  
Direct to gallery upload. For example [ngg_public_uploader gid="3"] would create an upload
page that sends all uploads directly to gallery 3.
  
Upload to queue. If you use NextGen Gallery, but don't want user uplods to be moved automatically, you can enable
the moderation queue. All user uploads will go into an upload directory as set in the settings page, and then put into 
a temporary holding queue until they are approved. You can set specific user roles that can access the queue, with options
of either deleting the images uploaded, or moving them to a set gallery. 
 
Even if you don't use NextGen Gallery, this plugin can still be used to allow users to upload images to a directory you 
specify. From there, you are free to use the images in posts, or anywhere else on your site you want. 

E-mail notification. This plugin can also be set up to notify you when a user uploads images. 

Create galleries. Yes, you can even make new galleries, right from the front-end. 

For a working demo of the plugin in action, visit: http://www.demo.amazinglyamusing.com/moderation/
== Installation ==

Installation of this plugin is exactly the same as any other wordpress plugin. If you 
are uplading via your wordpress admin panel, simply upload the ngg-pup.rar file
through the client and wordpress will automatically unpack the file. Once wordpress
is done, you are given the option of activating it. Simply press activate, and voila
you are done. 

If you are uploading the file manually, unpack the ngg-pup.rar file. This will create
a folder called Ngg_pup. Take this folder, and manually it via FTP or whatever method
you prefer, and store it in your plugins folder which are typically stored in 
your-website/wp-content/plugins. Once uploaded, you should see:
your-website/wp-content/plugins/Ngg_pup. 

Once the plugin is manually uploaded, go into your plugin manager page in wordpres
and manually activate the plugin by clicking on the activate link. 

It's recommended that after activation, you go to the settings page and 
configure the plugin to what you want.  

You can use the following shortcodes once everything is configured:

[ ngg_public_uploader ] - This code creates an uploading page. Any files uploaded through this
page will be moved to the directory you set in the options page. If you have the moderation queue
enabled, uploads through this page will be stored in the queue. 

[ ngg_public_uploader gid="xx" ] - Where xx is the gallery ID of the gallery you want uploads to go
to. This will create an upload page, that will send all uploads directly to the gallery you specify. 
This will bypass the queue, and will update the gallery list, as well as create a thumbnail in the 
gallery list. Using this option, all files are processed completely automatically. 

[ NGG_pup_Moderation ] If enabled in the settings page, this will create a page that gives access
to the moderation queue. All files marked for moderation will show up on this page, and users with
access will be able to move or delete files. Once moved or deleted, the file is removed from the
queue. 

== Frequently Asked Questions ==

= Do I need NextGen Gallery installed, to use this plugin? =

Not at all, this plugin will work just fine on it's own.

= Is this plugin secure? =

Absolutely. Many steps are taken to ensure everything this plugin does is completely secure.
Each image has to pass through many validation checks, and all user input is completely sanitized.

== Screenshots ==

1. Settings page screen 1
2. Settings page screen 2
3. Settings page screen 3
4. Settings page screen 4
5. Settings page screen 5
6. An example of the moderation queue. 

== Changelog ==

= 1.2.2 Farrah Fowler = 

More small fixes, including removing errant file from includes folder. If you notice subscribed.php, please delete it immediately!!!

Also fixed output, so it will now sit right on the page in relation to where the shortcode is placed. 

= 1.2.1 Farrah Fowler =  

Emergency update, it's imperative this update be done immediately. The last update enabled anonymous uploading, but unfortunately a bug was forcing the option to stay on by default. This patch fixes the issue. Sorry for any inconvenience. 

= 1.2 Farrah Fowler = 

Quick update, to include the ability to enable users who are not logged in to upload. 

= 1.1 Wolowitz = 

* Added the ability to create new galleries on the front end. Of course this can be configured
 by role, or disabled entirely.  
* Did some housekeeping, including extra error handling. Tried to make sure
almost all errors are handled by the plugin, so no PHP errors should ever be seen. 
* Made the entire front-end nicer, including menu streamlining and other nice touches. 
  
= 1.0 Cooper =
* First released version. 

== Upgrade Notice ==

= 1.1 Wolowitz = 

* Added the ability to create new galleries on the front end. Of course this can be configured
 by role, or disabled entirely.  
* Did some housekeeping, including extra error handling. Tried to make sure
almost all errors are handled by the plugin, so no PHP errors should ever be seen. 
* Made the entire front-end nicer, including menu streamlining and other nice touches. 

= 1.0 =
First public releas. While the plugin was been thoroughly tested before release, it should be noticed this is currently
a public beta. 

