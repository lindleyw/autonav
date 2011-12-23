<?php
/*
Plugin Name: Autonav Image Table Based Site Navigation
Plugin URI: http://www.saltriversystems.com/website/autonav/
Description: Displays child pages, posts, attached images or more, in a table of images or a simple list. Automatically resizes thumbnails.
Author: William Lindley
Version: 1.4.2
Author URI: http://www.saltriversystems.com/
*/

/*  Copyright 2008-2011 William Lindley (email : bill -at- saltriversystems -dot- com)

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

add_action('admin_init', 'autonav_wloptions_init' );
add_action('admin_menu', 'autonav_wloptions_add_page');

// Modifies $attr['pic_thumb'], $attr['thumbwidth'] and $attr['thumbheight']
function resize_crop (&$attr, $prefix) {

  $wp_dir = wp_upload_dir();
  $pic_full = $attr['pic_full'];
  $pic_full_path = $attr['pic_full_path'];
  if ($pic_full_path == '') {
    $pic_full_path = trailingslashit($wp_dir['basedir']) . $pic_full;
    $attr['pic_full_path'] = $pic_full_path;
  }

  if ($prefix == '') {
    $prefix = 'thumb'; // default to thumbnail sizes
  }

  if (is_array($attr[$prefix.'_resample'])) {
    $resample_params = $attr[$prefix.'_resample'];
  } else {

    $from_size = getimagesize($pic_full_path);
    $to_width = $attr[$prefix.'width'];
    $to_height = $attr[$prefix.'height'];
    $resample_params = image_resize_dimensions($from_size[0], $from_size[1],
					       $to_width, $to_height, $attr['crop'] > 0 ? 1 : 0);
  }

  if (is_array($resample_params)) {

    list($dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h) = $resample_params;
    if (($attr['crop'] & 1) == 0) { // 1=center x,y; 2=upper-left (x=0,y=0); 3=top-center (center x,y=0)
      $src_x = 0;
    }
    if ($attr['crop'] & 2) {
      $src_y = 0;
    }
    $pic_full = preg_replace('#-\d+x\d+\.#','.',$pic_full); // remove, e.g., trailing '-1024x768'
    $info = pathinfo($pic_full); // relative to upload directory
    $dir = $info['dirname'];
    $ext = $info['extension'];
    $name = basename($pic_full, ".{$ext}");
    $suffix = "{$dst_w}x{$dst_h}";
    $to_file = "{$dir}/{$name}-{$suffix}.{$ext}";
    $to_file_path = trailingslashit($wp_dir['basedir']) . $to_file;
    $to_file_url = trailingslashit($wp_dir['baseurl']) . $to_file;

    // Resample
    if (preg_match("#jp#i", $ext)) {
      $from_image = imagecreatefromjpeg($pic_full_path);
    } elseif (preg_match("#png#", $ext)) {
      $from_image = imagecreatefrompng($pic_full_path);
    } elseif (preg_match("#gif#", $ext)) {
      $from_image = imagecreatefromgif($pic_full_path);
    } else {
      return;
    }
    $to_image = imagecreatetruecolor($dst_w, $dst_h);

    $bkg = substr($attr['background'], 0, 7);
    if (preg_match('/#[0-9a-fA-F]{6}/', $bkg)) {
      $fill_color = sscanf($bkg, '#%2x%2x%2x');
      imagefilledrectangle($to_image, 0, 0, $dst_w, $dst_h, $fill_color);
    }

    // Create transparent images -- for possible PNG output
    // imagealphablending($to_image, true);
    // imagesavealpha ($to_image, true);

    $quality = abs($attr['sharp']);
    if ($quality < 10) { $quality = 90; } // sharpness parameter becomes quality

    // Create image in memory:
    if ($attr['sharp'] > 0) {   // positive for pixellated, zero or negative for smooth
      if ($quality > 90) {      // create intermediate size
        $i_factor = 1 - ($quality - floor($quality)); // 90.3333 becomes sharp factor .6666
        if ($i_factor == 0) { $i_factor = 1/2; }
        $interm_h = floor(($dst_h + $src_h) * $i_factor);
        $interm_w = floor(($dst_w + $src_w) * $i_factor);
        $interm_image = imagecreatetruecolor($interm_w, $interm_h);
        imagecopyresized( $interm_image, $from_image,
                          $dst_x * $i_factor, $dst_y * $i_factor, $src_x * $i_factor, $src_y * $i_factor,
                          $interm_w, $interm_h, $src_w, $src_h);
        imagecopyresampled( $to_image, $interm_image,
                            $dst_x, $dst_y, $dst_x * $i_factor, $dst_y * $i_factor,
                            $dst_w, $dst_h, $interm_w, $interm_h);
        imagedestroy($interm_image);
      } else {
        imagecopyresized( $to_image, $from_image, $dst_x, $dst_y, $src_x, $src_y,
                          $dst_w, $dst_h, $src_w, $src_h);
      }
    } else {
      imagecopyresampled( $to_image, $from_image, $dst_x, $dst_y, $src_x, $src_y,
                          $dst_w, $dst_h, $src_w, $src_h);
    }
    imagedestroy($from_image);
    if (!imagejpeg($to_image, $to_file_path, $quality)) {  // Creates file
      $attr['error'] = "CANNOT CREATE: ".$to_file_path;
    } else {
      $attr['pic_'.$prefix] = $to_file;
      $attr['pic_'.$prefix.'_path'] = $to_file_path;
      $attr['pic_'.$prefix.'_url']  = $to_file_url;
    }
    imagedestroy($to_image);

  }

}

/* ********************** */

