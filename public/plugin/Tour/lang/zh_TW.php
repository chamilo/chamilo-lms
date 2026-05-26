<?php

/* For licensing terms, see /license.txt */
/**
 * Strings to english L10n.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
$strings['plugin_title'] = '導覽';
$strings['plugin_comment'] = '此插件向使用者示範如何使用您的 Chamilo LMS。您必須啟用一個區域（例如「header-right」）來顯示啟動導覽的按鈕。';

/* Strings for settings */
$strings['show_tour'] = '顯示導覽';

$showTourHelpLine01 = 'The necessary configuration to show the help blocks, in JSON format, is located in the %splugin/tour/config/tour.json%s file.';
$showTourHelpLine02 = 'See README file for more information.';

$strings['show_tour_help'] = '顯示說明區塊所需的設定，以 JSON 格式，位於 <strong>plugin/tour/config/tour.json</strong> 檔案。<br>請參閱 README 檔案以取得更多資訊。';

$strings['theme'] = '主題';
$strings['theme_help'] = '選擇 <i>nassim</i>、<i>nazanin</i>、<i>royal</i>。留空使用預設主題。';

/* Strings for plugin UI */
$strings['Skip'] = '略過';
$strings['Next'] = '下一步';
$strings['Prev'] = '上一步';
$strings['Done'] = '完成';
$strings['StartButtonText'] = '開始導覽';

/* String for the steps */
// if body class = section-mycampus
$strings['TheLogoStep'] = '歡迎來到 <b>Chamilo LMS 1.9.x</b>';
$strings['TheNavbarStep'] = '連結至入口網站主要區段的選單列';
$strings['TheRightPanelStep'] = '側邊欄面板';
$strings['TheUserImageBlock'] = '您的個人檔案照片';
$strings['TheProfileBlock'] = '您的個人檔案工具：<i>收件匣</i>、<i>訊息撰寫</i>、<i>待處理邀請</i>、<i>個人檔案編輯</i>。';
$strings['TheHomePageStep'] = '這是首頁，您可在這裡找到入口網站公告、連結以及管理團隊設定的任何資訊。';

// if body class = section-mycourses
$strings['YourCoursesList'] = '此區域顯示您已註冊的課程（或班級）。若無課程顯示，請前往課程目錄（請參閱選單）或與入口網站管理員討論。';

// if body class = section-myagenda
$strings['AgendaAllowsYouToSeeWhatsHappening'] = '行事曆工具讓您查看未來數天、數週或數月的排定活動。';
$strings['AgendaTheActionBar'] = '您可使用提供的動作圖示，將活動以清單而非行事曆檢視方式顯示。';
$strings['AgendaTodayButton'] = '按一下「今天」按鈕僅查看今日排程';
$strings['AgendaTheMonthIsAlwaysInEvidence'] = '行事曆檢視中總是以醒目方式顯示當前月份';
$strings['AgendaButtonsAllowYouToChangePeriod'] = '按一下這些按鈕之一，即可切換至每日、每週或每月檢視。';

// if body class = section-session_my_space
$strings['MySpaceAllowsYouToKeepTrackOfProgress'] = '此區域讓學生查看學習進度，老師查看學生進度。';
$strings['MySpaceSectionsGiveYouImportantInsight'] = '此畫面提供的報告可擴充，並能提供學習或教學的寶貴洞察。';

// if body class = section-social-network
$strings['SocialAllowsYouToGetInTouchWithOtherUsersOfThePlatform'] = '社群區域讓您與平台上其他使用者聯繫。';
$strings['SocialMenuGivesAccessToDifferentToolsToGetInTouchOrPublishStuff'] = '選單提供一系列畫面，讓您參與私人訊息、聊天、興趣群組等。';

// if body class = section-dashboard
$strings['DashboardAllowsYouToGetVerySpecificInformationInAnIllustratedCondensedFormat'] = '儀表板以圖文並茂且精簡格式提供特定資訊。目前僅管理員可存取此功能。';
$strings['DashboardMustBeConfiguredFirstFromTheAdminSectionPluginsThenHereToEnableDesiredBlocks'] = '要啟用儀表板面板，您必須先在插件管理區段啟用可能的面板，然後返回此處選擇您要在儀表板上看到的面板。';

// if body class = section-platform_admin
$strings['AdministrationAllowsYouToManageYourPortal'] = '管理面板讓您管理 Chamilo 入口網站的所有資源。';
$strings['AdminUsersBlockAllowsYouToManageUsers'] = '使用者區塊讓您管理所有與使用者相關的事項。';
$strings['AdminCoursesBlockAllowsYouToManageCourses'] = '課程區塊提供課程建立、編輯等存取權。其他區塊也專供特定用途。';


