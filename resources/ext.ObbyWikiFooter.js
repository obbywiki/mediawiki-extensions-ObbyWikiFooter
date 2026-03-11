$( function () {
	// polyfill
	var $footer = $( '.obbywiki-footer' );
	var $relatedPosts = $( '.discourse-related-posts' );

	if ( $footer.length && $relatedPosts.length ) {
		$footer.insertAfter( $relatedPosts.last() );
	}
} );