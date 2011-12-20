<?php
/*
Plugin Name: Autonav Image Table Based Site Navigation
Plugin URI: http://www.saltriversystems.com/website/autonav/
Description: Displays child pages, posts, attached images or more, in a table of images or a simple list. Automatically resizes thumbnails.
Author: William Lindley
Version: 1.4.7
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

define ("AUTONAV_ARBITRARY_SIZE", 900); // for images unsupported by getimagesize() 
define ("AUTONAV_FORCE_CONSTRAIN", 4000); // forces WP's image_constrain_size_for_editor

/* *** HTML helpers *** */

function an_create_tag ($html_tag, $html_attr) {
  $attrs = array($html_tag);
  if (is_array($html_attr)) {
    foreach ($html_attr as $attr => $value) {
      $attrs[] = $attr . '="' . esc_attr($value) . '"';
    }
  }
  return '<' . implode(' ',$attrs) . '>';
}

/* *** Resize filters **** */

function autonav_internal_resize ($from_image, $attr, $prefix) {

  if (is_array($attr[$prefix.'_resample'])) {
    $resample_params = $attr[$prefix.'_resample'];
  } else {
    $resample_params = image_resize_dimensions(imagesx($from_image), imagesy($from_image),
					       $attr["{$prefix}width"], $attr["{$prefix}height"],
					       $attr['crop'] > 0 ? 1 : 0);
  }

  if (is_array($resample_params)) {
    // Prepare to resize
    list($dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h) = $resample_params;
    $to_image = imagecreatetruecolor($dst_w, $dst_h);
    $bkg = substr($attr['background'], 0, 7);
    if (preg_match('/#[0-9a-fA-F]{6}/', $bkg)) {
      $fill_color = sscanf($bkg, '#%2x%2x%2x');
      imagefilledrectangle($to_image, 0, 0, $dst_w, $dst_h, $fill_color);
    }

    // 1=center x,y; 2=upper-left (x=0,y=0); 3=top-center (center x,y=0)
    if (($attr['crop'] & 1) == 0) $src_x = 0;
    if ($attr['crop'] & 2)        $src_y = 0;

    imagecopyresampled( $to_image, $from_image, $dst_x, $dst_y, $src_x, $src_y,
			$dst_w, $dst_h, $src_w, $src_h );
  }
  if (isset($to_image)) {
    imagedestroy($from_image);
    return $to_image;
  }
  return $from_image;
}

add_filter('autonav_resize_image', 'autonav_internal_resize', 50, 4);

/* *** Resize function **** */

