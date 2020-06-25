<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

use Exception;

class ParticipantList
{
    use Pagination;

    /** @var ParticipantListItem[] */
    public $participants;

    /**
     * ParticipantList constructor.
     */
    public function __construct()
    {
        $this->participants = [];
    }

    /**
     * {@inheritdoc}
     */
    public function itemClass($propertyName)
    {
        if ('participants' === $propertyName) {
            return ParticipantListItem::class;
        }
        throw new Exception("No such array property $propertyName");
    }
}
