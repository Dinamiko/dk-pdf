jQuery(window).load(function(){

	if( jQuery('#dkpdf_pdf_custom_css').length ) {

		// ref: http://jsfiddle.net/deepumohanp/tGF6y/

		var textarea = jQuery('#dkpdf_pdf_custom_css');
		jQuery('#dkpdf_pdf_custom_css').hide();

		var editor = ace.edit("editor");
		editor.setTheme("ace/theme/twilight");
		editor.getSession().setMode("ace/mode/css");

		editor.getSession().on('change', function () {
		    textarea.val(editor.getSession().getValue());
		});

		textarea.val(editor.getSession().getValue());

	}
	
});