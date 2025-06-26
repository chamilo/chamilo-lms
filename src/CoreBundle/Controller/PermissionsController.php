<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\PermissionRelRole;
use Chamilo\CoreBundle\Form\PermissionType;
use Chamilo\CoreBundle\Helpers\PermissionHelper;
use Chamilo\CoreBundle\Repository\PermissionRelRoleRepository;
use Chamilo\CoreBundle\Repository\PermissionRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/permissions')]
class PermissionsController extends AbstractController
{
    public function __construct(
        private PermissionHelper $permissionHelper
    ) {}

    #[IsGranted('ROLE_ADMIN')]
    #[Route('', name: 'permissions')]
    public function index(
        PermissionRepository $permissionRepo,
        PermissionRelRoleRepository $permissionRelRoleRepo,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $permissions = $permissionRepo->findAll();
        $roles = $this->permissionHelper->getUserRoles();

        if ($request->isMethod('POST')) {
            $data = $request->request->all('permissions');
            foreach ($permissions as $permission) {
                foreach ($roles as $role) {
                    $checkboxValue = isset($data[$permission->getSlug()][$role]);
                    $permRelRole = $permissionRelRoleRepo->findOneBy(['permission' => $permission, 'roleCode' => $role]);

                    if ($checkboxValue) {
                        if (!$permRelRole) {
                            $permRelRole = new PermissionRelRole();
                            $permRelRole->setPermission($permission);
                            $permRelRole->setRoleCode($role);
                        }
                        $permRelRole->setChangeable(true);
                        $permRelRole->setUpdatedAt(new DateTime());
                        $em->persist($permRelRole);
                    } else {
                        if ($permRelRole) {
                            $em->remove($permRelRole);
                        }
                    }
                }
            }
            $em->flush();

            return $this->redirectToRoute('permissions');
        }

        $forms = [];
        foreach ($permissions as $permission) {
            $defaultData = [];
            foreach ($roles as $role) {
                $permRelRole = $permissionRelRoleRepo->findOneBy(['permission' => $permission, 'roleCode' => $role]);
                $defaultData[$role] = $permRelRole ? $permRelRole->isChangeable() : false;
            }

            $form = $this->createForm(PermissionType::class, $defaultData, ['roles' => $roles]);
            $forms[$permission->getSlug()] = $form->createView();
        }

        return $this->render('@ChamiloCore/Permission/index.html.twig', [
            'permissions' => $permissions,
            'forms' => $forms,
            'roles' => $roles,
        ]);
    }

    #[Route('/is_allowed_to_edit', name: 'is_allowed_to_edit')]
    public function isAllowedToEdit(Request $request): Response
    {
        $tutor = $request->query->getBoolean('tutor');
        $coach = $request->query->getBoolean('coach');
        $sessionCoach = $request->query->getBoolean('sessioncoach');
        $checkStudentView = $request->query->getBoolean('checkstudentview');

        $isAllowed = api_is_allowed_to_edit(
            $tutor,
            $coach,
            $sessionCoach,
            $checkStudentView
        );

        return $this->json([
            'isAllowedToEdit' => $isAllowed,
        ]);
    }
}
