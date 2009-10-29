<?php
/**
 * Helper 'model' class for Photogallery plugin. All methods are mentioned 
 * to use as a statical.
 * 
 * @author Ondrej Donek, <ondrejd@gmail.com>
 * @category odWpPhotogalleryPlugin
 * @since 0.5
 */
class odWpPhotogalleryPluginModel
{
	
	// FIXME Prozkoumat [http://codex.wordpress.org/wpdb] a pripadne optimalizovat dle toho!
	
	/**
	 * Create or upgrade database according to given plugin's version
	 * 
	 * @param string $version
	 * @return boolean
	 */
	function create_database($version)
	{
		global $wpdb;
		
		$wpdb->query(
				"CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}fotogalerie` (
				  `ID` int(11) NOT NULL auto_increment,
				  `folder` varchar(255) NOT NULL,
				  `title` varchar(255) collate utf8_general_ci NOT NULL,
				  `description` tinytext collate utf8_general_ci NOT NULL,
				  `created` datetime NOT NULL default '0000-00-00 00:00:00',
				  `deleted` tinyint(1) NOT NULL default '0',
				  PRIMARY KEY  (`ID`)
				) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ;");
		$wpdb->query(
				"CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}fotogalerie_files` (
				  `ID` int(11) NOT NULL auto_increment,
				  `gallery_ID` int(11) NOT NULL default '0',
				  `title` varchar(255) collate utf8_general_ci NOT NULL,
				  `description` tinytext collate utf8_general_ci NOT NULL,
				  `file` varchar(255) NOT NULL,
				  `order` bigint(20) NOT NULL default '0',
				  `display` tinyint(1) NOT NULL default '1',
				  PRIMARY KEY  (`ID`)
				) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ;");
		
		return true;
	}
	
	/**
	 * Returns galleries ordered by date of creation.
	 * Returned array has this format:
	 *   array(0 => array('ID'           => 1, 
	 *                    'title'        => 'Some gallery\'s title', 
	 *                    'description'  => 'Some gallery\'s description.', 
	 *                    'folder'       => 'some-gallery', 
	 *                    'deleted'      => false, 
	 *                    'images_count' => 12)
	 *         1 => array(...))
	 * 
	 * @param boolean $deleted Optional. Defaultly FALSE.
	 * @return array|NULL
	 */
	function get_galleries($deleted = false) 
	{
		global $wpdb;
		
		$query = "
				SELECT 
					`t1`.*, 
					(SELECT count(`t2`.`ID`) FROM `{$wpdb->prefix}fotogalerie_files` AS `t2` WHERE `t2`.`gallery_ID` = `t1`.`ID` AND `t2`.`display` = 1) AS `images_count` 
				FROM `{$wpdb->prefix}fotogalerie` AS `t1` ";
		
		if(!$deleted) {
			$query .= "WHERE `t1`.`deleted` = 0 ";
		} else {
			$query .= "WHERE `t1`.`deleted` = 1 ";
		}
		
		$query .= "ORDER BY `t1`.`created` DESC ";
		$galleries = $wpdb->get_results($query, ARRAY_A);
		
		if(is_null($galleries)) 
			return null;
		
		if(count($galleries) == 0) 
			return null;
		
		return $galleries;
	}
	
	/**
	 * Helper method. Returns count of galleries which are NOT marked as 'deleted'.
	 * 
	 * @return integer
	 */
	function get_published_galleries_count()
	{
		global $wpdb;
		
		$query = 'SELECT count(`t1`.`ID`) AS `galleries_count` ' . 
		         'FROM `' . $wpdb->prefix . 'fotogalerie` AS `t1` ' . 
				 'WHERE `t1`.`deleted` = 0 ';
		
		return $wpdb->get_var($query);
	}
	
	/**
	 * Helper method. Returns count of galleries which are marked as 'deleted'.
	 * 
	 * @return integer
	 */
	function get_deleted_galleries_count()
	{
		global $wpdb;
		
		$query = 'SELECT count(`t1`.`ID`) AS `galleries_count` ' . 
		         'FROM `' . $wpdb->prefix . 'fotogalerie` AS `t1` ' . 
				 'WHERE `t1`.`deleted` = 1 ';
		
		return $wpdb->get_var($query);
	}
	
