<?php
/*
Plugin Name: Photogallery
Plugin URI: http://ondrejd.info/projects/wordpress-plugins/
Description: Photogalleries plugin. Originally developed for site <a href="http://www.volby09.cz/">www.volby09.cz</a>.
Version: 0.5.2
Author: Ondrej Donek
Author URI: http://www.ondrejd.info/
*/

/*  ***** BEGIN LICENSE BLOCK *****
    Version: MPL 1.1
    
    The contents of this file are subject to the Mozilla Public License Version 
    1.1 (the "License"); you may not use this file except in compliance with 
    the License. You may obtain a copy of the License at 
    http://www.mozilla.org/MPL/
    
    Software distributed under the License is distributed on an "AS IS" basis,
    WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
    for the specific language governing rights and limitations under the
    License.
    
    The Original Code is WordPress Photogallery Plugin.
    
    The Initial Developer of the Original Code is
    Ondrej Donek.
    Portions created by the Initial Developer are Copyright (C) 2009
    the Initial Developer. All Rights Reserved.
    
    Contributor(s):
    
    ***** END LICENSE BLOCK ***** */

// TODO Pridat dalsi widget (se zobrazenym miniaturnim nahledem od kazde galerie do ctverce (pro sidebar).
// TODO Umoznit pouzivat Google Picasa jako zdroj obrazku i celych galerii.
// TODO Umoznit pouzivat i dalsi sluzby (Flickr atp.)
// TODO Umoznit (na predvolbu) pouzivat standartni WordPress media.
// FIXME Don't use $wpdb here but use odWpPhotogalleryPluginModel instead!!!
// FIXME Use WordPress internal for getting plugin URL (several times in the file)!

// Add our widgets
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'od-photogallery-plugin-model.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'od-photogallery-plugin-renderer.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'od-photogallery-plugin-widgets.php';

/**
 * Main photogallery object
 * 
 * @author Ondrej Donek, <ondrejd@gmail.com>
 * @category odWpPhotogalleryPlugin
 * @version 0.5.2
 */
class odWpPhotogalleryPlugin /* extends WP_Plugin */
{
	static $plugin_id;
	static $version;
	static $textdomain;

	var $default_options = array(
		'main_gallery_dir' => 'wp-content/photogallery/',
		'gallery_page_id'  => 0,
		'gallery_thumb_size_width' => 100, 
		'gallery_thumb_size_height' => 75,
		'gallery_full_size_width' => 640,
		'gallery_full_size_height' => 480,
		'gallery_supported_img_types' => 'jpg,png',
		'gallery_max_upload_count' => 5
	);
	
	/**
	 * Constructor.
	 * 
	 * @return void
	 */
	function odWpPhotogalleryPlugin()
	{
		// Set up the plugin
		odWpPhotogalleryPlugin::$plugin_id = 'od-photogallery-plugin';
		odWpPhotogalleryPlugin::$version = '0.5.2';
		odWpPhotogalleryPlugin::$textdomain = odWpPhotogalleryPlugin::$plugin_id;

		// Ensure that plugin's options are initialized
		// TODO This is pretty usefull in development but it can be slowly on production use!
		$this->init_options();
	
		// Initialize the plugin
		load_plugin_textdomain(odWpPhotogalleryPlugin::$textdomain, 
		                       '/wp-content/plugins/' . odWpPhotogalleryPlugin::$plugin_id);
		
		register_activation_hook(__FILE__, array(&$this, 'activate'));
		register_deactivation_hook(__FILE__, array(&$this, 'deactivate'));
		
		if(is_admin()) {
			// TODO Add AJAX check for new galleries directories
			//add_action('admin_print_scripts', array(&$this, 'js_admin_header'));
			//add_action('wp_ajax_photogallery_check_dir', array(&$this, 'ajax_check_if_dir_exist'));
			
			// Register admin menu
			add_action('admin_menu', array(&$this, 'register_admin_menu'));
			
			// TODO Register TinyMCE buttons
			// add_action('init', array(&$this, 'register_tinymce_buttons'));
		}
		
		// Initialize plugin's widgets
		add_action('widgets_init', array(&$this, 'init_widgets'));
	}

	/**
	 * Activates the plugin
	 * 
	 * @returns void
	 */
	function activate()
	{
		global $wpdb;

		// Create the database table
		odWpPhotogalleryPluginModel::create_database(odWpPhotogalleryPlugin::$version);
	}

	/**
	 * Deactivates the plugin
	 * 
	 * @returns void
	 */
	function deactivate() { 
		// TODO Destroy database if user want it! 
	}
  
	/**
	 * Initialize plugin's options
	 *
	 * @return array
	 */
	function init_options() 
	{
		$options_id = odWpPhotogalleryPlugin::$plugin_id . '-options';
		$options = get_option($options_id);
		$need_update = false;

		if($options === false) {
			$need_update = true;
			$options = array();
		}

		foreach($this->default_options as $key => $value) {
			if(!array_key_exists($key, $options)) {
				$options[$key] = $value;
			}
		}

		if(!array_key_exists('latest_used_version', $options)) {
			$options['latest_used_version'] = odWpPhotogalleryPlugin::$version;
			$need_update = true;
		}

		if($need_update === true) {
			update_option($options_id, $options);
		}

		return $options;
	}
	
