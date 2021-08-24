<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

class CcAssesmentQuestionMultichoice extends CcAssesmentQuestionProcBase
{
    public function __construct($quiz, $questions, $manifest, $section, $question, $rootpath, $contextid, $outdir)
    {
        parent::__construct($quiz, $questions, $manifest, $section, $question, $rootpath, $contextid, $outdir);
        $this->qtype = CcQtiProfiletype::MULTIPLE_CHOICE;

        $correctAnswerNode = 0;
        $questionScore = 0;
        foreach ($question->answers as $answer) {
            if ($answer['correct'] > 0) {
                $correctAnswerNode = 1;
                $this->correctAnswerNodeId = $answer['id'];
                $questionScore = $answer['ponderation'];
                break;
            }
        }
        if (empty($correctAnswerNode)) {
            throw new RuntimeException('No correct answer!');
        }
        //$this->total_grade_value = ($question_score).'.0000000';
        $this->totalGradeValue = $questionScore;
    }

    public function onGenerateAnswers()
    {
        //add responses holder
        $qresponseLid = new CcResponseLidtype();
        $this->qresponseLid = $qresponseLid;
        $this->qpresentation->setResponseLid($qresponseLid);
        $qresponseChoice = new CcAssesmentRenderChoicetype();
        $qresponseLid->setRenderChoice($qresponseChoice);

        //Mark that question has only one correct answer -
        //which applies for multiple choice and yes/no questions
        $qresponseLid->setRcardinality(CcQtiValues::SINGLE);

        //are we to shuffle the responses?
        $shuffleAnswers = $this->quiz['random_answers'] > 0;

        $qresponseChoice->enableShuffle($shuffleAnswers);
        $answerlist = [];

        $qaResponses = $this->questionNode->answers;

        foreach ($qaResponses as $node) {
            $answerContent = $node['answer'];
            $id = $node['id'];

            $result = CcHelpers::processLinkedFiles($answerContent,
                $this->manifest,
                $this->rootpath,
                $this->contextid,
                $this->outdir);

            $qresponseLabel = CcAssesmentHelper::addAnswer($qresponseChoice,
                $result[0],
                CcQtiValues::HTMLTYPE);

            PkgResourceDependencies::instance()->add($result[1]);

            $answerIdent = $qresponseLabel->getIdent();
            $feedbackIdent = $answerIdent.'_fb';
            if (empty($this->correctAnswerIdent) && $id) {
                $this->correctAnswerIdent = $answerIdent;
            }

            //add answer specific feedbacks if not empty
            $content = $node['comment'];

            if (!empty($content)) {
                $result = CcHelpers::processLinkedFiles($content,
                    $this->manifest,
                    $this->rootpath,
                    $this->contextid,
                    $this->outdir);

                CcAssesmentHelper::addFeedback($this->qitem,
                    $result[0],
                    CcQtiValues::HTMLTYPE,
                    $feedbackIdent);

                PkgResourceDependencies::instance()->add($result[1]);
                $answerlist[$answerIdent] = $feedbackIdent;
            }
        }
        $this->answerlist = $answerlist;
    }

    public function onGenerateFeedbacks()
    {
        parent::onGenerateFeedbacks();

        //Question combined feedbacks
        $correctQuestionFb = '';
        $incorrectQuestionFb = '';

        if (empty($correctQuestionFb)) {
            //Hardcode some text for now
            $correctQuestionFb = 'Well done!';
        }
        if (empty($incorrectQuestionFb)) {
            //Hardcode some text for now
            $incorrectQuestionFb = 'Better luck next time!';
        }

        $proc = ['correct_fb' => $correctQuestionFb, 'general_incorrect_fb' => $incorrectQuestionFb];
        foreach ($proc as $ident => $content) {
            if (empty($content)) {
                continue;
            }
            $result = CcHelpers::processLinkedFiles($content,
                $this->manifest,
                $this->rootpath,
                $this->contextid,
                $this->outdir);
            CcAssesmentHelper::addFeedback($this->qitem,
                $result[0],
                CcQtiValues::HTMLTYPE,
                $ident);
            PkgResourceDependencies::instance()->add($result[1]);
            if ($ident == 'correct_fb') {
                $this->correctFeedbacks[] = $ident;
            } else {
                $this->incorrectFeedbacks[] = $ident;
            }
        }
    }

