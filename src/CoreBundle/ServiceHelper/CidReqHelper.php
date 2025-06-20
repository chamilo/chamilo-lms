<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\ServiceHelper;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\EventListener\CidReqListener;
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
}
