<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\MessageTag;
use Chamilo\CoreBundle\Repository\MessageRepository;
use Chamilo\CoreBundle\Repository\MessageTagRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @covers \MessageRepository
 */
class MessageRepositoryTest extends WebTestCase
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
}
