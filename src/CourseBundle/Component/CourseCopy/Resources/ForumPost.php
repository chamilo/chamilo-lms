<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Resources;

/**
 * A forum-post.
 *
 * @author Bart Mollet <bart.mollet@hogent.be>
 *
 * @package chamilo.backup
 */
class ForumPost extends Resource
{
    /**
     * Create a new ForumPost.
     */
    public function __construct($obj)
    {
        parent::__construct($obj->post_id, RESOURCE_FORUMPOST);
        $this->obj = $obj;
    }

    /**
     * Show this resource.
     */
    public function show()
    {
        parent::show();
        echo $this->obj->title.' ('.$this->obj->poster_name.', '.$this->obj->post_date.')';
    }
}
