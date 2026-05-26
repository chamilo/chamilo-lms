<?php
/* License: see /license.txt */
// Needed in order to show the plugin title
$strings['plugin_title'] = 'Hội nghị truyền hình';
$strings['plugin_comment'] = 'Thêm phòng hội nghị truyền hình vào khóa học Chamilo bằng BigBlueButton (BBB)';

$strings['Videoconference'] = 'Hội nghị truyền hình';
$strings['MeetingOpened'] = 'Cuộc họp đã mở';
$strings['MeetingClosed'] = 'Cuộc họp đã đóng';
$strings['MeetingClosedComment'] = 'Nếu bạn đã yêu cầu ghi phiên họp, bản ghi sẽ có sẵn trong danh sách bên dưới khi đã được tạo hoàn toàn.';
$strings['CloseMeeting'] = 'Đóng cuộc họp';

$strings['VideoConferenceXCourseX'] = 'Hội nghị truyền hình #%s khóa học %s';
$strings['VideoConferenceAddedToTheCalendar'] = 'Hội nghị truyền hình đã thêm vào lịch';
$strings['VideoConferenceAddedToTheLinkTool'] = 'Hội nghị truyền hình đã thêm vào công cụ liên kết';

$strings['GoToTheVideoConference'] = 'Vào hội nghị truyền hình';

$strings['Records'] = 'Bản ghi';
$strings['Meeting'] = 'Cuộc họp';

$strings['ViewRecord'] = 'Xem bản ghi';
$strings['CopyToLinkTool'] = 'Sao chép vào công cụ liên kết';

$strings['EnterConference'] = 'Tham gia hội nghị truyền hình';
$strings['RecordList'] = 'Danh sách bản ghi';
$strings['ServerIsNotRunning'] = 'Máy chủ hội nghị truyền hình không chạy';
$strings['ServerIsNotConfigured'] = 'Máy chủ hội nghị truyền hình chưa được cấu hình';

$strings['XUsersOnLine'] = '%s người dùng trực tuyến';

$strings['host'] = 'Máy chủ BigBlueButton';
$strings['host_help'] = 'Đây là tên máy chủ nơi máy chủ BigBlueButton của bạn đang chạy.
Có thể là localhost, địa chỉ IP (ví dụ: http://192.168.13.54) hoặc tên miền (ví dụ: http://my.video.com).';

$strings['salt'] = 'Khóa bảo mật BigBlueButton';
$strings['salt_help'] = 'Đây là khóa bảo mật của máy chủ BigBlueButton, cho phép máy chủ xác thực cài đặt Chamilo. Tham khảo tài liệu BigBlueButton để tìm vị trí. Thử bbb-conf --salt';

$strings['big_blue_button_welcome_message'] = 'Thông điệp chào mừng';
$strings['enable_global_conference'] = 'Bật hội nghị toàn cục';
$strings['enable_global_conference_per_user'] = 'Bật hội nghị toàn cục cho từng người dùng';
$strings['enable_conference_in_course_groups'] = 'Bật hội nghị trong nhóm khóa học';
$strings['enable_global_conference_link'] = 'Hiển thị liên kết hội nghị toàn cục trên trang chủ';
$strings['disable_download_conference_link'] = 'Tắt tải xuống hội nghị';
$strings['big_blue_button_record_and_store'] = 'Ghi và lưu trữ phiên họp';
$strings['bbb_enable_conference_in_groups'] = 'Cho phép hội nghị trong nhóm';
$strings['plugin_tool_bbb'] = 'Video';
$strings['ThereAreNotRecordingsForTheMeetings'] = 'Không có bản ghi cho các phiên họp';
$strings['No recording'] = 'Không có bản ghi';
$strings['ClickToContinue'] = 'Nhấp để tiếp tục';
$strings['NoGroup'] = 'Không có nhóm';
$strings['UrlMeetingToShare'] = 'URL để chia sẻ';
$strings['AdminView'] = 'Xem cho quản trị viên';
$strings['max_users_limit'] = 'Giới hạn số người dùng tối đa';
$strings['max_users_limit_help'] = 'Đặt số lượng người dùng tối đa cho phép theo khóa học hoặc phiên-khóa học. Để trống hoặc đặt 0 để tắt giới hạn này.';
$strings['MaxXUsersWarning'] = 'Phòng hội nghị này có tối đa %s người dùng đồng thời.';
$strings['MaxXUsersReached'] = 'Đã đạt giới hạn %s người dùng đồng thời cho phòng hội nghị này. Vui lòng chờ một chỗ trống hoặc chờ hội nghị khác bắt đầu để tham gia.';
$strings['MaxXUsersReachedManager'] = 'Đã đạt giới hạn %s người dùng đồng thời cho phòng hội nghị này. Để tăng giới hạn, vui lòng liên hệ quản trị viên nền tảng.';
$strings['MaxUsersInConferenceRoom'] = 'Số người dùng đồng thời tối đa trong phòng hội nghị';
$strings['global_conference_allow_roles'] = 'Liên kết hội nghị toàn cục chỉ hiển thị cho các vai trò người dùng này';
$strings['CreatedAt'] = 'Tạo lúc';
$strings['allow_regenerate_recording'] = 'Cho phép tạo lại bản ghi';
$strings['bbb_force_record_generation'] = 'Buộc tạo bản ghi khi kết thúc cuộc họp';
$strings['disable_course_settings'] = 'Tắt cài đặt khóa học';
$strings['UpdateAllCourses'] = 'Cập nhật tất cả các khóa học';
$strings['UpdateAllCourseSettings'] = 'Cập nhật tất cả cài đặt khóa học';
$strings['ThisWillUpdateAllSettingsInAllCourses'] = 'Thao tác này sẽ cập nhật cùng lúc tất cả cài đặt khóa học của bạn.';
$strings['ThereIsNoVideoConferenceActive'] = 'Hiện không có hội nghị truyền hình nào đang hoạt động';
$strings['RoomClosed'] = 'Phòng đã đóng';
$strings['RoomClosedComment'] = ' ';
$strings['meeting_duration'] = 'Thời lượng cuộc họp (tính bằng phút)';
$strings['big_blue_button_students_start_conference_in_groups'] = 'Cho phép học viên bắt đầu hội nghị trong nhóm của họ.';
$strings['hide_conference_link'] = 'Ẩn liên kết hội nghị trong công cụ khóa học';
$strings['hide_conference_link_comment'] = 'Hiển thị hoặc ẩn khối với liên kết đến hội nghị truyền hình bên cạnh nút tham gia, để cho phép người dùng sao chép và dán vào cửa sổ trình duyệt khác hoặc mời người khác. Xác thực vẫn cần thiết để truy cập các hội nghị không công khai.';
$strings['delete_recordings_on_course_delete'] = 'Xóa bản ghi khi khóa học bị xóa';
$strings['defaultVisibilityInCourseHomepage'] = 'Hiển thị mặc định trên trang chủ khóa học';
$strings['ViewActivityDashboard'] = 'Xem bảng điều khiển hoạt động';
$strings['Participants'] = 'Người tham gia';
$strings['CountUsers'] = 'Đếm người dùng';