function get_image_thumbnails($attr, $selected_pics, $fullsize_pics, $pic_size_info) {

  $wp_dir = wp_upload_dir();

  // For each full size image:
  // I. Find or create thumbnail:
  //    if cropping, look for exact cropped size
  //     else get its size and call wp_constrain_dimensions and look for exactly that size
  // II. Find or create constrained full-size image

  $full_width = get_option('large_size_w'); $full_height = get_option('large_size_h');

  foreach ($selected_pics as $apic_key) {
    $pic_info = array();
    $pic_info['pic_full'] = $fullsize_pics[$apic_key];
    $pic_info['pic_full_path'] = trailingslashit($wp_dir['basedir']) . $pic_info['pic_full'];
    $pic_info['pic_full_url'] = trailingslashit($wp_dir['baseurl']) . $pic_info['pic_full'];

    $orig_size = getimagesize($pic_info['pic_full_path']);
    $pic_info['fullwidth'] = $orig_size[0];
    $pic_info['fullheight'] = $orig_size[1];

    // "Full size" images are actually constrained to size chosen in Admin screen.
    // This means huge off-the-camera will actually be resized to, for example, 1024 width.
    if (($orig_size[0] > $full_width) || ($orig_size[1] > $full_height)) {
      $new_full_size = wp_constrain_dimensions($orig_size[0],$orig_size[1],$full_width,$full_height);
      $pic_info['fullwidth'] = $new_full_size[0];
      $pic_info['fullheight'] = $new_full_size[1];
      $full_size = $new_full_size[0] . 'x' . $new_full_size[1];
      $pic_info['crop'] = 0; // always scale these images, never crop
      if ($pic_size_info[$apic_key][$full_size] == '') {
	// properly sized full image does not exist; create it
	resize_crop($pic_info, 'full'); // modifies ['pic_full'], creates ['pic_full_url'] in $pic_info
      } else {
	$pic_info['pic_full'] = $pic_size_info[$apic_key][$full_size];
	$pic_info['pic_full_path'] = trailingslashit($wp_dir['basedir']) . $pic_info['pic_full'];
	$pic_info['pic_full_url'] = trailingslashit($wp_dir['baseurl']) . $pic_info['pic_full'];
      }
    }

    // Find or create thumbnail
    if ($pic_info['fullwidth'] <= $attr['width'] && $pic_info['fullheight'] <= $attr['height']) {
      // requested full size image already qualifies as a thumbnail
      $pic_info['thumbwidth'] = $pic_info['fullwidth'];
      $pic_info['thumbheight'] = $pic_info['fullheight'];
      $pic_info['pic_thumb'] = $pic_info['pic_full'];
    } else {
      $size_params = image_resize_dimensions($pic_info['fullwidth'], $pic_info['fullheight'],
					     $attr['width'], $attr['height'], $attr['crop']);
      $pic_info['thumbwidth'] = $size_params[4]; // new width and height, whether cropped or scaled-to-fit
      $pic_info['thumbheight'] = $size_params[5];
      $thumb_size = $pic_info['thumbwidth']. 'x' . $pic_info['thumbheight'];
      $pic_info['pic_thumb'] = $pic_size_info[$apic_key][$thumb_size];
    }

    $pic_info['sharp'] = $attr['sharp'];

    if ($pic_info['pic_thumb'] == '') {
      // desired thumbnail does not exist; create it
      $pic_info['crop'] = $attr['crop'];
      resize_crop($pic_info, 'thumb'); // creates ['pic_thumb'], ['pic_thumb_url'] in $pic_info
    } else {
      $pic_info['pic_thumb_url'] = trailingslashit($wp_dir['baseurl']) . $pic_info['pic_thumb'];
    }
    $pic_info['image'] = $pic_info['pic_thumb']; // Copy thumbnail properties into image properties
    $pic_info['image_url'] = $pic_info['pic_thumb_url'];
    $pic_info['width'] = $pic_info['thumbwidth'];
    $pic_info['height'] = $pic_info['thumbheight'];
    $pic_info['linkto'] = $attr['linkto'];
    $pic_info['class'] = $attr['class'].'-image';

    $pics_info[] = $pic_info;
  }
  return($pics_info);
}

/* ********************** */

function get_images_from_folder($attr) {
  global $post;

  $wp_dir = wp_upload_dir();
  $pics_info = array();

  if (substr($attr['display'],0,1) == '/') {
    // Display images from a folder. NOTE: Absolute paths OK and are handled properly by path_join()
    $folder = substr($attr['display'],1);
    $dir_name = path_join(path_join($wp_dir['basedir'],$folder),'*.{jpg,jpeg,JPG,JPEG,png,PNG,gif,GIF}');
    $files = glob($dir_name, GLOB_BRACE);

    if (!is_array($files)) return ($pics_info);

    // Remove local path prefix (folder part remains)
    $sorted_files = array();
    foreach ($files as $afile) {
      $sorted_files[] = str_replace(trailingslashit($wp_dir['basedir']),
				    '',$afile);
    }

    // Sort names ('order' is ASC or DESC)
    switch (strtolower($attr['order'])) {
    case 'desc':
      rsort($sorted_files, SORT_STRING);
      break;
    case 'rand':
      shuffle($sorted_files);
      break;
    default:
      sort($sorted_files, SORT_STRING);
    }

    // Select names from 'include' parameter and build pic_info
    if ($attr['include'] != '') {
      // split on commas. for each word, first match exact filename; then match suffix.
      $included_files = array();
      $include_list = explode(',',$attr['include']);
      foreach ($include_list as $ifile) {
	foreach ($sorted_files as &$afile) {
          if (!strlen($afile)) continue; // already used this file
	  $info = pathinfo($afile);
	  $dir = $info['dirname'];
	  $ext = $info['extension'];
	  $name = basename($afile, ".$ext");

	  if (is_numeric($ifile)) {
	    // for "include=7" this will match 'file7.jpg' and '7.jpg' but not 'file17.jpg' or '17.jpg'
	    $match_string = "#^(.*?\\D)?0*$ifile(-\d+x\d+)?\.\w+\z#";
	  } else {
	    $match_string = "#$ifile(-\d+x\d+)?\.\w+\z#"; // match text at end of filename
	  }
	  $suffix_match = (preg_match($match_string,$afile));
	  // Match exact filename, or suffix
	  if ($ifile == $afile || $suffix_match) {
	    $included_files[] = $afile;
            $afile = ''; // do not consider this file again
	  }
	}
      }
      $sorted_files = $included_files;
    }

    $pic_size_info = array(); // Sizes available for each picture
    $fullsize_pics = array(); // List of full-size images
    $selected_pics = array();

    foreach ($sorted_files as $afile) {
      if (preg_match('#^(.*?)(?:-(\d+x\d+))?\.\w+\Z#',$afile,$filebits)) {
	if ($filebits[2] != '') { // resized image
	  $pic_size_info[$filebits[1]][$filebits[2]] = $afile;
	} else {
	  $fullsize_pics[$filebits[1]] = $afile;
	  $selected_pics[] = $filebits[1];
	}
      }
    }
    
    if (count($selected_pics)) {
      $pics_info = get_image_thumbnails($attr, $selected_pics, $fullsize_pics, $pic_size_info);
    }
  }
  return ($pics_info);
}

