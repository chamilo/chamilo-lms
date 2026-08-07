<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Survey;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Service\Document\CourseDocumentContentService;
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
    public function normalizeSurvey(CSurvey $survey, Course $course): array
    {
        $resourceLink = $survey->getResourceNode()?->getResourceLinkByContext($course, null, null);

        return [
            'survey_id' => (int) $survey->getIid(),
            'title' => $survey->getTitle(),
            'code' => $survey->getCode(),
            'description' => (string) $survey->getIntro(),
            'thanks' => (string) $survey->getSurveythanks(),
            'language' => $survey->getLang(),
            'anonymous' => '1' === (string) $survey->getAnonymous(),
            'question_count' => \count($this->listQuestions($survey)),
            'visibility' => $resourceLink?->getVisibility(),
            'published' => $resourceLink instanceof ResourceLink
                && ResourceLink::VISIBILITY_PUBLISHED === $resourceLink->getVisibility(),
            'available_from' => $survey->getAvailFrom()?->format(DATE_ATOM),
            'available_until' => $survey->getAvailTill()?->format(DATE_ATOM),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function normalizeQuestion(CSurveyQuestion $question, int $position): array
    {
        $options = array_map(
            fn (CSurveyQuestionOption $option): array => $this->normalizeOption($option),
            $this->listOptions($question),
        );

        return [
            'question_id' => (int) $question->getIid(),
            'position' => $position,
            'description' => $question->getSurveyQuestion(),
            'comment' => (string) $question->getSurveyQuestionComment(),
            'type' => $question->getType(),
            'required' => $question->isMandatory(),
            'answer_count' => \count($options),
            'answers' => $options,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function normalizeOption(CSurveyQuestionOption $option): array
    {
        return [
            'answer_id' => (int) $option->getIid(),
            'position' => $option->getSort(),
            'description' => $option->getOptionText(),
            'value' => $option->getValue(),
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
            'question' => $this->normalizeQuestion($question, $resolved['position']),
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

        return [
            'updated' => true,
            'question' => $this->normalizeQuestion($question, $resolved['position']),
            'answer' => $this->normalizeOption($option),
        ];
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
