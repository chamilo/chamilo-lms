<?php

/* For licensing terms, see /license.txt */
/**
 * Strings to english L10n.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
$strings['plugin_title'] = '导览';
$strings['plugin_comment'] = '此插件向人们展示如何使用您的 Chamilo LMS。您必须激活一个区域（例如“header-right”）来显示启动导览的按钮。';

/* Strings for settings */
$strings['show_tour'] = '显示导览';

$showTourHelpLine01 = 'The necessary configuration to show the help blocks, in JSON format, is located in the %splugin/tour/config/tour.json%s file.';
$showTourHelpLine02 = 'See README file for more information.';

$strings['show_tour_help'] = '显示帮助块所需的配置，以 JSON 格式位于 <strong>plugin/tour/config/tour.json</strong> 文件中。<br>请参阅 README 文件获取更多信息。';

$strings['theme'] = '主题';
$strings['theme_help'] = '选择 <i>nassim</i>、<i>nazanin</i>、<i>royal</i>。留空使用默认主题。';

/* Strings for plugin UI */
$strings['Skip'] = '跳过';
$strings['Next'] = '下一步';
$strings['Prev'] = '上一步';
$strings['Done'] = '完成';
$strings['StartButtonText'] = '开始导览';

/* String for the steps */
// if body class = section-mycampus
$strings['TheLogoStep'] = '欢迎来到 <b>Chamilo LMS 1.9.x</b>';
$strings['TheNavbarStep'] = '菜单栏，链接到门户的主要部分';
$strings['TheRightPanelStep'] = '侧边栏面板';
$strings['TheUserImageBlock'] = '您的个人资料照片';
$strings['TheProfileBlock'] = '您的个人资料工具：<i>收件箱</i>、<i>消息撰写</i>、<i>待处理邀请</i>、<i>个人资料编辑</i>。';
$strings['TheHomePageStep'] = '这是初始主页，您将在这里找到门户公告、链接和管理团队配置的任何信息。';

// if body class = section-mycourses
$strings['YourCoursesList'] = '此区域显示您已订阅的不同课程（或会话）。如果没有课程显示，请转到课程目录（见菜单）或与您的门户管理员讨论。';

// if body class = section-myagenda
$strings['AgendaAllowsYouToSeeWhatsHappening'] = '议程工具允许您查看未来几天、周或月的计划事件。';
$strings['AgendaTheActionBar'] = '您可以使用提供的操作图标决定将事件显示为列表，而不是日历视图。';
$strings['AgendaTodayButton'] = '点击“今天”按钮仅查看今天的日程。';
$strings['AgendaTheMonthIsAlwaysInEvidence'] = '日历视图中始终突出显示当前月份。';
$strings['AgendaButtonsAllowYouToChangePeriod'] = '点击这些按钮之一可切换到每日、周或月视图。';

// if body class = section-session_my_space
$strings['MySpaceAllowsYouToKeepTrackOfProgress'] = '此区域允许您检查作为学生的进度，或作为教师检查学生的进度。';
$strings['MySpaceSectionsGiveYouImportantInsight'] = '此屏幕提供的报告是可扩展的，可为您提供学习或教学的宝贵洞察。';

// if body class = section-social-network
$strings['SocialAllowsYouToGetInTouchWithOtherUsersOfThePlatform'] = '社交区域允许您与平台上的其他用户联系。';
$strings['SocialMenuGivesAccessToDifferentToolsToGetInTouchOrPublishStuff'] = '菜单为您提供一系列屏幕的访问权限，允许您参与私人消息、聊天、兴趣小组等。';

// if body class = section-dashboard
$strings['DashboardAllowsYouToGetVerySpecificInformationInAnIllustratedCondensedFormat'] = '仪表板允许您以图示和精简格式获取非常具体的信息。目前只有管理员可以访问此功能。';
$strings['DashboardMustBeConfiguredFirstFromTheAdminSectionPluginsThenHereToEnableDesiredBlocks'] = '要启用仪表板面板，您必须先在插件的管理员部分激活可能的面板，然后返回此处选择您想在仪表板上看到的面板。';

