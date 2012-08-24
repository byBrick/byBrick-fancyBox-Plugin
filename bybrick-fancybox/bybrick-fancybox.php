<?php
/*
Plugin Name: byBrick fancyBox Plugin
Plugin URI: https://github.com/byBrick/byBrick-fancyBox-Plugin
Description: A small plugin that enables fancyBox 2.1.0 and adds rel="fancybox" to all image links. Although this plugin is GPL2, remember that Fancybox2 by FancyApps isn't and you must buy a license in order to use this for any commercial projects. Read more about the license http://fancyapps.com/fancybox/#license
Version: 1.0
Author: byBrick
Author URI: http://bybrick.se
License: GPL2
	
Copyright 2012  David Paulsson  (email : david@davidpaulsson.se)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/* Lib */

function bb_fancybox_scripts() {
	
	/* Register the files */
	wp_register_script( 'fancybox', plugins_url( 'jquery.fancybox.pack.js', __FILE__ ), array( 'jquery' ), '2.1.0', true );
	wp_register_script( 'bb-fancybox', plugins_url( 'bybrick-fancybox.js', __FILE__ ), array( 'fancybox' ), '2.1.0', true );
	wp_register_style( 'fancybox-style', plugins_url( 'jquery.fancybox.min.css', __FILE__ ), array(), '2.1.0', 'all' );
	
	/* Load them */
	wp_enqueue_script( 'fancybox' );
	wp_enqueue_script( 'bb-fancybox' );
	wp_enqueue_style( 'fancybox-style' );

}
add_action('template_redirect', 'bb_fancybox_scripts');

/* Filter Hook */

add_filter('the_content', 'bb_add_rel_fancybox', 12);
add_filter('the_excerpt', 'bb_add_rel_fancybox', 12);

/* Add-rel-fancybox */

function bb_add_rel_fancybox($content)
{
	global $post;
	$id = $post->ID;

	if ( !function_exists('str_get_html') ) {
		require_once('simple_html_dom.php');
	}

	$html = str_get_html($content);

	/* Find internal image links */

	// First, check that there's content to process otherwise Simple_HTML_DOM will throw errors.
	if (!empty($content)) {
		// Collect details about any image attachments that may be in a gallery.
		$images = get_children( array('post_parent' => $id, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image') );

		// Find any links…
		foreach($html->find('a') as $a) {
			// …that wrap images.
			foreach($a->find('img') as $img) {
				// Check the link points to an image, and no rel="fancybox" is already applied.
				// Note: this also means that adding rel="nofancybox" will skip that link.
				if ( preg_match("/\.(jpg|jpeg|png|gif|bmp|ico|svg)$/i", $a->href) && !preg_match("/fancybox/i", $a->rel) ) {
					$image_no = "";
					// If it's a solo image from an internal source…
					if (preg_match("/wp-image-([0-9]+?)/i", $a->class, $image_no)) {
						// …then append its html escaped description.
						$a->title = esc_attr( get_post($image_no[1])->post_content );
					}
					// Else, if it's an attachment in the gallery…
					elseif ( !empty($images) && preg_match("/attachment-thumbnail/i", $img->class) ) {
						foreach ($images as $image_id => $image) {
							// …check for the right database entry by title…
							if ("$image->post_title" == "$img->title") {
								// …and add the html escaped description.
								$a->title = esc_attr($image->post_content);
								break;
							}
						}
					}

					// Then add the rel="fancybox[post-id]" to make it open with fancybox.
					$a->rel = "fancybox[post-" . $id . "]";
				}
			}
		}

		// Save the content if it's changed.
		$content = $html->save();
	}

	// And return the content back to where it's called from.
	return $content;
}

?>