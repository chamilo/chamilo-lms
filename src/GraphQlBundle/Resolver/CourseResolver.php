<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\GraphQlBundle\Resolver;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CourseBundle\Entity\CAnnouncement;
use Chamilo\CourseBundle\Entity\CForumCategory;
use Chamilo\CourseBundle\Entity\CForumForum;
use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\CourseBundle\Entity\CItemProperty;
use Chamilo\CourseBundle\Entity\CLpCategory;
use Chamilo\CourseBundle\Entity\CTool;
use Chamilo\CourseBundle\Repository\CNotebookRepository;
use Chamilo\GraphQlBundle\Traits\GraphQLTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Firebase\JWT\JWT;
use Overblog\GraphQLBundle\Definition\Argument;
use Overblog\GraphQLBundle\Error\UserError;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * Class CourseResolver.
 *
 * @package Chamilo\GraphQlBundle\Resolver
 */
class CourseResolver implements ContainerAwareInterface
{
    use GraphQLTrait;

    /**
     * @return array
     */
    public static function getAvailableTools()
    {
        return [
            TOOL_COURSE_DESCRIPTION => 'ToolDescription',
            TOOL_ANNOUNCEMENT => 'ToolAnnouncements',
            TOOL_NOTEBOOK => 'ToolNotebook',
            TOOL_FORUM => 'ToolForums',
            TOOL_CALENDAR_EVENT => 'ToolAgenda',
            TOOL_DOCUMENT => 'ToolDocuments',
            TOOL_LEARNPATH => 'ToolLearningPath',
        ];
    }

    /**
     * @param Course   $course
     * @param Argument $args
     *
     * @return string|null
     */
    public function getPicture(Course $course, Argument $args)
    {
        return \CourseManager::getPicturePath($course, $args['fullSize']);
    }

    /**
     * @param Course       $course
     * @param Argument     $args
     * @param \ArrayObject $context
     *
     * @return array
     */
    public function getTeachers(Course $course, Argument $args, \ArrayObject $context): array
    {
        if ($context->offsetExists('session')) {
            /** @var Session $session */
            $session = $context->offsetGet('session');

            if ($session) {
                $coaches = [];
                $coachSubscriptions = $session->getUserCourseSubscriptionsByStatus($course, Session::COACH);

                /** @var SessionRelCourseRelUser $coachSubscription */
                foreach ($coachSubscriptions as $coachSubscription) {
                    $coaches[] = $coachSubscription->getUser();
                }

                return $coaches;
            }
        }

        $courseRepo = $this->em->getRepository('ChamiloCoreBundle:Course');
        $teachers = $courseRepo
            ->getSubscribedTeachers($course)
            ->getQuery()
            ->getResult();

        return $teachers;
    }

    /**
     * @param Course       $course
     * @param Argument     $args
     * @param \ArrayObject $context
     *
     * @return ArrayCollection
     */
    public function getTools(Course $course, Argument $args, \ArrayObject $context): ArrayCollection
    {
        $session = null;

        if ($context->offsetExists('session')) {
            /** @var Session $session */
            $session = $context->offsetGet('session');
        }

        if (empty($args['type'])) {
            return $course
                ->getTools($session)
                ->filter(function (CTool $tool) {
                    return array_key_exists($tool->getName(), self::getAvailableTools());
                });
        }

        $criteria = Criteria::create()
            ->where(
                Criteria::expr()->eq('name', $args['type'])
            );

        return $course->getTools($session)->matching($criteria);
    }

    /**
     * @param CTool        $tool
     * @param \ArrayObject $context
     *
     * @return array
     */
    public function getDescriptions(Ctool $tool, \ArrayObject $context)
    {
        /** @var Session $session */
        $session = $context->offsetGet('session');
        $cd = new \CourseDescription();
        $cd->set_course_id($tool->getCourse()->getId());

        if ($session) {
            $cd->set_session_id($session->getId());
        }

        $descriptions = $cd->get_description_data();

        if (empty($descriptions)) {
            return [];
        }

        $qb = $this->em->createQueryBuilder();
        $qb
            ->select('d')
            ->from('ChamiloCourseBundle:CCourseDescription', 'd')
            ->where(
                $qb->expr()->in('d.id', array_keys($descriptions['descriptions']))
            );

        return $qb->getQuery()->getResult();
    }

