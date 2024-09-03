<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

class CcAssesmentQuestionFib extends CcAssesmentQuestionProcBase
{
    public function __construct($quiz, $questions, $manifest, $section, $questionNode, $rootpath, $contextid, $outdir)
    {
        parent::__construct($quiz, $questions, $manifest, $section, $questionNode, $rootpath, $contextid, $outdir);
        $this->qtype = CcQtiProfiletype::FIELD_ENTRY;
        $correctAnswerNodes = [];
        $questionScore = 0;
        foreach ($questionNode->answers as $index => $answer) {
            $answerScore = 0;
            $answerText = $answer['answer'];
            $pos = strrpos($answerText, '::');
            list($answerText, $answerRules) = explode('::', $answerText);
            $matches = [];
            list($weights, $sizes, $others) = explode(':', $answerRules);
            $weights = explode(',', $weights);
            $i = 0;
            // Todo: improve to tolerate all separators
            if (preg_match_all('/\[(.*?)\]/', $answerText, $matches)) {
                foreach ($matches[1] as $match) {
                    $correctAnswerNodes[] = $match;
                    $questionScore += $weights[$i];
                    $answerScore += $weights[$i];
                    $i++;
                }
            }
            $questionNode->answers[$index] = [
                'answer' => $answerText,
                'ponderation' => $answerScore,
                'comment' => $answer['comment'],
            ];
            $questionScore++;
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
        $qresponseChoice = new CcAssesmentRenderFibtype();
        $qresponseLid->setRenderFib($qresponseChoice);

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
    }

    public function onGenerateResponseProcessing()
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
