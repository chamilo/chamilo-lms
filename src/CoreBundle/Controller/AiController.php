<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\AiProvider\AiDocumentProcessProviderInterface;
use Chamilo\CoreBundle\AiProvider\AiImageProviderInterface;
use Chamilo\CoreBundle\AiProvider\AiProviderFactory;
use Chamilo\CoreBundle\AiProvider\AiVideoJobProviderInterface;
use Chamilo\CoreBundle\AiProvider\AiVideoProviderInterface;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\TrackEDefault;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Helpers\AiDisclosureHelper;
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
use Chamilo\CoreBundle\Security\Authorization\Voter\CourseVoter;
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
use const PHP_URL_QUERY;

#[IsGranted('ROLE_USER')]
#[Route('/ai')]
class AiController extends AbstractController
{
    private bool $debug = false;
    private const ACTIVE_MEDIA_PROVIDER_SESSION_PREFIX = 'ai_media_active_provider_';

    public function __construct(
        private readonly AiProviderFactory $aiProviderFactory,
        private readonly TrackEAttemptRepository $attemptRepo,
        private readonly EntityManagerInterface $em,
        private readonly HttpClientInterface $httpClient,
        private readonly TranslatorInterface $translator,
        private readonly ResourceNodeRepository $resourceNodeRepository,
        private readonly MessageHelper $messageHelper,
        private readonly AiDisclosureHelper $aiDisclosureHelper,
    ) {}

