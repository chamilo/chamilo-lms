<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Service\AI\AiProviderFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller to handle AI-related functionalities.
 */
#[Route('/ai')]
class AiController
{
    private AiProviderFactory $aiProviderFactory;

    public function __construct(AiProviderFactory $aiProviderFactory)
    {
        $this->aiProviderFactory = $aiProviderFactory;
    }

    /**
     * Generate Aiken questions using AI.
     */
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

        } catch (\Exception $e) {
            error_log("AI Request failed: " . $e->getMessage());
            return new JsonResponse([
                'success' => false,
                'text' => "An error occurred while generating questions. Please contact the administrator.",
            ], 500);
        }
    }
}
