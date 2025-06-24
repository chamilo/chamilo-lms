<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Utils\CidReqHelper;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Doctrine\ORM\EntityManagerInterface;

#[AsController]
class UpdateVisibilityDocument extends AbstractController
{
    public function __construct(
        private readonly CidReqHelper $cidReqHelper,
        private readonly EntityManagerInterface $em,
    ) {}

    public function __invoke(CDocument $document, CDocumentRepository $repo): CDocument
    {
        $course = $this->cidReqHelper->getCourseEntity();
        $session = $this->cidReqHelper->getSessionEntity();

        if ($course) {
            $course = $this->em->getRepository(Course::class)->find($course->getId());
        }

        if ($session) {
            $session = $this->em->getRepository(Session::class)->find($session->getId());
        }

        $repo->toggleVisibilityPublishedDraft($document, $course, $session);

        return $document;
    }
}
