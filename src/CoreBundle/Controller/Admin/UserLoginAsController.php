<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Admin;

use Chamilo\CoreBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Handles the "Login as" (user impersonation) action, replacing the legacy
 * user_list.php?action=login_as handler. Uses Symfony's built-in _switch_user.
 */
#[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_SESSION_MANAGER")'))]
class UserLoginAsController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    #[Route('/admin/user-list-login-as', name: 'admin_user_login_as', methods: ['GET'])]
    public function loginAs(Request $request): RedirectResponse
    {
        $userId = (int) $request->query->get('user_id', 0);
        $token = (string) $request->query->get('sec_token', '');

        if (!$this->isCsrfTokenValid('login_as', $token)) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        if ($userId <= 0) {
            throw $this->createAccessDeniedException('Invalid user ID.');
        }

        $user = $this->em->getRepository(User::class)->find($userId);

        if (!$user) {
            throw $this->createNotFoundException('User not found.');
        }

        $this->addFlash(
            'success',
            sprintf('Attempting to login as %s (id %d)', $user->getFullname(), $userId)
        );

        return $this->redirect('/?_switch_user='.urlencode($user->getUsername()));
    }
}
