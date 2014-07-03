<?php

namespace FOS\MessageBundle\Sender;

use FOS\MessageBundle\Model\MessageInterface;

/**
 * Sends messages
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 */
interface SenderInterface
{
    /**
     * Sends the message
     *
     * @param MessageInterface $message
     */
    function send(MessageInterface $message);
}
