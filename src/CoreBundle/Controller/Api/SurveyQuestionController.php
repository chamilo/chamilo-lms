<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Chamilo\CoreBundle\State\Survey\SurveyQuestionProcessor;
use Chamilo\CoreBundle\State\Survey\SurveyQuestionProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/api/survey/questions')]
final class SurveyQuestionController extends AbstractController
{
    public function __construct(
        private readonly SurveyQuestionProvider $surveyQuestionProvider,
        private readonly SurveyQuestionProcessor $surveyQuestionProcessor,
    ) {}

    #[Route('/{surveyId}', name: 'api_survey_questions_get', requirements: ['surveyId' => '\d+'], methods: ['GET'])]
    public function getQuestions(int $surveyId): JsonResponse
    {
        $data = $this->surveyQuestionProvider->provide(
            new Get(name: 'get_survey_questions'),
            ['surveyId' => $surveyId],
        );

        return $this->json($data);
    }

    #[Route('/{surveyId}', name: 'api_survey_questions_create', requirements: ['surveyId' => '\d+'], methods: ['POST'])]
    public function createQuestion(int $surveyId): JsonResponse
    {
        $data = $this->surveyQuestionProcessor->process(
            null,
            new Post(name: 'post_survey_question'),
            ['surveyId' => $surveyId],
        );

        return $this->json($data);
    }

    #[Route('/{surveyId}/{questionId}', name: 'api_survey_questions_update', requirements: ['surveyId' => '\d+', 'questionId' => '\d+'], methods: ['PUT'])]
    public function updateQuestion(int $surveyId, int $questionId): JsonResponse
    {
        $data = $this->surveyQuestionProcessor->process(
            null,
            new Put(name: 'put_survey_question'),
            [
                'surveyId' => $surveyId,
                'questionId' => $questionId,
            ],
        );

        return $this->json($data);
    }

    #[Route('/{surveyId}/{questionId}', name: 'api_survey_questions_delete', requirements: ['surveyId' => '\d+', 'questionId' => '\d+'], methods: ['DELETE'])]
    public function deleteQuestion(int $surveyId, int $questionId): JsonResponse
    {
        $data = $this->surveyQuestionProcessor->process(
            null,
            new Delete(name: 'delete_survey_question'),
            [
                'surveyId' => $surveyId,
                'questionId' => $questionId,
            ],
        );

        return $this->json($data);
    }

    #[Route('/{surveyId}/{questionId}/move', name: 'api_survey_questions_move', requirements: ['surveyId' => '\d+', 'questionId' => '\d+'], methods: ['POST'])]
    public function moveQuestion(int $surveyId, int $questionId): JsonResponse
    {
        $data = $this->surveyQuestionProcessor->process(
            null,
            new Post(name: 'post_survey_question_move'),
            [
                'surveyId' => $surveyId,
                'questionId' => $questionId,
            ],
        );

        return $this->json($data);
    }

    #[Route('/{surveyId}/{questionId}/copy', name: 'api_survey_questions_copy', requirements: ['surveyId' => '\d+', 'questionId' => '\d+'], methods: ['POST'])]
    public function copyQuestion(int $surveyId, int $questionId): JsonResponse
    {
        $data = $this->surveyQuestionProcessor->process(
            null,
            new Post(name: 'post_survey_question_copy'),
            [
                'surveyId' => $surveyId,
                'questionId' => $questionId,
            ],
        );

        return $this->json($data);
    }
}
