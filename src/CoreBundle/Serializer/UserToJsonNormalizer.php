<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Serializer;

use Chamilo\CoreBundle\Entity\GradebookCertificate;
use Chamilo\CoreBundle\Entity\GradebookResult;
use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\SkillRelUserComment;
use Chamilo\CoreBundle\Entity\Ticket;
use Chamilo\CoreBundle\Entity\TicketMessage;
use Chamilo\CoreBundle\Entity\TrackEAccess;
use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Chamilo\CoreBundle\Entity\TrackECourseAccess;
use Chamilo\CoreBundle\Entity\TrackEDefault;
use Chamilo\CoreBundle\Entity\TrackEDownloads;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Entity\TrackELastaccess;
use Chamilo\CoreBundle\Entity\TrackELogin;
use Chamilo\CoreBundle\Entity\TrackEOnline;
use Chamilo\CoreBundle\Entity\TrackEUploads;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserCourseCategory;
use Chamilo\CoreBundle\Entity\UserRelCourseVote;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CourseBundle\Entity\CAttendanceResult;
use Chamilo\CourseBundle\Entity\CAttendanceSheet;
use Chamilo\CourseBundle\Entity\CDropboxFeedback;
use Chamilo\CourseBundle\Entity\CDropboxFile;
use Chamilo\CourseBundle\Entity\CDropboxPerson;
use Chamilo\CourseBundle\Entity\CForumPost;
use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\CourseBundle\Entity\CGroupRelUser;
use Chamilo\CourseBundle\Entity\CLpView;
use Chamilo\CourseBundle\Entity\CNotebook;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Entity\CStudentPublicationComment;
use Chamilo\CourseBundle\Entity\CSurveyAnswer;
use Chamilo\CourseBundle\Entity\CWiki;
use DateTimeInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

final class UserToJsonNormalizer
{
    private EntityManager $em;
    private UserRepository $userRepository;
    private SerializerInterface $serializer;

    public function __construct(EntityManager $em, UserRepository $userRepository, SerializerInterface $serializer)
    {
        $this->em = $em;
        $this->userRepository = $userRepository;
        $this->serializer = $serializer;
    }

    /**
     * Serialize the whole entity to an array.
     *
     * @param array $substitutionTerms Substitute terms for some elements
     *
     * @throws NotSupported
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getPersonalDataToJson(int $userId, array $substitutionTerms): string
    {
        $em = $this->em;
        $dateFormat = DateTimeInterface::ATOM;

        /** @var User|null $user */
        $user = $this->userRepository->find($userId);
        if (!$user) {
            throw new \RuntimeException(sprintf('User %d not found', $userId));
        }

        $user->setPassword($substitutionTerms['password'] ?? '');
        $user->setSalt($substitutionTerms['salt'] ?? '');

        $noDataLabel = $substitutionTerms['empty'] ?? '';

        // Dummy content
        $user->setDateOfBirth(null);
        $user->setLocale($noDataLabel);
        $user->setTimezone($noDataLabel);
        $user->setWebsite($noDataLabel);

        // GradebookCertificate
        $result = $em->getRepository(GradebookCertificate::class)->findBy(['user' => $userId]);
        $gradebookCertificate = [];
        foreach ($result as $item) {
            $createdAt = $item->getCreatedAt()?->format($dateFormat) ?? '';
            $list = [
                'Score: '.$item->getScoreCertificate(),
                'Path: '.$item->getPathCertificate(),
                'Created at: '.$createdAt,
            ];
            $gradebookCertificate[] = implode(', ', $list);
        }

        // TrackEExercises
        $result = $em->getRepository(TrackEExercise::class)->findBy(['user' => $userId]);
        $trackEExercises = [];
        foreach ($result as $item) {
            $date = $item->getExeDate()?->format($dateFormat) ?? '';
            $list = [
                'IP: '.$item->getUserIp(),
                'Start: '.$date,
                'Status: '.$item->getStatus(),
            ];
            $trackEExercises[] = implode(', ', $list);
        }

