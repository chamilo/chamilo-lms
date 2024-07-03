<?php

/* For licensing terms, see /license.txt */
/**
 * Script.
 *
 * @package chamilo.gradebook
 */

//Disabling code when course code is null (gradebook as a tab) see issue #2705
exit;

require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_MYGRADEBOOK;
api_block_anonymous_users();
GradebookUtils::block_students();

$evaluation = Evaluation::load($_GET['selecteval']);
$newstudents = $evaluation[0]->get_not_subscribed_students();

if (count($newstudents) == '0') {
    header('Location: gradebook_view_result.php?nouser=&selecteval='.intval($_GET['selecteval']).'&'.api_get_cidreq());
    exit;
}
$add_user_form = new EvalForm(
    EvalForm::TYPE_ADD_USERS_TO_EVAL,
    $evaluation[0],
    null,
    'add_users_to_evaluation',
    null,
    api_get_self().'?selecteval='.Security::remove_XSS($_GET['selecteval']),
    Security::remove_XSS($_GET['firstletter']),
    $newstudents
);

if (isset($_POST['submit_button'])) {
    $users = is_array($_POST['add_users']) ? $_POST['add_users'] : [];
    foreach ($users as $key => $value) {
        $users[$key] = intval($value);
    }

    if (count($users) == 0) {
        header('Location: '.api_get_self().'?erroroneuser=&selecteval='.Security::remove_XSS($_GET['selecteval']));
        exit;
    } else {
        foreach ($users as $user_id) {
            $result = new Result();
            $result->set_user_id($user_id);
            $result->set_evaluation_id($_GET['selecteval']);
            $result->add();
        }
    }
    header(
        'Location: gradebook_view_result.php?adduser=&selecteval='.Security::remove_XSS($_GET['selecteval']).'&'.api_get_cidreq()
    );
    exit;
} elseif ($_POST['firstLetterUser']) {
    $firstletter = $_POST['firstLetterUser'];
    if (!empty($firstletter)) {
        header(
            'Location: '.api_get_self().'?firstletter='.Security::remove_XSS(
                $firstletter
            ).'&selecteval='.Security::remove_XSS($_GET['selecteval'])
        );
        exit;
    }
}

$interbreadcrumb[] = ['url' => Category::getUrl(), 'name' => get_lang('Gradebook')];
$interbreadcrumb[] = [
    'url' => 'gradebook_view_result.php?selecteval='.Security::remove_XSS($_GET['selecteval']).'&'.api_get_cidreq(),
    'name' => get_lang('ViewResult'),
];
Display::display_header(get_lang('AddUserToEval'));
if (isset($_GET['erroroneuser'])) {
    echo Display::return_message(get_lang('AtLeastOneUser'), 'warning', false);
}
DisplayGradebook::display_header_result($evaluation[0], null, 0, 0);
echo '<div class="main">';
echo $add_user_form->toHtml();
echo '</div>';
Display::display_footer();
