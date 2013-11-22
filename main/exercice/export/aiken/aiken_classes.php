<?php // $Id: $
/* For licensing terms, see /license.txt */
/**
 * @author Claro Team <cvs@claroline.net>
 * @author Yannick Warnier <yannick.warnier@beeznest.com> - updated ImsAnswerHotspot to match QTI norms
 * @author César Perales <cesar.perales@gmail.com> Updated function names and import files for Aiken format support
 * @package chamilo.exercise
 */
/**
 * Code
 */
if ( count( get_included_files() ) == 1 ) die( '---' );

if (!function_exists('mime_content_type')) {
	require_once api_get_path(LIBRARY_PATH).'document.lib.php';
	function mime_content_type($filename) {
		return DocumentManager::file_get_mime_type((string)$filename);
	}
}

require_once(api_get_path(SYS_CODE_PATH).'/exercice/answer.class.php');
require_once(api_get_path(SYS_CODE_PATH).'/exercice/exercise.class.php');
require_once(api_get_path(SYS_CODE_PATH).'/exercice/question.class.php');
//require_once(api_get_path(SYS_CODE_PATH).'/exercice/hotspot.class.php');
require_once(api_get_path(SYS_CODE_PATH).'/exercice/unique_answer.class.php');
//require_once(api_get_path(SYS_CODE_PATH).'/exercice/multiple_answer.class.php');
//require_once(api_get_path(SYS_CODE_PATH).'/exercice/multiple_answer_combination.class.php');
//require_once(api_get_path(SYS_CODE_PATH).'/exercice/matching.class.php');
//require_once(api_get_path(SYS_CODE_PATH).'/exercice/freeanswer.class.php');
//require_once(api_get_path(SYS_CODE_PATH).'/exercice/fill_blanks.class.php');
//include_once $path . '/../../lib/answer_multiplechoice.class.php';
//include_once $path . '/../../lib/answer_truefalse.class.php';
//include_once $path . '/../../lib/answer_fib.class.php';
//include_once $path . '/../../lib/answer_matching.class.php';
/**
 *
 * @package chamilo.exercise
 */
class Aiken2Question extends Question
{
    /**
     * Include the correct answer class and create answer
     */
    function setAnswer()
    {
        switch($this->type)
        {
            case MCUA :
                $answer = new AikenAnswerMultipleChoice($this->id);
            	return $answer;
            default :
                $answer = null;
                break;
        }
        return $answer;
    }
    function createAnswersForm($form)
    {
    	return true;
    }
    function processAnswersCreation($form)
    {
    	return true;
    }
}


/**
 * Class
 * @package chamilo.exercise
 */
class AikenAnswerMultipleChoice extends Answer
{
    /**
     * Return the XML flow for the possible answers.
     *
     */
    function imsExportResponses($questionIdent, $questionStatment)
    {
        $this->answerList = $this->getAnswersList(true);
        $out  = '    <choiceInteraction responseIdentifier="' . $questionIdent . '" >' . "\n";
        $out .= '      <prompt> ' . $questionStatment . ' </prompt>'. "\n";
        if (is_array($this->answerList)) {
            foreach ($this->answerList as $current_answer) {
                
                
                $out .= '      <simpleChoice identifier="answer_' . $current_answer['id'] . '" fixed="false">' . $current_answer['answer'];
                if (isset($current_answer['comment']) && $current_answer['comment'] != '')
                {
                    $out .= '<feedbackInline identifier="answer_' . $current_answer['id'] . '">' . $current_answer['comment'] . '</feedbackInline>';
                }
                $out .= '</simpleChoice>'. "\n";
            }
        }
        $out .= '    </choiceInteraction>'. "\n";
        return $out;
    }

    /**
     * Return the XML flow of answer ResponsesDeclaration
     *
     */
    function imsExportResponsesDeclaration($questionIdent)
    {
        $this->answerList = $this->getAnswersList(true);
        $type = $this->getQuestionType();
        if ($type == MCMA)  $cardinality = 'multiple'; else $cardinality = 'single';

        $out = '  <responseDeclaration identifier="' . $questionIdent . '" cardinality="' . $cardinality . '" baseType="identifier">' . "\n";

        //Match the correct answers

        $out .= '    <correctResponse>'. "\n";
        if (is_array($this->answerList)) {
            foreach($this->answerList as $current_answer) {
                if ($current_answer['correct'])
                {
                    $out .= '      <value>answer_'. $current_answer['id'] .'</value>'. "\n";
                }
            }
        }
        $out .= '    </correctResponse>'. "\n";

        //Add the grading

        $out .= '    <mapping>'. "\n";
        if (is_array($this->answerList)) {
            foreach($this->answerList as $current_answer)
            {
                if (isset($current_answer['grade']))
                {
                    $out .= '      <mapEntry mapKey="answer_'. $current_answer['id'] .'" mappedValue="'.$current_answer['grade'].'" />'. "\n";
                }
            }
        }
        $out .= '    </mapping>'. "\n";

        $out .= '  </responseDeclaration>'. "\n";

        return $out;
    }
}