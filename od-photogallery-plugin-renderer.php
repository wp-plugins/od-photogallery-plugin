<?php
/**
 * Class used for holding helper methods for plugin's admin rendering. 
 * All methods are mentioned to use as a statical.
 * 
 * @author Ondrej Donek, <ondrejd@gmail.com>
 * @category odWpPhotogalleryPlugin
 * @since 0.5
 */
class odWpPhotogalleryPluginRenderer
{
	
	/**
	 * Prints admin user message
	 * 
	 * @param string $message
	 * @param string $type Optional. Defaultly 'updated'.
	 * @param string $id_suffix Optional. Defaultly empty.
	 * @return void
	 */
	function print_admin_msg($message, $type = 'updated', $id_suffix = '') 
	{
		$type = ($type == 'updated' || $type == 'error') ? $type : 'updated';
		
		printf('<div id="message%1$s" class="%2$s fade"><p>%3$s</p></div>', 
		       $id_suffix, $type, $message);
	}
	
	/**
	 * Prints header for the admin page
	 * 
	 * @param string $page_title
	 * @param string $page_icon
	 * @return void
	 */
	function print_admin_page_header($plugin_title, $plugin_icon)
	{
?>
		<div class="wrap">
			<div class="icon32">
				<img src="<?php echo $plugin_icon;?>"/>
			</div>
			<h2><?php echo $plugin_title;?></h2>
<?php
	}
	
	/**
	 * Prints footer for the admin page
	 * 
	 * @return void
	 */
	function print_admin_page_footer()
	{
?>
		</div>
<?php
	}
	
