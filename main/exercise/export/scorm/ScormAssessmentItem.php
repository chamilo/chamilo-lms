<?php
/* For licensing terms, see /license.txt */

/**
 * A SCORM item. It corresponds to a single question.
 * This class allows export from Dokeos SCORM 1.2 format of a single question.
 * It is not usable as-is, but must be subclassed, to support different kinds of questions.
 *
 * Every start_*() and corresponding end_*(), as well as export_*() methods return a string.
 *
 * Attached files are NOT exported.
 *
 * @package chamilo.exercise.scorm
 */
class ScormAssessmentItem
{
    public $question;
    public $question_ident;
    public $answer;
    public $standalone;

    /**
     * Constructor.
     *
     * @param ScormQuestion $question the Question object we want to export
     */
    public function __construct($question, $standalone = false)
    {
        $this->question = $question;
        $this->question->setAnswer();
        $this->questionIdent = "QST_".$question->id;
        $this->standalone = $standalone;
    }

    /**
     * Start the XML flow.
     *
     * This opens the <item> block, with correct attributes.
     */
    public function start_page()
    {
        $head = '';
        if ($this->standalone) {
            $charset = 'UTF-8';
            $head = '<?xml version="1.0" encoding="'.$charset.'" standalone="no"?>';
            $head .= '<html>';
        }

        return $head;
    }

    /**
     * End the XML flow, closing the </item> tag.
     */
    public function end_page()
    {
        if ($this->standalone) {
            return '</html>';
        }

        return '';
    }

    /**
     * Start document header.
     */
    public function start_header()
    {
        if ($this->standalone) {
            return '<head>';
        }

        return '';
    }

    /**
     * Print CSS inclusion.
     */
    public function css()
    {
        $css = '';
        if ($this->standalone) {
            $css = '<style type="text/css" media="screen, projection">';
            $css .= '/*<![CDATA[*/'."\n";
            $css .= '/*]]>*/'."\n";
            $css .= '</style>'."\n";
            $css .= '<style type="text/css" media="print">';
            $css .= '/*<![CDATA[*/'."\n";
            $css .= '/*]]>*/'."\n";
            $css .= '</style>';
        }

        return $css;
    }

    /**
     * End document header.
     */
    public function end_header()
    {
        if ($this->standalone) {
            return '</head>';
        }

        return '';
    }

    /**
     * Start the itemBody.
     */
    public function start_js()
    {
        $js = '<script type="text/javascript" src="assets/api_wrapper.js"></script>';
        if ($this->standalone) {
            return '<script>';
        }

        return $js;
    }

    /**
     * Common JS functions.
     */
    public function common_js()
    {
        $js = 'var questions = new Array();';
        $js .= 'var questions_answers = new Array();';
        $js .= 'var questions_answers_correct = new Array();';
        $js .= 'var questions_types = new Array();';
        $js .= "\n".
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
        $js .= 'addEvent(window,\'load\',addListeners,false);'."\n";
        if ($this->standalone) {
            return $js."\n";
        }

        return '';
    }

    /**
     * End the itemBody part.
     */
    public function end_js()
    {
        if ($this->standalone) {
            return '</script>';
        }

        return '';
    }

    /**
     * Start the itemBody.
     */
    public function start_body()
    {
        if ($this->standalone) {
            return '<body><form id="dokeos_scorm_form" method="post" action="">';
        }

        return '';
    }

    /**
     * End the itemBody part.
     */
    public function end_body()
    {
        if ($this->standalone) {
            return '<br /><input class="btn" type="button" id="dokeos_scorm_submit" name="dokeos_scorm_submit" value="OK" /></form></body>';
        }

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
        if ($this->standalone) {
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
        }
    }
}