	/**
	 * Returns array with plugin options. 
	 * 
	 * @static
	 * @return array
	 */
	function get_options()
	{
		return get_option(odWpPhotogalleryPlugin::$plugin_id . '-options');
	}
	
	/**
	 * Add JavaScript needed by the plugin
	 * 
	 * @return void
	 */
	function js_admin_header()
	{
		wp_enqueue_script(array('jquery', 'sack'));

?><script type="text/javascript">
//<![CDATA[
addLoadEvent(function(func) { 
	if(typeof jQuery!="undefined") {
		jQuery(document).ready(function() {
			jQuery("#photogallery-create_new_folder-form").submit(function(event) {
				// TODO Disable submit button and show the animated image!
				var dirname = document.getElementById("photogallery-dirname").value;
				var ajax = new sack("<?php bloginfo('wpurl');?>/wp-admin/admin-ajax.php");    

				ajax.execute = 1;
				ajax.method = "POST";
				ajax.setVar("action", "wp_ajax_photogallery_check_dir");
				ajax.setVar("dirname", dirname);
				ajax.encVar("cookie", document.cookie, false);
				ajax.onError = function() { 
					alert("<?php __('Given directory name is not valid!', odWpPhotogalleryPlugin::$textdomain);?>"); 
					// TODO Hide animated image and enable submit button!
				};
				ajax.runAJAX();
				
				return false;
			});
		});
	}
});
//]]></script>
<?php
	}
  
	/**
	 * Registers administration menu for the plugin
	 * 
	 * @returns void
	 */
	function register_admin_menu() 
	{
		add_menu_page(__('Photogallery', odWpPhotogalleryPlugin::$textdomain),
					  __('Photogallery', odWpPhotogalleryPlugin::$textdomain),
					  0,
					  odWpPhotogalleryPlugin::$plugin_id,
					  array(&$this, 'admin_page'),
					  WP_PLUGIN_URL . '/' . odWpPhotogalleryPlugin::$plugin_id . '/icon16.png');
		add_submenu_page(odWpPhotogalleryPlugin::$plugin_id,
						 __('Photogallery - Add gallery', odWpPhotogalleryPlugin::$textdomain),
						 __('Add gallery', odWpPhotogalleryPlugin::$textdomain),
						 0,
						 'od-photogallery-add',
						 array(&$this, 'add_gallery'));
		add_submenu_page(odWpPhotogalleryPlugin::$plugin_id,
						 __('Photogallery - Settings', odWpPhotogalleryPlugin::$textdomain),
						 __('Settings', odWpPhotogalleryPlugin::$textdomain),
						 0,
						 'od-photogallery-settings',
						 array(&$this, 'settings_page'));
	}
	
	/*
	TODO Add photogallery's TinyMCE button!
	==========================================================================
	
	function tinymce_buttons() 
	{
		if(current_user_can('edit_posts') && !current_user_can('edit_pages'))
			return;

		if(get_user_option('rich_editing') == 'true') {
			add_filter('mce_external_plugins', 'add_myplugin_tinymce_plugin');
			add_filter('mce_buttons', 'register_myplugin_button');
		}
	}
 
	function register_myplugin_button($buttons) {
		array_push($buttons, "separator", "myplugin");
		return $buttons;
	}
 
	// Load the TinyMCE plugin : editor_plugin.js (wp2.5)
	function add_myplugin_tinymce_plugin($plugin_array) {
		$plugin_array['myplugin'] = URLPATH.'tinymce/editor_plugin.js';
		return $plugin_array;
	}
	
	==========================================================================
	*/
	
	/** 
	 * Check if given dirname for new gallery is valid and doesn't exist yet. 
	 * 
	 * @see http://codex.wordpress.org/AJAX_in_Plugins 
	 * @return void
	 */
	function ajax_check_if_dir_exist() 
	{
		$root_dir = odWpPhotogalleryPlugin::get_rootdir();
		$dirname = $_POST['dirname'];
		$msg_style = '';
		$msg_text = '';
		
		if(@file_exists($root_dir . $dirname)) {
			// TODO $msg_style = '';
			$msg_text = __('Directory or file with given name already exists! You should enter another name.', odWpPhotogalleryPlugin::$textdomain);
		} else {
			// TODO $msg_style = '';
			$msg_text =  __('Directory or file with given name doesn\'t exist yet &ndash; you can safely use it.', odWpPhotogalleryPlugin::$textdomain);
		}
		
		die('var odwppp_odiv = document.getElementById("photogallery-form_output_div"); ' . 
			'odwppp_odiv.appendChild(document.createTextNode("' . $msg_text  . '"));' . 
			'odwppp_odiv = setAttribute("style", "' . $msg_style  . '");');
	}
	
