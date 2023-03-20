<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\MessageHandler;

use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Repository\MessageRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Mime\Address;

class MessageHandler implements MessageHandlerInterface
{
    private Mailer $mailer;
    private MessageRepository $repo;

    public function __construct(Mailer $mailer, MessageRepository $repo)
    {
        $this->mailer = $mailer;
        $this->repo = $repo;
    }

    public function __invoke(Message $message): void
    {
        if (Message::MESSAGE_TYPE_INBOX !== $message->getMsgType()) {
            // Only send messages to the inbox.
            return;
        }

        $email = (new TemplatedEmail())
            ->subject($message->getTitle())
            ->from(new Address($message->getSender()->getEmail(), $message->getSender()->getFirstname()))
            ->htmlTemplate('@ChamiloCore/Mailer/Default/default.html.twig')
            ->textTemplate('@ChamiloCore/Mailer/Default/default.text.twig')
        ;
        foreach ($message->getReceivers() as $messageRelUser) {
            $receiver = $messageRelUser->getReceiver();
            $address = new Address($receiver->getEmail(), $receiver->getFirstname());
            $email->addBcc($address);
        }

        $params = [
            'content' => $message->getContent(),
            'automatic_email_text' => '',
            'mail_header_style' => '',
            'mail_content_style' => '',
            'theme' => '',
        ];
        $email->context($params);
        $this->mailer->send($email);
    }
}
