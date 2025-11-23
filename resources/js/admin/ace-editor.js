/**
 * ACE Editor Integration
 * Modern wrapper for ACE editor initialization
 */

class AceEditorWrapper {
	constructor() {
		this.init();
	}

	init() {
		if (document.readyState === 'loading') {
			window.addEventListener('load', () => this.initEditor());
		} else {
			this.initEditor();
		}
	}

	initEditor() {
		const textareaEl = document.getElementById('dkpdf_pdf_custom_css');

		if (!textareaEl || typeof ace === 'undefined') {
			return;
		}

		textareaEl.style.display = 'none';

		const editor = ace.edit('editor');
		editor.setTheme('ace/theme/twilight');
		editor.getSession().setMode('ace/mode/css');

		editor.getSession().on('change', () => {
			textareaEl.value = editor.getSession().getValue();
		});

		textareaEl.value = editor.getSession().getValue();
	}
}

new AceEditorWrapper();
