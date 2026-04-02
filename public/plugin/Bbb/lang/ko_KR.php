<?php
/* License: see /license.txt */
// Needed in order to show the plugin title
$strings['plugin_title'] = '화상회의';
$strings['plugin_comment'] = 'BigBlueButton (BBB)을 사용하여 Chamilo 강의에 화상회의실 추가';

$strings['Videoconference'] = '화상회의';
$strings['MeetingOpened'] = '회의 개설됨';
$strings['MeetingClosed'] = '회의 종료됨';
$strings['MeetingClosedComment'] = '세션을 녹화하도록 요청한 경우, 녹화가 완전히 생성되면 아래 목록에서 이용 가능합니다.';
$strings['CloseMeeting'] = '회의 종료';

$strings['VideoConferenceXCourseX'] = '화상회의 #%s 강의 %s';
$strings['VideoConferenceAddedToTheCalendar'] = '화상회의가 달력에 추가되었습니다';
$strings['VideoConferenceAddedToTheLinkTool'] = '화상회의가 링크 도구에 추가되었습니다';

$strings['GoToTheVideoConference'] = '화상회의로 이동';

$strings['Records'] = '녹화';
$strings['Meeting'] = '회의';

$strings['ViewRecord'] = '녹화 보기';
$strings['CopyToLinkTool'] = '링크 도구로 복사';

$strings['EnterConference'] = '화상회의 입장';
$strings['RecordList'] = '녹화 목록';
$strings['ServerIsNotRunning'] = '화상회의 서버가 실행 중이 아닙니다';
$strings['ServerIsNotConfigured'] = '화상회의 서버가 구성되지 않았습니다';

$strings['XUsersOnLine'] = '%s명 사용자 온라인';

$strings['host'] = 'BigBlueButton 호스트';
$strings['host_help'] = 'BigBlueButton 서버가 실행 중인 서버 이름입니다.
localhost, IP 주소 (예: http://192.168.13.54) 또는 도메인 이름 (예: http://my.video.com)일 수 있습니다.';

$strings['salt'] = 'BigBlueButton salt';
$strings['salt_help'] = 'BigBlueButton 서버의 보안 키로, 서버가 Chamilo 설치를 인증할 수 있게 합니다. BigBlueButton 문서를 참조하여 위치를 확인하세요. bbb-conf --salt를 시도해보세요';

$strings['big_blue_button_welcome_message'] = '환영 메시지';
$strings['enable_global_conference'] = '전역 컨퍼런스 활성화';
$strings['enable_global_conference_per_user'] = '사용자별 전역 컨퍼런스 활성화';
$strings['enable_conference_in_course_groups'] = '강의 그룹에서 컨퍼런스 활성화';
$strings['enable_global_conference_link'] = '홈페이지에 전역 컨퍼런스 링크 활성화';
$strings['disable_download_conference_link'] = '컨퍼런스 다운로드 비활성화';
$strings['big_blue_button_record_and_store'] = '세션 녹화 및 저장';
$strings['bbb_enable_conference_in_groups'] = '그룹에서 컨퍼런스 허용';
$strings['plugin_tool_bbb'] = '비디오';
$strings['ThereAreNotRecordingsForTheMeetings'] = '회의 세션에 대한 녹화가 없습니다';
$strings['NoRecording'] = '녹화 없음';
$strings['ClickToContinue'] = '계속하려면 클릭하세요';
$strings['NoGroup'] = '그룹 없음';
$strings['UrlMeetingToShare'] = '공유 URL';
$strings['AdminView'] = '관리자 보기';
$strings['max_users_limit'] = '최대 사용자 수 제한';
$strings['max_users_limit_help'] = '강의 또는 세션-강의별로 허용할 최대 사용자 수로 설정하세요. 비워두거나 0으로 설정하면 이 제한이 비활성화됩니다.';
$strings['MaxXUsersWarning'] = '이 컨퍼런스 룸은 %s명의 동시 사용자를 최대 허용합니다.';
$strings['MaxXUsersReached'] = '이 컨퍼런스 룸의 %s명 동시 사용자 제한에 도달했습니다. 자리가 비거나 다른 컨퍼런스가 시작될 때까지 기다리거나 입장하세요.';
$strings['MaxXUsersReachedManager'] = '이 컨퍼런스 룸의 %s명 동시 사용자 제한에 도달했습니다. 이 제한을 늘리려면 플랫폼 관리자에게 문의하세요.';
$strings['MaxUsersInConferenceRoom'] = '컨퍼런스 룸의 최대 동시 사용자 수';
$strings['global_conference_allow_roles'] = '이 사용자 역할에만 전역 컨퍼런스 링크 표시';
$strings['CreatedAt'] = '생성일';
$strings['allow_regenerate_recording'] = '녹화 재생성 허용';
$strings['bbb_force_record_generation'] = '회의 종료 시 녹화 생성 강제';
$strings['disable_course_settings'] = '강의 설정 비활성화';
$strings['UpdateAllCourses'] = '모든 과목 업데이트';
$strings['UpdateAllCourseSettings'] = '모든 과목 설정 업데이트';
$strings['ThisWillUpdateAllSettingsInAllCourses'] = '이 작업은 모든 과목 설정을 한 번에 업데이트합니다.';
$strings['ThereIsNoVideoConferenceActive'] = '현재 활성 화상 회의가 없습니다';
$strings['RoomClosed'] = '방이 닫혔습니다';
$strings['RoomClosedComment'] = ' ';
$strings['meeting_duration'] = '회의 지속 시간 (분)';
$strings['big_blue_button_students_start_conference_in_groups'] = '학생들이 그룹에서 화상 회의를 시작할 수 있도록 허용';
$strings['hide_conference_link'] = '과목 도구에서 화상 회의 링크 숨기기';
$strings['hide_conference_link_comment'] = '참가 버튼 옆에 화상 회의 링크가 있는 블록을 표시하거나 숨깁니다. 사용자가 이를 복사하여 다른 브라우저 창에 붙여넣거나 다른 사람을 초대할 수 있습니다. 비공개 화상 회의에 접근하려면 여전히 인증이 필요합니다.';
$strings['delete_recordings_on_course_delete'] = '과목이 삭제될 때 녹화본 삭제';
$strings['defaultVisibilityInCourseHomepage'] = '과목 홈페이지의 기본 표시 여부';
$strings['ViewActivityDashboard'] = '활동 대시보드 보기';
