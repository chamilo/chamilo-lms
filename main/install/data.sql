-- After the database schema is created, the database is filled
-- with default values.

INSERT INTO settings_current
(variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable)
VALUES
('Institution',NULL,'textfield','Platform','{ORGANISATIONNAME}','InstitutionTitle','InstitutionComment','platform',NULL, 1),
('InstitutionUrl',NULL,'textfield','Platform','{ORGANISATIONURL}','InstitutionUrlTitle','InstitutionUrlComment',NULL,NULL, 1),
('siteName',NULL,'textfield','Platform','{CAMPUSNAME}','SiteNameTitle','SiteNameComment',NULL,NULL, 1),
('emailAdministrator',NULL,'textfield','Platform','{ADMINEMAIL}','emailAdministratorTitle','emailAdministratorComment',NULL,NULL, 1),
('administratorSurname',NULL,'textfield','Platform','{ADMINLASTNAME}','administratorSurnameTitle','administratorSurnameComment',NULL,NULL, 1),
('administratorName',NULL,'textfield','Platform','{ADMINFIRSTNAME}','administratorNameTitle','administratorNameComment',NULL,NULL, 1),
('show_administrator_data',NULL,'radio','Platform','true','ShowAdministratorDataTitle','ShowAdministratorDataComment',NULL,NULL, 1),
('show_tutor_data',NULL,'radio','Session','true','ShowTutorDataTitle','ShowTutorDataComment',NULL,NULL, 1),
('show_teacher_data',NULL,'radio','Platform','true','ShowTeacherDataTitle','ShowTeacherDataComment',NULL,NULL, 1),
('homepage_view',NULL,'radio','Course','activity_big','HomepageViewTitle','HomepageViewComment',NULL,NULL, 1),
('show_toolshortcuts',NULL,'radio','Course','false','ShowToolShortcutsTitle','ShowToolShortcutsComment',NULL,NULL, 0),
('allow_group_categories',NULL,'radio','Course','false','AllowGroupCategories','AllowGroupCategoriesComment',NULL,NULL, 0),
('server_type',NULL,'radio','Platform','production','ServerStatusTitle','ServerStatusComment',NULL,NULL, 0),
('platformLanguage',NULL,'link','Languages','{PLATFORMLANGUAGE}','PlatformLanguageTitle','PlatformLanguageComment',NULL,NULL, 0),
('showonline','world','checkbox','Platform','true','ShowOnlineTitle','ShowOnlineComment',NULL,'ShowOnlineWorld', 0),
('showonline','users','checkbox','Platform','true','ShowOnlineTitle','ShowOnlineComment',NULL,'ShowOnlineUsers', 0),
('showonline','course','checkbox','Platform','true','ShowOnlineTitle','ShowOnlineComment',NULL,'ShowOnlineCourse', 0),
('profile','name','checkbox','User','false','ProfileChangesTitle','ProfileChangesComment',NULL,'Name', 0),
('profile','officialcode','checkbox','User','false','ProfileChangesTitle','ProfileChangesComment',NULL,'OfficialCode', 0),
('profile','email','checkbox','User','false','ProfileChangesTitle','ProfileChangesComment',NULL,'Email', 0),
('profile','picture','checkbox','User','true','ProfileChangesTitle','ProfileChangesComment',NULL,'UserPicture', 0),
('profile','login','checkbox','User','false','ProfileChangesTitle','ProfileChangesComment',NULL,'Login', 0),
('profile','password','checkbox','User','true','ProfileChangesTitle','ProfileChangesComment',NULL,'UserPassword', 0),
('profile','language','checkbox','User','true','ProfileChangesTitle','ProfileChangesComment',NULL,'Language', 0),
('default_document_quotum',NULL,'textfield','Course','100000000','DefaultDocumentQuotumTitle','DefaultDocumentQuotumComment',NULL,NULL, 0),
('registration','officialcode','checkbox','User','false','RegistrationRequiredFormsTitle','RegistrationRequiredFormsComment',NULL,'OfficialCode', 0),
('registration','email','checkbox','User','true','RegistrationRequiredFormsTitle','RegistrationRequiredFormsComment',NULL,'Email', 0),
('registration','language','checkbox','User','true','RegistrationRequiredFormsTitle','RegistrationRequiredFormsComment',NULL,'Language', 0),
('default_group_quotum',NULL,'textfield','Course','5000000','DefaultGroupQuotumTitle','DefaultGroupQuotumComment',NULL,NULL, 0),
('allow_registration',NULL,'radio','Platform','{ALLOWSELFREGISTRATION}','AllowRegistrationTitle','AllowRegistrationComment',NULL,NULL, 0),
('allow_registration_as_teacher',NULL,'radio','Platform','{ALLOWTEACHERSELFREGISTRATION}','AllowRegistrationAsTeacherTitle','AllowRegistrationAsTeacherComment',NULL,NULL, 0),
('allow_lostpassword',NULL,'radio','Platform','true','AllowLostPasswordTitle','AllowLostPasswordComment',NULL,NULL, 0),
('allow_user_headings',NULL,'radio','Course','false','AllowUserHeadings','AllowUserHeadingsComment',NULL,NULL, 0),
('course_create_active_tools','course_description','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'CourseDescription', 0),
('course_create_active_tools','agenda','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Agenda', 0),
('course_create_active_tools','documents','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Documents', 0),
('course_create_active_tools','learning_path','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'LearningPath', 0),
('course_create_active_tools','links','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Links', 0),
('course_create_active_tools','announcements','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Announcements', 0),
('course_create_active_tools','forums','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Forums', 0),
('course_create_active_tools','dropbox','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Dropbox', 0),
('course_create_active_tools','quiz','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Quiz', 0),
('course_create_active_tools','users','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Users', 0),
('course_create_active_tools','groups','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Groups', 0),
('course_create_active_tools','chat','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Chat', 0),
('course_create_active_tools','student_publications','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'StudentPublications', 0),
('allow_personal_agenda',NULL,'radio','User','true','AllowPersonalAgendaTitle','AllowPersonalAgendaComment',NULL,NULL, 0),
('display_coursecode_in_courselist',NULL,'radio','Platform','false','DisplayCourseCodeInCourselistTitle','DisplayCourseCodeInCourselistComment',NULL,NULL, 0),
('display_teacher_in_courselist',NULL,'radio','Platform','true','DisplayTeacherInCourselistTitle','DisplayTeacherInCourselistComment',NULL,NULL, 0),
('permanently_remove_deleted_files',NULL,'radio','Tools','false','PermanentlyRemoveFilesTitle','PermanentlyRemoveFilesComment',NULL,NULL, 0),
('dropbox_allow_overwrite',NULL,'radio','Tools','true','DropboxAllowOverwriteTitle','DropboxAllowOverwriteComment',NULL,NULL, 0),
('dropbox_max_filesize',NULL,'textfield','Tools','100000000','DropboxMaxFilesizeTitle','DropboxMaxFilesizeComment',NULL,NULL, 0),
('dropbox_allow_just_upload',NULL,'radio','Tools','true','DropboxAllowJustUploadTitle','DropboxAllowJustUploadComment',NULL,NULL, 0),
('dropbox_allow_student_to_student',NULL,'radio','Tools','true','DropboxAllowStudentToStudentTitle','DropboxAllowStudentToStudentComment',NULL,NULL, 0),
('dropbox_allow_group',NULL,'radio','Tools','true','DropboxAllowGroupTitle','DropboxAllowGroupComment',NULL,NULL, 0),
('dropbox_allow_mailing',NULL,'radio','Tools','false','DropboxAllowMailingTitle','DropboxAllowMailingComment',NULL,NULL, 0),
('administratorTelephone',NULL,'textfield','Platform','(000) 001 02 03','administratorTelephoneTitle','administratorTelephoneComment',NULL,NULL, 1),
('extended_profile',NULL,'radio','User','false','ExtendedProfileTitle','ExtendedProfileComment',NULL,NULL, 0),
('student_view_enabled',NULL,'radio','Platform','true','StudentViewEnabledTitle','StudentViewEnabledComment',NULL,NULL, 0),
('show_navigation_menu',NULL,'radio','Course','false','ShowNavigationMenuTitle','ShowNavigationMenuComment',NULL,NULL, 0),
('enable_tool_introduction',NULL,'radio','course','false','EnableToolIntroductionTitle','EnableToolIntroductionComment',NULL,NULL, 0),
('page_after_login', NULL, 'radio','Platform','user_portal.php', 'PageAfterLoginTitle','PageAfterLoginComment', NULL, NULL, 0),
('time_limit_whosonline', NULL, 'textfield','Platform','30', 'TimeLimitWhosonlineTitle','TimeLimitWhosonlineComment', NULL, NULL, 0),
('breadcrumbs_course_homepage', NULL, 'radio','Course','course_title', 'BreadCrumbsCourseHomepageTitle','BreadCrumbsCourseHomepageComment', NULL, NULL, 0),
('example_material_course_creation', NULL, 'radio','Platform','true', 'ExampleMaterialCourseCreationTitle','ExampleMaterialCourseCreationComment', NULL, NULL, 0),
('account_valid_duration',NULL, 'textfield','Platform','3660', 'AccountValidDurationTitle','AccountValidDurationComment', NULL, NULL, 0),
('use_session_mode', NULL, 'radio','Session','true', 'UseSessionModeTitle','UseSessionModeComment', NULL, NULL, 0),
('allow_email_editor', NULL, 'radio', 'Tools', 'false', 'AllowEmailEditorTitle', 'AllowEmailEditorComment', NULL, NULL, 0),
('registered', NULL, 'textfield', NULL, 'false', 'registered', NULL, NULL, NULL, 0),
('donotlistcampus', NULL, 'textfield', NULL, 'false', 'donotlistcampus', NULL, NULL, NULL,0 ),
('show_email_addresses', NULL,'radio','Platform','false','ShowEmailAddresses','ShowEmailAddressesComment',NULL,NULL, 1),
('profile','phone','checkbox','User','false','ProfileChangesTitle','ProfileChangesComment',NULL,'Phone', 0),
('service_ppt2lp', 'active', 'radio',NULL,'false', 'ppt2lp_actived','', NULL, NULL, 0),
('service_ppt2lp', 'host', 'textfield', NULL, NULL, 'Host', NULL, NULL, NULL, 0),
('service_ppt2lp', 'port', 'textfield', NULL, 2002, 'Port', NULL, NULL, NULL, 0),
('service_ppt2lp', 'user', 'textfield', NULL, NULL, 'UserOnHost', NULL, NULL, NULL, 0),
('service_ppt2lp', 'ftp_password', 'textfield', NULL, NULL, 'FtpPassword', NULL, NULL, NULL, 0),
('service_ppt2lp', 'path_to_lzx', 'textfield', NULL, NULL, '', NULL, NULL, NULL, 0),
('service_ppt2lp', 'size', 'radio', NULL, '720x540', '', NULL, NULL, NULL, 0),
('stylesheets', NULL, 'textfield','stylesheets','chamilo','',NULL, NULL, NULL, 1),
('upload_extensions_list_type', NULL, 'radio', 'Security', 'blacklist', 'UploadExtensionsListType', 'UploadExtensionsListTypeComment', NULL, NULL, 0),
('upload_extensions_blacklist', NULL, 'textfield', 'Security', '', 'UploadExtensionsBlacklist', 'UploadExtensionsBlacklistComment', NULL, NULL, 0),
('upload_extensions_whitelist', NULL, 'textfield', 'Security', 'htm;html;jpg;jpeg;gif;png;swf;avi;mpg;mpeg;mov;flv;doc;docx;xls;xlsx;ppt;pptx;odt;odp;ods;pdf', 'UploadExtensionsWhitelist', 'UploadExtensionsWhitelistComment', NULL, NULL, 0),
('upload_extensions_skip', NULL, 'radio', 'Security', 'true', 'UploadExtensionsSkip', 'UploadExtensionsSkipComment', NULL, NULL, 0),
('upload_extensions_replace_by', NULL, 'textfield', 'Security', 'dangerous', 'UploadExtensionsReplaceBy', 'UploadExtensionsReplaceByComment', NULL, NULL, 0),
('show_number_of_courses', NULL, 'radio','Platform','false', 'ShowNumberOfCourses','ShowNumberOfCoursesComment', NULL, NULL, 0),
('show_empty_course_categories', NULL, 'radio','Platform','true', 'ShowEmptyCourseCategories','ShowEmptyCourseCategoriesComment', NULL, NULL, 0),
('show_back_link_on_top_of_tree', NULL, 'radio','Platform','false', 'ShowBackLinkOnTopOfCourseTree','ShowBackLinkOnTopOfCourseTreeComment', NULL, NULL, 0),
('show_different_course_language', NULL, 'radio','Platform','true', 'ShowDifferentCourseLanguage','ShowDifferentCourseLanguageComment', NULL, NULL, 1),
('split_users_upload_directory', NULL, 'radio','Tuning','true', 'SplitUsersUploadDirectory','SplitUsersUploadDirectoryComment', NULL, NULL, 0),
('hide_dltt_markup', NULL, 'radio','Languages','true', 'HideDLTTMarkup','HideDLTTMarkupComment', NULL, NULL, 0),
('display_categories_on_homepage',NULL,'radio','Platform','false','DisplayCategoriesOnHomepageTitle','DisplayCategoriesOnHomepageComment',NULL,NULL, 1),
('permissions_for_new_directories', NULL, 'textfield', 'Security', '0777', 'PermissionsForNewDirs', 'PermissionsForNewDirsComment', NULL, NULL, 0),
('permissions_for_new_files', NULL, 'textfield', 'Security', '0666', 'PermissionsForNewFiles', 'PermissionsForNewFilesComment', NULL, NULL, 0),
('show_tabs', 'campus_homepage', 'checkbox', 'Platform', 'true', 'ShowTabsTitle','ShowTabsComment',NULL,'TabsCampusHomepage', 1),
('show_tabs', 'my_courses', 'checkbox', 'Platform', 'true', 'ShowTabsTitle','ShowTabsComment',NULL,'TabsMyCourses', 1),
('show_tabs', 'reporting', 'checkbox', 'Platform', 'true', 'ShowTabsTitle','ShowTabsComment',NULL,'TabsReporting', 1),
('show_tabs', 'platform_administration', 'checkbox', 'Platform', 'true', 'ShowTabsTitle','ShowTabsComment',NULL,'TabsPlatformAdministration', 1),
('show_tabs', 'my_agenda', 'checkbox', 'Platform', 'true', 'ShowTabsTitle','ShowTabsComment',NULL,'TabsMyAgenda', 1),
('show_tabs', 'my_profile', 'checkbox', 'Platform', 'true', 'ShowTabsTitle','ShowTabsComment',NULL,'TabsMyProfile', 1),
('default_forum_view', NULL, 'radio', 'Course', 'flat', 'DefaultForumViewTitle','DefaultForumViewComment',NULL,NULL, 0),
('platform_charset',NULL,'textfield','Languages','UTF-8','PlatformCharsetTitle','PlatformCharsetComment','platform',NULL, 0),
('noreply_email_address', '', 'textfield', 'Platform', '', 'NoReplyEmailAddress', 'NoReplyEmailAddressComment', NULL, NULL, 0),
('survey_email_sender_noreply', '', 'radio', 'Course', 'coach', 'SurveyEmailSenderNoReply', 'SurveyEmailSenderNoReplyComment', NULL, NULL, 0),
('openid_authentication',NULL,'radio','Security','false','OpenIdAuthentication','OpenIdAuthenticationComment',NULL,NULL, 0),
('profile','openid','checkbox','User','false','ProfileChangesTitle','ProfileChangesComment',NULL,'OpenIDURL', 0),
('gradebook_enable',NULL,'radio','Gradebook','false','GradebookActivation','GradebookActivationComment',NULL,NULL, 0),
('show_tabs','my_gradebook','checkbox','Platform','true','ShowTabsTitle','ShowTabsComment',NULL,'TabsMyGradebook', 1),
('gradebook_score_display_coloring','my_display_coloring','checkbox','Gradebook','false','GradebookScoreDisplayColoring','GradebookScoreDisplayColoringComment',NULL,'TabsGradebookEnableColoring', 0),
('gradebook_score_display_custom','my_display_custom','checkbox','Gradebook','false','GradebookScoreDisplayCustom','GradebookScoreDisplayCustomComment',NULL,'TabsGradebookEnableCustom', 0),
('gradebook_score_display_colorsplit',NULL,'textfield','Gradebook','50','GradebookScoreDisplayColorSplit','GradebookScoreDisplayColorSplitComment',NULL,NULL, 0),
('gradebook_score_display_upperlimit','my_display_upperlimit','checkbox','Gradebook','false','GradebookScoreDisplayUpperLimit','GradebookScoreDisplayUpperLimitComment',NULL,'TabsGradebookEnableUpperLimit', 0),
('gradebook_number_decimals', NULL, 'select', 'Gradebook', '0', 'GradebookNumberDecimals', 'GradebookNumberDecimalsComment', NULL, NULL, 0),
('user_selected_theme',NULL,'radio','Platform','false','UserThemeSelection','UserThemeSelectionComment',NULL,NULL, 0),
('profile','theme','checkbox','User','false','ProfileChangesTitle','ProfileChangesComment',NULL,'UserTheme', 0),
('allow_course_theme',NULL,'radio','Course','true','AllowCourseThemeTitle','AllowCourseThemeComment',NULL,NULL, 0),
('show_closed_courses',NULL,'radio','Platform','false','ShowClosedCoursesTitle','ShowClosedCoursesComment',NULL,NULL, 0),
('extendedprofile_registration', 'mycomptetences', 'checkbox','User','false', 'ExtendedProfileRegistrationTitle','ExtendedProfileRegistrationComment', NULL, 'MyCompetences', 0),
('extendedprofile_registration', 'mydiplomas', 'checkbox','User','false', 'ExtendedProfileRegistrationTitle','ExtendedProfileRegistrationComment', NULL, 'MyDiplomas', 0),
('extendedprofile_registration', 'myteach', 'checkbox','User','false', 'ExtendedProfileRegistrationTitle','ExtendedProfileRegistrationComment', NULL, 'MyTeach', 0),
('extendedprofile_registration', 'mypersonalopenarea', 'checkbox','User','false', 'ExtendedProfileRegistrationTitle','ExtendedProfileRegistrationComment', NULL, 'MyPersonalOpenArea', 0),
('extendedprofile_registrationrequired', 'mycomptetences', 'checkbox','User','false', 'ExtendedProfileRegistrationRequiredTitle','ExtendedProfileRegistrationRequiredComment', NULL, 'MyCompetences', 0),
('extendedprofile_registrationrequired', 'mydiplomas', 'checkbox','User','false', 'ExtendedProfileRegistrationRequiredTitle','ExtendedProfileRegistrationRequiredComment', NULL, 'MyDiplomas', 0),
('extendedprofile_registrationrequired', 'myteach', 'checkbox','User','false', 'ExtendedProfileRegistrationRequiredTitle','ExtendedProfileRegistrationRequiredComment', NULL, 'MyTeach', 0),
('extendedprofile_registrationrequired', 'mypersonalopenarea', 'checkbox','User','false', 'ExtendedProfileRegistrationRequiredTitle','ExtendedProfileRegistrationRequiredComment', NULL, 'MyPersonalOpenArea', 0),
('registration','phone','checkbox','User','false','RegistrationRequiredFormsTitle','RegistrationRequiredFormsComment',NULL,'Phone', 0),
('add_users_by_coach',NULL,'radio','Session','false','AddUsersByCoachTitle','AddUsersByCoachComment',NULL,NULL, 0),
('extend_rights_for_coach',NULL,'radio','Security','false','ExtendRightsForCoachTitle','ExtendRightsForCoachComment',NULL,NULL, 0),
('extend_rights_for_coach_on_survey',NULL,'radio','Security','true','ExtendRightsForCoachOnSurveyTitle','ExtendRightsForCoachOnSurveyComment',NULL,NULL, 0),
('course_create_active_tools','wiki','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Wiki', 0),
('show_session_coach', NULL, 'radio','Session','false', 'ShowSessionCoachTitle','ShowSessionCoachComment', NULL, NULL, 0),
('course_create_active_tools','gradebook','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Gradebook', 0),
('allow_users_to_create_courses',NULL,'radio','Platform','true','AllowUsersToCreateCoursesTitle','AllowUsersToCreateCoursesComment',NULL,NULL, 0),
('course_create_active_tools','survey','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Survey', 0),
('course_create_active_tools','glossary','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Glossary', 0),
('course_create_active_tools','notebook','checkbox','Tools','true','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Notebook', 0),
('course_create_active_tools','attendances','checkbox','Tools','false','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Attendances', 0),
('course_create_active_tools','course_progress','checkbox','Tools','false','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'CourseProgress', 0),
('profile','apikeys','checkbox','User','false','ProfileChangesTitle','ProfileChangesComment',NULL,'ApiKeys', 0),
('allow_message_tool', NULL, 'radio', 'Tools', 'true', 'AllowMessageToolTitle', 'AllowMessageToolComment', NULL, NULL,1),
('allow_social_tool', NULL, 'radio', 'Tools', 'true', 'AllowSocialToolTitle', 'AllowSocialToolComment', NULL, NULL,1),
('allow_students_to_browse_courses',NULL,'radio','Platform','true','AllowStudentsToBrowseCoursesTitle','AllowStudentsToBrowseCoursesComment',NULL,NULL, 1),
('show_session_data', NULL, 'radio', 'Session', 'false', 'ShowSessionDataTitle', 'ShowSessionDataComment', NULL, NULL, 1),
('allow_use_sub_language', NULL, 'radio', 'Languages', 'false', 'AllowUseSubLanguageTitle', 'AllowUseSubLanguageComment', NULL, NULL,0),
('show_glossary_in_documents', NULL, 'radio', 'Course', 'none', 'ShowGlossaryInDocumentsTitle', 'ShowGlossaryInDocumentsComment', NULL, NULL,1),
('allow_terms_conditions', NULL, 'radio', 'Platform', 'false', 'AllowTermsAndConditionsTitle', 'AllowTermsAndConditionsComment', NULL, NULL,0),
('course_create_active_tools','enable_search','checkbox','Tools','false','CourseCreateActiveToolsTitle','CourseCreateActiveToolsComment',NULL,'Search',0),
('search_enabled',NULL,'radio','Search','false','EnableSearchTitle','EnableSearchComment',NULL,NULL,1),
('search_prefilter_prefix',NULL, NULL,'Search','','SearchPrefilterPrefix','SearchPrefilterPrefixComment',NULL,NULL,0),
('search_show_unlinked_results',NULL,'radio','Search','true','SearchShowUnlinkedResultsTitle','SearchShowUnlinkedResultsComment',NULL,NULL,1),
('show_courses_descriptions_in_catalog', NULL, 'radio', 'Course', 'true', 'ShowCoursesDescriptionsInCatalogTitle', 'ShowCoursesDescriptionsInCatalogComment', NULL, NULL, 1),
('allow_coach_to_edit_course_session',NULL,'radio','Session','true','AllowCoachsToEditInsideTrainingSessions','AllowCoachsToEditInsideTrainingSessionsComment',NULL,NULL, 0),
('show_glossary_in_extra_tools', NULL, 'radio', 'Course', 'none', 'ShowGlossaryInExtraToolsTitle', 'ShowGlossaryInExtraToolsComment', NULL, NULL,1),
('send_email_to_admin_when_create_course',NULL,'radio','Platform','false','SendEmailToAdminTitle','SendEmailToAdminComment',NULL,NULL, 1),
('go_to_course_after_login',NULL,'radio','Course','false','GoToCourseAfterLoginTitle','GoToCourseAfterLoginComment',NULL,NULL, 0),
('math_asciimathML',NULL,'radio','Editor','false','MathASCIImathMLTitle','MathASCIImathMLComment',NULL,NULL, 0),
('enabled_asciisvg',NULL,'radio','Editor','false','AsciiSvgTitle','AsciiSvgComment',NULL,NULL, 0),
('include_asciimathml_script',NULL,'radio','Editor','false','IncludeAsciiMathMlTitle','IncludeAsciiMathMlComment',NULL,NULL, 0),
('youtube_for_students',NULL,'radio','Editor','true','YoutubeForStudentsTitle','YoutubeForStudentsComment',NULL,NULL, 0),
('block_copy_paste_for_students',NULL,'radio','Editor','false','BlockCopyPasteForStudentsTitle','BlockCopyPasteForStudentsComment',NULL,NULL, 0),
('more_buttons_maximized_mode',NULL,'radio','Editor','true','MoreButtonsForMaximizedModeTitle','MoreButtonsForMaximizedModeComment',NULL,NULL, 0),
('students_download_folders',NULL,'radio','Tools','true','AllowStudentsDownloadFoldersTitle','AllowStudentsDownloadFoldersComment',NULL,NULL, 0),
('users_copy_files',NULL,'radio','Tools','true','AllowUsersCopyFilesTitle','AllowUsersCopyFilesComment',NULL,NULL, 1),
('show_tabs', 'social', 'checkbox', 'Platform', 'true', 'ShowTabsTitle','ShowTabsComment',NULL,'TabsSocial', 0),
('allow_students_to_create_groups_in_social',NULL,'radio','Tools','false','AllowStudentsToCreateGroupsInSocialTitle','AllowStudentsToCreateGroupsInSocialComment',NULL,NULL, 0),
('allow_send_message_to_all_platform_users',NULL,'radio','Tools','true','AllowSendMessageToAllPlatformUsersTitle','AllowSendMessageToAllPlatformUsersComment',NULL,NULL, 0),
('message_max_upload_filesize',NULL,'textfield','Tools','20971520','MessageMaxUploadFilesizeTitle','MessageMaxUploadFilesizeComment',NULL,NULL, 0),
('show_tabs', 'dashboard', 'checkbox', 'Platform', 'true', 'ShowTabsTitle', 'ShowTabsComment', NULL, 'TabsDashboard', 1),
('use_users_timezone', 'timezones', 'radio', 'Timezones', 'true', 'UseUsersTimezoneTitle','UseUsersTimezoneComment',NULL,'Timezones', 1),
('timezone_value', 'timezones', 'select', 'Timezones', '', 'TimezoneValueTitle','TimezoneValueComment',NULL,'Timezones', 1),
('allow_user_course_subscription_by_course_admin', NULL, 'radio', 'Security', 'true', 'AllowUserCourseSubscriptionByCourseAdminTitle', 'AllowUserCourseSubscriptionByCourseAdminComment', NULL, NULL, 1),
('show_link_bug_notification', NULL, 'radio', 'Platform', 'false', 'ShowLinkBugNotificationTitle', 'ShowLinkBugNotificationComment', NULL, NULL, 0),
('show_link_ticket_notification', NULL, 'radio', 'Platform', 'false', 'ShowLinkTicketNotificationTitle', 'ShowLinkTicketNotificationComment', NULL, NULL, 0),
('course_validation', NULL, 'radio', 'Platform', 'false', 'EnableCourseValidation', 'EnableCourseValidationComment', NULL, NULL, 1),
('course_validation_terms_and_conditions_url', NULL, 'textfield', 'Platform', '', 'CourseValidationTermsAndConditionsLink', 'CourseValidationTermsAndConditionsLinkComment', NULL, NULL, 1),
('sso_authentication',NULL,'radio','Security','false','EnableSSOTitle','EnableSSOComment',NULL,NULL,1),
('sso_authentication_domain',NULL,'textfield','Security','','SSOServerDomainTitle','SSOServerDomainComment',NULL,NULL,1),
('sso_authentication_auth_uri',NULL,'textfield','Security','/?q=user','SSOServerAuthURITitle','SSOServerAuthURIComment',NULL,NULL,1),
('sso_authentication_unauth_uri',NULL,'textfield','Security','/?q=logout','SSOServerUnAuthURITitle','SSOServerUnAuthURIComment',NULL,NULL,1),
('sso_authentication_protocol',NULL,'radio','Security','http://','SSOServerProtocolTitle','SSOServerProtocolComment',NULL,NULL,1),
('enabled_wiris',NULL,'radio','Editor','false','EnabledWirisTitle','EnabledWirisComment',NULL,NULL, 0),
('allow_spellcheck',NULL,'radio','Editor','false','AllowSpellCheckTitle','AllowSpellCheckComment',NULL,NULL, 0),
('force_wiki_paste_as_plain_text',NULL,'radio','Editor','false','ForceWikiPasteAsPlainTextTitle','ForceWikiPasteAsPlainTextComment',NULL,NULL, 0),
('enabled_googlemaps',NULL,'radio','Editor','false','EnabledGooglemapsTitle','EnabledGooglemapsComment',NULL,NULL, 0),
('enabled_imgmap',NULL,'radio','Editor','true','EnabledImageMapsTitle','EnabledImageMapsComment',NULL,NULL, 0),
('enabled_support_svg',				NULL,'radio',		'Tools',	'true',	'EnabledSVGTitle','EnabledSVGComment',NULL,NULL, 0),
('pdf_export_watermark_enable',		NULL,'radio',		'Platform',	'false','PDFExportWatermarkEnableTitle',	'PDFExportWatermarkEnableComment',	'platform',NULL, 1),
('pdf_export_watermark_by_course',	NULL,'radio',		'Platform',	'false','PDFExportWatermarkByCourseTitle',	'PDFExportWatermarkByCourseComment','platform',NULL, 1),
('pdf_export_watermark_text',		NULL,'textfield',	'Platform',	'',		'PDFExportWatermarkTextTitle',		'PDFExportWatermarkTextComment',	'platform',NULL, 1),
('enabled_insertHtml',				NULL,'radio',		'Editor',	'true','EnabledInsertHtmlTitle',			'EnabledInsertHtmlComment',NULL,NULL, 0),
('students_export2pdf',				NULL,'radio',		'Tools',	'true',	'EnabledStudentExport2PDFTitle',	'EnabledStudentExport2PDFComment',NULL,NULL, 0),
('exercise_min_score', 				NULL,'textfield',	'Course',	'',		'ExerciseMinScoreTitle',			'ExerciseMinScoreComment','platform',NULL, 	1),
('exercise_max_score', 				NULL,'textfield',	'Course',	'',		'ExerciseMaxScoreTitle',			'ExerciseMaxScoreComment','platform',NULL, 	1),
('show_users_folders',				NULL,'radio',		'Tools',	'true',	'ShowUsersFoldersTitle','ShowUsersFoldersComment',NULL,NULL, 0),
('show_default_folders',			NULL,'radio',		'Tools',	'true',	'ShowDefaultFoldersTitle','ShowDefaultFoldersComment',NULL,NULL, 0),
('show_chat_folder',				NULL,'radio',		'Tools',	'true',	'ShowChatFolderTitle','ShowChatFolderComment',NULL,NULL, 0),
('enabled_text2audio',				NULL,'radio',		'Tools',	'false',	'Text2AudioTitle','Text2AudioComment',NULL,NULL, 0),
('course_hide_tools','course_description','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'CourseDescription', 1),
('course_hide_tools','calendar_event','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Agenda', 1),
('course_hide_tools','document','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Documents', 1),
('course_hide_tools','learnpath','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'LearningPath', 1),
('course_hide_tools','link','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Links', 1),
('course_hide_tools','announcement','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Announcements', 1),
('course_hide_tools','forum','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Forums', 1),
('course_hide_tools','dropbox','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Dropbox', 1),
('course_hide_tools','quiz','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Quiz', 1),
('course_hide_tools','user','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Users', 1),
('course_hide_tools','group','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Groups', 1),
('course_hide_tools','chat','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Chat', 1),
('course_hide_tools','student_publication','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'StudentPublications', 1),
('course_hide_tools','wiki','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Wiki', 1),
('course_hide_tools','gradebook','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Gradebook', 1),
('course_hide_tools','survey','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Survey', 1),
('course_hide_tools','glossary','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Glossary', 1),
('course_hide_tools','notebook','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Notebook', 1),
('course_hide_tools','attendance','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Attendances', 1),
('course_hide_tools','course_progress','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'CourseProgress', 1),
('course_hide_tools','blog_management','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Blog',1),
('course_hide_tools','tracking','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Stats',1),
('course_hide_tools','course_maintenance','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'Maintenance',1),
('course_hide_tools','course_setting','checkbox','Tools','false','CourseHideToolsTitle','CourseHideToolsComment',NULL,'CourseSettings',1),
('enabled_support_pixlr',NULL,'radio','Tools','false','EnabledPixlrTitle','EnabledPixlrComment',NULL,NULL, 0),
('show_groups_to_users',NULL,'radio','Session','false','ShowGroupsToUsersTitle','ShowGroupsToUsersComment',NULL,NULL, 0),
('accessibility_font_resize',NULL,'radio','Platform','false','EnableAccessibilityFontResizeTitle','EnableAccessibilityFontResizeComment',NULL,NULL, 1),
('hide_courses_in_sessions',NULL,'radio', 'Session','false','HideCoursesInSessionsTitle',	'HideCoursesInSessionsComment','platform',NULL, 1),
('enable_quiz_scenario',  NULL,'radio','Course','true','EnableQuizScenarioTitle','EnableQuizScenarioComment',NULL,NULL, 1),
('filter_terms',NULL,'textarea','Security','','FilterTermsTitle','FilterTermsComment',NULL,NULL, 0),
('header_extra_content', NULL, 'textarea', 'Tracking', '', 'HeaderExtraContentTitle', 'HeaderExtraContentComment', NULL, NULL, 1),
('footer_extra_content', NULL, 'textarea', 'Tracking', '', 'FooterExtraContentTitle', 'FooterExtraContentComment', NULL, NULL, 1),
('show_documents_preview', NULL, 'radio', 'Tools', 'false', 'ShowDocumentPreviewTitle', 'ShowDocumentPreviewComment', NULL, NULL, 1),
('htmlpurifier_wiki', NULL, 'radio', 'Editor', 'false', 'HtmlPurifierWikiTitle', 'HtmlPurifierWikiComment', NULL, NULL, 0),
('cas_activate', NULL, 'radio', 'CAS', 'false', 'CasMainActivateTitle', 'CasMainActivateComment', NULL, NULL, 0),
('cas_server', NULL, 'textfield', 'CAS', '', 'CasMainServerTitle', 'CasMainServerComment', NULL, NULL, 0),
('cas_server_uri', NULL, 'textfield', 'CAS', '', 'CasMainServerURITitle', 'CasMainServerURIComment', NULL, NULL, 0),
('cas_port', NULL, 'textfield', 'CAS', '', 'CasMainPortTitle', 'CasMainPortComment', NULL, NULL, 0),
('cas_protocol', NULL, 'radio', 'CAS', '', 'CasMainProtocolTitle', 'CasMainProtocolComment', NULL, NULL, 0),
('cas_add_user_activate', NULL, 'radio', 'CAS', 'false', 'CasUserAddActivateTitle', 'CasUserAddActivateComment', NULL, NULL, 0),
('update_user_info_cas_with_ldap', NULL, 'radio', 'CAS', 'true', 'UpdateUserInfoCasWithLdapTitle', 'UpdateUserInfoCasWithLdapComment', NULL, NULL, 0),
('student_page_after_login', NULL, 'textfield', 'Platform', '', 'StudentPageAfterLoginTitle', 'StudentPageAfterLoginComment', NULL, NULL, 0),
('teacher_page_after_login', NULL, 'textfield', 'Platform', '', 'TeacherPageAfterLoginTitle', 'TeacherPageAfterLoginComment', NULL, NULL, 0),
('drh_page_after_login', NULL, 'textfield', 'Platform', '', 'DRHPageAfterLoginTitle', 'DRHPageAfterLoginComment', NULL, NULL, 0),
('sessionadmin_page_after_login', NULL, 'textfield', 'Session', '', 'SessionAdminPageAfterLoginTitle', 'SessionAdminPageAfterLoginComment', NULL, NULL, 0),
('student_autosubscribe', NULL, 'textfield', 'Platform', '', 'StudentAutosubscribeTitle', 'StudentAutosubscribeComment', NULL, NULL, 0),
('teacher_autosubscribe', NULL, 'textfield', 'Platform', '', 'TeacherAutosubscribeTitle', 'TeacherAutosubscribeComment', NULL, NULL, 0),
('drh_autosubscribe', NULL, 'textfield', 'Platform', '', 'DRHAutosubscribeTitle', 'DRHAutosubscribeComment', NULL, NULL, 0),
('sessionadmin_autosubscribe', NULL, 'textfield', 'Session', '', 'SessionadminAutosubscribeTitle', 'SessionadminAutosubscribeComment', NULL, NULL, 0),
('scorm_cumulative_session_time', NULL, 'radio', 'Course', 'true', 'ScormCumulativeSessionTimeTitle', 'ScormCumulativeSessionTimeComment', NULL, NULL, 0),
('allow_hr_skills_management', NULL, 'radio', 'Gradebook', 'true', 'AllowHRSkillsManagementTitle', 'AllowHRSkillsManagementComment', NULL, NULL, 1),
('enable_help_link', NULL, 'radio', 'Platform', 'true', 'EnableHelpLinkTitle', 'EnableHelpLinkComment', NULL, NULL, 0),
('teachers_can_change_score_settings', NULL, 'radio', 'Gradebook', 'true', 'TeachersCanChangeScoreSettingsTitle', 'TeachersCanChangeScoreSettingsComment', NULL, NULL, 1),
('allow_users_to_change_email_with_no_password', NULL, 'radio', 'User', 'false', 'AllowUsersToChangeEmailWithNoPasswordTitle', 'AllowUsersToChangeEmailWithNoPasswordComment', NULL, NULL, 0),
('show_admin_toolbar', NULL, 'radio', 'Platform', 'do_not_show', 'ShowAdminToolbarTitle', 'ShowAdminToolbarComment', NULL, NULL, 1),
('allow_global_chat', NULL, 'radio', 'Platform', 'true', 'AllowGlobalChatTitle', 'AllowGlobalChatComment', NULL, NULL, 1),
('languagePriority1', NULL, 'radio', 'Languages', 'course_lang', 'LanguagePriority1Title', 'LanguagePriority1Comment', NULL, NULL, 0),
('languagePriority2', NULL, 'radio', 'Languages','user_profil_lang', 'LanguagePriority2Title', 'LanguagePriority2Comment', NULL, NULL, 0),
('languagePriority3', NULL, 'radio', 'Languages','user_selected_lang', 'LanguagePriority3Title', 'LanguagePriority3Comment', NULL, NULL, 0),
('languagePriority4', NULL, 'radio', 'Languages', 'platform_lang','LanguagePriority4Title', 'LanguagePriority4Comment', NULL, NULL, 0),
('login_is_email', NULL, 'radio', 'Platform', 'false', 'LoginIsEmailTitle', 'LoginIsEmailComment', NULL, NULL, 0),
('courses_default_creation_visibility', NULL, 'radio', 'Course', '2', 'CoursesDefaultCreationVisibilityTitle', 'CoursesDefaultCreationVisibilityComment', NULL, NULL, 1),
('gradebook_enable_grade_model', NULL, 'radio', 'Gradebook', 'false', 'GradebookEnableGradeModelTitle', 'GradebookEnableGradeModelComment', NULL, NULL, 1),
('teachers_can_change_grade_model_settings', NULL, 'radio', 'Gradebook', 'true', 'TeachersCanChangeGradeModelSettingsTitle', 'TeachersCanChangeGradeModelSettingsComment', NULL, NULL, 1),
('gradebook_default_weight', NULL, 'textfield', 'Gradebook', '100', 'GradebookDefaultWeightTitle', 'GradebookDefaultWeightComment', NULL, NULL, 0),
('ldap_description', NULL, 'radio', 'LDAP', NULL, 'LdapDescriptionTitle', 'LdapDescriptionComment', NULL, NULL, 0),
('shibboleth_description', NULL, 'radio', 'Shibboleth', 'false', 'ShibbolethMainActivateTitle', 'ShibbolethMainActivateComment', NULL, NULL, 0),
('facebook_description', NULL, 'radio', 'Facebook', 'false', 'FacebookMainActivateTitle', 'FacebookMainActivateComment', NULL, NULL, 0),
('gradebook_locking_enabled', NULL, 'radio', 'Gradebook', 'false', 'GradebookEnableLockingTitle', 'GradebookEnableLockingComment', NULL, NULL, 0),
('gradebook_default_grade_model_id', NULL, 'select', 'Gradebook', '', 'GradebookDefaultGradeModelTitle', 'GradebookDefaultGradeModelComment', NULL, NULL, 1),
('allow_session_admins_to_manage_all_sessions', NULL, 'radio', 'Session', 'false', 'AllowSessionAdminsToSeeAllSessionsTitle', 'AllowSessionAdminsToSeeAllSessionsComment', NULL, NULL, 1),
('allow_skills_tool', NULL, 'radio', 'Platform', 'true', 'AllowSkillsToolTitle', 'AllowSkillsToolComment', NULL, NULL, 1),
('allow_public_certificates', NULL, 'radio', 'Course', 'false', 'AllowPublicCertificatesTitle', 'AllowPublicCertificatesComment', NULL, NULL, 1),
('platform_unsubscribe_allowed', NULL, 'radio', 'Platform', 'false', 'PlatformUnsubscribeTitle', 'PlatformUnsubscribeComment', NULL, NULL, 1),
('activate_email_template', NULL, 'radio', 'Platform', 'false', 'ActivateEmailTemplateTitle', 'ActivateEmailTemplateComment', NULL, NULL, 0),
('enable_iframe_inclusion', NULL, 'radio', 'Editor', 'false', 'EnableIframeInclusionTitle', 'EnableIframeInclusionComment', NULL, NULL, 1),
('show_hot_courses', NULL, 'radio', 'Platform', 'true', 'ShowHotCoursesTitle', 'ShowHotCoursesComment', NULL, NULL, 1),
('enable_webcam_clip',NULL,'radio','Tools','false','EnableWebCamClipTitle','EnableWebCamClipComment',NULL,NULL, 0),
('use_custom_pages', NULL, 'radio','Platform','false','UseCustomPagesTitle','UseCustomPagesComment', NULL, NULL, 1),
('tool_visible_by_default_at_creation','documents','checkbox','Tools','true','ToolVisibleByDefaultAtCreationTitle','ToolVisibleByDefaultAtCreationComment',NULL,'Documents', 1),
('tool_visible_by_default_at_creation','learning_path','checkbox','Tools','true','ToolVisibleByDefaultAtCreationTitle','ToolVisibleByDefaultAtCreationComment',NULL,'LearningPath', 1),
('tool_visible_by_default_at_creation','links','checkbox','Tools','true','ToolVisibleByDefaultAtCreationTitle','ToolVisibleByDefaultAtCreationComment',NULL,'Links', 1),
('tool_visible_by_default_at_creation','announcements','checkbox','Tools','true','ToolVisibleByDefaultAtCreationTitle','ToolVisibleByDefaultAtCreationComment',NULL,'Announcements', 1),
('tool_visible_by_default_at_creation','forums','checkbox','Tools','true','ToolVisibleByDefaultAtCreationTitle','ToolVisibleByDefaultAtCreationComment',NULL,'Forums', 1),
('tool_visible_by_default_at_creation','quiz','checkbox','Tools','true','ToolVisibleByDefaultAtCreationTitle','ToolVisibleByDefaultAtCreationComment',NULL,'Quiz', 1),
('tool_visible_by_default_at_creation','gradebook','checkbox','Tools','true','ToolVisibleByDefaultAtCreationTitle','ToolVisibleByDefaultAtCreationComment',NULL,'Gradebook', 1),
('prevent_session_admins_to_manage_all_users', NULL, 'radio', 'Session', 'false', 'PreventSessionAdminsToManageAllUsersTitle', 'PreventSessionAdminsToManageAllUsersComment', NULL, NULL, 1),
('documents_default_visibility_defined_in_course', NULL,'radio','Tools','false','DocumentsDefaultVisibilityDefinedInCourseTitle','DocumentsDefaultVisibilityDefinedInCourseComment',NULL, NULL, 1),
('enabled_mathjax', NULL, 'radio', 'Editor', 'false', 'EnableMathJaxTitle', 'EnableMathJaxComment', NULL, NULL, 0),
('meta_twitter_site', NULL, 'textfield', 'Tracking', '', 'MetaTwitterSiteTitle', 'MetaTwitterSiteComment', NULL, NULL, 1),
('meta_twitter_creator', NULL, 'textfield', 'Tracking', '', 'MetaTwitterCreatorTitle', 'MetaTwitterCreatorComment', NULL, NULL, 1),
('meta_title', NULL, 'textfield', 'Tracking', '', 'MetaTitleTitle', 'MetaTitleComment', NULL, NULL, 1),
('meta_description', NULL, 'textfield', 'Tracking', '', 'MetaDescriptionTitle', 'MetaDescriptionComment', NULL, NULL, 1),
('meta_image_path', NULL, 'textfield', 'Tracking', '', 'MetaImagePathTitle', 'MetaImagePathComment', NULL, NULL, 1),
('allow_teachers_to_create_sessions', NULL, 'radio', 'Session', 'false', 'AllowTeachersToCreateSessionsTitle', 'AllowTeachersToCreateSessionsComment', NULL, NULL, 0),
('institution_address',NULL,'textfield','Platform','','InstitutionAddressTitle','InstitutionAddressComment',NULL,NULL, 1),
('chamilo_database_version', NULL, 'textfield', NULL, '0', 'DatabaseVersion', '', NULL, NULL, 0),
('cron_remind_course_finished_activate', NULL, 'radio', 'Crons', 'false', 'CronRemindCourseFinishedActivateTitle', 'CronRemindCourseFinishedActivateComment', NULL, NULL, 1),
('cron_remind_course_expiration_frequency', NULL, 'textfield', 'Crons', '2', 'CronRemindCourseExpirationFrequencyTitle', 'CronRemindCourseExpirationFrequencyComment', NULL, NULL, 1),
('cron_remind_course_expiration_activate', NULL, 'radio', 'Crons', 'false', 'CronRemindCourseExpirationActivateTitle', 'CronRemindCourseExpirationActivateComment', NULL, NULL, 1),
('allow_coach_feedback_exercises',NULL,'radio','Session','true','AllowCoachFeedbackExercisesTitle','AllowCoachFeedbackExercisesComment',NULL,NULL, 0),
('allow_my_files',NULL,'radio','Platform','true','AllowMyFilesTitle','AllowMyFilesComment','',NULL, 1),
('ticket_allow_student_add', NULL, 'radio','Ticket', 'false','TicketAllowStudentAddTitle','TicketAllowStudentAddComment',NULL,NULL, 0),
('ticket_send_warning_to_all_admins', NULL, 'radio','Ticket', 'false','TicketSendWarningToAllAdminsTitle','TicketSendWarningToAllAdminsComment',NULL,NULL, 0),
('ticket_warn_admin_no_user_in_category', NULL, 'radio','Ticket', 'false','TicketWarnAdminNoUserInCategoryTitle','TicketWarnAdminNoUserInCategoryComment',NULL,NULL, 0),
('ticket_allow_category_edition', NULL, 'radio','Ticket', 'false','TicketAllowCategoryEditionTitle','TicketAllowCategoryEditionComment',NULL,NULL, 0),
('load_term_conditions_section', NULL, 'radio','Platform', 'login','LoadTermConditionsSectionTitle','LoadTermConditionsSectionDescription',NULL,NULL, 0),
('show_terms_if_profile_completed', NULL, 'radio','Ticket', 'false','ShowTermsIfProfileCompletedTitle','ShowTermsIfProfileCompletedComment',NULL,NULL, 0);

INSERT INTO settings_options (variable, value, display_text)
VALUES
('show_administrator_data','true','Yes'),
('show_administrator_data','false','No'),
('show_tutor_data','true','Yes'),
('show_tutor_data','false','No'),
('show_teacher_data','true','Yes'),
('show_teacher_data','false','No'),
('homepage_view','activity','HomepageViewActivity'),
('homepage_view','2column','HomepageView2column'),
('homepage_view','3column','HomepageView3column'),
('homepage_view','vertical_activity','HomepageViewVerticalActivity'),
('homepage_view','activity_big','HomepageViewActivityBig'),
('show_toolshortcuts','true','Yes'),
('show_toolshortcuts','false','No'),
('allow_group_categories','true','Yes'),
('allow_group_categories','false','No'),
('server_type','production','ProductionServer'),
('server_type','test','TestServer'),
('allow_name_change','true','Yes'),
('allow_name_change','false','No'),
('allow_officialcode_change','true','Yes'),
('allow_officialcode_change','false','No'),
('allow_registration','true','Yes'),
('allow_registration','false','No'),
('allow_registration','approval','AfterApproval'),
('allow_registration_as_teacher','true','Yes'),
('allow_registration_as_teacher','false','No'),
('allow_lostpassword','true','Yes'),
('allow_lostpassword','false','No'),
('allow_user_headings','true','Yes'),
('allow_user_headings','false','No'),
('allow_personal_agenda','true','Yes'),
('allow_personal_agenda','false','No'),
('display_coursecode_in_courselist','true','Yes'),
('display_coursecode_in_courselist','false','No'),
('display_teacher_in_courselist','true','Yes'),
('display_teacher_in_courselist','false','No'),
('permanently_remove_deleted_files','true','YesWillDeletePermanently'),
('permanently_remove_deleted_files','false','NoWillDeletePermanently'),
('dropbox_allow_overwrite','true','Yes'),
('dropbox_allow_overwrite','false','No'),
('dropbox_allow_just_upload','true','Yes'),
('dropbox_allow_just_upload','false','No'),
('dropbox_allow_student_to_student','true','Yes'),
('dropbox_allow_student_to_student','false','No'),
('dropbox_allow_group','true','Yes'),
('dropbox_allow_group','false','No'),
('dropbox_allow_mailing','true','Yes'),
('dropbox_allow_mailing','false','No'),
('extended_profile','true','Yes'),
('extended_profile','false','No'),
('student_view_enabled','true','Yes'),
('student_view_enabled','false','No'),
('show_navigation_menu','false','No'),
('show_navigation_menu','icons','IconsOnly'),
('show_navigation_menu','text','TextOnly'),
('show_navigation_menu','iconstext','IconsText'),
('enable_tool_introduction','true','Yes'),
('enable_tool_introduction','false','No'),
('page_after_login', 'index.php', 'CampusHomepage'),
('page_after_login', 'user_portal.php', 'MyCourses'),
('page_after_login', 'main/auth/courses.php', 'CourseCatalog'),
('breadcrumbs_course_homepage', 'get_lang', 'CourseHomepage'),
('breadcrumbs_course_homepage', 'course_code', 'CourseCode'),
('breadcrumbs_course_homepage', 'course_title', 'CourseTitle'),
('example_material_course_creation', 'true', 'Yes'),
('example_material_course_creation', 'false', 'No'),
('use_session_mode', 'true', 'Yes'),
('use_session_mode', 'false', 'No'),
('allow_email_editor', 'true' ,'Yes'),
('allow_email_editor', 'false', 'No'),
('show_email_addresses','true','Yes'),
('show_email_addresses','false','No'),
('upload_extensions_list_type', 'blacklist', 'Blacklist'),
('upload_extensions_list_type', 'whitelist', 'Whitelist'),
('upload_extensions_skip', 'true', 'Remove'),
('upload_extensions_skip', 'false', 'Rename'),
('show_number_of_courses', 'true', 'Yes'),
('show_number_of_courses', 'false', 'No'),
('show_empty_course_categories', 'true', 'Yes'),
('show_empty_course_categories', 'false', 'No'),
('show_back_link_on_top_of_tree', 'true', 'Yes'),
('show_back_link_on_top_of_tree', 'false', 'No'),
('show_different_course_language', 'true', 'Yes'),
('show_different_course_language', 'false', 'No'),
('split_users_upload_directory', 'true', 'Yes'),
('split_users_upload_directory', 'false', 'No'),
('hide_dltt_markup', 'false', 'No'),
('hide_dltt_markup', 'true', 'Yes'),
('display_categories_on_homepage','true','Yes'),
('display_categories_on_homepage','false','No'),
('default_forum_view', 'flat', 'Flat'),
('default_forum_view', 'threaded', 'Threaded'),
('default_forum_view', 'nested', 'Nested'),
('survey_email_sender_noreply', 'coach', 'CourseCoachEmailSender'),
('survey_email_sender_noreply', 'noreply', 'NoReplyEmailSender'),
('openid_authentication','true','Yes'),
('openid_authentication','false','No'),
('gradebook_enable','true','Yes'),
('gradebook_enable','false','No'),
('user_selected_theme','true','Yes'),
('user_selected_theme','false','No'),
('allow_course_theme','true','Yes'),
('allow_course_theme','false','No'),
('show_closed_courses', 'true', 'Yes'),
('show_closed_courses', 'false', 'No'),
('ldap_version', '2', 'LDAPVersion2'),
('ldap_version', '3', 'LDAPVersion3'),
('add_users_by_coach', 'true', 'Yes'),
('add_users_by_coach', 'false', 'No'),
('extend_rights_for_coach', 'true', 'Yes'),
('extend_rights_for_coach', 'false', 'No'),
('extend_rights_for_coach_on_survey', 'true', 'Yes'),
('extend_rights_for_coach_on_survey', 'false', 'No'),
('show_session_coach', 'true', 'Yes'),
('show_session_coach', 'false', 'No'),
('allow_users_to_create_courses','true','Yes'),
('allow_users_to_create_courses','false','No'),
('breadcrumbs_course_homepage', 'session_name_and_course_title', 'SessionNameAndCourseTitle'),
('allow_message_tool', 'true', 'Yes'),
('allow_message_tool', 'false', 'No'),
('allow_social_tool', 'true', 'Yes'),
('allow_social_tool', 'false', 'No'),
('allow_students_to_browse_courses','true','Yes'),
('allow_students_to_browse_courses','false','No'),
('show_email_of_teacher_or_tutor ', 'true', 'Yes'),
('show_email_of_teacher_or_tutor ', 'false', 'No'),
('show_session_data ', 'true', 'Yes'),
('show_session_data ', 'false', 'No'),
('allow_use_sub_language', 'true', 'Yes'),
('allow_use_sub_language', 'false', 'No'),
('show_glossary_in_documents', 'none', 'ShowGlossaryInDocumentsIsNone'),
('show_glossary_in_documents', 'ismanual', 'ShowGlossaryInDocumentsIsManual'),
('show_glossary_in_documents', 'isautomatic', 'ShowGlossaryInDocumentsIsAutomatic'),
('allow_terms_conditions', 'true', 'Yes'),
('allow_terms_conditions', 'false', 'No'),
('search_enabled', 'true', 'Yes'),
('search_enabled', 'false', 'No'),
('search_show_unlinked_results', 'true', 'SearchShowUnlinkedResults'),
('search_show_unlinked_results', 'false', 'SearchHideUnlinkedResults'),
('show_courses_descriptions_in_catalog', 'true', 'Yes'),
('show_courses_descriptions_in_catalog', 'false', 'No'),
('allow_coach_to_edit_course_session','true','Yes'),
('allow_coach_to_edit_course_session','false','No'),
('show_glossary_in_extra_tools', 'none', 'None'),
('show_glossary_in_extra_tools', 'exercise', 'Exercise'),
('show_glossary_in_extra_tools', 'lp', 'LearningPaths'),
('show_glossary_in_extra_tools', 'exercise_and_lp', 'ExerciseAndLearningPath'),
('send_email_to_admin_when_create_course','true','Yes'),
('send_email_to_admin_when_create_course','false','No'),
('go_to_course_after_login','true','Yes'),
('go_to_course_after_login','false','No'),
('math_asciimathML','true','Yes'),
('math_asciimathML','false','No'),
('enabled_asciisvg','true','Yes'),
('enabled_asciisvg','false','No'),
('include_asciimathml_script','true','Yes'),
('include_asciimathml_script','false','No'),
('youtube_for_students','true','Yes'),
('youtube_for_students','false','No'),
('block_copy_paste_for_students','true','Yes'),
('block_copy_paste_for_students','false','No'),
('more_buttons_maximized_mode','true','Yes'),
('more_buttons_maximized_mode','false','No'),
('students_download_folders','true','Yes'),
('students_download_folders','false','No'),
('users_copy_files','true','Yes'),
('users_copy_files','false','No'),
('allow_students_to_create_groups_in_social','true','Yes'),
('allow_students_to_create_groups_in_social','false','No'),
('allow_send_message_to_all_platform_users','true','Yes'),
('allow_send_message_to_all_platform_users','false','No'),
('use_users_timezone', 'true', 'Yes'),
('use_users_timezone', 'false', 'No'),
('allow_user_course_subscription_by_course_admin', 'true', 'Yes'),
('allow_user_course_subscription_by_course_admin', 'false', 'No'),
('show_link_bug_notification', 'true', 'Yes'),
('show_link_bug_notification', 'false', 'No'),
('show_link_ticket_notification', 'true', 'Yes'),
('show_link_ticket_notification', 'false', 'No'),
('course_validation', 'true', 'Yes'),
('course_validation', 'false', 'No'),
('sso_authentication', 'true', 'Yes'),
('sso_authentication', 'false', 'No'),
('sso_authentication_protocol', 'http://', 'http://'),
('sso_authentication_protocol', 'https://', 'https://'),
('enabled_wiris','true','Yes'),
('enabled_wiris','false','No'),
('allow_spellcheck','true','Yes'),
('allow_spellcheck','false','No'),
('force_wiki_paste_as_plain_text','true','Yes'),
('force_wiki_paste_as_plain_text','false','No'),
('enabled_googlemaps','true','Yes'),
('enabled_googlemaps','false','No'),
('enabled_imgmap','true','Yes'),
('enabled_imgmap','false','No'),
('enabled_support_svg','true','Yes'),
('enabled_support_svg','false','No'),
('pdf_export_watermark_enable','true','Yes'),
('pdf_export_watermark_enable','false','No'),
('pdf_export_watermark_by_course','true','Yes'),
('pdf_export_watermark_by_course','false','No'),
('enabled_insertHtml','true','Yes'),
('enabled_insertHtml','false','No'),
('students_export2pdf','true','Yes'),
('students_export2pdf','false','No'),
('show_users_folders','true','Yes'),
('show_users_folders','false','No'),
('show_default_folders','true','Yes'),
('show_default_folders','false','No'),
('show_chat_folder','true','Yes'),
('show_chat_folder','false','No'),
('enabled_text2audio','true','Yes'),
('enabled_text2audio','false','No'),
('enabled_support_pixlr','true','Yes'),
('enabled_support_pixlr','false','No'),
('show_groups_to_users','true','Yes'),
('show_groups_to_users','false','No'),
('accessibility_font_resize', 'true', 'Yes'),
('accessibility_font_resize', 'false', 'No'),
('hide_courses_in_sessions','true','Yes'),
('hide_courses_in_sessions','false','No'),
('enable_quiz_scenario', 'true', 'Yes'),
('enable_quiz_scenario', 'false', 'No'),
('show_documents_preview', 'true', 'Yes'),
('show_documents_preview', 'false', 'No'),
('htmlpurifier_wiki', 'true', 'Yes'),
('htmlpurifier_wiki', 'false', 'No'),
('cas_activate', 'true', 'Yes'),
('cas_activate', 'false', 'No'),
('cas_protocol', 'CAS1', 'CAS1Text'),
('cas_protocol', 'CAS2', 'CAS2Text'),
('cas_protocol', 'CAS3', 'CAS3Text'),
('cas_protocol', 'SAML', 'SAMLText'),
('cas_add_user_activate', 'false', 'No'),
('cas_add_user_activate', 'platform', 'casAddUserActivatePlatform'),
('cas_add_user_activate', 'extldap', 'casAddUserActivateLDAP'),
('update_user_info_cas_with_ldap', 'true', 'Yes'),
('update_user_info_cas_with_ldap', 'false', 'No'),
('scorm_cumulative_session_time','true','Yes'),
('scorm_cumulative_session_time','false','No'),
('allow_hr_skills_management', 'true', 'Yes'),
('allow_hr_skills_management', 'false', 'No'),
('enable_help_link', 'true', 'Yes'),
('enable_help_link', 'false', 'No'),
('allow_users_to_change_email_with_no_password', 'true', 'Yes'),
('allow_users_to_change_email_with_no_password', 'false', 'No'),
('show_admin_toolbar', 'do_not_show', 'DoNotShow'),
('show_admin_toolbar', 'show_to_admin', 'ShowToAdminsOnly'),
('show_admin_toolbar', 'show_to_admin_and_teachers', 'ShowToAdminsAndTeachers'),
('show_admin_toolbar', 'show_to_all', 'ShowToAllUsers'),
('use_custom_pages','true','Yes'),
('use_custom_pages','false','No'),
('languagePriority1','platform_lang','PlatformLanguage'),
('languagePriority1','user_profil_lang','UserLanguage'),
('languagePriority1','user_selected_lang','UserSelectedLanguage'),
('languagePriority1','course_lang','CourseLanguage'),
('languagePriority2','platform_lang','PlatformLanguage'),
('languagePriority2','user_profil_lang','UserLanguage'),
('languagePriority2','user_selected_lang','UserSelectedLanguage'),
('languagePriority2','course_lang','CourseLanguage'),
('languagePriority3','platform_lang','PlatformLanguage'),
('languagePriority3','user_profil_lang','UserLanguage'),
('languagePriority3','user_selected_lang','UserSelectedLanguage'),
('languagePriority3','course_lang','CourseLanguage'),
('languagePriority4','platform_lang','PlatformLanguage'),
('languagePriority4','user_profil_lang','UserLanguage'),
('languagePriority4','user_selected_lang','UserSelectedLanguage'),
('languagePriority4','course_lang','CourseLanguage'),
('allow_global_chat', 'true', 'Yes'),
('allow_global_chat', 'false', 'No'),
('login_is_email','true','Yes'),
('login_is_email','false','No'),
('courses_default_creation_visibility', '3', 'OpenToTheWorld'),
('courses_default_creation_visibility', '2', 'OpenToThePlatform'),
('courses_default_creation_visibility', '1', 'Private'),
('courses_default_creation_visibility', '0', 'CourseVisibilityClosed'),
('teachers_can_change_score_settings', 'true', 'Yes'),
('teachers_can_change_score_settings', 'false', 'No'),
('teachers_can_change_grade_model_settings', 'true', 'Yes'),
('teachers_can_change_grade_model_settings', 'false', 'No'),
('gradebook_locking_enabled', 'true', 'Yes'),
('gradebook_locking_enabled', 'false', 'No'),
('gradebook_enable_grade_model', 'true', 'Yes'),
('gradebook_enable_grade_model', 'false', 'No'),
('allow_session_admins_to_manage_all_sessions', 'true', 'Yes'),
('allow_session_admins_to_manage_all_sessions', 'false', 'No'),
('allow_skills_tool', 'true', 'Yes'),
('allow_skills_tool', 'false', 'No'),
('allow_public_certificates', 'true', 'Yes'),
('allow_public_certificates', 'false', 'No'),
('platform_unsubscribe_allowed', 'true', 'Yes'),
('platform_unsubscribe_allowed', 'false', 'No'),
('activate_email_template', 'true', 'Yes'),
('activate_email_template', 'false', 'No'),
('enable_iframe_inclusion', 'true', 'Yes'),
('enable_iframe_inclusion', 'false', 'No'),
('show_hot_courses', 'true', 'Yes'),
('show_hot_courses', 'false', 'No'),
('enable_webcam_clip', 'true', 'Yes'),
('enable_webcam_clip', 'false', 'No'),
('prevent_session_admins_to_manage_all_users', 'true', 'Yes'),
('prevent_session_admins_to_manage_all_users', 'false', 'No'),
('documents_default_visibility_defined_in_course', 'true', 'Yes'),
('documents_default_visibility_defined_in_course', 'false', 'No'),
('enabled_mathjax','true','Yes'),
('enabled_mathjax','false','No'),
('allow_teachers_to_create_sessions', 'true', 'Yes'),
('allow_teachers_to_create_sessions', 'false', 'No'),
('cron_remind_course_finished_activate', 'false', 'No'),
('cron_remind_course_finished_activate', 'true', 'Yes'),
('cron_remind_course_expiration_activate', 'false', 'No'),
('cron_remind_course_expiration_activate', 'true', 'Yes'),
('allow_coach_feedback_exercises','true','Yes'),
('allow_coach_feedback_exercises','false','No'),
('allow_my_files','true','Yes'),
('allow_my_files','false','No'),
('ticket_allow_student_add','true','Yes'),
('ticket_allow_student_add','false','No'),
('ticket_allow_category_edition', 'true', 'Yes'),
('ticket_allow_category_edition', 'false', 'No'),
('ticket_send_warning_to_all_admins', 'true', 'Yes'),
('ticket_send_warning_to_all_admins', 'false', 'No'),
('ticket_warn_admin_no_user_in_category', 'true', 'Yes'),
('ticket_warn_admin_no_user_in_category', 'false', 'No'),
('load_term_conditions_section', 'login', 'Login'),
('load_term_conditions_section', 'course', 'Course'),
('show_terms_if_profile_completed', 'true', 'Yes'),
('show_terms_if_profile_completed', 'false', 'No');

INSERT INTO language (original_name, english_name, isocode, dokeos_folder, available) VALUES
('&#1575;&#1604;&#1593;&#1585;&#1576;&#1610;&#1577;','arabic','ar','arabic',0),
('Asturianu','asturian','ast','asturian',0),
('Euskara','basque','eu','basque',1),
('&#2476;&#2494;&#2434;&#2482;&#2494;','bengali','bn','bengali',0),
('Bosanski','bosnian','bs','bosnian',1),
('Portugu&ecirc;s do Brasil','brazilian','pt-BR','brazilian',1),
('&#1041;&#1098;&#1083;&#1075;&#1072;&#1088;&#1089;&#1082;&#1080;','bulgarian','bg','bulgarian',1),
('Catal&agrave;','catalan','ca','catalan',0),
('Hrvatski','croatian','hr','croatian',0),
('&#268;esky','czech','cs','czech',0),
('Dansk','danish','da','danish',0),
('&#1583;&#1585;&#1740;','dari','prs','dari',0),
('Nederlands','dutch','nl','dutch',1),
('English','english','en','english',1),
('Esperanto','esperanto','eo','esperanto',0),
('Føroyskt', 'faroese', 'fo', 'faroese', 0),
('Suomi','finnish','fi','finnish',0),
('Fran&ccedil;ais','french','fr','french',1),
('Furlan','friulian','fur','friulian',0),
('Galego','galician','gl','galician',1),
('&#4325;&#4304;&#4320;&#4311;&#4323;&#4314;&#4312;','georgian','ka','georgian',0),
('Deutsch','german','de','german',1),
('&Epsilon;&lambda;&lambda;&eta;&nu;&iota;&kappa;&#940;','greek','el','greek',1),
('&#1506;&#1489;&#1512;&#1497;&#1514;','hebrew','he','hebrew',0),
('&#2361;&#2367;&#2344;&#2381;&#2342;&#2368;','hindi','hi','hindi',0),
('Magyar','hungarian','hu','hungarian',1),
('Bahasa Indonesia','indonesian','id','indonesian',1),
('Italiano','italian','it','italian',1),
('&#26085;&#26412;&#35486;','japanese','ja','japanese',0),
('&#54620;&#44397;&#50612;','korean','ko','korean',0),
('Latvie&scaron;u','latvian','lv','latvian',1),
('Lietuvi&#371;','lithuanian','lt','lithuanian',0),
('&#1052;&#1072;&#1082;&#1077;&#1076;&#1086;&#1085;&#1089;&#1082;&#1080;','macedonian','mk','macedonian',0),
('Bahasa Melayu','malay','ms','malay',0),
('Norsk','norwegian','no','norwegian',0),
('Occitan','occitan','oc','occitan',0),
('&#1662;&#1690;&#1578;&#1608;','pashto','ps','pashto',0),
('&#1601;&#1575;&#1585;&#1587;&#1740;','persian','fa','persian',0),
('Polski','polish','pl','polish',1),
('Portugu&ecirc;s europeu','portuguese','pt','portuguese',1),
('Runasimi','quechua_cusco','qu','quechua_cusco',0),
('Rom&acirc;n&#259;','romanian','ro','romanian',0),
('&#1056;&#1091;&#1089;&#1089;&#1082;&#1080;&#1081;','russian','ru','russian',0),
('Srpski','serbian','sr','serbian',0),
('&#20013;&#25991;&#65288;&#31616;&#20307;&#65289;','simpl_chinese','zh','simpl_chinese',0),
('Sloven&#269;ina','slovak','sk','slovak',1),
('Sloven&scaron;&#269;ina','slovenian','sl','slovenian',1),
('&#1575;&#1604;&#1589;&#1608;&#1605;&#1575;&#1604;&#1610;&#1577;','somali','so','somali',0),
('Espa&ntilde;ol','spanish','es','spanish',1),
('Kiswahili','swahili','sw','swahili',0),
('Svenska','swedish','sv','swedish',0),
('Tagalog', 'tagalog', 'tl', 'tagalog',1),
('&#3652;&#3607;&#3618;','thai','th','thai',0),
('Tibetan', 'tibetan', 'bo', 'tibetan', 0),
('&#32321;&#39636;&#20013;&#25991;','trad_chinese','zh-TW','trad_chinese',0),
('T&uuml;rk&ccedil;e','turkish','tr','turkish',0),
('&#1059;&#1082;&#1088;&#1072;&#1111;&#1085;&#1089;&#1100;&#1082;&#1072;','ukrainian','uk','ukrainian',0),
('Ti&#7871;ng Vi&#7879;t','vietnamese','vi','vietnamese',0),
('isiXhosa', 'xhosa', 'xh', 'xhosa', 0),
('Yor&ugrave;b&aacute;','yoruba','yo','yoruba',0);

-- Set parent language to Spanish for all close-by languages, same for Italian, French, Portuguese and Chinese
UPDATE language SET parent_id = 49 WHERE english_name = 'quechua_cusco';
UPDATE language SET parent_id = 49 WHERE english_name = 'galician';
UPDATE language SET parent_id = 49 WHERE english_name = 'esperanto';
UPDATE language SET parent_id = 49 WHERE english_name = 'catalan';
UPDATE language SET parent_id = 49 WHERE english_name = 'asturian';
UPDATE language SET parent_id = 28 WHERE english_name = 'friulian';
UPDATE language SET parent_id = 18 WHERE english_name = 'occitan';
UPDATE language SET parent_id = 40 WHERE english_name = 'brazilian';
UPDATE language SET parent_id = 45 WHERE english_name = 'trad_chinese';

INSERT INTO course_category VALUES (1,'Language skills','LANG',NULL,1,0,'TRUE','TRUE'),(2,'PC Skills','PC',NULL,2,0,'TRUE','TRUE'),(3,'Projects','PROJ',NULL,3,0,'TRUE','TRUE');

INSERT INTO course_module VALUES
(1,'calendar_event','calendar/agenda.php','agenda.gif',1,1,'basic'),
(2,'link','link/link.php','links.gif',4,1,'basic'),
(3,'document','document/document.php','documents.gif',3,1,'basic'),
(4,'student_publication','work/work.php','works.gif',3,2,'basic'),
(5,'announcement','announcements/announcements.php','valves.gif',2,1,'basic'),
(6,'user','user/user.php','members.gif',2,3,'basic'),
(7,'forum','forum/index.php','forum.gif',1,2,'basic'),
(8,'quiz','exercice/exercice.php','quiz.gif',2,2,'basic'),
(9,'group','group/group.php','group.gif',3,3,'basic'),
(10,'course_description','course_description/','info.gif',1,3,'basic'),
(11,'chat','chat/chat.php','chat.gif',0,0,'external'),
(12,'dropbox','dropbox/index.php','dropbox.gif',4,2,'basic'),
(13,'tracking','tracking/courseLog.php','statistics.gif',1,3,'courseadmin'),
(14,'homepage_link','link/link.php?action=addlink','npage.gif',1,1,'courseadmin'),
(15,'course_setting','course_info/infocours.php','reference.gif',1,1,'courseadmin'),
(16,'External','','external.gif',0,0,'external'),
(17,'AddedLearnpath','','scormbuilder.gif',0,0,'external'),
(18,'learnpath','lp/lp_controller.php','scorms.gif',5,1,'basic'),
(19,'blog','blog/blog.php','blog.gif',1,2,'basic'),
(20,'blog_management','blog/blog_admin.php','blog_admin.gif',1,2,'courseadmin'),
(21,'course_maintenance','course_info/maintenance.php','backup.gif',2,3,'courseadmin'),
(22,'survey','survey/survey_list.php','survey.gif',2,1,'basic'),
(23,'wiki','wiki/index.php','wiki.gif',2,3,'basic'),
(24,'gradebook','gradebook/index.php','gradebook.gif',2,2,'basic'),
(25,'glossary','glossary/index.php','glossary.gif',2,1,'basic'),
(26,'notebook','notebook/index.php','notebook.gif',2,1,'basic'),
(27,'attendance','attendance/index.php','attendance.gif',2,1,'basic'),
(28,'course_progress','course_progress/index.php','course_progress.gif',2,1,'basic');

INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, created_at) VALUES (1, 1, 'legal_accept','Legal',0,0, NOW());
INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, created_at) VALUES (1, 1, 'already_logged_in','Already logged in',0,0, NOW());
INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, created_at) VALUES (1, 1, 'update_type','Update script type',0,0, NOW());
INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, created_at) VALUES (1, 10, 'tags','tags',0,0, NOW());
INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, created_at) VALUES (1, 1, 'rssfeeds','RSS',0,0, NOW());
INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, created_at) VALUES (1, 1, 'dashboard', 'Dashboard', 0, 0, NOW());
INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, created_at) VALUES (1, 11, 'timezone', 'Timezone', 0, 0, NOW());
INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, default_value, created_at) values (1, 4, 'mail_notify_invitation',   'MailNotifyInvitation',0,1,'1', NOW());
INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, default_value, created_at) values (1, 4, 'mail_notify_message',      'MailNotifyMessage',0,1,'1', NOW());
INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, default_value, created_at) values (1, 4, 'mail_notify_group_message','MailNotifyGroupMessage',0,1,'1', NOW());
INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, created_at) VALUES (1, 1, 'user_chat_status','User chat status',0,0, NOW());
INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, created_at) VALUES (1, 1, 'google_calendar_url','Google Calendar URL',0,0, NOW());
INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, default_value, created_at) VALUES (2, 13, 'special_course', 'Special course', 1 , 1, '', NOW());
INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, created_at) VALUES (2, 10, 'tags', 'Tags', 1, 1, NOW());
INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, created_at) VALUES (2, 19, 'video_url', 'VideoUrl', 1, 1, NOW());
INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, created_at) VALUES (3, 16, 'image', 'Image', 1, 1, NOW());
INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, created_at) VALUES (1, 1, 'captcha_blocked_until_date', 'Account locked until', 0, 0, NOW());

