<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\PermissionRelRole;
use Chamilo\CoreBundle\Form\PermissionType;
use Chamilo\CoreBundle\Repository\PermissionRelRoleRepository;
use Chamilo\CoreBundle\Repository\PermissionRepository;
use Chamilo\CoreBundle\ServiceHelper\PermissionServiceHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class PermissionController extends AbstractController
{
    public function __construct(
        private PermissionServiceHelper $permissionServiceHelper
    ) {}

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/permissions', name: 'permissions')]
    public function index(
        PermissionRepository $permissionRepo,
        PermissionRelRoleRepository $permissionRelRoleRepo,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $permissions = $permissionRepo->findAll();
        $roles = $this->permissionServiceHelper->getUserRoles();

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
                        $permRelRole->setUpdatedAt(new \DateTime());
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
            'roles' => $roles
        ]);
    }
}
