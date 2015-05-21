<?php
/*
Plugin Name: AutoNav Widget
Version: 1.5.10
Plugin URI: http://www.wlindley.com/website/autonav/
Description: Adds a sidebar widget to display attachments or navigational tables/lists.
Author: William Lindley
Author URI: http://www.wlindley.com/
License: GPL2
*/

/*  Copyright 2013-2015  William Lindley (email : wlindley -at- wlindley -dot- com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
  */

if (!class_exists('WL_Config_Form')) {
  include 'wl-config-form.php';
}

if (!function_exists('get_filter')) {
  function get_filter($tag) {
# Returns the current state of the given WordPress filter.
    global $wp_filter;
    return $wp_filter[$tag];
  }

  function set_filter($tag, $saved) {
# Sets the given WordPress filter to a state saved by get_filter.
    remove_all_filters($tag);
    foreach ($saved as $priority => $func_list) {
      foreach ($func_list as $func_name => $func_args) {
	add_filter($tag,$func_args['function'], $priority, $func_args['accepted_args']);
      }
    }
  }
}

class AutoNavWidget extends WP_Widget
{
  /**
   * Declares the HierPageWidget class.
   *
   */
  function AutonavWidget(){
    $widget_ops = array('classname' => 'widget_autonav', 'description' => __( "AutoNav Navigation and Attachment Widget") );
    $control_ops = array('width' => 300, 'height' => 300);
    $this->WP_Widget('autonav', __('AutoNav'), $widget_ops, $control_ops);
  }

  # Specific to WP_Widget -derived
  function form_item($options, $option_name, $html_tags) {

    print '<p style="text-align:right;">';
    if (strlen($html_tags['title'])) {
      foreach (array('style','class') as $p) {
        if (!empty($html_tags["title.$p"])) {
	  $extra_tags .= " $p=\"" . $html_tags["title.$p"] . '"';
        }
      }
      print '<label for="'.$html_tags['id'].'"' . $extra_tags .
        '>' . __($html_tags['title']) . '</label>';
      unset($html_tags['title']);
    }
    return $html_tags;
  }

  function form_item_close($options, $option_name, $html_tags) {
    print '</p>';
    return $html_tags;
  }

  function form_html($instance) {
    $option_menu = $this->known_params(1);

    $conf_menu = new WL_Config_Form;
    $conf_menu -> set_callbacks(array('item_pre' => array($this, 'form_item'),
				      'item_post' => array($this, 'form_item_close')));

    foreach ($option_menu as $option_name => $html_tags) {
      if ($html_tags['type'] != 'checkbox') {
	$html_tags['value'] = htmlspecialchars($instance[$option_name]);
      }

    $fieldname = strlen($html_tags['name']) ? $html_tags['name'] : $option_name;
    $html_tags['name'] = $this->get_field_name($fieldname);
    $html_tags['id'] = $this->get_field_id($option_name);
    $html_tags['style'] = 'width:150px;';
    $html_tags['class'] = 'srcs_widget_form_element';
    if (!array_key_exists('type',$html_tags)) {
      $html_tags['type'] = 'text';
    }
      $conf_menu->form_item($instance, $option_name, $html_tags);
    }
  }

  /**
   * Displays the Widget
   *
   */
  function widget($args, $instance){

    $title = apply_filters('widget_title', empty($instance['title']) ? '' : $instance['title']);
    $known_params = $this->known_params(0);
    foreach ($known_params as $param) {
      if (strlen($instance[$param])) {
	$page_options[$param] = $instance[$param];
      }
    }
    print $args['before_widget'];
    if ( $title )
      print "{$args['before_title']}{$title}{$args['after_title']}";

    #    $was_filter = get_filter('autonav_create_list_item');
    #    remove_all_filters('autonav_create_list_item');

    /* Set filters according to user specifications.  ~~~~ future */

    $autonav_args = array();
    $display_options = array($page_options['display']);
    foreach ($page_options as $option => $option_value) {
      if (substr($option, 0, 1) == '_') {
	$display_options[] = substr($option, 1);
      } else {
	$autonav_args[$option] = $option_value;
      }
    }
    $autonav_args['display'] = join(',', $display_options);
    print autonav_wl_shortcode($autonav_args);
    print $args['after_widget'];

    /* Restore filter state */
    #    set_filter('autonav_create_list_item', $was_filter );

  }

