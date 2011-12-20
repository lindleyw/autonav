<?php
/*
Plugin Name: Autonav Menus selector
Description: Select posts, pages, taxonomies, etc., from a custom WordPress menu
Author: William Lindley
Author URI: http://www.saltriversystems.com/
*/

function autonav_menu_selector($pic_info, $attr) {
  $display_args = explode(',',$attr['display']);
  if ($display_args[0] == 'menu') {

    // override display with standard list or images.
    // $attr['list'] set by shortcode handler
    $attr['display'] = ($attr['list']) ? 'list' : 'images';
    $attr['linkto'] = 'none'; // we handle creating the links

    $retrieve_options = array('post_type' => 'nav_menu_item',
			      'post_status' => 'publish',
			      );
    $opts = array('order', 'orderby');
    foreach ($opts as $o) {
      if (!empty ($attr[$o]))
	$retrieve_options[$o] = $attr[$o];
    }
    $menu_items = wp_get_nav_menu_items($attr['postid']);

    $these_posts = array();
    foreach ($menu_items as $m_i) {
      if ($m_i->type == 'post_type' && $m_i->object == 'page') {
	$page = get_page($m_i->object_id);
	if (!empty($page)) {
	  $page->permalink = get_permalink($page->ID);
	  $these_posts[] = $page;
	}
      } elseif ($m_i->type == 'taxonomy') {
	$category = get_term_by('id', $m_i->object_id, $m_i->object);
	if (!empty($category)) {
	  // copy category fields to where get_pics_info() looks
	  $category->post_title = $category->name;
	  $category->permalink = $m_i->url;
	  $these_posts[] = $category;
	}
      } elseif ($m_i->type == 'custom') {
	$custom = $m_i;
	$custom->permalink = $custom->url;
	$these_posts[] = $custom;
      }
    }
  }
  return get_pics_info($attr, $these_posts);

}
add_filter('autonav_select', 'autonav_menu_selector', 25, 2);

function autonav_menu_select_url ($pic_info, $attr, $page) {
  if ($page->permalink)
    $pic_info['permalink'] = $page->permalink;
  return $pic_info;
}
// Run last so as not to prevent built-in filters from finding their thumbnails
add_filter('autonav_thumb', 'autonav_menu_select_url', 100, 3 );



function autonav_menu_select_url2 ($pic_info, $attr, $page) {
  if ($pic_info['permalink'] == 'http://example.com')
    $pic_info['image_url'] = 'http://www.saltriversystems.com/images/srcs.png';
  return $pic_info;
}
add_filter('autonav_thumb', 'autonav_menu_select_url2', 101, 3 );





/*
 * Saves new field to postmeta for navigation
 */
add_action('wp_update_nav_menu_item', 'custom_nav_update',10, 3);
function custom_nav_update($menu_id, $menu_item_db_id, $args ) {
  if ( is_array($_REQUEST['menu-item-custom']) ) {
    $custom_value = $_REQUEST['menu-item-custom'][$menu_item_db_id];
    update_post_meta( $menu_item_db_id, '_menu_item_custom', $custom_value );
  }
}

/*
 * Adds value of new field to $item object that will be passed to     Walker_Nav_Menu_Edit_Custom
 */
add_filter( 'wp_setup_nav_menu_item','custom_nav_item' );
function custom_nav_item($menu_item) {
  $menu_item->custom = get_post_meta( $menu_item->ID, '_menu_item_custom', true );
  return $menu_item;
}

add_filter( 'wp_edit_nav_menu_walker', 'custom_nav_edit_walker',10,2 );
function custom_nav_edit_walker($walker,$menu_id) {
  return 'Walker_Nav_Menu_Edit_Custom';
}

/* This requires a patch which may be incorporated
   into a future version of WordPress, see 
   http://core.trac.wordpress.org/ticket/14414
*/

function autonav_custom_menu_field($item_id, $item, $depth, $args) {
?>
            <p class="field-custom description description-wide">
                <label for="edit-menu-item-custom-<?php echo $item_id; ?>">
    <?php _e( 'Custom' ); ?><br />
                    <input type="text" id="edit-menu-item-custom-<?php echo $item_id; ?>" class="widefat code edit-menu-item-custom" name="menu-item-custom[<?php echo $item_id; ?>]" value="<?php echo esc_attr( $item->custom ); ?>" />
                </label>
            </p>
<?php
}
add_filter('wp_nav_menu_item_custom_fields', 'autonav_custom_menu_field', 10, 4);
