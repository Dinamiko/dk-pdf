<?php
declare( strict_types=1 );

namespace Dinamiko\DKPDF\Template;

class TemplateRenderer {

	private TemplateLoader $template_loader;

	public function __construct( TemplateLoader $template_loader ) {
		$this->template_loader = $template_loader;
	}

	/**
	 * Returns rendered template content
	 *
	 * @param string $template_name Template name to render
	 * @return string Rendered template content
	 */
	public function get_template( string $template_name ): string {
		$selected_template = get_option( 'dkpdf_selected_template', '' );
		$full_template_name = $selected_template . $template_name;

		ob_start();
		$this->template_loader->get_template_part( $full_template_name );
		$content = ob_get_clean();

		return $content;
	}
}