	/**
	 * Prints plugin's main admin page
	 * 
	 * @return void
	 */
	function print_main_admin_page()
	{
		$form_url = get_bloginfo('home') . '/wp-admin/admin.php?page=od-photogallery-plugin';
		$add_form_url = get_bloginfo('home') . '/wp-admin/admin.php?page=od-photogallery-add';
		$root_dir = odWpPhotogalleryPlugin::get_rootdir();
		
		// Select which galleries should be displayed
		$gallery_status = (isset($_GET['gallery_status'])) 
				? $_GET['gallery_status'] 
				: ((isset($_POST['gallery_status'])) ? $_POST['gallery_status'] : 'published');
		$published = odWpPhotogalleryPluginModel::get_published_galleries_count();
		$deleted = odWpPhotogalleryPluginModel::get_deleted_galleries_count();
		
		// Get galleries to display
		$galleries = ($gallery_status == 'published') 
				? odWpPhotogalleryPluginModel::get_galleries(false)
				: odWpPhotogalleryPluginModel::get_galleries(true);
		$i = 0;
		
		// TODO (0.6+) Add paging!
?>
		<form action="<?php echo $form_url;?>" method="post" enctype="multipart/form-data">
			<ul class="subsubsub">
				<li><a href="<?php echo $form_url;?>&amp;gallery_status=published"<?php if($gallery_status=='published'){ echo ' class="current"'; }?>><?php echo sprintf(__('Published <span class="count">(%s)</span>', odWpPhotogalleryPlugin::$textdomain), $published);?></a> |</li>
				<li><a href="<?php echo $form_url;?>&amp;gallery_status=deleted"<?php if($gallery_status=='deleted'){ echo ' class="current"'; }?>><?php echo sprintf(__('Deleted <span class="count">(%s)</span>', odWpPhotogalleryPlugin::$textdomain), $deleted);?></a></li>
			</ul>
			<div class="tablenav">
				<input type="hidden" name="gallery_status" value="<?php echo $gallery_status;?>"/>
				<div class="alignleft actions">
					<select name="action" class="select-action">
						<option value="-1" selected="selected"><?php echo __('Bulk actions', odWpPhotogalleryPlugin::$textdomain);?></option>
						<option value="delete"><?php echo __('Delete', odWpPhotogalleryPlugin::$textdomain);?></option>
						<option value="undelete"><?php echo __('Undelete', odWpPhotogalleryPlugin::$textdomain);?></option>
					</select>
					<input type="submit" value="<?php echo __('Apply', odWpPhotogalleryPlugin::$textdomain);?>" name="doaction" id="doaction" class="button-secondary action" />
				</div>
				<br class="clear" />
			</div>
			<div class="clear"></div>
			<table class="widefat post fixed" cellspacing="0">
				<thead>
					<tr>
						<th scope="col" style="width:5%;" class="manage-column column-cb check-column"><input type="checkbox" /></th>
						<th scope="col" style="width:45%;" class="manage-column column-cb"><?php echo __('Title', odWpPhotogalleryPlugin::$textdomain);?></th>
						<th scope="col" style="width:20%;" class="manage-column column-cb"><?php echo __('Folder', odWpPhotogalleryPlugin::$textdomain);?></th>
						<th scope="col" style="width:20%;" class="manage-column column-cb"><?php echo __('Created', odWpPhotogalleryPlugin::$textdomain);?></th>
						<th scope="col" style="width:10%;text-align:center;" class="manage-column column-cb"><?php echo __('Images count', odWpPhotogalleryPlugin::$textdomain);?></th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<th scope="col" style="width:5%;" class="manage-column column-cb check-column"><input type="checkbox"/></th>
						<th scope="col" style="width:45%;" class="manage-column column-cb"><?php echo __('Title', odWpPhotogalleryPlugin::$textdomain);?></th>
						<th scope="col" style="width:20%;" class="manage-column column-cb"><?php echo __('Folder', odWpPhotogalleryPlugin::$textdomain);?></th>
						<th scope="col" style="width:20%;" class="manage-column column-cb"><?php echo __('Created', odWpPhotogalleryPlugin::$textdomain);?></th>
						<th scope="col" style="width:10%;text-align:center;" class="manage-column column-cb"><?php echo __('Images count', odWpPhotogalleryPlugin::$textdomain);?></th>
					</tr>
				</tfoot>
				<tbody id="the-list" class="list:od-photogallery">
					<?php if(is_null($galleries)):?>
					<tr class="status-inherit">
						<td colspan="5">
							<?php if($gallery_status == 'deleted' && ((int) $published > 0)):?>
							<p><?php echo sprintf(__('No galleries are founded but you have selected filter for displaying ' . 
							                         '<em>only deleted</em> galleries. You can <a href="%1$s">switch this ' . 
													 'filter off</a> or create <a href="%2$s">new gallery</a>.', 
											         odWpPhotogalleryPlugin::$textdomain), 
												  $form_url . '&amp;gallery_status=published', $add_form_url);?></p>
							<?php else:?>
							<p><?php echo sprintf(__('No galleries are founded. You can create new one <a href="%s">here</a>.', 
							                         odWpPhotogalleryPlugin::$textdomain), 
												  $add_form_url);?></p>
							<?php endif;?>
						</td>
					</tr>
					<?php else:?>
					<?php foreach($galleries as $gallery):?>
					<tr class="<?php echo (($i++%2) ? 'alternate' : '' );?> status-inherit">
						<th scope="row" class="check-column"><input type="checkbox" name="items[]" value="<?php echo $gallery['ID'];?>"/></td>
						<td><a href="<?php echo $form_url;?>&amp;gallery_ID=<?php echo $gallery['ID'];?>&amp;gallery_status=<?php echo $gallery_status;?>"><?php echo $gallery['title'];?></a></td>
						<td><code style="font-size: 6pt;"><?php echo $gallery['folder'];?></code></td>
						<td><?php echo mysql2date('j F Y', $gallery['created'], true);?></td>
						<td style="color:<?php echo (($gallery['images_count']!=0)?'green':'#f30');?>;text-align:center;"><?php echo $gallery['images_count'];?></td>
					</tr>
					<?php endforeach;?>
					<?php endif;?>
				</tbody>
			</table>
			<div class="tablenav">
				<div class="alignleft actions">
					<select name="action2" class="select-action">
						<option value="-1" selected="selected"><?php echo __('Bulk actions', odWpPhotogalleryPlugin::$textdomain);?></option>
						<option value="delete"><?php echo __('Delete', odWpPhotogalleryPlugin::$textdomain);?></option>
						<option value="undelete"><?php echo __('Undelete', odWpPhotogalleryPlugin::$textdomain);?></option>
					</select>
					<input type="submit" value="<?php echo __('Apply', odWpPhotogalleryPlugin::$textdomain);?>" name="doaction2" id="doaction2" class="button-secondary action" />
				</div>
				<br class="clear" />
			</div>
		</form>
<?php
	}
	
