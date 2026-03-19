<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_SESSION_MANAGER")'))]
class UserListActionController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    #[Route('/admin/user-list-action', name: 'admin_user_list_action', methods: ['POST'])]
    public function handleAction(Request $request): JsonResponse|RedirectResponse
    {
        $action = (string) $request->request->get('action');
        $token = (string) $request->request->get('_token');

        if (!$this->isCsrfTokenValid('user_list_action', $token)) {
            if (\in_array($action, ['delete_users', 'disable_users', 'enable_users'], true)) {
                return $this->json(['error' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
            }
            $this->addFlash('error', 'Invalid CSRF token.');

            return $this->redirect('/admin/user-list');
        }

        if (\in_array($action, ['delete_users', 'disable_users', 'enable_users'], true)) {
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
            }
        }

        $this->em->flush();

        return $this->json(['success' => true, 'affected' => $affected]);
    }

    private function handleSingleAction(Request $request, string $action): RedirectResponse
    {
        $userId = (int) $request->request->get('user_id');
        $view = (string) $request->request->get('view', 'all');

        $currentUser = $this->getUser();
        $currentUserId = $currentUser ? $currentUser->getId() : 0;

        if ($userId <= 0 || $userId === $currentUserId) {
            $this->addFlash('error', 'Invalid action.');

            return $this->redirect('/admin/user-list');
        }

        $userRepo = $this->em->getRepository(User::class);
        $user = $userRepo->find($userId);

        if (!$user) {
            $this->addFlash('error', 'User not found.');

            return $this->redirect('/admin/user-list');
        }

        switch ($action) {
            case 'delete_user':
                if (!$this->isGranted('ROLE_ADMIN')) {
                    break;
                }
                $user->setActive(User::SOFT_DELETED);
                $this->em->flush();
                $this->addFlash('success', 'User has been removed.');

                break;

            case 'restore':
                if (!$this->isGranted('ROLE_ADMIN')) {
                    break;
                }
                $user->setActive(User::ACTIVE);
                $this->em->flush();
                $this->addFlash('success', 'The user has been restored.');

                break;

            case 'destroy':
                if (!$this->isGranted('ROLE_ADMIN')) {
                    break;
                }
                $this->em->remove($user);
                $this->em->flush();
                $this->addFlash('success', 'The user has been deleted permanently.');
                $view = 'deleted';

                break;

            case 'anonymize':
                if (!$this->isGranted('ROLE_ADMIN')) {
                    break;
                }
                $user->setFirstname('Anonymous');
                $user->setLastname('Anonymous');
                $user->setEmail('anonymous_'.$userId.'@example.com');
                $user->setUsername('anon_'.$userId);
                $user->setPhone(null);
                $user->setAddress(null);
                $user->setBiography('');
                $user->setDateOfBirth(null);
                $this->em->flush();
                $this->addFlash('success', 'User has been anonymized.');

                break;
        }

        $redirectUrl = '/admin/user-list';
        if ('deleted' === $view) {
            $redirectUrl .= '?view=deleted';
        }

        return $this->redirect($redirectUrl);
    }
}
