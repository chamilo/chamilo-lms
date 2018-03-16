<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\ThemeBundle\Event\MessageListEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MessageListener.
 *
 * @package Chamilo\CoreBundle\EventListener
 */
class MessageListener
{
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param MessageListEvent $event
     */
    public function onListMessages(MessageListEvent $event)
    {
        foreach ($this->getMessages() as $message) {
            $event->addMessage($message);
        }
    }

    /**
     * @return array
     */
    protected function getMessages()
    {
        $threads = $this->container->get('fos_message.provider')->getInboxThreads();
        $security = $this->container->get('security.token_storage');
        $token = $security->getToken();
        $user = $token->getUser();

        if (!empty($user)) {
            $messages = [];

            /** @var \Chamilo\CoreBundle\Entity\Thread $thread */
            foreach ($threads as $thread) {
                if (!$thread->isReadByParticipant($user)) {
                    foreach ($thread->getMessages() as $message) {
                        $messages[] = $message;
                    }
                }
            }

            return $messages;
        }
    }
}
