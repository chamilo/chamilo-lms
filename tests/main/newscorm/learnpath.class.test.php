<?php
require_once(api_get_path(SYS_CODE_PATH).'newscorm/learnpath.class.php');

class TestScorm extends UnitTestCase {
	
	function testAddItem() {
		//ob_start();
		$parent = 2;
		$previous = 1;
		$type = 'dokeos_chapter';
		$id = 1;
		$title = 'Titulo';
		$description = 'Descripcion';
		$prerequisites = 0;	
		$max_time_allowed = 0;
		
		$course='COURSETEST';
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath($course, $lp_id, $user_id); 
		
		$res = $obj->add_item($parent, $previous, $type, $id, $title, $description, $prerequisites, $max_time_allowed);
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	function testAddLp() {
		//ob_start();
		$course = 'COURSETEST';
		$name = '';
		$description = '';
		$learnpath = 'guess';
		$origin = 'zip';
		$zipname = '';
		$res = learnpath::add_lp($course, $name, $description, $learnpath, $origin, $zipname);
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	function testAppendMessage() {
		//ob_start();
		$string = '';
		$res = learnpath::append_message($string);
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
	/*	
	function testAutocompleteParents() {
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
	function testAutosave() {
		//ob_start();
		$res = learnpath::autosave();
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	function testBuildActionMenu() {
		ob_start();
		$res = learnpath::build_action_menu();
	 	$this->assertTrue(is_null($res));
		ob_end_clean();
	 	//var_dump($res);
	}
/*
	function testBuildTree() {
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
	function testClearMessage() {
		//ob_start();
		$res = learnpath::clear_message();
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	function testClose() {
		//ob_start();
		$res = learnpath::close();
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	function testCreateDocument() {
		//ob_start();
		$_course = '';
		$res = learnpath::create_document($_course);
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
/*	
	function testCreateJs() {
		//ob_start();
		$res = learnpath::create_js();
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
*/		
	function testCreatePath() {
		//ob_start();
		$path = '';
		$res = learnpath::create_path($path);
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	function testCreateTreeArray() {
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
		
	function testDisplayDocument() {
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
	function testDisplayDocumentForm() {
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
	function testDisplayEditItem() {
		//ob_start();
		$item_id = '';
		$res = learnpath::display_edit_item($item_id);
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
	/*
	function testDisplayForumForm() {
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
		
	function testDisplayHotpotatoesForm() {
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
	function testDisplayItem() {
		//ob_start();
		$item_id = '';
		$iframe = true;
		$msg = '';
		$res = learnpath::display_item($item_id, $iframe, $msg);
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
/*	function testDisplayItemForm() {
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

	function testDisplayItemPrerequisitesForm() {
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
	function testDisplayItemSmallForm() {
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
	function testDisplayLinkForm() {
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
		
	function testDisplayManipulate() {
		//ob_start();
		$item_id = '';
		$item_type = TOOL_DOCUMENT;
		$res = learnpath::display_manipulate($item_id, $item_type);
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
*/
	function testDisplayMoveItem() {
		//ob_start();
		$item_id = '';
		$res = learnpath::display_move_item($item_id);
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
/*		
	function testDisplayQuizForm() {
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
/*	function testDisplayResources() {
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
		
	function testDisplayStudentPublicationForm() {
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

	function testDisplayThreadForm() {
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
	function testEditDocument() {
		//ob_start();
		$_course='';
		$res = learnpath::edit_document($_course);
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	function testEditItem() {
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
		
	function testEditItemPrereq() {
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

	function testEscapeString() {
		//ob_start();
		$string = '';
		$res = learnpath::escape_string($string);
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	function testExportLp() {
		//ob_start();
		$type = 'scorm';
		$course = 'COURSETEST';
		$id = 1;
		$zipname = 'FILE';
		$res = learnpath::export_lp($type, $course, $id, $zipname);
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	function testFirst() {
		//ob_start();
		$res = learnpath::first();
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	function testGetAuthor() {
		//ob_start();
		$res = learnpath::get_author();
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
	
	function testGetBrotherChapters() {
		//ob_start();
		$id = '';
		$res = learnpath::get_brother_chapters($id);
	 	$this->assertTrue(is_array($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	function testGetBrotherItems() {
		//ob_start();
		$id = '';
		$res = learnpath::get_brother_items($id);
	 	$this->assertTrue(is_array($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
/*
	function testGetCommonIndexTermsByPrefix() {
		//ob_start();
		$prefix = '';
		$res = learnpath::get_common_index_terms_by_prefix($prefix);
	 	$this->assertTrue(is_array($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	function testGetCompleteItemsCount() {
		//ob_start();
		$res = learnpath::get_complete_items_count();
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
*/	
	function testGetCurrentItemId() {
		//ob_start();
		$res = learnpath::get_current_item_id();
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	function testGetDbProgress() {
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

	function testGetDocuments() {
		//ob_start();
		$course='COURSETEST';
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath($course, $lp_id, $user_id); 
		
		$res = $obj->get_documents();
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	function testGetExercises() {
		//ob_start();
		$res = learnpath::get_exercises();
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	function testGetExtension() {
		//ob_start();
		$filename = 'file';
		$res = learnpath::get_extension($filename);
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	function testGetFirstItemId() {
		//ob_start();
		$res = learnpath::get_first_item_id();
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	function testGetFlatOrderedItemsList() {
		//ob_start();
		$lp = 1;
		$parent = 0;
		$res = learnpath::get_flat_ordered_items_list($lp, $parent);
	 	$this->assertTrue(is_array($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		/*
	function testGetForums() {
		//ob_start();
		
		//require_once api_get_path(WEB_PATH).('forum/forumfunction.inc.php');
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

	function testGetHtmlToc() {
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
	function testGetId() {
		//ob_start();
		$res = learnpath::get_id();
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	function testGetInteractionsCountFromDb() {
		//ob_start();
		$lp_iv_id = 0;
		$res = learnpath::get_interactions_count_from_db($lp_iv_id);
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
/*		
	function testGetItemsDetailsAsJs() {
		//ob_start();
		$varname='olms.lms_item_types';
		$res = learnpath::get_items_details_as_js($varname);
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	function testGetItemsStatusList() {
		//ob_start();
		$res = learnpath::get_items_status_list();
	 	$this->assertTrue(is_array($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	function testGetIvInteractionsArray() {
		//ob_start();
		$lp_iv_id = '';
		$res = learnpath::get_iv_interactions_array($lp_iv_id);
	 	$this->assertTrue(is_array($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
*/
	function testGetIvObjectivesArray() {
		//ob_start();
		$lp_iv_id = 0;
		$res = learnpath::get_iv_objectives_array($lp_iv_id);
	 	$this->assertTrue(is_array($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
	/*	
	function testGetJsInfo() {
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
	
	function testGetJsLib() {
		//ob_start();
		$res = learnpath::get_js_lib();
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	function testGetLast() {
		//ob_start();
		$res = learnpath::get_last();
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	function testGetLink() {
		//ob_start();
		$type = 'http';
		$item_id = null;
		$course='COURSETEST';
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath($course, $lp_id, $user_id); 
		
		$res = $obj->get_link($type, $item_id);
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	function testGetLinks() {
		//ob_start();
		$res = learnpath::get_links();
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	function testGetLpSessionId() {
		//ob_start();
		$res = learnpath::get_lp_session_id();
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	function testGetMaker() {
		//ob_start();
		$res = learnpath::get_maker();
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	function testGetMediaplayer() {
		//ob_start();
		$autostart='true';
		$res = learnpath::get_mediaplayer($autostart);
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	function testGetMessage() {
		//ob_start();
		$res = learnpath::get_message();
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	function testGetName() {
		//ob_start();
		$res = learnpath::get_name();
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	function testGetNavigationBar() {
		//ob_start();
		$course='COURSETEST';
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath($course, $lp_id, $user_id); 
		
		$res = $obj->get_navigation_bar();
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
	
	function testGetNextIndex() {
		//ob_start();
		$res = learnpath::get_next_index();
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	function testGetNextItemId() {
		//ob_start();
		$course='COURSETEST';
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath($course, $lp_id, $user_id); 
		
		$res = $obj->get_next_item_id();
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	function testGetObjectivesCountFromDb() {
		//ob_start();
		$lp_iv_id = 0;
		$res = learnpath::get_objectives_count_from_db($lp_iv_id);
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
	/*	
	function testGetPackageType() {
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
	
	function testGetPreviewImage() {
		//ob_start();
		$res = learnpath::get_preview_image();
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	function testGetPreviousIndex() {
		//ob_start();
		$res = learnpath::get_previous_index();
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	function testGetPreviousItemId() {
		//ob_start();
		$course='COURSETEST';
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath($course, $lp_id, $user_id); 
		
		$res = $obj->get_previous_item_id();
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	function testGetProgress() {
		//ob_start();
		$res = learnpath::get_progress();
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	function testGetProgressBar() {
		//ob_start();
		$mode = '';
		$percentage = -1;
		$text_add = '';
		$from_lp = false;
		$course='COURSETEST';
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath($course, $lp_id, $user_id); 
		
		$res = $obj->get_progress_bar($mode, $percentage, $text_add, $from_lp);
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	function testGetProgressBarMode() {
		//ob_start();
		$res = learnpath::get_progress_bar_mode();
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
	
	function testGetProgressBarText() {
		//ob_start();
		$mode = '';
		$add = 0;
		$course='COURSETEST';
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath($course, $lp_id, $user_id); 
		
		$res = $obj->get_progress_bar_text($mode, $add);
	 	$this->assertTrue(is_array($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	function testGetProximity() {
		//ob_start();
		$res = learnpath::get_proximity();
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	function testGetScormPrereqString() {
		//ob_start();
		$item_id = 1;
		$res = learnpath::get_scorm_prereq_string($item_id);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	function testGetScormXmlNode() {
		//ob_start();
		$children = 'children';
		$id = 1;		
		$res = learnpath::get_scorm_xml_node($children, $id);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	function testGetStats() {
		//ob_start();
		$res = learnpath::get_stats();
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	function testGetStatsCourse() {
		//ob_start();
		$course = '';
		$res = learnpath::get_stats_course($course);
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	function testGetStatsLp() {
		//ob_start();
		$course = 'COURSETEST';
		$lp = 1;
		$res = learnpath::get_stats_lp($course, $lp);
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	function testGetStatsLpUser() {
		//ob_start();
		$course = 'COURSETEST';
		$lp = 1;
		$user = 1;
		$res = learnpath::get_stats_lp_user($course, $lp, $user);
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	function testGetStatsUser() {
		//ob_start();
		$course = 'COURSETEST';
		$user = 1;
		$res = learnpath::get_stats_user($course, $user);
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	function testGetStudentPublications() {
		//ob_start();
		$res = learnpath::get_student_publications();
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
	
	function testGetTheme() {
		//ob_start();
		$res = learnpath::get_theme();
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
	/*	
	function testGetToc() {
		//ob_start();
		$res = learnpath::get_toc();
	 	$this->assertTrue(is_array($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
*/
	function testGetTotalItemsCount() {
		//ob_start();
		$res = learnpath::get_total_items_count();
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
/*		
	function testGetTotalItemsCountWithoutChapters() {
		//ob_start();
		$res = learnpath::get_total_items_count_without_chapters();
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	*/
	function testGetType() {
		//ob_start();
		$get_name = false;
		$res=learnpath::get_type($get_name) ;
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	function testGetTypeStatic() {
		//ob_start();
		$lp_id = 0;
		$res = learnpath::get_type_static($lp_id);
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	function testGetUpdateQueue() {
		//ob_start();
		$res = learnpath::get_update_queue();
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	function testGetUserId() {
		//ob_start();
		$res = learnpath::get_user_id();
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
/*
	function testGetView() {
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
	function testGetViewId() {
		//ob_start();
		$course='COURSETEST';
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath($course, $lp_id, $user_id); 
		
		$res = $obj->get_view_id();
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
	/*
	function testHasAudio() {
		//ob_start();
		$res = learnpath::has_audio();
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}*/	
		
	function testLearnpath() {
		//ob_start();
		$course = '';
		$lp_id = '';
		$user_id = '';
		$res = learnpath::learnpath($course, $lp_id, $user_id);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	function testLog() {
		//ob_start();
		$msg = '';
		$res = learnpath::log($msg);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	function testMoveDown() {
		//ob_start();
		$lp_id = 0;
		$res = learnpath::move_down($lp_id);
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	function testMoveItem() {
		//ob_start();
		$id = 1;
		$direction = '';
		$res = learnpath::move_item($id, $direction);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	function testMoveUp() {
		//ob_start();
		$lp_id = 0;
		$res = learnpath::move_up($lp_id);
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
	/*
	function testNext() {
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
		
	function testOpen() {
		//ob_start();
		$id = '';
		$res = learnpath::open($id);
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
/*
	function testOverview() {
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
	function testPrerequisitesMatch() {
		//ob_start();
		$item = null;
		$res = learnpath::prerequisites_match($item);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
/*
	function testPrevious() {
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
	/*function testRestart() {
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
	
	function testSaveCurrent() {
		//ob_start();
		$res = learnpath::save_current();
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	function testSaveItem() {
		//ob_start();
		$item_id = null;
		$from_outside = true;
		$course='COURSETEST';
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath($course, $lp_id, $user_id); 
		
		$res = $obj->save_item($item_id, $from_outside);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	function testSaveLast() {
		//ob_start();
		$course='COURSETEST';
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath($course, $lp_id, $user_id); 
		
		$res = $obj->save_last();
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	/*
	function testScormExport() {
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
	function testSetAuthor() {
		//ob_start();
		$name = '';
		$course='COURSETEST';
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath($course, $lp_id, $user_id); 
		
		$res = $obj->set_author($name);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	function testSetCurrentItem() {
		//ob_start();
		$item_id = null;
		$res = learnpath::set_current_item($item_id);
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	function testSetEncoding() {
		//ob_start();
		$enc = 'ISO-8859-15';
		$course='COURSETEST';
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath($course, $lp_id, $user_id); 
		
		$res = $obj->set_encoding($enc);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	function testSetErrorMsg() {
		//ob_start();
		$error = '';
		$res = learnpath::set_error_msg($error);
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	function testSetJslib() {
		//ob_start();
		$lib = '';
		$course='COURSETEST';
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath($course, $lp_id, $user_id); 
		
		$res = $obj->set_jslib($lib);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	function testSetMaker() {
		//ob_start();
		$name = '';
		$res = learnpath::set_maker($name);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	function testSetName() {
		//ob_start();
		$name = '';
		$res = learnpath::set_name($name);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	function testSetPreviewImage() {
		//ob_start();
		$name = '';
		$course='COURSETEST';
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath($course, $lp_id, $user_id); 
		
		$res = $obj->set_preview_image($name);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	function testSetPreviousItem() {
		//ob_start();
		$id = '';
		$res = learnpath::set_previous_item($id);
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	function testSetProximity() {
		//ob_start();
		$name = '';
		$res = learnpath::set_proximity($name);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	function testSetTermsByPrefix() {
		//ob_start();
		$terms_string = '';
		$prefix = '';
		$res = learnpath::set_terms_by_prefix($terms_string, $prefix);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	function testSetTheme() {
		//ob_start();
		$name = '';
		$course='COURSETEST';
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath($course, $lp_id, $user_id); 
		
		$res = $obj->set_theme($name);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	/*
	function testSortTreeArray() {
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
	function testStartCurrentItem() {
		//ob_start();
		$allow_new_attempt = false;
		$res = learnpath::start_current_item($allow_new_attempt);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	function testStopPreviousItem() {
		//ob_start();
		$res = learnpath::stop_previous_item();
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
/*
	function testTogglePublish() {
		//ob_start();
		$lp_id = '';
		$set_visibility = 'v';
		$res = learnpath::toggle_publish($lp_id, $set_visibility);
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	*/
	function testToggleVisibility() {
		//ob_start();
		$lp_id = '';
		$set_visibility = 1;
		$res = learnpath::toggle_visibility($lp_id, $set_visibility);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
/*	
	function testTreeArray() {
		//ob_start();
		$array = '';
		$res = learnpath::tree_array($array);
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
*/	
	function testUpdateDefaultScormCommit() {
		//ob_start();
		$course='COURSETEST';
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath($course, $lp_id, $user_id); 
		
		$res = $obj->update_default_scorm_commit();
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	function testUpdateDefaultViewMode() {
		//ob_start();
		$course='COURSETEST';
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath($course, $lp_id, $user_id); 
		
		$res = $obj->update_default_view_mode();
	 	$this->assertTrue(is_string($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	function testUpdateDisplayOrder() {
		//ob_start();
		$res = learnpath::update_display_order();
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	function testUpdateReinit() {
		//ob_start();
		$course='COURSETEST';
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath($course, $lp_id, $user_id); 
		
		$res = $obj->update_reinit();
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	function testUpdateScormDebug() {
		//ob_start();
		$course='COURSETEST';
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath($course, $lp_id, $user_id); 
		
		$res = $obj->update_scorm_debug();
	 	$this->assertTrue(is_numeric($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	
	function testUploadImage() {
		//ob_start();
		$image_array = '';
		$res = learnpath::upload_image($image_array);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
/*
	function testWriteResourcesTree() {
		//ob_start();
		$resources_sorted = '';
		$num = 0;
		$res = learnpath::write_resources_tree($resources_sorted, $num);
	 	$this->assertTrue(is_null($res));
		//ob_end_clean();
	 	//var_dump($res);
	}
	function testDelete() {
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
	
	function testDeleteChildrenItems() {
		//ob_start();
		$id = '';
		$res = learnpath::delete_children_items($id);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		
	function testDeleteItem() {
		//ob_start();
		$id = '';
		$remove = 'keep';
		$res = learnpath::delete_item($id, $remove);
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}

	function testDeleteLpImage() {
		//ob_start();
		$course='COURSETEST';
		$lp_id=1;
		$user_id=1;
		$obj = new learnpath($course, $lp_id, $user_id); 
		
		$res = $obj->delete_lp_image();
	 	$this->assertTrue(is_bool($res));
		//ob_end_clean();
	 	//var_dump($res);
	}	
		

}
?>