INSERT INTO extra_field_options (field_id, option_value, display_text, option_order) VALUES (8, '1', 'AtOnce',1);
INSERT INTO extra_field_options (field_id, option_value, display_text, option_order) VALUES (8, '8', 'Daily',2);
INSERT INTO extra_field_options (field_id, option_value, display_text, option_order) VALUES (8, '0', 'No',3);

INSERT INTO extra_field_options (field_id, option_value, display_text, option_order) VALUES (9, '1', 'AtOnce',1);
INSERT INTO extra_field_options (field_id, option_value, display_text, option_order) VALUES (9, '8', 'Daily',2);
INSERT INTO extra_field_options (field_id, option_value, display_text, option_order) VALUES (9, '0', 'No',3);

INSERT INTO extra_field_options (field_id, option_value, display_text, option_order) VALUES (10, '1', 'AtOnce',1);
INSERT INTO extra_field_options (field_id, option_value, display_text, option_order) VALUES (10, '8', 'Daily',2);
INSERT INTO extra_field_options (field_id, option_value, display_text, option_order) VALUES (10, '0', 'No',3);

INSERT INTO access_url(url, description, active, created_by, tms) VALUES ('http://localhost/',' ',1,1, NOW());

-- Adding the platform templates
INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleCourseTitle', 'TemplateTitleCourseTitleDescription', 'coursetitle.gif', '
<!DOCTYPE html>
<html>
<head>
    {CSS}
    <style type="text/css">
        .gris_title {
            color: silver;
        }

        h1 {
            text-align: right;
        }
    </style>
