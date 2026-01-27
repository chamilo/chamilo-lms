<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\AiProvider\AiDocumentProcessProviderInterface;
use Chamilo\CoreBundle\AiProvider\AiImageProviderInterface;
use Chamilo\CoreBundle\AiProvider\AiProviderFactory;
use Chamilo\CoreBundle\AiProvider\AiVideoProviderInterface;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\TrackEDefault;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Helpers\MessageHelper;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CoreBundle\Repository\TrackEAttemptRepository;
use Chamilo\CourseBundle\Entity\CGlossary;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Repository\CGlossaryRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Question;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;
use TypeError;

use const ENT_QUOTES;
use const ENT_SUBSTITUTE;
use const FILTER_FLAG_NO_PRIV_RANGE;
use const FILTER_FLAG_NO_RES_RANGE;
use const FILTER_SANITIZE_NUMBER_INT;
use const FILTER_VALIDATE_IP;
use const FILTER_VALIDATE_URL;
use const PATHINFO_EXTENSION;

#[IsGranted('ROLE_USER')]
#[Route('/ai')]
class AiController extends AbstractController
{
    private bool $debug = false;

    public function __construct(
        private readonly AiProviderFactory $aiProviderFactory,
        private readonly TrackEAttemptRepository $attemptRepo,
        private readonly EntityManagerInterface $em,
        private readonly HttpClientInterface $httpClient,
        private readonly TranslatorInterface $translator,
        private readonly ResourceNodeRepository $resourceNodeRepository,
        private readonly MessageHelper $messageHelper,
    ) {}

    #[Route('/text_providers', name: 'chamilo_core_ai_text_providers', methods: ['GET'])]
    public function textProviders(): JsonResponse
    {
        try {
            $this->denyIfNotTeacher();
        } catch (AccessDeniedException $e) {
            return new JsonResponse(['providers' => []], 403);
        }

        return new JsonResponse([
            'providers' => array_values($this->aiProviderFactory->getProvidersForType('text')),
        ]);
    }

    #[Route('/glossary_default_prompt', name: 'chamilo_core_ai_glossary_default_prompt', methods: ['GET'])]
    public function glossaryDefaultPrompt(Request $request): JsonResponse
    {
        try {
            $this->denyIfNotTeacher();
        } catch (AccessDeniedException $e) {
            return new JsonResponse(['prompt' => ''], 403);
        }

        $cid = (int) $request->query->get('cid', 0);
        $sid = (int) $request->query->get('sid', 0);
        $n = (int) $request->query->get('n', 15);

        if ($n < 1) {
            $n = 1;
        }
        if ($n > 200) {
            $n = 200;
        }

        if (0 === $cid) {
            return new JsonResponse(['prompt' => ''], 400);
        }

        /** @var Course|null $course */
        $course = $this->em->getRepository(Course::class)->find($cid);
        if (null === $course) {
            return new JsonResponse(['prompt' => ''], 404);
        }

        $courseTitle = (string) $course->getTitle();
        $desc = $this->getGenericCourseDescription($cid, $sid);

        $base = $this->translator->trans(
            "Generate %d glossary terms for a course on '%s', each term on a single line, with its definition on the next line and one blank line between each term. Do not add any other formatting for the title nor for the definition."
        );

        $prompt = \sprintf($base, $n, $courseTitle);

        if ('' !== $desc) {
            $descPrefix = $this->translator->trans(
                "This is a short description of the course '%s'."
            );
            $prompt .= ' '.\sprintf($descPrefix, $courseTitle).' '.$desc;
        }

        return new JsonResponse(['prompt' => $prompt]);
    }

    #[Route('/generate_glossary_terms', name: 'chamilo_core_ai_generate_glossary_terms', methods: ['POST'])]
    public function generateGlossaryTerms(Request $request): JsonResponse
    {
        try {
            $this->denyIfNotTeacher();
        } catch (AccessDeniedException $e) {
            return new JsonResponse([
                'success' => false,
                'text' => 'Access denied.',
            ], 403);
        }

        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            return new JsonResponse([
                'success' => false,
                'text' => 'Invalid JSON payload.',
            ], 400);
        }

        $n = (int) ($data['n'] ?? 15);
        $language = (string) ($data['language'] ?? 'en');
        $prompt = trim((string) ($data['prompt'] ?? ''));
        $providerName = isset($data['ai_provider']) ? (string) $data['ai_provider'] : null;
        $cid = (int) ($data['cid'] ?? 0);
        $toolName = trim((string) ($data['tool'] ?? 'glossary'));

        if ($n < 1) {
            $n = 1;
        }
        if ($n > 200) {
            $n = 200;
        }

        if (0 === $cid || '' === $prompt) {
            return new JsonResponse([
                'success' => false,
                'text' => 'Invalid request parameters.',
            ], 400);
        }

        /** @var Course|null $course */
        $course = $this->em->getRepository(Course::class)->find($cid);
        if (null === $course) {
            return new JsonResponse([
                'success' => false,
                'text' => 'Course not found.',
            ], 404);
        }

