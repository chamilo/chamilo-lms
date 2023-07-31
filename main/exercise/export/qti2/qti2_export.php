<?php

/* For licensing terms, see /license.txt */

/**
 * @author Claro Team <cvs@claroline.net>
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 */
require __DIR__.'/qti2_classes.php';

/**
 * An IMS/QTI item. It corresponds to a single question.
 * This class allows export from Claroline to IMS/QTI2.0 XML format of a single question.
 * It is not usable as-is, but must be subclassed, to support different kinds of questions.
 *
 * Every start_*() and corresponding end_*(), as well as export_*() methods return a string.
 *
 * note: Attached files are NOT exported.
 */
class ImsAssessmentItem
{
    /**
     * @var Ims2Question
     */
    public $question;
    /**
     * @var string
     */
    public $questionIdent;
    /**
     * @var ImsAnswerInterface
     */
    public $answer;

    /**
     * Constructor.
     *
     * @param Ims2Question $question ims2Question object we want to export
     */
    public function __construct($question)
    {
        $this->question = $question;
        $this->answer = $this->question->setAnswer();
        $this->questionIdent = 'QST_'.$question->iid;
    }

    /**
     * Start the XML flow.
     *
     * This opens the <item> block, with correct attributes.
     */
    public function start_item()
    {
        $categoryTitle = '';
        if (!empty($this->question->category)) {
            $category = new TestCategory();
            $category = $category->getCategory($this->question->category);
            if ($category) {
                $categoryTitle = htmlspecialchars(formatExerciseQtiText($category->name));
            }
        }

        return '<assessmentItem xmlns="http://www.imsglobal.org/xsd/imsqti_v2p1"
                xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                xsi:schemaLocation="http://www.imsglobal.org/xsd/imsqti_v2p1 imsqti_v2p1.xsd"
                identifier="'.$this->questionIdent.'"
                title = "'.htmlspecialchars(formatExerciseQtiText($this->question->selectTitle())).'"
                category = "'.$categoryTitle.'"
        >'."\n";
    }

    /**
     * End the XML flow, closing the </item> tag.
     */
    public function end_item()
    {
        return "</assessmentItem>\n";
    }

    /**
     * Start the itemBody.
     */
    public function start_item_body()
    {
        return '  <itemBody>'."\n";
    }

    /**
     * End the itemBody part.
     */
    public function end_item_body()
    {
        return "  </itemBody>\n";
    }

    /**
     * add the response processing template used.
     */
    public function add_response_processing()
    {
        return '  <responseProcessing template="http://www.imsglobal.org/question/qti_v2p1/rptemplates/map_correct"/>'."\n";
    }

