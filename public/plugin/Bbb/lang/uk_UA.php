<?php
/* License: see /license.txt */
// Needed in order to show the plugin title
$strings['plugin_title'] = 'Відеоконференція';
$strings['plugin_comment'] = 'Додати кімнату відеоконференції до курсу Chamilo за допомогою BigBlueButton (BBB)';

$strings['Videoconference'] = 'Відеоконференція';
$strings['MeetingOpened'] = 'Зустріч відкрита';
$strings['MeetingClosed'] = 'Зустріч закрита';
$strings['MeetingClosedComment'] = 'Якщо ви попросили записувати ваші сесії, запис буде доступний у списку нижче після повного створення.';
$strings['CloseMeeting'] = 'Закрити зустріч';

$strings['VideoConferenceXCourseX'] = 'Відеоконференція #%s курс %s';
$strings['VideoConferenceAddedToTheCalendar'] = 'Відеоконференцію додано до календаря';
$strings['VideoConferenceAddedToTheLinkTool'] = 'Відеоконференцію додано до інструменту посилань';

$strings['GoToTheVideoConference'] = 'Перейти до відеоконференції';

$strings['Records'] = 'Запис';
$strings['Meeting'] = 'Зустріч';

$strings['ViewRecord'] = 'Переглянути запис';
$strings['CopyToLinkTool'] = 'Копіювати до інструменту посилань';

$strings['EnterConference'] = 'Ввійти до відеоконференції';
$strings['RecordList'] = 'Список записів';
$strings['ServerIsNotRunning'] = 'Сервер відеоконференцій не запущено';
$strings['ServerIsNotConfigured'] = 'Сервер відеоконференцій не налаштовано';

$strings['XUsersOnLine'] = '%s користувач(ів) онлайн';

$strings['host'] = 'Хост BigBlueButton';
$strings['host_help'] = "Це назва сервера, на якому запущено ваш сервер BigBlueButton.\nМоже бути localhost, IP-адреса (наприклад, http://192.168.13.54) або доменне ім'я (наприклад, http://my.video.com).";

$strings['salt'] = 'Сіль BigBlueButton';
$strings['salt_help'] = 'Це ключ безпеки вашого сервера BigBlueButton, який дозволить серверу автентифікувати встановлення Chamilo. Зверніться до документації BigBlueButton, щоб знайти його. Спробуйте bbb-conf --salt';

$strings['big_blue_button_welcome_message'] = 'Повідомлення привітання';
$strings['enable_global_conference'] = 'Увімкнути глобальну конференцію';
$strings['enable_global_conference_per_user'] = 'Увімкнути глобальну конференцію для користувача';
$strings['enable_conference_in_course_groups'] = 'Увімкнути конференцію в групах курсу';
$strings['enable_global_conference_link'] = 'Увімкнути посилання на глобальну конференцію на головній сторінці';
$strings['disable_download_conference_link'] = 'Вимкнути завантаження конференції';
$strings['big_blue_button_record_and_store'] = 'Записувати та зберігати сесії';
$strings['bbb_enable_conference_in_groups'] = 'Дозволити конференцію в групах';
$strings['plugin_tool_bbb'] = 'Відео';
$strings['ThereAreNotRecordingsForTheMeetings'] = 'Немає записів для сесій зустрічі';
$strings['NoRecording'] = 'Немає запису';
$strings['ClickToContinue'] = 'Натисніть для продовження';
$strings['NoGroup'] = 'Немає групи';
$strings['UrlMeetingToShare'] = 'URL для поширення';
$strings['AdminView'] = 'Перегляд для адміністраторів';
$strings['max_users_limit'] = 'Ліміт максимальної кількості користувачів';
$strings['max_users_limit_help'] = 'Встановіть це значення для максимальної кількості користувачів, яку ви хочете дозволити для курсу або сесії-курсу. Залиште порожнім або встановіть 0, щоб вимкнути цей ліміт.';
$strings['MaxXUsersWarning'] = 'Ця кімната конференції має максимальну кількість %s одночасних користувачів.';
$strings['MaxXUsersReached'] = 'Досягнуто ліміт %s одночасних користувачів для цієї кімнати конференції. Зачекайте, поки звільниться одне місце, або почнеться інша конференція, щоб приєднатися.';
$strings['MaxXUsersReachedManager'] = 'Досягнуто ліміт %s одночасних користувачів для цієї кімнати конференції. Щоб збільшити цей ліміт, зверніться до адміністратора платформи.';
$strings['MaxUsersInConferenceRoom'] = 'Макс. кількість одночасних користувачів у кімнаті конференції';
$strings['global_conference_allow_roles'] = 'Посилання на глобальну конференцію видно лише для цих ролей користувачів';
$strings['CreatedAt'] = 'Створено';
$strings['allow_regenerate_recording'] = 'Дозволити повторну генерацію запису';
$strings['bbb_force_record_generation'] = 'Примусова генерація запису наприкінці зустрічі';
$strings['disable_course_settings'] = 'Вимкнути налаштування курсу';
$strings['UpdateAllCourses'] = 'Оновити всі курси';
$strings['UpdateAllCourseSettings'] = 'Оновити всі налаштування курсу';
$strings['ThisWillUpdateAllSettingsInAllCourses'] = 'Це оновить одразу всі ваші налаштування курсу.';
$strings['ThereIsNoVideoConferenceActive'] = 'Наразі немає активної відеоконференції';
$strings['RoomClosed'] = 'Кімната закрита';
$strings['RoomClosedComment'] = ' ';
$strings['meeting_duration'] = 'Тривалість зустрічі (у хвилинах)';
$strings['big_blue_button_students_start_conference_in_groups'] = 'Дозволити студентам починати конференцію в їхніх групах.';
$strings['hide_conference_link'] = 'Приховати посилання на конференцію в інструменті курсу';
$strings['hide_conference_link_comment'] = 'Показати або приховати блок з посиланням на відеоконференцію поруч з кнопкою приєднання, щоб дозволити користувачам копіювати його та вставляти в інше вікно браузера або запрошувати інших. Аутентифікація все одно буде необхідна для доступу до непублічних конференцій.';
$strings['delete_recordings_on_course_delete'] = 'Видаляти записи при видаленні курсу';
$strings['defaultVisibilityInCourseHomepage'] = 'Видимість за замовчуванням на головній сторінці курсу';
$strings['ViewActivityDashboard'] = 'Переглянути панель активності';
$strings['Participants'] = 'Учасники';
$strings['CountUsers'] = 'Підрахунок користувачів';
