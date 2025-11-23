<?php
declare( strict_types=1 );

namespace Dinamiko\DKPDF\Frontend;

class AssetLoader {

	public function enqueue_styles(): void {
		wp_register_style( 'font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css', array(), '4.3.0' );
		wp_enqueue_style( 'font-awesome' );

		wp_register_style( 'dkpdf-frontend', plugins_url( 'dk-pdf/build/frontend-style.css' ), array(), DKPDF_VERSION );
		wp_enqueue_style( 'dkpdf-frontend' );
	}

	public function enqueue_scripts(): void {
		$asset_file = include plugin_dir_path( dirname( __DIR__ ) ) . 'build/frontend.asset.php';
		$dependencies = isset( $asset_file['dependencies'] ) ? $asset_file['dependencies'] : array();
		$version = isset( $asset_file['version'] ) ? $asset_file['version'] : DKPDF_VERSION;

		wp_register_script( 'dkpdf-frontend', plugins_url( 'dk-pdf/build/frontend.js' ), $dependencies, $version, true );
		wp_enqueue_script( 'dkpdf-frontend' );
	}

	public function admin_enqueue_styles( string $hook = '' ): void {
		if ( isset( $_GET['page'] ) && $_GET['page'] == 'dkpdf_settings' ) {
			wp_register_style( 'font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css', array(), '4.3.0' );
			wp_enqueue_style( 'font-awesome' );

			wp_register_style( 'dkpdf-admin', plugins_url( 'dk-pdf/build/admin-style.css' ), array(), DKPDF_VERSION );
			wp_enqueue_style( 'dkpdf-admin' );
		}
	}

	public function admin_enqueue_scripts( string $hook = '' ): void {
		if ( isset( $_GET['page'] ) && $_GET['page'] == 'dkpdf_settings' ) {
			$settings_asset_file = include plugin_dir_path( dirname( __DIR__ ) ) . 'build/admin-settings.asset.php';
			$settings_dependencies = isset( $settings_asset_file['dependencies'] ) ? $settings_asset_file['dependencies'] : array();
			$settings_version = isset( $settings_asset_file['version'] ) ? $settings_asset_file['version'] : DKPDF_VERSION;

			wp_register_script( 'dkpdf-settings-admin', plugins_url( 'dk-pdf/build/admin-settings.js' ), $settings_dependencies, $settings_version, true );
			wp_enqueue_script( 'dkpdf-settings-admin' );

			wp_register_script( 'dkpdf-ace', plugins_url( 'dk-pdf/vendor-assets/js/src-min/ace.js' ), array(), DKPDF_VERSION );
			wp_enqueue_script( 'dkpdf-ace' );

			$ace_asset_file = include plugin_dir_path( dirname( __DIR__ ) ) . 'build/admin-ace.asset.php';
			$ace_dependencies = isset( $ace_asset_file['dependencies'] ) ? $ace_asset_file['dependencies'] : array();
			$ace_version = isset( $ace_asset_file['version'] ) ? $ace_asset_file['version'] : DKPDF_VERSION;

			wp_register_script( 'dkpdf-admin', plugins_url( 'dk-pdf/build/admin-ace.js' ), array_merge( array( 'dkpdf-ace' ), $ace_dependencies ), $ace_version, true );
			wp_enqueue_script( 'dkpdf-admin' );
		}
	}
}
