<?php //$id:$


class TestMainApi extends UnitTestCase {
	
	function TestMainApi() {
        $this->UnitTestCase('Main API tests');

	}
// todo function testApiProtectCourseScriptReturnsFalse()
// todo function testApiProtectAdminScriptReturnsFalse()
// todo function testApiBlockAnonymousUsers()
// todo function testApiGetNavigatorReturnArray($name,$version)
// todo function testApiIsSelfRegistrationAllowedReturnTrue()
// todo function testApiGetPathReturnString()
// todo function testApiGetUserIdReturnInteger()
// todo function testApiGetUserCoursesReturnArray()
// todo function testApiGetUserInfoReturnArray($user_id)
// todo function testApiGetUserInfoFromUsernameReturnArray($username)
// todo function testApiGetCourseIdReturnInteger()
// todo function testApiGetCoursePath()
// todo function testApiGetCourseSetting()
// todo function testApiGetAnonymousIdReturnInt()
// todo function testApiGetCidreq()
// todo function testApiGetCourseInfoReturnString()
// todo function testApiSqlQuery()
// todo function testApiStoreResultReturnArray()
// todo function testApiSessionStartReturnTrue()
// todo function testApiSessionRegister()
// todo function testApiSessionUnregister()
// todo function testApiSessionClearReturnArray()
// todo function testApiSessionDestroyReturnArray()
// todo function testApiAdd_UrlParamReturnString()
// todo function testApiGeneratePasswordReurnPassword()
// todo function testApiCheckPasswordReturnTrue()
// todo function testApiClearAnonymousReturnFalse()
// todo function testApiTruncStr()
// todo function testDomesticate
// todo function testGetStatusFromCodeReturnString()
// todo function testApiSetFailureReturnFalse()
// todo function testApiSetAnonymousReturnTrue()
// todo function testGetLastFailureRetunrString()
// todo function testApiGetSessionIdReturnInt()
// todo function testApiGetSessionNameReturnString()
// todo function testApiGetSelfReturnRightValue()
// todo function testGetLangReturnRightValue()
// todo function testApiGetInterfaceLanguageReturnString()
// todo function testApiIsPlatformAdminReturnTrue()
// todo function testApiIsAllowedToCreateCourseReturnTrue()
// todo function testApiIsCourseAdminRetunTrue()
// todo function testApiIsCourseCoachReturnTrue()
// todo function testApiIsCourseTutorReturnTrue()
// todo function testApiIsCoachReturnTrue()
// todo function testApiIsSessionAdminReturnTrue()
// todo function testApiDisplayToolTitle($titleElement)
// todo function testApiDisplayToolViewOption()
// todo function testApiDisplayArray()
// todo function testApiDisplayDebugInfo()
// todo function testApiIsAllowedToEdit()
// todo function testApiIsAllowed()
// todo function testApiIsAnonymous()
// todo function testApiNotAllowed()
// todo function testConvertMysqlDate()
// todo function testApiGetDatetime()
// todo function testApiGetItemVisibility()
// todo function testApiItemPropertyUpdate()
// todo function testApiGetLanguagesCombo()
// todo function testApiDisplayLanguageForm()
// todo function testApiGetLanguages()
// todo function testApiGetLanguageIsocode()
// todo function testApiGetThemesReturnArray()
// todo function testApiDispHtmlArea()
// todo function testApiReturnHtmlArea()
// todo function testApiSendMail()
// todo function testApiMaxSortValue()
// todo function testString2Boolean()
// todo function testApiNumberOfPlugins()
// todo function testApiPlugin()
// todo function testApiIsPluginInstalled()
// todo function testApiParseTex()
// todo function testApiTimeToHms()
// todo function testCopyr()
// todo function testApiChmodR()
// todo function testApiGetVersionReturnString()
// todo function testApiStatusExistsReturnTrue()
// todo function testApiStatusKeyReturnTrue()
// todo function testApiStatusLangvarsReturnArray()
// todo function testApiSetSetting()
// todo function testApiSetSettingsCategoryReturnTrue()
// todo function testApiGetAccessUrlsReturnArray()
// todo function testApiGetAccessUrlReturnArray()
// todo function testApiAddAccessUrlReturnInt()
// todo function testApiGetSettingsReturnArray()
// todo function testApiGetSettingsCategoriesReturnArray()
// todo function testApiDeleteSettingReturnTrue()
// todo function testApiDeleteCategorySettingsReturnTrue()
// todo function testApiAddSettingReturnTrue()
// todo function testApiIsCourseVisibleForUserReturnBooleanValue()
// todo function testApiIsElementInTheSessionReturnBooleanValue()
// todo function testReplaceDangerousChar()
// todo function testApiRequestUri()
// todo function testApiCreateIncludePathSetting()
// todo function testApiGetCurrentAccessUrlIdReturnInt()
// todo function testApiAccessUrlFromUserReturnInt()
// todo function testApiGetStatusOfUserInCourseReturnInteger()
// todo function testApiIsInCourseReturnBooleanValue()
// todo function testApiIsInGroupReturnBooleanValue()
// todo function testApiIsXmlHttpRequest()
// todo function testApiGetEncryptedPassword()
// todo function testApiIsValidSecretKeyReturnBooleanValue()
// todo function testApiIsUserOfCourseReturnBooleanValue()
// todo function testApiIsWnidowsOsReturnBooleanValue()
// todo function testApiUrlToLocalPathReturnString()
// todo function testApiResizeImage()
// todo function testApiCalculateImageSizeReturnArray()
	/*
	/**
	 * Test out of a course context
    

	 
	function testApiProtectCourseScriptReturnsFalseWhenOutOfCourseContext(){
		$res= api_protect_course_script();
		$this->assertTrue($res);
	
	}
    
    function testApiGetSettingReturnsTrueWhenIsRightValue(){
	 	$res=api_get_setting();
		$this->assertFalse($res);
	
	}
  
	/**
	/* Test out of a Admin context
	
	
	function testApiProtectAdminScriptReturnsFalseWhenOutOfCourseContext(){
	 	$res= api_protect_admin_script();
	 	$this->assertTrue($res);
	 
	}
	 
	function testApiBlockAnonymousUsersReturnTrueWhenUserIsAnonymous(){
	 	$res=api_block_anonymous_users();
	 	$this->assertTrue($res);
	 
	}
   
	function testApiGetNavigator(){	
	 	$res=api_get_navigator();
	 	$this->assertTrue($res);
	 
	}
    
	function testApiIsSelfRegistrationAllowed(){
		$res = api_is_self_registration_allowed(); 
		$this->assertFalse($res);
	
	}
	
	function testApiGetPath($path_type){
		$res=api_get_path();
	 	$this->assertFalse($res);
	
	}
	
	function testApiGetUserId(){	
		$res= api_get_user_id();		
		$this->assertPattern('/\d/',$res);		
	
	}

    function testApiGetUserCoursesReturnTrueWhenOutOfCoursesContext(){
    	$res = api_get_user_courses();
    	$this->assertFalse($res);
    
    }
  
    function testApiGetUserInfoReturnFalseWhenOutOfUserInfoContext(){
    	$res = api_get_user_info();
   		$this->assertTrue($res);
    
    }
   
    function testApiGetUserInfoUsernameReturnTrueWhenOutOfUserInfoUsernameContext(){
    	$res=api_get_user_info_from_username();
    	$this->assertFalse($res);
    
    }
    
    function testApiGetCourseIdReturnFalseWhenOutOfCourseIdContext(){
    	$res =api_get_course_id();
    	$this->assertTrue($res);
    
    }

    function testApiGetCoursePathReturnFalseWhenOutOfCoursePathContext(){
    	$res = api_get_course_path();
    	$this->assertFalse($res);	
    
    }
 
	function testApiGetCourseSettingReturnFalseWhenOutOfCourseSeetingContext(){
		$res = api_get_course_setting();
		$this->assertTrue($res);
	
	}
	
	function testApiGetAnonymousId(){
		$res = api_get_anonymous_id();
		$this->assertTrue($res);
	
	}
		
	function testApiGetCidreq(){
		$res=api_get_cidreq();
		$this->assertTrue($res);
	
	}
	
	function testApiGetCourseInfo(){
		$res=api_get_course_info();
		$this->assertTrue($res);
	
	}
	
	function testApiSqlQuery(){
		$res = api_sql_query();
		$this->assertFalse($res);
	
	}
	
	function testApiStoreResult(){
		$res = api_store_result();
		$this->assertFalse($res);
		
	}
	
	function testApiSessionStart(){
		$res = api_session_start($already_sintalled=true);
		$this->assertFalse($res);
	
	}
	
	function testApiSessionRegister(){
		$$variable[session_register]=false;
		$res=api_session_register($$variable);
		$this->assertFalse($res);
		$this->assertFalse($variable[session_register]);
	
	}
	
	function testApiSessionUnregister(){
		$variable=strval($variable);
		$res=api_session_unregister($variable);
		$this->assertFalse($res);
		$this->assertFalse(isset($GLOBALS[$variable]));
		$this->assertFalse($_SESSION[$variable]=null);
	
	}
	
	function testApiSessionClear(){
		$res=api_session_clear();
		$this->assertFalse($res);
	
	}
	
	function testApiSessionDestroy(){
		$res=api_session_destroy();
		$this->assertFalse($res);
	
	}
	
	function testApiAddUrlParam(){
		global $url , $param ; 
		$res=api_add_url_param($url, $param, $filter_xss=true);
		$this->assertFalse($res);
	
	}
	
	function testApiGeneratePassword(){
		$res = api_generate_password();
	    $this->assertTrue($res);
	
	}
	
	function testApiCheckPassword(){
		$lengthPass=strlen(5);
		$password= $lengthPass;
		$res = api_check_password($password);
		return $this->assertFalse($res);
	
	}
	
	function testApiClearAnonymous(){
		global $_user;
		$_user['user_id'] = 1;
		$res = api_clear_anonymous($db_check=false);
		$this->assertFalse($res);
		$this->assertTrue(isset($_user['user_id'] ));
	
	}
	
	function testApiTruncStr(){
		$res= api_trunc_str();
		return $this->assertFalse($res);
	
	}
	
	function testDomesticate(){
		$input= 'dome';
		$res = domesticate($input);
		return $this->assertTrue($res);
	
	}
	
	function testGetStatusFromCode(){
		$status_code = 1;
		$res=get_status_from_code($status_code);
		return $this->assertTrue($res);
	
	}
	
	function testApiSetFailure(){
		global $api_failureList;
		$failureType=true;
		$res = api_set_failure($failureType);
		$this->assertFalse($res);
		$this->assertTrue($api_failureList);
	
	}
	
	function testApiSetAnonymous(){
		$res = api_set_anonymous();
		return $this->assertFalse($res);
	
	}
	
	function testApiGetLastFailure(){
		$res= api_Get_last_failure();
		$this->assertTrue($res);
	
	}
	
	function testApiGetSessionId(){
		$res = api_get_session_id();
		return $this->assertFalse($res);
	
	}
	
	function testApiGetSessionName(){
		$session_id['sesion_id']=1;
		$res = api_get_session_name($session_id);
		$this->assertTrue($res);
	
	}
	
	function testAPiGetSetting(){
		global $variable;
	    $res=api_get_setting($variable, $key= NULL);
		return $this->assertFalse($res);
	
	}
	
	function testApiGetSelf(){
		$res = api_get_self();
		return $this->assertTrue($res);
	
	}
	
    /**
     * function still unproved 
     
   function testGetLang(){
   global $language_interface, $language_interface_initial_value, $language_file;
   static $cache=array();
   		$language=$language_interface;	
		$cache[$language]=array(false=> array(), true=>array());
		$res = get_lang($variable, $notrans = 'DLTT', $language = null);
		
	
	}
	/**
	 * function still unproved 
	*
	function testGetLangToSystemEncoding(){
		$language;
		$res=&get_lang_to_system_encoding(& $string, $language);
		
		
	}
	
	function testApiGetInterfaceLanguage(){
		global $language_interface;
		$language_interface=false;
		$res=api_get_interface_language();
		$this->assertFalse($res);
		$this->assertTrue(isset($language_interface));
	
	}
	
	function testApiIsPlatformAdmin(){
		global $_user;
		$_user['status']=true;
		$allow_sessions_admins=true;
		$res= api_is_platform_admin($allow_sessions_admins=true);
		$this->assertTrue($res);
		$this->assertTrue($_SESSION['is_platformAdmin']=true);
		$this->assertTrue(isset($_user['status']));
		
	}
	
	function testApiIsAllowedToCreateCourse(){
		$res=api_is_allowed_to_create_course();
		$this->assertFalse($res);
	
	}
	
	function testApiIsCourseAdmin(){
		$res=api_is_course_admin();
		$this->assertFalse($res);
	
	}
	
	function testApiIsCourseCoach(){
		$res=api_is_course_coach();
		$this->assertFalse($res);
	
	}
	
	function testApiIsCoach(){
		global $_user;
		global $sessionIsCoach;
		$_user['user_id']=1;
		$sessionIsCoach=api_store_result($result=false);
		$res=api_is_coach();
		$this->assertTrue($res);
		$this->assertTrue($_user['user_id']);
		$this->assertTrue($sessionIsCoach);
	
	}
	
	function testApiIsSessionAdmin(){
		global $_user;
		$_user['status']=true;
		$res=api_is_session_admin();
		$this->assertTrue($res);
		$this->assertTrue($_user);
	
	}
		
	function testApiDisplayToolTitle(){
		$tit=true;
		$titleElement['mainTitle']=$tit;
		$res=api_display_tool_title($titleElement);
		$this->assertFalse($res);
		$this->assertTrue(isset($titleElement));
		$this->assertTrue($titleElement['mainTitle']);
		
	}/**
		untested
	
	/*	
	function testApiDisplayToolViewOption(){
		
		$res=api_display_tool_view_option();
		
	}
		
	
	function testApiDisplayArray(){
		global $info_array;
		$res=api_display_array($info_array);
		$this->assertFalse($res);
			
	}
		
	function testApiDisplayDebugInfo(){
		$message = "mensaje de error"; // siempre que puedas, te conviene probar con valores creados al azar
		$res=api_display_debug_info($message);
		$this->assertFalse($res);
		
	}
		
	/**
	 * function is_allowed_to_edit() is deprecated and have been instead by 
	 * api_is_allowed_to_edit() 
	 * 
	
	function testApiIsAllowedToEdit(){
	 	$is_courseAdmin=false;
	 	$res=api_is_allowed_to_edit($tutor=false,$scoach=false);
	 	$this->assertTrue($res);
	 	$this->assertTrue(isset($is_courseAdmin));
	 	//$this->assertTrue($is_courseAdmin);	 	
	
	}
	 
	function testApiIsAllowed(){
	    global $_course;
	 	global $_user;
	 	$_user['user_id']=1;
	 	$_course['code']=0;
	  	$tool= true;
	 	$action= api_get_setting();	 		 	
	 	$res=api_is_allowed($task_id=0);
	  	$this->assertFalse($res);
	  	$this->assertFalse($action);
	 	$this->assertTrue($_user['user_id']);
	 	$this->assertFalse($_course['code']);
	 
	 }
	 
	 function testApiIsAnonymous(){
	 	global $_user, $use_anonymous;
	  	$_user['is_anonymous']=False;
	 	$res=api_is_anonymous($user_id=null, $db_check=false);
	 	$this->assertFalse($res);
	 	$this->assertFalse(isset($user_id));
	 	$this->assertTrue(isset($_user['is_anonymous']));
	 	$this->assertFalse($use_anonymous);
	 	$this->assertFalse($db_check);
	 
	 }
	 
	 
	 /**
	  * test was stopped because of errors in the interpretation of 
	  * the role, find out more details.
	 
	 
	 function testApiNotAllowed(){
 	  	$res=api_not_allowed($sprint_headers=false);
	 	$this->assertFalse($res);

	 }
	 
	 
	 function testConvertMysqlDate(){
	 	$result=false;
	 	$myrow = Database::fetch_array($result);
	 	$last_post_datetime = $myrow['end_date'];
	 	
	 	$res=convert_mysql_date($last_post_datetime);
	 	$this->assertTrue($res);
	 	$this->assertFalse($result);
	 	$this->assertFalse($myrow);
	 
	 } 
	
	 function testApiGetDatetime(){
	 	$res=api_get_datetime($time=null);
	 	$this->assertTrue($res);
	 	$this->assertFalse(isset($time));
	 
	 }
	 
	 function testApiGetItemVisibility(){
	 	$_course= -1;
	 	$tool = Database::escape_string($tool);
	 	$id=Database::escape_string($id);
	 	$_course['dbName']=false;
	 	$res =api_get_item_visibility($_course,$tool,$id);
	 	$this->assertTrue($res);
	 	$this->assertFalse(isset($_course['dbName']));
	 
	 }
	 /**
	  * function very complex and analized test is empty
	  *	
	  	function testApiItemPropertyUpdate(){
	  	$res=api_item_property_update($_course, $tool, $item_id, $lastedit_type, $user_id, $to_group_id = 0, $to_user_id = NULL, $start_visible = 0, $end_visible = 0);
	 	
	 }
		
	function testApiGetLanguagesCombo(){
		$platformLanguage = api_get_setting('platformLanguage');
		$language_list = api_get_languages();
		$res=api_get_languages_combo($name="language");
		$this->assertTrue($res);
		$this->assertTrue($platformLanguage);
		$this->assertTrue($language_list['name']);
		
	}
	
	function testApiDisplayLanguageForm(){
		
		$res=api_display_language_form($hide_if_no_choice=false);
		$this->assertFalse($res);
	
	}
		
	function testApiGetLanguages(){
		$result=true;
		$row = mysql_fetch_array($result);
		$res= api_get_languages();
		$this->assertTrue($res);
		$this->assertFalse($row);
	
	}

	function testApiGetLanguageIsocode(){
		$sql= true;
		$var=api_sql_query($sql,_FILE_,_LINE_);
		$res=api_get_language_isocode();
		$this->assertFalse($res);
		$this->assertTrue(isset($var));
	
	}
	
	function testApiGetThemes(){
		$cssdir= api_get_path(SYS_PATH).'main/css/';
		$res=api_get_themes();
		$this->assertTrue($res);
		$this->assertTrue($cssdir);
	
	}

	function testApiDispHtmlArea(){
		$name = 'hola';
		global $_configuration, $_course, $fck_attribute;
		$res=api_disp_html_area($name, $content ='', $height='', $width='100%', $optAttrib='');
		$this->assertFalse($res);
		
	}
	
	function testApiReturnHtmlArea(){
		//require_once(dirname(__FILE__).'/formvalidator/Element/html_editor.php');
		
		$name = true;
		global $_configuration, $_course, $fck_attribute;
		$res=api_return_html_area($name, $content='', $height='', $width='100%', $optAttrib='');
		$this->assertTrue($res);
		
	}
	
	function testApiSendMail(){
		$to= 'ricardo.rodriguez@dokeos.com'; 
		$subject='Hola'; 
		$message="prueba de envio"; 
		$send_mail=mail();
		$res=api_send_mail($to, $subject, $message, $additional_headers = null, $additional_parameters = null);
		$this->assertTrue($res);
		$this->assertFalse($send_mail);
		
	}
	
	function testApiMaxSortValue(){
	    global $image, $target_width, $target_height;
		$res= api_max_sort_value($image, $target_width, $target_height);
		$this->assertFalse($res);
		
	}
	
	*/
	function testApiCreateIncludePathSetting(){
		
	}
	/*
	function testApiGetCurrentAccessUrlId(){
		
		$res=api_get_current_access_url_id();
		$this->assertTrue($res);
		
	}
	
	
	function testApiGetAccessUrlFromUser(){
		$user_id=1;
		$res= api_get_access_url_from_user($user_id);
		$this->assertFalse($res);
		
	}
	
	
	function testApiGetStausOfUserInCourse(){
		$id = array(
					'course_code'=>'211',
					'user_id'=>'112');
		$res=api_get_status_of_user_in_course($id['course_code'],$id['user_id']);
		$this->assertFalse($res);
		//$this->assertPattern('/\d/',$res);
	}

	function testApiIsInCourse(){
		$_SESSION['_course']['sysCode']=0;
		$res=api_is_in_course($course_code=null);
		$this->assertTrue($res);
		$this->assertTrue(isset($_SESSION['_course']['sysCode']));
	}
	
	function testApiIsInGroup(){
		
		$res=api_is_in_group($group_id=null, $course_code=null);
		$this->assertFalse($res);
	}
	
	
	function testApiIsXmlHttpRequest(){
		$res=api_is_xml_http_request();
		$this->assertTrue(isset($res));
	}
	
	
	function testApiGetEncryptedPassword(){
		global $userPasswordCrypted;
		$pass= array ('password'=> '2222');
		$res=api_get_encrypted_password($pass['password'],null);
		$this->assertTrue($res);
		$this->assertPattern('/\d/',$res);
		
	}
			
	
	function testApiIsValidSecretKey(){
		global $_configuration;
		//$_configuration['key']=true;
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
		$this->assertFalse($res);
		$this->assertFalse(var_dump(api_is_windows_os()));
	
	}
	
		
	function testApiUrlToLocalPath(){
		global $url;
		$cond= preg_match(false);
		$res= api_url_to_local_path($url);
		$this->assertFalse($res);
		$this->assertFalse($cond);

	}
	
	function testApiResizeImage(){
		global $image, $target_width, $target_height;
		$res = api_resize_image($image,$target_width,$target_height);
		$this->assertTrue($res);
	
	}
		
	function testApiCalculateImageSize(){
		global $image_width, $image_height, $target_width, $target_height;
		$result = array($image_width, $image_height);
		$res = api_calculate_image_size($image_width, $image_height, $target_width, $target_height);
		$this->assertTrue($res);
		$this->assertTrue($result);
	
	}
	*/
}

?>