// if body class = section-platform_admin
$strings['AdministrationAllowsYouToManageYourPortal'] = '管理面板允许您管理 Chamilo 门户中的所有资源。';
$strings['AdminUsersBlockAllowsYouToManageUsers'] = '用户块允许您管理与用户相关的所有事项。';
$strings['AdminCoursesBlockAllowsYouToManageCourses'] = '课程块为您提供课程创建、编辑等的访问权限。其他块也专用于特定用途。';


$strings['tour_home_featured_courses_title'] = '精选课程';
$strings['tour_home_featured_courses_content'] = '此部分显示主页上可用的精选课程。';

$strings['tour_home_course_card_title'] = '课程卡片';
$strings['tour_home_course_card_content'] = '每张卡片总结一门课程，并为您提供其主要信息的快速访问。';

$strings['tour_home_course_title_title'] = '课程标题';
$strings['tour_home_course_title_content'] = '课程标题帮助您快速识别课程，并可能根据平台设置打开更多信息。';

$strings['tour_home_teachers_title'] = '教师';
$strings['tour_home_teachers_content'] = '此区域显示与课程关联的教师或用户。';

$strings['tour_home_rating_title'] = '评分和反馈';
$strings['tour_home_rating_content'] = '在这里您可以查看课程评分，并在允许时提交自己的投票。';

$strings['tour_home_main_action_title'] = '主要课程操作';
$strings['tour_home_main_action_content'] = '使用此按钮进入课程、订阅或查看课程状态的访问限制。';

$strings['tour_home_show_more_title'] = '显示更多课程';
$strings['tour_home_show_more_content'] = '使用此按钮加载更多课程，并从主页继续探索目录。';

$strings['tour_my_courses_cards_title'] = '您的课程卡片';
$strings['tour_my_courses_cards_content'] = '此页面列出您已订阅的课程。每张卡片为您提供课程及其当前状态的快速访问。';

$strings['tour_my_courses_image_title'] = '课程图片';
$strings['tour_my_courses_image_content'] = '课程图片帮助您快速识别课程。在大多数情况下，点击它会打开课程。';

$strings['tour_my_courses_title_title'] = '课程和会话标题';
$strings['tour_my_courses_title_content'] = '这里您可以看到课程标题，以及适用的会话名称。';

$strings['tour_my_courses_progress_title'] = '学习进度';
$strings['tour_my_courses_progress_content'] = '此进度条显示您已完成的课程比例。';

$strings['tour_my_courses_notifications_title'] = '新内容通知';
$strings['tour_my_courses_notifications_content'] = '使用此铃铛按钮检查课程是否有新内容或最近更新。高亮时，帮助您快速发现自上次访问以来的变化。';

$strings['tour_my_courses_footer_title'] = '教师和课程详情';
$strings['tour_my_courses_footer_content'] = '页脚可显示教师、语言和其他与课程相关的有用信息。';

$strings['tour_my_courses_create_course_title'] = '创建课程';
$strings['tour_my_courses_create_course_content'] = '如果您有创建课程权限，使用此按钮直接从此页面打开课程创建表单。';

$strings['tour_course_home_header_title'] = '课程头部';
$strings['tour_course_home_header_content'] = '此头部显示课程标题及适用的活动会话，还分组显示本页面主要教师操作。';

$strings['tour_course_home_title_title'] = '课程标题';
$strings['tour_course_home_title_content'] = '这里您可以快速识别当前课程。如果课程属于会话，则会话标题显示在其旁边。';

$strings['tour_course_home_teacher_tools_title'] = '教师工具';
$strings['tour_course_home_teacher_tools_content'] = '根据您的权限，此区域可能包括学生视图切换、介绍编辑、报告访问和其他课程管理操作。';

$strings['tour_course_home_intro_title'] = '课程介绍';
$strings['tour_course_home_intro_content'] = '此部分显示课程介绍。教师可用于呈现目标、指导、链接或学习者关键信息。';

$strings['tour_course_home_tools_controls_title'] = '工具控件';
$strings['tour_course_home_tools_controls_content'] = '教师可使用这些控件一次性显示或隐藏所有工具，或启用排序模式重新组织课程工具。';

$strings['tour_course_home_tools_title'] = '课程工具';
$strings['tour_course_home_tools_content'] = '此区域包含主要课程工具，如文档、学习路径、练习、论坛和其他课程资源。';

