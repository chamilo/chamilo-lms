<?php
/* For licensing terms, see /license.txt */

// To add your new link type here:
// - define a unique type id
// - add include
// - change create() and get_all_types()
// Please do not change existing values, they are used in the database !
define('LINK_EXERCISE',				1);
define('LINK_DROPBOX',				2);
define('LINK_STUDENTPUBLICATION',	3);
define('LINK_LEARNPATH',			4);
define('LINK_FORUM_THREAD',			5);
//define('LINK_WORK',6);
define('LINK_ATTENDANCE',			7);
define('LINK_SURVEY',				8);

require_once 'gradebookitem.class.php';
require_once 'abstractlink.class.php';
require_once 'exerciselink.class.php';
require_once 'evallink.class.php';
require_once 'dropboxlink.class.php';
require_once 'studentpublicationlink.class.php';
require_once 'learnpathlink.class.php';
require_once 'forumthreadlink.class.php';
require_once 'attendancelink.class.php';
require_once 'surveylink.class.php';

/**
 * Factory for link objects
 * @author Bert Steppé
 * @package chamilo.gradebook
 */
class LinkFactory
{

	/**
	 * Retrieve links and return them as an array of extensions of AbstractLink.
	 * @param $id link id
	 * @param $type link type
	 * @param $ref_id reference id
	 * @param $user_id user id (link owner)
	 * @param $course_code course code
	 * @param $category_id parent category
	 * @param $visible visible
	 */
	public function load ($id = null, $type = null, $ref_id = null, $user_id = null, $course_code = null, $category_id = null, $visible = null) {
		return AbstractLink::load($id, $type, $ref_id, $user_id, $course_code, $category_id, $visible);
	}


	/**
	 * Get the link object referring to an evaluation
	 */
	public function get_evaluation_link ($eval_id) {
		$links = AbstractLink :: load(null, null, $eval_id);
		foreach ($links as $link) {
			if (is_a($link, 'EvalLink')) {
				return $link;
			}
		}
		return null;
	}

    /**
     * Find links by name
     * @param string $name_mask search string
     * @return array link objects matching the search criterium
     */
    public function find_links ($name_mask,$selectcat) {
    	return AbstractLink::find_links($name_mask,$selectcat);
    }

	/**
	 * Static method to create specific link objects
	 * @param $type link type
	 */
	public function create ($type) {
		switch ($type) {
			case LINK_EXERCISE:
				return new ExerciseLink();
			case LINK_DROPBOX:
				return new DropboxLink();
			case LINK_STUDENTPUBLICATION:
				return new StudentPublicationLink();
			case LINK_LEARNPATH:
				return new LearnpathLink();
			case LINK_FORUM_THREAD:
				return new ForumThreadLink();
			case LINK_ATTENDANCE:
				return new AttendanceLink();
			case LINK_SURVEY:
				return new SurveyLink();
		}
		return null;
	}

	/**
	 * Return an array of all known link types
	 */
	public function get_all_types () {
		//LINK_DROPBOX,
		return array (LINK_EXERCISE,
					  //LINK_DROPBOX,
					  LINK_STUDENTPUBLICATION,
					  LINK_LEARNPATH,
                      LINK_FORUM_THREAD,
                      LINK_ATTENDANCE,
                      LINK_SURVEY
					  );
	}

}