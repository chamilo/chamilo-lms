<?php
/* For licensing terms, see /license.txt */

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
 * Class LinkFactory
 * Factory for link objects
 * @author Bert Steppé
 * @package chamilo.gradebook
 */
class LinkFactory
{
	/**
	 * Retrieve links and return them as an array of extensions of AbstractLink.
	 * @param int $id link id
	 * @param int $type link type
	 * @param int $ref_id reference id
	 * @param int $user_id user id (link owner)
	 * @param string $course_code course code
	 * @param int $category_id parent category
	 * @param int $visible visible
	 */
    public static function load(
        $id = null,
        $type = null,
        $ref_id = null,
        $user_id = null,
        $course_code = null,
        $category_id = null,
        $visible = null
    ) {
        return AbstractLink::load(
            $id,
            $type,
            $ref_id,
            $user_id,
            $course_code,
            $category_id,
            $visible
        );
    }

	/**
	 * Get the link object referring to an evaluation
	 */
	public function get_evaluation_link ($eval_id)
    {
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
    public function find_links ($name_mask,$selectcat)
    {
    	return AbstractLink::find_links($name_mask, $selectcat);
    }

	/**
	 * Static method to create specific link objects
	 * @param $type link type
	 */
	public static function create ($type)
    {
        $type = intval($type);
		switch ($type) {
			case LINK_EXERCISE:
				return new ExerciseLink();
            case LINK_HOTPOTATOES:
                return new ExerciseLink(1);
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
     * @return array
	 */
	public static function get_all_types ()
    {
		//LINK_DROPBOX,
		return array (
            LINK_EXERCISE,
            //LINK_DROPBOX,
            LINK_HOTPOTATOES,
            LINK_STUDENTPUBLICATION,
            LINK_LEARNPATH,
            LINK_FORUM_THREAD,
            LINK_ATTENDANCE,
            LINK_SURVEY
        );
	}

    public function delete()
    {

    }
}
