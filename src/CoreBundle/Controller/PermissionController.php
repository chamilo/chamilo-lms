<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\PermissionRelRole;
use Chamilo\CoreBundle\Form\PermissionType;
use Chamilo\CoreBundle\Repository\PermissionRelRoleRepository;
use Chamilo\CoreBundle\Repository\PermissionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class PermissionController extends AbstractController
{

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/permissions/test', name: 'permissions_test')]
    public function testPermissions(): Response
    {
        // Test roles and permission slug
        $roles = ['ROLE_STUDENT', 'ROLE_TEACHER'];
        $permissionSlug = 'analytics:view';

        // Call the api_get_permission function and log the result
        $hasPermission = api_get_permission($permissionSlug, $roles);
        error_log('Permission check for ' . $permissionSlug . ' with roles ' . implode(', ', $roles) . ': ' . ($hasPermission ? 'true' : 'false'));

        // Return a simple response for testing purposes
        return new Response('<html><body>Permission check result: ' . ($hasPermission ? 'true' : 'false') . '</body></html>');
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/permissions', name: 'permissions')]
    public function index(
        PermissionRepository $permissionRepo,
        PermissionRelRoleRepository $permissionRelRoleRepo,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $permissions = $permissionRepo->findAll();
        $roles = ['ROLE_INVITEE', 'ROLE_STUDENT', 'ROLE_TEACHER', 'ROLE_ADMIN', 'ROLE_SUPER_ADMIN', 'ROLE_GLOBAL_ADMIN', 'ROLE_RRHH', 'ROLE_QUESTION_MANAGER', 'ROLE_SESSION_MANAGER', 'ROLE_STUDENT_BOSS'];

        if ($request->isMethod('POST')) {
            $data = $request->request->all('permissions');
            foreach ($permissions as $permission) {
                foreach ($roles as $role) {
                    $checkboxValue = isset($data[$permission->getSlug()][$role]);
                    error_log('Processing role: ' . $role . ' with value: ' . ($checkboxValue ? 'true' : 'false'));
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
                        error_log('Persisting PermissionRelRole for permission: ' . $permission->getSlug() . ' and role: ' . $role);
                    } else {
                        if ($permRelRole) {
                            $em->remove($permRelRole);
                            error_log('Removing PermissionRelRole for permission: ' . $permission->getSlug() . ' and role: ' . $role);
                        }
                    }
                }
            }
            $em->flush();
            error_log('Flush complete');

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
