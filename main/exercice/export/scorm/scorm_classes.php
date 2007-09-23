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
require_once(api_get_path(SYS_CODE_PATH).'exercice/exercise.class.php');
require_once(api_get_path(SYS_CODE_PATH).'exercice/question.class.php');
require_once(api_get_path(SYS_CODE_PATH).'exercice/answer.class.php');
require_once(api_get_path(SYS_CODE_PATH).'exercice/unique_answer.class.php');
require_once(api_get_path(SYS_CODE_PATH).'exercice/multiple_answer.class.php');
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

/**
 * The ScormQuestion class is a gateway to getting the answers exported 
 * (the question is just an HTML text, while the answers are the most important).
 * It is important to note that the SCORM export process is done in two parts.
 * First, the HTML part (which is the presentation), and second the JavaScript
 * part (the process).
 * The two bits are separate to allow for a one-big-javascript and a one-big-html
 * files to be built. Each export function thus returns an array of HTML+JS
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
			case FREE_ANSWER :
				$this->answer = new ScormAnswerFree($this->id); 
				break;
			case HOTSPOT:
				$this->answer = new ScormAnswerHotspot($this->id); 
				break;
			default :
				$this->answer = null;
				break;
		}

		return true;
	}
	
	function export()
	{
		//echo "<pre>".print_r($this,1)."</pre>";
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
    function getQuestionHtml()
    {
    	$title			= $this->selectTitle();
		$description	= $this->selectDescription();
		$type 			= $this->selectType();

		$cols = 0;
		switch($type)
		{
			case MCUA:
			case MCMA:
			case TF:
			case FIB:
			case FREE_ANSWER:
			case HOTSPOT:
				$cols = 2;
				break;
			case MATCHING:
				$cols = 3;
				break;			
		}
		$s='<tr>' .
			'<td valign="top" colspan="'.$cols.'" id="question_'.$this->id.'_title">' . "\n" .
		   api_parse_tex($title).
		   	'</td>' . "\n" .
		   '</tr>' . "\n" .
		   '<tr>' . "\n" .
		   		'<td valign="top" colspan="'.$cols.'">' . "\n" .
		   		'	<i>'.api_parse_tex($description).'</i>' . "\n" .
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
    	$s = 'questions.push('.$this->id.');'."\n";
    	return $s;
    }
}

/**
 * This class handles the export to SCORM of a multiple choice question
 * (be it single answer or multiple answers)
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
        if ($type == MCMA)
        {
        	//$questionTypeLang = get_lang('MultipleChoiceMultipleAnswers');
        	$id = 1;
        	$jstmp = '';
        	$jstmpc = '';
			foreach( $this->answer as $i => $answer )
			{
				$identifier = 'question_'.$this->questionId.'_multiple_'.$i;
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
		    	$id++;
			}
			$js .= 'questions_answers['.$this->questionId.'] = new Array('.substr($jstmp,0,-1).');'."\n";
	    	$js .= 'questions_answers_correct['.$this->questionId.'] = new Array('.substr($jstmpc,0,-1).');'."\n";
	    	$js .= 'questions_types['.$this->questionId.'] = \'mcma\';'."\n";
        }
        else
        {
        	//$questionTypeLang = get_lang('MultipleChoiceUniqueAnswer');
        	$id = 1;
			foreach( $this->answer as $i => $answer )
			{			
	        	$identifier = 'question_'.$this->questionId.'_unique_'.$i;
				$html .=	
		    		'<tr>' . "\n" 
				.	'<td align="center" width="5%">' . "\n"
		    	.	'<input name="'.$identifier.'" id="'.$identifier.'" value="'.$i.'" type="radio" '
		    	.		($this->correct[$i] == 1? 'checked="checked"':'')
				.		'/>' . "\n"
		    	.	'</td>' . "\n"
		    	.	'<td width="95%">' . "\n"
		    	.	'<label for="'.$identifier.'">' . $this->answer[$i] . '</label>' . "\n"
		    	.	'</td>' . "\n"
		    	.	'</tr>' . "\n\n";
		    	$id++;
			}
        }
		$html .= '</table></td></tr>' . "\n";
        return array($js,$html);
    }
}

/**
 * This class handles the SCORM export of true/false questions
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
    	$html = '';
		$identifier = 'unique_'.$this->questionId.'_x';
				
    	$html .= 
			'<table width="100%">' . "\n\n";
		
		$scormIdentifier = 'scorm_'.getIdCounter();
		
		$html .=	
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
    		
    	$html .=
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
			
        return array($js,$html);
    }
}

/**
 * This class handles the SCORM export of fill-in-the-blanks questions
 */