	/**
	 * Initializes widgets
	 * 
	 * @return void
	 */
	function init_widgets() 
	{
		register_widget('odWpPhotogallerypanelWidget');
		register_widget('odWpPhotogallerylist1Widget');
		// TODO register_widget('odWpPhotogallerylist2Widget'); 
		//   Tento druhy widget se seznamem fotogalerii zobrazuje namisto seznamu 
		//   uvodni obrazek fotogalerie v male ctvereckove miniature - na primy 
		//   proklik na cilovou fotogalerii.
	}
	
	/**
	 * Renders main admin page for plugin FOTOGALERIE.
	 * 
	 * @returns void
	 */
	function admin_page()
	{
		global $wpdb;
		
		$options = odWpPhotogalleryPlugin::get_options();
		
		$title = __('Photogallery', odWpPhotogalleryPlugin::$textdomain);
		$icon  = WP_PLUGIN_URL . '/' . odWpPhotogalleryPlugin::$plugin_id . '/icon32.png';
		odWpPhotogalleryPluginRenderer::print_admin_page_header($title, $icon);
		
		// Bulk actions
		if((isset($_POST['doaction']) || isset($_POST['doaction2']))) {
			$action = ($_POST['action'] != '-1') ? $_POST['action'] : $_POST['action2'];
			$items = (isset($_POST['items'])) ? $_POST['items'] : array();
			
			if(count($items) == 0) {
				odWpPhotogalleryPluginRenderer::print_admin_msg(__('No galleries selected!', odWpPhotogalleryPlugin::$textdomain), 'error');
			}
			else if($action == '-1') {
				odWpPhotogalleryPluginRenderer::print_admin_msg(__('No galleries selected!', odWpPhotogalleryPlugin::$textdomain), 'error');
			}
			else if($action == 'delete') {
				$query = "UPDATE `{$wpdb->prefix}fotogalerie` SET `deleted` = '1' WHERE ";
				
				for($i=0; $i<count($items); $i++) {
					$query = $query . "`ID`='" . $items[$i] . "' " . ((($i+1)==count($items)) ? '' : 'OR ');
				}
				
				$res = $wpdb->query($query);
				odWpPhotogalleryPluginRenderer::print_admin_msg(__('Selected galleries were deleted.', odWpPhotogalleryPlugin::$textdomain));
			}
			else if($action == 'undelete') {
				$query = "UPDATE `{$wpdb->prefix}fotogalerie` SET `deleted` = '0' WHERE ";
				
				for($i=0; $i<count($items); $i++) {
					$query = $query . "`ID`='" . $items[$i] . "' " . ((($i+1)==count($items)) ? '' : 'OR ');
				}
				
				$res = $wpdb->query($query);
				odWpPhotogalleryPluginRenderer::print_admin_msg(__('Selected galleries were undeleted.', odWpPhotogalleryPlugin::$textdomain));
			}
		}
		// User select gallery to edit
		else if(isset($_GET['gallery_ID'])) {
			$gallery_ID = $_GET['gallery_ID'];
			$gallery = odWpPhotogalleryPluginModel::get_gallery($gallery_ID, true);
			
			if(!is_null($gallery)) {
				odWpPhotogalleryPluginRenderer::print_edit_gallery_form($gallery);
			} else {
				odWpPhotogalleryPluginRenderer::print_admin_msg(sprintf(__('Gallery with given ID <code>%s</code> wasn\'t found!', odWpPhotogalleryPlugin::$textdomain), $gallery_ID), 'error');
			}
		}
		// User saved edited gallery
		else if(isset($_POST['savegallery']) || isset($_POST['editgallery-photos']) || isset($_POST['addgallery-photos'])) {
			$gallery_id = $_POST['gallery_ID'];
			$gallery = odWpPhotogalleryPluginModel::get_gallery($gallery_id, true);
			$gallery['title'] = $_POST['title'];
			$gallery['description'] = (isset($_POST['description'])) ? $_POST['description'] : '';
			
			odWpPhotogalleryPluginModel::update_gallery($gallery);
			odWpPhotogalleryPluginRenderer::print_admin_msg(__('Gallery was successfully updated.', odWpPhotogalleryPlugin::$textdomain));
			
			// User also want to edit gallery images
			if(isset($_POST['editgallery-photos'])) {
				$images = odWpPhotogalleryPluginModel::get_gallery_images($gallery_id, $gallery['folder'], -1, true);
				
				$form_url = get_bloginfo('home') . '/wp-admin/admin.php?page=od-photogallery-plugin';
				odWpPhotogalleryPluginRenderer::print_edit_gallery_images_form($form_url, $gallery, $images);
			}
			// User want to add some new images
			else if(isset($_POST['addgallery-photos'])) {
				$form_url = get_bloginfo('home') . '/wp-admin/admin.php?page=od-photogallery-plugin';
				// TODO Add the highest used 'order' in this gallery!!!
				odWpPhotogalleryPluginRenderer::render_upload_form($form_url, $gallery['folder'], $gallery_id, false, false);
			}
		}
		else if(isset($_POST['uploadgalleryfiles'])) {
			// User submit images upload form
			$files = $this->upload_files();

			if(count($files) > 0) {
				// After than user want to go and edit gallery photos
				$root_dir = odWpPhotogalleryPlugin::get_rootdir();
				$gallery_id = $_POST['gallery_id'];
				$gallery = odWpPhotogalleryPluginModel::get_gallery($gallery_id, true);
				// FIXME If $gallery is NULL!!!

				// Convert given array with uploaded files into the correct form
				$images = array();
				foreach($files as $file) {
					$photofile = str_replace($root_dir . $gallery['folder'] . DIRECTORY_SEPARATOR, '', $file);
					$img_type = str_replace('.', '', strtolower(strrchr($photofile, '.')));
					$urls = $this->get_urls_to_photo($gallery['folder'] . '/', $photofile, $img_type);

					$images[] = array('ID'          => null,
									  'title'       => '',
									  'description' => '',
									  'file'        => $photofile,
									  'full_url'    => $urls['full'],
									  'thumb_url'   => $urls['thumb'],
									  'order'       => 0,
									  'display'     => true,
									  'img_type'    => $img_type);
				}

				$form_url = get_bloginfo('home') . '/wp-admin/admin.php?page=od-photogallery-plugin';
				odWpPhotogalleryPluginRenderer::print_edit_gallery_images_form($form_url, $gallery, $images);
			}

			if(isset($_POST['moreimages'])) {
				$form_url = get_bloginfo('home') . '/wp-admin/admin.php?page=od-fotografie';
				// TODO Add the highest used 'order' in this gallery!!!
				odWpPhotogalleryPluginRenderer::render_upload_form($form_url, $_POST['dirname'], $gallery_ID, false);
			} else {
				// TOFO Display back link!
			}
		}
		else if(isset($_POST['savegalleryphotos'])) {
			// User submit gallery images edit form
			$gallery_ID = $_POST['gallery_ID'];
			$image_IDs = $_POST['image_ID'];
			$images = $_POST['image'];
			$titles = $_POST['title'];
			$descriptions = $_POST['description'];
			$order = $_POST['order'];
			$photos_count = count($images);
			
			for($i=0; $i<$photos_count; $i++) {
				if($image_IDs[$i] == '0') {
					odWpPhotogalleryPluginModel::add_gallery_image(array(
						'gallery_ID' => $gallery_ID,
						'title' => $titles[$i],
						'description' => $descriptions[$i], 
						'file' => $images[$i],
						'order' => $order[$i],
						'display' => true/* XXX ((isset($_POST['display_' . $i])) ? true : false)*/
					));
					odWpPhotogalleryPluginRenderer::print_admin_msg(__('Image was successfully saved.', odWpPhotogalleryPlugin::$textdomain));
				} else {
					odWpPhotogalleryPluginModel::update_gallery_image(array(
						'ID' => $image_IDs[$i],
						'gallery_ID' => $gallery_ID,
						'title' => $titles[$i],
						'description' => $descriptions[$i], 
						'file' => $images[$i],
						'order' => $order[$i],
						'display' => true/* XXX ((isset($_POST['display_' . $i])) ? true : false)*/
					));
					odWpPhotogalleryPluginRenderer::print_admin_msg(__('Image was successfully updated.', odWpPhotogalleryPlugin::$textdomain));
				}
			}
		}
		
		odWpPhotogalleryPluginRenderer::print_main_admin_page();
		odWpPhotogalleryPluginRenderer::print_admin_page_footer();
	}
	
