<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Survey;

use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Entity\CSurveyAnswer;
use Chamilo\CourseBundle\Entity\CSurveyPersonalityCategory;
use Chamilo\CourseBundle\Entity\CSurveyQuestionOption;
use Doctrine\ORM\EntityManagerInterface;

/** Profile calculation over the existing survey answers and option-category assignments. */
final readonly class SurveyPersonalityManager
{
    public const array QUESTION_TYPES = [
        'personality_choice',
        'personality_agreement',
        'personality_scale',
        'personality_order',
    ];

    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * @return array<int, CSurveyPersonalityCategory>
     */
    public function getCategories(CSurvey $survey): array
    {
        return $this->entityManager->getRepository(CSurveyPersonalityCategory::class)->findBy(
            ['survey' => $survey],
            ['sort' => 'ASC', 'iid' => 'ASC'],
        );
    }

    /**
     * @return array<int, array{id: int, label: string, sort: int}>
     */
    public function normalizeCategories(CSurvey $survey): array
    {
        $items = [];
        foreach ($this->getCategories($survey) as $category) {
            $items[] = [
                'id' => (int) $category->getIid(),
                'label' => $category->getLabel(),
                'sort' => $category->getSort(),
            ];
        }

        return $items;
    }

    /**
     * Calculate a personal profile, or a class-wide total when $answerUserKey is null.
     * A separate count is kept because negative and positive scores may cancel out.
     *
     * @return array<int, array{id: int, label: string, sort: int, score: int, answers: int}>
     */
    public function profile(CSurvey $survey, ?string $answerUserKey, ?int $sessionId, ?int $lpItemId = null): array
    {
        $profile = [];
        foreach ($this->normalizeCategories($survey) as $category) {
            $profile[$category['id']] = [...$category, 'score' => 0, 'answers' => 0];
        }
        if ([] === $profile) {
            return [];
        }

        $options = $this->entityManager->getRepository(CSurveyQuestionOption::class)->findBy(['survey' => $survey]);
        $optionCategories = [];
        foreach ($options as $option) {
            if (!\in_array($option->getQuestion()->getType(), self::QUESTION_TYPES, true)) {
                continue;
            }
            $categoryId = (int) $option->getValue();
            if (isset($profile[$categoryId])) {
                $optionCategories[(int) $option->getIid()] = $categoryId;
            }
        }

        $qb = $this->entityManager->createQueryBuilder()
            ->select('answer')
            ->from(CSurveyAnswer::class, 'answer')
            ->andWhere('IDENTITY(answer.survey) = :surveyId')
            ->setParameter('surveyId', (int) $survey->getIid())
        ;
        if (null !== $answerUserKey) {
            $qb->andWhere('answer.user = :answerUser')->setParameter('answerUser', $answerUserKey);
        }
        if (null === $sessionId) {
            $qb->andWhere('answer.sessionId IS NULL');
        } else {
            $qb->andWhere('answer.sessionId = :sessionId')->setParameter('sessionId', $sessionId);
        }
        if (null !== $lpItemId) {
            $qb->andWhere('answer.lpItemId = :lpItemId')->setParameter('lpItemId', $lpItemId);
        }

        foreach ($qb->getQuery()->getResult() as $answer) {
            if (!$answer instanceof CSurveyAnswer) {
                continue;
            }
            if (!\in_array($answer->getQuestion()->getType(), self::QUESTION_TYPES, true)) {
                continue;
            }
            $categoryId = $optionCategories[(int) $answer->getOptionId()] ?? null;
            if (null === $categoryId) {
                continue;
            }
            $profile[$categoryId]['score'] += $answer->getValue();
            ++$profile[$categoryId]['answers'];
        }

        return array_values($profile);
    }
}
