<?php
/* Source: https://github.com/moodle/moodle/blob/MOODLE_310_STABLE/backup/cc/cc_lib/cc_asssesment.php under GNU/GPL license */

abstract class CcAssesmentHelper
{
    public static $correctFb = null;
    public static $incorrectFb = null;

    public static function addFeedback($qitem, $content, $contentType, $ident)
    {
        if (empty($content)) {
            return false;
        }
        $qitemfeedback = new CcAssesmentItemfeedbacktype();
        $qitem->addItemfeedback($qitemfeedback);
        if (!empty($ident)) {
            $qitemfeedback->setIdent($ident);
        }
        $qflowmat = new CcAssesmentFlowMattype();
        $qitemfeedback->setFlowMat($qflowmat);
        $qmaterialfb = new CcAssesmentMaterial();
        $qflowmat->setMaterial($qmaterialfb);
        $qmattext = new CcAssesmentMattext();
        $qmaterialfb->setMattext($qmattext);
        $qmattext->setContent($content, $contentType);

        return true;
    }

    public static function addAnswer($qresponseChoice, $content, $contentType)
    {
        $qresponseLabel = new CcAssesmentResponseLabeltype();
        $qresponseChoice->addResponseLabel($qresponseLabel);
        $qrespmaterial = new CcAssesmentMaterial();
        $qresponseLabel->setMaterial($qrespmaterial);
        $qrespmattext = new CcAssesmentMattext();
        $qrespmaterial->setMattext($qrespmattext);
        $qrespmattext->setContent($content, $contentType);

        return $qresponseLabel;
    }

    public static function addResponseCondition($node, $title, $ident, $feedbackRefid, $respident)
    {
        $qrespcondition = new CcAssesmentRespconditiontype();
        $node->addRespcondition($qrespcondition);
        //define rest of the conditions
        $qconditionvar = new CcAssignmentConditionvar();
        $qrespcondition->setConditionvar($qconditionvar);
        $qvarequal = new CcAssignmentConditionvarVarequaltype($ident);
        $qvarequal->enableCase();
        $qconditionvar->setVarequal($qvarequal);
        $qvarequal->setRespident($respident);
        $qdisplayfeedback = new CcAssignmentDisplayfeedbacktype();
        $qrespcondition->addDisplayfeedback($qdisplayfeedback);
        $qdisplayfeedback->setFeedbacktype(CcQtiValues::RESPONSE);
        $qdisplayfeedback->setLinkrefid($feedbackRefid);
    }

    public static function addAssesmentDescription($rt, $content, $contenttype)
    {
        if (empty($rt) || empty($content)) {
            return;
        }
        $activity_rubric = new CcAssesmentRubricBase();
        $rubric_material = new CcAssesmentMaterial();
        $activity_rubric->setMaterial($rubric_material);
        $rubric_mattext = new CcAssesmentMattext();
        $rubric_material->setLabel('Summary');
        $rubric_material->setMattext($rubric_mattext);
        $rubric_mattext->setContent($content, $contenttype);
        $rt->setRubric($activity_rubric);
    }

    public static function addRespcondition($node, $title, $feedbackRefid, $gradeValue = null, $continue = false)
    {
        $qrespcondition = new CcAssesmentRespconditiontype();
        $qrespcondition->setTitle($title);
        $node->addRespcondition($qrespcondition);
        $qrespcondition->enableContinue($continue);
        //Add setvar if grade present
        if ($gradeValue !== null) {
            $qsetvar = new CcAssignmentSetvartype($gradeValue);
            $qrespcondition->addSetvar($qsetvar);
        }
        //define the condition for success
        $qconditionvar = new CcAssignmentConditionvar();
        $qrespcondition->setConditionvar($qconditionvar);
        $qother = new CcAssignmentConditionvarOthertype();
        $qconditionvar->setOther($qother);
        $qdisplayfeedback = new CcAssignmentDisplayfeedbacktype();
        $qrespcondition->addDisplayfeedback($qdisplayfeedback);
        $qdisplayfeedback->setFeedbacktype(CcQtiValues::RESPONSE);
        $qdisplayfeedback->setLinkrefid($feedbackRefid);
    }

    /**
     * Enter description here ...
     *
     * @param XMLGenericDocument   $qdoc
     * @param unknown_type         $manifest
     * @param cc_assesment_section $section
     * @param unknown_type         $rootpath
     * @param unknown_type         $contextid
     * @param string               $outdir
     */
    public static function processQuestions(&$objQuizz, &$manifest, CcAssesmentSection &$section, $rootpath, $contextid, $outdir)
    {
        PkgResourceDependencies::instance()->reset();
        $questioncount = 0;
        foreach ($objQuizz['questions'] as $question) {
            $qtype = $question->quiz_type;
            /* Question type comes from the c_quiz_question.type column.
             * You can find the different types defined in api.lib.php.
             * Look for UNIQUE_ANSWER as the first constant defined
             * 1 : Unique Answer (Multiple choice, single response)
             * 2 : Multiple Answers (Multiple choice, multiple response)
             * 5 : Free Answer ("essay" in CC13)
             */
            $questionProcessor = null;
            switch ($qtype) {
                case UNIQUE_ANSWER:
                    try {
                        $questionProcessor = new CcAssesmentQuestionMultichoice($objQuizz, $objQuizz['questions'], $manifest, $section, $question, $rootpath, $contextid, $outdir);
                        $questionProcessor->generate();
                        $questioncount++;
                    } catch (RuntimeException $e) {
                        error_log($e->getMessage().' in question of test '.$objQuizz['title']);
                        continue 2;
                    }
                    break;
                case MULTIPLE_ANSWER:
                    try {
                        $questionProcessor = new CcAssesmentQuestionMultichoiceMultiresponse($objQuizz, $objQuizz['questions'], $manifest, $section, $question, $rootpath, $contextid, $outdir);
                        $questionProcessor->generate();
                        $questioncount++;
                    } catch (RuntimeException $e) {
                        error_log($e->getMessage().' in question of test '.$objQuizz['title']);
                        continue 2;
                    }
                    break;
                case FILL_IN_BLANKS:
                    try {
                        $questionProcessor = new CcAssesmentQuestionFib($objQuizz, $objQuizz['questions'], $manifest, $section, $question, $rootpath, $contextid, $outdir);
                        $questionProcessor->generate();
                        $questioncount++;
                    } catch (RuntimeException $e) {
                        error_log($e->getMessage().' in question of test '.$objQuizz['title']);
                        continue 2;
                    }
                    break;
                case FREE_ANSWER:
                    try {
                        $questionProcessor = new CcAssesmentQuestionEssay($objQuizz, $objQuizz['questions'], $manifest, $section, $question, $rootpath, $contextid, $outdir);
                        $questionProcessor->generate();
                        $questioncount++;
                    } catch (RuntimeException $e) {
                        error_log($e->getMessage().' in question of test '.$objQuizz['title']);
                        continue 2;
                    }
                    break;
            }
        }
        //return dependencies
        return ($questioncount == 0) ? false : PkgResourceDependencies::instance()->getDeps();
    }
}