        // TrackEAttempt
        $result = $em->getRepository(TrackEAttempt::class)->findBy(['user' => $userId]);
        $trackEAttempt = [];
        foreach ($result as $item) {
            $date = $item->getTms()?->format($dateFormat) ?? '';
            $list = [
                'Attempt #'.($item->getTrackEExercise()?->getExeId() ?? 'n/a'),
                'Position: '.$item->getPosition(),
                'Date: '.$date,
            ];
            $trackEAttempt[] = implode(', ', $list);
        }

        // TrackECourseAccess
        $result = $em->getRepository(TrackECourseAccess::class)->findBy(['user' => $userId]);
        $trackECourseAccessList = [];
        foreach ($result as $item) {
            $startDate = $item->getLoginCourseDate()?->format($dateFormat) ?? '';
            $endDate = $item->getLogoutCourseDate()?->format($dateFormat) ?? '';
            $list = [
                'IP: '.$item->getUserIp(),
                'Start: '.$startDate,
                'End: '.$endDate,
            ];
            $trackECourseAccessList[] = implode(', ', $list);
        }

        $checkEntities = [
            TrackELogin::class => 'user',
            TrackEAccess::class => 'accessUserId',
            TrackEOnline::class => 'loginUserId',
            TrackEDefault::class => 'defaultUserId',
            TrackELastaccess::class => 'accessUserId',
            TrackEUploads::class => 'uploadUserId',
            GradebookResult::class => 'user',
            TrackEDownloads::class => 'downUserId',
        ];

        $maxResults = 1000;
        $trackResults = [];
        foreach ($checkEntities as $entity => $field) {
            $qb = $em->createQueryBuilder();
            $qb->select($qb->expr()->count('l'))
                ->from($entity, 'l')
                ->where("l.$field = :login")
                ->setParameter('login', $userId);
            $count = (int) $qb->getQuery()->getSingleScalarResult();

            if ($count > $maxResults) {
                $qb = $em->getRepository($entity)->createQueryBuilder('l');
                $qb->select('l')
                    ->where("l.$field = :login")
                    ->setParameter('login', $userId)
                    ->setFirstResult(0)
                    ->setMaxResults($maxResults);
                $result = $qb->getQuery()->getResult();
            } else {
                $result = $em->getRepository($entity)->findBy([$field => $userId]);
            }
            $trackResults[$entity] = $result;
        }

        // TrackELogin
        $trackELoginList = [];
        foreach ($trackResults[TrackELogin::class] as $item) {
            $startDate = $item->getLoginDate()?->format($dateFormat) ?? '';
            $endDate = $item->getLogoutDate()?->format($dateFormat) ?? '';
            $list = [
                'IP: '.$item->getUserIp(),
                'Start: '.$startDate,
                'End: '.$endDate,
            ];
            $trackELoginList[] = implode(', ', $list);
        }

        // TrackEAccess
        $trackEAccessList = [];
        foreach ($trackResults[TrackEAccess::class] as $item) {
            $date = $item->getAccessDate()?->format($dateFormat) ?? '';
            $list = [
                'IP: '.$item->getUserIp(),
                'Tool: '.$item->getAccessTool(),
                'End: '.$date,
            ];
            $trackEAccessList[] = implode(', ', $list);
        }

        // TrackEOnline
        $trackEOnlineList = [];
        foreach ($trackResults[TrackEOnline::class] as $item) {
            $date = $item->getLoginDate()?->format($dateFormat) ?? '';
            $list = [
                'IP: '.$item->getUserIp(),
                'Login date: '.$date,
                'Course # '.$item->getCId(),
                'Session # '.$item->getSessionId(),
            ];
            $trackEOnlineList[] = implode(', ', $list);
        }

        // TrackEDefault
        $trackEDefault = [];
        foreach ($trackResults[TrackEDefault::class] as $item) {
            $date = $item->getDefaultDate()?->format($dateFormat) ?? '';
            $list = [
                'Type: '.$item->getDefaultEventType(),
                'Value: '.$item->getDefaultValue(),
                'Value type: '.$item->getDefaultValueType(),
                'Date: '.$date,
                'Course #'.$item->getCId(),
                'Session # '.$item->getSessionId(),
            ];
            $trackEDefault[] = implode(', ', $list);
        }

