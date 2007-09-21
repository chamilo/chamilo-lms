<?php // $Id: scorm_classes.php,v 1.2 2006/07/06 18:50:49 moosh Exp $
if ( count( get_included_files() ) == 1 ) die( '---' );
/**
 * @copyright (c) 2007 Dokeos
 * @copyright (c) 2001-2006 Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @author Claro Team <cvs@claroline.net>
 * @author Yannick Warnier <yannick.warnier@dokeos.com>
 */
require_once('../../exercise.class.php');
require_once('../../question.class.php');
require_once('../../answer.class.php');
require_once('../../unique_answer.class.php');
require_once('../../multiple_answer.class.php');
require_once('../../fill_blanks.class.php');
require_once('../../freeanswer.class.php');
require_once('../../hotspot.class.php');
require_once('../../matching.class.php');
require_once('../../hotspot.class.php');

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
				break; 
			case MCMA :
				$this->answer = new ScormAnswerMultipleChoice($this->id, true);	
				break;
			case TF :
				$this->answer = new ScormAnswerTrueFalse($this->id); 
				break;
			case FIB :
				$this->answer = new ScormAnswerFillInBlanks($this->id); 
				break;
			case MATCHING :
				$this->answer = new ScormAnswerMatching($this->id); 
				break;
			default :
				$this->answer = null;
				break;
		}

		return true;
	}
	
	function export()
	{
		$out = $this->getQuestionHtml();
		
		if( is_object($this->answer) )
		{
			$out .= $this->answer->export();
		}
		
		return $out;
		
	}
} 

class ScormAnswerMultipleChoice extends answerMultipleChoice
{
	/**
     * Return the XML flow for the possible answers. 
     * That's one <response_lid>, containing several <flow_label>
     *
     * @author Amand Tihon <amand@alrj.org>
     */
    function export()
    {
    	$out = 
			'<table width="100%">' . "\n\n";
		
		
        if( $this->multipleAnswer )
        {
        	$questionTypeLang = get_lang('Multiple choice (Multiple answers)');
        	       	
        	
			foreach( $this->answerList as $answer )
			{
				$identifier = 'multiple_'.$this->questionId.'_'.$answer['id'];
				$scormIdentifier = 'scorm_'.getIdCounter();
				
				$out .=	
		    		'<tr>' . "\n" 
				.	'<td align="center" width="5%">' . "\n"
		    	.	'<input name="'.$identifier.'" id="'.$scormIdentifier.'" value="'.$answer['grade'].'" type="checkbox" '
		    	.		($this->response == 'TRUE'? 'checked="checked"':'')
				.		'/>' . "\n"
		    	.	'</td>' . "\n"
		    	.	'<td width="95%">' . "\n"
		    	.	'<label for="'.$scormIdentifier.'">' . $answer['answer'] . '</label>' . "\n"
		    	.	'</td>' . "\n"
		    	.	'</tr>' . "\n\n";
			}
			               
        }
        else
        {
        	$questionTypeLang = get_lang('Multiple choice (Unique answer)');
        	$identifier = 'unique_'.$this->questionId.'_x';
        	
			foreach( $this->answerList as $answer )
			{			
				$scormIdentifier = 'scorm_'.getIdCounter();
				
				$out .=	
		    		'<tr>' . "\n" 
				.	'<td align="center" width="5%">' . "\n"
		    	.	'<input name="'.$identifier.'" id="'.$scormIdentifier.'" value="'.$answer['grade'].'" type="radio" '
		    	.		($this->response == 'TRUE'? 'checked="checked"':'')
				.		'/>' . "\n"
		    	.	'</td>' . "\n"
		    	.	'<td width="95%">' . "\n"
		    	.	'<label for="'.$scormIdentifier.'">' . $answer['answer'] . '</label>' . "\n"
		    	.	'</td>' . "\n"
		    	.	'</tr>' . "\n\n";
			}

        }
        
		$out .= 
			'</table>' . "\n"
		.	'<p><small>' . $questionTypeLang . '</small></p>' . "\n";
        
        return $out;
    }
}

class ScormAnswerTrueFalse extends answerTrueFalse
{
	/**
     * Return the XML flow for the possible answers. 
     * That's one <response_lid>, containing several <flow_label>
     *
     * @author Amand Tihon <amand@alrj.org>
     */
    function export()
    {
		$identifier = 'unique_'.$this->questionId.'_x';
				
    	$out = 
			'<table width="100%">' . "\n\n";
		
		$scormIdentifier = 'scorm_'.getIdCounter();
		
		$out .=	
    		'<tr>' . "\n" 
		.	'<td align="center" width="5%">' . "\n"
    	.	'<input name="'.$identifier.'" id="'.$scormIdentifier.'" value="'.$this->trueGrade.'" type="radio" '
    	.		($this->response == 'TRUE'? 'checked="checked"':'')
		.		'/>' . "\n"
    	.	'</td>' . "\n"
    	.	'<td width="95%">' . "\n"
    	.	'<label for="'.$scormIdentifier.'">' . get_lang('True') . '</label>' . "\n"
    	.	'</td>' . "\n"
    	.	'</tr>' . "\n\n";
    	
    	$scormIdentifier = 'scorm_'.getIdCounter();
    		
    	$out .=
			'<tr>' . "\n" 
		.	'<td align="center" width="5%">' . "\n"
		.	'<input name="'.$identifier.'" id="'.$scormIdentifier.'" value="'.$this->falseGrade.'" type="radio" '
		.		($this->response == 'FALSE'? 'checked="checked"':'')
		.		'/>' . "\n"
		.	'</td>' . "\n"
		.	'<td width="95%">' . "\n"
		.	'<label for="'.$scormIdentifier.'">' . get_lang('False') . '</label>' . "\n"
		.	'</td>' . "\n"
		.	'</tr>' . "\n\n"
		
		.	'</table>' . "\n"
		.	'<p><small>' . get_lang('True/False') . '</small></p>' . "\n";
			
        return $out;
    }
}

