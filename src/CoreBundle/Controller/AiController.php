<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Service\AI\AiProviderFactory;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

use const FILTER_VALIDATE_BOOLEAN;

/**
 * Controller to handle AI-related functionalities.
 */
#[Route('/ai')]
class AiController
{
    public function __construct(
        private readonly AiProviderFactory $aiProviderFactory
    ) {}

    #[Route('/generate_aiken', name: 'chamilo_core_ai_generate_aiken', methods: ['POST'])]
    public function generateAiken(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $nQ = (int) ($data['nro_questions'] ?? 0);
            $language = (string) ($data['language'] ?? 'en');
            $topic = trim((string) ($data['quiz_name'] ?? ''));
            $questionType = $data['question_type'] ?? 'multiple_choice';
            $aiProvider = $data['ai_provider'] ?? null;

            if ($nQ <= 0 || empty($topic)) {
                return new JsonResponse([
                    'success' => false,
                    'text' => 'Invalid request parameters. Ensure all fields are filled correctly.',
                ], 400);
            }

            $aiService = $this->aiProviderFactory->getProvider($aiProvider);
            $questions = $aiService->generateQuestions($topic, $nQ, $questionType, $language);

            if (str_starts_with($questions, 'Error:')) {
                return new JsonResponse([
                    'success' => false,
                    'text' => $questions,
                ], 500);
            }

            return new JsonResponse([
                'success' => true,
                'text' => trim($questions),
            ]);
        } catch (Exception $e) {
            error_log('AI Request failed: '.$e->getMessage());

            return new JsonResponse([
                'success' => false,
                'text' => 'An error occurred while generating questions. Please contact the administrator.',
            ], 500);
        }
    }

    #[Route('/generate_learnpath', name: 'chamilo_core_ai_generate_learnpath', methods: ['POST'])]
    public function generateLearnPath(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $topic = trim((string) ($data['lp_name'] ?? ''));
            $chaptersCount = (int) ($data['nro_items'] ?? 5);
            $language = (string) ($data['language'] ?? 'en');
            $wordsCount = (int) ($data['words_count'] ?? 500);
            $addTests = filter_var($data['add_tests'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $numQuestions = (int) ($data['nro_questions'] ?? 0);
            $aiProvider = $data['ai_provider'] ?? null;

            if (empty($topic)) {
                return new JsonResponse(['success' => false, 'text' => 'Invalid parameters.'], 400);
            }

            $aiService = $this->aiProviderFactory->getProvider($aiProvider);
            $lpData = $aiService->generateLearnPath($topic, $chaptersCount, $language, $wordsCount, $addTests, $numQuestions);

            if (!$lpData['success']) {
                return new JsonResponse(['success' => false, 'text' => 'Failed to generate learning path.'], 500);
            }

            return new JsonResponse([
                'success' => true,
                'data' => $lpData,
            ]);
        } catch (Exception $e) {
            error_log('ERROR: '.$e->getMessage());

            return new JsonResponse(['success' => false, 'text' => 'An error occurred.'], 500);
        }
    }
}