/* ********************** */

function create_images_for($attr, $pic_fullsize) {
    $info = pathinfo($pic_fullsize);
    $dir = $info['dirname'];
    $ext = $info['extension'];
    $name = basename($pic_fullsize, ".$ext");

    // Get attachment image from the folder where it was uploaded.
    $attr['display'] = "/$dir";
    $attr['include'] = $name;
    $pic_info = get_images_from_folder($attr);
    if (is_array($pic_info)) {
      return $pic_info[0]; // should only be one image found
    }
    return 0; // no image found
}

/* ********************** */

function pic_info_for($attr, $id) {
  $attached_pic = get_attached_file($id);
  if ($attached_pic == '') return; // cannot find the attachment
  $pic_info = create_images_for($attr, $attached_pic);
  if (is_array($pic_info)) {
    $post_info = get_post($id); // the attachment's post
    $pic_info['id'] = $id;
    $pic_info['caption'] = $post_info->post_excerpt; // Attachment: Caption
    $pic_info['description'] = $post_info->post_content; // Attachment: Description
    $pic_info['title'] = $post_info->post_title; // Attachment: Title

    $alt = get_post_meta($id, '_wp_attachment_image_alt', true);
    $pic_info['attpage'] = get_attachment_link($id);
    if(count($alt)) $pic_info['alt_text'] = $alt; // Attachment: Alternate Text

    return $pic_info;
  }
  return;
}

/* ********************** */

function get_actual_pid ($pids) { // permit list of ids, post-slugs, and page-paths
  $pids_array = explode(',',$pids);
  if (is_array($pids_array)) {
    foreach ($pids_array as &$pid) {
      if (strlen($pid) && !is_numeric($pid)) {
	$get_obj = get_posts('name='.$pid); // first, try post by slug
	if (count($get_obj)) {
	  $pid = $get_obj[0]->ID;
	} else {	       // then page with full path (not merely the slug!)
	  $get_obj = get_page_by_path($pid);
	  $pid = $get_obj->ID;
	}
      }
    }
    return implode(',',$pids_array);
  }
  return $pids;
 }

/* ********************** */

function get_images_attached($attr, $pids, $limit) {
  global $post;

  $wp_dir = wp_upload_dir();

  if (strlen($pids) == 0) {
    $pids = $post->ID;
  }
  $pids = get_actual_pid($pids);
  $pics_info = array();

  $order = strtolower($attr['order']) == 'desc' ? 'desc' : 'asc';
  // NOTE: Possibly use $attr['orderby'] directly at risk of breaking backwards compability
  $orderby = strtolower($attr['order']) == 'rand' ? 'rand' : 'menu_order';

  foreach (explode(',',$pids) as $pid) {
    // use post_status=inherit to disable finding attachments that are set to draft or private
    $attachments = get_children(array('post_parent' => $pid, 'post_status' => 'inherit',
				      'numberposts' => $limit >= 1 ? $limit : -1,
				      'post_type' => 'attachment', 'post_mime_type' => 'image',
				      'post_status' => 'inherit', 'orderby' => $orderby,
				      'order' => $order));
    if (empty ($attachments)) return $pics_info;

    foreach ($attachments as $id => $attach_info) {
      if ($attach_info->menu_order < -100) continue; // permit disabling images via menu_order
      $pic_info = pic_info_for($attr,$id);
      if (is_array($pic_info)) {
	$pics_info[] = $pic_info;
      }
    }
  }

  return $pics_info;
}

/* ********************** */

function get_attachments($attr, $pid, $limit) {
  global $post;

  if (function_exists('attachments_get_attachments')) {
    if (strlen($pid) == 0) {
      $pid = $post->ID;
    }
    $pid = get_actual_pid($pid);

    $attachments = attachments_get_attachments($pid);
    $pics_info = array();

    // Title, caption override?
    foreach ($attachments as $attach_info) {
      $pic_info = pic_info_for($attr,$attach_info['id']);
      if (is_array($pic_info)) {
	$pics_info[] = $pic_info;
      }
    }
    
    return $pics_info;
  }

}

/* ********************** */

function get_selected_thumbnail ($attr, $pid) {
  $pics_info = array();
  $tid = 0;
  if (function_exists('get_post_thumbnail_id')) { /* since 2.9.0 */
    $tid = get_post_thumbnail_id($pid);
  }
  if ($tid) {
    $pic_info = pic_info_for($attr, $tid);
    if (is_array($pic_info)) {
      $pics_info[] = $pic_info;
      return $pics_info;
    }
  }
  return;
}

/* ********************** */

function get_pics_info($attr, $pages) {
  // Called with a list of either posts or pages (or even custom post-types).
  $wp_dir = wp_upload_dir(); // ['basedir'] is local path, ['baseurl'] as seen from browser
  $disp_pages = array();
  $picpages_only = $attr['pics_only'];

  foreach ($pages as $page) {
    $pic_info = array();
    $ximg = get_post_meta($page->ID, 'subpage_thumb', 1);
    if ($ximg != '') { // Specified exact thumbnail image
      if ( preg_match( '|^https?://|i', $ximg ) ) {
	$pic_info['image_url'] = $ximg; // as explicit URL
      } else {
	// local file... assume full-size picture given, and automagically create thumbnail
	$pic_info = create_images_for($attr, $ximg);
      }
    } else { // Use selected thumbnail; or first attached image, if any
      $pics = get_selected_thumbnail($attr, $page->ID);
      if (!is_array($pics)) {
	$pics = get_images_attached($attr, $page->ID, 1);
      }
      if (is_array($pics)) {
	$pic_info = $pics[0]; // should be exactly one
      }
    }

    if ((!$picpages_only) || $pic_info['image_url'] != '') {
      $pic_info['linkto'] = $attr['linkto'];
      $pic_info['page'] = $page;
      switch($pic_info['linkto']) {
      case 'pic':
	$pic_info['permalink'] = $pic_info['pic_full_url'];
      case 'none':	 	// no link at all
	break;
      default:
	$pic_info['permalink'] = get_permalink($page->ID);
      }

      $pic_info['excerpt'] = get_post_meta($page->ID, 'subpage_excerpt', 1);
      if ($pic_info['excerpt'] == '') $pic_info['excerpt'] = $page->post_excerpt;

      $pic_info['title'] = get_post_meta($page->ID, 'subpage_title', 1);
      if ($pic_info['title'] == '') $pic_info['title'] = $page->post_title;
      $disp_pages[] = $pic_info;
    }
  }
  return $disp_pages;

}

