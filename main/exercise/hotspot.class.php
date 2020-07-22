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
    public $explanationLangVar = 'HotSpot';

    /**
     * HotSpot constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->type = HOT_SPOT;
    }

    public function display()
    {
    }

    /**
     * {@inheritdoc}
     */
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
            $form->addElement(
                'file',
                'imageUpload',
                [
                    '<img src="'.$icon.'" />',
                    get_lang('UploadJpgPicture'),
                ]
            );

            // setting the save button here and not in the question class.php
            // Saving a question
            $form->addButtonSave(get_lang('GoToQuestion'), 'submitQuestion');
            $form->addRule(
                'imageUpload',
                get_lang('OnlyImagesAllowed'),
                'filetype',
                ['jpg', 'jpeg', 'png', 'gif']
            );
            $form->addRule('imageUpload', get_lang('NoImage'), 'uploadedfile');
        } else {
            // setting the save button here and not in the question class.php
            // Editing a question
            $form->addButtonUpdate(get_lang('ModifyQuestion'), 'submitQuestion');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function processCreation($form, $exercise)
    {
        $fileInfo = $form->getSubmitValue('imageUpload');
        parent::processCreation($form, $exercise);

        if (!empty($fileInfo['tmp_name'])) {
            $result = $this->uploadPicture($fileInfo['tmp_name']);
            if ($result) {
                $this->save($exercise);

                return true;
            }
        }

        return false;
    }

    public function createAnswersForm($form)
    {
        // nothing
    }

    /**
     * {@inheritdoc}
     */
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
}
