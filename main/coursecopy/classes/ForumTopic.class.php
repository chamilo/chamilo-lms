<?php

/* For licensing terms, see /license.txt */
/**
 * Forum topic backup script
 * @package chamilo.backup
 */
/**
 * Code
 */
require_once 'Resource.class.php';

/**
 * A forum-topic/thread
 * @author Bart Mollet <bart.mollet@hogent.be>
 * @package chamilo.backup
 */
class ForumTopic extends Resource {
    /**
     * Create a new ForumTopic
     */
    /* function ForumTopic($id, $title, $time, $topic_poster_id, $topic_poster_name, $forum_id, $last_post, $replies, $views = 0, $sticky = 0, $locked = 0, 
      $time_closed = null, $weight = 0, $title_qualify = null, $qualify_max = 0) */
    function ForumTopic($obj) {
        parent::Resource($obj->thread_id, RESOURCE_FORUMTOPIC);
        $this->obj = $obj;
        /*
          $this->title = $title;
          $this->time = $time;
          $this->topic_poster_id = $topic_poster_id;
          $this->topic_poster_name = $topic_poster_name;
          $this->forum_id = $forum_id;
          $this->last_post = $last_post;
          $this->replies = $replies;
          $this->views = $views;
          $this->sticky = $sticky;
          $this->locked = $locked;
          $this->time_closed = $time_closed;
          $this->weight = $weight;
          $this->title_qualify = $title_qualify;
          $this->qualify_max = $qualify_max; */
    }

    /**
     * Show this resource
     */
    function show() {
        parent::show();
        echo $this->obj->thread_title . ' (' . $this->obj->topic_poster_name . ', ' . $this->obj->topic_time . ')';
    }

}