	/**
	 * Returns gallery with given id. 
	 * Returned array has this format:
	 *   array('ID'          => 1, 
	 *         'title'       => 'Some gallery\'s title', 
	 *         'description' => 'Some gallery\'s description.', 
	 *         'folder'      => 'some-gallery', 
	 *         'deleted'     => false)
	 * 
	 * @param number $gallery_id
	 * @param boolean $also_deleted Optional. Defaultly FALSE.
	 * @return array|NULL
	 */
	function get_gallery($gallery_id, $also_deleted = false) 
	{
		global $wpdb;
		
		$query = 'SELECT * FROM `' . $wpdb->prefix . 'fotogalerie` WHERE `ID` = \'' . $gallery_id . '\' '; 
		
		if(!$also_deleted) 
			$query .= 'AND `deleted` = 0 ';
		
		$query .= 'LIMIT 1 ';
		
		$gallery = $wpdb->get_results($query, ARRAY_A);
		
		if(is_null($gallery)) 
			return null;
		
		if(count($gallery) == 1) { 
			return $gallery[0];
		}
   
		return null;
	}
	
	/**
	 * Adds new gallery into the database.
	 * Given array should contains these values:
	 *   array('title'       => 'Some gallery\'s title', 
	 *         'description' => 'Some gallery\'s description.', 
	 *         'folder'      => 'some-gallery')
	 * 
	 * @param array $gallery
	 * @return boolean
	 */
	function add_gallery($gallery) 
	{
		global $wpdb;
		
		$query = 'INSERT INTO `' . $wpdb->prefix . 'fotogalerie` ' . 
		         '(`folder`, `title`, `description`, `created`, `deleted`) VALUES ' . 
				 '(\'' . $gallery['folder'] . '\', \'' . $gallery['title'] . '\', \'' . $gallery['description'] . '\', CURRENT_TIMESTAMP, 0) ';
		$wpdb->query($query);
		
		return true;
	}
	
	/**
	 * Updates given gallery.
	 * Given array should have this format:
	 *   array('ID'          => 1, 
	 *         'title'       => 'Some gallery\'s title', 
	 *         'description' => 'Some gallery\'s description.', 
	 *         'folder'      => 'some-gallery', 
	 *         'deleted'     => false)
	 * 
	 * @param array $gallery
	 * @return boolean
	 */
	function update_gallery($gallery) 
	{
		global $wpdb;
		
		$query = 'UPDATE `' . $wpdb->prefix . 'fotogalerie` SET ' . 
		         '`title` = \'' . $gallery['title'] . '\', ' . 
		         '`description` = \'' . $gallery['description'] . '\' ' . 
				 ' WHERE `ID` = ' . $gallery['ID'] . ' LIMIT 1 ';
		$wpdb->query($query);
		
		return true;
	}
	
