// Register the command.
FCKCommands.RegisterCommand( 'EmbedMovies',
	new FCKDialogCommand( FCKLang['DlgEmbedMoviesTitle'], FCKLang['DlgEmbedMoviesTitle'],
	FCKConfig.PluginsPath + 'fckEmbedMovies/fck_embedmovies.html', 600, 440 )
) ;

// Create and register the Video toolbar button.
var oVideoItem = new FCKToolbarButton( 'EmbedMovies', FCKLang['DlgEmbedMoviesTitle'] ) ;
oVideoItem.IconPath	= FCKConfig.PluginsPath + 'fckEmbedMovies/embedmovies.gif' ;
FCKToolbarItems.RegisterItem( 'EmbedMovies', oVideoItem ) ;
