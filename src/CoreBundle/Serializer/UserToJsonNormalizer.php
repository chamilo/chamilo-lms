<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Serializer;

use Agenda;
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
use Chamilo\CoreBundle\Entity\TrackELastaccess;
use Chamilo\CoreBundle\Entity\TrackELogin;
use Chamilo\CoreBundle\Entity\TrackEOnline;
use Chamilo\CoreBundle\Entity\TrackEUploads;
use Chamilo\CoreBundle\Entity\TrackEExercise;
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
use SocialManager;
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
     * @return string
     */
    public function getPersonalDataToJson(int $userId, array $substitutionTerms)
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
        //$user->setBiography($noDataLabel);
        /*$user->setFacebookData($noDataLabel);
        $user->setFacebookName($noDataLabel);
        $user->setFacebookUid($noDataLabel);*/
        //$user->setImageName($noDataLabel);
        //$user->setTwoStepVerificationCode($noDataLabel);
        //$user->setGender($noDataLabel);
        /*$user->setGplusData($noDataLabel);
        $user->setGplusName($noDataLabel);
        $user->setGplusUid($noDataLabel);*/
        $user->setLocale($noDataLabel);
        $user->setTimezone($noDataLabel);
        /*$user->setTwitterData($noDataLabel);
        $user->setTwitterName($noDataLabel);
        $user->setTwitterUid($noDataLabel);*/
        $user->setWebsite($noDataLabel);
        //$user->setToken($noDataLabel);

        /*$friends = SocialManager::get_friends($userId);
        $friendList = [];
        if (!empty($friends)) {
            foreach ($friends as $friend) {
                $friendList[] = $friend['user_info']['complete_name'];
            }
        }*/

        /*$agenda = new Agenda('personal');
        $events = $agenda->getEvents(0, 0, 0, 0, $userId, 'array');
        $eventList = [];
        if (!empty($events)) {
            foreach ($events as $event) {
                $eventList[] = $event['title'].' '.$event['start_date_localtime'].' / '.$event['end_date_localtime'];
            }
        }*/

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
        $criteria = [
            'user' => $userId,
        ];
        $result = $em->getRepository(TrackEExercise::class)->findBy($criteria);
        $trackEExercises = [];
        /** @var TrackEExercise $item */
        foreach ($result as $item) {
            $date = $item->getExeDate()->format($dateFormat);
            $list = [
                'IP: '.$item->getUserIp(),
                'Start: '.$date,
                'Status: '.$item->getStatus(),
                // 'Result: '.$item->getExeResult(),
                // 'Weighting: '.$item->getExeWeighting(),
            ];
            $trackEExercises[] = implode(', ', $list);
        }

        // TrackEAttempt
        $criteria = [
            'user' => $userId,
        ];
        $result = $em->getRepository(TrackEAttempt::class)->findBy($criteria);
        $trackEAttempt = [];
        /** @var TrackEAttempt $item */
        foreach ($result as $item) {
            $date = $item->getTms()->format($dateFormat);
            $list = [
                'Attempt #'.$item->getTrackEExercise()->getExeId(),
                //'Answer: '.$item->getAnswer(),
                //'Marks: '.$item->getMarks(),
                'Position: '.$item->getPosition(),
                'Date: '.$date,
            ];
            $trackEAttempt[] = implode(', ', $list);
        }

        // TrackECourseAccess
        $criteria = [
            'user' => $userId,
        ];
        $result = $em->getRepository(TrackECourseAccess::class)->findBy($criteria);
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
                'Session # '.$item->getAccessSessionId(),
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
                //'Score: '.$item->getScore(),
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
        $criteria = [
            'user' => $userId,
        ];
        $result = $em->getRepository(UserCourseCategory::class)->findBy($criteria);
        $userCourseCategory = [];
        /** @var UserCourseCategory $item */
        foreach ($result as $item) {
            $list = [
                'Title: '.$item->getTitle(),
            ];
            $userCourseCategory[] = implode(', ', $list);
        }

        // Forum
        $criteria = [
            'user' => $userId,
        ];
        $result = $em->getRepository(CForumPost::class)->findBy($criteria);
        $cForumPostList = [];
        /** @var CForumPost $item */
        foreach ($result as $item) {
            $date = $item->getPostDate()->format($dateFormat);
            $list = [
                'Title: '.$item->getPostTitle(),
                'Creation date: '.$date,
            ];
            $cForumPostList[] = implode(', ', $list);
        }

        // CForumThread
        $criteria = [
            'user' => $userId,
        ];
        $result = $em->getRepository(CForumThread::class)->findBy($criteria);
        $cForumThreadList = [];
        /** @var CForumThread $item */
        foreach ($result as $item) {
            $date = $item->getThreadDate()->format($dateFormat);
            $list = [
                'Title: '.$item->getThreadTitle(),
                'Creation date: '.$date,
            ];
            $cForumThreadList[] = implode(', ', $list);
        }
        // CForumAttachment
        /*$criteria = [
            'threadPosterId' => $userId,
        ];
        $result = $em->getRepository('ChamiloCourseBundle:CForumAttachment')->findBy($criteria);
        $cForumThreadList = [];
        * @var CForumThread $item
        foreach ($result as $item) {
            $list = [
                'Title: '.$item->getThreadTitle(),
                'Creation date: '.$item->getThreadDate()->format($dateFormat),
            ];
            $cForumThreadList[] = implode(', ', $list);
        }*/

        // cGroupRelUser
        $criteria = [
            'user' => $userId,
        ];
        $result = $em->getRepository(CGroupRelUser::class)->findBy($criteria);
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
        $criteria = [
            'user' => $userId,
        ];
        $result = $em->getRepository(CAttendanceSheet::class)->findBy($criteria);
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
        $criteria = [
            'user' => $userId,
        ];
        $result = $em->getRepository(CAttendanceResult::class)->findBy($criteria);
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
        $criteria = [
            'sender' => $userId,
        ];
        $result = $em->getRepository(Message::class)->findBy($criteria);
        $messageList = [];
        /** @var Message $item */
        foreach ($result as $item) {
            $date = $item->getSendDate()->format($dateFormat);
            $userName = '';
            if ($item->getReceivers()) {
                foreach ($item->getReceivers() as $receiver) {
                    $userName = ', '.$receiver->getUsername();
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
        $criteria = [
            'user' => $userId,
        ];
        $result = $em->getRepository(CSurveyAnswer::class)->findBy($criteria);
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
        $criteria = [
            'uploaderId' => $userId,
        ];
        $result = $em->getRepository(CDropboxFile::class)->findBy($criteria);
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
        $criteria = [
            'userId' => $userId,
        ];
        $result = $em->getRepository(CDropboxPerson::class)->findBy($criteria);
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
        $criteria = [
            'authorUserId' => $userId,
        ];
        $result = $em->getRepository(CDropboxFeedback::class)->findBy($criteria);
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
        $criteria = [
            'user' => $userId,
        ];
        $result = $em->getRepository(CNotebook::class)->findBy($criteria);
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
        $criteria = [
            'user' => $userId,
        ];
        $result = $em->getRepository(CLpView::class)->findBy($criteria);
        $cLpView = [];
        /** @var CLpView $item */
        foreach ($result as $item) {
            $list = [
                //'Id #'.$item->getId(),
                'LP #'.$item->getLp()->getIid(),
                'Progress: '.$item->getProgress(),
                //'Course #'.$item->getCId(),
                //'Session #'.$item->getSessionId(),
            ];
            $cLpView[] = implode(', ', $list);
        }

        // CStudentPublication
        $criteria = [
            'user' => $userId,
        ];
        $result = $em->getRepository(CStudentPublication::class)->findBy($criteria);
        $cStudentPublication = [];
        /** @var CStudentPublication $item */
        foreach ($result as $item) {
            $list = [
                'Title: '.$item->getTitle(),
                //'URL: '.$item->getTitle(),
            ];
            $cStudentPublication[] = implode(', ', $list);
        }

        // CStudentPublicationComment
        $criteria = [
            'user' => $userId,
        ];
        $result = $em->getRepository(CStudentPublicationComment::class)->findBy($criteria);
        $cStudentPublicationComment = [];
        /** @var CStudentPublicationComment $item */
        foreach ($result as $item) {
            $date = $item->getSentAt()->format($dateFormat);
            $list = [
                'Commment: '.$item->getComment(),
                'File '.$item->getFile(),
                //'Course # '.$item->getCId(),
                'Date: '.$date,
            ];
            $cStudentPublicationComment[] = implode(', ', $list);
        }

        // CWiki
        $criteria = [
            'userId' => $userId,
        ];
        $result = $em->getRepository(CWiki::class)->findBy($criteria);
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
        $criteria = [
            'insertUserId' => $userId,
        ];
        $result = $em->getRepository(Ticket::class)->findBy($criteria);
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
        $criteria = [
            'insertUserId' => $userId,
        ];
        $result = $em->getRepository(TicketMessage::class)->findBy($criteria);
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
        $criteria = [
            'feedbackGiver' => $userId,
        ];
        $result = $em->getRepository(SkillRelUserComment::class)->findBy($criteria);
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
        $criteria = [
            'user' => $userId,
        ];
        $result = $em->getRepository(UserRelCourseVote::class)->findBy($criteria);
        $userRelCourseVote = [];
        /** @var UserRelCourseVote $item */
        foreach ($result as $item) {
            $list = [
                'Course #'.$item->getCourse()->getId(),
                //'Session #'.$item->getSession()->getId(),
                'Vote: '.$item->getVote(),
            ];
            $userRelCourseVote[] = implode(', ', $list);
        }

        /*$user->setDropBoxSentFiles(
            [
                'Friends' => $friendList,
                'Events' => $eventList,
                'GradebookCertificate' => $gradebookCertificate,

                'TrackECourseAccess' => $trackECourseAccessList,
                'TrackELogin' => $trackELoginList,
                'TrackEAccess' => $trackEAccessList,
                'TrackEDefault' => $trackEDefault,
                'TrackEOnline' => $trackEOnlineList,
                'TrackEUploads' => $trackEUploads,
                'TrackELastaccess' => $trackELastaccess,
                'GradebookResult' => $gradebookResult,
                'Downloads' => $trackEDownloads,
                'UserCourseCategory' => $userCourseCategory,
                'SkillRelUserComment' => $skillRelUserComment,
                'UserRelCourseVote' => $userRelCourseVote,

                // courses
                'AttendanceResult' => $cAttendanceResult,
                'Blog' => $cBlog,
                'DocumentsAdded' => $documents,
                'Chat' => $chatFiles,
                'ForumPost' => $cForumPostList,
                'ForumThread' => $cForumThreadList,
                'TrackEExercises' => $trackEExercises,
                'TrackEAttempt' => $trackEAttempt,

                'GroupRelUser' => $cGroupRelUser,
                'Message' => $messageList,
                'Survey' => $cSurveyAnswer,
                'StudentPublication' => $cStudentPublication,
                'StudentPublicationComment' => $cStudentPublicationComment,
                'DropboxFile' => $cDropboxFile,
                'DropboxPerson' => $cDropboxPerson,
                'DropboxFeedback' => $cDropboxFeedback,

                'LpView' => $cLpView,
                'Notebook' => $cNotebook,

                'Wiki' => $cWiki,
                // Tickets

                'Ticket' => $ticket,
                'TicketMessage' => $ticketMessage,
            ]
        );*/

        //$user->setDropBoxReceivedFiles([]);
        //$user->setGroups([]);
        //$user->setCurriculumItems([]);

        /*$portals = $user->getPortals();
        if (!empty($portals)) {
            $list = [];
            /** @var AccessUrlRelUser $portal */
        /*foreach ($portals as $portal) {
            $portalInfo = UrlManager::get_url_data_from_id($portal->getUrl()->getId());
            $list[] = $portalInfo['url'];
        }
        }
        $user->setPortals($list);*/

        /*$skillRelUserList = $user->getAchievedSkills();
        $list = [];
        foreach ($skillRelUserList as $skillRelUser) {
            $list[] = $skillRelUser->getSkill()->getName();
        }
        $user->setAchievedSkills($list);
        $user->setCommentedUserSkills([]);*/

        //$extraFieldValues = new \ExtraFieldValue('user');

        $lastLogin = $user->getLastLogin();
        /*if (null === $lastLogin) {
            $login = $this->userRepository->getLastLogin($user);
            if (null !== $login) {
                $lastLogin = $login->getLoginDate();
            }
        }*/
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
}