        // TrackELastaccess
        $trackELastaccess = [];
        foreach ($trackResults[TrackELastaccess::class] as $item) {
            $date = $item->getAccessDate()?->format($dateFormat) ?? '';
            $list = [
                'Course #'.$item->getCId(),
                'Session # '.$item->getSessionId(),
                'Tool: '.$item->getAccessTool(),
                'Access date: '.$date,
            ];
            $trackELastaccess[] = implode(', ', $list);
        }

        // TrackEUploads
        $trackEUploads = [];
        foreach ($trackResults[TrackEUploads::class] as $item) {
            $date = $item->getUploadDate()?->format($dateFormat) ?? '';
            $list = [
                'Course #'.$item->getCId(),
                'Uploaded at: '.$date,
                'Upload id # '.$item->getUploadId(),
            ];
            $trackEUploads[] = implode(', ', $list);
        }

        // GradebookResult
        $gradebookResult = [];
        foreach ($trackResults[GradebookResult::class] as $item) {
            $date = $item->getCreatedAt()?->format($dateFormat) ?? '';
            $list = [
                'Evaluation id# '.($item->getEvaluation()?->getId() ?? 'n/a'),
                'Creation date: '.$date,
            ];
            $gradebookResult[] = implode(', ', $list);
        }

        // TrackEDownloads
        $trackEDownloads = [];
        foreach ($trackResults[TrackEDownloads::class] as $item) {
            $date = $item->getDownDate()?->format($dateFormat) ?? '';
            $list = [
                'File: '.$item->getDownDocPath(),
                'Download at: '.$date,
            ];
            $trackEDownloads[] = implode(', ', $list);
        }

        // UserCourseCategory
        $result = $em->getRepository(UserCourseCategory::class)->findBy(['user' => $userId]);
        $userCourseCategory = [];
        foreach ($result as $item) {
            $userCourseCategory[] = 'Title: '.$item->getTitle();
        }

        // Forum Posts
        $result = $em->getRepository(CForumPost::class)->findBy(['user' => $userId]);
        $cForumPostList = [];
        foreach ($result as $item) {
            $date = $item->getPostDate()?->format($dateFormat) ?? '';
            $list = [
                'Title: '.$item->getTitle(),
                'Creation date: '.$date,
            ];
            $cForumPostList[] = implode(', ', $list);
        }

        // Forum Threads
        $result = $em->getRepository(CForumThread::class)->findBy(['user' => $userId]);
        $cForumThreadList = [];
        foreach ($result as $item) {
            $date = $item->getThreadDate()?->format($dateFormat) ?? '';
            $list = [
                'Title: '.$item->getTitle(),
                'Creation date: '.$date,
            ];
            $cForumThreadList[] = implode(', ', $list);
        }

        // Group Relations
        $result = $em->getRepository(CGroupRelUser::class)->findBy(['user' => $userId]);
        $cGroupRelUser = [];
        foreach ($result as $item) {
            $list = [
                'Course #'.$item->getCId(),
                'Group #'.($item->getGroup()?->getIid() ?? 'n/a'),
                'Role: '.$item->getStatus(),
            ];
            $cGroupRelUser[] = implode(', ', $list);
        }

        // Attendance Sheets
        $result = $em->getRepository(CAttendanceSheet::class)->findBy(['user' => $userId]);
        $cAttendanceSheetList = [];
        foreach ($result as $item) {
            $list = [
                'Presence: '.$item->getPresence(),
                'Calendar id: '.($item->getAttendanceCalendar()?->getIid() ?? 'n/a'),
            ];
            $cAttendanceSheetList[] = implode(', ', $list);
        }

        // Attendance Results
        $result = $em->getRepository(CAttendanceResult::class)->findBy(['user' => $userId]);
        $cAttendanceResult = [];
        foreach ($result as $item) {
            $list = [
                'Score : '.$item->getScore(),
                'Calendar id: '.($item->getAttendance()?->getIid() ?? 'n/a'),
            ];
            $cAttendanceResult[] = implode(', ', $list);
        }

