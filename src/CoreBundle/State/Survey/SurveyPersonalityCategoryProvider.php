<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Survey;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Survey\SurveyPersonalityCategory;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\SurveyHelper;
use Chamilo\CoreBundle\Service\Survey\SurveyPersonalityManager;
use Chamilo\CourseBundle\Entity\CSurvey;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/** @implements ProviderInterface<SurveyPersonalityCategory> */
final readonly class SurveyPersonalityCategoryProvider implements ProviderInterface
{
    use SurveyPersonalitySupportTrait;

    public function __construct(
        private CidReqHelper $cidReqHelper,
        private SurveyHelper $surveyHelper,
        private SurveyQuestionProvider $questionProvider,
        private SurveyPersonalityManager $manager,
        private EntityManagerInterface $entityManager,
    ) {}

    public function getPersonalitySurvey(int $surveyId): CSurvey
    {
        if ($surveyId <= 0) {
            throw new BadRequestHttpException('A valid survey id is required.');
        }
        if (!$this->surveyHelper->canManage()) {
            throw new AccessDeniedHttpException('You cannot manage personality categories.');
        }
        $course = $this->cidReqHelper->requireDoctrineCourseEntity();
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        $survey = $this->questionProvider->getSurveyFromCurrentContext($surveyId, $course, $session);
        $this->assertPersonalitySurveySupported($survey);
        if (1 !== $survey->getSurveyType()) {
            throw new BadRequestHttpException('Categories are available only for personality surveys.');
        }

        return $survey;
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): SurveyPersonalityCategory
    {
        $surveyId = (int) ($uriVariables['surveyId'] ?? 0);
        $survey = $this->getPersonalitySurvey($surveyId);
        $result = new SurveyPersonalityCategory();
        $result->surveyId = $surveyId;
        $result->categories = $this->manager->normalizeCategories($survey);

        return $result;
    }
}
