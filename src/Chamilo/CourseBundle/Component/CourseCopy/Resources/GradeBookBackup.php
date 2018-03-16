<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Resources;

/**
 * Class GradeBookBackup.
 */
class GradeBookBackup extends Resource
{
    public $categories;

    /**
     * GradeBookBackup constructor.
     *
     * @param array $categories
     */
    public function __construct($categories)
    {
        parent::__construct(uniqid(), RESOURCE_GRADEBOOK);
        $this->categories = $categories;
    }

    /**
     * @return string
     */
    public function show()
    {
        parent::show();
        echo get_lang('All');
    }
}