        // Messages
        $result = $em->getRepository(Message::class)->findBy(['sender' => $userId]);
        $messageList = [];
        foreach ($result as $item) {
            $date = $item->getSendDate()?->format($dateFormat) ?? '';
            $userNames = [];
            if ($item->getReceivers()) {
                foreach ($item->getReceivers() as $receiver) {
                    $username = $receiver->getReceiver()?->getUsername();
                    if ($username) {
                        $userNames[] = $username;
                    }
                }
            }
            $list = [
                'Title: '.$item->getTitle(),
                'Sent date: '.$date,
                'To users: '.implode(', ', $userNames),
                'Type: '.$item->getMsgType(),
            ];
            $messageList[] = implode(', ', $list);
        }

        // Survey Answers
        $result = $em->getRepository(CSurveyAnswer::class)->findBy(['user' => $userId]);
        $cSurveyAnswer = [];
        foreach ($result as $item) {
            $list = [
                'Answer # '.$item->getIid(),
                'Value: '.$item->getValue(),
            ];
            $cSurveyAnswer[] = implode(', ', $list);
        }

        // Dropbox File
        $result = $em->getRepository(CDropboxFile::class)->findBy(['uploaderId' => $userId]);
        $cDropboxFile = [];
        foreach ($result as $item) {
            $date = $item->getUploadDate()?->format($dateFormat) ?? '';
            $list = [
                'Title: '.$item->getTitle(),
                'Uploaded date: '.$date,
                'File: '.$item->getFilename(),
            ];
            $cDropboxFile[] = implode(', ', $list);
        }

        // Dropbox Person
        $result = $em->getRepository(CDropboxPerson::class)->findBy(['userId' => $userId]);
        $cDropboxPerson = [];
        foreach ($result as $item) {
            $list = [
                'File #'.$item->getFileId(),
                'Course #'.$item->getCId(),
            ];
            $cDropboxPerson[] = implode(', ', $list);
        }

        // Dropbox Feedback
        $result = $em->getRepository(CDropboxFeedback::class)->findBy(['authorUserId' => $userId]);
        $cDropboxFeedback = [];
        foreach ($result as $item) {
            $date = $item->getFeedbackDate()?->format($dateFormat) ?? '';
            $list = [
                'File #'.$item->getFileId(),
                'Feedback: '.$item->getFeedback(),
                'Date: '.$date,
            ];
            $cDropboxFeedback[] = implode(', ', $list);
        }

        // Notebook
        $result = $em->getRepository(CNotebook::class)->findBy(['user' => $userId]);
        $cNotebook = [];
        foreach ($result as $item) {
            $date = $item->getUpdateDate()?->format($dateFormat) ?? '';
            $list = [
                'Title: '.$item->getTitle(),
                'Date: '.$date,
            ];
            $cNotebook[] = implode(', ', $list);
        }

        // LP Views
        $result = $em->getRepository(CLpView::class)->findBy(['user' => $userId]);
        $cLpView = [];
        foreach ($result as $item) {
            $list = [
                'LP #'.($item->getLp()?->getIid() ?? 'n/a'),
                'Progress: '.$item->getProgress(),
            ];
            $cLpView[] = implode(', ', $list);
        }

        // Student Publications
        $result = $em->getRepository(CStudentPublication::class)->findBy(['user' => $userId]);
        $cStudentPublication = [];
        foreach ($result as $item) {
            $cStudentPublication[] = 'Title: '.$item->getTitle();
        }

        // Student Publication Comments
        $result = $em->getRepository(CStudentPublicationComment::class)->findBy(['user' => $userId]);
        $cStudentPublicationComment = [];
        foreach ($result as $item) {
            $date = $item->getSentAt()?->format($dateFormat) ?? '';
            $list = [
                'Commment: '.$item->getComment(),
                'File '.$item->getFile(),
                'Date: '.$date,
            ];
            $cStudentPublicationComment[] = implode(', ', $list);
        }

