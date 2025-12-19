import { ajaxRequest } from '../utils/ajax';
import { fadeIn, fadeOut, show, hide, empty, isVisible } from '../utils/dom';

class TemplateSetManager {
	constructor() {
		this.modal = null;
		this.templateSets = [];
		this.selectedFile = null;
		this.init();
	}

	init() {
		this.bindEvents();
		this.createModal();
	}

	bindEvents() {
		// Open modal when "Manage Template Sets" button is clicked
		document.addEventListener('click', (e) => {
			if (e.target.closest('#dkpdf-manage-template-sets')) {
				e.preventDefault();
				this.openModal();
			}
		});

		// Close modal on close button click
		document.addEventListener('click', (e) => {
			if (e.target.closest('.dkpdf-modal-close')) {
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
			if (e.target.closest('#dkpdf-select-template-zip-btn')) {
				e.preventDefault();
				document.getElementById('dkpdf-template-zip-input').click();
			}
		});

		// File input change - show upload button
		document.addEventListener('change', (e) => {
			if (e.target.matches('#dkpdf-template-zip-input')) {
				const file = e.target.files[0];
				if (file) {
					this.selectedFile = file;

					// Show selected filename
					const filenameDisplay = document.getElementById('dkpdf-selected-zip-filename');
					if (filenameDisplay) {
						filenameDisplay.textContent = file.name;
						filenameDisplay.style.display = 'inline';
					}

					// Hide "Select File" button and show "Upload Template Set" button
					const selectFileBtn = document.getElementById('dkpdf-select-template-zip-btn');
					const uploadBtn = document.getElementById('dkpdf-upload-set-btn');

					if (selectFileBtn) {
						selectFileBtn.style.display = 'none';
					}
					if (uploadBtn) {
						uploadBtn.style.display = 'inline-block';
					}
				}
			}
		});

		// Upload template set button
		document.addEventListener('click', (e) => {
			if (e.target.closest('#dkpdf-upload-set-btn')) {
				e.preventDefault();
				if (this.selectedFile) {
					this.uploadTemplateSet(this.selectedFile);
				}
			}
		});

		// Delete template set button
		document.addEventListener('click', (e) => {
			const deleteBtn = e.target.closest('.dkpdf-delete-template-set');
			if (deleteBtn) {
				e.preventDefault();
				const templateSetKey = deleteBtn.dataset.templateSetKey;
				const templateSetType = deleteBtn.dataset.templateSetType;
				const templateSetName = deleteBtn.dataset.templateSetName;
				this.confirmDelete(templateSetKey, templateSetType, templateSetName);
			}
		});
	}

	createModal() {
		const i18n = window.dkpdf_ajax?.i18n?.template_sets || {};
		const modalHtml = `
			<div class="dkpdf-modal-overlay dkpdf-template-set-modal" style="display:none;">
				<div class="dkpdf-modal">
					<div class="dkpdf-modal-header">
						<h2>${i18n.manage_template_sets || 'Manage Template Sets'}</h2>
						<button class="dkpdf-modal-close" aria-label="${i18n.close || 'Close'}">&times;</button>
					</div>
					<div class="dkpdf-modal-body">
						<div class="dkpdf-upload-form">
							<div class="dkpdf-form-row">
								<div class="dkpdf-file-selection">
									<button type="button" id="dkpdf-select-template-zip-btn" class="button">
										${i18n.select_zip_file || 'Select Template Set (.zip)'}
									</button>
									<button type="button" id="dkpdf-upload-set-btn" class="button button-primary" style="display:none;">
										${i18n.upload_template_set || 'Upload Template Set'}
									</button>
									<span id="dkpdf-selected-zip-filename" style="display:none; margin-left: 10px; color: #666;"></span>
									<input type="file" id="dkpdf-template-zip-input" accept=".zip" style="display:none;">
								</div>
							</div>
						</div>
						<div class="dkpdf-modal-message"></div>
						<div class="dkpdf-template-sets-list-wrapper">
							<div class="dkpdf-loading">${i18n.loading || 'Loading'}...</div>
							<ul class="dkpdf-template-sets-list"></ul>
						</div>
					</div>
				</div>
			</div>
		`;

		if (!document.querySelector('.dkpdf-template-set-modal')) {
			document.body.insertAdjacentHTML('beforeend', modalHtml);
		}

		this.modal = document.querySelector('.dkpdf-template-set-modal');
	}

	async openModal() {
		fadeIn(this.modal, 200);
		await this.loadTemplateSets();
	}

	closeModal() {
		fadeOut(this.modal, 200);
		this.clearMessage();
	}

	async loadTemplateSets() {
		const loadingEl = this.modal.querySelector('.dkpdf-loading');
		const listEl = this.modal.querySelector('.dkpdf-template-sets-list');

		show(loadingEl);
		empty(listEl);

		try {
			const response = await ajaxRequest('dkpdf_list_template_sets');
			hide(loadingEl);

			if (response.success && response.data.template_sets) {
				this.templateSets = response.data.template_sets;
				this.renderTemplateSetsList(response.data.template_sets);
			} else {
				const i18n = window.dkpdf_ajax?.i18n?.template_sets || {};
				this.showMessage(i18n.error_loading || 'Failed to load template sets.', 'error');
			}
		} catch (error) {
			hide(loadingEl);
			const i18n = window.dkpdf_ajax?.i18n?.template_sets || {};
			this.showMessage(i18n.error_loading || 'Failed to load template sets.', 'error');
		}
	}

	renderTemplateSetsList(templateSets) {
		const listEl = this.modal.querySelector('.dkpdf-template-sets-list');
		const i18n = window.dkpdf_ajax?.i18n?.template_sets || {};
		empty(listEl);

		if (templateSets.length === 0) {
			listEl.innerHTML = `<li class="dkpdf-no-template-sets">${i18n.no_template_sets || 'No template sets available.'}</li>`;
			return;
		}

		templateSets.forEach(templateSet => {
			let badges = '';

			if (templateSet.selected) {
				badges += `<span class="dkpdf-badge dkpdf-badge-active">${i18n.active || 'Active'}</span>`;
			}

			badges += `<span class="dkpdf-badge dkpdf-badge-${templateSet.type}">${templateSet.type === 'core' ? (i18n.core || 'Core') : (i18n.custom || 'Custom')}</span>`;

			// Add tags if available
			let tagsHtml = '';
			if (templateSet.tags && templateSet.tags.length > 0) {
				tagsHtml = '<div class="dkpdf-template-tags">';
				templateSet.tags.forEach(tag => {
					tagsHtml += `<span class="dkpdf-tag">${tag}</span>`;
				});
				tagsHtml += '</div>';
			}

			// Add required files list
			let filesHtml = '';
			if (templateSet.required_files && templateSet.required_files.length > 0) {
				filesHtml = `<div class="dkpdf-template-files"><strong>Files:</strong> ${templateSet.required_files.join(', ')}</div>`;
			}

			const canDelete = !templateSet.selected && templateSet.type !== 'core';
			const deleteButton = canDelete
				? `<button class="button dkpdf-delete-template-set" data-template-set-key="${templateSet.key}" data-template-set-type="${templateSet.type}" data-template-set-name="${templateSet.name}">${i18n.delete || 'Delete'}</button>`
				: `<button class="button dkpdf-delete-template-set" disabled data-template-set-key="${templateSet.key}" data-template-set-type="${templateSet.type}" data-template-set-name="${templateSet.name}" title="${templateSet.selected ? (i18n.cannot_delete_active || 'Cannot delete active template set') : (i18n.cannot_delete_core || 'Cannot delete core template sets')}">${i18n.delete || 'Delete'}</button>`;

			const listItem = `
				<li class="dkpdf-template-set-item ${templateSet.selected ? 'dkpdf-template-set-selected' : ''}">
					<div class="dkpdf-template-set-info">
						<div class="dkpdf-template-set-header">
							<span class="dkpdf-template-set-name">${templateSet.name}</span>
							<div class="dkpdf-template-set-badges">${badges}</div>
						</div>
						<div class="dkpdf-template-set-meta">
							<span class="dkpdf-template-version">v${templateSet.version}</span>
							${templateSet.author ? `<span class="dkpdf-template-author">by ${templateSet.author}</span>` : ''}
						</div>
						${templateSet.description ? `<p class="dkpdf-template-description">${templateSet.description}</p>` : ''}
						${tagsHtml}
						${filesHtml}
					</div>
					<div class="dkpdf-template-set-actions">
						${deleteButton}
					</div>
				</li>
			`;

			listEl.insertAdjacentHTML('beforeend', listItem);
		});
	}

	async uploadTemplateSet(file) {
		const i18n = window.dkpdf_ajax?.i18n?.template_sets || {};

		if (!file.name.toLowerCase().endsWith('.zip')) {
			this.showMessage(i18n.only_zip_files || 'Only ZIP files are supported.', 'error');
			return;
		}

		const uploadBtn = document.getElementById('dkpdf-upload-set-btn');
		const originalText = uploadBtn.textContent;
		uploadBtn.disabled = true;
		uploadBtn.textContent = i18n.uploading || 'Uploading...';
		this.clearMessage();

		try {
			const response = await ajaxRequest('dkpdf_upload_template_set', {
				template_file: file
			});

			if (response.success) {
				this.showMessage(response.data.message, 'success');
				await this.loadTemplateSets();
				this.resetUploadForm();
				this.refreshTemplateSelector();
			} else {
				uploadBtn.disabled = false;
				uploadBtn.textContent = originalText;
				this.showMessage(response.data.message || (i18n.upload_failed || 'Failed to upload template set.'), 'error');
			}
		} catch (error) {
			uploadBtn.disabled = false;
			uploadBtn.textContent = originalText;
			this.showMessage(i18n.upload_failed || 'Failed to upload template set.', 'error');
		}
	}

	resetUploadForm() {
		const i18n = window.dkpdf_ajax?.i18n?.template_sets || {};

		this.selectedFile = null;

		const fileInput = document.getElementById('dkpdf-template-zip-input');
		if (fileInput) {
			fileInput.value = '';
		}

		const filenameDisplay = document.getElementById('dkpdf-selected-zip-filename');
		if (filenameDisplay) {
			filenameDisplay.textContent = '';
			filenameDisplay.style.display = 'none';
		}

		const selectFileBtn = document.getElementById('dkpdf-select-template-zip-btn');
		const uploadBtn = document.getElementById('dkpdf-upload-set-btn');

		if (selectFileBtn) {
			selectFileBtn.style.display = 'inline-block';
			selectFileBtn.disabled = false;
		}
		if (uploadBtn) {
			uploadBtn.style.display = 'none';
			uploadBtn.disabled = false;
			uploadBtn.textContent = i18n.upload_template_set || 'Upload Template Set';
		}
	}

	confirmDelete(templateSetKey, templateSetType, templateSetName) {
		const i18n = window.dkpdf_ajax?.i18n?.template_sets || {};
		const message = (i18n.confirm_delete || 'Are you sure you want to delete "%s"? This action cannot be undone.').replace('%s', templateSetName);

		if (confirm(message)) {
			this.deleteTemplateSet(templateSetKey);
		}
	}

	async deleteTemplateSet(templateSetKey) {
		const i18n = window.dkpdf_ajax?.i18n?.template_sets || {};
		this.clearMessage();

		try {
			const response = await ajaxRequest('dkpdf_delete_template_set', { template_set_key: templateSetKey });

			if (response.success) {
				this.showMessage(response.data.message, 'success');
				await this.loadTemplateSets();
				this.refreshTemplateSelector();
			} else {
				this.showMessage(response.data.message || (i18n.delete_failed || 'Failed to delete template set.'), 'error');
			}
		} catch (error) {
			this.showMessage(i18n.delete_failed || 'Failed to delete template set.', 'error');
		}
	}

	async refreshTemplateSelector() {
		// Reload the page to refresh the template selector dropdown
		// This ensures the dropdown shows updated template sets
		window.location.reload();
	}

	showMessage(message, type) {
		const messageBox = this.modal.querySelector('.dkpdf-modal-message');
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
		const messageBox = this.modal.querySelector('.dkpdf-modal-message');
		empty(messageBox);
		hide(messageBox);
	}
}

// Initialize Template Set Manager when DOM is ready
if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', () => {
		new TemplateSetManager();
	});
} else {
	new TemplateSetManager();
}
