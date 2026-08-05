<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use Chamilo\CoreBundle\State\Survey\SurveyAnswerProcessor;
use Chamilo\CoreBundle\State\Survey\SurveyAnswerProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/api/survey/answer')]
final class SurveyAnswerController extends AbstractController
{
    public function __construct(
        private readonly SurveyAnswerProvider $surveyAnswerProvider,
        private readonly SurveyAnswerProcessor $surveyAnswerProcessor,
    ) {}

    #[Route('/{surveyId}', name: 'api_survey_answer_get', requirements: ['surveyId' => '\d+'], methods: ['GET'])]
    public function getSurveyAnswer(int $surveyId): JsonResponse
    {
        $data = $this->surveyAnswerProvider->provide(
            new Get(name: 'get_survey_answer'),
            ['surveyId' => $surveyId],
        );

        return $this->json($data);
    }

    #[Route('/{surveyId}', name: 'api_survey_answer_submit', requirements: ['surveyId' => '\d+'], methods: ['POST'])]
    public function submitSurveyAnswer(int $surveyId): JsonResponse
    {
        $data = $this->surveyAnswerProcessor->process(
            null,
            new Post(name: 'post_survey_answer'),
            ['surveyId' => $surveyId],
        );

        return $this->json($data);
    }
}