        // Wiki
        $result = $em->getRepository(CWiki::class)->findBy(['userId' => $userId]);
        $cWiki = [];
        foreach ($result as $item) {
            $list = [
                'Title: '.$item->getTitle(),
                'Progress: '.$item->getProgress(),
                'IP: '.$item->getUserIp(),
            ];
            $cWiki[] = implode(', ', $list);
        }

        // Tickets
        $result = $em->getRepository(Ticket::class)->findBy(['insertUserId' => $userId]);
        $ticket = [];
        foreach ($result as $item) {
            $list = [
                'Code: '.$item->getCode(),
                'Subject: '.$item->getSubject(),
            ];
            $ticket[] = implode(', ', $list);
        }

        // Ticket Messages
        $result = $em->getRepository(TicketMessage::class)->findBy(['insertUserId' => $userId]);
        $ticketMessage = [];
        foreach ($result as $item) {
            $date = $item->getInsertDateTime()?->format($dateFormat) ?? '';
            $list = [
                'Subject: '.$item->getSubject(),
                'IP: '.$item->getIpAddress(),
                'Status: '.$item->getStatus(),
                'Creation date: '.$date,
            ];
            $ticketMessage[] = implode(', ', $list);
        }

        // SkillRelUserComment
        $result = $em->getRepository(SkillRelUserComment::class)->findBy(['feedbackGiver' => $userId]);
        $skillRelUserComment = [];
        foreach ($result as $item) {
            $date = $item->getFeedbackDateTime()?->format($dateFormat) ?? '';
            $list = [
                'Feedback: '.$item->getFeedbackText(),
                'Value: '.$item->getFeedbackValue(),
                'Created at: '.$date,
            ];
            $skillRelUserComment[] = implode(', ', $list);
        }

        // UserRelCourseVote
        $result = $em->getRepository(UserRelCourseVote::class)->findBy(['user' => $userId]);
        $userRelCourseVote = [];
        foreach ($result as $item) {
            $list = [
                'Course #'.($item->getCourse()?->getId() ?? 'n/a'),
                'Vote: '.$item->getVote(),
            ];
            $userRelCourseVote[] = implode(', ', $list);
        }

        // Preserve last login safely (no-op but keeps behavior)
        $lastLogin = $user->getLastLogin();
        $user->setLastLogin($lastLogin);

        $ignore = [
            'twoStepVerificationCode',
            'biography',
            'dateOfBirth',
            'gender',
            'facebookData',
            'facebookName',
            'facebookUid',
            'gplusData',
            'gplusName',
            'gplusUid',
            'locale',
            'timezone',
            'twitterData',
            'twitterName',
            'twitterUid',
            'token',
            'website',
            'plainPassword',
            'completeNameWithUsername',
            'completeName',
            'completeNameWithClasses',
            'salt',
            'dropBoxSentFiles',
            'dropBoxReceivedFiles',
            'currentUrl',
            'uuid',
            'curriculumItems',
            'currentSession',
            'currentCourse',
            'resourceNode',
        ];

