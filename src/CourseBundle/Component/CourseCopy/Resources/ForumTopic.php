<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Resources;

class ForumTopic extends Resource
{
    public ?string $title = null;
    public ?string $topic_poster_name = null;
    public ?string $title_qualify = null;

    public function __construct($obj)
    {
        parent::__construct($obj->thread_id, RESOURCE_FORUMTOPIC);
        $this->obj = $obj;

        $this->title             = (string)($obj->thread_title ?? $obj->title ?? '');
        $this->topic_poster_name = (string)($obj->thread_poster_name ?? $obj->topic_poster_name ?? '');
        $this->title_qualify     = (string)($obj->thread_title_qualify ?? $obj->title_qualify ?? '');
    }

    public function show()
    {
        parent::show();

        $date  = $this->obj->thread_date ?? ($this->obj->time ?? null);
        $extra = $date ? api_convert_and_format_date($date) : '';

        if (!empty($this->obj->thread_poster_id)) {
            $ui = api_get_user_info($this->obj->thread_poster_id);
            $name = $ui['complete_name'] ?? $this->topic_poster_name;
            $extra = ($name ? $name.', ' : '').$extra;
        } elseif (!empty($this->topic_poster_name)) {
            $extra = $this->topic_poster_name.', '.$extra;
        }

        echo $this->title.($this->title_qualify ? ' ['.$this->title_qualify.']' : '').($extra ? ' ('.$extra.')' : '');
    }
}
