<?php
/* For licensing terms, see /license.txt */

/**
 * This tool allows platform admins to anonymize users by uploading a text file, with one username per line.
 */

use Chamilo\CoreBundle\Entity\User;
use Doctrine\Common\Collections\Criteria;

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script(true, null);

$tool_name = get_lang('Anonymise users list');
$interbreadcrumb[] = ["url" => 'index.php', "name" => get_lang('Administration')];

set_time_limit(0);
ini_set('memory_limit', -1);

Display::display_header($tool_name);

$step1Form = new FormValidator('step1Form');

$usernameListFile = $step1Form->addFile('usernameList', get_lang('Username list'));
$step1Form->addButtonUpload(get_lang('Upload'));

$step2Form = new FormValidator('step2Form');
$usernameTextarea = $step2Form->addTextarea(
    'usersToBeAnonymized',
    get_lang('Users about to be anonymized:'),
    [
        'readonly' => 1,
    ]
);
$anonymizedSessions = $step2Form->addCheckBox('anonymize_sessions', null, get_lang("Anonymize user's sessions"));
$step2Form->addButtonUpdate(get_lang('Anonymize'));

if ($step1Form->validate() && $usernameListFile->isUploadedFile()) {
    $usernameListFileUploaded = $usernameListFile->getValue();
    $usernameListFileUploaded['name'] = api_htmlentities($usernameListFileUploaded['name']);
    $filePath = $usernameListFileUploaded['tmp_name'];
    if (!file_exists($filePath)) {
        throw new Exception(get_lang('Could not read file.').' '.$filePath);
    }
    $submittedUsernames = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (false === $submittedUsernames) {
        throw new Exception(get_lang('Could not read file lines.').' '.$filePath);
    }

    $submittedUsernames = array_map('api_htmlentities', $submittedUsernames);
    $submittedUsernames = array_filter($submittedUsernames);

    if (empty($submittedUsernames)) {
        printf(
            '<p>'.get_lang('File %s is empty or only contains empty lines.').'</p>',
            '<em>'.$usernameListFileUploaded['name'].'</em>'
        );
    } else {
        printf(
            '<p>'.get_lang('File %s has %d non-empty lines.').'</p>',
            '<em>'.$usernameListFileUploaded['name'].'</em>',
            count($submittedUsernames)
        );
        $uniqueSubmittedUsernames = array_values(array_unique($submittedUsernames));
        if (count($uniqueSubmittedUsernames) !== count($submittedUsernames)) {
            printf(
                '<p>'.get_lang('There are duplicates: only %d unique user names were extracted.').'</p>',
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
            echo '<p>'.get_lang('No line matched any actual user name.').'</p>';
        } else {
            $foundUsernames = [];
            /** @var User $user */
            foreach ($users as $user) {
                $foundUsernames[] = $user->getUsername();
            }
            if (count($users) !== count($uniqueSubmittedUsernames)) {
                printf('<p>'.get_lang('Only %d lines matched actual users.').'</p>', count($users));
                $usernamesNotFound = array_diff($uniqueSubmittedUsernames, $foundUsernames);
                printf(
                    '<p>'.get_lang('The following %d line(s) do not match any actual user:').'<pre>%s</pre></p>',
                    count($usernamesNotFound),
                    join("\n", $usernamesNotFound)
                );
            }
            printf('<p>'.get_lang('%d users are about to be anonymized :').'</p>', count($foundUsernames));
            $usernameTextarea->setValue(join("\n", $foundUsernames));
            $step2Form->display();
        }
    }
} elseif ($step2Form->validate()) {
    $usernames = preg_split("/\s+/", $usernameTextarea->getValue());
    if (false === $usernames) {
        throw new Exception('preg_split failed');
    }
    printf('<p>'.get_lang('Loading %d users...')."</p>\n", count($usernames));
    $users = UserManager::getRepository()->matching(
        Criteria::create()->where(
            Criteria::expr()->in('username', $usernames)
        )
    );
    $anonymizedSessionsValue = $anonymizedSessions->getValue();
    if (count($users) === count($usernames)) {
        printf('<p>'.get_lang('Anonymizing %d users...')."</p>\n", count($users));
        $anonymized = [];
        $errors = [];
        $tableSession = Database::get_main_table(TABLE_MAIN_SESSION);
        /** @var User $user */
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
                                echo '<p> '.$sessionTitle.' ('.$sessionId.') - '.get_lang('Learners').': '.$usersCount.'</p>';
                                if (1 === $usersCount) {
                                    $uniqueId = uniqid('anon_session', true);
                                    echo '<p> '.get_lang('Rename').': '.$sessionTitle.' -> '.$uniqueId.'</p>';
                                    $sql = "UPDATE $tableSession SET name = '$uniqueId' WHERE id = $sessionId";
                                    Database::query($sql);
                                } else {
                                    echo '<p> '.sprintf(get_lang('Session %s skipped'), $sessionTitle).'</p>';
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
            printf('<p>'.get_lang('All %d users were anonymized.').'</p>', count($users));
        } else {
            printf(
                '<p>'
                .get_lang('Only %d users were anonymized.')
                .' '
                .get_lang('Attempted anonymization of the following %d users failed:')
                .'<pre>%s</pre></p>',
                count($users),
                count($errors),
                implode("\n", $errors)
            );
        }
    } else {
        printf(
            '<p>'.get_lang('Internal inconsistency found : %d users found from %d submitted usernames. Please start over.').'</p>',
            count($users),
            count($usernames)
        );
    }
} else {
    echo '<p>'.get_lang('Please upload a text file listing the users to be anonymized, one username per line.').'</p>';
    $step1Form->display();
}

Display::display_footer();
