<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use UserGroupModel;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin')]
class UsergroupController extends AbstractController
{
    #[Route('/usergroup/{id}/overview', name: 'class_overview')]
    public function overview(int $id): Response
    {
        $usergroupLib = new UserGroupModel();
        $usergroup = $usergroupLib->get($id);

        if (empty($usergroup)) {
            throw $this->createNotFoundException('Class not found');
        }

        $data = $usergroupLib->getUsersAndCoursesSubscribedToAUserGroup($id);

        return $this->render('@ChamiloCore/Usergroup/overview.html.twig', [
            'usergroupName' => $usergroup['title'],
            'usersSubscribedToUsergroup' => $data['usersSubscribedToUsergroup'],
            'coursesSubscribedToUsergroup' => $data['coursesSubscribedToUsergroup'],
            'warning' => $data['warning'],
            'error' => $data['error'],
        ]);
    }
}