class ScormAnswerFillInBlanks extends answerFillInBlanks 
{
	/**
     * Export the text with missing words.
     *
     * As a side effect, it stores two lists in the class :
     * the missing words and their respective weightings.
     *
     * @author Amand Tihon <amand@alrj.org>
     */
    function export()
    {
		// get all enclosed answers
		foreach( $this->answerList as $answer )
		{
			$blankList[] = '['.$answer.']';
		}
		$answerCount = count($blankList);
							
		// build replacement 
		$replacementList = array();
		
		if( $this->type == LISTBOX_FILL )
		{
			// build the list shown in list box
			// prepare option list using good and wrong answers
			$allAnswerList = array_merge($this->answerList, $this->wrongAnswerList);
			
			// alphabetical sort of the list
			natcasesort($allAnswerList);
			
			$optionList[''] = '';
			
			foreach( $allAnswerList as $answer )
			{
				$optionList[htmlspecialchars($answer)] = htmlspecialchars($answer);
			}
					
			for( $i = 0; $i < $answerCount; $i++ )
			{
				$identifier = 'fill_' . $this->questionId . '_' . $i;
				$attr['id'] = 'scorm_'.getIdCounter();
								
				$replacementList[] = claro_html_form_select($identifier, $optionList, null, $attr);
			}
		}
		else
		{
			for( $i = 0; $i < $answerCount; $i++ )
			{
				$identifier = 'fill_' . $this->questionId . '_' . $i;
				$scormIdentifier = 'scorm_'.getIdCounter();
				
				$replacementList[] = "\n" . ' <input type="text" name="'.$identifier.'" id="'.$scormIdentifier.'" size="10" value="" /> ' . "\n";  					
			}
		}
		
		
		// apply replacement on answer
		$displayedAnswer = str_replace( $blankList, $replacementList, claro_parse_user_text($this->answerText) );
		
		// some javascript must be added for that kind of questions
		$out = 
			'<script type="text/javascript" language="javascript">' . "\n";
    
        // Add the data for fillAnswerList
		for( $i = 0; $i < $answerCount; $i++ )
        {
            $out .= "    fillAnswerList['fill_" . $this->questionId . "_" . $i . "'] = new Array('" . $this->answerList[$i] . "', '" . $this->gradeList[$i] . "');\n";
        }
		
    	$out .=
    		'</script>' . "\n" 
		.	'<table width="100%">' . "\n\n"
			
    	.	'<tr>' . "\n" 
		.	'<td>' . "\n"
    		
    	.	$displayedAnswer  . "\n"
    		
    	.	'</td>' . "\n"
    	.	'</tr>' . "\n\n"
		
    	.	'</table>' . "\n"
		.	'<p><small>' . get_lang('Fill in blanks') . '</small></p>' . "\n";

        return $out;
        
    }
    
}

class ScormAnswerMatching extends answerMatching
{
	/**
     * Export the question part as a matrix-choice, with only one possible answer per line.
     * @author Amand Tihon <amand@alrj.org>
     */
    function export()
    {
  		// prepare list of right proposition to allow
		// - easiest display
		// - easiest randomisation if needed one day 
		// (here I use array_values to change array keys from $code1 $code2 ... to 0 1 ...)	
		$displayedRightList = array_values($this->rightList);

		// get max length of displayed array
		$arrayLength = max( count($this->leftList), count($this->rightList) );

		$out = '<table width="100%">' . "\n\n";
		
		$leftCpt = 1;
		$rightCpt = 'A';
		for( $i = 0; $i < $arrayLength; $i++ ) 
		{
			if( isset($this->leftList[$i]['answer']) )
			{
				// build html option list 
				$optionList = array();
				$optionCpt = 'A';
				$optionList[0] = '--';
				
				foreach( $this->rightList as $rightElt )
				{
					$optionList[$optionCpt] = $this->leftList[$i]['grade'];
		
					$optionCpt++;		
				}

				$leftHtml = $leftCpt . '. ' . $this->leftList[$i]['answer'];
				
				$attr['id'] = 'scorm_'.getIdCounter();
				$centerHtml = claro_html_form_select('matching_'.$this->questionId.'_'.$this->leftList[$i]['code'], $optionList, null, $attr);	
			}
			else
			{
				$leftHtml = '&nbsp;';
				$centerHtml = '&nbsp;';
			}
			
			if( isset($displayedRightList[$i]['answer']) )
			{
				$rightHtml = $rightCpt . '. ' . $displayedRightList[$i]['answer'];
			}
			else
			{
				$rightHtml = '&nbsp;';
			}
			
			$out .= 
				'<tr>' . "\n"	
			. 	'<td valign="top" width="40%">' . "\n" . $leftHtml . "\n" . '</td>' . "\n"
    		. 	'<td valign="top" width="20%">' . "\n" . $centerHtml . "\n" . '</td>' . "\n"	    		
    		. 	'<td valign="top" width="40%">' . "\n" . $rightHtml . "\n" . '</td>' . "\n"
    		.	'</tr>' . "\n\n";
			
			$leftCpt++;
			$rightCpt++;
		}

		
		$out .= 
			'</table>' . "\n"
		.	'<p><small>' . get_lang('Matching') . '</small></p>' . "\n";
		
       return $out; 
    }
} 
?>