<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Survey;

use Chamilo\CourseBundle\Entity\CSurvey;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Throwable;

trait SurveyPersonalitySupportTrait
{
    private function isPersonalitySurveySupported(): bool
    {
        try {
            return $this->entityManager->getConnection()->createSchemaManager()->tablesExist(['c_survey_group']);
        } catch (Throwable) {
            return false;
        }
    }

    private function isUnsupportedPersonalitySurvey(CSurvey $survey): bool
    {
        return 1 === (int) $survey->getSurveyType() && !$this->isPersonalitySurveySupported();
    }

    private function getUnsupportedPersonalitySurveyMessage(): string
    {
        return 'Personality surveys require the legacy survey_group table and cannot be managed in this installation.';
    }

    private function assertPersonalitySurveySupported(CSurvey $survey): void
    {
        if ($this->isUnsupportedPersonalitySurvey($survey)) {
            throw new BadRequestHttpException($this->getUnsupportedPersonalitySurveyMessage());
        }
    }
}
