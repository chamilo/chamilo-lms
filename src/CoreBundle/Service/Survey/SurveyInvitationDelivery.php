<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Survey;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\MailHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Entity\CSurveyAutoInvitation;
use Chamilo\CourseBundle\Entity\CSurveyExternalInvitation;
use Chamilo\CourseBundle\Entity\CSurveyInvitation;
use Chamilo\CourseBundle\Entity\CSurveyInvitationHistory;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Throwable;

use const FILTER_VALIDATE_EMAIL;
use const PHP_URL_HOST;

final readonly class SurveyInvitationDelivery
{
    public const string EXTERNAL_CODE_PREFIX = 'external-';
    private const int MAX_EXTERNAL_PER_REQUEST = 50;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private AccessUrlHelper $accessUrlHelper,
        private SettingsManager $settingsManager,
        private MailHelper $mailHelper,
        private MailerInterface $mailer,
        private Security $security,
        private RateLimiterFactory $surveyExternalInvitationsLimiter,
    ) {}

    public function getPortal(): ?AccessUrl
    {
        return $this->accessUrlHelper->getCurrent();
    }

    public function supportsAutomatic(CSurvey $survey, Course $course, ?Session $session, ?AccessUrl $portal): bool
    {
        return 0 === $survey->getSurveyType() && null !== $portal
            && $this->isPublishedInContext($survey, $course, $session, $portal);
    }

    public function supportsExternal(CSurvey $survey, Course $course, ?Session $session, ?AccessUrl $portal): bool
    {
        return '1' === $survey->getAnonymous() && $this->supportsAutomatic($survey, $course, $session, $portal);
    }

    public function isPublishedInContext(CSurvey $survey, Course $course, ?Session $session, AccessUrl $portal): bool
    {
        if (1 !== $portal->getActive() || !$this->belongsToPortal($course->getUrls(), $portal)
            || (null !== $session && !$this->belongsToPortal($session->getUrls(), $portal))) {
            return false;
        }
        if (\in_array($course->getVisibility(), [Course::CLOSED, Course::HIDDEN], true)) {
            return false;
        }
        if (null !== $session) {
            $containsCourse = false;
            foreach ($session->getCourses() as $relation) {
                if ($relation->getCourse()->getId() === $course->getId()) {
                    $containsCourse = true;

                    break;
                }
            }
            if (!$containsCourse) {
                return false;
            }
        }
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        foreach ($survey->getResourceNode()?->getResourceLinks() ?? [] as $link) {
            if ($link->getCourse()?->getId() === $course->getId()
                && $link->getSession()?->getId() === $session?->getId()
                && null === $link->getDeletedAt() && $link->isPublished()
                && null === $link->getGroup() && null === $link->getUser() && null === $link->getUserGroup()
                && (null === $link->getStartVisibilityAt() || $link->getStartVisibilityAt() <= $now)
                && (null === $link->getEndVisibilityAt() || $link->getEndVisibilityAt() >= $now)) {
                return true;
            }
        }

        return false;
    }

    public function isOpen(CSurvey $survey, ?Session $session): bool
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        return (null === $survey->getAvailFrom() || $survey->getAvailFrom() <= $now)
            && (null === $survey->getAvailTill() || $survey->getAvailTill() >= $now)
            && (null === $session || (
                (null === $session->getAccessStartDate() || $session->getAccessStartDate() <= $now)
                && (null === $session->getAccessEndDate() || $session->getAccessEndDate() >= $now)
            ));
    }

    private function belongsToPortal(iterable $relations, AccessUrl $portal): bool
    {
        foreach ($relations as $relation) {
            if ($relation->getUrl()?->getId() === $portal->getId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Serialize publication, scheduled processing and external answers for the same survey context.
     */
    public function withContextLock(CSurvey $survey, Course $course, ?Session $session, callable $callback): mixed
    {
        $connection = $this->entityManager->getConnection();
        $key = 'survey_'.substr(hash('sha256', ($connection->getParams()['dbname'] ?? '').':'.$survey->getIid().':'.$course->getId().':'.($session?->getId() ?? 0)), 0, 50);
        if (1 !== (int) $connection->fetchOne('SELECT GET_LOCK(?, 5)', [$key])) {
            throw new ConflictHttpException('Survey invitations are being processed. Please try again.');
        }

        try {
            return $callback();
        } finally {
            $connection->fetchOne('SELECT RELEASE_LOCK(?)', [$key]);
        }
    }

    /**
     * @return list<string>
     */
    public static function normalizeExternalEmails(array $values): array
    {
        if (\count($values) > self::MAX_EXTERNAL_PER_REQUEST) {
            throw new BadRequestHttpException('A maximum of 50 external email addresses is allowed per publication.');
        }
        $emails = [];
        foreach ($values as $value) {
            if (!\is_string($value)) {
                throw new BadRequestHttpException('Invalid external email address.');
            }
            $email = strtolower(trim($value));
            if ('' === $email) {
                continue;
            }
            if (\strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new BadRequestHttpException('Invalid external email address.');
            }
            $emails[$email] = $email;
        }

        return array_values($emails);
    }

    public function validateExternal(array $emails, CSurvey $survey, Course $course, ?Session $session, ?AccessUrl $portal, bool $sendMail, bool $hideLink): void
    {
        if ([] === $emails) {
            return;
        }
        if (!$this->supportsExternal($survey, $course, $session, $portal)) {
            throw new BadRequestHttpException('External email invitations require a published anonymous standard survey.');
        }
        if (!$sendMail || $hideLink) {
            throw new BadRequestHttpException('External email invitations require sending mail with the survey link.');
        }
        if (!$this->isOpen($survey, $session)) {
            throw new BadRequestHttpException('External email invitations require an open survey.');
        }
        $actor = $this->security->getUser();
        if (!$actor instanceof User || !$this->surveyExternalInvitationsLimiter->create((string) $actor->getId())->consume(\count($emails))->isAccepted()) {
            throw new TooManyRequestsHttpException(null, 'External invitation limit reached. Please try again later.');
        }
    }

    /**
     * @return list<CSurveyExternalInvitation>
     */
    public function externalRecipients(CSurvey $survey, Course $course, ?Session $session, AccessUrl $portal): array
    {
        $builder = $this->entityManager->createQueryBuilder()
            ->select('external', 'invitation')
            ->from(CSurveyExternalInvitation::class, 'external')
            ->innerJoin('external.invitation', 'invitation')
            ->andWhere('invitation.survey = :survey AND invitation.course = :course AND invitation.lpItemId = 0')
            ->andWhere(null === $session ? 'invitation.session IS NULL' : 'invitation.session = :session')
            ->andWhere('external.portal = :portal')
            ->setParameter('survey', (int) $survey->getIid())->setParameter('course', (int) $course->getId())->setParameter('portal', (int) $portal->getId())
            ->orderBy('external.email', 'ASC')
        ;
        if (null !== $session) {
            $builder->setParameter('session', (int) $session->getId());
        }

        return $builder->getQuery()->getResult();
    }

    public function externalRecipient(CSurveyInvitation $invitation): ?CSurveyExternalInvitation
    {
        return $this->entityManager->getRepository(CSurveyExternalInvitation::class)->findOneBy(['invitation' => $invitation]);
    }

    public function createInvitation(CSurvey $survey, Course $course, ?Session $session, ?User $user, bool $external = false): CSurveyInvitation
    {
        $invitation = (new CSurveyInvitation())
            ->setSurvey($survey)->setCourse($course)->setSession($session)->setUser($user)
            ->setInvitationCode(($external ? self::EXTERNAL_CODE_PREFIX : '').bin2hex(random_bytes(32)))
        ;
        $this->entityManager->persist($invitation);

        return $invitation;
    }

    /**
     * A separate message per recipient, never CC/BCC other recipients or copy personal links.
     */
    public function send(CSurveyInvitation $invitation, AccessUrl $portal, string $email, string $subject, string $body, ?MailerInterface $mailer = null): bool
    {
        try {
            $base = rtrim($portal->getUrl(), '/');
            if (!preg_match('~^https?://~i', $base) || !parse_url($base, PHP_URL_HOST)) {
                return false;
            }
            $survey = $invitation->getSurvey();
            $course = $invitation->getCourse();
            $session = $invitation->getSession();
            $query = str_starts_with($invitation->getInvitationCode(), self::EXTERNAL_CODE_PREFIX)
                ? ['publicCid' => $course->getId(), 'publicSid' => $session?->getId() ?? 0, 'publicGid' => 0]
                : ['cid' => $course->getId(), 'sid' => $session?->getId() ?? 0, 'gid' => 0];
            $query['invitationCode'] = $invitation->getInvitationCode();
            $link = $base.'/resources/survey/'.$survey->getResourceNode()->getId().'/'.$survey->getIid().'/answer?'.http_build_query($query);
            $text = html_entity_decode(strip_tags($body));
            $text = str_ireplace('**link**', $link, $text, $replacements);
            if (0 === $replacements) {
                $text .= "\n\n".$link;
            }
            $message = (new Email())->from($this->mailHelper->getPlatformFromAddress())
                ->to(new Address($email))->subject($subject)->text($text)
            ;
            ($mailer ?? $this->mailer)->send($message);

            return true;
        } catch (Throwable) {
            // Do not leak the address, invitation token or transport credentials in logs.
            return false;
        }
    }

    public function getPolicy(CSurvey $survey, Course $course, ?Session $session, ?AccessUrl $portal): ?CSurveyAutoInvitation
    {
        if (null === $portal) {
            return null;
        }

        return $this->entityManager->find(CSurveyAutoInvitation::class, CSurveyAutoInvitation::key($survey, $course, $session, $portal));
    }

    public function configurePolicy(CSurvey $survey, Course $course, ?Session $session, ?AccessUrl $portal, ?bool $enabled, bool $sendMail, bool $hideLink, bool $isReminder, string $subject, string $body): void
    {
        if (null === $enabled) {
            return;
        }
        if (null === $portal) {
            if ($enabled) {
                throw new BadRequestHttpException('A valid portal is required for automatic invitations.');
            }

            return;
        }
        $policy = $this->getPolicy($survey, $course, $session, $portal);
        if (!$enabled) {
            if (null !== $policy) {
                $this->entityManager->remove($policy);
            }

            return;
        }
        // Keep the original invitation template until the teacher disables and enables the policy again.
        if (null !== $policy) {
            return;
        }
        if (!$this->supportsAutomatic($survey, $course, $session, $portal) || !$sendMail || $hideLink || $isReminder || '' === $subject || '' === $body) {
            throw new BadRequestHttpException('Automatic invitations require a published standard survey and mail with a survey link.');
        }
        $builder = $this->subscriptionQuery($course, $session)->select('MAX(subscription.id)');
        $cutoff = (int) $builder->getQuery()->getSingleScalarResult();
        $this->entityManager->persist(new CSurveyAutoInvitation($survey, $course, $session, $portal, $cutoff, $subject, $body));
    }

    private function subscriptionQuery(Course $course, ?Session $session): QueryBuilder
    {
        $builder = $this->entityManager->createQueryBuilder()
            ->from(null === $session ? CourseRelUser::class : SessionRelCourseRelUser::class, 'subscription')
            ->andWhere('subscription.course = :course')->setParameter('course', (int) $course->getId())
        ;
        if (null !== $session) {
            $builder->andWhere('subscription.session = :session')->setParameter('session', (int) $session->getId());
        }

        return $builder;
    }

    /**
     * @return list<User>
     */
    public function pendingUsers(CSurveyAutoInvitation $policy, int $limit = 100): array
    {
        if (!$this->supportsAutomatic($policy->survey, $policy->course, $policy->session, $policy->portal)
            || !$this->isOpen($policy->survey, $policy->session)) {
            return [];
        }
        $existing = $this->entityManager->createQueryBuilder()->select('1')->from(CSurveyInvitation::class, 'invitation')
            ->andWhere('invitation.survey = :survey AND invitation.course = :course AND invitation.user = u AND invitation.lpItemId = 0')
            ->andWhere(null === $policy->session ? 'invitation.session IS NULL' : 'invitation.session = :session')
        ;
        $builder = $this->subscriptionQuery($policy->course, $policy->session)
            ->select('subscription', 'u')->innerJoin('subscription.user', 'u')
            ->andWhere('subscription.id > :cutoff AND subscription.status = :student AND subscription.visibility = 1 AND u.active = :active')
            ->andWhere('NOT EXISTS ('.$existing->getDQL().')')
            ->setParameter('survey', $policy->survey)->setParameter('cutoff', $policy->afterSubscriptionId)
            ->setParameter('student', null === $policy->session ? CourseRelUser::STUDENT : Session::STUDENT)
            ->setParameter('active', User::ACTIVE)->orderBy('subscription.id', 'ASC')
        ;
        $eligibleSessionUsers = [];
        if (null !== $policy->session) {
            $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
            foreach ($policy->session->getStudentSubscriptions() as $subscription) {
                if ((null === $subscription->getAccessStartDate() || $subscription->getAccessStartDate() <= $now)
                    && (null === $subscription->getAccessEndDate() || $subscription->getAccessEndDate() >= $now)) {
                    $eligibleSessionUsers[$subscription->getUser()->getId()] = true;
                }
            }
        }
        $users = [];
        foreach ($builder->getQuery()->toIterable() as $subscription) {
            $user = $subscription->getUser();
            if ((null === $policy->session || isset($eligibleSessionUsers[$user->getId()]))
                && $this->belongsToPortal($user->getPortals(), $policy->portal) && filter_var($user->getEmail(), FILTER_VALIDATE_EMAIL)) {
                $users[$user->getId()] = $user;
                if (\count($users) >= $limit) {
                    break;
                }
            }
        }

        return array_values($users);
    }

    /**
     * @return array{pending: int, sent: int, failed: int}
     */
    public function processPolicy(CSurveyAutoInvitation $policy, bool $send): array
    {
        return $this->withContextLock($policy->survey, $policy->course, $policy->session, function () use ($policy, $send): array {
            $current = $this->entityManager->getRepository(CSurveyAutoInvitation::class)->findOneBy(['contextKey' => CSurveyAutoInvitation::key($policy->survey, $policy->course, $policy->session, $policy->portal)]);
            if (null === $current) {
                return ['pending' => 0, 'sent' => 0, 'failed' => 0];
            }
            $this->entityManager->refresh($current);
            $users = $this->pendingUsers($current);
            $result = ['pending' => \count($users), 'sent' => 0, 'failed' => 0];
            if (!$send || [] === $users) {
                return $result;
            }
            // Resolve the actual portal transport, not the first portal's CLI defaults.
            $this->settingsManager->setUrl($current->portal);
            $dsn = trim((string) $this->settingsManager->getSetting('mail.mailer_dsn', true));
            if ('' === $dsn) {
                // Do not reserve invitations when there is no configured transport.
                return ['pending' => $result['pending'], 'sent' => 0, 'failed' => $result['pending']];
            }
            $mailer = new Mailer(Transport::fromDsn($dsn));
            $history = null;
            foreach ($users as $user) {
                // Reserve the invitation before dispatch. A crash cannot cause a blind automatic resend.
                $invitation = $this->createInvitation($current->survey, $current->course, $current->session, $user);
                $this->entityManager->flush();
                if (!$this->send($invitation, $current->portal, $user->getEmail(), $current->subject, $current->body, $mailer)) {
                    ++$result['failed'];

                    continue;
                }
                ++$result['sent'];
                $invitation->setReminderDate(new DateTime());
                if (null === $history) {
                    $history = new CSurveyInvitationHistory($current->survey, $current->course, $current->session, $current->subject, $current->body, false, false);
                    $this->entityManager->persist($history);
                }
                $history->addInvitation($invitation);
                $this->entityManager->flush();
            }
            $this->updateCounters($current->survey, $current->course, $current->session);
            $this->entityManager->flush();

            return $result;
        });
    }

    public function updateCounters(CSurvey $survey, Course $course, ?Session $session): void
    {
        $builder = $this->entityManager->createQueryBuilder()
            ->select('COUNT(invitation.iid) AS invitedCount, SUM(CASE WHEN invitation.answered = 1 THEN 1 ELSE 0 END) AS answeredCount')
            ->from(CSurveyInvitation::class, 'invitation')
            ->andWhere('invitation.survey = :survey AND invitation.course = :course')
            ->setParameter('survey', (int) $survey->getIid())->setParameter('course', (int) $course->getId())
        ;
        if (null === $session) {
            $builder->andWhere('invitation.session IS NULL');
        } else {
            $builder->andWhere('invitation.session = :session')->setParameter('session', (int) $session->getId());
        }
        $counts = $builder->getQuery()->getSingleResult();
        $survey->setInvited((int) $counts['invitedCount']);
        $survey->setAnswered((int) $counts['answeredCount']);
    }
}