    #[Route('/text_providers', name: 'chamilo_core_ai_text_providers', methods: ['GET'])]
    public function textProviders(): JsonResponse
    {
        try {
            $this->denyIfNotTeacher();
        } catch (AccessDeniedException $e) {
            return new JsonResponse(['providers' => []], 403);
        }

        // Expected format from factory: ['openai' => 'openai (gpt-4o)', 'mistral' => 'mistral (mistral-large-latest)', ...]
        $raw = $this->aiProviderFactory->getProvidersForType('text');

        $providers = [];
        foreach ($raw as $key => $label) {
            // If it's a numeric array, fallback to value as both key+label.
            if (\is_int($key)) {
                $providers[] = [
                    'key' => (string) $label,
                    'label' => (string) $label,
                ];

                continue;
            }

            $providers[] = [
                'key' => (string) $key,
                'label' => (string) $label,
            ];
        }

        return new JsonResponse(['providers' => $providers]);
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
        $providerName = null;

        $providerRaw = $data['ai_provider'] ?? null;
        if (\is_array($providerRaw)) {
            // Front-end may send {key,label}
            $providerName = isset($providerRaw['key']) ? trim((string) $providerRaw['key']) : null;
            if (null === $providerName || '' === $providerName) {
                $providerName = isset($providerRaw['name']) ? trim((string) $providerRaw['name']) : null;
            }
        } elseif (\is_string($providerRaw)) {
            $providerName = trim($providerRaw);
        }

        if ('' === (string) $providerName) {
            $providerName = null; // Use factory default
        }
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

            // Audit only (do not modify the glossary terms text output).
            $this->aiDisclosureHelper->logAudit(
                targetKey: 'course:'.$cid.':glossary_terms:'.sha1($prompt.'|'.$language.'|'.$n),
                userId: $this->getCurrentUserId(),
                meta: [
                    'feature' => 'glossary_generator',
                    'mode' => 'generated',
                    'provider' => $providerName ?? 'default',
                    'tool' => $toolName,
                    'n' => $n,
                    'language' => $language,
                ],
                courseId: $cid,
                sessionId: api_get_session_id()
            );

            return new JsonResponse([
                'success' => true,
                'text' => $raw,
                'ai_assisted' => $this->aiDisclosureHelper->isDisclosureEnabled(),
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
        $typesText = $this->aiProviderFactory->getProvidersForType('text');
        $typesImage = $this->aiProviderFactory->getProvidersForType('image');
        $typesVideo = $this->aiProviderFactory->getProvidersForType('video');
        $typesDocument = $this->aiProviderFactory->getProvidersForType('document');
        $typesDocumentProcess = $this->aiProviderFactory->getProvidersForType('document_process');

        return new JsonResponse([
            'success' => true,

            // Keep existing output (backward compatible)
            'types' => [
                'text' => $typesText,
                'image' => $typesImage,
                'video' => $typesVideo,
                'document' => $typesDocument,
                'document_process' => $typesDocumentProcess,
            ],

            'providers' => [
                'text' => $this->normalizeProvidersForJson($typesText),
                'image' => $this->normalizeProvidersForJson($typesImage),
                'video' => $this->normalizeProvidersForJson($typesVideo),
                'document' => $this->normalizeProvidersForJson($typesDocument),
                'document_process' => $this->normalizeProvidersForJson($typesDocumentProcess),
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

    #[Route('/generate_learnpath', name: 'chamilo_core_ai_generate_learnpath', methods: ['POST'])]
    public function generateLearnPath(Request $request): JsonResponse
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

            $topic = trim((string) ($data['lp_name'] ?? ''));
            $chaptersCount = (int) ($data['nro_items'] ?? 0);
            $wordsCount = (int) ($data['words_count'] ?? 0);
            $language = (string) ($data['language'] ?? 'en');
            $addTests = (bool) ($data['add_tests'] ?? false);
            $numQuestions = (int) ($data['nro_questions'] ?? 0);
            $aiProvider = $data['ai_provider'] ?? null;

            $cid = $this->resolveCourseIdFromRequest($request, $data);
            $sid = $this->resolveSessionIdFromRequest($request, $data);

            if ('' === $topic || $chaptersCount <= 0 || $wordsCount <= 0) {
                return new JsonResponse([
                    'success' => false,
                    'text' => 'Invalid request parameters. Ensure all fields are filled correctly.',
                ], 400);
            }

            if ($addTests) {
                if ($numQuestions <= 0) {
                    $numQuestions = 2;
                }
                if ($numQuestions > 5) {
                    $numQuestions = 5;
                }
            }

            $provider = $this->aiProviderFactory->getProvider($aiProvider, 'text');

            if (!\is_object($provider) || !method_exists($provider, 'generateLearnPath')) {
                return new JsonResponse([
                    'success' => false,
                    'text' => 'Selected AI provider does not support learning path generation.',
                ], 400);
            }

            $result = $provider->generateLearnPath($topic, $chaptersCount, $language, $wordsCount, $addTests, $numQuestions);

            if (empty($result)) {
                return new JsonResponse([
                    'success' => false,
                    'text' => 'AI request returned an empty response.',
                ], 500);
            }

            if (\is_string($result) && str_starts_with($result, 'Error:')) {
                return new JsonResponse([
                    'success' => false,
                    'text' => $result,
                ], 500);
            }

            if (\is_array($result) && isset($result['success']) && false === (bool) $result['success']) {
                $msg = isset($result['message']) ? (string) $result['message'] : 'Learning path generation failed.';

                return new JsonResponse([
                    'success' => false,
                    'text' => $msg,
                ], 500);
            }

            // Audit (provider/model details stay in DB, not in response).
            $this->aiDisclosureHelper->logAudit(
                targetKey: 'course:'.$cid.':learnpath:'.sha1($topic.'|'.$language.'|'.$chaptersCount.'|'.$wordsCount.'|'.(int) $addTests.'|'.$numQuestions),
                userId: $this->getCurrentUserId(),
                meta: [
                    'feature' => 'learnpath_generator',
                    'mode' => 'generated',
                    'provider' => \is_string($aiProvider) && '' !== trim((string) $aiProvider) ? trim((string) $aiProvider) : 'default',
                    'language' => $language,
                    'chapters' => $chaptersCount,
                    'words' => $wordsCount,
                    'add_tests' => $addTests,
                    'num_questions' => $numQuestions,
                ],
                courseId: $cid > 0 ? $cid : 0,
                sessionId: $sid > 0 ? $sid : api_get_session_id()
            );

            return new JsonResponse([
                'success' => true,
                'data' => $result,
                'ai_assisted' => $this->aiDisclosureHelper->isDisclosureEnabled(),
            ]);
        } catch (Throwable $e) {
            error_log('[AI][learnpath] Generation failed: '.$e->getMessage());

            return new JsonResponse([
                'success' => false,
                'text' => 'An error occurred while generating the learning path.',
            ], 500);
        }
    }

    #[Route('/generate_aiken', name: 'chamilo_core_ai_generate_aiken', methods: ['POST'])]
    public function generateAiken(Request $request): JsonResponse
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

            $nQ = (int) ($data['nro_questions'] ?? 0);
            $language = (string) ($data['language'] ?? 'en');
            $topic = trim((string) ($data['quiz_name'] ?? ''));
            $questionType = (string) ($data['question_type'] ?? 'multiple_choice');
            $aiProvider = $data['ai_provider'] ?? null;
            $cid = (int) ($data['cid'] ?? 0);
            $sid = (int) ($data['sid'] ?? 0);

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

            $questionsText = $this->sanitizeGeneratedAikenText((string) $questions);

            $this->aiDisclosureHelper->logAudit(
                targetKey: 'course:'.$cid.':aiken:'.sha1($topic.'|'.$language.'|'.$nQ.'|'.$questionType),
                userId: $this->getCurrentUserId(),
                meta: [
                    'feature' => 'exercise_generator_aiken',
                    'mode' => 'generated',
                    'provider' => \is_string($aiProvider) && '' !== trim((string) $aiProvider) ? trim((string) $aiProvider) : 'default',
                    'language' => $language,
                    'question_type' => $questionType,
                    'n' => $nQ,
                ],
                courseId: $cid > 0 ? $cid : 0,
                sessionId: $sid > 0 ? $sid : api_get_session_id()
            );

            return new JsonResponse([
                'success' => true,
                'text' => $questionsText,
                'ai_assisted' => $this->aiDisclosureHelper->isDisclosureEnabled(),
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

        $course = $this->em->getRepository(Course::class)->find($courseId);
        if (!$course) {
            return $this->json(['error' => 'Course not found'], 404);
        }

        $this->denyAccessUnlessGranted(CourseVoter::EDIT, $course);

        // Optional provider selection (form-encoded)
        $aiProvider = $request->request->get('ai_provider');

        // Backward compatibility: allow JSON payload too
        if (null === $aiProvider || '' === trim((string) $aiProvider)) {
            $json = json_decode((string) $request->getContent(), true);
            if (\is_array($json) && isset($json['ai_provider'])) {
                $aiProvider = $json['ai_provider'];
            }
        }

        // Normalize: empty string means "use default provider"
        if (null !== $aiProvider) {
            $aiProvider = trim((string) $aiProvider);
            if ('' === $aiProvider) {
                $aiProvider = null;
            }
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

        try {
            // When $aiProvider is null => factory should use the default provider
            $provider = $this->aiProviderFactory->getProvider($aiProvider, 'text');
        } catch (Throwable $e) {
            error_log('[AI][open_answer_grade] Provider selection failed: '.$e->getMessage());

            return $this->json([
                'error' => 'AI provider is not supported or not configured.',
            ], 500);
        }

        try {
            $raw = trim((string) $provider->gradeOpenAnswer($prompt, 'open_answer_grade'));
        } catch (Throwable $e) {
            error_log('[AI][open_answer_grade] Provider call failed: '.$e->getMessage());

            return $this->json([
                'error' => 'AI request failed.',
            ], 500);
        }

        if ('' === $raw) {
            return $this->json(['error' => 'AI request failed'], 500);
        }

        // If provider returned an "Error:" string, do not treat it as a successful feedback.
        if (str_starts_with($raw, 'Error:')) {
            $msg = trim((string) preg_replace('/^Error:\s*/', '', $raw));

            $status = 500;
            $m = strtolower($msg);
            if (str_contains($m, 'invalid api key') || str_contains($m, 'incorrect api key') || str_contains($m, 'unauthorized')) {
                $status = 401;
            } elseif (str_contains($m, 'rate limit') || str_contains($m, 'too many requests')) {
                $status = 429;
            } elseif (str_contains($m, 'quota') || str_contains($m, 'insufficient_quota')) {
                $status = 402;
            }

            return $this->json(['error' => $msg], $status);
        }

        if (str_contains($raw, "\n")) {
            [$scoreLine, $feedback] = explode("\n", $raw, 2);
        } else {
            $scoreLine = (string) $maxScore;
            $feedback = $raw;
        }

        $score = (int) filter_var($scoreLine, FILTER_SANITIZE_NUMBER_INT);

        // After you have $exeId (track exercise id) and you are sure it's > 0.
        if ($this->aiDisclosureHelper->isDisclosureEnabled() && $exeId > 0) {
            // Store disclosure metadata in extra fields instead of altering feedback text.
            $this->aiDisclosureHelper->markAiAssistedExtraField('track_exercise', (int) $exeId, true);
        }

        // Keep legacy TrackEDefault event for analytics/backward compatibility.
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

        // Audit record with provider details (stored in DB only).
        $this->aiDisclosureHelper->logAudit(
            targetKey: 'attempt:'.$attempt->getId(),
            userId: $this->getCurrentUserId(),
            meta: [
                'feature' => 'open_answer_grade',
                'mode' => 'co_generated',
                'provider' => \is_string($aiProvider) && '' !== trim((string) $aiProvider) ? trim((string) $aiProvider) : 'default',
                'course_id' => $courseId,
                'question_id' => $questionId,
                'exe_id' => $exeId,
            ],
            courseId: $courseId,
            sessionId: api_get_session_id(),
            flush: false
        );

        $this->em->flush();

        $payload = [
            'score' => $score,
            'feedback' => $feedback,
            'ai_assisted' => $this->aiDisclosureHelper->isDisclosureEnabled(),
        ];

        if ($this->shouldExposeProviderDetails()) {
            $payload['provider_used'] = \is_string($aiProvider) && '' !== trim((string) $aiProvider) ? trim((string) $aiProvider) : 'default';
        }

        return $this->json($payload);
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
            $cid = (int) ($data['cid'] ?? 0);
            $sid = (int) ($data['sid'] ?? 0);

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

                $payload = [
                    'success' => false,
                    'text' => $explicitProvider
                        ? 'Image generation failed for the selected provider.'
                        : 'All image providers failed.',
                ];

                if ($this->shouldExposeProviderDetails()) {
                    $payload['providers_tried'] = $providersToTry;
                    $payload['errors'] = $errors;
                }

                return new JsonResponse($payload, 500);
            }

            // Audit (provider details in DB only).
            $this->aiDisclosureHelper->logAudit(
                targetKey: 'image:'.sha1($prompt.'|'.$toolName.'|'.$language.'|'.$n),
                userId: $this->getCurrentUserId(),
                meta: [
                    'feature' => 'image_generator',
                    'mode' => 'generated',
                    'provider' => $providerUsed,
                    'tool' => $toolName,
                    'language' => $language,
                    'n' => $n,
                ],
                courseId: $cid > 0 ? $cid : 0,
                sessionId: $sid > 0 ? $sid : api_get_session_id()
            );

            if (\is_string($result)) {
                $normalized = [
                    'content' => trim($result),
                    'url' => null,
                    'is_base64' => true,
                    'content_type' => 'image/png',
                    'revised_prompt' => null,
                ];

                $payload = [
                    'success' => true,
                    'text' => $normalized['content'],
                    'result' => $normalized,
                    'ai_assisted' => $this->aiDisclosureHelper->isDisclosureEnabled(),
                ];

                if ($this->shouldExposeProviderDetails()) {
                    $payload['provider_used'] = $providerUsed;
                    $payload['providers_tried'] = $providersToTry;
                    $payload['errors'] = $errors;
                }

                return new JsonResponse($payload);
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

            $payload = [
                'success' => true,
                'text' => $text,
                'result' => $result,
                'ai_assisted' => $this->aiDisclosureHelper->isDisclosureEnabled(),
            ];

            if ($this->shouldExposeProviderDetails()) {
                $payload['provider_used'] = $providerUsed;
                $payload['providers_tried'] = $providersToTry;
                $payload['errors'] = $errors;
            }

            return new JsonResponse($payload);
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
                return new JsonResponse(['success' => false, 'text' => 'Access denied.'], 403);
            }

            $data = json_decode($request->getContent(), true);
            if (!\is_array($data)) {
                return new JsonResponse(['success' => false, 'text' => 'Invalid JSON payload.'], 400);
            }

            $n = (int) ($data['n'] ?? 1);
            if ($n <= 0) {
                $n = 1;
            }

            $language = (string) ($data['language'] ?? 'en');
            $prompt = trim((string) ($data['prompt'] ?? ''));
            $toolName = trim((string) ($data['tool'] ?? 'document'));

            $aiProvider = isset($data['ai_provider']) ? trim((string) $data['ai_provider']) : null;
            if ('' === (string) $aiProvider) {
                $aiProvider = null;
            }

            $cid = (int) ($data['cid'] ?? 0);
            $sid = (int) ($data['sid'] ?? 0);

            // Gemini requires durationSeconds to be a NUMBER (int).
            $seconds = null;
            if (isset($data['seconds'])) {
                $secondsInt = (int) $data['seconds'];
                if ($secondsInt > 0) {
                    $seconds = $secondsInt;
                }
            }

            $size = isset($data['size']) ? trim((string) $data['size']) : null;
            if ('' === (string) $size) {
                $size = null;
            }

            if ('' === $prompt || '' === $toolName) {
                return new JsonResponse([
                    'success' => false,
                    'text' => 'Invalid request parameters. Ensure all fields are filled correctly.',
                ], 400);
            }

            $availableProviders = $this->aiProviderFactory->getProvidersForType('video');
            if (empty($availableProviders)) {
                return new JsonResponse(['success' => false, 'text' => 'No AI providers available for video generation.'], 400);
            }

            $explicitProvider = null;
            if (null !== $aiProvider) {
                if (!\in_array($aiProvider, $availableProviders, true)) {
                    return new JsonResponse(['success' => false, 'text' => 'Selected AI provider is not available for video generation.'], 400);
                }
                $explicitProvider = $aiProvider;
            }

            $providersToTry = $explicitProvider ? [$explicitProvider] : $availableProviders;

            if (!$explicitProvider) {
                $active = $this->getActiveMediaProviderFromSession($request, 'video', $cid);
                if ('' !== $active && \in_array($active, $providersToTry, true)) {
                    $providersToTry = array_values(array_unique(array_merge([$active], $providersToTry)));
                }
            }

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
                    ];

                    // Keep "n" for providers that support it, but NEVER send it to Gemini Veo.
                    if ($n > 0) {
                        $options['n'] = $n;
                    }

                    if (null !== $seconds) {
                        $options['seconds'] = $seconds; // int
                    }

                    if (null !== $size) {
                        $options['size'] = $size;
                    }

                    if ('gemini' === strtolower((string) $providerName)) {
                        // Veo rejects numberOfVideos/candidateCount style options.
                        unset($options['n']);
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

                    if (!$explicitProvider) {
                        $this->setActiveMediaProviderInSession($request, 'video', $cid, $providerUsed);
                    }

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

                $message = '' !== $firstError
                    ? preg_replace('/^Error:\s*/', '', $firstError)
                    : ($explicitProvider ? 'Video generation failed for the selected provider.' : 'All video providers failed.');

                $statusCode = $this->mapVideoErrorToHttpStatus((string) $message);

                $payload = [
                    'success' => false,
                    'text' => (string) $message,
                ];

                if ($this->shouldExposeProviderDetails()) {
                    $payload['providers_tried'] = $providersToTry;
                    $payload['errors'] = $errors;
                }

                return new JsonResponse($payload, $statusCode);
            }

            $this->aiDisclosureHelper->logAudit(
                targetKey: 'video:'.sha1($prompt.'|'.$toolName.'|'.$language.'|'.$n.'|'.(string) $seconds.'|'.(string) $size),
                userId: $this->getCurrentUserId(),
                meta: [
                    'feature' => 'video_generator',
                    'mode' => 'generated',
                    'provider' => $providerUsed,
                    'tool' => $toolName,
                    'language' => $language,
                    'n' => $n,
                    'seconds' => $seconds,
                    'size' => $size,
                ],
                courseId: $cid > 0 ? $cid : 0,
                sessionId: $sid > 0 ? $sid : api_get_session_id()
            );

            // String result normalization (keep existing behavior)
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
                    'ai_assisted' => $this->aiDisclosureHelper->isDisclosureEnabled(),
                ]);
            }

            if (!\is_array($result)) {
                return new JsonResponse(['success' => false, 'text' => 'Provider returned an unsupported response type.'], 500);
            }

            $result['is_base64'] = (bool) ($result['is_base64'] ?? false);
            $result['content_type'] = (string) ($result['content_type'] ?? 'video/mp4');
            $result['revised_prompt'] = $result['revised_prompt'] ?? null;

            $text = '';
            if (isset($result['url']) && \is_string($result['url']) && '' !== trim($result['url'])) {
                $text = trim($result['url']);
            } elseif (isset($result['content']) && \is_string($result['content']) && '' !== trim($result['content'])) {
                $text = trim($result['content']);
            } elseif (isset($result['id']) && \is_string($result['id']) && '' !== trim($result['id'])) {
                $text = trim($result['id']);
            }

            $payload = [
                'success' => true,
                'text' => $text,
                'result' => $result,
                'ai_assisted' => $this->aiDisclosureHelper->isDisclosureEnabled(),
            ];

            if ($this->shouldExposeProviderDetails()) {
                $payload['provider_used'] = $providerUsed;
            }

            return new JsonResponse($payload);
        } catch (Throwable $e) {
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
                return new JsonResponse(['success' => false, 'text' => 'Access denied.'], 403);
            }

            $cid = (int) $request->query->get('cid', 0);

            $aiProvider = $request->query->get('ai_provider');
            $aiProvider = null !== $aiProvider ? trim((string) $aiProvider) : '';

            if ('' === $aiProvider) {
                if ($cid > 0) {
                    $aiProvider = $this->getActiveMediaProviderFromSession($request, 'video', $cid);
                }
                if ('' === $aiProvider) {
                    $aiProvider = $this->getActiveMediaProviderFromSession($request, 'video', 0);
                }
            }

            if ('' === $aiProvider) {
                $aiProvider = null; // Factory default
            }

            $aiService = $this->aiProviderFactory->getProvider($aiProvider, 'video');

            if (!$aiService instanceof AiVideoProviderInterface) {
                return new JsonResponse([
                    'success' => false,
                    'text' => 'Selected AI provider does not support video generation.',
                ], 400);
            }

            if (!$aiService instanceof AiVideoJobProviderInterface) {
                return new JsonResponse([
                    'success' => false,
                    'text' => 'This AI provider does not expose a video job status method.',
                ], 400);
            }

            $job = $aiService->getVideoJobStatus($id);
            if (empty($job)) {
                return new JsonResponse(['success' => false, 'text' => 'Failed to fetch video job status.'], 500);
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
                $maxBytes = 15 * 1024 * 1024;
                $p = \is_string($aiProvider) ? strtolower(trim($aiProvider)) : '';
                if ('gemini' === $p) {
                    $maxBytes = 80 * 1024 * 1024; // only Gemini
                }

                $content = $aiService->getVideoJobContentAsBase64($id, $maxBytes);
                if (\is_array($content)) {
                    $result['is_base64'] = (bool) ($content['is_base64'] ?? false);
                    $result['content'] = $content['content'] ?? null;
                    $result['url'] = $content['url'] ?? null;
                    $result['content_type'] = (string) ($content['content_type'] ?? 'video/mp4');

                    if (!empty($content['error'])) {
                        $result['error'] = \is_string($content['error'])
                            ? trim($content['error'])
                            : (string) $content['error'];
                    }
                }
            }

            $this->aiDisclosureHelper->logAudit(
                targetKey: 'video_job:'.trim((string) $id),
                userId: $this->getCurrentUserId(),
                meta: [
                    'feature' => 'video_job_status',
                    'mode' => 'generated',
                    'provider' => \is_string($aiProvider) && '' !== trim((string) $aiProvider) ? trim((string) $aiProvider) : 'default',
                    'job_id' => $id,
                    'status' => $status,
                    'cid' => $cid,
                ],
                courseId: 0,
                sessionId: api_get_session_id()
            );

            $payload = [
                'success' => true,
                'text' => '' !== $jobError ? $jobError : '',
                'result' => $result,
                'ai_assisted' => $this->aiDisclosureHelper->isDisclosureEnabled(),
            ];

            if ($this->shouldExposeProviderDetails()) {
                $payload['provider_used'] = \is_string($aiProvider) ? (string) $aiProvider : '';
            }

            return new JsonResponse($payload);
        } catch (Throwable $e) {
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
        $aiProvider = $this->normalizeProviderNameFromPayload($data['ai_provider'] ?? null);

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
            // TXT: send content as text to TEXT provider
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

                if ($this->aiDisclosureHelper->isDisclosureEnabled()) {
                    $nodeId = (int) $node->getId();
                    if ($nodeId > 0) {
                        $this->aiDisclosureHelper->markAiAssistedExtraField('document', $nodeId, true);
                    }
                }

                $this->aiDisclosureHelper->logAudit(
                    targetKey: 'resource_file:'.$resourceFileId,
                    userId: $this->getCurrentUserId(),
                    meta: [
                        'feature' => 'document_feedback',
                        'mode' => 'co_generated',
                        'provider' => $aiProvider ?? 'default',
                        'mime' => 'text/plain',
                        'course_id' => $cid,
                        'session_id' => $sid,
                    ],
                    courseId: $cid,
                    sessionId: $sid > 0 ? $sid : api_get_session_id()
                );

                return new JsonResponse(['success' => true, 'text' => $result, 'ai_assisted' => $this->aiDisclosureHelper->isDisclosureEnabled()]);
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

            if ($this->aiDisclosureHelper->isDisclosureEnabled()) {
                $result = $this->aiDisclosureHelper->prependDisclosureToPlainText($result);
            }

            $this->aiDisclosureHelper->logAudit(
                targetKey: 'resource_file:'.$resourceFileId,
                userId: $this->getCurrentUserId(),
                meta: [
                    'feature' => 'document_feedback',
                    'mode' => 'co_generated',
                    'provider' => $aiProvider ?? 'default',
                    'mime' => 'application/pdf',
                    'course_id' => $cid,
                    'session_id' => $sid,
                ],
                courseId: $cid,
                sessionId: $sid > 0 ? $sid : api_get_session_id()
            );

            return new JsonResponse(['success' => true, 'text' => $result, 'ai_assisted' => $this->aiDisclosureHelper->isDisclosureEnabled()]);
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

            if (null !== $messageId && $this->aiDisclosureHelper->isDisclosureEnabled()) {
                $this->aiDisclosureHelper->markAiAssistedExtraField('message', (int) $messageId, true);
            }

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

    private function buildActiveMediaProviderSessionKey(string $type, int $courseId = 0): string
    {
        $t = strtolower(trim($type));
        $cid = max(0, (int) $courseId);

        // If cid is not provided, fall back to a generic key (still useful).
        return $cid > 0
            ? self::ACTIVE_MEDIA_PROVIDER_SESSION_PREFIX.$t.'_'.$cid
            : self::ACTIVE_MEDIA_PROVIDER_SESSION_PREFIX.$t;
    }

    private function getActiveMediaProviderFromSession(Request $request, string $type, int $courseId = 0): string
    {
        try {
            if (!$request->hasSession()) {
                return '';
            }

            return (string) $request->getSession()->get($this->buildActiveMediaProviderSessionKey($type, $courseId), '');
        } catch (Throwable) {
            return '';
        }
    }

    private function setActiveMediaProviderInSession(Request $request, string $type, int $courseId, string $provider): void
    {
        $provider = strtolower(trim($provider));
        if ('' === $provider) {
            return;
        }

        try {
            if (!$request->hasSession()) {
                return;
            }

            $request->getSession()->set($this->buildActiveMediaProviderSessionKey($type, $courseId), $provider);
        } catch (Throwable) {
            // Ignore session errors.
        }
    }

    /**
     * Normalize providers to a stable JSON format: [{key,label}, ...].
     */
    private function normalizeProvidersForJson(array $raw): array
    {
        $providers = [];

        foreach ($raw as $key => $label) {
            // Numeric array: ["openai", "mistral"]
            if (\is_int($key)) {
                $k = trim((string) $label);
                if ('' === $k) {
                    continue;
                }
                $providers[] = ['key' => $k, 'label' => $k];

                continue;
            }

            $k = trim((string) $key);
            if ('' === $k) {
                continue;
            }

            $l = trim((string) $label);
            if ('' === $l) {
                $l = $k;
            }

            $providers[] = ['key' => $k, 'label' => $l];
        }

        return $providers;
    }

    /**
     * Accept ai_provider as string or as {key,label}/{name}.
     */
    private function normalizeProviderNameFromPayload(mixed $raw): ?string
    {
        $provider = null;

        if (\is_array($raw)) {
            $provider = isset($raw['key']) ? trim((string) $raw['key']) : null;
            if (null === $provider || '' === $provider) {
                $provider = isset($raw['name']) ? trim((string) $raw['name']) : null;
            }
        } elseif (\is_string($raw)) {
            $provider = trim($raw);
        }

        if (null === $provider || '' === $provider) {
            return null;
        }

        return $provider;
    }

    private function debugLog(string $message): void
    {
        if (!$this->debug) {
            return;
        }

        error_log($message);
    }

    private function getCurrentUserId(): int
    {
        $u = $this->getUser();
        if (!\is_object($u) || !method_exists($u, 'getId')) {
            return 0;
        }

        return (int) $u->getId();
    }

    private function shouldExposeProviderDetails(): bool
    {
        try {
            $isDebug = (bool) $this->getParameter('kernel.debug');
        } catch (Throwable) {
            $isDebug = false;
        }

        return $isDebug && $this->isGranted('ROLE_ADMIN');
    }

    /**
     * Recursively inject a visible disclosure tag into HTML fields found in a structure.
     */
    private function injectDisclosureTagsRecursively(mixed $value): mixed
    {
        if (\is_string($value)) {
            if ($this->looksLikeHtmlFragment($value)) {
                return $this->aiDisclosureHelper->injectDisclosureTagIntoHtml($value);
            }

            return $value;
        }

        if (\is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = $this->injectDisclosureTagsRecursively($v);
            }

            return $value;
        }

        return $value;
    }