        return $this->serializer->serialize($user, 'json', [
            AbstractNormalizer::IGNORED_ATTRIBUTES => $ignore,
            'groups' => ['user_export'],
        ]);
    }

    public function serializeUserData(int $userId): string
    {
        $em = $this->em;
        $dateFormat = DateTimeInterface::ATOM;

        /** @var User|null $user */
        $user = $this->userRepository->find($userId);
        if (!$user) {
            throw new \Exception('User not found.');
        }

        $personalData = [];

        // User info
        $personalData['user_info'][] = [
            'ID' => $user->getId(),
            'Username' => $user->getUsername(),
            'Email' => $user->getEmail(),
            'FirstName' => $user->getFirstname(),
            'LastName' => $user->getLastname(),
            'Website' => $user->getWebsite(),
            'Biography' => $user->getBiography(),
            'Locale' => $user->getLocale(),
            'Timezone' => $user->getTimezone(),
            'PhoneNumber' => $user->getPhone(),
            'Address' => $user->getAddress(),
            'Gender' => $user->getGender(),
            'LastLogin' => $user->getLastLogin() ? $user->getLastLogin()->format($dateFormat) : null,
            'Roles' => $user->getRoles(),
            'ApiToken' => $user->getApiToken(),
        ];

        // Courses (null-safe)
        $courses = $user->getCourses();
        foreach ($courses as $course) {
            $personalData['Courses'][] = [
                'CourseID' => $course->getCourse()?->getId() ?? 'n/a',
                'CourseCode' => $course->getCourse()?->getCode() ?? 'n/a',
                'CourseTitle' => $course->getCourse()?->getTitle() ?? 'n/a',
            ];
        }

        // GradebookCertificates
        $gradebookCertificates = $em->getRepository(GradebookCertificate::class)->findBy(['user' => $userId]);
        foreach ($gradebookCertificates as $certificate) {
            $personalData['GradebookCertificates'][] = [
                'Score' => $certificate->getScoreCertificate(),
                'Path' => $certificate->getPathCertificate(),
                'CreatedAt' => $certificate->getCreatedAt()?->format($dateFormat) ?? '',
            ];
        }

        // TrackEExercises
        $trackEExercises = $em->getRepository(TrackEExercise::class)->findBy(['user' => $userId]);
        foreach ($trackEExercises as $exercise) {
            $personalData['TrackEExercises'][] = [
                'IP' => $exercise->getUserIp(),
                'Start' => $exercise->getExeDate()?->format($dateFormat) ?? '',
                'Status' => $exercise->getStatus(),
            ];
        }

        // Downloads (null-safe on resource link)
        $resourceLink = $user->getResourceNode()?->getResourceLinks()?->first();
        if ($resourceLink) {
            $trackEDownloads = $em->getRepository(TrackEDownloads::class)->findBy(['resourceLink' => $resourceLink]);
            foreach ($trackEDownloads as $item) {
                $personalData['Downloads'][] = [
                    'File' => $item->getDownDocPath(),
                    'DownloadedAt' => $item->getDownDate()?->format($dateFormat) ?? '',
                ];
            }
        }

        // UserCourseCategory
        $userCourseCategories = $em->getRepository(UserCourseCategory::class)->findBy(['user' => $userId]);
        foreach ($userCourseCategories as $item) {
            $personalData['CourseCategories'][] = [
                'Title' => $item->getTitle(),
            ];
        }

        // Forum Posts
        $cForumPosts = $em->getRepository(CForumPost::class)->findBy(['user' => $userId]);
        foreach ($cForumPosts as $item) {
            $personalData['ForumPosts'][] = [
                'Title' => $item->getTitle(),
                'CreationDate' => $item->getPostDate()?->format($dateFormat) ?? '',
            ];
        }

        // Forum Threads (single block; removed duplicate)
        $cForumThreads = $em->getRepository(CForumThread::class)->findBy(['user' => $userId]);
        foreach ($cForumThreads as $item) {
            $personalData['ForumThreads'][] = [
                'Title' => $item->getTitle(),
                'CreationDate' => $item->getThreadDate()?->format($dateFormat) ?? '',
            ];
        }

        // Group Relations
        $cGroupRelUsers = $em->getRepository(CGroupRelUser::class)->findBy(['user' => $userId]);
        foreach ($cGroupRelUsers as $item) {
            $personalData['GroupRelations'][] = [
                'CourseId' => $item->getCId(),
                'GroupId'  => $item->getGroup()?->getIid() ?? 'n/a',
                'Role'     => $item->getStatus(),
            ];
        }

        // Attendance Sheets
        $cAttendanceSheets = $em->getRepository(CAttendanceSheet::class)->findBy(['user' => $userId]);
        foreach ($cAttendanceSheets as $item) {
            $personalData['AttendanceSheets'][] = [
                'Presence'   => $item->getPresence(),
                'CalendarId' => $item->getAttendanceCalendar()?->getIid() ?? 'n/a',
            ];
        }

        // Attendance Results
        $cAttendanceResults = $em->getRepository(CAttendanceResult::class)->findBy(['user' => $userId]);
        foreach ($cAttendanceResults as $item) {
            $personalData['AttendanceResults'][] = [
                'Score'      => $item->getScore(),
                'CalendarId' => $item->getAttendance()?->getIid() ?? 'n/a',
            ];
        }

        // Messages
        $messages = $em->getRepository(Message::class)->findBy(['sender' => $userId]);
        foreach ($messages as $item) {
            $receivers = array_map(
                fn ($receiver) => $receiver->getReceiver()?->getUsername() ?? 'n/a',
                $item->getReceivers()->toArray()
            );
            $personalData['Messages'][] = [
                'Title'    => $item->getTitle(),
                'SentDate' => $item->getSendDate()?->format($dateFormat) ?? '',
                'ToUsers'  => implode(', ', $receivers),
                'Type'     => $item->getMsgType(),
            ];
        }

        // Survey Answers
        $cSurveyAnswers = $em->getRepository(CSurveyAnswer::class)->findBy(['user' => $userId]);
        foreach ($cSurveyAnswers as $item) {
            $personalData['SurveyAnswers'][] = [
                'AnswerId' => $item->getIid(),
                'Value' => $item->getValue(),
            ];
        }

        // LP Views
        $cLpViews = $em->getRepository(CLpView::class)->findBy(['user' => $userId]);
        foreach ($cLpViews as $item) {
            $personalData['LpViews'][] = [
                'LPId'     => $item->getLp()?->getIid() ?? 'n/a',
                'Progress' => $item->getProgress(),
            ];
        }

        // Student Publications
        $cStudentPublications = $em->getRepository(CStudentPublication::class)->findBy(['user' => $userId]);
        foreach ($cStudentPublications as $item) {
            $personalData['StudentPublications'][] = [
                'Title' => $item->getTitle(),
            ];
        }

        // Student Publication Comments
        $cStudentPublicationComments = $em->getRepository(CStudentPublicationComment::class)->findBy(['user' => $userId]);
        foreach ($cStudentPublicationComments as $item) {
            $personalData['StudentPublicationComments'][] = [
                'Comment' => $item->getComment(),
                'File' => $item->getFile(),
                'Date' => $item->getSentAt()?->format($dateFormat) ?? '',
            ];
        }

        // Wiki
        $cWikis = $em->getRepository(CWiki::class)->findBy(['userId' => $userId]);
        foreach ($cWikis as $item) {
            $personalData['Wikis'][] = [
                'Title' => $item->getTitle(),
                'Progress' => $item->getProgress(),
                'IP' => $item->getUserIp(),
            ];
        }

        // Tickets
        $tickets = $em->getRepository(Ticket::class)->findBy(['insertUserId' => $userId]);
        foreach ($tickets as $item) {
            $personalData['Tickets'][] = [
                'Code' => $item->getCode(),
                'Subject' => $item->getSubject(),
            ];
        }

        // Ticket Messages
        $ticketMessages = $em->getRepository(TicketMessage::class)->findBy(['insertUserId' => $userId]);
        foreach ($ticketMessages as $item) {
            $personalData['TicketMessages'][] = [
                'Subject' => $item->getSubject(),
                'IP' => $item->getIpAddress(),
                'Status' => $item->getStatus(),
                'CreationDate' => $item->getInsertDateTime()?->format($dateFormat) ?? '',
            ];
        }

        // SkillRelUserComment
        $skillRelUserComments = $em->getRepository(SkillRelUserComment::class)->findBy(['feedbackGiver' => $userId]);
        foreach ($skillRelUserComments as $item) {
            $personalData['SkillUserComments'][] = [
                'Feedback' => $item->getFeedbackText(),
                'Value' => $item->getFeedbackValue(),
                'CreatedAt' => $item->getFeedbackDateTime()?->format($dateFormat) ?? '',
            ];
        }

        // Course Votes
        $userRelCourseVotes = $em->getRepository(UserRelCourseVote::class)->findBy(['user' => $userId]);
        foreach ($userRelCourseVotes as $item) {
            $personalData['CourseVotes'][] = [
                'CourseId' => $item->getCourse()?->getId() ?? 'n/a',
                'Vote' => $item->getVote(),
            ];
        }

        return $this->serializer->serialize($personalData, 'json');
    }
}
