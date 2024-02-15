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
use Exception;
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

        /** @var User $user */
        $user = $this->userRepository->find($userId);

        $user->setPassword($substitutionTerms['password'] ?? '');
        $user->setSalt($substitutionTerms['salt'] ?? '');

        $noDataLabel = $substitutionTerms['empty'] ?? '';

        // Dummy content
        $user->setDateOfBirth(null);
        $user->setLocale($noDataLabel);
        $user->setTimezone($noDataLabel);
        $user->setWebsite($noDataLabel);

        // GradebookCertificate
        $result = $em->getRepository(GradebookCertificate::class)->findBy([
            'user' => $userId,
        ]);
        $gradebookCertificate = [];

        /** @var GradebookCertificate $item */
        foreach ($result as $item) {
            $createdAt = $item->getCreatedAt()->format($dateFormat);
            $list = [
                'Score: '.$item->getScoreCertificate(),
                'Path: '.$item->getPathCertificate(),
                'Created at: '.$createdAt,
            ];
            $gradebookCertificate[] = implode(', ', $list);
        }

        // TrackEExercises
        $result = $em->getRepository(TrackEExercise::class)->findBy([
            'user' => $userId,
        ]);
        $trackEExercises = [];

        /** @var TrackEExercise $item */
        foreach ($result as $item) {
            $date = $item->getExeDate()->format($dateFormat);
            $list = [
                'IP: '.$item->getUserIp(),
                'Start: '.$date,
                'Status: '.$item->getStatus(),
            ];
            $trackEExercises[] = implode(', ', $list);
        }

        // TrackEAttempt
        $result = $em->getRepository(TrackEAttempt::class)->findBy([
            'user' => $userId,
        ]);
        $trackEAttempt = [];

        /** @var TrackEAttempt $item */
        foreach ($result as $item) {
            $date = $item->getTms()->format($dateFormat);
            $list = [
                'Attempt #'.$item->getTrackEExercise()->getExeId(),
                // 'Answer: '.$item->getAnswer(),
                // 'Marks: '.$item->getMarks(),
                'Position: '.$item->getPosition(),
                'Date: '.$date,
            ];
            $trackEAttempt[] = implode(', ', $list);
        }

        // TrackECourseAccess
        $result = $em->getRepository(TrackECourseAccess::class)->findBy([
            'user' => $userId,
        ]);
        $trackECourseAccessList = [];

        /** @var TrackECourseAccess $item */
        foreach ($result as $item) {
            $startDate = $item->getLoginCourseDate()->format($dateFormat);
            $endDate = null !== $item->getLogoutCourseDate() ? $item->getLogoutCourseDate()->format($dateFormat) : '';
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
                ->setParameter('login', $userId)
            ;
            $query = $qb->getQuery();
            $count = $query->getSingleScalarResult();

            if ($count > $maxResults) {
                $qb = $em->getRepository($entity)->createQueryBuilder('l');
                $qb
                    ->select('l')
                    ->where("l.$field = :login")
                    ->setParameter('login', $userId)
                ;
                $qb
                    ->setFirstResult(0)
                    ->setMaxResults($maxResults)
                ;
                $result = $qb->getQuery()->getResult();
            } else {
                $criteria = [
                    $field => $userId,
                ];
                $result = $em->getRepository($entity)->findBy($criteria);
            }
            $trackResults[$entity] = $result;
        }

        $trackELoginList = [];

        /** @var TrackELogin $item */
        foreach ($trackResults[TrackELogin::class] as $item) {
            $startDate = $item->getLoginDate()->format($dateFormat);
            $endDate = null !== $item->getLogoutDate() ? $item->getLogoutDate()->format($dateFormat) : '';
            $list = [
                'IP: '.$item->getUserIp(),
                'Start: '.$startDate,
                'End: '.$endDate,
            ];
            $trackELoginList[] = implode(', ', $list);
        }

        // TrackEAccess
        $trackEAccessList = [];

        /** @var TrackEAccess $item */
        foreach ($trackResults[TrackEAccess::class] as $item) {
            $date = $item->getAccessDate()->format($dateFormat);
            $list = [
                'IP: '.$item->getUserIp(),
                'Tool: '.$item->getAccessTool(),
                'End: '.$date,
            ];
            $trackEAccessList[] = implode(', ', $list);
        }

        // TrackEOnline
        $trackEOnlineList = [];

        /** @var TrackEOnline $item */
        foreach ($trackResults[TrackEOnline::class] as $item) {
            $date = $item->getLoginDate()->format($dateFormat);
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

        /** @var TrackEDefault $item */
        foreach ($trackResults[TrackEDefault::class] as $item) {
            $date = $item->getDefaultDate()->format($dateFormat);
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

        /** @var TrackELastaccess $item */
        foreach ($trackResults[TrackELastaccess::class] as $item) {
            $date = $item->getAccessDate()->format($dateFormat);
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

        /** @var TrackEUploads $item */
        foreach ($trackResults[TrackEUploads::class] as $item) {
            $date = $item->getUploadDate()->format($dateFormat);
            $list = [
                'Course #'.$item->getCId(),
                'Uploaded at: '.$date,
                'Upload id # '.$item->getUploadId(),
            ];
            $trackEUploads[] = implode(', ', $list);
        }

        $gradebookResult = [];

        /** @var GradebookResult $item */
        foreach ($trackResults[GradebookResult::class] as $item) {
            $date = $item->getCreatedAt()->format($dateFormat);
            $list = [
                'Evaluation id# '.$item->getEvaluation()->getId(),
                // 'Score: '.$item->getScore(),
                'Creation date: '.$date,
            ];
            $gradebookResult[] = implode(', ', $list);
        }

        $trackEDownloads = [];

        /** @var TrackEDownloads $item */
        foreach ($trackResults[TrackEDownloads::class] as $item) {
            $date = $item->getDownDate()->format($dateFormat);
            $list = [
                'File: '.$item->getDownDocPath(),
                'Download at: '.$date,
            ];
            $trackEDownloads[] = implode(', ', $list);
        }

        // UserCourseCategory
        $result = $em->getRepository(UserCourseCategory::class)->findBy([
            'user' => $userId,
        ]);
        $userCourseCategory = [];

        /** @var UserCourseCategory $item */
        foreach ($result as $item) {
            $list = [
                'Title: '.$item->getTitle(),
            ];
            $userCourseCategory[] = implode(', ', $list);
        }

        // Forum
        $result = $em->getRepository(CForumPost::class)->findBy([
            'user' => $userId,
        ]);
        $cForumPostList = [];

        /** @var CForumPost $item */
        foreach ($result as $item) {
            $date = $item->getPostDate()->format($dateFormat);
            $list = [
                'Title: '.$item->getTitle(),
                'Creation date: '.$date,
            ];
            $cForumPostList[] = implode(', ', $list);
        }

        // CForumThread
        $result = $em->getRepository(CForumThread::class)->findBy([
            'user' => $userId,
        ]);
        $cForumThreadList = [];

        /** @var CForumThread $item */
        foreach ($result as $item) {
            $date = $item->getThreadDate()->format($dateFormat);
            $list = [
                'Title: '.$item->getTitle(),
                'Creation date: '.$date,
            ];
            $cForumThreadList[] = implode(', ', $list);
        }

        // cGroupRelUser
        $result = $em->getRepository(CGroupRelUser::class)->findBy([
            'user' => $userId,
        ]);
        $cGroupRelUser = [];

        /** @var CGroupRelUser $item */
        foreach ($result as $item) {
            $list = [
                'Course # '.$item->getCId(),
                'Group #'.$item->getGroup()->getIid(),
                'Role: '.$item->getStatus(),
            ];
            $cGroupRelUser[] = implode(', ', $list);
        }

        // CAttendanceSheet
        $result = $em->getRepository(CAttendanceSheet::class)->findBy([
            'user' => $userId,
        ]);
        $cAttendanceSheetList = [];

        /** @var CAttendanceSheet $item */
        foreach ($result as $item) {
            $list = [
                'Presence: '.$item->getPresence(),
                'Calendar id: '.$item->getAttendanceCalendar()->getIid(),
            ];
            $cAttendanceSheetList[] = implode(', ', $list);
        }

        // CAttendanceResult
        $result = $em->getRepository(CAttendanceResult::class)->findBy([
            'user' => $userId,
        ]);
        $cAttendanceResult = [];

        /** @var CAttendanceResult $item */
        foreach ($result as $item) {
            $list = [
                'Score : '.$item->getScore(),
                'Calendar id: '.$item->getAttendance()->getIid(),
            ];
            $cAttendanceResult[] = implode(', ', $list);
        }

        // Message
        $result = $em->getRepository(Message::class)->findBy([
            'sender' => $userId,
        ]);
        $messageList = [];

        /** @var Message $item */
        foreach ($result as $item) {
            $date = $item->getSendDate()->format($dateFormat);
            $userName = '';
            if ($item->getReceivers()) {
                foreach ($item->getReceivers() as $receiver) {
                    $userName = ', '.$receiver->getReceiver()->getUsername();
                }
            }

            $list = [
                'Title: '.$item->getTitle(),
                'Sent date: '.$date,
                'To users: '.$userName,
                'Type: '.$item->getMsgType(),
            ];
            $messageList[] = implode(', ', $list);
        }

        // CSurveyAnswer
        $result = $em->getRepository(CSurveyAnswer::class)->findBy([
            'user' => $userId,
        ]);
        $cSurveyAnswer = [];

        /** @var CSurveyAnswer $item */
        foreach ($result as $item) {
            $list = [
                'Answer # '.$item->getIid(),
                'Value: '.$item->getValue(),
            ];
            $cSurveyAnswer[] = implode(', ', $list);
        }

        // CDropboxFile
        $result = $em->getRepository(CDropboxFile::class)->findBy([
            'uploaderId' => $userId,
        ]);
        $cDropboxFile = [];

        /** @var CDropboxFile $item */
        foreach ($result as $item) {
            $date = $item->getUploadDate()->format($dateFormat);
            $list = [
                'Title: '.$item->getTitle(),
                'Uploaded date: '.$date,
                'File: '.$item->getFilename(),
            ];
            $cDropboxFile[] = implode(', ', $list);
        }

        // CDropboxPerson
        $result = $em->getRepository(CDropboxPerson::class)->findBy([
            'userId' => $userId,
        ]);
        $cDropboxPerson = [];

        /** @var CDropboxPerson $item */
        foreach ($result as $item) {
            $list = [
                'File #'.$item->getFileId(),
                'Course #'.$item->getCId(),
            ];
            $cDropboxPerson[] = implode(', ', $list);
        }

        // CDropboxPerson
        $result = $em->getRepository(CDropboxFeedback::class)->findBy([
            'authorUserId' => $userId,
        ]);
        $cDropboxFeedback = [];

        /** @var CDropboxFeedback $item */
        foreach ($result as $item) {
            $date = $item->getFeedbackDate()->format($dateFormat);
            $list = [
                'File #'.$item->getFileId(),
                'Feedback: '.$item->getFeedback(),
                'Date: '.$date,
            ];
            $cDropboxFeedback[] = implode(', ', $list);
        }

        // CNotebook
        $result = $em->getRepository(CNotebook::class)->findBy([
            'user' => $userId,
        ]);
        $cNotebook = [];

        /** @var CNotebook $item */
        foreach ($result as $item) {
            $date = $item->getUpdateDate()->format($dateFormat);
            $list = [
                'Title: '.$item->getTitle(),
                'Date: '.$date,
            ];
            $cNotebook[] = implode(', ', $list);
        }

        // CLpView
        $result = $em->getRepository(CLpView::class)->findBy([
            'user' => $userId,
        ]);
        $cLpView = [];

        /** @var CLpView $item */
        foreach ($result as $item) {
            $list = [
                // 'Id #'.$item->getId(),
                'LP #'.$item->getLp()->getIid(),
                'Progress: '.$item->getProgress(),
                // 'Course #'.$item->getCId(),
                // 'Session #'.$item->getSessionId(),
            ];
            $cLpView[] = implode(', ', $list);
        }

        // CStudentPublication
        $result = $em->getRepository(CStudentPublication::class)->findBy([
            'user' => $userId,
        ]);
        $cStudentPublication = [];

        /** @var CStudentPublication $item */
        foreach ($result as $item) {
            $list = [
                'Title: '.$item->getTitle(),
                // 'URL: '.$item->getTitle(),
            ];
            $cStudentPublication[] = implode(', ', $list);
        }

        // CStudentPublicationComment
        $result = $em->getRepository(CStudentPublicationComment::class)->findBy([
            'user' => $userId,
        ]);
        $cStudentPublicationComment = [];

        /** @var CStudentPublicationComment $item */
        foreach ($result as $item) {
            $date = $item->getSentAt()->format($dateFormat);
            $list = [
                'Commment: '.$item->getComment(),
                'File '.$item->getFile(),
                // 'Course # '.$item->getCId(),
                'Date: '.$date,
            ];
            $cStudentPublicationComment[] = implode(', ', $list);
        }

        // CWiki
        $result = $em->getRepository(CWiki::class)->findBy([
            'userId' => $userId,
        ]);
        $cWiki = [];

        /** @var CWiki $item */
        foreach ($result as $item) {
            $list = [
                'Title: '.$item->getTitle(),
                'Progress: '.$item->getProgress(),
                'IP: '.$item->getUserIp(),
            ];
            $cWiki[] = implode(', ', $list);
        }

        // Ticket
        $result = $em->getRepository(Ticket::class)->findBy([
            'insertUserId' => $userId,
        ]);
        $ticket = [];

        /** @var Ticket $item */
        foreach ($result as $item) {
            $list = [
                'Code: '.$item->getCode(),
                'Subject: '.$item->getSubject(),
            ];
            $ticket[] = implode(', ', $list);
        }

        // Message
        $result = $em->getRepository(TicketMessage::class)->findBy([
            'insertUserId' => $userId,
        ]);
        $ticketMessage = [];

        /** @var TicketMessage $item */
        foreach ($result as $item) {
            $date = $item->getInsertDateTime()->format($dateFormat);
            $list = [
                'Subject: '.$item->getSubject(),
                'IP: '.$item->getIpAddress(),
                'Status: '.$item->getStatus(),
                'Creation date: '.$date,
            ];
            $ticketMessage[] = implode(', ', $list);
        }

        // SkillRelUserComment
        $result = $em->getRepository(SkillRelUserComment::class)->findBy([
            'feedbackGiver' => $userId,
        ]);
        $skillRelUserComment = [];

        /** @var SkillRelUserComment $item */
        foreach ($result as $item) {
            $date = $item->getFeedbackDateTime()->format($dateFormat);
            $list = [
                'Feedback: '.$item->getFeedbackText(),
                'Value: '.$item->getFeedbackValue(),
                'Created at: '.$date,
            ];
            $skillRelUserComment[] = implode(', ', $list);
        }

        // UserRelCourseVote
        $result = $em->getRepository(UserRelCourseVote::class)->findBy([
            'user' => $userId,
        ]);
        $userRelCourseVote = [];

        /** @var UserRelCourseVote $item */
        foreach ($result as $item) {
            $list = [
                'Course #'.$item->getCourse()->getId(),
                // 'Session #'.$item->getSession()->getId(),
                'Vote: '.$item->getVote(),
            ];
            $userRelCourseVote[] = implode(', ', $list);
        }

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
            'gplusUid',
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

        /** @var User $user */
        $user = $this->userRepository->find($userId);
        if (!$user) {
            throw new Exception('User not found.');
        }

        $personalData = [];
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

        $courses = $user->getCourses();
        foreach ($courses as $course) {
            $personalData['Courses'][] = [
                'CourseID' => $course->getCourse()->getId(),
                'CourseCode' => $course->getCourse()->getCode(),
                'CourseTitle' => $course->getCourse()->getTitle(),
            ];
        }

        $gradebookCertificates = $em->getRepository(GradebookCertificate::class)->findBy(['user' => $userId]);
        foreach ($gradebookCertificates as $certificate) {
            $personalData['GradebookCertificates'][] = [
                'Score' => $certificate->getScoreCertificate(),
                'Path' => $certificate->getPathCertificate(),
                'CreatedAt' => $certificate->getCreatedAt()->format($dateFormat),
            ];
        }

        $trackEExercises = $em->getRepository(TrackEExercise::class)->findBy(['user' => $userId]);
        foreach ($trackEExercises as $exercise) {
            $personalData['TrackEExercises'][] = [
                'IP' => $exercise->getUserIp(),
                'Start' => $exercise->getExeDate()->format($dateFormat),
                'Status' => $exercise->getStatus(),
            ];
        }

        // TrackEDownloads
        $resourceLinkId = $user->getResourceNode()->getResourceLinks()->first();
        if (null !== $resourceLinkId) {
            $trackEDownloads = $em->getRepository(TrackEDownloads::class)->findBy(['resourceLink' => $resourceLinkId]);
            foreach ($trackEDownloads as $item) {
                $userData['Downloads'][] = [
                    'File' => $item->getDownDocPath(),
                    'DownloadedAt' => $item->getDownDate()->format($dateFormat),
                ];
            }
        }

        // UserCourseCategory
        $userCourseCategories = $em->getRepository(UserCourseCategory::class)->findBy(['user' => $userId]);
        foreach ($userCourseCategories as $item) {
            $userData['CourseCategories'][] = [
                'Title' => $item->getTitle(),
            ];
        }

        // CForumPost
        $cForumPosts = $em->getRepository(CForumPost::class)->findBy(['user' => $userId]);
        foreach ($cForumPosts as $item) {
            $userData['ForumPosts'][] = [
                'Title' => $item->getTitle(),
                'CreationDate' => $item->getPostDate()->format($dateFormat),
            ];
        }

        // CForumThread
        $cForumThreads = $em->getRepository(CForumThread::class)->findBy(['user' => $userId]);
        foreach ($cForumThreads as $item) {
            $userData['ForumThreads'][] = [
                'Title' => $item->getTitle(),
                'CreationDate' => $item->getThreadDate()->format($dateFormat),
            ];
        }

        // CForumThread
        $cForumThreads = $em->getRepository(CForumThread::class)->findBy(['user' => $userId]);
        foreach ($cForumThreads as $item) {
            $userData['ForumThreads'][] = [
                'Title' => $item->getTitle(),
                'CreationDate' => $item->getThreadDate()->format($dateFormat),
            ];
        }

        // CGroupRelUser
        $cGroupRelUsers = $em->getRepository(CGroupRelUser::class)->findBy(['user' => $userId]);
        foreach ($cGroupRelUsers as $item) {
            $userData['GroupRelations'][] = [
                'CourseId' => $item->getCId(),
                'GroupId' => $item->getGroup()->getIid(),
                'Role' => $item->getStatus(),
            ];
        }

        // CAttendanceSheet
        $cAttendanceSheets = $em->getRepository(CAttendanceSheet::class)->findBy(['user' => $userId]);
        foreach ($cAttendanceSheets as $item) {
            $userData['AttendanceSheets'][] = [
                'Presence' => $item->getPresence(),
                'CalendarId' => $item->getAttendanceCalendar()->getIid(),
            ];
        }

        // CAttendanceResult
        $cAttendanceResults = $em->getRepository(CAttendanceResult::class)->findBy(['user' => $userId]);
        foreach ($cAttendanceResults as $item) {
            $userData['AttendanceResults'][] = [
                'Score' => $item->getScore(),
                'CalendarId' => $item->getAttendance()->getIid(),
            ];
        }

        // Message
        $messages = $em->getRepository(Message::class)->findBy(['sender' => $userId]);
        foreach ($messages as $item) {
            $receivers = array_map(fn ($receiver) => $receiver->getReceiver()->getUsername(), $item->getReceivers()->toArray());
            $userData['Messages'][] = [
                'Title' => $item->getTitle(),
                'SentDate' => $item->getSendDate()->format($dateFormat),
                'ToUsers' => implode(', ', $receivers),
                'Type' => $item->getMsgType(),
            ];
        }

        // CSurveyAnswer
        $cSurveyAnswers = $em->getRepository(CSurveyAnswer::class)->findBy(['user' => $userId]);
        foreach ($cSurveyAnswers as $item) {
            $userData['SurveyAnswers'][] = [
                'AnswerId' => $item->getIid(),
                'Value' => $item->getValue(),
            ];
        }

        // CLpView
        $cLpViews = $em->getRepository(CLpView::class)->findBy(['user' => $userId]);
        foreach ($cLpViews as $item) {
            $userData['LpViews'][] = [
                'LPId' => $item->getLp()->getIid(),
                'Progress' => $item->getProgress(),
            ];
        }

        // CStudentPublication
        $cStudentPublications = $em->getRepository(CStudentPublication::class)->findBy(['user' => $userId]);
        foreach ($cStudentPublications as $item) {
            $userData['StudentPublications'][] = [
                'Title' => $item->getTitle(),
            ];
        }

        // CStudentPublicationComment
        $cStudentPublicationComments = $em->getRepository(CStudentPublicationComment::class)->findBy(['user' => $userId]);
        foreach ($cStudentPublicationComments as $item) {
            $userData['StudentPublicationComments'][] = [
                'Comment' => $item->getComment(),
                'File' => $item->getFile(),
                'Date' => $item->getSentAt()->format($dateFormat),
            ];
        }

        // CWiki
        $cWikis = $em->getRepository(CWiki::class)->findBy(['userId' => $userId]);
        foreach ($cWikis as $item) {
            $userData['Wikis'][] = [
                'Title' => $item->getTitle(),
                'Progress' => $item->getProgress(),
                'IP' => $item->getUserIp(),
            ];
        }

        // Ticket
        $tickets = $em->getRepository(Ticket::class)->findBy(['insertUserId' => $userId]);
        foreach ($tickets as $item) {
            $userData['Tickets'][] = [
                'Code' => $item->getCode(),
                'Subject' => $item->getSubject(),
            ];
        }

        // TicketMessage
        $ticketMessages = $em->getRepository(TicketMessage::class)->findBy(['insertUserId' => $userId]);
        foreach ($ticketMessages as $item) {
            $userData['TicketMessages'][] = [
                'Subject' => $item->getSubject(),
                'IP' => $item->getIpAddress(),
                'Status' => $item->getStatus(),
                'CreationDate' => $item->getInsertDateTime()->format($dateFormat),
            ];
        }

        // SkillRelUserComment
        $skillRelUserComments = $em->getRepository(SkillRelUserComment::class)->findBy(['feedbackGiver' => $userId]);
        foreach ($skillRelUserComments as $item) {
            $userData['SkillUserComments'][] = [
                'Feedback' => $item->getFeedbackText(),
                'Value' => $item->getFeedbackValue(),
                'CreatedAt' => $item->getFeedbackDateTime()->format($dateFormat),
            ];
        }

        // UserRelCourseVote
        $userRelCourseVotes = $em->getRepository(UserRelCourseVote::class)->findBy(['user' => $userId]);
        foreach ($userRelCourseVotes as $item) {
            $userData['CourseVotes'][] = [
                'CourseId' => $item->getCourse()->getId(),
                'Vote' => $item->getVote(),
            ];
        }

        return $this->serializer->serialize($personalData, 'json');
    }
}
