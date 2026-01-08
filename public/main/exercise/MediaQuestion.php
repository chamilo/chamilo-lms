<?php
/* For licensing terms, see /license.txt */

class MediaQuestion extends Question
{
    /**
     * Icon shown in the question-type menu.
     */
    public $typePicture = 'media.png';
    public $explanationLangVar = 'Question media';

    public function __construct()
    {
        parent::__construct();
        $this->type      = MEDIA_QUESTION;
        // Mark as content so it’s not counted towards score
        $this->isContent = 1;
    }

    /**
     * Form to create / edit a Media item.
     */
    public function createForm(&$form, $exercise)
    {
        // Title for the media block
        $form->addText(
            'questionName',
            get_lang('Title'),
            false,
            ['maxlength' => 255]
        );

        // WYSIWYG for the media content (could be text, embed code, etc.)
        $editorConfig = [
            'ToolbarSet' => 'TestQuestionDescription',
            'Height'     => '150'
        ];
        $form->addHtmlEditor(
            'questionDescription',
            get_lang('Content'),
            false,
            false,
            $editorConfig
        );

        global $text;
        $form->addButtonSave($text, 'submitQuestion');

        // Populate defaults if editing
        $defaults = [
            'questionName'        => $this->question,
            'questionDescription' => $this->description
        ];
        $form->setDefaults($defaults);
    }

    /**
     * No answers to configure for media.
     */
    public function createAnswersForm($form) {}

    public function processAnswersCreation($form, $exercise) {}

    /**
     * On save, treat like any other question: persist and attach to the exercise.
     */
    public function processCreation(FormValidator $form, Exercise $exercise)
    {
        $this->updateTitle($form->getSubmitValue('questionName'));
        $this->updateDescription($form->getSubmitValue('questionDescription'));
        $this->save($exercise);
        $exercise->addToList($this->id);
    }
}