	/**
	 * Prints admin page for creating new galleries
	 * 
	 * @return void
	 */
	function print_add_gallery_page()
	{
		global $wpdb;
		
		$options = odWpPhotogalleryPlugin::get_options();
		$root_dir = odWpPhotogalleryPlugin::get_rootdir();
		$i = 0;
?>
		<div>
			<p><?php echo __('Here you can create new gallery from already existing folders with images.', 
							 odWpPhotogalleryPlugin::$textdomain);?></p>
			<p><?php printf(__('Folders of single galleries should be save only in main photogallery ' . 
				               'folder as is defined in <a href="%1$s">plugin\'s options</a> (currently ' . 
				               '<code>%2$s</code>).<br/>' . 
				               'For upload use either your favourite FTP client either plugin\'s own ' . 
							   '<a href="%3$s"><strong>wizard for uploading new images</strong></a>. If' . 
							   'you want to upload images through your FTP client all images already ' . 
							   '<strong>should be</strong> available in all required sizes (original, ' . 
							   'big, thumb) and saved with correct filenames (you can show to any folder of ' . 
							   'an existing gallery to see what is meaned by <em>correct filenames</em>).', 
							   odWpPhotogalleryPlugin::$textdomain),
							   get_bloginfo('home') . '/wp-admin.php?page=od-photogallery-settings', 
							   $options['main_gallery_dir'], 
							   get_bloginfo('home') . '/wp-admin/admin.php?page=od-photogallery-add&amp;uf=1');?></p>
		</div>
		<h3><?php echo __('Existing directories which are not associated with any gallery', odWpPhotogalleryPlugin::$textdomain);?></h3>
		<table class="widefat post fixed" cellspacing="0">
			<thead>
				<tr>
					<th scope="col" style="width:60%;"><?php echo __('Folder', odWpPhotogalleryPlugin::$textdomain);?></th>
					<th scope="col" style="width:10%; text-align:center;"><?php echo __('Images count', odWpPhotogalleryPlugin::$textdomain);?></th>
					<th scope="col" style="width:30%; text-align:center;"> &nbsp;</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th scope="col" style="width:60%;"><?php echo __('Folder', odWpPhotogalleryPlugin::$textdomain);?></th>
					<th scope="col" style="width:10%; text-align:center;"><?php echo __('Images count', odWpPhotogalleryPlugin::$textdomain);?></th>
					<th scope="col" style="width:30%; text-align:center;"> &nbsp;</th>
				</tr>
			</tfoot>
			<tbody id="the-list" class="list:od-photogallery">
<?php
		// Get all folders
		$dirs = glob($root_dir . '*', GLOB_ONLYDIR);
		if(count($dirs) > 0) {
			foreach($dirs as $dir) {
				$dirname = str_replace($root_dir, '', $dir);
				
				// Filter those which are not used by any of existing galleries
				if(!odWpPhotogalleryPluginModel::check_if_dir_is_used($dirname)) {
					$photo_count = odWpPhotogalleryPlugin::get_files_count($dirname);
?>
			<tr class="<?php echo ($i++%2) ? 'alternate' : '';?> status-inherit">
				<td><code style="font-size: 6pt;"><?php echo $dirname;?></code></td>
				<td style="color:<?php echo (($photo_count!=0) ? 'green' : '#f30');?>;text-align:center;"><?php echo $photo_count;?></td>
				<td>
					<?php if($photo_count == 0):?>
					<a href="admin.php?page=od-photogallery-add&amp;dd=<?php echo $dirname;?>"><?php echo __('Delete folder', odWpPhotogalleryPlugin::$textdomain);?></a>
					<?php else: ?>
					<a href="admin.php?page=od-photogallery-add&amp;dn=<?php echo $dirname;?>&amp;pc=<?php echo $photo_count;?>"><?php echo __('Create gallery', odWpPhotogalleryPlugin::$textdomain);?></a>
					<?php endif;?>
				</td>
			</tr>
<?php
					$i++;
				}
			}
		} 
		
		if($i == 0) {
?>
			<tr class="status-inherit">
				<td colspan="3">
					<?php printf(__('<strong>There are no existing and unused directories with ' . 
					                'images.</strong> If you want to create new one use our ' . 
								    '<a href="%s"><strong>wizard for uploading new images</strong></a>.', 
								    odWpPhotogalleryPlugin::$textdomain),
								 get_bloginfo('home') . '/wp-admin/admin.php?page=od-photogallery-add&amp;uf=1');?>
				</td>
			</tr>
<?php
		}
?>
			</tbody>
		</table>
		<!--
		<p><?php echo __('Images in these directories already <strong>should be</strong> ' . 
		                 'available in all required sizes (original, big, thumb) and saved ' . 
		                 'with correct filenames.', odWpPhotogalleryPlugin::$textdomain);?></p>
		-->
<?php
	}
	
