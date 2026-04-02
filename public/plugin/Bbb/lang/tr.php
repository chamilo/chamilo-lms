<?php
/* License: see /license.txt */
// Needed in order to show the plugin title
$strings['plugin_title'] = 'Videokonferans';
$strings['plugin_comment'] = 'BigBlueButton (BBB) kullanarak bir Chamilo dersi içinde videokonferans odası ekleyin';

$strings['Videoconference'] = 'Videokonferans';
$strings['MeetingOpened'] = 'Toplantı açıldı';
$strings['MeetingClosed'] = 'Toplantı kapatıldı';
$strings['MeetingClosedComment'] = 'Oturumlarınızın kaydedilmesini istediyseniz, kayıt tamamen oluşturulduğunda aşağıdaki listede mevcut olacaktır.';
$strings['CloseMeeting'] = 'Toplantıyı kapat';

$strings['VideoConferenceXCourseX'] = 'Videokonferans #%s ders %s';
$strings['VideoConferenceAddedToTheCalendar'] = 'Videokonferans takvime eklendi';
$strings['VideoConferenceAddedToTheLinkTool'] = 'Videokonferans bağlantı aracına eklendi';

$strings['GoToTheVideoConference'] = 'Videokonferansa git';

$strings['Records'] = 'Kayıt';
$strings['Meeting'] = 'Toplantı';

$strings['ViewRecord'] = 'Kaydı görüntüle';
$strings['CopyToLinkTool'] = 'Bağlantı aracına kopyala';

$strings['EnterConference'] = 'Videokonferansa gir';
$strings['RecordList'] = 'Kayıt listesi';
$strings['ServerIsNotRunning'] = 'Videokonferans sunucusu çalışmıyor';
$strings['ServerIsNotConfigured'] = 'Videokonferans sunucusu yapılandırılmamış';

$strings['XUsersOnLine'] = '%s kullanıcı(s) çevrimiçi';

$strings['host'] = 'BigBlueButton sunucusu';
$strings['host_help'] = 'BigBlueButton sunucunuzun çalıştığı sunucunun adıdır.
localhost, bir IP adresi (ör. http://192.168.13.54) veya bir alan adı (ör. http://my.video.com) olabilir.';

$strings['salt'] = 'BigBlueButton tuzu';
$strings['salt_help'] = 'Bu, sunucunuzun Chamilo kurulumunu doğrulamasına izin verecek BigBlueButton sunucunuzun güvenlik anahtarıdır. Konumunu bulmak için BigBlueButton belgelerine bakın. bbb-conf --salt deneyin';

$strings['big_blue_button_welcome_message'] = 'Hoş geldiniz mesajı';
$strings['enable_global_conference'] = 'Genel konferansı etkinleştir';
$strings['enable_global_conference_per_user'] = 'Kullanıcı başına genel konferansı etkinleştir';
$strings['enable_conference_in_course_groups'] = 'Ders gruplarında konferansı etkinleştir';
$strings['enable_global_conference_link'] = 'Ana sayfada genel konferans bağlantısını etkinleştir';
$strings['disable_download_conference_link'] = 'Konferans indirmesini devre dışı bırak';
$strings['big_blue_button_record_and_store'] = 'Oturumları kaydet ve sakla';
$strings['bbb_enable_conference_in_groups'] = 'Gruplarda konferans izni ver';
$strings['plugin_tool_bbb'] = 'Video';
$strings['ThereAreNotRecordingsForTheMeetings'] = 'Toplantı oturumları için kayıt yok';
$strings['NoRecording'] = 'Kayıt yok';
$strings['ClickToContinue'] = 'Devam etmek için tıklayın';
$strings['NoGroup'] = 'Grup yok';
$strings['UrlMeetingToShare'] = 'Paylaşılacak URL';
$strings['AdminView'] = 'Yöneticiler için görüntüle';
$strings['max_users_limit'] = 'Maksimum kullanıcı limiti';
$strings['max_users_limit_help'] = 'Ders veya oturum-dersi başına izin vermek istediğiniz maksimum kullanıcı sayısını ayarlayın. Limiti devre dışı bırakmak için boş bırakın veya 0 olarak ayarlayın.';
$strings['MaxXUsersWarning'] = 'Bu konferans odasında %s eşzamanlı kullanıcı maksimumu vardır.';
$strings['MaxXUsersReached'] = 'Bu konferans odası için %s eşzamanlı kullanıcı limiti aşılmıştır. Lütfen bir koltuk boşalmasını bekleyin veya katılmak için başka bir konferansın başlamasını bekleyin.';
$strings['MaxXUsersReachedManager'] = 'Bu konferans odası için %s eşzamanlı kullanıcı limiti aşılmıştır. Bu limiti artırmak için lütfen platform yöneticisiyle iletişime geçin.';
$strings['MaxUsersInConferenceRoom'] = 'Bir konferans odasındaki maksimum eşzamanlı kullanıcılar';
$strings['global_conference_allow_roles'] = 'Genel konferans bağlantısı yalnızca bu kullanıcı rolleri için görünür';
$strings['CreatedAt'] = 'Oluşturulma tarihi';
$strings['allow_regenerate_recording'] = 'Kayıt yeniden oluşturmaya izin ver';
$strings['bbb_force_record_generation'] = 'Toplantı sonunda kayıt oluşturmayı zorla';
$strings['disable_course_settings'] = 'Ders ayarlarını devre dışı bırak';
$strings['UpdateAllCourses'] = 'Tüm dersleri güncelle';
$strings['UpdateAllCourseSettings'] = 'Tüm ders ayarlarını güncelle';
$strings['ThisWillUpdateAllSettingsInAllCourses'] = 'Bu, tüm ders ayarlarınızı bir kerede güncelleyecektir.';
$strings['ThereIsNoVideoConferenceActive'] = 'Şu anda aktif videoconference yok';
$strings['RoomClosed'] = 'Oda kapatıldı';
$strings['RoomClosedComment'] = ' ';
$strings['meeting_duration'] = 'Toplantı süresi (dakika cinsinden)';
$strings['big_blue_button_students_start_conference_in_groups'] = 'Öğrencilerin gruplarında konferansı başlatmasına izin ver.';
$strings['hide_conference_link'] = 'Ders aracında konferans bağlantısını gizle';
$strings['hide_conference_link_comment'] = 'Katılma düğmesinin yanına videoconference bağlantısına sahip bir blok göster veya gizle, böylece kullanıcılar bunu kopyalayıp başka bir tarayıcı penceresine yapıştırabilir veya başkalarını davet edebilir. Kamuya açık olmayan konferanslara erişim için kimlik doğrulama hala gereklidir.';
$strings['delete_recordings_on_course_delete'] = 'Ders kaldırıldığında kayıtları sil';
$strings['defaultVisibilityInCourseHomepage'] = 'Ders ana sayfasında varsayılan görünürlük';
$strings['ViewActivityDashboard'] = 'Etkinlik panosunu görüntüle';