    private function looksLikeHtmlFragment(string $s): bool
    {
        $s = trim($s);
        if ('' === $s) {
            return false;
        }

        if (str_contains($s, '<html') || str_contains($s, '<body') || str_contains($s, '</body>')) {
            return true;
        }

        return (bool) preg_match('#</?(p|div|span|h[1-6]|ul|ol|li|table|tr|td|img|a)\b#i', $s);
    }

    private function extractContextFromReferer(Request $request): array
    {
        $cid = 0;
        $sid = 0;
        $gid = 0;

        $ref = (string) $request->headers->get('referer', '');
        if ('' === trim($ref)) {
            return ['cid' => 0, 'sid' => 0, 'gid' => 0];
        }

        $q = parse_url($ref, PHP_URL_QUERY);
        if (!\is_string($q) || '' === trim($q)) {
            return ['cid' => 0, 'sid' => 0, 'gid' => 0];
        }

        $params = [];
        parse_str($q, $params);

        if (isset($params['cid'])) {
            $cid = (int) $params['cid'];
        }
        if (isset($params['sid'])) {
            $sid = (int) $params['sid'];
        }
        if (isset($params['gid'])) {
            $gid = (int) $params['gid'];
        }

        return [
            'cid' => $cid > 0 ? $cid : 0,
            'sid' => $sid > 0 ? $sid : 0,
            'gid' => $gid > 0 ? $gid : 0,
        ];
    }

