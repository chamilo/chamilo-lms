<?php

/* For licensing terms, see /license.txt */
/**
 * Strings to english L10n.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
$strings['plugin_title'] = 'ツアー';
$strings['plugin_comment'] = 'このプラグインは、Chamilo LMSの使い方をユーザーに示します。ツアーを開始するボタンを表示するには、地域（例：「header-right」）を1つ有効化する必要があります。';

/* Strings for settings */
$strings['show_tour'] = 'ツアーを表示';

$showTourHelpLine01 = 'The necessary configuration to show the help blocks, in JSON format, is located in the %splugin/tour/config/tour.json%s file.';
$showTourHelpLine02 = 'See README file for more information.';

$strings['show_tour_help'] = 'ヘルプブロックを表示するための必要な設定は、JSON形式で<strong>plugin/tour/config/tour.json</strong>ファイルにあります。<br>詳細はREADMEファイルをご覧ください。';

$strings['theme'] = 'テーマ';
$strings['theme_help'] = '<i>nassim</i>、<i>nazanin</i>、<i>royal</i>から選択。空欄でデフォルトテーマを使用します。';

/* Strings for plugin UI */
$strings['Skip'] = 'スキップ';
$strings['Next'] = '次へ';
$strings['Prev'] = '前へ';
$strings['Done'] = '完了';
$strings['StartButtonText'] = 'ツアーを開始';

/* String for the steps */
// if body class = section-mycampus
$strings['TheLogoStep'] = '<b>Chamilo LMS 1.9.x</b>へようこそ';
$strings['TheNavbarStep'] = 'ポータルの主要セクションへのリンクがあるメニューバー';
$strings['TheRightPanelStep'] = 'サイドバー パネル';
$strings['TheUserImageBlock'] = 'プロフィール写真';
$strings['TheProfileBlock'] = 'プロフィールツール：<i>受信箱</i>、<i>メッセージ作成</i>、<i>保留中の招待</i>、<i>プロフィール編集</i>。';
$strings['TheHomePageStep'] = 'ここは初期ホームページで、ポータルのお知らせ、リンク、管理チームが設定した情報を確認できます。';

// if body class = section-mycourses
$strings['YourCoursesList'] = 'このエリアには登録済みのコース（またはセッション）が表示されます。コースが表示されない場合は、コースカタログ（メニュー参照）へ行き、またはポータル管理者にお問い合わせください。';

// if body class = section-myagenda
$strings['AgendaAllowsYouToSeeWhatsHappening'] = 'アジェンダツールで、今後数日、週、月の予定イベントを確認できます。';
$strings['AgendaTheActionBar'] = '提供されたアクションボタンを使用して、カレンダー表示ではなくリスト形式でイベントを表示できます。';
$strings['AgendaTodayButton'] = '「今日」ボタンをクリックして今日の予定のみを表示';
$strings['AgendaTheMonthIsAlwaysInEvidence'] = 'カレンダー表示では現在の月が常に強調表示されます';
$strings['AgendaButtonsAllowYouToChangePeriod'] = 'これらのボタンをクリックして、日次、週次、月次ビューに切り替えられます';

// if body class = section-session_my_space
$strings['MySpaceAllowsYouToKeepTrackOfProgress'] = '学生の場合自分の進捗、教師の場合学生の進捗を確認できます';
$strings['MySpaceSectionsGiveYouImportantInsight'] = 'この画面のレポートは拡張可能で、学びや指導に関する貴重な洞察を提供します';

// if body class = section-social-network
$strings['SocialAllowsYouToGetInTouchWithOtherUsersOfThePlatform'] = 'ソーシャルエリアでプラットフォーム上の他のユーザーと連絡を取れます';
$strings['SocialMenuGivesAccessToDifferentToolsToGetInTouchOrPublishStuff'] = 'メニューからプライベートメッセージ、チャット、興味グループなどにアクセスできます';

// if body class = section-dashboard
$strings['DashboardAllowsYouToGetVerySpecificInformationInAnIllustratedCondensedFormat'] = 'ダッシュボードはイラスト付きの凝縮形式で詳細情報を提供します。現在は管理者のみアクセス可能です';
$strings['DashboardMustBeConfiguredFirstFromTheAdminSectionPluginsThenHereToEnableDesiredBlocks'] = 'ダッシュボードパネルを有効にするには、まず管理セクションのプラグインで可能なパネルを有効化し、ここに戻って自分のダッシュボードに表示するパネルを選択してください';

// if body class = section-platform_admin
$strings['AdministrationAllowsYouToManageYourPortal'] = '管理パネルでChamiloポータルの全リソースを管理できます';
$strings['AdminUsersBlockAllowsYouToManageUsers'] = 'ユーザーブロックでユーザー関連の全項目を管理できます';
$strings['AdminCoursesBlockAllowsYouToManageCourses'] = 'コースブロックでコースの作成・編集などを行えます。他のブロックも特定の用途に特化しています';