/* ********************** */

function get_subpages ($attr) {
  global $post;

  $query = array();
  $child_pages = array();
  $postids = $attr['postid']; 	
  if (preg_match('#(\w+)\s*:\s*(.*?)\s*$#', $postids, $selection)) {
    $type = strtolower($selection[1]);
    $value = $selection[2];
    $postids = preg_replace('#,?(\w+)\s*:\s*(.*?)\s*$#', '', $postids);
    switch ($type) {
    case 'author':		/* postid="author:4" */
      $query['authors']=$value;
      break;
    default: 			/* postid="custom-field:value" */
      $query['meta_key']=$type;
      $query['meta_value']=$value;
    }
  }
  /* postid="5,3"; postid="desserts,entrees"; postid="entrees/beef" */
  $child_pages = explode(',',get_actual_pid($postids)); 

  $my_children = 0;
  if ($attr['siblings']) {
    if ($post->post_parent) { // children of our parent
      $child_pages = array($post->post_parent);
    }
    if (!$attr['self']) { // add ourselves to exception list
      $attr['exclude'] = $post->ID . ($attr['exclude'] !== '' ? ','.$attr['exclude'] : '');
    }
  } else {
    if (!$child_pages[0]) {
      $show_on_front = get_option('show_on_front');
      $page_on_front = get_option('page_on_front');
      if ($show_on_front == 'page' && $page_on_front == $post->ID ) {
        $child_pages = array(0, $post->ID); // children of "Main Page (no parent)" and us explicitly
        if (!$attr['self']) { // except ourself (will the "real" homepage please stand up)
          $attr['exclude'] = $post->ID . ($attr['exclude'] !== '' ? ','.$attr['exclude'] : '');
        }
      } else {
        $child_pages = array($post->ID);
      }
      $my_children = 1;
    }
  }
  $query['echo'] = 0;
  $query['title_li'] = 0;
  $query['sort_column'] = $attr['orderby'];
  // Prefix with post_ to match database table column names
  if ((strpos($query['sort_column'], 'post_') === false) && 
      in_array($query['sort_column'], 
	       array('author', 'date', 'modified', 'parent', 'title', 'excerpt', 'content'))) {
    $query['sort_column'] = 'post_' . $query['sort_column'];
    // Not requiring post_ prefix: comment_status, comment_count, menu_order, ID
  }


  if (strlen ($attr['exclude'])) {
    $query['exclude'] = $attr['exclude'];
  }
  $base_query = $query;

  $pages = array();
  foreach ($child_pages as $child_of) {
    $query = $base_query;
    $query['child_of'] = $child_of;
    if (!$attr['family']) { // Only children of this page
      $query['hierarchical'] = 0;
      $query['parent'] = $child_of;
    }
    $these_pages = & get_pages($query);

    if (count ($these_pages)) {
      foreach ($these_pages as $subpage) {
          array_push($pages, $subpage);
      }
    } else {
      // If specified a different page with no subpages, use that page alone.
      // i.e., If listing "my" subpages, and I haven't any, don't list "me."
      if ($my_children == 0) {
	$these_pages = get_pages("include=$child_of&echo=0&title_li=0");
	array_splice($pages, count($pages), 0, $these_pages);
      }
    }
  }

  if (count($pages) == 0) {
    return;
  }
  switch (strtolower($attr['order'])) {
  case 'desc':
    $pages = array_reverse($pages);
    break;
  case 'rand':
    shuffle($pages);
    break;
  }
  return get_pics_info($attr, $pages);

}

/* ********************** */

function get_selposts($attr) {
  global $post;
  $query = array();

  $postids = $attr['postid'];
  if (preg_match('#^(\w+)\s*:\s*(.*?)\s*$#', $postids, $selection)) {
    $type = strtolower($selection[1]);
    $value = $selection[2];
    $numeric_value = preg_match('#^[-0-9,]+#', $value); // '5,-3' counts as all numeric here

    switch ($type) {
	case 'cat':
	case 'category':  // choose numeric or name
      $query[$numeric_value ? 'cat' : 'category_name']=$value;
      break;
    case 'author':
      $query[$numeric_value ? 'author' : 'author_name']=$value;
      break;
    case 'tag':
      $query['tag'] = $value;
      break;
    default:  // First check for custom taxonomies; otherwise, use custom fields.
      if (taxonomy_exists($type)) {
	$query[$type]=$value;
      } else {
	$query['meta_key']=$type;
	$query['meta_value']=$value;
      }
    }
  } else {
    $query['include'] = $postids;
  }
  if (!$attr['self']) { // add ourselves to exception list
    $query['exclude'] = $post->ID;
  }
  if (preg_match('#^posts\s*:\s*(.*)#', $attr['display'], $value)) {
    $query['post_type'] = $value[1]; // custom post type
  }
  if ($attr['count']) { $query['numberposts'] = $attr['count']; }
  if ($attr['start']) { $query['offset'] = $attr['start']; }

  // possible ordering values (date, author, title,...) listed in http://codex.wordpress.org/Template_Tags/query_posts
  if (substr(strtolower($attr['orderby']),0,5) == 'meta:') {
    $query['meta_key'] = substr($attr['orderby'],5);
    $attr['orderby']='meta_value';
  }
  if (strtolower($attr['order']) == 'rand') {
    $attr['orderby'] = 'rand'; 	// for backwards compatibility
    $attr['order'] = '';
  }
  if (strtolower($attr['orderby']) == 'menu_order') { // useless for posts
    $attr['orderby'] = 'post_date'; 	// a sensible default, and backwards compatible
    if (strtoupper($attr['order']) == $attr['order']) { // default order? override
      $attr['order'] = 'desc';
    }
  }
  if (strtolower($attr['orderby']) == 'postmash') { // use menu_order, but NOT orderby=post_date.
    $attr['orderby'] = 'menu_order';
  }
  if ($attr['order']) { $query['order'] = $attr['order']; } 
  if ($attr['orderby']) { $query['orderby'] = $attr['orderby']; } 
  $these_posts = get_posts($query);

  if (count($these_posts) == 0) {
    return;
  }
  return get_pics_info($attr, $these_posts);
}

