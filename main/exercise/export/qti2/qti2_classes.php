<?php
/* For licensing terms, see /license.txt */

/**
 * Interface ImsAnswerInterface.
 */
interface ImsAnswerInterface
{
    /**
     * @param string $questionIdent
     * @param string $questionStatment
     * @param string $questionDesc
     * @param string $questionMedia
     *
     * @return string
     */
    public function imsExportResponses($questionIdent, $questionStatment, $questionDesc = '', $questionMedia = '');

    /**
     * @param $questionIdent
     *
     * @return mixed
     */
    public function imsExportResponsesDeclaration($questionIdent, Question $question = null);
}

/**
 * @author Claro Team <cvs@claroline.net>
 * @author Yannick Warnier <yannick.warnier@beeznest.com> -
 * updated ImsAnswerHotspot to match QTI norms
 */
class Ims2Question extends Question
{
    /**
     * Include the correct answer class and create answer.
     *
     * @return Answer
     */
    public function setAnswer()
    {
        switch ($this->type) {
            case MCUA:
                $answer = new ImsAnswerMultipleChoice($this->iid);

                return $answer;
            case MCMA:
            case MULTIPLE_ANSWER_DROPDOWN:
            case MULTIPLE_ANSWER_DROPDOWN_COMBINATION:
                $answer = new ImsAnswerMultipleChoice($this->iid);

                return $answer;
            case TF:
                $answer = new ImsAnswerMultipleChoice($this->iid);

                return $answer;
            case FIB:
                $answer = new ImsAnswerFillInBlanks($this->iid);

                return $answer;
            case MATCHING:
            case MATCHING_DRAGGABLE:
                $answer = new ImsAnswerMatching($this->iid);

                return $answer;
            case FREE_ANSWER:
                $answer = new ImsAnswerFree($this->iid);

                return $answer;
            case HOT_SPOT:
            case HOT_SPOT_COMBINATION:
                $answer = new ImsAnswerHotspot($this->iid);

                return $answer;
            default:
                $answer = null;
                break;
        }

        return $answer;
    }

    public function createAnswersForm($form)
    {
        return true;
    }

    public function processAnswersCreation($form, $exercise)
    {
        return true;
    }
}

/**
 * Class.
 */
class ImsAnswerMultipleChoice extends Answer implements ImsAnswerInterface
{
    /**
     * Return the XML flow for the possible answers.
     *
     * @param string $questionIdent
     * @param string $questionStatment
     * @param string $questionDesc
     * @param string $questionMedia
     *
     * @return string
     */
    public function imsExportResponses($questionIdent, $questionStatment, $questionDesc = '', $questionMedia = '')
    {
        // @todo getAnswersList() converts the answers using api_html_entity_decode()
        $this->answerList = $this->getAnswersList(true);
        $out = '    <choiceInteraction responseIdentifier="'.$questionIdent.'" >'."\n";
        $out .= '      <prompt><![CDATA['.formatExerciseQtiText($questionDesc).']]></prompt>'."\n";
        if (is_array($this->answerList)) {
            foreach ($this->answerList as $current_answer) {
                $out .= '<simpleChoice identifier="answer_'.$current_answer['iid'].'" fixed="false">
                         <![CDATA['.formatExerciseQtiText($current_answer['answer']).']]>';
                if (isset($current_answer['comment']) && $current_answer['comment'] != '') {
                    $out .= '<feedbackInline identifier="answer_'.$current_answer['iid'].'">
                             <![CDATA['.formatExerciseQtiText($current_answer['comment']).']]>
                             </feedbackInline>';
                }
                $out .= '</simpleChoice>'."\n";
            }
        }
        $out .= '    </choiceInteraction>'."\n";

        return $out;
    }

