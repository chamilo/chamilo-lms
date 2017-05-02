<?php
/* For licensing terms, see /license.txt */

/**
*	@package chamilo.admin
*/
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$tbl_user                           = Database::get_main_table(TABLE_MAIN_USER);
$tbl_course                         = Database::get_main_table(TABLE_MAIN_COURSE);
$tbl_session                        = Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_session_rel_course             = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_session_rel_user               = Database::get_main_table(TABLE_MAIN_SESSION_USER);
$tbl_session_rel_course_rel_user    = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

$id_session = intval($_GET['id_session']);
SessionManager::protectSession($id_session);

if (empty($id_session)) {
    api_not_allowed();
}

$course_code = Database::escape_string(trim($_GET['course_code']));
$courseInfo = api_get_course_info($course_code);
$courseId = $courseInfo['real_id'];

$page           = isset($_GET['page']) ? intval($_GET['page']) : null;
$action         = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;
$default_sort   = api_sort_by_first_name() ? 'firstname' : 'lastname';
$sort           = isset($_GET['sort']) && in_array($_GET['sort'], array('lastname', 'firstname', 'username')) ? $_GET['sort'] : $default_sort;
$idChecked      = isset($_GET['idChecked']) && is_array($_GET['idChecked']) ? $_GET['idChecked'] : (isset($_POST['idChecked']) && is_array($_POST['idChecked']) ? $_POST['idChecked'] : null);
$direction      = isset($_GET['direction']) && in_array($_GET['direction'], array('desc', 'asc')) ? $_GET['direction'] : 'desc';

if (is_array($idChecked)) {
    $my_temp = array();
    foreach ($idChecked as $id) {
        // forcing the intval
        $my_temp[] = intval($id);
    }
    $idChecked = $my_temp;
}

$sql = "SELECT s.name, c.title
        FROM $tbl_session_rel_course src
		INNER JOIN $tbl_session s ON s.id = src.session_id
		INNER JOIN $tbl_course c ON c.id = src.c_id
		WHERE src.session_id='$id_session' AND src.c_id='$courseId' ";

$result = Database::query($sql);
if (!list($session_name, $course_title) = Database::fetch_row($result)) {
    header('Location: session_course_list.php?id_session='.$id_session);
    exit();
}

switch ($action) {
    case 'delete':
        if (is_array($idChecked) && count($idChecked) > 0) {
            array_map('intval', $idChecked);
            $idChecked = implode(',', $idChecked);
        }
        if (!empty($idChecked)) {
            $sql = "DELETE FROM $tbl_session_rel_course_rel_user
                    WHERE session_id='$id_session' AND c_id='".$courseId."' AND user_id IN($idChecked)";
            $result = Database::query($sql);
            $nbr_affected_rows = Database::affected_rows($result);
            $sql = "UPDATE $tbl_session_rel_course SET nbr_users=nbr_users-$nbr_affected_rows
                    WHERE session_id='$id_session' AND c_id='".$courseId."'";
            Database::query($sql);
        }
        header('Location: '.api_get_self().'?id_session='.$id_session.'&course_code='.urlencode($course_code).'&sort='.$sort);
        exit();
        break;
    case 'add':
        SessionManager::subscribe_users_to_session_course($idChecked, $id_session, $course_code);
        header('Location: '.api_get_self().'?id_session='.$id_session.'&course_code='.urlencode($course_code).'&sort='.$sort);
        exit;
        break;
}


$limit  = 20;
$from   = $page * $limit;
$is_western_name_order = api_is_western_name_order();
$sql = "SELECT DISTINCT
         u.user_id,".($is_western_name_order ? 'u.firstname, u.lastname' : 'u.lastname, u.firstname').", u.username, scru.user_id as is_subscribed
         FROM $tbl_session_rel_user s
         INNER JOIN $tbl_user u ON (u.user_id=s.user_id)
         LEFT JOIN $tbl_session_rel_course_rel_user scru
         ON (s.session_id = scru.session_id AND s.user_id = scru.user_id AND scru.c_id = '".$courseId."' )
         WHERE s.session_id='$id_session'
         ORDER BY $sort $direction
         LIMIT $from,".($limit + 1);

if ($direction == 'desc') {
    $direction = 'asc';
} else {
    $direction = 'desc';
}

$result = Database::query($sql);
$users  = Database::store_result($result);

$nbr_results = sizeof($users);

$tool_name = get_lang('Session').': '.$session_name.' - '.get_lang('Course').': '.$course_title;

//$interbreadcrumb[] = array("url" => "index.php","name" => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array("url" => "session_list.php", "name" => get_lang('SessionList'));
$interbreadcrumb[] = array('url' => "resume_session.php?id_session=".$id_session, "name" => get_lang('SessionOverview'));

Display::display_header($tool_name);

