<?php
/* License: see /license.txt */
// Needed in order to show the plugin title
$strings['plugin_title'] = 'Видеоконференция';
$strings['plugin_comment'] = 'Добавяне на стая за видеоконференция в курс на Chamilo с BigBlueButton (BBB)';

$strings['Videoconference'] = 'Видеоконференция';
$strings['MeetingOpened'] = 'Срещата е отворена';
$strings['MeetingClosed'] = 'Срещата е затворена';
$strings['MeetingClosedComment'] = 'Ако сте поискали запис на сесиите си, записът ще бъде наличен в списъка по-долу, когато бъде напълно генериран.';
$strings['CloseMeeting'] = 'Затваряне на срещата';

$strings['VideoConferenceXCourseX'] = 'Видеоконференция #%s курс %s';
$strings['VideoConferenceAddedToTheCalendar'] = 'Видеоконференцията е добавена към календара';
$strings['VideoConferenceAddedToTheLinkTool'] = 'Видеоконференцията е добавена към инструмента за връзки';

$strings['GoToTheVideoConference'] = 'Отиди на видеоконференцията';

$strings['Records'] = 'Запис';
$strings['Meeting'] = 'Среща';

$strings['ViewRecord'] = 'Преглед на записа';
$strings['CopyToLinkTool'] = 'Копиране към инструмента за връзки';

$strings['EnterConference'] = 'Влезте в видеоконференцията';
$strings['RecordList'] = 'Списък със записи';
$strings['ServerIsNotRunning'] = 'Сървърът за видеоконференции не работи';
$strings['ServerIsNotConfigured'] = 'Сървърът за видеоконференции не е конфигуриран';

$strings['XUsersOnLine'] = '%s потребител(и) онлайн';

$strings['host'] = 'BigBlueButton хост';
$strings['host_help'] = 'Това е името на сървъра, където работи вашият BigBlueButton сървър.
Може да бъде localhost, IP адрес (напр. http://192.168.13.54) или домейн име (напр. http://my.video.com).';

$strings['salt'] = 'BigBlueButton salt';
$strings['salt_help'] = 'Това е ключът за сигурност на вашия BigBlueButton сървър, който ще позволи на сървъра да удостовери инсталацията на Chamilo. Обърнете се към документацията на BigBlueButton, за да го намерите. Опитайте bbb-conf --salt';

$strings['big_blue_button_welcome_message'] = 'Добре дошли съобщение';
$strings['enable_global_conference'] = 'Включване на глобална конференция';
$strings['enable_global_conference_per_user'] = 'Включване на глобална конференция на потребител';
$strings['enable_conference_in_course_groups'] = 'Включване на конференция в курсови групи';
$strings['enable_global_conference_link'] = 'Включване на връзката към глобалната конференция на началната страница';
$strings['disable_download_conference_link'] = 'Забраняване на изтегляне на конференция';
$strings['big_blue_button_record_and_store'] = 'Запис и съхранение на сесиите';
$strings['bbb_enable_conference_in_groups'] = 'Разрешаване на конференции в групи';
$strings['plugin_tool_bbb'] = 'Видео';
$strings['ThereAreNotRecordingsForTheMeetings'] = 'Няма записи за сесиите на срещата';
$strings['NoRecording'] = 'Няма запис';
$strings['ClickToContinue'] = 'Кликнете за продължение';
$strings['NoGroup'] = 'Няма група';
$strings['UrlMeetingToShare'] = 'URL за споделяне';
$strings['AdminView'] = 'Преглед за администратори';
$strings['max_users_limit'] = 'Максимален брой потребители';
$strings['max_users_limit_help'] = 'Задайте максималния брой потребители, които искате да разрешите за курс или сесия-курс. Оставете празно или задайте на 0, за да забраните този лимит.';
$strings['MaxXUsersWarning'] = 'Тази конференцна стая има максимален брой от %s едновременни потребители.';
$strings['MaxXUsersReached'] = 'Лимитът от %s едновременни потребители е достигнат за тази конференцна стая. Моля, изчакайте да се освободи място или започване на друга конференция, за да се присъедините.';
$strings['MaxXUsersReachedManager'] = 'Лимитът от %s едновременни потребители е достигнат за тази конференцна стая. За да увеличите този лимит, моля, свържете се с администратора на платформата.';
$strings['MaxUsersInConferenceRoom'] = 'Максимален брой едновременни потребители в конференцна стая';
$strings['global_conference_allow_roles'] = 'Връзката към глобалната конференция е видима само за тези роли на потребители';
$strings['CreatedAt'] = 'Създадено на';
$strings['allow_regenerate_recording'] = 'Разрешаване на регенериране на запис';
$strings['bbb_force_record_generation'] = 'Принудително генериране на запис в края на срещата';
$strings['disable_course_settings'] = 'Забраняване на настройките на курса';
$strings['UpdateAllCourses'] = 'Обновяване на всички курсове';
$strings['UpdateAllCourseSettings'] = 'Обновяване на настройките на всички курсове';
$strings['ThisWillUpdateAllSettingsInAllCourses'] = 'Това ще обнови наведнъж всички настройки на курсовете ви.';
$strings['ThereIsNoVideoConferenceActive'] = 'В момента няма активно видеоконференция';
$strings['RoomClosed'] = 'Стаята е затворена';
$strings['RoomClosedComment'] = ' ';
$strings['meeting_duration'] = 'Продължителност на срещата (в минути)';
$strings['big_blue_button_students_start_conference_in_groups'] = 'Разреши на студентите да започват конференция в техните групи.';
$strings['hide_conference_link'] = 'Скрий връзката към конференцията в инструментите на курса';
$strings['hide_conference_link_comment'] = 'Покажи или скрий блок с връзка към видеоконференцията до бутона за присъединяване, за да позволиш на потребителите да я копират и поставят в друг прозорец на браузъра или да поканят други. Удостоверяването все още ще е необходимо за достъп до непублични конференции.';
$strings['delete_recordings_on_course_delete'] = 'Изтрий записите при премахване на курса';
$strings['defaultVisibilityInCourseHomepage'] = 'Стандартна видимост на началната страница на курса';
$strings['ViewActivityDashboard'] = 'Преглед на таблото за активност';
$strings['Participants'] = 'Участници';
$strings['CountUsers'] = 'Брой потребители';
