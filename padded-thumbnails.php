<?php
/**
 * Plugin Name: Padded Thumbnails
 * Description: Allows the creation of padded thumbnails.
 * Version: 1.0
 * Author: Verb Brands Limited
 * Author URI: http://verbbrands.com/
 */

class PaddedThumbnails {
	public function __construct() {
		// Add filters
		add_filter('wp_generate_attachment_metadata', [
			$this,
			'wp_generate_attachment_metadata'
		]);
	}
	
	public function wp_generate_attachment_metadata($data) {
		global $_wp_additional_image_sizes;
		
		$upload_dir = wp_upload_dir()['basedir'] . '/';
		
		foreach ($_wp_additional_image_sizes as $k => $size) {
			if (array_key_exists('padded', $size) && $size['padded']) {
				// Check the image has an incorrect aspect ratio
				list($width, $height) = getimagesize($orig_file = $upload_dir . $data['file']);
				if (round($size['height'] * $width / $height) == $size['width']) {
					continue;
				}
				
				// Scale image down
				$im = new Imagick($orig_file);
				$im->setImageFormat('png');
				$im->scaleImage($size['width'], $size['height'], true);
				
				// Get new width & height
				$width = $im->getImageWidth();
				$height = $im->getImageHeight();
				
				// Save temporary version
				$im->writeImage($orig_file . '.tmp');
				$im->clear();
				$im->destroy();
				
				// Create the padded version				
				$im = imagecreatetruecolor($size['width'], $size['height']);
				imagesavealpha($im, true);
				imagefill($im, 0, 0, imagecolorallocatealpha($im, 0, 0, 0, 127));
				imagecopy($im, ($tmp = imagecreatefrompng($orig_file . '.tmp')), round(($size['width'] - $width) / 2), round(($size['height'] - $height) / 2), 0, 0, $width, $height);
				imagedestroy($tmp);
				imagepng($im, ($file = $upload_dir . dirname($data['file']) . '/' . ($filename = basename($data['file'], '.' . pathinfo($data['file'])['extension']) . '-' . $size['width'] . 'x' . $size['height'] . '.png')));
				imagedestroy($im);
				
				// Destroy the temporary version
				unlink($orig_file . '.tmp');
				
				// Update metadata
				if (!array_key_exists($k, $data['sizes'])) {
					$data['sizes'][$k] = [];
				}
				$data['sizes'][$k]['file'] = $filename;
				$data['sizes'][$k]['height'] = $size['height'];
				$data['sizes'][$k]['mime-type'] = 'image/png';
				$data['sizes'][$k]['width'] = $size['width'];
			}
		}
		
		return $data;
	}
}
new PaddedThumbnails();