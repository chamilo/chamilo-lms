<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\MessageRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Component\HttpFoundation\Response;

class MessageApiTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    private function createMessageWithContent(User $sender, User $receiver, string $content): Message
    {
        $repo = static::getContainer()->get(MessageRepository::class);

        $message = (new Message())
            ->setTitle('test message')
            ->setContent($content)
            ->setMsgType(Message::MESSAGE_TYPE_INBOX)
            ->setSender($sender)
            ->addReceiverTo($receiver)
        ;

        $repo->update($message);

        return $message;
    }

    public function testXssEventHandlersAreStrippedOnItemGet(): void
    {
        $sender = $this->createUser('xss_sender');
        $receiver = $this->createUser('xss_receiver');

        $message = $this->createMessageWithContent(
            $sender,
            $receiver,
            '<p>Hello</p><img src="x" onerror="alert(1)"><svg onload="alert(2)">'
        );

        $receiverToken = $this->getUserToken(
            ['username' => 'xss_receiver', 'password' => 'xss_receiver'],
            true
        );

        $response = $this
            ->createClientWithCredentials($receiverToken)
            ->request(
            'GET',
            '/api/messages/'.$message->getId()
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $content = $response->toArray()['content'];

        $this->assertStringNotContainsString('onerror', $content);
        $this->assertStringNotContainsString('onload', $content);
        $this->assertStringContainsString('<p>Hello</p>', $content);
    }

    public function testXssScriptTagIsStrippedOnItemGet(): void
    {
        $sender = $this->createUser('xss_script_sender');
        $receiver = $this->createUser('xss_script_receiver');

        $message = $this->createMessageWithContent(
            $sender,
            $receiver,
            '<strong>Bold</strong><script>fetch("https://evil")</script>'
        );

        $receiverToken = $this->getUserToken(
            ['username' => 'xss_script_receiver', 'password' => 'xss_script_receiver'],
            true
        );

        $response = $this
            ->createClientWithCredentials($receiverToken)
            ->request(
            'GET',
            '/api/messages/'.$message->getId()
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $content = $response->toArray()['content'];

        $this->assertStringNotContainsString('<script>', $content);
        $this->assertStringNotContainsString('fetch(', $content);
        $this->assertStringContainsString('<strong>Bold</strong>', $content);
    }

    public function testXssPayloadIsStrippedOnCollectionGet(): void
    {
        $sender = $this->createUser('xss_col_sender');
        $receiver = $this->createUser('xss_col_receiver');

        $this->createMessageWithContent(
            $sender,
            $receiver,
            '<em>text</em><img src="x" onerror="stealCookies()">'
        );

        $receiverToken = $this->getUserToken(
            ['username' => 'xss_col_receiver', 'password' => 'xss_col_receiver'],
            true
        );

        $response = $this
            ->createClientWithCredentials($receiverToken)
            ->request(
            'GET',
            '/api/messages',
            ['query' => ['msgType' => Message::MESSAGE_TYPE_INBOX]]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $members = $response->toArray()['hydra:member'];

        $this->assertNotEmpty($members);

        $content = $members[0]['content'];

        $this->assertStringNotContainsString('onerror', $content);
        $this->assertStringNotContainsString('stealCookies', $content);
        $this->assertStringContainsString('<em>text</em>', $content);
    }
}