	/** 
	 * Prints plugin's settings page
	 * 
	 * @return void
	 */
	function print_settings_page()
	{
		$options = odWpPhotogalleryPlugin::get_options();
		$title = __('Photogallery - Settings', odWpPhotogalleryPlugin::$textdomain);
		$icon = WP_PLUGIN_URL . '/' . odWpPhotogalleryPlugin::$plugin_id . '/icon32.png';
		self::print_admin_page_header($title, $icon);
?>
		<form action="<?php echo get_bloginfo('home');?>/wp-admin/admin.php?page=od-photogallery-settings" method="post" enctype="multipart/form-data">
			<div>
				<table class="widefat post fixed" cellpadding="1" cellspacing="1" style="100%;">
					<tr>
						<th scope="row"><label for="option-gallery_page_id"><?php echo __('Set ID of page which acts as gallery main page:', odWpPhotogalleryPlugin::$textdomain);?></label></th>
						<td><input type="text" name="option-gallery_page_id" value="<?php echo $options['gallery_page_id'];?>" style="min-width: 300px;"/></td>
					</tr>
					<tr>
						<th scope="row"><label for="option-main_gallery_dir"><?php echo __('Set directory for storing single galleries:', odWpPhotogalleryPlugin::$textdomain);?></label></th>
						<td><input type="text" name="option-main_gallery_dir" value="<?php echo $options['main_gallery_dir'];?>" style="min-width: 300px;"/></td>
					</tr>
					<tr>
						<th scope="row"><label for="option-gallery_thumb_size_width"><?php echo __('Set max. width of thumbnail images:', odWpPhotogalleryPlugin::$textdomain);?></label></th>
						<td><input type="text" name="option-gallery_thumb_size_width" value="<?php echo $options['gallery_thumb_size_width'];?>"/>&nbsp;<?php echo __('pixels', odWpPhotogalleryPlugin::$textdomain);?></td>
					</tr>
					<tr>
						<th scope="row"><label for="option-gallery_thumb_size_height"><?php echo __('Set max. height of thumbnail images:', odWpPhotogalleryPlugin::$textdomain);?></label></th>
						<td><input type="text" name="option-gallery_thumb_size_height" value="<?php echo $options['gallery_thumb_size_height'];?>"/>&nbsp;<?php echo __('pixels', odWpPhotogalleryPlugin::$textdomain);?></td>
					</tr>
					<tr>
						<th scope="row"><label for="option-gallery_full_size_width"><?php echo __('Set max. width for larger images:', odWpPhotogalleryPlugin::$textdomain);?></label></th>
						<td><input type="text" name="option-gallery_full_size_width" value="<?php echo $options['gallery_full_size_width'];?>"/>&nbsp;<?php echo __('pixels', odWpPhotogalleryPlugin::$textdomain);?></td>
					</tr>
					<tr>
						<th scope="row"><label for="option-gallery_full_size_height"><?php echo __('Set max. height for larger images:', odWpPhotogalleryPlugin::$textdomain);?></label></th>
						<td><input type="text" name="option-gallery_full_size_height" value="<?php echo $options['gallery_full_size_height'];?>"/>&nbsp;<?php echo __('pixels', odWpPhotogalleryPlugin::$textdomain);?></td>
					</tr>
					<tr>
						<th scope="row" style="vertical-align: middle;"><label for="option-gallery_supported_img_types"><?php echo __('Supported images formats:', odWpPhotogalleryPlugin::$textdomain);?></label></th>
						<td>
							<input type="text" name="option-gallery_supported_img_types" value="<?php echo $options['gallery_supported_img_types'];?>" style="min-width: 300px;"/><br/>
							<?php echo __('Enter lowercased and comma-separated list of supported image formats. Defaultly is set <code>jpg,png</code> but available is also <code>gif</code>.', odWpPhotogalleryPlugin::$textdomain);?>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="option-gallery_max_upload_count"><?php echo __('Set maximum count of images uploaded per once:', odWpPhotogalleryPlugin::$textdomain);?></label></th>
						<td><input type="text" name="option-gallery_max_upload_count" value="<?php echo $options['gallery_max_upload_count'];?>"/>&nbsp;<?php echo __('images', odWpPhotogalleryPlugin::$textdomain);?></td>
					</tr>
				</table>
				<h3>Gallery template</h3>
				<table class="widefat post fixed" cellpadding="1" cellspacing="1" style="100%;">
          <tbody>
            <tr>
              <th scope="row" style="vertical-align: middle;"><label for="option-gallery_template_cont_prefix"><?php echo __('Gallery container prefix template:', odWpPhotogalleryPlugin::$textdomain);?></label></th>
              <td><textarea name="option-gallery_template_cont_prefix" style="width: 100%;"><?php echo str_replace('\\"', '"', str_replace('\\\'', '\'', $options['gallery_template_cont_prefix']));?></textarea></td>
            </tr>
            <tr>
              <th scope="row" style="vertical-align: middle;"><label for="option-gallery_template_row_prefix"><?php echo __('Single gallery row prefix template:', odWpPhotogalleryPlugin::$textdomain);?></label></th>
              <td><textarea name="option-gallery_template_row_prefix" style="width: 100%;"><?php echo str_replace('\\"', '"', str_replace('\\\'', '\'', $options['gallery_template_row_prefix']));?></textarea></td>
            </tr>
            <tr>
              <th scope="row" style="vertical-align: middle;"><label for="option-gallery_template_photo_prefix"><?php echo __('Photo container prefix template:', odWpPhotogalleryPlugin::$textdomain);?></label></th>
              <td><textarea name="option-gallery_template_photo_prefix" style="width: 100%;"><?php echo str_replace('\\"', '"', str_replace('\\\'', '\'', $options['gallery_template_photo_prefix']));?></textarea></td>
            </tr>
            <tr>
              <th scope="row" style="vertical-align: middle;"><label for="option-gallery_template_photo_body"><?php echo __('Photo body template:', odWpPhotogalleryPlugin::$textdomain);?></label></th>
              <td><textarea name="option-gallery_template_photo_body" style="width: 100%;"><?php echo str_replace('\\"', '"', str_replace('\\\'', '\'', $options['gallery_template_photo_body']));?></textarea></td>
            </tr>
            <tr>
              <th scope="row" style="vertical-align: middle;"><label for="option-gallery_template_photo_suffix"><?php echo __('Photo container suffix template:', odWpPhotogalleryPlugin::$textdomain);?></label></th>
              <td><textarea name="option-gallery_template_photo_suffix" style="width: 100%;"><?php echo str_replace('\\"', '"', str_replace('\\\'', '\'', $options['gallery_template_photo_suffix']));?></textarea></td>
            </tr>
            <tr>
              <th scope="row" style="vertical-align: middle;"><label for="option-gallery_template_row_suffix"><?php echo __('Single gallery row suffix template:', odWpPhotogalleryPlugin::$textdomain);?></label></th>
              <td><textarea name="option-gallery_template_row_suffix" style="width: 100%;"><?php echo str_replace('\\"', '"', str_replace('\\\'', '\'', $options['gallery_template_row_suffix']));?></textarea></td>
            </tr>
            <tr>
              <th scope="row" style="vertical-align: middle;"><label for="option-gallery_template_cont_suffix"><?php echo __('Gallery container suffix template:', odWpPhotogalleryPlugin::$textdomain);?></label></th>
              <td><textarea name="option-gallery_template_cont_suffix" style="width: 100%;"><?php echo str_replace('\\"', '"', str_replace('\\\'', '\'', $options['gallery_template_cont_suffix']));?></textarea></td>
            </tr>
          </tbody>
        </table>
				<input type="submit" value=" <?php echo __('Save', odWpPhotogalleryPlugin::$textdomain);?> " name="settings_save" class="button-primary action" />
			</div>
		</form>
<?php
		self::print_admin_page_footer();
	}
	
