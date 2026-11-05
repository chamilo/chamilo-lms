<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\MessageRelUser;
use Chamilo\CoreBundle\Repository\MessageRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

/**
 * A MessageRelUser row holds one participant's own mailbox state (read, starred,
 * label). Each participant may manage only their own row: a co-recipient of the
 * same message must not read, change or delete another recipient's row.
 */
final class MessageRelUserApiSecurityTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testParticipantCannotActOnAnotherReceiversRow(): void
    {
        $sender = $this->createUser('mru_sender');
        $alice = $this->createUser('mru_alice');
        $bob = $this->createUser('mru_bob');

        /** @var MessageRepository $messageRepo */
        $messageRepo = self::getContainer()->get(MessageRepository::class);

        $message = (new Message())
            ->setTitle('to alice and bob')
            ->setContent('hi')
            ->setMsgType(Message::MESSAGE_TYPE_INBOX)
            ->setSender($sender)
            ->addReceiverTo($alice)
            ->addReceiverTo($bob)
        ;
        $messageRepo->update($message);

        $relRepo = $this->getEntityManager()->getRepository(MessageRelUser::class);
        $aliceRow = $relRepo->findOneBy(['message' => $message, 'receiver' => $alice]);
        $bobRow = $relRepo->findOneBy(['message' => $message, 'receiver' => $bob]);

        $token = $this->getUserTokenFromUser($alice);

        // Alice manages her own row.
        $this->createClientWithCredentials($token)->request(
            'GET',
            '/api/message_rel_users/'.$aliceRow->getId()
        );
        $this->assertResponseStatusCodeSame(200);

        // Alice must not read Bob's row.
        $this->createClientWithCredentials($token)->request(
            'GET',
            '/api/message_rel_users/'.$bobRow->getId()
        );
        $this->assertResponseStatusCodeSame(403);

        // Alice must not change Bob's read state.
        $this->createClientWithCredentials($token)->request(
            'PATCH',
            '/api/message_rel_users/'.$bobRow->getId(),
            [
                'headers' => ['content-type' => ['application/merge-patch+json']],
                'json' => ['read' => true],
            ]
        );
        $this->assertResponseStatusCodeSame(403);

        // Alice must not delete Bob's message from his inbox.
        $this->createClientWithCredentials($token)->request(
            'DELETE',
            '/api/message_rel_users/'.$bobRow->getId()
        );
        $this->assertResponseStatusCodeSame(403);
    }
}
