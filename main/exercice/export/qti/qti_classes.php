<?php // $Id: $
if ( count( get_included_files() ) == 1 ) die( '---' );
/**
 * @copyright (c) 2007 Dokeos
 * @copyright (c) 2001-2006 Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @author Claro Team <cvs@claroline.net>
 * @author Yannick Warnier <yannick.warnier@dokeos.com>
 * @package dokeos.exercise
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

class ImsQuestion extends Question
{
	/**
	 * Include the correct answer class and create answer
	 */
	function setAnswer()
	{
		switch($this->type)
		{
			case MCUA :
				$this->answer = new ImsAnswerMultipleChoice($this->id, false);
				break; 
			case MCMA :
				$this->answer = new ImsAnswerMultipleChoice($this->id, true);	
				break;
			case TF :
				$this->answer = new ImsAnswerTrueFalse($this->id); 
				break;
			case FIB :
				$this->answer = new ImsAnswerFillInBlanks($this->id); 
				break;
			case MATCHING :
				$this->answer = new ImsAnswerMatching($this->id); 
				break;
			default :
				$this->answer = null;
				break;
		}

		return true;
	}

    /**
     * allow to import the question
     *
     * @param questionArray is an array that must contain all the information needed to build the question
     * @author Guillaume Lederer <guillaume@claroline.net>
     */

    function import($questionArray, $exerciseTempPath)
    {
        //import answers

        $this->answer->import($questionArray);

        //import attached file, if any

        if (isset($questionArray['attached_file_url']))
        {
            $file= array();
            $file['name'] = $questionArray['attached_file_url'];
            $file['tmp_name'] = $exerciseTempPath.$file['name'];

            $this->setAttachment($file);
        }
    } 
} 

class ImsAnswerMultipleChoice extends answerMultipleChoice
{
	/**
     * Return the XML flow for the possible answers. 
     * That's one <response_lid>, containing several <flow_label>
     *
     * @author Amand Tihon <amand@alrj.org>
     */
    function imsExportResponses($questionIdent)
    {
        // Opening of the response block.
        if( $this->multipleAnswer )
        {
		    $out = '<response_lid ident = "MCM_' . $questionIdent . '" rcardinality = "Multiple" rtiming = "No">' . "\n"
		         . '<render_choice shuffle = "No" minnumber = "1" maxnumber = "' . count($this->answerList) . '">' . "\n";
        }
        else
        {
			$out = '<response_lid ident="MCS_' . $questionIdent . '" rcardinality="Single" rtiming="No"><render_choice shuffle="No">' . "\n";
        }
        
        // Loop over answers
        foreach( $this->answerList as $answer )
        {
            $responseIdent = $questionIdent . "_A_" . $answer['id'];
            
            $out.= '  <flow_label><response_label ident="' . $responseIdent . '">'.(!$this->multipleAnswer ? '<flow_mat class="list">':'').'<material>' . "\n"
                . '    <mattext><![CDATA[' . $answer['answer'] . ']]></mattext>' . "\n"
                . '  </material>'.(!$this->multipleAnswer ? '</flow_mat>':'').'</response_label></flow_label>' . "\n";
        }
        $out.= "</render_choice></response_lid>\n";
        
        return $out;
    }
    
    /**
     * Return the XML flow of answer processing : a succession of <respcondition>. 
     *
     * @author Amand Tihon <amand@alrj.org>
     */
    function imsExportProcessing($questionIdent)
    {
        $out = '';
        
        foreach( $this->answerList as $answer )
        {
            $responseIdent = $questionIdent . "_A_" . $answer['id'];
            $feedbackIdent = $questionIdent . "_F_" . $answer['id'];
            $conditionIdent = $questionIdent . "_C_" . $answer['id'];
            
            if( $this->multipleAnswer )
        	{
	            $out .= '<respcondition title="' . $conditionIdent . '" continue="Yes"><conditionvar>' . "\n"
				.	 '  <varequal respident="MCM_' . $questionIdent . '">' . $responseIdent . '</varequal>' . "\n";
        	}
        	else
        	{
	            $out .= '<respcondition title="' . $conditionIdent . '"><conditionvar>' . "\n"
				.	 '  <varequal respident="MCS_' . $questionIdent . '">' . $responseIdent . '</varequal>' . "\n";
        	}
               
            $out .= "  </conditionvar>\n" . '  <setvar action="Add">' . $answer['grade'] . "</setvar>\n";
                
            // Only add references for actually existing comments/feedbacks.
            if( !empty($answer['comment']) )
            {
                $out .= '  <displayfeedback feedbacktype="Response" linkrefid="' . $feedbackIdent . '" />' . "\n";
            }
            $out .= "</respcondition>\n";
        }
        return $out;
    }
         