/* ********************** */

function prepare_picture (&$pic) {
  $alt_text = strlen($pic['alt_text']) ? $pic['alt_text'] : $pic['title'];
  if (!strlen($pic['error'])) {
    if (strlen($pic['image_url'])) {
      $pic['content'] = '<img src="' . $pic['image_url'] . '" alt="'. esc_attr($alt_text) . '" ' .
	image_hwstring($pic['width'],$pic['height']) . ' class="' . $pic['class']. '" />';
    } else {
      $pic['error'] = __('Missing image');
      $error_print = $pic['error'];
      if (is_object($pic['page'])) {
	$pic['error'] .= ' for postid='.$pic['page']->ID;
      }
    }
  } else {
    $error_print = $pic['pic_full_url'];
  }
  if (strlen($pic['error'])) {
    $pic['content'] = '<!-- ' . $pic['error'] . ' -->' . $error_print;
  }
  if ($pic['permalink'] == '') {
    switch ($pic['linkto']) {
    case 'pic':
      $pic['permalink'] = $pic['pic_full_url']; // link to fullsize image
      break;
    case 'attpage':
      $pic['permalink'] = $pic['attpage'];
      break;
    }
  }
}

/* ********************** */

function an_create_output_list_item ($html, $class, $pic, $attr) {
  // Outputs the list-item element for a single post/page
  $has_link = strlen($pic['permalink']) > 0;

  if ($has_link) {
    $html .= '<a href="' . esc_attr($pic['permalink']) . '">';
  }
  $html .= $pic['title'];
  if ($attr['thumb']) {
    $html .= '<span class="' . $class . '-list-image">' . $pic['content'] . '</span>';
  }
  if ($has_link) {
    $html .= "</a>";
  }
  return $html;
}

function an_create_output_table_picture ($html, $class, $pic, $attr) {
  // Outputs the picture in a table-data element for a single post/page
  $my_img_rel = ($pic['linkto'] == 'pic') ? $attr['_img_rel'] : '';
  $has_link = strlen($pic['permalink']) > 0;
  $title_text = '';
  if (strlen($pic['title']) && ! $attr['titles']) { // Put title in tag, not plaintext
    $title_text = ' title="' . esc_attr($pic['title']) . '"';
  }
  if ($has_link) {
    $html .= '<a href="' . esc_attr($pic['permalink']) . "\" $my_img_rel$title_text>";
  }
  $html .= $pic['content'];
  if ($has_link) {
    $html .= '</a>';
  }
  return $html;
}

function an_create_output_table_text ($html, $class, $pic, $attr) {
  // Outputs the text for a table-data element for a single post/page
  $has_link = strlen($pic['permalink']) > 0;
  if (strlen($pic['title']) && $attr['titles']) {
    $html .= '<p class="' . $class . '-text">';
    if ($has_link) {
      $html .= '<a href="' . esc_attr($pic['permalink']) . '">'; 
    }
    $html .= $pic['title'];
    if ($has_link) {
      $html .= '</a>';
    }
    $html .= '</p>';
  }
  return $html;
}

function an_create_output_excerpt ($html, $class, $pic, $attr) {
  if ($attr['excerpt'] && strlen($pic['excerpt'])) {
    $html .= '<p class="' . $class . '-excerpt">' . $pic['excerpt'] . "</p>\n";
  }
  return $html;
}

function an_create_page_links($html, $class, $total_pages, $cur_page) {
  $html .= '<p class="' . $class . '-pages">';
  // Possibly permit override of 'next_text', 'prev_text', etc. - see /wp-includes/general_template.php
  $paginate_args = array('base' => get_permalink() . '%_%',
			 'total' => $total_pages, 'current' => $cur_page, 'show_all' => 1);
  $mybase = get_permalink();
  // if append rather than start arg:
  if (strpos($mybase,'?') !== FALSE) { $paginate_args['format'] = '&page=%#%'; } 
  $html .= paginate_links($paginate_args);
  $html .= '</p>';

  return $html;
}

