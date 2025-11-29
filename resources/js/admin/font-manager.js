/**
 * Font Manager Modal Handler
 * Vanilla JavaScript implementation
 */

import { ajaxRequest } from '../utils/ajax';
import { fadeIn, fadeOut, show, hide, empty, val, isVisible } from '../utils/dom';

class FontManager {
	constructor() {
		this.modal = null;
		this.fonts = [];
		this.selectedFile = null;
		this.init();
	}

	init() {
		this.bindEvents();
		this.createModal();
	}

	bindEvents() {
		// Open modal when "Manage Fonts" button is clicked
		document.addEventListener('click', (e) => {
			if (e.target.matches('#dkpdf-manage-fonts')) {
				e.preventDefault();
				this.openModal();
			}
		});

		// Close modal on close button click
		document.addEventListener('click', (e) => {
			if (e.target.matches('.dkpdf-modal-close')) {
				e.preventDefault();
				this.closeModal();
			}
		});

		// Close modal when clicking outside
		document.addEventListener('click', (e) => {
			if (e.target.matches('.dkpdf-modal-overlay')) {
				this.closeModal();
			}
		});

		// Close modal on ESC key
		document.addEventListener('keydown', (e) => {
			if (e.key === 'Escape' && this.modal && isVisible(this.modal)) {
				this.closeModal();
			}
		});

		// Select file button - opens file picker
		document.addEventListener('click', (e) => {
			if (e.target.matches('#dkpdf-select-file-btn')) {
				e.preventDefault();
				document.getElementById('dkpdf-font-file-input').click();
			}
		});

		// File input change - auto-fill and show upload button
		document.addEventListener('change', (e) => {
			if (e.target.matches('#dkpdf-font-file-input')) {
				const file = e.target.files[0];
				if (file) {
					// Store selected file
					this.selectedFile = file;

					// Auto-fill family name if empty
					const familyNameInput = document.getElementById('dkpdf-family-name');
					if (familyNameInput && !familyNameInput.value.trim()) {
						const extractedName = this.extractFamilyName(file.name);
						familyNameInput.value = extractedName;
					}

					// Show selected filename
					const filenameDisplay = document.getElementById('dkpdf-selected-filename');
					if (filenameDisplay) {
						filenameDisplay.textContent = file.name;
						filenameDisplay.style.display = 'inline';
					}

					// Hide "Select File" button and show "Upload Font" button
					const selectFileBtn = document.getElementById('dkpdf-select-file-btn');
					const uploadBtn = document.getElementById('dkpdf-upload-font-btn');

					if (selectFileBtn) {
						selectFileBtn.style.display = 'none';
					}
					if (uploadBtn) {
						uploadBtn.style.display = 'inline-block';
					}
				}
			}
		});

		// Upload font button - actually performs the upload
		document.addEventListener('click', (e) => {
			if (e.target.matches('#dkpdf-upload-font-btn')) {
				e.preventDefault();
				if (this.selectedFile) {
					this.uploadFont(this.selectedFile);
				}
			}
		});

		// Delete font button
		document.addEventListener('click', (e) => {
			if (e.target.matches('.dkpdf-delete-font')) {
				e.preventDefault();
				const fontKey = e.target.dataset.fontKey;
				const fontType = e.target.dataset.fontType;
				this.confirmDelete(fontKey, fontType);
			}
		});
	}

