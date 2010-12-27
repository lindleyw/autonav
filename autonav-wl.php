<?php
/*
Plugin Name: Autonav Image Table Based Site Navigation
Plugin URI: http://www.wlindley.com/webpage/autonav
Description: Displays child pages in a table of images or a simple list; also displays attached images, or images from a subdirectory under wp-uploads, in a table, with automatic resizing of thumbnails and full-size images.
Author: William Lindley
Version: 1.3.4
Author URI: http://www.wlindley.com/
*/

/*  Copyright 2008-2010 William Lindley (email : wlindley -at- wlindley -dot- com)

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
					       $to_width, $to_height, $attr['crop']);
  }

  if (is_array($resample_params)) {

    list($dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h) = $resample_params;
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

    // Create image in memory:
    imagecopyresampled( $to_image, $from_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
    imagejpeg($to_image, $to_file_path, 90);  // Creates file

    $attr['pic_'.$prefix] = $to_file;
    $attr['pic_'.$prefix.'_path'] = $to_file_path;
    $attr['pic_'.$prefix.'_url']  = $to_file_url;
  }

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

    // Remove local path prefix (folder part remains)
    $sorted_files = array();
    foreach ($files as $afile) {
      $sorted_files[] = str_replace(trailingslashit($wp_dir['basedir']),
				    '',$afile);
    }

    // Sort names ('order' is ASC or DESC)
    if (strtolower($attr['order']) == 'desc') {
      rsort($sorted_files, SORT_STRING);
    } elseif (strtolower($attr['order']) == 'rand') {
      shuffle($sorted_files);
    } else {
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

    $full_width = get_option('large_size_w'); $full_height = get_option('large_size_h');

    // For each full size image:
    // I. Find or create thumbnail:
    //    if cropping, look for exact cropped size
    //     else get its size and call wp_constrain_dimensions and look for exactly that size
    // II. Find or create constrained full-size image
    //     

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
      $size_params = image_resize_dimensions($pic_info['fullwidth'], $pic_info['fullheight'],
					     $attr['width'], $attr['height'], $attr['crop']);

      $pic_info['thumb_resample'] = $size_params;
      $pic_info['thumbwidth'] = $size_params[4]; // new width and height, whether cropped or scaled-to-fit
      $pic_info['thumbheight'] = $size_params[5];
      $thumb_size = $size_params[4] . 'x' . $size_params[5];
      $pic_info['pic_thumb'] = $pic_size_info[$apic_key][$thumb_size];
      if ($pic_info['pic_thumb'] == '') {
	// properly sized full image does not exist; create it
	resize_crop($pic_info, 'thumb'); // creates ['pic_thumb'], ['pic_thumb_url'] in $pic_info
      } else {
	$pic_info['pic_thumb_url'] = trailingslashit($wp_dir['baseurl']) . $pic_info['pic_thumb'];
      }
      $pic_info['image'] = $pic_info['pic_thumb']; // Copy thumbnail properties into image properties
      $pic_info['image_url'] = $pic_info['pic_thumb_url'];
      $pic_info['width'] = $pic_info['thumbwidth'];
      $pic_info['height'] = $pic_info['thumbheight'];
      $pic_info['linkto'] = $attr['linkto'];

      $pics_info[] = $pic_info;
    }
  }

  return($pics_info);

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
    $pic_info['caption'] = $post_info->post_excerpt; // Attachment: Caption
    $pic_info['description'] = $post_info->post_content; // Attachment: Description
    $pic_info['title'] = $post_info->post_title; // Attachment: Title

    $alt = get_post_meta($id, '_wp_attachment_image_alt', true);
    if(count($alt)) $pic_info['alt_text'] = $alt; // Attachment: Alternate Text

    return $pic_info;
  }
  return;
}

/* ********************** */

function get_images_attached($attr, $pid, $limit) {
  global $post;

  $wp_dir = wp_upload_dir();
  $pics_info = array();
  if ($pid == 0) {
    $pid = $post->ID;
  }
  $order = strtolower($attr['order']) == 'desc' ? 'desc' : 'asc';
  // NOTE: Possibly use $attr['orderby'] directly at risk of breaking backwards compability
  $orderby = strtolower($attr['order']) == 'rand' ? 'rand' : 'menu_order';

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

  return $pics_info;
}

