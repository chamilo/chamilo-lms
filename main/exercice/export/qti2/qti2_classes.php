<?php // $Id: $
if ( count( get_included_files() ) == 1 ) die( '---' );
/**
 * @copyright (c) 2007 Dokeos
 * @copyright (c) 2001-2006 Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @author Claro Team <cvs@claroline.net>
 * @author Yannick Warnier <yannick.warnier@dokeos.com> - updated ImsAnswerHotspot to match QTI norms
 */
// answer types
define('UNIQUE_ANSWER',		1);
define('MCUA',				1);
define('TF',				1);
define('MULTIPLE_ANSWER',	2);
define('MCMA',				2);
define('FILL_IN_BLANKS',	3);
define('FIB',				3);
define('MATCHING',			4);
define('FREE_ANSWER', 		5);
define('HOTSPOT',			6);

if (!function_exists('mime_content_type')) {
	require_once api_get_path(LIBRARY_PATH).'document.lib.php';
	function mime_content_type($filename) {
		return DocumentManager::file_get_mime_type((string)$filename);
	}
}

require_once(api_get_path(SYS_CODE_PATH).'/exercice/answer.class.php');
require_once(api_get_path(SYS_CODE_PATH).'/exercice/exercise.class.php');
require_once(api_get_path(SYS_CODE_PATH).'/exercice/hotspot.class.php');
require_once(api_get_path(SYS_CODE_PATH).'/exercice/unique_answer.class.php');
require_once(api_get_path(SYS_CODE_PATH).'/exercice/multiple_answer.class.php');
//require_once(api_get_path(SYS_CODE_PATH).'/exercice/multiple_answer_combination.class.php');
require_once(api_get_path(SYS_CODE_PATH).'/exercice/matching.class.php');
require_once(api_get_path(SYS_CODE_PATH).'/exercice/freeanswer.class.php');
require_once(api_get_path(SYS_CODE_PATH).'/exercice/fill_blanks.class.php');
//include_once $path . '/../../lib/answer_multiplechoice.class.php';
//include_once $path . '/../../lib/answer_truefalse.class.php';
//include_once $path . '/../../lib/answer_fib.class.php';
//include_once $path . '/../../lib/answer_matching.class.php';

