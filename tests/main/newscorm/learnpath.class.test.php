<?php
require_once(api_get_path(SYS_CODE_PATH).'newscorm/learnpath.class.php');

class TestLearnpath extends UnitTestCase {
	
	const course = 'COURSETEST';
	
	public function testAddItem() {
		//ob_start();
		$parent = 2;
		$previous = 1;
		$type = 'dokeos_chapter';
		$id = 1;
		$title = 'Titulo';
		$description = 'Descripcion';
		$prerequisites = 0;	
		$max_time_allowed = 0;
		
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath(self::course, $lp_id, $user_id); 
		
		$res = $obj->add_item($parent, $previous, $type, $id, $title, $description, $prerequisites, $max_time_allowed);
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	public function testAddLp() {
		//ob_start();
		$name = '';
		$description = '';
		$learnpath = 'guess';
		$origin = 'zip';
		$zipname = '';
		$res = learnpath::add_lp(self::course, $name, $description, $learnpath, $origin, $zipname);
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	public function testAppendMessage() {
		//ob_start();
		$string = '';
		$res = learnpath::append_message($string);
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
	/*	
	public function testAutocompleteParents() {
		//ob_start();
		$item = 1;
		
		$course='COURSETEST';
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath($course, $lp_id, $user_id); 
		$res = $obj->autocomplete_parents($item);
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	var_dump($res);
	}
	*/
	public function testAutosave() {
		//ob_start();
		$res = learnpath::autosave();
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	public function testBuildActionMenu() {
		ob_start();
		$res = learnpath::build_action_menu();
	 	$this->assertTrue(is_null($res));
		ob_end_clean();
	 	//var_dump($res);
	}
/*
	public function testBuildTree() {
		//ob_start();
		$course = 'COURSETEST';
		$lp_id = 0;
		$user_id = 1;
		$obj = new learnpath($course, $lp_id, $user_id); 
		$res = $obj->build_tree();
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
	*/	
	public function testClearMessage() {
		//ob_start();
		$res = learnpath::clear_message();
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	public function testClose() {
		//ob_start();
		$res = learnpath::close();
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	public function testCreateDocument() {
		//ob_start();
		$_course = '';
		$res = learnpath::create_document($_course);
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
/*	
	public function testCreateJs() {
		//ob_start();
		$res = learnpath::create_js();
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
*/		
	public function testCreatePath() {
		//ob_start();
		$path = '';
		$res = learnpath::create_path($path);
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	public function testCreateTreeArray() {
		//ob_start();
		$array = '';
		$parent = 0;
		$depth = -1;
		$tmp = array ();
		$res = learnpath::create_tree_array($array, $parent, $depth, $tmp);
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	public function testDisplayDocument() {
		//ob_start();
		$id = 1;
		$show_title = false;
		$iframe = true;
		$edit_link = false;
		$res = learnpath::display_document($id, $show_title, $iframe, $edit_link);
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
/*
	public function testDisplayDocumentForm() {
		//ob_start();
		$action = 'add';
		$id = 0;
		$extra_info = 'new';
		$course='COURSETEST';
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath($course, $lp_id, $user_id); 
		
		$res = $obj->display_document_form($action, $id, $extra_info);
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
*/		
	public function testDisplayEditItem() {
		//ob_start();
		$item_id = '';
		$res = learnpath::display_edit_item($item_id);
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
	/*
	public function testDisplayForumForm() {
		//ob_start();
		$action = 'add';
		$id = 0;
		$extra_info = '';
		$course='COURSETEST';
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath($course, $lp_id, $user_id); 
		
		$res = $obj->display_forum_form($action, $id, $extra_info);
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	public function testDisplayHotpotatoesForm() {
		//ob_start();
		$action = 'add';
		$id = 0;
		$extra_info = '';
		$course='COURSETEST';
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath($course, $lp_id, $user_id); 
		
		$res = $obj->display_hotpotatoes_form($action, $id, $extra_info);
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
*/
	public function testDisplayItem() {
		//ob_start();
		$item_id = '';
		$iframe = true;
		$msg = '';
		$res = learnpath::display_item($item_id, $iframe, $msg);
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
/*	public function testDisplayItemForm() {
		//ob_start();
		$item_type = '';
		$title = '';
		$action = 'add';
		$id = 0;
		$extra_info = 'new';
		$course='COURSETEST';
		$lp_id = 1;
		$user_id = 1;
		$obj = new learnpath($course, $lp_id, $user_id); 
		$res = $obj->display_item_form($item_type, $title, $action, $id, $extra_info);
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	public function testDisplayItemPrerequisitesForm() {
		//ob_start();
		$item_id = '';
		$course='COURSETEST';
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath($course, $lp_id, $user_id); 
		
		$res = $obj->display_item_prerequisites_form($item_id);
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
*/	
	public function testDisplayItemSmallForm() {
		//ob_start();
		$item_type = '';
		$title = '';
		$data = '';
		$res = learnpath::display_item_small_form($item_type, $title, $data);
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
/*		
	public function testDisplayLinkForm() {
		//ob_start();
		$action = 'add';
		$id = 0;
		$extra_info = '';
		$course='COURSETEST';
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath($course, $lp_id, $user_id); 
		
		$res = $obj->display_link_form($action, $id, $extra_info);
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	public function testDisplayManipulate() {
		//ob_start();
		$item_id = '';
		$item_type = TOOL_DOCUMENT;
		$res = learnpath::display_manipulate($item_id, $item_type);
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
*/
	public function testDisplayMoveItem() {
		//ob_start();
		$item_id = '';
		$res = learnpath::display_move_item($item_id);
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
/*		
	public function testDisplayQuizForm() {
		//ob_start();
		$action = 'add';
		$id = 0;
		$extra_info = '';
		$course='COURSETEST';
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath($course, $lp_id, $user_id); 
		
		$res = $obj->display_quiz_form($action, $id, $extra_info);
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
*/	
/*	public function testDisplayResources() {
		//ob_start();
		$course='COURSETEST';
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath($course, $lp_id, $user_id); 
		
		$res = $obj->display_resources();
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	public function testDisplayStudentPublicationForm() {
		//ob_start();
		$action = 'add';
		$id = 0;
		$extra_info = '';
		$course='COURSETEST';
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath($course, $lp_id, $user_id); 
		
		$res = $obj->display_student_publication_form($action, $id, $extra_info);
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	public function testDisplayThreadForm() {
		//ob_start();
		$action = 'add';
		$id = 0;
		$extra_info = '';
		$course='COURSETEST';
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath($course, $lp_id, $user_id); 
		
		$res = $obj->display_thread_form($action, $id, $extra_info);
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
*/		
	public function testEditDocument() {
		//ob_start();
		$_course='';
		$res = learnpath::edit_document($_course);
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testEditItem() {
		//ob_start();
		$id = '';
		$parent = '';
		$previous = '';
		$title = '';
		$description = '';
		$prerequisites = 0;
		$audio = NULL;
		$max_time_allowed = 0;
		$res = learnpath::edit_item($id, $parent, $previous, $title, $description, $prerequisites, $audio, $max_time_allowed);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	public function testEditItemPrereq() {
		//ob_start();
		$id = '';
		$prerequisite_id = '';
		$mastery_score = 0;
		$max_score = 100;
		$res = learnpath::edit_item_prereq($id, $prerequisite_id, $mastery_score, $max_score);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	public function testEscapeString() {
		//ob_start();
		$string = '';
		$res = learnpath::escape_string($string);
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	public function testExportLp() {
		//ob_start();
		$type = 'scorm';
		$id = 1;
		$zipname = 'FILE';
		$res = learnpath::export_lp($type, self::course, $id, $zipname);
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	public function testFirst() {
		//ob_start();
		$res = learnpath::first();
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	public function testGetAuthor() {
		//ob_start();
		$res = learnpath::get_author();
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
	
	public function testGetBrotherChapters() {
		//ob_start();
		$id = '';
		$res = learnpath::get_brother_chapters($id);
	 	$this->assertTrue(is_array($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	public function testGetBrotherItems() {
		//ob_start();
		$id = '';
		$res = learnpath::get_brother_items($id);
	 	$this->assertTrue(is_array($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
/*
	public function testGetCommonIndexTermsByPrefix() {
		//ob_start();
		$prefix = '';
		$res = learnpath::get_common_index_terms_by_prefix($prefix);
	 	$this->assertTrue(is_array($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	public function testGetCompleteItemsCount() {
		//ob_start();
		$res = learnpath::get_complete_items_count();
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
*/	
	public function testGetCurrentItemId() {
		//ob_start();
		$res = learnpath::get_current_item_id();
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	public function testGetDbProgress() {
		//ob_start();
		$lp_id = 0;
		$user_id = 1;
		$mode = '%';
		$course_db = '';
		$sincere = false;
		$res = learnpath::get_db_progress($lp_id, $user_id, $mode, $course_db, $sincere);
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	public function testGetDocuments() {
		//ob_start();
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath(self::course, $lp_id, $user_id); 
		
		$res = $obj->get_documents();
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	public function testGetExercises() {
		//ob_start();
		$res = learnpath::get_exercises();
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	public function testGetExtension() {
		//ob_start();
		$filename = 'file';
		$res = learnpath::get_extension($filename);
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	public function testGetFirstItemId() {
		//ob_start();
		$res = learnpath::get_first_item_id();
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	public function testGetFlatOrderedItemsList() {
		//ob_start();
		$lp = 1;
		$parent = 0;
		$res = learnpath::get_flat_ordered_items_list($lp, $parent);
	 	$this->assertTrue(is_array($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		/*
	public function testGetForums() {
		//ob_start();
		
		//require_once api_get_path(WEB_PATH).('forum/forumpublic function.inc.php');
		//require_once api_get_path(WEB_PATH).('forum/forumconfig.inc.php');
		$course='COURSETEST';
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath($course, $lp_id, $user_id); 
		
		$res = $obj->get_forums();
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	public function testGetHtmlToc() {
		//ob_start();
		$course='COURSETEST';
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath($course, $lp_id, $user_id); 
		
		$res = $obj->get_html_toc();
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
*/		
	public function testGetId() {
		//ob_start();
		$res = learnpath::get_id();
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testGetInteractionsCountFromDb() {
		//ob_start();
		$lp_iv_id = 0;
		$res = learnpath::get_interactions_count_from_db($lp_iv_id);
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
/*		
	public function testGetItemsDetailsAsJs() {
		//ob_start();
		$varname='olms.lms_item_types';
		$res = learnpath::get_items_details_as_js($varname);
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	public function testGetItemsStatusList() {
		//ob_start();
		$res = learnpath::get_items_status_list();
	 	$this->assertTrue(is_array($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	public function testGetIvInteractionsArray() {
		//ob_start();
		$lp_iv_id = '';
		$res = learnpath::get_iv_interactions_array($lp_iv_id);
	 	$this->assertTrue(is_array($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
*/
	public function testGetIvObjectivesArray() {
		//ob_start();
		$lp_iv_id = 0;
		$res = learnpath::get_iv_objectives_array($lp_iv_id);
	 	$this->assertTrue(is_array($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
	/*	
	public function testGetJsInfo() {
		//ob_start();
		$item_id = '';
		$course='COURSETEST';
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath($course, $lp_id, $user_id); 
		
		$res = $obj->get_js_info($item_id);
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	*/
	
	public function testGetJsLib() {
		//ob_start();
		$res = learnpath::get_js_lib();
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	public function testGetLast() {
		//ob_start();
		$res = learnpath::get_last();
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	public function testGetLink() {
		//ob_start();
		$type = 'http';
		$item_id = null;
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath(self::course, $lp_id, $user_id); 
		
		$res = $obj->get_link($type, $item_id);
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	public function testGetLinks() {
		//ob_start();
		$res = learnpath::get_links();
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testGetLpSessionId() {
		//ob_start();
		$res = learnpath::get_lp_session_id();
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	public function testGetMaker() {
		//ob_start();
		$res = learnpath::get_maker();
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	public function testGetMediaplayer() {
		//ob_start();
		$autostart='true';
		$res = learnpath::get_mediaplayer($autostart);
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	public function testGetMessage() {
		//ob_start();
		$res = learnpath::get_message();
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	public function testGetName() {
		//ob_start();
		$res = learnpath::get_name();
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	public function testGetNavigationBar() {
		//ob_start();
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath(self::course, $lp_id, $user_id); 
		
		$res = $obj->get_navigation_bar();
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
	
	public function testGetNextIndex() {
		//ob_start();
		$res = learnpath::get_next_index();
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	public function testGetNextItemId() {
		//ob_start();
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath(self::course, $lp_id, $user_id); 
		
		$res = $obj->get_next_item_id();
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	public function testGetObjectivesCountFromDb() {
		//ob_start();
		$lp_iv_id = 0;
		$res = learnpath::get_objectives_count_from_db($lp_iv_id);
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
	/*	
	public function testGetPackageType() {
		//ob_start();
		$file_path = '';
		$file_name = '';
		$course='COURSETEST';
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath($course, $lp_id, $user_id); 
		
		$res = $obj->get_package_type($file_path, $file_name);
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}*/
	
	public function testGetPreviewImage() {
		//ob_start();
		$res = learnpath::get_preview_image();
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	public function testGetPreviousIndex() {
		//ob_start();
		$res = learnpath::get_previous_index();
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	public function testGetPreviousItemId() {
		//ob_start();
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath(self::course, $lp_id, $user_id); 
		
		$res = $obj->get_previous_item_id();
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	public function testGetProgress() {
		//ob_start();
		$res = learnpath::get_progress();
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	public function testGetProgressBar() {
		//ob_start();
		$mode = '';
		$percentage = -1;
		$text_add = '';
		$from_lp = false;
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath(self::course, $lp_id, $user_id); 
		
		$res = $obj->get_progress_bar($mode, $percentage, $text_add, $from_lp);
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	public function testGetProgressBarMode() {
		//ob_start();
		$res = learnpath::get_progress_bar_mode();
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
	
	public function testGetProgressBarText() {
		//ob_start();
		$mode = '';
		$add = 0;
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath(self::course, $lp_id, $user_id); 
		
		$res = $obj->get_progress_bar_text($mode, $add);
	 	$this->assertTrue(is_array($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	public function testGetProximity() {
		//ob_start();
		$res = learnpath::get_proximity();
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	public function testGetScormPrereqString() {
		//ob_start();
		$item_id = 1;
		$res = learnpath::get_scorm_prereq_string($item_id);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	public function testGetScormXmlNode() {
		//ob_start();
		$children = 'children';
		$id = 1;		
		$res = learnpath::get_scorm_xml_node($children, $id);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testGetStats() {
		//ob_start();
		$res = learnpath::get_stats();
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	public function testGetStatsCourse() {
		//ob_start();
		$course = '';
		$res = learnpath::get_stats_course($course);
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	public function testGetStatsLp() {
		//ob_start();
		$lp = 1;
		$res = learnpath::get_stats_lp(self::course, $lp);
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	public function testGetStatsLpUser() {
		//ob_start();
		$lp = 1;
		$user = 1;
		$res = learnpath::get_stats_lp_user(self::course, $lp, $user);
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	public function testGetStatsUser() {
		//ob_start();
		$user = 1;
		$res = learnpath::get_stats_user(self::course, $user);
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	public function testGetStudentPublications() {
		//ob_start();
		$res = learnpath::get_student_publications();
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
	
	public function testGetTheme() {
		//ob_start();
		$res = learnpath::get_theme();
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
	/*	
	public function testGetToc() {
		//ob_start();
		$res = learnpath::get_toc();
	 	$this->assertTrue(is_array($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
*/
	public function testGetTotalItemsCount() {
		//ob_start();
		$res = learnpath::get_total_items_count();
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
/*		
	public function testGetTotalItemsCountWithoutChapters() {
		//ob_start();
		$res = learnpath::get_total_items_count_without_chapters();
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	*/
	public function testGetType() {
		//ob_start();
		$get_name = false;
		$res=learnpath::get_type($get_name) ;
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	public function testGetTypeStatic() {
		//ob_start();
		$lp_id = 0;
		$res = learnpath::get_type_static($lp_id);
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	public function testGetUpdateQueue() {
		//ob_start();
		$res = learnpath::get_update_queue();
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	public function testGetUserId() {
		//ob_start();
		$res = learnpath::get_user_id();
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
/*
	public function testGetView() {
		//ob_start();
		$attempt_num = 0;
		$course='COURSETEST';
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath($course, $lp_id, $user_id); 
		
		$res = $obj->get_view($attempt_num);
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
	*/	
	public function testGetViewId() {
		//ob_start();
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath(self::course, $lp_id, $user_id); 
		
		$res = $obj->get_view_id();
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
	/*
	public function testHasAudio() {
		//ob_start();
		$res = learnpath::has_audio();
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}*/	
		
	public function testLearnpath() {
		//ob_start();
		$course = '';
		$lp_id = '';
		$user_id = '';
		$res = learnpath::__construct($course, $lp_id, $user_id);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	public function testLog() {
		//ob_start();
		$msg = '';
		$res = learnpath::log($msg);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	public function testMoveDown() {
		//ob_start();
		$lp_id = 0;
		$res = learnpath::move_down($lp_id);
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	public function testMoveItem() {
		//ob_start();
		$id = 1;
		$direction = '';
		$res = learnpath::move_item($id, $direction);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	public function testMoveUp() {
		//ob_start();
		$lp_id = 0;
		$res = learnpath::move_up($lp_id);
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
	/*
	public function testNext() {
		//ob_start();
		$course='COURSETEST';
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath($course, $lp_id, $user_id); 
		
		$res = $obj->next();
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	*/
		
	public function testOpen() {
		//ob_start();
		$id = '';
		$res = learnpath::open($id);
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
/*
	public function testOverview() {
		//ob_start();
		$course='COURSETEST';
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath($course, $lp_id, $user_id); 
		
		$res = $obj->overview();
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
	*/	
	public function testPrerequisitesMatch() {
		//ob_start();
		$item = null;
		$res = learnpath::prerequisites_match($item);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
/*
	public function testPrevious() {
		//ob_start();
		$course='COURSETEST';
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath($course, $lp_id, $user_id); 
		
		$res = $obj->previous();
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
*/		
	/*public function testRestart() {
		//ob_start();
		$course='COURSETEST';
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath($course, $lp_id, $user_id); 
		
		$res = $obj->restart();
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}*/
	
	public function testSaveCurrent() {
		//ob_start();
		$res = learnpath::save_current();
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testSaveItem() {
		//ob_start();
		$item_id = null;
		$from_outside = true;
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath(self::course, $lp_id, $user_id); 
		
		$res = $obj->save_item($item_id, $from_outside);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testSaveLast() {
		//ob_start();
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath(self::course, $lp_id, $user_id); 
		
		$res = $obj->save_last();
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	/*
	public function testScormExport() {
		//ob_start();
		$course='COURSETEST';
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath($course, $lp_id, $user_id); 
		
		$res = $obj->scorm_export();
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
*/
	public function testSetAuthor() {
		//ob_start();
		$name = '';
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath(self::course, $lp_id, $user_id); 
		
		$res = $obj->set_author($name);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testSetCurrentItem() {
		//ob_start();
		$item_id = null;
		$res = learnpath::set_current_item($item_id);
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testSetEncoding() {
		//ob_start();
		$enc = 'ISO-8859-15';
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath(self::course, $lp_id, $user_id); 
		
		$res = $obj->set_encoding($enc);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testSetErrorMsg() {
		//ob_start();
		$error = '';
		$res = learnpath::set_error_msg($error);
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testSetJslib() {
		//ob_start();
		$lib = '';
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath(self::course, $lp_id, $user_id); 
		
		$res = $obj->set_jslib($lib);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	public function testSetMaker() {
		//ob_start();
		$name = '';
		$res = learnpath::set_maker($name);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testSetName() {
		//ob_start();
		$name = '';
		$res = learnpath::set_name($name);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testSetPreviewImage() {
		//ob_start();
		$name = '';
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath(self::course, $lp_id, $user_id); 
		
		$res = $obj->set_preview_image($name);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testSetPreviousItem() {
		//ob_start();
		$id = '';
		$res = learnpath::set_previous_item($id);
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testSetProximity() {
		//ob_start();
		$name = '';
		$res = learnpath::set_proximity($name);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	public function testSetTermsByPrefix() {
		//ob_start();
		$terms_string = '';
		$prefix = '';
		$res = learnpath::set_terms_by_prefix($terms_string, $prefix);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testSetTheme() {
		//ob_start();
		$name = '';
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath(self::course, $lp_id, $user_id); 
		
		$res = $obj->set_theme($name);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	/*
	public function testSortTreeArray() {
		//ob_start();
		$array = '';
		$course='COURSETEST';
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath($course, $lp_id, $user_id); 
		
		$res = $obj->sort_tree_array($array);
	 	$this->assertTrue(is_array($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	*/
	public function testStartCurrentItem() {
		//ob_start();
		$allow_new_attempt = false;
		$res = learnpath::start_current_item($allow_new_attempt);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testStopPreviousItem() {
		//ob_start();
		$res = learnpath::stop_previous_item();
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
/*
	public function testTogglePublish() {
		//ob_start();
		$lp_id = '';
		$set_visibility = 'v';
		$res = learnpath::toggle_publish($lp_id, $set_visibility);
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	*/
	public function testToggleVisibility() {
		//ob_start();
		$lp_id = '';
		$set_visibility = 1;
		$res = learnpath::toggle_visibility($lp_id, $set_visibility);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
/*	
	public function testTreeArray() {
		//ob_start();
		$array = '';
		$res = learnpath::tree_array($array);
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
*/	
	public function testUpdateDefaultScormCommit() {
		//ob_start();
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath(self::course, $lp_id, $user_id); 
		
		$res = $obj->update_default_scorm_commit();
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testUpdateDefaultViewMode() {
		//ob_start();
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath(self::course, $lp_id, $user_id); 
		
		$res = $obj->update_default_view_mode();
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	public function testUpdateDisplayOrder() {
		//ob_start();
		$res = learnpath::update_display_order();
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testUpdateReinit() {
		//ob_start();
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath(self::course, $lp_id, $user_id); 
		
		$res = $obj->update_reinit();
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testUpdateScormDebug() {
		//ob_start();
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath(self::course, $lp_id, $user_id); 
		
		$res = $obj->update_scorm_debug();
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	public function testUploadImage() {
		//ob_start();
		$image_array = '';
		$res = learnpath::upload_image($image_array);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
/*
	public function testWriteResourcesTree() {
		//ob_start();
		$resources_sorted = '';
		$num = 0;
		$res = learnpath::write_resources_tree($resources_sorted, $num);
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	public function testDelete() {
		//ob_start();
		$course = null;
		$id = null;
		$delete = 'keep';
		$course='COURSETEST';
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath($course, $lp_id, $user_id); 
		
		$res = $obj->delete($course, $id, $delete);
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}*/
	
	public function testDeleteChildrenItems() {
		//ob_start();
		$id = '';
		$res = learnpath::delete_children_items($id);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	public function testDeleteItem() {
		//ob_start();
		$id = '';
		$remove = 'keep';
		$res = learnpath::delete_item($id, $remove);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	public function testDeleteLpImage() {
		//ob_start();
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath(self::course, $lp_id, $user_id); 
		
		$res = $obj->delete_lp_image();
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		

}
?>