echo Display::page_header($tool_name);
?>
<form method="post" action="<?php echo api_get_self(); ?>?id_session=<?php echo $id_session; ?>&course_code=<?php echo urlencode($course_code); ?>&sort=<?php echo $sort; ?>" onsubmit="javascript:if(!confirm('<?php echo get_lang('ConfirmYourChoice'); ?>')) return false;">
<div align="right">
<?php
if ($page) {
?>
<a href="<?php echo api_get_self(); ?>?id_session=<?php echo $id_session; ?>&course_code=<?php echo urlencode($course_code); ?>&page=<?php echo $page - 1; ?>&sort=<?php echo $sort; ?>"><?php echo get_lang('Previous'); ?></a>
<?php
} else {
    echo get_lang('Previous');
}
?>
|
<?php
if ($nbr_results > $limit) {
?>
<a href="<?php echo api_get_self(); ?>?id_session=<?php echo $id_session; ?>&course_code=<?php echo urlencode($course_code); ?>&page=<?php echo $page + 1; ?>&sort=<?php echo $sort; ?>"><?php echo get_lang('Next'); ?></a>
<?php
} else {
    echo get_lang('Next');
}
?>
</div>
<br />
<table class="data_table" width="100%">
<tr>
  <th>&nbsp;</th>
  <?php if ($is_western_name_order) { ?>
  <th><a href="<?php echo api_get_self(); ?>?id_session=<?php echo $id_session; ?>&course_code=<?php echo urlencode($course_code); ?>&sort=firstname&direction=<?php echo urlencode($direction); ?>"><?php echo get_lang('FirstName'); ?></a></th>
  <th><a href="<?php echo api_get_self(); ?>?id_session=<?php echo $id_session; ?>&course_code=<?php echo urlencode($course_code); ?>&sort=lastname&direction=<?php echo urlencode($direction); ?>"><?php echo get_lang('LastName'); ?></a></th>
  <?php } else { ?>
  <th><a href="<?php echo api_get_self(); ?>?id_session=<?php echo $id_session; ?>&course_code=<?php echo urlencode($course_code); ?>&sort=lastname&direction=<?php echo urlencode($direction); ?>"><?php echo get_lang('LastName'); ?></a></th>
  <th><a href="<?php echo api_get_self(); ?>?id_session=<?php echo $id_session; ?>&course_code=<?php echo urlencode($course_code); ?>&sort=firstname&direction=<?php echo urlencode($direction); ?>"><?php echo get_lang('FirstName'); ?></a></th>
  <?php } ?>
  <th><a href="<?php echo api_get_self(); ?>?id_session=<?php echo $id_session; ?>&course_code=<?php echo urlencode($course_code); ?>&sort=username&direction=<?php echo urlencode($direction); ?>"><?php echo get_lang('Login'); ?></a></th>
  <th><?php echo get_lang('Actions'); ?></th>
</tr>
<?php
$i = 0;
foreach ($users as $key => $enreg) {

    if ($key == $limit) {
        break;
    }
    ?>
    <tr class="<?php echo $i ? 'row_odd' : 'row_even'; ?>">
        <td><input type="checkbox" name="idChecked[]" value="<?php echo $enreg['user_id']; ?>"></td>
        <?php if ($is_western_name_order) { ?>
        <td><?php echo api_htmlentities($enreg['firstname'], ENT_QUOTES, $charset); ?></td>
        <td><?php echo api_htmlentities($enreg['lastname'], ENT_QUOTES, $charset); ?></td>
        <?php } else { ?>
        <td><?php echo api_htmlentities($enreg['lastname'], ENT_QUOTES, $charset); ?></td>
        <td><?php echo api_htmlentities($enreg['firstname'], ENT_QUOTES, $charset); ?></td>
        <?php } ?>
        <td><?php echo api_htmlentities($enreg['username'], ENT_QUOTES, $charset); ?></td>
        <td>
        <?php if ($enreg['is_subscribed']) { ?>
            <a href="<?php echo api_get_self(); ?>?id_session=<?php echo $id_session; ?>&course_code=<?php echo urlencode($course_code); ?>&sort=<?php echo $sort; ?>&action=delete&idChecked[]=<?php echo $enreg['user_id']; ?>" onclick="javascript:if(!confirm('<?php echo get_lang('ConfirmYourChoice'); ?>')) return false;">
                <?php Display::display_icon('delete.png', get_lang('Delete')); ?>
            </a>
        <?php } else { ?>
            <a href="<?php echo api_get_self(); ?>?id_session=<?php echo $id_session; ?>&course_code=<?php echo urlencode($course_code); ?>&sort=<?php echo $sort; ?>&action=add&idChecked[]=<?php echo $enreg['user_id']; ?>" onclick="javascript:if(!confirm('<?php echo get_lang('ConfirmYourChoice'); ?>')) return false;">
                <?php Display::display_icon('add.png', get_lang('Add'), array(), ICON_SIZE_SMALL); ?>
            </a>
        <?php }  ?>

        </td>
    </tr>
    <?php
        $i = $i ? 0 : 1;
}
unset($users);
?>
</table>
<br />
<div align="left">
    <?php
    if ($page) {
    ?>
    <a href="<?php echo api_get_self(); ?>?id_session=<?php echo $id_session; ?>&course_code=<?php echo urlencode($course_code); ?>&page=<?php echo $page - 1; ?>&sort=<?php echo $sort; ?>"><?php echo get_lang('Previous'); ?></a>
    <?php
    } else {
        echo get_lang('Previous');
    }
    ?>
    |
    <?php
    if ($nbr_results > $limit) {
    ?>
    <a href="<?php echo api_get_self(); ?>?id_session=<?php echo $id_session; ?>&course_code=<?php echo urlencode($course_code); ?>&page=<?php echo $page + 1; ?>&sort=<?php echo $sort; ?>"><?php echo get_lang('Next'); ?></a>
    <?php
    } else {
        echo get_lang('Next');
    }
    ?>
</div>
<br />
<select name="action">
<option value="delete"><?php echo get_lang('UnsubscribeSelectedUsersFromSession'); ?></option>
<option value="add"><?php echo get_lang('AddUsers'); ?></option>
</select>
<button class="save" type="submit"> <?php echo get_lang('Ok'); ?></button>
</form>
<?php
Display::display_footer();