	/**
	 * Prints form for edit gallery 
	 * 
	 * @param array $gallery
	 * @return void
	 */
	function print_edit_gallery_form($gallery)
	{
		$photo_count = $this->get_files_count($gallery['folder']);
?>
		<h3><?php echo __('Edit gallery #', odWpPhotogalleryPlugin::$textdomain);?><code><?php echo $gallery['ID'];?></code></h3>
		<form action="<?php echo get_bloginfo('home');?>/wp-admin/admin.php?page=od-photogallery-plugin" method="post" enctype="multipart/form-data">
			<input type="hidden" name="gallery_ID" value="<?php echo $gallery['ID'];?>"/>
			<table cellspacing="1" cellpadding="1" style="width: 80%;">
				<tr>
					<th scope="row"><label for="title"><?php echo __('Gallery title:', odWpPhotogalleryPlugin::$textdomain);?></label></th>
					<td><input type="text" name="title" value="<?php echo $gallery['title'];?>" style="width: 100%;"/></td>
				</tr>
				<tr>
					<th scope="row" style="vertical-align: top;"><label for="description"><?php echo __('Gallery description:', odWpPhotogalleryPlugin::$textdomain);?></label></th>
					<td><textarea name="description" style="width: 100%;"><?php echo $gallery['description'];?></textarea></td>
				</tr>
				<tr>
					<th scope="row"><label for="photos_count"><?php echo __('Images count:', odWpPhotogalleryPlugin::$textdomain);?></label></th>
					<td>
						<input type="text" name="photos_count" value="<?php echo $photo_count;?>" disabled="disabled"/>
						<input type="submit" name="editgallery-photos" value="<?php echo __('Edit images', odWpPhotogalleryPlugin::$textdomain);?>" class="button-secondary action"/>&nbsp;
						<input type="submit" name="addgallery-photos" value="<?php echo __('Add images', odWpPhotogalleryPlugin::$textdomain);?>" class="button-primary action"/>
					</td>
				</tr>
			</table>
			<p>
				<input type="submit" name="savegallery" value="<?php echo __('Save', odWpPhotogalleryPlugin::$textdomain);?>" class="button-primary action" />
			</p>
		</form>
<?php
	}
	
