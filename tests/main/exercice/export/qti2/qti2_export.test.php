<?php

//Is possible than this functions are not implemented or some are deprecated, this file will not
//available for the test suite.

class TestQti2Export extends UnitTestCase {
	
	public $qImsAssessmentItem;
	public $qImsItem;
	public $qImsSection;
	
	
	public function TestQti2Export() {
		$this->UnitTestCase('');
	}
	
	public function setUp() {	

		//$objQuestion = Question::read(1);		
		$objQuestion = Question::read(1);
		
		$question = new Ims2Question();
	    /*
	    $qst = $question->read(1);
	    if( !$qst or $qst->type == FREE_ANSWER)
	    {
	        return '';
	    }
	    $question->id = $qst->id;
	    $question->type = $qst->type;
	    $question->question = $qst->question;
	    $question->description = $qst->description;
		$question->weighting=$qst->weighting;
		$question->position=$qst->position;
		$question->picture=$qst->picture;
		*/
						
		$this->qImsAssessmentItem = new ImsAssessmentItem($question);		
		//$this->qImsItem = new ImsItem(1);
		//$this->qImsSection = new ImsSection(1);
	}
	
	public function tearDown() {		
		$this-> qImsAssessmentItem = null;
		$this-> qImsItem = null;
		$this-> qImsSection = null;
	}
	
//Class ImsAssessmentItem
	
	 /**
     * Constructor.
     * @param $question The Question object we want to export.
     */
     /*
	function testImsAssessmentItem() {
		$question = array();
		$res = $this->qImsAssessmentItem->ImsAssessmentItem($question);
			if(!is_null){
			$this->assertTrue(is_bool($res));
			}
			var_dump($res);
	}  
	*/
	
	function teststart_item() {
		/*
		$res = $this->qImsAssessmentItem->start_item();
		if(!is_null){
			$this->assertTrue(is_bool($res));
		}
		var_dump($res);
		*/
	} 
	
	
	
	
	
	
	
	
	
	
}
?>
