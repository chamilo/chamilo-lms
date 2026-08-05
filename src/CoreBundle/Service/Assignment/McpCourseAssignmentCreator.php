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
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Security;

use const DATE_ATOM;

final readonly class McpCourseAssignmentCreator
{
    public function __construct(
        private CStudentPublicationRepository $publicationRepository,
        private EntityManagerInterface $entityManager,
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
        ?DateTime $dueDate = null,
        bool $preserveExistingDueDate = false,
    ): array {
        $title = trim(strip_tags($title));
        $description = trim($description);

        if ('' === $title) {
            throw new InvalidArgumentException('The assignment title is required.');
        }
        if (mb_strlen($title) > 255) {
            throw new InvalidArgumentException('The assignment title cannot be longer than 255 characters.');
        }
        if (mb_strlen($description) > 2_000_000) {
            throw new InvalidArgumentException('The assignment description is too large.');
        }

        $description = (string) Security::remove_XSS($description);
        if ('' === trim(strip_tags($description))) {
            throw new InvalidArgumentException('The assignment description is required.');
        }
        if ($maximumScore <= 0.0 || $maximumScore > 100000.0) {
            throw new InvalidArgumentException('The maximum score must be greater than zero and no greater than 100000.');
        }
        if (!\in_array($submissionMode, [0, 1, 2], true)) {
            throw new InvalidArgumentException('The submission mode must be 0, 1 or 2.');
        }
        if (null !== $dueDate && $dueDate <= new DateTime()) {
            throw new InvalidArgumentException('The assignment due date must be in the future.');
        }

        $existing = $this->findExistingAssignment($course, $title);
        if ($existing instanceof CStudentPublication) {
            $updated = $this->synchronizeExistingAssignment(
                $existing,
                $description,
                $maximumScore,
                $publish,
                $submissionMode,
                $dueDate,
                $preserveExistingDueDate,
            );

            return $this->normalize($existing, $course, false, $updated);
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
            ->setExpiresOn($dueDate)
            ->setEndsOn($dueDate)
        ;
        $publication->setAssignment($assignment);

        $this->publicationRepository->create($publication);

        return $this->normalize($publication, $course, true, false);
    }

    private function synchronizeExistingAssignment(
        CStudentPublication $publication,
        string $description,
        float $maximumScore,
        bool $publish,
        int $submissionMode,
        ?DateTime $dueDate,
        bool $preserveExistingDueDate,
    ): bool {
        $updated = false;

        if ($description !== (string) $publication->getDescription()) {
            $publication->setDescription($description);
            $updated = true;
        }
        if ($maximumScore !== (float) $publication->getQualification()) {
            $publication->setQualification($maximumScore);
            $updated = true;
        }
        if ($submissionMode !== (int) $publication->getAllowTextAssignment()) {
            $publication->setAllowTextAssignment($submissionMode);
            $updated = true;
        }

        $assignment = $publication->getAssignment();
        if (!$assignment instanceof CStudentPublicationAssignment) {
            $assignment = (new CStudentPublicationAssignment())
                ->setPublication($publication)
                ->setEnableQualification(true)
                ->setEventCalendarId(0)
            ;
            $publication->setAssignment($assignment);
            $updated = true;
        }
        if (null !== $dueDate) {
            $existingDueDate = $assignment->getExpiresOn() ?? $assignment->getEndsOn();
            $targetDueDate = $preserveExistingDueDate && null !== $existingDueDate
                ? $existingDueDate
                : $dueDate;

            if ($assignment->getExpiresOn()?->getTimestamp() !== $targetDueDate->getTimestamp()
                || $assignment->getEndsOn()?->getTimestamp() !== $targetDueDate->getTimestamp()
            ) {
                $assignment
                    ->setExpiresOn($targetDueDate)
                    ->setEndsOn($targetDueDate)
                ;
                $updated = true;
            }
        }

        $visibility = $publish
            ? ResourceLink::VISIBILITY_PUBLISHED
            : ResourceLink::VISIBILITY_DRAFT;
        $resourceLink = $publication->getFirstResourceLink();
        if ($resourceLink instanceof ResourceLink && $resourceLink->getVisibility() !== $visibility) {
            $resourceLink->setVisibility($visibility);
            $this->entityManager->persist($resourceLink);
            $updated = true;
        }

        if ($updated) {
            $this->entityManager->persist($publication);
            $this->entityManager->persist($assignment);
            $this->entityManager->flush();
        }

        return $updated;
    }

    private function findExistingAssignment(Course $course, string $title): ?CStudentPublication
    {
        $result = $this->entityManager->createQueryBuilder()
            ->select('publication', 'assignment')
            ->from(CStudentPublication::class, 'publication')
            ->innerJoin('publication.assignment', 'assignment')
            ->innerJoin('publication.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'resourceLink')
            ->andWhere('IDENTITY(resourceLink.course) = :courseId')
            ->andWhere('resourceLink.session IS NULL')
            ->andWhere('resourceLink.group IS NULL')
            ->andWhere('resourceLink.userGroup IS NULL')
            ->andWhere('resourceLink.user IS NULL')
            ->andWhere('publication.title = :title')
            ->andWhere('publication.filetype = :filetype')
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setParameter('title', $title, Types::STRING)
            ->setParameter('filetype', 'folder', Types::STRING)
            ->orderBy('publication.iid', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $result instanceof CStudentPublication ? $result : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalize(
        CStudentPublication $publication,
        Course $course,
        bool $created,
        bool $updatedExisting,
    ): array {
        $assignment = $publication->getAssignment();

        return [
            'created' => $created,
            'reused_existing' => !$created,
            'updated_existing' => $updatedExisting,
            'assignment_id' => (int) $publication->getIid(),
            'resource_node_id' => (int) $publication->getResourceNode()?->getId(),
            'title' => $publication->getTitle(),
            'description' => $publication->getDescription(),
            'maximum_score' => $publication->getQualification(),
            'submission_mode' => $publication->getAllowTextAssignment(),
            'due_at' => $assignment?->getExpiresOn()?->format(DATE_ATOM),
            'published' => ResourceLink::VISIBILITY_PUBLISHED === $publication->getFirstResourceLink()?->getVisibility(),
            'content_url' => '/resources/assignment/'
                .(int) $course->getResourceNode()?->getId()
                .'/submission/'
                .(int) $publication->getIid()
                .'?cid='.(int) $course->getId(),
        ];
    }
}
