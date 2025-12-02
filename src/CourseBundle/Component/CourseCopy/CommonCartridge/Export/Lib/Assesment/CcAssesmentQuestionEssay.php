<?php

/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Lib\Assesment;

class CcAssesmentQuestionEssay extends CcAssesmentQuestionProcBase
{
    public function __construct($quiz, $questions, $manifest, $section, $question, $rootpath, $contextid, $outdir)
    {
        parent::__construct($quiz, $questions, $manifest, $section, $question, $rootpath, $contextid, $outdir);
        $this->qtype = CcQtiProfiletype::ESSAY;

        $questionScore = $question->ponderation;
        /*
        // Looks useless for CC13!?
        if (is_int($questionScore)) {
            $questionScore = ($questionScore).'.0000000';
        }
        */
        $this->total_grade_value = $questionScore;
        $this->totalGradeValue = $questionScore;
        $this->qitem->setTitle($question->question);
    }

    public function onGenerateAnswers(): void
    {
        // add responses holder
        $answerlist = [];
        $this->answerlist = $answerlist;
    }

    public function onGenerateFeedbacks(): void
    {
        parent::onGenerateFeedbacks();
    }

    public function onGenerateResponseProcessing(): void
    {
        parent::onGenerateResponseProcessing();

        /**
         * General unconditional feedback must be added as a first respcondition
         * without any condition and just displayfeedback (if exists).
         */
        $qrespcondition = new CcAssesmentRespconditiontype();
        $qrespcondition->setTitle('General feedback');
        $this->qresprocessing->addRespcondition($qrespcondition);
        $qrespcondition->enableContinue();
    }
}