/* ********************** */

 function create_output($attr, $pic_info) {

  if (!array($pic_info)) { return ''; }
  if ($attr['start'] > 0) {
    $pic_info = array_slice($pic_info, $attr['start']);
  }
  if ($attr['count'] > 0) {
    $pic_info = array_slice($pic_info, 0, $attr['count']);    
  }

  if (!is_array($pic_info) || (count($pic_info) == 0)) { // nothing to do
    return '';
  }

  $total_pages = 1;
  // Pagination: Break candidate images, selected above, into pages.
  if ($attr['paged'] > 0) {
    $total_pages = ceil(count($pic_info) / $attr['paged']);
    $cur_page = 1;
    global $wp_query;    // For pagination
    if( isset( $wp_query->query_vars['paged'] )) {
      // no page number, or page 1, gives offset 0.
      $cur_page = max(1, $wp_query->query_vars['page'] );
    }
    // Now select only current page.
    $pic_info = array_slice($pic_info, ($cur_page - 1) * $attr['paged'], $attr['paged']);
  }

  $html = '';
  $class = $attr['class'];

  if ($attr['display'] == 'list' || $attr['list']) { // Produce list output
    $html = '<ul class="' . $class . '-list">';
    foreach ($pic_info as $pic) { // well, really the page not a picture
      if ($attr['thumb']) {
	prepare_picture($pic);
      }
      $html .= '<li class="' . $class . '-item">';
      $html = apply_filters('autonav_create_list_item', $html, $class, $pic, $attr);
      $html .= "</li>\n";
    }
    $html .= "</ul>";
  } else {  // Produce table output
    $viewer = $attr['imgrel'];
    if (strpos($viewer, '*')) {
      $viewer = str_replace( '*', ($attr['group']!='') ? '['.$attr['group'].']' : '', $viewer);
    }
    $attr['_img_rel'] = strlen($viewer) ? " rel=\"$viewer\"" : '';
    $html = '';
    $col = 0; $row = 0;
    $maxcol = $attr['columns'];
    $indiv_rows = $attr['combine'] == 'none'; // true when 'none', false otherwise
    $widow_row = $attr['combine'] == 'full';  // place widow row in separate table
    $start_table = '<table class="' . $class . '-table">';
    $end_table = "</table>\n";
    $in_table = 0;
    $start_row = '<tr class="' . $class . '-row">';
    $end_row = "</tr>\n";
    $in_row = 0; 

    $html = $start_table; $in_table = 1;
    if (strlen($attr['caption'])) { // only on first table
      $html .= '<caption>' . $attr['caption'] . '</caption>';
    }
    foreach ($pic_info as $pic) {

      prepare_picture($pic);

      if ($col == 0) {
	if ($row > 0 && $widow_row && ((count($pic_info) - ($row * $maxcol)) < $maxcol)) {
	  $indiv_rows = 1; // reached last (widow) row; switch to separate tables.
	  $html .= $end_table; $in_table = 0;
	}
	if ($indiv_rows && ($row > 0)) {
	  $html .= $start_table; $in_table = 1;
	}
	$html .= $start_row; $in_row = 1;
      }

      $html .= '<td class="' . $class . '-cell">';
      $html = apply_filters('autonav_create_table_item', $html, $class, $pic, $attr);
      $html .= "</td>\n";

      $col++;
      if ($col >= $maxcol) {
	if ($in_row) { $html .= $end_row; $in_row = 0; }
	if ($indiv_rows) {
	  $html .= $end_table; $in_table = 0;
	}
	$col = 0;
	$row++;
      }
    }
    if ($in_row) { $html .= $end_row; }
    if ($in_table) { $html .= $end_table; }
  }

  if ($total_pages > 1) {	// display pagination links
    $html = apply_filters('autonav_create_page_links',$html, $class, $total_pages, $cur_page);
  }
  return $html;
}

/* ********************** */

add_shortcode('autonav','autonav_wl_shortcode');

function autonav_wl_shortcode($attr) {
  global $post;

  // NOTE: This function can be added as a filter to override the standard Gallery shortcode.
  // In that case, this function may return an empty string to restore default behavior.

  if (!function_exists('imagecreatefromjpeg')) {
    print ("<span style=\"color:red; font-weight: bold;\">ERROR:</span> imagecreatefromjpeg() must be installed. On Ubuntu, \
use: <b><tt>apt-get install php5-gd</tt></b> Use yum on RedHat/CentOS, or similarly for your system.<br>\n"); return;
  }

  $options = get_option('autonav_wl');

  // Default values come from saved configuration
  $attr['order'] = strtolower($attr['order']); // so we can make 'desc' default for posts, regardless of option setting
  $attr = (shortcode_atts($options, $attr));

  // display can be: 'images' or 'list' (for child pages), '/folder' for images from directory,
  // or the default 'attached' for table of attached images

  if (in_array($attr['size'],get_intermediate_image_sizes())) {
    $size_list = image_constrain_size_for_editor(4000,4000,$attr['size']); // 4000 forces constraints
    $attr['size'] = $size_list[0].'x'.$size_list[1];
  } elseif (substr($attr['size'],0,5) == 'size_') {
    $attr['size'] = $attr[$attr['size']]; // e.g., size_small --> 150x120 
  }
  if (!preg_match('#(\d+)x(\d+)#',$attr['size'],$size)) {
    if ($attr['columns'] <= $attr['col_large']) {
      $attr['size'] = $attr['size_large'];
    } elseif ($attr['columns'] >= $attr['col_small']) {
      $attr['size'] = $attr['size_small'];
    } else {
      $attr['size'] = $attr['size_med'];
    }
  }
  if (!preg_match('#(\d+)x(\d+)#',$attr['size'],$sizebits)) {
    return 'Incorrect size specified: '. $attr['size'];
  } else {
    $attr['width'] = $sizebits[1];
    $attr['height'] = $sizebits[2];
  }

  $display_options = explode(',', $attr['display']);
  $attr['display'] = array_shift($display_options);

  // Mode specific defaults
  if (substr($attr['display'],0,6) == 'attach') {
    $attr['linkto'] = 'pic';
  } else {
    $attr['linkto']='page';
  }

  // process options
  foreach ($display_options as $o) {
    switch ($o) {
    case 'title':    $o = 'titles';
    case 'titles':   // eponymous boolean options
    case 'excerpt':  
    case 'thumb':    
    case 'siblings': 
    case 'family':   
    case 'self':     
    case 'list':     $attr[$o] = 1; break;
    case 'image':    $attr['linkto'] = 'pic'; break;
    case 'page':     $attr['linkto'] = 'attpage'; break;
    case 'nolink':   $attr['linkto'] = 'none'; break;
    }
  }
  if (!strlen($attr['class'])) $attr['class'] = 'subpages';

  $attr = apply_filters('autonav_pre_select', $attr); // Permit plugin/theme to override here

  if (($attr['display'] == 'list') || ($attr['display'] == 'images')) {
    $pic_info = get_subpages($attr);
  } elseif (substr($attr['display'],0,6) == 'attach') {
    $post_id = $attr['postid'];
    if (strlen($post_id)==0) {
      $post_id = $post->ID;
    }
    if ($attr['display'] == 'attachments') {
      $pic_info = get_attachments($attr, $post_id, 0);
    } else {
      $pic_info = get_images_attached($attr, $post_id, 0);
    }
  } elseif (substr($attr['display'], 0, 5) == 'posts') {
    $pic_info = get_selposts($attr);
    $attr['start'] = 0;		// start,count handled inside get_selposts query
    $attr['count'] = 0; 
  } elseif (substr($attr['display'], 0, 1) == '/') { // looks like a directory name
    if ($attr['linkto'] != 'none') $attr['linkto'] = 'pic'; // unless explicitly no links
    $pic_info = get_images_from_folder($attr);
  } else {			// permit custom hook to handle everything else
    $pic_info = apply_filters('autonav_select', array(), $attr);
  }
  $pic_info = apply_filters('autonav_post_select', $pic_info, $attr);
  $html = create_output($attr, $pic_info);
  $html = apply_filters('autonav_html', $html, $attr); // permit custom hook post HTML creation

  return $html;
}

