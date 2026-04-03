<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'ユーザー リモート サービス';
$strings['plugin_comment'] = 'メニューバーにサイト固有の iframe ターゲットのユーザー識別リンクを追加します。';

$strings['salt'] = 'ソルト';
$strings['salt_help'] = '<em>hash</em> URL パラメータを生成するために使用される秘密の文字列です。長ければ長いほど良いです。
<br/>リモートユーザーサービスは以下の PHP 式で生成された URL の真正性を確認できます :
<br/><code class="php">password_verify($salt.$userId, $hash)</code>
<br/>ここで
<br/><code>$salt</code> はこの入力値、
<br/><code>$userId</code> は <em>username</em> URL パラメータ値で参照されるユーザーの番号、
<br/><code>$hash</code> は <em>hash</em> URL パラメータ値です。';
$strings['hide_link_from_navigation_menu'] = 'メニューのリンクを非表示';

// Please keep alphabetically sorted
$strings['CreateService'] = 'メニューバーにサービスを追加';
$strings['DeleteServices'] = 'メニューバーからサービスを削除';
$strings['ServicesToDelete'] = 'メニューバーから削除するサービス';
$strings['ServiceTitle'] = 'サービスタイトル';
$strings['ServiceURL'] = 'サービスウェブサイトの場所 (URL)';
$strings['RedirectAccessURL'] = 'ユーザーをサービスにリダイレクトするための Chamilo で使用する URL (URL)';
