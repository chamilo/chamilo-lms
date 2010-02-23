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
 */

require dirname(__FILE__) . '/qti2_classes.php';

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
/**
 * An IMS/QTI item. It corresponds to a single question.
 * This class allows export from Claroline to IMS/QTI2.0 XML format of a single question.
 * It is not usable as-is, but must be subclassed, to support different kinds of questions.
 *
 * Every start_*() and corresponding end_*(), as well as export_*() methods return a string.
 *
 * @warning Attached files are NOT exported.
 */
class ImsAssessmentItem
{
    var $question;
    var $question_ident;
    var $answer;

    /**
     * Constructor.
     *
     * @param $question The Question object we want to export.
     */
     function ImsAssessmentItem($question)
     {
        $this->question = $question;
        //$this->answer = new Answer($question->id);
        $this->answer = $this->question->setAnswer();
        $this->questionIdent = "QST_" . $question->id ;
     }

     /**
      * Start the XML flow.
      *
      * This opens the <item> block, with correct attributes.
      *
      */
      function start_item()
      {
        /*
        return '<assessmentItem xmlns="http://www.imsglobal.org/xsd/imsqti_v2p0"
                    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                    xsi:schemaLocation="http://www.imsglobal.org/xsd/imsqti_v2p0 imsqti_v2p0.xsd"
                    identifier="'.$this->questionIdent.'"
                    title="'.htmlspecialchars($this->question->selectTitle()).'">'."\n";
         */
        $string = '<assessmentItem xmlns="http://www.imsglobal.org/xsd/imsqti_v2p1"
                    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                    xsi:schemaLocation="http://www.imsglobal.org/xsd/imsqti_v2p1 imsqti_v2p1.xsd"
                    identifier="'.$this->questionIdent.'"
                    title="'.htmlspecialchars($this->question->selectTitle()).'">'."\n";
        return $string;

      }

      /**
       * End the XML flow, closing the </item> tag.
       *
       */
      function end_item()
      {
        return "</assessmentItem>\n";
      }

     /**
      * Start the itemBody
      *
      */
     function start_item_body()
     {
        return '  <itemBody>' . "\n";
     }

     /**
      * End the itemBody part.
      *
      */
     function end_item_body()
     {
        return "  </itemBody>\n";
     }

     /**
      * add the response processing template used.
      *
      */

      function add_response_processing()
      {
          //return '  <responseProcessing template="http://www.imsglobal.org/question/qti_v2p0/rptemplates/map_response"/>' . "\n";
          return '  <responseProcessing template="http://www.imsglobal.org/question/qti_v2p1/rptemplates/map_correct"/>' . "\n";
      }


     /**
      * Export the question as an IMS/QTI Item.
      *
      * This is a default behaviour, some classes may want to override this.
      *
      * @param $standalone: Boolean stating if it should be exported as a stand-alone question
      * @return A string, the XML flow for an Item.
      */
     function export($standalone = false)
     {
        global $charset;
        $head = $foot = "";

        if( $standalone )
        {
            $head = '<?xml version="1.0" encoding="'.$charset.'" standalone="no"?>' . "\n";
        }
        $res = $head
               . $this->start_item()
                 .$this->answer->imsExportResponsesDeclaration($this->questionIdent)
                 . $this->start_item_body()
                 . $this->answer->imsExportResponses($this->questionIdent, $this->question->question, $this->question->description, $this->question->picture)
                 . $this->end_item_body()
               . $this->add_response_processing()
               . $this->end_item()
             . $foot;
        return $res;
     }
}

/**
 * This class represents an entire exercise to be exported in IMS/QTI.
 * It will be represented by a single <section> containing several <item>.
 *
 * Some properties cannot be exported, as IMS does not support them :
 *   - type (one page or multiple pages)
 *   - start_date and end_date
 *   - max_attempts
 *   - show_answer
 *   - anonymous_attempts
 *
 * @author Amand Tihon <amand@alrj.org>
 */
class ImsSection
{
    var $exercise;

    /**
     * Constructor.
     * @param $exe The Exercise instance to export
     * @author Amand Tihon <amand@alrj.org>
     */
    function ImsSection($exe)
    {
        $this->exercise = $exe;
    }