    /**
     * Return the XML flow of answer ResponsesDeclaration.
     */
    public function imsExportResponsesDeclaration($questionIdent, Question $question = null)
    {
        $this->answerList = $this->getAnswersList(true);
        $type = $this->getQuestionType();
        if (in_array($type, [MCMA, MULTIPLE_ANSWER_DROPDOWN, MULTIPLE_ANSWER_DROPDOWN_COMBINATION])) {
            $cardinality = 'multiple';
        } else {
            $cardinality = 'single';
        }

        $out = '  <responseDeclaration identifier="'.$questionIdent.'" cardinality="'.$cardinality.'" baseType="identifier">'."\n";

        // Match the correct answers.
        if (is_array($this->answerList)) {
            $out .= '    <correctResponse>'."\n";
            foreach ($this->answerList as $current_answer) {
                if ($current_answer['correct']) {
                    $out .= '      <value>answer_'.$current_answer['iid'].'</value>'."\n";
                }
            }
            $out .= '    </correctResponse>'."\n";
        }

        // Add the grading
        if (is_array($this->answerList)) {
            $out .= '    <mapping';

            if (MULTIPLE_ANSWER_DROPDOWN_COMBINATION == $this->getQuestionType()) {
                $out .= ' defaultValue="'.$question->selectWeighting().'"';
            }

            $out .= '>'."\n";

            foreach ($this->answerList as $current_answer) {
                if (isset($current_answer['grade'])) {
                    $out .= ' <mapEntry mapKey="answer_'.$current_answer['iid'].'" mappedValue="'.$current_answer['grade'].'" />'."\n";
                }
            }
            $out .= '    </mapping>'."\n";
        }

        $out .= '  </responseDeclaration>'."\n";

        return $out;
    }
}

/**
 * Class.
 *
 * @package chamilo.exercise
 */
class ImsAnswerFillInBlanks extends Answer implements ImsAnswerInterface
{
    private $answerList = [];
    private $gradeList = [];

    /**
     * Export the text with missing words.
     *
     * @param string $questionIdent
     * @param string $questionStatment
     * @param string $questionDesc
     * @param string $questionMedia
     *
     * @return string
     */
    public function imsExportResponses($questionIdent, $questionStatment, $questionDesc = '', $questionMedia = '')
    {
        $this->answerList = $this->getAnswersList(true);
        $text = isset($this->answerText) ? $this->answerText : '';
        if (is_array($this->answerList)) {
            foreach ($this->answerList as $key => $answer) {
                $key = $answer['iid'];
                $answer = $answer['answer'];
                $len = api_strlen($answer);
                $text = str_replace('['.$answer.']', '<textEntryInteraction responseIdentifier="fill_'.$key.'" expectedLength="'.api_strlen($answer).'"/>', $text);
            }
        }

        return $text;
    }

    public function imsExportResponsesDeclaration($questionIdent, Question $question = null)
    {
        $this->answerList = $this->getAnswersList(true);
        $this->gradeList = $this->getGradesList();
        $out = '';
        if (is_array($this->answerList)) {
            foreach ($this->answerList as $answer) {
                $answerKey = $answer['iid'];
                $answer = $answer['answer'];
                $out .= '  <responseDeclaration identifier="fill_'.$answerKey.'" cardinality="single" baseType="identifier">'."\n";
                $out .= '    <correctResponse>'."\n";
                $out .= '      <value><![CDATA['.formatExerciseQtiText($answer).']]></value>'."\n";
                $out .= '    </correctResponse>'."\n";
                if (isset($this->gradeList[$answerKey])) {
                    $out .= '    <mapping>'."\n";
                    $out .= '      <mapEntry mapKey="'.$answer.'" mappedValue="'.$this->gradeList[$answerKey].'"/>'."\n";
                    $out .= '    </mapping>'."\n";
                }

                $out .= '  </responseDeclaration>'."\n";
            }
        }

        return $out;
    }
}

/**
 * Class.
 *
 * @package chamilo.exercise
 */
class ImsAnswerMatching extends Answer implements ImsAnswerInterface
{
    public $leftList = [];
    public $rightList = [];
    private $answerList = [];

