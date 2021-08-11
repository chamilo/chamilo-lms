<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Actor;

use Chamilo\UserBundle\Entity\User as UserEntity;
use Xabbuh\XApi\Model\Agent;
use Xabbuh\XApi\Model\InverseFunctionalIdentifier;
use Xabbuh\XApi\Model\IRI;

/**
 * Class User.
 *
 * @package Chamilo\PluginBundle\XApi\ToolExperience\Actor
 */
class User extends BaseActor
{
    private $user;

    public function __construct(UserEntity $user)
    {
        $this->user = $user;
    }

    public function generate(): Agent
    {
        return new Agent(
            InverseFunctionalIdentifier::withMbox(
                IRI::fromString('mailto:'.$this->user->getEmail())
            ),
            $this->user->getCompleteName()
        );
    }
}
