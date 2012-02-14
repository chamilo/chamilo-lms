<?php // $Id: $
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.exercise.scorm
 */
/**
 * Code
 */
if ( count( get_included_files() ) == 1 ) die( '---' );

require dirname(__FILE__) . '/scorm_classes.php';

/*--------------------------------------------------------
      Classes
  --------------------------------------------------------*/
// answer types
define(UNIQUE_ANSWER,	1);
define(MULTIPLE_ANSWER,	2);
define(FILL_IN_BLANKS,	3);
define(MATCHING,		4);
define(FREE_ANSWER,     5);
define(HOT_SPOT, 		6);
define(HOT_SPOT_ORDER, 	7);
define('ORAL_EXPRESSION', 		13);
/**
 * A SCORM item. It corresponds to a single question.
 * This class allows export from Dokeos SCORM 1.2 format of a single question.
 * It is not usable as-is, but must be subclassed, to support different kinds of questions.
 *
 * Every start_*() and corresponding end_*(), as well as export_*() methods return a string.
 *
 * Attached files are NOT exported.
 * @package chamilo.exercise.scorm
 */
class ScormAssessmentItem
{
    var $question;
    var $question_ident;
    var $answer;

    /**
     * Constructor.
     *
     * @param $question The Question object we want to export.
     */
     function ScormAssessmentItem($question,$standalone=false)
     {
        $this->question = $question;
        //$this->answer = new Answer($question->id);
        $this->answer = $this->question->setAnswer();
        $this->questionIdent = "QST_" . $question->id ;
        $this->standalone = $standalone;
        //echo "<pre>".print_r($this,1)."</pre>";
     }

     /**
      * Start the XML flow.
      *
      * This opens the <item> block, with correct attributes.
      *
      */
     function start_page()
     {
        global $charset;
        $head = "";
        if( $this->standalone)
        {
        	$head = '<?xml version="1.0" encoding="'.$charset.'" standalone="no"?>' . "\n";
        	$head .= '<html>'."\n";
        }
        return $head;
     }

     /**
      * End the XML flow, closing the </item> tag.
      *
      */
     function end_page()
     {
     	if($this->standalone){return '</html>';}
     	return '';
     }
	/**
	 * Start document header
	 */
	function start_header()
	{
		if($this->standalone){return '<head>'. "\n";}
		return '';
	}
	/**
	 * Print CSS inclusion
	 */
	function css()
	{
		if($this->standalone)
		{
			$css = '<style type="text/css" media="screen, projection">'."\n";
			$css .= '/*<![CDATA[*/'."\n";
			$css .= '@import "'.api_get_path(WEB_PATH).'main/css/public_admin/default.css";'."\n";
			$css .= '@import "'.api_get_path(WEB_PATH).'main/css/public_admin/course.css";'."\n";
			$css .= '/*]]>*/'."\n";
			$css .= '</style>'."\n";
			$css .= '<style type="text/css" media="print">'."\n";
			$css .= '/*<![CDATA[*/'."\n";
			$css .= '@import "'.api_get_path(WEB_PATH).'main/css/public_admin/print.css";'."\n";
			$css .= '/*]]>*/'."\n";
			$css .= '</style>'."\n";
			return $css;
		}
		return '';
	}

