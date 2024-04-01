<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\AgendaReminder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DeleteRemindersByEventAction extends AbstractController
{
    public function __invoke(Request $request, EntityManagerInterface $em): Response
    {
        $data = json_decode($request->getContent(), true);
        $eventId = $data['eventId'] ?? null;

        if (!$eventId) {
            return $this->json(['message' => 'Event ID is required.'], Response::HTTP_BAD_REQUEST);
        }

        $repository = $em->getRepository(AgendaReminder::class);
        $reminders = $repository->findBy(['eventId' => $eventId]);

        foreach ($reminders as $reminder) {
            $em->remove($reminder);
        }
        $em->flush();

        return $this->json(['message' => 'All reminders for the event have been deleted.']);
    }
}