    function start_section()
    {
        $out = '<section ident="EXO_' . $this->exercise->selectId() . '" title="' . $this->exercise->selectTitle() . '">' . "\n";
        return $out;
    }

    function end_section()
    {
        return "</section>\n";
    }

    function export_duration()
    {
        if ($max_time = $this->exercise->selectTimeLimit())
        {
            // return exercise duration in ISO8601 format.
            $minutes = floor($max_time / 60);
            $seconds = $max_time % 60;
            return '<duration>PT' . $minutes . 'M' . $seconds . "S</duration>\n";
        }
        else
        {
            return '';
        }
    }

    /**
     * Export the presentation (Exercise's description)
     * @author Amand Tihon <amand@alrj.org>
     */
    function export_presentation()
    {
        $out = "<presentation_material><flow_mat><material>\n"
             . "  <mattext><![CDATA[" . $this->exercise->selectDescription() . "]]></mattext>\n"
             . "</material></flow_mat></presentation_material>\n";
        return $out;
    }

    /**
     * Export the ordering information.
     * Either sequential, through all questions, or random, with a selected number of questions.
     * @author Amand Tihon <amand@alrj.org>
     */
    function export_ordering()
    {
        $out = '';
        if ($n = $this->exercise->getShuffle()) {
            $out.= "<selection_ordering>"
                 . "  <selection>\n"
                 . "    <selection_number>" . $n . "</selection_number>\n"
                 . "  </selection>\n"
                 . '  <order order_type="Random" />'
                 . "\n</selection_ordering>\n";
        }
        else
        {
            $out.= '<selection_ordering sequence_type="Normal">' . "\n"
                 . "  <selection />\n"
                 . "</selection_ordering>\n";
        }

        return $out;
    }

    /**
     * Export the questions, as a succession of <items>
     * @author Amand Tihon <amand@alrj.org>
     */
    function export_questions()
    {
        $out = "";
        foreach ($this->exercise->selectQuestionList() as $q)
        {
        	$out .= export_question($q, false);
        }
        return $out;
    }

    /**
     * Export the exercise in IMS/QTI.
     *
     * @param bool $standalone Wether it should include XML tag and DTD line.
     * @return a string containing the XML flow
     * @author Amand Tihon <amand@alrj.org>
     */
    function export($standalone)
    {
        global $charset;

        $head = $foot = "";
        if ($standalone) {
            $head = '<?xml version = "1.0" encoding = "' . $charset . '" standalone = "no"?>' . "\n"
                  . '<!DOCTYPE questestinterop SYSTEM "ims_qtiasiv2p1.dtd">' . "\n"
                  . "<questestinterop>\n";
            $foot = "</questestinterop>\n";
        }
        $out = $head
             . $this->start_section()
             . $this->export_duration()
             . $this->export_presentation()
             . $this->export_ordering()
             . $this->export_questions()
             . $this->end_section()
             . $foot;

        return $out;
    }
}

/*
    Some quick notes on identifiers generation.
    The IMS format requires some blocks, like items, responses, feedbacks, to be uniquely
    identified.
    The unicity is mandatory in a single XML, of course, but it's prefered that the identifier stays
    coherent for an entire site.

    Here's the method used to generate those identifiers.
    Question identifier :: "QST_" + <Question Id from the DB> + "_" + <Question numeric type>
    Response identifier :: <Question identifier> + "_A_" + <Response Id from the DB>
    Condition identifier :: <Question identifier> + "_C_" + <Response Id from the DB>
    Feedback identifier :: <Question identifier> + "_F_" + <Response Id from the DB>
*/
/**
 * An IMS/QTI item. It corresponds to a single question.
 * This class allows export from Claroline to IMS/QTI XML format.
 * It is not usable as-is, but must be subclassed, to support different kinds of questions.
 *
 * Every start_*() and corresponding end_*(), as well as export_*() methods return a string.
 *
 * @warning Attached files are NOT exported.
 * @author Amand Tihon <amand@alrj.org>
 */
class ImsItem
{
    var $question;
    var $question_ident;
    var $answer;

    /**
     * Constructor.
     *
     * @param $question The Question object we want to export.
     * @author Anamd Tihon
     */
     function ImsItem($question)
     {
        $this->question = $question;
        $this->answer = $question->answer;
        $this->questionIdent = "QST_" . $question->selectId() ;
     }

