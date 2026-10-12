<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Entity;

use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Entity\CSurveyTemplate;
use Chamilo\CourseBundle\Entity\CSurveyTemplateQuestion;
use Chamilo\CourseBundle\Entity\CSurveyTemplateQuestionOption;
use PHPUnit\Framework\TestCase;

final class SurveyTemplateEntityTest extends TestCase
{
    public function testNewTemplateIsHiddenUntilAdministratorPublishesIt(): void
    {
        $template = (new CSurveyTemplate())
            ->setTitle('Training satisfaction')
        ;

        self::assertFalse($template->isVisible());
        self::assertSame('Training satisfaction', $template->getTitle());

        $template->setVisible(true);

        self::assertTrue($template->isVisible());
    }

    public function testTemplateQuestionKeepsConditionalDependencyAndIntegerOptionValue(): void
    {
        $template = (new CSurveyTemplate())->setTitle('Template');
        $parent = (new CSurveyTemplateQuestion())
            ->setTemplate($template)
            ->setQuestion('Would you recommend this training?')
            ->setType('yesno')
            ->setDisplay('vertical')
            ->setSort(1)
        ;
        $option = (new CSurveyTemplateQuestionOption())
            ->setQuestion($parent)
            ->setOptionText('Yes')
            ->setSort(1)
            ->setValue(1)
        ;
        $child = (new CSurveyTemplateQuestion())
            ->setTemplate($template)
            ->setQuestion('Why?')
            ->setType('open')
            ->setDisplay('')
            ->setSort(2)
            ->setParent($parent)
            ->setParentOption($option)
        ;

        self::assertSame($parent, $child->getParent());
        self::assertSame($option, $child->getParentOption());
        self::assertSame(1, $option->getValue());
    }

    public function testSurveyResultsAreVisibleToTeachersByDefault(): void
    {
        $survey = new CSurvey();

        self::assertFalse($survey->isResultsHiddenFromTeacher());

        $survey->setResultsHiddenFromTeacher(true);

        self::assertTrue($survey->isResultsHiddenFromTeacher());
    }
}