	createModal() {
		const i18n = window.dkpdf_ajax?.i18n || {};
		const modalHtml = `
			<div class="dkpdf-modal-overlay" style="display:none;">
				<div class="dkpdf-modal">
					<div class="dkpdf-modal-header">
						<h2>${i18n.manage_fonts || 'Manage Fonts'}</h2>
						<button class="dkpdf-modal-close" aria-label="${i18n.close || 'Close'}">&times;</button>
					</div>
					<div class="dkpdf-modal-body">
						<div class="dkpdf-upload-form">
							<div class="dkpdf-form-row">
								<label for="dkpdf-family-name">${i18n.family_name || 'Family Name'}</label>
								<input type="text" id="dkpdf-family-name" class="regular-text" placeholder="${i18n.auto_detected || 'Auto-detected from filename'}">
							</div>
							<div class="dkpdf-form-row">
								<label for="dkpdf-variant-type">${i18n.variant_type || 'Variant Type'}</label>
								<select id="dkpdf-variant-type" class="regular-text">
									<option value="">${i18n.auto_detect || 'Auto-detect'}</option>
									<option value="R">${i18n.regular || 'Regular'}</option>
									<option value="B">${i18n.bold || 'Bold'}</option>
									<option value="I">${i18n.italic || 'Italic'}</option>
									<option value="BI">${i18n.bold_italic || 'Bold Italic'}</option>
								</select>
							</div>
							<div class="dkpdf-form-row">
								<div class="dkpdf-file-selection">
									<button type="button" id="dkpdf-select-file-btn" class="button">
										${i18n.select_file || 'Select File'}
									</button>
									<button type="button" id="dkpdf-upload-font-btn" class="button button-primary" style="display:none;">
										${i18n.upload_font || 'Upload Font'}
									</button>
									<span id="dkpdf-selected-filename" style="display:none; margin-left: 10px; color: #666;"></span>
									<input type="file" id="dkpdf-font-file-input" accept=".ttf" style="display:none;">
								</div>
							</div>
						</div>
						<div class="dkpdf-modal-message"></div>
						<div class="dkpdf-fonts-list-wrapper">
							<div class="dkpdf-loading">${i18n.loading || 'Loading'}...</div>
							<ul class="dkpdf-fonts-list"></ul>
						</div>
					</div>
				</div>
			</div>
		`;

		if (!document.querySelector('.dkpdf-modal-overlay')) {
			document.body.insertAdjacentHTML('beforeend', modalHtml);
		}

		this.modal = document.querySelector('.dkpdf-modal-overlay');
	}

	openModal() {
		fadeIn(this.modal, 200);
		this.loadFonts();
	}

	closeModal() {
		fadeOut(this.modal, 200);
		this.clearMessage();
	}

	async loadFonts() {
		const loadingEl = document.querySelector('.dkpdf-loading');
		const listEl = document.querySelector('.dkpdf-fonts-list');

		show(loadingEl);
		empty(listEl);

		try {
			const response = await ajaxRequest('dkpdf_list_fonts');
			hide(loadingEl);

			if (response.success && response.data.fonts) {
				this.fonts = response.data.fonts;
				this.renderFontsList(response.data.fonts);
			} else {
				this.showMessage(window.dkpdf_ajax?.i18n?.error_loading_fonts || 'Failed to load fonts.', 'error');
			}
		} catch (error) {
			hide(loadingEl);
			this.showMessage(window.dkpdf_ajax?.i18n?.error_loading_fonts || 'Failed to load fonts.', 'error');
		}
	}