    private function resolveCourseIdFromRequest(Request $request, array $data): int
    {
        // 1) JSON payload
        $cid = (int) ($data['cid'] ?? 0);
        if ($cid > 0) {
            return $cid;
        }

        // 2) Query string (rare for /ai/* but keep it)
        $cid = (int) $request->query->get('cid', 0);
        if ($cid > 0) {
            return $cid;
        }

        // 3) Referer (LP AI helper calls /ai/* without cid in URL/body)
        $ctx = $this->extractContextFromReferer($request);
        if (!empty($ctx['cid'])) {
            return (int) $ctx['cid'];
        }

        // 4) course_code from payload -> resolve to real_id
        $courseCode = trim((string) ($data['course_code'] ?? ''));
        if ('' !== $courseCode) {
            // Prefer legacy helper if available in this runtime.
            try {
                if (\function_exists('api_get_course_info')) {
                    $info = api_get_course_info($courseCode);
                    if (\is_array($info) && !empty($info['real_id'])) {
                        return (int) $info['real_id'];
                    }
                }
            } catch (Throwable) {
                // Ignore.
            }

            // Fallback to Doctrine lookup (best-effort).
            try {
                $course = $this->em->getRepository(Course::class)->findOneBy(['code' => $courseCode]);
                if ($course instanceof Course && (int) $course->getId() > 0) {
                    return (int) $course->getId();
                }
            } catch (Throwable) {
                // Ignore.
            }
        }

        // 5) Global Chamilo context (best-effort)
        try {
            $cid = (int) api_get_course_int_id();
            if ($cid > 0) {
                return $cid;
            }
        } catch (Throwable) {
            // Ignore.
        }

        try {
            $info = api_get_course_info();
            if (\is_array($info) && !empty($info['real_id'])) {
                return (int) $info['real_id'];
            }
        } catch (Throwable) {
            // Ignore.
        }

        return 0;
    }

