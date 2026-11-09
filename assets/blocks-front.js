( function () {
	function initReadmeTabs( root ) {
		var tabs = root.querySelectorAll( '.wpgit-readme-tab' );
		var panels = root.querySelectorAll( '.wpgit-readme-panel' );
		if ( ! tabs.length || ! panels.length ) {
			return;
		}

		tabs.forEach( function ( tab ) {
			tab.addEventListener( 'click', function () {
				var index = tab.getAttribute( 'data-tab' );
				tabs.forEach( function ( t ) {
					var active = t === tab;
					t.classList.toggle( 'is-active', active );
					t.setAttribute( 'aria-selected', active ? 'true' : 'false' );
				} );
				panels.forEach( function ( panel, i ) {
					if ( String( i ) === String( index ) ) {
						panel.removeAttribute( 'hidden' );
					} else {
						panel.setAttribute( 'hidden', 'hidden' );
					}
				} );
			} );
		} );
	}

	function boot() {
		document.querySelectorAll( '[data-wpgit-readme]' ).forEach( initReadmeTabs );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
} )();
