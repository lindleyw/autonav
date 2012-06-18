<?php
 
 
if ('autonav-wl-options.php' == basename($_SERVER['SCRIPT_FILENAME'])) {
  die ('Please access through the Dashboard!');
}

if (!is_admin()) {die('This plugin is only accessible from the WordPress Dashboard.');}
  
if (!defined('WP_CONTENT_DIR')) define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
if (!defined('WP_CONTENT_URL')) define('WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
if (!defined('WP_ADMIN_URL')) define('WP_ADMIN_URL', get_option('siteurl') . '/wp-admin');
if (!defined('WP_PLUGIN_DIR')) define('WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins');
if (!defined('WP_PLUGIN_URL')) define('WP_PLUGIN_URL', WP_CONTENT_URL . '/plugins');

/* ******************* */

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

/* ********************** */

// Draw the menu page itself
function autonav_wloptions_do_page() {
  global $_wp_additional_image_sizes;
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
				     'text' => '<span style="display: inline-block; vertical-align:top;">from <a href="http://codex.wordpress.org/Template_Tags/wp_list_pages#Parameters" alt="(on wordpress.org)">list of possible values</a>, or<br> <tt>meta:<em>customfieldname</em></tt></span>'));
    autonav_o_header('List of page IDs to exclude', 3);
    autonav_o_item($options, array('name'=>'exclude', 'type'=>'text'));
    autonav_o_header('Display Titles Under Images', 3, 'Displaying images');
    autonav_o_item($options, array('name'=>'titles', 'type'=>'checkbox', 'checked' => '1'));
    autonav_o_header('Size of images', 3);

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
	$wp_reg_size_text .= "<br />\"{$s}\" <em>currently set to</em> {$x}x{$y}";
      }
    }

    autonav_o_item($options, array('name'=>'size', 'type'=>'text', 'text' => '<span style="display: inline-block; vertical-align: top;">"auto" for below, or as "300x200"' . $wp_reg_size_text . '</span>'));
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
<div style="border: 5px ridge #ff8833; float: right; width: 200px; margin-left: 5px; padding-left: 5px; margin-bottom: 1em;"><form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<p style="text-align: center;">If you like this plugin, please consider a $9 donation to help fund its ongoing development.</p>
<center><input name="cmd" type="hidden" value="_s-xclick" /> <input name="hosted_button_id" type="hidden" value="8365853" /> <input alt="PayPal - The safer, easier way to pay online!" name="submit" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" type="image" /><img src="https://www.paypal.com/en_US/i/scr/pixel.gif" border="0" alt="" width="1" height="1" /></center>
</form></div>
<h4><a href="http://blog.saltriversystems.com/tag/autonav/feed/">AutoNav RSS</a></h4>
<div class="rss-widget">
<?php 
  wp_widget_rss_output(array('url' => 'http://blog.saltriversystems.com/tag/autonav/feed/',
			     'title' => '',
			     'items' => $count,
			     'show_summary' => $summary,
			     'show_author' => $author,
			     'show_date' => $date
			     ));
?></div>
</td></tr></table>
</div>
<?php
}

autonav_wloptions_do_page();
