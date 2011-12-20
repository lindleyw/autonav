<?php
/*
Plugin Name: Autonav Category Thumbnail Integration
Description: Permits using Category and Taxonomy images from the taxonomy-images plugin, with AutoNav
Author: William Lindley
Author URI: http://www.saltriversystems.com/
*/

/*  Copyright 2011 William Lindley (email : bill -at- saltriversystems -dot- com)

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




  /*  Far from functional.  This uses the data from the WordPress
   plugin: taxonomy-images to set a default image for categories and
   other taxonomies.  We would like to pass a taxonomy name and a term
   within that taxonomy, and retrieve the selected image.  However,
   the plugin does not currently give us a way to use its objects or
   call its functions to retrieve that.
   */

function autonav_thumb_taxonomy_image ($pic_info, $attr, $post) {
  if (empty($pic_info)) {
    list ($display_type, $display_term) = explode(':',$attr['display'] . ':');

    // Hack to directly read the image associations from the taxonomy-images plugin.
    $assoc_images = array();
    if (function_exists('taxonomy_image_plugin_sanitize_associations')) {
      $assoc_images = taxonomy_image_plugin_sanitize_associations( get_option( 'taxonomy_image_plugin' ) );

      // Priority of thumbnails: Selected; Tags; Catgories.
      $taxonomy_search = array_unique(array($attr['taxonomy'], 'post_tag', 'category'));
      foreach ($taxonomy_search as $tax) {
	$post_terms = wp_get_object_terms($post->ID,$tax);
	if (!empty($post_terms)) {
	  foreach ($post_terms as $term) {
	    if ($assoc_images[$term->term_id]) {
	      $pic_info = pic_info_for($attr,$assoc_images[$term->term_id]);
	      // CSS also includes, e.g., category-image:
              if (strlen($pic_info['class'])) 
		$pic_info['class'] .= " taxonomy-image {$attr['taxonomy']}-image";
	      return $pic_info;
	    }
	  }
	}
      }
    }
  }
  return $pic_info;
}

add_filter ('autonav_thumb', 'autonav_thumb_taxonomy_image', 90, 4);

