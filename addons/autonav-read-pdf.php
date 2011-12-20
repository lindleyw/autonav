<?php
/*
Plugin Name: Autonav PDF Image reader
Description: Experimental support for creating PDF thumbnails with AutoNav
Author: William Lindley
Author URI: http://www.saltriversystems.com/
*/

function autonav_read_pdf($img_resource, $pic_full_path, $attr, $ext) {
  if (!isset($img_resource)) {
    switch ($ext) {
    case 'pdf':
      // Experimental support
      if (class_exists('Imagick')) {
	$im = new Imagick();
	$im->setResolution( 100, 100 );
	$im->readImage( $pic_full_path.'[0]' ); // read only the first page
	$im->scaleImage(900, 900, 1); // final param = bestfit
	// need to pick resource from object???
	$img_resource = NULL; // ~~~~ Hmm...
      } else {
	$attr['error'] = "Imagick support missing for: {$to_file_path}";
	return ($attr);
      }
    }
  }
  return $img_resource;
}

add_filter('autonav_image_create', 'autonav_read_pdf', 10,5);
