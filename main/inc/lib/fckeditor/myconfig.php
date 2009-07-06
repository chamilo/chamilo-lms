<?php

/*
 *	Dokeos - elearning and course management software
 *
 *	Copyright (c) 2009 Dokeos SPRL
 *	Copyright (c) 2009 Juan Carlos Raña
 *	Copyright (c) 2009 Ivan Tcholakov
 *
 *	For a full list of contributors, see "credits.txt".
 *	The full license can be read in "license.txt".
 *
 *	This program is free software; you can redistribute it and/or
 *	modify it under the terms of the GNU General Public License
 *	as published by the Free Software Foundation; either version 2
 *	of the License, or (at your option) any later version.
 *
 *	See the GNU General Public License for more details.
 *
 * Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
 * Mail: info@dokeos.com
 */

/*
 * Custom editor configuration settings, php-side.
 *
 * Follow this link for more information:
 * http://docs.fckeditor.net/FCKeditor_2.x/Developers_Guide/Configuration/Configuration_Options
 *
 */

/*
 * Editor's toolbar definitions.
 */

$config['ToolbarSets']['EditHomePage'] =
	array(
		array(
			'FitWindow', 'PasteWord', 'Link', 'Unlink', 'Anchor', '-',
			'Image', 'flvPlayer', 'Flash', 'EmbedMovies', 'MP3', 'YouTube', 'Table', 'Rule', '-',
			'Subscript', 'Superscript', '-', 'OrderedList', 'UnorderedList', 'Outdent', 'Indent', '-',
			'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyFull'
		),
		'/',
		array(
			'FontFormat', 'Style', 'FontName', 'FontSize', 'Bold', 'Italic', 'Underline', 'StrikeThrough', 'TextColor', 'BGColor', '-',
			'Source', 'mimetex'
		)
	) ;

// To be continued ...