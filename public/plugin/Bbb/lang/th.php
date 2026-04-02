<?php
/* License: see /license.txt */
// Needed in order to show the plugin title
$strings['plugin_title'] = 'วิดีโอคอนเฟอเรนซ์';
$strings['plugin_comment'] = 'เพิ่มห้องวิดีโอคอนเฟอเรนซ์ในคอร์ส Chamilo โดยใช้ BigBlueButton (BBB)';

$strings['Videoconference'] = 'วิดีโอคอนเฟอเรนซ์';
$strings['MeetingOpened'] = 'การประชุมเปิดแล้ว';
$strings['MeetingClosed'] = 'การประชุมปิดแล้ว';
$strings['MeetingClosedComment'] = 'หากคุณได้ขอให้บันทึกเซสชัน การบันทึกจะปรากฏในรายการด้านล่างเมื่อสร้างเสร็จสมบูรณ์แล้ว';
$strings['CloseMeeting'] = 'ปิดการประชุม';

$strings['VideoConferenceXCourseX'] = 'วิดีโอคอนเฟอเรนซ์ #%s คอร์ส %s';
$strings['VideoConferenceAddedToTheCalendar'] = 'เพิ่มวิดีโอคอนเฟอเรนซ์ลงในปฏิทินแล้ว';
$strings['VideoConferenceAddedToTheLinkTool'] = 'เพิ่มวิดีโอคอนเฟอเรนซ์ลงในเครื่องมือลิงก์แล้ว';

$strings['GoToTheVideoConference'] = 'ไปที่วิดีโอคอนเฟอเรนซ์';

$strings['Records'] = 'การบันทึก';
$strings['Meeting'] = 'การประชุม';

$strings['ViewRecord'] = 'ดูการบันทึก';
$strings['CopyToLinkTool'] = 'คัดลอกไปยังเครื่องมือลิงก์';

$strings['EnterConference'] = 'เข้าสู่วิดีโอคอนเฟอเรนซ์';
$strings['RecordList'] = 'รายการการบันทึก';
$strings['ServerIsNotRunning'] = 'เซิร์ฟเวอร์วิดีโอคอนเฟอเรนซ์ไม่ได้ทำงาน';
$strings['ServerIsNotConfigured'] = 'เซิร์ฟเวอร์วิดีโอคอนเฟอเรนซ์ไม่ได้กำหนดค่า';

$strings['XUsersOnLine'] = '%s ผู้ใช้(s) ออนไลน์';

