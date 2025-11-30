/**
 * Settings Admin JavaScript
 * Vanilla JavaScript implementation (keeping Select2 and Farbtastic as-is)
 */

import { ajaxRequest } from '../utils/ajax';

class SettingsAdmin {
	constructor() {
		this.init();
	}

	init() {
		this.initColorPicker();
		this.initMediaUploader();
		this.initNavigation();
		this.initSelect2();
		this.initFontDownloader();
	}

	initColorPicker() {
		const colorPickers = document.querySelectorAll('.colorpicker');
		colorPickers.forEach(picker => {
			picker.style.display = 'none';
			if (typeof jQuery !== 'undefined' && jQuery.fn.farbtastic) {
				jQuery(picker).farbtastic(jQuery(picker).closest('.color-picker').find('.color'));
			}
		});

		document.querySelectorAll('.color').forEach(colorInput => {
			colorInput.addEventListener('click', function() {
				const picker = this.closest('.color-picker').querySelector('.colorpicker');
				picker.style.display = 'block';
			});
		});

		document.addEventListener('mousedown', (e) => {
			if (!e.target.closest('.color-picker')) {
				document.querySelectorAll('.colorpicker').forEach(picker => {
					if (picker.style.display === 'block') {
						picker.style.display = 'none';
					}
				});
			}
		});
	}

	initMediaUploader() {
		let fileFrame;

		const uploadMediaFile = (button, previewMedia) => {
			const buttonId = button.id;
			const fieldId = buttonId.replace('_button', '');
			const previewId = buttonId.replace('_button', '_preview');

			if (fileFrame) {
				fileFrame.open();
				return;
			}

			fileFrame = wp.media.frames.file_frame = wp.media({
				title: button.dataset.uploaderTitle,
				button: {
					text: button.dataset.uploaderButtonText,
				},
				multiple: false
			});

			fileFrame.on('select', function() {
				const attachment = fileFrame.state().get('selection').first().toJSON();
				document.getElementById(fieldId).value = attachment.id;
				if (previewMedia) {
					const previewEl = document.getElementById(previewId);
					if (previewEl) {
						previewEl.src = attachment.sizes.thumbnail.url;
					}
				}
			});

			fileFrame.open();
		};

		document.querySelectorAll('.image_upload_button').forEach(button => {
			button.addEventListener('click', function(e) {
				e.preventDefault();
				uploadMediaFile(this, true);
			});
		});

		document.querySelectorAll('.image_delete_button').forEach(button => {
			button.addEventListener('click', function(e) {
				e.preventDefault();
				const fieldInput = this.closest('td').querySelector('.image_data_field');
				if (fieldInput) {
					fieldInput.value = '';
				}
				const preview = document.querySelector('.image_preview');
				if (preview) {
					preview.remove();
				}
			});
		});
	}

	initNavigation() {
		const settingsSections = document.querySelector('ul#settings-sections.subsubsub');
		if (!settingsSections) return;

		settingsSections.querySelectorAll('a').forEach((link, i) => {
			const idValue = link.getAttribute('href').replace('#', '');
			const linkText = link.textContent;

			const headings = document.querySelectorAll('h3');
			headings.forEach(heading => {
				if (heading.textContent.includes(linkText)) {
					heading.setAttribute('id', idValue);
					heading.classList.add('section-heading');
				}
			});
		});

		const tabLinks = document.querySelectorAll('#plugin_settings .subsubsub a.tab');
		tabLinks.forEach(link => {
			link.addEventListener('click', function(e) {
				e.preventDefault();

				document.querySelectorAll('.subsubsub .current').forEach(el => {
					el.classList.remove('current');
				});
				this.classList.add('current');

				if (this.classList.contains('all')) {
					document.querySelectorAll('#plugin_settings h3, #plugin_settings form p, #plugin_settings table.form-table, p.submit').forEach(el => {
						el.style.display = '';
					});
					return;
				}

				let toShow = this.getAttribute('href');
				toShow = toShow.replace('#', '');

				document.querySelectorAll('#plugin_settings h3, #plugin_settings form > p:not(.submit), #plugin_settings table').forEach(el => {
					el.style.display = 'none';
				});

				const targetHeading = document.getElementById(toShow);
				if (targetHeading) {
					targetHeading.style.display = '';

					let nextEl = targetHeading.nextElementSibling;
					while (nextEl && !nextEl.matches('h3.section-heading')) {
						if (nextEl.matches('p, table, table p')) {
							nextEl.style.display = '';
						}
						nextEl = nextEl.nextElementSibling;
					}
				}
			});
		});
	}