    /**
     * Export the question as an IMS/QTI Item.
     *
     * This is a default behaviour, some classes may want to override this.
     *
     * @return string string, the XML flow for an Item
     */
    public function export($standalone = false)
    {
        $head = $foot = '';
        if ($standalone) {
            $head = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>'."\n";
        }

        //TODO understand why answer might be a non-object sometimes
        if (!is_object($this->answer)) {
            return $head;
        }

        return $head
            .$this->start_item()
            .$this->answer->imsExportResponsesDeclaration($this->questionIdent, $this->question)
            .$this->start_item_body()
            .$this->answer->imsExportResponses(
                $this->questionIdent,
                $this->question->question,
                $this->question->description,
                $this->question->getPictureFilename()
            )
            .$this->end_item_body()
            .$this->add_response_processing()
            .$this->end_item()
            .$foot;
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
    public $exercise;

    /**
     * Constructor.
     *
     * @param Exercise $exe The Exercise instance to export
     *
     * @author Amand Tihon <amand@alrj.org>
     */
    public function __construct($exe)
    {
        $this->exercise = $exe;
    }

    public function start_section()
    {
        return '<section
            ident = "EXO_'.$this->exercise->selectId().'"
            title = "'.cleanAttribute(formatExerciseQtiDescription($this->exercise->selectTitle())).'"
        >'."\n";
    }

    public function end_section()
    {
        return "</section>\n";
    }

    public function export_duration()
    {
        if ($max_time = $this->exercise->selectTimeLimit()) {
            // return exercise duration in ISO8601 format.
            $minutes = floor($max_time / 60);
            $seconds = $max_time % 60;

            return '<duration>PT'.$minutes.'M'.$seconds."S</duration>\n";
        } else {
            return '';
        }
    }

    /**
     * Export the presentation (Exercise's description).
     *
     * @author Amand Tihon <amand@alrj.org>
     */
    public function export_presentation()
    {
        return "<presentation_material><flow_mat><material>\n"
             .'  <mattext><![CDATA['.formatExerciseQtiDescription($this->exercise->selectDescription())."]]></mattext>\n"
             ."</material></flow_mat></presentation_material>\n";
    }

    /**
     * Export the ordering information.
     * Either sequential, through all questions, or random, with a selected number of questions.
     *
     * @author Amand Tihon <amand@alrj.org>
     */
    public function export_ordering()
    {
        $out = '';
        if ($n = $this->exercise->getShuffle()) {
            $out .= "<selection_ordering>"
                 ."  <selection>\n"
                 ."    <selection_number>".$n."</selection_number>\n"
                 ."  </selection>\n"
                 .'  <order order_type="Random" />'
                 ."\n</selection_ordering>\n";
        } else {
            $out .= '<selection_ordering sequence_type="Normal">'."\n"
                 ."  <selection />\n"
                 ."</selection_ordering>\n";
        }

        return $out;
    }

    /**
     * Export the questions, as a succession of <items>.
     *
     * @author Amand Tihon <amand@alrj.org>
     */
    public function exportQuestions()
    {
        $out = '';
        foreach ($this->exercise->selectQuestionList() as $q) {
            $out .= export_question_qti($q, false);
        }

        return $out;
    }

    /**
     * Export the exercise in IMS/QTI.
     *
     * @param bool $standalone wether it should include XML tag and DTD line
     *
     * @return string string containing the XML flow
     *
     * @author Amand Tihon <amand@alrj.org>
     */
    public function export($standalone)
    {
        $head = $foot = '';
        if ($standalone) {
            $head = '<?xml version = "1.0" encoding = "UTF-8" standalone = "no"?>'."\n"
                  .'<!DOCTYPE questestinterop SYSTEM "ims_qtiasiv2p1.dtd">'."\n"
                  ."<questestinterop>\n";
            $foot = "</questestinterop>\n";
        }

        return $head
             .$this->start_section()
             .$this->export_duration()
             .$this->export_presentation()
             .$this->export_ordering()
             .$this->exportQuestions()
             .$this->end_section()
             .$foot;
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
 * Class ImsItem.
 *
 * An IMS/QTI item. It corresponds to a single question.
 * This class allows export from Claroline to IMS/QTI XML format.
 * It is not usable as-is, but must be subclassed, to support different kinds of questions.
 *
 * Every start_*() and corresponding end_*(), as well as export_*() methods return a string.
 *
 * warning: Attached files are NOT exported.
 *
 * @author Amand Tihon <amand@alrj.org>
 */
class ImsItem
{
    public $question;
    public $questionIdent;
    public $answer;

    /**
     * Constructor.
     *
     * @param Question $question the Question object we want to export
     *
     * @author Anamd Tihon
     */
    public function __construct($question)
    {
        $this->question = $question;
        $this->answer = $question->answer;
        $this->questionIdent = 'QST_'.$question->selectId();
    }

    /**
     * Start the XML flow.
     *
     * This opens the <item> block, with correct attributes.
     *
     * @author Amand Tihon <amand@alrj.org>
     */
    public function start_item()
    {
        return '<item title="'.cleanAttribute(formatExerciseQtiDescription($this->question->selectTitle())).'" ident="'.$this->questionIdent.'">'."\n";
    }

    /**
     * End the XML flow, closing the </item> tag.
     *
     * @author Amand Tihon <amand@alrj.org>
     */
    public function end_item()
    {
        return "</item>\n";
    }

    /**
     * Create the opening, with the question itself.
     *
     * This means it opens the <presentation> but doesn't close it, as this is the role of end_presentation().
     * In between, the export_responses from the subclass should have been called.
     *
     * @author Amand Tihon <amand@alrj.org>
     */
    public function start_presentation()
    {
        return '<presentation label="'.$this->questionIdent.'"><flow>'."\n"
            .'<material><mattext>'.formatExerciseQtiDescription($this->question->selectDescription())."</mattext></material>\n";
    }

    /**
     * End the </presentation> part, opened by export_header.
     *
     * @author Amand Tihon <amand@alrj.org>
     */
    public function end_presentation()
    {
        return "</flow></presentation>\n";
    }

    /**
     * Start the response processing, and declare the default variable, SCORE, at 0 in the outcomes.
     *
     * @author Amand Tihon <amand@alrj.org>
     */
    public function start_processing()
    {
        return '<resprocessing><outcomes><decvar vartype="Integer" defaultval="0" /></outcomes>'."\n";
    }

    /**
     * End the response processing part.
     *
     * @author Amand Tihon <amand@alrj.org>
     */
    public function end_processing()
    {
        return "</resprocessing>\n";
    }

    /**
     * Export the question as an IMS/QTI Item.
     *
     * This is a default behaviour, some classes may want to override this.
     *
     * @param bool $standalone Boolean stating if it should be exported as a stand-alone question
     *
     * @return string string, the XML flow for an Item
     *
     * @author Amand Tihon <amand@alrj.org>
     */
    public function export($standalone = false)
    {
        global $charset;
        $head = $foot = '';

        if ($standalone) {
            $head = '<?xml version = "1.0" encoding = "'.$charset.'" standalone = "no"?>'."\n"
                  .'<!DOCTYPE questestinterop SYSTEM "ims_qtiasiv2p1.dtd">'."\n"
                  ."<questestinterop>\n";
            $foot = "</questestinterop>\n";
        }

        return $head
            .$this->start_item()
            .$this->start_presentation()
            .$this->answer->imsExportResponses($this->questionIdent)
            .$this->end_presentation()
            .$this->start_processing()
            .$this->answer->imsExportProcessing($this->questionIdent)
            .$this->end_processing()
            .$this->answer->imsExportFeedback($this->questionIdent)
            .$this->end_item()
            .$foot;
    }
}

/**
 * Send a complete exercise in IMS/QTI format, from its ID.
 *
 * @param int  $exerciseId The exercise to export
 * @param bool $standalone wether it should include XML tag and DTD line
 *
 * @return string XML as a string, or an empty string if there's no exercise with given ID
 */
function export_exercise_to_qti($exerciseId, $standalone = true)
{
    $exercise = new Exercise();
    if (!$exercise->read($exerciseId)) {
        return '';
    }
    $ims = new ImsSection($exercise);

    return $ims->export($standalone);
}

/**
 * Returns the XML flow corresponding to one question.
 *
 * @param int  $questionId
 * @param bool $standalone (ie including XML tag, DTD declaration, etc)
 *
 * @return string
 */
function export_question_qti($questionId, $standalone = true)
{
    $question = new Ims2Question();
    $qst = $question->read($questionId);
    if (!$qst) {
        return '';
    }

    $isValid = $qst instanceof UniqueAnswer
        || $qst instanceof MultipleAnswer
        || $qst instanceof FreeAnswer
        || $qst instanceof MultipleAnswerDropdown
        || $qst instanceof MultipleAnswerDropdownCombination
    ;

    if (!$isValid) {
        return '';
    }

    $question->iid = $qst->iid;
    $question->type = $qst->type;
    $question->question = $qst->question;
    $question->description = $qst->description;
    $question->weighting = $qst->weighting;
    $question->position = $qst->position;
    $question->picture = $qst->picture;
    $question->category = $qst->category;
    $ims = new ImsAssessmentItem($question);

    return $ims->export($standalone);
}

/**
 * Clean text like a description.
 */
function formatExerciseQtiDescription($text)
{
    $entities = api_html_entity_decode($text);

    return htmlspecialchars($entities);
}

/**
 * Clean titles.
 *
 * @param $text
 *
 * @return string
 */
function formatExerciseQtiText($text)
{
    return htmlspecialchars($text);
}

/**
 * @param string $text
 *
 * @return string
 */
function cleanAttribute($text)
{
    return $text;
}
