<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = '사용자 원격 서비스';
$strings['plugin_comment'] = '메뉴 바에 사이트별 iframe 대상 사용자 식별 링크를 추가합니다.';

$strings['salt'] = 'Salt';
$strings['salt_help'] = '<em>hash</em> URL 매개변수를 생성하는 데 사용되는 비밀 문자 문자열입니다. 길수록 좋습니다.
<br/>원격 사용자 서비스는 다음 PHP 표현식으로 생성된 URL의 진위 여부를 확인할 수 있습니다:
<br/><code class="php">password_verify($salt.$userId, $hash)</code>
<br/>여기서
<br/><code>$salt</code>은 이 입력 값이고,
<br/><code>$userId</code>은 <em>username</em> URL 매개변수 값으로 참조되는 사용자의 번호이며
<br/><code>$hash</code>는 <em>hash</em> URL 매개변수 값을 포함합니다.';
$strings['hide_link_from_navigation_menu'] = '메뉴에서 링크 숨기기';

// Please keep alphabetically sorted
$strings['CreateService'] = '메뉴 바에 서비스 추가';
$strings['DeleteServices'] = '메뉴 바에서 서비스 제거';
$strings['ServicesToDelete'] = '메뉴 바에서 제거할 서비스';
$strings['ServiceTitle'] = '서비스 제목';
$strings['ServiceURL'] = '서비스 웹사이트 위치 (URL)';
$strings['RedirectAccessURL'] = '사용자를 서비스로 리디렉션하기 위해 Chamilo에서 사용할 URL (URL)';
