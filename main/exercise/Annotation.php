<?php
/* For licensing terms, see /license.txt */

/**
 * Class Annotation
 * Allow instanciate an object of type HotSpot extending the class question
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.
 */
class Annotation extends Question
{
    public static $typePicture = 'annotation.png';
    public static $explanationLangVar = 'Annotation';

    /**
     * Annotation constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->type = ANNOTATION;
        $this->isContent = $this->getIsContent();
    }

    public function display()
    {
    }

    /**
     * @inheritdoc
     */
    public function createForm(&$form, $exercise)
    {
        parent::createForm($form, $exercise);

        $form->addElement(
            'number',
            'weighting',
            get_lang('Weighting'),
            ['step' => '0.1']
        );

        if (!empty($this->id)) {
            $form->setDefaults(array('weighting' => float_format($this->weighting, 1)));
        } else {
            if ($this->isContent == 1) {
                $form->setDefaults(array('weighting' => '10'));
            }
        }

        if (isset($_GET['editQuestion'])) {
            $form->addButtonUpdate(get_lang('ModifyExercise'), 'submitQuestion');

            return;
        }

        $form->addElement(
            'file',
            'imageUpload',
            array(
                Display::img(
                    Display::return_icon(
                        'annotation.png',
                        null,
                        null,
                        ICON_SIZE_BIG,
                        false,
                        true
                    )
                ),
                get_lang('UploadJpgPicture'),
            )
        );

        $form->addButtonSave(get_lang('GoToQuestion'), 'submitQuestion');
        $form->addRule(
            'imageUpload',
            get_lang('OnlyImagesAllowed'),
            'filetype',
            array('jpg', 'jpeg', 'png', 'gif')
        );
        $form->addRule('imageUpload', get_lang('NoImage'), 'uploadedfile');
    }

    /**
     * @inheritdoc
     */
    public function processCreation($form, $exercise)
    {
        $fileInfo = $form->getSubmitValue('imageUpload');
        parent::processCreation($form, $exercise);

        if (!empty($fileInfo['tmp_name'])) {
            $result = $this->uploadPicture($fileInfo['tmp_name']);
            if ($result) {
                $this->weighting = $form->getSubmitValue('weighting');
                $this->save($exercise);
                return true;
            }

            return false;
        }
        return false;
    }

    /**
     * @param FormValidator $form
     */
    public function createAnswersForm($form)
    {
        // nothing
    }

    /**
     * @inheritdoc
     */
    public function processAnswersCreation($form, $exercise)
    {
        $this->weighting = $form->getSubmitValue('weighting');
        $this->save($exercise);
    }
}
