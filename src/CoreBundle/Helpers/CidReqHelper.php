<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\EventListener\CidReqListener;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @see CidReqListener::onKernelRequest()
 */
class CidReqHelper
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly EntityManagerInterface $em,
    ) {}

    private function getRequest(): ?Request
    {
        return $this->requestStack->getCurrentRequest();
    }

    private function getSessionHandler(): ?SessionInterface
    {
        $request = $this->getRequest();

        return $request?->getSession();
    }

    public function getSessionId(): ?int
    {
        $session = $this->getSessionHandler();

        return $session?->get('sid');
    }

    public function getSessionEntity(): ?Session
    {
        $session = $this->getSessionHandler();

        return $session?->get('session');
    }

    public function getCourseId(): mixed
    {
        $session = $this->getSessionHandler();

        return $session?->get('cid');
    }

    public function getCourseEntity(): ?Course
    {
        $session = $this->getSessionHandler();

        return $session?->get('course');
    }

    public function getGroupId(): ?int
    {
        $session = $this->getSessionHandler();

        return $session?->get('gid');
    }

    public function getDoctrineCourseEntity(): ?Course
    {
        $courseId = $this->getCourseId();
        if (empty($courseId)) {
            return null;
        }

        return $this->em->getRepository(Course::class)->find((int) $courseId);
    }

    public function getDoctrineSessionEntity(): ?Session
    {
        $sessionId = $this->getSessionId();
        if (empty($sessionId)) {
            return null;
        }

        return $this->em->getRepository(Session::class)->find((int) $sessionId);
    }
}