	/**
	 * Renders add admin page for plugin FOTOGALERIE.
	 * 
	 * @return void
	 */
	function add_gallery()
	{
		// FIXME Firstly check if photogallery directory is writeable!!!
		global $wpdb;
		
		$options = odWpPhotogalleryPlugin::get_options();
		$root_dir = odWpPhotogalleryPlugin::get_rootdir();
		
		$title = __('Photogallery - Add gallery', odWpPhotogalleryPlugin::$textdomain);
		$icon  = WP_PLUGIN_URL . '/' . odWpPhotogalleryPlugin::$plugin_id . '/icon32.png';
		odWpPhotogalleryPluginRenderer::print_admin_page_header($title, $icon);
		
		// Render form for creating new gallery folder
		if(isset($_GET['uf'])) {
			$form_url = get_bloginfo('home') . '/wp-admin/admin.php?page=od-photogallery-add';
			odWpPhotogalleryPluginRenderer::print_create_gallery_dir_form($form_url);
		}
		// User submit 'create new gallery folder' form and now want to upload images
		elseif(isset($_POST['creategallerydir'])) {
			$dirname = $_POST['dirname'];
			$dirpath = odWpPhotogalleryPlugin::get_rootdir() . $dirname;
			$exists  = (@file_exists($dirpath)) ? true : false;
			
			// Create new folder
			if(!$exists) {
				// FIXME 0777 are not the best rights!
				$dir = @mkdir($dirpath, 0777);
				
				if($dir) { 
					// Folder created successfully
					odWpPhotogalleryPluginRenderer::print_admin_msg(sprintf(__('Directory <code>%s</code> was successfully created.', odWpPhotogalleryPlugin::$textdomain), $dirname));
					
					// Render fotogalerie upload form
					odWpPhotogalleryPluginRenderer::render_upload_form(get_bloginfo('home') . '/wp-admin/admin.php?page=od-photogallery-add', $dirname);
				} else { 
					// Folder creation failed
					odWpPhotogalleryPluginRenderer::print_admin_msg(sprintf(__('Folder <code>%s</code> wasn\'t created! Application couldn\'t continue with uploading images.', odWpPhotogalleryPlugin::$textdomain), $dirname), 'error');
				}

			}
			else {
				odWpPhotogalleryPluginRenderer::print_admin_msg(sprintf(__('Folder <code>%s</code> wasn\'t created because it already exists!', odWpPhotogalleryPlugin::$textdomain), $dirname));
			}
		}
		// User submitted uploading of new images
		elseif(isset($_POST['uploadgalleryfiles'])) {
			$this->upload_files();
			
			if(isset($_POST['moreimages'])) {
			  odWpPhotogalleryPluginRenderer::render_upload_form(get_bloginfo('home') . '/wp-admin/admin.php?page=od-photogallery-add', $_POST['dirname']);
			} else {
				// TODO Display back link!!!
			}
		}
		// User want to create new gallery from one of listed unused directories
		elseif(isset($_GET['dn'])) {
			$form_url = get_bloginfo('home') . '/wp-admin/admin.php?page=od-photogallery-add';
			odWpPhotogalleryPluginRenderer::print_create_gallery_from_dir_form($form_url);
		}
		// User want to save new gallery to db and select and edit images for it
		elseif(isset($_POST['creategallery1'])) {
			// Firstly save new gallery into the database ...
			$dirname = $_POST['dn'];
			$gallery = array('title'       => $_POST['title'], 
							 'description' => ((isset($_POST['description'])) ? $_POST['description'] : ''),
							 'folder'      => $dirname);
			$res = odWpPhotogalleryPluginModel::add_gallery($gallery);
			$gallery['ID'] = $wpdb->insert_id;
			odWpPhotogalleryPluginRenderer::print_admin_msg(sprintf(__('Gallery was successfully created (with ID <code>%s</code>).', odWpPhotogalleryPlugin::$textdomain), $gallery['ID']));
			
			// ... and create form with table with listed all images from the gallery's directory *
			$form_url = get_bloginfo('home') . '/wp-admin/admin.php?page=od-photogallery-add';
			$images = $this->get_available_images($dirname);
			$title = __('Available images', odWpPhotogalleryPlugin::$textdomain);
			$desc = sprintf(__('You\'re adding gallery <strong>%s</strong>. On this page ' . 
				               'you can select and describe images which you want to include ' . 
							   'into this new gallery.', odWpPhotogalleryPlugin::$textdomain), 
							   $gallery['title']);
			
			odWpPhotogalleryPluginRenderer::print_edit_gallery_images_form($form_url, 
			                                                               $gallery, 
																		   $images, 
																		   $title, 
																		   $desc);
		}
		// User want to save edited images of the new gallery
		elseif(isset($_POST['savegalleryphotos'])) {
			$gallery_ID = $_POST['gallery_ID'];
			$images = $_POST['image'];
			$titles = $_POST['title'];
			$descriptions = $_POST['description'];
			$order = $_POST['order'];
			$display = (isset($_POST['display'])) ? $_POST['display'] : 1;
			$photos_count = count($images);
			
			for($i=0; $i<$photos_count; $i++) {
				$image = array('gallery_ID'  => $gallery_ID, 
				               'title'       => $titles[$i],
							   'description' => $descriptions[$i],
				               'file'        => $images[$i],
				               'order'       => $order[$i],
				               'display'     => true/* XXX (isset($_POST['display_' . $i])) ? true : false*/);
				
				odWpPhotogalleryPluginModel::add_gallery_image($image);
				odWpPhotogalleryPluginRenderer::print_admin_msg(sprintf(__('Image was successfully created (with ID <code>%s</code>).', odWpPhotogalleryPlugin::$textdomain), $wpdb->insert_id));
			}
			
			odWpPhotogalleryPluginRenderer::print_admin_msg(__('Gallery was successfully created.', odWpPhotogalleryPlugin::$textdomain));
		}
		// User doesn't do anything yet, so display start page
		else {
			// User want to delete some empty directory
			if(isset($_GET['dd'])) {
				$res = $this->delete_empty_folder($_GET['dd']);
				
				if($res === true) {
					odWpPhotogalleryPluginRenderer::print_admin_msg(sprintf(__('Directory <code>%s</code> was successfully deleted.', odWpPhotogalleryPlugin::$textdomain), $dirname));
				} else {
					odWpPhotogalleryPluginRenderer::print_admin_msg(sprintf(__('Directory <code>%s</code> was not deleted!', odWpPhotogalleryPlugin::$textdomain), $dirname), 'error');
				}
			}
			
			// Print entry page for creating new galleries
			odWpPhotogalleryPluginRenderer::print_add_gallery_page();
		}
		
		odWpPhotogalleryPluginRenderer::print_admin_page_footer();
	}
	
