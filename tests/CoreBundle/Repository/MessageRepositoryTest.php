<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\MessageRelUser;
use Chamilo\CoreBundle\Entity\MessageTag;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\MessageRepository;
use Chamilo\CoreBundle\Repository\MessageTagRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
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

        $em = self::getContainer()->get('doctrine')->getManager();

        $messageTagRepo = self::getContainer()->get(MessageTagRepository::class);
        $messageRepo = self::getContainer()->get(MessageRepository::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $messageRelUserRepo = $em->getRepository(MessageRelUser::class);

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
        $messageRepo->update($message);

        // 1. Message exists in the inbox.
        $count = $messageRepo->count(['msgType' => Message::MESSAGE_TYPE_INBOX]);
        $this->assertSame(1, $count);

        // Check if message was schedule to be sent.
        /** @var InMemoryTransport $transport */
        $transport = $this->getContainer()->get('messenger.transport.sync_priority_high');
        $this->assertCount(1, $transport->getSent());

        $em->clear();

        /** @var User $testUser */
        $testUser = $userRepo->find($testUser->getId());

        // Receiver should have one message.
        $this->assertSame(1, $testUser->getReceivedMessages()->count());

        // Getting message sent.
        /** @var MessageRelUser $receivedMessage */
        $receivedMessage = $testUser->getReceivedMessages()->first();

        // Add tags to the same message.
        $tag = (new MessageTag())
            ->setTag('my tag')
            ->setUser($testUser)
            ->addMessage($receivedMessage)
        ;
        $this->assertHasNoEntityViolations($tag);
        $messageTagRepo->update($tag);

        // 1 tag created.
        $this->assertSame(1, $messageTagRepo->count([]));

        // MessageRelUser has 1 tag
        $this->assertSame(1, $receivedMessage->getTags()->count());

        // Add same tag again, should appear an invalid entity.
        $tag = (new MessageTag())
            ->setTag('my tag')
            ->setUser($testUser)
            ->addMessage($receivedMessage)
        ;
        $this->assertSame(1, $this->getViolations($tag)->count());

        /*$this->assertHasNoEntityViolations($tag);
        $tagRepo->update($tag);*/

        $em->clear();

        /** @var User $testUser */
        $testUser = $userRepo->find($testUser->getId());
        /** @var MessageRelUser $receivedMessage */
        $receivedMessage = $testUser->getReceivedMessages()->first();

        // Add new tag.
        $tag = (new MessageTag())
            ->setTag('my tag 2')
            ->setUser($testUser)
            ->addMessage($receivedMessage)
        ;

        $this->assertHasNoEntityViolations($tag);
        $messageTagRepo->update($tag);
        $this->assertSame(2, $receivedMessage->getTags()->count());

        // Delete message.
        $message = $messageRepo->find($message->getId());
        $messageRepo->delete($message);

        // No messages.
        $this->assertSame(0, $messageRepo->count([]));
        // No message_rel_user.
        $this->assertSame(0, $messageRelUserRepo->count([]));
        // Tags.
        $this->assertSame(2, $messageTagRepo->count([]));
    }

    public function testCreateMessageWithApi(): void
    {
        self::bootKernel();

        $fromUser = $this->createUser('from');
        $toUser = $this->createUser('to');
        $messageRepo = self::getContainer()->get(MessageRepository::class);

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
                    'receivers' => [
                        [
                            'receiver' => $toUser->getIri(),
                        ],
                    ],
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
                'receivers' => [
                    [
                        '@type' => 'MessageRelUser',
                        'receiver' => [
                            '@id' => $toUser->getIri(),
                            '@type' => 'http://schema.org/Person',
                            'username' => $toUser->getUsername(),
                        ],
                        'read' => false,
                        'starred' => false,
                    ],
                ],
            ]
        );

        // Messages: 1 from inbox
        $this->assertSame(1, $messageRepo->count(['msgType' => Message::MESSAGE_TYPE_INBOX]));

        // The message was added in the queue.
        /** @var InMemoryTransport $transport */
        $transport = $this->getContainer()->get('messenger.transport.sync_priority_high');
        $this->assertCount(1, $transport->getSent());

        // Reciever adds tags + starred

        $messageId = $response->toArray()['id'];
        /** @var Message $message */
        $message = $messageRepo->find($messageId);

        /** @var MessageRelUser $messageRelUser */
        $messageRelUser = $message->getReceivers()->first();

        $response = $this->createClientWithCredentials($tokenFrom)->request(
            'PUT',
            '/api/message_rel_users/'.$messageRelUser->getId(),
            [
                'json' => [
                    'read' => true,
                    'starred' => true,
                    /*'tags' => [
                        [
                            '',
                        ],
                        [
                            'tag' => 'pop',
                        ]
                    ]*/
                ],
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains(
            [
                '@context' => '/api/contexts/MessageRelUser',
                '@type' => 'MessageRelUser',
                'read' => true,
                'starred' => true,
                //'tags' => []
            ]
        );
    }

    public function testCreateMessageWithApiAsOtherUser(): void
    {
        $fromUser = $this->createUser('from');
        $toUser = $this->createUser('to');

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
                    'receivers' => [
                        [
                            'receiver' => $toUser->getIri(),
                        ],
                    ],
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
                    'receivers' => [
                        [
                            'receiver' => $fromUser->getIri(),
                        ],
                    ],
                ],
            ]
        );
        $this->assertResponseStatusCodeSame(403);
    }

    public function testDeleteMessageWithApi(): void
    {
        self::bootKernel();

        $messageTagRepo = self::getContainer()->get(MessageTagRepository::class);
        $messageRepo = self::getContainer()->get(MessageRepository::class);
        $userRepo = self::getContainer()->get(UserRepository::class);

        $fromUser = $this->createUser('from');
        $toUser = $this->createUser('to');

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
                    'receivers' => [
                        [
                            'receiver' => $toUser->getIri(),
                        ],
                    ],
                ],
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(201);

        $id = $response->toArray()['@id'];
        $messageId = $response->toArray()['id'];

        // Sender cannot delete a message already sent.
        $this->createClientWithCredentials($tokenFrom)->request(
            'DELETE',
            $id,
        );
        $this->assertResponseStatusCodeSame(403);

        /** @var Message $message */
        $message = $messageRepo->find($messageId);

        $this->assertSame(1, $message->getReceivers()->count());

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
        $this->assertResponseStatusCodeSame(403);
    }
}
