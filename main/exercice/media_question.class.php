<?php
/* For licensing terms, see /license.txt */

class MediaQuestion extends Question
{
	static $typePicture = 'looknfeel.png';
	static $explanationLangVar = 'MediaQuestion';

    function __construct()
    {
        parent::question();
		$this->type = MEDIA_QUESTION;
    }

    function processAnswersCreation($form)
    {
        $params = $form->getSubmitValues();
        $this->saveMedia($params);
    }

    function saveMedia($params)
    {
        $table_question = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $new_params = array(
            'c_id'          => $this->course['real_id'],
            'question'      => $params['questionName'],
            'description'   => $params['questionDescription'],
            'parent_id'     => 0,
            'type'          => MEDIA_QUESTION
        );

        if (isset($this->id) && !empty($this->id)) {
            Database::update($table_question, $new_params, array('iid = ? and c_id = ?' => array($this->id, $this->course['real_id'])));
        } else {
            return Database::insert($table_question, $new_params);
        }
    }

    function createAnswersForm ($form)
    {
        $form->addElement('button', 'submitQuestion', get_lang('Save'));
    }
}
