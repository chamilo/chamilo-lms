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
     function export($standalone = False)
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
function export_exercise($exerciseId, $standalone=True)
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
function export_question($questionId, $standalone=True)
{
    $question = new Ims2Question();
    if( !$question->read($questionId) )
    {
        return '';
    }
    
    $ims = new ImsAssessmentItem($question);
    
    return $ims->export($standalone);

}

?>