<?php

/* For licensing terms, see /license.txt */
/**
 * Forum post backup script
 * @package chamilo.backup
 */
/**
 * Code
 */
require_once 'Resource.class.php';

/**
 * A forum-post
 * @author Bart Mollet <bart.mollet@hogent.be>
 * @package chamilo.backup
 */
class ForumPost extends Resource {

    /**
     * Create a new ForumPost
     */
    function ForumPost($obj) {
        parent::Resource($obj->post_id, RESOURCE_FORUMPOST);
        $this->obj = $obj;
    }

    /**
     * Show this resource
     */
    function show() {
        parent::show();
        echo $this->obj->title . ' (' . $this->obj->poster_name . ', ' . $this->obj->post_date . ')';
    }
}
