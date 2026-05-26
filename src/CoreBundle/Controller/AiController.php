<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\AiProvider\AiCourseAnalyzerService;
use Chamilo\CoreBundle\AiProvider\AiDocumentProcessProviderInterface;
use Chamilo\CoreBundle\AiProvider\AiImageProviderInterface;
use Chamilo\CoreBundle\AiProvider\AiProviderFactory;
use Chamilo\CoreBundle\AiProvider\AiVideoJobProviderInterface;
use Chamilo\CoreBundle\AiProvider\AiVideoProviderInterface;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\TrackEDefault;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Helpers\AiDisclosureHelper;
use Chamilo\CoreBundle\Helpers\MessageHelper;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CoreBundle\Repository\TrackEAttemptRepository;
use Chamilo\CoreBundle\Security\Authorization\Voter\CourseVoter;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CGlossary;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Repository\CGlossaryRepository;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Question;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
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
    private const LP_LEARNING_HELPER_MAX_SELECTED_TEXT_LENGTH = 5000;
    private const LP_LEARNING_HELPER_METHODS = [
        'mind_map',
        'feynman',
        'elaborative_interrogation',
        'spaced_repetition',
        'sq3r',
        'analogies_metaphors',
        'dual_coding',
        'storytelling',
        'thematic_connections',
        'interleaved_learning',
        'memory_palace',
    ];

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

    #[Route('/glossary_document_sources', name: 'chamilo_core_ai_glossary_document_sources', methods: ['GET'])]
    public function glossaryDocumentSources(Request $request): JsonResponse
    {
        try {
            $this->denyIfNotTeacher();
        } catch (AccessDeniedException) {
            return new JsonResponse(['documents' => []], 403);
        }

        $cid = (int) $request->query->get('cid', 0);
        $sid = (int) $request->query->get('sid', 0);

        if (0 === $cid) {
            return new JsonResponse(['documents' => []], 400);
        }

        /** @var Course|null $course */
        $course = $this->em->getRepository(Course::class)->find($cid);
        if (null === $course) {
            return new JsonResponse(['documents' => []], 404);
        }

        $documents = [];
        $seen = [];
        $qb = $this->em->createQueryBuilder();
        $qb
            ->select(
                'DISTINCT rf.id AS resource_file_id',
                'rn.id AS resource_node_id',
                'rn.title AS title',
                'rf.title AS file_title',
                'rf.originalName AS original_name',
                'rf.mimeType AS mime_type',
                'rf.size AS size'
            )
            ->from(ResourceFile::class, 'rf')
            ->join('rf.resourceNode', 'rn')
            ->join('rn.resourceLinks', 'rl')
            ->where('IDENTITY(rl.course) = :cid')
            ->andWhere('rl.deletedAt IS NULL')
            ->andWhere(
                $qb->expr()->orX(
                    'LOWER(rf.mimeType) = :pdfMime',
                    'LOWER(rf.mimeType) LIKE :textMime',
                    'LOWER(rf.originalName) LIKE :pdfExt',
                    'LOWER(rf.originalName) LIKE :txtExt',
                    'LOWER(rf.title) LIKE :pdfExt',
                    'LOWER(rf.title) LIKE :txtExt',
                    'LOWER(rn.title) LIKE :pdfExt',
                    'LOWER(rn.title) LIKE :txtExt'
                )
            )
            ->orderBy('rn.title', 'ASC')
            ->addOrderBy('rf.id', 'ASC')
            ->setMaxResults(200)
            ->setParameter('cid', $cid, Types::INTEGER)
            ->setParameter('pdfMime', 'application/pdf')
            ->setParameter('textMime', 'text/plain%')
            ->setParameter('pdfExt', '%.pdf')
            ->setParameter('txtExt', '%.txt')
        ;

        if ($sid > 0) {
            $qb
                ->andWhere('(IDENTITY(rl.session) = :sid OR rl.session IS NULL)')
                ->setParameter('sid', $sid, Types::INTEGER)
            ;
        }

        /** @var list<array<string, mixed>> $rows */
        $rows = $qb->getQuery()->getArrayResult();

        foreach ($rows as $row) {
            $this->addGlossaryDocumentSourceFromRow($documents, $seen, $row);
        }

        /*
         * Fallback for installations where the document resource link is
         * resolved through CDocument. This keeps the selector robust without
         * replacing the ResourceFile flow that already worked.
         */
        if (\count($documents) < 200) {
            $qb = $this->em->createQueryBuilder();
            $qb
                ->select(
                    'DISTINCT d.iid AS document_id',
                    'rn.title AS title',
                    'rf.id AS resource_file_id',
                    'rf.title AS file_title',
                    'rf.originalName AS original_name',
                    'rf.mimeType AS mime_type',
                    'rf.size AS size'
                )
                ->from(CDocument::class, 'd')
                ->join('d.resourceNode', 'rn')
                ->join('rn.resourceLinks', 'rl')
                ->join('rn.resourceFiles', 'rf')
                ->where('IDENTITY(rl.course) = :cid')
                ->andWhere('rl.deletedAt IS NULL')
                ->andWhere('d.filetype = :filetype')
                ->andWhere(
                    $qb->expr()->orX(
                        'LOWER(rf.mimeType) = :pdfMime',
                        'LOWER(rf.mimeType) LIKE :textMime',
                        'LOWER(rf.originalName) LIKE :pdfExt',
                        'LOWER(rf.originalName) LIKE :txtExt',
                        'LOWER(rf.title) LIKE :pdfExt',
                        'LOWER(rf.title) LIKE :txtExt',
                        'LOWER(rn.title) LIKE :pdfExt',
                        'LOWER(rn.title) LIKE :txtExt'
                    )
                )
                ->orderBy('rn.title', 'ASC')
                ->addOrderBy('rf.id', 'ASC')
                ->setMaxResults(200)
                ->setParameter('cid', $cid, Types::INTEGER)
                ->setParameter('filetype', 'file')
                ->setParameter('pdfMime', 'application/pdf')
                ->setParameter('textMime', 'text/plain%')
                ->setParameter('pdfExt', '%.pdf')
                ->setParameter('txtExt', '%.txt')
            ;

            if ($sid > 0) {
                $qb
                    ->andWhere('(IDENTITY(rl.session) = :sid OR rl.session IS NULL)')
                    ->setParameter('sid', $sid, Types::INTEGER)
                ;
            }

            /** @var list<array<string, mixed>> $rows */
            $rows = $qb->getQuery()->getArrayResult();

            foreach ($rows as $row) {
                if (\count($documents) >= 200) {
                    break;
                }

                $this->addGlossaryDocumentSourceFromRow($documents, $seen, $row);
            }
        }

        return new JsonResponse(['documents' => $documents]);
    }

    #[Route('/glossary_default_prompt', name: 'chamilo_core_ai_glossary_default_prompt', methods: ['GET'])]
    public function glossaryDefaultPrompt(Request $request): JsonResponse
    {
        try {
            $this->denyIfNotTeacher();
        } catch (AccessDeniedException) {
            return new JsonResponse(['prompt' => ''], 403);
        }

        $cid = (int) $request->query->get('cid', 0);
        $sid = (int) $request->query->get('sid', 0);
        $n = (int) $request->query->get('n', 15);
        $resourceFileId = (int) $request->query->get('resource_file_id', 0);

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

        $existingTerms = $this->getExistingGlossaryTermTitles($course, $sid);

        if ($resourceFileId > 0) {
            /** @var ResourceFile|null $resourceFile */
            $resourceFile = $this->em->getRepository(ResourceFile::class)->find($resourceFileId);
            if (null === $resourceFile) {
                return new JsonResponse(['prompt' => ''], 404);
            }

            if (!$this->resourceFileBelongsToCourse($resourceFile, $cid)) {
                return new JsonResponse(['prompt' => ''], 403);
            }

            $node = $resourceFile->getResourceNode();
            $documentTitle = null !== $node ? (string) $node->getTitle() : (string) ($resourceFile->getOriginalName() ?: $resourceFile->getTitle());

            return new JsonResponse([
                'prompt' => $this->appendExistingGlossaryTermsToPrompt(
                    $this->buildGlossaryFromDocumentPrompt(
                        (string) $course->getTitle(),
                        $documentTitle,
                        (string) $request->query->get('language', 'en'),
                        $n
                    ),
                    $existingTerms
                ),
            ]);
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

        $prompt = $this->appendExistingGlossaryTermsToPrompt($prompt, $existingTerms);

        return new JsonResponse(['prompt' => $prompt]);
    }

    #[Route('/generate_glossary_terms', name: 'chamilo_core_ai_generate_glossary_terms', methods: ['POST'])]
    public function generateGlossaryTerms(Request $request): JsonResponse
    {
        try {
            $this->denyIfNotTeacher();
        } catch (AccessDeniedException) {
            return new JsonResponse([
                'success' => false,
                'text' => 'Access denied.',
            ], 403);
        }

        $debug = false;

        try {
            $debug = (bool) $this->getParameter('kernel.debug');
        } catch (Throwable) {
            $debug = false;
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
        $providerName = $this->normalizeProviderNameFromPayload($data['ai_provider'] ?? null);
        $cid = (int) ($data['cid'] ?? 0);
        $sid = (int) ($data['sid'] ?? 0);
        $toolName = trim((string) ($data['tool'] ?? 'glossary'));
        $resourceFileId = (int) ($data['resource_file_id'] ?? 0);
        $documentTitle = trim((string) ($data['document_title'] ?? ''));

        if ($n < 1) {
            $n = 1;
        }
        if ($n > 200) {
            $n = 200;
        }

        if (0 === $cid || (0 === $resourceFileId && '' === $prompt)) {
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

        $existingTerms = $this->getExistingGlossaryTermTitles($course, $sid);

        try {
            if ($resourceFileId > 0) {
                /** @var ResourceFile|null $resourceFile */
                $resourceFile = $this->em->getRepository(ResourceFile::class)->find($resourceFileId);
                if (null === $resourceFile) {
                    return new JsonResponse([
                        'success' => false,
                        'text' => 'Resource file not found.',
                    ], 404);
                }

                $node = $resourceFile->getResourceNode();
                if (null === $node) {
                    return new JsonResponse([
                        'success' => false,
                        'text' => 'Resource node not found.',
                    ], 404);
                }

                if (!$this->resourceFileBelongsToCourse($resourceFile, $cid)) {
                    return new JsonResponse([
                        'success' => false,
                        'text' => 'Resource does not belong to this course.',
                    ], 403);
                }

                $fileUri = $this->resourceNodeRepository->getFilename($resourceFile);
                if (!\is_string($fileUri) || '' === trim($fileUri)) {
                    return new JsonResponse([
                        'success' => false,
                        'text' => 'Could not resolve resource file URI.',
                    ], 500);
                }

                try {
                    $binary = (string) $this->resourceNodeRepository->getFileSystem()->read($fileUri);
                } catch (Throwable $e) {
                    if ($debug) {
                        error_log('[AI][glossary_document] Failed to read file: '.$e->getMessage());
                    }

                    return new JsonResponse([
                        'success' => false,
                        'text' => 'Failed to read file content.',
                    ], 500);
                }

                if ('' === $binary) {
                    return new JsonResponse([
                        'success' => false,
                        'text' => 'File is empty.',
                    ], 400);
                }

                $filename = basename($fileUri);
                $mimeType = (string) ($resourceFile->getMimeType() ?: 'application/octet-stream');
                $mode = $this->getSupportedDocumentMode($filename, $mimeType);

                if ('' === $mode) {
                    return new JsonResponse([
                        'success' => false,
                        'text' => 'Unsupported file type. Supported: PDF, TXT.',
                    ], 415);
                }

                $maxBytesPdf = 10 * 1024 * 1024;
                $maxBytesTxt = 1 * 1024 * 1024;

                if ('pdf' === $mode && \strlen($binary) > $maxBytesPdf) {
                    return new JsonResponse([
                        'success' => false,
                        'text' => 'Document is too large to analyze.',
                    ], 413);
                }

                if ('txt' === $mode && \strlen($binary) > $maxBytesTxt) {
                    return new JsonResponse([
                        'success' => false,
                        'text' => 'Text file is too large to analyze.',
                    ], 413);
                }

                $docLabel = '' !== $documentTitle ? $documentTitle : (string) ($node->getTitle() ?: $filename);
                $documentPrompt = $this->appendExistingGlossaryTermsToPrompt(
                    $this->buildGlossaryFromDocumentPrompt((string) $course->getTitle(), $docLabel, $language, $n),
                    $existingTerms
                );

                if ('txt' === $mode) {
                    $text = $this->normalizePlainText($binary);

                    $maxChars = 20000;
                    $truncated = false;
                    if (mb_strlen($text) > $maxChars) {
                        $text = mb_substr($text, 0, $maxChars);
                        $truncated = true;
                    }

                    $fullPrompt = $documentPrompt
                        ."\n\nUse only the following document content as source material:\n"
                        .$text
                        .($truncated ? "\n\n[Content truncated]" : '');

                    $provider = $this->aiProviderFactory->getProvider($providerName, 'text');

                    if (!\is_object($provider) || !method_exists($provider, 'generateText')) {
                        return new JsonResponse([
                            'success' => false,
                            'text' => 'Selected AI provider does not support text generation.',
                        ], 400);
                    }

                    try {
                        $raw = (string) $provider->generateText($fullPrompt, [
                            'language' => $language,
                            'n' => $n,
                            'tool' => 'glossary_terms_from_document_txt',
                            'cid' => $cid,
                            'sid' => $sid,
                            'resource_file_id' => $resourceFileId,
                        ]);
                    } catch (TypeError $e) {
                        $raw = (string) $provider->generateText($fullPrompt, $language);
                    }

                    $raw = trim($raw);
                } else {
                    $provider = $this->aiProviderFactory->getProvider($providerName, 'document_process');

                    if (!$provider instanceof AiDocumentProcessProviderInterface) {
                        return new JsonResponse([
                            'success' => false,
                            'text' => 'Selected AI provider does not support document processing.',
                        ], 400);
                    }

                    $result = $provider->processDocument(
                        $documentPrompt,
                        'glossary_terms_from_document_pdf',
                        $filename,
                        'application/pdf',
                        $binary,
                        [
                            'language' => $language,
                            'n' => $n,
                            'cid' => $cid,
                            'sid' => $sid,
                            'resource_file_id' => $resourceFileId,
                        ]
                    );

                    $raw = \is_string($result) ? trim($result) : '';
                }

                if ('' === $raw) {
                    return new JsonResponse([
                        'success' => false,
                        'text' => 'AI request returned an empty response.',
                    ], 500);
                }

                if (str_starts_with($raw, 'Error:')) {
                    $msg = trim((string) preg_replace('/^Error:\s*/', '', $raw));
                    $status = $this->mapDocumentErrorToHttpStatus($msg);

                    return new JsonResponse([
                        'success' => false,
                        'text' => '' !== $msg ? $msg : $raw,
                    ], $status);
                }

                $this->aiDisclosureHelper->logAudit(
                    targetKey: 'course:'.$cid.':glossary_terms_document:'.sha1($resourceFileId.'|'.$language.'|'.$n),
                    userId: $this->getCurrentUserId(),
                    meta: [
                        'feature' => 'glossary_generator_document',
                        'mode' => 'generated',
                        'provider' => $providerName ?? 'default',
                        'tool' => $toolName,
                        'n' => $n,
                        'language' => $language,
                        'resource_file_id' => $resourceFileId,
                        'document_title' => $docLabel,
                        'document_mode' => $mode,
                    ],
                    courseId: $cid,
                    sessionId: $sid > 0 ? $sid : api_get_session_id()
                );

                return new JsonResponse([
                    'success' => true,
                    'text' => $raw,
                    'ai_assisted' => $this->aiDisclosureHelper->isDisclosureEnabled(),
                ]);
            }

            $prompt = $this->appendExistingGlossaryTermsToPrompt($prompt, $existingTerms);

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

    #[Route('/course/{courseId}/analyzer', name: 'chamilo_core_ai_course_analyzer', methods: ['GET', 'POST'])]
    public function courseAnalyzer(
        Request $request,
        int $courseId,
        CourseRepository $courseRepository,
        SettingsManager $settingsManager,
        AiCourseAnalyzerService $courseAnalyzerService,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): Response {
        /** @var Course|null $course */
        $course = $courseRepository->find($courseId);
        if (!$course instanceof Course) {
            throw $this->createNotFoundException('Course not found.');
        }

        $this->denyAccessUnlessGranted(CourseVoter::EDIT, $course);

        $enabled = $this->isAiCourseAnalyzerSettingEnabled($settingsManager->getSetting('ai_helpers.enable_ai_helpers', true))
            && $this->isAiCourseAnalyzerSettingEnabled($settingsManager->getSetting('ai_helpers.course_analyser', true));

        $session = $this->getAiCourseAnalyzerSessionFromRequest($request);
        $providers = $this->aiProviderFactory->getProvidersForType('text');
        $defaultProvider = $providers[0] ?? '';
        $csrfTokenId = 'ai_course_analyzer_'.$course->getId();

        $result = null;
        $error = null;
        $prompt = trim((string) $request->request->get('prompt', ''));
        $selectedProvider = trim((string) $request->request->get('provider', $defaultProvider));
        $includeStandaloneDocuments = '1' === (string) $request->request->get('include_standalone_documents', '0');
        $includeStandaloneExercises = '1' === (string) $request->request->get('include_standalone_exercises', '0');

        if ('' === $selectedProvider) {
            $selectedProvider = $defaultProvider;
        }

        if ($request->isMethod('POST')) {
            if (!$enabled) {
                $error = 'AI course analyzer is disabled.';
            } elseif ('' === $prompt) {
                $error = 'Please describe what kind of feedback you want.';
            } elseif ('' === $selectedProvider || !\in_array($selectedProvider, $providers, true)) {
                $error = 'No valid AI text provider is configured.';
            } else {
                $submittedToken = (string) $request->request->get('_token', '');
                $token = new CsrfToken($csrfTokenId, $submittedToken);

                if (!$csrfTokenManager->isTokenValid($token)) {
                    $error = 'Invalid security token. Please reload the page and try again.';
                } else {
                    try {
                        $result = $courseAnalyzerService->analyze(
                            $course,
                            $session,
                            $prompt,
                            $selectedProvider,
                            $includeStandaloneDocuments,
                            $includeStandaloneExercises,
                        );
                    } catch (Throwable $exception) {
                        $error = 'The AI analysis could not be completed: '.$exception->getMessage();
                    }
                }
            }
        }

        return $this->render('@ChamiloCore/Course/ai_analyzer.html.twig', [
            'course' => $course,
            'session' => $session,
            'enabled' => $enabled,
            'providers' => $providers,
            'selected_provider' => $selectedProvider,
            'prompt' => $prompt,
            'result' => $result,
            'error' => $error,
            'include_standalone_documents' => $includeStandaloneDocuments,
            'include_standalone_exercises' => $includeStandaloneExercises,
            'csrf_token_id' => $csrfTokenId,
        ]);
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

    #[Route('/lp_learning_helper', name: 'chamilo_core_ai_lp_learning_helper', methods: ['POST'])]
    public function lpLearningHelper(Request $request): JsonResponse
    {
        if ('true' !== api_get_setting('ai_helpers.enable_ai_helpers')) {
            return new JsonResponse([
                'success' => false,
                'text' => 'AI helpers are disabled.',
            ], 403);
        }

        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            return new JsonResponse([
                'success' => false,
                'text' => 'Invalid JSON payload.',
            ], 400);
        }

        $lpItemId = (int) ($data['lp_item_id'] ?? 0);
        $cid = $this->resolveCourseIdFromRequest($request, $data);
        $sid = $this->resolveSessionIdFromRequest($request, $data);
        $method = trim((string) ($data['method'] ?? ''));
        $selectedText = trim((string) ($data['selected_text'] ?? ''));
        $language = trim((string) ($data['language'] ?? 'en'));
        $aiProvider = $this->normalizeProviderNameFromPayload($data['ai_provider'] ?? null);

        if ($lpItemId <= 0 || $cid <= 0 || '' === $method || '' === $selectedText) {
            return new JsonResponse([
                'success' => false,
                'text' => 'Invalid request parameters.',
            ], 400);
        }

        if (!\in_array($method, self::LP_LEARNING_HELPER_METHODS, true)) {
            return new JsonResponse([
                'success' => false,
                'text' => 'Unsupported learning helper method.',
            ], 400);
        }

        if (mb_strlen($selectedText) > self::LP_LEARNING_HELPER_MAX_SELECTED_TEXT_LENGTH) {
            $selectedText = mb_substr($selectedText, 0, self::LP_LEARNING_HELPER_MAX_SELECTED_TEXT_LENGTH);
        }

        if ('' === $language) {
            $language = 'en';
        }

        /** @var CLpItem|null $lpItem */
        $lpItem = $this->em->getRepository(CLpItem::class)->find($lpItemId);
        if (null === $lpItem) {
            return new JsonResponse([
                'success' => false,
                'text' => 'Learning path item not found.',
            ], 404);
        }

        if ('document' !== $lpItem->getItemType()) {
            return new JsonResponse([
                'success' => false,
                'text' => 'AI learning helper is only available for document items.',
            ], 400);
        }

        $lp = $lpItem->getLp();
        $lpNode = $lp->getResourceNode();
        if (null === $lpNode) {
            return new JsonResponse([
                'success' => false,
                'text' => 'Learning path resource was not found.',
            ], 404);
        }

        if (!$this->learningPathBelongsToCourse($lpItem, $cid, $sid)) {
            return new JsonResponse([
                'success' => false,
                'text' => 'Learning path item does not belong to this course.',
            ], 403);
        }

        if (!$this->isGranted('VIEW', $lpNode)) {
            return new JsonResponse([
                'success' => false,
                'text' => 'Access denied.',
            ], 403);
        }

        $prompt = $this->buildLpLearningHelperPrompt(
            $method,
            $language,
            trim($lpItem->getTitle()),
            trim($lp->getTitle()),
            $selectedText
        );

        try {
            $provider = $this->aiProviderFactory->getProvider($aiProvider, 'text');
        } catch (Throwable) {
            return new JsonResponse([
                'success' => false,
                'text' => 'No AI text provider is configured.',
            ], 400);
        }

        if (!\is_object($provider) || !method_exists($provider, 'generateText')) {
            return new JsonResponse([
                'success' => false,
                'text' => 'Selected AI provider does not support text generation.',
            ], 400);
        }

        try {
            $raw = (string) $provider->generateText($prompt, [
                'language' => $language,
                'tool' => 'lp_learning_helper',
                'method' => $method,
                'cid' => $cid,
                'sid' => $sid,
                'lp_item_id' => $lpItemId,
            ]);
        } catch (TypeError) {
            $raw = (string) $provider->generateText($prompt, $language);
        } catch (Throwable $e) {
            error_log('[AI][lp_learning_helper] Generation failed: '.$e->getMessage());

            return new JsonResponse([
                'success' => false,
                'text' => 'An error occurred while generating the learning helper response.',
            ], 500);
        }

        $answer = trim($raw);
        if ('' === $answer) {
            return new JsonResponse([
                'success' => false,
                'text' => 'AI request returned an empty response.',
            ], 500);
        }

        $this->aiDisclosureHelper->logAudit(
            targetKey: 'course:'.$cid.':lp_item:'.$lpItemId.':learning_helper:'.$method,
            userId: $this->getCurrentUserId(),
            meta: [
                'feature' => 'lp_learning_helper',
                'method' => $method,
                'provider' => \is_string($aiProvider) && '' !== trim($aiProvider) ? $aiProvider : 'default',
                'language' => $language,
                'selected_text_length' => mb_strlen($selectedText),
            ],
            courseId: $cid,
            sessionId: $sid
        );

        return new JsonResponse([
            'success' => true,
            'text' => $answer,
            'method' => $method,
            'ai_assisted' => $this->aiDisclosureHelper->isDisclosureEnabled(),
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
            $this->denyIfNotTeacher();
        } catch (AccessDeniedException) {
            return new JsonResponse([
                'success' => false,
                'text' => 'Access denied.',
            ], 403);
        }

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
            $aiProvider = $this->normalizeProviderNameFromPayload($data['ai_provider'] ?? null);
            $cid = (int) ($data['cid'] ?? 0);
            $sid = (int) ($data['sid'] ?? 0);

            if ($nQ <= 0 || '' === $topic) {
                return new JsonResponse([
                    'success' => false,
                    'text' => 'Invalid request parameters. Ensure all fields are filled correctly.',
                ], 400);
            }

            if ($nQ > 100) {
                $nQ = 100;
            }

            $aiService = $this->aiProviderFactory->getProvider($aiProvider, 'text');

            if (!\is_object($aiService) || !method_exists($aiService, 'generateQuestions')) {
                return new JsonResponse([
                    'success' => false,
                    'text' => 'Selected AI provider does not support question generation.',
                ], 400);
            }

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

            if ('' === $questionsText) {
                return new JsonResponse([
                    'success' => false,
                    'text' => 'AI request returned an invalid Aiken response.',
                ], 500);
            }

            $this->aiDisclosureHelper->logAudit(
                targetKey: 'course:'.$cid.':aiken:'.sha1($topic.'|'.$language.'|'.$nQ.'|'.$questionType),
                userId: $this->getCurrentUserId(),
                meta: [
                    'feature' => 'exercise_generator_aiken',
                    'mode' => 'generated',
                    'provider' => $aiProvider ?? 'default',
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
        } catch (Throwable $e) {
            error_log('[AI][aiken] Generation failed: '.$e->getMessage());

            return new JsonResponse([
                'success' => false,
                'text' => 'An error occurred while generating questions. Please contact the administrator.',
            ], 500);
        }
    }

    #[Route('/generate_aiken_from_document', name: 'chamilo_core_ai_generate_aiken_from_document', methods: ['POST'])]
    public function generateAikenFromDocument(Request $request): JsonResponse
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
        $topicPrompt = trim((string) ($data['quiz_name'] ?? $data['prompt'] ?? ''));
        $questionType = (string) ($data['question_type'] ?? 'multiple_choice');
        $aiProvider = $this->normalizeProviderNameFromPayload($data['ai_provider'] ?? null);

        $cid = (int) ($data['cid'] ?? 0);
        $sid = (int) ($data['sid'] ?? 0);
        $gid = (int) ($data['gid'] ?? 0);

        $resourceFileId = (int) ($data['resource_file_id'] ?? 0);
        $documentTitle = trim((string) ($data['document_title'] ?? ''));

        if (0 === $cid || 0 === $resourceFileId || $nQ <= 0) {
            return new JsonResponse([
                'success' => false,
                'text' => 'Invalid request parameters.',
            ], 400);
        }

        if ($nQ > 100) {
            $nQ = 100;
        }

        /** @var Course|null $course */
        $course = $this->em->getRepository(Course::class)->find($cid);
        if (null === $course) {
            return new JsonResponse([
                'success' => false,
                'text' => 'Course not found.',
            ], 404);
        }

        /** @var ResourceFile|null $resourceFile */
        $resourceFile = $this->em->getRepository(ResourceFile::class)->find($resourceFileId);
        if (null === $resourceFile) {
            return new JsonResponse([
                'success' => false,
                'text' => 'Resource file not found.',
            ], 404);
        }

        $node = $resourceFile->getResourceNode();
        if (null === $node) {
            return new JsonResponse([
                'success' => false,
                'text' => 'Resource node not found.',
            ], 404);
        }

        $belongsToCourse = false;
        foreach ($node->getResourceLinks() as $link) {
            $linkCourse = $link->getCourse();
            if ($linkCourse && (int) $linkCourse->getId() === $cid) {
                $belongsToCourse = true;

                break;
            }
        }

        if (!$belongsToCourse) {
            return new JsonResponse([
                'success' => false,
                'text' => 'Resource does not belong to this course.',
            ], 403);
        }

        $fileUri = $this->resourceNodeRepository->getFilename($resourceFile);
        if (!\is_string($fileUri) || '' === trim($fileUri)) {
            return new JsonResponse([
                'success' => false,
                'text' => 'Could not resolve resource file URI.',
            ], 500);
        }

        try {
            $binary = (string) $this->resourceNodeRepository->getFileSystem()->read($fileUri);
        } catch (Throwable $e) {
            if ($debug) {
                error_log('[AI][aiken_document] Failed to read file: '.$e->getMessage());
            }

            return new JsonResponse([
                'success' => false,
                'text' => 'Failed to read file content.',
            ], 500);
        }

        if ('' === $binary) {
            return new JsonResponse([
                'success' => false,
                'text' => 'File is empty.',
            ], 400);
        }

        $filename = basename($fileUri);

        $mimeType = (string) $resourceFile->getMimeType();
        if ('' === trim($mimeType)) {
            $mimeType = 'application/octet-stream';
        }

        $ext = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));
        $isPdf = ('pdf' === $ext) || ('application/pdf' === $mimeType);
        $isTxt = ('txt' === $ext) || str_starts_with(strtolower($mimeType), 'text/plain');

        $maxBytesPdf = 10 * 1024 * 1024;
        $maxBytesTxt = 1 * 1024 * 1024;

        if ($isPdf && \strlen($binary) > $maxBytesPdf) {
            return new JsonResponse([
                'success' => false,
                'text' => 'Document is too large to analyze.',
            ], 413);
        }

        if ($isTxt && \strlen($binary) > $maxBytesTxt) {
            return new JsonResponse([
                'success' => false,
                'text' => 'Text file is too large to analyze.',
            ], 413);
        }

        if (!$isPdf && !$isTxt) {
            return new JsonResponse([
                'success' => false,
                'text' => 'Unsupported file type. Supported: PDF, TXT.',
            ], 415);
        }

        $courseTitle = (string) $course->getTitle();
        $docLabel = '' !== $documentTitle ? $documentTitle : (string) ($node->getTitle() ?? $filename);

        $basePrompt = $this->buildAikenFromDocumentPrompt(
            $courseTitle,
            $docLabel,
            $topicPrompt,
            $language,
            $sid,
            $nQ,
            $questionType
        );

        try {
            if ($isTxt) {
                $text = $this->normalizePlainText($binary);

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
                        'tool' => 'exercise_aiken_from_document_txt',
                        'cid' => $cid,
                        'sid' => $sid,
                        'gid' => $gid,
                        'n' => $nQ,
                        'question_type' => $questionType,
                    ]);
                } catch (TypeError) {
                    $raw = (string) $provider->generateText($fullPrompt, $language);
                }

                $result = trim($raw);
            } else {
                $provider = $this->aiProviderFactory->getProvider($aiProvider, 'document_process');

                if (!$provider instanceof AiDocumentProcessProviderInterface) {
                    return new JsonResponse([
                        'success' => false,
                        'text' => 'Selected AI provider does not support document processing.',
                    ], 400);
                }

                $result = $provider->processDocument(
                    $basePrompt,
                    'exercise_aiken_from_document_pdf',
                    $filename,
                    'application/pdf',
                    $binary,
                    [
                        'language' => $language,
                        'cid' => $cid,
                        'sid' => $sid,
                        'gid' => $gid,
                        'n' => $nQ,
                        'question_type' => $questionType,
                    ]
                );

                $result = \is_string($result) ? trim($result) : '';
            }

            if ('' === $result) {
                return new JsonResponse([
                    'success' => false,
                    'text' => 'AI request returned an empty response.',
                ], 500);
            }

            if (str_starts_with($result, 'Error:')) {
                $msg = trim((string) preg_replace('/^Error:\s*/', '', $result));
                $status = $this->mapDocumentErrorToHttpStatus($msg);

                return new JsonResponse([
                    'success' => false,
                    'text' => '' !== $msg ? $msg : $result,
                ], $status);
            }

            $questionsText = $this->sanitizeGeneratedAikenText($result);

            if ('' === $questionsText) {
                return new JsonResponse([
                    'success' => false,
                    'text' => 'AI request returned an invalid Aiken response.',
                ], 500);
            }

            // Do not prepend visible disclosure text here because it would break the Aiken format.
            $this->aiDisclosureHelper->logAudit(
                targetKey: 'course:'.$cid.':aiken_document:'.sha1($resourceFileId.'|'.$topicPrompt.'|'.$language.'|'.$nQ.'|'.$questionType),
                userId: $this->getCurrentUserId(),
                meta: [
                    'feature' => 'exercise_generator_aiken_document',
                    'mode' => 'generated',
                    'provider' => $aiProvider ?? 'default',
                    'language' => $language,
                    'question_type' => $questionType,
                    'n' => $nQ,
                    'resource_file_id' => $resourceFileId,
                    'document_title' => $docLabel,
                ],
                courseId: $cid,
                sessionId: $sid > 0 ? $sid : api_get_session_id()
            );

            return new JsonResponse([
                'success' => true,
                'text' => $questionsText,
                'ai_assisted' => $this->aiDisclosureHelper->isDisclosureEnabled(),
            ]);
        } catch (Throwable $e) {
            $msg = trim($e->getMessage());

            if ($debug) {
                error_log('[AI][aiken_document] Exception: '.$msg);
            }

            $status = $this->mapDocumentErrorToHttpStatus($msg);

            return new JsonResponse([
                'success' => false,
                'text' => $debug && '' !== $msg ? $msg : 'An error occurred while generating questions from the document.',
            ], $status);
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
     * @param list<array<string, mixed>> $documents
     * @param array<int, bool>           $seen
     * @param array<string, mixed>       $row
     */
    private function addGlossaryDocumentSourceFromRow(array &$documents, array &$seen, array $row): void
    {
        $resourceFileId = (int) ($row['resource_file_id'] ?? 0);
        if ($resourceFileId <= 0 || isset($seen[$resourceFileId])) {
            return;
        }

        $title = trim((string) ($row['title'] ?? ''));
        $fileTitle = trim((string) ($row['file_title'] ?? ''));
        $originalName = trim((string) ($row['original_name'] ?? ''));
        $filename = $originalName ?: $fileTitle ?: $title;
        $mimeType = trim((string) ($row['mime_type'] ?? ''));
        $mode = $this->getSupportedDocumentMode($filename, $mimeType);

        if ('' === $mode) {
            return;
        }

        $seen[$resourceFileId] = true;
        $documents[] = [
            'resource_file_id' => $resourceFileId,
            'document_id' => (int) ($row['document_id'] ?? 0),
            'title' => '' !== $title ? $title : $filename,
            'filename' => $filename,
            'mime_type' => $mimeType,
            'mode' => $mode,
            'size' => (int) ($row['size'] ?? 0),
        ];
    }

    /**
     * @return list<string>
     */
    private function getExistingGlossaryTermTitles(Course $course, int $sid = 0, int $limit = 200): array
    {
        $session = null;
        if ($sid > 0) {
            /** @var Session|null $session */
            $session = $this->em->getRepository(Session::class)->find($sid);
        }

        $repo = $this->em->getRepository(CGlossary::class);
        if (!$repo instanceof CGlossaryRepository) {
            return [];
        }

        try {
            $qb = $repo->getResourcesByCourse($course, $session, null, null, true, true);
            $qb
                ->orderBy('resource.title', 'ASC')
                ->setMaxResults($limit)
            ;

            /** @var list<CGlossary> $glossaryItems */
            $glossaryItems = $qb->getQuery()->getResult();
        } catch (Throwable) {
            return [];
        }

        $terms = [];
        $seen = [];

        foreach ($glossaryItems as $item) {
            if (!$item instanceof CGlossary) {
                continue;
            }

            $title = $this->normalizeExistingGlossaryTermTitle($item->getTitle());
            if ('' === $title) {
                continue;
            }

            $key = mb_strtolower($title);
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $terms[] = $title;
        }

        return $terms;
    }

    private function normalizeExistingGlossaryTermTitle(string $title): string
    {
        $title = trim(strip_tags($title));
        $title = preg_replace('/\s+/u', ' ', $title) ?? '';

        return trim($title);
    }

    /**
     * @param list<string> $existingTerms
     */
    private function appendExistingGlossaryTermsToPrompt(string $prompt, array $existingTerms): string
    {
        if (empty($existingTerms)) {
            return $prompt;
        }

        if (str_contains($prompt, 'Existing glossary terms that must not be generated again:')) {
            return $prompt;
        }

        $prompt .= "\n\nExisting glossary terms that must not be generated again:\n";

        foreach ($existingTerms as $term) {
            $prompt .= '- '.$term."\n";
        }

        $prompt .= "\nDo not generate duplicate entries for these terms, and do not generate minor spelling, plural, singular or capitalization variants of them.";

        return $prompt;
    }

    private function resourceFileBelongsToCourse(ResourceFile $resourceFile, int $courseId): bool
    {
        if ($courseId <= 0) {
            return false;
        }

        $node = $resourceFile->getResourceNode();
        if (null !== $node) {
            foreach ($node->getResourceLinks() as $link) {
                $linkCourse = $link->getCourse();
                if ($linkCourse && (int) $linkCourse->getId() === $courseId) {
                    return true;
                }
            }
        }

        $qb = $this->em->createQueryBuilder();
        $count = (int) $qb
            ->select('COUNT(d.iid)')
            ->from(CDocument::class, 'd')
            ->join('d.resourceNode', 'rn')
            ->join('rn.resourceLinks', 'rl')
            ->join('rn.resourceFiles', 'rf')
            ->where('rf.id = :resourceFileId')
            ->andWhere('IDENTITY(rl.course) = :courseId')
            ->andWhere('rl.deletedAt IS NULL')
            ->andWhere('d.filetype = :filetype')
            ->setParameter('resourceFileId', (int) $resourceFile->getId(), Types::INTEGER)
            ->setParameter('courseId', $courseId, Types::INTEGER)
            ->setParameter('filetype', 'file')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return $count > 0;
    }

    private function buildGlossaryFromDocumentPrompt(
        string $courseTitle,
        string $documentTitle,
        string $language,
        int $numberOfTerms
    ): string {
        $prompt = "You are an expert teacher creating glossary entries.\n";
        $prompt .= "Language: {$language}\n";
        $prompt .= "Course: {$courseTitle}\n";
        $prompt .= "Document: {$documentTitle}\n";
        $prompt .= "Requested term count: {$numberOfTerms}\n\n";
        $prompt .= "Task:\n";
        $prompt .= "- Use only the selected document content as source material.\n";
        $prompt .= "- Do not use outside knowledge or general course context.\n";
        $prompt .= "- Generate exactly {$numberOfTerms} glossary terms when possible.\n";
        $prompt .= "- Select relevant concepts, names, acronyms, expressions or technical terms found in the document.\n\n";
        $prompt .= "Output rules:\n";
        $prompt .= "- Return plain text only.\n";
        $prompt .= "- Each term must be on a single line.\n";
        $prompt .= "- The definition must be on the next line.\n";
        $prompt .= "- Add one blank line between terms.\n";
        $prompt .= "- Do not return Markdown, HTML, bullets, code fences, headings or explanations.\n";

        return $prompt;
    }

    private function getSupportedDocumentMode(string $filename, string $mimeType): string
    {
        $mimeType = strtolower(trim($mimeType));
        $extension = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));

        if ('pdf' === $extension || 'application/pdf' === $mimeType) {
            return 'pdf';
        }

        if ('txt' === $extension || str_starts_with($mimeType, 'text/plain')) {
            return 'txt';
        }

        return '';
    }

    private function buildAikenFromDocumentPrompt(
        string $courseTitle,
        string $documentTitle,
        string $teacherPrompt,
        string $language,
        int $sid,
        int $numberOfQuestions,
        string $questionType
    ): string {
        $teacherPrompt = trim($teacherPrompt);
        $questionTypeLabel = $this->normalizeAikenQuestionTypeLabel($questionType);

        $prompt = "You are an expert teacher creating a student assessment.\n";
        $prompt .= "Language: {$language}\n";
        $prompt .= "Course: {$courseTitle}\n";
        $prompt .= "Document: {$documentTitle}\n";

        if ($sid > 0) {
            $prompt .= "Session ID: {$sid}\n";
        }

        $prompt .= "Requested question count: {$numberOfQuestions}\n";
        $prompt .= "Question type: {$questionTypeLabel}\n";

        if ('' !== $teacherPrompt) {
            $prompt .= "Teacher topic hint: {$teacherPrompt}\n";
        }

        $prompt .= "\nTask:\n";
        $prompt .= "- Use only the provided document.\n";
        $prompt .= "- The goal is to evaluate the student's understanding of the document.\n";
        $prompt .= "- Do not use outside knowledge.\n";
        $prompt .= "- Generate exactly {$numberOfQuestions} questions.\n";
        $prompt .= "- Each question must have exactly four answer options: A, B, C, D.\n";
        $prompt .= "- Each question must have exactly one correct answer.\n";

        $prompt .= "\nOutput rules:\n";
        $prompt .= "- Return strict AIKEN format only.\n";
        $prompt .= "- Return plain text only.\n";
        $prompt .= "- Do not return Markdown, HTML, code fences, headings or explanations.\n";
        $prompt .= "- Do not number the questions.\n";
        $prompt .= "- After each question block, add exactly one line in the format ANSWER: X.\n";
        $prompt .= "- Do not add ANSWER_EXPLANATION lines.\n";

        return $prompt;
    }

    private function normalizeAikenQuestionTypeLabel(string $questionType): string
    {
        return match ($questionType) {
            'multiple_choice' => 'single-answer multiple choice',
            default => 'single-answer multiple choice',
        };
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

    private function learningPathBelongsToCourse(CLpItem $lpItem, int $courseId, int $sessionId): bool
    {
        $lp = $lpItem->getLp();
        $node = $lp->getResourceNode();
        if (null === $node) {
            return false;
        }

        foreach ($node->getResourceLinks() as $link) {
            $linkCourse = $link->getCourse();
            if (null === $linkCourse || (int) $linkCourse->getId() !== $courseId) {
                continue;
            }

            if ($sessionId <= 0) {
                return true;
            }

            $linkSession = $link->getSession();
            if (null === $linkSession || (int) $linkSession->getId() === $sessionId) {
                return true;
            }
        }

        return false;
    }

    private function buildLpLearningHelperPrompt(
        string $method,
        string $language,
        string $lpItemTitle,
        string $lpTitle,
        string $selectedText
    ): string {
        $topic = '' !== $lpItemTitle ? $lpItemTitle : $lpTitle;
        if ('' === trim($topic)) {
            $topic = 'the selected learning content';
        }

        $instructions = [
            'mind_map' => 'Explain how to create a mind map to visually structure and organize the key concepts, facilitating better understanding and recall.',
            'feynman' => 'Demonstrate how to apply the Feynman Technique by simplifying complex concepts and explaining them as if teaching someone else.',
            'elaborative_interrogation' => 'Describe the process of elaborative interrogation and provide useful questions and examples to improve information retention.',
            'spaced_repetition' => 'Explain how to incorporate spaced repetition into a study routine to enhance long-term memory retention and recall.',
            'sq3r' => 'Introduce the SQ3R Method and demonstrate how to apply it to read and retain information from this content.',
            'analogies_metaphors' => 'Share analogies and metaphors that simplify complex ideas and make them more memorable and easier to understand.',
            'dual_coding' => 'Explain how to combine verbal and visual information using dual coding theory to enhance understanding and retention.',
            'storytelling' => 'Explain how to transform the concepts into a relatable story, making them more memorable and easier to understand.',
            'thematic_connections' => 'Describe how to build thematic connections between different aspects of the topic to form a coherent mental structure.',
            'interleaved_learning' => 'Introduce interleaved learning and demonstrate how to mix related subjects or skills to enhance retention and transfer.',
            'memory_palace' => 'Describe how to create a memory palace to retain and recall the main ideas by associating concepts with vivid mental images and locations.',
        ];
        $instruction = $instructions[$method] ?? $instructions['feynman'];

        return "You are an educational assistant inside Chamilo LMS.\n"
            ."Answer in the {$language} language.\n"
            ."Use the selected learning path document content as the only source material.\n"
            ."Do not invent facts that are not supported by the selected text.\n"
            ."Keep the explanation practical and useful for a learner.\n\n"
            ."Learning path: {$lpTitle}\n"
            ."Topic: {$topic}\n"
            ."Task: {$instruction}\n\n"
            ."Selected content:\n{$selectedText}";
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

    private function getAiCourseAnalyzerSessionFromRequest(Request $request): ?Session
    {
        $sessionId = (int) $request->get('sid', 0);
        if ($sessionId <= 0) {
            return null;
        }

        $session = $this->em->getRepository(Session::class)->find($sessionId);

        return $session instanceof Session ? $session : null;
    }

    private function isAiCourseAnalyzerSettingEnabled(mixed $value): bool
    {
        if (\is_bool($value)) {
            return $value;
        }

        if (\is_int($value)) {
            return 1 === $value;
        }

        return \in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
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
