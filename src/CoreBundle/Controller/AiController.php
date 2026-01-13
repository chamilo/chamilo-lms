<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\AiProvider\AiImageProviderInterface;
use Chamilo\CoreBundle\AiProvider\AiProviderFactory;
use Chamilo\CoreBundle\AiProvider\AiVideoProviderInterface;
use Chamilo\CoreBundle\Entity\TrackEDefault;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Repository\TrackEAttemptRepository;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Question;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use const FILTER_SANITIZE_NUMBER_INT;

#[Route('/ai')]
class AiController extends AbstractController
{
    public function __construct(
        private readonly AiProviderFactory $aiProviderFactory,
        private readonly TrackEAttemptRepository $attemptRepo,
        private readonly EntityManagerInterface $em,
        private readonly HttpClientInterface $httpClient,
    ) {}

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
            ],
        ]);
    }

    #[Route('/generate_aiken', name: 'chamilo_core_ai_generate_aiken', methods: ['POST'])]
    public function generateAiken(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (!is_array($data)) {
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
            if (!is_array($data)) {
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

                if (!in_array($explicitProvider, $availableProviders, true)) {
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
                } catch (\Throwable $e) {
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

            $content = isset($result['content']) && \is_string($result['content']) ? trim($result['content']) : '';
            $url = isset($result['url']) && \is_string($result['url']) ? trim($result['url']) : '';
            $isBase64 = (bool) ($result['is_base64'] ?? false);
            $contentType = (string) ($result['content_type'] ?? 'image/png');

            if (!$isBase64 && '' === $content && '' !== $url) {
                try {
                    $fetched = $this->fetchUrlAsBase64($url, 10 * 1024 * 1024);
                    $result['content'] = $fetched['content'];
                    $result['content_type'] = $fetched['content_type'];
                    $result['is_base64'] = true;
                    $result['url'] = null;
                } catch (\Throwable $e) {
                    error_log('[AI][image] Failed to fetch image URL as base64: '.$e->getMessage());

                    return new JsonResponse([
                        'success' => false,
                        'text' => 'Image was generated, but could not be converted to base64 for preview.',
                    ], 500);
                }
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
            if (!is_array($data)) {
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

            // Optional overrides
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

                if (!in_array($explicitProvider, $availableProviders, true)) {
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

                    if (null !== $seconds && $seconds !== '') {
                        $options['seconds'] = $seconds; // must be string: "4"|"8"|"12"
                    }
                    if (null !== $size && $size !== '') {
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
                } catch (\Throwable $e) {
                    $errors[$providerName] = $e->getMessage();
                    $result = null;
                    continue;
                }
            }

            if (null === $providerUsed || empty($result)) {
                error_log('[AI][video] Video generation failed for all providers: '.json_encode($errors));

                $firstError = '';
                foreach ($errors as $err) {
                    if (is_string($err) && '' !== trim($err)) {
                        $firstError = trim($err);
                        break;
                    }
                }

                $message = $firstError !== '' ? preg_replace('/^Error:\s*/', '', $firstError) : (
                $explicitProvider
                    ? 'Video generation failed for the selected provider.'
                    : 'All video providers failed.'
                );

                $statusCode = $this->mapVideoErrorToHttpStatus($message);

                return new JsonResponse([
                    'success' => false,
                    'text' => $message,
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

            if (!is_array($result)) {
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

            if (empty($content) && !empty($url) && false === (bool) ($result['is_base64'] ?? false)) {
                try {
                    $fetched = $this->fetchUrlAsBase64($url, 15 * 1024 * 1024);
                    $result['content'] = $fetched['content'];
                    $result['content_type'] = $fetched['content_type'];
                    $result['is_base64'] = true;
                    $result['url'] = null;
                } catch (\Throwable $e) {
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

    /**
     * Returns a reasonable HTTP status code for known provider errors.
     */
    private function mapVideoErrorToHttpStatus(string $message): int
    {
        $m = strtolower(trim($message));

        if ($m === '') {
            return 500;
        }

        // OpenAI typical cases
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
        if ($s === '') {
            return false;
        }

        return (bool) filter_var($s, FILTER_VALIDATE_URL);
    }

    private function looksLikeBase64(string $s): bool
    {
        $s = trim($s);
        if ($s === '' || strlen($s) < 64) {
            return false;
        }

        // Basic base64 charset check
        if (!preg_match('/^[A-Za-z0-9+\/=\r\n]+$/', $s)) {
            return false;
        }

        // Validate decode (strict)
        $decoded = base64_decode($s, true);
        if ($decoded === false) {
            return false;
        }

        // Video will likely be binary; just ensure not empty
        return $decoded !== '';
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
            $jobError = isset($job['error']) && is_string($job['error']) ? trim($job['error']) : '';

            $result = [
                'id' => (string) ($job['id'] ?? $id),
                'status' => $status,
                'content' => null,
                'url' => null,
                'is_base64' => false,
                'content_type' => 'video/mp4',
                'revised_prompt' => null,
                'error' => $jobError !== '' ? $jobError : null,
            ];

            if (in_array($status, ['completed', 'succeeded', 'done'], true)) {
                if (method_exists($aiService, 'getVideoJobContentAsBase64')) {
                    $maxBytes = 15 * 1024 * 1024;
                    $content = $aiService->getVideoJobContentAsBase64($id, $maxBytes);

                    if (is_array($content)) {
                        $result['is_base64'] = (bool) ($content['is_base64'] ?? false);
                        $result['content'] = $content['content'] ?? null;
                        $result['url'] = $content['url'] ?? null;
                        $result['content_type'] = (string) ($content['content_type'] ?? 'video/mp4');

                        if (!empty($content['error'])) {
                            $result['error'] = is_string($content['error']) ? trim($content['error']) : (string) $content['error'];

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
                'text' => $jobError !== '' ? $jobError : '',
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

    private function denyIfNotTeacher(): void
    {
        if (!$this->isGranted('ROLE_CURRENT_COURSE_TEACHER')
            && !$this->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
            && !$this->isGranted('ROLE_TEACHER')
        ) {
            throw new \RuntimeException('Access denied.');
        }
    }

    private function fetchUrlAsBase64(string $url, int $maxBytes = 10485760): array
    {
        if (!$this->isSafeRemoteUrl($url)) {
            throw new \RuntimeException('Remote URL is not allowed.');
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
            throw new \RuntimeException('Remote content is too large to inline as base64.');
        }

        $binary = $response->getContent(false);

        if (strlen($binary) > $maxBytes) {
            throw new \RuntimeException('Remote content exceeded the maximum allowed size.');
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
        if (!is_array($parts)) {
            return false;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (!in_array($scheme, ['https'], true)) {
            return false;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        if ('' === $host) {
            return false;
        }

        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return false;
        }

        $ip = gethostbyname($host);
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            // Block private/reserved ranges (basic SSRF hardening).
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return false;
            }
        }

        return true;
    }
}
