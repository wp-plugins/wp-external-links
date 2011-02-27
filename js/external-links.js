// New Window Links Plugin
function setExternalLinks() {
	var links = document.getElementsByTagName( 'a' ),
		glob = window.globNewWindowLinks || {};

	for ( var i = 0; i < links.length; i++ ){
		var a = links[ i ],
			href = a.href ? a.href.toLowerCase() : '',
			rel = a.rel ? a.rel.toLowerCase() : '';

		if ( a.href	&& ( rel.indexOf( 'external' ) > -1
							|| ( href.indexOf( glob.baseUrl ) === -1 &&
									( href.substr( 0, 7 ) == 'http://'
										|| href.substr( 0, 8 ) == 'https://'
										|| href.substr( 0, 6 ) == 'ftp://'  ) ) ) ) {

			// set target blank
			a.target = '_blank';
		}
	}
}

if ( typeof jQuery == 'undefined' ) {
	window.onload = function() {
		setExternalLinks();
	}
} else {
	jQuery(function(){
		setExternalLinks();
	});
}
