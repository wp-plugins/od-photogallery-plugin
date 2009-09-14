<?php
/**
 * Widget with panel of three images of the latest or selected gallery.
 * It's not aimed for sidebars but for home page (it's horizonal).
 * 
 * @author Ondrej Donek, <ondrejd@gmail.com>
 * @category odWpPhotogalleryPlugin
 * @since 0.5.1
 */
class odWpPhotogallerypanelWidget extends WP_Widget
{
  
  function odWpPhotogallerypanelWidget() 
  {
	// Widget options
	$widget_ops = array('classname' 	=> 'odWpPhotogallerypanelWidget', 
	                    'description' 	=> __('Widget with pane with rendered ' . 
						                      'images from the newest photogallery.', 
											  odWpPhotogalleryPlugin::$textdomain));
	// Widget control options
	$control_ops = array('width' 		=> 200, 
	                     'height' 		=> 350, 
						 'id_base' 		=> 'photogallerypanel-widget');
	// Create the widget
	$this->WP_Widget('photogallerypanel-widget', 
	                 __('Latest photogallery', odWpPhotogalleryPlugin::$textdomain), 
					 $widget_ops, 
					 $control_ops);
  }
  
  function widget($args, $instance) 
  {
	extract($args);

	// User-selected settings
	$title = apply_filters('widget_title', $instance['title']);
	$type = ($instance['type'] == '-----') ? 'latest' : $instance['type'];
	$maxcount = (int) $instance['limit'];
	$lightbox = (isset($instance['lightbox'])) ? $instance['lightbox'] : false;
	
	// Before widget (defined by theme)
	echo $before_widget;

	// Title of widget (before and after defined by theme)
	if($title) {
	  echo $before_title . $title . $after_title;
	}
	
	// Print images from the apropriate gallery
	global $od_photogallery_plugin;
	$gallery = null;
	
	if($type == 'latest') {
		// Shows {$maxcount} images from the latest photogallery
		$galleries = odWpPhotogalleryPluginModel::get_galleries();
		// Select nonempty gallery
		$gallery = null;
		foreach($galleries as $_gallery) {
			if($_gallery['image_count'] != '0' && is_null($gallery)) {
				$gallery = $_gallery;
				break;
			}
		}
		$gallery = $galleries[0];
	} 
	else if($type == 'latest2') {
		// TODO 'latest2' display only one image from {$maxcount} newest photogalleries
		// ...
	} else {
		$gallery_id = intval(str_replace('gallery_', '', $type));
		$gallery = odWpPhotogalleryPluginModel::get_gallery($gallery_id);
	}
	
	if(is_null($gallery)) {
?>
	<p><strong><?php echo __('We are sorry but gallery with given ID wasn\'t found.', odWpPhotogalleryPlugin::$textdomain);?></p>
<?php
	} else {
		$images = odWpPhotogalleryPluginModel::get_gallery_images((int) $gallery['ID'], $gallery['folder'], $maxcount);
?>
	<h3><?php echo mysql2date('j F Y', $gallery['created'], true);?>, <?php echo $gallery['title'];?></h3>
	<div class="fotky">
<?php foreach($images as $image):?>
		<div class="fotka">
			<a href="<?php echo $od_photogallery_plugin->get_public_url_for_gallery($gallery['ID']);?>">
				<img src="<?php echo $image['thumb_url'];?>" alt="<?php echo $image['title'];?>" title="<?php echo $image['title'];?>" />
			</a>
			<p><?php echo $image['title'];?></p>
		</div>
<?php endforeach;?>
	</div>
<?php
	}

	// After widget (defined by theme)
	echo $after_widget;
  }
  
  function update($new_instance, $old_instance) 
  {
	$instance = $old_instance;

	// Strip tags (if needed) and update the widget settings
	$instance['title'] = strip_tags($new_instance['title']);
	$instance['type'] = ($new_instance['type'] == '-----') 
			? 'latest' 
			: $new_instance['type'];
	$instance['limit'] = intval($new_instance['limit']); // XXX Check if is given really number!!!
	$instance['lightbox'] = $new_instance['lightbox'];

	return $instance;
  }
  
  function form($instance) 
  {
	global $od_photogallery_plugin;
	$options = $od_photogallery_plugin->get_options();
	
	// Set up some default widget settings.
	$defaults = array('title' 		=> __('Latest photogallery', odWpPhotogalleryPlugin::$textdomain), 
					  'limit' 		=> 4, 
					  'lightbox' 	=> true,
					  'type'        => 'latest');
	$instance = wp_parse_args((array) $instance, $defaults); 
?>
	<p>
		<label for="<?php echo $this->get_field_id('title');?>"><?php echo __('Widget title', odWpPhotogalleryPlugin::$textdomain);?>:</label>
		<input id="<?php echo $this->get_field_id('title');?>" name="<?php echo $this->get_field_name('title');?>" value="<?php echo $instance['title'];?>" style="width: 90%;"/>
	</p>
	<p>
		<label for="<?php echo $this->get_field_id('type');?>"><?php echo __('Select gallery to show', odWpPhotogalleryPlugin::$textdomain);?>:</label>
		<select id="<?php echo $this->get_field_id('type');?>" name="<?php echo $this->get_field_name('type');?>" value="<?php echo $instance['type'];?>" style="width: 90%; max-width:230px!important;">
			<option value="latest"<?php if($instance['type']=='latest'){ echo ' selected="selected"'; }?>><?php echo __('The newest photogallery', odWpPhotogalleryPlugin::$textdomain);?></option>
			<option disabled="disabled" value="latest2"<?php if($instance['type']=='latest2'){ echo ' selected="selected"'; }?>><?php echo __('The newest photogalleries', odWpPhotogalleryPlugin::$textdomain);?></option>
			<option value="------" disabled="disabled">-----</option>
			<?php foreach(($galleries = odWpPhotogalleryPluginModel::get_galleries()) as $gallery):?>
			<?php if($gallery['images_count'] != '0'):?>
			<option value="gallery_<?php echo $gallery['ID'];?>"<?php if($instance['type']=='gallery_'.$gallery['ID']){ echo ' selected="selected"'; }?>><?php echo $gallery['title'];?></option>
			<?php endif;?>
			<?php endforeach;?>
		</select>
	<p>
		<label for="<?php echo $this->get_field_id('limit');?>"><?php echo __('Max. items count', odWpPhotogalleryPlugin::$textdomain);?>:</label>
		<input id="<?php echo $this->get_field_id('limit');?>" name="<?php echo $this->get_field_name('limit');?>" value="<?php echo $instance['limit'];?>"/>
	</p>
	<p>
		<input class="checkbox" type="checkbox" <?php checked($instance['lightbox'], true);?> id="<?php echo $this->get_field_id('lightbox');?>" name="<?php echo $this->get_field_name('lightbox');?>"/>
		<label for="<?php echo $this->get_field_id('lightbox');?>"><?php echo __('Use <em>Lightbox</em> effect?', odWpPhotogalleryPlugin::$textdomain);?></label>
	</p>
<?php
  }
  
}

