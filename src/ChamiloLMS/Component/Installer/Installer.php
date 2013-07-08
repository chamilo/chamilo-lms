<?php

namespace ChamiloLMS\Component\Installer;

class Installer
{

    public function __construct()
    {

    }

    public function setSettingsAfterInstallation($adminInfo, $portalSettings)
    {
        // Updating admin user
        $adminInfo['user_id'] = 1;
        $adminInfo['auth_source'] = 'platform';
        $adminInfo['password'] = api_get_encrypted_password($adminInfo['password']);

        $result = \UserManager::update($adminInfo);
        if ($result) {
            \UserManager::add_user_as_admin($adminInfo['user_id']);
        }

        // Updating anonymous user
        $anonymousUser['user_id'] = 2;
        $anonymousUser['language'] = $adminInfo['language'];
        \UserManager::update($anonymousUser);

        // Updating portal settings

        api_set_setting('emailAdministrator', $adminInfo['email']);
        api_set_setting('administratorSurname', $adminInfo['lastname']);
        api_set_setting('administratorName', $adminInfo['firstname']);
        api_set_setting('platformLanguage', $adminInfo['language']);

        api_set_setting('allow_registration', '1');
        api_set_setting('allow_registration_as_teacher', '1');

        api_set_setting('permissions_for_new_directories', $portalSettings['permissions_for_new_directories']);
        api_set_setting('permissions_for_new_files', $portalSettings['permissions_for_new_files']);

        api_set_setting('Institution', $portalSettings['institution']);
        api_set_setting('InstitutionUrl', $portalSettings['institution_url']);
        api_set_setting('siteName', $portalSettings['sitename']);

    }
}
