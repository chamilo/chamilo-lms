<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceIllustrationInterface;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\SequenceResource;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Chamilo\CoreBundle\Search\Xapian\XapianIndexService;
use Chamilo\CoreBundle\Search\Xapian\XapianSearchService;
use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceNodeVoter;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

use const ENT_HTML5;
use const ENT_QUOTES;
use const ENT_SUBSTITUTE;
use const PATHINFO_EXTENSION;

#[IsGranted('ROLE_USER')]
#[Route('/search')]
final class SearchController extends AbstractController
{
    public function __construct(
        private readonly XapianSearchService $xapianSearchService,
        private readonly XapianIndexService $xapianIndexService,
        private readonly EntityManagerInterface $em,
        private readonly SettingsManager $settingsManager,
        private readonly IllustrationRepository $illustrationRepository,
    ) {}

    #[Route(
        path: '/xapian',
        name: 'chamilo_core.search_xapian',
        methods: ['GET']
    )]
    public function xapianSearchAction(Request $request): JsonResponse
    {
        $q = trim((string) $request->query->get('q', ''));

        if ('' === $q) {
            return $this->json([
                'query' => '',
                'total' => 0,
                'results' => [],
            ]);
        }

        $languageIso = $this->resolveRequestLanguageIso($request);

        try {
            $result = $this->xapianSearchService->search(
                queryString: $q,
                offset: 0,
                length: 20,
                extra: [
                    'language' => $languageIso,
                ]
            );

            return $this->json([
                'query' => $q,
                'language' => $languageIso,
                'total' => $result['count'],
                'results' => $result['results'],
            ]);
        } catch (Throwable $e) {
            return $this->json([
                'query' => $q,
                'language' => $languageIso,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    #[Route(
        path: '/ui',
        name: 'chamilo_core.search_ui',
        methods: ['GET']
    )]
    public function xapianSearchPageAction(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $q = trim((string) $request->query->get('q', ''));

        $estimatedTotal = 0;
        $results = [];
        $error = null;

        $languageIso = $this->resolveRequestLanguageIso($request);

        // Setting: show results even if user has no access?
        $showUnlinked = 'true' === (string) $this->settingsManager->getSetting('search.search_show_unlinked_results', true);

        if ('' !== $q) {
            try {
                $searchResult = $this->xapianSearchService->search(
                    queryString: $q,
                    offset: 0,
                    length: 20,
                    extra: [
                        'language' => $languageIso,
                    ]
                );

                $estimatedTotal = (int) ($searchResult['count'] ?? 0);
                $results = $searchResult['results'] ?? [];

                $results = $this->hydrateResultsWithCourseRootNode($results);
                $results = $this->hydrateQuestionResultsWithQuizIds($results);

                $results = $this->hydrateResultsWithCourseMeta($results);

                $results = $this->decorateResultsForUi($results, $q);

                $results = $this->applyAccessPrefilter($results, $showUnlinked);
            } catch (Throwable $e) {
                $error = $e->getMessage();
            }
        }

        return $this->render('@ChamiloCore/Search/xapian_search.html.twig', [
            'query' => $q,
            'language' => $languageIso,
            'show_unlinked' => $showUnlinked,
            'estimated_total' => $estimatedTotal,
            'visible_total' => \is_array($results) ? \count($results) : 0,
            'results' => $results,
            'error' => $error,
        ]);
    }

    #[Route(
        path: '/xapian/demo-index',
        name: 'chamilo_core.search_xapian_demo_index',
        methods: ['POST']
    )]
    public function xapianDemoIndexAction(): JsonResponse
    {
        try {
            $docId = $this->xapianIndexService->indexDemoDocument();

            return $this->json([
                'indexed' => true,
                'doc_id' => $docId,
            ]);
        } catch (Throwable $e) {
            return $this->json([
                'indexed' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Attach course_root_node_id to each result if we can resolve it from course_id.
     *
     * @param array<int, array<string, mixed>> $results
     *
     * @return array<int, array<string, mixed>>
     */
    private function hydrateResultsWithCourseRootNode(array $results): array
    {
        foreach ($results as &$result) {
            if (!\is_array($result)) {
                continue;
            }

            $data = $result['data'] ?? [];
            if (!\is_array($data)) {
                $data = [];
            }

            if (!empty($data['course_root_node_id'])) {
                $result['data'] = $data;

                continue;
            }

            $courseId = isset($data['course_id']) && '' !== (string) $data['course_id']
                ? (int) $data['course_id']
                : null;

            if (!$courseId) {
                $result['data'] = $data;

                continue;
            }

            /** @var Course|null $course */
            $course = $this->em->find(Course::class, $courseId);
            if (!$course || !$course->getResourceNode()) {
                $result['data'] = $data;

                continue;
            }

            $data['course_root_node_id'] = (string) $course->getResourceNode()->getId();
            $result['data'] = $data;
        }

        return $results;
    }

    /**
     * For question results, resolve the related quiz from c_quiz_rel_question
     * and attach quiz_id (and quiz_title) into the result data so Twig can build safer links/UI.
     *
     * @param array<int, array<string, mixed>> $results
     *
     * @return array<int, array<string, mixed>>
     */
    private function hydrateQuestionResultsWithQuizIds(array $results): array
    {
        foreach ($results as &$result) {
            if (!\is_array($result)) {
                continue;
            }

            $data = $result['data'] ?? [];
            if (!\is_array($data)) {
                $data = [];
            }

            $kind = $data['kind'] ?? null;
            $tool = $data['tool'] ?? null;

            $isQuestion = ('question' === $kind) || ('quiz_question' === $tool);

            if (!$isQuestion) {
                $result['data'] = $data;

                continue;
            }

            // If already present, keep it (but still try to enrich quiz_title if missing).
            $questionId = isset($data['question_id']) && '' !== (string) $data['question_id']
                ? (int) $data['question_id']
                : null;

            if (null === $questionId) {
                $result['data'] = $data;

                continue;
            }

            /** @var CQuizRelQuestion|null $rel */
            $rel = $this->em
                ->getRepository(CQuizRelQuestion::class)
                ->findOneBy(['question' => $questionId])
            ;

            if (!$rel) {
                $result['data'] = $data;

                continue;
            }

            $quiz = $rel->getQuiz();
            if (!$quiz || null === $quiz->getIid()) {
                $result['data'] = $data;

                continue;
            }

            // Attach quiz id for linking.
            if (empty($data['quiz_id'])) {
                $data['quiz_id'] = (string) $quiz->getIid();
            }

            // Attach quiz title so we can display it instead of exposing full question text.
            if (empty($data['quiz_title']) && method_exists($quiz, 'getTitle')) {
                $quizTitle = (string) $quiz->getTitle();
                if ('' !== $quizTitle) {
                    $data['quiz_title'] = $quizTitle;
                }
            }

            $result['data'] = $data;
        }

        return $results;
    }

    /**
     * Adds course title/code/image to result data for nicer UI.
     *
     * @param array<int, array<string, mixed>> $results
     *
     * @return array<int, array<string, mixed>>
     */
    private function hydrateResultsWithCourseMeta(array $results): array
    {
        $courseIds = [];

        foreach ($results as $result) {
            $data = $result['data'] ?? [];
            if (!\is_array($data)) {
                continue;
            }
            if (!empty($data['course_id'])) {
                $courseIds[(int) $data['course_id']] = true;
            }
        }

        if (empty($courseIds)) {
            return $results;
        }

        foreach ($results as &$result) {
            if (!\is_array($result)) {
                continue;
            }

            $data = $result['data'] ?? [];
            if (!\is_array($data)) {
                $data = [];
            }

            $courseId = !empty($data['course_id']) ? (int) $data['course_id'] : null;
            if (!$courseId) {
                $result['data'] = $data;

                continue;
            }

            /** @var Course|null $course */
            $course = $this->em->find(Course::class, $courseId);
            if (!$course) {
                $result['data'] = $data;

                continue;
            }

            // These names might vary depending on your entity – keep safe with method_exists.
            $data['course_title'] = method_exists($course, 'getTitle') ? (string) $course->getTitle() : ('Course #'.$courseId);
            $data['course_code'] = method_exists($course, 'getCode') ? (string) $course->getCode() : (string) $courseId;

            $data['course_image_url'] = $this->resolveCourseImageUrl($course);

            $result['data'] = $data;
        }

        return $results;
    }

    private function resolveCourseImageUrl(Course $course): ?string
    {
        // The course must support illustrations (it normally does).
        if (!$course instanceof ResourceIllustrationInterface) {
            return null;
        }

        try {
            // Only show an image if the course really has an uploaded illustration.
            if (!$this->illustrationRepository->hasIllustration($course)) {
                return null;
            }

            // Use a glide filter intended for course pictures if available.
            // If you don't have a specific filter yet, you can pass ''.
            return $this->illustrationRepository->getIllustrationUrl(
                $course,
                '',
                96
            );
        } catch (Throwable $e) {
            error_log('[Search] resolveCourseImageUrl: failed: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Adds UI-friendly fields:
     * - file icon based on extension
     * - excerpt with highlighted terms
     * - is_accessible flag (session-aware)
     *
     * Important: for question results, do NOT expose full question text.
     *
     * @param array<int, array<string, mixed>> $results
     *
     * @return array<int, array<string, mixed>>
     */
    private function decorateResultsForUi(array $results, string $queryString): array
    {
        $terms = $this->extractQueryTerms($queryString);

        foreach ($results as &$result) {
            if (!\is_array($result)) {
                continue;
            }

            $data = $result['data'] ?? [];
            if (!\is_array($data)) {
                $data = [];
            }

            $kind = (string) ($data['kind'] ?? '');
            $tool = (string) ($data['tool'] ?? '');
            $isQuestion = ('question' === $kind) || ('quiz_question' === $tool);

            $title = isset($data['title']) ? $this->normalizeDisplayText((string) $data['title']) : '';
            $data['title'] = $title;
            $fullPath = isset($data['full_path']) ? (string) $data['full_path'] : '';
            $content = isset($data['content']) ? (string) $data['content'] : '';

            if (isset($data['title']) && \is_string($data['title'])) {
                $data['title'] = html_entity_decode($data['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }

            if (!empty($data['quiz_title']) && \is_string($data['quiz_title'])) {
                $data['quiz_title'] = html_entity_decode($data['quiz_title'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
            $title = isset($data['title']) ? (string) $data['title'] : $title;
            $ext = $this->guessFileExtension($fullPath, $title);
            $data['file_ext'] = $ext;
            $data['file_icon'] = $this->guessFileIconMdi($ext, (string) ($data['filetype'] ?? ''));

            // Resolve session context first (used for access checks + link building).
            $resolvedSid = $this->resolveSessionIdForResult($data);
            $data['resolved_session_id'] = $resolvedSid;

            // Build excerpt safely.
            if ($isQuestion) {
                // Use quiz title as the main visible title if available.
                if (!empty($data['quiz_title'])) {
                    $data['title'] = $this->normalizeDisplayText((string) $data['quiz_title']);
                }

                // Show only a tiny context around the match, never the full question content.
                $data['excerpt_html'] = $this->buildSafeQuestionExcerptHtml($content, $terms, 3);
            } else {
                $data['excerpt_html'] = $this->buildExcerptHtml($content, $terms, 220);
            }

            // Session-aware access flag.
            $data['is_accessible'] = $this->isResultAccessible($data, $resolvedSid);

            $result['data'] = $data;
        }

        return $results;
    }

    /**
     * Builds a safe excerpt for question-like content:
     * show only a few words around the first matched term, with <mark> highlight.
     * Never returns the full text.
     *
     * @param string   $content     Raw indexed content (question text may be here)
     * @param string[] $terms       Query terms
     * @param int      $radiusWords Number of words before/after the match
     */
    private function buildSafeQuestionExcerptHtml(string $content, array $terms, int $radiusWords = 3): string
    {
        $plain = html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plain = preg_replace('/\s+/u', ' ', $plain) ?? $plain;
        $plain = trim($plain);

        if ('' === $plain || empty($terms)) {
            return '';
        }

        $lower = mb_strtolower($plain, 'UTF-8');

        $matchPos = null;
        $matchedTerm = null;

        foreach ($terms as $t) {
            $t = trim((string) $t);
            if ('' === $t) {
                continue;
            }
            $p = mb_stripos($lower, mb_strtolower($t, 'UTF-8'), 0, 'UTF-8');
            if (false !== $p) {
                $matchPos = (int) $p;
                $matchedTerm = $t;

                break;
            }
        }

        if (null === $matchPos || null === $matchedTerm) {
            // If we cannot locate a match safely, do not reveal anything.
            return '';
        }

        // Split into words, find the word index that contains the match.
        $words = preg_split('/\s+/u', $plain) ?: [];
        if (empty($words)) {
            return '';
        }

        $charCount = 0;
        $matchWordIndex = 0;

        foreach ($words as $i => $w) {
            $wLen = mb_strlen($w, 'UTF-8');
            // Approximate char span with 1 whitespace between words.
            $spanStart = $charCount;
            $spanEnd = $charCount + $wLen;

            if ($matchPos >= $spanStart && $matchPos <= $spanEnd) {
                $matchWordIndex = (int) $i;

                break;
            }

            $charCount = $spanEnd + 1;
        }

        $start = max(0, $matchWordIndex - $radiusWords);
        $end = min(\count($words) - 1, $matchWordIndex + $radiusWords);

        $slice = \array_slice($words, $start, ($end - $start) + 1);
        $snippet = implode(' ', $slice);

        $escaped = htmlspecialchars($snippet, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        // Highlight terms
        foreach ($terms as $t) {
            $t = trim((string) $t);
            if ('' === $t) {
                continue;
            }
            $pattern = '/('.preg_quote(htmlspecialchars($t, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), '/').')/iu';
            $escaped = preg_replace($pattern, '<mark class="rounded bg-yellow-200">$1</mark>', $escaped) ?? $escaped;
        }

        if ($start > 0) {
            $escaped = '…'.$escaped;
        }
        if ($end < (\count($words) - 1)) {
            $escaped .= '…';
        }

        return $escaped;
    }

    /**
     * Normalize a UI text field coming from the index.
     * It may contain HTML entities (e.g. &eacute;) depending on how it was indexed.
     */
    private function normalizeDisplayText(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    /**
     * @param array<int, array<string, mixed>> $results
     */
    private function applyAccessPrefilter(array $results, bool $showUnlinked): array
    {
        if ($showUnlinked) {
            return $results;
        }

        $filtered = [];
        foreach ($results as $result) {
            $data = $result['data'] ?? [];
            if (!\is_array($data)) {
                continue;
            }

            if (($data['is_accessible'] ?? false) !== true) {
                continue;
            }

            $filtered[] = $result;
        }

        return $filtered;
    }

    private function isResultAccessible(array $data, int $resolvedSessionId = 0): bool
    {
        $user = $this->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        if ($this->isGranted('ROLE_ADMIN')) {
            return true;
        }

        if (!$user instanceof User) {
            return false;
        }

        $courseId = !empty($data['course_id']) ? (int) $data['course_id'] : 0;
        if ($courseId <= 0) {
            return false;
        }

        /** @var Course|null $course */
        $course = $this->em->find(Course::class, $courseId);
        if (!$course) {
            return false;
        }

        // 1) Validate course visibility/subscription (session-aware)
        if (!$this->canUserViewCourse($user, $course, $resolvedSessionId)) {
            return false;
        }

        // 2) If we have a specific node, validate node access too.
        if (!empty($data['resource_node_id'])) {
            $nodeId = (int) $data['resource_node_id'];

            /** @var ResourceNode|null $node */
            $node = $this->em->find(ResourceNode::class, $nodeId);
            if (!$node) {
                return false;
            }

            return $this->isGranted(ResourceNodeVoter::VIEW, $node);
        }

        return true;
    }

    private function canUserViewCourse(User $user, Course $course, int $sessionId = 0): bool
    {
        // Hidden course => only admins (already handled above)
        if ($course->isHidden()) {
            return false;
        }

        /** @var Session|null $session */
        $session = null;
        if ($sessionId > 0) {
            $session = $this->em->find(Session::class, $sessionId);
        }

        // Public course => visible (but may be locked by prerequisites for students)
        if ($course->isPublic()) {
            $this->applyCourseContextRoles($user, $course, $session);

            if ($this->isStudentInContext($user, $course, $session)) {
                if ($this->isCourseLockedForUser($user, $course, $session?->getId() ?? 0)) {
                    return false;
                }
            }

            return true;
        }

        // Open platform => any logged-in user
        if (Course::OPEN_PLATFORM === $course->getVisibility()) {
            $this->applyCourseContextRoles($user, $course, $session);

            if ($this->isStudentInContext($user, $course, $session)) {
                if ($this->isCourseLockedForUser($user, $course, $session?->getId() ?? 0)) {
                    return false;
                }
            }

            return true;
        }

        // Session-specific subscription
        if ($session) {
            $userIsGeneralCoach = $session->hasUserAsGeneralCoach($user);
            $userIsCourseCoach = $session->hasCourseCoachInCourse($user, $course);
            $userIsStudent = $session->hasUserInCourse($user, $course, Session::STUDENT);

            if ($userIsGeneralCoach || $userIsCourseCoach) {
                $user->addRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_SESSION_TEACHER);

                return true;
            }

            if ($userIsStudent) {
                $user->addRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_SESSION_STUDENT);

                if ($this->isCourseLockedForUser($user, $course, $session->getId())) {
                    return false;
                }

                return true;
            }

            return false;
        }

        // Registered-only courses => must be subscribed directly
        if ($course->hasSubscriptionByUser($user)) {
            $this->applyCourseContextRoles($user, $course, null);

            if ($this->isCourseLockedForUser($user, $course, 0)) {
                return false;
            }

            return true;
        }

        return false;
    }

    private function applyCourseContextRoles(User $user, Course $course, ?Session $session): void
    {
        // Mimic CourseVoter behavior: add dynamic roles for the current course context.
        $user->addRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_STUDENT);

        if ($course->hasUserAsTeacher($user)) {
            $user->addRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_TEACHER);
        }

        if ($session) {
            if ($session->hasUserAsGeneralCoach($user) || $session->hasCourseCoachInCourse($user, $course)) {
                $user->addRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_SESSION_TEACHER);
            }

            if ($session->hasUserInCourse($user, $course, Session::STUDENT)) {
                $user->addRole(ResourceNodeVoter::ROLE_CURRENT_COURSE_SESSION_STUDENT);
            }
        }
    }

    /**
     * Checks whether the given course is locked for the user due to unmet prerequisites.
     */
    private function isCourseLockedForUser(User $user, Course $course, int $sessionId = 0): bool
    {
        $sequenceRepo = $this->em->getRepository(SequenceResource::class);

        $sequences = $sequenceRepo->getRequirements(
            $course->getId(),
            SequenceResource::COURSE_TYPE
        );

        if (empty($sequences)) {
            return false;
        }

        $statusList = $sequenceRepo->checkRequirementsForUser(
            $sequences,
            SequenceResource::COURSE_TYPE,
            $user->getId()
        );

        return !$sequenceRepo->checkSequenceAreCompleted($statusList);
    }

    private function isStudentInContext(User $user, Course $course, ?Session $session): bool
    {
        if ($session) {
            return $session->hasUserInCourse($user, $course, Session::STUDENT);
        }

        return $course->hasUserAsStudent($user);
    }

    /**
     * Extract query terms for snippet highlighting.
     *
     * @return string[] small list of tokens
     */
    private function extractQueryTerms(string $queryString): array
    {
        $q = trim($queryString);
        if ('' === $q) {
            return [];
        }

        // Remove field prefixes like t:, d:, k:, etc.
        $q = preg_replace('/\b[a-zA-Z]{1,3}:/', ' ', $q) ?? $q;

        // Remove operators and punctuation that usually appear in Xapian queries.
        $q = str_replace(['"', "'", '(', ')', '[', ']', '{', '}', '+', '-', '*', '~', '^', ':'], ' ', $q);

        $parts = preg_split('/\s+/', $q) ?: [];

        $stop = [
            'and', 'or', 'not', 'near', 'adj',
        ];

        $out = [];
        foreach ($parts as $p) {
            $p = mb_strtolower(trim($p), 'UTF-8');
            if ('' === $p) {
                continue;
            }
            if (\in_array($p, $stop, true)) {
                continue;
            }
            if (mb_strlen($p, 'UTF-8') < 3) {
                continue;
            }
            $out[$p] = true;
            if (\count($out) >= 6) {
                break;
            }
        }

        return array_keys($out);
    }

    private function guessFileExtension(string $fullPath, string $title): string
    {
        $candidate = '' !== $fullPath ? $fullPath : $title;
        $ext = strtolower((string) pathinfo($candidate, PATHINFO_EXTENSION));

        return $ext ?: '';
    }

    private function guessFileIconMdi(string $ext, string $filetype): string
    {
        $ext = strtolower(trim($ext));
        $filetype = strtolower(trim($filetype));

        // Generic kinds
        if ('folder' === $filetype) {
            return 'mdi-folder';
        }

        // Office / docs
        return match ($ext) {
            'pdf' => 'mdi-file-pdf-box',
            'doc', 'docx' => 'mdi-file-word-box',
            'xls', 'xlsx', 'ods' => 'mdi-file-excel-box',
            'ppt', 'pptx', 'odp' => 'mdi-file-powerpoint-box',
            'txt', 'md', 'log', 'csv' => 'mdi-file-document-outline',
            'html', 'htm' => 'mdi-language-html5',
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg' => 'mdi-file-image',
            'zip', 'rar', '7z', 'tar', 'gz' => 'mdi-folder-zip-outline',
            default => 'mdi-file-outline',
        };
    }

    /**
     * Build a small excerpt around the first occurrence of a term.
     * Returns safe HTML with <mark> highlights.
     */
    private function buildExcerptHtml(string $content, array $terms, int $maxLen): string
    {
        $plain = html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plain = preg_replace('/\s+/u', ' ', $plain) ?? $plain;
        $plain = trim($plain);

        if ('' === $plain) {
            return '';
        }

        $pos = null;

        foreach ($terms as $t) {
            $t = trim((string) $t);
            if ('' === $t) {
                continue;
            }

            $p = mb_stripos($plain, $t, 0, 'UTF-8');
            if (false !== $p) {
                $pos = (int) $p;

                break;
            }
        }

        if (null === $pos) {
            $snippet = mb_substr($plain, 0, $maxLen, 'UTF-8');
            $escaped = htmlspecialchars($snippet, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            return $escaped.(mb_strlen($plain, 'UTF-8') > $maxLen ? '…' : '');
        }

        $radius = (int) floor($maxLen / 2);
        $start = max(0, $pos - $radius);

        $snippet = mb_substr($plain, $start, $maxLen, 'UTF-8');
        $snippet = trim($snippet);

        $escaped = htmlspecialchars($snippet, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        // Highlight all terms in escaped string.
        foreach ($terms as $t) {
            $t = trim((string) $t);
            if ('' === $t) {
                continue;
            }
            $pattern = '/('.preg_quote(htmlspecialchars($t, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), '/').')/iu';
            $escaped = preg_replace($pattern, '<mark class="rounded bg-yellow-200">$1</mark>', $escaped) ?? $escaped;
        }

        if ($start > 0) {
            $escaped = '…'.$escaped;
        }
        if (($start + $maxLen) < mb_strlen($plain, 'UTF-8')) {
            $escaped .= '…';
        }

        return $escaped;
    }

    private function resolveRequestLanguageIso(Request $request): ?string
    {
        $lang = trim((string) $request->query->get('lang', ''));
        if ('' !== $lang) {
            return $lang;
        }

        if (\function_exists('api_get_language_isocode')) {
            $iso = (string) api_get_language_isocode();
            $iso = trim($iso);

            if ('' !== $iso) {
                return $iso;
            }
        }

        $locale = trim((string) $request->getLocale());
        if ('' !== $locale) {
            return $locale;
        }

        return null;
    }

    private function resolveSessionIdForResult(array $data): int
    {
        $sid = !empty($data['session_id']) ? (int) $data['session_id'] : 0;
        if ($sid > 0) {
            return $sid;
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            return 0;
        }

        $courseId = !empty($data['course_id']) ? (int) $data['course_id'] : 0;
        if ($courseId <= 0) {
            return 0;
        }

        return $this->findAnySessionIdForUserAndCourse($user->getId(), $courseId);
    }

    private function findAnySessionIdForUserAndCourse(int $userId, int $courseId): int
    {
        try {
            $conn = $this->em->getConnection();

            // Table name in Chamilo is usually this:
            $table = 'session_rel_course_rel_user';

            // Detect column names safely.
            $sm = method_exists($conn, 'createSchemaManager')
                ? $conn->createSchemaManager()
                : $conn->getSchemaManager();

            $columns = array_map(
                static fn ($c) => strtolower($c->getName()),
                $sm->listTableColumns($table)
            );

            $userCol = \in_array('user_id', $columns, true) ? 'user_id' : null;
            $sessionCol = \in_array('session_id', $columns, true) ? 'session_id' : null;

            // Course column can be c_id or course_id depending on schema.
            $courseCol = null;
            if (\in_array('c_id', $columns, true)) {
                $courseCol = 'c_id';
            } elseif (\in_array('course_id', $columns, true)) {
                $courseCol = 'course_id';
            }

            if (!$userCol || !$sessionCol || !$courseCol) {
                // If schema is unexpected, fail closed.
                return 0;
            }

            $sql = "SELECT {$sessionCol} FROM {$table}
                WHERE {$userCol} = :uid AND {$courseCol} = :cid
                ORDER BY {$sessionCol} DESC
                LIMIT 1";

            $sid = (int) $conn->fetchOne($sql, ['uid' => $userId, 'cid' => $courseId]);

            return $sid > 0 ? $sid : 0;
        } catch (Throwable $e) {
            error_log('[Search] Failed to resolve session id: '.$e->getMessage());

            return 0;
        }
    }
}
