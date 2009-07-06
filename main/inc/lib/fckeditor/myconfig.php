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

/*
 * Platform administrator's tools
 */

// Edit platform home page.
$config['ToolbarSets']['EditHomePage'] =
	array(
		array(
			'NewPage', 'Templates', 'Save', 'Print', 'PageBreak', 'FitWindow', '-', 'PasteWord', '-', 'Undo', 'Redo', '-', 'SelectAll', '-', 'Find'
		),
		array(
			'Link', 'Unlink', 'Anchor'
		),
		array(
			'Image', 'flvPlayer', 'Flash', 'EmbedMovies', 'YouTube', 'MP3'
		),
		array(
			'Table', 'Smiley', 'SpecialChar', 'googlemaps'
		),
		array(
			'FontFormat', 'FontName', 'FontSize'
		),
		array(
			'Bold', 'Italic', 'Underline'
		),
		array(
			'JustifyLeft', 'JustifyCenter', 'JustifyRight', '-', 'OrderedList', 'UnorderedList', '-', 'Outdent', 'Indent', '-', 'TextColor', 'BGColor'
		),
		array(
			'Source'
		)
	);
// These are temporary settings for experimental purposes.
$config['ToolbarCanCollapse']['EditHomePage'] = true; // true (default), false
$config['ToolbarStartExpanded']['EditHomePage'] = true; // true (default) , false
$config['ToolbarLocation']['EditHomePage'] = 'In'; // 'In' (default) , 'None' , 'Out:[TargetId]' , 'Out:[TargetWindow]([TargetId])'
$config['BlockCopyPaste']['EditHomePage'] = false; // true , false (default)


// Insert or Edit a page link in the platform home page.
$config['ToolbarSets']['LinksHomePage'] =
	array(
		array(
			'NewPage', 'Templates', 'Save', 'Print', 'PageBreak', 'FitWindow', '-', 'PasteWord', '-', 'Undo', 'Redo', '-', 'SelectAll', '-', 'Find'
		),
		array(
			'Link', 'Unlink', 'Anchor'
		),
		array(
			'Image', 'flvPlayer', 'Flash', 'EmbedMovies', 'YouTube', 'MP3'
		),
		array(
			'Table', 'Smiley', 'SpecialChar', 'googlemaps'
		),
		array(
			'FontFormat', 'FontName', 'FontSize'
		),
		array(
			'Bold', 'Italic', 'Underline'
		),
		array(
			'JustifyLeft', 'JustifyCenter', 'JustifyRight', '-', 'OrderedList', 'UnorderedList', '-', 'Outdent', 'Indent', '-', 'TextColor', 'BGColor'
		),
		array(
			'Source'
		)
	);