$strings['tour_home_featured_courses_title'] = '精選課程';
$strings['tour_home_featured_courses_content'] = '此區段顯示首頁上可用的精選課程。';

$strings['tour_home_course_card_title'] = '課程卡片';
$strings['tour_home_course_card_content'] = '每個卡片摘要一個課程，並提供其主要資訊的快速存取。';

$strings['tour_home_course_title_title'] = '課程標題';
$strings['tour_home_course_title_content'] = '課程標題有助於快速辨識課程，並可能依平台設定開啟更多資訊。';

$strings['tour_home_teachers_title'] = '教師';
$strings['tour_home_teachers_content'] = '此區域顯示與課程相關聯的教師或使用者。';

$strings['tour_home_rating_title'] = '評分與回饋';
$strings['tour_home_rating_content'] = '您可在這裡檢視課程評分，並在允許時提交自己的評分。';

$strings['tour_home_main_action_title'] = '主要課程動作';
$strings['tour_home_main_action_content'] = '使用此按鈕進入課程、註冊，或檢視依課程狀態的存取限制。';

$strings['tour_home_show_more_title'] = '顯示更多課程';
$strings['tour_home_show_more_content'] = '使用此按鈕載入更多課程，並從首頁繼續探索目錄。';

$strings['tour_my_courses_cards_title'] = '您的課程卡片';
$strings['tour_my_courses_cards_content'] = '此頁面列出您已註冊的課程。每個卡片提供課程及其目前狀態的快速存取。';

$strings['tour_my_courses_image_title'] = '課程圖片';
$strings['tour_my_courses_image_content'] = '課程圖片有助於快速辨識課程。大多數情況下，按一下即可開啟課程。';

$strings['tour_my_courses_title_title'] = '課程與班次標題';
$strings['tour_my_courses_title_content'] = '在此您可以看到課程標題，以及適用時的相關班次名稱。';

$strings['tour_my_courses_progress_title'] = '學習進度';
$strings['tour_my_courses_progress_content'] = '此進度條顯示您已完成的課程比例。';

$strings['tour_my_courses_notifications_title'] = '新內容通知';
$strings['tour_my_courses_notifications_content'] = '使用此鈴鐺按鈕檢查課程是否有新內容或近期更新。亮起時，可快速發現自上次存取以來的變更。';

$strings['tour_my_courses_footer_title'] = '教師與課程詳細資料';
$strings['tour_my_courses_footer_content'] = '頁尾可顯示教師、語言及其他與課程相關的有用資訊。';

$strings['tour_my_courses_create_course_title'] = '建立課程';
$strings['tour_my_courses_create_course_content'] = '若您有建立課程權限，可使用此按鈕直接在本頁開啟課程建立表單。';

$strings['tour_course_home_header_title'] = '課程標頭';
$strings['tour_course_home_header_content'] = '此標頭顯示課程標題及適用時的目前班次，並群組本頁主要教師動作。';

$strings['tour_course_home_title_title'] = '課程標題';
$strings['tour_course_home_title_content'] = '在此可快速辨識目前課程。若課程屬於班次，則會顯示班次標題。';

$strings['tour_course_home_teacher_tools_title'] = '教師工具';
$strings['tour_course_home_teacher_tools_content'] = '依您的權限，此區域可能包含學生檢視切換、簡介編輯、報表存取及其他課程管理動作。';

$strings['tour_course_home_intro_title'] = '課程簡介';
$strings['tour_course_home_intro_content'] = '此區段顯示課程簡介。教師可用來呈現目標、指引、連結或學習者的重要資訊。';

$strings['tour_course_home_tools_controls_title'] = '工具控制';
$strings['tour_course_home_tools_controls_content'] = '教師可使用這些控制項一次顯示或隱藏所有工具，或啟用排序模式以重新組織課程工具。';

$strings['tour_course_home_tools_title'] = '課程工具';
$strings['tour_course_home_tools_content'] = '此區域包含主要課程工具，如文件、學習路徑、測驗、論壇及其他課程資源。';

$strings['tour_course_home_tool_card_title'] = '工具卡片';
$strings['tour_course_home_tool_card_content'] = '每個工具卡片提供存取一個課程工具。用來快速進入選定課程區域。';

$strings['tour_course_home_tool_shortcut_title'] = '工具捷徑';
$strings['tour_course_home_tool_shortcut_content'] = '點擊圖示區域直接開啟選定課程工具。';

$strings['tour_course_home_tool_name_title'] = '工具名稱';
$strings['tour_course_home_tool_name_content'] = '標題辨識工具，並作為直接存取連結。';

