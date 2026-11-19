<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\ValidationToken;
use Chamilo\CoreBundle\Helpers\ValidationTokenHelper;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Repository\TicketRelUserRepository;
use Chamilo\CoreBundle\Repository\TicketRepository;
use Chamilo\CoreBundle\Repository\TrackEDefaultRepository;
use Chamilo\CoreBundle\Repository\ValidationTokenRepository;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/validate')]
class ValidationTokenController extends AbstractController
{
    public function __construct(
        private readonly ValidationTokenHelper $validationTokenHelper,
        private readonly ValidationTokenRepository $tokenRepository,
        private readonly TrackEDefaultRepository $trackEDefaultRepository,
        private readonly TicketRepository $ticketRepository,
        private readonly UserRepository $userRepository,
        private readonly TicketRelUserRepository $ticketRelUserRepository,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly RequestStack $requestStack,
        private readonly TranslatorInterface $translator,
    ) {}

    #[Route('/{type}/{hash}', name: 'chamilo_core_validate_token')]
    public function validate(string $type, string $hash): Response
    {
        $userId = $this->requestStack->getCurrentRequest()->query->get('user_id');
        $userId = null !== $userId ? (int) $userId : null;

        $typeId = $this->validationTokenHelper->getTypeId($type);

        // Ticket tokens are bound to the user the link was sent to.
        if (ValidationTokenHelper::TYPE_TICKET === $typeId) {
            if (!$userId) {
                return $this->redirectExpiredLink();
            }

            $hash = ValidationTokenHelper::bindHashToUser($hash, $userId);
        }

        $token = $this->tokenRepository->findOneBy([
            'type' => $typeId,
            'hash' => $hash,
        ]);

        if (!$token) {
            // Links mailed before tokens were bound to a user, or already used, end here.
            if (ValidationTokenHelper::TYPE_TICKET === $typeId) {
                return $this->redirectExpiredLink();
            }

            throw $this->createNotFoundException('Invalid token.');
        }

        // Process the action related to the token type
        $this->processAction($token, $userId);

        // Remove the used token
        $this->tokenRepository->remove($token, true);

        // Register the token usage event
        $this->registerTokenUsedEvent($token);

        if ('ticket' === $type) {
            $ticketId = $token->getResourceId();

            return $this->redirect('/tickets/'.$ticketId);
        }

        return $this->render('@ChamiloCore/Validation/success.html.twig', [
            'type' => $type,
        ]);
    }

    private function redirectExpiredLink(): Response
    {
        $this->addFlash(
            'warning',
            $this->translator->trans('This link has expired. Please use the link in the most recent notification email.')
        );

        return $this->redirect('/');
    }

    /**
     * Processes the action associated with the given token type.
     */
    private function processAction(ValidationToken $token, ?int $userId): void
    {
        switch ($token->getType()) {
            case ValidationTokenHelper::TYPE_TICKET:
                $this->unsubscribeUserFromTicket($token->getResourceId(), $userId);

                break;

            default:
                throw new InvalidArgumentException('Unrecognized token type');
        }
    }

    /**
     * Unsubscribes a user from a ticket.
     */
    private function unsubscribeUserFromTicket(int $ticketId, ?int $userId): void
    {
        if (!$userId) {
            throw $this->createAccessDeniedException('User not authenticated.');
        }

        $ticket = $this->ticketRepository->find($ticketId);
        $user = $this->userRepository->find($userId);

        if ($ticket && $user) {
            $this->ticketRelUserRepository->unsubscribeUserFromTicket($user, $ticket);
            $this->trackEDefaultRepository->registerTicketUnsubscribeEvent($ticketId, $userId);
        } else {
            throw $this->createNotFoundException('Ticket or User not found.');
        }
    }

    /**
     * Registers the usage event of a validation token.
     */
    private function registerTokenUsedEvent(ValidationToken $token): void
    {
        $userId = $this->getUserId();
        $this->trackEDefaultRepository->registerTokenUsedEvent($token, $userId);
    }

    /**
     * Retrieves the current authenticated user's ID.
     */
    private function getUserId(): ?int
    {
        $user = $this->tokenStorage->getToken()?->getUser();

        return $user instanceof User ? $user->getId() : null;
    }
}
