<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Entity\User;
use Display;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use UserManager;

#[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_SESSION_MANAGER")'))]
class UserListActionController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    #[Route('/admin/user-list-action', name: 'admin_user_list_action', methods: ['POST'])]
    public function handleAction(Request $request): JsonResponse
    {
        $action = (string) $request->request->get('action');
        $token = (string) $request->request->get('_token');

        if (!$this->isCsrfTokenValid('user_list_action', $token)) {
            return $this->json(['error' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
        }

        $bulkActions = ['delete_users', 'disable_users', 'enable_users', 'restore_users', 'destroy_users'];
        if (\in_array($action, $bulkActions, true)) {
            return $this->handleBulkAction($request, $action);
        }

        return $this->handleSingleAction($request, $action);
    }

    private function handleBulkAction(Request $request, string $action): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Access denied.'], Response::HTTP_FORBIDDEN);
        }

        $userIds = array_map('intval', $request->request->all('user_ids'));
        if (empty($userIds)) {
            return $this->json(['error' => 'No users selected.'], Response::HTTP_BAD_REQUEST);
        }

        $currentUser = $this->getUser();
        $currentUserId = $currentUser ? $currentUser->getId() : 0;

        $userRepo = $this->em->getRepository(User::class);
        $affected = 0;

        foreach ($userIds as $userId) {
            if ($userId <= 0 || $userId === $currentUserId) {
                continue;
            }

            $user = $userRepo->find($userId);
            if (!$user) {
                continue;
            }

            switch ($action) {
                case 'delete_users':
                    $user->setActive(User::SOFT_DELETED);
                    ++$affected;

                    break;

                case 'disable_users':
                    if (User::ACTIVE === $user->getActive()) {
                        $user->setActive(User::INACTIVE);
                        ++$affected;
                    }

                    break;

                case 'enable_users':
                    if (User::INACTIVE === $user->getActive()) {
                        $user->setActive(User::ACTIVE);
                        ++$affected;
                    }

                    break;

                case 'restore_users':
                    if (User::SOFT_DELETED === $user->getActive()) {
                        $user->setActive(User::ACTIVE);
                        ++$affected;
                    }

                    break;

                case 'destroy_users':
                    UserManager::delete_user($userId, true);
                    ++$affected;

                    break;
            }
        }

        $this->em->flush();

        return $this->json(['success' => true, 'affected' => $affected]);
    }

    private function handleSingleAction(Request $request, string $action): JsonResponse
    {
        $userId = (int) $request->request->get('user_id');

        $currentUser = $this->getUser();
        $currentUserId = $currentUser ? $currentUser->getId() : 0;

        if ($userId <= 0 || $userId === $currentUserId) {
            return $this->json(['error' => 'Invalid action.'], Response::HTTP_BAD_REQUEST);
        }

        $userRepo = $this->em->getRepository(User::class);
        $user = $userRepo->find($userId);

        if (!$user) {
            return $this->json(['error' => 'User not found.'], Response::HTTP_NOT_FOUND);
        }

        switch ($action) {
            case 'delete_user':
                if (!$this->isGranted('ROLE_ADMIN')) {
                    return $this->json(['error' => 'Access denied.'], Response::HTTP_FORBIDDEN);
                }

                UserManager::delete_user($userId);

                break;

            case 'restore':
                if (!$this->isGranted('ROLE_ADMIN')) {
                    return $this->json(['error' => 'Access denied.'], Response::HTTP_FORBIDDEN);
                }

                UserManager::change_active_state($userId, User::ACTIVE);

                break;

            case 'destroy':
                if (!$this->isGranted('ROLE_ADMIN')) {
                    return $this->json(['error' => 'Access denied.'], Response::HTTP_FORBIDDEN);
                }

                UserManager::delete_user($userId, true);

                break;

            case 'anonymize':
                if (!$this->isGranted('ROLE_ADMIN')) {
                    return $this->json(['error' => 'Access denied.'], Response::HTTP_FORBIDDEN);
                }

                $message = UserManager::anonymizeUserWithVerification($userId);

                Display::addFlash($message);

                break;
        }

        return $this->json(['success' => true]);
    }
}
