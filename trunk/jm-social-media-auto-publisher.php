<?php
/*
Plugin Name: Jannes & Mannes Social Media Auto Publisher
Plugin URI:
Description:
Author: Jan Henkes
Version: 0.5.2
Author URI: https://www.jannesmannes.nl
*/

if ( ! defined( 'JM_SMAP_DEBUG' ) ) {
	define( 'JM_SMAP_DEBUG', false );
}

spl_autoload_register( function ( $class ) {
	$filename = dirname( __FILE__ ) . '/' . str_replace( '\\', '/', $class ) . '.php';
	if ( file_exists( $filename ) ) {
		require $filename;
	}
} );

require dirname( __FILE__ ) . '/vendor/autoload.php';

JmSocialMediaAutoPublisher\Plugin::init();

register_activation_hook( __FILE__, [\JmSocialMediaAutoPublisher\Plugin::class, 'on_activate'] );