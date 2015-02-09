<?php // $Id: $
/* For licensing terms, see /license.txt */
/**
 * @copyright (c) 2007 Dokeos
 * @copyright (c) 2001-2006 Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @author Claro Team <cvs@claroline.net>
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 * @package chamilo.exercise
 */
/**
 * Code
 */
if ( count( get_included_files() ) == 1 ) die( '---' );
require_once '../../exercise.class.php';
require_once '../../question.class.php';
require_once '../../answer.class.php';
require_once '../../unique_answer.class.php';
require_once '../../multiple_answer.class.php';
require_once '../../fill_blanks.class.php';
require_once '../../freeanswer.class.php';
require_once '../../hotspot.class.php';
require_once '../../matching.class.php';
require_once '../../hotspot.class.php';

/**
 *
 * @package chamilo.exercise
 */
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
