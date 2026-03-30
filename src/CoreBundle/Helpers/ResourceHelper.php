<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\TrackEDefault;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use ReflectionClass;

class ResourceHelper
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserHelper $userHelper,
    ) {}

    public function createResourceEvent(
        ResourceNode $resourceNode,
        string $eventType,
        ?int $userId = null,
        ?int $courseId = null,
        ?int $sessionId = null
    ): ?TrackEDefault {
        if (!$userId) {
            $user = $this->userHelper->getCurrent();

            if ($user) {
                $userId = $user->getId();
            }
        }

        if (null === $courseId || null === $sessionId) {
            $link = $resourceNode->getResourceLinks()->first();
            if (!$link) {
                return null;
            }

            if (null === $courseId && $link->getCourse()) {
                $courseId = $link->getCourse()->getId();
            }
            if (null === $sessionId && $link->getSession()) {
                $sessionId = $link->getSession()->getId();
            }
        }

        $resourceTypeTitle = $resourceNode->getResourceType()?->getTitle();
        if (null === $resourceTypeTitle) {
            $resourceTypeTitle = (new ReflectionClass($resourceNode))->getShortName();
        }

        $event = new TrackEDefault();
        $event->setDefaultUserId($userId ?? 0);
        $event->setCId($courseId);
        $event->setSessionId($sessionId);
        $event->setDefaultDate(new DateTime());
        $event->setDefaultEventType($eventType);
        $event->setDefaultValueType('resource_type_'.$resourceTypeTitle);
        $event->setDefaultValue((string) $resourceNode->getId());

        return $event;
    }

    public function createAndSaveResourceEvent(
        ResourceNode $resourceNode,
        string $eventType,
        ?int $userId = null,
        ?int $courseId = null,
        ?int $sessionId = null
    ): void {
        $event = $this->createResourceEvent($resourceNode, $eventType, $userId, $courseId, $sessionId);

        if (null === $event) {
            return;
        }

        $this->entityManager->persist($event);
        $this->entityManager->flush();
    }
}
