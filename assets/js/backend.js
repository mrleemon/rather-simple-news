( function( $ ) {

    $( function() {

        var file_frame;
        
        $( '#select_pdf' ).on( 'click', function( e ) {
            e.preventDefault();
            
            // If the media frame already exists, reopen it.
            if (file_frame) {
                file_frame.open();
                return;
            }
            
            // Create the media frame.
            file_frame = wp.media.frames.file_frame = wp.media({
                title: $( this ).data( 'choose' ),
                library: {
                    type: 'application/pdf'
                },
                multiple: false
            });
            
            // When a file is selected, run a callback.
            file_frame.on( 'select', function() {
                var selection = file_frame.state().get( 'selection' );
                selection.map( function( attachment ) {
                    attachment = attachment.toJSON();
                    if ( attachment.id ) {
                        // Set which variable you want the field to have
                        $( '#article_pdf' ).val( attachment.url );
                    }
                });
            });
            
            // Finally, open the modal
            file_frame.open();
        });
	
    } );

} )( jQuery );