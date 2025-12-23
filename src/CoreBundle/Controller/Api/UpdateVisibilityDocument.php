<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

#[AsController]
class UpdateVisibilityDocument extends AbstractController
{
    public function __construct(
        private readonly CidReqHelper $cidReqHelper,
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {}

    public function __invoke(CDocument $document, CDocumentRepository $repo): CDocument
    {
        $request = $this->requestStack->getCurrentRequest();

        $cid = $request?->query->getInt('cid', 0) ?? 0;
        $sid = $request?->query->getInt('sid', 0) ?? 0;

        $courseFromHelper = $this->cidReqHelper->getCourseEntity();
        $sessionFromHelper = $this->cidReqHelper->getSessionEntity();

        $courseId = $courseFromHelper?->getId() ?? $cid;
        $sessionId = $sessionFromHelper?->getId() ?? $sid;

        $course = $courseId > 0 ? $this->em->getRepository(Course::class)->find($courseId) : null;
        $session = $sessionId > 0 ? $this->em->getRepository(Session::class)->find($sessionId) : null;

        if (null === $course) {
            throw new BadRequestHttpException('Course context is required to toggle visibility.');
        }

        $repo->toggleVisibilityPublishedDraft($document, $course, $session);

        return $document;
    }
}
