<?php

namespace FOS\MessageBundle\Security;

use FOS\MessageBundle\Model\ParticipantInterface;

/**
 * Provides the authenticated participant
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 */
interface ParticipantProviderInterface
{
    /**
     * Gets the current authenticated user
     *
     * @return ParticipantInterface
     */
    function getAuthenticatedParticipant();
}
