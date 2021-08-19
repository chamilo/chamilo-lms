<?php

/* For licensing terms, see /license.txt */

/**
 * A SCORM item. It corresponds to a single question.
 * This class allows export from Chamilo SCORM 1.2 format of a single question.
 * It is not usable as-is, but must be subclassed, to support different kinds of questions.
 *
 * Every start_*() and corresponding end_*(), as well as export_*() methods return a string.
 *
 * Attached files are NOT exported.
 */
class ScormAssessmentItem
{
    public $question;
    public $question_ident;
    public $answer;

    /**
     * Constructor.
     *
     * @param ScormQuestion $question the Question object we want to export
     */
    public function __construct($question)
    {
        $this->question = $question;
        $this->question->setAnswer();
        $this->questionIdent = 'QST_'.$question->iid;
    }

    /**
     * Start the XML flow.
     *
     * This opens the <item> block, with correct attributes.
     */
    public function start_page()
    {
        return '';
    }

    /**
     * End the XML flow, closing the </item> tag.
     */
    public function end_page()
    {
        /*if ($this->standalone) {
            return '</html>';
        }*/

        return '';
    }

    /**
     * Start document header.
     */
    public function start_header()
    {
        /*if ($this->standalone) {
            return '<head>';
        }*/

        return '';
    }

    /**
     * Print CSS inclusion.
     */
    public function css()
    {
        return '';
    }

    /**
     * End document header.
     */
    public function end_header()
    {
//        if ($this->standalone) {
//            return '</head>';
//        }

        return '';
    }

    /**
     * Start the itemBody.
     */
    public function start_js()
    {
        return '<script type="text/javascript" src="assets/api_wrapper.js"></script>';
    }

    /**
     * End the itemBody part.
     */
    public function end_js()
    {
        /*if ($this->standalone) {
            return '</script>';
        }*/

        return '';
    }

    /**
     * Start the itemBody.
     */
    public function start_body()
    {
        /*if ($this->standalone) {
            return '<body><form id="dokeos_scorm_form" method="post" action="">';
        }*/

        return '';
    }

    /**
     * End the itemBody part.
     */
    public function end_body()
    {
        /*if ($this->standalone) {
            return '<br /><input class="btn" type="button" id="dokeos_scorm_submit" name="dokeos_scorm_submit" value="OK" /></form></body>';
        }*/

        return '';
    }

    /**
     * Export the question as a SCORM Item.
     * This is a default behaviour, some classes may want to override this.
     *
     * @return string|array a string, the XML flow for an Item
     */
    public function export()
    {
        list($js, $html) = $this->question->export();
        /*if ($this->standalone) {
            $res = $this->start_page()
                .$this->start_header()
                .$this->css()
                .$this->start_js()
                .$this->common_js()
                .$js
                .$this->end_js()
                .$this->end_header()
                .$this->start_body()
                .$html
                .$this->end_body()
                .$this->end_page();

            return $res;
        } else {
            return [$js, $html];
        }*/
        return [$js, $html];
    }
}