	initSelect2() {
		if (typeof jQuery === 'undefined' || !jQuery.fn.select2) {
			return;
		}

		document.querySelectorAll('.dkpdf-select2-ajax').forEach(select => {
			const postType = select.dataset.postType;
			const ajaxAction = select.dataset.ajaxAction;

			if (!postType || !ajaxAction) {
				jQuery(select).select2({
					placeholder: 'Select custom fields...',
					width: '100%'
				});
				return;
			}

			jQuery(select).select2({
				placeholder: '',
				width: '100%',
				minimumInputLength: 0,
				dropdownAutoWidth: true,
				ajax: {
					url: window.dkpdf_ajax?.ajax_url,
					dataType: 'json',
					delay: 250,
					data: function(params) {
						return {
							q: params.term || '',
							post_type: postType,
							action: ajaxAction,
							nonce: window.dkpdf_ajax?.nonce
						};
					},
					processResults: function(data) {
						if (data.success && data.data) {
							return {
								results: data.data
							};
						}
						return {
							results: []
						};
					},
					cache: true
				}
			});
		});
	}

	initFontDownloader() {
		const downloadBtn = document.getElementById('dkpdf-download-fonts');
		if (!downloadBtn) return;

		downloadBtn.addEventListener('click', async (e) => {
			e.preventDefault();

			const progressContainer = document.getElementById('dkpdf-download-progress');
			const progressFill = document.querySelector('.dkpdf-progress-fill');
			const progressText = document.querySelector('.dkpdf-progress-text');
			const statusEl = document.getElementById('dkpdf-download-status');

			downloadBtn.disabled = true;
			downloadBtn.textContent = 'Downloading...';
			progressContainer.style.display = 'block';
			statusEl.innerHTML = '';

			const progressInterval = setInterval(async () => {
				try {
					const response = await ajaxRequest('dkpdf_download_progress');
					if (response.success) {
						const progress = response.data.progress;
						progressFill.style.width = progress + '%';
						progressText.textContent = progress + '%';

						if (progress >= 100) {
							clearInterval(progressInterval);
						}
					}
				} catch (error) {
					console.error('Progress update failed:', error);
				}
			}, 500);

			try {
				const response = await ajaxRequest('dkpdf_download_fonts');
				clearInterval(progressInterval);

				if (response.success) {
					progressFill.style.width = '100%';
					progressText.textContent = '100%';

					setTimeout(() => {
						// Replace button with installed status
						const buttonContainer = downloadBtn.parentElement;
						buttonContainer.innerHTML = '<p class="dkpdf-core-fonts-status">' +
							'<span class="dashicons dashicons-yes-alt" style="color: #46b450; vertical-align: middle;"></span> ' +
							'Core fonts installed' +
							'</p>';

						// Refresh the font selector dropdown
						this.refreshFontSelector();

						// Show success message
						statusEl.innerHTML = '<p class="notice notice-success inline"><strong>' +
							'Fonts downloaded successfully!' +
							'</strong></p>';
					}, 500);
				} else {
					downloadBtn.disabled = false;
					downloadBtn.textContent = 'Download Fonts';
					progressContainer.style.display = 'none';
					progressFill.style.width = '0%';
					progressText.textContent = '0%';
					const errorMessage = response.data?.message || 'Unknown error';
					statusEl.innerHTML = '<p class="notice notice-error inline"><strong>Error:</strong> ' +
						errorMessage + '</p>';
				}
			} catch (error) {
				clearInterval(progressInterval);
				downloadBtn.disabled = false;
				downloadBtn.textContent = 'Download Fonts';
				progressContainer.style.display = 'none';
				progressFill.style.width = '0%';
				progressText.textContent = '0%';
				statusEl.innerHTML = '<p class="notice notice-error inline"><strong>Error:</strong> ' +
					'Failed to download fonts. Please try again.</p>';
			}
		});
	}

	async refreshFontSelector() {
		const selector = document.getElementById('dkpdf_default_font');
		if (!selector) {
			return;
		}

		try {
			const response = await ajaxRequest('dkpdf_list_fonts');

			if (response.success && response.data.fonts) {
				const currentValue = selector.value;
				selector.innerHTML = '';

				const fonts = response.data.fonts;

				fonts.forEach(font => {
					if (!font.complete) {
						return; // Skip incomplete families
					}

					const option = document.createElement('option');
					option.value = font.key;
					option.textContent = font.family_name;

					if (font.type === 'core') {
						option.textContent += ' (Core)';
					}

					if (font.key === currentValue || font.selected) {
						option.selected = true;
					}

					selector.appendChild(option);
				});

				// Update helper text
				const description = selector.nextElementSibling;
				if (description && description.classList.contains('description')) {
					const coreCount = fonts.filter(f => f.type === 'core').length;
					const customCount = fonts.filter(f => f.type === 'custom').length;
					description.textContent = `${coreCount} core fonts, ${customCount} custom fonts available. Need more? See options below.`;
				}
			}
		} catch (error) {
			console.error('Failed to refresh font selector:', error);
		}
	}
}

// Initialize Settings Admin when DOM is ready
if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', () => {
		new SettingsAdmin();
	});
} else {
	new SettingsAdmin();
}
