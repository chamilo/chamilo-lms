<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Resources;

class ForumPost extends Resource
{
    public ?string $title = null;
    public ?string $text = null;
    public ?string $poster_name = null;

    public function __construct($obj)
    {
        parent::__construct($obj->post_id, RESOURCE_FORUMPOST);
        $this->obj = $obj;

        $this->title       = (string)($obj->post_title ?? $obj->title ?? '');
        $this->text        = (string)($obj->post_text  ?? $obj->text  ?? '');
        $this->poster_name = (string)($obj->poster_name ?? '');
    }

    public function show()
    {
        parent::show();

        $date = $this->obj->post_date ?? ($this->obj->time ?? null);
        $dateStr = $date ? api_convert_and_format_date($date) : '';

        $extra = $this->poster_name ? $this->poster_name : '';
        if ($dateStr) {
            $extra = $extra ? ($extra.', '.$dateStr) : $dateStr;
        }

        echo $this->title.($extra ? ' ('.$extra.')' : '');
    }
}