    /**
     * @param CTool        $tool
     * @param \ArrayObject $context
     *
     * @return array
     */
    public function getAnnouncements(CTool $tool, \ArrayObject $context): array
    {
        $announcementManager = $this->container->get('chamilo_course.entity.manager.announcement_manager');
        $announcementsInfo = $announcementManager->getAnnouncements(
            $this->getCurrentUser(),
            $tool->getCourse(),
            null,
            $context->offsetGet('session'),
            api_get_course_setting('allow_user_edit_announcement') === 'true',
            api_get_configuration_value('hide_base_course_announcements_in_group') === true
        );

        $announcements = [];

        for ($z = 0; $z < count($announcementsInfo); $z += 2) {
            $announcements[] = self::getAnnouncementObject($announcementsInfo[$z], $announcementsInfo[$z + 1]);
        }

        return $announcements;
    }

    /**
     * @param int          $id
     * @param \ArrayObject $context
     *
     * @return \stdClass
     */
    public function getAnnouncement($id, \ArrayObject $context)
    {
        $announcementInfo = \AnnouncementManager::getAnnouncementInfoById(
            $id,
            $context->offsetGet('course')->getId(),
            $this->getCurrentUser()->getId()
        );

        if (empty($announcementInfo)) {
            throw new UserError($this->translator->trans('Announcement not found.'));
        }

        return self::getAnnouncementObject($announcementInfo['announcement'], $announcementInfo['item_property']);
    }

    /**
     * @param \ArrayObject $context
     *
     * @return array
     */
    public function getNotes(\ArrayObject $context): array
    {
        /** @var CNotebookRepository $notebooksRepo */
        $notebooksRepo = $this->em->getRepository('ChamiloCourseBundle:CNotebook');
        $notebooks = $notebooksRepo->findByUser(
            $this->getCurrentUser(),
            $context->offsetGet('course'),
            $context->offsetGet('session')
        );

        return $notebooks;
    }

    /**
     * @param \ArrayObject $context
     *
     * @return array
     */
    public function getForumCategories(\ArrayObject $context): array
    {
        /** @var Course $course */
        $course = $context->offsetGet('course');
        /** @var Session $session */
        $session = $context->offsetGet('session');

        $catRepo = $this->em->getRepository('ChamiloCourseBundle:CForumCategory');
        $cats = $catRepo->findAllInCourse(false, $course, $session);

        return $cats;
    }

    /**
     * @param CForumCategory $category
     * @param \ArrayObject   $context
     *
     * @return array
     */
    public function getForums(CForumCategory $category, \ArrayObject $context): array
    {
        /** @var Course $course */
        $course = $context->offsetGet('course');
        /** @var Session $session */
        $session = $context->offsetGet('session');

        $forumRepo = $this->em->getRepository('ChamiloCourseBundle:CForumForum');
        $forums = $forumRepo->findAllInCourseByCategory(false, $category, $course, $session);

        return $forums;
    }

    /**
     * @param int          $id
     * @param \ArrayObject $context
     *
     * @return CForumForum
     */
    public function getForum($id, \ArrayObject $context)
    {
        /** @var Course $course */
        $course = $context->offsetGet('course');

        $forumRepo = $this->em->getRepository('ChamiloCourseBundle:CForumForum');
        $forum = $forumRepo->findOneInCourse($id, $course);

        if (empty($forum)) {
            throw new UserError($this->translator->trans('Forum not found in this course.'));
        }

        return $forum;
    }

    /**
     * @param CForumForum  $forum
     * @param \ArrayObject $context
     *
     * @return array
     */
    public function getThreads(CForumForum $forum, \ArrayObject $context): array
    {
        /** @var Course $course */
        $course = $context->offsetGet('course');
        /** @var Session $session */
        $session = $context->offsetGet('session');

        $threadRepo = $this->em->getRepository('ChamiloCourseBundle:CForumThread');
        $threads = $threadRepo->findAllInCourseByForum(false, $forum, $course, $session);

        return $threads;
    }

