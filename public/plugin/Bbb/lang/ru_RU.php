<?php
/* License: see /license.txt */
// Needed in order to show the plugin title
$strings['plugin_title'] = 'Видеоконференция';
$strings['plugin_comment'] = 'Добавить комнату видеоконференции в курс Chamilo с использованием BigBlueButton (BBB)';

$strings['Videoconference'] = 'Видеоконференция';
$strings['MeetingOpened'] = 'Встреча открыта';
$strings['MeetingClosed'] = 'Встреча закрыта';
$strings['MeetingClosedComment'] = 'Если вы запросили запись сессий, запись будет доступна в списке ниже после полной генерации.';
$strings['CloseMeeting'] = 'Закрыть встречу';

$strings['VideoConferenceXCourseX'] = 'Видеоконференция #%s курс %s';
$strings['VideoConferenceAddedToTheCalendar'] = 'Видеоконференция добавлена в календарь';
$strings['VideoConferenceAddedToTheLinkTool'] = 'Видеоконференция добавлена в инструмент ссылок';

$strings['GoToTheVideoConference'] = 'Перейти к видеоконференции';

$strings['Records'] = 'Запись';
$strings['Meeting'] = 'Встреча';

$strings['ViewRecord'] = 'Просмотреть запись';
$strings['CopyToLinkTool'] = 'Копировать в инструмент ссылок';

$strings['EnterConference'] = 'Войти в видеоконференцию';
$strings['RecordList'] = 'Список записей';
$strings['ServerIsNotRunning'] = 'Сервер видеоконференций не запущен';
$strings['ServerIsNotConfigured'] = 'Сервер видеоконференций не настроен';

$strings['XUsersOnLine'] = '%s пользователь(ей) в сети';

$strings['host'] = 'Хост BigBlueButton';
$strings['host_help'] = 'Это имя сервера, на котором запущен ваш сервер BigBlueButton.
Может быть localhost, IP-адрес (например, http://192.168.13.54) или доменное имя (например, http://my.video.com).';

$strings['salt'] = 'Salt BigBlueButton';
$strings['salt_help'] = 'Это ключ безопасности вашего сервера BigBlueButton, который позволит серверу аутентифицировать установку Chamilo. Обратитесь к документации BigBlueButton для его поиска. Попробуйте bbb-conf --salt';

$strings['big_blue_button_welcome_message'] = 'Приветственное сообщение';
$strings['enable_global_conference'] = 'Включить глобальную конференцию';
$strings['enable_global_conference_per_user'] = 'Включить глобальную конференцию для каждого пользователя';
$strings['enable_conference_in_course_groups'] = 'Включить конференцию в групповых курсах';
$strings['enable_global_conference_link'] = 'Включить ссылку на глобальную конференцию на главной странице';
$strings['disable_download_conference_link'] = 'Отключить загрузку конференции';
$strings['big_blue_button_record_and_store'] = 'Записывать и хранить сессии';
$strings['bbb_enable_conference_in_groups'] = 'Разрешить конференции в группах';
$strings['plugin_tool_bbb'] = 'Видео';
$strings['ThereAreNotRecordingsForTheMeetings'] = 'Нет записей для сессий встречи';
$strings['No recording'] = 'Нет записи';
$strings['ClickToContinue'] = 'Нажмите для продолжения';
$strings['NoGroup'] = 'Нет группы';
$strings['UrlMeetingToShare'] = 'URL для распространения';
$strings['AdminView'] = 'Просмотр для администраторов';
$strings['max_users_limit'] = 'Максимум пользователей';
$strings['max_users_limit_help'] = 'Установите максимальное количество пользователей, которое вы хотите разрешить для курса или сессии-курса. Оставьте пустым или установите 0 для отключения ограничения.';
$strings['MaxXUsersWarning'] = 'Эта комната конференции имеет максимум %s одновременных пользователей.';
$strings['MaxXUsersReached'] = 'Достигнут лимит %s одновременных пользователей для этой комнаты конференции. Подождите освобождения места или начала другой конференции для присоединения.';
$strings['MaxXUsersReachedManager'] = 'Достигнут лимит %s одновременных пользователей для этой комнаты конференции. Для увеличения лимита обратитесь к администратору платформы.';
$strings['MaxUsersInConferenceRoom'] = 'Максимум одновременных пользователей в комнате конференции';
$strings['global_conference_allow_roles'] = 'Ссылка на глобальную конференцию видна только для этих ролей пользователей';
$strings['CreatedAt'] = 'Создано';
$strings['allow_regenerate_recording'] = 'Разрешить повторную генерацию записи';
$strings['bbb_force_record_generation'] = 'Принудительная генерация записи в конце встречи';
$strings['disable_course_settings'] = 'Отключить настройки курса';
$strings['UpdateAllCourses'] = 'Обновить все курсы';
$strings['UpdateAllCourseSettings'] = 'Обновить все настройки курсов';
$strings['ThisWillUpdateAllSettingsInAllCourses'] = 'Это обновит все ваши настройки курсов одновременно.';
$strings['ThereIsNoVideoConferenceActive'] = 'В настоящее время нет активной видеоконференции';
$strings['RoomClosed'] = 'Комната закрыта';
$strings['RoomClosedComment'] = ' ';
$strings['meeting_duration'] = 'Продолжительность встречи (в минутах)';
$strings['big_blue_button_students_start_conference_in_groups'] = 'Разрешить студентам начинать конференцию в их группах.';
$strings['hide_conference_link'] = 'Скрыть ссылку на конференцию в инструменте курса';
$strings['hide_conference_link_comment'] = 'Показать или скрыть блок со ссылкой на видеоконференцию рядом с кнопкой подключения, чтобы пользователи могли скопировать её и вставить в другое окно браузера или пригласить других. Аутентификация всё равно необходима для доступа к непубличным конференциям.';
$strings['delete_recordings_on_course_delete'] = 'Удалять записи при удалении курса';
$strings['defaultVisibilityInCourseHomepage'] = 'Видимость по умолчанию на главной странице курса';
$strings['ViewActivityDashboard'] = 'Просмотр панели активности';
$strings['Participants'] = 'Участники';
$strings['CountUsers'] = 'Подсчёт пользователей';