    /**
     * Export the question part as a matrix-choice, with only one possible answer per line.
     *
     * @param string $questionIdent
     * @param string $questionStatment
     * @param string $questionDesc
     * @param string $questionMedia
     *
     * @return string
     */
    public function imsExportResponses($questionIdent, $questionStatment, $questionDesc = '', $questionMedia = '')
    {
        $this->answerList = $this->getAnswersList(true);
        $maxAssociation = max(count($this->leftList), count($this->rightList));

        $out = '<matchInteraction responseIdentifier="'.$questionIdent.'" maxAssociations="'.$maxAssociation.'">'."\n";
        $out .= $questionStatment;

        //add left column
        $out .= '  <simpleMatchSet>'."\n";
        if (is_array($this->leftList)) {
            foreach ($this->leftList as $leftKey => $leftElement) {
                $out .= '
                <simpleAssociableChoice identifier="left_'.$leftKey.'" >
                    <![CDATA['.formatExerciseQtiText($leftElement['answer']).']]>
                </simpleAssociableChoice>'."\n";
            }
        }

        $out .= '  </simpleMatchSet>'."\n";

        //add right column
        $out .= '  <simpleMatchSet>'."\n";
        $i = 0;

        if (is_array($this->rightList)) {
            foreach ($this->rightList as $rightKey => $rightElement) {
                $out .= '<simpleAssociableChoice identifier="right_'.$i.'" >
                        <![CDATA['.formatExerciseQtiText($rightElement['answer']).']]>
                        </simpleAssociableChoice>'."\n";
                $i++;
            }
        }
        $out .= '  </simpleMatchSet>'."\n";
        $out .= '</matchInteraction>'."\n";

        return $out;
    }

    public function imsExportResponsesDeclaration($questionIdent, Question $question = null)
    {
        $this->answerList = $this->getAnswersList(true);
        $out = '  <responseDeclaration identifier="'.$questionIdent.'" cardinality="single" baseType="identifier">'."\n";
        $out .= '    <correctResponse>'."\n";

        $gradeArray = [];
        if (isset($this->leftList) && is_array($this->leftList)) {
            foreach ($this->leftList as $leftKey => $leftElement) {
                $i = 0;
                foreach ($this->rightList as $rightKey => $rightElement) {
                    if (($leftElement['match'] == $rightElement['code'])) {
                        $out .= '      <value>left_'.$leftKey.' right_'.$i.'</value>'."\n";
                        $gradeArray['left_'.$leftKey.' right_'.$i] = $leftElement['grade'];
                    }
                    $i++;
                }
            }
        }
        $out .= '    </correctResponse>'."\n";

        if (is_array($gradeArray)) {
            $out .= '    <mapping>'."\n";
            foreach ($gradeArray as $gradeKey => $grade) {
                $out .= '          <mapEntry mapKey="'.$gradeKey.'" mappedValue="'.$grade.'"/>'."\n";
            }
            $out .= '    </mapping>'."\n";
        }

        $out .= '  </responseDeclaration>'."\n";

        return $out;
    }
}

/**
 * Class.
 *
 * @package chamilo.exercise
 */
class ImsAnswerHotspot extends Answer implements ImsAnswerInterface
{
    private $answerList = [];
    private $gradeList = [];

