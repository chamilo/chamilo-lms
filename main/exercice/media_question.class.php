<?php
/* For licensing terms, see /license.txt */

class MediaQuestion extends Question
{
	static $typePicture = 'media-question.png';
	static $explanationLangVar = 'MediaQuestion';

    public function __construct()
    {
        parent::question();
		$this->type = MEDIA_QUESTION;
    }

    /**
     * @param \FormValidator $form
     */
    public function processAnswersCreation($form)
    {
        $params = $form->getSubmitValues();
        $this->saveMedia($params);
    }

    /**
     * @param array $params
     * @return int
     */
    public function saveMedia($params)
    {
        $table_question = Database::get_course_table(TABLE_QUIZ_QUESTION);
        $course_id = '';
        $questionName = '';

        if (!empty($this->course['real_id'])) {
            $course_id = $this->course['real_id'];
        } else {
            throw new Exception('Missing $this->course info in MediaQuestion::saveMedia()');
        }

        if (isset($params['questionName'])) {
            $questionName = $params['questionName'];
        } else {
            throw new Exception('Missing questionName in $params in MediaQuestion::saveMedia()');
        }

        $questionDescription = isset($params['questionDescription']) ? $params['questionDescription'] : '';

        $new_params = array(
            'c_id'          => $course_id,
            'question'      => $questionName,
            'description'   => $questionDescription,
            'parent_id'     => 0,
            'type'          => MEDIA_QUESTION
        );

        if (isset($this->id) && !empty($this->id)) {
            Database::update($table_question, $new_params, array('iid = ? and c_id = ?' => array($this->id, $course_id)));
        } else {
            return Database::insert($table_question, $new_params);
        }
    }

    /**
     * @param \FormValidator $form
     */
    public function createAnswersForm ($form)
    {
        $form->addElement('button', 'submitQuestion', get_lang('Save'));
    }
}