$strings['tour_home_featured_courses_title'] = '注目コース';
$strings['tour_home_featured_courses_content'] = 'ホームページで利用可能な注目コースを表示します';

$strings['tour_home_course_card_title'] = 'コースカード';
$strings['tour_home_course_card_content'] = '各カードは1つのコースを要約し、主な情報に素早くアクセスできます';

$strings['tour_home_course_title_title'] = 'コースタイトル';
$strings['tour_home_course_title_content'] = 'コースタイトルでコースを素早く識別でき、プラットフォーム設定によっては詳細情報を開きます';

$strings['tour_home_teachers_title'] = '教師';
$strings['tour_home_teachers_content'] = 'コースに関連付けられた教師やユーザーを表示します';

$strings['tour_home_rating_title'] = '評価とフィードバック';
$strings['tour_home_rating_content'] = 'ここでコースの評価を確認し、許可されている場合は投票できます';

$strings['tour_home_main_action_title'] = '主なコースアクション';
$strings['tour_home_main_action_content'] = 'このボタンでコースに入室、登録、またはコース状況に応じたアクセス制限を確認します';

$strings['tour_home_show_more_title'] = 'さらにコースを表示';
$strings['tour_home_show_more_content'] = 'このボタンでさらにコースを読み込み、ホームページからカタログの探索を続けます';

$strings['tour_my_courses_cards_title'] = 'あなたのコースカード';
$strings['tour_my_courses_cards_content'] = '登録済みコースを一覧表示します。各カードでコースと現在の状況に素早くアクセスできます';

$strings['tour_my_courses_image_title'] = 'コース画像';
$strings['tour_my_courses_image_content'] = 'コース画像でコースを素早く識別できます。通常はクリックでコースが開きます';

$strings['tour_my_courses_title_title'] = 'コースおよびセッションタイトル';
$strings['tour_my_courses_title_content'] = 'ここではコースタイトルと、該当する場合のそのコースに関連付けられたセッション名を確認できます。';

$strings['tour_my_courses_progress_title'] = '学習進捗';
$strings['tour_my_courses_progress_content'] = 'この進捗バーは、コースの完了度を示します。';

$strings['tour_my_courses_notifications_title'] = '新コンテンツ通知';
$strings['tour_my_courses_notifications_content'] = 'このベルボタンを使用して、コースに新しいコンテンツや最近の更新があるかを確認してください。ハイライト表示されている場合、前回のアクセス以降の変更を素早く特定できます。';

$strings['tour_my_courses_footer_title'] = '教師とコース詳細';
$strings['tour_my_courses_footer_content'] = 'フッターには教師、言語、およびコースに関連するその他の有用な情報が表示されます。';

$strings['tour_my_courses_create_course_title'] = 'コースを作成';
$strings['tour_my_courses_create_course_content'] = 'コース作成権限がある場合、このボタンでこのページから直接コース作成フォームを開けます。';

$strings['tour_course_home_header_title'] = 'コースヘッダー';
$strings['tour_course_home_header_content'] = 'このヘッダーにはコースタイトルと、該当する場合のアクティブセッションが表示されます。また、このページで利用可能な主な教師アクションがグループ化されています。';

$strings['tour_course_home_title_title'] = 'コースタイトル';
$strings['tour_course_home_title_content'] = 'ここで現在のコースを素早く特定できます。コースがセッションに属する場合、セッションタイトルが隣に表示されます。';

$strings['tour_course_home_teacher_tools_title'] = '教師ツール';
$strings['tour_course_home_teacher_tools_content'] = '権限に応じて、このエリアには学生ビュー切り替え、導入部編集、レポートアクセス、およびその他のコース管理アクションが含まれる場合があります。';

$strings['tour_course_home_intro_title'] = 'コース導入部';
$strings['tour_course_home_intro_content'] = 'このセクションにはコースの導入部が表示されます。教師はこれを使用して学習者向けの目標、ガイダンス、リンク、または重要な情報を提示できます。';

$strings['tour_course_home_tools_controls_title'] = 'ツールコントロール';
$strings['tour_course_home_tools_controls_content'] = '教師はこれらのコントロールを使用してすべてのツールを一括で表示/非表示にしたり、ソートモードを有効にしてコースツールを再整理できます。';

$strings['tour_course_home_tools_title'] = 'コースツール';
$strings['tour_course_home_tools_content'] = 'このエリアには、文書、学習パス、演習、フォーラム、およびコースで利用可能なその他のリソースなどの主なコースツールが含まれます。';

$strings['tour_course_home_tool_card_title'] = 'ツールカード';
$strings['tour_course_home_tool_card_content'] = '各ツールカードは1つのコースツールにアクセスします。選択したコースエリアに素早く入るために使用してください。';

