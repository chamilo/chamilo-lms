<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ServiceHelper;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\EventListener\CourseListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @see CourseListener::onKernelRequest()
 */
class CidReqHelper
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    private function getRequest(): ?Request
    {
        return $this->requestStack->getCurrentRequest();
    }

    private function getSessionHandler(): SessionInterface
    {
        return $this->getRequest()->getSession();
    }

    public function getSessionId(): ?int
    {
        return $this->getSessionHandler()->get('sid');
    }

    public function getSessionEntity(): ?Session
    {
        return $this->getSessionHandler()->get('session');
    }

    public function getCourseId()
    {
        return $this->getSessionHandler()->get('cid');
    }

    public function getCourseEntity(): ?Course
    {
        return $this->getSessionHandler()->get('course');
    }

    public function getGroupId(): ?int
    {
        return $this->getSessionHandler()->get('gid');
    }
}