     /**
      * Export the feedback (comments to selected answers) to IMS/QTI
      * 
      * @author Amand Tihon <amand@alrj.org>
      */
     function imsExportFeedback($questionIdent)
     {
        $out = "";
        foreach( $this->answerList as $answer )
        {
            if( !empty($answer['comment']) )
            {
                $feedbackIdent = $questionIdent . "_F_" . $answer['id'];
                $out.= '<itemfeedback ident="' . $feedbackIdent . '" view="Candidate"><flow_mat><material>' . "\n"
                    . '  <mattext><![CDATA[' . $answer['comment'] . "]]></mattext>\n"
                    . "</material></flow_mat></itemfeedback>\n";
            }
        }
        return $out;
     }

     /**
     * allow to import the answers, feedbacks, and grades of a question
     * @param questionArray is an array that must contain all the information needed to build the question
     * @author Guillaume Lederer <guillaume@claroline.net>
     */

    function import($questionArray)
    {

        $answerArray = $questionArray['answer'];

        $this->answerList = array(); //re-initialize answer object content

        

        foreach ($answerArray as $key => $answer)
        {
            if (!isset($answer['feedback'])) $answer['feedback'] = "";
            if (!isset($questionArray['weighting'][$key]))
            {
                if (isset($questionArray['default_weighting']))
                {
                    $grade = $questionArray['default_weighting'];
                }
                else
                {
                    $grade = 0;
                }
            }
            else
            {
                $grade = $questionArray['weighting'][$key];
            }
            if (in_array($key,$questionArray['correct_answers'])) $is_correct = true; else $is_correct = false;
            $addedAnswer = array( 
                            'answer' => $answer['value'],
                            'correct' => $is_correct,
                            'grade' => $grade,
                            'comment' => $answer['feedback'],
                            );

            $this->answerList[] = $addedAnswer;
        }
    }
}

class ImsAnswerTrueFalse extends answerTrueFalse
{
	/**
     * Return the XML flow for the possible answers. 
     * That's one <response_lid>, containing several <flow_label>
     *
     * @author Amand Tihon <amand@alrj.org>
     */
    function imsExportResponses($questionIdent)
    {
        // Opening of the response block.
        $out = '<response_lid ident="TF_' . $questionIdent . '" rcardinality="Single" rtiming="No"><render_choice shuffle="No">' . "\n";
       
        // true
        $response_ident = $questionIdent . '_A_true';    
		$out .= 
			'  <flow_label><response_label ident="'.$response_ident.'"><flow_mat class="list"><material>' . "\n"
		.	'    <mattext><![CDATA[' . get_lang('True') . ']]></mattext>' . "\n"
		.	'  </material></flow_mat></response_label></flow_label>' . "\n";

		// false       
		$response_ident = $questionIdent . '_A_false'; 
        $out .= 
			'  <flow_label><response_label ident="'.$response_ident.'"><flow_mat class="list"><material>' . "\n"
		.	'    <mattext><![CDATA[' . get_lang('False') . ']]></mattext>' . "\n"
		.	'  </material></flow_mat></response_label></flow_label>' . "\n";
		
        $out .= '</render_choice></response_lid>' . "\n";
        
        return $out;
    }

