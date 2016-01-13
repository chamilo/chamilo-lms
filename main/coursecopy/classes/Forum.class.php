<?php
/* For licensing terms, see /license.txt */

require_once 'Resource.class.php';

/**
 * Class forum
 * @author Bart Mollet <bart.mollet@hogent.be>
 * @package chamilo.backup
 */
class Forum extends Coursecopy\Resource
{
    /**
     * The title
     */
    var $title;

    /**
     * The description
     */
    var $description;

    /**
     * Category-id
     */
    var $category_id;

    /**
     * Last post
     */
    var $last_post;

    /**
     * Number of threads
     */
    var $topics;

    /**
     * Number of posts
     */
    var $posts;

    /**
     * Allow anonimous
     */
    var $allow_anonymous;

    /**
     * Allow edit
     */
    var $allow_edit;

    /**
     * Approval direct post
     */
    var $approval_direct_post;

    /**
     * Allow attachments
     */
    var $allow_attachements;

    /**
     * Allow new threads
     */
    var $allow_new_topics;

    /**
     * Default view
     */
    var $default_view;

    /**
     * Group forum
     */
    var $of_group;

    /**
     * Public/private group forum
     */
    var $group_public_private;

    /**
     * Order
     */
    var $order;

    /**
     * Locked or not
     */
    var $locked;

    /**
     * Session id
     */
    var $session_id;

    /**
     * Image
     */
    var $image;

    /**
     * Create a new Forum
     */
    function __construct($obj)
    {
        parent::__construct($obj->forum_id, RESOURCE_FORUM);
        $this->obj = $obj;
    }

    /**
     * Show this resource
     */
    function show() {
        parent::show();
        echo $this->obj->forum_title;
    }

}
