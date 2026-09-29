<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\UsergroupRelCourse;
use Chamilo\CoreBundle\Entity\UsergroupRelSession;
use Chamilo\CoreBundle\Security\Authorization\Voter\CourseVoter;
use Chamilo\CoreBundle\State\CourseClass\CourseClassManager;
use CourseManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use UserGroupModel;

/**
 * @author Julio Montoya <gugli100@gmail.com>
 */
#[Route('/user')]
class UserController extends AbstractController
{
    #[Route(path: '/usergroup_overview', name: 'overview_class', methods: ['GET'])]
    public function overview(
        Request $request,
        EntityManagerInterface $em,
        CourseClassManager $courseClassManager,
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $usergroupId = $request->query->getInt('usergroup');
        $courseId = $request->query->getInt('course');
        $sessionId = $request->query->getInt('sid');

        // Resolve the target course and require access to it: the roster must not be
        // disclosed for a course the caller cannot view (cross-course IDOR).
        $course = $em->getRepository(Course::class)->find($courseId);
        if (!$course instanceof Course) {
            throw $this->createNotFoundException('Course not found');
        }
        $this->denyAccessUnlessGranted(CourseVoter::VIEW, $course);

        $session = null;
        if ($sessionId > 0) {
            $session = $em->getRepository(Session::class)->find($sessionId);
            if (!$session instanceof Session || !$session->hasCourse($course)) {
                throw $this->createNotFoundException('Session not found');
            }
        }

        $courseClassManager->assertCanManage($course, $session);
        $usergroupEntity = $courseClassManager->findAccessibleGroup($usergroupId);

        $relation = $session instanceof Session
            ? $em->getRepository(UsergroupRelSession::class)->findOneBy([
                'usergroup' => $usergroupEntity,
                'session' => $session,
            ])
            : $em->getRepository(UsergroupRelCourse::class)->findOneBy([
                'usergroup' => $usergroupEntity,
                'course' => $course,
            ]);
        if (null === $relation) {
            throw $this->createNotFoundException('Class not found in this course context');
        }

        $usergroupLib = new UserGroupModel();
        $usergroup = $usergroupLib->get($usergroupId);

        if (empty($usergroup)) {
            throw $this->createNotFoundException('Class not found');
        }

        $courseLib = new CourseManager();
        $courseCode = $courseLib->get_course_code_from_course_id($courseId);
        $courseName = $courseLib->getCourseNameFromCode($courseCode);

        if (empty($courseName)) {
            throw $this->createNotFoundException('Course not found');
        }

        $data = $usergroupLib->getUsersInAndOutOfCourse($usergroupId, $courseId);
        $courseContextQuery = 'cid='.$courseId.($sessionId > 0 ? '&sid='.$sessionId : '');
        $courseUsersUrl = '/main/user/user.php?'.$courseContextQuery;
        $courseClassesUrl = '/main/user/class.php?'.$courseContextQuery;
        if ($course->hasResourceNode()) {
            $courseUsersBaseUrl = '/resources/course-users/'.$course->getResourceNode()->getId().'/';
            $courseUsersUrl = $courseUsersBaseUrl.'?'.$courseContextQuery;
            $courseClassesUrl = $courseUsersBaseUrl.'classes?'.$courseContextQuery;
        }

        $breadcrumb = json_encode([
            ['name' => get_lang('My courses'), 'url' => '/courses'],
            ['name' => $courseName, 'url' => '/course/'.$courseId.'/home'],
            ['name' => get_lang('Users'), 'url' => $courseUsersUrl],
            ['name' => get_lang('Classes'), 'url' => $courseClassesUrl],
            ['name' => $usergroup['title'], 'url' => '#'],
            ['name' => get_lang('Overview'), 'url' => ''],
        ]);

        return $this->render('@ChamiloCore/User/usergroup_overview.html.twig', [
            'legacy_breadcrumb' => $breadcrumb,
            'courseId' => $courseId,
            'courseName' => $courseName,
            'courseClassesUrl' => $courseClassesUrl,
            'usergroupName' => $usergroup['title'],
            'usersSubscribedToCourse' => $data['usersSubscribedToCourse'],
            'usersNotSubscribedToCourse' => $data['usersNotSubscribedToCourse'],
            'error' => $data['error'],
            'warning' => $data['warning'],
        ]);
    }
}
