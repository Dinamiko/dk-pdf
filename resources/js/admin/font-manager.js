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

		// Upload font button
		document.addEventListener('click', (e) => {
			if (e.target.matches('#dkpdf-upload-font-btn')) {
				e.preventDefault();
				document.getElementById('dkpdf-font-file-input').click();
			}
		});

		// File input change
		document.addEventListener('change', (e) => {
			if (e.target.matches('#dkpdf-font-file-input')) {
				const file = e.target.files[0];
				if (file) {
					this.uploadFont(file);
				}
				e.target.value = '';
			}
		});

		// Delete font button
		document.addEventListener('click', (e) => {
			if (e.target.matches('.dkpdf-delete-font')) {
				e.preventDefault();
				const fontName = e.target.dataset.fontName;
				const fontType = e.target.dataset.fontType;
				this.confirmDelete(fontName, fontType);
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
						<div class="dkpdf-modal-actions">
							<button type="button" id="dkpdf-upload-font-btn" class="button button-primary">
								${i18n.upload_font || 'Upload Font'}
							</button>
							<input type="file" id="dkpdf-font-file-input" accept=".ttf" style="display:none;">
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

			const deleteButton = font.selected
				? `<button class="button dkpdf-delete-font" disabled data-font-name="${font.name}" data-font-type="${font.type}" title="${i18n.cannot_delete_active || 'Cannot delete the currently selected font'}">${i18n.delete || 'Delete'}</button>`
				: `<button class="button dkpdf-delete-font" data-font-name="${font.name}" data-font-type="${font.type}">${i18n.delete || 'Delete'}</button>`;

			const listItem = `
				<li class="dkpdf-font-item ${font.selected ? 'dkpdf-font-selected' : ''}">
					<div class="dkpdf-font-info">
						<span class="dkpdf-font-name">${font.name}</span>
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
		uploadBtn.disabled = true;
		uploadBtn.textContent = i18n.uploading || 'Uploading...';
		this.clearMessage();

		try {
			const response = await ajaxRequest('dkpdf_upload_font', { font_file: file });
			uploadBtn.disabled = false;
			uploadBtn.textContent = i18n.upload_font || 'Upload Font';

			if (response.success) {
				this.showMessage(response.data.message, 'success');
				this.loadFonts();
				this.refreshFontSelector();
			} else {
				this.showMessage(response.data.message || (i18n.upload_failed || 'Failed to upload font.'), 'error');
			}
		} catch (error) {
			uploadBtn.disabled = false;
			uploadBtn.textContent = i18n.upload_font || 'Upload Font';
			this.showMessage(i18n.upload_failed || 'Failed to upload font.', 'error');
		}
	}

	confirmDelete(fontName, fontType) {
		const i18n = window.dkpdf_ajax?.i18n || {};
		const message = fontType === 'core'
			? (i18n.confirm_delete_core || 'Are you sure you want to delete the core font "%s"?').replace('%s', fontName)
			: (i18n.confirm_delete_custom || 'Are you sure you want to delete the custom font "%s"?').replace('%s', fontName);

		if (confirm(message)) {
			this.deleteFont(fontName);
		}
	}

	async deleteFont(fontName) {
		const i18n = window.dkpdf_ajax?.i18n || {};
		this.clearMessage();

		try {
			const response = await ajaxRequest('dkpdf_delete_font', { font_name: fontName });

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

				fonts.forEach(font => {
					const option = document.createElement('option');
					option.value = font.name;
					option.textContent = this.formatFontName(font.name);
					if (font.name === currentValue) {
						option.selected = true;
					}
					dropdown.appendChild(option);
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
