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
                    get_lang('Upload image (jpg, png or gif) to apply hotspots.'),
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

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function processAnswersCreation($form, $exercise)
    {
        // nothing
    }
}