    public function onGenerateResponseProcessing()
    {
        parent::onGenerateResponseProcessing();

        //respconditions
        /**
         * General unconditional feedback must be added as a first respcondition
         * without any condition and just displayfeedback (if exists).
         */
        if (!empty($this->generalFeedback)) {
            $qrespcondition = new CcAssesmentRespconditiontype();
            $qrespcondition->setTitle('General feedback');
            $this->qresprocessing->addRespcondition($qrespcondition);
            $qrespcondition->enableContinue();
            //define the condition for success
            $qconditionvar = new CcAssignmentConditionvar();
            $qrespcondition->setConditionvar($qconditionvar);
            $qother = new CcAssignmentConditionvarOthertype();
            $qconditionvar->setOther($qother);
            $qdisplayfeedback = new CcAssignmentDisplayfeedbacktype();
            $qrespcondition->addDisplayfeedback($qdisplayfeedback);
            $qdisplayfeedback->setFeedbacktype(CcQtiValues::RESPONSE);
            $qdisplayfeedback->setLinkrefid('general_fb');
        }

        //success condition
        $qrespcondition = new CcAssesmentRespconditiontype();
        $qrespcondition->setTitle('Correct');
        $this->qresprocessing->addRespcondition($qrespcondition);
        $qrespcondition->enableContinue(false);
        $qsetvar = new CcAssignmentSetvartype(100);
        $qrespcondition->addSetvar($qsetvar);
        //define the condition for success
        $qconditionvar = new CcAssignmentConditionvar();
        $qrespcondition->setConditionvar($qconditionvar);
        $qvarequal = new CcAssignmentConditionvarVarequaltype($this->correctAnswerIdent);
        $qconditionvar->setVarequal($qvarequal);
        $qvarequal->setRespident($this->qresponseLid->getIdent());

        if (array_key_exists($this->correctAnswerIdent, $this->answerlist)) {
            $qdisplayfeedback = new CcAssignmentDisplayfeedbacktype();
            $qrespcondition->addDisplayfeedback($qdisplayfeedback);
            $qdisplayfeedback->setFeedbacktype(CcQtiValues::RESPONSE);
            $qdisplayfeedback->setLinkrefid($this->answerlist[$this->correctAnswerIdent]);
        }

        foreach ($this->correctFeedbacks as $ident) {
            $qdisplayfeedback = new CcAssignmentDisplayfeedbacktype();
            $qrespcondition->addDisplayfeedback($qdisplayfeedback);
            $qdisplayfeedback->setFeedbacktype(CcQtiValues::RESPONSE);
            $qdisplayfeedback->setLinkrefid($ident);
        }

        //rest of the conditions
        foreach ($this->answerlist as $ident => $refid) {
            if ($ident == $this->correctAnswerIdent) {
                continue;
            }

            $qrespcondition = new CcAssesmentRespconditiontype();
            $this->qresprocessing->addRespcondition($qrespcondition);
            $qsetvar = new CcAssignmentSetvartype(0);
            $qrespcondition->addSetvar($qsetvar);
            //define the condition for fail
            $qconditionvar = new CcAssignmentConditionvar();
            $qrespcondition->setConditionvar($qconditionvar);
            $qvarequal = new CcAssignmentConditionvarVarequaltype($ident);
            $qconditionvar->setVarequal($qvarequal);
            $qvarequal->setRespident($this->qresponseLid->getIdent());

            $qdisplayfeedback = new CcAssignmentDisplayfeedbacktype();
            $qrespcondition->addDisplayfeedback($qdisplayfeedback);
            $qdisplayfeedback->setFeedbacktype(CcQtiValues::RESPONSE);
            $qdisplayfeedback->setLinkrefid($refid);

            foreach ($this->incorrectFeedbacks as $ident) {
                $qdisplayfeedback = new CcAssignmentDisplayfeedbacktype();
                $qrespcondition->addDisplayfeedback($qdisplayfeedback);
                $qdisplayfeedback->setFeedbacktype(CcQtiValues::RESPONSE);
                $qdisplayfeedback->setLinkrefid($ident);
            }
        }
    }
}
