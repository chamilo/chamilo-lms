<?php // $Id: scorm_classes.php,v 1.2 2006/07/06 18:50:49 moosh Exp $
/* For licensing terms, see /license.txt */
/**
 * @author Claro Team <cvs@claroline.net>
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 * @package chamilo.exercise.scorm
 */
/**
 * Code
 */
if ( count( get_included_files() ) == 1 ) die( '---' );
require_once(api_get_path(SYS_CODE_PATH).'exercice/exercise.class.php');
require_once(api_get_path(SYS_CODE_PATH).'exercice/question.class.php');
require_once(api_get_path(SYS_CODE_PATH).'exercice/answer.class.php');
require_once(api_get_path(SYS_CODE_PATH).'exercice/unique_answer.class.php');
require_once(api_get_path(SYS_CODE_PATH).'exercice/multiple_answer.class.php');
require_once(api_get_path(SYS_CODE_PATH).'exercice/multiple_answer_combination.class.php');
require_once(api_get_path(SYS_CODE_PATH).'exercice/fill_blanks.class.php');
require_once(api_get_path(SYS_CODE_PATH).'exercice/freeanswer.class.php');
require_once(api_get_path(SYS_CODE_PATH).'exercice/hotspot.class.php');
require_once(api_get_path(SYS_CODE_PATH).'exercice/matching.class.php');
require_once(api_get_path(SYS_CODE_PATH).'exercice/hotspot.class.php');

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

define('HOT_SPOT_ORDER', 	7);
define('HOT_SPOT_DELINEATION', 		8);
define('MULTIPLE_ANSWER_COMBINATION', 	9);
define('ORAL_EXPRESSION', 		13);


/**
 * The ScormQuestion class is a gateway to getting the answers exported
 * (the question is just an HTML text, while the answers are the most important).
 * It is important to note that the SCORM export process is done in two parts.
 * First, the HTML part (which is the presentation), and second the JavaScript
 * part (the process).
 * The two bits are separate to allow for a one-big-javascript and a one-big-html
 * files to be built. Each export function thus returns an array of HTML+JS
 * @package chamilo.exercise.scorm
 */
class ScormQuestion extends Question
{
	/**
	 * Include the correct answer class and create answer
	 */
	function setAnswer()
	{
		switch($this->type)
		{
			case MCUA :
				$this->answer = new ScormAnswerMultipleChoice($this->id, false);
                $this->answer->questionJSId = $this->js_id;
				break;
			case MCMA :
				$this->answer = new ScormAnswerMultipleChoice($this->id, true);
                $this->answer->questionJSId = $this->js_id;
				break;
			case TF :
				$this->answer = new ScormAnswerTrueFalse($this->id);
                $this->answer->questionJSId = $this->js_id;
				break;
			case FIB :
				$this->answer = new ScormAnswerFillInBlanks($this->id);
                $this->answer->questionJSId = $this->js_id;
				break;
			case MATCHING :
				$this->answer = new ScormAnswerMatching($this->id);
                $this->answer->questionJSId = $this->js_id;
				break;
			case FREE_ANSWER :
				$this->answer = new ScormAnswerFree($this->id);
                $this->answer->questionJSId = $this->js_id;
				break;
			case HOTSPOT:
				$this->answer = new ScormAnswerHotspot($this->id);
                $this->answer->questionJSId = $this->js_id;
				break;
			case MULTIPLE_ANSWER_COMBINATION:
				$this->answer = new ScormAnswerMultipleChoice($this->id, false);
                $this->answer->questionJSId = $this->js_id;
				break;
            case HOT_SPOT_ORDER:
				$this->answer = new ScormAnswerHotspot($this->id); 
                $this->answer->questionJSId = $this->js_id;
				break;
			case HOT_SPOT_DELINEATION:
				$this->answer = new ScormAnswerHotspot($this->id); 
                $this->answer->questionJSId = $this->js_id;
				break;
			default :
				$this->answer = null;
                $this->answer->questionJSId = $this->js_id;
				break;
		}

		return true;
	}

