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
    public $title;

    /**
     * The description
     */
    public $description;

    /**
     * Category-id
     */
    public $category_id;

    /**
     * Last post
     */
    public $last_post;

    /**
     * Number of threads
     */
    public $topics;

    /**
     * Number of posts
     */
    public $posts;

    /**
     * Allow anonimous
     */
    public $allow_anonymous;

    /**
     * Allow edit
     */
    public $allow_edit;

    /**
     * Approval direct post
     */
    public $approval_direct_post;

    /**
     * Allow attachments
     */
    public $allow_attachements;

    /**
     * Allow new threads
     */
    public $allow_new_topics;

    /**
     * Default view
     */
    public $default_view;

    /**
     * Group forum
     */
    public $of_group;

    /**
     * Public/private group forum
     */
    public $group_public_private;

    /**
     * Order
     */
    public $order;

    /**
     * Locked or not
     */
    public $locked;

    /**
     * Session id
     */
    public $session_id;

    /**
     * Image
     */
    public $image;

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
