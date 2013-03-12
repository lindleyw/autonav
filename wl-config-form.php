<?php
/*
Description: Support for standard WLindley.com configuration screens
Author: William Lindley
Author URI: http://www.wlindley.com/
License: GPL2
*/

class WL_Config_Form {

  protected $callback;

  function set_callbacks($args) {
    $this->callback = $args;  # entirely replace
  }

  function _print_form_item($options, $option_name, $html_tags) {

    $value = $options[$option_name];
    if ($html_tags['type'] == 'checkbox') {
      $html_tags['checked'] = 1;
    }
    print '<input ';
    foreach ($html_tags as $h_tag => $h_value) {
      if (!strpos($h_tag,'.')) {
	switch ($h_tag) {
	case 'text': # handled below
	  $plain_text = $h_value;
	  break;
	case 'checked':
	  checked($options[$option_name], $html_tags['value']); # add checked if values match
	  print " ";
	  break;
	default:
	  print "$h_tag=\"$h_value\" ";
	}
      }
    }
    print " /> $plain_text";

  }

  function form_item($options, $option_name, $html_tags) {
    if (array_key_exists('item_pre', $this->callback)) {
      $html_tags = call_user_func($this->callback['item_pre'], $options, $option_name, $html_tags);
    }

    if (isset($html_tags['choices']) && is_array($html_tags['choices'])) {
      $fset = (isset($html_tags['title']) && strlen($html_tags['title']));
      if ($fset) {
	print '<fieldset><legend>' . $html_tags['title'] .'</legend>';
	unset ($html_tags['title']);
      }

      foreach ($html_tags['choices'] as $radio_value => $radio_text) {
	$html_tag_copy = $html_tags;
	unset ($html_tag_copy['choices']);
	$html_tag_copy['checked'] = 1;
	$html_tag_copy['value'] = $radio_value;
	$html_tag_copy['text'] = $radio_text;
	$this->_print_form_item($options, $option_name, $html_tag_copy);
      }

      if ($fset) {
	print '</fieldset>';
      }
    } else {
      $this->_print_form_item($options, $option_name, $html_tags);
    }
    if (array_key_exists('item_post', $this->callback)) {
      $html_tags = call_user_func($this->callback['item_post'], $options, $option_name, $html_tags);
    }
  }

}  # End of Config Form class