	function export()
	{
		$html = $this->getQuestionHTML();
		$js   = $this->getQuestionJS();

		if( is_object($this->answer) )
		{
			list($js2,$html2) = $this->answer->export();
			$js .= $js2;
			$html .= $html2;
		}
		return array($js,$html);

	}
    function createAnswersForm($form)
    {
    	return true;
    }
    function processAnswersCreation($form)
    {
    	return true;
    }
    /**
     * Returns an HTML-formatted question
     */
    function getQuestionHTML()
    {
    	$title			= $this->selectTitle();
		$description	= $this->selectDescription();
		$cols = 2;
		$s='<tr>' .
			'<td colspan="'.$cols.'" id="question_'.$this->id.'_title" valign="middle" style="background-color:#d6d6d6;">' . "\n" .
		   	text_filter($title).
		   	'</td>' . "\n" .
		   	'</tr>' . "\n" .
		   	'<tr>' . "\n" .
		   	'<td valign="top" colspan="'.$cols.'">' . "\n" .
		   	'<i>'.text_filter($description).'</i>' . "\n" .
		   	'</td>' . "\n" .
		   	'</tr>' . "\n";
		return $s;
    }
    /**
     * Return the JavaScript code bound to the question
     */
    function getQuestionJS()
    {
    	//$id = $this->id;
    	$w = $this->selectWeighting();
    	$s = 'questions.push('.$this->js_id.');'."\n";
    	if($this->type == FREE_ANSWER or $this->type == HOTSPOT)
    	{ //put the max score to 0 to avoid discounting the points of
    	  //non-exported quiz types in the SCORM
    		$w=0;
    	}
    	$s .= 'questions_score_max['.$this->js_id.'] = '.$w.";\n";
    	return $s;
    }
}

/**
 * This class handles the export to SCORM of a multiple choice question
 * (be it single answer or multiple answers)
 * @package chamilo.exercise.scorm
 */
