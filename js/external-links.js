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
			if ( typeof wpExtLinks != 'undefined' )
				setExtLinks( wpExtLinks );
		};

	function setExtLinks( options ) {
		var links = w.document.getElementsByTagName( 'a' );

		// check each <a> element
		for ( var i = 0; i < links.length; i++ ){
			var a = links[ i ],
				href = a.href ? a.href.toLowerCase() : '',
				rel = a.rel ? a.rel.toLowerCase() : '';

			if ( a.href && ( options.excludeClass.length == 0 || a.className.indexOf( options.excludeClass ) )
						&& ( rel.indexOf( 'external' ) > -1
								|| ( ( href.indexOf( options.baseUrl ) === -1 ) &&
										( href.substr( 0, 7 ) == 'http://'
											|| href.substr( 0, 8 ) == 'https://'
											|| href.substr( 0, 6 ) == 'ftp://'  ) ) ) ) {

				// click event for opening in a new window
				addEvt( a, 'click', function( a ){
					return function( e ){
						// open link in a new window
						var n = w.open( a.href, options.target );
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
		// jQuery DOMready method
		$( init );
	} else {
		// use onload when jQuery not available
		addEvt( w, 'load', init );
	}

})( window, typeof jQuery == 'undefined' ? null : jQuery );
