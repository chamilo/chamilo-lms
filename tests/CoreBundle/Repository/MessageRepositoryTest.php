<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\MessageAttachment;
use Chamilo\CoreBundle\Entity\MessageRelUser;
use Chamilo\CoreBundle\Entity\MessageTag;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\MessageRepository;
use Chamilo\CoreBundle\Repository\MessageTagRepository;
use Chamilo\CoreBundle\Repository\Node\MessageAttachmentRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Component\HttpFoundation\Response;

class MessageRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreateMessage(): void
    {
        $this->createUser('from');
        $senderUserIri = $this->findIriBy(User::class, ['username' => 'from']);
        $fromUserToken = $this->getUserToken(['username' => 'from', 'password' => 'from']);

        $this->createUser('to');
        $receiverUserIri = $this->findIriBy(User::class, ['username' => 'to']);
        $receiverUserToken = $this->getUserToken(['username' => 'to', 'password' => 'to']);

        $this->createUser('cc');
        $receiverCopyUserIri = $this->findIriBy(User::class, ['username' => 'cc']);
        $receiverCopyUserToken = $this->getUserToken(['username' => 'cc', 'password' => 'cc']);

        $response = $this
            ->createClientWithCredentials($fromUserToken)
            ->request(
                'POST',
                '/api/messages',
                [
                    'json' => [
                        'title' => 'hello',
                        'content' => 'content of hello',
                        'msgType' => Message::MESSAGE_TYPE_INBOX,
                        'sender' => $senderUserIri,
                        'receivers' => [
                            [
                                'receiver' => $receiverUserIri,
                                'receiverType' => MessageRelUser::TYPE_TO,
                            ],
                            [
                                'receiver' => $receiverCopyUserIri,
                                'receiverType' => MessageRelUser::TYPE_CC,
                            ],
                        ],
                    ],
                ]
            )
        ;

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains(
            [
                '@context' => '/api/contexts/Message',
                '@type' => 'Message',
                'sender' => [
                    '@id' => $senderUserIri,
                ],
                'msgType' => Message::MESSAGE_TYPE_INBOX,
                'receiversTo' => [
                    0 => [
                        '@type' => 'MessageRelUser',
                        'receiver' => [
                            '@id' => $receiverUserIri,
                        ],
                        'receiverType' => MessageRelUser::TYPE_TO,
                    ],
                ],
                'receiversCc' => [
                    0 => [
                        '@type' => 'MessageRelUser',
                        'receiver' => [
                            '@id' => $receiverCopyUserIri,
                        ],
                        'receiverType' => MessageRelUser::TYPE_CC,
                    ],
                ],
            ]
        );
        $this->assertEmailCount(2);

        $messageArray = $response->toArray();
        $messageRelUserIri = $messageArray['firstReceiver']['@id'];

        $this
            ->createClientWithCredentials($receiverUserToken)
            ->request(
                'PUT',
                $messageRelUserIri,
                [
                    'json' => [
                        'read' => true,
                        'starred' => true,
                    ],
                ]
            )
        ;

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJsonContains(
            [
                '@type' => 'MessageRelUser',
                'read' => true,
                'starred' => true,
            ]
        );
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
            ->addReceiverTo($testUser)
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
        $messageTagRepo = self::getContainer()->get(MessageTagRepository::class);
        $messageRepo = self::getContainer()->get(MessageRepository::class);
        $messageRelUserRepo = $em->getRepository(MessageRelUser::class);

        $message = $this->testCreateMessageWithTags();

        /** @var Message $message */
        $message = $messageRepo->find($message->getId());

        $messageRepo->delete($message);

        $this->assertSame(1, $messageRepo->count([]));
        $this->assertSame(1, $messageRelUserRepo->count([]));
        $this->assertSame(2, $messageTagRepo->count([]));

        $this->assertNotNull($this->getUser('admin'));
        $this->assertNotNull($this->getUser('test'));
    }

    public function testCreateMessageWithAttachment(): void
    {
        $user1 = $this->getUser('admin');
        $user2 = $this->createUser('user2');

        $user1Token = $this->getUserTokenFromUser($user1);

        $client = $this->createClientWithCredentials($user1Token);

        $file = $this->getUploadedFile();

        $resourceFile = $client->request(
            'POST',
            '/api/resource_files',
            [
                'headers' => [
                    'Content-Type' => 'multipart/form-data',
                ],
                'extra' => [
                    'files' => [
                        'file' => $file,
                    ],
                ],
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains(
            [
                '@context' => '/api/contexts/ResourceFile',
                '@type' => 'http://schema.org/MediaObject',
            ]
        );

        $resourceFileId = $resourceFile->toArray()['@id'];

        $client->request(
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
                    'attachments' => [
                        [
                            'resourceFileToAttach' => $resourceFileId,
                        ],
                    ],
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
                'attachments' => [
                    [
                        '@type' => 'http://schema.org/MediaObject',
                        'resourceNode' => [
                            '@type' => 'ResourceNode',
                            'resourceFiles' => [
                                [
                                    '@type' => 'http://schema.org/MediaObject',
                                    '@id' => $resourceFileId,
                                ],
                            ],
                        ],
                    ],
                ],
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
            ->addReceiverTo($testUser)
        ;

        $messageRelUserSender = new MessageRelUser();
        $messageRelUserSender->setMessage($message)
            ->setReceiver($admin)
            ->setReceiverType(MessageRelUser::TYPE_SENDER)
        ;

        $em->persist($messageRelUserSender);
        $em->flush();

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

        /** @var Message $message */
        $message = $messageRepo->find($message->getId());
        $messageRepo->delete($message);

        // Message is not deleted.
        $this->assertSame(1, $messageRepo->count([]));
        // Message has 2 message_rel_user (sender and receiver).
        $this->assertSame(2, $messageRelUserRepo->count([]));
        // No attachments.
        $this->assertSame(1, $messageAttachmentRepo->count([]));
        // 2 tags still exists.
        $this->assertSame(1, $messageTagRepo->count([]));

        /** @var Message $message */
        $message = $messageRepo->find($message->getId());

        $em->remove($message->getReceiversSender()[0]);
        $em->flush();

        $this->assertSame(1, $message->getReceivers()->count());
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
                    'receiversTo' => [
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
        /** @var MessageRepository $messageRepo */
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
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        /** @var Message $message */
        $message = $messageRepo->find($messageId);

        $senderRelation = $message->getReceiversSender()[0];
        $senderRelationIri = $this->findIriBy(
            MessageRelUser::class,
            ['id' => $senderRelation->getId()]
        );

        $this
            ->createClientWithCredentials($tokenFrom)
            ->request('DELETE', $senderRelationIri)
        ;

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        // Receiver deletes the message.
        $tokenTo = $this->getUserToken(
            [
                'username' => 'to',
                'password' => 'to',
            ],
            true
        );

        $receiverRelation = $message->getFirstReceiver();
        $senderRelationIri = $this->findIriBy(
            MessageRelUser::class,
            ['id' => $receiverRelation?->getId()]
        );

        $this->createClientWithCredentials($tokenTo)->request(
            'DELETE',
            $senderRelationIri,
        );
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
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
            ->addReceiverTo($testUser)
        ;

        $this->assertHasNoEntityViolations($message);
        $messageRepo->update($message);

        $messages = $messageRepo->getMessageByUser($testUser, Message::MESSAGE_TYPE_INBOX);
        $this->assertSame(1, \count($messages));
    }
}
