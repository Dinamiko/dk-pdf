<?php

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'DKPDF_CACHE_DIR', WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'dkpdf' );

add_action( 'save_post', 'dkpdf_cache_unset' );
add_action( 'sidebar_admin_setup', 'dkpdf_cache_sidebar_flush' );

function dkpdf_cache_is_enabled() {
	/* define('DKPDF_CACHE', 'on') in wp-config.php saves a few seconds compared to just ticking the box in options */
	$enabled = defined('DKPDF_CACHE') ? DKPDF_CACHE : get_option( 'dkpdf_enable_cache' );
	return ($enabled == "on" or $enabled === true);
}

function dkpdf_cache_init() {
	if( !is_dir( DKPDF_CACHE_DIR ) ) {
		mkdir( DKPDF_CACHE_DIR, 0750, TRUE );
	}

	return is_writable( DKPDF_CACHE_DIR );
}

function dkpdf_cache_path( $post_id ) {
	return DKPDF_CACHE_DIR . DIRECTORY_SEPARATOR . hash( 'sha256', 'dkpdf_' . $post_id . '_' . LOGGED_IN_SALT ) . '.pdf';
}

function dkpdf_cache_get( $post_id ) {

	if( !dkpdf_cache_init() ) {
		return false;
	}

	$path = dkpdf_cache_path( $post_id );
	if( !is_readable( $path ) ) {
		return false;
	}

	return file_get_contents( $path );
}

function dkpdf_cache_set( $post_id, $data ) {

	if(!dkpdf_cache_init()) {
		return false;
	}

	$path = dkpdf_cache_path( $post_id );
	return file_put_contents( $path, $data );
}

function dkpdf_cache_unset( $post_id ) {

	if(!dkpdf_cache_init()) {
		return false;
	}

	$path = dkpdf_cache_path( $post_id );

	if( is_file( $path ) ) {
		unlink( $path );
	}
}

/**
 * Delete all PDFs from cache
 */
function dkpdf_cache_flush() {
	$num_files = 0;

	if(!dkpdf_cache_init()) {
		return;
	}

	$files = glob( DKPDF_CACHE_DIR . DIRECTORY_SEPARATOR . '*', GLOB_NOSORT | GLOB_MARK );
  if( $files === false ) {
		return;
	}

	foreach( $files as $file ) {
		if( strlen( $file ) == 0 || $file[strlen( $file ) - 1] == DIRECTORY_SEPARATOR ) {
			continue;
		}
		if( is_file( $path ) ) {
			unlink( $file );
			$num_files++;
		}
	}

	return $num_files;
}

/* 
 * Flush cache when saving sidebar widgets 
 */
function dkpdf_cache_sidebar_flush() {
	error_log('dkpdf_cache_sidebar_flush, method: ' . $_SERVER['REQUEST_METHOD'] . ' url ' . $_SERVER['REQUEST_URI']);
}
