<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DataFixtures;

use Chamilo\CoreBundle\Entity\TicketCategory;
use Chamilo\CoreBundle\Entity\TicketPriority;
use Chamilo\CoreBundle\Entity\TicketProject;
use Chamilo\CoreBundle\Entity\TicketStatus;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use TicketManager;

/**
 * @internal
 * @coversNothing
 */
class TicketFixtures extends KernelTestCase
{
    public function load(ObjectManager $manager): void
    {
        self::bootKernel();

        $container = $this->getContainer();
        $trans = $container->get('translator');

        $adminId = 1;

        $ticketProject = new TicketProject();
        $ticketProject
            ->setName('Ticket System')
            ->setInsertUserId($adminId)
        ;

        $manager->persist($ticketProject);
        $manager->flush();

        $categories = [
            $trans->trans('Enrollment') => $trans->trans('Tickets about enrollment'),
            $trans->trans('General information') => $trans->trans('Tickets about general information'),
            $trans->trans('Requests and paperwork') => $trans->trans('Tickets about requests and paperwork'),
            $trans->trans('Academic Incidents') => $trans->trans('Tickets about academic incidents, like exams, practices, tasks, etc.'),
            $trans->trans('Virtual campus') => $trans->trans('Tickets about virtual campus'),
            $trans->trans('Online evaluation') => $trans->trans('Tickets about online evaluation'),
        ];

        $i = 1;
        foreach ($categories as $category => $description) {
            // Online evaluation requires a course
            $ticketCategory = new TicketCategory();
            $ticketCategory
                ->setName($category)
                ->setDescription($description)
                ->setProject($ticketProject)
                ->setInsertUserId($adminId)
            ;

            $isRequired = 6 === $i;
            $ticketCategory->setCourseRequired($isRequired);

            $manager->persist($ticketCategory);
            $i++;
        }

        // Default Priorities
        $defaultPriorities = [
            TicketManager::PRIORITY_NORMAL => $trans->trans('Normal'),
            TicketManager::PRIORITY_HIGH => $trans->trans('High'),
            TicketManager::PRIORITY_LOW => $trans->trans('Low'),
        ];

        foreach ($defaultPriorities as $code => $priority) {
            $ticketPriority = new TicketPriority();
            $ticketPriority
                ->setName($priority)
                ->setCode($code)
                ->setInsertUserId($adminId)
            ;

            $manager->persist($ticketPriority);
        }

        $manager->flush();

        // Default status
        $defaultStatus = [
            TicketManager::STATUS_NEW => $trans->trans('New'),
            TicketManager::STATUS_PENDING => $trans->trans('Pending'),
            TicketManager::STATUS_UNCONFIRMED => $trans->trans('Unconfirmed'),
            TicketManager::STATUS_CLOSE => $trans->trans('Close'),
            TicketManager::STATUS_FORWARDED => $trans->trans('Forwarded'),
        ];

        foreach ($defaultStatus as $code => $status) {
            $ticketStatus = new TicketStatus();
            $ticketStatus
                ->setName($status)
                ->setCode($code)
            ;
            $manager->persist($ticketStatus);
        }

        $manager->flush();
    }
}