function resize_crop (&$attr, $prefix) {
  // Modifies $attr['pic_thumb'], $attr['thumbwidth'] and $attr['thumbheight']

  $wp_dir = wp_upload_dir();
  $pic_full = $attr['pic_full'];
  $pic_full_path = $attr['pic_full_path'];
  if ($pic_full_path == '') {
    $pic_full_path = trailingslashit($wp_dir['basedir']) . $pic_full;
    $attr['pic_full_path'] = $pic_full_path;
  }

  $pic_full	= preg_replace('#-\d+x\d+\.#','.',$pic_full); /* remove, e.g., trailing '-1024x768' */
  $info	= pathinfo($pic_full); // relative to upload directory
  $ext	= $info['extension'];
  $name	= basename($pic_full, ".{$ext}");

  // Read source image.
  switch (strtolower($ext)) {
  case 'jpg':
  case 'jpeg':
    $from_image = imagecreatefromjpeg($pic_full_path);
  break;
  case 'png':
    $from_image = imagecreatefrompng($pic_full_path);
    break;
  case 'gif':
    $from_image = imagecreatefromgif($pic_full_path);
    break;
  default:
    // Returns either an image resource, or a modified $attr
    $from_image = apply_filters('autonav_image_create', NULL, $pic_full_path, $attr, $ext);
    if (is_array($from_image)) {
      $attr['error'] = $from_image['error'];
      return;
    }
  }
  if (!isset($from_image)) {
    $attr['error'] = "Failed to read $ext image ($pic_full_path)";
    return;
  }

  if ($prefix == '') {
    $prefix = 'thumb'; // default to thumbnail sizes
  }

  $to_image = apply_filters('autonav_resize_image', $from_image, $attr, $prefix);
  if (!isset ($to_image)) {
    $attr['error'] = "Resize failed for image ({$pic_full}";
  } else {
    $to_ext	= 'jpg';  // for now, we always create jpeg
    $suffix	= imagesx($to_image) .'x'. imagesy($to_image);
    $to_file      = path_join($info['dirname'],"{$name}-{$suffix}.{$to_ext}");
    $to_file_path = path_join($wp_dir['basedir'], $to_file);
    $to_file_url  = path_join($wp_dir['baseurl'], $to_file);

    $quality = abs($attr['sharp']);
    if ($quality < 10) { $quality = 90; } // specify quality via 'sharpness'

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

/* *** Select filters **** */

function autonav_pick_sort($picked_files, $attr, $pic_size_info) {
    // Sort names before picking from Include list:
    if (is_array($picked_files)) {
	    switch (strtolower($attr['order'])) {
	    case 'desc':
	      rsort($picked_files, SORT_STRING);
	      break;
	    case 'rand':
	      shuffle($picked_files);
	      break;
	    default:
	      sort($picked_files, SORT_STRING);
	    }
	}
	return $picked_files;
}
add_filter('autonav_pick_files','autonav_pick_sort',10,3);

function autonav_select_include ($picked_files, $attr, $pic_size_info) {
  // Select pictures based on 'include' parameter
  if (strlen($attr['include']) == 0)
    return $picked_files;

  // split on commas. for each word, first match exact filename; then match suffix.
  $included_files = array();
  $include_list = explode(',',$attr['include']);
  foreach ($include_list as $ifile) {
    foreach ($picked_files as &$afile) {
      if (!strlen($afile)) continue; // already used this file
      if (is_numeric($ifile)) {
	// for "include=7" this will match 'file7.jpg' and '7.jpg' but not 'file17.jpg' or '17.jpg'
	$match_string = "#^(.*?\\D)?0*$ifile\z#";
      } else {
	$match_string = "#$ifile\z#"; /* match text at end of filename */
      }
      $suffix_match = (preg_match($match_string,$afile));
      if ($ifile == $name || $suffix_match) {
	$included_files[] = $afile;
	$afile = ''; // do not consider file again (! REMOVES from $picked_files !)
      }
    }
  }
  return ($included_files);
}

add_filter('autonav_pick_files', 'autonav_select_include', 20, 3);

/* ********************** */

function get_image_thumbnails($pics_info, $attr, $pic_size_info) {

  if (!count ($pics_info)) return array();
  $wp_dir = wp_upload_dir();

  // For each full size image:
  // I. Find or create thumbnail:
  //    if cropping, look for exact cropped size
  //     else get its size and call wp_constrain_dimensions and look for exactly that size
  // II. Find or create constrained full-size image

  $full_width = get_option('large_size_w'); $full_height = get_option('large_size_h');

  foreach ($pics_info as &$pic_info) {  // (! Modifies $pics_info !)
    $apic_key = $pic_info['pic_base']; // Picture base name is key into pic_size_info
    $pic_info['pic_full'] = $pic_size_info[$apic_key]['full']; // including folder name relative to WP root
    $pic_info['pic_full_path'] = path_join($wp_dir['basedir'], $pic_info['pic_full']);
    $pic_info['pic_full_url'] =  path_join($wp_dir['baseurl'], $pic_info['pic_full']);

    // "Full size" images are actually constrained to size chosen in Admin screen.
    // This means huge off-the-camera will actually be resized to, for example, 1024 width.
    $image_size = getimagesize($pic_info['pic_full_path']);
    if (empty($image_size))
      $image_size = array(AUTONAV_ARBITRARY_SIZE, AUTONAV_ARBITRARY_SIZE);

    $pic_info['fullwidth'] = $image_size[0];
    $pic_info['fullheight'] = $image_size[1];

    if (($image_size[0] > $full_width) || ($image_size[1] > $full_height)) {
      $image_size = wp_constrain_dimensions($image_size[0],$image_size[1],$full_width,$full_height);
      $full_size = $image_size[0] . 'x' . $image_size[1];
      $pic_info['fullwidth'] = $image_size[0];
      $pic_info['fullheight'] = $image_size[1];
      $pic_info['crop'] = 0; // always scale these images, never crop
      if ($pic_size_info[$apic_key][$full_size] == '') {
	// properly sized full image does not exist; create it
	resize_crop($pic_info, 'full'); // modifies ['pic_full'], creates ['pic_full_url'] in $pic_info
      } else {
	$pic_info['pic_full'] = $pic_size_info[$apic_key][$full_size];
	$pic_info['pic_full_path'] = path_join($wp_dir['basedir'], $pic_info['pic_full']);
	$pic_info['pic_full_url']  = path_join($wp_dir['baseurl'], $pic_info['pic_full']);
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
      $pic_info['pic_thumb_url'] = path_join($wp_dir['baseurl'], $pic_info['pic_thumb']);
    }
    $pic_info['image'] = $pic_info['pic_thumb']; // Copy thumbnail properties into image properties
    $pic_info['image_url'] = $pic_info['pic_thumb_url'];
    $pic_info['width'] = $pic_info['thumbwidth'];
    $pic_info['height'] = $pic_info['thumbheight'];
    $pic_info['linkto'] = $attr['linkto'];
    $pic_info['class'] = $attr['class'].'-image';
  }
  return($pics_info);
}

add_filter('autonav_get_thumbnails', 'get_image_thumbnails', 10, 4);

/* ********************** */

function get_images_from_folder($attr) {
  global $post;
  global $cached_pic_size_info; // since 1.4.3 -- Cache directory info

  $wp_dir = wp_upload_dir();
  $pics_info = array();

  if (substr($attr['display'],0,1) == '/') {
    // Display images from a folder. NOTE: Absolute paths OK and are
    // handled properly by path_join(). We then force a relative path.
    $full_path = trailingslashit(path_join($wp_dir['basedir'],substr($attr['display'],1)));
    $folder = str_replace(trailingslashit($wp_dir['basedir']),'',$full_path);

    // Retrieve file list sans reliance on the (possibly unavailable) glob() function.
    $picked_files = array();
    $pic_size_info = array(); // Sizes available for each picture
    if (is_array($cached_pic_size_info[$full_path])) {
      $pic_size_info = $cached_pic_size_info[$full_path];
      $picked_files = array_keys($pic_size_info);
    } else {
      if (($dir = opendir(path_join($wp_dir['basedir'], $folder))) == false) {
	print "ERROR: opendir('" . path_join($wp_dir['basedir'], $folder) . "') failed";
      } else {
	while (($file = readdir($dir)) !== false) {
	  // parse each image in the directory as either full-size or thumbnail
          $afile = strlen($folder) ? path_join($folder, $file) : $file;
	  if (preg_match('#^(.*?)(?:-(\d+x\d+))?\.(\w+)\Z#',$file,$filebits)) {
	    switch (strtolower($filebits[3])) {
	    case 'jpeg':
	    case 'jpg':
	    case 'png':
	    case 'gif':
	      if (strlen($filebits[2])) { // resized image (NOT pdf)
		$pic_size_info[$filebits[1]][$filebits[2]] = $afile;
	      } // and fall through:
	    case 'pdf': 
	      if (strlen($filebits[2]) ==0 ) { // Picture name without path or extension
		$picked_files[] = $filebits[1]; 
		$pic_size_info[$filebits[1]]['full'] = $afile;
		$pic_size_info[$filebits[1]]['pic_full_path'] = path_join($full_path,$afile); // ~~~~ TEST
	      }
	      break;
	    }
	  }
	}
	closedir($dir);
      }
      $cached_pic_size_info[$full_path] = $pic_size_info;
    }

    // Sort list, then apply Include list, per defined filters.
    $picked_files = apply_filters('autonav_pick_files', $picked_files, $attr, $pic_size_info);
    if (!is_array($picked_files))
			return ($pics_info);

    // Build preliminary pics_info array:
    $pics_info = array();
    foreach ($picked_files as $pic_base) {
      if (strlen($pic_size_info[$pic_base]['full'])) // we have a full-size image
	$pics_info[] = array('pic_base' => $pic_base);
    }

    // Choose from available sizes, or create resized images.
    $pics_info = apply_filters('autonav_get_thumbnails', $pics_info, $attr, $pic_size_info);
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

function pic_info_for($attr, $id, $attach_info) {
  $attached_pic = get_attached_file($id);
  if ($attached_pic == '') return; // cannot find the attachment
  $pic_info = create_images_for($attr, $attached_pic);
  if (is_array($pic_info)) {
    $post_info = get_post($id); // the attachment's post
    $pic_info['id'] = $id;
    // Attachment texts
    $pic_info['caption'] = $post_info->post_excerpt;
    $pic_info['description'] = $post_info->post_content;
    $pic_info['title'] = $post_info->post_title;
    if (isset($attach_info['menu_order']))
      $pic_info['menu_order'] = $attach_info['menu_order'];

    $alt = get_post_meta($id, '_wp_attachment_image_alt', true);
    $pic_info['attpage'] = get_attachment_link($id);
    if(count($alt)) $pic_info['alt_text'] = $alt; // Attachment: Alternate Text

    return $pic_info;
  }
  return;
}

/* **** postid handlers **** */

function get_actual_pid ($pids) {
  /* 
  Handle the 'include' list which autonav_get_postid_modifiers()
  (below) splits from the postid="" parameter. Permit list of ids,
  post-slugs, and page-paths
  */
  $pids_array = explode(',',$pids);
  if (is_array($pids_array)) {
    foreach ($pids_array as &$pid) {
      if (strlen($pid) && !is_numeric($pid)) {
	$get_obj = get_posts("name={$pid}"); // first, try post by slug
	if (count($get_obj)) {
	  $pid = $get_obj[0]->ID;
	} else {	       // then page with full path (not merely the slug!)
	  $get_obj = get_page_by_path($pid);
	  $pid = $get_obj->ID;
	}
      }
    }
    return $pids_array;
  }
  return array();
}

function autonav_get_postid_modifiers ($attr, $param = 'include') {
  /* Pre-process the postid= parameter, in the form
     "include,include,param1:param1val,param1val, param2:param2val,param2val"
  */
  $parsed_bits = array();
  $bits = explode(',',$attr['postid']);
  foreach ($bits as $bit) {
    $bitvalues = explode(':',$bit,2);
    if (!empty($bitvalues[1])) {
      $param = trim(array_shift($bitvalues));
      if ($param == 'cat') $param = 'category'; // shortcut
    }
    $parsed_bits[$param][] = trim($bitvalues[0]);
  }
  return $parsed_bits;
}

/* ********************** */

function get_images_attached($attr, $pids, $limit) {
  global $post;

  $query = array();
  $child_pages = array();
  $attr['postid'] = $pids;
  $selectors = autonav_get_postid_modifiers($attr);
  foreach ($selectors as $key => $avalue) {
    $value = implode(',',$avalue);
    switch ($key) {
    case 'include':
      if (empty($value)) $value = $post->ID;
      $child_pages = get_actual_pid($value);
    case 'category': // attachments don't support
      break;
    case 'tag':
    case 'tags':
      // Support taxonomy created by the 
      // http://wordpress.org/extend/plugins/media-tags/ plugin
      $query['tax_query'] = array(array('taxonomy' => 'media-tags',
					'field' => 'slug',
					'terms' => explode(',',$value)));
      break;
    case 'author': // NOTE: WP creates but no default way to edit for attachments
      $value = preg_replace('#\*#',$post->post_author,$value);
      $query['authors']=$value;
      break;
    case 'status':
      $value = preg_replace('#\*#',$post->post_status,$value);
      $query['post_status']=$value;
      break;
    default: 	   // NOTE: attachments don't by default support custom fields
      $query['meta_key']=$type;
      $query['meta_value']=$value;
    }
  }
  // NOTE: Possibly use $attr['orderby'] directly at risk of breaking backwards compability
  $query['orderby'] = strtolower($attr['order']) == 'rand' ? 'rand' : 
    empty($attr['orderby']) ? 'menu_order' : strtolower($attr['orderby']);
  $query['order'] = strtolower($attr['order']) == 'desc' ? 'desc' : 'asc';
  $query['post_status'] = 'inherit'; // prevents finding draft or private
  $query['numberposts'] = $limit >= 1 ? $limit : -1;
  $query['post_type'] = 'attachment';
  $query['post_mime_type'] = array('image','application/pdf'); // test extension ~~~~

  $pics_info = array();

  if (empty($child_pages)) {
    $child_pages[0] = $post->ID;
  }
  foreach ($child_pages as $pid) {
    $query['post_parent'] = $pid;
    $attachments = get_posts($query);
    if (empty ($attachments)) return $pics_info;
    foreach ($attachments as $attach_info) {
      if ($attach_info->menu_order < -100) continue; // permit disabling images via menu_order
      $pic_info = pic_info_for($attr,$attach_info->ID, array());
      if (is_array($pic_info))
	$pics_info[] = $pic_info;
    }
  }

  return $pics_info;
}

/* ********************** */

function get_attachments($attr, $pid, $limit) {
  global $post;

  if (function_exists('attachments_get_attachments')) {
    $pids = get_actual_pid(strlen($pid) ? $pid : $post->ID);
    if (is_array($pids)) {
      $attachments = attachments_get_attachments($pids[0]);
      $pics_info = array();

      // Title, caption override?
      foreach ($attachments as $attach_info) {
	$pic_info = pic_info_for($attr,$attach_info['id'], $attach_info);
	if (is_array($pic_info))
	  $pics_info[] = $pic_info;
      }
    }
    return $pics_info;
  }
}

/* **************************
   Thumbnail helper functions
   **************************
*/

function autonav_thumb_featured ( $pic_info, $attr, $post ) {
  // Use featured image
  if (empty($pic_info)) {
    /* NOTE: below is undef without add_theme_support('post-thumbnails'); in functions.php */
    if (function_exists('get_post_thumbnail_id')) { 
      $tid = get_post_thumbnail_id($post->ID);
      if ($tid) {
	$pic_info = pic_info_for($attr, $tid, array());
      }
    }
  }
  return $pic_info;
}
add_filter('autonav_thumb', 'autonav_thumb_featured', 10, 4);

function autonav_thumb_specified( $pic_info, $attr, $post ) {
  if (empty($pic_info)) {
    $ximg = get_post_meta($post->ID, 'subpage_thumb', 1);
    if ($ximg != '') { // Specified exact thumbnail image
      if ( preg_match( '|^https?://|i', $ximg ) ) {
	$pic_info['image_url'] = $ximg; // as explicit URL
	$pic_info['class'] = $attr['class'].'-image';
      } else {
	// local file... assume full-size picture given, and automagically create thumbnail
	$pic_info = create_images_for($attr, $ximg);
      }
    }
  }
  return $pic_info;
}
add_filter('autonav_thumb', 'autonav_thumb_specified', 20, 4);

function autonav_thumb_attached( $pic_info, $attr, $post ) {
  if (empty($pic_info)) {
    $attr['order'] = 'ASC';
    $pics = get_images_attached($attr, $post->ID, 1);
    if (is_array($pics)) {
      $pic_info = $pics[0]; // should be exactly one
    }
  }
  return $pic_info;
}
add_filter('autonav_thumb', 'autonav_thumb_attached', 30, 4);

/* ********************** */

function get_pics_info($attr, $pages) {
  // Called with a list of either posts or pages (or even custom post-types).
  $wp_dir = wp_upload_dir(); // ['basedir'] is local path, ['baseurl'] as seen from browser
  $disp_pages = array();
  $picpages_only = $attr['pics_only'];

  foreach ($pages as $page) {
    $pic_info = array();
    $pic_info = apply_filters( 'autonav_thumb', $pic_info, $attr, $page );

    if ((!$picpages_only) || $pic_info['image_url'] != '') {
      $pic_info['linkto'] = $attr['linkto'];
      $pic_info['page'] = $page;
      switch($pic_info['linkto']) {
      case 'pic':
      case 'file': // compatibility with [gallery]
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

function autonav_wl_standardize_orderby ($orderby) {
  // Prefix with post_ to match database table column names
  if ((strpos($$orderby, 'post_') === false) && 
      in_array($orderby, 
	       array('author', 'date', 'modified', 'parent', 'title', 'excerpt', 'content'))) {
    $orderby = 'post_' . $orderby;
    // Not requiring post_ prefix: comment_status, comment_count, menu_order, ID
  }
  return $orderby;
}



/* **** Page and Post handlers **** */

function get_subpages ($attr) {
  global $post;

  $query = array();
  $child_pages = array();
  $selectors = autonav_get_postid_modifiers($attr);
  foreach ($selectors as $key => $avalue) {
    $value = implode(',',$avalue);
    switch ($key) {
    case 'include':
      $child_pages = get_actual_pid($value);
      break;
    case 'category': // pages don't support these
    case 'tag':
    case 'tags':
      break;
    case 'author':		/* postid="author:4" */
      $value = preg_replace('#\*#',$post->post_author,$value);
      $query['authors']=$value;
      break;
    case 'status':
      $value = preg_replace('#\*#',$post->post_status,$value);
      $query['post_status']=$value;
      break;
    default: 			/* postid="custom-field:value" */
      $query['meta_key']=$type;
      $query['meta_value']=$value;
    }
  }

  $my_children = 0;
  $home_base = 0;
  if ($attr['siblings']) {
    if ($post->post_parent) { // children of our parent
      $child_pages[] = array($post->post_parent);
    }
    if (!$attr['self']) { // add ourselves to exception list
      $attr['exclude'] = $post->ID . ($attr['exclude'] !== '' ? ','.$attr['exclude'] : '');
    }
  } else {
    if (!$child_pages[0]) { // no postid: select our children.
      // Are we the static home page?
      $home_base = ( (get_option('show_on_front') == 'page') &&
                     (get_option('page_on_front') == $post->ID ) );
      $child_pages = array($post->ID);
      $my_children = 1;
    }
  }
  $query['echo'] = 0;
  $query['title_li'] = 0;
  $query['sort_column'] = autonav_wl_standardize_orderby($attr['orderby']);

  if (strlen ($attr['exclude']))
    $query['exclude'] = $attr['exclude'];
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

    if ($home_base && !count($these_pages)) {
      // Static home page, with no children. Look for siblings: children of page '0'.
      $query['child_of'] = 0;
      $query['parent'] = 0;
      if (!$attr['self']) { // except ourself (will the "real" homepage please stand up)             
        $query['exclude'] = $post->ID . ($query['exclude'] !== '' ? ','.$query['exclude'] : '');
      }
      $these_pages = & get_pages($query);
    }

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

function get_selposts($attr) {
  global $post;
  $query = array();

  $selectors = autonav_get_postid_modifiers($attr);
  foreach ($selectors as $key => $avalue) {
    $value = implode(',',$avalue);
    $numeric_value = preg_match('#^[-0-9,]+#', $value); /* accept '5,-3' as all numeric */
    switch ($key) {
    case 'include':
      $query[$key] = $value;
      break;
    case 'tag__and':
    case 'tag__in':
    case 'tag__not_in':
      if (!$numeric_value)
	$key = preg_replace('#tag_#', 'tag_slug_', $key);
    case 'tag_slug__and':
    case 'tag_slug__in':
      $query[$key] = $avalue;
      $key = 'post_tag';
      break;
    case 'tag':
    case 'tags':
      $query[$key] = $value;
      $key='post_tag';
      break;
    case 'category':
      $value = preg_replace('#\*#',$post->post_category,$value);
      $query[$numeric_value ? 'cat' : 'category_name']=$value;
      $save_tax=$key;
      break;
    case 'category__and':
    case 'category__in':
    case 'category__not_in':
      $value = preg_replace('#\*#',$post->post_category,$value);
    $query[$key]=explode(',',$value);
      $key='category';
      break;
    case 'author':
      $value = preg_replace('#\*#',$post->post_author,$value);
      $query[$numeric_value ? 'author' : 'author_name']=$value;
      break;
    case 'status':
      $value = preg_replace('#\*#',$post->post_status,$value);
      $query['post_status']=$value;
      break;
    default:  // First check for custom taxonomies; otherwise, use custom fields.
      if (taxonomy_exists($key)) {
	$query[$key]=$value;
      } else {
	$query['meta_key']=$key;
	$query['meta_value']=$value;
      }
    }
    if ($key != 'include' && empty($attr['taxonomy']) && strlen($key))
      $attr['taxonomy'] = $key;    // Purely for CSS purposes
  }
  if (!$attr['self']) { // add ourselves to exception list
    $query['exclude'] = $post->ID;
  }
  if (preg_match('#^posts\s*:\s*(.*)#', $attr['display'], $value)) {
    $query['post_type'] = $value[1]; // custom post type
  }
  if ($attr['count']) { $query['numberposts'] = $attr['count']; }
  if ($attr['start']) { $query['offset'] = $attr['start']; }

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

function prepare_picture (&$pic) {  /* (! modifies $pic !) */
  $alt_text = strlen($pic['alt_text']) ? $pic['alt_text'] : $pic['title'];
  if (strlen($pic['error'])) {
    $error_print = $pic['pic_full_url'];
  } else {
    if (strlen($pic['image_url'])) {
      $pic['content'] = 
	an_create_tag('img', array('src' => $pic['image_url'], 'alt' => $alt_text,
				   'width' => $pic['width'], 'height' => $pic['height'],
				   'class' => $pic['class']));
    } else {
      $pic['error'] = 'Missing image';
      $error_print = apply_filters('autonav_missing_image',__($pic['error']), $pic);
      if (is_object($pic['page'])) {
	$pic['error'] .= __(' for postid=').$pic['page']->ID;
      }
    }
  }
  if (strlen($pic['error'])) {
    $pic['content'] = an_create_tag('span', array('class' => implode(' ',array('autonav-error', $pic['class'])))) .
      "<!-- {$pic['error']} -->{$error_print}</span>\n";
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

/* **** Output helpers **** */

function an_create_output_list_item ($html, $class, $pic, $attr) {
  // Outputs the list-item element for a single post/page
  $has_link = strlen($pic['permalink']) > 0;

  if ($has_link) {
    $html .= an_create_tag('a', array('href' => $pic['permalink']));
  }
  $html .= $pic['title'];
  if ($attr['thumb']) {
    $html .= an_create_tag('span', array('class' => "{$class}-list-image")) .
      $pic['content'] . '</span>';
  }
  if ($has_link) {
    $html .= "</a>";
  }
  return $html;
}

function an_create_output_table_picture ($html, $class, $pic, $attr) {
  // Outputs the picture in a table-data element for a single post/page
  $anchor = array('href' => $pic['permalink']);
  if ($pic['linkto'] == 'pic') 
    $anchor['rel'] = $attr['_img_rel'];
  // Put title in tag, not plaintext?
  if (strlen($pic['title']) && ! $attr['titles'])
    $anchor['title'] = $pic['title'];

  $has_link = strlen($pic['permalink']) > 0;
  if ($has_link) {
    $html .= an_create_tag('a', $anchor);
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
    $html .= an_create_tag('p',array('class' => "{$class}-text"));
    if ($has_link) {
      $html .= an_create_tag('a',array('href' => $pic['permalink']));
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
    $html .= an_create_tag('p', array('class' => "{$class}-excerpt")) . "{$pic['excerpt']}</p>\n";
  }
  return $html;
}

function an_create_page_links($html, $class, $total_pages, $cur_page) {
  $html .= an_create_tag('p', array('class' => "{$class}-pages}"));
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

add_filter('autonav_create_list_item', 'an_create_output_list_item', 10, 4);
add_filter('autonav_create_list_item', 'an_create_output_excerpt', 20, 4);
add_filter('autonav_create_table_item', 'an_create_output_table_picture', 10, 4);
add_filter('autonav_create_table_item', 'an_create_output_table_text', 15, 4);
add_filter('autonav_create_table_item', 'an_create_output_excerpt', 20, 4);
add_filter('autonav_create_page_links', 'an_create_page_links', 10, 4);

/* **** Main output function **** */

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
      $html .= apply_filters('autonav_create_list_item',
			     '<li class="' . $class . '-item">', $class, $pic, $attr) . "</li>\n";
    }
    $html .= "</ul>";
  } else {  // Produce table output
    $viewer = $attr['imgrel'];
    if (strpos($viewer, '*')) {
      $viewer = str_replace( '*', ($attr['group']!='') ? '['.$attr['group'].']' : '', $viewer);
    }
    $attr['_img_rel'] = $viewer;
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
      $html .= apply_filters('autonav_create_table_item',
			     '<td class="' . $class . '-cell">', $class, $pic, $attr) . "</td>\n";

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

function autonav_wl_shortcode($attr) {
  global $post;

  // NOTE: This function can be added as a filter to override the standard Gallery shortcode.
  // In that case, this function may return an empty string to restore default behavior.

  if (!function_exists('imagecreatefromjpeg')) {
    print ("<span style=\"color:red; font-weight: bold;\">ERROR:</span> imagecreatefromjpeg() must be installed. On Ubuntu, \
use: <b><tt>apt-get install php5-gd</tt></b> Use yum on RedHat/CentOS, or similarly for your system.<br>\n"); 
    return;
  }

  if (!empty($attr['id'])) {
    $attr['postid'] = $attr['id']; // compatibility with WP gallery shortcode
  }
  if (!empty($attr['link'])) {
    $attr['linkto'] = $attr['link']; // compatibility with WP gallery shortcode
  }
  $options = get_option('autonav_wl');

  // Default values come from saved configuration
  $attr['order'] = strtolower($attr['order']); // so we can make 'desc' default for posts, regardless of option setting
  $attr = (shortcode_atts($options, $attr));

  // display can be: 'images' or 'list' (for child pages), '/folder' for images from directory,
  // or the default 'attached' for table of attached images

  if (in_array($attr['size'],get_intermediate_image_sizes())) {
    $size_list = image_constrain_size_for_editor(AUTONAV_FORCE_CONSTRAIN, 
						 AUTONAV_FORCE_CONSTRAIN,
						 $attr['size']);
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
    switch (trim($o)) {
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

  // Permit plugin/theme to override here
  $attr = apply_filters('autonav_pre_select', $attr, $display_options);

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
    $attr['start'] = 0;		// start,count already handled by get_selposts
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

add_shortcode('autonav','autonav_wl_shortcode');

/* ****
   Plugin Options handling
   **** */

// White-list plugin our option page

function autonav_wloptions_init(){
  register_setting( 'autonav_wloptions_options', 'autonav_wl', 'autonav_wloptions_validate' );
}
add_action('admin_init', 'autonav_wloptions_init' );

function autonav_wl_options() {
  if (is_admin())
    include 'autonav-wl-options.php';
}
  
function autonav_wl_activate_admin_options() {
  $my_info = pathinfo(__FILE__);
  $my_name =  basename(__FILE__,'.'.$my_info['extension']);
  add_submenu_page('options-general.php','AutoNav Options','AutoNav','manage_options',
		   $my_name, 'autonav_wl_options');
}
 
add_action('admin_menu', 'autonav_wl_activate_admin_options');

// Run at plugin activation time: Force update of preferences.
function autonav_wlactivate ($input) {
  // Retrieve current options (if any), call our validate function, and store.
  $options = get_option('autonav_wl');
  $options = autonav_wloptions_validate($options);
  update_option('autonav_wl', $options);
}
register_activation_hook( __FILE__, 'autonav_wlactivate' );


/* ********************** */

/*
  Options validation. Here because our --options.php not loaded
  during post-input validation phase.  Returns a sanitized array.
*/

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


