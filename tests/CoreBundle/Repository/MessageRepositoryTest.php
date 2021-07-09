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
use Symfony\Component\Messenger\Transport\InMemoryTransport;

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
        $admin = $this->getUser('admin');
        $testUser = $this->createUser('test');

        $message =
            (new Message())
                ->setTitle('hello')
                ->setContent('content')
                ->setMsgType(Message::MESSAGE_TYPE_INBOX)
                ->setSender($admin)
                ->addReceiver($testUser)
        ;

        $this->assertHasNoEntityViolations($message);

        $repo->update($message);

        // 1. Message exists in the inbox.
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
        $repo = self::getContainer()->get(MessageRepository::class);

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
                    'sender' => $fromUser->getIri(),
                    'receivers' => [$toUser->getIri()],
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

        // 2 Messages: 1 from inbox + 1 from outbox.
        $this->assertSame(1, $repo->count(['msgType' => Message::MESSAGE_TYPE_INBOX]));
        $this->assertSame(1, $repo->count(['msgType' => Message::MESSAGE_TYPE_OUTBOX]));

        // The message was added in the queue.
        /** @var InMemoryTransport $transport */
        $transport = $this->getContainer()->get('messenger.transport.sync_priority_high');
        $this->assertCount(1, $transport->getSent());
    }

    public function testCreateMessageWithApiAsOtherUser(): void
    {
        $fromUser = $this->createUser('from');
        $toUser = $this->createUser('to');
        $repo = self::getContainer()->get(MessageRepository::class);

        // Try to send a message as another user.
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
                    'sender' => $fromUser->getIri(),
                    'receivers' => [$toUser->getIri()],
                ],
            ]
        );
        $this->assertResponseStatusCodeSame(403);

        // Try to send a message as another user.
        $this->createClientWithCredentials($tokenFromBadUser)->request(
            'POST',
            '/api/messages',
            [
                'json' => [
                    'title' => 'hello',
                    'content' => 'content of hello',
                    'msgType' => Message::MESSAGE_TYPE_INBOX,
                    'sender' => $toUser->getIri(),
                    'receivers' => [$fromUser->getIri()],
                ],
            ]
        );
        $this->assertResponseStatusCodeSame(403);
    }

    public function testDeleteMessageWithApi(): void
    {
        self::bootKernel();

        $fromUser = $this->createUser('from');
        $toUser = $this->createUser('to');
        $repo = self::getContainer()->get(MessageRepository::class);

        $tokenFrom = $this->getUserToken(
            [
                'username' => 'from',
                'password' => 'from',
            ]
        );

        $response = $this->createClientWithCredentials($tokenFrom)->request(
            'POST',
            '/api/messages',
            [
                'json' => [
                    'title' => 'hello',
                    'content' => 'content of hello',
                    'msgType' => Message::MESSAGE_TYPE_INBOX,
                    'sender' => $fromUser->getIri(),
                    'receivers' => [$toUser->getIri()],
                ],
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(201);

        $id = $response->toArray()['@id'];

        // Sender cannot delete a message already sent.
        $this->createClientWithCredentials($tokenFrom)->request(
            'DELETE',
            $id,
        );
        $this->assertResponseStatusCodeSame(403);

        // Get the outbox message.
        /** @var Message $outboxMessage */
        $outboxMessage = $repo->findOneBy(['msgType' => Message::MESSAGE_TYPE_OUTBOX]);
        $this->assertInstanceOf(Message::class, $outboxMessage);

        // Sender removes the outbox message.
        $this->createClientWithCredentials($tokenFrom)->request(
            'DELETE',
            '/api/messages/'.$outboxMessage->getId(),
        );
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(204);
        $this->assertSame(1, $repo->count([]));

        // Receiver deletes the message.
        $tokenTo = $this->getUserToken(
            [
                'username' => 'to',
                'password' => 'to',
            ],
            true
        );

        $this->createClientWithCredentials($tokenTo)->request(
            'DELETE',
            $id,
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(204);
    }
}