    /**
     * @param int          $id
     * @param \ArrayObject $context
     *
     * @return CForumThread
     */
    public function getThread($id, \ArrayObject $context)
    {
        /** @var Course $course */
        $course = $context->offsetGet('course');
        /** @var Session $session */
        $session = $context->offsetGet('session');

        $threadRepo = $this->em->getRepository('ChamiloCourseBundle:CForumThread');
        $thread = $threadRepo->findOneInCourse($id, $course, $session);

        if (empty($thread)) {
            throw new UserError($this->translator->trans('Forum thread not found in this course.'));
        }

        return $thread;
    }

    /**
     * @param CForumThread $thread
     * @param \ArrayObject $context
     *
     * @return array
     */
    public function getPosts(CForumThread $thread, \ArrayObject $context)
    {
        /** @var Course $course */
        $course = $context->offsetGet('course');

        $postRepo = $this->em->getRepository('ChamiloCourseBundle:CForumPost');
        $posts = $postRepo->findAllInCourseByThread(
            api_is_allowed_to_edit(false, true),
            api_is_allowed_to_edit(),
            $thread,
            $course,
            $this->getCurrentUser()
        );

        return $posts;
    }

    /**
     * @param \ArrayObject $context
     *
     * @return array
     */
    public function getAgenda(\ArrayObject $context): array
    {
        /** @var Session|null $session */
        $session = $context->offsetGet('session');
        /** @var Course $course */
        $course = $context->offsetGet('course');

        $agenda = new \Agenda(
            'course',
            $this->getCurrentUser()->getId(),
            $course->getId(),
            $session ? $session->getId() : 0
        );
        $result = $agenda->parseAgendaFilter(null);
        $firstDay = new \DateTime('now', new \DateTimeZone('UTC'));
        $firstDay->modify('first day of this month');
        $firstDay->setTime(0, 0);
        $lastDay = new \DateTime('now', new \DateTimeZone('UTC'));
        $lastDay->modify('last day of this month');
        $lastDay->setTime(0, 0);

        $groupId = current($result['groups']);
        $userId = current($result['users']);

        $events = $agenda->getEvents(
            $firstDay->getTimestamp(),
            $lastDay->getTimestamp(),
            $course->getId(),
            $groupId,
            $userId,
            'array'
        );

        return $events;
    }

    /**
     * @param int          $dirId
     * @param \ArrayObject $context
     *
     * @return array
     */
    public function getDocuments($dirId, \ArrayObject $context): array
    {
        $path = '/';
        /** @var Course $course */
        $course = $context->offsetGet('course');
        /** @var Session $session */
        $session = $context->offsetGet('session');

        if (!empty($dirId)) {
            $directory = $this->em->getRepository('ChamiloCourseBundle:CDocument')->find($dirId);

            if (empty($directory)) {
                throw new UserError($this->translator->trans('Directory not found.'));
            }

            if (empty($directory->getCourse())) {
                throw new UserError('The directory has not been assigned to a course.');
            }

            if ($directory->getCourse()->getId() !== $course->getId()) {
                throw new UserError('The directory has not been assidned to this course.');
            }

            $path = $directory->getPath();
        }

        $documents = \DocumentManager::getAllDocumentData(
            api_get_course_info_by_id($course->getId()),
            $path,
            0,
            null,
            false,
            false,
            $session ? $session->getId() : 0,
            $this->getCurrentUser()
        );

        if (empty($documents)) {
            return [];
        }

        $webPath = api_get_path(WEB_CODE_PATH).'document/document.php?';

        $results = array_map(
            function ($documentInfo) use ($webPath, $course, $session) {
                $icon = $documentInfo['filetype'] == 'file'
                    ? choose_image($documentInfo['path'])
                    : chooseFolderIcon($documentInfo['path']);

                return [
                    'id' => $documentInfo['id'],
                    'fileType' => $documentInfo['filetype'],
                    'title' => $documentInfo['title'],
                    'comment' => $documentInfo['comment'],
                    'path' => $documentInfo['path'],
                    'icon' => $icon,
                    'size' => format_file_size($documentInfo['size']),
                    'url' => $webPath.http_build_query(
                            [
                                'username' => $this->getCurrentUser()->getUsername(),
                                'api_key' => '', //$this->apiKey,
                                'cidReq' => $course->getCode(),
                                'id_session' => $session ? $session->getId() : 0,
                                'gidReq' => 0,
                                'gradebook' => 0,
                                'origin' => '',
                                'action' => 'download',
                                'id' => $documentInfo['id'],
                            ]
                        ),
                ];
            },
            $documents
        );

        return $results;
    }

