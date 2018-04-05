<?php
/* For licensing terms, see /license.txt */

/**
 * This file contains the necessary elements to implement a Single Sign On
 * using chamilo as a SSO server.
 *
 * @package chamilo.auth.sso
 */
class SsoServer
{
    /**
     * This is used to get the url with the SSO params.
     *
     * @param string $refererSso
     * @param array  $additionalParams
     *
     * @return string
     */
    public function getUrl($refererSso, $additionalParams = [])
    {
        if (empty($refererSso)) {
            return null;
        }

        $getParams = parse_url($refererSso, PHP_URL_QUERY);
        $userInfo = api_get_user_info(api_get_user_id(), false, true);
        $chamiloUrl = api_get_path(WEB_PATH);
        $sso = [
            'username' => $userInfo['username'],
            'secret' => sha1($userInfo['password']),
            'master_domain' => $chamiloUrl,
            'master_auth_uri' => $chamiloUrl.'?submitAuth=true',
            'lifetime' => time() + 3600,
            'target' => $refererSso,
        ];

        if (!empty($additionalParams)) {
            foreach ($additionalParams as $key => $value) {
                if (!empty($key)) {
                    $sso[$key] = $value;

                    continue;
                }

                $sso[] = $value;
            }
        }

        $cookie = base64_encode(serialize($sso));

        return $refererSso
            .($getParams ? '&' : '?')
            .http_build_query([
                'loginFailed' => 0,
                'sso_referer' => $refererSso,
                'sso_cookie' => $cookie,
            ]);
    }
}
