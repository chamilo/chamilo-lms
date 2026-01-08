<?php
/* For licensing terms, see /license.txt */

class PageBreakQuestion extends Question
{
    public $typePicture = 'page_end.png';
    public $explanationLangVar = 'Page break';

    public function __construct()
    {
        parent::__construct();
        $this->type      = PAGE_BREAK;
        $this->isContent = 1;
    }

    public function createForm(&$form, $exercise)
    {
        $form->addText(
            'questionName',
            get_lang('Title'),
            false,
            ['maxlength' => 255]
        );
        $editorConfig = [
            'ToolbarSet' => 'TestQuestionDescription',
            'Height'     => '100'
        ];
        $form->addHtmlEditor(
            'questionDescription',
            get_lang('Description'),
            false,
            false,
            $editorConfig
        );

        global $text;
        $form->addButtonSave($text, 'submitQuestion');

        $defaults = [
            'questionName'        => $this->question,
            'questionDescription' => $this->description
        ];
        $form->setDefaults($defaults);
    }

    public function createAnswersForm($form) {}

    public function processAnswersCreation($form, $exercise) {}

    public function processCreation(FormValidator $form, Exercise $exercise)
    {
        $this->updateTitle($form->getSubmitValue('questionName'));
        $this->updateDescription($form->getSubmitValue('questionDescription'));
        $this->save($exercise);
        $exercise->addToList($this->id);
    }
}
