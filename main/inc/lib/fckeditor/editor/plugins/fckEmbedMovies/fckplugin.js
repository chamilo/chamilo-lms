//TODO : make the language file work

FCKCommands.RegisterCommand(
	'EmbedMovies',
	new FCKDialogCommand(
		'EmbedMovies',
		'Embed movies',
		FCKPlugins.Items['fckEmbedMovies'].Path + 'fck_embedmovies.html',
		450,
		400
	)
);
// Create the "EmbedMovies" toolbar button.
// FCKToolbarButton( commandName, label, tooltip, style, sourceView,contextSensitive )
var oEmbedMoviesItem = new FCKToolbarButton( 'EmbedMovies', FCKLang["DlgEmbedMoviesBtn"], FCKLang["DlgEmbedMoviesTooltip"], null, false, true); 
oEmbedMoviesItem.IconPath = FCKConfig.PluginsPath + 'fckEmbedMovies/embedmovies.gif'; 

// 'EmbedMovies' is the name that is used in the toolbar config.
FCKToolbarItems.RegisterItem( 'EmbedMovies', oEmbedMoviesItem );

