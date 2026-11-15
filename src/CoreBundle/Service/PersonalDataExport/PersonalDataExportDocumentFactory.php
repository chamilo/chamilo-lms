<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Service\PersonalDataExport;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\GradebookCertificate;
use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelUser;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Entity\TrackELogin;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Serializer\UserToJsonNormalizer;
use Chamilo\CourseBundle\Entity\CAttendanceSheet;
use Chamilo\CourseBundle\Entity\CForumPost;
use Chamilo\CourseBundle\Entity\CLpView;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Entity\CSurveyAnswer;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * Builds the versioned personal-data export.
 *
 * The envelope (format, version, generated_at) is Chamilo-specific. The
 * payload uses public vocabularies so another system or model can recover
 * the semantics: schema.org JSON-LD (or the same tree as XML) for the
 * person and for messages, and xAPI (Experience API) statements for
 * learning activity.
 */
final class PersonalDataExportDocumentFactory
{
    public const string FORMAT = 'chamilo-personal-data';
    public const string VERSION = '1.0';
    public const string SCHEMA_CONTEXT = 'https://schema.org';
    public const string XAPI_VERB_BASE = 'http://adlnet.gov/expapi/verbs/';
    public const string XAPI_ACTIVITY_BASE = 'http://adlnet.gov/expapi/activities/';
    public const string EXTENSION_SESSION_ID = 'https://chamilo.org/xapi/extensions/session-id';
    public const string EXTENSION_COURSE_CODE = 'https://chamilo.org/xapi/extensions/course-code';

    /**
     * Extra-field variables that belong in the consents section, not on the Person.
     *
     * @var list<string>
     */
    private const array CONSENT_FIELD_VARIABLES = [
        'legal_accept',
        'request_for_delete_account',
        'request_for_delete_account_justification',
        'request_for_legal_agreement_consent_removal',
        'request_for_legal_agreement_consent_removal_justification',
    ];

