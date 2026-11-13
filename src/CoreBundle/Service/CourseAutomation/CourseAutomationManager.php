<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\CourseAutomation;

use ApiPlatform\Metadata\Post;
use Chamilo\CoreBundle\ApiResource\CourseAutomation\CourseAutomationConfiguration;
use Chamilo\CoreBundle\ApiResource\Survey\SurveyInvitation;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseAutomationAction;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookCertificate;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\MailHelper;
use Chamilo\CoreBundle\Repository\GradebookCertificateRepository;
use Chamilo\CoreBundle\Service\CourseReporting\CourseReportingContext;
use Chamilo\CoreBundle\Service\CourseReporting\CourseReportingContextResolver;
use Chamilo\CoreBundle\State\CourseSettings\CourseSettingsManager;
use Chamilo\CoreBundle\State\Survey\SurveyInvitationProcessor;
use Chamilo\CoreBundle\State\Survey\SurveyInvitationProvider;
use Chamilo\CourseBundle\Entity\CSurvey;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Throwable;

use const DATE_ATOM;
use const FILTER_VALIDATE_EMAIL;
use const FILTER_VALIDATE_INT;
use const PATHINFO_FILENAME;

/**
 * Configured, manually triggered actions. Execution is claimed atomically before
 * external side effects; completed actions are not sent a second time.
 */