class ScormAnswerMultipleChoice extends Answer
{
	/**
	 * Return HTML code for possible answers
     */
	function export()
	{
		$html = '';
    	$js   = '';
    	$html = '<tr><td colspan="2"><table width="100%">' . "\n";
		$type = $this->getQuestionType();
		$jstmpw = 'questions_answers_ponderation['.$this->questionJSId.'] = new Array();'."\n";
		$jstmpw .= 'questions_answers_ponderation['.$this->questionJSId.'][0] = 0;'."\n";

		//not sure if we are going to export also the MULTIPLE_ANSWER_COMBINATION to SCORM
        //if ($type == MCMA  || $type == MULTIPLE_ANSWER_COMBINATION ) {
		if ($type == MCMA ) {
        	//$questionTypeLang = get_lang('MultipleChoiceMultipleAnswers');
        	$id = 1;
        	$jstmp = '';
        	$jstmpc = '';
			foreach( $this->answer as $i => $answer )
			{
				$identifier = 'question_'.$this->questionJSId.'_multiple_'.$i;
				$html .=
		    		'<tr>' . "\n"
				.	'<td align="center" width="5%">' . "\n"
		    	.	'<input name="'.$identifier.'" id="'.$identifier.'" value="'.$i.'" type="checkbox" />' . "\n"
		    	.	'</td>' . "\n"
		    	.	'<td width="95%">' . "\n"
		    	.	'<label for="'.$identifier.'">' . $this->answer[$i] . '</label>' . "\n"
		    	.	'</td>' . "\n"
		    	.	'</tr>' . "\n\n";
		    	$jstmp .= $i.',';
		    	if($this->correct[$i])
		    	{
		    		$jstmpc .= $i.',';
		    	}
		    	$jstmpw .= 'questions_answers_ponderation['.$this->questionJSId.']['.$i.'] = '.$this->weighting[$i].";\n";
		    	$id++;
			}
			$js .= 'questions_answers['.$this->questionJSId.'] = new Array('.substr($jstmp,0,-1).');'."\n";
	    	$js .= 'questions_answers_correct['.$this->questionJSId.'] = new Array('.substr($jstmpc,0,-1).');'."\n";
	    	if ($type == MCMA) {
	    		$js .= 'questions_types['.$this->questionJSId.'] = \'mcma\';'."\n";
	    	} else {
	    		$js .= 'questions_types['.$this->questionJSId.'] = \'exact\';'."\n";
	    	}
	    	$js .= $jstmpw;
        } elseif ($type == MULTIPLE_ANSWER_COMBINATION) {
        		//To this items we show the ThisItemIsNotExportable
        	    $qId = $this->questionJSId;
		    	$js = '';
		    	$html = '<tr><td colspan="2"><table width="100%">' . "\n";
				// some javascript must be added for that kind of questions
				$html .= '<tr>' . "\n"
						.	'<td>' . "\n"
				    	. '<textarea name="question_'.$qId.'_free" id="question_'.$qId.'_exact" rows="20" cols="100"></textarea>' . "\n"
				    	.	'</td>' . "\n"
				    	.	'</tr>' . "\n";
				$html .= '</table></td></tr>' . "\n";
				// currently the exact answers cannot be displayed, so ignore the textarea
				$html = '<tr><td colspan="2">'.get_lang('ThisItemIsNotExportable').'</td></tr>';
				$js .= 'questions_answers['.$this->questionJSId.'] = new Array();'."\n";
		    	$js .= 'questions_answers_correct['.$this->questionJSId.'] = new Array();'."\n";
		    	$js .= 'questions_types['.$this->questionJSId.'] = \'exact\';'."\n";
				$jstmpw = 'questions_answers_ponderation['.$this->questionJSId.'] = new Array();'."\n";
				$jstmpw .= 'questions_answers_ponderation['.$this->questionJSId.'][0] = 0;'."\n";
		    	$jstmpw .= 'questions_answers_ponderation['.$this->questionJSId.'][1] = 0;'.";\n";
		    	$js .= $jstmpw;
		        return array($js,$html);
        } else {
        	//$questionTypeLang = get_lang('MultipleChoiceUniqueAnswer');
        	$id = 1;
        	$jstmp = '';
        	$jstmpc = '';
			foreach( $this->answer as $i => $answer )
			{
	        	$identifier = 'question_'.$this->questionJSId.'_unique_'.$i;
	        	$identifier_name = 'question_'.$this->questionJSId.'_unique_answer';
				$html .=
		    		'<tr>' . "\n"
				.	'<td align="center" width="5%">' . "\n"
		    	.	'<input name="'.$identifier_name.'" id="'.$identifier.'" value="'.$i.'" type="radio"/>' . "\n"
		    	.	'</td>' . "\n"
		    	.	'<td width="95%">' . "\n"
		    	.	'<label for="'.$identifier.'">' . $this->answer[$i] . '</label>' . "\n"
		    	.	'</td>' . "\n"
		    	.	'</tr>' . "\n\n";
		    	$jstmp .= $i.',';
		    	if($this->correct[$i])
		    	{
		    		$jstmpc .= $i;
		    	}
		    	$jstmpw .= 'questions_answers_ponderation['.$this->questionJSId.']['.$i.'] = '.$this->weighting[$i].";\n";
		    	$id++;
			}
			$js .= 'questions_answers['.$this->questionJSId.'] = new Array('.substr($jstmp,0,-1).');'."\n";
	    	$js .= 'questions_answers_correct['.$this->questionJSId.'] = '.$jstmpc.';'."\n";
	    	$js .= 'questions_types['.$this->questionJSId.'] = \'mcua\';'."\n";
			$js .= $jstmpw;
        }
		$html .= '</table></td></tr>' . "\n";
        return array($js,$html);
    }
}

/**
 * This class handles the SCORM export of true/false questions
 * @package chamilo.exercise.scorm
 */
