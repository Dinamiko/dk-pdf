jQuery(document).ready(function($) {

    /***** Colour picker *****/
    $('.colorpicker').hide();
    $('.colorpicker').each( function() {
        if( jQuery().farbtastic ) {
            $(this).farbtastic( $(this).closest('.color-picker').find('.color') );
        }
    });

    $('.color').click(function() {
        $(this).closest('.color-picker').find('.colorpicker').fadeIn();
    });

    $(document).mousedown(function() {
        $('.colorpicker').each(function() {
            var display = $(this).css('display');
            if ( display == 'block' )
                $(this).fadeOut();
        });
    });

    /***** Uploading images *****/

    var file_frame;

    jQuery.fn.uploadMediaFile = function( button, preview_media ) {
        var button_id = button.attr('id');
        var field_id = button_id.replace( '_button', '' );
        var preview_id = button_id.replace( '_button', '_preview' );

        // If the media frame already exists, reopen it.
        if ( file_frame ) {
          file_frame.open();
          return;
        }

        // Create the media frame.
        file_frame = wp.media.frames.file_frame = wp.media({
          title: jQuery( this ).data( 'uploader_title' ),
          button: {
            text: jQuery( this ).data( 'uploader_button_text' ),
          },
          multiple: false
        });

        // When an image is selected, run a callback.
        file_frame.on( 'select', function() {
          attachment = file_frame.state().get('selection').first().toJSON();
          jQuery("#"+field_id).val(attachment.id);
          if( preview_media ) {
            jQuery("#"+preview_id).attr('src',attachment.sizes.thumbnail.url);
          }
        });

        // Finally, open the modal
        file_frame.open();
    }

    jQuery('.image_upload_button').click(function() {
        jQuery.fn.uploadMediaFile( jQuery(this), true );
    });

    jQuery('.image_delete_button').click(function() {
        jQuery(this).closest('td').find( '.image_data_field' ).val( '' );
        jQuery( '.image_preview' ).remove();
        return false;
    });


    /***** Navigation for settings page *****/

    // Make sure each heading has a unique ID.
    jQuery( 'ul#settings-sections.subsubsub' ).find( 'a' ).each( function ( i ) {
        var id_value = jQuery( this ).attr( 'href' ).replace( '#', '' );
        jQuery( 'h3:contains("' + jQuery( this ).text() + '")' ).attr( 'id', id_value ).addClass( 'section-heading' );
    });

    // Create nav links for settings page
    jQuery( '#plugin_settings .subsubsub a.tab' ).click( function ( e ) {
        // Move the "current" CSS class.
        jQuery( this ).parents( '.subsubsub' ).find( '.current' ).removeClass( 'current' );
        jQuery( this ).addClass( 'current' );

        // If "All" is clicked, show all.
        if ( jQuery( this ).hasClass( 'all' ) ) {
            jQuery( '#plugin_settings h3, #plugin_settings form p, #plugin_settings table.form-table, p.submit' ).show();

            return false;
        }

        // If the link is a tab, show only the specified tab.
        var toShow = jQuery( this ).attr( 'href' );

        // Remove the first occurance of # from the selected string (will be added manually below).
        toShow = toShow.replace( '#', '', toShow );

        jQuery( '#plugin_settings h3, #plugin_settings form > p:not(".submit"), #plugin_settings table' ).hide();
        jQuery( 'h3#' + toShow ).show().nextUntil( 'h3.section-heading', 'p, table, table p' ).show();

        return false;
    });

    /***** Select2 for Custom Fields with AJAX *****/

    // Initialize Select2 for custom fields dropdowns
    jQuery('.dkpdf-select2-ajax').each(function() {
        var $select = $(this);
        var postType = $select.data('post-type');
        var ajaxAction = $select.data('ajax-action');

        if (!postType || !ajaxAction) {
            // Fallback to regular Select2 if no AJAX data
            $select.select2({
                placeholder: 'Select custom fields...',
                width: '100%'
            });
            return;
        }

        $select.select2({
            placeholder: '',
            width: '100%',
            minimumInputLength: 0,
            dropdownAutoWidth: true,
            ajax: {
                url: dkpdf_ajax.ajax_url,
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term || '',
                        post_type: postType,
                        action: ajaxAction,
                        nonce: dkpdf_ajax.nonce
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

        // Force responsive behavior after initialization
        $select.on('select2:open', function() {
            $('.select2-dropdown').css('width', '100%');
        });
    });

    // Handle window resize for responsive behavior
    $(window).on('resize', function() {
        $('.dkpdf-select2-ajax').each(function() {
            var $select = $(this);
            if ($select.hasClass('select2-hidden-accessible')) {
                // Trigger resize to force Select2 to recalculate dimensions
                $select.select2('destroy').select2({
                    placeholder: '',
                    width: '100%',
                    minimumInputLength: 0,
                    dropdownAutoWidth: true,
                    data: $select.data('current-selection') || []
                });
            }
        });
    });
});