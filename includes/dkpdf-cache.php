<?php

if ( ! defined( 'ABSPATH' ) ) exit;

define('DKPDF_CACHE_DIR', WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'dkpdf');

function dkpdf_cache_init() {
	if( !is_dir(DKPDF_CACHE_DIR) ) {
		mkdir(DKPDF_CACHE_DIR, 0750, TRUE);
	}

	return is_writable(DKPDF_CACHE_DIR);
}

function dkpdf_cache_path($post_id) {
	return DKPDF_CACHE_DIR . DIRECTORY_SEPARATOR . hash('sha256', 'dkpdf_' . $post_id . '_' . LOGGED_IN_SALT) . '.pdf';
}

function dkpdf_cache_get($post_id) {

	if( !dkpdf_cache_init() ) {
		return false;
	}

	$path = dkpdf_cache_path($post_id);
	if( !is_readable($path) ) {
		return false;
	}

	return file_get_contents($path);
}

function dkpdf_cache_set($post_id, $data) {

	if(!dkpdf_cache_init()) {
		return false;
	}

	$path = dkpdf_cache_path($post_id);
	return file_put_contents($path, $data);
}