	/** 
	 * Renders settings page for gallery
	 * 
	 * @return void
	 */
	function settings_page()
	{
		$options = odWpPhotogalleryPlugin::get_options();
		
		if(isset($_POST['settings_save'])){
			$options['main_gallery_dir'] = $_POST['option-main_gallery_dir']; 
			$options['gallery_page_id'] = (int) $_POST['option-gallery_page_id']; 
			$options['gallery_thumb_size_width'] = (int) $_POST['option-gallery_thumb_size_width']; 
			$options['gallery_thumb_size_height'] = (int) $_POST['option-gallery_thumb_size_height']; 
			$options['gallery_full_size_width'] = (int) $_POST['option-gallery_full_size_width'];
			$options['gallery_full_size_height'] = (int) $_POST['option-gallery_full_size_height'];
			$options['gallery_supported_img_types'] = $_POST['option-gallery_supported_img_types'];
			$options['gallery_max_upload_count'] = (int) $_POST['option-gallery_max_upload_count'];
			update_option(odWpPhotogalleryPlugin::$plugin_id . '-options', $options);
			
			odWpPhotogalleryPluginRenderer::print_admin_msg(__('Photogallery settings were updated.', odWpPhotogalleryPlugin::$textdomain));
		}
		
		odWpPhotogalleryPluginRenderer::print_settings_page();
	}
	
