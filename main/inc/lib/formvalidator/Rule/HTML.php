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
require_once ('HTML/QuickForm/Rule.php');
require_once (api_get_path(SYS_PATH).'main/inc/lib/kses-0.2.2/kses.php');
/**
 * QuickForm rule to check a html
 */
class HTML_QuickForm_Rule_HTML extends HTML_QuickForm_Rule
{
	/**
	 * Function to validate HTML
	 * @see HTML_QuickForm_Rule
	 * @param string $html
	 * @return boolean True if html is valid
	 */
	function validate($html, $mode = NO_HTML)
	{
		$allowed_tags = $this->get_allowed_tags ($mode,$fullpage);
		$cleaned_html = kses($html, $allowed_tags);
		return $html == $cleaned_html;
	}
	/**
	 * Get allowed tags
	 * @param int $mode NO_HTML, STUDENT_HTML, TEACHER_HTML,
	 * STUDENT_HTML_FULLPAGE or TEACHER_HTML_FULLPAGE
	 * @param boolean $fullpage If true, the allowed tags for full-page editing
	 * are returned.
	 */
	function get_allowed_tags($mode)
	{
		//Include the allowed tags
		include(dirname(__FILE__).'/allowed_tags.inc.php');
		switch($mode)
		{
			case NO_HTML:
				return array();
				break;
			case STUDENT_HTML:
				return $allowed_tags_student;
				break;
			case STUDENT_HTML_FULLPAGE:
				return array_merge($allowed_tags_student,$allowed_tags_student_full_page);
				break;
			case TEACHER_HTML:
				return $allowed_tags_teacher;
				break;
			case TEACHER_HTML_FULLPAGE:
				return array_merge($allowed_tags_teacher,$allowed_tags_teacher_full_page);
				break;
			default:
				return array();
				break;
		}
	}
}
?>