<?php

/* For licensing terms, see /license.txt */
/**
 * Special reporting page for admins.
 */
ob_start();
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_TRACKING;

$nameTools = get_lang('Administrators');

api_block_anonymous_users();
$interbreadcrumb[] = ["url" => "index.php", "name" => get_lang('MySpace')];
Display::display_header($nameTools);

api_display_tool_title($nameTools);

// Database Table Definitions
$tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
$tbl_user = Database::get_main_table(TABLE_MAIN_USER);
$tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_session_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);
$tbl_admin = Database::get_main_table(TABLE_MAIN_ADMIN);

if (isset($_POST['export'])) {
    $order_clause = api_is_western_name_order(PERSON_NAME_DATA_EXPORT) ? ' ORDER BY firstname, lastname' : ' ORDER BY lastname, firstname';
} else {
    $order_clause = api_sort_by_first_name() ? ' ORDER BY firstname, lastname' : ' ORDER BY lastname, firstname';
}
$sql = "SELECT user.user_id,lastname,firstname,email
        FROM $tbl_user as user, $tbl_admin as admin
        WHERE admin.user_id=user.user_id".$order_clause;
$result_admins = Database::query($sql);

if (api_is_western_name_order()) {
    echo '<table class="table table-hover table-striped data_table">
        <tr>
        <th>'.get_lang('FirstName').'</th>
        <th>'.get_lang('LastName').'</th>
        <th>'.get_lang('Email').'</th></tr>';
} else {
    echo '<table class="table table-hover table-striped data_table">
        <tr>
            <th>'.get_lang('LastName').'</th>
            <th>'.get_lang('FirstName').'</th>
            <th>'.get_lang('Email').'</th></tr>';
}

if (api_is_western_name_order(PERSON_NAME_DATA_EXPORT)) {
    $header[] = get_lang('FirstName', '');
    $header[] = get_lang('LastName', '');
} else {
    $header[] = get_lang('LastName', '');
    $header[] = get_lang('FirstName', '');
}
$header[] = get_lang('Email', '');

if (Database::num_rows($result_admins) > 0) {
    while ($admins = Database::fetch_array($result_admins)) {
        $user_id = $admins["user_id"];
        $lastname = $admins["lastname"];
        $firstname = $admins["firstname"];
        $email = $admins["email"];

        if ($i % 2 == 0) {
            $css_class = "row_odd";
            if ($i % 20 == 0 && $i != 0) {
                if (api_is_western_name_order()) {
                    echo '<tr><th>'.get_lang('FirstName').'</th><th>'.get_lang('LastName').'</th><th>'.get_lang('Email').'</th></tr>';
                } else {
                    echo '<tr><th>'.get_lang('LastName').'</th><th>'.get_lang('FirstName').'</th><th>'.get_lang('Email').'</th></tr>';
                }
            }
        } else {
            $css_class = "row_even";
        }

        $i++;

        if (api_is_western_name_order()) {
            echo "<tr class=".$css_class."><td>$firstname</td><td>$lastname</td><td><a href='mailto:".$email."'>$email</a></td></tr>";
        } else {
            echo "<tr class=".$css_class."><td>$lastname</td><td>$firstname</td><td><a href='mailto:".$email."'>$email</a></td></tr>";
        }

        if (api_is_western_name_order(PERSON_NAME_DATA_EXPORT)) {
            $data[$user_id]["firstname"] = $firstname;
            $data[$user_id]["lastname"] = $lastname;
        } else {
            $data[$user_id]["lastname"] = $lastname;
            $data[$user_id]["firstname"] = $firstname;
        }
        $data[$user_id]["email"] = $email;
    }
} else {
    // No results
    echo '<tr><td colspan="3">'.get_lang('NoResults').'</td></tr>';
}
echo '</table>';

if (isset($_POST['export'])) {
    export_csv($header, $data, 'administrators.csv');
}

echo "
    <br /><br />
    <form method='post' action='admin.php'>
        <button type='submit' class='save' name='export' value='".get_lang('ExportExcel')."'>
            ".get_lang('ExportExcel')."
        </button>
    <form>
";

Display::display_footer();