        try {
            $provider = $this->aiProviderFactory->getProvider($providerName, 'text');

            if (!\is_object($provider) || !method_exists($provider, 'generateText')) {
                return new JsonResponse([
                    'success' => false,
                    'text' => 'Selected AI provider does not support text generation.',
                ], 400);
            }

            // Preferred signature: generateText(string $prompt, array $options = []): string
            try {
                $raw = (string) $provider->generateText($prompt, [
                    'language' => $language,
                    'n' => $n,
                    'tool' => $toolName,
                ]);
            } catch (TypeError $e) {
                // Backward compatibility: generateText(string $prompt, string $language): string
                $raw = (string) $provider->generateText($prompt, $language);
            }

            $raw = trim($raw);

            if ('' === $raw) {
                return new JsonResponse([
                    'success' => false,
                    'text' => 'AI request returned an empty response.',
                ], 500);
            }

            if (str_starts_with($raw, 'Error:')) {
                $msg = trim((string) preg_replace('/^Error:\s*/', '', $raw));

                return new JsonResponse([
                    'success' => false,
                    'text' => '' !== $msg ? $msg : $raw,
                ], 500);
            }

            return new JsonResponse([
                'success' => true,
                'text' => $raw,
            ]);
        } catch (Throwable $e) {
            error_log('[AI][glossary] Generation failed: '.$e->getMessage());

            return new JsonResponse([
                'success' => false,
                'text' => 'An error occurred while generating glossary terms.',
            ], 500);
        }
    }

    #[Route('/capabilities', name: 'chamilo_core_ai_capabilities', methods: ['GET'])]
    public function capabilities(): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'types' => [
                'text' => $this->aiProviderFactory->getProvidersForType('text'),
                'image' => $this->aiProviderFactory->getProvidersForType('image'),
                'video' => $this->aiProviderFactory->getProvidersForType('video'),
                'document' => $this->aiProviderFactory->getProvidersForType('document'),
                'document_process' => $this->aiProviderFactory->getProvidersForType('document_process'),
            ],
            'has' => [
                'text' => $this->aiProviderFactory->hasProvidersForType('text'),
                'image' => $this->aiProviderFactory->hasProvidersForType('image'),
                'video' => $this->aiProviderFactory->hasProvidersForType('video'),
                'document' => $this->aiProviderFactory->hasProvidersForType('document'),
                'document_process' => $this->aiProviderFactory->hasProvidersForType('document_process'),
            ],
        ]);
    }

    #[Route('/generate_aiken', name: 'chamilo_core_ai_generate_aiken', methods: ['POST'])]
    public function generateAiken(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (!\is_array($data)) {
                return new JsonResponse([
                    'success' => false,
                    'text' => 'Invalid JSON payload.',
                ], 400);
            }

            $nQ = (int) ($data['nro_questions'] ?? 0);
            $language = (string) ($data['language'] ?? 'en');
            $topic = trim((string) ($data['quiz_name'] ?? ''));
            $questionType = (string) ($data['question_type'] ?? 'multiple_choice');
            $aiProvider = $data['ai_provider'] ?? null;

            if ($nQ <= 0 || '' === $topic) {
                return new JsonResponse([
                    'success' => false,
                    'text' => 'Invalid request parameters. Ensure all fields are filled correctly.',
                ], 400);
            }

            $aiService = $this->aiProviderFactory->getProvider($aiProvider, 'text');
            $questions = $aiService->generateQuestions($topic, $nQ, $questionType, $language);

            if (empty($questions)) {
                return new JsonResponse([
                    'success' => false,
                    'text' => 'AI request returned an empty response.',
                ], 500);
            }

            if (\is_string($questions) && str_starts_with($questions, 'Error:')) {
                return new JsonResponse([
                    'success' => false,
                    'text' => $questions,
                ], 500);
            }

            return new JsonResponse([
                'success' => true,
                'text' => trim((string) $questions),
            ]);
        } catch (Exception $e) {
            error_log('[AI] Aiken generation failed: '.$e->getMessage());

            return new JsonResponse([
                'success' => false,
                'text' => 'An error occurred while generating questions. Please contact the administrator.',
            ], 500);
        }
    }

    #[Route('/open_answer_grade', name: 'chamilo_core_ai_open_answer_grade', methods: ['POST'])]
    public function openAnswerGrade(Request $request): JsonResponse
    {
        $exeId = $request->request->getInt('exeId', 0);
        $questionId = $request->request->getInt('questionId', 0);
        $courseId = $request->request->getInt('courseId', 0);

        if (0 === $exeId || 0 === $questionId || 0 === $courseId) {
            return $this->json(['error' => 'Missing parameters'], 400);
        }

        /** @var TrackEExercise|null $trackExercise */
        $trackExercise = $this->em->getRepository(TrackEExercise::class)->find($exeId);
        if (null === $trackExercise) {
            return $this->json(['error' => 'Exercise attempt not found'], 404);
        }

        $attempt = $this->attemptRepo->findOneBy([
            'trackExercise' => $trackExercise,
            'questionId' => $questionId,
            'user' => $trackExercise->getUser(),
        ]);
        if (null === $attempt) {
            return $this->json(['error' => 'Attempt not found'], 404);
        }

        $answerText = $attempt->getAnswer();

        if (ctype_digit($answerText)) {
            $cqa = $this->em->getRepository(CQuizAnswer::class)->find((int) $answerText);
            if ($cqa) {
                $answerText = $cqa->getAnswer();
            }
        }

        $courseInfo = api_get_course_info_by_id($courseId);
        if (empty($courseInfo['real_id'])) {
            return $this->json(['error' => 'Course info not found'], 500);
        }

        $question = Question::read($questionId, $courseInfo);
        if (false === $question) {
            return $this->json(['error' => 'Question not found'], 404);
        }

        $language = $courseInfo['language'] ?? 'en';
        $courseTitle = $courseInfo['title'] ?? '';
        $maxScore = $question->selectWeighting();
        $questionText = $question->selectTitle();

        $prompt = \sprintf(
            "In language %s, for the question: '%s', in the context of %s, provide a score between 0 and %d on one line, then feedback on the next line for the following answer: '%s'.",
            $language,
            $questionText,
            $courseTitle,
            $maxScore,
            $answerText
        );

        $provider = $this->aiProviderFactory->getProvider(null, 'text');
        $raw = trim((string) $provider->gradeOpenAnswer($prompt, 'open_answer_grade'));

        if ('' === $raw) {
            return $this->json(['error' => 'AI request failed'], 500);
        }

        if (str_contains($raw, "\n")) {
            [$scoreLine, $feedback] = explode("\n", $raw, 2);
        } else {
            $scoreLine = (string) $maxScore;
            $feedback = $raw;
        }

        $score = (int) filter_var($scoreLine, FILTER_SANITIZE_NUMBER_INT);

        $track = new TrackEDefault();
        $track
            ->setDefaultUserId($this->getUser()->getId())
            ->setDefaultEventType('ai_use_question_feedback')
            ->setDefaultValueType('attempt_id')
            ->setDefaultValue((string) $attempt->getId())
            ->setDefaultDate(new DateTime())
            ->setCId($courseId)
            ->setSessionId(api_get_session_id())
        ;

        $this->em->persist($track);
        $this->em->flush();

        return $this->json([
            'score' => $score,
            'feedback' => $feedback,
        ]);
    }

    #[Route('/generate_image', name: 'chamilo_core_ai_generate_image', methods: ['POST'])]
    public function generateImage(Request $request): JsonResponse
    {
        try {
            try {
                $this->denyIfNotTeacher();
            } catch (AccessDeniedException $e) {
                return new JsonResponse([
                    'success' => false,
                    'text' => 'Access denied.',
                ], 403);
            }

            $data = json_decode($request->getContent(), true);
            if (!\is_array($data)) {
                return new JsonResponse([
                    'success' => false,
                    'text' => 'Invalid JSON payload.',
                ], 400);
            }

            $n = (int) ($data['n'] ?? 1);
            $language = (string) ($data['language'] ?? 'en');
            $prompt = trim((string) ($data['prompt'] ?? ''));
            $toolName = trim((string) ($data['tool'] ?? 'document'));
            $aiProvider = $data['ai_provider'] ?? null;

            if ($n <= 0 || '' === $prompt || '' === $toolName) {
                return new JsonResponse([
                    'success' => false,
                    'text' => 'Invalid request parameters. Ensure all fields are filled correctly.',
                ], 400);
            }

            $availableProviders = $this->aiProviderFactory->getProvidersForType('image');
            if (empty($availableProviders)) {
                return new JsonResponse([
                    'success' => false,
                    'text' => 'No AI providers available for image generation.',
                ], 400);
            }

            $explicitProvider = null;
            if (null !== $aiProvider && '' !== (string) $aiProvider) {
                $explicitProvider = (string) $aiProvider;

                if (!\in_array($explicitProvider, $availableProviders, true)) {
                    return new JsonResponse([
                        'success' => false,
                        'text' => 'Selected AI provider is not available for image generation.',
                    ], 400);
                }
            }

            $providersToTry = $explicitProvider ? [$explicitProvider] : $availableProviders;
            $errors = [];
            $providerUsed = null;
            $result = null;

            foreach ($providersToTry as $providerName) {
                try {
                    $aiService = $this->aiProviderFactory->getProvider($providerName, 'image');

                    if (!$aiService instanceof AiImageProviderInterface) {
                        $errors[$providerName] = 'Provider does not implement image generation interface.';

                        continue;
                    }

                    $result = $aiService->generateImage($prompt, $toolName, [
                        'language' => $language,
                        'n' => $n,
                    ]);

                    if (empty($result)) {
                        $errors[$providerName] = 'Provider returned an empty response.';

                        continue;
                    }

                    if (\is_string($result) && str_starts_with($result, 'Error:')) {
                        $errors[$providerName] = $result;
                        $result = null;

                        continue;
                    }

                    $providerUsed = $providerName;

                    break;
                } catch (Throwable $e) {
                    $errors[$providerName] = $e->getMessage();

                    continue;
                }
            }

            if (null === $providerUsed || empty($result)) {
                error_log('[AI][image] Image generation failed for all providers: '.json_encode($errors));

                return new JsonResponse([
                    'success' => false,
                    'text' => $explicitProvider
                        ? 'Image generation failed for the selected provider.'
                        : 'All image providers failed.',
                    'providers_tried' => $providersToTry,
                    'errors' => $errors,
                ], 500);
            }

            if (\is_string($result)) {
                $normalized = [
                    'content' => trim($result),
                    'url' => null,
                    'is_base64' => true,
                    'content_type' => 'image/png',
                    'revised_prompt' => null,
                ];

                return new JsonResponse([
                    'success' => true,
                    'text' => $normalized['content'],
                    'result' => $normalized,
                    'provider_used' => $providerUsed,
                    'providers_tried' => $providersToTry,
                    'errors' => $errors,
                ]);
            }

            $url = isset($result['url']) && \is_string($result['url']) ? trim($result['url']) : '';
            $content = isset($result['content']) && \is_string($result['content']) ? trim($result['content']) : '';

            if ('' === $content && '' !== $url && false === (bool) ($result['is_base64'] ?? false)) {
                $fetched = $this->fetchUrlAsBase64($url, 10 * 1024 * 1024);
                $result['content'] = $fetched['content'];
                $result['content_type'] = $fetched['content_type'];
                $result['is_base64'] = true;
                $result['url'] = null;
            }

            $text = '';
            if (!empty($result['content']) && \is_string($result['content'])) {
                $text = trim($result['content']);
            }

            return new JsonResponse([
                'success' => true,
                'text' => $text,
                'result' => $result,
                'provider_used' => $providerUsed,
                'providers_tried' => $providersToTry,
                'errors' => $errors,
            ]);
        } catch (Exception $e) {
            error_log('[AI][image] Controller exception: '.$e->getMessage());

            return new JsonResponse([
                'success' => false,
                'text' => 'An error occurred while generating the image. Please contact the administrator.',
            ], 500);
        }
    }

    #[Route('/generate_video', name: 'chamilo_core_ai_generate_video', methods: ['POST'])]
    public function generateVideo(Request $request): JsonResponse
    {
        try {
            try {
                $this->denyIfNotTeacher();
            } catch (AccessDeniedException $e) {
                return new JsonResponse([
                    'success' => false,
                    'text' => 'Access denied.',
                ], 403);
            }

            $data = json_decode($request->getContent(), true);
            if (!\is_array($data)) {
                return new JsonResponse([
                    'success' => false,
                    'text' => 'Invalid JSON payload.',
                ], 400);
            }

            $n = (int) ($data['n'] ?? 1);
            $language = (string) ($data['language'] ?? 'en');
            $prompt = trim((string) ($data['prompt'] ?? ''));
            $toolName = trim((string) ($data['tool'] ?? 'document'));
            $aiProvider = $data['ai_provider'] ?? null;

            $seconds = isset($data['seconds']) ? trim((string) $data['seconds']) : null;
            $size = isset($data['size']) ? trim((string) $data['size']) : null;

            if ($n <= 0 || '' === $prompt || '' === $toolName) {
                return new JsonResponse([
                    'success' => false,
                    'text' => 'Invalid request parameters. Ensure all fields are filled correctly.',
                ], 400);
            }

            $availableProviders = $this->aiProviderFactory->getProvidersForType('video');
            if (empty($availableProviders)) {
                return new JsonResponse([
                    'success' => false,
                    'text' => 'No AI providers available for video generation.',
                ], 400);
            }

            $explicitProvider = null;
            if (null !== $aiProvider && '' !== (string) $aiProvider) {
                $explicitProvider = (string) $aiProvider;

                if (!\in_array($explicitProvider, $availableProviders, true)) {
                    return new JsonResponse([
                        'success' => false,
                        'text' => 'Selected AI provider is not available for video generation.',
                    ], 400);
                }
            }

            $providersToTry = $explicitProvider ? [$explicitProvider] : $availableProviders;
            $errors = [];
            $providerUsed = null;
            $result = null;

            foreach ($providersToTry as $providerName) {
                try {
                    $aiService = $this->aiProviderFactory->getProvider($providerName, 'video');

                    if (!$aiService instanceof AiVideoProviderInterface) {
                        $errors[$providerName] = 'Provider does not implement video generation interface.';

                        continue;
                    }

                    $options = [
                        'language' => $language,
                        'n' => $n,
                    ];

                    if (null !== $seconds && '' !== $seconds) {
                        $options['seconds'] = $seconds;
                    }
                    if (null !== $size && '' !== $size) {
                        $options['size'] = $size;
                    }

                    $result = $aiService->generateVideo($prompt, $toolName, $options);

                    if (empty($result)) {
                        $errors[$providerName] = 'Provider returned an empty response.';
                        $result = null;

                        continue;
                    }

                    if (\is_string($result) && str_starts_with($result, 'Error:')) {
                        $errors[$providerName] = $result;
                        $result = null;

                        continue;
                    }

                    $providerUsed = $providerName;

                    break;
                } catch (Throwable $e) {
                    $errors[$providerName] = $e->getMessage();
                    $result = null;

                    continue;
                }
            }

            if (null === $providerUsed || empty($result)) {
                error_log('[AI][video] Video generation failed for all providers: '.json_encode($errors));

                $firstError = '';
                foreach ($errors as $err) {
                    if (\is_string($err) && '' !== trim($err)) {
                        $firstError = trim($err);

                        break;
                    }
                }

                $message = '' !== $firstError ? preg_replace('/^Error:\s*/', '', $firstError) : (
                    $explicitProvider
                    ? 'Video generation failed for the selected provider.'
                    : 'All video providers failed.'
                );

                $statusCode = $this->mapVideoErrorToHttpStatus((string) $message);

                return new JsonResponse([
                    'success' => false,
                    'text' => (string) $message,
                    'providers_tried' => $providersToTry,
                    'errors' => $errors,
                ], $statusCode);
            }

            if (\is_string($result)) {
                $raw = trim($result);

                $normalized = [
                    'content' => null,
                    'url' => null,
                    'id' => null,
                    'status' => null,
                    'is_base64' => false,
                    'content_type' => 'video/mp4',
                    'revised_prompt' => null,
                ];

                if ($this->looksLikeUrl($raw)) {
                    $normalized['url'] = $raw;
                } elseif ($this->looksLikeBase64($raw)) {
                    $normalized['content'] = $raw;
                    $normalized['is_base64'] = true;
                } else {
                    $normalized['id'] = $raw;
                }

                return new JsonResponse([
                    'success' => true,
                    'text' => (string) ($normalized['url'] ?? $normalized['content'] ?? $normalized['id'] ?? ''),
                    'result' => $normalized,
                    'provider_used' => $providerUsed,
                    'providers_tried' => $providersToTry,
                    'errors' => $errors,
                ]);
            }

            if (!\is_array($result)) {
                return new JsonResponse([
                    'success' => false,
                    'text' => 'Provider returned an unsupported response type.',
                ], 500);
            }

            $result['is_base64'] = (bool) ($result['is_base64'] ?? false);
            $result['content_type'] = (string) ($result['content_type'] ?? 'video/mp4');
            $result['revised_prompt'] = $result['revised_prompt'] ?? null;

            $url = isset($result['url']) && \is_string($result['url']) ? trim($result['url']) : '';
            $content = isset($result['content']) && \is_string($result['content']) ? trim($result['content']) : '';

            if ('' === $content && '' !== $url && false === (bool) ($result['is_base64'] ?? false)) {
                try {
                    $fetched = $this->fetchUrlAsBase64($url, 15 * 1024 * 1024);
                    $result['content'] = $fetched['content'];
                    $result['content_type'] = $fetched['content_type'];
                    $result['is_base64'] = true;
                    $result['url'] = null;
                } catch (Throwable $e) {
                    error_log('[AI][video] Failed to fetch video URL as base64: '.$e->getMessage());
                }
            }

            $text = '';
            if (isset($result['url']) && \is_string($result['url']) && '' !== trim($result['url'])) {
                $text = trim($result['url']);
            } elseif (isset($result['content']) && \is_string($result['content']) && '' !== trim($result['content'])) {
                $text = trim($result['content']);
            } elseif (isset($result['id']) && \is_string($result['id']) && '' !== trim($result['id'])) {
                $text = trim($result['id']);
            }

            return new JsonResponse([
                'success' => true,
                'text' => $text,
                'result' => $result,
                'provider_used' => $providerUsed,
                'providers_tried' => $providersToTry,
                'errors' => $errors,
            ]);
        } catch (Exception $e) {
            error_log('[AI][video] Video generation failed: '.$e->getMessage());

            return new JsonResponse([
                'success' => false,
                'text' => 'An error occurred while generating the video. Please contact the administrator.',
            ], 500);
        }
    }

    #[Route('/video_job/{id}', name: 'chamilo_core_ai_video_job', methods: ['GET'])]
    public function videoJobStatus(string $id, Request $request): JsonResponse
    {
        try {
            try {
                $this->denyIfNotTeacher();
            } catch (AccessDeniedException $e) {
                return new JsonResponse([
                    'success' => false,
                    'text' => 'Access denied.',
                ], 403);
            }

            $aiProvider = $request->query->get('ai_provider');

            $aiService = $this->aiProviderFactory->getProvider($aiProvider, 'video');
            if (!$aiService instanceof AiVideoProviderInterface) {
                return new JsonResponse([
                    'success' => false,
                    'text' => 'Selected AI provider does not support video generation.',
                ], 400);
            }

            if (!method_exists($aiService, 'getVideoJobStatus')) {
                return new JsonResponse([
                    'success' => false,
                    'text' => 'This AI provider does not expose a video job status method.',
                ], 400);
            }

            $job = $aiService->getVideoJobStatus($id);
            if (empty($job)) {
                return new JsonResponse([
                    'success' => false,
                    'text' => 'Failed to fetch video job status.',
                ], 500);
            }

            $status = (string) ($job['status'] ?? '');
            $jobError = isset($job['error']) && \is_string($job['error']) ? trim($job['error']) : '';

            $result = [
                'id' => (string) ($job['id'] ?? $id),
                'status' => $status,
                'content' => null,
                'url' => null,
                'is_base64' => false,
                'content_type' => 'video/mp4',
                'revised_prompt' => null,
                'error' => '' !== $jobError ? $jobError : null,
            ];

            if (\in_array($status, ['completed', 'succeeded', 'done'], true)) {
                if (method_exists($aiService, 'getVideoJobContentAsBase64')) {
                    $maxBytes = 15 * 1024 * 1024;
                    $content = $aiService->getVideoJobContentAsBase64($id, $maxBytes);

                    if (\is_array($content)) {
                        $result['is_base64'] = (bool) ($content['is_base64'] ?? false);
                        $result['content'] = $content['content'] ?? null;
                        $result['url'] = $content['url'] ?? null;
                        $result['content_type'] = (string) ($content['content_type'] ?? 'video/mp4');

                        if (!empty($content['error'])) {
                            $result['error'] = \is_string($content['error']) ? trim($content['error']) : (string) $content['error'];

                            return new JsonResponse([
                                'success' => true,
                                'text' => (string) $result['error'],
                                'result' => $result,
                                'provider_used' => $aiProvider,
                            ]);
                        }
                    }
                }
            }

            return new JsonResponse([
                'success' => true,
                'text' => '' !== $jobError ? $jobError : '',
                'result' => $result,
                'provider_used' => $aiProvider,
            ]);
        } catch (Exception $e) {
            error_log('[AI][video] Video job status failed: '.$e->getMessage());

            return new JsonResponse([
                'success' => false,
                'text' => 'An error occurred while checking the video status. Please contact the administrator.',
            ], 500);
        }
    }
    #[Route('/document_feedback', name: 'chamilo_core_ai_document_feedback', methods: ['POST'])]
    public function documentFeedback(Request $request): JsonResponse
    {
        $debug = false;

        try {
            $debug = (bool) $this->getParameter('kernel.debug');
        } catch (Throwable) {
            $debug = false;
        }

        try {
            $this->denyIfNotTeacher();
        } catch (AccessDeniedException) {
            return new JsonResponse(['success' => false, 'text' => 'Access denied.'], 403);
        }

        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            return new JsonResponse(['success' => false, 'text' => 'Invalid JSON payload.'], 400);
        }

        $cid = (int) ($data['cid'] ?? 0);
        $sid = (int) ($data['sid'] ?? 0);
        $gid = (int) ($data['gid'] ?? 0);
        $language = (string) ($data['language'] ?? 'en');
        $prompt = trim((string) ($data['prompt'] ?? ''));
        $aiProvider = isset($data['ai_provider']) ? (string) $data['ai_provider'] : null;

        $resourceFileId = (int) ($data['resource_file_id'] ?? 0);
        $documentTitle = trim((string) ($data['document_title'] ?? ''));

        if (0 === $cid || 0 === $resourceFileId || '' === $prompt) {
            return new JsonResponse(['success' => false, 'text' => 'Invalid request parameters.'], 400);
        }

        /** @var Course|null $course */
        $course = $this->em->getRepository(Course::class)->find($cid);
        if (null === $course) {
            return new JsonResponse(['success' => false, 'text' => 'Course not found.'], 404);
        }

        /** @var ResourceFile|null $resourceFile */
        $resourceFile = $this->em->getRepository(ResourceFile::class)->find($resourceFileId);
        if (null === $resourceFile) {
            return new JsonResponse(['success' => false, 'text' => 'Resource file not found.'], 404);
        }

        $node = $resourceFile->getResourceNode();
        if (null === $node) {
            return new JsonResponse(['success' => false, 'text' => 'Resource node not found.'], 404);
        }

        // Security: ensure this resource belongs to the course through ResourceLinks
        $belongsToCourse = false;
        foreach ($node->getResourceLinks() as $link) {
            $linkCourse = $link->getCourse();
            if ($linkCourse && (int) $linkCourse->getId() === $cid) {
                $belongsToCourse = true;

                break;
            }
        }

        if (!$belongsToCourse) {
            return new JsonResponse(['success' => false, 'text' => 'Resource does not belong to this course.'], 403);
        }

        $fileUri = $this->resourceNodeRepository->getFilename($resourceFile);
        if (!\is_string($fileUri) || '' === trim($fileUri)) {
            return new JsonResponse(['success' => false, 'text' => 'Could not resolve resource file URI.'], 500);
        }

        try {
            $binary = (string) $this->resourceNodeRepository->getFileSystem()->read($fileUri);
        } catch (Throwable $e) {
            if ($debug) {
                error_log('[AI][document_feedback] Failed to read file: '.$e->getMessage());
            }

            return new JsonResponse(['success' => false, 'text' => 'Failed to read file content.'], 500);
        }

        if ('' === $binary) {
            return new JsonResponse(['success' => false, 'text' => 'File is empty.'], 400);
        }

        $filename = basename($fileUri);

        $mimeType = 'application/octet-stream';
        if (method_exists($resourceFile, 'getMimeType')) {
            $mt = (string) $resourceFile->getMimeType();
            if ('' !== trim($mt)) {
                $mimeType = $mt;
            }
        }

        // Use extension as a fallback (some files come as application/octet-stream)
        $ext = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));
        $isPdf = ('pdf' === $ext) || ('application/pdf' === $mimeType);
        $isTxt = ('txt' === $ext) || str_starts_with(strtolower($mimeType), 'text/plain');

        // Size limits (different per type)
        $maxBytesPdf = 10 * 1024 * 1024;
        $maxBytesTxt = 1 * 1024 * 1024;

        if ($isPdf && \strlen($binary) > $maxBytesPdf) {
            return new JsonResponse(['success' => false, 'text' => 'Document is too large to analyze.'], 413);
        }
        if ($isTxt && \strlen($binary) > $maxBytesTxt) {
            return new JsonResponse(['success' => false, 'text' => 'Text file is too large to analyze.'], 413);
        }

        // If not pdf/txt => fail early (don’t call provider)
        if (!$isPdf && !$isTxt) {
            return new JsonResponse([
                'success' => false,
                'text' => 'Unsupported file type. Supported: PDF, TXT.',
            ], 415);
        }

        $courseTitle = (string) $course->getTitle();
        $docLabel = '' !== $documentTitle ? $documentTitle : (string) ($node->getTitle() ?? $filename);

        // Base prompt (same structure for both)
        $basePrompt = $this->buildDocumentFeedbackPrompt($courseTitle, $docLabel, $prompt, $language, $sid);

        try {
            // TXT: task_grader-style -> send content as text to TEXT provider
            if ($isTxt) {
                $text = $this->normalizePlainText($binary);

                // Keep it safe: truncate very long text (tokens)
                $maxChars = 20000;
                $truncated = false;
                if (mb_strlen($text) > $maxChars) {
                    $text = mb_substr($text, 0, $maxChars);
                    $truncated = true;
                }

                $fullPrompt = $basePrompt
                    ."\n\nDocument content (plain text):\n"
                    .$text
                    .($truncated ? "\n\n[Content truncated]" : '');

                $provider = $this->aiProviderFactory->getProvider($aiProvider, 'text');

                if (!\is_object($provider) || !method_exists($provider, 'generateText')) {
                    return new JsonResponse([
                        'success' => false,
                        'text' => 'Selected AI provider does not support text generation.',
                    ], 400);
                }

                try {
                    $raw = (string) $provider->generateText($fullPrompt, [
                        'language' => $language,
                        'tool' => 'document_analyzer_txt',
                        'cid' => $cid,
                        'sid' => $sid,
                        'gid' => $gid,
                    ]);
                } catch (TypeError) {
                    // Backward compatibility
                    $raw = (string) $provider->generateText($fullPrompt, $language);
                }

                $result = trim($raw);

                if ('' === $result) {
                    return new JsonResponse(['success' => false, 'text' => 'AI request returned an empty response.'], 500);
                }

                if (str_starts_with($result, 'Error:')) {
                    $msg = trim((string) preg_replace('/^Error:\s*/', '', $result));
                    $status = $this->mapDocumentErrorToHttpStatus($msg);

                    return new JsonResponse(['success' => false, 'text' => '' !== $msg ? $msg : $result], $status);
                }

                return new JsonResponse(['success' => true, 'text' => $result]);
            }

            // PDF: send as file to DOCUMENT_PROCESS provider
            $provider = $this->aiProviderFactory->getProvider($aiProvider, 'document_process');

            if (!$provider instanceof AiDocumentProcessProviderInterface) {
                return new JsonResponse([
                    'success' => false,
                    'text' => 'Selected AI provider does not support document processing.',
                ], 400);
            }

            $result = $provider->processDocument(
                $basePrompt,
                'document_analyzer_pdf',
                $filename,
                'application/pdf', // force consistent mime
                $binary,
                [
                    'language' => $language,
                    'cid' => $cid,
                    'sid' => $sid,
                    'gid' => $gid,
                ]
            );

            $result = \is_string($result) ? trim($result) : '';

            if ('' === $result) {
                return new JsonResponse(['success' => false, 'text' => 'AI request returned an empty response.'], 500);
            }

            if (str_starts_with($result, 'Error:')) {
                $msg = trim((string) preg_replace('/^Error:\s*/', '', $result));
                $status = $this->mapDocumentErrorToHttpStatus($msg);

                return new JsonResponse(['success' => false, 'text' => '' !== $msg ? $msg : $result], $status);
            }

            return new JsonResponse(['success' => true, 'text' => $result]);
        } catch (Throwable $e) {
            $msg = trim($e->getMessage());
            if ($debug) {
                error_log('[AI][document_feedback] Exception: '.$msg);
            }

            $status = $this->mapDocumentErrorToHttpStatus($msg);

            return new JsonResponse([
                'success' => false,
                'text' => $debug && '' !== $msg ? $msg : 'An error occurred while analyzing the document.',
            ], $status);
        }
    }

    #[Route('/document_feedback/save_to_inbox', name: 'chamilo_core_ai_document_feedback_save_to_inbox', methods: ['POST'])]
    public function saveDocumentFeedbackToInbox(Request $request): JsonResponse
    {
        try {
            $this->denyIfNotTeacher();
        } catch (AccessDeniedException) {
            return new JsonResponse(['success' => false, 'text' => 'Access denied.'], 403);
        }

        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            return new JsonResponse(['success' => false, 'text' => 'Invalid JSON payload.'], 400);
        }

        $cid = (int) ($data['cid'] ?? 0);
        $documentTitle = trim((string) ($data['document_title'] ?? ''));
        $answer = trim((string) ($data['answer'] ?? ''));

        if (0 === $cid || '' === $documentTitle || '' === $answer) {
            return new JsonResponse(['success' => false, 'text' => 'Invalid request parameters.'], 400);
        }

        /** @var Course|null $course */
        $course = $this->em->getRepository(Course::class)->find($cid);
        if (null === $course) {
            return new JsonResponse(['success' => false, 'text' => 'Course not found.'], 404);
        }

        $user = $this->getUser();
        if (!\is_object($user) || !method_exists($user, 'getId')) {
            return new JsonResponse(['success' => false, 'text' => 'User is not authenticated.'], 401);
        }

        $userId = (int) $user->getId();
        $courseTitle = (string) $course->getTitle();

        // "AI feedback on %s in course %s"
        $subjectTpl = $this->translator->trans('AI feedback on %s in course %s');
        $subject = \sprintf($subjectTpl, $documentTitle, $courseTitle);

        try {
            $safeHtml = '<pre style="white-space:pre-wrap;">'
                .htmlspecialchars($answer, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                .'</pre>';

            $messageId = $this->messageHelper->sendMessageSimple(
                $userId,
                $subject,
                $safeHtml,
                $userId,
                false,
                false
            );

            if (null === $messageId) {
                $this->debugLog('[AI][document_feedback][inbox] MessageHelper returned null (message not created).');

                return new JsonResponse([
                    'success' => false,
                    'text' => 'Failed to save answer to inbox.',
                ], 500);
            }

            return new JsonResponse(['success' => true, 'message_id' => $messageId]);
        } catch (Throwable $e) {
            $this->debugLog('[AI][document_feedback][inbox] Exception: '.$e->getMessage());

            return new JsonResponse([
                'success' => false,
                'text' => $this->debug ? $e->getMessage() : 'Failed to save answer to inbox.',
            ], 500);
        }
    }

    /**
     * Build a stable prompt that always includes course/document context.
     */
    private function buildDocumentFeedbackPrompt(
        string $courseTitle,
        string $documentTitle,
        string $teacherPrompt,
        string $language,
        int $sid
    ): string {
        $teacherPrompt = trim($teacherPrompt);

        $base = "You are an expert educational content reviewer.\n";
        $base .= "Language: {$language}\n";
        $base .= "Course: {$courseTitle}\n";
        $base .= "Document: {$documentTitle}\n";
        if ($sid > 0) {
            $base .= "Session ID: {$sid}\n";
        }
        $base .= "\nTeacher request:\n{$teacherPrompt}\n";
        $base .= "\nReturn clear, structured feedback with actionable suggestions.\n";
        $base .= "\nFormatting rules:\n";
        $base .= "- Return plain text only (no Markdown).\n";
        $base .= "- Do not use **, #, ``` or HTML.\n";
        $base .= "- Use short headings and bullet points with '-'.\n";

        return $base;
    }

    /**
     * Returns a reasonable HTTP status code for known provider errors.
     */
    private function mapVideoErrorToHttpStatus(string $message): int
    {
        $m = strtolower(trim($message));

        if ('' === $m) {
            return 500;
        }

        if (str_contains($m, 'invalid api key') || str_contains($m, 'incorrect api key') || str_contains($m, 'unauthorized')) {
            return 401;
        }

        if (str_contains($m, 'must be verified') || str_contains($m, 'verify organization') || str_contains($m, 'organization must be verified')) {
            return 403;
        }

        if (str_contains($m, 'does not have access') || str_contains($m, 'not authorized') || str_contains($m, 'permission')) {
            return 403;
        }

        if (str_contains($m, 'rate limit') || str_contains($m, 'too many requests')) {
            return 429;
        }

        if (str_contains($m, 'insufficient_quota') || str_contains($m, 'quota')) {
            return 402;
        }

        return 500;
    }

    private function looksLikeUrl(string $s): bool
    {
        $s = trim($s);
        if ('' === $s) {
            return false;
        }

        return (bool) filter_var($s, FILTER_VALIDATE_URL);
    }

    private function looksLikeBase64(string $s): bool
    {
        $s = trim($s);
        if ('' === $s || \strlen($s) < 64) {
            return false;
        }

        if (!preg_match('/^[A-Za-z0-9+\/=\r\n]+$/', $s)) {
            return false;
        }

        $decoded = base64_decode($s, true);
        if (false === $decoded) {
            return false;
        }

        return '' !== $decoded;
    }

    private function denyIfNotTeacher(): void
    {
        if (
            !$this->isGranted('ROLE_CURRENT_COURSE_TEACHER')
            && !$this->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
            && !$this->isGranted('ROLE_TEACHER')
        ) {
            throw new AccessDeniedException('Access denied.');
        }
    }

    private function fetchUrlAsBase64(string $url, int $maxBytes = 10485760): array
    {
        if (!$this->isSafeRemoteUrl($url)) {
            throw new RuntimeException('Remote URL is not allowed.');
        }

        $response = $this->httpClient->request('GET', $url, [
            'headers' => [
                'Accept' => '*/*',
            ],
        ]);

        $headers = $response->getHeaders(false);
        $contentType = $headers['content-type'][0] ?? 'application/octet-stream';

        $lenHeader = $headers['content-length'][0] ?? null;
        if (null !== $lenHeader && is_numeric($lenHeader) && (int) $lenHeader > $maxBytes) {
            throw new RuntimeException('Remote content is too large to inline as base64.');
        }

        $binary = $response->getContent(false);

        if (\strlen($binary) > $maxBytes) {
            throw new RuntimeException('Remote content exceeded the maximum allowed size.');
        }

        return [
            'content' => base64_encode($binary),
            'content_type' => (string) $contentType,
            'is_base64' => true,
            'url' => null,
        ];
    }

    private function isSafeRemoteUrl(string $url): bool
    {
        $parts = parse_url($url);
        if (!\is_array($parts)) {
            return false;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (!\in_array($scheme, ['https'], true)) {
            return false;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        if ('' === $host) {
            return false;
        }

        if (\in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return false;
        }

        $ip = gethostbyname($host);
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return false;
            }
        }

        return true;
    }

    private function getGenericCourseDescription(int $cid, int $sid): string
    {
        try {
            $repo = $this->em->getRepository(CGlossary::class);

            if ($repo instanceof CGlossaryRepository) {
                return $repo->getGenericCourseDescription($cid, $sid);
            }
        } catch (Throwable) {
            // Ignore repository instantiation differences.
        }

        return '';
    }

    /**
     * Normalize plain text content (best-effort) into UTF-8.
     */
    private function normalizePlainText(string $binary): string
    {
        // If mbstring is missing, just return as-is.
        if (!\function_exists('mb_detect_encoding') || !\function_exists('mb_convert_encoding')) {
            return trim($binary);
        }

        $enc = mb_detect_encoding($binary, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if (!$enc) {
            return trim($binary);
        }

        return trim((string) mb_convert_encoding($binary, 'UTF-8', $enc));
    }

    /**
     * Map known provider/user errors to better HTTP status codes.
     */
    private function mapDocumentErrorToHttpStatus(string $message): int
    {
        $m = strtolower(trim($message));

        if ('' === $m) {
            return 500;
        }

        if (str_contains($m, 'file type') && str_contains($m, 'not supported')) {
            return 415;
        }

        if (str_contains($m, 'too large') || str_contains($m, 'exceeds') || str_contains($m, 'maximum')) {
            return 413;
        }

        if (str_contains($m, 'invalid api key') || str_contains($m, 'incorrect api key') || str_contains($m, 'unauthorized')) {
            return 401;
        }

        if (str_contains($m, 'rate limit') || str_contains($m, 'too many requests')) {
            return 429;
        }

        if (str_contains($m, 'insufficient_quota') || str_contains($m, 'quota')) {
            return 402;
        }

        return 500;
    }

    private function debugLog(string $message): void
    {
        if (!$this->debug) {
            return;
        }

        error_log($message);
    }
}
