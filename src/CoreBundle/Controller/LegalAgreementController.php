<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use LegalManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use UserManager;

/**
 * Sends a learner their terms and conditions to sign, or deletes their acceptance, for a
 * platform administrator or the learner's boss. Shared by the legacy user information and
 * learner tracking pages (plain form posts, redirected back) and the Vue global reporting
 * (JSON).
 */
#[IsGranted('ROLE_USER')]
#[Route('/legal-agreement/{userId}', requirements: ['userId' => '\d+'])]
class LegalAgreementController extends AbstractController
{
    public const string CSRF_INTENT = 'legal_agreement';

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly TranslatorInterface $translator,
    ) {}

    #[Route('/send', name: 'chamilo_core_legal_agreement_send', methods: ['POST'])]
    public function send(int $userId, Request $request, #[CurrentUser] User $currentUser): Response
    {
        $denied = $this->denyUnlessAllowed($userId, $request, $currentUser);
        if (null !== $denied) {
            return $denied;
        }

        LegalManager::sendLegal($userId, (int) $currentUser->getId());

        return $this->respond($request, $userId, $this->translator->trans('Sent'));
    }

    #[Route('/delete', name: 'chamilo_core_legal_agreement_delete', methods: ['POST'])]
    public function delete(int $userId, Request $request, #[CurrentUser] User $currentUser): Response
    {
        $denied = $this->denyUnlessAllowed($userId, $request, $currentUser);
        if (null !== $denied) {
            return $denied;
        }

        LegalManager::deleteLegal($userId);

        return $this->respond($request, $userId, $this->translator->trans('Deleted'));
    }

    private function denyUnlessAllowed(int $userId, Request $request, User $currentUser): ?JsonResponse
    {
        if (!$this->isCsrfTokenValid(self::CSRF_INTENT, (string) $this->getParameters($request)['_token'])) {
            return $this->json(['error' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
        }

        if (null === $this->userRepository->find($userId)) {
            return $this->json(['error' => 'User not found.'], Response::HTTP_NOT_FOUND);
        }

        // Same rule as the legacy pages: a platform administrator, or the learner's boss.
        if (!$this->isGranted('ROLE_ADMIN') && !UserManager::userIsBossOfStudent((int) $currentUser->getId(), $userId)) {
            return $this->json(['error' => 'Access denied.'], Response::HTTP_FORBIDDEN);
        }

        return null;
    }

    private function respond(Request $request, int $userId, string $message): Response
    {
        if ($this->isJson($request)) {
            return $this->json(['success' => true, 'message' => $message]);
        }

        $this->addFlash('success', $message);

        // Only a path on this site, never an absolute or protocol-relative URL (open redirect).
        $returnTo = (string) $this->getParameters($request)['returnTo'];
        if (!str_starts_with($returnTo, '/') || str_starts_with($returnTo, '//') || str_starts_with($returnTo, '/\\')) {
            $returnTo = '/main/admin/user_information.php?user_id='.$userId;
        }

        return new RedirectResponse($returnTo);
    }

    /**
     * @return array{_token: mixed, returnTo: mixed}
     */
    private function getParameters(Request $request): array
    {
        $parameters = $this->isJson($request) ? (array) json_decode($request->getContent(), true) : $request->request->all();

        return [
            '_token' => $parameters['_token'] ?? '',
            'returnTo' => $parameters['returnTo'] ?? '',
        ];
    }

    private function isJson(Request $request): bool
    {
        return 'json' === $request->getContentTypeFormat();
    }
}
