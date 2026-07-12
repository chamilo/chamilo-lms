<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Wiki;

use Chamilo\CoreBundle\ApiResource\Wiki\WikiPageForm;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CGroupRelUser;
use Chamilo\CourseBundle\Entity\CWiki;
use Chamilo\CourseBundle\Entity\CWikiConf;
use Chamilo\CourseBundle\Repository\CWikiRepository;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

use const DATE_ATOM;
use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

final readonly class WikiAssignmentService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CWikiRepository $wikiRepository,
        private WikiPageRenderer $renderer,
        private WikiAssignmentFeedbackResolver $feedbackResolver,
    ) {}

    /**
     * @return array<int, User>
     */
    public function getTargetUsers(Course $course, ?Session $session, ?CGroup $group, User $teacher): array
    {
        $courseId = (int) $course->getId();
        $teacherId = (int) $teacher->getId();
        $users = [];

        if ($group instanceof CGroup && null !== $group->getIid()) {
            $relations = $this->entityManager->getRepository(CGroupRelUser::class)->createQueryBuilder('relation')
                ->innerJoin('relation.user', 'user')
                ->addSelect('user')
                ->andWhere('relation.cId = :courseId')
                ->andWhere('IDENTITY(relation.group) = :groupId')
                ->andWhere('user.active = :active')
                ->setParameter('courseId', $courseId, Types::INTEGER)
                ->setParameter('groupId', (int) $group->getIid(), Types::INTEGER)
                ->setParameter('active', User::ACTIVE, Types::INTEGER)
                ->getQuery()
                ->getResult()
            ;

            foreach ($relations as $relation) {
                if (!$relation instanceof CGroupRelUser) {
                    continue;
                }

                $this->addTargetUser($users, $relation->getUser(), $teacherId);
            }

            return $this->sortUsers($users);
        }

        if ($session instanceof Session && null !== $session->getId()) {
            $relations = $this->entityManager->getRepository(SessionRelCourseRelUser::class)->createQueryBuilder('relation')
                ->innerJoin('relation.user', 'user')
                ->addSelect('user')
                ->andWhere('IDENTITY(relation.course) = :courseId')
                ->andWhere('IDENTITY(relation.session) = :sessionId')
                ->andWhere('relation.status = :student')
                ->andWhere('user.active = :active')
                ->setParameter('courseId', $courseId, Types::INTEGER)
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
                ->setParameter('student', Session::STUDENT, Types::INTEGER)
                ->setParameter('active', User::ACTIVE, Types::INTEGER)
                ->getQuery()
                ->getResult()
            ;

            foreach ($relations as $relation) {
                if (!$relation instanceof SessionRelCourseRelUser) {
                    continue;
                }

                $this->addTargetUser($users, $relation->getUser(), $teacherId);
            }

            return $this->sortUsers($users);
        }

        $relations = $this->entityManager->getRepository(CourseRelUser::class)->createQueryBuilder('relation')
            ->innerJoin('relation.user', 'user')
            ->addSelect('user')
            ->andWhere('IDENTITY(relation.course) = :courseId')
            ->andWhere('relation.status = :student')
            ->andWhere('user.active = :active')
            ->setParameter('courseId', $courseId, Types::INTEGER)
            ->setParameter('student', CourseRelUser::STUDENT, Types::INTEGER)
            ->setParameter('active', User::ACTIVE, Types::INTEGER)
            ->getQuery()
            ->getResult()
        ;

        foreach ($relations as $relation) {
            if (!$relation instanceof CourseRelUser) {
                continue;
            }

            $this->addTargetUser($users, $relation->getUser(), $teacherId);
        }

        return $this->sortUsers($users);
    }

    public function countTargetUsers(Course $course, ?Session $session, ?CGroup $group, User $teacher): int
    {
        return \count($this->getTargetUsers($course, $session, $group, $teacher));
    }

    /**
     * @return array{teacherPage:CWiki, createdPages:array<int, CWiki>}
     */
    public function createAssignmentPages(
        WikiPageForm $data,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        User $teacher,
        string $title,
        string $baseReflink,
        string $content,
        string $comment,
        int $progress,
        string $clientIp,
    ): array {
        $courseId = (int) $course->getId();
        $sessionId = null !== $session ? (int) $session->getId() : 0;
        $groupId = null !== $group?->getIid() ? (int) $group->getIid() : 0;
        $teacherId = (int) $teacher->getId();
        $contextAddLock = $this->wikiRepository->findContextAddLock($courseId, $groupId, $sessionId);
        $targetUsers = $this->getTargetUsers($course, $session, $group, $teacher);
        $teacherReflink = $this->assignmentReflink($baseReflink, $teacherId);
        $studentReflinks = [];

        foreach ($targetUsers as $targetUser) {
            $targetUserId = (int) $targetUser->getId();
            $studentReflinks[$targetUserId] = $this->assignmentReflink($baseReflink, $targetUserId);
        }

        $candidateReflinks = [$teacherReflink, ...array_values($studentReflinks)];
        foreach ($candidateReflinks as $candidateReflink) {
            if ($this->wikiRepository->reflinkExistsInContext($courseId, $candidateReflink, $groupId, $sessionId)) {
                throw new ConflictHttpException('A Wiki assignment page already exists for one of the selected users.');
            }
        }

        $createdPages = [];
        $studentLinks = [];

        foreach ($targetUsers as $targetUser) {
            $targetUserId = (int) $targetUser->getId();
            $studentReflink = $studentReflinks[$targetUserId];
            $studentLinks[] = '<li>[['.$studentReflink.'|'.$this->escapeWikiLabel($targetUser->getFullName()).']]</li>';
            $studentContent = '<h2>Learner work</h2>'
                .'<p>'.$this->escapeHtml($targetUser->getFullName()).'</p>'
                .'<p>[['.$teacherReflink.'|Access teacher page]]</p>'
                .'<p>&nbsp;</p>';

            $createdPages[] = $this->createPage(
                $course,
                $session,
                $group,
                $targetUser,
                $title,
                $studentReflink,
                $studentContent,
                $comment,
                $progress,
                2,
                0,
                0,
                0,
                $contextAddLock,
                $clientIp,
            );
        }

        $teacherContent = '<h2>Assignment proposed by the trainer</h2>'
            .'<p>'.$this->escapeHtml($teacher->getFullName()).'</p>'
            .'<div>'.$content.'</div>'
            .'<h3>Access to learner work</h3>'
            .([] === $studentLinks ? '<p>No eligible learners were found in this context.</p>' : '<ol>'.implode('', $studentLinks).'</ol>');
        $teacherPage = $this->createPage(
            $course,
            $session,
            $group,
            $teacher,
            $title,
            $teacherReflink,
            $teacherContent,
            'Assignment proposed by the trainer',
            $progress,
            1,
            1,
            1,
            1,
            $contextAddLock,
            $clientIp,
        );
        $createdPages[] = $teacherPage;

        $this->entityManager->flush();

        foreach ($createdPages as $createdPage) {
            if (null === $createdPage->getIid()) {
                throw new ConflictHttpException('The Wiki assignment page could not be created.');
            }

            $createdPage->setPageId((int) $createdPage->getIid());
            $this->saveConfiguration($data, $createdPage, $courseId);
            $this->applyResourceLanguage($createdPage, $data->language);
        }

        $this->entityManager->flush();

        return [
            'teacherPage' => $teacherPage,
            'createdPages' => $createdPages,
        ];
    }

    public function saveConfiguration(WikiPageForm $data, CWiki $wiki, int $courseId): void
    {
        $pageId = $wiki->getPageId();
        if (null === $pageId) {
            throw new BadRequestHttpException('The Wiki assignment page id is missing.');
        }

        $configuration = $this->entityManager->getRepository(CWikiConf::class)->findOneBy([
            'cId' => $courseId,
            'pageId' => (int) $pageId,
        ]);
        $hasValues = $this->hasConfigurationValues($data) || $wiki->getAssignment() > 0;

        if (!$configuration instanceof CWikiConf && !$hasValues) {
            return;
        }

        if (!$configuration instanceof CWikiConf) {
            $configuration = new CWikiConf();
            $configuration
                ->setCId($courseId)
                ->setPageId((int) $pageId)
            ;
        }

        $configuration
            ->setTask($data->task)
            ->setFeedback1($data->feedback1)
            ->setFeedback2($data->feedback2)
            ->setFeedback3($data->feedback3)
            ->setFprogress1($this->feedbackResolver->serializeProgress($data->feedbackProgress1))
            ->setFprogress2($this->feedbackResolver->serializeProgress($data->feedbackProgress2))
            ->setFprogress3($this->feedbackResolver->serializeProgress($data->feedbackProgress3))
            ->setMaxText($data->maxWords)
            ->setMaxVersion($data->maxVersions)
            ->setStartdateAssig($this->parseDate($data->startDate))
            ->setEnddateAssig($this->parseDate($data->endDate))
            ->setDelayedsubmit($data->delayedSubmit ? 1 : 0)
        ;

        $this->entityManager->persist($configuration);
    }

    public function populateFormConfiguration(WikiPageForm $form, ?CWikiConf $configuration): void
    {
        if (!$configuration instanceof CWikiConf) {
            return;
        }

        $form->task = (string) $configuration->getTask();
        $form->feedback1 = (string) $configuration->getFeedback1();
        $form->feedback2 = (string) $configuration->getFeedback2();
        $form->feedback3 = (string) $configuration->getFeedback3();
        $form->feedbackProgress1 = $this->feedbackResolver->normalizeStoredProgress((string) $configuration->getFprogress1());
        $form->feedbackProgress2 = $this->feedbackResolver->normalizeStoredProgress((string) $configuration->getFprogress2());
        $form->feedbackProgress3 = $this->feedbackResolver->normalizeStoredProgress((string) $configuration->getFprogress3());
        $form->startDate = $configuration->getStartdateAssig()?->format(DATE_ATOM);
        $form->endDate = $configuration->getEnddateAssig()?->format(DATE_ATOM);
        $form->delayedSubmit = 1 === $configuration->getDelayedsubmit();
        $form->maxWords = max(0, (int) $configuration->getMaxText());
        $form->maxVersions = max(0, (int) $configuration->getMaxVersion());
    }

    public function validateConfiguration(WikiPageForm $data): void
    {
        foreach ([$data->feedbackProgress1, $data->feedbackProgress2, $data->feedbackProgress3] as $progress) {
            if ($progress < 0 || $progress > 100 || 0 !== $progress % 10) {
                throw new BadRequestHttpException('A Wiki assignment feedback progress value is invalid.');
            }
        }

        if ($data->maxWords < 0 || $data->maxVersions < 0) {
            throw new BadRequestHttpException('Wiki assignment limits cannot be negative.');
        }

        $startDate = $this->parseDate($data->startDate);
        $endDate = $this->parseDate($data->endDate);

        if ($startDate instanceof DateTimeInterface
            && $endDate instanceof DateTimeInterface
            && $startDate > $endDate
        ) {
            throw new BadRequestHttpException('The end date cannot be before the start date.');
        }
    }

    private function createPage(
        Course $course,
        ?Session $session,
        ?CGroup $group,
        User $owner,
        string $title,
        string $reflink,
        string $content,
        string $comment,
        int $progress,
        int $assignment,
        int $visibility,
        int $discussionVisibility,
        int $ratingLock,
        int $addLock,
        string $clientIp,
    ): CWiki {
        $now = new DateTime('now', new DateTimeZone('UTC'));
        $courseId = (int) $course->getId();
        $sessionId = null !== $session ? (int) $session->getId() : 0;
        $groupId = null !== $group?->getIid() ? (int) $group->getIid() : 0;
        $wiki = new CWiki();
        $wiki
            ->setCId($courseId)
            ->setPageId(0)
            ->setReflink($reflink)
            ->setTitle($title)
            ->setContent($content)
            ->setUserId((int) $owner->getId())
            ->setGroupId($groupId)
            ->setDtime($now)
            ->setAddlock($addLock)
            ->setEditlock(0)
            ->setVisibility($visibility)
            ->setAddlockDisc(1)
            ->setVisibilityDisc($discussionVisibility)
            ->setRatinglockDisc($ratingLock)
            ->setAssignment($assignment)
            ->setComment($comment)
            ->setProgress((string) ($progress / 10))
            ->setScore(0)
            ->setVersion(1)
            ->setIsEditing(0)
            ->setTimeEdit(null)
            ->setHits(0)
            ->setLinksto($this->renderer->serializeInternalReflinks($content))
            ->setTag('')
            ->setUserIp($clientIp)
            ->setSessionId($sessionId)
            ->setParent($course)
            ->addCourseLink($course, $session, $group)
        ;
        $wiki->setCreator($owner);
        $this->entityManager->persist($wiki);

        return $wiki;
    }

    private function assignmentReflink(string $baseReflink, int $userId): string
    {
        $reflink = $this->renderer->normalizeReflink($baseReflink.'_uass'.$userId);
        if (mb_strlen($reflink) > 255) {
            throw new BadRequestHttpException('The generated Wiki assignment page reference is too long.');
        }

        return $reflink;
    }

    /**
     * @param array<int, User> $users
     */
    private function addTargetUser(array &$users, User $user, int $teacherId): void
    {
        $userId = (int) $user->getId();
        if ($userId <= 0 || $userId === $teacherId) {
            return;
        }

        $users[$userId] = $user;
    }

    /**
     * @param array<int, User> $users
     *
     * @return array<int, User>
     */
    private function sortUsers(array $users): array
    {
        uasort(
            $users,
            static fn (User $first, User $second): int => strcasecmp(
                trim((string) $first->getLastname()).' '.trim((string) $first->getFirstname()),
                trim((string) $second->getLastname()).' '.trim((string) $second->getFirstname()),
            ),
        );

        return array_values($users);
    }

    private function hasConfigurationValues(WikiPageForm $data): bool
    {
        return '' !== trim($data->task)
            || '' !== trim($data->feedback1)
            || '' !== trim($data->feedback2)
            || '' !== trim($data->feedback3)
            || $data->feedbackProgress1 > 0
            || $data->feedbackProgress2 > 0
            || $data->feedbackProgress3 > 0
            || null !== $data->startDate
            || null !== $data->endDate
            || $data->delayedSubmit
            || $data->maxWords > 0
            || $data->maxVersions > 0;
    }

    private function parseDate(?string $value): ?DateTime
    {
        if (null === $value || '' === trim($value)) {
            return null;
        }

        try {
            $date = new DateTimeImmutable($value);
        } catch (Exception) {
            throw new BadRequestHttpException('A Wiki assignment date is invalid.');
        }

        return DateTime::createFromInterface($date->setTimezone(new DateTimeZone('UTC')));
    }

    private function applyResourceLanguage(CWiki $wiki, string $languageCode): void
    {
        $resourceNode = $wiki->getResourceNode();
        if (null === $resourceNode) {
            return;
        }

        $language = null;
        if ('' !== trim($languageCode)) {
            $language = $this->entityManager->getRepository(Language::class)->findOneBy([
                'isocode' => trim($languageCode),
                'available' => true,
            ]);

            if (!$language instanceof Language) {
                throw new BadRequestHttpException('The selected language is invalid.');
            }
        }

        $resourceNode->setLanguage($language);
        $this->entityManager->persist($resourceNode);
    }

    private function escapeHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function escapeWikiLabel(string $value): string
    {
        return str_replace(['|', '[', ']'], '', trim($value));
    }
}
