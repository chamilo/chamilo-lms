<?php
/* For licensing terms, see /license.txt */

/**
 * This tool allows platform admins to anonymize users by uploading a text file, with one username per line.
 *
 * @package chamilo.admin
 */

use Doctrine\Common\Collections\Criteria;

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
$step2Form->addButtonUpdate(get_lang('Anonymize'));

if ($step1Form->validate() && $usernameListFile->isUploadedFile()) {
    $filePath = $usernameListFile->getValue()['tmp_name'];
    if (!file_exists($filePath)) {
        throw new Exception(get_lang('CouldNotReadFile').' '.$filePath);
    }
    $submittedUsernames = file($filePath, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
    if (false === $submittedUsernames) {
        throw new Exception(get_lang('CouldNotReadFileLines').' '.$filePath);
    }
    if (empty($submittedUsernames)) {
        printf('<p>File <em>%s</em> is empty or only contains empty lines.</p>', $usernameListFile->getValue()['name']);
    } else {
        printf(
            '<p>File <em>%s</em> has %d non-empty lines.</p>',
            $usernameListFile->getValue()['name'],
            count($submittedUsernames)
        );
        $uniqueSubmittedUsernames = array_values(array_unique($submittedUsernames));
        if (count($uniqueSubmittedUsernames) !== count($submittedUsernames)) {
            printf(
                "<p>There are duplicates: only %d unique user names were extracted.</p>",
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
            echo "<p>No line matched any actual user name.</p>";
        } else {
            $foundUsernames = [];
            foreach ($users as $user) {
                $foundUsernames[] = $user->getUsername();
            }
            if (count($users) !== count($uniqueSubmittedUsernames)) {
                printf('<p>Only %d lines matched actual users.</p>', count($users));
                $usernamesNotFound = array_diff($uniqueSubmittedUsernames, $foundUsernames);
                printf(
                    "<p>The following %d line(s) do not match any actual user : <pre>%s</pre></p>",
                    count($usernamesNotFound),
                    join("\n", $usernamesNotFound)
                );
            }
            printf('<p>%d users are about to be anonymized :</p>', count($foundUsernames));
            $usernameTextarea->setValue(join("\n", $foundUsernames));
            $step2Form->display();
        }
    }
} elseif ($step2Form->validate()) {
    $usernames = preg_split("/\s+/", $usernameTextarea->getValue());
    printf("<p>Loading %d users...</p>\n", count($usernames));
    $users = UserManager::getRepository()->matching(
        Criteria::create()->where(
            Criteria::expr()->in('username', $usernames)
        )
    );
    if (count($users) === count($usernames)) {
        printf("<p>Anonymizing %d users...</p>\n", count($users));
        $anonymized = [];
        $errors = [];
        foreach ($users as $user) {
            $username = $user->getUsername();
            $userId = $user->getId();
            $name = api_get_person_name($user->getFirstname(), $user->getLastname());
            echo "<p>$username ($name, id=$userId):\n";
            try {
                if (UserManager::anonymize($userId)) {
                    echo "done.";
                    $anonymized[] = $username;
                } else {
                    echo "error: UserManager::anonymize failed.";
                    $errors[] = $username;
                }
            } catch (Exception $exception) {
                echo 'error: '.$exception->getMessage();
                $errors[] = $username;
            }
            echo "</p>\n";
        }
        if (empty($error)) {
            printf("<p>All %d users were anonymized.</p>", count($users));
        } else {
            printf(
                '<p>Only %d users were anonymized.'
                .'Attempted anonymization of the following %d users failed:<pre>%s</pre></p>',
                count($users),
                count($errors),
                join("\n", $errors)
            );
        }
    } else {
        printf(
            '<p>Internal inconsistency found : %d users found from %d submitted usernames. Please start over.</p>',
            count($users),
            count($usernames)
        );
    }
} else {
    echo '<p>Please upload a text file listing the users to be anonymized, one username per line.</p>';
    $step1Form->display();
}

Display::display_footer();
