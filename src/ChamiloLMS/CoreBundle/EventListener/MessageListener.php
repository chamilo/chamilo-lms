<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\CoreBundle\EventListener;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Avanzu\AdminThemeBundle\Event\MessageListEvent;

class MessageListener
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function onListMessages(MessageListEvent $event)
    {
        foreach ($this->getMessages() as $message) {
            $event->addMessage($message);
        }
    }

    protected function getMessages()
    {
        return array();

        $threads = $this->container->get('fos_message.provider')->getInboxThreads();
        $security = $this->container->get('security.context');
        $token = $security->getToken();

        $user = $token->getUser();
        if (!empty($user)) {
            $messages = array();

            /** @var \ChamiloLMS\CoreBundle\Entity\Thread $thread */
            foreach ($threads as $thread) {
                if ($thread->isReadByParticipant($user)) {
                    foreach ($thread->getMessages() as $message) {
                        $messages[] = $message;
                    }
                }
            }

            return $messages;
        }

    }
}
