<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Admin;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizQuestionCategory;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

final readonly class AdminQuestionBankManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getData(Request $request, bool $exportAll = false): array
    {
        $filters = $this->getFilters($request);
        $searched = $exportAll || 1 === (int) $filters['formSent'];
        $page = max(1, (int) $filters['page']);
        $itemsPerPage = min(100, max(5, (int) $filters['itemsPerPage']));
        $totalItems = 0;
        $items = [];

        if ($searched) {
            $queryBuilder = $this->createQuestionQueryBuilder($filters);

            $countQueryBuilder = clone $queryBuilder;
            $totalItems = (int) $countQueryBuilder
                ->select('COUNT(DISTINCT question.iid)')
                ->getQuery()
                ->getSingleScalarResult()
            ;

            if ($totalItems > 0) {
                $idQueryBuilder = clone $queryBuilder;
                $idQueryBuilder
                    ->select('DISTINCT question.iid AS id')
                    ->orderBy('question.iid', 'DESC')
                ;

                if (!$exportAll) {
                    $idQueryBuilder
                        ->setFirstResult(($page - 1) * $itemsPerPage)
                        ->setMaxResults($itemsPerPage)
                    ;
                }

                $questionIds = array_map(
                    static fn (array $row): int => (int) $row['id'],
                    $idQueryBuilder->getQuery()->getArrayResult()
                );

                $items = $this->getQuestionItems($questionIds);
            }
        }

        return [
            'items' => $items,
            'courseOptions' => $this->getCourseOptions(),
            'difficultyOptions' => $this->getDifficultyOptions(),
            'questionTypeOptions' => $this->getQuestionTypeOptions(),
            'extraFields' => $this->getExtraFieldDefinitions(),
            'filters' => $filters,
            'page' => $page,
            'itemsPerPage' => $itemsPerPage,
            'totalItems' => $totalItems,
            'searched' => $searched,
        ];
    }

    public function deleteQuestion(int $questionId): void
    {
        if ($questionId <= 0) {
            throw new NotFoundHttpException('The requested question was not found.');
        }

        $question = $this->entityManager->getRepository(CQuizQuestion::class)->find($questionId);
        if (!$question instanceof CQuizQuestion) {
            throw new NotFoundHttpException('The requested question was not found.');
        }

        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            /** @var array<int, array<int, int>> $ordersByQuiz */
            $ordersByQuiz = [];
            $relations = $this->entityManager->getRepository(CQuizRelQuestion::class)->findBy(['question' => $question]);

            foreach ($relations as $relation) {
                if (!$relation instanceof CQuizRelQuestion) {
                    continue;
                }

                $quizId = (int) ($relation->getQuiz()->getIid() ?? 0);
                $order = (int) $relation->getQuestionOrder();
                if ($quizId > 0 && $order > 0) {
                    $ordersByQuiz[$quizId][] = $order;
                }

                $this->entityManager->remove($relation);
            }
            $this->entityManager->flush();

            foreach ($ordersByQuiz as $quizId => $orders) {
                rsort($orders);
                foreach ($orders as $order) {
                    $this->entityManager->createQueryBuilder()
                        ->update(CQuizRelQuestion::class, 'relation')
                        ->set('relation.questionOrder', 'relation.questionOrder - 1')
                        ->andWhere('IDENTITY(relation.quiz) = :quizId')
                        ->andWhere('relation.questionOrder > :questionOrder')
                        ->setParameter('quizId', $quizId, Types::INTEGER)
                        ->setParameter('questionOrder', $order, Types::INTEGER)
                        ->getQuery()
                        ->execute()
                    ;
                }
            }

            foreach ($question->getAnswers() as $answer) {
                $this->entityManager->remove($answer);
            }
            foreach ($question->getOptions() as $option) {
                $this->entityManager->remove($option);
            }
            foreach ($question->getCategories() as $category) {
                if ($category instanceof CQuizQuestionCategory) {
                    $question->removeCategory($category);
                }
            }

            $this->entityManager->createQueryBuilder()
                ->update(CQuizQuestion::class, 'childQuestion')
                ->set('childQuestion.parentMediaId', 'NULL')
                ->andWhere('childQuestion.parentMediaId = :questionId')
                ->setParameter('questionId', $questionId, Types::INTEGER)
                ->getQuery()
                ->execute()
            ;

            $this->entityManager->remove($question);
            $this->entityManager->flush();
            $connection->commit();
        } catch (Throwable $throwable) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }

            throw new ConflictHttpException('This question is still referenced by another resource and could not be deleted.', $throwable);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getFilters(Request $request): array
    {
        $all = $request->query->all();
        $extraValues = [];

        foreach ($all as $key => $value) {
            if (!\is_string($key) || !str_starts_with($key, 'extra_')) {
                continue;
            }

            if (\is_array($value)) {
                $value = implode(';', array_values(array_filter(array_map('strval', $value), static fn (string $item): bool => '' !== trim($item))));
            }

            $extraValues[$key] = trim((string) $value);
        }

        return [
            'formSent' => max(0, $request->query->getInt('form_sent')),
            'page' => max(1, $request->query->getInt('page', 1)),
            'itemsPerPage' => max(5, $request->query->getInt('itemsPerPage', 20)),
            'id' => max(0, $request->query->getInt('id')),
            'title' => trim((string) $request->query->get('title', '')),
            'description' => trim((string) $request->query->get('description', '')),
            'selectedCourse' => $request->query->getInt('selected_course', -1),
            'questionLevel' => $request->query->getInt('question_level', -1),
            'answerType' => $request->query->getInt('answer_type', -1),
            'extraValues' => $extraValues,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function createQuestionQueryBuilder(array $filters): QueryBuilder
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->from(CQuizQuestion::class, 'question')
            ->leftJoin('question.resourceNode', 'questionNode')
        ;

        $id = (int) $filters['id'];
        if ($id > 0) {
            $queryBuilder
                ->andWhere('question.iid = :questionId')
                ->setParameter('questionId', $id, Types::INTEGER)
            ;
        }

        $selectedCourse = (int) $filters['selectedCourse'];
        if ($selectedCourse > 0) {
            $directLinkQuery = $this->entityManager->createQueryBuilder()
                ->select('1')
                ->from(ResourceLink::class, 'directQuestionLink')
                ->where('directQuestionLink.resourceNode = questionNode')
                ->andWhere('IDENTITY(directQuestionLink.course) = :selectedCourse')
                ->andWhere('directQuestionLink.deletedAt IS NULL')
                ->andWhere('directQuestionLink.endVisibilityAt IS NULL')
            ;

            $quizLinkQuery = $this->entityManager->createQueryBuilder()
                ->select('1')
                ->from(CQuizRelQuestion::class, 'scopeRelation')
                ->innerJoin('scopeRelation.quiz', 'scopeQuiz')
                ->innerJoin('scopeQuiz.resourceNode', 'scopeQuizNode')
                ->innerJoin('scopeQuizNode.resourceLinks', 'scopeQuizLink')
                ->where('scopeRelation.question = question')
                ->andWhere('IDENTITY(scopeQuizLink.course) = :selectedCourse')
                ->andWhere('scopeQuizLink.deletedAt IS NULL')
                ->andWhere('scopeQuizLink.endVisibilityAt IS NULL')
            ;

            $queryBuilder
                ->andWhere(
                    $queryBuilder->expr()->orX(
                        $queryBuilder->expr()->exists($directLinkQuery->getDQL()),
                        $queryBuilder->expr()->exists($quizLinkQuery->getDQL())
                    )
                )
                ->setParameter('selectedCourse', $selectedCourse, Types::INTEGER)
            ;
        }

        $questionLevel = (int) $filters['questionLevel'];
        if ($questionLevel >= 0) {
            $queryBuilder
                ->andWhere('question.level = :questionLevel')
                ->setParameter('questionLevel', $questionLevel, Types::INTEGER)
            ;
        }

        $answerType = (int) $filters['answerType'];
        if ($answerType >= 0) {
            $queryBuilder
                ->andWhere('question.type = :answerType')
                ->setParameter('answerType', $answerType, Types::INTEGER)
            ;
        }

        $textExpressions = [];
        $title = (string) $filters['title'];
        if ('' !== $title) {
            $textExpressions[] = $queryBuilder->expr()->like('LOWER(question.question)', ':titleSearch');
            $queryBuilder->setParameter('titleSearch', '%'.mb_strtolower($title).'%');
        }

        $description = (string) $filters['description'];
        if ('' !== $description) {
            $textExpressions[] = $queryBuilder->expr()->like('LOWER(question.description)', ':descriptionSearch');
            $queryBuilder->setParameter('descriptionSearch', '%'.mb_strtolower($description).'%');
        }

        if ([] !== $textExpressions) {
            $queryBuilder->andWhere($queryBuilder->expr()->orX(...$textExpressions));
        }

        $extraIndex = 0;
        foreach ((array) $filters['extraValues'] as $key => $value) {
            if ('' === $value) {
                continue;
            }

            ++$extraIndex;
            $variable = substr((string) $key, 6);
            if ('' === $variable) {
                continue;
            }

            $valueAlias = 'extraValue'.$extraIndex;
            $fieldAlias = 'extraField'.$extraIndex;
            $variableParameter = 'extraVariable'.$extraIndex;
            $valueParameter = 'extraFilterValue'.$extraIndex;

            $extraQueryBuilder = $this->entityManager->createQueryBuilder()
                ->select('1')
                ->from(ExtraFieldValues::class, $valueAlias)
                ->innerJoin($valueAlias.'.field', $fieldAlias)
                ->where($valueAlias.'.itemId = question.iid')
                ->andWhere($fieldAlias.'.itemType = :questionExtraFieldType')
                ->andWhere($fieldAlias.'.variable = :'.$variableParameter)
                ->andWhere($valueAlias.'.fieldValue = :'.$valueParameter)
            ;

            $queryBuilder
                ->andWhere($queryBuilder->expr()->exists($extraQueryBuilder->getDQL()))
                ->setParameter('questionExtraFieldType', ExtraField::QUESTION_FIELD_TYPE, Types::INTEGER)
                ->setParameter($variableParameter, $variable)
                ->setParameter($valueParameter, $value)
            ;
        }

        return $queryBuilder;
    }

    /**
     * @param array<int, int> $questionIds
     *
     * @return array<int, array<string, mixed>>
     */
    private function getQuestionItems(array $questionIds): array
    {
        if ([] === $questionIds) {
            return [];
        }

        /** @var array<int, CQuizQuestion> $questionById */
        $questionById = [];
        $questions = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT question, categories, questionNode')
            ->from(CQuizQuestion::class, 'question')
            ->leftJoin('question.categories', 'categories')
            ->leftJoin('question.resourceNode', 'questionNode')
            ->andWhere('question.iid IN (:questionIds)')
            ->setParameter('questionIds', $questionIds)
            ->getQuery()
            ->getResult()
        ;

        $nodeToQuestionId = [];
        foreach ($questions as $question) {
            if (!$question instanceof CQuizQuestion || null === $question->getIid()) {
                continue;
            }

            $questionId = (int) $question->getIid();
            $questionById[$questionId] = $question;
            $resourceNode = $question->getResourceNode();
            if (null !== $resourceNode && null !== $resourceNode->getId()) {
                $nodeToQuestionId[(int) $resourceNode->getId()] = $questionId;
            }
        }

        $answersByQuestion = $this->getAnswersByQuestion($questionIds);
        $directSourcesByQuestion = $this->getDirectSourcesByQuestion($nodeToQuestionId);
        $exerciseRefsByQuestion = $this->getExerciseReferencesByQuestion($questionIds);

        $items = [];
        foreach ($questionIds as $questionId) {
            $question = $questionById[$questionId] ?? null;
            if (!$question instanceof CQuizQuestion) {
                continue;
            }

            $exerciseReferences = $exerciseRefsByQuestion[$questionId] ?? [];
            $source = $this->chooseSource($directSourcesByQuestion[$questionId] ?? [], $exerciseReferences);
            $type = (int) $question->getType();
            $categories = [];

            foreach ($question->getCategories() as $category) {
                if ($category instanceof CQuizQuestionCategory) {
                    $categories[] = $category->getTitle();
                }
            }

            $items[] = [
                'id' => $questionId,
                'titleHtml' => $this->sanitizeRichText($question->getQuestion()),
                'titleText' => trim(strip_tags($question->getQuestion())),
                'descriptionHtml' => $this->sanitizeRichText((string) $question->getDescription()),
                'descriptionText' => trim(strip_tags((string) $question->getDescription())),
                'type' => $type,
                'typeLabel' => $this->getQuestionTypeLabel($type),
                'typeIcon' => $this->getQuestionTypeIcon($type),
                'difficulty' => (int) $question->getLevel(),
                'score' => (float) $question->getPonderation(),
                'questionCode' => (string) $question->getQuestionCode(),
                'categories' => array_values($categories),
                'answers' => $this->mapAnswerPreview($answersByQuestion[$questionId] ?? [], $type),
                'exerciseReferences' => $exerciseReferences,
                'source' => $source,
                'orphan' => [] === $exerciseReferences,
                'canEdit' => null !== $source
                    && (int) ($source['courseNodeId'] ?? 0) > 0
                    && (30 !== $type || $this->hasActiveExerciseReference($exerciseReferences)),
                'canDelete' => true,
            ];
        }

        return $items;
    }

    /**
     * @param array<int, int> $questionIds
     *
     * @return array<int, array<int, CQuizAnswer>>
     */
    private function getAnswersByQuestion(array $questionIds): array
    {
        $result = [];
        $answers = $this->entityManager->createQueryBuilder()
            ->select('answer, question')
            ->from(CQuizAnswer::class, 'answer')
            ->innerJoin('answer.question', 'question')
            ->andWhere('question.iid IN (:questionIds)')
            ->setParameter('questionIds', $questionIds)
            ->orderBy('question.iid', 'ASC')
            ->addOrderBy('answer.position', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        foreach ($answers as $answer) {
            if (!$answer instanceof CQuizAnswer || null === $answer->getQuestion()->getIid()) {
                continue;
            }

            $result[(int) $answer->getQuestion()->getIid()][] = $answer;
        }

        return $result;
    }

    /**
     * @param array<int, int> $nodeToQuestionId
     *
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function getDirectSourcesByQuestion(array $nodeToQuestionId): array
    {
        if ([] === $nodeToQuestionId) {
            return [];
        }

        $rows = $this->entityManager->createQueryBuilder()
            ->select(
                'IDENTITY(link.resourceNode) AS nodeId',
                'course.id AS courseId',
                'course.code AS courseCode',
                'course.title AS courseTitle',
                'courseNode.id AS courseNodeId',
                'session.id AS sessionId',
                'link.visibility AS visibility',
                'link.deletedAt AS deletedAt',
                'link.endVisibilityAt AS endVisibilityAt'
            )
            ->from(ResourceLink::class, 'link')
            ->innerJoin('link.course', 'course')
            ->innerJoin('course.resourceNode', 'courseNode')
            ->leftJoin('link.session', 'session')
            ->andWhere('IDENTITY(link.resourceNode) IN (:nodeIds)')
            ->setParameter('nodeIds', array_keys($nodeToQuestionId))
            ->getQuery()
            ->getArrayResult()
        ;

        $result = [];
        foreach ($rows as $row) {
            $questionId = $nodeToQuestionId[(int) $row['nodeId']] ?? 0;
            if ($questionId <= 0) {
                continue;
            }

            $row['active'] = $this->isActiveLinkRow($row);
            $row['sessionId'] = null === $row['sessionId'] ? 0 : (int) $row['sessionId'];
            $result[$questionId][] = $row;
        }

        return $result;
    }

    /**
     * @param array<int, int> $questionIds
     *
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function getExerciseReferencesByQuestion(array $questionIds): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select(
                'question.iid AS questionId',
                'quiz.iid AS exerciseId',
                'quiz.title AS exerciseTitle',
                'course.id AS courseId',
                'course.code AS courseCode',
                'course.title AS courseTitle',
                'courseNode.id AS courseNodeId',
                'session.id AS sessionId',
                'quizLink.visibility AS visibility',
                'quizLink.deletedAt AS deletedAt',
                'quizLink.endVisibilityAt AS endVisibilityAt'
            )
            ->from(CQuizRelQuestion::class, 'relation')
            ->innerJoin('relation.question', 'question')
            ->innerJoin('relation.quiz', 'quiz')
            ->innerJoin('quiz.resourceNode', 'quizNode')
            ->leftJoin('quizNode.resourceLinks', 'quizLink')
            ->leftJoin('quizLink.course', 'course')
            ->leftJoin('course.resourceNode', 'courseNode')
            ->leftJoin('quizLink.session', 'session')
            ->andWhere('question.iid IN (:questionIds)')
            ->setParameter('questionIds', $questionIds)
            ->orderBy('question.iid', 'ASC')
            ->addOrderBy('quiz.title', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;

        $result = [];
        $seen = [];
        foreach ($rows as $row) {
            $questionId = (int) $row['questionId'];
            $exerciseId = (int) $row['exerciseId'];
            $courseId = null === $row['courseId'] ? 0 : (int) $row['courseId'];
            $sessionId = null === $row['sessionId'] ? 0 : (int) $row['sessionId'];
            $key = $questionId.':'.$exerciseId.':'.$courseId.':'.$sessionId;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $active = $courseId > 0 && $this->isActiveLinkRow($row);
            $result[$questionId][] = [
                'exerciseId' => $exerciseId,
                'exerciseTitle' => (string) $row['exerciseTitle'],
                'courseId' => $courseId,
                'courseCode' => (string) ($row['courseCode'] ?? ''),
                'courseTitle' => (string) ($row['courseTitle'] ?? ''),
                'courseNodeId' => null === $row['courseNodeId'] ? 0 : (int) $row['courseNodeId'],
                'sessionId' => $sessionId,
                'active' => $active,
                'deleted' => !$active,
            ];
        }

        return $result;
    }

    /**
     * @param array<int, array<string, mixed>> $directSources
     * @param array<int, array<string, mixed>> $exerciseReferences
     *
     * @return array<string, mixed>|null
     */
    private function chooseSource(array $directSources, array $exerciseReferences): ?array
    {
        foreach ([$directSources, $exerciseReferences] as $sources) {
            foreach ($sources as $source) {
                if (true === ($source['active'] ?? false) && (int) ($source['courseId'] ?? 0) > 0) {
                    return $source;
                }
            }
        }

        foreach ([$directSources, $exerciseReferences] as $sources) {
            foreach ($sources as $source) {
                if ((int) ($source['courseId'] ?? 0) > 0) {
                    return $source;
                }
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function isActiveLinkRow(array $row): bool
    {
        return null === ($row['deletedAt'] ?? null)
            && null === ($row['endVisibilityAt'] ?? null)
            && \in_array((int) ($row['visibility'] ?? -1), [ResourceLink::VISIBILITY_DRAFT, ResourceLink::VISIBILITY_PUBLISHED], true);
    }

    /**
     * @param array<int, array<string, mixed>> $exerciseReferences
     */
    private function hasActiveExerciseReference(array $exerciseReferences): bool
    {
        foreach ($exerciseReferences as $reference) {
            if (true === ($reference['active'] ?? false)
                && (int) ($reference['exerciseId'] ?? 0) > 0
                && (int) ($reference['courseNodeId'] ?? 0) > 0
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, CQuizAnswer> $answers
     *
     * @return array<int, array<string, mixed>>
     */
    private function mapAnswerPreview(array $answers, int $questionType): array
    {
        $items = [];
        $fillBlank = \in_array($questionType, [3, 27], true);

        foreach ($answers as $answer) {
            $answerText = $answer->getAnswer();
            if ('' === trim(strip_tags($answerText))) {
                continue;
            }

            if ($fillBlank) {
                $answerText = (string) preg_replace('/::.*$/s', '', $answerText);
                $answerText = (string) preg_replace_callback(
                    '/\[([^\]]+)\]/',
                    static fn (array $matches): string => '<mark>'.htmlspecialchars(trim((string) $matches[1]), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</mark>',
                    $answerText
                );
            }

            $items[] = [
                'html' => $this->sanitizeRichText($answerText),
                'text' => trim(strip_tags($answerText)),
                'correct' => 0 !== (int) $answer->getCorrect(),
                'score' => (float) $answer->getPonderation(),
                'position' => $answer->getPosition(),
            ];
        }

        return $items;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getCourseOptions(): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->select('course.id AS value', 'course.title AS title', 'course.code AS code')
            ->from(Course::class, 'course')
            ->orderBy('course.title', 'ASC')
            ->addOrderBy('course.code', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;

        $items = [['value' => -1, 'label' => 'All']];
        foreach ($rows as $row) {
            $title = trim((string) $row['title']);
            $code = trim((string) $row['code']);
            $items[] = [
                'value' => (int) $row['value'],
                'label' => '' === $code ? $title : $title.' ['.$code.']',
            ];
        }

        return $items;
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    private function getDifficultyOptions(): array
    {
        $items = [['value' => -1, 'label' => 'All']];
        for ($level = 0; $level <= 5; ++$level) {
            $items[] = ['value' => $level, 'label' => (string) $level];
        }

        return $items;
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    private function getQuestionTypeOptions(): array
    {
        $items = [['value' => -1, 'label' => 'All']];
        foreach ($this->getQuestionTypeDefinitions() as $definition) {
            $items[] = ['value' => $definition['type'], 'label' => $definition['label']];
        }

        return $items;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getExtraFieldDefinitions(): array
    {
        $fields = $this->entityManager->getRepository(ExtraField::class)->findBy(
            [
                'itemType' => ExtraField::QUESTION_FIELD_TYPE,
                'visibleToSelf' => true,
                'filter' => true,
            ],
            ['fieldOrder' => 'ASC', 'displayText' => 'ASC']
        );

        $items = [];
        foreach ($fields as $field) {
            if (!$field instanceof ExtraField) {
                continue;
            }

            $options = [];
            foreach ($field->getOptions() as $option) {
                $options[] = [
                    'id' => (int) ($option->getId() ?? 0),
                    'value' => (string) $option->getValue(),
                    'label' => (string) ($option->getDisplayText() ?: $option->getValue()),
                    'parent' => (string) $option->getPriority(),
                    'order' => (int) ($option->getOptionOrder() ?? 0),
                ];
            }
            usort($options, static fn (array $left, array $right): int => [$left['order'], $left['label']] <=> [$right['order'], $right['label']]);

            $items[] = [
                'variable' => $field->getVariable(),
                'label' => (string) ($field->getDisplayText() ?: $field->getVariable()),
                'help' => (string) ($field->getHelperText() ?: $field->getDescription()),
                'type' => $field->getValueType(),
                'defaultValue' => (string) $field->getDefaultValue(),
                'options' => $options,
            ];
        }

        return $items;
    }

    private function getQuestionTypeLabel(int $type): string
    {
        foreach ($this->getQuestionTypeDefinitions() as $definition) {
            if ($type === $definition['type']) {
                return $definition['label'];
            }
        }

        return 'Question';
    }

    private function getQuestionTypeIcon(int $type): string
    {
        foreach ($this->getQuestionTypeDefinitions() as $definition) {
            if ($type === $definition['type']) {
                return $definition['icon'];
            }
        }

        return 'quiz.png';
    }

    /**
     * @return array<int, array{type: int, label: string, icon: string}>
     */
    private function getQuestionTypeDefinitions(): array
    {
        return [
            ['type' => 1, 'label' => 'Multiple choice', 'icon' => 'mcua.png'],
            ['type' => 2, 'label' => 'Multiple answer', 'icon' => 'mcma.png'],
            ['type' => 3, 'label' => 'Fill blanks or form', 'icon' => 'fill_in_blanks.png'],
            ['type' => 4, 'label' => 'Matching', 'icon' => 'matching.png'],
            ['type' => 5, 'label' => 'Open question', 'icon' => 'open_answer.png'],
            ['type' => 6, 'label' => 'Image zones', 'icon' => 'hotspot.png'],
            ['type' => 8, 'label' => 'Hotspot delineation', 'icon' => 'hotspot_delineation.png'],
            ['type' => 9, 'label' => 'Exact Selection', 'icon' => 'mcmac.png'],
            ['type' => 10, 'label' => 'Unique answer with unknown', 'icon' => 'mcuao.png'],
            ['type' => 11, 'label' => "Multiple answer true/false/don't know", 'icon' => 'mcmao.png'],
            ['type' => 12, 'label' => "Combination true/false/don't-know", 'icon' => 'mcmaco.png'],
            ['type' => 13, 'label' => 'Oral expression', 'icon' => 'audio_question.png'],
            ['type' => 14, 'label' => 'Global multiple answer', 'icon' => 'mcmagl.png'],
            ['type' => 15, 'label' => 'Media question', 'icon' => 'media.png'],
            ['type' => 16, 'label' => 'Calculated answer', 'icon' => 'calculated_answer.png'],
            ['type' => 17, 'label' => 'Unique answer with images', 'icon' => 'uaimg.png'],
            ['type' => 18, 'label' => 'Sequence ordering', 'icon' => 'ordering.png'],
            ['type' => 19, 'label' => 'Match by dragging', 'icon' => 'matchingdrag.png'],
            ['type' => 20, 'label' => 'Annotation', 'icon' => 'annotation.png'],
            ['type' => 21, 'label' => 'Reading comprehension', 'icon' => 'reading_comprehension.png'],
            ['type' => 22, 'label' => 'Multiple answer true/false with degree of certainty', 'icon' => 'mccert.png'],
            ['type' => 23, 'label' => 'Upload Answer', 'icon' => 'file_upload_question.png'],
            ['type' => 24, 'label' => 'Matching combination', 'icon' => 'matching_co.png'],
            ['type' => 25, 'label' => 'Matching draggable combination', 'icon' => 'matchingdrag_co.png'],
            ['type' => 26, 'label' => 'Hotspot combination', 'icon' => 'hotspot_co.png'],
            ['type' => 27, 'label' => 'Fill in blanks combination', 'icon' => 'fill_in_blanks_co.png'],
            ['type' => 28, 'label' => 'Multiple Answer Dropdown Combination', 'icon' => 'mcma_dropdown_co.png'],
            ['type' => 29, 'label' => 'Multiple Answer Dropdown', 'icon' => 'mcma_dropdown.png'],
            ['type' => 30, 'label' => 'Answer in Office document', 'icon' => 'file_upload_question.png'],
            ['type' => 31, 'label' => 'Page break', 'icon' => 'page_end.png'],
        ];
    }

    private function sanitizeRichText(string $html): string
    {
        if ('' === trim($html)) {
            return '';
        }

        $html = (string) preg_replace('#<(script|style|iframe|object|embed|form|input|button|meta|link|base)[^>]*>.*?</\1>#is', '', $html);
        $html = strip_tags(
            $html,
            '<p><br><strong><b><em><i><u><s><ul><ol><li><span><mark><img><a><table><thead><tbody><tfoot><tr><th><td><blockquote><h1><h2><h3><h4><h5><h6><sub><sup><code><pre>'
        );
        $html = (string) preg_replace('/\s(?:on[a-z]+|style|srcdoc)\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
        $html = (string) preg_replace_callback(
            '/\s(href|src)\s*=\s*(["\'])(.*?)\2/i',
            static function (array $matches): string {
                $attribute = strtolower((string) $matches[1]);
                $value = trim((string) $matches[3]);
                $lowerValue = strtolower($value);
                $allowedDataImage = 'src' === $attribute && 1 === preg_match('#^data:image/(?:png|gif|jpe?g|webp);base64,#i', $value);

                if (!$allowedDataImage && preg_match('#^(?:javascript|vbscript|data):#i', $lowerValue)) {
                    return '';
                }

                return ' '.$attribute.'="'.htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'"';
            },
            $html
        );

        return trim($html);
    }
}
