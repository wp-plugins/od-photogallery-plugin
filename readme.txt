=== Plugin Name ===
Contributors: ondrejd
Donate link: http://www.ondrejd.info/projects/wordpress-plugins/od-wp-photogallery-plugin/
Tags: media,gallery,images
Requires at least: 2.8
Tested up to: 3.0.2
Stable tag: 0.5.2

Plugin for creating image galleries and publishing them with your posts. 

== Description ==

Main features:

* enable simple building of image galleries (it not uses built in media support). Is targeted for sites where is [WordPress](http://wordpress.org/ "Your favorite software") used as a CMS
* contains two widgets, one with the list of available galleries and second with images from the selected (or latest) gallery. This widget could be used on the main page of the site
* you can create galleries by two ways: using upload wizard or your favourite FTP client. If you are using the plugin upload wizard  images are automatically resized.
* offers English and Czech locales

== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload plugin's folder `od-photogallery-plugin` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Set up the plugin and its widgets
4. For other details see [plugin's home page](http://www.ondrejd.info/projects/wordpress-plugins/od-wp-photogallery-plugin/)

== Frequently Asked Questions ==

= Which types of images are supported? =

These types of images are supported: GIF, JPEG, PNG. But you can specify which you want to use (or users of site your administer) in plugin's options.

== Screenshots ==

1. `screenshot-1.png`
2. `screenshot-2.png`
3. `screenshot-3.png`
4. `screenshot-4.png`

== Changelog ==

= 0.5.3 =
* allowed to create more galleries from one directory
* added AJAX check if name for new directory is valid
* used WP admin internal abilities for help and configuration
* from this version is plugin hosted on http://wordpress.org/extend/plugins

= 0.5.2 =
* added support for more types of images then JPEG - currently can be used also PNG and GIF and you can specify supported types in plugin's options
* galleries now can hold also longer description
* added 'undelete' bulk action and made some changes around them
* added filters for which galleries to display on main admin page (Published, Deleted)
* added option for maximum count of images uploaded in once 
* fixed bug when saving plugin options
* fixed URL rendering

= 0.5.1 =
* repaired some bugs when edit galleries
* added 'List galleries' widtget

= 0.5 =
* initial public version (published on www.volby09.cz)
* added to SVN on code.google.com
