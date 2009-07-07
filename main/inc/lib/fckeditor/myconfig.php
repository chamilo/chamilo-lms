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
 * Follow this link for more information:
 * http://docs.fckeditor.net/FCKeditor_2.x/Developers_Guide/Configuration/Configuration_Options
 */

/*
 * Editor's toolbar definitions.
 */

// The following setting is the directory where the online editor's toobar definitions reside in correspondent php-files.
// By default, the directory name is 'toolbars' and it has been created at .../dokeos/main/inc/lib/fckeditor/ .
// For using your customized toolbars, crate another directory 'toolbars_custom' at the same path, i.e.
// create .../dokeos/main/inc/lib/fckeditor/toolbars_custom/ . Then, copy the original php-definition files
// from .../dokeos/main/inc/lib/fckeditor/toolbars/ to the new one. Change the following configuration setting, so it to
// point to the new directory:
// $config['ToolbarSets']['Directory'] = 'toolbars_custom';
// Then you may modify the newly copied toolbar definitions at your will, just keep correct php-syntax.
$config['ToolbarSets']['Directory'] = 'toolbars';
