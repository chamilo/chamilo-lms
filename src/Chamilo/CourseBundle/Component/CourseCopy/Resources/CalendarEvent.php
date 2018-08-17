<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Resources;

/**
 * Event backup script.
 *
 * @author Bart Mollet <bart.mollet@hogent.be>
 *
 * @package chamilo.backup
 */
class CalendarEvent extends Resource
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
     * The start date.
     */
    public $start_date;
    /**
     * The end date.
     */
    public $end_date;
    /**
     * The attachment path.
     */
    public $attachment_path;

    /**
     * The attachment filename.
     */
    public $attachment_filename;
    /**
     * The attachment size.
     */
    public $attachment_size;

    /**
     * The attachment comment.
     */
    public $attachment_comment;

    /**
     * Create a new Event.
     *
     * @param int    $id
     * @param string $title
     * @param string $content
     */
    public function __construct(
        $id,
        $title,
        $content,
        $start_date,
        $end_date,
        $attachment_path = null,
        $attachment_filename = null,
        $attachment_size = null,
        $attachment_comment = null,
        $all_day = 0
    ) {
        parent::__construct($id, RESOURCE_EVENT);

        $this->title = $title;
        $this->content = $content;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->all_day = $all_day;
        $this->attachment_path = $attachment_path;
        $this->attachment_filename = $attachment_filename;
        $this->attachment_size = $attachment_size;
        $this->attachment_comment = $attachment_comment;
    }

    /**
     * Show this Event.
     */
    public function show()
    {
        parent::show();
        echo $this->title.' ('.$this->start_date.' -> '.$this->end_date.')';
    }
}
