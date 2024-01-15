<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Traits;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

trait CourseFromRequestTrait
{
    protected RequestStack $requestStack;
    protected EntityManagerInterface $entityManager;

    public function getRequest(): ?Request
    {
        return $this->requestStack->getMainRequest();
    }

    public function getCourse(): ?Course
    {
        $request = $this->getRequest();

        if ($request) {
            $courseId = $request->getSession()->get('cid', 0);
        }

        if (empty($courseId)) {
            return null;
        }

        return $this->entityManager->find(Course::class, $courseId);
    }

    public function getSession(): ?Session
    {
        $request = $this->getRequest();

        if ($request) {
            $sessionId = $request->getSession()->get('sid', 0);
        }

        if (empty($sessionId)) {
            return null;
        }

        return $this->entityManager->find(Session::class, $sessionId);
    }
}
