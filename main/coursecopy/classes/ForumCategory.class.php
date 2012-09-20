<?php

/* For licensing terms, see /license.txt */
/**
 * Forum category backup class
 * @package chamilo.backup
 */
/**
 * Code
 */
require_once 'Resource.class.php';

/**
 * A forum-category
 * @author Bart Mollet <bart.mollet@hogent.be>
 * @package chamilo.backup
 */
class ForumCategory extends Resource {
    /**
     * Create a new ForumCategory
     */
    function ForumCategory($obj) {
        parent::Resource($obj->cat_id, RESOURCE_FORUMCATEGORY);
        $this->obj = $obj;
    }

    /**
     * Show this resource
     */
    function show() {
        parent::show();
        echo $this->obj->cat_title;
    }
}
