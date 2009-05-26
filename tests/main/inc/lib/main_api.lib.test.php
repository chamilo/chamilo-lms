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
    
	 
	function testApiProtectCourseScriptReturnsFalseWhenOutOfCourseContext(){
		$res= api_protect_course_script();
		$this->assertTrue($res);
	}
    
    function testApiGetSettingReturnsTrueWhenIsRightValue(){
	 	$res=api_get_setting();
	 	$this->assertFalse($res);
	}
  
	/*
	/* Test out of a Admin context
	/
	 
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
	 	$this->assertTrue($res);
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
    	$this->assertTrue($res);	
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
		$this->assertTrue($res);
	}
	
	function testApiSessionRegister(){
		$res = api_session_register($variable);
		$this->assertFalse($res);
	}
	
	function testApiSessionUnregister(){
		$res = api_session_unregister($variable);
		$this->assertFalse($res);
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
		$res=api_add_url_param($url, $param, $filter_xss=true);
		$this->assertFalse($res);
	}
	
	function testApiGeneratePassword(){
		$res = api_generate_password();
	    $this->assertTrue($res);
	}
	
	function testApiCheckPassword(){
		$res = api_check_password($password);
		return $this->assertFalse($res);
	}
	
	function testApiClearAnonymous(){
		$res = api_clear_anonymous($db_check=false);
		return $this->assertFalse($res);
	}
	*/
	function testApiTruncStr(){
		$res= api_trunc_str($text, $length = 30, $endStr = '...', $middle = false);
		return $this->assertFalse($res);
	}


}

?>
