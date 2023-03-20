<?php

/* For licensing terms, see /license.txt */

/**
 * Class HotSpot.
 *
 * This class allows to instantiate an object of
 * type HotSpot (MULTIPLE CHOICE, UNIQUE ANSWER)
 * extending the class question
 *
 * @author Eric Marguin
 */
class HotSpot extends Question
{
    public $typePicture = 'hotspot.png';
    public $explanationLangVar = 'Image zones';

    public function __construct()
    {
        parent::__construct();
        $this->type = HOT_SPOT;
    }

    public function display()
    {
    }

    public function createForm(&$form, $exercise)
    {
        parent::createForm($form, $exercise);

        if (!isset($_GET['editQuestion'])) {
            $icon = Display::return_icon(
                'hotspot.png',
                null,
                null,
                ICON_SIZE_BIG,
                false,
                true
            );
            $form->addFile(
                'imageUpload',
                get_lang('Upload image (jpg, png or gif) to apply hotspots.'),
                [
                    //'<img src="'.$icon.'" />',
                ]
            );

            // setting the save button here and not in the question class.php
            // Saving a question
            $form->addButtonSave(get_lang('Go to question'), 'submitQuestion');
            $form->addRule(
                'imageUpload',
                get_lang('Only PNG, JPG or GIF images allowed'),
                'filetype',
                ['jpg', 'jpeg', 'png', 'gif']
            );
            $form->addRule('imageUpload', get_lang('Please select an image'), 'uploadedfile');
        } else {
            // setting the save button here and not in the question class.php
            // Editing a question
            $form->addButtonUpdate(get_lang('Save the question'), 'submitQuestion');
        }
    }

    public function processCreation($form, $exercise)
    {
        $fileInfo = $form->getSubmitValue('imageUpload');
        parent::processCreation($form, $exercise);

        if (!empty($fileInfo['tmp_name'])) {
            $this->save($exercise);
        }

        return false;
    }

    public function createAnswersForm($form)
    {
        // nothing
    }

    public function processAnswersCreation($form, $exercise)
    {
        // nothing
    }

    /**
     * {@inheritdoc}
     */
    public function return_header(Exercise $exercise, $counter = null, $score = [])
    {
        return parent::return_header($exercise, $counter, $score)
            .'<table><tr><td><table class="table">';
    }

    public static function getLangVariables()
    {
        return [
            'Square' => get_lang('Square'),
            'Ellipse' => get_lang('Ellipse'),
            'Polygon' => get_lang('Polygon'),
            'HotspotStatus1' => get_lang('Draw a hotspot'),
            'HotspotStatus2Polygon' => get_lang('Use right-click to close the polygon'),
            'HotspotStatus2Other' => get_lang('Release the mousebutton to save the hotspot'),
            'HotspotStatus3' => get_lang('Hotspot saved'),
            'HotspotShowUserPoints' => get_lang('Show/Hide userclicks'),
            'ShowHotspots' => get_lang('Show / Hide hotspots'),
            'Triesleft' => get_lang('Attempts left'),
            'HotspotExerciseFinished' => get_lang('Now click on the button below to validate your answers'),
            'NextAnswer' => get_lang('Now click on:'),
            'Delineation' => get_lang('Delineation'),
            'CloseDelineation' => get_lang('Close delineation'),
            'Oar' => get_lang('Area to avoid'),
            'ClosePolygon' => get_lang('Close polygon'),
            'DelineationStatus1' => get_lang('Use right-click to close the delineation'),
        ];
    }
}
