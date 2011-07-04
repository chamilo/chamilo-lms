<?php
/* For licensing terms, see /license.txt */

require_once 'Resource.class.php';

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