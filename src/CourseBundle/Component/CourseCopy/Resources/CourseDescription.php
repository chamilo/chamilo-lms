<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Resources;

/**
 * A course description.
 *
 * @author Bart Mollet <bart.mollet@hogent.be>
 */
class CourseDescription extends Resource
{
    /**
     * The title.
     */
    public $title;

    /**
     * The content.
     */
    public $content;

    /**
     * The description type.
     */
    public $description_type;

    /**
     * The description progress.
     */
    public $progress;

    /**
     * The resource language ISO code.
     */
    public $language;

    /**
     * Create a new course description.
     *
     * @param int    $id
     * @param string $title
     * @param string $content
     * @param string $descriptionType
     * @param int    $progress
     * @param string $language
     */
    public function __construct($id, $title, $content, $descriptionType, $progress = 0, $language = '')
    {
        parent::__construct($id, RESOURCE_COURSEDESCRIPTION);
        $this->title = $title;
        $this->content = $content;
        $this->description_type = $descriptionType;
        $this->progress = $progress;
        $this->language = $language;
    }

    /**
     * Show this Event.
     */
    public function show(): void
    {
        parent::show();
        echo $this->title;
    }
}
