<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

/**
 * Class Registrant.
 *
 * Structure of the information to send the server in order to register someone to a meeting.
 */
class MeetingRegistrant extends RegistrantSchema
{
    public $auto_approve;

    public function __construct()
    {
        parent::__construct();

        $this->auto_approve = true;
    }
}
