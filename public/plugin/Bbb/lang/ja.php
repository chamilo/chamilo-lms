<?php
/* License: see /license.txt */
// Needed in order to show the plugin title
$strings['plugin_title'] = 'ビデオ会議';
$strings['plugin_comment'] = 'BigBlueButton (BBB) を使用して Chamilo コースにビデオ会議室を追加';

$strings['Videoconference'] = 'ビデオ会議';
$strings['MeetingOpened'] = '会議が開始されました';
$strings['MeetingClosed'] = '会議が終了しました';
$strings['MeetingClosedComment'] = 'セッションの録画をリクエストした場合、録画が完全に生成された後に以下のリストで利用可能になります。';
$strings['CloseMeeting'] = '会議を終了';

$strings['VideoConferenceXCourseX'] = 'ビデオ会議 #%s コース %s';
$strings['VideoConferenceAddedToTheCalendar'] = 'ビデオ会議がカレンダーに追加されました';
$strings['VideoConferenceAddedToTheLinkTool'] = 'ビデオ会議がリンクツールに追加されました';

$strings['GoToTheVideoConference'] = 'ビデオ会議に参加';

$strings['Records'] = '録画';
$strings['Meeting'] = '会議';

$strings['ViewRecord'] = '録画を閲覧';
$strings['CopyToLinkTool'] = 'リンクツールにコピー';

$strings['EnterConference'] = 'ビデオ会議に入室';
$strings['RecordList'] = '録画リスト';
$strings['ServerIsNotRunning'] = 'ビデオ会議サーバーが実行されていません';
$strings['ServerIsNotConfigured'] = 'ビデオ会議サーバーが設定されていません';

$strings['XUsersOnLine'] = '%s 名のユーザーがオンライン';

$strings['host'] = 'BigBlueButton ホスト';
$strings['host_help'] = 'BigBlueButton サーバーが実行されているサーバーの名前です。
localhost、IP アドレス (例: http://192.168.13.54) またはドメイン名 (例: http://my.video.com) のいずれかです。';

$strings['salt'] = 'BigBlueButton ソルト';
$strings['salt_help'] = 'BigBlueButton サーバーのセキュリティキーです。これによりサーバーが Chamilo インストールを認証できます。BigBlueButton ドキュメントを参照して場所を確認してください。bbb-conf --salt を試してください。';

$strings['big_blue_button_welcome_message'] = 'ようこそメッセージ';
$strings['enable_global_conference'] = 'グローバル会議を有効化';
$strings['enable_global_conference_per_user'] = 'ユーザーごとのグローバル会議を有効化';
$strings['enable_conference_in_course_groups'] = 'コースグループで会議を有効化';
$strings['enable_global_conference_link'] = 'ホームページでグローバル会議へのリンクを有効化';
$strings['disable_download_conference_link'] = '会議のダウンロードを無効化';
$strings['big_blue_button_record_and_store'] = 'セッションを録画して保存';
$strings['bbb_enable_conference_in_groups'] = 'グループで会議を許可';
$strings['plugin_tool_bbb'] = 'ビデオ';
$strings['ThereAreNotRecordingsForTheMeetings'] = '会議セッションの録画はありません';
$strings['No recording'] = '録画なし';
$strings['ClickToContinue'] = '続行するにはクリック';
$strings['NoGroup'] = 'グループなし';
$strings['UrlMeetingToShare'] = '共有用 URL';
$strings['AdminView'] = '管理者向け表示';
$strings['max_users_limit'] = '最大ユーザー数制限';
$strings['max_users_limit_help'] = 'コースまたはセッションコースごとに許可する最大ユーザー数を設定します。空欄または 0 に設定するとこの制限を無効にします。';
$strings['MaxXUsersWarning'] = 'この会議室の同時ユーザー数の最大数は %s です。';
$strings['MaxXUsersReached'] = 'この会議室の同時ユーザー数の制限 %s に達しました。席が空くのを待つか、他の会議が開始するまでお待ちください。';
$strings['MaxXUsersReachedManager'] = 'この会議室の同時ユーザー数の制限 %s に達しました。この制限を増やすには、プラットフォーム管理者にお問い合わせください。';
$strings['MaxUsersInConferenceRoom'] = '会議室の最大同時ユーザー数';
$strings['global_conference_allow_roles'] = 'グローバル会議リンクをこれらのユーザー役割にのみ表示';
$strings['CreatedAt'] = '作成日時';
$strings['allow_regenerate_recording'] = '録画の再生成を許可';
$strings['bbb_force_record_generation'] = '会議終了時に録画生成を強制';
$strings['disable_course_settings'] = 'コース設定を無効化';
$strings['UpdateAllCourses'] = 'すべてのコースを更新';
$strings['UpdateAllCourseSettings'] = 'すべてのコース設定を更新';
$strings['ThisWillUpdateAllSettingsInAllCourses'] = 'これにより、すべてのコース設定が一度に更新されます。';
$strings['ThereIsNoVideoConferenceActive'] = '現在アクティブなビデオ会議はありません';
$strings['RoomClosed'] = 'ルームが閉鎖されました';
$strings['RoomClosedComment'] = ' ';
$strings['meeting_duration'] = '会議時間（分）';
$strings['big_blue_button_students_start_conference_in_groups'] = '学生がグループで会議を開始できるようにする。';
$strings['hide_conference_link'] = 'コースツールで会議リンクを非表示にする';
$strings['hide_conference_link_comment'] = '参加ボタンの横にビデオ会議へのリンクを含むブロックを表示または非表示にします。これにより、ユーザーはリンクをコピーして別のブラウザウィンドウに貼り付けたり、他者を招待したりできます。非公開の会議にアクセスするには認証が必要です。';
$strings['delete_recordings_on_course_delete'] = 'コースが削除されたときに録画を削除';
$strings['defaultVisibilityInCourseHomepage'] = 'コースホームページでのデフォルトの表示';
$strings['ViewActivityDashboard'] = 'アクティビティダッシュボードを表示';
$strings['Participants'] = '参加者';
$strings['CountUsers'] = 'ユーザー数';
