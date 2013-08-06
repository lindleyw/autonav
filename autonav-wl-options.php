<?php
/*
Description: Support for AutoNav
Author: William Lindley
Author URI: http://www.wlindley.com/
License: GPL2
*/
 
if ('autonav-wl-options.php' == basename($_SERVER['SCRIPT_FILENAME'])) {
  die ('Please access through the Dashboard!');
}

if (!is_admin()) {die('This plugin is only accessible from the WordPress Dashboard.');}
  
if (!defined('WP_CONTENT_DIR')) define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
if (!defined('WP_CONTENT_URL')) define('WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
if (!defined('WP_ADMIN_URL')) define('WP_ADMIN_URL', get_option('siteurl') . '/wp-admin');
if (!defined('WP_PLUGIN_DIR')) define('WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins');
if (!defined('WP_PLUGIN_URL')) define('WP_PLUGIN_URL', WP_CONTENT_URL . '/plugins');

if (!class_exists('WL_Config_Form')) {
  include 'wl-config-form.php';
}

add_filter('plugin_action_links', 'autonav_wl_plugin_action_links', 10, 2);

function autonav_wl_plugin_action_links($links, $file) {
    static $this_plugin;

    if (!$this_plugin) {
        $this_plugin = plugin_basename(__FILE__);
    }

    if ($file == $this_plugin) {
        $settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=autonav-wl">Settings</a>';
        array_unshift($links, $settings_link);
    }

    return $links;
}

/* ******************* */

function autonav_o_pre ($options, $option_name, $html_tags) {
  if (!empty($html_tags['heading'])) {
    print '<tr><th colspan="2"><h3>' . $html_tags['heading'] . "</h3></th></tr>\n";
    unset ($html_tags['heading']);
  }
  if (empty($html_tags['title.no_row'])) {
    print '<tr valign="top"><th scope="row">';
    $print_row = 1;
  }
  $p = 'title.prefix';
  if (!empty($html_tags[$p])) {
    print $html_tags[$p];
  }

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
  if ($print_row) {
    print '</th><td>';
  }

  return $html_tags;
}

function autonav_o_post ($options, $option_name, $html_tags) {
  if (empty($html_tags['title.no_row'])) {
    print "</td></tr>\n";
  }
}

/* ********************** */

function autonav_known_params($options) {

  $wp_reg_size_text = '';
  $sizes = autonav_wloptions_sizes();
  foreach ($sizes as $sz_name => $sz_val) {
    $wp_reg_size_text .= "<br /><b><tt>{$sz_name}</tt></b> <em>currently set to</em> " .
      "{$sz_val['x']}x{$sz_val['y']}";
  }

  $option_menu = array('pics_only' => array('heading' => 'When listing child pages','type'=>'checkbox', 'value' => 1,
					    'title' => 'Show only pages with thumbnails?'),
		       'orderby' => array('title' => 'Use sort_column', 'type'=>'text',
					  'text' => '<span style="display: inline-block; vertical-align:top;">from <a href="http://codex.wordpress.org/Template_Tags/wp_list_pages#Parameters" alt="(on wordpress.org)">list of possible values</a>, or<br> <tt>meta:<em>customfieldname</em></tt></span>'),
		       'exclude' => array('title' => 'List of page IDs to exclude', 'type' => 'text'),
		       'titles' => array('heading' => 'Displaying images', 'type'=>'checkbox', 'value' => 1,
					 'title' => 'Display Titles Under Images'),
		       'size' => array('title' => 'Size of images','type'=>'text',
				       'text' => '<span style="display: inline-block; vertical-align: top;"><b><tt>auto</tt></b> for automatic <em>(see below)</em>,<br>explicitly like <b><tt>300x200</tt></b> or a registered size:' . $wp_reg_size_text . '</span>'),
		       'columns' => array('title' =>'Default number of columns', 'type'=>'text'),
		       'col_large' => array('title' => 'Automatic image sizing', 'type' => 'text', 'size' => 2,
					    'text' => 'or fewer columns, use Large Images'),
		       'col_small' => array('type' => 'text', 'size' => 2, 'text' => 'or or more columns, use Small Images'),
		       'size_large' => array('type'=>'text', 'size' => 12, 'text' => 'Large image size'),
		       'size_med' => array('type'=>'text', 'size' => 12, 'text' => 'Intermediate image size'),
		       'size_small' => array('type'=>'text', 'size' => 12, 'text' => 'Small image size'),
		       'crop' => array('heading' => 'Image Cropping', 'type'=>'radio', 'title' => 'Crop images to size?',
				       'choices' => array(0 => 'Fit images inside specified size.<br />',
							  1 =>'Crop to exact size, from center of image.<br />',
							  2 =>' ... from upper-left.<br />', 
							  3 =>' ... from top middle.<br />')),
		       'combine' => array('heading' => 'Table controls', 'type'=>'radio',
					  'title' => 'Combine rows of images into tables:', 
					  'choices' => array('all' => 'All rows in one table.<br />',
							     'none' => 'Each row a separate table.<br />',
							     'full' => 'All full rows in one table; trailing partial row in separate table.')),
		       'class' => array( 'type'=>'text', 'heading' => 'Default class for tables',
					 'text' => '<br />Table elements will use this as the prefix for their styles, as <em>class</em>-table, <em>class</em>-row, <em>class</em>-cell, etc.'),
		       'imgrel' => array('title' => 'Image relation (rel="") tag',
					 'type'=>'text', 'heading' => 'Image Relations',
					 'text' => '<br /><em>Optional.</em> If this tag contains an asterisk * then the optional "group" specifier (below; but usually specified in the shortcode) will be inserted as [group], as when you wish to have multiple groups of pictures with a lightbox-style display.'),
		       'group' => array('title' => 'Default image group for above', 'type'=>'text',
					'text' => '<em>Usually left blank</em>'),
		       'attach_tag' => array('heading' => 'Taxonomy for attachment Tags', 'type'=>'text',
					     'title' => 'Attachment Taxonomies',
					     'text' => 'media-tags <em>for Media tags plugin,</em> attachment_tag <em>for Attachment Taxonomy plugin</em>'),
		       'attach_category' => array( 'type'=>'text', 'title' => 'Taxonomy for attachment Category',
						   'text' => 'attachment_category <em>for Attachment Taxonomy plugin</em>')
		       );
  return ($options ? $option_menu : array_keys($option_menu));
}

function autonav_form_html($instance) {
  $option_menu = autonav_known_params(1);

  $our_menu = new WL_Config_Form;
  $our_menu->set_callbacks(array('item_pre' => 'autonav_o_pre',
				 'item_post' => 'autonav_o_post'));

  foreach ($option_menu as $option_name => $html_tags) {
    if ($html_tags['type'] != 'checkbox') {
      $html_tags['value'] = htmlspecialchars($instance[$option_name]);
    }
    $html_tags['name'] = "autonav_wl[$option_name]";
    $html_tags['id'] = $option_name;
    $our_menu->form_item($instance, $option_name, $html_tags);
  }
}

function autonav_wloptions_sizes() {
  global $_wp_additional_image_sizes;

    $wp_registered_sizes = array('thumbnail','medium','large');

    if (is_array($_wp_additional_image_sizes)) {
      $wp_registered_sizes = array_unique(array_merge($wp_registered_sizes,
						      array_keys( $_wp_additional_image_sizes ) ) );
    }
    sort($wp_registered_sizes);
    foreach ($wp_registered_sizes as $s) {
      $x = intval(get_option("{$s}_size_w")); $y = intval(get_option("{$s}_size_h"));
      if (! $x && is_array($_wp_additional_image_sizes[$s])) {
	$x = intval( $_wp_additional_image_sizes[$s]['width'] );
	$y = intval( $_wp_additional_image_sizes[$s]['height'] );
      }
      if ($x) {
	$wp_registered_size[$s] = array('x' => $x, 'y' => $y);
      }
    }

    return $wp_registered_size;
}

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
    if (!strlen($options['combine'])) { $options['combine'] = 'all'; }
    if (intval($options['col_large']) < 1) { $options['col_large'] = 2; }
    if (intval($options['col_small']) == 0) {
      $options['col_small'] = 4;
    } elseif (intval($options['col_small']) < $options['col_large'] + 1) {
      $options['col_small'] = $options['col_large'] + 1;
    }
    if (!strlen($options['order'])) { 
      $options['order'] = 'ASC';
    } else {
      $options['order'] = strtoupper($options['order']); // so we can make 'desc' default for posts, regardless
    }
    if (!strlen($options['orderby'])) { $options['orderby'] = 'menu_order'; }

    print '<table class="form-table">';
    autonav_form_html($options);
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
<li><a href="http://www.wlindley.com/website/autonav/">Plugin homepage</a></li>
<li><a href="http://wordpress.org/extend/plugins/autonav/">AutoNav in Wordpress repository</a></li>
</ul>
<div style="border: 5px ridge #ff8833; float: right; width: 200px; margin-left: 5px; padding-left: 5px; margin-bottom: 1em;"><form action="https://www.paypal.com/cgi-bin/webscr" method="post">
      <p style="text-align: center;">If you like this plugin, please consider a $10 donation to help fund its ongoing development.</p>
<center><input name="cmd" type="hidden" value="_s-xclick" /> <input name="hosted_button_id" type="hidden" value="8365853" /> <input alt="PayPal - The safer, easier way to pay online!" name="submit" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" type="image" /><img src="https://www.paypal.com/en_US/i/scr/pixel.gif" border="0" alt="" width="1" height="1" /></center>
</form></div>
</td></tr></table>
</div>
<?php
}

autonav_wloptions_do_page();
