<?php
/* For licensing terms, see /license.txt */

/**
 * Class HotSpot
 *
 * This class allows to instantiate an object of
 * type HotSpot (MULTIPLE CHOICE, UNIQUE ANSWER)
 * extending the class question
 *
 * @author Eric Marguin
 * @package chamilo.exercise
 **/
class HotSpot extends Question
{
    public static $typePicture = 'hotspot.png';
    public static $explanationLangVar = 'HotSpot';

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
     * @param FormValidator $form
     * @param int $fck_config
     */
    public function createForm(&$form, $fck_config = 0)
    {
        parent::createForm($form, $fck_config);

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
                array(
                    '<img src="'.$icon.'" />',
                    get_lang('UploadJpgPicture'),
                )
            );

            // setting the save button here and not in the question class.php
            // Saving a question
            $form->addButtonSave(get_lang('GoToQuestion'), 'submitQuestion');
            //$form->addButtonSave(get_lang('GoToQuestion'), 'submitQuestion');
            $form->addRule(
                'imageUpload',
                get_lang('OnlyImagesAllowed'),
                'filetype',
                array('jpg', 'jpeg', 'png', 'gif')
            );
            $form->addRule('imageUpload', get_lang('NoImage'), 'uploadedfile');
        } else {
            // setting the save button here and not in the question class.php
            // Editing a question
            $form->addButtonUpdate(get_lang('ModifyExercise'), 'submitQuestion');
        }
    }

    /**
     * @param FormValidator $form
     * @param null $objExercise
     * @return bool
     */
    public function processCreation($form, $objExercise = null)
    {
        $file_info = $form->getSubmitValue('imageUpload');
        parent::processCreation($form, $objExercise);

        if (!empty($file_info['tmp_name'])) {
            $result = $this->uploadPicture($file_info['tmp_name']);
            if ($result) {
                $this->save();
                return true;
            } else {
                return false;
            }
        }
        return false;
    }

    function createAnswersForm($form)
    {
        // nothing
    }

    function processAnswersCreation($form)
    {
        // nothing
    }
}

/**
 * Class HotSpotDelineation
 */
class HotSpotDelineation extends HotSpot
{
    public static $typePicture = 'hotspot-delineation.png';
    public static $explanationLangVar = 'HotspotDelineation';

    /**
     * HotSpotDelineation constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this -> type = HOT_SPOT_DELINEATION;
    }

    public function createForm(&$form, $fck_config = 0)
    {
        parent::createForm($form, $fck_config);
    }

    public function processCreation($form, $objExercise = null)
    {
        $file_info = $form->getSubmitValue('imageUpload');
        parent::processCreation($form, $objExercise);
    }

    public function createAnswersForm($form)
    {
        parent::createAnswersForm($form);
    }

    public function processAnswersCreation($form)
    {
        parent::processAnswersCreation($form);
    }
}
