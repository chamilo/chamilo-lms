<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use CourseManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use UserGroupModel;

/**
 * @author Julio Montoya <gugli100@gmail.com>
 */
#[Route('/user')]
class UserController extends AbstractController
{
    #[Route(path: '/usergroup_overview', name: 'overview_class', methods: ['GET'])]
    public function overview(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $usergroupId = $request->query->getInt('usergroup');
        $courseId = $request->query->getInt('course');

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

        $breadcrumb = json_encode([
            ['name' => get_lang('My courses'), 'url' => '/courses'],
            ['name' => $courseName, 'url' => '/course/'.$courseId.'/home'],
            ['name' => get_lang('Users'), 'url' => '/main/user/user.php?cid='.$courseId],
            ['name' => get_lang('Classes'), 'url' => '/main/user/class.php?cid='.$courseId],
            ['name' => $usergroup['title'], 'url' => '#'],
            ['name' => get_lang('Overview'), 'url' => ''],
        ]);

        return $this->render('@ChamiloCore/User/usergroup_overview.html.twig', [
            'legacy_breadcrumb' => $breadcrumb,
            'courseId' => $courseId,
            'courseName' => $courseName,
            'usergroupName' => $usergroup['title'],
            'usersSubscribedToCourse' => $data['usersSubscribedToCourse'],
            'usersNotSubscribedToCourse' => $data['usersNotSubscribedToCourse'],
            'error' => $data['error'],
            'warning' => $data['warning'],
        ]);
    }

    /**
     * Public profile.
     */
    #[Route(path: '/{username}', name: 'chamilo_core_user_profile', methods: ['GET'])]
    public function profile(string $username, UserRepository $userRepository, IllustrationRepository $illustrationRepository): Response
    {
        $user = $userRepository->findByUsername($username);

        if (!\is_object($user) || !$user instanceof UserInterface) {
            throw $this->createAccessDeniedException('This user does not have access to this section');
        }

        $url = $illustrationRepository->getIllustrationUrl($user);

        $sanitize = static fn (?string $v): string => '' !== ($v ?? '') ? \Security::remove_XSS($v, STUDENT) : '';

        return $this->render('@ChamiloCore/User/profile.html.twig', [
            'user' => $user,
            'illustration_url' => $url,
            'competences' => $sanitize($user->getCompetences()),
            'openarea' => $sanitize($user->getOpenarea()),
            'teach' => $sanitize($user->getTeach()),
            'diplomas' => $sanitize($user->getDiplomas()),
        ]);
    }
}