class ScormAnswerTrueFalse extends Answer
{
	/**
     * Return the XML flow for the possible answers.
     * That's one <response_lid>, containing several <flow_label>
     *
     * @author Amand Tihon <amand@alrj.org>
     */
    function export()
    {
    	$js = '';
    	$html = '<tr><td colspan="2"><table width="100%">';
		$identifier = 'question_'.$this->questionJSId.'_tf';
		$identifier_true  = $identifier.'_true';
		$identifier_false = $identifier.'_false';
		$html .=
    		'<tr>' . "\n"
		.	'<td align="center" width="5%">' . "\n"
    	.	'<input name="'.$identifier_true.'" id="'.$identifier_true.'" value="'.$this->trueGrade.'" type="radio" '
		.		'/>' . "\n"
    	.	'</td>' . "\n"
    	.	'<td width="95%">' . "\n"
    	.	'<label for="'.$identifier_true.'">' . get_lang('True') . '</label>' . "\n"
    	.	'</td>' . "\n"
    	.	'</tr>' . "\n\n";
    	$html .=
			'<tr>' . "\n"
		.	'<td align="center" width="5%">' . "\n"
		.	'<input name="'.$identifier_false.'" id="'.$identifier_false.'" value="'.$this->falseGrade.'" type="radio" '
		.		'/>' . "\n"
		.	'</td>' . "\n"
		.	'<td width="95%">' . "\n"
		.	'<label for="'.$identifier_false.'">' . get_lang('False') . '</label>' . "\n"
		.	'</td>' . "\n"
		.	'</tr>' . "\n\n";
		$html .= '</table></td></tr>' . "\n";
		$js .= 'questions_answers['.$this->questionJSId.'] = new Array(\'true\',\'false\');'."\n";
    	$js .= 'questions_types['.$this->questionJSId.'] = \'tf\';'."\n";
		if($this->response == 'TRUE')
		{
	    	$js .= 'questions_answers_correct['.$this->questionJSId.'] = new Array(\'true\');'."\n";
		}
		else
		{
	    	$js .= 'questions_answers_correct['.$this->questionJSId.'] = new Array(\'false\');'."\n";
		}
		$jstmpw .= 'questions_answers_ponderation['.$this->questionJSId.'] = new Array();'."\n";
		$jstmpw .= 'questions_answers_ponderation['.$this->questionJSId.'][0] = 0;'."\n";
    	$jstmpw .= 'questions_answers_ponderation['.$this->questionJSId.'][1] = '.$this->weighting[1].";\n";
    	$js .= $jstmpw;
        return array($js,$html);
    }
}

/**
 * This class handles the SCORM export of fill-in-the-blanks questions
 * @package chamilo.exercise.scorm
 */
class ScormAnswerFillInBlanks extends Answer
{
	/**
     * Export the text with missing words.
     *
     * As a side effect, it stores two lists in the class :
     * the missing words and their respective weightings.
     */
    function export()
    {
    	global $charset;
    	$js = '';
    	$html = '<tr><td colspan="2"><table width="100%">' . "\n";
		// get all enclosed answers
		$blankList = array();
		// build replacement
		$replacementList = array();
		foreach( $this->answer as $i => $answer )
		{
			$blankList[] = '['.$answer.']';
		}
		$answerCount = count($blankList);


		// splits text and weightings that are joined with the character '::'
		list($answer,$weight)=explode('::',$answer);
		$weights = explode(',',$weight);
		// because [] is parsed here we follow this procedure:
		// 1. find everything between the [ and ] tags
		$i=1;
		$jstmp = '';
		$jstmpc = '';
		$jstmpw = 'questions_answers_ponderation['.$this->questionJSId.'] = new Array();'."\n";
		$jstmpw .= 'questions_answers_ponderation['.$this->questionJSId.'][0] = 0;'."\n";
		$startlocations=api_strpos($answer,'[');
		$endlocations=api_strpos($answer,']');
		while($startlocations !== false && $endlocations !== false)
		{
			$texstring=api_substr($answer,$startlocations,($endlocations-$startlocations)+1);
			$answer = api_substr_replace($answer,'<input type="text" name="question_'.$this->questionJSId.'_fib_'.$i.'" id="question_'.$this->questionJSId.'_fib_'.$i.'" size="10" value="" />',$startlocations,($endlocations-$startlocations)+1);
			$jstmp .= $i.',';
			$jstmpc .= "'".api_htmlentities(api_substr($texstring,1,-1),ENT_QUOTES,$charset)."',";
				$my_weight=explode('@',$weights[$i-1]);
				if (count($my_weight)==2) {
					$weight_db=$my_weight[0];
				} else {
					$weight_db=$my_weight[0];
				}
	    	$jstmpw .= 'questions_answers_ponderation['.$this->questionJSId.']['.$i.'] = '.$weight_db.";\n";
			$i++;
			$startlocations=api_strpos($answer,'[');
			$endlocations=api_strpos($answer,']');
		}

		$html .= 	'<tr>' . "\n"
				.	'<td>' . "\n"
		    	.	$answer  . "\n"
	    		.	'</td>' . "\n"
	    		.	'</tr>' . "\n";
		$html .= '</table></td></tr>' . "\n";
		$js .= 'questions_answers['.$this->questionJSId.'] = new Array('.api_substr($jstmp,0,-1).');'."\n";
    	$js .= 'questions_answers_correct['.$this->questionJSId.'] = new Array('.api_substr($jstmpc,0,-1).');'."\n";
    	$js .= 'questions_types['.$this->questionJSId.'] = \'fib\';'."\n";
    	$js .= $jstmpw;
        return array($js,$html);
    }

}

