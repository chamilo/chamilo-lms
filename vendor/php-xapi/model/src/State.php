<?php

/*
 * This file is part of the xAPI package.
 *
 * (c) Christian Flothmann <christian.flothmann@xabbuh.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xabbuh\XApi\Model;

/**
 * An activity provider's state stored on a remote LRS.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
final class State
{
    private $activity;
    private $actor;
    private $registrationId;
    private $stateId;

    public function __construct(Activity $activity, Actor $actor, string $stateId, string $registrationId = null)
    {
        if (!$actor instanceof Agent) {
            @trigger_error(sprintf('Passing an instance of "%s" as the second argument is deprecated since 1.2. In 4.0, only instances of "Xabbuh\XApi\Model\Agent" will be accepted.', get_class($actor)), E_USER_DEPRECATED);
        }

        $this->activity = $activity;
        $this->actor = $actor;
        $this->stateId = $stateId;
        $this->registrationId = $registrationId;
    }

    /**
     * Returns the activity.
     */
    public function getActivity(): Activity
    {
        return $this->activity;
    }

    /**
     * Returns the actor.
     *
     * @deprecated since 1.2, to be removed in 4.0
     */
    public function getActor(): Actor
    {
        @trigger_error(sprintf('The "%s()" method is deprecated since 1.2 and will be removed in 4.0, use "%s::getAgent()" instead.', __METHOD__, __CLASS__), E_USER_DEPRECATED);

        return $this->getAgent();
    }

    /**
     * Returns the agent.
     */
    public function getAgent(): Agent
    {
        return $this->actor;
    }

    /**
     * Returns the registration id.
     */
    public function getRegistrationId(): ?string
    {
        return $this->registrationId;
    }

    /**
     * Returns the state's id.
     */
    public function getStateId(): string
    {
        return $this->stateId;
    }
}
