<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\LtiBundle\Util;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\LtiBundle\Entity\ExternalTool;
use OAuthConsumer;
use OAuthRequest;
use OAuthSignatureMethod_HMAC_SHA1;
use URLify;
use UserManager;

class Utils
{
    private SettingsManager $settingsManager;

    public function __construct(SettingsManager $settingsManager)
    {
        $this->settingsManager = $settingsManager;
    }

    /**
     * @return string
     */
    public function getInstitutionDomain()
    {
        $setting = $this->settingsManager->getSetting('platform.institution_url');

        return str_replace(['https://', 'http://'], '', $setting);
    }

    /**
     * @param int $userId
     *
     * @return string
     */
    public function generateToolUserId($userId)
    {
        $siteName = $this->settingsManager->getSetting('platform.site_name');
        $institution = $this->settingsManager->getSetting('platform.institution');

        $userString = "{$siteName} - {$institution} - {$userId}";

        return URLify::filter($userString, 255, '', true, true, false, false, true);
    }

    /**
     * @return string
     */
    public static function generateUserRoles(User $user)
    {
        if ($user->hasRole('ROLE_RRHH')) {
            return 'urn:lti:role:ims/lis/Mentor';
        }

        //if ($user->hasRole('ROLE_INVITEE')) {
        //    return 'Learner,urn:lti:role:ims/lis/Learner/GuestLearner';
        //}

        if ($user->hasRole('ROLE_CURRENT_COURSE_STUDENT') ||
            $user->hasRole('ROLE_CURRENT_COURSE_SESSION_STUDENT')
        ) {
            return 'Learner';
        }

        $roles = ['Instructor'];

        if ($user->hasRole('ROLE_ADMIN')) {
            $roles[] = 'urn:lti:role:ims/lis/Administrator';
        }

        return implode(',', $roles);
    }

    /**
     * @return string
     */
    public function generateRoleScopeMentor(User $currentUser)
    {
        if (DRH !== $currentUser->getStatus()) {
            return '';
        }

        $followedUsers = UserManager::get_users_followed_by_drh($currentUser->getId());
        $scope = [];

        foreach ($followedUsers as $userInfo) {
            $scope[] = $this->generateToolUserId($userInfo['user_id']);
        }

        return implode(',', $scope);
    }

    public static function trimParams(array &$params): void
    {
        foreach ($params as $key => $value) {
            $newValue = preg_replace('/\s+/', ' ', (string) $value);

            $params[$key] = trim($newValue);
        }
    }

    /**
     * @return array
     */
    public static function removeQueryParamsFromLaunchUrl(ExternalTool $tool, array &$params)
    {
        $urlQuery = parse_url($tool->getLaunchUrl(), PHP_URL_QUERY);

        if (empty($urlQuery)) {
            return $params;
        }

        $queryParams = [];
        parse_str($urlQuery, $queryParams);
        $queryKeys = array_keys($queryParams);

        foreach ($queryKeys as $key) {
            if (isset($params[$key])) {
                unset($params[$key]);
            }
        }
    }

    /**
     * @param string $url
     * @param string $originConsumerKey
     * @param string $originSignature
     *
     * @return bool
     */
    public static function checkRequestSignature($url, $originConsumerKey, $originSignature, ExternalTool $tool)
    {
        $consumer = new OAuthConsumer(
            $originConsumerKey,
            $tool->getSharedSecret()
        );
        $hmacMethod = new OAuthSignatureMethod_HMAC_SHA1();
        $oAuthRequest = OAuthRequest::from_request('POST', $url);
        $oAuthRequest->sign_request($hmacMethod, $consumer, '');
        $signature = $oAuthRequest->get_parameter('oauth_signature');

        return $signature !== $originSignature;
    }
}
