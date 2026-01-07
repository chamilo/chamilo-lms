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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

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
        private readonly RequestStack $requestStack
    ) {}

    #[Route('/{type}/{hash}', name: 'chamilo_core_validate_token')]
    public function validate(string $type, string $hash): Response
    {
        $userId = $this->requestStack->getCurrentRequest()->query->get('user_id');
        $userId = null !== $userId ? (int) $userId : null;

        $token = $this->tokenRepository->findOneBy([
            'type' => $this->validationTokenHelper->getTypeId($type),
            'hash' => $hash,
        ]);

        if (!$token) {
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

            return $this->redirect('/main/ticket/ticket_details.php?ticket_id='.$ticketId);
        }

        return $this->render('@ChamiloCore/Validation/success.html.twig', [
            'type' => $type,
        ]);
    }

    #[Route('/test/generate-token/{type}/{resourceId}', name: 'test_generate_token')]
    public function testGenerateToken(string $type, int $resourceId): Response
    {
        $typeId = $this->validationTokenHelper->getTypeId($type);
        $token = new ValidationToken($typeId, $resourceId);
        $this->tokenRepository->save($token, true);

        $validationLink = $this->generateUrl('validate_token', [
            'type' => $type,
            'hash' => $token->getHash(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        return new Response("Generated token: {$token->getHash()}<br>Validation link: <a href='{$validationLink}'>{$validationLink}</a>");
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
