jQuery(document).ready(function($) {
    'use strict';

    /**
     * Font Manager Modal Handler
     */
    var FontManager = {

        modal: null,
        fonts: [],

        /**
         * Initialize the font manager
         */
        init: function() {
            this.bindEvents();
            this.createModal();
        },

        /**
         * Bind UI events
         */
        bindEvents: function() {
            var self = this;

            // Open modal when "Manage Fonts" button is clicked
            $(document).on('click', '#dkpdf-manage-fonts', function(e) {
                e.preventDefault();
                self.openModal();
            });

            // Close modal on close button click
            $(document).on('click', '.dkpdf-modal-close', function(e) {
                e.preventDefault();
                self.closeModal();
            });

            // Close modal when clicking outside
            $(document).on('click', '.dkpdf-modal-overlay', function(e) {
                if (e.target === this) {
                    self.closeModal();
                }
            });

            // Close modal on ESC key
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && self.modal.is(':visible')) {
                    self.closeModal();
                }
            });

            // Upload font button
            $(document).on('click', '#dkpdf-upload-font-btn', function(e) {
                e.preventDefault();
                $('#dkpdf-font-file-input').trigger('click');
            });

            // File input change
            $(document).on('change', '#dkpdf-font-file-input', function() {
                var file = this.files[0];
                if (file) {
                    self.uploadFont(file);
                }
                // Reset input so same file can be selected again
                $(this).val('');
            });

            // Delete font button
            $(document).on('click', '.dkpdf-delete-font', function(e) {
                e.preventDefault();
                var fontName = $(this).data('font-name');
                var fontType = $(this).data('font-type');
                self.confirmDelete(fontName, fontType);
            });
        },

        /**
         * Create modal HTML structure
         */
        createModal: function() {
            var modalHtml = `
                <div class="dkpdf-modal-overlay" style="display:none;">
                    <div class="dkpdf-modal">
                        <div class="dkpdf-modal-header">
                            <h2>${dkpdf_ajax.i18n.manage_fonts}</h2>
                            <button class="dkpdf-modal-close" aria-label="${dkpdf_ajax.i18n.close}">&times;</button>
                        </div>
                        <div class="dkpdf-modal-body">
                            <div class="dkpdf-modal-actions">
                                <button type="button" id="dkpdf-upload-font-btn" class="button button-primary">
                                    ${dkpdf_ajax.i18n.upload_font}
                                </button>
                                <input type="file" id="dkpdf-font-file-input" accept=".ttf" style="display:none;">
                            </div>
                            <div class="dkpdf-modal-message"></div>
                            <div class="dkpdf-fonts-list-wrapper">
                                <div class="dkpdf-loading">${dkpdf_ajax.i18n.loading}...</div>
                                <ul class="dkpdf-fonts-list"></ul>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            if ($('.dkpdf-modal-overlay').length === 0) {
                $('body').append(modalHtml);
            }

            this.modal = $('.dkpdf-modal-overlay');
        },

        /**
         * Open the modal and load fonts
         */
        openModal: function() {
            this.modal.fadeIn(200);
            this.loadFonts();
        },

        /**
         * Close the modal
         */
        closeModal: function() {
            this.modal.fadeOut(200);
            this.clearMessage();
        },

        /**
         * Load fonts via AJAX
         */
        loadFonts: function() {
            var self = this;

            $('.dkpdf-loading').show();
            $('.dkpdf-fonts-list').empty();

            $.ajax({
                url: dkpdf_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'dkpdf_list_fonts',
                    nonce: dkpdf_ajax.nonce
                },
                success: function(response) {
                    $('.dkpdf-loading').hide();

                    if (response.success && response.data.fonts) {
                        self.fonts = response.data.fonts;
                        self.renderFontsList(response.data.fonts);
                    } else {
                        self.showMessage(dkpdf_ajax.i18n.error_loading_fonts, 'error');
                    }
                },
                error: function() {
                    $('.dkpdf-loading').hide();
                    self.showMessage(dkpdf_ajax.i18n.error_loading_fonts, 'error');
                }
            });
        },

        /**
         * Render fonts list
         */
        renderFontsList: function(fonts) {
            var $list = $('.dkpdf-fonts-list');
            $list.empty();

            if (fonts.length === 0) {
                $list.html(`<li class="dkpdf-no-fonts">${dkpdf_ajax.i18n.no_fonts}</li>`);
                return;
            }

            fonts.forEach(function(font) {
                var badges = '';

                if (font.selected) {
                    badges += `<span class="dkpdf-badge dkpdf-badge-active">${dkpdf_ajax.i18n.active}</span>`;
                }

                // Add category badge for core fonts
                if (font.category) {
                    badges += `<span class="dkpdf-badge dkpdf-badge-category">${font.category}</span>`;
                }

                badges += `<span class="dkpdf-badge dkpdf-badge-${font.type}">${font.type === 'core' ? dkpdf_ajax.i18n.core : dkpdf_ajax.i18n.custom}</span>`;

                var deleteButton = font.selected
                    ? `<button class="button dkpdf-delete-font" disabled data-font-name="${font.name}" data-font-type="${font.type}" title="${dkpdf_ajax.i18n.cannot_delete_active}">${dkpdf_ajax.i18n.delete}</button>`
                    : `<button class="button dkpdf-delete-font" data-font-name="${font.name}" data-font-type="${font.type}">${dkpdf_ajax.i18n.delete}</button>`;

                var listItem = `
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

                $list.append(listItem);
            });
        },

        /**
         * Upload font file
         */
        uploadFont: function(file) {
            var self = this;

            // Validate file extension
            if (!file.name.toLowerCase().endsWith('.ttf')) {
                this.showMessage(dkpdf_ajax.i18n.only_ttf_files, 'error');
                return;
            }

            // Show loading state
            $('#dkpdf-upload-font-btn').prop('disabled', true).text(dkpdf_ajax.i18n.uploading);
            this.clearMessage();

            var formData = new FormData();
            formData.append('action', 'dkpdf_upload_font');
            formData.append('nonce', dkpdf_ajax.nonce);
            formData.append('font_file', file);

            $.ajax({
                url: dkpdf_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    $('#dkpdf-upload-font-btn').prop('disabled', false).text(dkpdf_ajax.i18n.upload_font);

                    if (response.success) {
                        self.showMessage(response.data.message, 'success');
                        self.loadFonts();
                        self.refreshFontSelector();
                    } else {
                        self.showMessage(response.data.message || dkpdf_ajax.i18n.upload_failed, 'error');
                    }
                },
                error: function() {
                    $('#dkpdf-upload-font-btn').prop('disabled', false).text(dkpdf_ajax.i18n.upload_font);
                    self.showMessage(dkpdf_ajax.i18n.upload_failed, 'error');
                }
            });
        },

        /**
         * Show delete confirmation dialog
         */
        confirmDelete: function(fontName, fontType) {
            var message = fontType === 'core'
                ? dkpdf_ajax.i18n.confirm_delete_core.replace('%s', fontName)
                : dkpdf_ajax.i18n.confirm_delete_custom.replace('%s', fontName);

            if (confirm(message)) {
                this.deleteFont(fontName);
            }
        },

        /**
         * Delete font
         */
        deleteFont: function(fontName) {
            var self = this;

            this.clearMessage();

            $.ajax({
                url: dkpdf_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'dkpdf_delete_font',
                    nonce: dkpdf_ajax.nonce,
                    font_name: fontName
                },
                success: function(response) {
                    if (response.success) {
                        self.showMessage(response.data.message, 'success');
                        self.loadFonts();
                        self.refreshFontSelector();
                    } else {
                        self.showMessage(response.data.message || dkpdf_ajax.i18n.delete_failed, 'error');
                    }
                },
                error: function() {
                    self.showMessage(dkpdf_ajax.i18n.delete_failed, 'error');
                }
            });
        },

        /**
         * Format font name for display (matches PHP format_font_name logic)
         */
        formatFontName: function(fontName) {
            // Replace hyphens with spaces
            var name = fontName.replace(/-/g, ' ');

            // Add spaces before uppercase letters (for camelCase)
            name = name.replace(/([a-z])([A-Z])/g, '$1 $2');

            return name;
        },

        /**
         * Refresh font selector dropdown without page reload
         */
        refreshFontSelector: function() {
            var self = this;
            var $dropdown = $('#dkpdf_font_downloader');

            if ($dropdown.length === 0) {
                return;
            }

            // Get current selected value to preserve it
            var currentValue = $dropdown.val();

            $.ajax({
                url: dkpdf_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'dkpdf_list_fonts',
                    nonce: dkpdf_ajax.nonce
                },
                success: function(response) {
                    if (response.success && response.data.fonts) {
                        var fonts = response.data.fonts;

                        // Clear and rebuild dropdown options
                        $dropdown.empty();

                        fonts.forEach(function(font) {
                            var selected = font.name === currentValue ? ' selected="selected"' : '';
                            var displayName = self.formatFontName(font.name);

                            $dropdown.append(
                                '<option value="' + font.name + '"' + selected + '>' +
                                displayName +
                                '</option>'
                            );
                        });
                    }
                }
            });
        },

        /**
         * Show message
         */
        showMessage: function(message, type) {
            var $messageBox = $('.dkpdf-modal-message');
            $messageBox.html(`<div class="notice notice-${type === 'error' ? 'error' : 'success'}"><p>${message}</p></div>`);
            $messageBox.show();

            // Auto-hide success messages after 5 seconds
            if (type === 'success') {
                setTimeout(function() {
                    $messageBox.fadeOut(300, function() {
                        $messageBox.empty();
                    });
                }, 5000);
            }
        },

        /**
         * Clear message
         */
        clearMessage: function() {
            $('.dkpdf-modal-message').empty().hide();
        }
    };

    // Initialize Font Manager
    FontManager.init();
});
