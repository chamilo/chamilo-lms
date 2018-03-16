<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Component\CourseCopy\Resources;

/**
 * An announcement.
 *
 * @author Bart Mollet <bart.mollet@hogent.be>
 *
 * @package chamilo.backup
 */
class Announcement extends Resource
{
    /**
     * The title of the announcement.
     */
    public $title;
    /**
     * The content of the announcement.
     */
    public $content;
    /**
     * The date on which this announcement was made.
     */
    public $date;
    /**
     * The display order of this announcement.
     */
    public $display_order;
    /**
     * Has the e-mail been sent?
     */
    public $email_sent;

    public $attachment_path;

    public $attachment_filename;

    public $attachment_size;

    public $attachment_comment;

    /**
     * Create a new announcement.
     *
     * @param int    $id
     * @param string $title
     * @param string $content
     * @param string $date
     * @param int display_order
     */
    public function __construct(
        $id,
        $title,
        $content,
        $date,
        $display_order,
        $email_sent,
        $path,
        $filename,
        $size,
        $comment
    ) {
        parent::__construct($id, RESOURCE_ANNOUNCEMENT);

        $this->content = $content;
        $this->title = $title;
        $this->date = $date;
        $this->display_order = $display_order;
        $this->email_sent = $email_sent;
        $this->attachment_path = $path;
        $this->attachment_filename = $filename;
        $this->attachment_size = $size;
        $this->attachment_comment = $comment;
    }

    /**
     * Show this announcement.
     */
    public function show()
    {
        parent::show();
        echo $this->date.': '.$this->title;
    }
}
