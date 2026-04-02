<?php
/* License: see /license.txt */
// Needed in order to show the plugin title
$strings['plugin_title'] = 'مؤتمر فيديو';
$strings['plugin_comment'] = 'إضافة غرفة مؤتمر فيديو في مساق Chamilo باستخدام BigBlueButton (BBB)';

$strings['Videoconference'] = 'مؤتمر فيديو';
$strings['MeetingOpened'] = 'الاجتماع مفتوح';
$strings['MeetingClosed'] = 'الاجتماع مغلق';
$strings['MeetingClosedComment'] = 'إذا طلبت تسجيل جلساتك، فسيكون التسجيل متاحًا في القائمة أدناه عند اكتمال إنشائه تمامًا.';
$strings['CloseMeeting'] = 'إغلاق الاجتماع';

$strings['VideoConferenceXCourseX'] = 'مؤتمر فيديو #%s مساق %s';
$strings['VideoConferenceAddedToTheCalendar'] = 'تم إضافة مؤتمر الفيديو إلى التقويم';
$strings['VideoConferenceAddedToTheLinkTool'] = 'تم إضافة مؤتمر الفيديو إلى أداة الروابط';

$strings['GoToTheVideoConference'] = 'الذهاب إلى مؤتمر الفيديو';

$strings['Records'] = 'تسجيل';
$strings['Meeting'] = 'اجتماع';

$strings['ViewRecord'] = 'عرض التسجيل';
$strings['CopyToLinkTool'] = 'نسخ إلى أداة الروابط';

$strings['EnterConference'] = 'الدخول إلى مؤتمر الفيديو';
$strings['RecordList'] = 'قائمة التسجيلات';
$strings['ServerIsNotRunning'] = 'خادم مؤتمر الفيديو غير يعمل';
$strings['ServerIsNotConfigured'] = 'خادم مؤتمر الفيديو غير مُهيأ';

$strings['XUsersOnLine'] = '%s مستخدم(ين) متصل(ين)';

$strings['host'] = 'مضيف BigBlueButton';
$strings['host_help'] = 'هذا هو اسم الخادم الذي يعمل عليه خادم BigBlueButton الخاص بك.
قد يكون localhost، أو عنوان IP (مثل http://192.168.13.54) أو اسم نطاق (مثل http://my.video.com).';

$strings['salt'] = 'ملح BigBlueButton';
$strings['salt_help'] = 'هذا هو مفتاح الأمان لخادم BigBlueButton الخاص بك، والذي سيسمح لخادمك بتوثيق تثبيت Chamilo. راجع وثائق BigBlueButton للعثور عليه. جرب bbb-conf --salt';

$strings['big_blue_button_welcome_message'] = 'رسالة الترحيب';
$strings['enable_global_conference'] = 'تفعيل المؤتمر العام';
$strings['enable_global_conference_per_user'] = 'تفعيل المؤتمر العام لكل مستخدم';
$strings['enable_conference_in_course_groups'] = 'تفعيل المؤتمر في مجموعات المساق';
$strings['enable_global_conference_link'] = 'تفعيل الرابط إلى المؤتمر العام في الصفحة الرئيسية';
$strings['disable_download_conference_link'] = 'تعطيل تنزيل المؤتمر';
$strings['big_blue_button_record_and_store'] = 'تسجيل الجلسات وحفظها';
$strings['bbb_enable_conference_in_groups'] = 'السماح بالمؤتمر في المجموعات';
$strings['plugin_tool_bbb'] = 'فيديو';
$strings['ThereAreNotRecordingsForTheMeetings'] = 'لا توجد تسجيلات لجلسات الاجتماع';
$strings['NoRecording'] = 'لا تسجيل';
$strings['ClickToContinue'] = 'انقر للمتابعة';
$strings['NoGroup'] = 'لا مجموعة';
$strings['UrlMeetingToShare'] = 'رابط المشاركة';
$strings['AdminView'] = 'عرض للمشرفين';
$strings['max_users_limit'] = 'حد أقصى للمستخدمين';
$strings['max_users_limit_help'] = 'اضبط هذا على الحد الأقصى لعدد المستخدمين الذين تريد السماح بهم لكل مساق أو جلسة-مساق. اتركه فارغًا أو اضبطه على 0 لتعطيل هذا الحد.';
$strings['MaxXUsersWarning'] = 'هذه الغرفة لها حد أقصى قدره %s مستخدم متزامن.';
$strings['MaxXUsersReached'] = 'تم الوصول إلى حد %s مستخدم متزامن لهذه الغرفة. يرجى الانتظار حتى يُفرغ مقعد أو يبدأ مؤتمر آخر للانضمام.';
$strings['MaxXUsersReachedManager'] = 'تم الوصول إلى حد %s مستخدم متزامن لهذه الغرفة. لزيادة هذا الحد، يرجى الاتصال بمشرف المنصة.';
$strings['MaxUsersInConferenceRoom'] = 'أقصى عدد مستخدمين متزامنين في غرفة المؤتمر';
$strings['global_conference_allow_roles'] = 'رابط المؤتمر العام مرئي فقط لهذه أدوار المستخدمين';
$strings['CreatedAt'] = 'تم إنشاؤه في';
$strings['allow_regenerate_recording'] = 'السماح بإعادة توليد التسجيل';
$strings['bbb_force_record_generation'] = 'فرض توليد التسجيل في نهاية الاجتماع';
$strings['disable_course_settings'] = 'تعطيل إعدادات المساق';
$strings['UpdateAllCourses'] = 'تحديث جميع المقررات الدراسية';
$strings['UpdateAllCourseSettings'] = 'تحديث جميع إعدادات المقرر الدراسي';
$strings['ThisWillUpdateAllSettingsInAllCourses'] = 'سيقوم هذا بتحديث جميع إعدادات مقررك الدراسي دفعة واحدة.';
$strings['ThereIsNoVideoConferenceActive'] = 'لا يوجد مؤتمر فيديو نشط حالياً';
$strings['RoomClosed'] = 'الغرفة مغلقة';
$strings['RoomClosedComment'] = ' ';
$strings['meeting_duration'] = 'مدة الاجتماع (بالدقائق)';
$strings['big_blue_button_students_start_conference_in_groups'] = 'السماح للطلاب ببدء المؤتمر في مجموعاتهم.';
$strings['hide_conference_link'] = 'إخفاء رابط المؤتمر في أداة المقرر';
$strings['hide_conference_link_comment'] = 'إظهار أو إخفاء كتلة تحتوي على رابط المؤتمر الفيديو بجانب زر الانضمام، للسماح للمستخدمين بنسخه ولصقه في نافذة متصفح أخرى أو دعوة الآخرين. سيظل التحقق من الهوية مطلوباً للوصول إلى المؤتمرات غير العامة.';
$strings['delete_recordings_on_course_delete'] = 'حذف التسجيلات عند إزالة المقرر الدراسي';
$strings['defaultVisibilityInCourseHomepage'] = 'الرؤية الافتراضية في صفحة رئيسية المقرر';
$strings['ViewActivityDashboard'] = 'عرض لوحة تحكم النشاط';
