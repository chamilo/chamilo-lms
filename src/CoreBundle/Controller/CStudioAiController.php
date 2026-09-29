<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\AiProvider\AiImageProviderInterface;
use Chamilo\CoreBundle\AiProvider\AiProviderFactory;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AiDisclosureHelper;
use Chamilo\CoreBundle\Helpers\AiFeatureAccessHelper;
use Chamilo\CoreBundle\Security\Authorization\Voter\CourseVoter;
use Chamilo\CoreBundle\Service\Ai\AiRequestQuotaGuard;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use finfo;
use League\Flysystem\FilesystemOperator;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;
use TypeError;

use const FILEINFO_MIME_TYPE;
use const FILTER_FLAG_NO_PRIV_RANGE;
use const FILTER_FLAG_NO_RES_RANGE;
use const FILTER_VALIDATE_IP;
use const FILTER_VALIDATE_URL;
use const JSON_THROW_ON_ERROR;

final class CStudioAiController extends AbstractController
{
    private const int MAX_IMAGE_BYTES = 10 * 1024 * 1024;
    private const string CSRF_TOKEN_PREFIX = 'cstudio_ai_';

    public function __construct(
        private readonly AiProviderFactory $providerFactory,
        private readonly AiFeatureAccessHelper $featureAccessHelper,
        private readonly AiRequestQuotaGuard $quotaGuard,
        private readonly AiDisclosureHelper $disclosureHelper,
        private readonly EntityManagerInterface $entityManager,
        private readonly CLpRepository $learningPathRepository,
        private readonly HttpClientInterface $httpClient,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        #[Autowire(service: 'oneup_flysystem.plugins_filesystem')]
        private readonly FilesystemOperator $pluginsFileSystem,
    ) {}

    #[Route('/ai/cstudio', name: 'chamilo_core_ai_cstudio', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $payload = json_decode($request->getContent(), true);
            if (!\is_array($payload)) {
                return $this->error('Invalid JSON payload.', 400);
            }

            $pageId = (int) ($payload['page_id'] ?? 0);
            $action = trim((string) ($payload['action'] ?? ''));
            $csrfToken = trim((string) ($payload['csrf_token'] ?? ''));

            if ($pageId <= 0 || '' === $action) {
                return $this->error('Invalid request parameters.', 400);
            }

            $user = $this->getUser();
            if (!$user instanceof User) {
                return $this->error('Authentication is required.', 401);
            }

            if (
                !$this->csrfTokenManager->isTokenValid(
                    new CsrfToken(self::CSRF_TOKEN_PREFIX.$pageId, $csrfToken),
                )
            ) {
                return $this->error('Invalid security token. Please reload the editor.', 403);
            }

            $context = $this->resolveContext($pageId);

            try {
                $this->denyAccessUnlessGranted(CourseVoter::EDIT, $context['course']);
            } catch (AccessDeniedException) {
                return $this->error('Access denied.', 403);
            }

            if ('capabilities' === $action) {
                return new JsonResponse([
                    'success' => true,
                    'message' => '',
                    'capabilities' => [
                        'text' => $this->isFeatureAvailable(
                            'content_analyser',
                            'text',
                            'generateText',
                            $context['course_id'],
                        ),
                        'image' => $this->isFeatureAvailable(
                            'image_generator',
                            'image',
                            null,
                            $context['course_id'],
                        ),
                        'quiz' => $this->isFeatureAvailable(
                            'exercise_generator',
                            'text',
                            'generateQuestions',
                            $context['course_id'],
                        ),
                    ],
                ]);
            }

            $feature = match ($action) {
                'text' => 'content_analyser',
                'image' => 'image_generator',
                'quiz' => 'exercise_generator',
                default => '',
            };

            if ('' === $feature) {
                return $this->error('Unsupported AI action.', 400);
            }

            if (!$this->featureAccessHelper->isFeatureEnabledForCourse($feature, $context['course_id'])) {
                return $this->error('This AI feature is disabled for the course.', 403);
            }

            $serviceType = 'image' === $action ? 'image' : 'text';
            $requiredMethod = match ($action) {
                'text' => 'generateText',
                'quiz' => 'generateQuestions',
                default => null,
            };
            $providerName = $this->getCompatibleProviderName($serviceType, $requiredMethod);

            if (null === $providerName) {
                return $this->error('No compatible AI provider is configured.', 400);
            }

            try {
                $this->quotaGuard->assertCanRequest($user, $providerName, $serviceType);
            } catch (RuntimeException $exception) {
                return $this->error($exception->getMessage(), 429);
            }