	/**
	 * End document header
	 */
	function end_header()
	{
		if($this->standalone){return '</head>'. "\n";}
		return '';
	}
    /**
     * Start the itemBody
     *
     */
    function start_js()
    {
    	if($this->standalone){return '<script type="text/javascript" language="javascript">'. "\n";}
    	return '';
    }
	/**
	 * Common JS functions
	 */
	function common_js()
	{
		$js = file_get_contents('../newscorm/js/api_wrapper.js');
		$js .= 'var questions = new Array();' . "\n";
		$js .= 'var questions_answers = new Array();' . "\n";
		$js .= 'var questions_answers_correct = new Array();' . "\n";
		$js .= 'var questions_types = new Array();' . "\n";
		$js .= "\n" .
			'/**
             * Assigns any event handler to any element
             * @param	object	Element on which the event is added
             * @param	string	Name of event
             * @param	string	Function to trigger on event
             * @param	boolean	Capture the event and prevent
             */
            function addEvent(elm, evType, fn, useCapture)
            { //by Scott Andrew
                if(elm.addEventListener){
            		elm.addEventListener(evType, fn, useCapture);
            		return true;
            	} else if(elm.attachEvent) {
            		var r = elm.attachEvent(\'on\' + evType, fn);
            		return r;
            	} else {
            		elm[\'on\' + evType] = fn;
            	}
            }
            /**
             * Adds the event listener
             */
            function addListeners(e) {
            	loadPage();
            	/*
            	var my_form = document.getElementById(\'dokeos_scorm_form\');
            	addEvent(my_form,\'submit\',checkAnswers,false);
            	*/
            	var my_button = document.getElementById(\'dokeos_scorm_submit\');
            	addEvent(my_button,\'click\',doQuit,false);
            	//addEvent(my_button,\'click\',checkAnswers,false);
            	//addEvent(my_button,\'change\',checkAnswers,false);
            	addEvent(window,\'unload\',unloadPage,false);
            }'."\n\n";

		$js .= '';
		//$js .= 'addEvent(window,\'load\',loadPage,false);'."\n";
		//$js .= 'addEvent(window,\'unload\',unloadPage,false);'."\n";
		$js .= 'addEvent(window,\'load\',addListeners,false);'."\n";
		if($this->standalone){return $js. "\n";}
		return '';
	}
    /**
     * End the itemBody part.
     *
     */
    function end_js()
    {
    	if($this->standalone){return '</script>'. "\n";}
    	return '';
    }
    /**
     * Start the itemBody
     *
     */
    function start_body()
    {
    	if($this->standalone){return '<body>'. "\n".'<form id="dokeos_scorm_form" method="post" action="">'."\n";}
    	return '';
    }

    /**
     * End the itemBody part.
     *
     */
    function end_body()
    {
    	if($this->standalone){return '<br /><input type="button" id="dokeos_scorm_submit" name="dokeos_scorm_submit" value="OK" /></form>'."\n".'</body>'. "\n";}
    	return '';
    }

    /**
     * Export the question as a SCORM Item.
     *
     * This is a default behaviour, some classes may want to override this.
     *
     * @param $standalone: Boolean stating if it should be exported as a stand-alone question
     * @return A string, the XML flow for an Item.
     */
    function export()
    {
    	$js = $html = '';
        list($js,$html) = $this->question->export();
        //list($js,$html) = $this->question->answer->export();
		if($this->standalone)
		{
	        $res = $this->start_page()
	               . $this->start_header()
	               . $this->css()
	               . $this->start_js()
	               . $this->common_js()
	               . $js
	               . $this->end_js()
	               . $this->end_header()
	               . $this->start_body()
	        //         .$this->answer->imsExportResponsesDeclaration($this->questionIdent)
	        //         . $this->start_item_body()
	        //           . $this->answer->scormExportResponses($this->questionIdent, $this->question->question, $this->question->description, $this->question->picture)
	        //			.$question
	      		   . $html
	               . $this->end_body()
	               . $this->end_page();
	        return $res;
		}
		else
		{
			return array($js,$html);
		}
    }
}

/**
 * This class represents an entire exercise to be exported in SCORM.
 * It will be represented by a single <section> containing several <item>.
 *
 * Some properties cannot be exported, as SCORM does not support them :
 *   - type (one page or multiple pages)
 *   - start_date and end_date
 *   - max_attempts
 *   - show_answer
 *   - anonymous_attempts
 * @package chamilo.exercise.scorm
 */
class ScormSection
{
    var $exercise;

    /**
     * Constructor.
     * @param $exe The Exercise instance to export
     * @author Amand Tihon <amand@alrj.org>
     */
    function ScormSection($exe)
    {
        $this->exercise = $exe;
    }


     /**
      * Start the XML flow.
      *
      * This opens the <item> block, with correct attributes.
      *
      */
     function start_page()
     {
        global $charset;
        $head = $foot = "";
		$head = '<?xml version="1.0" encoding="'.$charset.'" standalone="no"?>' . "\n".'<html>'."\n";
        return $head;
     }

     /**
      * End the XML flow, closing the </item> tag.
      *
      */
     function end_page()
     {
       return '</html>';
     }
	/**
	 * Start document header
	 */
	function start_header()
	{
		return '<head>'. "\n";
	}
	/**
	 * Print CSS inclusion
	 */
	function css()
	{

		$css = '<style type="text/css" media="screen, projection">'."\n";
		$css .= '/*<![CDATA[*/'."\n";
		$css .= '@import "'.api_get_path(WEB_PATH).'main/css/public_admin/default.css";'."\n";
		$css .= '@import "'.api_get_path(WEB_PATH).'main/css/public_admin/course.css";'."\n";
		$css .= '/*]]>*/'."\n";
		$css .= '</style>'."\n";
		$css .= '<style type="text/css" media="print">'."\n";
		$css .= '/*<![CDATA[*/'."\n";
		$css .= '@import "'.api_get_path(WEB_PATH).'main/css/public_admin/print.css";'."\n";
		$css .= '/*]]>*/'."\n";
		$css .= '</style>'."\n";
		return $css;
	}

	/**
	 * End document header
	 */
	function end_header()
	{
		return '</head>'. "\n";
	}
    /**
     * Start the itemBody
     *
     */
    function start_js()
    {
       return '<script type="text/javascript" language="javascript">'. "\n";
    }
	/**
	 * Common JS functions
	 */
	function common_js()
	{
		$js = "\n";
		$js .= file_get_contents('../plugin/hotspot/JavaScriptFlashGateway.js');
		$js .= file_get_contents('../plugin/hotspot/hotspot.js');
		$js .=	"<!--
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
					// -->\n\n";
		$js .= file_get_contents('../newscorm/js/api_wrapper.js');
		$js .= 'var questions = new Array();' . "\n";
		$js .= 'var questions_answers = new Array();' . "\n";
		$js .= 'var questions_answers_correct = new Array();' . "\n";
		$js .= 'var questions_types = new Array();' . "\n";
		$js .= "\n" .
			'/**
             * Assigns any event handler to any element
             * @param	object	Element on which the event is added
             * @param	string	Name of event
             * @param	string	Function to trigger on event
             * @param	boolean	Capture the event and prevent
             */
            function addEvent(elm, evType, fn, useCapture)
            { //by Scott Andrew
                if(elm.addEventListener){
            		elm.addEventListener(evType, fn, useCapture);
            		return true;
            	} else if(elm.attachEvent) {
            		var r = elm.attachEvent(\'on\' + evType, fn);
            		return r;
            	} else {
            		elm[\'on\' + evType] = fn;
            	}
            }
            /**
             * Adds the event listener
             */
            function addListeners(e) {
            	loadPage();
            	/*
            	var my_form = document.getElementById(\'dokeos_scorm_form\');
            	addEvent(my_form,\'submit\',checkAnswers,false);
            	*/
            	var my_button = document.getElementById(\'dokeos_scorm_submit\');
            	addEvent(my_button,\'click\',doQuit,false);
                addEvent(my_button,\'click\',disableButton,false);
            	//addEvent(my_button,\'click\',checkAnswers,false);
            	//addEvent(my_button,\'change\',checkAnswers,false);
            	addEvent(window,\'unload\',unloadPage,false);
            }
            /** Disables the submit button on SCORM result submission **/
            function disableButton() {
              var mybtn = document.getElementById(\'dokeos_scorm_submit\');
              mybtn.setAttribute(\'disabled\',\'disabled\');
            }
            '."\n";

		$js .= '';
		//$js .= 'addEvent(window,\'load\',loadPage,false);'."\n";
		//$js .= 'addEvent(window,\'unload\',unloadPage,false);'."\n";
		$js .= 'addEvent(window,\'load\',addListeners,false);'."\n";
		return $js. "\n";
	}
    /**
     * End the itemBody part.
     *
     */
    function end_js()
    {
       return '</script>'. "\n";
    }
    /**
     * Start the itemBody
     *
     */
    function start_body()
    {
       return '<body>'. "\n".
       		'<h1>'.$this->exercise->selectTitle().'</h1><p>'.$this->exercise->selectDescription()."</p>\n".
			'<form id="dokeos_scorm_form" method="post" action="">'."\n".
			'<table width="100%">'."\n";
    }

    /**
     * End the itemBody part.
     *
     */
    function end_body()
    {
       return '</table><br /><input type="button" id="dokeos_scorm_submit" name="dokeos_scorm_submit" value="OK" /></form>'."\n".'</body>'. "\n";
    }

    /**
     * Export the question as a SCORM Item.
     *
     * This is a default behaviour, some classes may want to override this.
     *
     * @param $standalone: Boolean stating if it should be exported as a stand-alone question
     * @return A string, the XML flow for an Item.
     */
    function export()
    {
        global $charset;

        $head = "";
        if ($this->standalone) {
            $head = '<?xml version = "1.0" encoding = "' . $charset . '" standalone = "no"?>' . "\n"
                  . '<!DOCTYPE questestinterop SYSTEM "ims_qtiasiv2p1.dtd">' . "\n";
        }

        list($js,$html) = $this->export_questions();
        //list($js,$html) = $this->question->answer->export();
        $res = $this->start_page()
               . $this->start_header()
               . $this->css()
               . $this->start_js()
               . $this->common_js()
               . $js
               . $this->end_js()
               . $this->end_header()
               . $this->start_body()
        //         .$this->answer->imsExportResponsesDeclaration($this->questionIdent)
        //         . $this->start_item_body()
        //           . $this->answer->scormExportResponses($this->questionIdent, $this->question->question, $this->question->description, $this->question->picture)
        //			.$question
        			.$html
               . $this->end_body()
               . $this->end_page();

        return $res;
    }

    /**
     * Export the questions, as a succession of <items>
     * @author Amand Tihon <amand@alrj.org>
     */
    function export_questions()
    {
        $js = $html = "";
        $js_id = 0;
        foreach ($this->exercise->selectQuestionList() as $q)
        {
        	list($jstmp,$htmltmp)= export_question($q, false, $js_id);
        	$js .= $jstmp."\n";
        	$html .= $htmltmp."\n";
            ++$js_id;
        }
        return array($js,$html);
    }
}

/*--------------------------------------------------------
      Functions
  --------------------------------------------------------*/

/**
 * Send a complete exercise in SCORM format, from its ID
 *
 * @param int $exerciseId The exercise to exporte
 * @param boolean $standalone Wether it should include XML tag and DTD line.
 * @return The XML as a string, or an empty string if there's no exercise with given ID.
 */
function export_exercise($exerciseId, $standalone=true)
{
    $exercise = new Exercise();
    if (! $exercise->read($exerciseId)) {
        return '';
    }
    $ims = new ScormSection($exercise);
    $xml = $ims->export($standalone);
    return $xml;
}

/**
 * Returns the HTML + JS flow corresponding to one question
 *
 * @param int The question ID
 * @param bool standalone (ie including XML tag, DTD declaration, etc)
 * @param int  The JavaScript ID for this question. Due to the nature of interactions, we must have a natural sequence for questions in the generated JavaScript.
 */
function export_question($questionId, $standalone=true, $js_id)
{
    $question = new ScormQuestion();
    $qst = $question->read($questionId);
    if( !$qst )
    {
        return '';
    }
    $question->id = $qst->id;
    $question->js_id = $js_id;
    $question->type = $qst->type;
    $question->question = $qst->question;
    $question->description = $qst->description;
	$question->weighting=$qst->weighting;
	$question->position=$qst->position;
	$question->picture=$qst->picture;
    $assessmentItem = new ScormAssessmentItem($question,$standalone);
    //echo "<pre>".print_r($scorm,1)."</pre>";exit;
    return $assessmentItem->export();
}
?>
