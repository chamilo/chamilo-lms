<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use CourseManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use UserGroupModel;

/**
 * @author Julio Montoya <gugli100@gmail.com>
 */
#[Route('/user')]
class UserController extends AbstractController
{
    // #[Route(path: '/overview', name: 'overview_class', methods: ['GET'])]
    #[Route(path: '/usergroup_overview', name: 'overview_class', methods: ['GET'])]
    public function overview(Request $request): Response
    {
        $usergroupId = $request->query->get('usergroup');
        $courseId = $request->query->get('course');

        $usergroupLib = new UserGroupModel();
        $usergroup = $usergroupLib->get($usergroupId);

        $courseLib = new CourseManager();
        $courseName = $courseLib->getCourseNameFromCode($courseLib->get_course_code_from_course_id($courseId));

        $data = $usergroupLib->getUsersInAndOutOfCourse($usergroupId, $courseId);

        $breadcrumb = json_encode([
            ['name' => get_lang('My courses'),  'url' => '/courses'],
            ['name' => $courseName,             'url' => '/course/'.$courseId.'/home'],
            ['name' => get_lang('Users'),       'url' => '/main/user/user.php?cid='.$courseId],
            ['name' => get_lang('Classes'),     'url' => '/main/user/class.php?cid='.$courseId],
            ['name' => $usergroup['title'],     'url' => '#'],
            ['name' => get_lang('Overview'),    'url' => ''],
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
    #[Route(path: '/{username}', methods: ['GET'], name: 'chamilo_core_user_profile')]
    public function profile(string $username, UserRepository $userRepository, IllustrationRepository $illustrationRepository): Response
    {
        $user = $userRepository->findByUsername($username);

        if (!\is_object($user) || !$user instanceof UserInterface) {
            throw $this->createAccessDeniedException('This user does not have access to this section');
        }

        $url = $illustrationRepository->getIllustrationUrl($user);

        return $this->render('@ChamiloCore/User/profile.html.twig', [
            'user' => $user,
            'illustration_url' => $url,
        ]);
    }

}
