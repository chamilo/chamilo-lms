<?php

/* For licensing terms, see /license.txt */
/**
 * Forum backup script
 * @package chamilo.backup
 */
/**
 * Code
 */
require_once 'Resource.class.php';

/**
 * A forum
 * @author Bart Mollet <bart.mollet@hogent.be>
 * @package chamilo.backup
 */
class Forum extends Resource {

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
    /* function Forum($id, $title, $description, $category_id, $last_post, $topics, $posts, $allow_anonymous, $allow_edit, $approval_direct_post, $allow_attachements, 
      $allow_new_topics, $default_view, $of_group, $group_public_private, $order, $locked, $session_id, $image)
      { */
    function Forum($obj) {
        parent::Resource($obj->forum_id, RESOURCE_FORUM);
        $this->obj = $obj;

        /*
          $this->title = $title;
          $this->description = $description;
          $this->category_id = $category_id;
          $this->last_post = $last_post;
          $this->topics = $topics;
          $this->posts = $posts;
          $this->allow_anonymous = $allow_anonymous;
          $this->allow_edit = $allow_edit;
          $this->approval_direct_post = $approval_direct_post;
          $this->allow_attachements = $allow_attachements;
          $this->allow_new_topics = $allow_new_topics;
          $this->default_view = $default_view;
          $this->of_group = $of_group;
          $this->group_public_private = $group_public_private;
          $this->order = $order;
          $this->locked = $locked;
          $this->session_id = $session_id;
          $this->image = $image; */
    }

    /**
     * Show this resource
     */
    function show() {
        parent::show();
        echo $this->obj->forum_title;
    }

}
