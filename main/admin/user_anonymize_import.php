<?php

/* For licensing terms, see /license.txt */

use Doctrine\Common\Collections\Criteria;

/**
 * This tool allows platform admins to anonymize users by uploading a text file,
 * with one username per line.
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script(true, null);

$tool_name = get_lang('BulkAnonymizeUsers');
$interbreadcrumb[] = ["url" => 'index.php', "name" => get_lang('PlatformAdmin')];

set_time_limit(0);
ini_set('memory_limit', -1);

Display::display_header($tool_name);

$step1Form = new FormValidator('step1Form');

$usernameListFile = $step1Form->addFile('usernameList', get_lang('UsernameList'));
$step1Form->addButtonUpload(get_lang('Upload'));

$step2Form = new FormValidator('step2Form');
$usernameTextarea = $step2Form->addTextarea(
    'usersToBeAnonymized',
    get_lang('UsersAboutToBeAnonymized'),
    [
        'readonly' => 1,
    ]
);
$anonymizedSessions = $step2Form->addCheckBox('anonymize_sessions', null, get_lang('AnonymizeUserSessions'));

$step2Form->addButtonUpdate(get_lang('Anonymize'));

if ($step1Form->validate() && $usernameListFile->isUploadedFile()) {
    $filePath = $usernameListFile->getValue()['tmp_name'];
    if (!file_exists($filePath)) {
        throw new Exception(get_lang('CouldNotReadFile').' '.$filePath);
    }
    $submittedUsernames = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (false === $submittedUsernames) {
        throw new Exception(get_lang('CouldNotReadFileLines').' '.$filePath);
    }
    if (empty($submittedUsernames)) {
        printf(
            '<p>'.get_lang('FileXHasNoData').'</p>',
            '<em>'.$usernameListFile->getValue()['name'].'</em>'
        );
    } else {
        printf(
            '<p>'.get_lang('FileXHasYNonEmptyLines').'</p>',
            '<em>'.$usernameListFile->getValue()['name'].'</em>',
            count($submittedUsernames)
        );
        $uniqueSubmittedUsernames = array_values(array_unique($submittedUsernames));
        if (count($uniqueSubmittedUsernames) !== count($submittedUsernames)) {
            printf(
                '<p>'.get_lang('DuplicatesOnlyXUniqueUserNames').'</p>',
                count($uniqueSubmittedUsernames)
            );
        }
        $matching = UserManager::getRepository()->matching(
            Criteria::create()->where(
                Criteria::expr()->in('username', $uniqueSubmittedUsernames)
            )
        );
        foreach ($matching as $element) {
            if (!is_null($element)) {
                $users[] = $element;
            }
        }
        if (empty($users)) {
            echo '<p>'.get_lang('NoLineMatchedAnyActualUserName').'</p>';
        } else {
            $foundUsernames = [];
            foreach ($users as $user) {
                $foundUsernames[] = $user->getUsername();
            }
            if (count($users) !== count($uniqueSubmittedUsernames)) {
                printf('<p>'.get_lang('OnlyXLinesMatchedActualUsers').'</p>', count($users));
                $usernamesNotFound = array_diff($uniqueSubmittedUsernames, $foundUsernames);
                printf(
                    '<p>'.get_lang('TheFollowingXLinesDoNotMatchAnyActualUser').'<pre>%s</pre></p>',
                    count($usernamesNotFound),
                    join("\n", $usernamesNotFound)
                );
            }
            printf('<p>'.get_lang('XUsersAreAboutToBeAnonymized').'</p>', count($foundUsernames));
            $usernameTextarea->setValue(join("\n", $foundUsernames));
            $step2Form->display();
        }
    }
} elseif ($step2Form->validate()) {
    $usernames = preg_split("/\s+/", $usernameTextarea->getValue());
    if (false === $usernames) {
        throw new Exception('preg_split failed');
    }
    printf('<p>'.get_lang('LoadingXUsers')."</p>\n", count($usernames));
    $users = UserManager::getRepository()->matching(
        Criteria::create()->where(
            Criteria::expr()->in('username', $usernames)
        )
    );

    $anonymizedSessionsValue = $anonymizedSessions->getValue();

    if (count($users) === count($usernames)) {
        printf('<p>'.get_lang('AnonymizingXUsers')."</p>\n", count($users));
        $anonymized = [];
        $errors = [];
        $tableSession = Database::get_main_table(TABLE_MAIN_SESSION);
        foreach ($users as $user) {
            $username = $user->getUsername();
            $userId = $user->getId();
            $name = api_get_person_name($user->getFirstname(), $user->getLastname());
            echo "<h4>$username ($name, id= $userId) </h4>";
            try {
                if (UserManager::anonymize($userId)) {
                    if ($anonymizedSessionsValue) {
                        $sessions = SessionManager::getSessionsFollowedByUser($userId);
                        if ($sessions) {
                            echo '<p> '.get_lang('Sessions').' </p>';
                            foreach ($sessions as $session) {
                                $sessionId = $session['id'];
                                $sessionTitle = $session['name'];
                                $usersCount = SessionManager::get_users_by_session($sessionId, null, true);
                                echo '<p> '.$sessionTitle.' ('.$sessionId.') - '.get_lang('Students').': '.$usersCount.'</p>';
                                if (1 === $usersCount) {
                                    $uniqueId = uniqid('anon_session', true);
                                    echo '<p> '.get_lang('Rename').': '.$sessionTitle.' -> '.$uniqueId.'</p>';
                                    $sql = "UPDATE $tableSession SET name = '$uniqueId' WHERE id = $sessionId";
                                    Database::query($sql);
                                } else {
                                    echo '<p> '.sprintf(get_lang('SessionXSkipped'), $sessionTitle).'</p>';
                                }
                            }
                        }
                    }

                    echo get_lang('Done');
                    $anonymized[] = $username;
                } else {
                    echo 'error: UserManager::anonymize failed.';
                    $errors[] = $username;
                }
            } catch (Exception $exception) {
                echo 'error: '.$exception->getMessage();
                $errors[] = $username;
            }
            echo "</p>\n";
        }

        if (empty($error)) {
            printf('<p>'.get_lang('AllXUsersWereAnonymized').'</p>', count($users));
        } else {
            printf(
                '<p>'
                .get_lang('OnlyXUsersWereAnonymized')
                .' '
                .get_lang('AttemptedAnonymizationOfTheseXUsersFailed')
                .'<pre>%s</pre></p>',
                count($users),
                count($errors),
                implode("\n", $errors)
            );
        }
    } else {
        printf(
            '<p>'.get_lang('InternalInconsistencyXUsersFoundForYUserNames').'</p>',
            count($users),
            count($usernames)
        );
    }
} else {
    echo '<p>'.get_lang('PleaseUploadListOfUsers').'</p>';
    $step1Form->display();
}

Display::display_footer();
