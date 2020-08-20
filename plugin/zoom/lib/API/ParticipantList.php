<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

use Exception;

/**
 * Class ParticipantList
 * List of past meeting instance participants.
 *
 * @see ParticipantListItem;
 */
class ParticipantList
{
    use PaginationToken;

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
     * Retrieves a meeting instance's participants.
     *
     * @param string $instanceUUID
     *
     * @throws Exception
     *
     * @return ParticipantListItem[] participants
     */
    public static function loadInstanceParticipants($instanceUUID)
    {
        return static::loadItems(
            'participants',
            'past_meetings/'.htmlentities($instanceUUID).'/participants'
        );
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