	renderFontsList(fonts) {
		const listEl = document.querySelector('.dkpdf-fonts-list');
		const i18n = window.dkpdf_ajax?.i18n || {};
		empty(listEl);

		if (fonts.length === 0) {
			listEl.innerHTML = `<li class="dkpdf-no-fonts">${i18n.no_fonts || 'No fonts available.'}</li>`;
			return;
		}

		fonts.forEach(font => {
			let badges = '';

			if (font.selected) {
				badges += `<span class="dkpdf-badge dkpdf-badge-active">${i18n.active || 'Active'}</span>`;
			}

			if (font.category) {
				badges += `<span class="dkpdf-badge dkpdf-badge-category">${font.category}</span>`;
			}

			badges += `<span class="dkpdf-badge dkpdf-badge-${font.type}">${font.type === 'core' ? (i18n.core || 'Core') : (i18n.custom || 'Custom')}</span>`;

			// Add variant indicators for custom fonts
			let variantsHtml = '';
			if (font.type === 'custom' && font.variants) {
				const variantLabels = { R: 'Regular', B: 'Bold', I: 'Italic', BI: 'Bold Italic' };
				variantsHtml = '<div class="dkpdf-font-variants">';
				['R', 'B', 'I', 'BI'].forEach(variant => {
					const exists = font.variants[variant] && font.variants[variant].exists;
					const statusClass = exists ? 'dkpdf-variant-exists' : 'dkpdf-variant-missing';
					const statusIcon = exists ? '\u2713' : '\u2717';
					variantsHtml += `<span class="dkpdf-variant ${statusClass}" title="${variantLabels[variant]}">${variant} ${statusIcon}</span>`;
				});
				variantsHtml += '</div>';
			}

			const deleteButton = font.selected
				? `<button class="button dkpdf-delete-font" disabled data-font-key="${font.key}" data-font-type="${font.type}" title="${i18n.cannot_delete_active || 'Cannot delete the currently selected font'}">${i18n.delete || 'Delete'}</button>`
				: `<button class="button dkpdf-delete-font" data-font-key="${font.key}" data-font-type="${font.type}">${i18n.delete || 'Delete'}</button>`;

			const listItem = `
				<li class="dkpdf-font-item ${font.selected ? 'dkpdf-font-selected' : ''}">
					<div class="dkpdf-font-info">
						<span class="dkpdf-font-name">${font.family_name || font.name}</span>
						${variantsHtml}
						<div class="dkpdf-font-badges">${badges}</div>
					</div>
					<div class="dkpdf-font-actions">
						${deleteButton}
					</div>
				</li>
			`;

			listEl.insertAdjacentHTML('beforeend', listItem);
		});
	}

	async uploadFont(file) {
		const i18n = window.dkpdf_ajax?.i18n || {};

		if (!file.name.toLowerCase().endsWith('.ttf')) {
			this.showMessage(i18n.only_ttf_files || 'Only TTF font files are supported.', 'error');
			return;
		}

		const uploadBtn = document.getElementById('dkpdf-upload-font-btn');
		const originalText = uploadBtn.textContent;
		uploadBtn.disabled = true;
		uploadBtn.textContent = i18n.uploading || 'Uploading...';
		this.clearMessage();

		// Get family name and variant from form
		const familyName = val('#dkpdf-family-name') || '';
		const variant = val('#dkpdf-variant-type') || '';

		try {
			const response = await ajaxRequest('dkpdf_upload_font', {
				font_file: file,
				family_name: familyName,
				variant: variant
			});

			if (response.success) {
				this.showMessage(response.data.message, 'success');
				// Clear form fields and reset
				this.resetUploadForm();
				this.loadFonts();
				this.refreshFontSelector();
			} else {
				uploadBtn.disabled = false;
				uploadBtn.textContent = originalText;
				this.showMessage(response.data.message || (i18n.upload_failed || 'Failed to upload font.'), 'error');
			}
		} catch (error) {
			uploadBtn.disabled = false;
			uploadBtn.textContent = originalText;
			this.showMessage(i18n.upload_failed || 'Failed to upload font.', 'error');
		}
	}

	resetUploadForm() {
		const i18n = window.dkpdf_ajax?.i18n || {};

		// Clear selected file
		this.selectedFile = null;

		// Clear form inputs
		const familyNameInput = document.getElementById('dkpdf-family-name');
		if (familyNameInput) {
			familyNameInput.value = '';
		}

		const variantSelect = document.getElementById('dkpdf-variant-type');
		if (variantSelect) {
			variantSelect.value = '';
		}

		const fileInput = document.getElementById('dkpdf-font-file-input');
		if (fileInput) {
			fileInput.value = '';
		}

		// Hide filename display
		const filenameDisplay = document.getElementById('dkpdf-selected-filename');
		if (filenameDisplay) {
			filenameDisplay.textContent = '';
			filenameDisplay.style.display = 'none';
		}

		// Reset buttons: Show "Select File" and hide "Upload Font"
		const selectFileBtn = document.getElementById('dkpdf-select-file-btn');
		const uploadBtn = document.getElementById('dkpdf-upload-font-btn');

		if (selectFileBtn) {
			selectFileBtn.style.display = 'inline-block';
			selectFileBtn.disabled = false;
		}
		if (uploadBtn) {
			uploadBtn.style.display = 'none';
			uploadBtn.disabled = false;
			uploadBtn.textContent = i18n.upload_font || 'Upload Font';
		}
	}

