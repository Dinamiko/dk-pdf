<?php
declare( strict_types=1 );

namespace Dinamiko\DKPDF\Frontend;

class AssetLoader {

	public function enqueue_styles(): void {
		wp_register_style( 'font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css', array(), '4.3.0' );
		wp_enqueue_style( 'font-awesome' );

		wp_register_style( 'dkpdf-frontend', plugins_url( 'dk-pdf/assets/css/frontend.css' ), array(), DKPDF_VERSION );
		wp_enqueue_style( 'dkpdf-frontend' );
	}

	public function enqueue_scripts(): void {
		wp_register_script( 'dkpdf-frontend', plugins_url( 'dk-pdf/assets/js/frontend.js' ), array( 'jquery' ), DKPDF_VERSION, true );
		wp_enqueue_script( 'dkpdf-frontend' );
	}

	public function admin_enqueue_styles( string $hook = '' ): void {
		if ( isset( $_GET['page'] ) && $_GET['page'] == 'dkpdf_settings' ) {
			wp_register_style( 'font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css', array(), '4.3.0' );
			wp_enqueue_style( 'font-awesome' );

			wp_register_style( 'dkpdf-admin', plugins_url( 'dk-pdf/assets/css/admin.css' ), array(), DKPDF_VERSION );
			wp_enqueue_style( 'dkpdf-admin' );
		}
	}

	public function admin_enqueue_scripts( string $hook = '' ): void {
		if ( isset( $_GET['page'] ) && $_GET['page'] == 'dkpdf_settings' ) {
			wp_register_script( 'dkpdf-settings-admin', plugins_url( 'dk-pdf/assets/js/settings-admin.js' ), array( 'jquery' ), DKPDF_VERSION );
			wp_enqueue_script( 'dkpdf-settings-admin' );

			wp_register_script( 'dkpdf-ace', plugins_url( 'dk-pdf/assets/js/src-min/ace.js' ), array(), DKPDF_VERSION );
			wp_enqueue_script( 'dkpdf-ace' );

			wp_register_script( 'dkpdf-admin', plugins_url( 'dk-pdf/assets/js/admin.js' ), array( 'jquery' ), DKPDF_VERSION );
			wp_enqueue_script( 'dkpdf-admin' );
		}
	}
}
