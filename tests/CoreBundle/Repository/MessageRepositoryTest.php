<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\MessageAttachment;
use Chamilo\CoreBundle\Entity\MessageFeedback;
use Chamilo\CoreBundle\Entity\MessageRelUser;
use Chamilo\CoreBundle\Entity\MessageTag;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\MessageRepository;
use Chamilo\CoreBundle\Repository\MessageTagRepository;
use Chamilo\CoreBundle\Repository\Node\MessageAttachmentRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;
use Symfony\Component\Messenger\Transport\InMemoryTransport;

class MessageRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreateMessage(): void
    {
        $em = $this->getEntityManager();

        $messageRepo = self::getContainer()->get(MessageRepository::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $messageRelUserRepo = $em->getRepository(MessageRelUser::class);

        $admin = $this->getUser('admin');
        $testUser = $this->createUser('test');

        $message = (new Message())
            ->setTitle('hello')
            ->setContent('content')
            ->setMsgType(Message::MESSAGE_TYPE_INBOX)
            ->setSender($admin)
            ->addReceiver($testUser)
            ->setSendDate(new DateTime())
            ->setVotes(0)
            ->setGroup(null)
            ->setUpdateDate(new DateTime())
            ->setParent(null)
        ;

        $this->assertHasNoEntityViolations($message);
        $messageRepo->update($message);

        $this->assertNotNull($message->getUpdateDate());
        $this->assertNull($message->getParent());
        $this->assertTrue($message->hasReceiver($testUser));

        $transport = $this->getContainer()->get('messenger.transport.sync_priority_high');
        $this->assertCount(1, $transport->getSent());

        // 1. Message exists in the inbox.
        $count = $messageRepo->count(['msgType' => Message::MESSAGE_TYPE_INBOX]);
        $this->assertSame(1, $count);

        // One receiver in MessageRelUser.
        $this->assertSame(1, $messageRelUserRepo->count([]));
        $this->assertSame(0, $message->getVotes());
        $this->assertSame(1, $message->getReceivers()->count());

        // Check if message was schedule to be sent.
        /** @var InMemoryTransport $transport */
        $transport = $this->getContainer()->get('messenger.transport.sync_priority_high');
        $this->assertCount(1, $transport->getSent());

        $em->clear();

        /** @var User $testUser */
        $testUser = $userRepo->find($testUser->getId());

        // Receiver should have one message.
        $this->assertSame(1, $testUser->getReceivedMessages()->count());
    }

    public function testCreateMessageWithFeedback(): void
    {
        $em = $this->getEntityManager();

        $messageRepo = self::getContainer()->get(MessageRepository::class);
        $messageFeedbackRepo = $em->getRepository(MessageFeedback::class);

        $admin = $this->getUser('admin');
        $testUser = $this->createUser('test');

        $message = (new Message())
            ->setTitle('hello')
            ->setContent('content')
            ->setMsgType(Message::MESSAGE_TYPE_INBOX)
            ->setSender($admin)
            ->addReceiver($testUser)
            ->setSendDate(new DateTime())
            ->setVotes(0)
            ->setGroup(null)
        ;
        $messageRepo->update($message);

        // 1. Message exists in the inbox.
        $this->assertSame(1, $messageRepo->count([]));

        $feedback = (new MessageFeedback())
            ->setMessage($message)
            ->setUser($testUser)
            ->setUpdatedAt(new DateTime())
            ->setDisliked(true)
            ->setLiked(true)
        ;
        $em->persist($feedback);
        $em->flush();
        $em->clear();

        $this->assertSame(1, $messageFeedbackRepo->count([]));
        $this->assertNotNull($feedback->getUser());
        $this->assertNotNull($feedback->getUpdatedAt());
        $this->assertNotNull($feedback->getMessage());

        /** @var Message $message */
        $message = $messageRepo->find($message->getId());
        $this->assertSame(1, $message->getLikes()->count());

        $messageRepo->delete($message);

        $this->assertSame(0, $messageRepo->count([]));
        $this->assertSame(0, $messageFeedbackRepo->count([]));
    }

    public function testCreateMessageWithTags(): Message
    {
        $em = $this->getEntityManager();

        $messageTagRepo = self::getContainer()->get(MessageTagRepository::class);
        $messageRepo = self::getContainer()->get(MessageRepository::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $messageRelUserRepo = $em->getRepository(MessageRelUser::class);

        $admin = $this->getUser('admin');
        $testUser = $this->createUser('test');

        $message = (new Message())
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

        $em->clear();

        /** @var User $testUser */
        $testUser = $userRepo->find($testUser->getId());

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
        $em->clear();

        /** @var User $testUser */
        $testUser = $userRepo->find($testUser->getId());
        /** @var MessageRelUser $receivedMessage */
        $receivedMessage = $testUser->getReceivedMessages()->first();

        // Add second tag.
        $tag = (new MessageTag())
            ->setTag('my tag 2')
            ->setUser($testUser)
            ->addMessage($receivedMessage)
        ;

        $this->assertHasNoEntityViolations($tag);
        $messageTagRepo->update($tag);

        $this->assertSame(2, $receivedMessage->getTags()->count());
        $this->assertSame(1, $messageRepo->count([]));
        $this->assertSame(1, $messageRelUserRepo->count([]));
        $this->assertSame(2, $messageTagRepo->count([]));

        return $message;
    }

    public function testDeleteMessageTag(): void
    {
        $em = $this->getEntityManager();
        $messageTagRepo = self::getContainer()->get(MessageTagRepository::class);
        $messageRepo = self::getContainer()->get(MessageRepository::class);
        $messageRelUserRepo = $em->getRepository(MessageRelUser::class);

        $message = $this->testCreateMessageWithTags();

        /** @var Message $message */
        $message = $messageRepo->find($message->getId());
        $this->assertSame(1, $message->getReceivers()->count());

        /** @var MessageRelUser $messageRelUser */
        $messageRelUser = $message->getReceivers()->first();
        $tag = $messageTagRepo->find($messageRelUser->getTags()->first());

        $messageTagRepo->delete($tag);

        /** @var Message $message */
        $message = $messageRepo->find($message->getId());

        $this->assertSame(1, $message->getReceivers()->count());
        $this->assertSame(1, $messageRepo->count([]));
        $this->assertSame(1, $messageRelUserRepo->count([]));
        $this->assertSame(1, $messageTagRepo->count([]));
    }

    public function testDeleteMessageWithTag(): void
    {
        $em = $this->getEntityManager();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $messageTagRepo = self::getContainer()->get(MessageTagRepository::class);
        $messageRepo = self::getContainer()->get(MessageRepository::class);
        $messageRelUserRepo = $em->getRepository(MessageRelUser::class);

        $message = $this->testCreateMessageWithTags();

        /** @var Message $message */
        $message = $messageRepo->find($message->getId());

        $messageRepo->delete($message);

        $this->assertSame(0, $messageRepo->count([]));
        $this->assertSame(0, $messageRelUserRepo->count([]));
        $this->assertSame(0, $messageTagRepo->count([]));

        $this->assertNotNull($this->getUser('admin'));
        $this->assertNotNull($this->getUser('test'));
    }

    public function testCreateMessageWithAttachment(): void
    {
        $user1 = $this->getUser('admin');
        $user2 = $this->createUser('user2');

        $user1Token = $this->getUserTokenFromUser($user1);

        $client = $this->createClientWithCredentials($user1Token);

        $responseMessage = $client->request(
            'POST',
            '/api/messages',
            [
                'json' => [
                    'msgType' => Message::MESSAGE_TYPE_INBOX,
                    'title' => 'Message title',
                    'content' => 'Message content',
                    'receivers' => [
                        [
                            'receiver' => "/api/users/{$user2->getId()}",
                            'receiverType' => MessageRelUser::TYPE_TO,
                        ],
                    ],
                    'sender' => "/api/users/{$user1->getId()}",
                ],
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains(
            [
                '@context' => '/api/contexts/Message',
                '@type' => 'Message',
                'sender' => [
                    '@id' => "/api/users/{$user1->getId()}",
                ],
                'receiversTo' => [
                    [
                        '@type' => 'MessageRelUser',
                        'receiver' => [
                            '@id' => "/api/users/{$user2->getId()}",
                        ],
                        'receiverType' => MessageRelUser::TYPE_TO,
                    ],
                ],
                'msgType' => Message::MESSAGE_TYPE_INBOX,
                'title' => 'Message title',
                'content' => 'Message content',
            ]
        );

        $messageId = $responseMessage->toArray()['id'];

        $file = $this->getUploadedFile();

        $responseAttachment = $client->request(
            'POST',
            '/api/message_attachments',
            [
                'headers' => [
                    'Content-Type' => 'multipart/form-data',
                ],
                'extra' => [
                    'files' => [
                        'file' => $file,
                    ],
                    'parameters' => [
                        'messageId' => $messageId,
                    ],
                ],
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains(
            [
                '@context' => '/api/contexts/MessageAttachment',
                '@type' => 'http://schema.org/MediaObject',
            ]
        );
    }

    public function testDeleteMessage(): void
    {
        $em = $this->getEntityManager();

        $messageAttachmentRepo = self::getContainer()->get(MessageAttachmentRepository::class);
        $messageTagRepo = self::getContainer()->get(MessageTagRepository::class);
        $messageRepo = self::getContainer()->get(MessageRepository::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $messageRelUserRepo = $em->getRepository(MessageRelUser::class);

        $admin = $this->getUser('admin');
        $testUser = $this->createUser('test');

        // 1. Create message.
        $message = (new Message())
            ->setTitle('hello')
            ->setContent('content')
            ->setMsgType(Message::MESSAGE_TYPE_INBOX)
            ->setSender($admin)
            ->addReceiver($testUser)
        ;

        $this->assertHasNoEntityViolations($message);
        $messageRepo->update($message);

        $file = $this->getUploadedFile();

        // 2. Create attachment.
        $attachment = (new MessageAttachment())
            ->setFilename($file->getFilename())
            ->setMessage($message)
            ->setParent($message->getSender())
            ->setCreator($message->getSender())
        ;
        $em->persist($attachment);
        $messageAttachmentRepo->addFile($attachment, $file);
        $em->flush();
        $em->clear();

        $url = $messageAttachmentRepo->getResourceFileUrl($attachment);
        $this->assertNotEmpty($url);

        // Create tag.
        /** @var User $testUser */
        $testUser = $userRepo->find($testUser->getId());
        /** @var MessageRelUser $receivedMessage */
        $receivedMessage = $testUser->getReceivedMessages()->first();

        $tag = (new MessageTag())
            ->setTag('my tag')
            ->setUser($testUser)
            ->addMessage($receivedMessage)
        ;
        $this->assertHasNoEntityViolations($tag);
        $messageTagRepo->update($tag);

        $em->clear();

        // Delete message.
        $message = $messageRepo->find($message->getId());
        $messageRepo->delete($message);

        // No messages.
        $this->assertSame(0, $messageRepo->count([]));
        // No message_rel_user.
        $this->assertSame(0, $messageRelUserRepo->count([]));
        // No attachments.
        $this->assertSame(0, $messageAttachmentRepo->count([]));
        // 2 tags still exists.
        $this->assertSame(0, $messageTagRepo->count([]));
    }

    public function testCreateMessageWithCc(): void
    {
        $em = $this->getEntityManager();

        $messageRepo = self::getContainer()->get(MessageRepository::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $messageRelUserRepo = $em->getRepository(MessageRelUser::class);

        $admin = $this->getUser('admin');
        $testUser = $this->createUser('test');
        $receiverCopy = $this->createUser('cc');

        $message =
            (new Message())
                ->setTitle('hello')
                ->setContent('content')
                ->setMsgType(Message::MESSAGE_TYPE_INBOX)
                ->setSender($admin)
                ->addReceiver($testUser)
                ->addReceiver($receiverCopy, MessageRelUser::TYPE_CC)
        ;

        $this->assertHasNoEntityViolations($message);
        $messageRepo->update($message);

        // 1. Message exists in the inbox.
        $count = $messageRepo->count(['msgType' => Message::MESSAGE_TYPE_INBOX]);
        $this->assertSame(1, $count);

        $this->assertSame(2, $messageRelUserRepo->count([]));

        // Check if message was schedule to be sent.
        /** @var InMemoryTransport $transport */
        $transport = $this->getContainer()->get('messenger.transport.sync_priority_high');
        $this->assertCount(1, $transport->getSent());

        $em->clear();
        /** @var Message $message */
        $message = $messageRepo->find($message->getId());

        $this->assertSame(2, $message->getReceivers()->count());

        // Delete message.
        $messageRepo->delete($message);

        // No messages.
        $this->assertSame(0, $messageRepo->count([]));
        // No message_rel_user.
        $this->assertSame(0, $messageRelUserRepo->count([]));
    }

    public function testCreateMessageWithApi(): void
    {
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

        // Receiver adds tags + starred

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
        $messageRepo = self::getContainer()->get(MessageRepository::class);

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

    public function testGetMessageByUser(): void
    {
        $messageRepo = self::getContainer()->get(MessageRepository::class);

        $admin = $this->getUser('admin');
        $testUser = $this->createUser('test');

        $message = (new Message())
            ->setTitle('hello')
            ->setContent('content')
            ->setMsgType(Message::MESSAGE_TYPE_INBOX)
            ->setSender($admin)
            ->addReceiver($testUser)
        ;

        $this->assertHasNoEntityViolations($message);
        $messageRepo->update($message);

        $messages = $messageRepo->getMessageByUser($testUser, Message::MESSAGE_TYPE_INBOX);
        $this->assertSame(1, \count($messages));
    }
}
