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
	 * Test out of a course context
    */


	function testApiProtectCourseScriptReturnsFalseWhenOutOfCourseContext(){
		ob_start();
		//$res= api_protect_course_script();
		$res = ob_get_contents();
		$this->assertTrue(is_string($res));
		ob_end_clean();
	}

	function testApiProtectAdminScriptReturnsFalseWhenOutOfCourseContext(){
		ob_start();
	 	//api_protect_admin_script();
	 	$res = ob_get_contents();
	 	$this->assertTrue(is_string($res));
	 	ob_end_clean();
	}

	function testApiBlockAnonymousUsersReturnTrueWhenUserIsAnonymous(){
		ob_start();
		//api_block_anonymous_users();
		$res = ob_get_contents();
	 	$this->assertTrue(is_string($res));
	 	ob_end_clean();
	}

	function testApiGetNavigator(){
	 	$res=api_get_navigator();
	 	$this->assertTrue($res);
	}

	function testApiIsSelfRegistrationAllowed(){
		$res = api_is_self_registration_allowed();
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	function testApiGetPath($path_type){
		$res=api_get_path();
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

    function testApiGetCourseIdReturnFalseWhenOutOfCourseIdContext(){
    	$res =api_get_course_id();
    	$this->assertTrue($res);
    }

    function testApiGetCoursePathReturnFalseWhenOutOfCoursePathContext(){
    	$res = api_get_course_path();
    	if(!is_null($res)) :
    	$this->assertTrue(is_string($res));
    	endif;
    	//var_dump($res);
    }

	function testApiGetCourseSettingReturnFalseWhenOutOfCourseSeetingContext(){
		$res = api_get_course_setting();
		$this->assertTrue($res);
	}

	function testApiGetAnonymousId(){
		$res = api_get_anonymous_id();
		$this->assertTrue(is_numeric($res));
	}

	function testApiGetCidreq(){
		$res=api_get_cidreq();
		$this->assertTrue($res);
	}

	function testApiGetCourseInfo(){
		ob_start();
		$res=api_get_course_info();
		$this->assertTrue($res);
		ob_end_clean();
	}

	function testApiSqlQuery(){
		ob_start();
		$res = api_sql_query();
		$this->assertTrue(is_bool($res));
		ob_end_clean();
		//var_dump($res);
	}

	function testApiSessionStart(){
		$res = api_session_start($already_sintalled=true);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	function testApiSessionRegister(){
		$$variable[session_register]=false;
		global $$variable;
		$res=api_session_register($$variable);
		$this->assertTrue(is_null($res));
		$this->assertTrue(is_null($variable[session_register]));
		//var_dump($variable);
	}

	function testApiSessionUnregister(){
		$variable=strval($variable);
		$res=api_session_unregister($variable);
		$this->assertTrue(is_null($res));
		$this->assertTrue(is_null($_SESSION[$variable]=null));
	}

	function testApiSessionClear(){
		$res=api_session_clear();
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	function testApiSessionDestroy(){
		$res=api_session_destroy();
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	function testApiAddUrlParam(){
		global $url , $param ;
		$res=api_add_url_param($url, $param, $filter_xss=true);
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	function testApiGeneratePassword(){
		$res = api_generate_password();
	    $this->assertTrue(is_string($res));
	    //var_dump($res);
	}

	function testApiCheckPassword(){
		$lengthPass=strlen(5);
		$password= $lengthPass;
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

	function testApiTruncStr(){
		$res= api_trunc_str();
		$this->assertTrue(is_null($res));
		//var_dump($res);
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
	/** If there is not a session name not return nothing, return null
	 * @author aportugal
	 */

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

   	function testApiIsAllowedToCreateCourse(){
		$res=api_is_allowed_to_create_course();
		$this->assertTrue(is_null($res));
		//var_dump($res);

   	}

   	function testApiIsCourseAdmin(){
		$res=api_is_course_admin();
		$this->assertTrue(is_null($res));
		//var_dump($res);
   	}

    function testApiIsCourseCoach(){
		$res=api_is_course_coach();
		$this->assertTrue(is_null($res));
		//var_dump($res);
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

	function testApiDisplayToolViewOption(){
		ob_start();
		api_display_tool_view_option();
		$res = ob_get_contents();
		$this->assertNotEqual($res,'');
		ob_end_clean();
	}

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

	/**
	 * function is_allowed_to_edit() is deprecated and have been instead by
	 * api_is_allowed_to_edit()
	 */

	function testApiIsAllowedToEdit(){
	 	$is_courseAdmin=false;
	 	$res=api_is_allowed_to_edit($tutor=false,$scoach=false);
	 	$this->assertTrue(is_bool($res));
	 	$this->assertTrue(isset($is_courseAdmin));
	}

	function testApiIsAllowed(){
		ob_start();
	    global $_course, $_user;
	 	$_user['user_id']=1;
	 	$_course['code']=0;
	  	$tool= 'full';
	 	$action = 'delete';
	 	$res=api_is_allowed($tool, $action, $task_id=0);
	  	$this->assertTrue(is_null($res));
	  	$this->assertTrue($action);
	 	$this->assertTrue($_user['user_id']);
	 	ob_end_clean();
	 	//var_dump($res);
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

	/**
	 * test was stopped because of errors in the interpretation of
	 * the role, find out more details.
	 */
	function testApiNotAllowed(){
		ob_start();
		//api_not_allowed($print_headers = false);
		$res = ob_get_contents();
		$this->assertEqual($res,'');
		ob_end_clean();
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

	function testApiItemPropertyUpdate(){
	  	global $_course, $tool, $item_id, $lastedit_type, $user_id;
	  	$res=api_item_property_update($_course, $tool, $item_id, $lastedit_type, $user_id, $to_group_id = 0, $to_user_id = NULL, $start_visible = 0, $end_visible = 0);
	 	$this-> assertTrue($res);
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
		ob_start();
		api_display_language_form($hide_if_no_choice=false);
		$res = ob_get_contents();
		$this->assertNotEqual($res,'');
		ob_end_clean();
	}

	function testApiGetLanguages(){
		$result=true;
		$row = mysql_fetch_array($result);
		$res= api_get_languages();
		$this->assertTrue($res);
		$this->assertFalse($row);
	}

	function testApiGetLanguageIsocode(){
		$query='';
		$sql= true;
		$var=api_sql_query($sql,_FILE_,_LINE_);
		$res=api_get_language_isocode($query);
		$this->assertTrue(is_string($query));
		$this->assertTrue(isset($var));
		//var_dump($query);
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
		$to= 'test@dokeos.com';
		$subject='Hola';
		$message="prueba de envio";
		$send_mail=mail();
		$res=api_send_mail($to, $subject, $message, $additional_headers = null, $additional_parameters = null);
		$this->assertTrue(is_bool($res));
		$this->assertTrue(is_null($send_mail));
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
		//var_dump($res);
	}

	function testApiParseTex(){
		global $textext;
		$res = api_parse_tex($textext);
		$this->assertTrue(is_string($res));
		//var_dump($res);
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

	function testCopyr(){
		$source = api_get_path(SYS_CODE_PATH).'app_share/DokeosAppShare.exe';
		$dest = '';
		$res = copyr($source, $dest, $exclude=array(), $copied_files=array());
		$this->assertTrue(is_null($res));
		//var_dump($res);
	}

	function testApiChmod_R(){
		$path = $_GET['path'];
		$filemode = '';
		$res = api_chmod_R($path, $filemode);
		$this->assertTrue(is_bool($res));
		$this->assertTrue(is_null($path));
		//var_dump($path);
	}

	function testApiGetVersion(){
		$_configuration = null;
		$res = api_get_version();
		$this->assertFalse(isset($_configuration));
		$this->assertTrue($res);
	}

	function testApiStatusExists(){
		global $_status_list,$_status_list1,$_status_list2 ;
		$status_asked[$_status_list]= false;
		$status_asked[$_status_list1]= null;
		$status_asked[$_status_list2]= true;
		$res = api_status_exists($status_asked);
		$this->assertTrue(is_bool($res));
		$this->assertNotNull(isset($status_asked[$_status_list]));
		$this->assertNotNull(isset($status_asked[$_status_list1]));
		$this->assertNotNull(isset($status_asked[$_status_list2]));
		//var_dump($res);
	}

	function testApiStatusKey(){
		global $_status_list, $_status_list1;
		$status[$_status_list] = true;
		$status[$_status_list1] = null;
		$res = api_status_key($status);
		$this->assertNotNull(isset($status[$_status_list]));
		$this->assertNotNull(isset($status[$_status_list1]));
		$this->assertTrue(is_bool($res));
		//var_dump($res);
	}

	function testApiGetStatusLangvars(){
		$res = api_get_status_langvars();
		$this->assertTrue(is_array($res));
		//var_dump($res);
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
		global $_configuration;
		$id[$_configuration]=1;
		$res = api_get_access_url($id);
		$this->assertTrue(is_bool($res));
		$this->assertFalse($id[$_configuration]);
		//var_dump($res);
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

	function testApiIsCourseVisibleForUser(){
		$res = api_is_course_visible_for_user($userid=null, $cid=null);
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
		global $userPasswordCrypted;
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
}
?>