	/**
	 * Prints form for edit gallery images.
	 * Array with gallery should have this format:
	 * Given array should have this format:
	 *   array('ID'          => 1, 
	 *         'title'       => 'Some gallery\'s title', 
	 *         'description' => 'Some gallery\'s description.', 
	 *         'folder'      => 'some-gallery', 
	 *         'deleted'     => false)
	 * 
	 * Array with images should have this format:
	 *   array(0 => array('ID'          => 1, 
	 *                    'title'       => 'Some title', 
	 *                    'description' => 'Some description.', 
	 *                    'file'        => 'image.jpg', 
	 *                    'full_url'    => 'http://some.url/image.jpg', 
	 *                    'thumb_url'   => 'http://some.url/image_.jpg', 
	 *                    'order'       => 0, 
	 *                    'display'     => true,
	 *                    'img_type'    => 'jpg'))
	 * 
	 * @param array $form_url
	 * @param array $gallery
	 * @param array $images
	 * @param array $title Optional. Title for the form.
	 * @param array $description Optional. Adds additional description above the form.
	 * @return void
	 */
	function print_edit_gallery_images_form($form_url, $gallery, $images, $title = '', $description = '')
	{
?>
		<?php if(!empty($title)):?>
		<h3><?php echo $title;?></h3>
		<?php endif;?>
		<?php if(!empty($description)):?>
		<p><?php echo $description;?></p>
		<?php endif;?>
		<form action="<?php echo $form_url;?>" method="post" enctype="multipart/form-data">
			<input type="hidden" name="gallery_ID" value="<?php echo $gallery['ID'];?>"/>
			<table class="widefat post fixed" cellspacing="0">
				<thead>
					<tr>
						<th scope="col" style="width:5%; text-align:center;"><?php echo __('ID', odWpPhotogalleryPlugin::$textdomain);?></th>
						<th scope="col" style="width:15%;"><?php echo __('Image', odWpPhotogalleryPlugin::$textdomain);?></th>
						<th scope="col" style="width:30%;"><?php echo __('Title', odWpPhotogalleryPlugin::$textdomain);?></th>
						<th scope="col" style="width:40%;"><?php echo __('Description', odWpPhotogalleryPlugin::$textdomain);?></th>
						<th scope="col" style="width:10%;"><?php echo __('Order', odWpPhotogalleryPlugin::$textdomain);?></th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<th scope="col" style="width:5%; text-align:center;"><?php echo __('Check all', odWpPhotogalleryPlugin::$textdomain);?></th>
						<th scope="col" style="width:15%;"><?php echo __('Image', odWpPhotogalleryPlugin::$textdomain);?></th>
						<th scope="col" style="width:30%;"><?php echo __('Title', odWpPhotogalleryPlugin::$textdomain);?></th>
						<th scope="col" style="width:40%;"><?php echo __('Description', odWpPhotogalleryPlugin::$textdomain);?></th>
						<th scope="col" style="width:10%;"><?php echo __('Order', odWpPhotogalleryPlugin::$textdomain);?></th>
					</tr>
				</tfoot>
				<tbody id="the-list" class="list:od-photogallery">
<?php
		$i = 0;
		foreach($images as $image) {
?>
					<tr>
						<td style="vertical-align: middle; text-align: center;">
							<input type="hidden" name="image_ID[<?php echo $i;?>]" value="<?php echo ((is_null($image['ID'])) ? '0' : $image['ID']);?>"/>
							<input type="hidden" name="image[<?php echo $i;?>]" value="<?php echo $image['file'];?>"/>
							<?php echo ((is_null($image['ID'])) ? '0' : $image['ID']);?>
						</td>
						<td><img src="<?php echo $image['thumb_url'];?>"/></td>
						<td><input type="text" name="title[<?php echo $i;?>]" value="<?php echo (isset($image['title'])) ? $image['title'] : '';?>" style="width: 100%;"/></td>
						<td><textarea name="description[<?php echo $i;?>]" style="width: 100%;"><?php echo (isset($image['description'])) ? $image['description'] : '';?></textarea></td>
						<td><input type="text" name="order[<?php echo $i;?>]" value="<?php echo (isset($image['order'])) ? $image['order'] : $i;?>" style="width: 100%;"/></td>
					</tr>
<?php
			$i++;
		}
?>
				</tbody>
			</table>
			<p>
				<input type="submit" name="savegalleryphotos" value="<?php echo __('Save', odWpPhotogalleryPlugin::$textdomain);?>" class="button-primary action" />
			</p>
		</form>
<?php
	}
	