    /**
     * Return the XML flow of answer processing : a succession of <respcondition>. 
     *
     * @author Amand Tihon <amand@alrj.org>
     */
    function imsExportProcessing($questionIdent)
    {
        $out = '';
        
        // true
		$response_ident = $questionIdent. '_A_true';
        $feedback_ident = $questionIdent . '_F_true';
        $condition_ident = $questionIdent . '_C_true';
            
		$out .= 
			'<respcondition title="' . $condition_ident . '"><conditionvar>' . "\n"
		.	'  <varequal respident="TF_' . $questionIdent . '">' . $response_ident . '</varequal>' . "\n"
		.	'  </conditionvar>' . "\n" . '  <setvar action="Add">' . $this->trueGrade . '</setvar>' . "\n";
                
        // Only add references for actually existing comments/feedbacks.
        if( !empty($this->trueFeedback) )
        {
            $out.= '  <displayfeedback feedbacktype="Response" linkrefid="' . $this->trueFeedback . '" />' . "\n";
        }
        
		$out .= '</respcondition>' . "\n";

		// false
		$response_ident = $questionIdent. '_A_false';
        $feedback_ident = $questionIdent . '_F_false';
        $condition_ident = $questionIdent . '_C_false';
				
		$out .= 
			'<respcondition title="' . $condition_ident . '"><conditionvar>' . "\n"
		.	'  <varequal respident="TF_' . $questionIdent . '">' . $response_ident . '</varequal>' . "\n"
		.	'  </conditionvar>' . "\n" . '  <setvar action="Add">' . $this->falseGrade . '</setvar>' . "\n";
                
        // Only add references for actually existing comments/feedbacks.
        if( !empty($this->falseFeedback) )
        {
            $out.= '  <displayfeedback feedbacktype="Response" linkrefid="' . $feedback_ident . '" />' . "\n";
        }
        
		$out .= '</respcondition>' . "\n";
		
        return $out;
    }
         
     /**
      * Export the feedback (comments to selected answers) to IMS/QTI
      * 
      * @author Amand Tihon <amand@alrj.org>
      */
     function imsExportFeedback($questionIdent)
     {
        $out = "";
        
        if( !empty($this->trueFeedback) )
        {
            $feedback_ident = $questionIdent . '_F_true';
            $out.= '<itemfeedback ident="' . $feedback_ident . '" view="Candidate"><flow_mat><material>' . "\n"
                . '  <mattext><![CDATA[' . $this->trueFeedback . "]]></mattext>\n"
                . "</material></flow_mat></itemfeedback>\n";
        }
        
		if( !empty($this->falseFeedback) )
        {
            $feedback_ident = $questionIdent . '_F_false';
            $out.= '<itemfeedback ident="' . $feedback_ident . '" view="Candidate"><flow_mat><material>' . "\n"
                . '  <mattext><![CDATA[' . $this->falseFeedback . "]]></mattext>\n"
                . "</material></flow_mat></itemfeedback>\n";
        }
        return $out;
     }
}

class ImsAnswerFillInBlanks extends answerFillInBlanks 
{
	/**
     * Export the text with missing words.
     *
     * As a side effect, it stores two lists in the class :
     * the missing words and their respective weightings.
     *
     * @author Amand Tihon <amand@alrj.org>
     */
    function imsExportResponses($questionIdent)
    {
        global $charset;
    
        $out = "<flow>\n";

        $responsePart = explode(']', $this->answer);
        $i = 0; // Used for the reference generation.
        foreach($responsePart as $part)
        {
            $response_ident = $questionIdent . "_A_" . $i;
        
            if( strpos($part,'[') !== false )
            {
                list($rawText, $blank) = explode('[', $part);
            }
            else
            {
                $rawText = $part;
                $blank = "";
            }

            if ($rawText!="")
            {
                $out.="  <material><mattext><![CDATA[" . $rawText . "]]></mattext></material>\n";
            }
            
            if ($blank!="")
            {
                $out.= '  <response_str ident="' . $response_ident . '" rcardinality="Single" rtiming="No">' . "\n"
                     . '    <render_fib fibtype="String" prompt="Box" encoding="' . $charset . '">' . "\n"
                     . '      <response_label ident="A"/>' . "\n"
                     . "     </render_fib>\n"
                     . "  </response_str>\n";
            }
            $i++;
        }
        $out.="</flow>\n";

        return $out;
        
    }
    
    /**
     * Exports the response processing.
     *
     * It uses the two lists build by export_responses(). This implies that export_responses MUST
     * be called before.
     *
     * @author Amand Tihon <amand@alrj.org>
     */
    function imsExportProcessing($questionIdent)
    {
        $out = "";
        
		$answerCount = count($this->answerList);
		
		for( $i = 0; $i < $answerCount ; $i++ ) 
        {
            $response_ident = $questionIdent . "_A_" . $i;
            $out.= '  <respcondition continue="Yes"><conditionvar>' . "\n"
                 . '    <varequal respident="' . $response_ident . '" case="No"><![CDATA[' . $this->answerList[$i] . ']]></varequal>' . "\n"
                 . '  </conditionvar><setvar action="Add">' . $this->gradeList[$i] . "</setvar>\n"
                 . "  </respcondition>\n";
        }
        return $out;
    }
    
