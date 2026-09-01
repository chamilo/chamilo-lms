<?php

/* For licensing terms, see /license.txt */

// Storage API
// PHP Backend
// CBlue SPRL, Jean-Karim Bockstael, <jeankarim@cblue.be>

require_once '../inc/global.inc.php';

// Every action of this API reads or writes learning data belonging to a given
// user, so an authenticated session is required before dispatching anything.
api_block_anonymous_users(false);

// variable cleaning... the request is not used any further: the storage
// functions below only receive these normalized values and each of them
// escapes or casts its own parameters before building a query.
$action = $_REQUEST['action'] ?? '';
$svKey = $_REQUEST['svkey'] ?? '';
$svValue = $_REQUEST['svvalue'] ?? '';
$svUser = isset($_REQUEST['svuser']) ? (int) $_REQUEST['svuser'] : 0;
$svCourse = isset($_REQUEST['svcourse']) ? (int) $_REQUEST['svcourse'] : 0;
$svSco = isset($_REQUEST['svsco']) ? (int) $_REQUEST['svsco'] : 0;
$svLength = isset($_REQUEST['svlength']) ? (int) $_REQUEST['svlength'] : 0;
$svAsc = isset($_REQUEST['svasc']) ? (int) $_REQUEST['svasc'] : 0;

switch ($action) {
    case "get":
        if (storage_can_access($svUser)) {
            echo storage_get($svUser, $svCourse, $svSco, $svKey);
        }
        break;
    case "set":
        if (storage_can_access($svUser)) {
            echo storage_set($svUser, $svCourse, $svSco, $svKey, $svValue);
        }
        break;
    case "getall":
        if (storage_can_access($svUser)) {
            echo storage_getall($svUser, $svCourse, $svSco);
        }
        break;
    case "stackpush":
        if (storage_can_access($svUser)) {
            echo storage_stack_push($svUser, $svCourse, $svSco, $svKey, $svValue);
        }
        break;
    case "stackpop":
        if (storage_can_access($svUser)) {
            echo storage_stack_pop($svUser, $svCourse, $svSco, $svKey);
        }
        break;
    case "stacklength":
        if (storage_can_access($svUser)) {
            echo storage_stack_length($svUser, $svCourse, $svSco, $svKey);
        }
        break;
    case "stackclear":
        if (storage_can_access($svUser)) {
            echo storage_stack_clear($svUser, $svCourse, $svSco, $svKey);
        }
        break;
    case "stackgetall":
        if (storage_can_access($svUser)) {
            echo storage_stack_getall($svUser, $svCourse, $svSco, $svKey);
        }
        break;
    case "getposition":
        if (storage_can_access($svUser)) {
            echo storage_get_position($svUser, $svCourse, $svSco, $svKey, $svAsc);
        }
        break;
    case "getleaders":
        if (storage_can_access($svUser)) {
            echo storage_get_leaders($svUser, $svCourse, $svSco, $svKey, $svAsc, $svLength);
        }
        break;
    case "usersgetall":
// security issue
        echo "NOT allowed, security issue, see sources";
//		print storage_get_all_users();
        break;
    default:
        // Do nothing
}

function storage_can_access($sv_user)
{
    // platform admin can read/change any user's stored values, other users can
    // only read/change their own values
    $allowed = ((api_is_platform_admin()) || (!empty($sv_user) && $sv_user == api_get_user_id()));
    if (!$allowed) {
        echo "ERROR : Not allowed";
    }

    return $allowed;
}

function storage_get($sv_user, $sv_course, $sv_sco, $sv_key)
{
    $row = Database::select(
        'sv_value',
        Database::get_main_table(TABLE_TRACK_STORED_VALUES),
        [
            'where' => [
                'user_id = ? AND sco_id = ? AND course_id = ? AND sv_key = ?' => [
                    (int) $sv_user,
                    (int) $sv_sco,
                    (int) $sv_course,
                    $sv_key,
                ],
            ],
        ],
        'first'
    );

    if (empty($row)) {
        return null;
    }

    return Security::remove_XSS($row['sv_value']);
}