$strings['tour_course_home_tool_shortcut_title'] = 'ツールショートカット';
$strings['tour_course_home_tool_shortcut_content'] = 'アイコンエリアをクリックして、選択したコースツールを直接開いてください。';

$strings['tour_course_home_tool_name_title'] = 'ツール名';
$strings['tour_course_home_tool_name_content'] = 'タイトルはツールを識別し、直接アクセスリンクとしても機能します。';

$strings['tour_course_home_tool_visibility_title'] = 'ツールの表示';
$strings['tour_course_home_tool_visibility_content'] = 'コースを編集している場合、このボタンで学習者向けのツールの表示を素早く変更できます。';
$strings['tour_admin_overview_title'] = '管理ダッシュボード';
$strings['tour_admin_overview_content'] = 'このページはプラットフォームの主な管理エリアを管理トピックごとにグループ化して集約しています。';

$strings['tour_admin_user_management_title'] = 'ユーザー管理';
$strings['tour_admin_user_management_content'] = 'このブロックから登録ユーザーの管理、アカウント作成、ユーザー一覧のインポート/エクスポート、ユーザー編集、データ匿名化、クラス管理ができます。';

$strings['tour_admin_course_management_title'] = 'コース管理';
$strings['tour_admin_course_management_content'] = 'このブロックでコースの作成と管理、コース一覧のインポート/エクスポート、カテゴリの整理、ユーザーのコース割り当て、コース関連フィールドとツールの設定ができます。';

$strings['tour_admin_sessions_management_title'] = 'セッション管理';
$strings['tour_admin_sessions_management_content'] = 'ここで研修セッション、セッションカテゴリ、インポート/エクスポート、人事責任者、キャリア、昇進、およびセッション関連フィールドを管理できます。';

$strings['tour_admin_platform_management_title'] = 'プラットフォーム管理';
$strings['tour_admin_platform_management_content'] = 'このブロックを使用してプラットフォームをグローバルに設定し、設定を調整し、アナウンス、言語、およびその他の中央管理オプションを管理してください。';

$strings['tour_admin_tracking_title'] = 'トラッキング';
$strings['tour_admin_tracking_content'] = 'このエリアからレポート、全般統計、学習分析、およびプラットフォーム全体のその他のトラッキングデータにアクセスできます。';

$strings['tour_admin_assessments_title'] = '評価';
$strings['tour_admin_assessments_content'] = 'このブロックはプラットフォームで利用可能な評価関連の管理機能にアクセスします。';
$strings['tour_admin_skills_title'] = 'スキル';
$strings['tour_admin_skills_content'] = 'このブロックでユーザースキルの管理、スキルインポート、ランキング、レベル、およびスキル関連の評価を管理できます。';

$strings['tour_admin_system_title'] = 'システム';
$strings['tour_admin_system_content'] = 'ここからサーバーおよびプラットフォームのメンテナンスツール（システムステータス、一時ファイルクリーンアップ、データフィラー、メールテスト、技術ユーティリティなど）にアクセスできます。';

$strings['tour_admin_rooms_title'] = '部屋';
$strings['tour_admin_rooms_content'] = 'このブロックから支店、部屋、および部屋の空き状況検索を含む部屋管理機能にアクセスできます。';

$strings['tour_admin_security_title'] = 'セキュリティ';
$strings['tour_admin_security_content'] = 'このエリアでログイン試行、セキュリティ関連レポート、およびプラットフォームで利用可能な追加セキュリティツールを確認します。';

$strings['tour_admin_chamilo_org_title'] = 'Chamilo.org';
$strings['tour_admin_chamilo_org_content'] = 'このブロックは、公式Chamiloリファレンス、ユーザーガイド、フォーラム、インストールリソース、サービスプロバイダおよびプロジェクト情報のリンクを提供します。';

$strings['tour_admin_health_check_title'] = 'ヘルスチェック';
$strings['tour_admin_health_check_content'] = 'このエリアで環境チェック、書き込み可能パス、および重要なインストール警告を一覧表示してプラットフォームの技術的な健全性を確認します。';

$strings['tour_admin_version_check_title'] = 'バージョン確認';
$strings['tour_admin_version_check_content'] = 'このブロックを使用してポータルを登録し、バージョン確認機能および公開プラットフォーム一覧オプションを有効にします。';

$strings['tour_admin_professional_support_title'] = 'プロフェッショナルサポート';
$strings['tour_admin_professional_support_content'] = 'このブロックは、コンサルティング、ホスティング、トレーニング、カスタム開発サポートのために公式Chamiloプロバイダに連絡する方法を説明します。';

$strings['tour_admin_news_title'] = 'Chamiloからのニュース';
$strings['tour_admin_news_content'] = 'このセクションでは、Chamiloプロジェクトからの最近のニュースと発表を表示します。';