</head>
<body>
<table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;"
       border="0" cellpadding="15" cellspacing="6">
    <tbody>
    <tr>
        <td style="vertical-align: middle; width: 50%;" colspan="1" rowspan="1">
            <h1>TITULUS 1<br>
                <span class="gris_title">TITULUS 2</span><br>
            </h1>
        </td>
        <td style="width: 50%;">
            <img style="width: 100px; height: 100px;" alt="Chamilo logo" src="{COURSE_DIR}images/logo_chamilo.png">
        </td>
    </tr>
    </tbody>
</table>
<p>
    <br><br>
</p>
</body>
</html>
');

/*
INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleCheckList', 'TemplateTitleCheckListDescription', 'checklist.gif', '
      <head>
                   {CSS}
                </head>
                <body>
                <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
                <tbody>
                <tr>
                <td style="vertical-align: top; width: 66%;">
                <h3>Lorem ipsum dolor sit amet</h3>
                <ul>
                    <li>consectetur adipisicing elit</li>
                    <li>sed do eiusmod tempor incididunt</li>
                    <li>ut labore et dolore magna aliqua</li>
                </ul>

                <h3>Ut enim ad minim veniam</h3>
                <ul>
                    <li>quis nostrud exercitation ullamco</li>
                    <li>laboris nisi ut aliquip ex ea commodo consequat</li>
                    <li>Excepteur sint occaecat cupidatat non proident</li>
                </ul>

                <h3>Sed ut perspiciatis unde omnis</h3>
                <ul>
                    <li>iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam</li>
                    <li>eaque ipsa quae ab illo inventore veritatis</li>
                    <li>et quasi architecto beatae vitae dicta sunt explicabo.&nbsp;</li>
                </ul>

                </td>
                <td style="background: transparent url({IMG_DIR}postit.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; width: 33%; text-align: center; vertical-align: bottom;">
                <h3>Ut enim ad minima</h3>
                Veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur.<br>
                <h3>
                <img style="width: 180px; height: 144px;" alt="trainer" src="{COURSE_DIR}images/trainer/trainer_smile.png "><br></h3>
                </td>
                </tr>
                </tbody>
                </table>
                <p><br>
                <br>
                </p>
                </body>
');
*/

INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleTeacher', 'TemplateTitleTeacherDescription', 'yourinstructor.gif', '
<!DOCTYPE html>
<html>
<head>
    {CSS}
    <style type="text/css">
        .text {
            font-weight: normal;
        }
    </style>
</head>
<body>
<table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;"
       border="0" cellpadding="15" cellspacing="6">
    <tbody>
    <tr>
        <td></td>
        <td style="height: 33%;"></td>
        <td></td>
    </tr>
    <tr>
        <td style="width: 25%;"></td>
        <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: right; font-weight: bold;"
            colspan="1" rowspan="1">
    <span class="text">
    <br>
    Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Duis pellentesque.</span>
        </td>
        <td style="width: 25%; font-weight: bold;">
            <img style="width: 180px; height: 241px;" alt="trainer" src="{COURSE_DIR}images/trainer/trainer_case.png ">
        </td>
    </tr>
    </tbody>
</table>
<p>
    <br><br>
</p>
</body>
</html>
');


INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleLeftList', 'TemplateTitleListLeftListDescription', 'leftlist.gif', '
<!DOCTYPE html>
<html>
<head>
    {CSS}
</head>
<body>
<table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;"
       border="0" cellpadding="15" cellspacing="6">
    <tbody>
    <tr>
        <td style="width: 66%;"></td>
        <td style="vertical-align: bottom; width: 33%;" colspan="1" rowspan="4">&nbsp;<img
                style="width: 180px; height: 248px;" alt="trainer"
                src="{COURSE_DIR}images/trainer/trainer_reads.png "><br>
        </td>
    </tr>
    <tr align="right">
        <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 66%;">
            Lorem
            ipsum dolor sit amet.
        </td>
    </tr>
    <tr align="right">
        <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 66%;">
            Vivamus
            a quam.&nbsp;<br>
        </td>
    </tr>
    <tr align="right">
        <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 66%;">
            Proin
            a est stibulum ante ipsum.
        </td>
    </tr>
    </tbody>
