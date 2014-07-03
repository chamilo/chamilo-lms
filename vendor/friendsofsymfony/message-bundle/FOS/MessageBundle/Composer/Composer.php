<?php

namespace FOS\MessageBundle\Composer;

use FOS\MessageBundle\ModelManager\MessageManagerInterface;
use FOS\MessageBundle\Sender\SenderInterface;
use FOS\MessageBundle\Model\ThreadInterface;
use FOS\MessageBundle\ModelManager\ThreadManagerInterface;
use FOS\MessageBundle\MessageBuilder\NewThreadMessageBuilder;
use FOS\MessageBundle\MessageBuilder\ReplyMessageBuilder;

/**
 * Factory for message builders
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 */
class Composer implements ComposerInterface
{
    /**
     * Message manager
     *
     * @var MessageManagerInterface
     */
    protected $messageManager;

    /**
     * Thread manager
     *
     * @var ThreadManagerInterface
     */
    protected $threadManager;

    public function __construct(MessageManagerInterface $messageManager, ThreadManagerInterface $threadManager)
    {
        $this->messageManager = $messageManager;
        $this->threadManager = $threadManager;
    }

    /**
     * Starts composing a message, starting a new thread
     *
     * @return NewThreadMessageBuilder
     */
    public function newThread()
    {
        $thread = $this->threadManager->createThread();
        $message = $this->messageManager->createMessage();

        return new NewThreadMessageBuilder($message, $thread);
    }

    /**
     * Starts composing a message in a reply to a thread
     *
     * @return ReplyMessageBuilder
     */
    public function reply(ThreadInterface $thread)
    {
        $message = $this->messageManager->createMessage();

        return new ReplyMessageBuilder($message, $thread);
    }
}
