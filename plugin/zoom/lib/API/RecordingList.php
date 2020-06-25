<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

use Exception;

class RecordingList
{
    use Pagination;

    /** @var string Start Date */
    public $from;

    /** @var string End Date */
    public $to;

    /** @var RecordingMeeting[] List of recordings */
    public $meetings;

    public function __construct()
    {
        $this->meetings = [];
    }

    /**
     * @inheritDoc
     */
    public function itemClass($propertyName)
    {
        if ('meetings' === $propertyName) {
            return RecordingMeeting::class;
        }
        throw new Exception("No such array property $propertyName");
    }
}