</table>
<p><br>
    <br>
</p>
</body>
</html>
');

INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleLeftRightList', 'TemplateTitleLeftRightListDescription', 'leftrightlist.gif', '
<!DOCTYPE html>
<html>
<head>
    {CSS}
</head>
<body>
<table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; height: 400px; width: 720px;"
       border="0" cellpadding="15" cellspacing="6">
    <tbody>
    <tr>
        <td></td>
        <td style="vertical-align: top;" colspan="1" rowspan="4">&nbsp;<img style="width: 180px; height: 294px;"
                                                                            alt="Trainer"
                                                                            src="{COURSE_DIR}images/trainer/trainer_join_hands.png "><br>
        </td>
        <td></td>
    </tr>
    <tr>
        <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: right;">
            Lorem
            ipsum dolor sit amet.
        </td>
        <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: left;">
            Convallis
            ut.&nbsp;Cras dui magna.
        </td>
    </tr>
    <tr>
        <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: right;">
            Vivamus
            a quam.&nbsp;<br>
        </td>
        <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: left;">
            Etiam
            lacinia stibulum ante.<br>
        </td>
    </tr>
    <tr>
        <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: right;">
            Proin
            a est stibulum ante ipsum.
        </td>
        <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: left;">
            Consectetuer
            adipiscing elit. <br>
        </td>
    </tr>
    </tbody>