/**
 * This class handles the SCORM export of matching questions
 * @package chamilo.exercise.scorm
 */
class ScormAnswerMatching extends Answer
{
	/**
     * Export the question part as a matrix-choice, with only one possible answer per line.
     * @author Amand Tihon <amand@alrj.org>
     */
    function export()
    {
    	$js = '';
    	$html = '<tr><td colspan="2"><table width="100%">' . "\n";
  		// prepare list of right proposition to allow
		// - easiest display
		// - easiest randomisation if needed one day
		// (here I use array_values to change array keys from $code1 $code2 ... to 0 1 ...)
		if (is_array($this->rightList)) {
			$displayedRightList = array_values($this->rightList);
		}
		// get max length of displayed array
		$arrayLength = max( count($this->leftList), count($this->rightList) );

		$nbrAnswers=$this->selectNbrAnswers();
		$cpt1='A';
		$cpt2=1;
		$Select=array();
		$qId = $this->questionJSId;
		$s = '';
		$jstmp = '';
		$jstmpc = '';
			$jstmpw = 'questions_answers_ponderation['.$this->questionJSId.'] = new Array();'."\n";
			$jstmpw .= 'questions_answers_ponderation['.$this->questionJSId.'][0] = 0;'."\n";
		for($answerId=1;$answerId <= $nbrAnswers;$answerId++)
		{
			$identifier = 'question_'.$qId.'_matching_';
			$answer=$this->selectAnswer($answerId);
			$answerCorrect=$this->isCorrect($answerId);
			$weight=$this->selectWeighting($answerId);
			$jstmp .= $answerId.',';

			if(!$answerCorrect)
			{
				// options (A, B, C, ...) that will be put into the list-box
				$Select[$answerId]['Lettre']=$cpt1;
				// answers that will be shown at the right side
				$answer = text_filter($answer);
				$Select[$answerId]['Reponse']=$answer;
				$cpt1++;
			}
			else
			{
				$s.='<tr>'."\n";
				$s.='<td width="40%" valign="top">'."\n".'<b>'.$cpt2.'</b>.&nbsp;'.$answer."\n</td>\n";
				$s.='<td width="20%" align="center">&nbsp;&nbsp;<select name="'.$identifier.$cpt2.'" id="'.$identifier.$cpt2.'">';
				$s.=' <option value="0">--</option>';
	            // fills the list-box
	            foreach($Select as $key=>$val)
	            {
					$s.='<option value="'.$key.'">'.$val['Lettre'].'</option>';
				}  // end foreach()

				$s.='</select>&nbsp;&nbsp;</td>'."\n";
				$s.='<td width="40%" valign="top">';
				if(isset($Select[$cpt2])) $s.='<b>'.$Select[$cpt2]['Lettre'].'.</b> '.$Select[$cpt2]['Reponse'];
					else $s.='&nbsp;';
				$s.="</td>\n</tr>\n";

				$jstmpc .= '['.$answerCorrect.','.$cpt2.'],';

				$my_weight=explode('@',$weight);
				if (count($my_weight)==2) {
					$weight=$my_weight[0];
				} else {
					$weight=$my_weight[0];
				}
		    	$jstmpw .= 'questions_answers_ponderation['.$qId.']['.$cpt2.'] = '.$weight.";\n";
				$cpt2++;

				// if the left side of the "matching" has been completely shown
				if($answerId == $nbrAnswers)
				{
					// if there remain answers to be shown on the right side
					while(isset($Select[$cpt2]))
					{
						//$s.='<tr>'."\n";
						//$s.='<td colspan="2">'."\n";
						//$s.='<table border="0" cellpadding="0" cellspacing="0" width="100%">'."\n";
						$s.='<tr>'."\n";
						$s.='<td width="60%" colspan="2">&nbsp;</td>'."\n";
						$s.='<td width="40%" valign="top">';
						$s.='<b>'.$Select[$cpt2]['Lettre'].'.</b> '.$Select[$cpt2]['Reponse'];
						$s.="</td>\n</tr>\n";
						$cpt2++;
					}	// end while()
				}  // end if()
			}
		}
		$js .= 'questions_answers['.$this->questionJSId.'] = new Array('.substr($jstmp,0,-1).');'."\n";
    	$js .= 'questions_answers_correct['.$this->questionJSId.'] = new Array('.substr($jstmpc,0,-1).');'."\n";
    	$js .= 'questions_types['.$this->questionJSId.'] = \'matching\';'."\n";
    	$js .= $jstmpw;
		$html .= $s;
		$html .= '</table></td></tr>' . "\n";
        return array($js,$html);
    }
}

