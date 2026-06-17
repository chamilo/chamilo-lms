<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use Chamilo\CoreBundle\State\Survey\SurveyInvitationProcessor;
use Chamilo\CoreBundle\State\Survey\SurveyInvitationProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/api/survey/invitations')]
final class SurveyInvitationController extends AbstractController
{
    public function __construct(
        private readonly SurveyInvitationProvider $surveyInvitationProvider,
        private readonly SurveyInvitationProcessor $surveyInvitationProcessor,
    ) {}

    #[Route('/{surveyId}', name: 'api_survey_invitations_get', requirements: ['surveyId' => '\d+'], methods: ['GET'])]
    public function getInvitations(int $surveyId): JsonResponse
    {
        $data = $this->surveyInvitationProvider->provide(
            new Get(name: 'get_survey_invitations'),
            ['surveyId' => $surveyId],
        );

        return $this->json($data);
    }

    #[Route('/{surveyId}/publish', name: 'api_survey_invitations_publish', requirements: ['surveyId' => '\d+'], methods: ['POST'])]
    public function publishInvitations(int $surveyId): JsonResponse
    {
        $data = $this->surveyInvitationProcessor->process(
            null,
            new Post(name: 'post_survey_invitations_publish'),
            ['surveyId' => $surveyId],
        );

        return $this->json($data);
    }
}