	/** 
	 * Renders upload form (called from add_gallery()).
	 * 
	 * @param string $form_url
	 * @param string $dirname
	 * @param integer $gallery_id Optional. ID of gallery to which images belongs. 
	 * @param boolean $show_title Optional. Defaultly TRUE.
	 * @param boolean $show_uploadmore Optional. Defaultly TRUE.
	 * @return void
	 */
	function render_upload_form($form_url, 
	                            $dirname, 
								$gallery_id = null, 
	                            $show_title = true, 
								$show_uploadmore = true)
	{
		$options = odWpPhotogalleryPlugin::get_options();
		
		if(!is_null($gallery_id)):?>
		<h3><?php echo __('Edit gallery #', odWpPhotogalleryPlugin::$textdomain);?><code><?php echo $gallery_id;?></code></h3>
		<?php endif;?>
		<?php if($show_title === true):?>
		<h4><?php echo __('Upload new images', odWpPhotogalleryPlugin::$textdomain);?></h4>
		<?php endif;?>
		<form action="<?php echo $form_url;?>" method="post" enctype="multipart/form-data">
			<input type="hidden" name="dirname" value="<?php echo $dirname;?>"/>
			<?php if(!is_null($gallery_id)):?>
			<input type="hidden" name="gallery_id" value="<?php echo ((is_null($gallery_id)) ? '0' : $gallery_id);?>"/>
			<?php endif;?>
			<!-- TODO Print maximum size of uploaded files (and aproximately size for one)! -->
			<table cellpadding="1" cellspacing="1" style="width:80%;">
				<tr>
					<th scope="row" style="min-width: 190px; vertical-align: top;">
						<label for="image"><?php echo __('Upload images <code>*</code>:', odWpPhotogalleryPlugin::$textdomain);?></label>
					</th>
					<td>
						<?php for($i=0; $i<(int) $options['gallery_max_upload_count']; $i++):?>
						<input type="file" name="image[]" value="" style="width: 100%;"/><br/>
						<?php endfor;?>
					</td>
				</tr>
				<?php if($show_uploadmore === true):?>
				<tr>
					<td colspan="2">
						<label for="moreimages"><?php echo __('Add more images', odWpPhotogalleryPlugin::$textdomain);?></label>
						<input type="checkbox" name="moreimages"/>
					</td>
				</tr>
				<?php endif;?>
			</table>
			<p><code>*</code> &ndash; <?php echo sprintf(__('You can upload only images of format specified in plugin\'s options (currently: <code>%s</code>)!', odWpPhotogalleryPlugin::$textdomain), $options['gallery_supported_img_types']);?></p>
			<p>
				<input type="submit" name="uploadgalleryfiles" value="<?php echo __('Continue', odWpPhotogalleryPlugin::$textdomain);?>" class="button-primary action" />
			</p>
		</form>
<?php
	}
	