/**
 * This class handles the SCORM export of free-answer questions
 * @package chamilo.exercise.scorm
 */
class ScormAnswerFree extends Answer
{
	/**
     * Export the text with missing words.
     *
     * As a side effect, it stores two lists in the class :
     * the missing words and their respective weightings.
     *
     */
    function export()
    {
    	$qId = $this->questionJSId;
    	$js = '';
    	$html = '<tr><td colspan="2"><table width="100%">' . "\n";
		// some javascript must be added for that kind of questions
		$html .= '<tr>' . "\n"
				.	'<td>' . "\n"
		    	. '<textarea name="question_'.$qId.'_free" id="question_'.$qId.'_free" rows="20" cols="100"></textarea>' . "\n"
		    	.	'</td>' . "\n"
		    	.	'</tr>' . "\n";
		$html .= '</table></td></tr>' . "\n";
		// currently the free answers cannot be displayed, so ignore the textarea
		$html = '<tr><td colspan="2">'.get_lang('ThisItemIsNotExportable').'</td></tr>';
		$js .= 'questions_answers['.$this->questionJSId.'] = new Array();'."\n";
    	$js .= 'questions_answers_correct['.$this->questionJSId.'] = new Array();'."\n";
    	$js .= 'questions_types['.$this->questionJSId.'] = \'free\';'."\n";
		$jstmpw = 'questions_answers_ponderation['.$this->questionJSId.'] = new Array();'."\n";
		$jstmpw .= 'questions_answers_ponderation['.$this->questionJSId.'][0] = 0;'."\n";
    	$jstmpw .= 'questions_answers_ponderation['.$this->questionJSId.'][1] = 0;'.";\n";
    	$js .= $jstmpw;
        return array($js,$html);
    }
}
/**
 * This class handles the SCORM export of hotpot questions
 * @package chamilo.exercise.scorm
 */