    /**
     * @todo update this to match hot spots instead of copying matching
     * Export the question part as a matrix-choice, with only one possible answer per line.
     *
     * @param string $questionIdent
     * @param string $questionStatment
     * @param string $questionDesc
     * @param string $questionMedia
     *
     * @return string
     */
    public function imsExportResponses($questionIdent, $questionStatment, $questionDesc = '', $questionMedia = '')
    {
        $this->answerList = $this->getAnswersList(true);
        $mediaFilePath = api_get_course_path().'/document/images/'.$questionMedia;
        $sysQuestionMediaPath = api_get_path(SYS_COURSE_PATH).$mediaFilePath;
        $questionMedia = api_get_path(WEB_COURSE_PATH).$mediaFilePath;
        $mimetype = mime_content_type($sysQuestionMediaPath);
        if (empty($mimetype)) {
            $mimetype = 'image/jpeg';
        }

        $text = '      <p>'.$questionStatment.'</p>'."\n";
        $text .= '      <graphicOrderInteraction responseIdentifier="hotspot_'.$questionIdent.'">'."\n";
        $text .= '        <prompt>'.$questionDesc.'</prompt>'."\n";
        $text .= '        <object type="'.$mimetype.'" width="250" height="230" data="'.$questionMedia.'">-</object>'."\n";
        if (is_array($this->answerList)) {
            foreach ($this->answerList as $key => $answer) {
                $key = $answer['iid'];
                $answerTxt = $answer['answer'];
                $len = api_strlen($answerTxt);
                //coords are transformed according to QTIv2 rules here: http://www.imsproject.org/question/qtiv2p1pd/imsqti_infov2p1pd.html#element10663
                $coords = '';
                $type = 'default';
                switch ($answer['hotspot_type']) {
                    case 'square':
                        $type = 'rect';
                        $res = [];
                        $coords = preg_match('/^\s*(\d+);(\d+)\|(\d+)\|(\d+)\s*$/', $answer['hotspot_coord'], $res);
                        $coords = $res[1].','.$res[2].','.((int) $res[1] + (int) $res[3]).",".((int) $res[2] + (int) $res[4]);
                        break;
                    case 'circle':
                        $type = 'circle';
                        $res = [];
                        $coords = preg_match('/^\s*(\d+);(\d+)\|(\d+)\|(\d+)\s*$/', $answer['hotspot_coord'], $res);
                        $coords = $res[1].','.$res[2].','.sqrt(pow(($res[1] - $res[3]), 2) + pow(($res[2] - $res[4])));
                        break;
                    case 'poly':
                        $type = 'poly';
                        $coords = str_replace([';', '|'], [',', ','], $answer['hotspot_coord']);
                        break;
                    case 'delineation':
                        $type = 'delineation';
                        $coords = str_replace([';', '|'], [',', ','], $answer['hotspot_coord']);
                        break;
                }
                $text .= '        <hotspotChoice shape="'.$type.'" coords="'.$coords.'" identifier="'.$key.'"/>'."\n";
            }
        }
        $text .= '      </graphicOrderInteraction>'."\n";

        return $text;
    }

    public function imsExportResponsesDeclaration($questionIdent, Question $question = null)
    {
        $this->answerList = $this->getAnswersList(true);
        $this->gradeList = $this->getGradesList();
        $out = '  <responseDeclaration identifier="hotspot_'.$questionIdent.'" cardinality="ordered" baseType="identifier">'."\n";
        if (is_array($this->answerList)) {
            $out .= '    <correctResponse>'."\n";
            foreach ($this->answerList as $answerKey => $answer) {
                $answerKey = $answer['iid'];
                $answer = $answer['answer'];
                $out .= '<value><![CDATA['.formatExerciseQtiText($answerKey).']]></value>';
            }
            $out .= '    </correctResponse>'."\n";
        }
        $out .= '  </responseDeclaration>'."\n";

        return $out;
    }
}

/**
 * Class.
 *
 * @package chamilo.exercise
 */
class ImsAnswerFree extends Answer implements ImsAnswerInterface
{
    /**
     * @todo implement
     * Export the question part as a matrix-choice, with only one possible answer per line.
     *
     * @param string $questionIdent
     * @param string $questionStatment
     * @param string $questionDesc
     * @param string $questionMedia
     *
     * @return string
     */
    public function imsExportResponses($questionIdent, $questionStatment, $questionDesc = '', $questionMedia = '')
    {
        $questionDesc = formatExerciseQtiText($questionDesc);

        return '<extendedTextInteraction responseIdentifier="'.$questionIdent.'" >
            <prompt>
            '.$questionDesc.'
            </prompt>
            </extendedTextInteraction>';
    }

    public function imsExportResponsesDeclaration($questionIdent, Question $question = null)
    {
        $out = '  <responseDeclaration identifier="'.$questionIdent.'" cardinality="single" baseType="string">';
        $out .= '<outcomeDeclaration identifier="SCORE" cardinality="single" baseType="float">
                <defaultValue><value>'.$question->weighting.'</value></defaultValue></outcomeDeclaration>';
        $out .= '  </responseDeclaration>'."\n";

        return $out;
    }
}