/* ********************** */

// Init plugin options to white list our options
function autonav_wloptions_init(){
  register_setting( 'autonav_wloptions_options', 'autonav_wl', 'autonav_wloptions_validate' );
}

/* ********************** */

function autonav_o_header ($html, $start_end_flag, $heading = NULL) {
  if ($start_end_flag & 2) {
    print "</tr>";
    if (!strlen($html)) {
      return;
    }
  }
  if (isset($heading)) {
    print "<tr><th colspan='2'><h3>$heading</h3></th></tr>\n";
  }
  if ($start_end_flag & 1) {
    print "<tr valign='top'><th scope='row'>$html</th>";
    //print "<tr>";
  }
}

function _autonav_o_item($options, $html_tags) {
  print '<input ';
  $value = $options[$html_tags['name']];
  foreach ($html_tags as $h_tag => $h_value) {
    switch ($h_tag) {
    case 'text':
      $plain_text = $h_value;
      break;
    case 'checked':
      checked($options[$html_tags['name']], $h_value);
      $value = $h_value;
      print " ";
      break;
    case 'name':
      print "$h_tag=\"autonav_wl[$h_value]\" ";
      break;
    default:
      print "$h_tag=\"$h_value\" ";
    }
  }
  print "value=\"$value\" /> $plain_text";
}

function autonav_o_item($options, $html_tags) {
  print '<td>';
  if (isset($html_tags['checked']) && is_array($html_tags['checked'])) {
    foreach ($html_tags['checked'] as $radio_value => $radio_text) {
      $html_tag_copy = $html_tags;
      $html_tag_copy['checked'] = $radio_value;
      $html_tag_copy['text'] = $radio_text;
      _autonav_o_item($options, $html_tag_copy);
    }
  } else {
    _autonav_o_item($options, $html_tags);
  }
  print "</td>\n";
}

// Add menu page
function autonav_wloptions_add_page() {
  // first string in page title (in html header), second string is title in menu
  add_submenu_page('options-general.php','AutoNav Options','AutoNav',8, __FILE__, 'autonav_wloptions_do_page');
}

/* ********************** */