	confirmDelete(fontKey, fontType) {
		const i18n = window.dkpdf_ajax?.i18n || {};
		const message = fontType === 'core'
			? (i18n.confirm_delete_core || 'Are you sure you want to delete the core font "%s"?').replace('%s', fontKey)
			: (i18n.confirm_delete_custom || 'Are you sure you want to delete the custom font family "%s"?').replace('%s', fontKey);

		if (confirm(message)) {
			this.deleteFont(fontKey);
		}
	}

	async deleteFont(fontKey) {
		const i18n = window.dkpdf_ajax?.i18n || {};
		this.clearMessage();

		try {
			const response = await ajaxRequest('dkpdf_delete_font', { font_key: fontKey });

			if (response.success) {
				this.showMessage(response.data.message, 'success');
				this.loadFonts();
				this.refreshFontSelector();
			} else {
				this.showMessage(response.data.message || (i18n.delete_failed || 'Failed to delete font.'), 'error');
			}
		} catch (error) {
			this.showMessage(i18n.delete_failed || 'Failed to delete font.', 'error');
		}
	}

	extractFamilyName(filename) {
		// Remove .ttf extension
		let name = filename.replace(/\.ttf$/i, '');

		// Remove variant suffixes
		const patterns = [
			/-BoldItalic$/i,
			/-BoldOblique$/i,
			/-Bold$/i,
			/-Italic$/i,
			/-Oblique$/i,
			/-Regular$/i,
			/-BI$/i,
			/-B$/i,
			/-I$/i,
			/-R$/i
		];

		for (const pattern of patterns) {
			name = name.replace(pattern, '');
		}

		return name;
	}

	formatFontName(fontName) {
		let name = fontName.replace(/-/g, ' ');
		name = name.replace(/([a-z])([A-Z])/g, '$1 $2');
		return name;
	}

	async refreshFontSelector() {
		const dropdown = document.getElementById('dkpdf_font_downloader');

		if (!dropdown) {
			return;
		}

		const currentValue = dropdown.value;

		try {
			const response = await ajaxRequest('dkpdf_list_fonts');

			if (response.success && response.data.fonts) {
				const fonts = response.data.fonts;
				empty(dropdown);

				// Only show complete families (those with Regular variant)
				fonts.forEach(font => {
					if (font.complete || font.type === 'core') {
						const option = document.createElement('option');
						option.value = font.key || font.name;
						option.textContent = this.formatFontName(font.family_name || font.name);
						if ((font.key && font.key === currentValue) || (font.name === currentValue) || font.selected) {
							option.selected = true;
						}
						dropdown.appendChild(option);
					}
				});
			}
		} catch (error) {
			console.error('Failed to refresh font selector:', error);
		}
	}

	showMessage(message, type) {
		const messageBox = document.querySelector('.dkpdf-modal-message');
		messageBox.innerHTML = `<div class="notice notice-${type === 'error' ? 'error' : 'success'}"><p>${message}</p></div>`;
		show(messageBox);

		if (type === 'success') {
			setTimeout(() => {
				fadeOut(messageBox, 300);
				setTimeout(() => empty(messageBox), 300);
			}, 5000);
		}
	}

	clearMessage() {
		const messageBox = document.querySelector('.dkpdf-modal-message');
		empty(messageBox);
		hide(messageBox);
	}
}

// Initialize Font Manager when DOM is ready
if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', () => {
		new FontManager();
	});
} else {
	new FontManager();
}
