// assets/js/taw-property-live.js
jQuery( function( $ ) {

    // show live slug and auto-save state
    function generateSlug( s ) {
        return s.toString().toLowerCase()
            .replace(/\s+/g, '-')           // replace spaces with -
            .replace(/[^\w\-]+/g, '')       // remove all non-word chars
            .replace(/\-\-+/g, '-')         // replace multiple - with single -
            .replace(/^-+/, '')             // trim - from start
            .replace(/-+$/, '');            // trim - from end
    }

    // Insert live UI elements if not present
    if ( $( '#taw-live-slug' ).length === 0 ) {
        $( 'h1' ).first().after('<div style="margin-bottom:10px;"><strong>Live Slug:</strong> <span id="taw-live-slug"></span> <span id="taw-live-save" style="color:#46b450;margin-left:15px;"></span></div>' );
    }

    // Update live slug as user types title
    $( document ).on( 'input', '#taw_property_title', function() {
        var title = $( this ).val();
        $( '#taw-live-slug' ).text( generateSlug( title ) );
    } );

    // Debounced auto-save
    var saveTimer = null;
    var lastAutoSaved = 0;
    var saving = false;

    function autoSave() {
        if ( saving ) {
            return;
        }

        var title = $( '#taw_property_title' ).val();
        var content = $( '#taw_property_content' ).val();

        // minimal check
        if ( title === '' && content === '' ) {
            return;
        }

        saving = true;
        $( '#taw-live-save' ).text( 'Saving...' );

        $.ajax( {
            url: TAWPropertyLive.ajax_url,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'taw_save_property_live',
                security: TAWPropertyLive.nonce,
                title: title,
                content: content
            }
        } ).done( function( res ) {
            if ( res && res.success ) {
                lastAutoSaved = Date.now();
                $( '#taw-live-save' ).text( 'Auto saved ✓' );
                // optionally write post id to hidden input for later use
                if ( res.data && res.data.post_id ) {
                    if ( $( '#taw_auto_saved_id' ).length ) {
                        $( '#taw_auto_saved_id' ).val( res.data.post_id );
                    } else {
                        $( 'form.taw-re-add-form' ).append( '<input type="hidden" id="taw_auto_saved_id" name="taw_auto_saved_id" value="' + res.data.post_id + '">' );
                    }
                }
            } else {
                var msg = ( res && res.data && res.data.message ) ? res.data.message : 'Save failed';
                $( '#taw-live-save' ).text( msg );
            }
        } ).fail( function() {
            $( '#taw-live-save' ).text( 'Network error' );
        } ).always( function() {
            saving = false;
        } );
    }

    // debounce input events
    $( document ).on( 'input change', '.taw-re-add-form input, .taw-re-add-form textarea, .taw-re-add-form select', function() {
        clearTimeout( saveTimer );
        saveTimer = setTimeout( autoSave, 1500 ); // auto-save 1.5s after typing stops
    } );

    // Optional: manual save on submit (prevent double insert)
    $( 'form.taw-re-add-form' ).on( 'submit', function( e ) {
        // Let the form submit normally; if you want to convert submit into AJAX final save,
        // preventDefault() and call AJAX with post status from the select.
        // For now we let server-side flow handle normal submission.
    } );

} );




/*----------------------------------- */

jQuery(document).ready(function($){
    $('.copy-shortcode').click(function(e){
        e.preventDefault();
        var target_id = $(this).data('target');
        var $code = $('#' + target_id);
        var temp = $('<textarea>');
        $('body').append(temp);
        temp.val($code.text()).select();
        document.execCommand('copy');
        temp.remove();
        $(this).text('Copied!').prop('disabled', true);
        setTimeout(() => {
            $(this).text('Copy').prop('disabled', false);
        }, 2000);
    });
});




