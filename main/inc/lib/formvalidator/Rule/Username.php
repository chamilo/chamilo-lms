<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2005 Dokeos S.A.
	Copyright (c) Bart Mollet, Hogeschool Gent

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, 44 rue des palais, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/

require_once 'HTML/QuickForm/Rule.php';

/**
 * QuickForm rule to check if a username is of the correct format
 */
class HTML_QuickForm_Rule_Username extends HTML_QuickForm_Rule {
	/**
	 * Function to check if a username is of the correct format
	 * @see HTML_QuickForm_Rule
	 * @param string $username Wanted username
	 * @return boolean True if username is of the correct format
	 * @author Modified by Ivan Tcholakov, 15-SEP-2009. The validation rule is served by the UserManager class as of this moment.
	 */
	function validate($username) {
		if (!class_exists('UserManager')) {
			require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
		}
		return UserManager::is_username_valid($username);
	}
}
