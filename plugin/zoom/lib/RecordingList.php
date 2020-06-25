<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

use DateTime;
use Exception;

class RecordingList extends API\RecordingList
{
    /**
     * @inheritDoc
     */
    public function itemClass($propertyName)
    {
        if ('meetings' === $propertyName) {
            return Recording::class;
        }
        return parent::itemClass($propertyName);
    }

    /**
     * Retrieves all recordings from a period of time.
     *
     * @param API\Client $client
     * @param DateTime $startDate first day of the period
     * @param DateTime $endDate   last day of the period
     *
     * @throws Exception
     *
     * @return Recording[] all recordings from that period
     */
    public static function loadRecordings($client, $startDate, $endDate)
    {
        return static::loadItems(
            'meetings',
            $client,
            'users/me/recordings',
            [
                'from' => $startDate->format('Y-m-d'),
                'to' => $endDate->format('Y-m-d'),
            ]
        );
    }
}