  function known_params ($options = 0) {
    $option_menu = array('title' => array('title' => 'Title:'),
			 'display' => array('title' => 'What to display?',
					    'type' => 'radio',
					    'choices' => array('list' => 'List: child pages of current page.<br>',
							       'images' => 'Table: child pages of current page<br>',
							       'attached' => 'Table: images attached to current post/page<br>',
							       'attachments' => 'Table: from the "Attachments" plugin<br>',
							       'posts,list' => 'List: Pages/Posts per <em>postid</em><br>',
							       'posts' => 'Table: Pages/Posts per <em>postid</em>')),
			 'postid' => array('title' => 'Post ID (see Readme):'),
			 'orderby' => array('title' => 'Sort field:',
					    'text' => '<br>Comma-separated list: <em>post_title, menu_order, post_date, post_modified, ID, post_author, post_name, postmash, meta:</em>custom_field_name'),
			 'order' => array('title' => 'Sort direction:',
					  'type' => 'radio',
					  'choices' => array( 'asc' => 'Ascending<br>', 'desc' => 'Descending<br>',
								   'rand' => 'Random')),
			 'columns' => array('title' => 'Table Columns:'),
			 'combine' => array('type'=>'radio',
					 'title' => 'Combine Tables?',
					    'choices' => array(0 => 'All', 1 => 'None', 2 => 'Full rows only')),
			 'size' => array('title' => 'Thumbnail Size:'),
			 'crop' => array('type'=>'radio',
					 'title' => 'Thumbnail cropping',
					 'choices' => array(0 => 'Fit',
							    1 => 'Crop from center.',
							    2 => 'Crop from upper-left.', 
							    3 => 'Crop from top middle.')),
			 'caption' => array('title' => 'Table Caption:'),
			 'pics_only' => array('title' => 'Only pages with pictures:',
					   'type' => 'checkbox', 'checked' => 1, 'value' => '1'),
			 '_thumb' => array('title' =>  'Force page/post thumbnails',
					  'type' => 'checkbox', 'checked' => 1, 'value' => '1'),
			 '_nothumb' => array('title' =>  'Suppress page/post thumbnails',
					    'type' => 'checkbox', 'checked' => 1, 'value' => '1'),
			 '_title' => array('title' =>  'Force page/post titles',
					  'type' => 'checkbox', 'checked' => 1, 'value' => '1'),
			 '_notitle' => array('title' =>  'Suppress page/post titles',
					    'type' => 'checkbox', 'checked' => 1, 'value' => '1'),
			 '_excerpt' => array('title' =>  'Display "manual excerpt" (see FAQ)',
					    'type' => 'checkbox', 'checked' => 1, 'value' => '1'),
			 '_noexcerpt' => array('title' =>  'Suppress manual excerpt',
					      'type' => 'checkbox', 'checked' => 1, 'value' => '1'),
			 '_plain' => array('title' => 'Use <em>div</em> instead of list',
					   'type' => 'checkbox', 'checked' => 1, 'value' => '1'),
			 '_siblings' => array('title' =>  'Display siblings of current page',
					     'type' => 'checkbox', 'checked' => 1, 'value' => '1'),
			 '_family' => array('title' =>  'Display page\'s family (children, grandchildren...)',
					   'type' => 'checkbox', 'checked' => 1, 'value' => '1'),
			 '_self' => array('title' =>  'Include the current page<br>(with siblings, or family)',
					 'type' => 'checkbox', 'checked' => 1, 'value' => '1'),
			 '_image' => array('title' =>  'Posts link to full-size of thumbnail',
					  'type' => 'checkbox', 'checked' => 1, 'value' => '1'),
			 '_page' => array('title' =>  'Attachments link to attachment page',
					 'type' => 'checkbox', 'checked' => 1, 'value' => '1'),
			 '_nolink' => array('title' =>  'Disable links entirely',
					   'type' => 'checkbox', 'checked' => 1, 'value' => '1'),
			 'exclude' => array('title' => 'Exclude pages:',
					    'desc' => 'List of page IDs to exclude'),
			 'imgrel' => array('title' => 'Image relation tag:'),
			 'meta_key' => array('title' => 'Meta Key:'),
			 'meta_value' => array('title' => 'Meta-key Value:',
					     'desc' => 'for selecting pages by custom fields'),
			 'authors' => array('title' => 'Authors:'),
			 'post_status' => array('title' => 'Post status:',
						'desc' => '(default: publish)'),
                         'target' => array('title' => 'Target frame to open link:'),
			 );
    return ($options ? $option_menu : array_keys($option_menu));
  }

  /**
   * Saves the widget's settings.
   *
   */
  function update($new_instance, $old_instance){
    $instance = $old_instance;
    $known_params = $this->known_params();
    unset($instance['menu_order']);
    foreach ($known_params as $param) {
      $instance[$param] = strip_tags(stripslashes($new_instance[$param]));
    }
    $instance['sort_order'] = strtolower($instance['sort_order']);
    return $instance;
  }

  /**
   * Creates the edit form for the widget.
   *
   */
  function form($instance){
    $instance = wp_parse_args( (array) $instance, array('title'=>'') );
    if (empty($instance['orderby'])) {
      $instance['orderby'] = 'menu_order';
    }

    $this->form_html($instance);
  }

}// END class

/**
 * Register this widget.
 *
 * Calls 'widgets_init' action after the widget has been registered.
 */
function AutoNavWidgetInit() {
  register_widget('AutoNavWidget');
}

add_action('widgets_init', 'AutoNavWidgetInit');

