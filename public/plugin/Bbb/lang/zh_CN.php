<?php
/* License: see /license.txt */
// Needed in order to show the plugin title
$strings['plugin_title'] = '视频会议';
$strings['plugin_comment'] = '使用 BigBlueButton (BBB) 在 Chamilo 课程中添加视频会议室';

$strings['Videoconference'] = '视频会议';
$strings['MeetingOpened'] = '会议已开启';
$strings['MeetingClosed'] = '会议已关闭';
$strings['MeetingClosedComment'] = '如果您要求录制会话，录制文件将在完全生成后出现在下面的列表中。';
$strings['CloseMeeting'] = '关闭会议';

$strings['VideoConferenceXCourseX'] = '视频会议 #%s 课程 %s';
$strings['VideoConferenceAddedToTheCalendar'] = '视频会议已添加到日历';
$strings['VideoConferenceAddedToTheLinkTool'] = '视频会议已添加到链接工具';

$strings['GoToTheVideoConference'] = '进入视频会议';

$strings['Records'] = '录制';
$strings['Meeting'] = '会议';

$strings['ViewRecord'] = '查看录制';
$strings['CopyToLinkTool'] = '复制到链接工具';

$strings['EnterConference'] = '进入视频会议';
$strings['RecordList'] = '录制列表';
$strings['ServerIsNotRunning'] = '视频会议服务器未运行';
$strings['ServerIsNotConfigured'] = '视频会议服务器未配置';

$strings['XUsersOnLine'] = '%s 名用户在线';

$strings['host'] = 'BigBlueButton 主机';
$strings['host_help'] = '这是运行 BigBlueButton 服务器的服务器名称。
可能是 localhost、IP 地址（例如 http://192.168.13.54）或域名（例如 http://my.video.com）。';

$strings['salt'] = 'BigBlueButton 密钥';
$strings['salt_help'] = '这是 BigBlueButton 服务器的安全密钥，用于验证 Chamilo 安装。请参阅 BigBlueButton 文档查找。尝试 bbb-conf --salt';

$strings['big_blue_button_welcome_message'] = '欢迎消息';
$strings['enable_global_conference'] = '启用全局会议';
$strings['enable_global_conference_per_user'] = '启用用户全局会议';
$strings['enable_conference_in_course_groups'] = '在课程组中启用会议';
$strings['enable_global_conference_link'] = '在首页启用全局会议链接';
$strings['disable_download_conference_link'] = '禁用会议下载';
$strings['big_blue_button_record_and_store'] = '录制并存储会话';
$strings['bbb_enable_conference_in_groups'] = '允许组会议';
$strings['plugin_tool_bbb'] = '视频';
$strings['ThereAreNotRecordingsForTheMeetings'] = '会议会话无录制';
$strings['NoRecording'] = '无录制';
$strings['ClickToContinue'] = '点击继续';
$strings['NoGroup'] = '无组';
$strings['UrlMeetingToShare'] = '分享 URL';
$strings['AdminView'] = '管理员视图';
$strings['max_users_limit'] = '最大用户数限制';
$strings['max_users_limit_help'] = '设置为每个课程或会话-课程允许的最大用户数。留空或设置为 0 以禁用此限制。';
$strings['MaxXUsersWarning'] = '此会议室最多允许 %s 名同时用户。';
$strings['MaxXUsersReached'] = '此会议室已达到 %s 名同时用户的限制。请等待一个席位空出，或等待另一个会议开始以加入。';
$strings['MaxXUsersReachedManager'] = '此会议室已达到 %s 名同时用户的限制。要增加此限制，请联系平台管理员。';
$strings['MaxUsersInConferenceRoom'] = '会议室最大同时用户数';
$strings['global_conference_allow_roles'] = '全局会议链接仅对这些用户角色可见';
$strings['CreatedAt'] = '创建时间';
$strings['allow_regenerate_recording'] = '允许重新生成录制';
$strings['bbb_force_record_generation'] = '会议结束时强制生成录制';
$strings['disable_course_settings'] = '禁用课程设置';
$strings['UpdateAllCourses'] = '更新所有课程';
$strings['UpdateAllCourseSettings'] = '更新所有课程设置';
$strings['ThisWillUpdateAllSettingsInAllCourses'] = '这将一次性更新您的所有课程设置。';
$strings['ThereIsNoVideoConferenceActive'] = '当前没有活跃的视频会议';
$strings['RoomClosed'] = '房间已关闭';
$strings['RoomClosedComment'] = ' ';
$strings['meeting_duration'] = '会议时长（分钟）';
$strings['big_blue_button_students_start_conference_in_groups'] = '允许学生在他们的组中启动会议。';
$strings['hide_conference_link'] = '在课程工具中隐藏会议链接';
$strings['hide_conference_link_comment'] = '显示或隐藏加入按钮旁边的视频会议链接块，允许用户复制并粘贴到另一个浏览器窗口或邀请他人。访问非公开会议仍需身份验证。';
$strings['delete_recordings_on_course_delete'] = '课程删除时删除录像';
$strings['defaultVisibilityInCourseHomepage'] = '课程首页默认可见性';
$strings['ViewActivityDashboard'] = '查看活动仪表板';
$strings['Participants'] = '参与者';
$strings['CountUsers'] = '统计用户';