	/** 
	 * Returns full path to directory where are stored single photo galleries.
	 * 
	 * @return string
	 */
	function get_rootdir() 
	{
		// It's also called statically so no '$this'!
		$options = odWpPhotogalleryPlugin::get_options();
		$dir = str_replace('/', DIRECTORY_SEPARATOR, $options['main_gallery_dir']);
		
		// FIXME Get correct path by any other way (use WP internals if can)!!!
		return dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . $dir;
	}
	
	/**
	 * Returns count of image files in the given gallery directory. Defaultly  
	 * searching only for files with supported extensions.
	 * 
	 * @param $folder string Folder of the gallery.
	 * @param $root_folder string Optional.
	 * @return integer
	 */
	function get_files_count($folder, $root_folder = '')
	{
		// This method is used also as statical - so no $this
		if($root_folder == '') {
			$root_folder = odWpPhotogalleryPlugin::get_rootdir();
		}
		
		$options = odWpPhotogalleryPlugin::get_options();
		$supp_ext = $options['gallery_supported_img_types'];
		$path = $root_folder . $folder . DIRECTORY_SEPARATOR;
		$extensions = explode(',', $supp_ext);
		$total_count = 0;
		
		foreach($extensions as $extension) {
			// We need to examine if in the folder are already proccessed 
			// images by this plugin - this we can investigate if there 
			// each image is three times:
			//   - filename.jpg            - bigger version of resized image
			//   - filename_.jpg           - thumbnail version of resized image
			//   - filename-original.jpg   - original image
			$images_thumb = @glob($path . '*_.' . $extension);
			$images_orig  = @glob($path . '*-original.' . $extension);
			
			if(count($images_thumb) > 0 && (count($images_thumb) == count($images_orig))) {
				$total_count = $total_count + count($images_thumb);
			} else {
				$total_count = $total_count + count(@glob($path . '*.' . $extension));
			}
		}
		
		return $total_count;
	}
	
	/**
	 * Delete given folder. Folder MUST be empty.
	 * 
	 * @param string $dirname
	 * @retun boolean
	 */
	function delete_empty_folder($dirname)
	{
		$root_dir = odWpPhotogalleryPlugin::get_rootdir();
		$dirname = $_GET['dd'];
		
		if(@rmdir($root_dir . $dirname)) {
			return true;
		} 
		
		return false;
	}
		
	/**
	 * Handles gallery files uploads. Returns array of filenames of uploaded images
	 * 
	 * @return array Returns array of string ['image1.jpg', image2.jpg' ...]
	 */
	function upload_files() 
	{
		$options = odWpPhotogalleryPlugin::get_options();
		$dirname   = $_POST['dirname'];
		$uploaddir = odWpPhotogalleryPlugin::get_rootdir() . $dirname;
		$filenames = array();
		
		for($i = 0; $i < (int) $options['gallery_max_upload_count']; $i++) { 
			if($_FILES['image']['name'][$i] != '') {
				// Get supported images types
				$img_types = explode(',', $options['gallery_supported_img_types']);
				
				// FIXME Oh, and what about files with more than one dot!
				$ext = str_replace('.', '', strtolower(strrchr($_FILES['image']['name'][$i], '.'))); 
				
				if(!in_array($ext, $img_types)) {
					odWpPhotogalleryPluginRenderer::print_admin_msg(
						sprintf(__('File <code>%s</code> wasn\'t uploaded to the server ' . 
						           'because is in bad image format (for supported images ' . 
								   'formats see plugin\'s options).', 
								   odWpPhotogalleryPlugin::$textdomain), 
								$_FILES['image']['name'][$i]), 
								'error', 
								($i + 1));
				} else {
					$filename = basename($_FILES['image']['name'][$i]);
					$uploadfile = $uploaddir . DIRECTORY_SEPARATOR . str_replace('.' . $ext, '-original.' . $ext, $filename);
					
					// FIXME Check `max_upload_filesize` value!!!
					$res = @move_uploaded_file($_FILES['image']['tmp_name'][$i], $uploadfile);
					
					if($res) {
						$res &= $this->image_resize($uploadfile, $ext, 'full');
						$res &= $this->image_resize(str_replace('-original.' . $ext, '.' . $ext, $uploadfile), $ext, 'thumb');
						$filenames[] = $filename;
					}
					
					if(!$res) {
						odWpPhotogalleryPluginRenderer::print_admin_msg(sprintf(__('Image file <code>%s</code> wasn\'t uploaded to the server!', odWpPhotogalleryPlugin::$textdomain), $_FILES['image']['name'][$i]), 'error', ($i + 1));
					} else {
						odWpPhotogalleryPluginRenderer::print_admin_msg(sprintf(__('Image file <code>%s</code> was successfully uploaded to the server.', odWpPhotogalleryPlugin::$textdomain), $_FILES['image']['name'][$i]), 'updated', ($i + 1));
					}
				}
			}
		}
		
		return $filenames;
	}
	
