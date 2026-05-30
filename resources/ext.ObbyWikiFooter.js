$( function () {
	var $footer = $( '.obbywiki-footer' );
	var $relatedPosts = $( '.discourse-related-posts' );

	if ( $footer.length && $relatedPosts.length ) {
		$footer.insertAfter( $relatedPosts.last() );
	}

	var $bottom = $footer.find( '.citizen-footer__bottom' );
	var $icons = $footer.find( '#footer-icons' );
	var $badges = $icons.find( '.ow-footer-mw-badge, .ow-footer-dgo-badge' );
	var collapse_timer = null;

	function set_active_badge( $badge ) {
		$badges.removeClass( 'is-active' );
		if ( $badge && $badge.length ) {
			$badge.addClass( 'is-active' );
		}
	}

	function activate_row() {
		clearTimeout( collapse_timer );
		$bottom.addClass( 'ow-footer-icons-row--active' );
	}

	function deactivate_row() {
		collapse_timer = setTimeout( function () {
			$bottom.removeClass( 'ow-footer-icons-row--active' );
			set_active_badge( null );
		}, 120 );
	}

	$icons.on( 'mouseenter', activate_row );
	$icons.on( 'mouseleave', deactivate_row );

	$badges.on( 'mouseenter', function () {
		activate_row();
		set_active_badge( $( this ) );
	} );

	$icons.on( 'focusin', function ( e ) {
		var $badge = $( e.target ).closest( '.ow-footer-mw-badge, .ow-footer-dgo-badge' );
		if ( $badge.length ) {
			activate_row();
			set_active_badge( $badge );
		}
	} );

	$icons.on( 'focusout', function ( e ) {
		if ( !$icons[ 0 ].contains( e.relatedTarget ) ) {
			deactivate_row();
		}
	} );
} );
