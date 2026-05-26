<?php
/* License: see /license.txt */
// Needed in order to show the plugin title
$strings['plugin_title'] = 'ویدئوکنفرانس';
$strings['plugin_comment'] = 'افزودن اتاق ویدئوکنفرانس به دوره Chamilo با استفاده از BigBlueButton (BBB)';

$strings['Videoconference'] = 'ویدئوکنفرانس';
$strings['MeetingOpened'] = 'جلسه باز شد';
$strings['MeetingClosed'] = 'جلسه بسته شد';
$strings['MeetingClosedComment'] = 'اگر درخواست ضبط جلسات خود را داده‌اید، ضبط پس از تولید کامل در لیست زیر در دسترس خواهد بود.';
$strings['CloseMeeting'] = 'بستن جلسه';

$strings['VideoConferenceXCourseX'] = 'ویدئوکنفرانس #%s دوره %s';
$strings['VideoConferenceAddedToTheCalendar'] = 'ویدئوکنفرانس به تقویم اضافه شد';
$strings['VideoConferenceAddedToTheLinkTool'] = 'ویدئوکنفرانس به ابزار لینک اضافه شد';

$strings['GoToTheVideoConference'] = 'رفتن به ویدئوکنفرانس';

$strings['Records'] = 'ضبط';
$strings['Meeting'] = 'جلسه';

$strings['ViewRecord'] = 'مشاهده ضبط';
$strings['CopyToLinkTool'] = 'کپی به ابزار لینک';

$strings['EnterConference'] = 'ورود به ویدئوکنفرانس';
$strings['RecordList'] = 'لیست ضبط‌ها';
$strings['ServerIsNotRunning'] = 'سرور ویدئوکنفرانس در حال اجرا نیست';
$strings['ServerIsNotConfigured'] = 'سرور ویدئوکنفرانس پیکربندی نشده است';

$strings['XUsersOnLine'] = '%s کاربر آنلاین';

$strings['host'] = 'میزبان BigBlueButton';
$strings['host_help'] = 'این نام سروری است که سرور BigBlueButton شما روی آن اجرا می‌شود.
ممکن است localhost، آدرس IP (مانند http://192.168.13.54) یا نام دامنه (مانند http://my.video.com) باشد.';

$strings['salt'] = 'نمک BigBlueButton';
$strings['salt_help'] = 'این کلید امنیتی سرور BigBlueButton شماست که به سرور شما اجازه احراز هویت نصب Chamilo را می‌دهد. به مستندات BigBlueButton مراجعه کنید تا آن را پیدا کنید. امتحان کنید bbb-conf --salt';

$strings['big_blue_button_welcome_message'] = 'پیام خوش‌آمدگویی';
$strings['enable_global_conference'] = 'فعال‌سازی کنفرانس عمومی';
$strings['enable_global_conference_per_user'] = 'فعال‌سازی کنفرانس عمومی برای هر کاربر';
$strings['enable_conference_in_course_groups'] = 'فعال‌سازی کنفرانس در گروه‌های دوره';
$strings['enable_global_conference_link'] = 'فعال‌سازی لینک کنفرانس عمومی در صفحه اصلی';
$strings['disable_download_conference_link'] = 'غیرفعال‌سازی دانلود کنفرانس';
$strings['big_blue_button_record_and_store'] = 'ضبط و ذخیره جلسات';
$strings['bbb_enable_conference_in_groups'] = 'اجازه کنفرانس در گروه‌ها';
$strings['plugin_tool_bbb'] = 'ویدئو';
$strings['ThereAreNotRecordingsForTheMeetings'] = 'هیچ ضبطی برای جلسات جلسه وجود ندارد';
$strings['No recording'] = 'بدون ضبط';
$strings['ClickToContinue'] = 'کلیک برای ادامه';
$strings['NoGroup'] = 'بدون گروه';
$strings['UrlMeetingToShare'] = 'URL برای اشتراک';
$strings['AdminView'] = 'نمایش برای مدیران';
$strings['max_users_limit'] = 'حداکثر محدودیت کاربران';
$strings['max_users_limit_help'] = 'این را به حداکثر تعداد کاربرانی که می‌خواهید برای هر دوره یا جلسه-دوره مجاز کنید، تنظیم کنید. خالی بگذارید یا ۰ قرار دهید تا این محدودیت غیرفعال شود.';
$strings['MaxXUsersWarning'] = 'این اتاق کنفرانس حداکثر %s کاربر همزمان دارد.';
$strings['MaxXUsersReached'] = 'حد %s کاربر همزمان برای این اتاق کنفرانس رسیده است. لطفاً منتظر خالی شدن یک صندلی بمانید یا برای پیوستن منتظر شروع کنفرانس دیگری باشید.';
$strings['MaxXUsersReachedManager'] = 'حد %s کاربر همزمان برای این اتاق کنفرانس رسیده است. برای افزایش این حد، با مدیر پلتفرم تماس بگیرید.';
$strings['MaxUsersInConferenceRoom'] = 'حداکثر کاربران همزمان در اتاق کنفرانس';
$strings['global_conference_allow_roles'] = 'لینک کنفرانس عمومی فقط برای این نقش‌های کاربری قابل مشاهده است';
$strings['CreatedAt'] = 'ایجاد شده در';
$strings['allow_regenerate_recording'] = 'اجازه بازتولید ضبط';
$strings['bbb_force_record_generation'] = 'اجبار به تولید ضبط در پایان جلسه';
$strings['disable_course_settings'] = 'غیرفعال‌سازی تنظیمات دوره';
$strings['UpdateAllCourses'] = 'به‌روزرسانی همه درس‌ها';
$strings['UpdateAllCourseSettings'] = 'به‌روزرسانی همه تنظیمات درس';
$strings['ThisWillUpdateAllSettingsInAllCourses'] = 'این کار تمام تنظیمات درس شما را یکجا به‌روزرسانی می‌کند.';
$strings['ThereIsNoVideoConferenceActive'] = 'در حال حاضر هیچ کنفرانس ویدیویی فعالی وجود ندارد';
$strings['RoomClosed'] = 'اتاق بسته شد';
$strings['RoomClosedComment'] = ' ';
$strings['meeting_duration'] = 'مدت زمان جلسه (به دقیقه)';
$strings['big_blue_button_students_start_conference_in_groups'] = 'اجازه شروع کنفرانس به دانشجویان در گروه‌هایشان';
$strings['hide_conference_link'] = 'مخفی کردن لینک کنفرانس در ابزار درس';
$strings['hide_conference_link_comment'] = 'نمایش یا مخفی کردن بلوکی با لینک به کنفرانس ویدیویی کنار دکمه پیوستن، تا کاربران بتوانند آن را کپی کرده و در پنجره مرورگر دیگری جای‌گذاری کنند یا دیگران را دعوت کنند. احراز هویت همچنان برای دسترسی به کنفرانس‌های غیرعمومی لازم است.';
$strings['delete_recordings_on_course_delete'] = 'حذف ضبط‌ها هنگام حذف درس';
$strings['defaultVisibilityInCourseHomepage'] = 'نمایش پیش‌فرض در صفحه اصلی درس';
$strings['ViewActivityDashboard'] = 'مشاهده داشبورد فعالیت';
$strings['Participants'] = 'شرکت‌کنندگان';
$strings['CountUsers'] = 'شمارش کاربران';