// ==========================================================================

/**
 * Widget with list of galleries (primarily targets for sidebars).
 * 
 * @author Ondrej Donek, <ondrejd@gmail.com>
 * @category odWpPhotogalleryPlugin
 * @since 0.5
 */
class odWpPhotogallerylist1Widget extends WP_Widget
{
  
  function odWpPhotogallerylist1Widget() 
  {
	// Widget options
	$widget_ops = array('classname' 	=> 'odWpPhotogallerylist1Widget', 
	                    'description' 	=> __('Widget with list of the newest photogalleries.', 
						                      odWpPhotogalleryPlugin::$textdomain));
	// Widget control options
	$control_ops = array('width' 		=> 200, 
	                     'height' 		=> 350, 
						 'id_base' 		=> 'photogallerylist1-widget');
	// Create the widget
	$this->WP_Widget('photogallerylist1-widget', 
	                 __('Latest photogalleries', odWpPhotogalleryPlugin::$textdomain), 
					 $widget_ops, 
					 $control_ops);
  }
  
  function widget($args, $instance) 
  {
	extract($args);

	// User-selected settings
	$title = apply_filters('widget_title', $instance['title']);
	$maxcount = (int) $instance['limit'];
	$morelink = (isset($instance['morelink'])) ? $instance['morelink'] : false;

	// Before widget (defined by theme)
	echo $before_widget;

	// Title of widget (before and after defined by theme)
	if($title) {
	  echo $before_title . $title . $after_title;
	}
	
	// Print list of photogalleries
	global $od_photogallery_plugin;
	
	foreach(($galleries = odWpPhotogalleryPluginModel::get_galleries()) as $gallery) {
		// TODO Add option if user want to display photogalleries with no images
		if($gallery['images_count'] != '0') {
?>
		<p><a value="<?php echo $od_photogallery_plugin->get_public_url_for_gallery($gallery['ID']);?>"><?php echo mysql2date('j F Y', $gallery['created'], true);?>, <?php echo $gallery['title'];?></a></p>
<?php
		}
	}
	
	if($morelink === true) {
		echo '<h4><a href="' . $od_photogalery_plugin->get_public_url_for_gallery() . '">' . __('More', odWpPhotogalleryPlugin::$textdomain) . '</a></h4>';
	}
	
	// After widget (defined by theme)
	echo $after_widget;
  }
  
  function update($new_instance, $old_instance) 
  {
	$instance = $old_instance;

	// Strip tags (if needed) and update the widget settings
	$instance['title'] = strip_tags($new_instance['title']);
	$instance['limit'] = intval($new_instance['limit']); // XXX Check if is given really number!!!
	$instance['morelink'] = $new_instance['morelink'];

	return $instance;
  }
  
  function form($instance) 
  {
	global $od_photogallery_plugin;
	$options = $od_photogallery_plugin->get_options();
	
	// Set up some default widget settings.
	$defaults = array('title' 		=> __('Latest photogalleries', odWpPhotogalleryPlugin::$textdomain), 
					  'limit' 		=> 7, 
					  'morelink' 	=> true);
	$instance = wp_parse_args((array) $instance, $defaults); 
?>
	<p>
	  <label for="<?php echo $this->get_field_id('title');?>"><?php echo __('Widget title', odWpPhotogalleryPlugin::$textdomain);?>:</label>
	  <input id="<?php echo $this->get_field_id('title');?>" name="<?php echo $this->get_field_name('title');?>" value="<?php echo $instance['title'];?>" style="width:100%;"/>
	</p>
	<p>
	  <label for="<?php echo $this->get_field_id('limit');?>"><?php echo __('Max. items count', odWpPhotogalleryPlugin::$textdomain);?>:</label>
	  <input id="<?php echo $this->get_field_id('limit');?>" name="<?php echo $this->get_field_name('limit');?>" value="<?php echo $instance['limit'];?>"/>
	</p>
	<p>
	  <input class="checkbox" type="checkbox" <?php checked($instance['morelink'], true);?> id="<?php echo $this->get_field_id('morelink');?>" name="<?php echo $this->get_field_name('morelink');?>"/>
	  <label for="<?php echo $this->get_field_id('morelink');?>"><?php echo __('Display <em>More...</em> link?', odWpPhotogalleryPlugin::$textdomain);?></label>
	</p>
<?php
  }
  
}
