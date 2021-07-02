<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\MessageTag;
use Chamilo\CoreBundle\Repository\MessageRepository;
use Chamilo\CoreBundle\Repository\MessageTagRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

/**
 * @covers \MessageRepository
 */
class MessageRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreateMessage(): void
    {
        self::bootKernel();
        $repo = self::getContainer()->get(MessageRepository::class);

        $testUser = $this->createUser('test');

        $message =
            (new Message())
                ->setTitle('hello')
                ->setContent('content')
                ->setMsgType(Message::MESSAGE_TYPE_INBOX)
                ->setUserSender($this->getUser('admin'))
                ->setUserReceiver($testUser)
        ;

        $this->assertHasNoEntityViolations($message);

        $repo->update($message);

        // 1. Message in the inbox
        $count = $repo->count(['msgType' => Message::MESSAGE_TYPE_INBOX]);
        $this->assertSame(1, $count);

        // 2. Message in the outbox (this is created in the MessageListener by default).
        $count = $repo->count(['msgType' => Message::MESSAGE_TYPE_OUTBOX]);
        $this->assertSame(1, $count);

        // Add tags to the same message.
        $tagRepo = self::getContainer()->get(MessageTagRepository::class);

        $tag = (new MessageTag())
            ->setTag('my tag')
            ->setUser($testUser)
        ;
        $message->addTag($tag);

        $repo->update($message);

        // 1 tag created.
        $count = $tagRepo->count([]);
        $this->assertSame(1, $count);

        // Message has 1 tag
        $this->assertSame(1, $message->getTags()->count());

        // Add same tag again
        $message->addTag($tag);
        $repo->update($message);
        $this->assertSame(1, $message->getTags()->count());

        // Add new tag.
        $tag = (new MessageTag())
            ->setTag('my tag 2')
            ->setUser($testUser)
        ;
        $message->addTag($tag);
        $repo->update($message);
        $message->addTag($tag);
        $repo->update($message);

        $this->assertSame(2, $message->getTags()->count());
    }

    public function testCreateMessageWithApi(): void
    {
        self::bootKernel();

        $fromUser = $this->createUser('from');
        $toUser = $this->createUser('to');

        $tokenFrom = $this->getUserToken(
            [
                'username' => 'from',
                'password' => 'from',
            ]
        );

        $this->createClientWithCredentials($tokenFrom)->request(
            'POST',
            '/api/messages',
            [
                'json' => [
                    'title' => 'hello',
                    'content' => 'content of hello',
                    'msgType' => Message::MESSAGE_TYPE_INBOX,
                    'userSender' => '/api/users/'.$fromUser->getId(),
                    'userReceiver' => '/api/users/'.$toUser->getId(),
                ],
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains(
            [
                '@context' => '/api/contexts/Message',
                '@type' => 'Message',
                'title' => 'hello',
                'read' => false,
                'starred' => false,
            ]
        );

        // Try to send a message as another user
        $this->createUser('bad');
        $tokenFromBadUser = $this->getUserToken(
            [
                'username' => 'bad',
                'password' => 'bad',
            ],
            true
        );

        $this->createClientWithCredentials($tokenFromBadUser)->request(
            'POST',
            '/api/messages',
            [
                'json' => [
                    'title' => 'hello',
                    'content' => 'content of hello',
                    'msgType' => Message::MESSAGE_TYPE_INBOX,
                    'userSender' => '/api/users/'.$fromUser->getId(),
                    'userReceiver' => '/api/users/'.$toUser->getId(),
                ],
            ]
        );

        $this->assertResponseStatusCodeSame(403);
    }
}
