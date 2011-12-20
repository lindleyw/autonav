<?php

function autonav_sharp_resize ($from_image, $attr) {

  // Formerly integrated into the main resize procedure, the sharpness filter
  // is now a pre-processor, returning an intermediate image.
  $quality = abs($attr['sharp']);
  if ($quality < 10) { $quality = 90; } // sharpness parameter becomes quality

  if ($attr['sharp'] > 0) {   // positive for pixellated, zero or negative for smooth
    if ($quality > 90) {      // create intermediate size
      $resample_params = image_resize_dimensions(imagesx($from_image), imagesy($from_image),
						 $attr["{$prefix}width"], $attr["{$prefix}height"],
						 $attr['crop'] > 0 ? 1 : 0);
      if (is_array($resample_params)) {
	// Prepare to resize
	list($dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h) = $resample_params;

	$i_factor = 1 - ($quality - floor($quality)); // 90.3333 becomes sharp factor .6666
	if ($i_factor == 0) { $i_factor = 1/2; }
	$interm_h = floor(($dst_h + $src_h) * $i_factor);
	$interm_w = floor(($dst_w + $src_w) * $i_factor);
	$to_image = imagecreatetruecolor($interm_w, $interm_h);
	imagecopyresized( $to_image, $from_image,
			  $dst_x * $i_factor, $dst_y * $i_factor, $src_x * $i_factor, $src_y * $i_factor,
			  $interm_w, $interm_h, $src_w, $src_h);
      }
    }
  }
  if (isset($to_image)) {
    imagedestroy($from_image);
    return $to_image;
  }
  return $from_image;
}

add_filter('autonav_resize_image', 'autonav_sharp_resize', 10, 3);
