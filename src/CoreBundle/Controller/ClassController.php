<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use UserGroupModel;

class ClassController extends AbstractController
{
    #[Route('/main/admin/classes/{id}/overview', name: 'class_overview')]
    public function overview(int $id): Response
    {
        $usergroupLib = new UserGroupModel();
        $usergroup = $usergroupLib->get($id);

        $data = $usergroupLib->getUsersAndCoursesSubscribedToAUserGroup($id);

        return $this->render('@ChamiloCore/Class/overview.html.twig', [
            'usergroupName' => $usergroup['title'],
            'usersSubscribedToUsergroup' => $data['usersSubscribedToUsergroup'],
            'coursesSubscribedToUsergroup' => $data['coursesSubscribedToUsergroup'],
            'warning' => $data['warning'],
            'error' => $data['error'],
        ]);
    }
}
