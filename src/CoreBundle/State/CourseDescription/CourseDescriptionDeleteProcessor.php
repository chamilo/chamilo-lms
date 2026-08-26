<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseDescription;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\CourseDescription\CourseDescriptionItem;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\CourseDescriptionHelper;
use Chamilo\CoreBundle\Helpers\IsAllowedToEditHelper;
use Chamilo\CourseBundle\Entity\CCourseDescription;
use Chamilo\CourseBundle\Repository\CCourseDescriptionRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProcessorInterface<CourseDescriptionItem, void>
 */
final readonly class CourseDescriptionDeleteProcessor implements ProcessorInterface
{
    public function __construct(
        private CidReqHelper $cidReqHelper,
        private CCourseDescriptionRepository $courseDescriptionRepository,
        private Security $security,
        private CourseDescriptionHelper $courseDescriptionHelper,
        private IsAllowedToEditHelper $isAllowedToEditHelper,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        $course = $this->cidReqHelper->requireDoctrineCourseEntity();
        $this->courseDescriptionHelper->assertToolEnabled($course);
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        $this->courseDescriptionHelper->assertSessionBelongsToCourse($session, $course);

        if (!$this->isAllowedToEditHelper->check(coach: true, course: $course, session: $session)) {
            throw new AccessDeniedHttpException('You are not allowed to delete course descriptions in this context.');
        }

        $descriptionId = isset($uriVariables['iid']) ? (int) $uriVariables['iid'] : 0;
        if ($descriptionId <= 0) {
            throw new BadRequestHttpException('A valid course description id is required.');
        }

        $description = $this->courseDescriptionRepository->find($descriptionId);
        if (!$description instanceof CCourseDescription) {
            throw new NotFoundHttpException('The requested course description was not found.');
        }

        if (!$this->belongsToExactContext($description, $course, $session)) {
            throw new AccessDeniedHttpException('The requested course description does not belong to the current course context.');
        }

        $resourceNode = $description->getResourceNode();
        if (null === $resourceNode || !$this->security->isGranted('DELETE', $resourceNode)) {
            throw new AccessDeniedHttpException('You are not allowed to delete this course description.');
        }

        $this->courseDescriptionRepository->delete($description);
    }

    private function belongsToExactContext(CCourseDescription $description, Course $course, ?Session $session): bool
    {
        $resourceNode = $description->getResourceNode();
        if (null === $resourceNode) {
            return false;
        }

        foreach ($resourceNode->getResourceLinks() as $link) {
            if (!$link instanceof ResourceLink) {
                continue;
            }

            $linkCourse = $link->getCourse();
            $linkSession = $link->getSession();
            $sameCourse = null !== $linkCourse && $linkCourse->getId() === $course->getId();
            $sameSession = null === $session
                ? null === $linkSession
                : null !== $linkSession && $linkSession->getId() === $session->getId();

            if ($sameCourse && $sameSession) {
                return true;
            }
        }

        return false;
    }
}
