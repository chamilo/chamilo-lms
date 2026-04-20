<?php
/* License: see /license.txt */
// Needed in order to show the plugin title
$strings['plugin_title'] = 'וידאו-כנס';
$strings['plugin_comment'] = 'הוסף חדר וידאו-כנס בקורס Chamilo באמצעות BigBlueButton (BBB)';

$strings['Videoconference'] = 'וידאו-כנס';
$strings['MeetingOpened'] = 'פגישה נפתחה';
$strings['MeetingClosed'] = 'פגישה נסגרה';
$strings['MeetingClosedComment'] = 'אם ביקשת להקליט את המפגשים שלך, ההקלטה תהיה זמינה ברשימה למטה כאשר היא תהיה מוכנה לחלוטין.';
$strings['CloseMeeting'] = 'סגור פגישה';

$strings['VideoConferenceXCourseX'] = 'וידאו-כנס #%s קורס %s';
$strings['VideoConferenceAddedToTheCalendar'] = 'וידאו-כנס נוסף ליומן';
$strings['VideoConferenceAddedToTheLinkTool'] = 'וידאו-כנס נוסף לכלי קישורים';

$strings['GoToTheVideoConference'] = 'עבור לוידאו-כנס';

$strings['Records'] = 'הקלטה';
$strings['Meeting'] = 'פגישה';

$strings['ViewRecord'] = 'צפה בהקלטה';
$strings['CopyToLinkTool'] = 'העתק לכלי קישורים';

$strings['EnterConference'] = 'היכנס לוידאו-כנס';
$strings['RecordList'] = 'רשימת הקלטות';
$strings['ServerIsNotRunning'] = 'שרת וידאו-כנס אינו פועל';
$strings['ServerIsNotConfigured'] = 'שרת וידאו-כנס אינו מוגדר';

$strings['XUsersOnLine'] = '%s משתמשים מקוונים';

$strings['host'] = 'מארח BigBlueButton';
$strings['host_help'] = 'זהו שם השרת שבו רץ שרת BigBlueButton שלך.
יכול להיות localhost, כתובת IP (למשל http://192.168.13.54) או שם דומיין (למשל http://my.video.com).';

$strings['salt'] = 'מפתח אבטחה BigBlueButton';
$strings['salt_help'] = 'זהו מפתח האבטחה של שרת BigBlueButton שלך, שיאפשר לשרת שלך לאמת את התקנת Chamilo. עיין בתיעוד BigBlueButton כדי למצוא אותו. נסה bbb-conf --salt';

$strings['big_blue_button_welcome_message'] = 'הודעת ברוכים הבאים';
$strings['enable_global_conference'] = 'הפעל כנס גלובלי';
$strings['enable_global_conference_per_user'] = 'הפעל כנס גלובלי למשתמש';
$strings['enable_conference_in_course_groups'] = 'הפעל כנס בקבוצות קורס';
$strings['enable_global_conference_link'] = 'הפעל קישור לכנס הגלובלי בעמוד הבית';
$strings['disable_download_conference_link'] = 'השבת הורדת כנס';
$strings['big_blue_button_record_and_store'] = 'הקלט ואחסן מפגשים';
$strings['bbb_enable_conference_in_groups'] = 'אפשר כנס בקבוצות';
$strings['plugin_tool_bbb'] = 'וידאו';
$strings['ThereAreNotRecordingsForTheMeetings'] = 'אין הקלטות למפגשי הפגישות';
$strings['NoRecording'] = 'אין הקלטה';
$strings['ClickToContinue'] = 'לחץ כדי להמשיך';
$strings['NoGroup'] = 'אין קבוצה';
$strings['UrlMeetingToShare'] = 'קישור לשיתוף';
$strings['AdminView'] = 'תצוגה למנהלים';
$strings['max_users_limit'] = 'מגבלת משתמשים מקסימלית';
$strings['max_users_limit_help'] = 'הגדר זאת למספר המקסימלי של משתמשים שברצונך לאפשר לקורס או למפגש-קורס. השאר ריק או הגדר ל-0 כדי להשבית מגבלה זו.';
$strings['MaxXUsersWarning'] = 'חדר הכנס הזה כולל מספר מקסימלי של %s משתמשים בו זמנית.';
$strings['MaxXUsersReached'] = 'המגבלה של %s משתמשים בו זמנית נתקיימה עבור חדר הכנס הזה. נא המתן עד שמקום יתפנה או עד שכנס אחר יתחיל כדי להצטרף.';
$strings['MaxXUsersReachedManager'] = 'המגבלה של %s משתמשים בו זמנית נתקיימה עבור חדר הכנס הזה. כדי להגדיל מגבלה זו, נא פנה למנהל הפלטפורמה.';
$strings['MaxUsersInConferenceRoom'] = 'מספר משתמשים מקסימלי בו זמנית בחדר כנס';
$strings['global_conference_allow_roles'] = 'קישור כנס גלובלי גלוי רק לתפקידי משתמשים אלה';
$strings['CreatedAt'] = 'נוצר ב';
$strings['allow_regenerate_recording'] = 'אפשר יצירת הקלטה מחדש';
$strings['bbb_force_record_generation'] = 'כפה יצירת הקלטה בסוף הפגישה';
$strings['disable_course_settings'] = 'השבת הגדרות קורס';
$strings['UpdateAllCourses'] = 'עדכון כל הקורסים';
$strings['UpdateAllCourseSettings'] = 'עדכון כל הגדרות הקורס';
$strings['ThisWillUpdateAllSettingsInAllCourses'] = 'פעולה זו תעדכן בבת אחת את כל הגדרות הקורס שלך.';
$strings['ThereIsNoVideoConferenceActive'] = 'אין וידאו-כנס פעיל כרגע';
$strings['RoomClosed'] = 'חדר סגור';
$strings['RoomClosedComment'] = ' ';
$strings['meeting_duration'] = 'משך הפגישה (בדקות)';
$strings['big_blue_button_students_start_conference_in_groups'] = 'אפשרות לסטודנטים להתחיל כנס בקבוצות שלהם.';
$strings['hide_conference_link'] = 'הסתרת קישור הכנס בכלי הקורס';
$strings['hide_conference_link_comment'] = 'הצגה או הסתרה של בלוק עם קישור לוידאו-כנס לצד כפתור ההצטרפות, כדי לאפשר למשתמשים להעתיק אותו ולהדביק בחלון דפדפן אחר או להזמין אחרים. אימות עדיין נדרש לגישה לכנסים שאינם ציבוריים.';
$strings['delete_recordings_on_course_delete'] = 'מחיקת הקלטות כאשר הקורס מוסר';
$strings['defaultVisibilityInCourseHomepage'] = 'נראות ברירת מחדל בדף הבית של הקורס';
$strings['ViewActivityDashboard'] = 'תצוגת לוח מחוונים של פעילות';
$strings['Participants'] = 'משתתפים';
$strings['CountUsers'] = 'ספירת משתמשים';
