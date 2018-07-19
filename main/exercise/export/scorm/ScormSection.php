<?php
/* For licensing terms, see /license.txt */

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
 *
 * @package chamilo.exercise.scorm
 */
class ScormSection
{
    public $exercise;
    public $standalone;

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

    /**
     * Send a complete exercise in SCORM format, from its ID.
     *
     * @param Exercise $exercise   The exercise to export
     * @param bool     $standalone wether it should include XML tag and DTD line
     *
     * @return string XML as a string, or an empty string if there's no exercise with given ID
     */
    public static function exportExerciseToScorm(
        Exercise $exercise,
        $standalone = true
    ) {
        $ims = new ScormSection($exercise);
        $xml = $ims->export($standalone);

        return $xml;
    }

    /**
     * Start the XML flow.
     *
     * This opens the <item> block, with correct attributes.
     */
    public function start_page()
    {
        $charset = 'UTF-8';
        $head = '<?xml version="1.0" encoding="'.$charset.'" standalone="no"?><html>';

        return $head;
    }

    /**
     * End the XML flow, closing the </item> tag.
     */
    public function end_page()
    {
        return '</html>';
    }

    /**
     * Start document header.
     */
    public function start_header()
    {
        return '<head>';
    }

    /**
     * Common JS functions.
     */
    public function common_js()
    {
        $js = file_get_contents('../inc/lib/javascript/hotspot/js/hotspot.js');

        $js .= 'var questions = new Array();'."\n";
        $js .= 'var questions_answers = new Array();'."\n";
        $js .= 'var questions_answers_correct = new Array();'."\n";
        $js .= 'var questions_types = new Array();'."\n";
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
        $js .= 'addEvent(window,\'load\',addListeners,false);'."\n";

        return $js."\n";
    }

    /**
     * End the itemBody part.
     */
    public function end_js()
    {
        return '</script>';
    }

    /**
     * Start the itemBody.
     */
    public function start_body()
    {
        return '<body>'.
            '<h1>'.$this->exercise->selectTitle().'</h1><p>'.$this->exercise->selectDescription()."</p>".
            '<form id="dokeos_scorm_form" method="post" action="">'.
            '<table width="100%">';
    }

    /**
     * End the itemBody part.
     */
    public function end_body()
    {
        return '</table><br /><input class="btn btn-primary" type="button" id="dokeos_scorm_submit" name="dokeos_scorm_submit" value="OK" /></form></body>';
    }

    /**
     * Export the question as a SCORM Item.
     *
     * This is a default behaviour, some classes may want to override this.
     *
     * @param $standalone: Boolean stating if it should be exported as a stand-alone question
     *
     * @return string string, the XML flow for an Item
     */
    public function export()
    {
        global $charset;

        $head = '';
        if ($this->standalone) {
            $head = '<?xml version = "1.0" encoding = "'.$charset.'" standalone = "no"?>'."\n"
                .'<!DOCTYPE questestinterop SYSTEM "ims_qtiasiv2p1.dtd">'."\n";
        }

        list($js, $html) = $this->export_questions();
        $res = $this->start_page()
            .$this->start_header()
            .$this->css()
            .$this->globalAssets()
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
    }

    /**
     * Export the questions, as a succession of <items>.
     *
     * @author Amand Tihon <amand@alrj.org>
     */
    public function export_questions()
    {
        $js = $html = '';
        $js_id = 0;
        foreach ($this->exercise->selectQuestionList() as $q) {
            list($jstmp, $htmltmp) = ScormQuestion::export_question($q, false, $js_id);
            $js .= $jstmp."\n";
            $html .= $htmltmp."\n";
            $js_id++;
        }

        return [$js, $html];
    }

    /**
     * Print CSS inclusion.
     */
    private function css()
    {
        return '';
    }

    /**
     * End document header.
     */
    private function end_header()
    {
        return '</head>';
    }

    /**
     * Start the itemBody.
     */
    private function start_js()
    {
        return '<script>';
    }

    /**
     * @return string
     */
    private function globalAssets()
    {
        $assets = '<script type="text/javascript" src="assets/jquery/jquery.min.js"></script>';
        $assets .= '<script type="text/javascript" src="assets/api_wrapper.js"></script>';
        $assets .= '<link href="assets/bootstrap/bootstrap.min.css" rel="stylesheet" media="screen" type="text/css" />';

        return $assets;
    }
}
