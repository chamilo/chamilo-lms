<?php

namespace FOS\MessageBundle\Composer;

use FOS\MessageBundle\Model\ThreadInterface;

/**
 * Factory for message builders
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 */
interface ComposerInterface
{
    /**
     * Starts composing a message, starting a new thread
     *
     * @return MessageBuilderInterface
     */
    function newThread();

    /**
     * Starts composing a message in a reply to a thread
     *
     * @return MessageBuilderInterface
     */
    function reply(ThreadInterface $thread);
}
