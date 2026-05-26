<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Entity\TrackECourseAccess;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Event\SessionAccess;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class SessionAccessListener
{
    protected ?Request $request = null;

    public function __construct(
        private readonly EntityManager $em,
        RequestStack $requestStack
    ) {
        $this->request = $requestStack->getCurrentRequest();
    }

    public function __invoke(SessionAccess $event): void
    {
        $user = $event->getUser();
        if (!$user instanceof User) {
            return;
        }

        $userId = (int) $user->getId();
        if ($userId <= 0) {
            return;
        }

        // Ensure the user is managed; avoid Doctrine trying to persist the User entity.
        $userRef = $this->em->contains($user)
            ? $user
            : $this->em->getReference(User::class, $userId);

        $course = $event->getCourse();
        $session = $event->getSession();
        $ip = (string) ($this->request?->getClientIp() ?? '');

        $now = new DateTime('now', new DateTimeZone('UTC'));

        $access = new TrackECourseAccess();
        $access
            ->setCId($course->getId())
            ->setUser($userRef)
            ->setSessionId($session->getId())
            ->setUserIp($ip)
            ->setLoginCourseDate($now)
            ->setLogoutCourseDate($now)
            ->setCounter(1)
        ;

        $this->em->persist($access);
        $this->em->flush();
    }
}
