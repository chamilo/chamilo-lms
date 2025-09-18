<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DataFixtures;

use Chamilo\CoreBundle\Entity\TicketCategory;
use Chamilo\CoreBundle\Entity\TicketPriority;
use Chamilo\CoreBundle\Entity\TicketProject;
use Chamilo\CoreBundle\Entity\TicketStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Contracts\Translation\TranslatorInterface;
use TicketManager;

class TicketFixtures extends Fixture
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {}

    public function load(ObjectManager $manager): void
    {
        $adminId = 1;

        $ticketProject = new TicketProject();
        $ticketProject
            ->setTitle('Ticket System')
            ->setInsertUserId($adminId)
        ;

        $manager->persist($ticketProject);
        $manager->flush();

        $categories = [
            $this->translator->trans('Enrollment') => $this->translator->trans('Tickets about enrollment'),
            $this->translator->trans('General information') => $this->translator->trans('Tickets about general information'),
            $this->translator->trans('Requests and paperwork') => $this->translator->trans('Tickets about requests and paperwork'),
            $this->translator->trans('Academic Incidents') => $this->translator->trans('Tickets about academic incidents, like exams, practices, tasks, etc.'),
            $this->translator->trans('Virtual campus') => $this->translator->trans('Tickets about virtual campus'),
            $this->translator->trans('Online evaluation') => $this->translator->trans('Tickets about online evaluation'),
        ];

        $i = 1;
        foreach ($categories as $category => $description) {
            // Online evaluation requires a course
            $ticketCategory = new TicketCategory();
            $ticketCategory
                ->setTitle($category)
                ->setDescription($description)
                ->setProject($ticketProject)
                ->setInsertUserId($adminId)
            ;

            $ticketCategory->setCourseRequired(6 === $i);

            $manager->persist($ticketCategory);
            $i++;
        }

        // Default Priorities
        $defaultPriorities = [
            TicketManager::PRIORITY_NORMAL => $this->translator->trans('Normal'),
            TicketManager::PRIORITY_HIGH => $this->translator->trans('High'),
            TicketManager::PRIORITY_LOW => $this->translator->trans('Low'),
        ];

        foreach ($defaultPriorities as $code => $priority) {
            $ticketPriority = new TicketPriority();
            $ticketPriority
                ->setTitle($priority)
                ->setCode((string) $code)
                ->setInsertUserId($adminId)
            ;

            $manager->persist($ticketPriority);
        }

        $manager->flush();

        // Default status
        $defaultStatus = [
            TicketManager::STATUS_NEW => $this->translator->trans('New'),
            TicketManager::STATUS_PENDING => $this->translator->trans('Pending'),
            TicketManager::STATUS_UNCONFIRMED => $this->translator->trans('Unconfirmed'),
            TicketManager::STATUS_CLOSED => $this->translator->trans('Closed'),
            TicketManager::STATUS_FORWARDED => $this->translator->trans('Forwarded'),
        ];

        foreach ($defaultStatus as $code => $status) {
            $ticketStatus = new TicketStatus();
            $ticketStatus
                ->setTitle($status)
                ->setCode((string) $code)
            ;
            $manager->persist($ticketStatus);
        }

        $manager->flush();
    }
}
