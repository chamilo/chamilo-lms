<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Survey;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Survey\SurveyPersonalityCategory;
use Chamilo\CoreBundle\Service\Survey\SurveyPersonalityManager;
use Chamilo\CourseBundle\Entity\CSurveyPersonalityCategory as PersonalityCategoryEntity;
use Chamilo\CourseBundle\Entity\CSurveyQuestionOption;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProcessorInterface<SurveyPersonalityCategory, SurveyPersonalityCategory> */
final readonly class SurveyPersonalityCategoryProcessor implements ProcessorInterface
{
    public function __construct(
        private SurveyPersonalityCategoryProvider $provider,
        private SurveyPersonalityManager $manager,
        private EntityManagerInterface $entityManager,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?SurveyPersonalityCategory
    {
        $surveyId = (int) ($uriVariables['surveyId'] ?? 0);
        $survey = $this->provider->getPersonalitySurvey($surveyId);
        if ('delete_survey_personality_category' === $operation->getName()) {
            $categoryId = (int) ($uriVariables['categoryId'] ?? 0);
            $category = $this->entityManager->getRepository(PersonalityCategoryEntity::class)->find($categoryId);
            if (!$category instanceof PersonalityCategoryEntity || $category->getSurvey()->getIid() !== $survey->getIid()) {
                throw new NotFoundHttpException('The requested category was not found.');
            }
            foreach ($this->entityManager->getRepository(CSurveyQuestionOption::class)->findBy(['survey' => $survey]) as $option) {
                if (\in_array($option->getQuestion()->getType(), SurveyPersonalityManager::QUESTION_TYPES, true)
                    && $option->getValue() === $categoryId) {
                    throw new ConflictHttpException('This category is still used by a question.');
                }
            }
            $this->entityManager->remove($category);
            $this->entityManager->flush();

            return null;
        }

        if (!$data instanceof SurveyPersonalityCategory) {
            throw new BadRequestHttpException('Invalid category payload.');
        }
        $label = trim($data->label);
        if ('' === $label || mb_strlen($label) > 120) {
            throw new BadRequestHttpException('The category label must contain 1 to 120 characters.');
        }
        $categories = $this->manager->getCategories($survey);
        if (\count($categories) >= 20) {
            throw new BadRequestHttpException('A personality survey supports at most 20 categories.');
        }
        foreach ($categories as $existing) {
            if (mb_strtolower($existing->getLabel()) === mb_strtolower($label)) {
                throw new ConflictHttpException('This category already exists.');
            }
        }
        $category = new PersonalityCategoryEntity($survey, $label, \count($categories) + 1);
        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $this->provider->provide($operation, ['surveyId' => $surveyId]);
    }
}
