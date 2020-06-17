<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

use Exception;

class MeetingRegistrantList
{
    use Pagination;

    /** @var MeetingRegistrantListItem[] */
    public $registrants;

    /**
     * MeetingRegistrantList constructor.
     */
    public function __construct()
    {
        $this->registrants = [];
    }

    /**
     * @inheritDoc
     */
    public function itemClass($propertyName)
    {
        if ('registrants' === $propertyName) {
            return MeetingRegistrantListItem::class;
        }
        throw new Exception("no such array property $propertyName");
    }
}