	/**
	 * Returns all images which are founded in given gallery directory.
	 *  
	 * @param $dirname string
	 * @return array 
	 */
	function get_available_images($dirname)
	{
		$options = odWpPhotogalleryPlugin::get_options();
		$root_dir = odWpPhotogalleryPlugin::get_rootdir();
		$img_types = explode(',', $options['gallery_supported_img_types']);
		$images = array();
		
		foreach($img_types as $img_type) {
			$photos = @glob($root_dir . $dirname . DIRECTORY_SEPARATOR . '*_.' . $img_type);
			
			for($i=0; $i<count($photos); $i++) {
				$photo = $photos[$i];
				$photofile = str_replace($root_dir . $dirname . DIRECTORY_SEPARATOR, '', $photo);
				$photofile = str_replace('_.' . $img_type, '.' . $img_type, $photofile);
				$urls = $this->get_urls_to_photo($dirname . '/', $photofile, $img_type);
				$images[] = array(
						'ID'          => null, 
						'title'       => '', 
						'description' => '', 
						'file'        => $photofile, 
						'full_url'    => $urls['full'], 
						'thumb_url'   => $urls['thumb'], 
						'order'       => $i, 
						'display'     => true,
						'image_type'  => $img_type
				);
			}
		}
		
		return $images;
	}
	
	/**
	 * Returns public URL for the gallery with given ID. If instead of ID is 
	 * given NULL will be returned correct URL to the page on which is 
	 * photogallery attached.
	 * 
	 * @param number $gallery_id
	 * @return string
	 */
	function get_public_url_for_gallery($gallery_id = null)
	{
		$options = odWpPhotogalleryPlugin::get_options();
		$page_id = (int) $options['gallery_page_id'];
		// FIXME This is work well only when we are using pretty URLs!!!
		$url = get_bloginfo('home') . '/' . get_page_uri($page_id) . '/';
		
		if(!is_null($gallery_id)) {
			$url .= '?gallery_ID=' . $gallery_id;
		}
		
		return $url;
	}
	
	/**
	 * Generates public URLs for gallery photo and its thumbnail.
	 * 
	 * @param string $folder
	 * @param string $file
	 * @param string $type Image type (Optional)
	 * @return array Returns ['url' => '...', 'url_to_thumb' => '...']
	 */
	function get_urls_to_photo($folder, $file, $type = null)
	{
		$options = odWpPhotogalleryPlugin::get_options();
		
		$url  = get_bloginfo('home') . '/';
		$url .= $options['main_gallery_dir']; 
		// FIXME This expecting that there is allways ending '/'
		$url .= str_replace('/', '', str_replace(DIRECTORY_SEPARATOR, '', $folder)) . '/' . $file;
		
		$ext = (is_null($type)) 
			? str_replace('.', '', strtolower(strrchr($file, '.')))
			: $type;
		
		return array('full'  => $url, 
					 'thumb' => str_replace('.' . $ext, '_.' . $ext, $url));
	}
	