function storage_get_leaders($sv_user, $sv_course, $sv_sco, $sv_key, $sv_asc, $sv_length)
{
    // only the course staff may see the personal data of the other learners
    $showPersonalData = api_is_platform_admin() || api_is_allowed_to_edit(false, true);
    $columns = 'u.user_id, firstname, lastname';
    if ($showPersonalData) {
        $columns .= ', email, username';
    }
    $columns .= ', sv_value as value';

    // get leaders
    $rows = Database::select(
        $columns,
        Database::get_main_table(TABLE_TRACK_STORED_VALUES).' sv, '
            .Database::get_main_table(TABLE_MAIN_USER).' u',
        [
            'where' => [
                'u.user_id = sv.user_id AND sco_id = ? AND course_id = ? AND sv_key = ?' => [
                    (int) $sv_sco,
                    (int) $sv_course,
                    $sv_key,
                ],
            ],
            'order' => 'sv_value '.($sv_asc ? 'ASC' : 'DESC'),
            // the caller must not be able to dump the whole table at once
            'limit' => min(max((int) $sv_length, 1), 100),
        ]
    );

    $result = [];
    foreach ($rows as $row) {
        $row["values"] = [];
        $row['value'] = Security::remove_XSS($row['value']);
        $result[] = $row;
    }

    return json_encode($result);
}

function storage_get_position($sv_user, $sv_course, $sv_sco, $sv_key, $sv_asc)
{
    $sv_user = (int) $sv_user;
    $sv_course = (int) $sv_course;
    $sv_sco = (int) $sv_sco;
    $sv_key = Database::escape_string($sv_key);
    $comparison = $sv_asc ? '<=' : '>=';
    $table = Database::get_main_table(TABLE_TRACK_STORED_VALUES);

    $sql = "select count(list.user_id) as position
        from $table search, $table list
        where search.user_id = $sv_user
        and search.sco_id = $sv_sco
        and search.course_id = '$sv_course'
        and search.sv_key = '$sv_key'
        and list.sv_value $comparison search.sv_value
        and list.sco_id = search.sco_id
        and list.course_id = search.course_id
        and list.sv_key = search.sv_key
        order by list.sv_value";
    $res = Database::query($sql);
    if (Database::num_rows($res) > 0) {
        $row = Database::fetch_assoc($res);

        return $row['position'];
    } else {
        return null;
    }
}

function storage_set($sv_user, $sv_course, $sv_sco, $sv_key, $sv_value)
{
    $sv_user = (int) $sv_user;
    $sv_course = (int) $sv_course;
    $sv_sco = (int) $sv_sco;
    $sv_key = Database::escape_string($sv_key);
    $sv_value = Database::escape_string($sv_value);

    $sql = "replace into ".Database::get_main_table(TABLE_TRACK_STORED_VALUES)."
        (user_id, sco_id, course_id, sv_key, sv_value)
        values
        ($sv_user, $sv_sco, '$sv_course', '$sv_key', '$sv_value')";
    $res = Database::query($sql);

    return Database::affected_rows($res);
}

function storage_getall($sv_user, $sv_course, $sv_sco)
{
    $rows = Database::select(
        'sv_key, sv_value',
        Database::get_main_table(TABLE_TRACK_STORED_VALUES),
        [
            'where' => [
                'user_id = ? AND sco_id = ? AND course_id = ?' => [
                    (int) $sv_user,
                    (int) $sv_sco,
                    (int) $sv_course,
                ],
            ],
        ]
    );

    $data = [];
    foreach ($rows as $row) {
        $row['sv_value'] = Security::remove_XSS($row['sv_value']);
        $row['sv_key'] = Security::remove_XSS($row['sv_key']);
        $data[] = $row;
    }

    return json_encode($data);
}

