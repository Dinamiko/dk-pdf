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
		// With template sets, we don't need to prepend the template directory
		// because get_templates_dir() already returns the correct path
		ob_start();
		$this->template_loader->get_template_part( $template_name );
		$content = ob_get_clean();

		return $content;
	}
}