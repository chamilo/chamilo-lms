<?php // $Id: $
/* For licensing terms, see /license.txt */
/**
 * @author Claro Team <cvs@claroline.net>
 * @author Amand Tihon <amand@alrj.org>
 * @author Sebastien Piraux <pir@cerdecam.be>
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 * @package chamilo.exercise.qti
 */
/**
 * Code
 */
if ( count( get_included_files() ) == 1 ) die( '---' );
include dirname(__FILE__) . '/qti_classes.php';
/*--------------------------------------------------------
      Classes
  --------------------------------------------------------*/

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
 * @package chamilo.exercise.qti
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
        $out = '<section ident="EXO_' . $this->exercise->getId() . '" title="' . $this->exercise->getTitle() . '">' . "\n";
        return $out;
    }

    function end_section()
    {
        return "</section>\n";
    }

    function export_duration()
    {
        if ($max_time = $this->exercise->getTimeLimit())
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
             . "  <mattext><![CDATA[" . $this->exercise->getDescription() . "]]></mattext>\n"
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
        foreach ($this->exercise->getQuestionList() as $q)
        {
            $out .= export_question($q['id'], False);
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
                  . '<!DOCTYPE questestinterop SYSTEM "ims_qtiasiv1p2p1.dtd">' . "\n"
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
 * Warning: Attached files are NOT exported.
 * @package chamilo.exercise.qti
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
        $this->questionIdent = "QST_" . $question->getId() ;
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
        return '<item title="' . htmlspecialchars($this->question->getTitle()) . '" ident="' . $this->questionIdent . '">' . "\n";
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
             . '<material><mattext><![CDATA[' . $this->question->getDescription() . "]]></mattext></material>\n";
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
                  . '<!DOCTYPE questestinterop SYSTEM "ims_qtiasiv1p2p1.dtd">' . "\n"
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
 * @author Amand Tihon <amand@alrj.org>
 */
function export_exercise($exerciseId, $standalone=True)
{
    $exercise = new Exercise();
    if (! $exercise->load($exerciseId))
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
 * @author Amand Tihon <amand@alrj.org>
 */
function export_question($questionId, $standalone=True)
{
    $question = new ImsQuestion();
    if( !$question->load($questionId) )
    {
        return '';
    }

    $ims = new ImsItem($question);

    return $ims->export($standalone);

}

?>