     /**
      * Start the XML flow.
      *
      * This opens the <item> block, with correct attributes.
      *
      * @author Amand Tihon <amand@alrj.org>
      */
      function start_item()
      {
        return '<item title="' . htmlspecialchars($this->question->selectTitle()) . '" ident="' . $this->questionIdent . '">' . "\n";
      }

      /**
       * End the XML flow, closing the </item> tag.
       *
       * @author Amand Tihon <amand@alrj.org>
       */
      function end_item()
      {
        return "</item>\n";
      }

     /**
      * Create the opening, with the question itself.
      *
      * This means it opens the <presentation> but doesn't close it, as this is the role of end_presentation().
      * Inbetween, the export_responses from the subclass should have been called.
      *
      * @author Amand Tihon <amand@alrj.org>
      */
     function start_presentation()
     {
        return '<presentation label="' . $this->questionIdent . '"><flow>' . "\n"
             . '<material><mattext><![CDATA[' . $this->question->selectDescription() . "]]></mattext></material>\n";
     }

     /**
      * End the </presentation> part, opened by export_header.
      *
      * @author Amand Tihon <amand@alrj.org>
      */
     function end_presentation()
     {
        return "</flow></presentation>\n";
     }

     /**
      * Start the response processing, and declare the default variable, SCORE, at 0 in the outcomes.
      *
      * @author Amand Tihon <amand@alrj.org>
      */
     function start_processing()
     {
        return '<resprocessing><outcomes><decvar vartype="Integer" defaultval="0" /></outcomes>' . "\n";
     }

     /**
      * End the response processing part.
      *
      * @author Amand Tihon <amand@alrj.org>
      */
     function end_processing()
     {
        return "</resprocessing>\n";
     }


     /**
      * Export the question as an IMS/QTI Item.
      *
      * This is a default behaviour, some classes may want to override this.
      *
      * @param $standalone: Boolean stating if it should be exported as a stand-alone question
      * @return A string, the XML flow for an Item.
      * @author Amand Tihon <amand@alrj.org>
      */
     function export($standalone = False)
     {
        global $charset;
        $head = $foot = "";

        if( $standalone )
        {
            $head = '<?xml version = "1.0" encoding = "'.$charset.'" standalone = "no"?>' . "\n"
                  . '<!DOCTYPE questestinterop SYSTEM "ims_qtiasiv2p1.dtd">' . "\n"
                  . "<questestinterop>\n";
            $foot = "</questestinterop>\n";
        }

        return $head
               . $this->start_item()
                . $this->start_presentation()
                    . $this->answer->imsExportResponses($this->questionIdent)
                . $this->end_presentation()
                . $this->start_processing()
                    . $this->answer->imsExportProcessing($this->questionIdent)
                . $this->end_processing()
                . $this->answer->imsExportFeedback($this->questionIdent)
               . $this->end_item()
              . $foot;
     }
}


/*--------------------------------------------------------
      Functions
  --------------------------------------------------------*/

/**
 * Send a complete exercise in IMS/QTI format, from its ID
 *
 * @param int $exerciseId The exercise to exporte
 * @param boolean $standalone Wether it should include XML tag and DTD line.
 * @return The XML as a string, or an empty string if there's no exercise with given ID.
 */
function export_exercise($exerciseId, $standalone=true)
{
    $exercise = new Exercise();
    if (! $exercise->read($exerciseId))
    {
        return '';
    }
    $ims = new ImsSection($exercise);
    $xml = $ims->export($standalone);
    return $xml;
}

/**
 * Returns the XML flow corresponding to one question
 *
 * @param int The question ID
 * @param bool standalone (ie including XML tag, DTD declaration, etc)
 */
function export_question($questionId, $standalone=true)
{
    $question = new Ims2Question();
    $qst = $question->read($questionId);
    if( !$qst or $qst->type == FREE_ANSWER)
    {
        return '';
    }
    $question->id = $qst->id;
    $question->type = $qst->type;
    $question->question = $qst->question;
    $question->description = $qst->description;
	$question->weighting=$qst->weighting;
	$question->position=$qst->position;
	$question->picture=$qst->picture;
    $ims = new ImsAssessmentItem($question);

    return $ims->export($standalone);

}

?>