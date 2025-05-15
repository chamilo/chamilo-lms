<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

use DateTime;
use Exception;

/**
 * Class RecordingList. A list of past meeting instance recordings generated between two dates.
 *
 * @see RecordingMeeting
 */
class RecordingList
{
    use PaginationToken;

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
     * Retrieves all recordings from a period of time.
     *
     * @param DateTime $startDate first day of the period
     * @param DateTime $endDate   last day of the period
     *
     * @throws Exception
     *
     * @return RecordingMeeting[] all recordings from that period
     */
    public static function loadPeriodRecordings($startDate, $endDate)
    {
        return static::loadItems(
            'meetings',
            'users/me/recordings',
            [
                'from' => $startDate->format('Y-m-d'),
                'to' => $endDate->format('Y-m-d'),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function itemClass($propertyName)
    {
        if ('meetings' === $propertyName) {
            return RecordingMeeting::class;
        }
        throw new Exception("No such array property $propertyName");
    }
}
