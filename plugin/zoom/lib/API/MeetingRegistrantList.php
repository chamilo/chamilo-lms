<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

class MeetingRegistrantList
{
    use Pagination;

    /** @var MeetingRegistrantListItem[] */
    public $registrants;
}
