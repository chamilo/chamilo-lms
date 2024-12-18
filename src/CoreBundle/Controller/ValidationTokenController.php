<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\ValidationToken;
use Chamilo\CoreBundle\Repository\TrackEDefaultRepository;
use Chamilo\CoreBundle\Repository\ValidationTokenRepository;
use Chamilo\CoreBundle\ServiceHelper\ValidationTokenHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

#[Route('/validate')]
class ValidationTokenController extends AbstractController
{
    public function __construct(
        private readonly ValidationTokenHelper $validationTokenHelper,
        private readonly ValidationTokenRepository $tokenRepository,
        private readonly TrackEDefaultRepository $trackEDefaultRepository,
        private readonly Security $security
    ) {}

    #[Route('/{type}/{hash}', name: 'validate_token')]
    public function validate(string $type, string $hash): Response
    {
        $token = $this->tokenRepository->findOneBy([
            'type' => $this->validationTokenHelper->getTypeId($type),
            'hash' => $hash
        ]);

        if (!$token) {
            throw $this->createNotFoundException('Invalid token.');
        }

        // Process the action related to the token type
        $this->processAction($token);

        // Remove the used token
        $this->tokenRepository->remove($token, true);

        // Register the token usage event
        $this->registerTokenUsedEvent($token);

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
        ], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);

        return new Response("Generated token: {$token->getHash()}<br>Validation link: <a href='{$validationLink}'>{$validationLink}</a>");
    }

    private function processAction(ValidationToken $token): void
    {
        switch ($token->getType()) {
            case 1: // Assuming 1 is for 'ticket'
                $this->processTicketValidation($token);
                break;
            case 2: // Assuming 2 is for 'user'
                // Implement user validation logic here
                break;
            default:
                throw new \InvalidArgumentException('Unrecognized token type');
        }
    }

    private function processTicketValidation(ValidationToken $token): void
    {
        $ticketId = $token->getResourceId();

        // Simulate ticket validation logic
        // Here you would typically check if the ticket exists and is valid
        // For now, we'll just print a message to simulate this
        // Replace this with your actual ticket validation logic
        $ticketValid = $this->validateTicket($ticketId);

        if (!$ticketValid) {
            throw new \RuntimeException('Invalid ticket.');
        }

        // If the ticket is valid, you can mark it as used or perform other actions
        // For example, update the ticket status in the database
        // $this->ticketRepository->markAsUsed($ticketId);
    }

    private function validateTicket(int $ticketId): bool
    {
        // Here you would implement the logic to check if the ticket is valid.
        // This is a placeholder function to simulate validation.

        // For testing purposes, let's assume all tickets are valid.
        // In a real implementation, you would query your database or service.

        return true; // Assume the ticket is valid for now
    }

    private function registerTokenUsedEvent(ValidationToken $token): void
    {
        $user = $this->security->getUser();
        $userId = $user?->getId();
        $this->trackEDefaultRepository->registerTokenUsedEvent($token, $userId);
    }
}