class Ims2Question extends Question
{
    /**
     * Include the correct answer class and create answer
     */
    function setAnswer()
    {
        switch($this->type)
        {
            case MCUA :
                $answer = new ImsAnswerMultipleChoice($this->id);
            	return $answer;
            case MCMA :
                $answer = new ImsAnswerMultipleChoice($this->id);
            	return $answer;
            case TF :
                $answer = new ImsAnswerMultipleChoice($this->id);
            	return $answer;
            case FIB :
                $answer = new ImsAnswerFillInBlanks($this->id);
            	return $answer;
            case MATCHING :
                $answer = new ImsAnswerMatching($this->id);
            	return $answer;
            case FREE_ANSWER :
            	$answer = new ImsAnswerFree($this->id);
            	return $answer;
            case HOTSPOT :
            	$answer = new ImsAnswerHotspot($this->id);
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

class ImsAnswerMultipleChoice extends Answer
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

class ImsAnswerFillInBlanks extends Answer
{
    /**
     * Export the text with missing words.
     *
     *
     */
    function imsExportResponses($questionIdent, $questionStatment)
    {
        global $charset;
		$this->answerList = $this->getAnswersList(true);

        //switch ($this->type)
        //{
        //    case TEXTFIELD_FILL :
        //    {
        		$text = '';
                $text .= $this->answerText;
				if (is_array($this->answerList)) {
	                foreach ($this->answerList as $key=>$answer) {
	                	$key = $answer['id'];
	                	$answer = $answer['answer'];
	                	$len = api_strlen($answer);
	                    $text = str_replace('['.$answer.']','<textEntryInteraction responseIdentifier="fill_'.$key.'" expectedLength="'.api_strlen($answer).'"/>', $text);
	                }
				}
                $out = $text;
        //    }
        //    break;

            /*
            case LISTBOX_FILL :
            {
                $text = $this->answerText;

                foreach ($this->answerList as $answerKey=>$answer)
                {

                    //build inlinechoice list

                    $inlineChoiceList = '';

                    //1-start interaction tag

                    $inlineChoiceList .= '<inlineChoiceInteraction responseIdentifier="fill_'.$answerKey.'" >'. "\n";

                    //2- add wrong answer array

                    foreach ($this->wrongAnswerList as $choiceKey=>$wrongAnswer)
                    {
                        $inlineChoiceList .= '  <inlineChoice identifier="choice_w_'.$answerKey.'_'.$choiceKey.'">'.$wrongAnswer.'</inlineChoice>'. "\n";
                    }

                    //3- add correct answers array
                    foreach ($this->answerList as $choiceKey=>$correctAnswer)
                    {
                        $inlineChoiceList .= '  <inlineChoice identifier="choice_c_'.$answerKey.'_'.$choiceKey.'">'.$correctAnswer.'</inlineChoice>'. "\n";
                    }

                    //4- finish interaction tag

                    $inlineChoiceList .= '</inlineChoiceInteraction>';

                    $text = str_replace('['.$answer.']',$inlineChoiceList, $text);
                }
                $out = $text;

            }
            break;
            */
        //}

        return $out;

    }

    /**
     *
     */
    function imsExportResponsesDeclaration($questionIdent)
    {

		$this->answerList = $this->getAnswersList(true);
		$this->gradeList = $this->getGradesList();
        $out = '';
		if (is_array($this->answerList)) {
	        foreach ($this->answerList as $answerKey=>$answer) {
	        	
	        	$answerKey = $answer['id'];
	        	$answer = $answer['answer'];
	            $out .= '  <responseDeclaration identifier="fill_' . $answerKey . '" cardinality="single" baseType="identifier">' . "\n";
	            $out .= '    <correctResponse>'. "\n";

	            //if ($this->type==TEXTFIELD_FILL)
	            //{
	                $out .= '      <value>'.$answer.'</value>'. "\n";
	            //}
	            /*
	            else
	            {
	                //find correct answer key to apply in manifest and output it

	                foreach ($this->answerList as $choiceKey=>$correctAnswer)
	                {
	                    if ($correctAnswer==$answer)
	                    {
	                        $out .= '      <value>choice_c_'.$answerKey.'_'.$choiceKey.'</value>'. "\n";
	                    }
	                }
	            }
	            */
	            $out .= '    </correctResponse>'. "\n";

	            if (isset($this->gradeList[$answerKey]))
	            {
	                $out .= '    <mapping>'. "\n";
	                $out .= '      <mapEntry mapKey="'.$answer.'" mappedValue="'.$this->gradeList[$answerKey].'"/>'. "\n";
	                $out .= '    </mapping>'. "\n";
	            }

	            $out .= '  </responseDeclaration>'. "\n";
	        }
		}

       return $out;
    }
}

class ImsAnswerMatching extends Answer
{
    /**
     * Export the question part as a matrix-choice, with only one possible answer per line.
     */
    function imsExportResponses($questionIdent, $questionStatment)
    {
		$this->answerList = $this->getAnswersList(true);
		$maxAssociation = max(count($this->leftList), count($this->rightList));

        $out = "";

        $out .= '<matchInteraction responseIdentifier="' . $questionIdent . '" maxAssociations="'. $maxAssociation .'">'. "\n";
        $out .= $questionStatment;

        //add left column

        $out .= '  <simpleMatchSet>'. "\n";
		if (is_array($this->leftList)) {
	        foreach ($this->leftList as $leftKey=>$leftElement)
	        {
	            $out .= '    <simpleAssociableChoice identifier="left_'.$leftKey.'" >'. $leftElement['answer'] .'</simpleAssociableChoice>'. "\n";
	        }
    	}

        $out .= '  </simpleMatchSet>'. "\n";

        //add right column

        $out .= '  <simpleMatchSet>'. "\n";

        $i = 0;

		if (is_array($this->rightList)) {
	        foreach($this->rightList as $rightKey=>$rightElement)
	        {
	            $out .= '    <simpleAssociableChoice identifier="right_'.$i.'" >'. $rightElement['answer'] .'</simpleAssociableChoice>'. "\n";
	            $i++;
	        }
		}
        $out .= '  </simpleMatchSet>'. "\n";

        $out .= '</matchInteraction>'. "\n";

        return $out;
    }

    /**
     *
     */
    function imsExportResponsesDeclaration($questionIdent)
    {
		$this->answerList = $this->getAnswersList(true);
        $out =  '  <responseDeclaration identifier="' . $questionIdent . '" cardinality="single" baseType="identifier">' . "\n";
        $out .= '    <correctResponse>' . "\n";

        $gradeArray = array();
		if (is_array($this->leftList)) {
	        foreach ($this->leftList as $leftKey=>$leftElement)
	        {
	            $i=0;
	            foreach ($this->rightList as $rightKey=>$rightElement)
	            {
	                if( ($leftElement['match'] == $rightElement['code']))
	                {
	                    $out .= '      <value>left_' . $leftKey . ' right_'.$i.'</value>'. "\n";

	                    $gradeArray['left_' . $leftKey . ' right_'.$i] = $leftElement['grade'];
	                }
	                $i++;
	            }
	        }
		}
        $out .= '    </correctResponse>'. "\n";
        $out .= '    <mapping>' . "\n";
        if (is_array($gradeArray)) {
	        foreach ($gradeArray as $gradeKey=>$grade)
	        {
	            $out .= '          <mapEntry mapKey="'.$gradeKey.'" mappedValue="'.$grade.'"/>' . "\n";
	        }
        }
        $out .= '    </mapping>' . "\n";
        $out .= '  </responseDeclaration>'. "\n";

        return $out;
    }

}

class ImsAnswerHotspot extends Answer
{
    /**
     * TODO update this to match hotspots instead of copying matching
     * Export the question part as a matrix-choice, with only one possible answer per line.
     */
   	function imsExportResponses($questionIdent, $questionStatment, $questionDesc='', $questionMedia='')
    {
        global $charset;
		$this->answerList = $this->getAnswersList(true);
		$questionMedia = api_get_path(WEB_COURSE_PATH).api_get_course_path().'/document/images/'.$questionMedia;
		$mimetype = mime_content_type($questionMedia);
		if(empty($mimetype)){
			$mimetype = 'image/jpeg';
		}

		$text = '      <p>'.$questionStatment.'</p>'."\n";
		$text .= '      <graphicOrderInteraction responseIdentifier="hotspot_'.$questionIdent.'">'."\n";
		$text .= '        <prompt>'.$questionDesc.'</prompt>'."\n";
		$text .= '        <object type="'.$mimetype.'" width="250" height="230" data="'.$questionMedia.'">-</object>'."\n";
        if (is_array($this->answerList)) {
	        foreach ($this->answerList as $key=>$answer)
	        {
	        	$key = $answer['id'];
	        	$answerTxt = $answer['answer'];
	        	$len = api_strlen($answerTxt);
	        	//coords are transformed according to QTIv2 rules here: http://www.imsproject.org/question/qtiv2p1pd/imsqti_infov2p1pd.html#element10663
	        	$coords = '';
	        	$type = 'default';
	        	switch($answer['hotspot_type']){
	        		case 'square':
	        			$type = 'rect';
						$res = array();
						$coords = preg_match('/^\s*(\d+);(\d+)\|(\d+)\|(\d+)\s*$/',$answer['hotspot_coord'],$res);
						$coords = $res[1].','.$res[2].','.((int)$res[1]+(int)$res[3]).",".((int)$res[2]+(int)$res[4]);
	        			break;
	        		case 'circle':
	        			$type = 'circle';
			 			$res = array();
						$coords = preg_match('/^\s*(\d+);(\d+)\|(\d+)\|(\d+)\s*$/',$answer['hotspot_coord'],$res);
						$coords = $res[1].','.$res[2].','.sqrt(pow(($res[1]-$res[3]),2)+pow(($res[2]-$res[4])));
	        			break;
	        		case 'poly':
	        			$type = 'poly';
						$coords = str_replace(array(';','|'),array(',',','),$answer['hotspot_coord']);
	        			break;
	        		 case 'delineation' :
	        			$type = 'delineation';
						$coords = str_replace(array(';','|'),array(',',','),$answer['hotspot_coord']);
	        			break;
	        	}
	            $text .= '        <hotspotChoice shape="'.$type.'" coords="'.$coords.'" identifier="'.$key.'"/>'."\n";
	        }
        }
        $text .= '      </graphicOrderInteraction>'."\n";
        $out = $text;


        return $out;

    }

    /**
     *
     */
    function imsExportResponsesDeclaration($questionIdent)
    {

		$this->answerList = $this->getAnswersList(true);
		$this->gradeList = $this->getGradesList();
        $out = '';
        $out .= '  <responseDeclaration identifier="hotspot_'.$questionIdent.'" cardinality="ordered" baseType="identifier">' . "\n";
        $out .= '    <correctResponse>'. "\n";

		if (is_array($this->answerList)) {
	        foreach ($this->answerList as $answerKey=>$answer)
	        {
	        	$answerKey = $answer['id'];
	        	$answer = $answer['answer'];
	            $out .= '      <value>'.$answerKey.'</value>'. "\n";

	        }
		}
        $out .= '    </correctResponse>'. "\n";
        $out .= '  </responseDeclaration>'. "\n";

       return $out;
    }
}

class ImsAnswerFree extends Answer
{
    /**
     * TODO implement
     * Export the question part as a matrix-choice, with only one possible answer per line.
     */
   	function imsExportResponses($questionIdent, $questionStatment, $questionDesc='', $questionMedia='')
	{
		return '';
	}
    /**
     *
     */
    function imsExportResponsesDeclaration($questionIdent)
    {
    	return '';
    }
}
?>
