<?php
/*
Plugin Name: Autonav Content Deferral
Description: Defers Autonav content when display type is preceded with an asterisk ('*')
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

global $an_deferred_content;

function an_pre_create_page_links($html, $class, $total_pages, $cur_page) {
  global $an_deferred_content;
  $an_deferred_content = $html;
  return '';
}

function an_post_create_page_links($html, $class, $total_pages, $cur_page) {
  return $html;
}

function an_post_html($html, $attr) {
  global $an_deferred_content;
  if (strlen($an_deferred_content)) {
    return $html; // Page link filters happened during autonav; do nothing
  } else {
    $an_deferred_content = $html; // No page links. Defer all content.
    return '';
  }
}

function autonav_defer_shortcode($attr) {
  global $an_deferred_content;
  return $an_deferred_content;
}

add_shortcode('autonav_content','autonav_defer_shortcode');

// Here we decide whether to engage the above logic for any given invocation of [autonav]

function an_defer_decide ($attr, $display_options) {
  global $an_deferred_content;
  $an_deferred_content = '';

  // Defers content when the [autonav] display type is preceded with an asterisk.
  // 'display="*table"' becomes 'display="table"' but the content is deferred;
  // only the page numbers are output.
  // Then the code [autonav_content] is placed elsewhere in the page or theme
  // and the deferred content is displayed there.
  // NOTE: If no page links are created, the filter autonav_create_page_links
  // will not happen. But the autonav_html filter always runs, at the end of
  // create_output.
  if (substr($attr['display'],0,1) == '*') {
    add_filter('autonav_create_page_links', 'an_pre_create_page_links', 1, 4);
    add_filter('autonav_create_page_links', 'an_post_create_page_links', 100, 4);
    add_filter('autonav_html', 'an_post_html', 100, 2);
    $attr['display'] = substr($attr['display'],1); 
  } else {
    remove_filter('autonav_create_page_links', 'an_pre_create_page_links', 1, 4);
    remove_filter('autonav_create_page_links', 'an_post_create_page_links', 100, 4);
    remove_filter('autonav_html', 'an_post_html', 100, 2);
  }
  return $attr;
}

add_filter('autonav_pre_select', 'an_defer_decide', 5, 2);

?>