	/**
	 * Resizes uploaded image.
	 * 
	 * @param string $imagepath
	 * @param string $imagetype Type of image (extension e.g. 'jpg', 'png' etc.)
	 * @param string $size Which size we want ['full'|'thumb'].
	 * @return boolean
	 */
	function image_resize($imagepath = '', $imagetype = 'jpg', $size = 'full')
	{
		$options = odWpPhotogalleryPlugin::get_options();
		
		list($imagewidth, $imageheight) = getimagesize($imagepath);
		
		$xscale = $imagewidth / (int) $options['gallery_' . $size . '_size_width'];
		$yscale = $imageheight / (int) $options['gallery_' . $size . '_size_height'];
		
		if($yscale > $xscale){
			$new_width = round($imagewidth * (1 / $yscale));
			$new_height = round($imageheight * (1 / $yscale));
		} else {
			$new_width = round($imagewidth * (1 / $xscale));
			$new_height = round($imageheight * (1 / $xscale));
		}
		
		$image_resized = @imagecreatetruecolor($new_width, $new_height);
		if(!$image_resized) {
			return false;
		}
		
		$image_tmp = false;
		
		if($imagetype == 'jpg') {
			$image_tmp = @imagecreatefromjpeg($imagepath);
		} else if($imagetype == 'png') {
			$image_tmp = @imagecreatefrompng($imagepath);
		} else if($imagetype == 'gif') {
			$image_tmp = @imagecreatefromgif($imagepath);
		}
		
		$ret = false;
		
		if($image_tmp) {
			if(@imagecopyresampled($image_resized, $image_tmp, 0, 0, 0, 0, $new_width, 
		                           $new_height, $imagewidth, $imageheight)) 
			{
				$orig_filename = ($size == 'full')
						? str_replace('-original.' . $imagetype, '.' . $imagetype, $imagepath)
						: str_replace('.' . $imagetype, '_.' . $imagetype, $imagepath);
				
				if($imagetype == 'jpg') {
					$ret = @imagejpeg($image_resized, $orig_filename);
				} else if($imagetype == 'png') {
					$ret = @imagepng($image_resized, $orig_filename);
				} else if($imagetype == 'gif') {
					$ret = @imagegif($image_resized, $orig_filename);
				}

			}
		}
		
		@imagedestroy($image_resized); // Here doesn't matter if image is 
		// successfully destroyed (memory is free)
		
		return $ret;
	}
	
	/**
	 * Renders list of available galleries for the site.
	 * 
	 * @return string
	 */
	function get_public_galleries_list()
	{
		// FIXME Use odWpPhotogalleryPluginModel::get_galleries(...)!!!
		global $wpdb;
		
		$options = odWpPhotogalleryPlugin::get_options();
		$rows    = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}fotogalerie WHERE `deleted` = 0 ", ARRAY_A);
		$ret     = '';
    
		if(is_null($rows)) {
			return ''; // XXX Return some user message!
		}
		
		foreach($rows as $row) {
			$url = odWpPhotogalleryPlugin::get_public_url_for_gallery($row['ID']);
			$ret .= '<p><a href="' . $url . '">' . mysql2date('j.m.Y', $row['created'], true) . ', ' . $row['title'] . '</a></p>';
		}
		
		return $ret;
	}
	
	/**
	 * Renders gallery for the site.
	 * 
	 * @param integer $gallery_ID
	 * @return string
	 * @deprecated Since 0.5
	 */
	function render_public_gallery($gallery_ID = 1)
	{
		// FIXME Use odWpPhotogalleryPluginModel::get_gallery(...)!!!
		// FIXME Use odWpPhotogalleryPluginModel::get_gallery_images(...)!!! 
		global $wpdb;
		
		$options = odWpPhotogalleryPlugin::get_options();
		$ret     = '';
		$query1  = "SELECT * FROM `{$wpdb->prefix}fotogalerie` WHERE `ID` = '{$gallery_ID}' LIMIT 1 ";
		$gallery = $wpdb->get_results($query1, ARRAY_A);
		
		if(!is_null($gallery)) {
			if(count($gallery) == 1) {
				$ret .= '<div class="fotogalerie"><h2>' . __('Photogallery', odWpPhotogalleryPlugin::$textdomain) . '</h2>';
				$ret .= '<h3>' . mysql2date('j.m.Y', $gallery[0]['created'], true) . ", " . $gallery[0]['title'] . '</h3>';
				
				if(!empty($gallery[0]['description'])) {
					$ret .= '<h5>' . $gallery[0]['description'] . '</h5>';
				}
				
				$query2 = "SELECT * FROM `{$wpdb->prefix}fotogalerie_files` WHERE `gallery_ID`='{$gallery_ID}' AND `display`=1 ORDER BY `order` DESC ";
				$photos = $wpdb->get_results($query2);
				
				if(!is_null($photos)) {
					if(count($photos) > 0) {
						$ii = 1;
						for($i=0; $i<count($photos); $i++) {
							if($ii == 1) {
								$ret .= '<div class="fotky">';
							}
							
							$img_type = str_replace('.', '', strtolower(strrchr($photos[$i]->file, '.')));
							$urls = $this->get_urls_to_photo($gallery[0]['folder'], $photos[$i]->file, $img_type);
							$ret .= "
									<div class=\"fotka\">
										<a href=\"" . $urls['full']  . "\" rel=\"lightbox[gallery]\" title=\"" . $photos[$i]->title . "\"><img src=\"" . $urls['thumb'] . "\"/></a>
										<p>" . $photos[$i]->description . "</p>
									</div>";
							if($ii == 4) {
								$ret .= '</div>';
								$ii = 1;
							} else {
								$ii++;
							}
						}
						
						if($ii > 1 && $ii < 5) {
							$ret .= '</div>';
						}
					}
				}
				
				$ret .= '</div>';
			}
		}
		
		return $ret;
	}
	
}

// ===========================================================================
// Plugin initialization

global $od_photogallery_plugin;

$od_photogallery_plugin = new odWpPhotogalleryPlugin();