	/**
      * Export the feedback (comments to selected answers) to IMS/QTI
      * 
      * @author Amand Tihon <amand@alrj.org>
      */
     function imsExportFeedback($questionIdent)
     {
		// no feedback in this question type
        return '';
     }

    /**
     * allow to import the answers, feedbacks, and grades of a question
     *
     * @param questionArray is an array that must contain all the information needed to build the question
     * @author Guillaume Lederer <guillaume@claroline.net>
     */

    function import($questionArray)
    {
        $answerArray = $questionArray['answer'];
        $this->answerText = str_replace ("\n","",$questionArray['response_text']);
        if ($questionArray['subtype'] == "TEXTFIELD_FILL") $this->type = TEXTFIELD_FILL;
        if ($questionArray['subtype'] == "LISTBOX_FILL")
        {
            $this->wrongAnswerList = $questionArray['wrong_answers'];
            $this->type = LISTBOX_FILL;
        }

        //build correct_answsers array
        
        if (isset($questionArray['weighting']))
        {
            $this->gradeList = $questionArray['weighting'];
        }
    }
}

class ImsAnswerMatching extends answerMatching
{
	/**
     * Export the question part as a matrix-choice, with only one possible answer per line.
     * @author Amand Tihon <amand@alrj.org>
     */
    function imsExportResponses($questionIdent)
    {
		$out = "";
        // Now, loop again, finding questions (rows)
        foreach( $this->leftList as $leftElt )
        {
            $responseIdent = $questionIdent . "_A_" . $leftElt['code'];
            $out.= '<response_lid ident="' . $responseIdent . '" rcardinality="Single" rtiming="No">' . "\n"
                 . '<material><mattext><![CDATA[' . $leftElt['answer'] . "]]></mattext></material>\n"
                 . '  <render_choice shuffle="No"><flow_label>' . "\n";
                 
            foreach( $this->rightList as $rightElt ) 
            {
                $out.= '    <response_label ident="' . $rightElt['code'] . '"><material>' . "\n"
                     . "      <mattext><![CDATA[" . $rightElt['answer'] . "]]></mattext>\n"
                     . "    </material></response_label>\n";
            }
            
            $out.= "</flow_label></render_choice></response_lid>\n";
        }
        
       return $out; 
    }
    
    /**
     * Export the response processing part
     * @author Amand Tihon <amand@alrj.org>
     */
    function imsExportProcessing($questionIdent)
    {
        $out = "";
        foreach( $this->leftList as $leftElt )
        {
            $responseIdent = $questionIdent . "_A_" . $leftElt['code'];
            $out.= '  <respcondition continue="Yes"><conditionvar>' . "\n"
                 . '    <varequal respident="' . $responseIdent . '">' . $leftElt['match'] . "</varequal>\n"
                 . '  </conditionvar><setvar action="Add">' . $leftElt['grade'] . "</setvar>\n"
                 . "  </respcondition>\n";
        }
        return $out;
    }   
    
	/**
      * Export the feedback (comments to selected answers) to IMS/QTI
      * 
      * @author Amand Tihon <amand@alrj.org>
      */
     function imsExportFeedback($questionIdent)
     {
		// no feedback in this question type
        return '';
     }

    /**
     * allow to import the answers, feedbacks, and grades of a question
     *
     * @param questionArray is an array that must contain all the information needed to build the question
     * @author Guillaume Lederer <guillaume@claroline.net>
     */

    function import($questionArray)
    {
        $answerArray = $questionArray['answer'];

        //This tick to remove examples in the answers!!!!
        $this->leftList = array();
        $this->rightList = array();
        
        //find right and left column

        $right_column = array_pop($answerArray);
        $left_column  = array_pop($answerArray);

        //1- build answers

        foreach ($right_column as $right_key => $right_element)
        {
            $code = $this->addRight($right_element);

            foreach ($left_column as $left_key => $left_element)
            {
                $matched_pattern = $left_key." ".$right_key;
                $matched_pattern_inverted = $right_key." ".$left_key;


                if (in_array($matched_pattern, $questionArray['correct_answers']) || in_array($matched_pattern_inverted, $questionArray['correct_answers']))
                {
                    if (isset($questionArray['weighting'][$matched_pattern]))
                    {
                        $grade = $questionArray['weighting'][$matched_pattern];
                    }
                    else
                    {
                        $grade = 0;
                    }
                    $this->addLeft($left_element, $code, $grade);
                }
            }
        }
    }
} 
?>