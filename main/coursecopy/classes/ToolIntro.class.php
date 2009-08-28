<?php // $Id: ToolIntro.class.php 5976 2005-08-12 09:06:04Z bmol $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Bart Mollet (bart.mollet@hogent.be)

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
require_once('Resource.class.php');
/**
 * A WWW-link from the Links-module in a Dokeos-course.
 * @author Bart Mollet <bart.mollet@hogent.be>
 */
class ToolIntro extends Resource
{
	var $id;

	/**
	 * intro text
	 */
	var $intro_text;

	/**
	 * Create a new text introduction
	 * @param int $id The id of this tool introduction in the Dokeos-course
	 * @param string $intro_text
	 */
	function ToolIntro($id, $intro_text)
	{
		parent::Resource($id,RESOURCE_TOOL_INTRO);
		$this->id = $id;
		$this->intro_text = $intro_text;
	}
	/**
	 * Show this resource
	 */
	function show()
	{
		parent::show();
		switch ($this->id)
		{
			case TOOL_DOCUMENT:
				$lang_id = 'Documents';
				break;
			case TOOL_CALENDAR_EVENT:
				$lang_id = 'Agenda';
				break;
			case TOOL_LINK:
				$lang_id = 'Links';
				break;
			case TOOL_LEARNPATH:
				$lang_id = 'LearningPath';
				break;
			case TOOL_ANNOUNCEMENT:
				$lang_id = 'Announcements';
				break;
			case TOOL_FORUM:
				$lang_id = 'Forums';
				break;
			case TOOL_DROPBOX:
				$lang_id = 'Dropbox';
				break;
			case TOOL_QUIZ:
				$lang_id = 'Exercises';
				break;
			case TOOL_USER:
				$lang_id = 'Users';
				break;
			case TOOL_GROUP:
				$lang_id = 'Group';
				break;
			case TOOL_WIKI:
				$lang_id = 'Wiki';
				break;
			case TOOL_STUDENTPUBLICATION:
				$lang_id = 'StudentPublications';
				break;
			case TOOL_COURSE_HOMEPAGE:
				$lang_id = 'CourseHomepageLink';
				break;
			case TOOL_GLOSSARY:
				$lang_id = 'Glossary';
				break;
			case TOOL_NOTEBOOK:
				$lang_id = 'Notebook';
				break;
			default:
				$lang_id = ucfirst($this->id); // This is a wild guess.
		}
		echo '<strong>'.get_lang($lang_id, '').':</strong><br />';
		echo $this->intro_text;
	}
}
?>