$strings['host'] = 'โฮสต์ BigBlueButton';
$strings['host_help'] = 'นี่คือชื่อเซิร์ฟเวอร์ที่เซิร์ฟเวอร์ BigBlueButton ของคุณทำงาน
อาจเป็น localhost, ที่อยู่ IP (เช่น http://192.168.13.54) หรือชื่อโดเมน (เช่น http://my.video.com)';

$strings['salt'] = 'BigBlueButton salt';
$strings['salt_help'] = 'นี่คือคีย์ความปลอดภัยของเซิร์ฟเวอร์ BigBlueButton ซึ่งจะช่วยให้เซิร์ฟเวอร์ของคุณยืนยันตัวตนการติดตั้ง Chamilo อ้างอิงเอกสาร BigBlueButton เพื่อค้นหา ลอง bbb-conf --salt';

$strings['big_blue_button_welcome_message'] = 'ข้อความต้อนรับ';
$strings['enable_global_conference'] = 'เปิดใช้งานการประชุมทั่วไป';
$strings['enable_global_conference_per_user'] = 'เปิดใช้งานการประชุมทั่วไปต่อผู้ใช้';
$strings['enable_conference_in_course_groups'] = 'เปิดใช้งานการประชุมในกลุ่มคอร์ส';
$strings['enable_global_conference_link'] = 'เปิดใช้งานลิงก์การประชุมทั่วไปในหน้าหลัก';
$strings['disable_download_conference_link'] = 'ปิดการดาวน์โหลดการประชุม';
$strings['big_blue_button_record_and_store'] = 'บันทึกและจัดเก็บเซสชัน';
$strings['bbb_enable_conference_in_groups'] = 'อนุญาตการประชุมในกลุ่ม';
$strings['plugin_tool_bbb'] = 'วิดีโอ';
$strings['ThereAreNotRecordingsForTheMeetings'] = 'ไม่มีบันทึกสำหรับเซสชันการประชุม';
$strings['NoRecording'] = 'ไม่มีบันทึก';
$strings['ClickToContinue'] = 'คลิกเพื่อดำเนินการต่อ';
$strings['NoGroup'] = 'ไม่มีกลุ่ม';
$strings['UrlMeetingToShare'] = 'URL สำหรับแชร์';
$strings['AdminView'] = 'มุมมองสำหรับผู้ดูแลระบบ';
$strings['max_users_limit'] = 'จำกัดจำนวนผู้ใช้สูงสุด';
$strings['max_users_limit_help'] = 'ตั้งค่านี้เป็นจำนวนผู้ใช้สูงสุดที่ต้องการอนุญาตต่อคอร์สหรือเซสชันคอร์ส ทิ้งว่างหรือตั้งเป็น 0 เพื่อปิดการจำกัดนี้';
$strings['MaxXUsersWarning'] = 'ห้องประชุมนี้มีจำนวนผู้ใช้พร้อมกันสูงสุด %s คน';
$strings['MaxXUsersReached'] = 'ถึงขีดจำกัดผู้ใช้พร้อมกัน %s คนสำหรับห้องประชุมนี้แล้ว โปรดรอที่นั่งว่างหรือรอการประชุมอื่นเริ่มเพื่อเข้าร่วม';
$strings['MaxXUsersReachedManager'] = 'ถึงขีดจำกัดผู้ใช้พร้อมกัน %s คนสำหรับห้องประชุมนี้แล้ว เพื่อเพิ่มขีดจำกัดนี้ โปรดติดต่อผู้ดูแลระบบแพลตฟอร์ม';
$strings['MaxUsersInConferenceRoom'] = 'ผู้ใช้พร้อมกันสูงสุดในห้องประชุม';
$strings['global_conference_allow_roles'] = 'ลิงก์การประชุมทั่วไปมองเห็นได้เฉพาะบทบาทผู้ใช้เหล่านี้';
$strings['CreatedAt'] = 'สร้างเมื่อ';
$strings['allow_regenerate_recording'] = 'อนุญาตสร้างการบันทึกใหม่';
$strings['bbb_force_record_generation'] = 'บังคับสร้างการบันทึกเมื่อสิ้นสุดการประชุม';
$strings['disable_course_settings'] = 'ปิดการตั้งค่าคอร์ส';
$strings['UpdateAllCourses'] = 'อัปเดตทุกหลักสูตร';
$strings['UpdateAllCourseSettings'] = 'อัปเดตการตั้งค่าทุกหลักสูตร';
$strings['ThisWillUpdateAllSettingsInAllCourses'] = 'การดำเนินการนี้จะอัปเดตการตั้งค่าทุกหลักสูตรของคุณในคราวเดียว';
$strings['ThereIsNoVideoConferenceActive'] = 'ไม่มีวิดีโอคอนเฟอเรนซ์ที่ใช้งานอยู่ในปัจจุบัน';
$strings['RoomClosed'] = 'ห้องปิดแล้ว';
$strings['RoomClosedComment'] = ' ';
$strings['meeting_duration'] = 'ระยะเวลาการประชุม (นาที)';
$strings['big_blue_button_students_start_conference_in_groups'] = 'อนุญาตให้นักเรียนเริ่มวิดีโอคอนเฟอเรนซ์ในกลุ่มของพวกเขา';
$strings['hide_conference_link'] = 'ซ่อนลิงก์วิดีโอคอนเฟอเรนซ์ในเครื่องมือหลักสูตร';
$strings['hide_conference_link_comment'] = 'แสดงหรือซ่อนบล็อกที่มีลิงก์ไปยังวิดีโอคอนเฟอเรนซ์ถัดจากปุ่มเข้าร่วม เพื่อให้ผู้ใช้คัดลอกและวางในหน้าต่างเบราว์เซอร์อื่นหรือเชิญผู้อื่น การยืนยันตัวตนยังคงจำเป็นสำหรับการเข้าถึงวิดีโอคอนเฟอเรนซ์ที่ไม่ใช่สาธารณะ';
$strings['delete_recordings_on_course_delete'] = 'ลบการบันทึกเมื่อลบหลักสูตร';
$strings['defaultVisibilityInCourseHomepage'] = 'การมองเห็นเริ่มต้นในหน้าหลักสูตร';
$strings['ViewActivityDashboard'] = 'ดูแดชบอร์ดกิจกรรม';