class ScormAnswerHotspot extends Answer
{
	/**
	 * Returns the javascript code that goes with HotSpot exercises
	 * @return string	The JavaScript code
	 */
	function get_js_header()
	{
		if($this->standalone)
		{
			$header = '<script type="text/javascript" language="javascript">';
			$header .= file_get_contents('../plugin/hotspot/JavaScriptFlashGateway.js');
			$header .= '</script>';
			$header .= '<script type="text/javascript" language="javascript">';
			$header .= file_get_contents('../plugin/hotspot/hotspot.js');
			$header .= '</script>';
			$header .= '<script language="javascript" type="text/javascript">'.
					"<!--
					// -----------------------------------------------------------------------------
					// Globals
					// Major version of Flash required
					var requiredMajorVersion = 7;
					// Minor version of Flash required
					var requiredMinorVersion = 0;
					// Minor version of Flash required
					var requiredRevision = 0;
					// the version of javascript supported
					var jsVersion = 1.0;
					// -----------------------------------------------------------------------------
					// -->
					</script>
					<script language=\"VBScript\" type=\"text/vbscript\">
					<!-- // Visual basic helper required to detect Flash Player ActiveX control version information
					Function VBGetSwfVer(i)
					  on error resume next
					  Dim swControl, swVersion
					  swVersion = 0

					  set swControl = CreateObject(\"ShockwaveFlash.ShockwaveFlash.\" + CStr(i))
					  if (IsObject(swControl)) then
					    swVersion = swControl.GetVariable(\"\$version\")
					  end if
					  VBGetSwfVer = swVersion
					End Function
					// -->
					</script>

					<script language=\"JavaScript1.1\" type=\"text/javascript\">
					<!-- // Detect Client Browser type
					var isIE  = (navigator.appVersion.indexOf(\"MSIE\") != -1) ? true : false;
					var isWin = (navigator.appVersion.toLowerCase().indexOf(\"win\") != -1) ? true : false;
					var isOpera = (navigator.userAgent.indexOf(\"Opera\") != -1) ? true : false;
					jsVersion = 1.1;
					// JavaScript helper required to detect Flash Player PlugIn version information
					function JSGetSwfVer(i){
						// NS/Opera version >= 3 check for Flash plugin in plugin array
						if (navigator.plugins != null && navigator.plugins.length > 0) {
							if (navigator.plugins[\"Shockwave Flash 2.0\"] || navigator.plugins[\"Shockwave Flash\"]) {
								var swVer2 = navigator.plugins[\"Shockwave Flash 2.0\"] ? \" 2.0\" : \"\";
					      		var flashDescription = navigator.plugins[\"Shockwave Flash\" + swVer2].description;
								descArray = flashDescription.split(\" \");
								tempArrayMajor = descArray[2].split(\".\");
								versionMajor = tempArrayMajor[0];
								versionMinor = tempArrayMajor[1];
								if ( descArray[3] != \"\" ) {
									tempArrayMinor = descArray[3].split(\"r\");
								} else {
									tempArrayMinor = descArray[4].split(\"r\");
								}
					      		versionRevision = tempArrayMinor[1] > 0 ? tempArrayMinor[1] : 0;
					            flashVer = versionMajor + \".\" + versionMinor + \".\" + versionRevision;
					      	} else {
								flashVer = -1;
							}
						}
						// MSN/WebTV 2.6 supports Flash 4
						else if (navigator.userAgent.toLowerCase().indexOf(\"webtv/2.6\") != -1) flashVer = 4;
						// WebTV 2.5 supports Flash 3
						else if (navigator.userAgent.toLowerCase().indexOf(\"webtv/2.5\") != -1) flashVer = 3;
						// older WebTV supports Flash 2
						else if (navigator.userAgent.toLowerCase().indexOf(\"webtv\") != -1) flashVer = 2;
						// Can't detect in all other cases
						else {

							flashVer = -1;
						}
						return flashVer;
					}
					// When called with reqMajorVer, reqMinorVer, reqRevision returns true if that version or greater is available
					function DetectFlashVer(reqMajorVer, reqMinorVer, reqRevision)
					{
					 	reqVer = parseFloat(reqMajorVer + \".\" + reqRevision);
					   	// loop backwards through the versions until we find the newest version
						for (i=25;i>0;i--) {
							if (isIE && isWin && !isOpera) {
								versionStr = VBGetSwfVer(i);
							} else {
								versionStr = JSGetSwfVer(i);
							}
							if (versionStr == -1 ) {
								return false;
							} else if (versionStr != 0) {
								if(isIE && isWin && !isOpera) {
									tempArray         = versionStr.split(\" \");
									tempString        = tempArray[1];
									versionArray      = tempString .split(\",\");
								} else {
									versionArray      = versionStr.split(\".\");
								}
								versionMajor      = versionArray[0];
								versionMinor      = versionArray[1];
								versionRevision   = versionArray[2];

								versionString     = versionMajor + \".\" + versionRevision;   // 7.0r24 == 7.24
								versionNum        = parseFloat(versionString);
					        	// is the major.revision >= requested major.revision AND the minor version >= requested minor
								if ( (versionMajor > reqMajorVer) && (versionNum >= reqVer) ) {
									return true;
								} else {
									return ((versionNum >= reqVer && versionMinor >= reqMinorVer) ? true : false );
								}
							}
						}
					}
					// -->
					</script>";
			//because this header closes so many times the <script> tag, we have to reopen our own
			$header .= '<script type="text/javascript" language="javascript">'."\n";
			$header .= 'questions_answers['.$this->questionJSId.'] = new Array();'."\n";
    		$header .= 'questions_answers_correct['.$this->questionJSId.'] = new Array();'."\n";
    		$header .= 'questions_types['.$this->questionJSId.'] = \'hotspot\';'."\n";
			$jstmpw = 'questions_answers_ponderation['.$this->questionJSId.'] = new Array();'."\n";
			$jstmpw .= 'questions_answers_ponderation['.$this->questionJSId.'][0] = 0;'."\n";
	    	$jstmpw .= 'questions_answers_ponderation['.$this->questionJSId.'][1] = 0;'.";\n";
	    	$header .= $jstmpw;
		}
		else
		{
			$header = '';
			$header .= 'questions_answers['.$this->questionJSId.'] = new Array();'."\n";
    		$header .= 'questions_answers_correct['.$this->questionJSId.'] = new Array();'."\n";
    		$header .= 'questions_types['.$this->questionJSId.'] = \'hotspot\';'."\n";
			$jstmpw = 'questions_answers_ponderation['.$this->questionJSId.'] = new Array();'."\n";
			$jstmpw .= 'questions_answers_ponderation['.$this->questionJSId.'][0] = 0;'."\n";
	    	$jstmpw .= 'questions_answers_ponderation['.$this->questionJSId.'][1] = 0;'."\n";
	    	$header .= $jstmpw;
		}
		return $header;
	}
	/**
     * Export the text with missing words.
     *
     * As a side effect, it stores two lists in the class :
     * the missing words and their respective weightings.
     *
     */
    function export()
    {
    	$js = $this->get_js_header();
    	$html = '<tr><td colspan="2"><table width="100%">' . "\n";
		// some javascript must be added for that kind of questions
		$html .= '';

		// Get the answers, make a list
		$nbrAnswers=$this->selectNbrAnswers();

		$answer_list = '<div style="padding: 10px; margin-left: -8px; border: 1px solid #4271b5; height: 448px; width: 200px;"><b>'.get_lang('HotspotZones').'</b><ol>';
		for($answerId=1;$answerId <= $nbrAnswers;$answerId++)
		{
			$answer_list .= '<li>'.$this->selectAnswer($answerId).'</li>';
		}
		$answer_list .= '</ol></div>';

		/*
		if(!$onlyAnswers)
		{
			$s="<tr>
			  <td valign='top' colspan='2'>&nbsp;";
			$questionName=text_filter($questionName);
			$s.=$questionName;
			$s.="</td>
			</tr>
			<tr>
			  <td valign='top' colspan='2'>
				<i>";
			$questionDescription=text_filter($questionDescription);
			$s.=$questionDescription;
			$s.="</i>
			  </td>
			</tr>";
		}
		*/

		//$canClick = isset($_GET['editQuestion']) ? '0' : (isset($_GET['modifyAnswers']) ? '0' : '1');
		$canClick = true;
		//$tes = isset($_GET['modifyAnswers']) ? '0' : '1';
		//echo $tes;
		$html .= '<tr><td>'."
					<script language=\"JavaScript\" type=\"text/javascript\">
						<!--
						// Version check based upon the values entered above in \"Globals\"
						var hasReqestedVersion = DetectFlashVer(requiredMajorVersion, requiredMinorVersion, requiredRevision);


						// Check to see if the version meets the requirements for playback
						if (hasReqestedVersion) {  // if we've detected an acceptable version
						    var oeTags = '<object type=\"application/x-shockwave-flash\"".' data="'.api_get_path(WEB_CODE_PATH).'plugin/hotspot/hotspot_user.swf?modifyAnswers='.$this->questionJSId."&amp;canClick:".$canClick."\" width=\"380\" height=\"470\">'
										+ '<param name=\"movie\"".' value="'.api_get_path(WEB_CODE_PATH).'plugin/hotspot/hotspot_user.swf?modifyAnswers='.$this->questionJSId."&amp;canClick:".$canClick."\" \/>'
										+ '<\/object>';
						    document.write(oeTags);   // embed the Flash Content SWF when all tests are passed
						} else {  // flash is too old or we can't detect the plugin
							var alternateContent = 'Error<br \/>'
								+ 'Hotspots requires Macromedia Flash 7.<br \/>'
								+ '<a href=http://www.macromedia.com/go/getflash/>Get Flash<\/a>';
							document.write(alternateContent);  // insert non-flash content
						}
						// -->
					</script></td>
					<td valign='top'>$answer_list</td></tr>";
		$html .= '</table></td></tr>' . "\n";

		// currently the free answers cannot be displayed, so ignore the textarea
		$html = '<tr><td colspan="2">'.get_lang('ThisItemIsNotExportable').'</td></tr>';
        return array($js,$html);
    }
}
?>
