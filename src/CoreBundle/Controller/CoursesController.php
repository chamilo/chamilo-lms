<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Gedmo\Sluggable\Util\Urlizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/courses')]
class CoursesController extends AbstractController
{
    /**
     * Redirects legacy /courses/ABC/index.php to /courses/1/ (where 1 is the course id) see CourseHomeController.
     */
    #[Route('/{code}/index.php', name: 'chamilo_core_course_home_redirect')]
    public function homeRedirect(Course $course): Response
    {
        return $this->redirectToRoute('chamilo_core_course_home', [
            'cid' => $course->getId(),
        ]);
    }

    /**
     * Redirects legacy /courses/ABC/document/images/file.jpg to the /r/document/file/123/view URL.
     */
    #[Route('/{code}/document/{path}', name: 'chamilo_core_course_document_redirect', requirements: ['path' => '.*'])]
    public function documentRedirect(Course $course, string $path, CDocumentRepository $documentRepository): Response
    {
        $pathList = explode('/', $path);

        /** @var CDocument|null $document */
        $document = null;
        $parent = $course;
        foreach ($pathList as $pathPart) {
            $pathPart = Urlizer::urlize($pathPart);
            $document = $documentRepository->findCourseResourceBySlugIgnoreVisibility($pathPart, $parent->getResourceNode(), $course);
            if (null !== $document) {
                $parent = $document;
            }
        }

        if (null !== $document && $document->getResourceNode()->hasResourceFile()) {
            return $this->redirectToRoute('chamilo_core_resource_view', [
                'tool' => 'document',
                'type' => 'file',
                'id' => $document->getResourceNode()->getUuid(),
            ]);
        }

        throw new AccessDeniedException('File not found');
    }
}
