<?php

/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CQuiz;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

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
 * @author Julio Montoya
 * @author Amand Tihon <amand@alrj.org>
 */
class ScormExercise
{
    public $exercise;
    public $standalone;

    /**
     * ScormExercise constructor.
     *
     * @param Exercise $exe
     * @param bool     $standalone
     */
    public function __construct($exe, $standalone)
    {
        $this->exercise = $exe;
        $this->standalone = $standalone;
    }

    /**
     * Start the XML flow.
     *
     * This opens the <item> block, with correct attributes.
     */
    public function startPage()
    {
        $charset = 'UTF-8';

        return '<?xml version="1.0" encoding="'.$charset.'" standalone="no"?><html>';
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
        $js = file_get_contents(api_get_path(SYS_CODE_PATH).'exercise/export/scorm/common.js');

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
            '<h1>'.$this->exercise->selectTitle().'</h1><p>'.$this->exercise->selectDescription().'</p>'.
            '<form id="chamilo_scorm_form" method="post" action="">'.
            '<table width="100%">';
    }

    /**
     * End the itemBody part.
     */
    public function end_body()
    {
        $button = '<input
            id="chamilo_scorm_submit"
            class="btn btn-primary"
            type="button"
            name="chamilo_scorm_submit"
            value="OK" />';

        return '</table><br />'.$button.'</form></body>';
    }

    /**
     * Export the question as a SCORM Item.
     *
     * This is a default behaviour, some classes may want to override this.
     *
     * @return string string, the XML flow for an Item
     */
    public function export()
    {
        global $charset;

        /*$head = '';
        if ($this->standalone) {
            $head = '<?xml version = "1.0" encoding = "'.$charset.'" standalone = "no"?>'."\n"
                .'<!DOCTYPE questestinterop SYSTEM "ims_qtiasiv2p1.dtd">'."\n";
        }*/

        list($js, $html) = $this->exportQuestions();

        return $this->startPage()
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
    }

    /**
     * Export the questions, as a succession of <items>.
     *
     * @author Amand Tihon <amand@alrj.org>
     */
    public function exportQuestions()
    {
        $js = $html = '';
        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];

        $em = Database::getManager();
        // Export cquiz data
        /** @var CQuiz $exercise */
        $exercise = $em->find('ChamiloCourseBundle:CQuiz', $this->exercise->iid);
        $exercise->setDescription('');
        $exercise->setTextWhenFinished('');

        $serializer = new Serializer($normalizers, $encoders);
        $jsonContent = $serializer->serialize($exercise, 'json');
        $js .= "var exerciseInfo = JSON.parse('".$jsonContent."');\n";

        $counter = 0;
        $scormQuestion = new ScormQuestion();
        foreach ($this->exercise->selectQuestionList() as $q) {
            list($jstmp, $htmltmp) = $scormQuestion->exportQuestionToScorm($q, $counter);
            $js .= $jstmp."\n";
            $html .= $htmltmp."\n";
            $counter++;
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
        $assets = '<script type="text/javascript" src="assets/jquery/jquery.min.js"></script>'."\n";
        $assets .= '<script type="text/javascript" src="assets/api_wrapper.js"></script>'."\n";
        $assets .= '<link href="assets/bootstrap/bootstrap.min.css" rel="stylesheet" media="screen" type="text/css" />';

        return $assets;
    }
}