function storage_stack_push($sv_user, $sv_course, $sv_sco, $sv_key, $sv_value)
{
    $sv_user = (int) $sv_user;
    $sv_course = (int) $sv_course;
    $sv_sco = (int) $sv_sco;
    $table = Database::get_main_table(TABLE_TRACK_STORED_VALUES_STACK);

    Database::query("start transaction");
    $row = Database::select(
        'ifnull(max(stack_order), 0) as stack_order',
        $table,
        [
            'where' => [
                'user_id = ? AND sco_id = ? AND course_id = ? AND sv_key = ?' => [
                    $sv_user,
                    $sv_sco,
                    $sv_course,
                    $sv_key,
                ],
            ],
        ],
        'first'
    );

    $resinsert = false;
    if (!empty($row)) {
        $resinsert = Database::insert(
            $table,
            [
                'user_id' => $sv_user,
                'sco_id' => $sv_sco,
                'course_id' => $sv_course,
                'sv_key' => $sv_key,
                'stack_order' => 1 + (int) $row['stack_order'],
                'sv_value' => $sv_value,
            ]
        );
    }

    if (!empty($row) && false !== $resinsert) {
        Database::query("commit");

        return 1;
    } else {
        Database::query("rollback");

        return 0;
    }
}

function storage_stack_pop($sv_user, $sv_course, $sv_sco, $sv_key)
{
    $sv_user = (int) $sv_user;
    $sv_course = (int) $sv_course;
    $sv_sco = (int) $sv_sco;
    $table = Database::get_main_table(TABLE_TRACK_STORED_VALUES_STACK);

    Database::query("start transaction");
    $rowselect = Database::select(
        'sv_value, stack_order',
        $table,
        [
            'where' => [
                'user_id = ? AND sco_id = ? AND course_id = ? AND sv_key = ?' => [
                    $sv_user,
                    $sv_sco,
                    $sv_course,
                    $sv_key,
                ],
            ],
            'order' => 'stack_order desc',
            'limit' => 1,
        ],
        'first'
    );

    if (empty($rowselect)) {
        Database::query("rollback");

        return null;
    }

    Database::delete(
        $table,
        [
            'user_id = ? AND sco_id = ? AND course_id = ? AND sv_key = ? AND stack_order = ?' => [
                $sv_user,
                $sv_sco,
                $sv_course,
                $sv_key,
                (int) $rowselect['stack_order'],
            ],
        ]
    );
    Database::query("commit");

    return Security::remove_XSS($rowselect['sv_value']);
}

function storage_stack_length($sv_user, $sv_course, $sv_sco, $sv_key)
{
    return Database::select(
        '*',
        Database::get_main_table(TABLE_TRACK_STORED_VALUES_STACK),
        [
            'where' => [
                'user_id = ? AND sco_id = ? AND course_id = ? AND sv_key = ?' => [
                    (int) $sv_user,
                    (int) $sv_sco,
                    (int) $sv_course,
                    $sv_key,
                ],
            ],
        ],
        'count'
    );
}

function storage_stack_clear($sv_user, $sv_course, $sv_sco, $sv_key)
{
    return Database::delete(
        Database::get_main_table(TABLE_TRACK_STORED_VALUES_STACK),
        [
            'user_id = ? AND sco_id = ? AND course_id = ? AND sv_key = ?' => [
                (int) $sv_user,
                (int) $sv_sco,
                (int) $sv_course,
                $sv_key,
            ],
        ]
    );
}

function storage_stack_getall($sv_user, $sv_course, $sv_sco, $sv_key)
{
    $rows = Database::select(
        'stack_order as stack_order, sv_value as value',
        Database::get_main_table(TABLE_TRACK_STORED_VALUES_STACK),
        [
            'where' => [
                'user_id = ? AND sco_id = ? AND course_id = ? AND sv_key = ?' => [
                    (int) $sv_user,
                    (int) $sv_sco,
                    (int) $sv_course,
                    $sv_key,
                ],
            ],
        ]
    );

    $results = [];
    foreach ($rows as $row) {
        $row['value'] = Security::remove_XSS($row['value']);
        $results[] = $row;
    }

    return json_encode($results);
}

function storage_get_all_users()
{
    $sql = "select user_id, username, firstname, lastname
        from ".Database::get_main_table(TABLE_MAIN_USER)."
        order by user_id asc";
    $res = Database::query($sql);
    $results = [];
    while ($row = Database::fetch_assoc($res)) {
        $results[] = $row;
    }

    return json_encode($results);
}
