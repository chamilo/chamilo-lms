<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

/**
 * Class ParticipantListItem. Item in a list of past meeting instance participants.
 *
 * @see ParticipantList
 */
class ParticipantListItem
{
    /** @var string participant UUID */
    public $id;

    /** @var string display name */
    public $name;

    /** @var string Email address of the user; will be returned if the user logged into Zoom to join the meeting. */
    public $user_email;
}
