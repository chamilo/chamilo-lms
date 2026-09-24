<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Message;

use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\MessageRelUser;
use Chamilo\CoreBundle\Repository\MessageRepository;
use Chamilo\CoreBundle\Service\Message\MessageEmailOpenTrackingService;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Component\HttpFoundation\Response;

final class MessageEmailOpenTrackingServiceTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testTrackingPixelMarksRecipientAsOpened(): void
    {
        $client = self::createClient();

        /** @var SettingsManager $settingsManager */
        $settingsManager = self::getContainer()->get(SettingsManager::class);
        $settingsManager->updateSetting('mail.enable_email_open_tracking', 'true');

        try {
            $sender = $this->createUser('pixel_sender');
            $receiver = $this->createUser('pixel_receiver');

            $message = (new Message())
                ->setSender($sender)
                ->setTitle('Tracked message')
                ->setContent('Tracked content')
                ->addReceiverTo($receiver)
            ;

            /** @var MessageRepository $messageRepository */
            $messageRepository = self::getContainer()->get(MessageRepository::class);
            $messageRepository->update($message);

            $relation = $message->getReceivers()->first();
            $this->assertInstanceOf(MessageRelUser::class, $relation);
            $this->assertNotNull($relation->getId());

            /** @var MessageEmailOpenTrackingService $trackingService */
            $trackingService = self::getContainer()->get(MessageEmailOpenTrackingService::class);
            $html = $trackingService->appendTrackingPixelForMessageRecipient(
                (int) $message->getId(),
                (int) $receiver->getId(),
                '<p>Hello</p>',
                'https://example.org'
            );

            $token = $relation->getMailTrackingToken();
            $this->assertNotNull($token);
            $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
            $this->assertStringContainsString('/mail/open/'.$token.'.gif', $html);

            $client->request('GET', '/mail/open/'.$token.'.gif');
            $this->assertResponseStatusCodeSame(Response::HTTP_OK);
            $this->assertResponseHeaderSame('content-type', 'image/gif');
            $this->assertResponseHeaderSame('x-content-type-options', 'nosniff');

            $entityManager = $this->getEntityManager();
            $relationId = (int) $relation->getId();
            $entityManager->clear();

            /** @var MessageRelUser|null $reloaded */
            $reloaded = $entityManager->getRepository(MessageRelUser::class)->find($relationId);
            $this->assertInstanceOf(MessageRelUser::class, $reloaded);
            $this->assertNotNull($reloaded->getMailOpenedAt());
            $firstOpenedAt = $reloaded->getMailOpenedAt()->format('Y-m-d H:i:s');

            $client->request('GET', '/mail/open/'.$token.'.gif');
            $this->assertResponseStatusCodeSame(Response::HTTP_OK);

            $entityManager->clear();
            /** @var MessageRelUser|null $reloadedAgain */
            $reloadedAgain = $entityManager->getRepository(MessageRelUser::class)->find($relationId);
            $this->assertInstanceOf(MessageRelUser::class, $reloadedAgain);
            $this->assertSame($firstOpenedAt, $reloadedAgain->getMailOpenedAt()?->format('Y-m-d H:i:s'));
        } finally {
            $settingsManager->updateSetting('mail.enable_email_open_tracking', 'false');
        }
    }

    public function testDisabledTrackingDoesNotCreateTokenOrPixel(): void
    {
        /** @var SettingsManager $settingsManager */
        $settingsManager = self::getContainer()->get(SettingsManager::class);
        $settingsManager->updateSetting('mail.enable_email_open_tracking', 'false');

        $sender = $this->createUser('pixel_disabled_sender');
        $receiver = $this->createUser('pixel_disabled_receiver');

        $message = (new Message())
            ->setSender($sender)
            ->setTitle('Untracked message')
            ->setContent('Untracked content')
            ->addReceiverTo($receiver)
        ;

        /** @var MessageRepository $messageRepository */
        $messageRepository = self::getContainer()->get(MessageRepository::class);
        $messageRepository->update($message);

        $relation = $message->getReceivers()->first();
        $this->assertInstanceOf(MessageRelUser::class, $relation);

        /** @var MessageEmailOpenTrackingService $trackingService */
        $trackingService = self::getContainer()->get(MessageEmailOpenTrackingService::class);
        $html = $trackingService->appendTrackingPixelForMessageRecipient(
            (int) $message->getId(),
            (int) $receiver->getId(),
            '<p>Hello</p>',
            'https://example.org'
        );

        $this->assertSame('<p>Hello</p>', $html);
        $this->assertNull($relation->getMailTrackingToken());
        $this->assertNull($relation->getMailOpenedAt());
    }
}