// Draw the menu page itself
function autonav_wloptions_do_page() {
  ?>
  <div class="wrap">
    <h2>Autonav Options</h2>
<table width="100%" border="0">
<tr><td valign="top">
<form method="post" action="options.php">
<?php
    settings_fields('autonav_wloptions_options');
    $options = get_option('autonav_wl');
    if ($options['combine'] == '') { $options['combine'] = 'all'; }
    if (intval($options['col_large']) < 1) {
      $options['col_large'] = 2;
    }
    if (intval($options['col_small']) == 0) {
      $options['col_small'] = 4;
    } elseif (intval($options['col_small']) < $options['col_large'] + 1) {
      $options['col_small'] = $options['col_large'] + 1;
    }
    if ($options['order'] == '') { 
      $options['order'] = 'ASC';
    } else {
      $options['order'] = strtoupper($options['order']); // so we can make 'desc' default for posts, regardless
    }
    if ($options['orderby'] == '') { $options['orderby'] = 'menu_order'; }
    print '<table class="form-table">';
    autonav_o_header('Show only pages with thumbnails?', 1, 'When listing child pages');
    autonav_o_item($options,array('name'=>'pics_only', 'type'=>'checkbox', 'checked' => '1'));
    autonav_o_header('Use sort_column', 3);
    autonav_o_item($options, array('name'=>'orderby', 'type'=>'text',
				     'text' => 'from <a href="http://codex.wordpress.org/Template_Tags/wp_list_pages#Parameters">list of possible values</a> <small>(<em>wordpress.org</em>)</small> or <tt>meta:<em>customfieldname</em></tt>'));
    autonav_o_header('List of page IDs to exclude', 3);
    autonav_o_item($options, array('name'=>'exclude', 'type'=>'text'));
    autonav_o_header('Display Titles Under Images', 3, 'Displaying images');
    autonav_o_item($options, array('name'=>'titles', 'type'=>'checkbox', 'checked' => '1'));
    autonav_o_header('Size of images', 3);
    autonav_o_item($options, array('name'=>'size', 'type'=>'text', 'text' => '"auto" for below, or as "300x200"'));
    autonav_o_header('Default number of columns', 3);
    autonav_o_item($options, array('name'=>'columns', 'type'=>'text'));
    autonav_o_header('Automatic image sizing', 3);
    print "<td><table border='1'><tr>";
    autonav_o_item($options, array('name'=>'col_large', 'type'=>'text', 'size' => 2, 'text' => '<br />or fewer columns,<br />use size:'));
    print "<td>Intermediate number of<br />columns, use size:</td>";
    autonav_o_item($options, array('name'=>'col_small', 'type'=>'text', 'size' => 2, 'text' => '<br />or more columns,<br />use size:'));
    print "</tr><tr>\n";
    autonav_o_item($options, array('name'=>'size_large', 'type'=>'text', 'size' => 12));
    autonav_o_item($options, array('name'=>'size_med', 'type'=>'text', 'size' => 12));
    autonav_o_item($options, array('name'=>'size_small', 'type'=>'text', 'size' => 12));
    print "</tr>\n</table>\n</td>";
    autonav_o_header('Crop images to size?', 3);
    autonav_o_item($options, array('name'=>'crop', 'type'=>'radio', 
				      'checked' => array(0 => 'Fit images inside specified size.<br />',
							 1 =>'Crop to exact size, from center of image.<br />',
							 2 =>'Crop to exact size, from upper-left.<br />', 
							 3 =>'Crop to exact size, from top middle.<br />')));
    autonav_o_header('Combine rows of images into tables:', 3, 'Table controls');
    autonav_o_item($options, array('name'=>'combine', 'type'=>'radio',
				      'checked' => array('all' => 'All rows in one table.<br />',
							 'none' => 'Each row a separate table.<br />',
							 'full' => 'All full rows in one table; trailing partial row in separate table.')));
    autonav_o_header('Default class for tables', 3);
    autonav_o_item($options, array('name'=>'class', 'type'=>'text', 
				      'text' => '<br />Table elements will use this as the prefix for their styles, as <em>class</em>-table,
<em>class</em>-row, <em>class</em>-cell, etc.'));
    autonav_o_header('Image relation (rel="") tag', 3, "Image Relations");
    autonav_o_item($options, array('name'=>'imgrel', 'type'=>'text', 
				      'text' => '<br />
<em>Optional.</em> If this tag contains an asterisk * then the optional "group" specifier
(below; but usually specified in the shortcode) will be inserted as [group], as when you wish
to have multiple groups of pictures with a lightbox-style display.'));
    autonav_o_header('Default image group for above', 3);
    autonav_o_item($options, array('name'=>'group', 'type'=>'text', 'text' => '<em>Usually left blank</em>'));
?>
<input type="hidden" name="autonav_wl[include]" value="" />
</table>
<p class="submit">
<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
</p>
</form>
</td><td valign="top">
<h2>Current&nbsp;Defaults</h2>
<table>
<?php
  $opt2 = $options; ksort($opt2);
  foreach ($opt2 as $opt_id => $opt_val) {
    print "<tr><td><em>$opt_id</em><td>$opt_val</td><tr>\n";
  }
?>
</table>
<h4>Further Information</h4>
<ul style="list-style: disc; margin-left: 5px;">
<li><a href="<?php
  $realpath = realpath(__DIR__."/readme.txt");
  $path = 'http://' . $_SERVER['HTTP_HOST'] . substr($realpath, strlen($_SERVER['DOCUMENT_ROOT']));
  if (DIRECTORY_SEPARATOR == '\\')
    $path = str_replace('\\', '/', $path);
  print $path;
 ?>">Readme file for this version</a></li>
<li><a href="http://www.saltriversystems.com/website/autonav/">Plugin homepage</a></li>
<li><a href="http://wordpress.org/extend/plugins/autonav/">AutoNav in Wordpress repository</a></li>
</ul>
<div style="border: 5px ridge #ff8833; float: right; width: 200px; margin-left: 5px; padding-left: 5px;"><form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<p style="text-align: center;">If you like this plugin, please consider a $12 donation to help fund its ongoing development.</p>
<center><input name="cmd" type="hidden" value="_s-xclick" /> <input name="hosted_button_id" type="hidden" value="8365853" /> <input alt="PayPal - The safer, easier way to pay online!" name="submit" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" type="image" /><img src="https://www.paypal.com/en_US/i/scr/pixel.gif" border="0" alt="" width="1" height="1" /></center>

</form></div>
</td></tr></table>
</div>
<?php
}

// Sanitize and validate input. Accepts an array, return a sanitized array.
function autonav_wloptions_validate($input) {

  $input['titles'] = ( $input['titles'] == 1 ? 1 : 0 );
  $input['pics_only'] = ( $input['pics_only'] == 1 ? 1 : 0 );
  $input['size'] =  wp_filter_nohtml_kses($input['size']);
  if ($input['size'] == '') { $input['size'] = 'auto'; }
  if ($input['size_small'] == '') { $input['size_small'] = '120x90'; }
  if ($input['size_med'] == '') { $input['size_med'] = '160x120'; }
  if ($input['size_large'] == '') { $input['size_large'] = '240x180'; }
  $input['display'] =  wp_filter_nohtml_kses($input['display']);
  if ($input['display'] == '') { $input['display'] = 'attached'; }
  if ($input['class'] == '') { $input['class'] = 'subpages'; }
  $input['combine'] =  wp_filter_nohtml_kses($input['combine']);
  if ($input['combine'] == '') { $input['combine'] = 'all'; }
  $input['crop'] =  ( $input['crop'] & 3 );  // 0=fit, 1=crop center, 2=from upper-left, 3=top middle crop
  $input['columns'] =  intval($input['columns']);
  if ($input['columns'] == 0) { $input['columns'] = 3; }
  $input['exclude'] =  wp_filter_nohtml_kses($input['exclude']);
  $input['postid'] = '';
  $input['start'] = 0;
  $input['count'] = 0;
  if (!isset ($input['paged'])) { $input['paged'] = 0; }
  if (!isset ($input['sharp'])) { $input['sharp'] = 0; }
  if ($input['order'] == '') { $input['order'] = 'ASC'; }
  if ($input['orderby'] == '') { $input['orderby'] = 'menu_order'; }
  $input['caption'] = '';
  if ($input['background'] == '') { $input['background'] = '#000000'; }
  return $input;
}

// Run at plugin activation time, to eliminate runtime failures when admin does not set preferences.
function autonav_wlactivate ($input) {
  // Retrieve current options (if any), call our validate function, and store.
  $options = get_option('autonav_wl');
  $options = autonav_wloptions_validate($options);
  update_option('autonav_wl', $options);
}

register_activation_hook( __FILE__, 'autonav_wlactivate' );
add_filter('autonav_create_list_item', 'an_create_output_list_item', 10, 4);
add_filter('autonav_create_list_item', 'an_create_output_excerpt', 20, 4);
add_filter('autonav_create_table_item', 'an_create_output_table_picture', 10, 4);
add_filter('autonav_create_table_item', 'an_create_output_table_text', 15, 4);
add_filter('autonav_create_table_item', 'an_create_output_excerpt', 20, 4);
add_filter('autonav_create_page_links', 'an_create_page_links', 10, 4);

?>
