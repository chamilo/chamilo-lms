<?php

namespace FOS\MessageBundle\Model;

/**
 * A user participating to a thread.
 * May be implemented by a FOS\UserBundle user document or entity.
 * Or anything you use to represent users in the application.
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 */
interface ParticipantInterface
{
    /**
     * Gets the unique identifier of the participant
     *
     * @return string
     */
    function getId();
}
