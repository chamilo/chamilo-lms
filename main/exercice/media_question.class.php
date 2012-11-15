<?php

class MediaQuestion extends Question {
	static $typePicture = 'looknfeel.png';
	static $explanationLangVar = 'MediaQuestion';
    
    function __construct(){
        parent::question();
		$this -> type = MEDIA_QUESTION;		
    }
    
    function processAnswersCreation($form) {
        $params = $form->getSubmitValues();        
        $this->save_media($params);
    }
    
    function save_media($params) {
        $table_question = Database::get_course_table(TABLE_QUIZ_QUESTION);         
        $new_params = array(
            'c_id'      => api_get_course_int_id(),
            'question'  => $params['questionName'],
            'description'  => $params['questionDescription'],
            'parent_id' => 0,
            'type'      => MEDIA_QUESTION
        );        
        Database::insert($table_question, $new_params);
    }
    
    function createAnswersForm ($form) {
        $form->addElement('button', 'submitQuestion', get_lang('Save'));
    }
}