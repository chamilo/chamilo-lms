<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

class CcAssesmentQuestionMultichoiceMultiresponse extends CcAssesmentQuestionProcBase
{
    protected $correctAnswers = null;

    public function __construct($quiz, $questions, $manifest, $section, $questionNode, $rootpath, $contextid, $outdir)
    {
        parent::__construct($quiz, $questions, $manifest, $section, $questionNode, $rootpath, $contextid, $outdir);
        $this->qtype = CcQtiProfiletype::MULTIPLE_RESPONSE;

        $correctAnswerNodes = [];
        $questionScore = 0;
        foreach ($questionNode->answers as $answer) {
            if ($answer['correct'] > 0) {
                $correctAnswerNodes[] = $answer;
                $questionScore += $answer['ponderation'];
            }
        }
        if (count($correctAnswerNodes) == 0) {
            throw new RuntimeException('No correct answer!');
        }
        $this->correctAnswers = $correctAnswerNodes;
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
        //Mark that question has more than one correct answer
        $qresponseLid->setRcardinality(CcQtiValues::MULTIPLE);
        //are we to shuffle the responses?

        $shuffleAnswers = $this->quiz['random_answers'] > 0;
        $qresponseChoice->enableShuffle($shuffleAnswers);
        $answerlist = [];

        $qaResponses = $this->questionNode->answers;
        foreach ($qaResponses as $node) {
            $answerContent = $node['answer'];
            $answerGradeFraction = $node['ponderation'];

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
            }
            $answerlist[$answerIdent] = [$feedbackIdent, ($answerGradeFraction > 0)];
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

        $proc = ['correct_fb' => $correctQuestionFb, 'incorrect_fb' => $incorrectQuestionFb];
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
                $this->correctFeedbacks[$ident] = $ident;
            } else {
                $this->incorrectFeedbacks[$ident] = $ident;
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
        CcAssesmentHelper::addRespcondition($this->qresprocessing,
            'General feedback',
            $this->generalFeedback,
            null,
            true
        );

        $qrespcondition = new CcAssesmentRespconditiontype();
        $qrespcondition->setTitle('Correct');
        $this->qresprocessing->addRespcondition($qrespcondition);
        $qrespcondition->enableContinue(false);
        $qsetvar = new CcAssignmentSetvartype(100);
        $qrespcondition->addSetvar($qsetvar);
        //define the condition for success
        $qconditionvar = new CcAssignmentConditionvar();
        $qrespcondition->setConditionvar($qconditionvar);
        //create root and condition
        $qandcondition = new CcAssignmentConditionvarAndtype();
        $qconditionvar->setAnd($qandcondition);
        foreach ($this->answerlist as $ident => $refid) {
            $qvarequal = new CcAssignmentConditionvarVarequaltype($ident);
            $qvarequal->enableCase();
            if ($refid[1]) {
                $qandcondition->setVarequal($qvarequal);
            } else {
                $qandcondition->setNot($qvarequal);
            }
            $qvarequal->setRespident($this->qresponseLid->getIdent());
        }

        $qdisplayfeedback = new CcAssignmentDisplayfeedbacktype();
        $qrespcondition->addDisplayfeedback($qdisplayfeedback);
        $qdisplayfeedback->setFeedbacktype(CcQtiValues::RESPONSE);
        //TODO: this needs to be fixed
        reset($this->correctFeedbacks);
        $ident = key($this->correctFeedbacks);
        $qdisplayfeedback->setLinkrefid($ident);

        //rest of the conditions
        foreach ($this->answerlist as $ident => $refid) {
            CcAssesmentHelper::addResponseCondition($this->qresprocessing,
                'Incorrect feedback',
                $refid[0],
                $this->generalFeedback,
                $this->qresponseLid->getIdent()
            );
        }

        //Final element for incorrect feedback
        reset($this->incorrectFeedbacks);
        $ident = key($this->incorrectFeedbacks);
        CcAssesmentHelper::addRespcondition($this->qresprocessing,
            'Incorrect feedback',
            $ident,
            0
        );
    }
}
