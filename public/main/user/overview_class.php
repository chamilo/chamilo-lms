<?php

require_once __DIR__ . '/../inc/global.inc.php';

use Chamilo\CoreBundle\Component\Utils\ActionIcon;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;

$idClasse = $_GET['usergroup'];
$idCours = $_GET['course'];

$actionsLeft = '<a href="class.php?cid=' . $idCours . '">' .
    Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Classes')) . '</a>';

$actions = Display::toolbarAction('actions-class', [$actionsLeft, '']);

$usergroup = new UserGroupModel();
$classe = $usergroup->get($idClasse);

$url = api_get_path(WEB_AJAX_PATH) . 'model.ajax.php?a=get_usergroups_users&id=' . $idClasse;

$tool_name = get_lang('Overview students in class') . ' : ' . $classe['title'];

Display::display_header($tool_name);
echo $actions;

$ch = curl_init($url);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json',]);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo Display::return_message(get_lang('An error occurred') . ' : ' . curl_error($ch), 'error');
} else {

    $data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo Display::return_message(get_lang('An error occurred') . ' : ' . json_last_error_msg(), 'error');
    } else {
        if (isset($data['rows'])) {

            $userManager = new UserManager();
            $courseManager = new CourseManager();
            $users = $courseManager->get_user_list_from_course_code($idCours);

            $usersSubscribedToUsergroup = [];
            foreach ($data['rows'] as $row) {
                $name = $row['cell'][0];
                $parts = explode(' ', $name);
                $usersSubscribedToUsergroup[] = $userManager->get_all_user_tags(trim($parts[2], '()'));
            }

            $usersSubscribedToCourse = [];
            $usersNotSubscribedToCourse = [];
            for ($i = 0; $i < count($usersSubscribedToUsergroup); $i++) {
                $userId = array_key_first($usersSubscribedToUsergroup[$i]);
                if (array_key_exists($userId, $users)) {
                    $usersSubscribedToCourse[] = array_values($usersSubscribedToUsergroup[$i]);
                } else {
                    $usersNotSubscribedToCourse[] = array_values($usersSubscribedToUsergroup[$i]);
                }
            }

            if (count($usersSubscribedToCourse) > 0) {
                echo "<b>" . get_lang('Users subscribed to the course') . "</b>";
                echo "<ul style='list-style-type: disc; padding-left: 1.5vw;'>";
                foreach ($usersSubscribedToCourse as $userSubscribedToCourse) {
                    echo "<li>" . $userSubscribedToCourse[0]['firstname'] . ' ' . $userSubscribedToCourse[0]['lastname'] . ' ( ' . $userSubscribedToCourse[0]['email'] . " )</li>";
                }
                echo "</ul>";
            }

            if (count($usersNotSubscribedToCourse) > 0) {
                echo "<b>" . get_lang('Users not subscribed to the course') . "</b>";
                echo "<ul style='list-style-type: disc; padding-left: 1.5vw;'>";
                foreach ($usersNotSubscribedToCourse as $userNotSubscribedToCourse) {
                    echo "<li>" . $userNotSubscribedToCourse[0]['firstname'] . ' ' . $userNotSubscribedToCourse[0]['lastname'] . ' ( ' . $userNotSubscribedToCourse[0]['email'] . " )</li>";
                }
                echo "</ul>";
            }

        } else {
            echo Display::return_message(get_lang('No user is subscribed to this class'), 'warning');
        }
    }
}

curl_close($ch);

Display::display_footer();
