<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Entity\CSurveyQuestion;

class Survey extends AbstractTool implements ToolInterface
{
    public function getName(): string
    {
        return 'survey';
    }

    public function getCategory(): string
    {
        return 'interaction';
    }

    public function getIcon(): string
    {
        return 'mdi-form-dropdown';
    }

    public function getLink(): string
    {
        return '/main/survey/survey_list.php';
    }

    public function getResourceTypes(): ?array
    {
        return [
            'surveys' => CSurvey::class,
            'survey_questions' => CSurveyQuestion::class,
        ];
    }
}
