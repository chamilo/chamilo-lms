<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use UserGroupModel;

#[Route('/admin')]
class UsergroupController extends AbstractController
{
    #[Route('/usergroup/{id}/overview', name: 'class_overview')]
    public function overview(int $id): Response
    {
        $usergroupLib = new UserGroupModel();
        $usergroup = $usergroupLib->get($id);

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