	/**
	 * Returns array with images associated to gallery with given id.
	 * Returned array has this format:
	 *   array('ID'          => 1, 
	 *         'title'       => 'Some title', 
	 *         'description' => 'Some description.', 
	 *         'file'        => 'image.jpg', 
	 *         'full_url'    => 'http://some.url/image.jpg', 
	 *         'thumb_url'   => 'http://some.url/image_.jpg', 
	 *         'order'       => 0, 
	 *         'display'     => true)
	 * 
	 * @param number $gallery_id
	 * @param string $gallery_dir
	 * @param number $gallery_id Optional. Defaultly no limit.
	 * @param boolean $also_deleted Optional. Defaultly FALSE.
	 * @return array 
	 */
	function get_gallery_images($gallery_id, $gallery_dir, $max_count = -1, $also_deleted = false) 
	{
		global $wpdb;
		global $od_photogallery_plugin;
		
		$images = array();
		$query = 'SELECT * FROM `' . $wpdb->prefix . 'fotogalerie_files` WHERE `gallery_ID` = \'' . $gallery_id . '\' ';
		
		if(!$also_deleted) 
			$query .= 'AND `display` = 1 ';
		
		$query .= 'ORDER BY `order` ASC  ';
		
		if($max_count != -1) 
			$query .= 'LIMIT 0, ' . $max_count . ' ';
		
		$rows = $wpdb->get_results($query);
		
		if(!is_null($rows)) {
			foreach($rows as $row) {
				$img_type = str_replace('.', '', strtolower(strrchr($row->file, '.')));
				$urls = $od_photogallery_plugin->get_urls_to_photo($gallery_dir, $row->file, $img_type);
				$images[] = array('ID'          => $row->ID, 
								  'title'       => $row->title,
								  'description' => $row->description,
								  'file'        => $row->file,
								  'full_url'    => $urls['full'],
								  'thumb_url'   => $urls['thumb'],
								  'order'       => $row->order,
								  'display'     => ($row->display == '1') ? true : false);
			}
		}
		
		return $images;
	}
	
	/**
	 * Saves new gallery image into the database.
	 * The image should be array formatted like this:
	 *   array('gallery_ID'  => 1,
	 *         'title'       => 'Some title',
	 *         'description' => 'Some description',
	 *         'file'        => 'image_file.jpg',
	 *         'order'       => 0,
	 *         'display'     => true)
	 * 
	 * @param array $image
	 * @return boolean
	 */
	function add_gallery_image($image)
	{
		global $wpdb;
		
		$query = 'INSERT INTO `' . $wpdb->prefix . 'fotogalerie_files` ' . 
		         '(`gallery_ID`, `title`, `description`, `file`, `order`, `display`) VALUES ' . 
				 '(\'' . $image['gallery_ID'] . '\', ' . 
				 '\'' . $image['title'] . '\', ' . 
				 '\'' . $image['description'] . '\', ' . 
				 '\'' . $image['file'] . '\', ' . 
				 '\'' . $image['order'] . '\', ' . 
				 '\'' . (($image['display'] === true) ? '1' : '0') . '\') ';
		$res = $wpdb->query($query);
		
		return true;
	}
	
	/**
	 * Updates given gallery image into the database.
	 * The image should be array formatted like this:
	 *   array('ID'          => 55,
	 *         'gallery_ID'  => 11,
	 *         'title'       => 'Some title',
	 *         'description' => 'Some description',
	 *         'file'        => 'image_file.jpg',
	 *         'order'       => 0,
	 *         'display'     => true)
	 * 
	 * @param array $image
	 * @return boolean
	 */
	function update_gallery_image($image)
	{
		global $wpdb;
		
		$query = 'UPDATE `' . $wpdb->prefix . 'fotogalerie_files` SET ' . 
		         '`gallery_ID` = \'' . $image['gallery_ID'] . '\', ' . 
		         '`title` = \'' . $image['title'] . '\', ' . 
		         '`description` = \'' . $image['description'] . '\', ' . 
		         '`file` = \'' . $image['file'] . '\', ' . 
		         '`order` = \'' . $image['order'] . '\', ' . 
		         '`display` = \'' . (($image['display'] === true) ? '1' : '0') . '\' ' . 
				 'WHERE `ID` = ' . $image['ID'] . ' ' . 
				 'LIMIT 1 ';
		$res = $wpdb->query($query);
		
		return true;
	}
	
	/**
	 * Checks if folder with given name is used in any gallery or not.
	 * 
	 * @param string $dirname
	 * @return boolean
	 */
	function check_if_dir_is_used($dirname) 
	{
		global $wpdb;
		
		$exist = $wpdb->get_var('SELECT `ID` ' . 
		                        'FROM `' . $wpdb->prefix . 'fotogalerie` ' . 
		                        'WHERE ' . 
									'`folder` = \'' . $dirname . '\' AND ' . 
									'`deleted` = 0 ' . 
								'LIMIT 1 ');
		
		return !is_null($exist);
	}
	
}
