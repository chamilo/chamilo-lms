<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Resources;

/**
 * A LinkCategory.
 *
 * @author Bart Mollet <bart.mollet@hogent.be>
 */
class LinkCategory extends Resource
{
    /**
     * The title.
     */
    public $title;

    /**
     * The description.
     */
    public $description;

    /**
     * The display order.
     */
    public $display_order;

    /**
     * Create a new LinkCategory.
     *
     * @param int    $id
     * @param string $title
     * @param string $description
     * @param mixed  $display_order
     */
    public function __construct($id, $title, $description, $display_order)
    {
        parent::__construct($id, RESOURCE_LINKCATEGORY);
        $this->title = $title;
        $this->description = $description;
        $this->display_order = $display_order;
    }

    /**
     * Show this LinkCategory.
     */
    public function show(): void
    {
        parent::show();
        echo $this->title.' '.$this->description.'<br />';
    }
}
