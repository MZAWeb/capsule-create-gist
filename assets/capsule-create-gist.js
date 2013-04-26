require( ["cf/js/capsule"], function () {


	Capsule.createGist = function ( postId, $article ) {
		$article.addClass( 'unstyled' ).children().end().append( Capsule.spinner() );
		Capsule.post(
			capsuleL10n.endpointAjax,
			{
				capsule_action: 'create_gist',
				post_id       : postId
			},
			function ( response ) {
				if ( response.result == 'success' ) {
					window.prompt( "Copy to clipboard: Ctrl+C, Enter", response.msg );
				}
				else {
					alert( response.msg );
				}
				$article.removeClass( 'unstyled' ).children().end().find( '.spinner' ).remove();
			},
			'json'
		);
	};


	jQuery( 'div.body' ).on( 'click', 'a.capsule-create-gist-icon', function ( e ) {
		var $article = jQuery( this ).closest( 'article' ),
			postId = $article.data( 'post-id' );

		Capsule.createGist( postId, $article );

		e.stopPropagation();
		e.preventDefault();

	} );

} );