final readonly class CourseAutomationManager
{
    private const string EVENT_START = 'start';
    private const string EVENT_END = 'end';
    private const string PUBLISH = 'publish_resources';
    private const string HIDE = 'hide_resources';
    private const string SURVEY = 'send_survey';
    private const string CERTIFICATES = 'send_certificates';
    private const string COURSE_VISIBILITY = 'course_visibility';
    private const int MAX_TARGETS = 100;

    public function __construct(
        private CourseReportingContextResolver $contextResolver,
        private EntityManagerInterface $entityManager,
        private Connection $connection,
        private SurveyInvitationProvider $surveyInvitationProvider,
        private SurveyInvitationProcessor $surveyInvitationProcessor,
        private GradebookCertificateRepository $certificateRepository,
        private CourseSettingsManager $courseSettingsManager,
        private MailerInterface $mailer,
        private MailHelper $mailHelper,
        private UrlGeneratorInterface $urlGenerator,
        private LoggerInterface $logger,
        private Security $security,
    ) {}

    public function getConfiguration(): CourseAutomationConfiguration
    {
        $context = $this->contextResolver->resolve();
        $response = new CourseAutomationConfiguration();
        $response->canManage = $this->canManage($context);
        $response->canChangeCourseVisibility = $response->canManage && $this->canChangeVisibility($context);
        $response->canUseHiddenVisibility = $context->isAdministrator;

        foreach ($this->actions($context) as $action) {
            $response->actions[] = [
                'id' => $action->getId(),
                'eventType' => $action->getEventType(),
                'actionType' => $action->getActionType(),
                'targetId' => $action->getResourceLink()?->getId() ?? $action->getTargetId(),
                'settingValue' => $action->getSettingValue(),
                'status' => $action->getStatus(),
                'attempts' => $action->getAttempts(),
                'lastExecutedById' => $action->getLastExecutedBy()?->getId(),
                'lastError' => $action->getLastError(),
                'lastExecutedAt' => $action->getLastExecutedAt()?->format(DATE_ATOM),
            ];
        }

        if (!$response->canManage) {
            return $response;
        }

        foreach ($this->resourceLinks($context) as $link) {
            $node = $link->getResourceNode();
            $response->resources[] = [
                'id' => $link->getId(),
                'title' => $node->getTitle(),
                'type' => $node->getResourceType()->getTitle(),
                'visibility' => $link->getVisibility(),
            ];
        }

        foreach ($this->surveys($context) as $survey) {
            $response->surveys[] = ['id' => $survey->getIid(), 'title' => $survey->getTitle()];
        }

        foreach ($this->gradebooks($context) as $category) {
            $response->gradebooks[] = ['id' => $category->getId(), 'title' => $category->getTitle()];
        }

        return $response;
    }

    public function addAction(CourseAutomationConfiguration $payload): CourseAutomationConfiguration
    {
        $context = $this->requireManage();
        $event = $this->assertEvent($payload->eventType);
        $type = $payload->actionType;
        $allowedTypes = [self::PUBLISH, self::HIDE, self::SURVEY, self::CERTIFICATES, self::COURSE_VISIBILITY];
        if (!\in_array($type, $allowedTypes, true)) {
            throw new BadRequestHttpException('Unsupported automation action.');
        }
        if ((self::PUBLISH === $type && self::EVENT_START !== $event)
            || ((self::HIDE === $type || self::CERTIFICATES === $type) && self::EVENT_END !== $event)) {
            throw new BadRequestHttpException('The selected action is not valid for this event.');
        }
        if (self::COURSE_VISIBILITY === $type && !$this->canChangeVisibility($context)) {
            throw new AccessDeniedHttpException('Course visibility cannot be changed in this context.');
        }

        $targets = [];
        $setting = null;
        if (\in_array($type, [self::PUBLISH, self::HIDE], true)) {
            if ([] === $payload->targetIds || \count($payload->targetIds) > self::MAX_TARGETS) {
                throw new BadRequestHttpException('Select between 1 and 100 valid course resources.');
            }
            $ids = [];
            foreach ($payload->targetIds as $value) {
                if ((!\is_int($value) && !\is_string($value)) || false === filter_var($value, FILTER_VALIDATE_INT) || (int) $value <= 0) {
                    throw new BadRequestHttpException('Invalid course resource identifier.');
                }
                $ids[] = (int) $value;
            }
            $ids = array_values(array_unique($ids));
            $available = [];
            foreach ($this->resourceLinks($context) as $link) {
                $available[(int) $link->getId()] = $link;
            }
            foreach ($ids as $id) {
                if (!isset($available[$id])) {
                    throw new AccessDeniedHttpException('A resource does not belong to this course context.');
                }
                $targets[] = $available[$id];
            }
        } elseif (self::SURVEY === $type) {
            $id = (int) ($payload->targetId ?? 0);
            $survey = $this->surveyInvitationProvider->getSurveyFromCurrentContext($id, $context->course, $context->session);
            $targets[] = (int) $survey->getIid();
        } elseif (self::CERTIFICATES === $type) {
            $id = (int) ($payload->targetId ?? 0);
            $category = $this->findGradebook($id, $context);
            $targets[] = (int) $category->getId();
        } else {
            $setting = $payload->settingValue;
            if (!\in_array($setting, [Course::CLOSED, Course::REGISTERED, Course::OPEN_PLATFORM, Course::OPEN_WORLD, Course::HIDDEN], true)) {
                throw new BadRequestHttpException('Invalid destination course visibility.');
            }
            if (Course::HIDDEN === $setting && !$context->isAdministrator) {
                throw new AccessDeniedHttpException('Only administrators can hide a course.');
            }
            $targets[] = 0;
        }

        $existing = $this->actions($context);
        foreach ($targets as $target) {
            $linkId = $target instanceof ResourceLink ? (int) $target->getId() : null;
            $targetId = $target instanceof ResourceLink || self::COURSE_VISIBILITY === $type ? null : $target;
            foreach ($existing as $item) {
                if (self::COURSE_VISIBILITY === $type && $item->getEventType() === $event && $item->getActionType() === $type) {
                    throw new ConflictHttpException('Only one course visibility action is allowed per event.');
                }
                if ($item->getEventType() === $event && $item->getActionType() === $type
                    && (int) ($item->getResourceLink()?->getId() ?? 0) === (int) ($linkId ?? 0)
                    && (int) ($item->getTargetId() ?? 0) === (int) ($targetId ?? 0)
                    && $item->getSettingValue() === $setting) {
                    throw new ConflictHttpException('This action is already configured.');
                }
            }
        }

        foreach ($targets as $target) {
            $action = new CourseAutomationAction($context->course, $context->session, $event, $type, $context->currentUser);
            if ($target instanceof ResourceLink) {
                $action->setResourceLink($target);
            } elseif (self::COURSE_VISIBILITY !== $type) {
                $action->setTargetId($target);
            }
            $action->setSettingValue($setting);
            $action->setConfigurationKey(hash('sha256', implode(':', [
                $context->courseId(), $context->sessionId(), $event, $type,
                (int) ($action->getResourceLink()?->getId() ?? 0),
                (int) ($action->getTargetId() ?? 0),
                (int) ($setting ?? -1),
            ])));
            $this->entityManager->persist($action);
        }
        $this->entityManager->flush();

        $response = $this->getConfiguration();
        $response->message = 'Automation actions saved.';

        return $response;
    }

    public function deleteAction(int $id): void
    {
        $context = $this->requireManage();
        $action = $this->entityManager->getRepository(CourseAutomationAction::class)->find($id);
        if (!$action instanceof CourseAutomationAction || !$this->belongsToContext($action, $context)) {
            throw new NotFoundHttpException('Automation action not found in this context.');
        }
        if ('running' === $action->getStatus()) {
            throw new ConflictHttpException('An executing action cannot be deleted.');
        }
        $this->entityManager->remove($action);
        $this->entityManager->flush();
    }

    public function executeEvent(string $eventType): CourseAutomationConfiguration
    {
        $context = $this->requireManage();
        $event = $this->assertEvent($eventType);
        $actions = array_filter($this->actions($context), static fn (CourseAutomationAction $action): bool => $action->getEventType() === $event);
        if ([] === $actions) {
            throw new BadRequestHttpException('Configure at least one action before triggering this event.');
        }

        $executed = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($actions as $action) {
            $claimed = $this->connection->executeStatement(
                'UPDATE course_automation_action SET status = :running WHERE id = :id AND status IN (:pending, :failed)',
                ['running' => 'running', 'id' => (int) $action->getId(), 'pending' => 'pending', 'failed' => 'failed'],
            );
            if (1 !== $claimed) {
                ++$skipped;

                continue;
            }

            $action->markRunning($context->currentUser);
            $this->entityManager->flush();

            try {
                $this->executeAction($action, $context);
                $action->markSuccess();
                ++$executed;
            } catch (Throwable $exception) {
                $action->markFailed($exception instanceof HttpExceptionInterface ? $exception->getMessage() : 'Action failed; see the server logs.');
                $this->logger->error('Course automation action failed.', [
                    'actionId' => $action->getId(),
                    'courseId' => $context->courseId(),
                    'sessionId' => $context->sessionId(),
                    'exception' => $exception,
                ]);
                ++$failed;
            }
            $this->entityManager->flush();
        }

        $response = $this->getConfiguration();
        $response->executed = $executed;
        $response->failed = $failed;
        $response->skipped = $skipped;
        $response->message = \sprintf('Executed: %d. Failed: %d. Already completed or running: %d.', $executed, $failed, $skipped);

        return $response;
    }

    private function executeAction(CourseAutomationAction $action, CourseReportingContext $context): void
    {
        $type = $action->getActionType();
        if (self::PUBLISH === $type || self::HIDE === $type) {
            $link = $action->getResourceLink();
            if (!$link instanceof ResourceLink || !$this->resourceBelongsToContext($link, $context)) {
                throw new AccessDeniedHttpException('Resource context has changed.');
            }
            $link->setVisibility(self::PUBLISH === $type ? ResourceLink::VISIBILITY_PUBLISHED : ResourceLink::VISIBILITY_DRAFT);
            $this->entityManager->flush();

            return;
        }

        if (self::COURSE_VISIBILITY === $type) {
            if (!$this->canChangeVisibility($context)) {
                throw new AccessDeniedHttpException('Course visibility can no longer be changed in this context.');
            }
            $visibility = $action->getSettingValue();
            if (!\in_array($visibility, [Course::CLOSED, Course::REGISTERED, Course::OPEN_PLATFORM, Course::OPEN_WORLD, Course::HIDDEN], true)
                || (Course::HIDDEN === $visibility && !$context->isAdministrator)) {
                throw new AccessDeniedHttpException('Destination visibility is not permitted.');
            }
            $this->courseSettingsManager->changeVisibilityForAutomation($context->course, $visibility);

            return;
        }

        if (self::SURVEY === $type) {
            $survey = $this->surveyInvitationProvider->getSurveyFromCurrentContext((int) $action->getTargetId(), $context->course, $context->session);
            $subject = trim($survey->getMailSubject());
            $body = trim($survey->getInviteMail());
            if ('' === $subject || '' === $body) {
                throw new BadRequestHttpException('Configure the survey invitation subject and body before using automation.');
            }
            $dto = new SurveyInvitation();
            $dto->surveyId = (int) $survey->getIid();
            $dto->selectedUserIds = $this->studentIds($context);
            if ([] === $dto->selectedUserIds) {
                throw new BadRequestHttpException('The course has no eligible learners to invite.');
            }
            $dto->mailSubject = $subject;
            $dto->mailText = $body;
            $dto->sendMail = true;
            $dto->resendToAll = false;
            $this->surveyInvitationProcessor->process($dto, new Post(name: 'post_survey_invitations_publish'), ['surveyId' => (int) $survey->getIid()]);

            return;
        }

        if (self::CERTIFICATES === $type) {
            $category = $this->findGradebook((int) $action->getTargetId(), $context);
            $eligible = 0;
            foreach ($this->students($context) as $student) {
                $certificate = $this->certificateRepository->getCertificateByUserId((int) $category->getId(), (int) $student->getId());
                if (!$certificate instanceof GradebookCertificate || !filter_var($student->getEmail(), FILTER_VALIDATE_EMAIL)) {
                    continue;
                }
                $path = trim((string) $certificate->getPathCertificate());
                $hash = pathinfo(basename($path), PATHINFO_FILENAME);
                if (1 !== preg_match('/^[A-Za-z0-9_-]+$/', $hash)) {
                    continue;
                }
                ++$eligible;
                $actionId = (int) $action->getId();
                $userId = (int) $student->getId();
                // Claim each recipient. 'sent' and 'sending' must never be sent again:
                // a process may have died after SMTP delivery but before acknowledgement.
                $inserted = $this->connection->executeStatement(
                    'INSERT IGNORE INTO course_automation_delivery (action_id, user_id, status, created_at) VALUES (:actionId, :userId, :status, NOW())',
                    ['actionId' => $actionId, 'userId' => $userId, 'status' => 'sending'],
                );
                if (0 === $inserted) {
                    $claimedRetry = $this->connection->executeStatement(
                        'UPDATE course_automation_delivery SET status = :sending, last_error = NULL WHERE action_id = :actionId AND user_id = :userId AND status = :failed',
                        ['sending' => 'sending', 'actionId' => $actionId, 'userId' => $userId, 'failed' => 'failed'],
                    );
                    if (0 === $claimedRetry) {
                        continue;
                    }
                }
                $url = rtrim($this->urlGenerator->generate('index', [], UrlGeneratorInterface::ABSOLUTE_URL), '/')
                    .'/certificates/'.rawurlencode($hash).'.html';
                $email = (new Email())
                    ->from($this->mailHelper->getPlatformFromAddress())
                    ->to(new Address((string) $student->getEmail(), $student->getFullName()))
                    ->subject('Your course certificate')
                    ->text('Your certificate for '.$context->course->getTitle().' is available at: '.$url)
                ;

                try {
                    $this->mailer->send($email);
                    $this->connection->executeStatement(
                        'UPDATE course_automation_delivery SET status = :sent, sent_at = NOW() WHERE action_id = :actionId AND user_id = :userId',
                        ['sent' => 'sent', 'actionId' => $actionId, 'userId' => $userId],
                    );
                } catch (Throwable $exception) {
                    $this->connection->executeStatement(
                        'UPDATE course_automation_delivery SET status = :failed, last_error = :error WHERE action_id = :actionId AND user_id = :userId',
                        ['failed' => 'failed', 'error' => 'Mail delivery failed.', 'actionId' => $actionId, 'userId' => $userId],
                    );

                    throw $exception;
                }
            }
            if (0 === $eligible) {
                throw new BadRequestHttpException('No generated certificates with valid recipient addresses were found.');
            }
            $unconfirmed = (int) $this->connection->fetchOne(
                'SELECT COUNT(*) FROM course_automation_delivery WHERE action_id = :actionId AND status <> :sent',
                ['actionId' => (int) $action->getId(), 'sent' => 'sent'],
            );
            if ($unconfirmed > 0) {
                throw new ConflictHttpException('Some certificate deliveries need manual review before this action can complete.');
            }

            return;
        }

        throw new BadRequestHttpException('Unsupported automation action.');
    }

    private function canManage(CourseReportingContext $context): bool
    {
        return 0 === $context->groupId && ($context->isAdministrator || $context->isTeacher);
    }

    private function requireManage(): CourseReportingContext
    {
        $context = $this->contextResolver->resolve();
        if (!$this->canManage($context)) {
            throw new AccessDeniedHttpException('You are not allowed to manage course automation.');
        }

        return $context;
    }

    private function canChangeVisibility(CourseReportingContext $context): bool
    {
        if (null !== $context->session) {
            // A session course has no independent course visibility field.
            return false;
        }

        return $this->courseSettingsManager->canChangeVisibilityForAutomation($context->course);
    }

    private function assertEvent(string $event): string
    {
        if (!\in_array($event, [self::EVENT_START, self::EVENT_END], true)) {
            throw new BadRequestHttpException('Only start and end events are supported.');
        }

        return $event;
    }

    private function actions(CourseReportingContext $context): array
    {
        return $this->entityManager->getRepository(CourseAutomationAction::class)->findBy(
            ['course' => $context->course, 'session' => $context->session],
            ['id' => 'ASC'],
        );
    }

    private function belongsToContext(CourseAutomationAction $action, CourseReportingContext $context): bool
    {
        return $action->getCourse()->getId() === $context->course->getId()
            && $action->getSession()?->getId() === $context->session?->getId();
    }

    private function resourceBelongsToContext(ResourceLink $link, CourseReportingContext $context): bool
    {
        return $link->getCourse()?->getId() === $context->course->getId()
            && $link->getSession()?->getId() === $context->session?->getId()
            && null === $link->getGroup() && null === $link->getUser() && null === $link->getUserGroup()
            && $link->getResourceNode()->getId() !== $context->courseResourceNodeId()
            && $this->security->isGranted('EDIT', $link->getResourceNode());
    }

    /**
     * @return list<ResourceLink>
     */
    private function resourceLinks(CourseReportingContext $context): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('link', 'node')
            ->from(ResourceLink::class, 'link')
            ->innerJoin('link.resourceNode', 'node')
            ->andWhere('link.course = :course')
            ->andWhere('link.group IS NULL AND link.user IS NULL AND link.userGroup IS NULL')
            ->andWhere('node.id != :courseNode')
            ->setParameter('course', $context->course)
            ->setParameter('courseNode', $context->courseResourceNodeId())
            ->orderBy('node.title', 'ASC')
            ->setMaxResults(1000)
        ;
        if (null === $context->session) {
            $qb->andWhere('link.session IS NULL');
        } else {
            $qb->andWhere('link.session = :session')->setParameter('session', $context->session);
        }

        return array_values(array_filter(
            $qb->getQuery()->getResult(),
            fn (ResourceLink $link): bool => $this->resourceBelongsToContext($link, $context),
        ));
    }

    /**
     * @return list<CSurvey>
     */
    private function surveys(CourseReportingContext $context): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT survey')
            ->from(CSurvey::class, 'survey')
            ->innerJoin('survey.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'link')
            ->andWhere('link.course = :course')
            ->setParameter('course', $context->course)
            ->setMaxResults(200)
        ;
        if (null === $context->session) {
            $qb->andWhere('link.session IS NULL');
        }
        $result = [];
        foreach ($qb->getQuery()->getResult() as $survey) {
            if ($this->surveyInvitationProvider->isSurveyInContext($survey, $context->course, $context->session)) {
                $result[] = $survey;
            }
        }

        return $result;
    }

    /**
     * @return list<GradebookCategory>
     */
    private function gradebooks(CourseReportingContext $context): array
    {
        return array_values(array_filter(
            $this->entityManager->getRepository(GradebookCategory::class)->findBy([
                'course' => $context->course,
                'session' => $context->session,
            ], ['id' => 'ASC'], 200),
            static fn (GradebookCategory $category): bool => (bool) $category->getGenerateCertificates(),
        ));
    }

    private function findGradebook(int $id, CourseReportingContext $context): GradebookCategory
    {
        foreach ($this->gradebooks($context) as $category) {
            if ((int) $category->getId() === $id) {
                return $category;
            }
        }

        throw new AccessDeniedHttpException('The Gradebook category is not available in this course context.');
    }

    /**
     * @return list<User>
     */
    private function students(CourseReportingContext $context): array
    {
        $isSessionCourse = null !== $context->session;
        $qb = $this->entityManager->createQueryBuilder()
            ->select('relation', 'user')
            ->from($isSessionCourse ? SessionRelCourseRelUser::class : CourseRelUser::class, 'relation')
            ->innerJoin('relation.user', 'user')
            ->andWhere('relation.course = :course')
            ->andWhere('relation.status = :student')
            ->andWhere('user.active = :active')
            ->setParameter('course', $context->course)
            ->setParameter('student', $isSessionCourse ? Session::STUDENT : CourseRelUser::STUDENT)
            ->setParameter('active', User::ACTIVE)
        ;

        if ($isSessionCourse) {
            $qb->andWhere('relation.session = :session')->setParameter('session', $context->session);
        }

        // Doctrine requires the root subscription alias to be selected when
        // fetching its user association. Return the users expected by callers.
        $students = [];
        foreach ($qb->getQuery()->getResult() as $relation) {
            $user = $relation->getUser();
            if (null !== $user->getId()) {
                $students[(int) $user->getId()] = $user;
            }
        }

        return array_values($students);
    }

    /**
     * @return list<int>
     */
    private function studentIds(CourseReportingContext $context): array
    {
        return array_map(static fn (User $user): int => (int) $user->getId(), $this->students($context));
    }
}