	/**
	 * Prints form for creating directory in the gallery main dir
	 * 
	 * @param string $form_url 
	 * @return void
	 */
	function print_create_gallery_dir_form($form_url)
	{
?>
		<h3><?php echo __('Photogallery - Create new folder', odWpPhotogalleryPlugin::$textdomain);?></h3>
		<form id="photogallery-create_new_folder-form" action="<?php echo $form_url;?>" method="post" enctype="multipart/form-data">
			<table cellpadding="1" cellspacing="1" style="width:80%;">
				<tr>
					<th scope="row" style="min-width: 190px; vertical-align: top;">
						<label for="dirname"><?php echo __('Directory name', odWpPhotogalleryPlugin::$textdomain);?>:</label>
					</th>
					<td>
						<input type="text" id="photogallery-dirname" name="dirname" value="" style="width: 100%;"/><br/>
						<?php echo __('Enter name for the folder of a new gallery. Directory name can not ' .
									  'contain spaces and national or special characters (good: <code>' . 
									  'test-gallery-title</code>; bad: <code>Test gallery title</code> or ' . 
									  '<code>Název testovací galerie</code> etc.)',
									  odwpPhotogalleryPlugin::$textdomain);?>
					</td>
					<!-- TODO (0.6) Vyrenderovat zde i adresare existujicich galerii - pro pripad ze by 
					   -      uzivatel chtel pridavat do stavajici ... pouzit novej selectbox ...
					   -      (no proste snazsi pridavani)
					   -->
				</tr>
				<tr>
					<th scope="row" style="vertical-align: middle;">
						<label for="description"><?php echo __('Gallery description:', odWpPhotogalleryPlugin::$textdomain);?></label>
					</th>
					<td><textarea name="description" style="width: 100%;"><?php echo $gallery['description'];?></textarea></td>
				</tr>
			</table>
			<p id="photogallery-form_output_div"/>
			<p>
				<input type="submit" value="<?php echo __('Continue', odWpPhotogalleryPlugin::$textdomain);?>" name="creategallerydir" class="button-primary action" />
			</p>
		</form>
<?php
	}
	
	/**
	 * Prints form for creating gallery from unused folder
	 * 
	 * @param string $form_url 
	 * @return void
	 */
	function print_create_gallery_from_dir_form($form_url)
	{
?>
		<p><?php printf(__('For the new gallery you\'ve select directory <code>%1$s</code>. ' . 
						   'This folder contains <code style="color:green;">%2$s</code> images.<br/>' .
						   'If you really want to create gallery, please, also fill it\'s name ' . 
						   'and submit this form.', odWpPhotogalleryPlugin::$textdomain),  
						   $_GET['dn'], $_GET['pc']);?></p>
		<form action="<?php echo $form_url;?>" method="post" enctype="multipart/form-data">
			<input type="hidden" name="dn" value="<?php echo $_GET['dn'];?>"/>
			<table cellspacing="1" cellpadding="1" style="width: 80%;">
				<tr>
					<th scope="row"><label for="title"><?php echo __('Gallery title:', odWpPhotogalleryPlugin::$textdomain);?></label></th>
					<td><input type="text" name="title" value="" style="width: 100%;"/></td>
				</tr>
			</table>
			<p><?php echo __('Now you will continue with selecting and describing single included images.', odWpPhotogalleryPlugin::$textdomain);?></p>
			<p>
				<input type="submit" name="creategallery1" value="<?php echo __('Continue', odWpPhotogalleryPlugin::$textdomain);?>" class="button-primary action" />
			</p>
		</form>
<?php
	}
	
}