    /**
     * @param \ArrayObject $context
     *
     * @return array
     */
    public function getLearnpathCategories(\ArrayObject $context): array
    {
        /** @var Course $course */
        $course = $context->offsetGet('course');

        $none = new CLpCategory();
        $none
            ->setId(0)
            ->setCId($course->getId())
            ->setName($this->translator->trans('Without category.'))
            ->setPosition(0);

        $categories = \learnpath::getCategories($course->getId());

        array_unshift($categories, $none);

        return $categories;
    }

    /**
     * @param CLpCategory  $category
     * @param \ArrayObject $context
     *
     * @return array
     */
    public function getLearnpathsByCategory(CLpCategory $category, \ArrayObject $context): array
    {
        $user = $this->getCurrentUser();
        /** @var Course $course */
        $course = $context->offsetGet('course');
        /** @var Session $session */
        $session = $context->offsetGet('session');
        $sessionId = $session ? $session->getId() : 0;

        $lpList = new \LearnpathList(
            $user->getId(),
            $course->getCode(),
            $sessionId,
            null,
            false,
            $category->getId()
        );

        $flatList = $lpList->get_flat_list();
        $lps = [];

        foreach ($flatList as $lpId => $lpInfo) {
            if (empty($lpInfo['lp_visibility'])) {
                continue;
            }

            if (
                !\learnpath::is_lp_visible_for_student($lpId, $user->getId(), $course->getCode(), $sessionId)
            ) {
                continue;
            }

            $timeLimits = !empty($lpInfo['expired_on']);

            if ($timeLimits) {
                if (!empty($lpInfo['publicated_on']) && !empty($lpInfo['expired_on'])) {
                    $utc = new \DateTimeZone('UTC');

                    $starTime = new \DateTime($lpInfo['publicated_on'], $utc);
                    $endTime = new \DateTime($lpInfo['expired_on'], $utc);
                    $now = new \DateTime('now', $utc);

                    $isActived = $now > $starTime && $endTime > $now;

                    if (!$isActived) {
                        continue;
                    }
                }
            }

            $progress = \learnpath::getProgress($lpId, $user->getId(), $course->getId(), $sessionId);

            $lps[] = [
                'id' => $lpId,
                'title' => \Security::remove_XSS($lpInfo['lp_name']),
                'progress' => (int) $progress,
                'url' => $this->generateLpUrl($lpId, $course, $session),
            ];
        }

        return $lps;
    }

    /**
     * @param CAnnouncement $a
     * @param CItemProperty $ip
     *
     * @return \stdClass
     */
    private static function getAnnouncementObject(CAnnouncement $a, CItemProperty $ip)
    {
        $announcement = new \stdClass();
        $announcement->id = $a->getIid();
        $announcement->title = $a->getTitle();
        $announcement->content = $a->getContent();
        $announcement->author = $ip->getInsertUser();
        $announcement->lastUpdateDate = $ip->getLasteditDate();

        return $announcement;
    }

    /**
     * @param int          $lpId
     * @param Course       $course
     * @param Session|null $session
     *
     * @return string
     */
    private function generateLpUrl($lpId, Course $course, Session $session = null)
    {
        $user = $this->getCurrentUser();
        $secret = $this->container->getParameter('secret');
        $time = time();

        $payload = [
            'iat' => $time,
            'exp' => $time + (60 * 30 * 1),
            'data' => [
                'user' => $user->getId(),
                'lp' => (int) $lpId,
                'course' => [
                    'id' => $course->getId(),
                    'code' => $course->getCode(),
                ],
                'session' => $session ? $session->getId() : null,
            ],
        ];

        $token = JWT::encode($payload, $secret, 'HS384');

        return $this->container->get('router')->generate('chamilo_graphql_learnpath', ['token' => $token]);
    }
}
