<?php
/* For licensing terms, see /license.txt*/

/**
 * Validates imported data.
 */
function validate_data($users)
{
    global $defined_auth_sources;
    $errors = [];
    $usernames = [];
    if (is_array($users)) {
        foreach ($users as $index => $user) {
            // 1. Check whether mandatory fields have been set.
            $mandatory_fields = ['LastName', 'FirstName'];
            if (api_get_setting('registration', 'email') == 'true') {
                $mandatory_fields[] = 'Email';
            }
            foreach ($mandatory_fields as $key => $field) {
                if (!isset($user[$field]) || strlen($user[$field]) == 0) {
                    $user['error'] = get_lang($field.'Mandatory');
                    $errors[] = $user;
                }
            }
            // 2. Check username.
            if (!UserManager::is_username_empty($user['UserName'])) {
                // 2.1. Check whether username was used twice in the import file.
                if (isset($usernames[$user['UserName']])) {
                    $user['error'] = get_lang('UserNameUsedTwice');
                    $errors[] = $user;
                }
                $usernames[$user['UserName']] = 1;
                // 2.2. Check whether username is allready in use in database.
                if (!UserManager::is_username_available($user['UserName'])) {
                    $user['error'] = get_lang('UserNameNotAvailable');
                    $errors[] = $user;
                }
                // 2.3. Check whether username is too long.
                if (UserManager::is_username_too_long($user['UserName'])) {
                    $user['error'] = get_lang('UserNameTooLong');
                    $errors[] = $user;
                }
            }
            // 3. Check status.
            if (isset($user['Status']) && !api_status_exists($user['Status'])) {
                $user['error'] = get_lang('WrongStatus');
                $errors[] = $user;
            }
            // 5. Check authentication source.
            if (isset($user['AuthSource']) && strlen($user['AuthSource']) != 0) {
                if (!in_array($user['AuthSource'], $defined_auth_sources)) {
                    $user['error'] = get_lang('AuthSourceNotAvailable');
                    $errors[] = $user;
                }
            }
        }
    }

    return $errors;
}

/**
 * Adds missing user-information (which isn't required, like password, username, etc).
 */
function complete_missing_data($user)
{
    // 1. Create a username if necessary.
    if (UserManager::is_username_empty($user['UserName'])) {
        $user['UserName'] = UserManager::create_unique_username(
            $user['FirstName'],
            $user['LastName']
        );
    }
    // 2. Generate a password if necessary.
    if (!isset($user['Password']) || strlen($user['Password']) == 0) {
        $user['Password'] = api_generate_password();
    }
    // 3. set status if not allready set.
    if (!isset($user['Status']) || strlen($user['Status']) == 0) {
        $user['Status'] = 'user';
    }
    // 4. Set authsource if not allready set.
    if (!isset($user['AuthSource']) || strlen($user['AuthSource']) == 0) {
        $user['AuthSource'] = PLATFORM_AUTH_SOURCE;
    }

    return $user;
}

/**
 * Save the imported data.
 *
 * @param array
 */
function save_data($users)
{
    if (is_array($users)) {
        foreach ($users as $index => $user) {
            $user = complete_missing_data($user);
            $user['Status'] = api_status_key($user['Status']);
            $user_id = UserManager::create_user(
                $user['FirstName'],
                $user['LastName'],
                $user['Status'],
                $user['Email'],
                $user['UserName'],
                $user['Password'],
                $user['OfficialCode'],
                api_get_setting('PlatformLanguage'),
                $user['PhoneNumber'],
                '',
                $user['AuthSource']
            );

            if (!empty($user['Courses'])) {
                foreach ($user['Courses'] as $course) {
                    if (CourseManager::course_exists($course)) {
                        CourseManager::subscribeUser(
                            $user_id,
                            $course,
                            $user['Status']
                        );
                    }
                }
            }

            // TODO: Hard-coded French texts.

            // Qualite
            if (!empty($user['Qualite'])) {
                UserManager::update_extra_field_value($user_id, 'qualite', $user['Qualite']);
            }

            // Categorie
            if (!empty($user['Categorie'])) {
                UserManager::update_extra_field_value($user_id, 'categorie', $user['Categorie']);
            }

            // Etat
            if (!empty($user['Etat'])) {
                UserManager::update_extra_field_value($user_id, 'etat', $user['Etat']);
            }

            // Niveau
            if (!empty($user['Niveau'])) {
                UserManager::update_extra_field_value($user_id, 'niveau', $user['Niveau']);
            }
        }
    }
}

/**
 * Reads the CSV-file.
 *
 * @param string $file Path to the CSV-file
 *
 * @return array All user information read from the file
 */
function parse_csv_data($file)
{
    $users = Import::csvToArray($file);
    foreach ($users as $index => $user) {
        if (isset($user['Courses'])) {
            $user['Courses'] = explode('|', trim($user['Courses']));
        }
        $users[$index] = $user;
    }

    return $users;
}