</table>
<p><br>
    <br>
</p>
</body>
</html>
');

INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleRightList', 'TemplateTitleRightListDescription', 'rightlist.gif', '
<!DOCTYPE html>
<html>
<head>
    {CSS}
</head>
<body style="direction: ltr;">
<table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;"
       border="0" cellpadding="15" cellspacing="6">
    <tbody>
    <tr>
        <td style="vertical-align: bottom; width: 50%;" colspan="1" rowspan="4"><img
                style="width: 300px; height: 199px;" alt="trainer"
                src="{COURSE_DIR}images/trainer/trainer_points_right.png"><br>
        </td>
        <td style="width: 50%;"></td>
    </tr>
    <tr>
        <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; text-align: left; width: 50%;">
            Convallis
            ut.&nbsp;Cras dui magna.
        </td>
    </tr>
    <tr>
        <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; text-align: left; width: 50%;">
            Etiam
            lacinia.<br>
        </td>
    </tr>
    <tr>
        <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; text-align: left; width: 50%;">
            Consectetuer
            adipiscing elit. <br>
        </td>
    </tr>
    </tbody>
</table>
<p><br>
    <br>
</p>
</body>
</html>
');

/*
INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleComparison', 'TemplateTitleComparisonDescription', 'compare.gif', '
<head>
            {CSS}
            </head>

            <body>
                <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
                <tr>
                    <td style="height: 10%; width: 33%;"></td>
                    <td style="vertical-align: top; width: 33%;" colspan="1" rowspan="2">&nbsp;<img style="width: 180px; height: 271px;" alt="trainer" src="{COURSE_DIR}images/trainer/trainer_standing.png "><br>
                    </td>
                    <td style="height: 10%; width: 33%;"></td>
                </tr>
            <tr>
            <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 33%; text-align: right;">
            Lorem ipsum dolor sit amet.
            </td>
            <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; text-align: left; width: 33%;">
            Convallis
            ut.&nbsp;Cras dui magna.</td>
            </tr>
            </body>
');
*/

INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleDiagram', 'TemplateTitleDiagramDescription', 'diagram.gif', '
<!DOCTYPE html>
<html>
<head>
    {CSS}
</head>
<body>
<table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;"
       border="0" cellpadding="15" cellspacing="6">
    <tbody>
    <tr>
        <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; text-align: left; height: 33%; width: 33%;">
            <br>
            Etiam
            lacinia stibulum ante.
            Convallis
            ut.&nbsp;Cras dui magna.
        </td>
        <td colspan="1" rowspan="3">
            <img style="width: 350px; height: 267px;" alt="Alaska chart"
                 src="{COURSE_DIR}images/diagrams/alaska_chart.png "></td>
    </tr>
    <tr>
        <td colspan="1" rowspan="1">
            <img style="width: 300px; height: 199px;" alt="trainer"
                 src="{COURSE_DIR}images/trainer/trainer_points_right.png "></td>
    </tr>
    <tr>
    </tr>
    </tbody>
</table>
<p><br>
    <br>
</p>
</body>
</html>
');

INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleDesc', 'TemplateTitleCheckListDescription', 'description.gif', '
<!DOCTYPE html>
<html>
<head>
    {CSS}
</head>
<body>
<table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;"
       border="0" cellpadding="15" cellspacing="6">
    <tbody>
    <tr>
        <td style="width: 50%; vertical-align: top;">
            <img style="width: 48px; height: 49px; float: left;" alt="01" src="{COURSE_DIR}images/small/01.png "
                 hspace="5"><br>Lorem ipsum dolor sit amet<br><br><br>
            <img style="width: 48px; height: 49px; float: left;" alt="02" src="{COURSE_DIR}images/small/02.png "
                 hspace="5">
            <br>Ut enim ad minim veniam<br><br><br>
            <img style="width: 48px; height: 49px; float: left;" alt="03" src="{COURSE_DIR}images/small/03.png "
                 hspace="5">Duis aute irure dolor in reprehenderit<br><br><br>
            <img style="width: 48px; height: 49px; float: left;" alt="04" src="{COURSE_DIR}images/small/04.png "
                 hspace="5">Neque porro quisquam est
        </td>

        <td style="vertical-align: top; width: 50%; text-align: right;" colspan="1" rowspan="1">
            <img style="width: 300px; height: 291px;" alt="Gearbox" src="{COURSE_DIR}images/diagrams/gearbox.jpg "><br>
        </td>
    </tr>
    <tr></tr>
    </tbody>
</table>
<p><br>
    <br>
</p>
</body>
</html>
');

/*
INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleObjectives', 'TemplateTitleObjectivesDescription', 'courseobjectives.gif', '
<head>
                   {CSS}
                </head>

                <body>
                    <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
                    <tbody>
                    <tr>
                    <td style="vertical-align: bottom; width: 33%;" colspan="1" rowspan="2">
                    <img style="width: 180px; height: 271px;" alt="trainer" src="{COURSE_DIR}images/trainer/trainer_chair.png "><br>
                    </td>
                    <td style="height: 10%; width: 66%;"></td>
                    </tr>
                    <tr>
                    <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; text-align: left; width: 66%;">
                    <h3>Lorem ipsum dolor sit amet</h3>
                    <ul>
                    <li>consectetur adipisicing elit</li>
                    <li>sed do eiusmod tempor incididunt</li>
                    <li>ut labore et dolore magna aliqua</li>
                    </ul>
                    <h3>Ut enim ad minim veniam</h3>
                    <ul>
                    <li>quis nostrud exercitation ullamco</li>
                    <li>laboris nisi ut aliquip ex ea commodo consequat</li>
                    <li>Excepteur sint occaecat cupidatat non proident</li>
                    </ul>
                    </td>
                    </tr>
                    </tbody>
                    </table>
                <p><br>
                <br>
                </p>
                </body>
');
*/

INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleCycle', 'TemplateTitleCycleDescription', 'cyclechart.gif', '
<!DOCTYPE html>
<html>
<head>
    {CSS}
    <style>
        .title {
            color: white;
            font-weight: bold;
        }
    </style>
</head>
<body>
<table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;"
       border="0" cellpadding="8" cellspacing="6">
    <tbody>
    <tr>
        <td style="text-align: center; vertical-align: bottom; height: 10%;" colspan="3" rowspan="1">
            <img style="width: 250px; height: 76px;" alt="arrow" src="{COURSE_DIR}images/diagrams/top_arrow.png ">
        </td>
    </tr>
    <tr>
        <td style="height: 5%; width: 45%; vertical-align: top; background-color: rgb(153, 153, 153); text-align: center;">
            <span class="title">Lorem ipsum</span>
        </td>
        <td style="height: 5%; width: 10%;"></td>
        <td style="height: 5%; vertical-align: top; background-color: rgb(153, 153, 153); text-align: center;">
            <span class="title">Sed ut perspiciatis</span>
        </td>
    </tr>
    <tr>
        <td style="background-color: rgb(204, 204, 255); width: 45%; vertical-align: top;">
            <ul>
                <li>dolor sit amet</li>
                <li>consectetur adipisicing elit</li>
                <li>sed do eiusmod tempor&nbsp;</li>
                <li>adipisci velit, sed quia non numquam</li>
                <li>eius modi tempora incidunt ut labore et dolore magnam</li>
            </ul>
        </td>
        <td style="width: 10%;"></td>
        <td style="background-color: rgb(204, 204, 255); width: 45%; vertical-align: top;">
            <ul>
                <li>ut enim ad minim veniam</li>
                <li>quis nostrud exercitation</li>
                <li>ullamco laboris nisi ut</li>
                <li> Quis autem vel eum iure reprehenderit qui in ea</li>
                <li>voluptate velit esse quam nihil molestiae consequatur,</li>
            </ul>
        </td>
    </tr>
    <tr align="center">
        <td style="height: 10%; vertical-align: top;" colspan="3" rowspan="1">
            <img style="width: 250px; height: 76px;" alt="arrow" src="{COURSE_DIR}images/diagrams/bottom_arrow.png ">&nbsp;&nbsp;
            &nbsp; &nbsp; &nbsp;
        </td>
    </tr>
    </tbody>
</table>
<p><br>
    <br>
</p>
</body>
</html>
');

/*
INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleLearnerWonder', 'TemplateTitleLearnerWonderDescription', 'learnerwonder.gif', '
<head>
               {CSS}
            </head>

            <body>
                <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
                <tbody>
                <tr>
                <td style="width: 33%;" colspan="1" rowspan="4">
                    <img style="width: 120px; height: 348px;" alt="learner wonders" src="{COURSE_DIR}images/silhouette.png "><br>
                </td>
                <td style="width: 66%;"></td>
                </tr>
                <tr align="center">
                <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 66%;">
                Convallis
                ut.&nbsp;Cras dui magna.</td>
                </tr>
                <tr align="center">
                <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 66%;">
                Etiam
                lacinia stibulum ante.<br>
                </td>
                </tr>
                <tr align="center">
                <td style="background: transparent url({IMG_DIR}faded_grey.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 66%;">
                Consectetuer
                adipiscing elit. <br>
                </td>
                </tr>
                </tbody>
                </table>
            <p><br>
            <br>
            </p>
            </body>
');
*/

INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleTimeline', 'TemplateTitleTimelineDescription', 'phasetimeline.gif', '
<!DOCTYPE html>
<html>
<head>
    {CSS}
    <style>
        .title {
            font-weight: bold;
            text-align: center;
        }
    </style>
</head>
<body>
<table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;"
       border="0" cellpadding="8" cellspacing="5">
    <tbody>
    <tr class="title">
        <td style="vertical-align: top; height: 3%; background-color: rgb(224, 224, 224);">Lorem ipsum</td>
        <td style="height: 3%;"></td>
        <td style="vertical-align: top; height: 3%; background-color: rgb(237, 237, 237);">Perspiciatis</td>
        <td style="height: 3%;"></td>
        <td style="vertical-align: top; height: 3%; background-color: rgb(245, 245, 245);">Nemo enim</td>
    </tr>
    <tr>
        <td style="vertical-align: top; width: 30%; background-color: rgb(224, 224, 224);">
            <ul>
                <li>dolor sit amet</li>
                <li>consectetur</li>
                <li>adipisicing elit</li>
            </ul>
            <br>
        </td>
        <td>
            <img style="width: 32px; height: 32px;" alt="arrow" src="{COURSE_DIR}images/small/arrow.png ">
        </td>
        <td style="vertical-align: top; width: 30%; background-color: rgb(237, 237, 237);">
            <ul>
                <li>ut labore</li>
                <li>et dolore</li>
                <li>magni dolores</li>
            </ul>
        </td>
        <td>
            <img style="width: 32px; height: 32px;" alt="arrow" src="{COURSE_DIR}images/small/arrow.png ">
        </td>
        <td style="vertical-align: top; background-color: rgb(245, 245, 245); width: 30%;">
            <ul>
                <li>neque porro</li>
                <li>quisquam est</li>
                <li>qui dolorem&nbsp;&nbsp;</li>
            </ul>
            <br><br>
        </td>
    </tr>
    </tbody>
</table>
<p><br>
    <br>
</p>
</body>
</html>
');

/*
INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleStopAndThink', 'TemplateTitleStopAndThinkDescription', 'stopthink.gif', '
<head>
               {CSS}
            </head>
            <body>
                <table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;" border="0" cellpadding="15" cellspacing="6">
                <tbody>
                <tr>
                <td style="vertical-align: bottom; width: 33%;" colspan="1" rowspan="2">
                    <img style="width: 180px; height: 169px;" alt="trainer" src="{COURSE_DIR}images/trainer/trainer_staring.png ">
                <br>
                </td>
                <td style="height: 10%; width: 66%;"></td>
                </tr>
                <tr>
                <td style="background: transparent url({IMG_DIR}postit.png ) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; width: 66%; vertical-align: middle; text-align: center;">
                    <h3>Attentio sectetur adipisicing elit</h3>
                    <ul>
                        <li>sed do eiusmod tempor incididunt</li>
                        <li>ut labore et dolore magna aliqua</li>
                        <li>quis nostrud exercitation ullamco</li>
                    </ul><br></td>
                </tr>
                </tbody>
                </table>
            <p><br>
            <br>
            </p>
            </body>
');
*/

INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleTable', 'TemplateTitleCheckListDescription', 'table.gif', '
<!DOCTYPE html>
<html>
<head>
    {CSS}
    <style type="text/css">
        .title {
            font-weight: bold;
            text-align: center;
        }

        .items {
            text-align: right;
        }
    </style>
</head>
<body>
<br/>
<h2>A table</h2>
<table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px;"
       border="1" cellpadding="5" cellspacing="0">
    <tbody>
    <tr class="title">
        <td>City</td>
        <td>2005</td>
        <td>2006</td>
        <td>2007</td>
        <td>2008</td>
    </tr>
    <tr class="items">
        <td>Lima</td>
        <td>10,40</td>
        <td>8,95</td>
        <td>9,19</td>
        <td>9,76</td>
    </tr>
    <tr class="items">
        <td>New York</td>
        <td>18,39</td>
        <td>17,52</td>
        <td>16,57</td>
        <td>16,60</td>
    </tr>
    <tr class="items">
        <td>Barcelona</td>
        <td>0,10</td>
        <td>0,10</td>
        <td>0,05</td>
        <td>0,05</td>
    </tr>
    <tr class="items">
        <td>Paris</td>
        <td>3,38</td>
        <td>3,63</td>
        <td>3,63</td>
        <td>3,54</td>
    </tr>
    </tbody>
