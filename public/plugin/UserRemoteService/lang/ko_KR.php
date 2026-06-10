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
$strings['Actions'] = '작업';
$strings['AddRemoteService'] = '원격 서비스 추가';
$strings['CurrentServices'] = '현재 서비스';
$strings['DeleteService'] = '서비스 삭제';
$strings['InvalidSecurityToken'] = '잘못된 보안 토큰입니다.';
$strings['InvalidServiceTitle'] = '서비스 제목을 입력하세요.';
$strings['InvalidServiceUrl'] = '유효한 HTTP 또는 HTTPS URL을 입력하세요.';
$strings['MissingSaltWarning'] = '원격 서비스 링크를 노출하기 전에 salt를 구성하세요. salt는 서명된 사용자 URL을 생성하는 데 필요합니다.';
$strings['NoServicesConfigured'] = '아직 구성된 원격 서비스가 없습니다.';
$strings['OpenInIframe'] = 'iframe에서 열기';
$strings['OpenRedirect'] = '리디렉션 URL 열기';
$strings['RemoteServicesDescription'] = 'Chamilo에서 서명된 사용자 URL을 수신하는 외부 서비스를 관리합니다. 인증된 사용자만 이러한 링크를 열 수 있습니다.';
$strings['ServiceCreated'] = '원격 서비스가 생성되었습니다.';
$strings['ServiceDeleted'] = '원격 서비스가 삭제되었습니다.';
$strings['ServiceManagement'] = '원격 서비스 관리';
$strings['ServiceUnavailable'] = '이 원격 서비스를 사용할 수 없습니다. 플러그인이 활성화되었는지, salt가 구성되었는지, URL이 유효한지 확인하세요.';