    private function resolveSessionIdFromRequest(Request $request, array $data): int
    {
        // 1) JSON payload
        $sid = (int) ($data['sid'] ?? 0);
        if ($sid > 0) {
            return $sid;
        }

        // 2) Query string
        $sid = (int) $request->query->get('sid', 0);
        if ($sid > 0) {
            return $sid;
        }

        // 3) Referer
        $ctx = $this->extractContextFromReferer($request);
        if (!empty($ctx['sid'])) {
            return (int) $ctx['sid'];
        }

        // 4) Current session context
        try {
            $sid = (int) api_get_session_id();
        } catch (Throwable) {
            $sid = 0;
        }

        return $sid > 0 ? $sid : 0;
    }

    /**
     * Sanitizes AI-generated Aiken text before it is shown in the textarea.
     * Removes markdown fences and leading numbering from question lines only.
     */
    private function sanitizeGeneratedAikenText(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", trim($text));

        if ('' === $text) {
            return '';
        }

        $lines = explode("\n", $text);
        $normalizedLines = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ('' === $line) {
                $normalizedLines[] = '';

                continue;
            }

            if ('```' === $line || str_starts_with($line, '```')) {
                continue;
            }

            if ($this->isAikenQuestionTitleLine($line)) {
                $line = $this->stripLeadingAikenQuestionNumber($line);
            }

            $normalizedLines[] = $line;
        }

        return trim(implode("\n", $normalizedLines));
    }

    /**
     * Returns true only for Aiken question title lines.
     */
    private function isAikenQuestionTitleLine(string $line): bool
    {
        return 1 !== preg_match('/^[A-Z]\.\s/', $line)
            && 1 !== preg_match('/^ANSWER:\s?[A-Z]/', $line)
            && 1 !== preg_match('/^ANSWER_EXPLANATION:\s?(.*)/', $line);
    }

    /**
     * Removes a leading numeric prefix from a generated Aiken question title.
     */
    private function stripLeadingAikenQuestionNumber(string $line): string
    {
        $line = trim($line);

        if ('' === $line) {
            return $line;
        }

        $normalized = preg_replace('/^\d+\s*[\.\)\-:]\s+/u', '', $line);
        if (null === $normalized) {
            return $line;
        }

        $normalized = trim($normalized);

        return '' !== $normalized ? $normalized : $line;
    }
}