</table>
<br>
</body>
</html>
');

INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleAudio', 'TemplateTitleAudioDescription', 'audiocomment.gif', '
<!DOCTYPE html>
<html>
<head>
    {CSS}
</head>
<body>
<table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;"
       border="0" cellpadding="15" cellspacing="6">
    <tbody>
    <tr>
        <td>
            <div align="center">
    <span style="text-align: center;">
        <embed type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer"
               width="300" height="20" bgcolor="#FFFFFF" src="{REL_PATH}main/inc/lib/mediaplayer/player.swf"
               allowfullscreen="false" allowscriptaccess="always"
               flashvars="file={COURSE_DIR}audio/ListeningComprehension.mp3&amp;autostart=true"></embed>
    </span></div>

            <br>
        </td>
        <td colspan="1" rowspan="3"><br>
            <img style="width: 300px; height: 341px; float: right;" alt="image"
                 src="{COURSE_DIR}images/diagrams/head_olfactory_nerve.png "><br></td>
    </tr>
    <tr>
        <td colspan="1" rowspan="1">
            <img style="width: 180px; height: 271px;" alt="trainer"
                 src="{COURSE_DIR}images/trainer/trainer_glasses.png"><br></td>
    </tr>
    <tr>
    </tr>
    </tbody>
</table>
<p><br>
    <br>
</p>
</body>
</html>
');

INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleVideo', 'TemplateTitleVideoDescription', 'video.gif', '
<!DOCTYPE html>
<html>
<head>
    {CSS}
</head>
<body>
<table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 720px; height: 400px;"
       border="0" cellpadding="15" cellspacing="6">
    <tbody>
    <tr>
        <td style="width: 50%; vertical-align: top;">
            <div style="text-align: center;" id="player810625-parent">
                <div style="border-style: none; overflow: hidden; width: 320px; height: 240px; background-color: rgb(220, 220, 220);">
                    <div id="player810625">
                        <div id="player810625-config"
                             style="overflow: hidden; display: none; visibility: hidden; width: 0px; height: 0px;">
                            url={REL_PATH}main/default_course_document/video/flv/example.flv width=320 height=240
                            loop=false play=false downloadable=false fullscreen=true displayNavigation=true
                            displayDigits=true align=left dispPlaylist=none playlistThumbs=false
                        </div>
                    </div>
                    <embed
                            type="application/x-shockwave-flash"
                            src="{REL_PATH}main/inc/lib/mediaplayer/player.swf"
                            width="320"
                            height="240"
                            id="single"
                            name="single"
                            quality="high"
                            allowfullscreen="true"
                            flashvars="width=320&height=240&autostart=false&file={REL_PATH}main/default_course_document/video/flv/example.flv&repeat=false&image=&showdownload=false&link={REL_PATH}main/default_course_document/video/flv/example.flv&showdigits=true&shownavigation=true&logo="
                    />
                </div>
            </div>
        </td>
        <td style="background: transparent url({IMG_DIR}faded_grey.png) repeat scroll center top; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; vertical-align: top; width: 50%;">
            <h3><br>
            </h3>
            <h3>Lorem ipsum dolor sit amet</h3>
            <ul>
                <li>consectetur adipisicing elit</li>
                <li>sed do eiusmod tempor incididunt</li>
                <li>ut labore et dolore magna aliqua</li>
            </ul>
            <h3>Ut enim ad minim veniam</h3>
            <ul>
                <li>quis nostrud exercitation ullamco</li>
                <li>laboris nisi ut aliquip ex ea commodo consequat</li>
                <li>Excepteur sint occaecat cupidatat non proident</li>
            </ul>
        </td>
    </tr>
    </tbody>
</table>
<p><br>
    <br>
</p>
<style type="text/css">body {
}</style><!-- to fix a strange bug appearing with firefox when editing this template -->
</body>
</html>
');

INSERT INTO system_template (title, comment, image, content) VALUES
('TemplateTitleFlash', 'TemplateTitleFlashDescription', 'flash.gif', '
<!DOCTYPE html>
<html>
<head>
    {CSS}
</head>
<body>
<table style="background: transparent url({IMG_DIR}faded_blue_horizontal.png ) repeat scroll 0% 50%; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; text-align: left; width: 100%; height: 400px;"
       border="0" cellpadding="15" cellspacing="6">
    <tbody>
    <tr>
        <td align="center">
            <embed width="700" height="300" type="application/x-shockwave-flash"
                   pluginspage="http://www.macromedia.com/go/getflashplayer"
                   src="{COURSE_DIR}flash/SpinEchoSequence.swf" play="true" loop="true" menu="true"></embed>
            </span><br/>
        </td>
    </tr>
    </tbody>
</table>
<p><br>
    <br>
</p>
</body>
</html>
');

INSERT INTO user_friend_relation_type (id, title)
VALUES
(1,'SocialUnknow'),
(2,'SocialParent'),
(3,'SocialFriend'),
(4,'SocialGoodFriend'),
(5,'SocialEnemy'),
(6,'SocialDeleted');

INSERT INTO skill (name, icon, description, short_code, access_url_id, updated_at) VALUES ('Root', '', '', 'root', 1, now());

INSERT INTO skill_rel_skill VALUES(1, 1, 0, 0, 0);

INSERT INTO course_type (id, name) VALUES (1, 'All tools');
INSERT INTO course_type (id, name) VALUES (2, 'Entry exam');


INSERT INTO sequence_rule (description)
VALUES ('If user completes 70% of an entity or group of items, he will be able to access another entity or group of items');

INSERT INTO sequence_condition (description, mat_op, param, act_true, act_false) VALUES ('<= 100%','<=', 100.0, 2, 0), ('>= 70%','>=', 70.0, 0, '');

INSERT INTO sequence_rule_condition VALUES
(1,1,1),
(2,1,2);

INSERT INTO sequence_method (description,formula, assign, met_type, act_false) VALUES
('Add completed item','v#2 + $complete_items;', 2, 'add', ''),
('Update progress by division', 'v#2 / v#3 * 100;', 1, 'div', ''),
('Update items count', '$total_items;', 3,'update', ''),
('Enable success', '1;', 4, 'success', ''),
('Store success date', '(empty(v#5))? api_get_utc_datetime() : v#5;', 5, 'success', ''),
('Enable availability', '1;', 6, 'pre', ''),
('Store availability start date', '(empty(v#7))? api_get_utc_datetime() : v#7;', 7, 'pre', ''),
('Store availability end date', '(empty($available_end_date))? api_get_utc_datetime($available_end_date) : "0000-00-00 00:00:00";', 8, 'pre', ''),
('Increase the items count', 'v#3 + $total_items;', 3,'add', ''),
('Update completed items', '$complete_items;', 2,'update', ''),
('Update progress', '$complete_items / $total_items * 100;', 1, 'update', '');

INSERT INTO sequence_rule_method VALUES
(1,1,1,1),
(2,1,2,3),
(3,1,3,0),
(4,1,4,0),
(5,1,5,0),
(6,1,6,0),
(7,1,7,0),
(8,1,8,0),
(9,1,9,2),
(10,1,10,0),
(11,1,11,0);

INSERT INTO sequence_variable VALUES
(1, 'Percentile progress', 'advance', 0.0),
(2, 'Completed items', 'complete_items', 0),
(3, 'Items count', 'total_items', 0),
(4, 'Completed', 'success', 0),
(5, 'Completion date', 'success_date', '0000-00-00 00:00:00'),
(6, 'Available', 'available', 0),
(7, 'Availability start date', 'available_start_date', '0000-00-00 00:00:00'),
(8, 'Availability end date', 'available_end_date', '0000-00-00 00:00:00');

INSERT INTO sequence_formula VALUES
(1,1,2),
(2,2,2),
(3,2,3),
(4,2,1),
(5,3,3),
(6,4,4),
(7,5,5),
(8,6,6),
(9,7,7),
(10,8,8),
(11,9,3),
(12,10,2),
(13,11,1);

INSERT INTO sequence_valid VALUES
(1,1,1),
(2,1,2);

INSERT INTO sequence_type_entity VALUES
(1,'Lp', 'Learning Path','c_lp'),
(2,'Quiz', 'Quiz and Tests','c_quiz'),
(3,'LpItem', 'Items of a Learning Path','c_lp_item');

-- Version 1.10.0.39

INSERT INTO settings_current
(variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable)
VALUES
('hide_home_top_when_connected', '', 'radio', 'Platform', 'false', 'HideHomeTopContentWhenLoggedInText', 'HideHomeTopContentWhenLoggedInComment', NULL, '', true),
('hide_global_announcements_when_not_connected','','radio','Platform','false', 'HideGlobalAnnouncementsWhenNotLoggedInText','HideGlobalAnnouncementsWhenNotLoggedInComment',NULL,'', true),
('course_creation_use_template','','select_course','Course','','CourseCreationUsesTemplateText','CourseCreationUsesTemplateComment',NULL,'',true),
('allow_strength_pass_checker','','radio','Security','true','EnablePasswordStrengthCheckerText','EnablePasswordStrengthCheckerComment',NULL,'',true),
('allow_captcha','','radio','Security','false','EnableCaptchaText','EnableCaptchaComment',NULL,'',true),
('captcha_number_mistakes_to_block_account','','textfield','Security',5,'CaptchaNumberOfMistakesBeforeBlockingAccountText','CaptchaNumberOfMistakesBeforeBlockingAccountComment',NULL,'',true),
('captcha_time_to_block','','textfield','Security',5,'CaptchaTimeAccountIsLockedText','CaptchaTimeAccountIsLockedComment',NULL,'',true),
('drh_can_access_all_session_content','','radio','Session','false','DRHAccessToAllSessionContentText','DRHAccessToAllSessionContentComment',NULL,'',true),
('display_groups_forum_in_general_tool','','radio','Tools','true','ShowGroupForaInGeneralToolText','ShowGroupForaInGeneralToolComment',NULL,'',true),
('allow_tutors_to_assign_students_to_session','','radio','Session','false','TutorsCanAssignStudentsToSessionsText','TutorsCanAssignStudentsToSessionsComment',NULL,'',true);

INSERT INTO settings_options (variable, value, display_text)
VALUES
('hide_home_top_when_connected','true','Yes'),
('hide_home_top_when_connected','false','No'),
('hide_global_announcements_when_not_connected','true','Yes'),
('hide_global_announcements_when_not_connected','false','No'),
('allow_strength_pass_checker','true','Yes'),
('allow_strength_pass_checker','false','No'),
('allow_captcha','true','Yes'),
('allow_captcha','false','No'),
('drh_can_access_all_session_content','true','Yes'),
('drh_can_access_all_session_content','false','No'),
('display_groups_forum_in_general_tool','true','Yes'),
('display_groups_forum_in_general_tool','false','No'),
('allow_tutors_to_assign_students_to_session','true','Yes'),
('allow_tutors_to_assign_students_to_session','false','No');

UPDATE user SET username_canonical = username;

-- Version 1.10.0.40

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable)
VALUES
('allow_lp_return_link', NULL, 'radio', 'Course', 'true', 'AllowLearningPathReturnLinkTitle', 'AllowLearningPathReturnLinkComment', NULL, NULL, 1),
('hide_scorm_export_link', NULL, 'radio', 'Course', 'false', 'HideScormExportLinkTitle', 'HideScormExportLinkComment', NULL, NULL, 1),
('hide_scorm_copy_link', NULL, 'radio', 'Course', 'false', 'HideScormCopyLinkTitle', 'HideScormCopyLinkComment', NULL, NULL, 1),
('hide_scorm_pdf_link', NULL, 'radio', 'Course', 'true', 'HideScormPdfLinkTitle', 'HideScormPdfLinkComment', NULL, NULL, 1),
('session_days_before_coach_access', NULL, 'textfield', 'Session', '0', 'SessionDaysBeforeCoachAccessTitle', 'SessionDaysBeforeCoachAccessComment', NULL, NULL, 1),
('session_days_after_coach_access', NULL, 'textfield', 'Session', '0', 'SessionDaysAfterCoachAccessTitle', 'SessionDaysAfterCoachAccessComment', NULL, NULL, 1),
('pdf_logo_header', NULL, 'radio', 'Course', 'false', 'PdfLogoHeaderTitle', 'PdfLogoHeaderComment', NULL, NULL, 1),
('order_user_list_by_official_code', NULL, 'radio', 'Platform', 'false', 'OrderUserListByOfficialCodeTitle', 'OrderUserListByOfficialCodeComment', NULL, NULL, 1),
('email_alert_manager_on_new_quiz', NULL, 'radio', 'Tools', 'true', 'AlertManagerOnNewQuizTitle', 'AlertManagerOnNewQuizComment', NULL, NULL, 1),
('show_official_code_exercise_result_list', NULL, 'radio', 'Tools', 'false', 'ShowOfficialCodeInExerciseResultListTitle', 'ShowOfficialCodeInExerciseResultListComment', NULL, NULL, 1),
('course_catalog_hide_private', NULL, 'radio', 'Platform', 'false', 'HidePrivateCoursesFromCourseCatalogTitle', 'HidePrivateCoursesFromCourseCatalogComment', NULL, NULL, 1),
('catalog_show_courses_sessions', NULL, 'radio', 'Platform', '0', 'CoursesCatalogueShowSessionsTitle', 'CoursesCatalogueShowSessionsComment', NULL, NULL, 1),
('auto_detect_language_custom_pages', NULL, 'radio', 'Platform', 'true', 'AutoDetectLanguageCustomPagesTitle', 'AutoDetectLanguageCustomPagesComment', NULL, NULL, 1),
('lp_show_reduced_report', NULL, 'radio', 'Tools', 'false', 'LearningPathShowReducedReportTitle', 'LearningPathShowReducedReportComment', NULL, NULL, 1),
('allow_session_course_copy_for_teachers', NULL, 'radio', 'Session', 'false', 'AllowSessionCourseCopyForTeachersTitle', 'AllowSessionCourseCopyForTeachersComment', NULL, NULL, 1),
('hide_logout_button', NULL, 'radio', 'Platform', 'false', 'HideLogoutButtonTitle', 'HideLogoutButtonComment', NULL, NULL, 1),
('redirect_admin_to_courses_list', NULL, 'radio', 'Platform', 'false', 'RedirectAdminToCoursesListTitle', 'RedirectAdminToCoursesListComment', NULL, NULL, 1),
('course_images_in_courses_list', NULL, 'radio', 'Course', 'true', 'CourseImagesInCoursesListTitle', 'CourseImagesInCoursesListComment', NULL, NULL, 1),
('student_publication_to_take_in_gradebook', NULL, 'radio', 'Gradebook', 'first', 'StudentPublicationSelectionForGradebookTitle', 'StudentPublicationSelectionForGradebookComment', NULL, NULL, 1),
('certificate_filter_by_official_code', NULL, 'radio', 'Gradebook', 'false', 'FilterCertificateByOfficialCodeTitle', 'FilterCertificateByOfficialCodeComment', NULL, NULL, 1),
('exercise_max_ckeditors_in_page', NULL, 'textfield', 'Tools', '0', 'MaxCKeditorsOnExerciseResultsPageTitle', 'MaxCKeditorsOnExerciseResultsPageComment', NULL, NULL, 1),
('document_if_file_exists_option', NULL, 'radio', 'Tools', 'rename', 'DocumentDefaultOptionIfFileExistsTitle', 'DocumentDefaultOptionIfFileExistsComment', NULL, NULL, 1),
('add_gradebook_certificates_cron_task_enabled', NULL, 'radio', 'Tools', 'false', 'GradebookCronTaskGenerationTitle', 'GradebookCronTaskGenerationComment', NULL, NULL, 1),
('openbadges_backpack', NULL, 'textfield', 'Gradebook', 'https://backpack.openbadges.org/', 'OpenBadgesBackpackUrlTitle', 'OpenBadgesBackpackUrlComment', NULL, NULL, 1),
('cookie_warning', NULL, 'radio', 'Tools', 'false', 'CookieWarningTitle', 'CookieWarningComment', NULL, NULL, 1),
('hide_course_group_if_no_tools_available', NULL, 'radio', 'Tools', 'false', 'HideCourseGroupIfNoToolAvailableTitle', 'HideCourseGroupIfNoToolAvailableComment', NULL, NULL, 1),
('catalog_allow_session_auto_subscription', NULL, 'radio', 'Session', 'false', 'CatalogueAllowSessionAutoSubscriptionTitle', 'CatalogueAllowSessionAutoSubscriptionTitle', NULL, NULL, 1),
('registration.soap.php.decode_utf8', NULL, 'radio', 'Platform', 'false', 'SoapRegistrationDecodeUtf8Title', 'SoapRegistrationDecodeUtf8Comment', NULL, NULL, 1),
('allow_delete_attendance', NULL, 'radio', 'Tools', 'true', 'AttendanceDeletionEnableTitle', 'AttendanceDeletionEnableComment', NULL, NULL, 1),
('gravatar_enabled', NULL, 'radio', 'Platform', 'false', 'GravatarPicturesTitle', 'GravatarPicturesComment', NULL, NULL, 1),
('gravatar_type', NULL, 'radio', 'Platform', 'mm', 'GravatarPicturesTypeTitle', 'GravatarPicturesTypeComment', NULL, NULL, 1),
('limit_session_admin_role', NULL, 'radio', 'Session', 'false', 'SessionAdminPermissionsLimitTitle', 'SessionAdminPermissionsLimitComment', NULL, NULL, 1),
('show_session_description', NULL, 'radio', 'Session', 'false', 'ShowSessionDescriptionTitle', 'ShowSessionDescriptionComment', NULL, NULL, 1),
('hide_certificate_export_link_students', NULL, 'radio', 'Gradebook', 'false', 'CertificateHideExportLinkStudentTitle', 'CertificateHideExportLinkStudentComment', NULL, NULL, 1),
('hide_certificate_export_link', NULL, 'radio', 'Gradebook', 'false', 'CertificateHideExportLinkTitle', 'CertificateHideExportLinkComment', NULL, NULL, 1),
('dropbox_hide_course_coach', NULL, 'radio', 'Tools', 'false', 'DropboxHideCourseCoachTitle', 'DropboxHideCourseCoachComment', NULL, NULL, 1),
('dropbox_hide_general_coach', NULL, 'radio', 'Tools', 'false', 'DropboxHideGeneralCoachTitle', 'DropboxHideGeneralCoachComment', NULL, NULL, 1),
('sso_force_redirect', NULL, 'radio', 'Security', 'false', 'SSOForceRedirectTitle', 'SSOForceRedirectComment', NULL, NULL, 1),
('session_course_ordering', NULL, 'radio', 'Session', 'false', 'SessionCourseOrderingTitle', 'SessionCourseOrderingComment', NULL, NULL, 1),
('gamification_mode', NULL, 'radio', 'Platform', '0', 'GamificationModeTitle', 'GamificationModeComment', NULL, NULL, 1),
('prevent_multiple_simultaneous_login', NULL, 'radio', 'Security', 'false', 'PreventMultipleSimultaneousLoginTitle', 'PreventMultipleSimultaneousLoginComment', NULL, NULL, 0),
('gradebook_detailed_admin_view', NULL, 'radio', 'Gradebook', 'false', 'ShowAdditionalColumnsInStudentResultsPageTitle', 'ShowAdditionalColumnsInStudentResultsPageComment', NULL, NULL, 1),
('course_catalog_published', NULL, 'radio', 'Course', 'false', 'CourseCatalogIsPublicTitle', 'CourseCatalogIsPublicComment', NULL, NULL, 0),
('user_reset_password', NULL, 'radio', 'Security', 'false', 'ResetPasswordTokenTitle', 'ResetPasswordTokenComment', NULL, NULL, 0),
('user_reset_password_token_limit', NULL, 'textfield', 'Security', '3600', 'ResetPasswordTokenLimitTitle', 'ResetPasswordTokenLimitComment', NULL, NULL, 0),
('my_courses_view_by_session', NULL, 'radio', 'Session', 'false', 'ViewMyCoursesListBySessionTitle', 'ViewMyCoursesListBySessionComment', NULL, NULL, 0),
('show_full_skill_name_on_skill_wheel', NULL, 'radio', 'Platform', 'false', 'ShowFullSkillNameOnSkillWheelTitle', 'ShowFullSkillNameOnSkillWheelComment', NULL, NULL, 1);