$strings['tour_course_home_tool_visibility_title'] = '工具可見性';
$strings['tour_course_home_tool_visibility_content'] = '若您正在編輯課程，此按鈕可快速變更學習者的工具可見性。';
$strings['tour_admin_overview_title'] = '管理後台';
$strings['tour_admin_overview_content'] = '此頁集中平台主要管理區域，按管理主題分組。';

$strings['tour_admin_user_management_title'] = '使用者管理';
$strings['tour_admin_user_management_content'] = '從此區塊可管理註冊使用者、建立帳戶、匯入或匯出使用者清單、編輯使用者、匿名化資料及管理班級。';

$strings['tour_admin_course_management_title'] = '課程管理';
$strings['tour_admin_course_management_content'] = '此區塊可建立及管理課程、匯入或匯出課程清單、組織分類、指派使用者至課程及設定課程相關欄位與工具。';

$strings['tour_admin_sessions_management_title'] = '班次管理';
$strings['tour_admin_sessions_management_content'] = '在此可管理訓練班次、班次分類、匯入及匯出、人力資源主管、職涯、升遷及班次相關欄位。';

$strings['tour_admin_platform_management_title'] = '平台管理';
$strings['tour_admin_platform_management_content'] = '使用此區塊全域設定平台、調整設定、管理公告、語言及其他中央管理選項。';

$strings['tour_admin_tracking_title'] = '追蹤';
$strings['tour_admin_tracking_content'] = '此區域提供報表、全域統計、學習分析及其他平台追蹤資料的存取。';

$strings['tour_admin_assessments_title'] = '評量';
$strings['tour_admin_assessments_content'] = '此區塊提供平台上評量相關的管理功能存取。';
$strings['tour_admin_skills_title'] = '技能';
$strings['tour_admin_skills_content'] = '此區塊可管理使用者技能、技能匯入、排名、等級及技能相關評量。';

$strings['tour_admin_system_title'] = '系統';
$strings['tour_admin_system_content'] = '在此可存取伺服器及平台維護工具，如系統狀態、暫存檔案清理、資料填入、電子郵件測試及技術工具。';

$strings['tour_admin_rooms_title'] = '教室';
$strings['tour_admin_rooms_content'] = '此區塊提供教室管理功能存取，包括分校、教室及教室可用性搜尋。';

$strings['tour_admin_security_title'] = '安全性';
$strings['tour_admin_security_content'] = '使用此區域檢視登入嘗試、安全性相關報告，以及平台上提供的其他安全性工具。';

$strings['tour_admin_chamilo_org_title'] = 'Chamilo.org';
$strings['tour_admin_chamilo_org_content'] = '此區塊提供官方 Chamilo 參考資料、使用指南、論壇、安裝資源，以及服務提供者和專案資訊的連結。';

$strings['tour_admin_health_check_title'] = '健康檢查';
$strings['tour_admin_health_check_content'] = '此區域透過列出環境檢查、可寫入路徑和重要安裝警告，協助您檢視平台的技術健康狀態。';

$strings['tour_admin_version_check_title'] = '版本檢查';
$strings['tour_admin_version_check_content'] = '使用此區塊註冊您的入口網站，並啟用版本檢查功能及公開平台清單選項。';

$strings['tour_admin_professional_support_title'] = '專業支援';
$strings['tour_admin_professional_support_content'] = '此區塊說明如何聯絡官方 Chamilo 提供者，以取得諮詢、主機、訓練及客製化開發支援。';

$strings['tour_admin_news_title'] = 'Chamilo 新聞';
$strings['tour_admin_news_content'] = '此區段顯示 Chamilo 專案的最新新聞與公告。';

$strings['tour_home_topbar_logo_title'] = '平台標誌';
$strings['tour_home_topbar_logo_content'] = '此標誌會帶你回到平台首頁。';
$strings['tour_home_topbar_actions_title'] = '快速操作';
$strings['tour_home_topbar_actions_content'] = '這裡可以看到捷徑圖示，例如建立課程、導引說明、工單與訊息，會依你的角色而定。';
$strings['tour_home_menu_button_title'] = '選單按鈕';
$strings['tour_home_menu_button_content'] = '使用此按鈕可快速開啟或關閉側邊選單。';
$strings['tour_home_sidebar_title'] = '主選單';
$strings['tour_home_sidebar_content'] = '此側邊選單可依你的權限存取平台的主要區域。';
$strings['tour_home_user_area_title'] = '使用者區域';
$strings['tour_home_user_area_content'] = '你可以在這裡存取個人資料、個人選項並登出。';