    public function __construct(
        private readonly AccessUrlHelper $accessUrlHelper,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserToJsonNormalizer $userToJsonNormalizer,
        private readonly PersonalDataExportXmlWriter $xmlWriter,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function buildExportDocument(User $user, ?DateTimeInterface $generatedAt = null): array
    {
        $generatedAt ??= new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $generatedAt = DateTimeImmutable::createFromInterface($generatedAt)->setTimezone(new DateTimeZone('UTC'));
        $baseUrl = $this->baseUrl();
        $collected = $this->userToJsonNormalizer->collectPersonalData((int) $user->getId());

        return [
            'format' => self::FORMAT,
            'version' => self::VERSION,
            'generated_at' => $generatedAt->format(DateTimeInterface::ATOM),
            'user' => $this->buildPerson($user, $baseUrl),
            'learning_activities' => $this->buildLearningActivities($user, $baseUrl),
            'messages' => $this->buildMessages($user, $baseUrl),
            'consents' => $this->buildConsents($user, $baseUrl),
            'logs' => $this->buildLogs($user, $collected, $baseUrl),
            'notebooks' => $this->asSchemaDocuments($collected['Notebook'] ?? [], 'DigitalDocument', 'Title', 'Content', 'CreationDate'),
            'wikis' => $this->asSchemaDocuments($collected['Wikis'] ?? [], 'Article', 'Title', null, null),
            'tickets' => $this->asSchemaDocuments($collected['Tickets'] ?? [], 'Question', 'Subject', 'Code', null),
            'ticket_messages' => $this->asSchemaDocuments($collected['TicketMessages'] ?? [], 'Message', 'Subject', null, 'CreationDate'),
            'groups' => $collected['GroupRelations'] ?? [],
            'course_categories' => $collected['CourseCategories'] ?? [],
            'course_votes' => $this->asRatings($collected['CourseVotes'] ?? []),
            'assignment_comments' => $this->asSchemaDocuments(
                $collected['StudentPublicationComments'] ?? [],
                'Comment',
                'Comment',
                'File',
                'Date'
            ),
        ];
    }

    public function buildExportJson(User $user, ?DateTimeInterface $generatedAt = null): string
    {
        $json = json_encode(
            $this->buildExportDocument($user, $generatedAt),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );
        if (false === $json) {
            throw new RuntimeException('The personal data export could not be encoded as JSON.');
        }

        return $json."\n";
    }

    public function buildExportXml(User $user, ?DateTimeInterface $generatedAt = null): string
    {
        return $this->xmlWriter->write($this->buildExportDocument($user, $generatedAt));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPerson(User $user, string $baseUrl): array
    {
        $person = [
            '@context' => self::SCHEMA_CONTEXT,
            '@type' => 'Person',
            '@id' => $this->iri($baseUrl, 'user/'.(int) $user->getId()),
            'identifier' => [
                $this->propertyValue('chamiloUserId', (string) $user->getId()),
                $this->propertyValue('username', (string) $user->getUsername()),
            ],
            'alternateName' => (string) $user->getUsername(),
            'givenName' => (string) $user->getFirstname(),
            'familyName' => (string) $user->getLastname(),
            'name' => $user->getFullName(),
        ];

        $email = trim((string) $user->getEmail());
        if ('' !== $email) {
            $person['email'] = $email;
        }

        $phone = trim((string) $user->getPhone());
        if ('' !== $phone) {
            $person['telephone'] = $phone;
        }

        $address = trim((string) $user->getAddress());
        if ('' !== $address) {
            $person['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => $address,
            ];
        }

        $locale = $this->normalizeLocale((string) $user->getLocale());
        if ('' !== $locale) {
            $person['knowsLanguage'] = $locale;
        }

        $website = trim((string) $user->getWebsite());
        if ('' !== $website) {
            $person['url'] = $website;
        }

        $biography = trim((string) $user->getBiography());
        if ('' !== $biography) {
            $person['description'] = $biography;
        }

        $gender = trim((string) $user->getGender());
        if ('' !== $gender) {
            $person['gender'] = $gender;
        }

        $additional = [];
        $timezone = trim((string) $user->getTimezone());
        if ('' !== $timezone) {
            $additional[] = $this->propertyValue('timezone', $timezone);
        }
        $roles = $user->getRoles();
        if ([] !== $roles) {
            $additional[] = $this->propertyValue('roles', implode(', ', $roles));
        }
        $lastLogin = $user->getLastLogin();
        if ($lastLogin instanceof DateTimeInterface) {
            $additional[] = $this->propertyValue('lastLogin', $this->atom($lastLogin));
        }

        foreach ($this->userExtraFieldValues($user) as $variable => $value) {
            if (\in_array($variable, self::CONSENT_FIELD_VARIABLES, true)) {
                continue;
            }
            if ($this->looksLikeSecretField($variable)) {
                continue;
            }
            $additional[] = $this->propertyValue($variable, $value);
        }

        if ([] !== $additional) {
            $person['additionalProperty'] = $additional;
        }

        return $person;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildLearningActivities(User $user, string $baseUrl): array
    {
        $statements = [];
        $actor = $this->actor($user, $baseUrl);
        $userId = (int) $user->getId();

        $courseSubscriptions = $this->entityManager->getRepository(CourseRelUser::class)->findBy(['user' => $user]);
        foreach ($courseSubscriptions as $subscription) {
            $course = $subscription->getCourse();
            if (!$course instanceof Course) {
                continue;
            }
            $statements[] = $this->statement(
                $baseUrl,
                'course-registration/'.(int) $subscription->getId(),
                $actor,
                'registered',
                $this->courseActivity($course, $baseUrl),
                $subscription->getSubscriptionDate(),
                null,
                $this->context($course, null, $baseUrl)
            );
        }

        foreach ($user->getSessionsRelUser() as $sessionRelUser) {
            if (!$sessionRelUser instanceof SessionRelUser) {
                continue;
            }
            $session = $sessionRelUser->getSession();
            if (!$session instanceof Session) {
                continue;
            }
            $statements[] = $this->statement(
                $baseUrl,
                'session-registration/'.(int) $sessionRelUser->getId(),
                $actor,
                'registered',
                $this->sessionActivity($session, $baseUrl),
                $sessionRelUser->getRegisteredAt(),
                null,
                $this->context(null, $session, $baseUrl)
            );
        }

        $exercises = $this->entityManager->getRepository(TrackEExercise::class)->findBy(['user' => $userId]);
        foreach ($exercises as $exercise) {
            $course = $exercise->getCourse();
            $quiz = $exercise->getQuiz();
            $completed = 'incomplete' !== strtolower($exercise->getStatus());
            $max = (float) $exercise->getMaxScore();
            $raw = (float) $exercise->getScore();
            $result = [
                'completion' => $completed,
                'duration' => $this->iso8601Duration((int) $exercise->getExeDuration()),
            ];
            if ($max > 0) {
                $result['score'] = [
                    'raw' => $raw,
                    'min' => 0,
                    'max' => $max,
                    'scaled' => round($raw / $max, 4),
                ];
                if ($completed) {
                    $result['success'] = $raw >= $max;
                }
            }
            $object = $this->activity(
                $this->iri($baseUrl, $this->coursePath($course).'exercise/'.($quiz?->getIid() ?? $exercise->getExeId())),
                $quiz?->getTitle() ?? 'Exercise',
                self::XAPI_ACTIVITY_BASE.'assessment'
            );
            $statements[] = $this->statement(
                $baseUrl,
                'exercise-attempt/'.$exercise->getExeId(),
                $actor,
                $completed ? 'completed' : 'attempted',
                $object,
                $exercise->getExeDate(),
                $result,
                $this->context($course, $exercise->getSession(), $baseUrl)
            );
        }

        $lpViews = $this->entityManager->getRepository(CLpView::class)->findBy(['user' => $userId]);
        foreach ($lpViews as $view) {
            $lp = $view->getLp();
            $course = $view->getCourse();
            $progress = (int) $view->getProgress();
            $completed = $progress >= 100 || $view->getCompletionDate() instanceof DateTimeInterface;
            $object = $this->activity(
                $this->iri($baseUrl, $this->coursePath($course).'lp/'.($lp->getIid() ?? $view->getIid())),
                $lp->getTitle(),
                self::XAPI_ACTIVITY_BASE.'module'
            );
            $timestamp = $view->getCompletionDate() ?? null;
            $statements[] = $this->statement(
                $baseUrl,
                'lp-view/'.(int) $view->getIid(),
                $actor,
                $completed ? 'completed' : 'attempted',
                $object,
                $timestamp,
                [
                    'completion' => $completed,
                    'score' => [
                        'raw' => $progress,
                        'min' => 0,
                        'max' => 100,
                        'scaled' => round($progress / 100, 4),
                    ],
                ],
                $this->context($course, $view->getSession(), $baseUrl)
            );
        }

        $assignments = $this->entityManager->getRepository(CStudentPublication::class)->findBy(['user' => $userId]);
        foreach ($assignments as $assignment) {
            $link = $assignment->getFirstResourceLink();
            $course = $link?->getCourse();
            $session = $link?->getSession();
            $weight = (float) $assignment->getWeight();
            $qualification = (float) $assignment->getQualification();
            $result = ['completion' => true];
            if ($weight > 0) {
                $result['score'] = [
                    'raw' => $qualification,
                    'min' => 0,
                    'max' => $weight,
                    'scaled' => round($qualification / $weight, 4),
                ];
            }
            $object = $this->activity(
                $this->iri($baseUrl, $this->coursePath($course).'assignment/'.(int) $assignment->getIid()),
                $assignment->getTitle(),
                'https://w3id.org/xapi/acrossx/activities/assignment'
            );
            $statements[] = $this->statement(
                $baseUrl,
                'assignment/'.(int) $assignment->getIid(),
                $actor,
                'completed',
                $object,
                $assignment->getSentDate(),
                $result,
                $this->context($course, $session, $baseUrl)
            );
        }

        $posts = $this->entityManager->getRepository(CForumPost::class)->findBy(['user' => $userId]);
        foreach ($posts as $post) {
            $link = $post->getFirstResourceLink() ?? $post->getForum()?->getFirstResourceLink();
            $course = $link?->getCourse();
            $session = $link?->getSession();
            $thread = $post->getThread();
            $object = $this->activity(
                $this->iri($baseUrl, $this->coursePath($course).'forum/post/'.(int) $post->getIid()),
                $post->getTitle() ?: ($thread?->getTitle() ?? 'Forum post'),
                'http://id.tincanapi.com/activitytype/forum-topic'
            );
            $statements[] = $this->statement(
                $baseUrl,
                'forum-post/'.(int) $post->getIid(),
                $actor,
                'commented',
                $object,
                $post->getPostDate(),
                null,
                $this->context($course, $session, $baseUrl)
            );
        }

        $certificates = $this->entityManager->getRepository(GradebookCertificate::class)->findBy(['user' => $userId]);
        foreach ($certificates as $certificate) {
            $category = $certificate->getCategory();
            $course = $category?->getCourse();
            $object = $this->activity(
                $this->iri($baseUrl, 'certificate/'.(int) $certificate->getId()),
                $category?->getTitle() ?? 'Certificate',
                self::XAPI_ACTIVITY_BASE.'course'
            );
            $result = [
                'completion' => true,
                'success' => true,
                'score' => [
                    'raw' => (float) $certificate->getScoreCertificate(),
                    'scaled' => round(((float) $certificate->getScoreCertificate()) / 100, 4),
                    'min' => 0,
                    'max' => 100,
                ],
            ];
            $statements[] = $this->statement(
                $baseUrl,
                'certificate/'.(int) $certificate->getId(),
                $actor,
                'passed',
                $object,
                $certificate->getCreatedAt(),
                $result,
                $this->context($course, $category?->getSession(), $baseUrl)
            );
        }

        $surveyAnswers = $this->entityManager->getRepository(CSurveyAnswer::class)->findBy(['user' => $userId]);
        foreach ($surveyAnswers as $answer) {
            $statements[] = $this->statement(
                $baseUrl,
                'survey-answer/'.(int) $answer->getIid(),
                $actor,
                'answered',
                $this->activity(
                    $this->iri($baseUrl, 'survey-answer/'.(int) $answer->getIid()),
                    'Survey answer',
                    self::XAPI_ACTIVITY_BASE.'cmi.interaction'
                ),
                null,
                null,
                null
            );
        }

        $sheets = $this->entityManager->getRepository(CAttendanceSheet::class)->findBy(['user' => $userId]);
        foreach ($sheets as $sheet) {
            $calendar = $sheet->getAttendanceCalendar();
            $attendance = $calendar->getAttendance();
            $link = $attendance->getFirstResourceLink();
            $present = (bool) $sheet->getPresence();
            $statements[] = $this->statement(
                $baseUrl,
                'attendance-sheet/'.(int) ($sheet->getIid() ?? 0).'-'.(int) $calendar->getIid(),
                $actor,
                'attended',
                $this->activity(
                    $this->iri($baseUrl, $this->coursePath($link?->getCourse()).'attendance/'.(int) $attendance->getIid()),
                    $attendance->getTitle() ?: 'Attendance',
                    self::XAPI_ACTIVITY_BASE.'meeting'
                ),
                $calendar->getDateTime(),
                ['completion' => $present, 'success' => $present],
                $this->context($link?->getCourse(), $link?->getSession(), $baseUrl)
            );
        }

        return $statements;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildMessages(User $user, string $baseUrl): array
    {
        $items = [];
        $messages = $this->entityManager->getRepository(Message::class)->findBy(['sender' => $user]);
        foreach ($messages as $message) {
            $recipients = [];
            foreach ($message->getReceivers() as $receiver) {
                $to = $receiver->getReceiver();
                if (!$to instanceof User) {
                    continue;
                }
                $recipients[] = [
                    '@type' => 'Person',
                    'alternateName' => (string) $to->getUsername(),
                    'name' => $to->getFullName(),
                ];
            }
            $item = [
                '@context' => self::SCHEMA_CONTEXT,
                '@type' => 'Message',
                '@id' => $this->iri($baseUrl, 'message/'.(int) $message->getId()),
                'name' => $message->getTitle(),
                'dateSent' => $this->atom($message->getSendDate()),
                'sender' => [
                    '@type' => 'Person',
                    'alternateName' => (string) $user->getUsername(),
                    'name' => $user->getFullName(),
                ],
            ];
            if ([] !== $recipients) {
                $item['recipient'] = $recipients;
            }
            $text = trim($message->getContent());
            if ('' !== $text) {
                $item['text'] = $text;
            }
            $items[] = $item;
        }

        return $items;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildConsents(User $user, string $baseUrl): array
    {
        $items = [];
        foreach ($this->userExtraFieldValues($user) as $variable => $value) {
            if (!\in_array($variable, self::CONSENT_FIELD_VARIABLES, true)) {
                continue;
            }

            $item = [
                '@context' => self::SCHEMA_CONTEXT,
                '@type' => 'legal_accept' === $variable ? 'AgreeAction' : 'Action',
                '@id' => $this->iri($baseUrl, 'consent/'.(int) $user->getId().'/'.$variable),
                'name' => $variable,
                'agent' => [
                    '@type' => 'Person',
                    '@id' => $this->iri($baseUrl, 'user/'.(int) $user->getId()),
                    'alternateName' => (string) $user->getUsername(),
                ],
            ];

            if ('legal_accept' === $variable) {
                $parts = explode(':', $value);
                if (isset($parts[2]) && ctype_digit($parts[2])) {
                    $item['startTime'] = $this->atom((new DateTimeImmutable('@'.$parts[2]))->setTimezone(new DateTimeZone('UTC')));
                }
                $item['additionalProperty'] = [
                    $this->propertyValue('version', $parts[0] ?? ''),
                    $this->propertyValue('languageId', $parts[1] ?? ''),
                ];
            } else {
                $item['additionalProperty'] = [$this->propertyValue($variable, $value)];
            }

            $items[] = $item;
        }

        return $items;
    }

    /**
     * @param array<string, mixed> $collected
     *
     * @return list<array<string, mixed>>
     */
    private function buildLogs(User $user, array $collected, string $baseUrl): array
    {
        $items = [];
        $logins = $this->entityManager->getRepository(TrackELogin::class)->findBy(['user' => $user]);
        foreach ($logins as $login) {
            $entry = [
                '@type' => 'Action',
                'name' => 'login',
                'agent' => [
                    '@type' => 'Person',
                    'alternateName' => (string) $user->getUsername(),
                ],
                'startTime' => $this->atom($login->getLoginDate()),
            ];
            if ($login->getLogoutDate() instanceof DateTimeInterface) {
                $entry['endTime'] = $this->atom($login->getLogoutDate());
            }
            $items[] = $entry;
        }

        foreach ($collected['Downloads'] ?? [] as $download) {
            if (!\is_array($download)) {
                continue;
            }
            $items[] = [
                '@type' => 'DownloadAction',
                'name' => (string) ($download['File'] ?? 'download'),
                'startTime' => (string) ($download['DownloadedAt'] ?? ''),
            ];
        }

        return $items;
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return list<array<string, mixed>>
     */
    private function asSchemaDocuments(array $rows, string $type, string $nameKey, ?string $textKey, ?string $dateKey): array
    {
        $items = [];
        foreach ($rows as $row) {
            if (!\is_array($row)) {
                continue;
            }
            $item = [
                '@context' => self::SCHEMA_CONTEXT,
                '@type' => $type,
            ];
            if (isset($row[$nameKey]) && '' !== (string) $row[$nameKey]) {
                $item['name'] = $row[$nameKey];
            }
            if (null !== $textKey && isset($row[$textKey]) && '' !== (string) $row[$textKey]) {
                $item['text'] = $row[$textKey];
            }
            if (null !== $dateKey && isset($row[$dateKey]) && '' !== (string) $row[$dateKey]) {
                $item['dateCreated'] = $row[$dateKey];
            }
            foreach ($row as $key => $value) {
                if (\in_array($key, array_filter([$nameKey, $textKey, $dateKey]), true)) {
                    continue;
                }
                $encoded = \is_scalar($value) ? (string) $value : (json_encode($value) ?: '');
                $item['additionalProperty'][] = $this->propertyValue((string) $key, $encoded);
            }
            $items[] = $item;
        }

        return $items;
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return list<array<string, mixed>>
     */
    private function asRatings(array $rows): array
    {
        $items = [];
        foreach ($rows as $row) {
            if (!\is_array($row)) {
                continue;
            }
            $items[] = [
                '@context' => self::SCHEMA_CONTEXT,
                '@type' => 'Rating',
                'ratingValue' => $row['Vote'] ?? null,
                'additionalProperty' => [
                    $this->propertyValue('courseId', (string) ($row['CourseId'] ?? '')),
                ],
            ];
        }

        return $items;
    }

    /**
     * @param array<string, mixed>      $actor
     * @param array<string, mixed>      $object
     * @param array<string, mixed>|null $result
     * @param array<string, mixed>|null $context
     *
     * @return array<string, mixed>
     */
    private function statement(
        string $baseUrl,
        string $idSuffix,
        array $actor,
        string $verb,
        array $object,
        ?DateTimeInterface $timestamp,
        ?array $result,
        ?array $context,
    ): array {
        $statement = [
            'id' => $this->iri($baseUrl, 'xapi/statements/'.$idSuffix),
            'actor' => $actor,
            'verb' => $this->verb($verb),
            'object' => $object,
        ];
        if (null !== $result) {
            $statement['result'] = $result;
        }
        if (null !== $context) {
            $statement['context'] = $context;
        }
        if ($timestamp instanceof DateTimeInterface) {
            $statement['timestamp'] = $this->atom($timestamp);
        }

        return $statement;
    }

    /**
     * @return array{id: string, display: array<string, string>}
     */
    private function verb(string $name): array
    {
        return [
            'id' => self::XAPI_VERB_BASE.$name,
            'display' => [
                'en-US' => $name,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function actor(User $user, string $baseUrl): array
    {
        return [
            'objectType' => 'Agent',
            'name' => $user->getFullName(),
            'account' => [
                'homePage' => $baseUrl,
                'name' => (string) $user->getUsername(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function activity(string $id, string $name, string $type, string $language = 'en-US'): array
    {
        return [
            'objectType' => 'Activity',
            'id' => $id,
            'definition' => [
                'name' => [$language => $name],
                'type' => $type,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function courseActivity(Course $course, string $baseUrl): array
    {
        return $this->activity(
            $this->iri($baseUrl, 'course/'.$course->getCode()),
            $course->getTitle() ?: $course->getCode(),
            self::XAPI_ACTIVITY_BASE.'course'
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function sessionActivity(Session $session, string $baseUrl): array
    {
        return $this->activity(
            $this->iri($baseUrl, 'session/'.(int) $session->getId()),
            $session->getTitle(),
            'https://chamilo.org/xapi/activities/session'
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function context(?Course $course, ?Session $session, string $baseUrl): ?array
    {
        $context = [];
        if ($course instanceof Course) {
            $context['contextActivities'] = [
                'parent' => [$this->courseActivity($course, $baseUrl)],
            ];
            $context['extensions'][self::EXTENSION_COURSE_CODE] = $course->getCode();
        }
        if ($session instanceof Session) {
            $context['extensions'][self::EXTENSION_SESSION_ID] = (int) $session->getId();
        }

        return [] === $context ? null : $context;
    }

    /**
     * @return array<string, string>
     */
    private function propertyValue(string $name, string $value): array
    {
        return [
            '@type' => 'PropertyValue',
            'name' => $name,
            'propertyID' => $name,
            'value' => $value,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function userExtraFieldValues(User $user): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select('v', 'f')
            ->from(ExtraFieldValues::class, 'v')
            ->innerJoin('v.field', 'f')
            ->andWhere('v.itemId = :id')
            ->andWhere('f.itemType = :type')
            ->setParameter('id', $user->getId())
            ->setParameter('type', ExtraField::USER_FIELD_TYPE)
            ->getQuery()
            ->getResult()
        ;

        $values = [];
        foreach ($rows as $row) {
            if (!$row instanceof ExtraFieldValues) {
                continue;
            }
            $variable = $row->getField()->getVariable();
            if ('' === $variable) {
                continue;
            }
            $values[$variable] = (string) $row->getFieldValue();
        }

        return $values;
    }

    private function looksLikeSecretField(string $variable): bool
    {
        return (bool) preg_match('/(password|passwd|secret|token|api_?key|salt|hash|credential)/i', $variable);
    }

    private function baseUrl(): string
    {
        $url = rtrim($this->accessUrlHelper->getCurrentBaseUrl(), '/');

        return '' === $url ? 'http://localhost' : $url;
    }

    private function iri(string $baseUrl, string $path): string
    {
        return rtrim($baseUrl, '/').'/'.ltrim($path, '/');
    }

    private function coursePath(?Course $course): string
    {
        if (!$course instanceof Course) {
            return '';
        }

        return 'course/'.$course->getCode().'/';
    }

    private function atom(DateTimeInterface $date): string
    {
        return DateTimeImmutable::createFromInterface($date)
            ->setTimezone(new DateTimeZone('UTC'))
            ->format(DateTimeInterface::ATOM)
        ;
    }

    private function iso8601Duration(int $seconds): string
    {
        if ($seconds < 0) {
            $seconds = 0;
        }
        $interval = new DateInterval('PT0S');
        $interval->h = intdiv($seconds, 3600);
        $interval->i = intdiv($seconds % 3600, 60);
        $interval->s = $seconds % 60;

        $out = 'PT';
        if ($interval->h > 0) {
            $out .= $interval->h.'H';
        }
        if ($interval->i > 0) {
            $out .= $interval->i.'M';
        }
        if ($interval->s > 0 || 'PT' === $out) {
            $out .= $interval->s.'S';
        }

        return $out;
    }

    private function normalizeLocale(string $locale): string
    {
        $locale = trim(str_replace('_', '-', $locale));
        if ('' === $locale || 'en' === strtolower($locale)) {
            return 'en-US' === $locale ? 'en-US' : $locale;
        }

        return $locale;
    }
}
