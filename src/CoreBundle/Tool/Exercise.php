<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Tool;

use Chamilo\CourseBundle\Entity\CExerciseCategory;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizQuestionCategory;

class Exercise extends AbstractTool implements ToolInterface
{
    public function getName(): string
    {
        return 'quiz';
    }

    public function getNameToShow(): string
    {
        return 'Tests';
    }

    public function getIcon(): string
    {
        return 'mdi-ballot';
    }

    public function getLink(): string
    {
        return '/main/exercise/exercise.php';
    }

    public function getCategory(): string
    {
        return 'authoring';
    }

    public function getResourceTypes(): ?array
    {
        return [
            'exercises' => CQuiz::class,
            'questions' => CQuizQuestion::class,
            'question_categories' => CQuizQuestionCategory::class,
            'exercise_categories' => CExerciseCategory::class,
        ];
    }
}