class ScormAnswerFillInBlanks extends Answer 
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
    	$js = '';
    	$html = '';
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
		$js .= ''; 
			//'<script type="text/javascript" language="javascript">' . "\n";
    
        // Add the data for fillAnswerList
		for( $i = 0; $i < $answerCount; $i++ )
        {
            $js .= "    fillAnswerList['fill_" . $this->questionId . "_" . $i . "'] = new Array('" . $this->answerList[$i] . "', '" . $this->gradeList[$i] . "');\n";
        }
		
    	$js .= '';
    		//'</script>' . "\n" 
		$html .= 	'<table width="100%">' . "\n\n"
			
    	.	'<tr>' . "\n" 
		.	'<td>' . "\n"
    		
    	.	$displayedAnswer  . "\n"
    		
    	.	'</td>' . "\n"
    	.	'</tr>' . "\n\n"
		
    	.	'</table>' . "\n"
		.	'<p><small>' . get_lang('Fill in blanks') . '</small></p>' . "\n";

        return array($js,$html);
        
    }
    
}

/**
 * This class handles the SCORM export of matching questions
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
    	$html = '';
  		// prepare list of right proposition to allow
		// - easiest display
		// - easiest randomisation if needed one day 
		// (here I use array_values to change array keys from $code1 $code2 ... to 0 1 ...)	
		$displayedRightList = array_values($this->rightList);

		// get max length of displayed array
		$arrayLength = max( count($this->leftList), count($this->rightList) );

		$html .= '<table width="100%">' . "\n\n";
		
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
			
			$html .= 
				'<tr>' . "\n"	
			. 	'<td valign="top" width="40%">' . "\n" . $leftHtml . "\n" . '</td>' . "\n"
    		. 	'<td valign="top" width="20%">' . "\n" . $centerHtml . "\n" . '</td>' . "\n"	    		
    		. 	'<td valign="top" width="40%">' . "\n" . $rightHtml . "\n" . '</td>' . "\n"
    		.	'</tr>' . "\n\n";
			
			$leftCpt++;
			$rightCpt++;
		}

		
		$html .= 
			'</table>' . "\n"
		.	'<p><small>' . get_lang('Matching') . '</small></p>' . "\n";
		
       return array($js,$html); 
    }
}

/**
 * This class handles the SCORM export of free-answer questions
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
    	$js = '';
    	$html = '';
		// some javascript must be added for that kind of questions
		$js .= ''; 
			//'<script type="text/javascript" language="javascript">' . "\n";
    
    	$js .= '';
    		//'</script>' . "\n" 
		$html .= '<table width="100%">' . "\n\n"
			
    	.	'<tr>' . "\n" 
		.	'<td>' . "\n"
    		
    	.	$displayedAnswer  . "\n"
    		
    	.	'</td>' . "\n"
    	.	'</tr>' . "\n\n"
		
    	.	'</table>' . "\n"
		.	'<p><small>' . get_lang('FreeAnswer') . '</small></p>' . "\n";
        return array($js,$html);
        
    }
    
}

/**
 * This class handles the SCORM export of hotpot questions
 */
class ScormAnswerHotspot extends Answer 
{
	/**
	 * Returns the javascript code that goes with HotSpot exercises
	 * @return string	The JavaScript code
	 */
	function get_js_header()
	{
		$header = "<script type=\"text/javascript\" src=\"hotspot/JavaScriptFlashGateway.js\"></script>
					<script src=\"hotspot/hotspot.js\" type=\"text/javascript\"></script>
					<script language=\"JavaScript\" type=\"text/javascript\">
					<!--
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
    	$js = '';
    	$html = '';
		// some javascript must be added for that kind of questions
		$js .= ''; 
			//'<script type="text/javascript" language="javascript">' . "\n";
    
    	$js .= '';
    		//'</script>' . "\n" 
		$html .= '<table width="100%">' . "\n\n"
			
    	.	'<tr>' . "\n" 
		.	'<td>' . "\n"
    		
    	.	$displayedAnswer  . "\n"
    		
    	.	'</td>' . "\n"
    	.	'</tr>' . "\n\n"
		
    	.	'</table>' . "\n"
		.	'<p><small>' . get_lang('HotSpot') . '</small></p>' . "\n";
        return array($js,$html);
        
    }
    
}
?>