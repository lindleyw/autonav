<?php
/*
Plugin Name: Autonav NextGEN Thumbnail integration
Description: Permits using NextGEN Gallery Thumbnails as featured images with AutoNav
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

function autonav_thumb_nextgen ( $pic_info, $attr, $post ) {
  if (empty($pic_info)) {
    // Support NextGEN gallery thumbnails. Unfortunately we cannot resize them here.
    if (function_exists('get_post_thumbnail_id')) { 
      $tid = get_post_thumbnail_id($post->ID);
      if (!empty($tid)) {
	$ngg_id = preg_match("#^ngg-(\\d+)$#", $tid);
	if ($ngg_id && class_exists('nggdb')) {
	  $ngg_info = nggdb::find_image($ngg_id); // See also: get_option('ngg_options')
	  $pic_info = array('fullwidth' => $ngg_info->meta_data['width'],
			    'fullheight' => $ngg_info->meta_data['height'],
			    'thumbwidth' => $ngg_info->meta_data['thumbnail']['width'],
			    'thumbheight' => $ngg_info->meta_data['thumbnail']['height'],
			    'pic_full' => $ngg_info->imagePath,
			    'pic_full_path' => $ngg_info->imagePath,
			    'pic_full_url' => $ngg_info->imageURL,
			    'pic_thumb' => $ngg_info->thumbPath,
			    'pic_thumb_url' => $ngg_info->thumbURL,
			    'image' => $ngg_info->thumbPath,
			    'image_url' => $ngg_info->thumbURL,
			    'linkto' => $attr['linkto'],
			    'class' => $attr['class'].'-image');
	}
      }
    }
  }
  return $pic_info;
}
# Priority value means NextGEN thumbs come just before ordinary attachments
add_filter('autonav_thumb', 'autonav_thumb_nextgen', 25, 4);

