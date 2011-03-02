/* WP External Links Plugin */
(function( w, $ ){

	var addEvt = function ( el, evt, fn ) {
			if ( $ ) {
				// jQuery method
				$( el ).bind( evt, fn );
			} else if ( el.attachEvent ) {
				// IE method
				el.attachEvent( 'on'+ evt, fn );
			} else if ( el.addEventListener ) {
				// Standard JS method
				el.addEventListener( evt, fn, false );
			}
		},
		init = function () {
			setExtLinks();
		};

	function setExtLinks() {
		var links = w.document.getElementsByTagName( 'a' );

		// check each <a> element
		for ( var i = 0; i < links.length; i++ ){
			var a = links[ i ],
				href = a.href ? a.href.toLowerCase() : '',
				rel = a.rel ? a.rel.toLowerCase() : '';

			if ( a.href	&& ( rel.indexOf( 'external' ) > -1
								|| ( ( href.indexOf( gExtLinks.baseUrl ) === -1 ) &&
										( href.substr( 0, 7 ) == 'http://'
											|| href.substr( 0, 8 ) == 'https://'
											|| href.substr( 0, 6 ) == 'ftp://'  ) ) ) ) {

				// click event
				addEvt( a, 'click', function( a ){
					return function( e ){
						// open link in a new window
						var n = w.open( a.href, gExtLinks.target );
						n.focus();

						// prevent default
						e.returnValue = false;
						if ( e.preventDefault )
							e.preventDefault();
					}
				}( a ));
			}
		}
	}

	if ( $ ) {
		// jQuery method
		$( init );
	} else {
		// when jQuery is not available
		addEvt( w, 'load', init );
	}

})( window, typeof jQuery == 'undefined' ? null : jQuery );
