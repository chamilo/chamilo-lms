<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository;

use Chamilo\CoreBundle\Entity\Ticket;
use Chamilo\CoreBundle\Entity\TicketMessage;
use Chamilo\CoreBundle\Entity\TicketMessageAttachment;
use Chamilo\CoreBundle\Repository\Node\TicketMessageAttachmentRepository;
use Chamilo\CoreBundle\Repository\TicketRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class TicketRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        $em = $this->getEntityManager();
        $ticketRepo = self::getContainer()->get(TicketRepository::class);

        $user = $this->createUser('user');
        $assignedUser = $this->createUser('assigned');

        $ticket = (new Ticket())
            ->setSubject('subject')
            ->setMessage('message')
            ->setCode('code')
            ->setPersonalEmail('email@email.com')
            ->setTotalMessages(0)
            ->setAssignedLastUser($assignedUser)
            ->setInsertUserId($user->getId())
        ;
        $this->assertHasNoEntityViolations($ticket);
        $em->persist($ticket);
        $em->flush();

        $this->assertSame(1, $ticketRepo->count([]));
    }

    public function testCreateWithAttachment(): void
    {
        $em = $this->getEntityManager();
        $ticketRepo = self::getContainer()->get(TicketRepository::class);
        $attachmentRepo = self::getContainer()->get(TicketMessageAttachmentRepository::class);

        $user = $this->createUser('user');
        $assignedUser = $this->createUser('assigned');

        $ticket = (new Ticket())
            ->setSubject('subject')
            ->setMessage('message')
            ->setCode('code')
            ->setPersonalEmail('email@email.com')
            ->setAssignedLastUser($assignedUser)
            ->setInsertUserId($user->getId())
        ;
        $em->persist($ticket);
        $em->flush();

        $message = (new TicketMessage())
            ->setMessage('message')
            ->setIpAddress('127.0.0.1')
            ->setSubject('subject')
            ->setInsertUserId($user->getId())
            ->setTicket($ticket)
            ->setStatus('1')
        ;
        $em->persist($message);

        $attachment = (new TicketMessageAttachment())
            ->setFilename('file')
            ->setPath(uniqid('ticket_message', true))
            ->setMessage($message)
            ->setSize(1)
            ->setTicket($ticket)
            ->setInsertUserId($user->getId())
            ->setInsertDateTime(api_get_utc_datetime(null, false, true))
            ->setCreator($user)
            ->setParent($user)
        ;

        if (null !== $ticket->getAssignedLastUser()) {
            $attachment->addUserLink($ticket->getAssignedLastUser());
        }

        $em->persist($attachment);
        $em->flush();

        $this->assertSame(1, $ticketRepo->count([]));
        $this->assertSame(1, $attachmentRepo->count([]));
    }
}
