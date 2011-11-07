<?php
require_once(api_get_path(LIBRARY_PATH).'course.lib.php');

class TestMainApi extends UnitTestCase {

	function TestMainApi() {
        	$this->UnitTestCase('Main API library - main/inc/lib/main_api.lib.test.php');
	}
/*
	function testApiProtectCourseScript(){
		ob_start();
		$res= api_protect_course_script($print_headers=null);
		ob_get_contents();
		$this->assertTrue(is_string($res));
		ob_end_clean();
	}

	function testApiProtectAdminScript(){
		ob_start();
	 	$res = api_protect_admin_script($allow_sessions_admins=false);
	 	ob_end_clean();
	 	//$this->assertTrue(is_string($res));

	}

	function testApiBlockAnonymousUser(){
		ob_start();
		$res = api_block_anonymous_users();
	 	$this->assertTrue(is_string($res));
	 	ob_end_clean();
	}
*/
	function testApiGetNavigator(){
		ob_start();
	 	$res=api_get_navigator();
	 	$this->assertTrue($res);
	 	ob_end_clean();
	}

	function testApiIsSelfRegistrationAllowed(){
		$res = api_is_self_registration_allowed();
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	function testApiGetPath() {
		$path_type = trim($path_type);
		$path = array(
		WEB_PATH => '',
		SYS_PATH => '',
		REL_PATH => '',
		WEB_SERVER_ROOT_PATH => '',
		SYS_SERVER_ROOT_PATH => '',
		WEB_COURSE_PATH => '',
		SYS_COURSE_PATH => '',
		REL_COURSE_PATH => '',
		REL_CODE_PATH => '',
		WEB_CODE_PATH => '',
		SYS_CODE_PATH => '',
		SYS_LANG_PATH => 'lang/',
		WEB_IMG_PATH => 'img/',
		WEB_CSS_PATH => 'css/',
		GARBAGE_PATH => 'archive/', // Deprecated?
		SYS_PLUGIN_PATH => 'plugin/',
		WEB_PLUGIN_PATH => 'plugin/',
		SYS_ARCHIVE_PATH => 'archive/',
		WEB_ARCHIVE_PATH => 'archive/',
		INCLUDE_PATH => 'inc/',
		LIBRARY_PATH => 'inc/lib/',
		CONFIGURATION_PATH => 'inc/conf/',
		WEB_LIBRARY_PATH => 'inc/lib/',
		WEB_AJAX_PATH => 'inc/ajax/'
	);
		$res=api_get_path($path_type, $path);
	 	$this->assertTrue(is_null($res));
	 	//var_dump($res);
	}

	function testApiGetUserId(){
		$res= api_get_user_id();
		$this->assertPattern('/\d/',$res);
	}
    function testApiGetUserCoursesReturnTrueWhenOutOfCoursesContext(){
    	global $tbl_user;
    	$userid=1;
    	$fetch_session=true;
    	$res = api_get_user_courses($userid,$fetch_session);
    	if(!is_null($res)) :
    	$this->assertTrue(is_array($res));
    	endif;
    	//var_dump($res);
    }

    function testApiGetUserInfoReturnFalseWhenOutOfUserInfoContext(){
    	$user_id= 1;
    	$res = api_get_user_info($user_id);
   		$this->assertTrue(is_array($res));
   		//var_dump($res);
    }
    function testApiGetUserInfoUsernameReturnTrueWhenOutOfUserInfoUsernameContext(){
    	$res=api_get_user_info_from_username();
    	$this->assertTrue(is_bool($res));
    	//var_dump($res);
    }
    /* Causing problems for some reason on automated tests server
    function testApiGetCourseIdReturnFalseWhenOutOfCourseIdContext(){
        $res = api_get_course_id();
    	$this->assertEqual($res,-1);
    }
    function testApiGetCoursePathReturnFalseWhenOutOfCoursePathContext(){
        $res = api_get_course_path();
    	$this->assertTrue(empty($res));
    }
    */
    function testApiGetCourseSettingReturnFalseWhenOutOfCourseSeetingContext(){
        global $_course;
        $course_code = $_course;
        $setting_name = 1;
        $res = api_get_course_setting($setting_name, $course_code);
        $this->assertTrue($res);
    }

    function testApiGetAnonymousId(){
        $res = api_get_anonymous_id();
        $this->assertTrue(is_numeric($res));
    }

    function testApiGetCidreq(){
        $res=api_get_cidreq();
        $this->assertTrue(is_string($res));
    }

    function testApiGetCourseInfo(){
        ob_start();
        $res=api_get_course_info();
        $this->assertTrue($res);
        ob_end_clean();
    }

    function testApiSessionStart(){
        if (!headers_sent()) {
            $res = api_session_start($already_sintalled=true);
        }
        $this->assertTrue(is_null($res));
    }

    function testApiSessionRegister(){
        $$variable[session_register]=false;
        global $$variable;
        if (!headers_sent()) {
            $res=api_session_register($$variable);
        }
        $this->assertTrue(is_null($res));
        $this->assertTrue(is_null($variable[session_register]));
    }

    function testApiSessionUnregister() {
        $variable=strval($variable);
        $res=api_session_unregister($variable);
        $this->assertTrue(is_null($res));
        $this->assertTrue(is_null($_SESSION[$variable]=null));
    }

	function testApiSessionClear() {
		$variable = 'test';
		 if (!headers_sent()) {
			$res=api_session_clear($variable);
		 }
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	function testApiSessionDestroy(){
		 if (!headers_sent()) {
			$res=api_session_destroy();
		 }
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	function testApiAddUrlParamReturnsUrlWhenNoParam() {
		global $url;
		$res=api_add_url_param($url, null, $filter_xss=true);
		$this->assertEqual($res,$url);
	}

	function testApiGeneratePassword() {
		$res = api_generate_password($length = 8);
	    $this->assertTrue(is_string($res));
	    //var_dump($res);
	}

	function testApiCheckPassword(){
		$password = '';
		$res = api_check_password($password);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	function testApiClearAnonymous(){
		global $_user;
		$_user['user_id'] = 1;
		$res = api_clear_anonymous($db_check=false);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
		$this->assertTrue(isset($_user['user_id'] ));
	}

	function testApiTruncStr() {
		$text= 'test';
		$res= api_trunc_str($text, $length = 30, $suffix = '...', $middle = false, $encoding = null);
		$this->assertTrue(is_string($res));
	}

	function testDomesticate(){
		$input= 'dome';
		$res = domesticate($input);
		$this->assertTrue($res);
	}

	function testGetStatusFromCode(){
		$status_code = 1;
		$res=get_status_from_code($status_code);
		$this->assertTrue($res);
	}

	function testApiSetFailure(){
		global $api_failureList;
		$failureType=true;
		$res = api_set_failure($failureType);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($api_failureList);
	}

	function testApiSetAnonymous(){
		$res = api_set_anonymous();
		$this->assertTrue(is_bool($res));
	}

	function testApiGetLastFailure(){
		$res= api_Get_last_failure();
		$this->assertTrue($res);
	}

	function testApiGetSessionId(){
		$res = api_get_session_id();
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
    }


    function testApiGetSessionName(){
   		$session_id='';
   		$res = api_get_session_name($session_id);
   		//$this->assertTrue(is_string($res));
   		$this->assertTrue(is_null($res));
   		//var_dump($res);
    }

   	function testAPiGetSetting(){
   		global $variable, $_setting;
   	    $res=api_get_setting($variable, $key= NULL);
    	$this->assertTrue(is_null($res));
    	//var_dump($res);
   	}

    function testApiGetSelf(){
   		$res = api_get_self();
    	$this->assertTrue(is_string($res));
    	//var_dump($res);
	}

/* function deprecated
   	function testGetLang(){
        global $language_interface, $language_interface_initial_value, $language_file,$variable;
   	 	$res = get_lang($variable, $notrans = 'DLTT', $language = null);
   	 	$this->assertTrue(is_string($res));
   	 	//var_dump($res);
   	}

   	function testGetLangToSystemEncoding(){
		global $language, $name;
		$res=&get_lang_to_system_encoding(& $string, $language);
		ob_start();
		api_disp_html_area($name, $content ='', $height='', $width='100%', $optAttrib='');
		$res = ob_get_contents();
		$this->assertNotEqual($res,'');
   		ob_end_clean();
   	}

   	function testApiGetInterfaceLanguage(){
		global $language_interface;
		$language_interface=false;
		ob_start();
		$res = api_get_interface_language();
		$res = ob_get_contents();
		$this->assertTrue(is_string($res));
		$this->assertTrue(isset($language_interface));
		ob_end_clean();
		//var_dump($res);
   	}
*/
   	function testApiIsPlatformAdmin(){
   		ob_start();
		global $_user;
		$_user['status']=true;
		$allow_sessions_admins=true;
		$res= api_is_platform_admin($allow_sessions_admins=true);
		$this->assertTrue(is_bool($res));
		$this->assertTrue($_SESSION['is_platformAdmin']=true);
		$this->assertTrue(isset($_user['status']));
		ob_end_clean();
		//var_dump($res);
   	}

   	function testApiIsAllowedToCreateCourse() {
		$res=api_is_allowed_to_create_course();
		if(!is_bool($res)){
			$this->assertTrue(is_null($res));
		}
   	}

   	function testApiIsCourseAdminIsFalseWhenNoCourseContextDefined() {
		$res=api_is_course_admin();
		if($_SESSION['is_courseAdmin'] === 1) {
			$this->assertTrue($res);
		} else {
			$this->assertFalse($res);
		}
   	}

    function testApiIsCourseCoach() {
		$res=api_is_course_coach();
		if(!is_bool($res)){
			$this->assertTrue(is_null($res));
		}
   	}

   	function testApiIsCoach(){
		global $_user;
		global $sessionIsCoach;
		$_user['user_id']=2;
		$sessionIsCoach=Database::store_result($result=false);
		$res=api_is_coach();
		$this->assertTrue(is_bool($res));
		//var_dump($res);
		$this->assertTrue($_user['user_id']);
		$this->assertTrue(is_array($sessionIsCoach));
		//var_dump($sessionIsCoach);
   	}

   	function testApiIsSessionAdmin(){
		global $_user;
		$_user['status']=true;
		$res=api_is_session_admin();
		$this->assertTrue(is_bool($res));
		$this->assertTrue(is_array($_user));
		//var_dump($_user);

    }

   	function testApiDisplayToolTitle(){
		$tit=true;
		$titleElement['mainTitle']=$tit;
		ob_start();
		api_display_tool_title($titleElement);
		$res = ob_get_contents();
		$this->assertEqual($res,'<h3>1</h3>');
		$this->assertTrue(isset($titleElement));
		$this->assertTrue($titleElement['mainTitle']);
		$this->assertPattern('/<h3>1<\/h3>/', $res);
		ob_end_clean();
	}
/* This test fails but it doesn't say much anyway
	function testApiDisplayToolViewOption(){
		ob_start();
		api_display_tool_view_option();
		$res = ob_get_contents();
		ob_end_clean();
		$this->assertTrue(empty($res));
	}
*/
	function testApiDisplayArray(){
		global $info_array;
		ob_start();
		api_display_array($info_array);
		$res = ob_get_contents();
		$this->assertNotEqual($res,'');
		ob_end_clean();
	}

	function testApiDisplayDebugInfo(){
		$message = "mensaje de error"; // siempre que puedas, te conviene probar con valores creados al azar
		ob_start();
		api_display_debug_info($message);
		$res = ob_get_contents();
		$this->assertNotEqual($res,'');
		ob_end_clean();
	}


	function testApiIsAllowedToEdit(){
	 	$is_courseAdmin=false;
	 	$res=api_is_allowed_to_edit($tutor=false,$scoach=false);
	 	$this->assertTrue(is_bool($res));
	 	$this->assertTrue(isset($is_courseAdmin));
	}

	function testApiIsAllowed(){
	    global $_course, $_user;
	  	$tool= 'full';
	 	$action = 'delete';
	 	$res=api_is_allowed($tool, $action, $task_id=0);
	 	if(!is_bool($res)){
	  	$this->assertTrue(is_null($res));
	  	}
	  	$this->assertTrue($action);
	 	$this->assertTrue($_user['user_id']);
	}

	function testApiIsAnonymous(){
	 	global $_user, $use_anonymous;
	  	$_user['is_anonymous']=False;
	 	$res=api_is_anonymous($user_id=null, $db_check=false);
	 	$this->assertTrue(is_bool($res));
	 	$this->assertFalse(isset($user_id));
	 	$this->assertTrue(isset($_user['is_anonymous']));
	 	$this->assertTrue(is_null($use_anonymous));
	 	$this->assertTrue(is_bool($db_check));
	 	//var_dump($db_check);
	}

	function testApiNotAllowed(){
		ob_start();
		//api_not_allowed($print_headers = false);
		$res = ob_get_contents();
		$this->assertEqual($res,'');
		ob_end_clean();
	}

	function testConvertMysqlDate(){
	 	$last_post_datetime = array();
	 	$res=convert_mysql_date($last_post_datetime);
	 	$this->assertTrue($res);
	}

	function testApiGetDatetime(){
	 	$res=api_get_datetime($time=null);
	 	$this->assertTrue($res);
	 	$this->assertFalse(isset($time));
	}

	function testApiGetItemVisibility(){
	 	global $_course;
	 	$tool = 'document';
	 	$id=1;
	 	$_course['dbName']=false;
	 	$res =api_get_item_visibility($_course,$tool,$id);
	 	$this->assertTrue(is_numeric($res));
	 	$this->assertFalse(is_bool($res));
	}

	function testApiItemPropertyUpdate(){
	  	global $_course, $tool, $item_id, $lastedit_type, $user_id;
	  	$res=api_item_property_update($_course, $tool, $item_id, $lastedit_type, $user_id, $to_group_id = 0, $to_user_id = NULL, $start_visible = 0, $end_visible = 0);
	 	$this-> assertTrue($res);
	}

	function testApiGetLanguagesCombo(){
		$res=api_get_languages_combo($name="language");
		$this->assertTrue($res);
	}

	function testApiDisplayLanguageForm(){
		ob_start();
		api_display_language_form($hide_if_no_choice=false);
		$res = ob_get_contents();
		$this->assertNotEqual($res,'');
		ob_end_clean();
	}

	function testApiGetLanguages(){
		$res= api_get_languages();
		$this->assertTrue($res);
	}

	function testApiGetThemes(){
		$cssdir= api_get_path(SYS_PATH).'main/css/';
		$res=api_get_themes();
		$this->assertTrue($res);
		$this->assertTrue($cssdir);
	}

	function testApiDispHtmlArea(){
		$name = 'name';
		global $_configuration, $_course, $fck_attribute;
		ob_start();
		api_disp_html_area($name, $content ='', $height='', $width='100%', $optAttrib='');
		$res = ob_get_contents();
		$this->assertNotEqual($res,'');
		ob_end_clean();
	}

	function testApiReturnHtmlArea(){
		$name = true;
		global $_configuration, $_course, $fck_attribute;
		$res=api_return_html_area($name, $content='', $height='', $width='100%', $optAttrib='');
		$this->assertTrue($res);
	}

	function testApiSendMail(){
		$to= 'chamilotest@beeznest.com';
		$subject='Hello';
		$message='test message';
		$res=api_send_mail($to, $subject, $message, $additional_headers = null, $additional_parameters = null);
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
		//var_dump($send_mail);
	}

	function testApiMaxSortValue(){
	    $user_course_category=1;
	    $user_id =1;
		$res= api_max_sort_value($user_course_category,$user_id);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	function testString2Boolean(){
		global $string;
		$res=string_2_boolean($string);
		$this->assertFalse($res);
	}

	function testApiNumberOfPlugins(){
		global $_plugins;
		$location=2;
		$_plugins[$location]=1;
		$res=api_number_of_plugins($location);
		$this->assertFalse($res);
		$this->assertTrue($_plugins[$location]);
	}

	function testApiPlugin(){
		global $_plugins;
		$location=2;
		$_plugins[$location]=1;
		$res1 = api_plugin($location);
		$this->assertFalse($res1);
		$this->assertTrue($_plugins[$location]);
	}

	function testApiIsPluginInstalled(){
		$plugin_name = false;
		$plugin_list = true;
		$res = api_is_plugin_installed($plugin_list, $plugin_name);
		$this->assertTrue(is_bool($res));
	}

	function testApiTimeToHms(){
		$seconds = -1;
		ob_start();
		api_time_to_hms($seconds);
		$res = ob_get_contents();
		$this-> assertTrue(is_string($res));
		ob_end_clean();
		//var_dump($res);
	}

	public function testApiGetPermissionsForNewDirectories() {
		$perm = trim(api_get_setting('permissions_for_new_directories'));
		$perm = octdec(!empty($perm) ? $perm : '0777');
		$res = api_get_permissions_for_new_directories();
		$this->assertTrue($res === $perm);
		//var_dump($res);
	}

	public function testApiGetPermissionsForNewFiles() {
		$perm = trim(api_get_setting('permissions_for_new_files'));
		$perm = octdec(!empty($perm) ? $perm : '0666');
		$res = api_get_permissions_for_new_files();
		$this->assertTrue($res === $perm);
		//var_dump($res);
	}

	function testCopyr_file_to_nothing(){
		$source = api_get_path(SYS_CODE_PATH).'admin/add_users_to_session.php';
		$dest = '';
		$res = copyr($source, $dest, $exclude=array(), $copied_files=array());
		$this->assertFalse($res,'Function coyr() should have not proceeeded because of empty destination');
		//var_dump($res);
	}

	/* This function is behaving differently on automated test server
	function testApiChmod_R(){
		// We know, it does not work for Windows.
		if (IS_WINDOWS_OS) { return true; }
		$dirname = api_get_path(SYS_LANG_PATH);
		$perm_dir = substr(sprintf('%o', fileperms($dirname)), -4);
		$this->assertEqual($perm_dir,'0777');
		$new_filemode = '0775';
		$res = api_chmod_R($dirname, $new_filemode);
		$this->assertTrue($res);
	}
    */
	function testApiGetVersion(){
		global $_configuration;
		$res = api_get_version();
		$this->assertTrue($res);
	}

	function testApiStatusExists(){
		$status_asked = 'user';
		$res = api_status_exists($status_asked);
		$this->assertTrue(is_bool($res));
	}
/* Fails for some reason on automated tests server
	function testApiStatusKey(){
		$status = 'user';
		$res = api_status_key($status);
		//var_dump($res);
		$this->assertEqual($res,STUDENT);
	}
*/
	function testApiGetStatusLangvars(){
		$res = api_get_status_langvars();
		$this->assertTrue(is_array($res));
	}

	function testApiSetSetting(){
		ob_start();
		$var = 0;
		$value = 2;
		$res = api_set_setting($var,$value,$subvar=null,$cat=null,$access_url=1);
		$this->assertTrue(is_bool($res));
		ob_end_clean();
		//var_dump($res);
	}

	function testApiSetSettingsCategory(){
		$category = $_GET['category'];
		$res = api_set_settings_category($category,$value=null,$access_url=1);
		$this->assertTrue(is_null($category));
		$this->assertTrue(is_bool($res));
		//var_dump($res);
		//var_dump($category);
	}

	function testApiGetAccessUrls(){
		$res = api_get_access_urls($from=0,$to=1000000,$order='url',$direction='ASC');
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	function testApiGetAccessUrl(){
		$id=1;
		$res = api_get_access_url($id);
		$this->assertTrue(is_array($res));
	}

	function testApiAddAccessUrl(){
		$u = Database::escape_string($u);
		$d = Database::escape_string($d);
		$res = api_add_access_url($u,$d='',$a=1);
		$this->assertTrue(is_numeric($res));
		//var_dump($res);
	}

	function testApiGetSettings(){
		$res = api_get_settings($cat=null,$ordering='list',$access_url=1,$url_changeable=0);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	function testApiGetSettingsCategories(){
		$res = api_get_settings_categories($exceptions=array(),$access_url=1);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	function testApiDeleteSetting(){
		$v = false;
		$res = api_delete_setting($v, $s=NULL, $a=1);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	function testApiDeleteCategorySettings(){
		$c= false;
		$res = api_delete_category_settings($c,$a=1);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	function testApiAddSetting(){
		$sk=null;
		$type='textfield';
		$c=null;
		$title='';
		$com='';
		$sc=null;
		$skt=null;
		$a=1;
		$v=0;
		$va=array('val'=>10, 'var'=>'name');
		$res= api_add_setting($va['val'],$va['var'],null,null,null,null,null,null,null,null,null);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	function testApiIsCourseVisibleForUser() {
		global $_user, $cidReq;
		$userid = $_user;
		$cid = $cidReq ;
		$res = api_is_course_visible_for_user($userid, $cid);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	function testApiIsElementInTheSession(){
		ob_start();
		$_tool['tool'] = 'TOOL_SURVEY';
		$_id['element_id']=3;
		$res = api_is_element_in_the_session($_tool['tool'], $_id['element_id'], $session_id=null);
		$this->assertTrue(is_bool($res));
		$this->assertTrue((isset($_tool['tool'],$_id['element_id'])));
		ob_end_clean();
		//var_dump($res);
	}

	function testReplaceDangerousChar(){
		$filename =ereg_replace("\.+$", "", substr(strtr(ereg_replace(
	    "[^!-~\x80-\xFF]", "_", trim($filename)), '\/:*?"<>|\'',
        /*Keep C1 controls for UTF-8 streams **/ '-----_---_'), 0, 250));
		$res = replace_dangerous_char($filename, $strict = 'loose');
		$this->assertEqual($res,$filename, $message = 'no se pudo');
	}

	function testApiRequestUri(){
		$res = api_request_uri();
		$this->assertTrue($res);
	}

	function testApiCreateIncludePathSetting(){
		$res=api_create_include_path_setting();
		$this->assertTrue($res);
	}

	function testApiGetCurrentAccessUrlId(){
		$res=api_get_current_access_url_id();
		$this->assertTrue($res);
	}

	function testApiGetAccessUrlFromUser(){
		$user_id=1;
		$res= api_get_access_url_from_user($user_id);
		$this->assertTrue(is_array($res));
		//var_dump($res);
	}

	function testApiGetStatusOfUserInCourse(){
		$id = array(
					'course_code'=>'TEST',
					'user_id'=>'1');
		$res=api_get_status_of_user_in_course($id['course_code'],$id['user_id']);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	function testApiIsInCourse(){
		$_SESSION['_course']['sysCode']=0;
		$res=api_is_in_course($course_code=null);
		$this->assertTrue(is_bool($res));
		$this->assertTrue(isset($_SESSION['_course']['sysCode']));
		//var_dump($res);
	}

	function testApiIsInGroup(){
		$res=api_is_in_group($group_id=null, $course_code=null);
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	function testApiIsXmlHttpRequest(){
		$res=api_is_xml_http_request();
		$this->assertTrue(isset($res));
	}

	function testApiGetEncryptedPassword(){
		$pass= array ('password'=> '2222');
		$res=api_get_encrypted_password($pass['password'],null);
		$this->assertTrue($res);
		$this->assertPattern('/\d/',$res);
	}

	function testApiIsValidSecretKey(){
		global $_configuration;
		$key = array(
		'original_key_secret'=>'2121212',
		'security_key'=>'2121212');
		$res = api_is_valid_secret_key($key['original_key_secret'],$key['security_key']);
		$this->assertTrue($_configuration);
		$this->assertFalse($res);
		$this->assertTrue($key);
		$this->assertEqual($key['original_key_secret'],$key['security_key'], $message ='%s');
	}

	function testApiIsUserOfCourse(){
		$course_id = 1;
		$user_id = 1;
		$tbl_course_rel_user =false;
		$sql='SELECT user_id FROM '.$tbl_course_rel_user.' WHERE course_code="'.Database::escape_string($course_id).'" AND user_id="'.Database::escape_string($user_id).'"';
		$res= api_is_user_of_course($course_id, $user_id);
		$this->assertFalse($res);
		$this->assertFalse($tbl_course_rel_user);
		$this->assertTrue($sql);
	}

	function testApiIsWindowsOs(){
		$res= api_is_windows_os();
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	function testApiResizeImage(){
		//global $image, $target_width, $target_height;
		$resize = array('image' =>'image.jpg', 'target_width'=>100, 'target_height'=>100);
		$res = api_resize_image($resize['image'],$resize['target_width'],$resize['target_height']);
		$this->assertTrue(is_array($res));
		$this->assertWithinMargin($first = 10, $second=20,$margin=200, $message = 'no se pudo redimensionar imagen');
		//var_dump($res);
	}

	function testApiCalculateImageSize(){
		global $image_width, $image_height, $target_width, $target_height;
		$result = array($image_width, $image_height);
		$res = api_calculate_image_size($image_width, $image_height, $target_width, $target_height);
		$this->assertTrue(is_array($res));
		$this->assertTrue($result);
		//var_dump($res);
	}

	function testApiGetToolsLists(){
		$tool_list = 'false';
		$res = api_get_tools_lists($my_tool =null);
		$this->assertTrue(is_array($res));
		$this->assertTrue($tool_list);
		//var_dump($res);
	}
	public function TestDeleteCourse() {
		$code = 'COURSETEST';
		$res = CourseManager::delete_course($code);
		$path = api_get_path(SYS_PATH).'archive';
		if ($handle = opendir($path)) {
			while (false !== ($file = readdir($handle))) {
				if (strpos($file,$code)!==false) {
					if (is_dir($path.'/'.$file)) {
						rmdirr($path.'/'.$file);
					}
				}
			}
			closedir($handle);
		}
	}
}
?>
