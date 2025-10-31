document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    /**
     * Initialize Select2 for Button Visibility Roles field
     * Enhances the multi-select with Select2 UI for better UX
     */

    // Wait a bit to ensure DOM is fully ready
    setTimeout(function() {
        const rolesSelect = document.getElementById('button_visibility_roles');

        if (rolesSelect) {
            // Check if already initialized
            if (rolesSelect.classList.contains('select2-hidden-accessible')) {
                return;
            }

            // Initialize Select2 with all options already in the HTML
            jQuery(rolesSelect).select2({
                placeholder: 'Select roles...',
                width: '100%',
                dropdownAutoWidth: true,
                allowClear: false
            });
        }
    }, 100);
});
