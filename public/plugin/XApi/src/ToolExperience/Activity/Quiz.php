<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\XApi\ToolExperience\Activity;

use Chamilo\CourseBundle\Entity\CQuiz;

/**
 * Class Quiz.
 */
class Quiz extends BaseActivity
{
    private CQuiz $quiz;

    public function __construct(CQuiz $quiz)
    {
        $this->quiz = $quiz;
    }

    public function generate(): array
    {
        $languageIso = $this->resolveLanguageIso();

        $iri = $this->generateIri(
            WEB_CODE_PATH,
            'exercise/overview.php',
            ['exerciseId' => $this->quiz->getId()]
        );

        return $this->buildActivity(
            $iri,
            (string) $this->quiz->getTitle(),
            $this->quiz->getDescription() ? (string) $this->quiz->getDescription() : null,
            'http://adlnet.gov/expapi/activities/assessment'
        );
    }
}
