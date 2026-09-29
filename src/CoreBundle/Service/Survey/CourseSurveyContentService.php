<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Survey;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Repository\LanguageRepository;
use Chamilo\CoreBundle\Service\Document\CourseDocumentContentService;
use Chamilo\CoreBundle\Service\Html\TranslateHtmlLanguageService;
use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Entity\CSurveyQuestion;
use Chamilo\CourseBundle\Entity\CSurveyQuestionOption;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use InvalidArgumentException;

use const DATE_ATOM;

/**
 * Read + description-only write helpers for base-course surveys used by MCP.
 * Survey / question / option titles-as-identifiers stay English-facing where
 * they exist; only the HTML description bodies are rewritten:
 * - survey intro (CSurvey::$intro) — title is never changed
 * - survey question text (CSurveyQuestion::$surveyQuestion)
 * - survey answer/option text (CSurveyQuestionOption::$optionText).
 */
final readonly class CourseSurveyContentService
{
    private const int MAX_HTML_LENGTH = 2_000_000;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private CourseDocumentContentService $documentContentService,
        private TranslateHtmlLanguageService $translateHtmlLanguageService,
        private LanguageRepository $languageRepository,
    ) {}

    /**
     * @return list<CSurvey>
     */
    public function listSurveys(Course $course): array
    {
        /** @var list<CSurvey> $result */
        return $this->baseSurveyQueryBuilder($course)
            ->addOrderBy('survey.title', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function resolveSurvey(Course $course, ?int $surveyId, ?string $surveyTitle): CSurvey
    {
        $surveyId = (null !== $surveyId && $surveyId > 0) ? $surveyId : null;
        $surveyTitle = null !== $surveyTitle ? trim($surveyTitle) : '';
        if (null === $surveyId && '' === $surveyTitle) {
            throw new InvalidArgumentException('Provide either surveyId or surveyTitle.');
        }

        $queryBuilder = $this->baseSurveyQueryBuilder($course);
        if (null !== $surveyId) {
            $queryBuilder
                ->andWhere('survey.iid = :surveyId')
                ->setParameter('surveyId', $surveyId, Types::INTEGER)
            ;
        } else {
            $queryBuilder
                ->andWhere('survey.title = :surveyTitle')
                ->setParameter('surveyTitle', $surveyTitle, Types::STRING)
            ;
        }

        /** @var list<CSurvey> $matches */
        $matches = $queryBuilder->getQuery()->getResult();
        if ([] === $matches) {
            throw new InvalidArgumentException('The survey was not found in this course.');
        }
        if (\count($matches) > 1) {
            throw new InvalidArgumentException('More than one survey has this title. Provide surveyId to disambiguate.');
        }

        return $matches[0];
    }

    /**
     * @return list<CSurveyQuestion>
     */
    public function listQuestions(CSurvey $survey): array
    {
        /** @var list<CSurveyQuestion> $result */
        return $this->entityManager->createQueryBuilder()
            ->select('question')
            ->from(CSurveyQuestion::class, 'question')
            ->andWhere('IDENTITY(question.survey) = :surveyId')
            ->setParameter('surveyId', (int) $survey->getIid(), Types::INTEGER)
            ->orderBy('question.sort', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return array{question: CSurveyQuestion, position: int}
     */
    public function resolveQuestionWithPosition(CSurvey $survey, int $questionId): array
    {
        if ($questionId <= 0) {
            throw new InvalidArgumentException('The question ID must be a positive integer.');
        }

        foreach ($this->listQuestions($survey) as $index => $question) {
            if ($questionId === (int) $question->getIid()) {
                return ['question' => $question, 'position' => $index + 1];
            }
        }

        throw new InvalidArgumentException('The question was not found in this survey.');
    }

    /**
     * @return list<CSurveyQuestionOption>
     */
    public function listOptions(CSurveyQuestion $question): array
    {
        /** @var list<CSurveyQuestionOption> $result */
        return $this->entityManager->createQueryBuilder()
            ->select('option')
            ->from(CSurveyQuestionOption::class, 'option')
            ->andWhere('IDENTITY(option.question) = :questionId')
            ->setParameter('questionId', (int) $question->getIid(), Types::INTEGER)
            ->orderBy('option.sort', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function resolveOption(CSurveyQuestion $question, int $answerId): CSurveyQuestionOption
    {
        if ($answerId <= 0) {
            throw new InvalidArgumentException('The answer ID must be a positive integer.');
        }

        foreach ($this->listOptions($question) as $option) {
            if ($answerId === (int) $option->getIid()) {
                return $option;
            }
        }

        throw new InvalidArgumentException('The survey answer/option was not found for this question.');
    }

    /**
     * @return array<string, mixed>
     */
    public function normalizeSurvey(
        CSurvey $survey,
        Course $course,
        string $mode = TranslateHtmlLanguageService::READ_MODE_FULL,
        ?string $sourceLanguage = null,
    ): array {
        $mode = $this->translateHtmlLanguageService->assertReadMode($mode);
        $sourceLanguage = $this->resolveSourceLanguageIsoCode($course, $sourceLanguage, $survey->getLang());
        $resourceLink = $survey->getResourceNode()?->getResourceLinkByContext($course, null, null);

        return [
            'survey_id' => (int) $survey->getIid(),
            'title' => $survey->getTitle(),
            'code' => $survey->getCode(),
            'language' => $survey->getLang(),
            'anonymous' => '1' === (string) $survey->getAnonymous(),
            'question_count' => \count($this->listQuestions($survey)),
            'visibility' => $resourceLink?->getVisibility(),
            'published' => $resourceLink instanceof ResourceLink
                && ResourceLink::VISIBILITY_PUBLISHED === $resourceLink->getVisibility(),
            'available_from' => $survey->getAvailFrom()?->format(DATE_ATOM),
            'available_until' => $survey->getAvailTill()?->format(DATE_ATOM),
            'mode' => $mode,
            ...$this->translateHtmlLanguageService->projectHtmlField(
                (string) $survey->getIntro(),
                $mode,
                $sourceLanguage,
                'description',
            ),
            ...$this->translateHtmlLanguageService->projectHtmlField(
                (string) $survey->getSubtitle(),
                $mode,
                $sourceLanguage,
                'subtitle',
                'subtitle_',
            ),
            ...$this->translateHtmlLanguageService->projectHtmlField(
                (string) $survey->getSurveythanks(),
                $mode,
                $sourceLanguage,
                'thanks',
                'thanks_',
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function normalizeQuestion(
        CSurveyQuestion $question,
        int $position,
        string $mode = TranslateHtmlLanguageService::READ_MODE_FULL,
        ?string $sourceLanguage = null,
        ?Course $course = null,
    ): array {
        $mode = $this->translateHtmlLanguageService->assertReadMode($mode);
        $sourceLanguage = $this->resolveSourceLanguageIsoCode(
            $course,
            $sourceLanguage,
            $question->getSurvey()?->getLang(),
        );
        $options = array_map(
            fn (CSurveyQuestionOption $option): array => $this->normalizeOption($option, $mode, $sourceLanguage),
            $this->listOptions($question),
        );

        return [
            'question_id' => (int) $question->getIid(),
            'position' => $position,
            'comment' => (string) $question->getSurveyQuestionComment(),
            'type' => $question->getType(),
            'required' => $question->isMandatory(),
            'answer_count' => \count($options),
            'answers' => $options,
            'mode' => $mode,
            ...$this->translateHtmlLanguageService->projectHtmlField(
                (string) $question->getSurveyQuestion(),
                $mode,
                $sourceLanguage,
                'description',
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function normalizeOption(
        CSurveyQuestionOption $option,
        string $mode = TranslateHtmlLanguageService::READ_MODE_FULL,
        ?string $sourceLanguage = null,
    ): array {
        $mode = $this->translateHtmlLanguageService->assertReadMode($mode);
        $sourceLanguage = $this->translateHtmlLanguageService->normalizeLanguageCode($sourceLanguage ?: 'en');

        return [
            'answer_id' => (int) $option->getIid(),
            'position' => $option->getSort(),
            'value' => $option->getValue(),
            'mode' => $mode,
            ...$this->translateHtmlLanguageService->projectHtmlField(
                (string) $option->getOptionText(),
                $mode,
                $sourceLanguage,
                'description',
            ),
        ];
    }

    /**
     * @return array{updated: true, survey: array<string, mixed>}
     */
    public function editSurveyDescription(Course $course, CSurvey $survey, string $description): array
    {
        $html = $this->sanitizeHtmlDescription($description, allowEmpty: true);
        $survey->setIntro($html);
        $this->entityManager->persist($survey);
        $this->entityManager->flush();

        return [
            'updated' => true,
            'survey' => $this->normalizeSurvey($survey, $course),
        ];
    }

    /**
     * @return array{updated: true, question: array<string, mixed>}
     */
    public function editQuestionDescription(
        Course $course,
        CSurvey $survey,
        int $questionId,
        string $description,
    ): array {
        $resolved = $this->resolveQuestionWithPosition($survey, $questionId);
        $question = $resolved['question'];
        $html = $this->sanitizeHtmlDescription($description, allowEmpty: false);

        $question->setSurveyQuestion($html);
        $this->entityManager->persist($question);
        $this->entityManager->flush();

        return [
            'updated' => true,
            'question' => $this->normalizeQuestion($question, $resolved['position'], TranslateHtmlLanguageService::READ_MODE_FULL, null, $course),
        ];
    }

    /**
     * @return array{updated: true, answer: array<string, mixed>, question: array<string, mixed>}
     */
    public function editAnswerDescription(
        Course $course,
        CSurvey $survey,
        int $questionId,
        int $answerId,
        string $description,
    ): array {
        $resolved = $this->resolveQuestionWithPosition($survey, $questionId);
        $question = $resolved['question'];
        $option = $this->resolveOption($question, $answerId);
        $html = $this->sanitizeHtmlDescription($description, allowEmpty: false);

        $option->setOptionText($html);
        $this->entityManager->persist($option);
        $this->entityManager->flush();

        $sourceLanguage = $this->resolveSourceLanguageIsoCode($course, null, $survey->getLang());

        return [
            'updated' => true,
            'question' => $this->normalizeQuestion($question, $resolved['position'], TranslateHtmlLanguageService::READ_MODE_FULL, $sourceLanguage, $course),
            'answer' => $this->normalizeOption($option, TranslateHtmlLanguageService::READ_MODE_FULL, $sourceLanguage),
        ];
    }

    /**
     * @return array{updated: true, action: 'created'|'replaced'}&array<string, mixed>
     */
    public function upsertSurveyDescriptionLanguage(
        Course $course,
        CSurvey $survey,
        string $language,
        string $content,
        string $mode = TranslateHtmlLanguageService::MODE_UPSERT,
        ?string $sourceLanguage = null,
        ?string $ifMatchSha256 = null,
    ): array {
        $languageIso = $this->resolveRequiredLanguageIsoCode($language);
        $sourceLanguageIso = $this->resolveSourceLanguageIsoCode($course, $sourceLanguage, $survey->getLang());

        $result = $this->translateHtmlLanguageService->upsertLanguageSanitized(
            (string) $survey->getIntro(),
            $languageIso,
            $content,
            $mode,
            $sourceLanguageIso,
            $ifMatchSha256,
            fn (string $html): string => $this->sanitizeHtmlDescription($html, allowEmpty: true),
        );

        $survey->setIntro($result['html']);
        $this->entityManager->persist($survey);
        $this->entityManager->flush();

        return [
            'updated' => true,
            'survey_id' => (int) $survey->getIid(),
            'title' => $survey->getTitle(),
            'action' => $result['action'],
            'language' => $result['language'],
            'present_languages' => $result['present_languages'],
            'content_sha256' => $result['content_sha256'],
            'chars' => $result['chars'],
            'words' => $result['words'],
            'has_markers' => $result['has_markers'],
            'per_language' => $result['per_language'],
        ];
    }

    /**
     * Upsert one language variant of a survey's subtitle or thanks-message text.
     * Title is intentionally excluded: it doubles as the exact-match lookup key
     * used by resolveSurvey($course, $surveyId, $surveyTitle), so it stays
     * English-facing like the survey/question/option titles-as-identifiers.
     *
     * @return array{updated: true, action: 'created'|'replaced'}&array<string, mixed>
     */
    public function upsertSurveyTextLanguage(
        Course $course,
        CSurvey $survey,
        string $field,
        string $language,
        string $content,
        string $mode = TranslateHtmlLanguageService::MODE_UPSERT,
        ?string $sourceLanguage = null,
        ?string $ifMatchSha256 = null,
    ): array {
        $accessor = $this->resolveSurveyTextFieldAccessor($field);
        $languageIso = $this->resolveRequiredLanguageIsoCode($language);
        $sourceLanguageIso = $this->resolveSourceLanguageIsoCode($course, $sourceLanguage, $survey->getLang());

        $result = $this->translateHtmlLanguageService->upsertLanguageSanitized(
            $accessor['get']($survey),
            $languageIso,
            $content,
            $mode,
            $sourceLanguageIso,
            $ifMatchSha256,
            fn (string $html): string => $this->sanitizeHtmlDescription($html, allowEmpty: true),
        );

        $accessor['set']($survey, $result['html']);
        $this->entityManager->persist($survey);
        $this->entityManager->flush();

        return [
            'updated' => true,
            'survey_id' => (int) $survey->getIid(),
            'title' => $survey->getTitle(),
            'field' => $field,
            'action' => $result['action'],
            'language' => $result['language'],
            'present_languages' => $result['present_languages'],
            'content_sha256' => $result['content_sha256'],
            'chars' => $result['chars'],
            'words' => $result['words'],
            'has_markers' => $result['has_markers'],
            'per_language' => $result['per_language'],
        ];
    }

    /**
     * @return array{get: callable(CSurvey): string, set: callable(CSurvey, string): void}
     */
    private function resolveSurveyTextFieldAccessor(string $field): array
    {
        return match ($field) {
            'subtitle' => [
                'get' => static fn (CSurvey $survey): string => (string) $survey->getSubtitle(),
                'set' => static function (CSurvey $survey, string $html): void {
                    $survey->setSubtitle($html);
                },
            ],
            'thanks' => [
                'get' => static fn (CSurvey $survey): string => (string) $survey->getSurveythanks(),
                'set' => static function (CSurvey $survey, string $html): void {
                    $survey->setSurveythanks($html);
                },
            ],
            default => throw new InvalidArgumentException(\sprintf('Unsupported survey text field "%s". Use "subtitle" or "thanks" (title is not translatable — it is used as a lookup key).', $field)),
        };
    }

    /**
     * @return array{updated: true, action: 'created'|'replaced'}&array<string, mixed>
     */
    public function upsertQuestionDescriptionLanguage(
        Course $course,
        CSurvey $survey,
        int $questionId,
        string $language,
        string $content,
        string $mode = TranslateHtmlLanguageService::MODE_UPSERT,
        ?string $sourceLanguage = null,
        ?string $ifMatchSha256 = null,
    ): array {
        $resolved = $this->resolveQuestionWithPosition($survey, $questionId);
        $question = $resolved['question'];
        $languageIso = $this->resolveRequiredLanguageIsoCode($language);
        $sourceLanguageIso = $this->resolveSourceLanguageIsoCode($course, $sourceLanguage, $survey->getLang());

        $result = $this->translateHtmlLanguageService->upsertLanguageSanitized(
            (string) $question->getSurveyQuestion(),
            $languageIso,
            $content,
            $mode,
            $sourceLanguageIso,
            $ifMatchSha256,
            fn (string $html): string => $this->sanitizeHtmlDescription($html, allowEmpty: false),
        );

        $question->setSurveyQuestion($result['html']);
        $this->entityManager->persist($question);
        $this->entityManager->flush();

        return [
            'updated' => true,
            'survey_id' => (int) $survey->getIid(),
            'question_id' => (int) $question->getIid(),
            'position' => $resolved['position'],
            'action' => $result['action'],
            'language' => $result['language'],
            'present_languages' => $result['present_languages'],
            'content_sha256' => $result['content_sha256'],
            'chars' => $result['chars'],
            'words' => $result['words'],
            'has_markers' => $result['has_markers'],
            'per_language' => $result['per_language'],
        ];
    }

    /**
     * @return array{updated: true, action: 'created'|'replaced'}&array<string, mixed>
     */
    public function upsertAnswerDescriptionLanguage(
        Course $course,
        CSurvey $survey,
        int $questionId,
        int $answerId,
        string $language,
        string $content,
        string $mode = TranslateHtmlLanguageService::MODE_UPSERT,
        ?string $sourceLanguage = null,
        ?string $ifMatchSha256 = null,
    ): array {
        $resolved = $this->resolveQuestionWithPosition($survey, $questionId);
        $question = $resolved['question'];
        $option = $this->resolveOption($question, $answerId);
        $languageIso = $this->resolveRequiredLanguageIsoCode($language);
        $sourceLanguageIso = $this->resolveSourceLanguageIsoCode($course, $sourceLanguage, $survey->getLang());

        $result = $this->translateHtmlLanguageService->upsertLanguageSanitized(
            (string) $option->getOptionText(),
            $languageIso,
            $content,
            $mode,
            $sourceLanguageIso,
            $ifMatchSha256,
            fn (string $html): string => $this->sanitizeHtmlDescription($html, allowEmpty: false),
        );

        $option->setOptionText($result['html']);
        $this->entityManager->persist($option);
        $this->entityManager->flush();

        return [
            'updated' => true,
            'survey_id' => (int) $survey->getIid(),
            'question_id' => (int) $question->getIid(),
            'answer_id' => (int) $option->getIid(),
            'action' => $result['action'],
            'language' => $result['language'],
            'present_languages' => $result['present_languages'],
            'content_sha256' => $result['content_sha256'],
            'chars' => $result['chars'],
            'words' => $result['words'],
            'has_markers' => $result['has_markers'],
            'per_language' => $result['per_language'],
        ];
    }

    private function resolveRequiredLanguageIsoCode(string $language): string
    {
        $language = trim($language);
        if ('' === $language) {
            throw new InvalidArgumentException('The language is required.');
        }

        $resolved = $this->languageRepository->findOneAvailableByTitleOrCode($language);
        if (!$resolved instanceof Language) {
            throw new InvalidArgumentException(\sprintf('Unknown language "%s". Provide a language name (e.g. "Spanish") or an existing Chamilo language code (e.g. "es").', $language));
        }

        return $this->translateHtmlLanguageService->normalizeLanguageCode((string) $resolved->getIsocode());
    }

    private function resolveSourceLanguageIsoCode(
        ?Course $course,
        ?string $sourceLanguage,
        ?string $fallbackIso = null,
    ): string {
        if (null !== $sourceLanguage && '' !== trim($sourceLanguage)) {
            return $this->resolveRequiredLanguageIsoCode($sourceLanguage);
        }

        if (null !== $fallbackIso && '' !== trim($fallbackIso)) {
            $fromFallback = $this->languageRepository->findOneAvailableByTitleOrCode($fallbackIso);
            if ($fromFallback instanceof Language) {
                return $this->translateHtmlLanguageService->normalizeLanguageCode((string) $fromFallback->getIsocode());
            }

            return $this->translateHtmlLanguageService->normalizeLanguageCode($fallbackIso);
        }

        if ($course instanceof Course) {
            $courseLanguage = trim((string) $course->getCourseLanguage());
            if ('' !== $courseLanguage) {
                $fromCourse = $this->languageRepository->findOneAvailableByTitleOrCode($courseLanguage);
                if ($fromCourse instanceof Language) {
                    return $this->translateHtmlLanguageService->normalizeLanguageCode((string) $fromCourse->getIsocode());
                }

                return $this->translateHtmlLanguageService->normalizeLanguageCode($courseLanguage);
            }
        }

        $platformDefault = $this->languageRepository->getPlatformDefaultIso();

        return $this->translateHtmlLanguageService->normalizeLanguageCode($platformDefault ?: 'en');
    }

    private function baseSurveyQueryBuilder(Course $course): QueryBuilder
    {
        return $this->entityManager->createQueryBuilder()
            ->select('survey')
            ->from(CSurvey::class, 'survey')
            ->innerJoin('survey.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'resourceLink')
            ->andWhere('IDENTITY(resourceLink.course) = :courseId')
            ->andWhere('resourceLink.session IS NULL')
            ->andWhere('resourceLink.group IS NULL')
            ->andWhere('resourceLink.userGroup IS NULL')
            ->andWhere('resourceLink.user IS NULL')
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
        ;
    }

    private function sanitizeHtmlDescription(string $description, bool $allowEmpty): string
    {
        $description = trim($description);
        if (mb_strlen($description) > self::MAX_HTML_LENGTH) {
            throw new InvalidArgumentException('The HTML description is too large.');
        }

        if ('' === $description) {
            if ($allowEmpty) {
                return '';
            }

            throw new InvalidArgumentException('The HTML description is required.');
        }

        $sanitized = $this->documentContentService->sanitizeHtml($description);
        if ('' === trim(strip_tags($sanitized))) {
            if ($allowEmpty && '' === trim(strip_tags($description))) {
                return '';
            }

            throw new InvalidArgumentException('The HTML description is empty after sanitization.');
        }

        return $sanitized;
    }
}