INSERT INTO settings_options (variable, value, display_text)
VALUES
('allow_lp_return_link', 'true', 'Yes'),
('allow_lp_return_link', 'false', 'No'),
('hide_scorm_export_link', 'true', 'Yes'),
('hide_scorm_export_link', 'false', 'No'),
('hide_scorm_copy_link', 'true', 'Yes'),
('hide_scorm_copy_link', 'false', 'No'),
('hide_scorm_pdf_link', 'true', 'Yes'),
('hide_scorm_pdf_link', 'false', 'No'),
('pdf_logo_header', 'true', 'Yes'),
('pdf_logo_header', 'false', 'No'),
('order_user_list_by_official_code', 'true', 'Yes'),
('order_user_list_by_official_code', 'false', 'No'),
('email_alert_manager_on_new_quiz', 'true', 'Yes'),
('email_alert_manager_on_new_quiz', 'false', 'No'),
('show_official_code_exercise_result_list', 'true', 'Yes'),
('show_official_code_exercise_result_list', 'false', 'No'),
('course_catalog_hide_private', 'true', 'Yes'),
('course_catalog_hide_private', 'false', 'No'),
('catalog_show_courses_sessions', '0', 'CatalogueShowOnlyCourses'),
('catalog_show_courses_sessions', '1', 'CatalogueShowOnlySessions'),
('catalog_show_courses_sessions', '2', 'CatalogueShowCoursesAndSessions'),
('auto_detect_language_custom_pages', 'true', 'Yes'),
('auto_detect_language_custom_pages', 'false', 'No'),
('lp_show_reduced_report', 'true', 'Yes'),
('lp_show_reduced_report', 'false', 'No'),
('allow_session_course_copy_for_teachers', 'true', 'Yes'),
('allow_session_course_copy_for_teachers', 'false', 'No'),
('hide_logout_button', 'true', 'Yes'),
('hide_logout_button', 'false', 'No'),
('redirect_admin_to_courses_list', 'true', 'Yes'),
('redirect_admin_to_courses_list', 'false', 'No'),
('course_images_in_courses_list', 'true', 'Yes'),
('course_images_in_courses_list', 'false', 'No'),
('student_publication_to_take_in_gradebook', 'first', 'First'),
('student_publication_to_take_in_gradebook', 'last', 'Last'),
('certificate_filter_by_official_code', 'true', 'Yes'),
('certificate_filter_by_official_code', 'false', 'No'),
('document_if_file_exists_option', 'rename', 'Rename'),
('document_if_file_exists_option', 'overwrite', 'Overwrite'),
('add_gradebook_certificates_cron_task_enabled', 'true', 'Yes'),
('add_gradebook_certificates_cron_task_enabled', 'false', 'No'),
('cookie_warning', 'true', 'Yes'),
('cookie_warning', 'false', 'No'),
('hide_course_group_if_no_tools_available', 'true', 'Yes'),
('hide_course_group_if_no_tools_available', 'false', 'No'),
('catalog_allow_session_auto_subscription', 'true', 'Yes'),
('catalog_allow_session_auto_subscription', 'false', 'No'),
('registration.soap.php.decode_utf8', 'true', 'Yes'),
('registration.soap.php.decode_utf8', 'false', 'No'),
('allow_delete_attendance', 'true', 'Yes'),
('allow_delete_attendance', 'false', 'No'),
('gravatar_enabled', 'true', 'Yes'),
('gravatar_enabled', 'false', 'No'),
('gravatar_type', 'mm', 'mistery-man'),
('gravatar_type', 'identicon', 'identicon'),
('gravatar_type', 'monsterid', 'monsterid'),
('gravatar_type', 'wavatar', 'wavatar'),
('limit_session_admin_role', 'true', 'Yes'),
('limit_session_admin_role', 'false', 'No'),
('show_session_description', 'true', 'Yes'),
('show_session_description', 'false', 'No'),
('hide_certificate_export_link_students', 'true', 'Yes'),
('hide_certificate_export_link_students', 'false', 'No'),
('hide_certificate_export_link', 'true', 'Yes'),
('hide_certificate_export_link', 'false', 'No'),
('dropbox_hide_course_coach', 'true', 'Yes'),
('dropbox_hide_course_coach', 'false', 'No'),
('dropbox_hide_general_coach', 'true', 'Yes'),
('dropbox_hide_general_coach', 'false', 'No'),
('sso_force_redirect', 'true', 'Yes'),
('sso_force_redirect', 'false', 'No'),
('session_course_ordering', 'true', 'Yes'),
('session_course_ordering', 'false', 'No'),
('gamification_mode', '1', 'Yes'),
('gamification_mode', '0', 'No'),
('prevent_multiple_simultaneous_login', 'true', 'Yes'),
('prevent_multiple_simultaneous_login', 'false', 'No'),
('gradebook_detailed_admin_view', 'true', 'Yes'),
('gradebook_detailed_admin_view', 'false', 'No'),
('course_catalog_published', 'true', 'Yes'),
('course_catalog_published', 'false', 'No'),
('user_reset_password', 'true', 'Yes'),
('user_reset_password', 'false', 'No'),
('my_courses_view_by_session', 'true', 'Yes'),
('my_courses_view_by_session', 'false', 'No'),
('show_full_skill_name_on_skill_wheel', 'true', 'Yes'),
('show_full_skill_name_on_skill_wheel', 'false', 'No');

-- Version 1.11.0.1

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable)
VALUES
('messaging_allow_send_push_notification', NULL, 'radio', 'WebServices', 'false', 'MessagingAllowSendPushNotificationTitle', 'MessagingAllowSendPushNotificationComment', NULL, NULL, 0),
('messaging_gdc_project_number', NULL, 'textfield', 'WebServices', '', 'MessagingGDCProjectNumberTitle', 'MessagingGDCProjectNumberComment', NULL, NULL, 0),
('messaging_gdc_api_key', NULL, 'textfield', 'WebServices', '', 'MessagingGDCApiKeyTitle', 'MessagingGDCApiKeyComment', NULL, NULL, 0);

INSERT INTO settings_options (variable, value, display_text)
VALUES
('messaging_allow_send_push_notification', 'true', 'Yes'),
('messaging_allow_send_push_notification', 'false', 'No');

-- Version 1.11.0.2

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable)
VALUES
('teacher_can_select_course_template', NULL, 'radio', 'Course', 'true', 'TeacherCanSelectCourseTemplateTitle', 'TeacherCanSelectCourseTemplateComment', NULL, NULL, 0);

INSERT INTO settings_options (variable, value, display_text)
VALUES
('teacher_can_select_course_template', 'true', 'Yes'),
('teacher_can_select_course_template', 'false', 'No');

-- Version 1.11.0.3

INSERT INTO settings_current
(variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable)
VALUES
('enable_record_audio',NULL,'radio','Tools','true','EnableRecordAudioTitle','EnableRecordAudioComment',NULL,NULL, 0);

INSERT INTO settings_options (variable, value, display_text)
VALUES
('enable_record_audio', 'true', 'Yes'),
('enable_record_audio', 'false', 'No');

-- Version 1.11.0.4

INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, created_at)
VALUES
(1, 1, 'skype', 'Skype', 1, 1, now()),
(1, 1, 'linkedin_url', 'LinkedInUrl', 1, 1, now());

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, access_url_changeable)
VALUES
('allow_show_skype_account', NULL, 'radio', 'Platform', 'true', 'AllowShowSkypeAccountTitle', 'AllowShowSkypeAccountComment', 1),
('allow_show_linkedin_url', NULL, 'radio', 'Platform', 'true', 'AllowShowLinkedInUrlTitle', 'AllowShowLinkedInUrlComment', 1);

INSERT INTO settings_options (variable, value, display_text)
VALUES
('allow_show_skype_account', 'true', 'Yes'),
('allow_show_skype_account', 'false', 'No'),
('allow_show_linkedin_url', 'true', 'Yes'),
('allow_show_linkedin_url', 'false', 'No');

UPDATE settings_current SET selected_value = '1.11.0.4' WHERE variable = 'chamilo_database_version';

INSERT INTO settings_current (variable, type, category, selected_value, title, comment) VALUES ('enable_profile_user_address_geolocalization', 'radio', 'User', 'false', 'EnableProfileUsersAddressGeolocalizationTitle', 'EnableProfileUsersAddressGeolocalizationComment');
INSERT INTO settings_options (variable, value, display_text) VALUES ('enable_profile_user_address_geolocalization', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('enable_profile_user_address_geolocalization', 'false', 'No');

INSERT INTO settings_current (variable, type, category, selected_value, title, comment) VALUES ('show_official_code_whoisonline', 'radio', 'User', 'false', 'ShowOfficialCodeInWhoIsOnlinePage', 'ShowOfficialCodeInWhoIsOnlinePageComment');
INSERT INTO settings_options (variable, value, display_text) VALUES ('show_official_code_whoisonline', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('show_official_code_whoisonline', 'false', 'No');

INSERT INTO access_url_rel_course_category (access_url_id, course_category_id) VALUES (1, 1);
INSERT INTO access_url_rel_course_category (access_url_id, course_category_id) VALUES (1, 2);
INSERT INTO access_url_rel_course_category (access_url_id, course_category_id) VALUES (1, 3);

UPDATE settings_current SET selected_value = '1.11.0.5' WHERE variable = 'chamilo_database_version';

INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, visible_to_self, changeable, created_at) VALUES (8, 10, 'tags', 'Tags', 1, 1, NOW());

UPDATE settings_current SET selected_value = '1.11.0.6' WHERE variable = 'chamilo_database_version';

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('icons_mode_svg', NULL, 'radio', 'Tuning', 'false', 'IconsModeSVGTitle', 'IconsModeSVGComment', '', NULL, 1);
INSERT INTO settings_options (variable, value, display_text) VALUES ('icons_mode_svg', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('icons_mode_svg', 'false', 'No');

INSERT INTO branch_sync (access_url_id, branch_name, unique_id, ssl_pub_key)
VALUES
(1, 'localhost', SHA1(UUID()), SHA1(UUID()));

INSERT INTO settings_current (variable, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable)
VALUES ('allow_download_documents_by_api_key', 'radio', 'WebServices', 'false', 'AllowDownloadDocumentsByApiKeyTitle', 'AllowDownloadDocumentsByApiKeyComment', '', NULL, 1);
INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_download_documents_by_api_key', 'true', 'Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('allow_download_documents_by_api_key', 'false', 'No');

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('exercise_invisible_in_session',NULL,'radio','Session','false','ExerciseInvisibleInSessionTitle','ExerciseInvisibleInSessionComment','',NULL, 1);
INSERT INTO settings_options (variable, value, display_text) VALUES ('exercise_invisible_in_session','true','Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('exercise_invisible_in_session','false','No');

INSERT INTO settings_current (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url_changeable) VALUES ('configure_exercise_visibility_in_course',NULL,'radio','Session','false','ConfigureExerciseVisibilityInCourseTitle','ConfigureExerciseVisibilityInCourseComment','',NULL, 1);
INSERT INTO settings_options (variable, value, display_text) VALUES ('configure_exercise_visibility_in_course','true','Yes');
INSERT INTO settings_options (variable, value, display_text) VALUES ('configure_exercise_visibility_in_course','false','No');
