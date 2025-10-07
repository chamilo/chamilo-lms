<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Resources;

class ForumCategory extends Resource
{
    public ?string $title = null;
    public ?string $description = null;

    public function __construct($obj)
    {
        parent::__construct($obj->cat_id, RESOURCE_FORUMCATEGORY);
        $this->obj = $obj;

        $this->title       = (string) ($obj->cat_title ?? $obj->title ?? '');
        $this->description = (string) ($obj->cat_comment ?? $obj->description ?? '');
    }

    public function show()
    {
        parent::show();
        echo $this->obj->cat_title ?? $this->obj->title ?? '';
    }
}