$strings['tour_course_home_tool_card_title'] = '工具卡片';
$strings['tour_course_home_tool_card_content'] = '每个工具卡片提供对一个课程工具的访问。使用它快速进入选定课程区域。';

$strings['tour_course_home_tool_shortcut_title'] = '工具快捷方式';
$strings['tour_course_home_tool_shortcut_content'] = '点击图标区域直接打开选定课程工具。';

$strings['tour_course_home_tool_name_title'] = '工具名称';
$strings['tour_course_home_tool_name_content'] = '标题标识工具，并作为直接访问链接。';

$strings['tour_course_home_tool_visibility_title'] = '工具可见性';
$strings['tour_course_home_tool_visibility_content'] = '如果您正在编辑课程，此按钮让您快速更改学习者对工具的可见性。';
$strings['tour_admin_overview_title'] = '管理仪表板';
$strings['tour_admin_overview_content'] = '此页面集中平台主要管理区域，按管理主题分组。';

$strings['tour_admin_user_management_title'] = '用户管理';
$strings['tour_admin_user_management_content'] = '从此模块您可以管理注册用户、创建账户、导入或导出用户列表、编辑用户、匿名化数据和管理班级。';

$strings['tour_admin_course_management_title'] = '课程管理';
$strings['tour_admin_course_management_content'] = '此模块让您创建和管理课程、导入或导出课程列表、组织类别、分配用户到课程并配置课程相关字段和工具。';

$strings['tour_admin_sessions_management_title'] = '会话管理';
$strings['tour_admin_sessions_management_content'] = '这里您可以管理培训会话、会话类别、导入和导出、HR主管、职业、晋升和会话相关字段。';

$strings['tour_admin_platform_management_title'] = '平台管理';
$strings['tour_admin_platform_management_content'] = '使用此模块全局配置平台、调整设置、管理公告、语言和其他中央管理选项。';

$strings['tour_admin_tracking_title'] = '跟踪';
$strings['tour_admin_tracking_content'] = '此区域提供对报告、全局统计、学习分析和其他平台跟踪数据的访问。';

$strings['tour_admin_assessments_title'] = '评估';
$strings['tour_admin_assessments_content'] = '此模块提供平台上评估相关管理功能的访问。';
$strings['tour_admin_skills_title'] = '技能';
$strings['tour_admin_skills_content'] = '此模块让您管理用户技能、技能导入、排名、级别和技能相关评估。';

$strings['tour_admin_system_title'] = '系统';
$strings['tour_admin_system_content'] = '这里您可以访问服务器和平台维护工具，如系统状态、临时文件清理、数据填充、电子邮件测试和技术实用工具。';

$strings['tour_admin_rooms_title'] = '教室';
$strings['tour_admin_rooms_content'] = '此模块提供教室管理功能访问，包括分支、教室和教室可用性搜索。';

$strings['tour_admin_security_title'] = '安全';
$strings['tour_admin_security_content'] = '使用此区域查看登录尝试、安全相关报告以及平台上可用的其他安全工具。';

$strings['tour_admin_chamilo_org_title'] = 'Chamilo.org';
$strings['tour_admin_chamilo_org_content'] = '此模块提供官方 Chamilo 参考资料、用户指南、论坛、安装资源以及服务提供商和项目信息的链接。';

$strings['tour_admin_health_check_title'] = '健康检查';
$strings['tour_admin_health_check_content'] = '此区域通过列出环境检查、可写路径和重要安装警告，帮助您审查平台的运行状况。';

$strings['tour_admin_version_check_title'] = '版本检查';
$strings['tour_admin_version_check_content'] = '使用此模块注册您的门户网站，并启用版本检查功能和公共平台列表选项。';

$strings['tour_admin_professional_support_title'] = '专业支持';
$strings['tour_admin_professional_support_content'] = '此模块说明如何联系官方 Chamilo 提供商，以获取咨询、托管、培训和定制开发支持。';

$strings['tour_admin_news_title'] = 'Chamilo 新闻';
$strings['tour_admin_news_content'] = '此部分显示 Chamilo 项目的最新新闻和公告。';