            return match ($action) {
                'text' => $this->generateText($payload, $providerName, $context, $user),
                'image' => $this->generateImage($payload, $providerName, $context, $user),
                'quiz' => $this->generateQuiz($payload, $providerName, $context, $user),
                default => $this->error('Unsupported AI action.', 400),
            };
        } catch (Throwable $exception) {
            error_log('[CStudio][AI][Symfony] '.$exception->getMessage());

            return $this->error('The AI request could not be completed.', 500);
        }
    }

    /**
     * @return array{
     *     course: Course,
     *     course_id: int,
     *     session_id: int,
     *     lp_id: int,
     *     local_folder: string,
     *     page_id: int
     * }
     */
    private function resolveContext(int $pageId): array
    {
        $connection = $this->entityManager->getConnection();
        $accessUrlId = (int) api_get_current_access_url_id();
        $currentId = $pageId;
        $page = null;

        for ($depth = 0; $depth < 20; ++$depth) {
            $page = $connection->fetchAssociative(
                'SELECT id, id_parent, lp_id, local_folder, id_url '
                .'FROM plugin_oel_tools_teachdoc '
                .'WHERE id = :id AND id_url = :accessUrl',
                [
                    'id' => $currentId,
                    'accessUrl' => $accessUrlId,
                ],
                [
                    'id' => Types::INTEGER,
                    'accessUrl' => Types::INTEGER,
                ],
            );

            if (false === $page) {
                throw new RuntimeException('CStudio page not found.');
            }

            $parentId = (int) ($page['id_parent'] ?? 0);
            if ($parentId <= 0) {
                break;
            }

            $currentId = $parentId;
        }

        if (!\is_array($page)) {
            throw new RuntimeException('CStudio project not found.');
        }

        $learningPathId = (int) ($page['lp_id'] ?? 0);
        $localFolder = strtolower(trim((string) ($page['local_folder'] ?? '')));

        if (
            $learningPathId <= 0
            || !preg_match('/^[a-z0-9][a-z0-9_-]*$/', $localFolder)
        ) {
            throw new RuntimeException('Invalid CStudio project context.');
        }

        $learningPath = $this->learningPathRepository->find($learningPathId);
        if (null === $learningPath) {
            throw new RuntimeException('Learning path not found.');
        }

        $resourceLink = $learningPath->getFirstResourceLink();
        $course = $resourceLink?->getCourse();

        if (!$course instanceof Course || null === $course->getId()) {
            throw new RuntimeException('The learning path course could not be resolved.');
        }

        return [
            'course' => $course,
            'course_id' => (int) $course->getId(),
            'session_id' => (int) ($resourceLink?->getSession()?->getId() ?? 0),
            'lp_id' => $learningPathId,
            'local_folder' => $localFolder,
            'page_id' => $pageId,
        ];
    }

    private function isFeatureAvailable(
        string $feature,
        string $serviceType,
        ?string $requiredMethod,
        int $courseId,
    ): bool {
        return $this->featureAccessHelper->isFeatureEnabledForCourse($feature, $courseId)
            && null !== $this->getCompatibleProviderName($serviceType, $requiredMethod);
    }

    private function getCompatibleProviderName(
        string $serviceType,
        ?string $requiredMethod,
    ): ?string {
        foreach ($this->providerFactory->getProvidersForType($serviceType) as $providerName) {
            try {
                $provider = $this->providerFactory->getProvider($providerName, $serviceType);
            } catch (Throwable) {
                continue;
            }

            if ('image' === $serviceType && !$provider instanceof AiImageProviderInterface) {
                continue;
            }

            if (null !== $requiredMethod && !method_exists($provider, $requiredMethod)) {
                continue;
            }

            return $providerName;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $payload
     * @param array{
     *     course: Course,
     *     course_id: int,
     *     session_id: int,
     *     lp_id: int,
     *     local_folder: string,
     *     page_id: int
     * } $context
     */
    private function generateText(
        array $payload,
        string $providerName,
        array $context,
        User $user,
    ): JsonResponse {
        $prompt = trim((string) ($payload['prompt'] ?? ''));
        $language = $this->normalizeLanguage((string) ($payload['language'] ?? 'en'));
        $words = max(25, min(1500, (int) ($payload['words'] ?? 200)));

        if ('' === $prompt || mb_strlen($prompt) > 4000) {
            return $this->error('Enter instructions of no more than 4000 characters.', 400);
        }

        $provider = $this->providerFactory->getProvider($providerName, 'text');
        if (!method_exists($provider, 'generateText')) {
            return $this->error('The configured provider cannot generate text.', 400);
        }

        $generationPrompt = \sprintf(
            "Write educational content in %s about the following request:\n%s\n\n"
            .'Use approximately %d words. Return plain text only, with short readable paragraphs. '
            .'Do not include Markdown fences, a title unless requested, or comments about the generation process.',
            $language,
            $prompt,
            $words,
        );

        try {
            $result = (string) $provider->generateText($generationPrompt, [
                'language' => $language,
                'tool' => 'cstudio_text_block',
                'words' => $words,
                'cid' => $context['course_id'],
                'lp_id' => $context['lp_id'],
                'page_id' => $context['page_id'],
            ]);
        } catch (TypeError) {
            $result = (string) $provider->generateText($generationPrompt, $language);
        }

        $result = trim(strip_tags($result));
        if ('' === $result || str_starts_with($result, 'Error:')) {
            return $this->error(
                '' !== $result ? $result : 'The AI provider returned an empty response.',
                500,
            );
        }

        if ($this->disclosureHelper->isDisclosureEnabled()) {
            $result = $this->disclosureHelper->prependDisclosureToPlainText($result);
        }

        $this->logAudit(
            'cstudio_text',
            $providerName,
            $context,
            $user,
            [
                'words' => $words,
                'prompt_length' => mb_strlen($prompt),
            ],
        );

        return new JsonResponse([
            'success' => true,
            'message' => '',
            'text' => $result,
            'ai_assisted' => $this->disclosureHelper->isDisclosureEnabled(),
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array{
     *     course: Course,
     *     course_id: int,
     *     session_id: int,
     *     lp_id: int,
     *     local_folder: string,
     *     page_id: int
     * } $context
     */
    private function generateImage(
        array $payload,
        string $providerName,
        array $context,
        User $user,
    ): JsonResponse {
        $prompt = trim((string) ($payload['prompt'] ?? ''));
        $language = $this->normalizeLanguage((string) ($payload['language'] ?? 'en'));
        $format = trim((string) ($payload['format'] ?? 'square'));

        if ('' === $prompt || mb_strlen($prompt) > 2000) {
            return $this->error('Enter an image topic of no more than 2000 characters.', 400);
        }

        if (!\in_array($format, ['square', 'landscape', 'portrait'], true)) {
            return $this->error('Invalid image format.', 400);
        }

        $provider = $this->providerFactory->getProvider($providerName, 'image');
        if (!$provider instanceof AiImageProviderInterface) {
            return $this->error('The configured provider cannot generate images.', 400);
        }

        $aspectRatio = match ($format) {
            'landscape' => '16:9',
            'portrait' => '9:16',
            default => '1:1',
        };

        $result = $provider->generateImage(
            $prompt,
            'cstudio_image',
            [
                'language' => $language,
                'n' => 1,
                'format' => $format,
                'aspect_ratio' => $aspectRatio,
            ],
        );

        [$binary, $reportedMime] = $this->extractImageBinary($result);
        if ('' === $binary || \strlen($binary) > self::MAX_IMAGE_BYTES) {
            return $this->error('The generated image is empty or too large.', 500);
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $detectedMime = (string) $finfo->buffer($binary);
        $mime = '' !== $detectedMime ? $detectedMime : $reportedMime;
        $extension = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => '',
        };

        if ('' === $extension) {
            return $this->error('The AI provider returned an unsupported image format.', 500);
        }

        $filename = 'ai-'.date('Ymd-His').'-'.bin2hex(random_bytes(6)).'.'.$extension;
        $relativeDirectory = 'CStudio/editor/img_cache/'.$context['local_folder'];
        $relativePath = $relativeDirectory.'/'.$filename;

        if (!$this->pluginsFileSystem->directoryExists($relativeDirectory)) {
            $this->pluginsFileSystem->createDirectory($relativeDirectory);
        }

        $this->pluginsFileSystem->write($relativePath, $binary);

        $this->logAudit(
            'cstudio_image',
            $providerName,
            $context,
            $user,
            [
                'format' => $format,
                'prompt_length' => mb_strlen($prompt),
                'mime_type' => $mime,
            ],
        );

        $url = api_get_path(WEB_PLUGIN_PATH)
            .'CStudio/img-cache.php?path='
            .rawurlencode($context['local_folder'].'/'.$filename);

        return new JsonResponse([
            'success' => true,
            'message' => '',
            'url' => $url,
            'format' => $format,
            'ai_assisted' => $this->disclosureHelper->isDisclosureEnabled(),
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function extractImageBinary(array|string|null $result): array
    {
        if (null === $result || '' === $result) {
            throw new RuntimeException('The AI provider returned an empty image.');
        }

        if (\is_string($result)) {
            if (str_starts_with($result, 'Error:')) {
                throw new RuntimeException($result);
            }

            if (filter_var($result, FILTER_VALIDATE_URL)) {
                return $this->downloadImage($result);
            }

            if (preg_match('#^data:(image/(?:png|jpeg|webp));base64,(.+)$#s', $result, $matches)) {
                $binary = base64_decode((string) $matches[2], true);

                return [false === $binary ? '' : $binary, (string) $matches[1]];
            }

            $binary = base64_decode($result, true);

            return [false === $binary ? '' : $binary, 'image/png'];
        }

        $error = trim((string) ($result['error'] ?? ''));
        if ('' !== $error) {
            throw new RuntimeException($error);
        }

        $content = trim((string) ($result['content'] ?? ''));
        $mime = trim((string) ($result['content_type'] ?? 'image/png'));

        if ('' !== $content && (bool) ($result['is_base64'] ?? false)) {
            $binary = base64_decode($content, true);

            return [false === $binary ? '' : $binary, $mime];
        }

        $url = trim((string) ($result['url'] ?? ''));
        if ('' === $url) {
            return ['', $mime];
        }

        return $this->downloadImage($url);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function downloadImage(string $url): array
    {
        $parts = parse_url($url);
        if (
            false === $parts
            || 'https' !== strtolower((string) ($parts['scheme'] ?? ''))
            || '' === trim((string) ($parts['host'] ?? ''))
        ) {
            throw new RuntimeException('The AI provider returned an invalid image URL.');
        }

        $host = (string) $parts['host'];
        $addresses = gethostbynamel($host) ?: [];

        if ([] === $addresses) {
            throw new RuntimeException('The AI image host could not be resolved.');
        }

        foreach ($addresses as $address) {
            if (
                false === filter_var(
                    $address,
                    FILTER_VALIDATE_IP,
                    FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
                )
            ) {
                throw new RuntimeException('The AI provider returned an unsafe image URL.');
            }
        }

        $response = $this->httpClient->request('GET', $url, [
            'headers' => ['Accept' => 'image/png,image/jpeg,image/webp'],
            'max_redirects' => 0,
            'timeout' => 30,
            'max_duration' => 60,
        ]);

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            throw new RuntimeException('The generated image could not be downloaded.');
        }

        $headers = $response->getHeaders(false);
        $contentLength = (int) ($headers['content-length'][0] ?? 0);
        if ($contentLength > self::MAX_IMAGE_BYTES) {
            throw new RuntimeException('The generated image is too large.');
        }

        $binary = (string) $response->getContent(false);
        if (\strlen($binary) > self::MAX_IMAGE_BYTES) {
            throw new RuntimeException('The generated image is too large.');
        }

        $mime = strtolower(
            trim(
                explode(
                    ';',
                    (string) ($headers['content-type'][0] ?? 'image/png'),
                )[0],
            ),
        );

        return [$binary, $mime];
    }

    /**
     * @param array<string, mixed> $payload
     * @param array{
     *     course: Course,
     *     course_id: int,
     *     session_id: int,
     *     lp_id: int,
     *     local_folder: string,
     *     page_id: int
     * } $context
     */
    private function generateQuiz(
        array $payload,
        string $providerName,
        array $context,
        User $user,
    ): JsonResponse {
        $topic = trim((string) ($payload['topic'] ?? ''));
        $sourceText = trim(strip_tags((string) ($payload['source_text'] ?? '')));
        $language = $this->normalizeLanguage((string) ($payload['language'] ?? 'en'));
        $number = max(1, min(10, (int) ($payload['questions'] ?? 3)));

        if ('' === $topic && '' === $sourceText) {
            return $this->error('Enter a topic or select a text block as the source.', 400);
        }

        if (mb_strlen($topic) > 2000 || mb_strlen($sourceText) > 12000) {
            return $this->error('The quiz source is too long.', 400);
        }

        $provider = $this->providerFactory->getProvider($providerName, 'text');
        if (!method_exists($provider, 'generateQuestions')) {
            return $this->error('The configured provider cannot generate quiz questions.', 400);
        }

        $generationTopic = $topic;
        if ('' !== $sourceText) {
            $generationTopic .= "\n\nUse the following source content:\n".$sourceText;
        }

        $result = (string) $provider->generateQuestions(
            $generationTopic,
            $number,
            'multiple_choice',
            $language,
        );

        if ('' === trim($result) || str_starts_with(trim($result), 'Error:')) {
            return $this->error(
                '' !== trim($result) ? trim($result) : 'The AI provider returned an empty response.',
                500,
            );
        }

        $questions = $this->parseAiken($result, $number);
        if (\count($questions) !== $number) {
            return $this->error(
                'The AI provider did not return the requested number of valid questions.',
                500,
            );
        }

        $this->logAudit(
            'cstudio_quiz',
            $providerName,
            $context,
            $user,
            [
                'questions' => \count($questions),
                'source_text' => '' !== $sourceText,
                'source_length' => mb_strlen($sourceText),
            ],
        );

        return new JsonResponse([
            'success' => true,
            'message' => '',
            'questions' => $questions,
            'ai_assisted' => $this->disclosureHelper->isDisclosureEnabled(),
        ]);
    }

    /**
     * @return list<array{question: string, answers: list<array{text: string, correct: bool}>}>
     */
    private function parseAiken(string $raw, int $maximum): array
    {
        $raw = preg_replace('/^```[a-z0-9_-]*\s*|\s*```$/mi', '', trim($raw)) ?? '';
        $blocks = preg_split('/\R{2,}/', $raw) ?: [];
        $questions = [];

        foreach ($blocks as $block) {
            $lines = preg_split('/\R/', trim($block)) ?: [];
            $questionLines = [];
            $answers = [];
            $correctLetters = [];

            foreach ($lines as $line) {
                $line = trim($line);
                if ('' === $line) {
                    continue;
                }

                if (preg_match('/^ANSWER\s*:\s*([A-F](?:\s*,\s*[A-F])*)$/i', $line, $matches)) {
                    $correctLetters = array_map(
                        'trim',
                        explode(',', strtoupper((string) $matches[1])),
                    );

                    continue;
                }

                if (preg_match('/^([A-F])[\.\)]\s+(.+)$/u', $line, $matches)) {
                    $answers[] = [
                        'letter' => strtoupper((string) $matches[1]),
                        'text' => trim(strip_tags((string) $matches[2])),
                    ];

                    continue;
                }

                if ([] === $answers) {
                    $questionLines[] = $line;
                }
            }

            $question = trim(strip_tags(implode(' ', $questionLines)));
            if ('' === $question || \count($answers) < 2 || \count($answers) > 6 || [] === $correctLetters) {
                continue;
            }

            $normalizedAnswers = [];
            foreach ($answers as $answer) {
                $text = trim((string) $answer['text']);
                if ('' === $text) {
                    continue 2;
                }

                $normalizedAnswers[] = [
                    'text' => $text,
                    'correct' => \in_array($answer['letter'], $correctLetters, true),
                ];
            }

            if (!array_filter($normalizedAnswers, static fn (array $answer): bool => $answer['correct'])) {
                continue;
            }

            $questions[] = [
                'question' => $question,
                'answers' => $normalizedAnswers,
            ];

            if (\count($questions) >= $maximum) {
                break;
            }
        }

        return $questions;
    }

    private function normalizeLanguage(string $language): string
    {
        $language = trim($language);

        if (!preg_match('/^[a-z]{2}(?:_[A-Z]{2})?$/', $language)) {
            return 'en';
        }

        return $language;
    }

    /**
     * @param array{
     *     course: Course,
     *     course_id: int,
     *     session_id: int,
     *     lp_id: int,
     *     local_folder: string,
     *     page_id: int
     * } $context
     * @param array<string, mixed> $metadata
     */
    private function logAudit(
        string $feature,
        string $provider,
        array $context,
        User $user,
        array $metadata,
    ): void {
        try {
            $userId = (int) ($user->getId() ?? 0);
            if ($userId <= 0) {
                return;
            }

            $this->disclosureHelper->logAudit(
                targetKey: 'course:'.$context['course_id']
                    .':cstudio:'.$context['page_id']
                    .':'.$feature.':'.sha1(json_encode($metadata, JSON_THROW_ON_ERROR)),
                userId: $userId,
                meta: array_merge(
                    [
                        'feature' => $feature,
                        'mode' => 'generated',
                        'provider' => $provider,
                        'lp_id' => $context['lp_id'],
                        'page_id' => $context['page_id'],
                    ],
                    $metadata,
                ),
                courseId: $context['course_id'],
                sessionId: $context['session_id'],
            );
        } catch (Throwable $exception) {
            error_log('[CStudio][AI][audit] '.$exception->getMessage());
        }
    }

    private function error(string $message, int $status): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'message' => $message,
        ], $status);
    }
}
