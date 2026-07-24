<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Assignment;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Entity\CStudentPublicationAssignment;
use Chamilo\CourseBundle\Repository\CStudentPublicationRepository;
use DateTime;
use InvalidArgumentException;

final readonly class McpCourseAssignmentCreator
{
    public function __construct(
        private CStudentPublicationRepository $publicationRepository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function create(
        Course $course,
        User $user,
        string $title,
        string $description,
        float $maximumScore,
        bool $publish,
        int $submissionMode,
    ): array {
        $title = trim(strip_tags($title));
        $description = trim($description);

        if ('' === $title) {
            throw new InvalidArgumentException('The assignment title is required.');
        }
        if (255 < mb_strlen($title)) {
            throw new InvalidArgumentException('The assignment title cannot be longer than 255 characters.');
        }
        if (2_000_000 < mb_strlen($description)) {
            throw new InvalidArgumentException('The assignment description is too large.');
        }

        $description = (string) \Security::remove_XSS($description);
        if ('' === trim(strip_tags($description))) {
            throw new InvalidArgumentException('The assignment description is required.');
        }
        if (0.0 >= $maximumScore || 100000.0 < $maximumScore) {
            throw new InvalidArgumentException('The maximum score must be greater than zero and no greater than 100000.');
        }
        if (!\in_array($submissionMode, [0, 1, 2], true)) {
            throw new InvalidArgumentException('The submission mode must be 0, 1 or 2.');
        }

        $visibility = $publish
            ? ResourceLink::VISIBILITY_PUBLISHED
            : ResourceLink::VISIBILITY_DRAFT;

        $publication = (new CStudentPublication())
            ->setTitle($title)
            ->setDescription($description)
            ->setUser($user)
            ->setAuthor($user->getFullName())
            ->setSentDate(new DateTime())
            ->setQualification($maximumScore)
            ->setWeight(0.0)
            ->setAllowTextAssignment($submissionMode)
            ->setFiletype('folder')
            ->setActive(1)
            ->setAccepted(true)
            ->setPostGroupId(0)
            ->setQualificatorId(0)
            ->setParent($course)
            ->addCourseLink($course, null, null, $visibility)
        ;

        $assignment = (new CStudentPublicationAssignment())
            ->setPublication($publication)
            ->setEnableQualification(true)
            ->setEventCalendarId(0)
        ;
        $publication->setAssignment($assignment);

        $this->publicationRepository->create($publication);

        return [
            'assignment_id' => (int) $publication->getIid(),
            'resource_node_id' => (int) $publication->getResourceNode()?->getId(),
            'title' => $publication->getTitle(),
            'description' => $publication->getDescription(),
            'maximum_score' => $publication->getQualification(),
            'submission_mode' => $publication->getAllowTextAssignment(),
            'published' => $publish,
            'content_url' => '/resources/assignment/'
                .(int) $course->getResourceNode()?->getId()
                .'/submission/'
                .(int) $publication->getIid()
                .'?cid='.(int) $course->getId(),
        ];
    }
}
