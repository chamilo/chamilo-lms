<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Message;

use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\MessageRelUser;
use Chamilo\CoreBundle\Repository\MessageRepository;
use Chamilo\CoreBundle\Service\Message\MessageInboundMailService;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use RuntimeException;

final class MessageInboundMailServiceTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testInboundReplyCreatesChildMessageAndIsIdempotent(): void
    {
        self::createClient();

        /** @var SettingsManager $settingsManager */
        $settingsManager = self::getContainer()->get(SettingsManager::class);
        $settingsManager->updateSetting('mail.enable_inbound_mail', 'true');
        $settingsManager->updateSetting('mail.inbound_mail_address', 'replies@example.org');

        try {
            $sender = $this->createUser('inbound_sender');
            $receiver = $this->createUser('inbound_receiver');

            $message = (new Message())
                ->setSender($sender)
                ->setTitle('Inbound mail test')
                ->setContent('Original content')
                ->addReceiverTo($receiver)
            ;

            /** @var MessageRepository $messageRepository */
            $messageRepository = self::getContainer()->get(MessageRepository::class);
            $messageRepository->update($message);

            /** @var MessageInboundMailService $service */
            $service = self::getContainer()->get(MessageInboundMailService::class);
            $headers = $service->prepareForMessageRecipient(
                (int) $message->getId(),
                (int) $receiver->getId()
            );

            $relation = $message->getReceivers()->first();
            $this->assertInstanceOf(MessageRelUser::class, $relation);
            $this->assertTrue($message->isMailAnswer());

            $token = $relation->getMailReplyToken();
            $this->assertNotNull($token);
            $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
            $this->assertSame('replies+'.$token.'@example.org', $headers['reply_to']['mail'] ?? null);

            $markedContent = $service->prependPreparedReplyMarker('<p>Original content</p>');
            $this->assertStringContainsString(MessageInboundMailService::REPLY_MARKER, $markedContent);
            $service->clearPreparedReply();

            $rawEmail = \sprintf(
                "From: %s\r\nTo: replies+%s@example.org\r\nMessage-ID: <reply-9030@example.org>\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\nHello from e-mail\r\n\r\n%s\r\nOriginal content\r\n",
                $receiver->getEmail(),
                $token,
                MessageInboundMailService::REPLY_MARKER
            );

            $reply = $service->processRawEmail($rawEmail);

            $this->assertNotNull($reply->getId());
            $this->assertSame($receiver->getId(), $reply->getSender()?->getId());
            $this->assertSame($message->getId(), $reply->getParent()?->getId());
            $this->assertSame('Re: Inbound mail test', $reply->getTitle());
            $this->assertSame('Hello from e-mail', $reply->getContent());
            $this->assertNotNull($reply->getMailInboundId());

            $toRelations = $reply->getReceiversTo();
            $this->assertCount(1, $toRelations);
            $this->assertSame($sender->getId(), $toRelations[0]->getReceiver()->getId());

            $duplicate = $service->processRawEmail($rawEmail);
            $this->assertSame($reply->getId(), $duplicate->getId());
        } finally {
            $settingsManager->updateSetting('mail.enable_inbound_mail', 'false');
            $settingsManager->updateSetting('mail.inbound_mail_address', '');
        }
    }

    public function testInboundReplyRejectsDifferentSender(): void
    {
        self::createClient();

        /** @var SettingsManager $settingsManager */
        $settingsManager = self::getContainer()->get(SettingsManager::class);
        $settingsManager->updateSetting('mail.enable_inbound_mail', 'true');
        $settingsManager->updateSetting('mail.inbound_mail_address', 'replies@example.org');

        try {
            $sender = $this->createUser('inbound_security_sender');
            $receiver = $this->createUser('inbound_security_receiver');

            $message = (new Message())
                ->setSender($sender)
                ->setTitle('Inbound security test')
                ->setContent('Original content')
                ->addReceiverTo($receiver)
            ;

            /** @var MessageRepository $messageRepository */
            $messageRepository = self::getContainer()->get(MessageRepository::class);
            $messageRepository->update($message);

            /** @var MessageInboundMailService $service */
            $service = self::getContainer()->get(MessageInboundMailService::class);
            $service->prepareForMessageRecipient((int) $message->getId(), (int) $receiver->getId());

            $relation = $message->getReceivers()->first();
            $this->assertInstanceOf(MessageRelUser::class, $relation);
            $token = $relation->getMailReplyToken();
            $this->assertNotNull($token);
            $service->clearPreparedReply();

            $rawEmail = \sprintf(
                "From: attacker@example.org\r\nTo: replies+%s@example.org\r\nMessage-ID: <reply-9030-security@example.org>\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\nUnauthorized reply\r\n",
                $token
            );

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('sender does not match');
            $service->processRawEmail($rawEmail);
        } finally {
            $settingsManager->updateSetting('mail.enable_inbound_mail', 'false');
            $settingsManager->updateSetting('mail.inbound_mail_address', '');
        }
    }
}
