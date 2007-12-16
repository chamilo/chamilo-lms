<?php

// To add your new link type here:
// - define a unique type id
// - add include
// - change create() and get_all_types()


// Please do not change existing values, they are used in the database !
define('LINK_EXERCISE',1);
define('LINK_DROPBOX',2);
define('LINK_STUDENTPUBLICATION',3);
define('LINK_LEARNPATH',4);



include_once('abstractlink.class.php');
include_once('exerciselink.class.php');
include_once('evallink.class.php');
include_once('dropboxlink.class.php');
include_once('studentpublicationlink.class.php');
include_once('learnpathlink.class.php');


/**
 * Factory for link objects
 * @author Bert Stepp�
 * @package dokeos.gradebook
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
	public function load ($id = null, $type = null, $ref_id = null, $user_id = null, $course_code = null, $category_id = null, $visible = null)
	{
		return AbstractLink::load($id, $type, $ref_id, $user_id, $course_code, $category_id, $visible);
	}


	/**
	 * Get the link object referring to an evaluation
	 */
	public function get_evaluation_link ($eval_id)
	{
		$links = AbstractLink :: load(null, null, $eval_id);
		foreach ($links as $link)
		{
			if (is_a($link, 'EvalLink'))
				return $link;
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
    	return AbstractLink::find_links($name_mask,$selectcat);
    }

	/**
	 * Static method to create specific link objects
	 * @param $type link type
	 */
	public function create ($type)
	{
		if ($type == LINK_EXERCISE ) return new ExerciseLink();
		elseif ($type == LINK_DROPBOX ) return new DropboxLink();
		elseif ($type == LINK_STUDENTPUBLICATION ) return new StudentPublicationLink();
		elseif ($type == LINK_LEARNPATH ) return new LearnpathLink();
		else return null;
	}

	/**
	 * Return an array of all known link types
	 */
	public function get_all_types ()
	{
		return array (LINK_EXERCISE,
					  LINK_DROPBOX,
					  LINK_STUDENTPUBLICATION,
					  LINK_LEARNPATH,
					  );
	}

}
?>