/* ********************** */

function get_attachments($attr, $pid, $limit) {
  global $post;

  if (function_exists('attachments_get_attachments')) {
    if ($pid == 0) {
      $pid = $post->ID;
    }
    $attachments = attachments_get_attachments($pid);
    $pic_info = array();

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
  $wp_dir = wp_upload_dir(); // ['basedir'] is local path, ['baseurl'] as seen from browser
  $disp_pages = array();
  $picpages_only = ($attr['display'] == 'list') ? 0 : $attr['pics_only'];

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
      $pic_info['linkto'] = strlen($attr['linkto'])?$attr['linkto']:'page';
      $pic_info['page'] = $page;
      $pic_info['permalink'] = ($pic_info['linkto'] == 'pic') ?
	$pic_info['pic_full_url']: get_permalink($page->ID);

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

  $child_pages = explode(',',$attr['postid']);
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

  $pages = array();
  foreach ($child_pages as $child_of) {
    $query = "child_of=$child_of&echo=0&title_li=0&sort_column=" . $attr['orderby'];
    if (strlen ($attr['exclude'])) {
      $query .= "&exclude=" . $attr['exclude'];
    }
    $these_pages = & get_pages($query);
    if (count ($these_pages)) {
      foreach ($these_pages as $subpage) {
        if (in_array($subpage->post_parent, $child_pages)) {  // first-generation children only
          array_push($pages, $subpage);
        }
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
  if (strtolower($attr['order']) =='rand') {
    shuffle($pages);
  } elseif (strtolower($attr['order']) =='desc') {
    $pages = array_reverse($pages);
  }
  return get_pics_info($attr, $pages);

}

/* ********************** */

function get_selposts($attr) {
  $postids = $attr['postid'];

  if (preg_match('#^(\w+)\s*:\s*(.*?)\s*$#', $postids, $selection)) {
    $type = strtolower($selection[1]);
    $value = $selection[2];
    $numeric_value = preg_match('#^[-0-9,]+#', $value); // '5,-3' counts as all numeric here

    if ($type == 'tag') {
      $query = "tag=$value";
    } elseif ($type == 'author') {
      $query = $numeric_value ? "author=$value" : "author_name=$value";
    } else {   			// assume $type == 'category')
      $query = $numeric_value ? "cat=$value" : "category_name=$value";
    }
  } else {
    $query = "include=$postids";
  }
  if ($attr['count']) { $query .= '&numberposts=' . $attr['count']; }
  if ($attr['start']) { $query .= '&offset=' . $attr['start']; }

  // possible ordering values (date, author, title,...) listed in http://codex.wordpress.org/Template_Tags/query_posts
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
  if (strtolower($attr['orderby']) == 'postmash') { // PostMash plugin actually uses post menu_order
    $attr['orderby'] = 'menu_order';
  }
  if ($attr['order']) { $query .= '&order=' . $attr['order']; } 
  if ($attr['orderby']) { $query .= '&orderby=' . $attr['orderby']; } 
  $these_posts = get_posts($query);

  if (count($these_posts) == 0) {
    return;
  }
  return get_pics_info($attr, $these_posts);
}

/* ********************** */

function prepare_picture (&$pic) {
  $alt_text = strlen($pic['alt_text']) ? $pic['alt_text'] : $pic['title'];
  $pic['content'] = '<img src="' . $pic['image_url'] . '" alt="'. esc_attr($alt_text) . '" ' .
    image_hwstring($pic['width'],$pic['height']) . ' />';
  if ($pic['permalink'] == '') {
    if ($pic['linkto'] == 'pic') {
      $pic['permalink'] = $pic['pic_full_url']; // link to fullsize image
    }
  }
}

/* ********************** */

function create_output($attr, $pic_info) {

  if ($attr['start'] > 0) {
    $pic_info = array_slice($pic_info, $attr['start']);
  }
  if ($attr['count'] > 0) {
    $pic_info = array_slice($pic_info, 0, $attr['count']);    
  }

  if (!is_array($pic_info)) { // nothing to do
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
  if ($class == '') {
    $class = 'subpages';
  }

  if ($attr['display'] == 'list' || $attr['list']) { // Produce list output
    $html = '<ul class="' . $class . '-list">';
    foreach ($pic_info as $pic) { // well, really page not picture
      $html .= '<li class="' . $class . '-item"><a href="' . $pic['permalink'] . '">'. $pic['title'];

      if ($attr['show_thumb']) {
	prepare_picture($pic);
	$html .= '<span class="' . $class . '-list-image">' . $pic['content'] . '</span>';
      }

      $html .= "</a>";
      if ($attr['excerpt'] && strlen($pic['excerpt'])) {
	$html .= '<p class="' . $class . '-excerpt">' . $pic['excerpt'] . "</p>\n";
      }

      $html .= "</li>\n";
    }
    $html .= "</ul>";
  } else {  // Produce table output
    $viewer = $attr['imgrel'];
    if (strpos($viewer, '*')) {
      $viewer = str_replace( '*', ($attr['group']!='') ? '['.$attr['group'].']' : '', $viewer);
    }
    $img_rel = strlen($viewer) ? " rel=\"$viewer\"" : '';
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

      $my_img_rel = ($pic['linkto'] == 'pic') ? $img_rel : '';
      $html .= '<td class="' . $class . '-cell">';
      $html_after = ''; $title_text = '';
      if (strlen($pic['title'])) {
        if ($attr['titles']) {
	  $html_after = '<p class="' . $class . '-text"><a href="' . $pic['permalink'] . '">' . $pic['title'] . "</a></p>";
	} else {
	  $title_text = ' title="' . esc_attr($pic['title']) . '"';
	}
      }
      $html .= '<a href="' . $pic['permalink'] . "\" $my_img_rel$title_text>" . $pic['content'] . "</a>$html_after";

      if ($attr['excerpt'] && strlen($pic['excerpt'])) {
	$html .= '<p class="' . $class . '-excerpt">' . $pic['excerpt'] . "</p>\n";
      }
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
    $html .= '<p class="' . $class . '-pages">';
    // Possibly permit override of 'next_text', 'prev_text', etc. - see /wp-includes/general_template.php
    $paginate_args = array('base' => get_permalink() . '%_%',
			   'total' => $total_pages, 'current' => $cur_page, 'show_all' => 1);
    $mybase = get_permalink();
    if (strpos($mybase,'?') !== FALSE) { $paginate_args['format'] = '&page=%#%'; } // append rather than start arg
    $html .= paginate_links($paginate_args);
    $html .= '</p>';

  }
  return $html;
}

/* ********************** */

add_shortcode('autonav','autonav_wl_shortcode');

function autonav_wl_shortcode($attr) {
  global $post;

  // NOTE: This function can be added as a filter to override the standard Gallery shortcode.
  // In that case, this function may return an empty string to restore default behavior.

  $options = get_option('autonav_wl');

  // Default values come from saved configuration
  $attr['order'] = strtolower($attr['order']); // so we can make 'desc' default for posts, regardless of option setting
  $attr = (shortcode_atts($options, $attr));

  // display can be: 'images' or 'list' (for child pages), '/folder' for images from directory,
  // or the default 'attached' for table of attached images

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

  $attr['orderby'] = sanitize_sql_orderby($attr['orderby']);
  $display_options = explode(',', $attr['display']);
  $attr['display'] = array_shift($display_options);
  foreach ($display_options as $o) {
    if (strpos($o, 'title') !== false) $attr['titles'] = 1;
    if (strpos($o, 'excerpt') !== false) $attr['excerpt'] = 1;
    if (strpos($o, 'thumb') !== false) $attr['show_thumb'] = 1;
    if (strpos($o, 'siblings') !== false) $attr['siblings'] = 1;
    if (strpos($o, 'self') !== false) $attr['self'] = 1;
    if (strpos($o, 'list') !== false) $attr['list'] = 1;
    if (strpos($o, 'image') !== false) $attr['linkto'] = 'pic';
  }
  if (($attr['display'] == 'list') || ($attr['display'] == 'images')) {
    $pic_info = get_subpages($attr);
  } elseif (substr($attr['display'],0,6) == 'attach') {
    $attr['linkto'] = 'pic';
    $post_id = $attr['postid'];
    if (!$post_id) {
      $post_id = $post->ID;
    }
    if ($attr['display'] == 'attachments') {
      $pic_info = get_attachments($attr, $post_id, 0);
    } else {
      $pic_info = get_images_attached($attr, $post_id, 0);
    }
  } elseif ($attr['display'] == 'posts') {
    $pic_info = get_selposts($attr);
    $attr['start'] = 0;		// start,count handled inside get_selposts query
    $attr['count'] = 0; 
  } else {
    $attr['linkto'] = 'pic';
    $pic_info = get_images_from_folder($attr);
  }
  $html = create_output($attr, $pic_info);

  return $html;
}

/* ********************** */

// This goes into table wp_options as follows:
//
//   option_id = <database dependent>
//   blog_id = <database dependent, for Wp-MU>
//   option_name = 'autonav_wl'
//   option_value = 'a:2:{s:7:"option1";i:1;s:8:"sometext";s:17:"crack &amp; shine";}'
//
// Note the value is stored serialized, with HTML encoded strings, and that
// get_option() will unserialize the string and return an array.

// Init plugin options to white list our options
function autonav_wloptions_init(){
  register_setting( 'autonav_wloptions_options', 'autonav_wl', 'autonav_wloptions_validate' );
}

/* ********************** */

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
    if ($options['size_small'] == '') { $options['size_small'] = '120x90'; }
    if ($options['size_med'] == '') { $options['size_med'] = '160x120'; }
    if ($options['size_large'] == '') { $options['size_large'] = '240x180'; }
    if ($options['order'] == '') { 
      $options['order'] = 'ASC';
    } else {
      $options['order'] = strtoupper($options['order']); // so we can make 'desc' default for posts, regardless
    }
    if ($options['orderby'] == '') { $options['orderby'] = 'menu_order'; }
?>
<table class="form-table">
<tr><th colspan="2"><h3>When listing child pages</h3></th></tr>
<tr valign="top"><th scope="row">Show only pages with thumbnails?</th>
<td><input name="autonav_wl[pics_only]" type="checkbox" value="1" <?php checked('1', $options['pics_only']); ?> /></td>
</tr>
<tr valign="top"><th scope="row">Use sort_column</th>
<td><input name="autonav_wl[orderby]" type="text" value="<?php echo $options['orderby']; ?>" />
(<a href="http://codex.wordpress.org/Template_Tags/wp_list_pages#Parameters">List of possible values</a>
 <small><em>from wordpress.org</em></small> )</tr>

<tr valign="top"><th scope="row">List of page IDs to exclude</th>
<td><input name="autonav_wl[exclude]" type="text" value="<?php echo $options['exclude']; ?>" /></tr>

<tr><th colspan="2"><h3>Displaying images</h3></th></tr>
<tr valign="top"><th scope="row">Display Titles Under Images</th>
<td><input name="autonav_wl[titles]" type="checkbox" value="1" <?php checked('1', $options['titles']); ?> /></td>
</tr>
<tr valign="top"><th scope="row">Size of images</th>
<td><input type="text" name="autonav_wl[size]" value="<?php echo $options['size']; ?>" />
"auto" for below, or as "300x200"</td>
</tr>

<tr valign="top"><th scope="row">Default number of columns</th>
<td><input name="autonav_wl[columns]" type="text" value="<?php echo $options['columns']; ?>" /></td>
</tr>

<Tr valign="top"><th scope="row">Automatic image sizing</th>
<td>
<table border="1">
<tr>
<td>
<input name="autonav_wl[col_large]" size="2" type="text" value="<?php echo $options['col_large']; ?>" /><br />
or fewer columns,<br />use size<br />
<input name="autonav_wl[size_large]" size="12" type="text" value="<?php echo $options['size_large']; ?>" />
</td>
<td>
<br />Intermediate number of<br />columns, use size<br />
<input name="autonav_wl[size_med]" size="12" type="text" value="<?php echo $options['size_med']; ?>" />
</td>
<td>
<input name="autonav_wl[col_small]" size="2" type="text" value="<?php echo $options['col_small']; ?>" /><br />
or more columns,<br />use size<br />
<input name="autonav_wl[size_small]" size="12" type="text" value="<?php echo $options['size_small']; ?>" />
</td>
</tr>
</table>
</td>
</tr>

<tr valign="top"><th scope="row">Crop images to size?</th>
<td><input name="autonav_wl[crop]" type="checkbox" value="1" <?php checked('1', $options['crop']); ?> />
If unchecked, fit images inside specified size
</td>
</tr>
</tr>

<tr><th colspan="2"><h3>Table controls</h3></th></tr>

<tr valign="top"><th scope="row">Combine rows of images into tables:</th>
<td>
<input name="autonav_wl[combine]" type="radio" value="all" <?php checked('all', $options['combine']); ?> />
All rows in one table.<br />
<input name="autonav_wl[combine]" type="radio" value="none" <?php checked('none', $options['combine']); ?> />
Each row a separate table.<br />
<input name="autonav_wl[combine]" type="radio" value="full" <?php checked('full', $options['combine']); ?> />
All full rows in one table; trailing partial row in separate table.
</td>
</tr>

<tr valign="top"><th scope="row">Default class for tables</th>
<td><input type="text" name="autonav_wl[class]" value="<?php echo $options['class']; ?>" /><br />
Table elements will use this as the prefix for their styles, as <em>class</em>-table,
<em>class</em>-row, <em>class</em>-cell, etc.
</td>
</tr>

<tr valign="top"><th scope="row">Image relation (rel="") tag</th>
<td><input type="text" name="autonav_wl[imgrel]" value="<?php echo $options['imgrel']; ?>" /><br />
<em>Optional.</em> If this tag contains an asterisk * then the optional "group" specifier
(below; but usually specified in the shortcode) will be inserted as [group], as when you wish
to have multiple groups of pictures with a lightbox-style display.
</td>
</tr>

<tr valign="top"><th scope="row">Default image group for above</th>
<td><input type="text" name="autonav_wl[group]" value="<?php echo $options['group']; ?>" /> <em>Usually left blank</em>
</td>
</tr>
<input type="hidden" name="autonav_wl[include]" value="" />
</table>
<p class="submit">
<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
</p>
</form>
</div>
<?php
}

// Sanitize and validate input. Accepts an array, return a sanitized array.
function autonav_wloptions_validate($input) {

  $input['titles'] = ( $input['titles'] == 1 ? 1 : 0 );
  $input['pics_only'] = ( $input['pics_only'] == 1 ? 1 : 0 );
  $input['size'] =  wp_filter_nohtml_kses($input['size']);
  if ($input['size'] == '') { $input['size'] = 'auto'; }
  $input['display'] =  wp_filter_nohtml_kses($input['display']);
  if ($input['display'] == '') { $input['display'] = 'attached'; }
  if ($input['class'] == '') { $input['class'] = 'subpages'; }
  $input['combine'] =  wp_filter_nohtml_kses($input['combine']);
  if ($input['combine'] == '') { $input['combine'] = 'all'; }
  $input['crop'] =  ( $input['crop'] == 1 ? 1 : 0 );  // 1 = crop, 0 = fit
  $input['columns'] =  intval($input['columns']);
  if ($input['columns'] == 0) { $input['columns'] = 3; }
  $input['exclude'] =  wp_filter_nohtml_kses($input['exclude']);
  $input['postid'] = 0;
  $input['start'] = 0;
  $input['count'] = 0;
  if (!isset ($input['paged'])) { $input['paged'] = 0; }
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

?>
