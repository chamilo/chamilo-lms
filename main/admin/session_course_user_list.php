<?php
/* For licensing terms, see /license.txt */
/**
*	@package chamilo.admin
*/
/**
 * Code
 */
$language_file = array('admin', 'registration');
$cidReset = true;

require_once '../inc/global.inc.php';

$tbl_user                           = Database::get_main_table(TABLE_MAIN_USER);
$tbl_course                         = Database::get_main_table(TABLE_MAIN_COURSE);
$tbl_session                        = Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_session_rel_course             = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_session_rel_user               = Database::get_main_table(TABLE_MAIN_SESSION_USER);
$tbl_session_rel_course_rel_user    = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

$id_session = intval($_GET['id_session']);
SessionManager::protect_session_edit($id_session);

if (empty($id_session )) {
    api_not_allowed();
}

$course_code    = Database::escape_string(trim($_GET['course_code']));
$page           = intval($_GET['page']);
$action         = $_REQUEST['action'];
$default_sort = api_sort_by_first_name() ? 'firstname':'lastname';
$sort           = in_array($_GET['sort'], array('lastname','firstname','username')) ? $_GET['sort'] : $default_sort;
$idChecked      = (is_array($_GET['idChecked']) ? $_GET['idChecked'] : (is_array($_POST['idChecked']) ? $_POST['idChecked'] : null));

$direction      = isset($_GET['direction']) && in_array($_GET['direction'], array('desc','asc')) ? $_GET['direction'] : 'desc';

if (is_array($idChecked)) {
	$my_temp = array();
	foreach ($idChecked as $id){
		$my_temp[]= intval($id);// forcing the intval
	}
	$idChecked = $my_temp;
}

$sql = "SELECT s.name, c.title  FROM $tbl_session_rel_course src
		INNER JOIN $tbl_session s ON s.id = src.id_session
		INNER JOIN $tbl_course c ON c.code = src.course_code
		WHERE src.id_session='$id_session' AND src.course_code='".Database::escape_string($course_code)."' ";

$result = Database::query($sql);

if (!list($session_name,$course_title)=Database::fetch_row($result)) {
	header('Location: session_course_list.php?id_session='.$id_session);
	exit();
}


switch($action) {
    case 'delete':
        if (is_array($idChecked) && count($idChecked)>0 ) {
            array_map('intval', $idChecked);
            $idChecked = implode(',',$idChecked);
        }
        if (!empty($idChecked)) {
            Database::query("DELETE FROM $tbl_session_rel_course_rel_user WHERE id_session='$id_session' AND course_code='".$course_code."' AND id_user IN($idChecked)");
            $nbr_affected_rows = Database::affected_rows();
            Database::query("UPDATE $tbl_session_rel_course SET nbr_users=nbr_users-$nbr_affected_rows WHERE id_session='$id_session' AND course_code='".$course_code."'");
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

//scru.status<>2  scru.course_code='".$course_code."'
$sql = "SELECT DISTINCT u.user_id,".($is_western_name_order ? 'u.firstname, u.lastname' : 'u.lastname, u.firstname').", u.username, scru.id_user as is_subscribed
             FROM $tbl_session_rel_user s INNER JOIN $tbl_user u ON (u.user_id=s.id_user) LEFT JOIN $tbl_session_rel_course_rel_user scru ON (u.user_id=scru.id_user AND  scru.course_code = '".$course_code."' )
             WHERE s.id_session='$id_session'
             ORDER BY $sort $direction LIMIT $from,".($limit+1);

if ($direction == 'desc') {
    $direction = 'asc';
} else {
    $direction = 'desc';
}

$result = Database::query($sql);
$Users  = Database::store_result($result);

$nbr_results = sizeof($Users);

$tool_name = get_lang('Session').': '.$session_name.' - '.get_lang('Course').': '.$course_title;

$interbreadcrumb[] = array("url" => "index.php","name" => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array("url" => "session_list.php","name" => get_lang('SessionList'));
$interbreadcrumb[] = array('url' => "resume_session.php?id_session=".$id_session,"name" => get_lang('SessionOverview'));
//$interbreadcrumb[]=array("url" => "session_course_list.php?id_session=$id_session","name" => get_lang('ListOfCoursesOfSession')." &quot;".api_htmlentities($session_name,ENT_QUOTES,$charset)."&quot;");

Display::display_header($tool_name);

echo Display::page_header($tool_name);
?>
<form method="post" action="<?php echo api_get_self(); ?>?id_session=<?php echo $id_session; ?>&course_code=<?php echo urlencode($course_code); ?>&sort=<?php echo $sort; ?>" onsubmit="javascript:if(!confirm('<?php echo get_lang('ConfirmYourChoice'); ?>')) return false;">
<div align="right">
<?php
if($page) {
?>
<a href="<?php echo api_get_self(); ?>?id_session=<?php echo $id_session; ?>&course_code=<?php echo urlencode($course_code); ?>&page=<?php echo $page-1; ?>&sort=<?php echo $sort; ?>"><?php echo get_lang('Previous');?></a>
<?php
} else {
	echo get_lang('Previous');
}
?>
|
<?php
if($nbr_results > $limit) {
?>
<a href="<?php echo api_get_self(); ?>?id_session=<?php echo $id_session; ?>&course_code=<?php echo urlencode($course_code); ?>&page=<?php echo $page+1; ?>&sort=<?php echo $sort; ?>"><?php echo get_lang('Next');?></a>
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
  <th><a href="<?php echo api_get_self(); ?>?id_session=<?php echo $id_session; ?>&course_code=<?php echo urlencode($course_code); ?>&sort=firstname&direction=<?php echo urlencode($direction); ?>"><?php echo get_lang('FirstName');?></a></th>
  <th><a href="<?php echo api_get_self(); ?>?id_session=<?php echo $id_session; ?>&course_code=<?php echo urlencode($course_code); ?>&sort=lastname&direction=<?php echo urlencode($direction); ?>"><?php echo get_lang('LastName');?></a></th>
  <?php } else { ?>
  <th><a href="<?php echo api_get_self(); ?>?id_session=<?php echo $id_session; ?>&course_code=<?php echo urlencode($course_code); ?>&sort=lastname&direction=<?php echo urlencode($direction); ?>"><?php echo get_lang('LastName');?></a></th>
  <th><a href="<?php echo api_get_self(); ?>?id_session=<?php echo $id_session; ?>&course_code=<?php echo urlencode($course_code); ?>&sort=firstname&direction=<?php echo urlencode($direction); ?>"><?php echo get_lang('FirstName');?></a></th>
  <?php } ?>
  <th><a href="<?php echo api_get_self(); ?>?id_session=<?php echo $id_session; ?>&course_code=<?php echo urlencode($course_code); ?>&sort=username&direction=<?php echo urlencode($direction); ?>"><?php echo get_lang('Login');?></a></th>
  <th><?php echo get_lang('Actions');?></th>
</tr>

<?php
$i=0;

foreach ($Users as $key=>$enreg) {
	if ($key == $limit) {
		break;
	}
    ?>

    <tr class="<?php echo $i?'row_odd':'row_even'; ?>">
        <td><input type="checkbox" name="idChecked[]" value="<?php echo $enreg['user_id']; ?>"></td>
        <?php if ($is_western_name_order) { ?>
        <td><?php echo api_htmlentities($enreg['firstname'],ENT_QUOTES,$charset); ?></td>
        <td><?php echo api_htmlentities($enreg['lastname'],ENT_QUOTES,$charset); ?></td>
        <?php } else { ?>
        <td><?php echo api_htmlentities($enreg['lastname'],ENT_QUOTES,$charset); ?></td>
        <td><?php echo api_htmlentities($enreg['firstname'],ENT_QUOTES,$charset); ?></td>
        <?php } ?>
        <td><?php echo api_htmlentities($enreg['username'],ENT_QUOTES,$charset); ?></td>
        <td>
        <?php if ($enreg['is_subscribed']) { ?>
            <a href="<?php echo api_get_self(); ?>?id_session=<?php echo $id_session; ?>&course_code=<?php echo urlencode($course_code); ?>&sort=<?php echo $sort; ?>&action=delete&idChecked[]=<?php echo $enreg['user_id']; ?>" onclick="javascript:if(!confirm('<?php echo get_lang('ConfirmYourChoice'); ?>')) return false;">
                <?php Display::display_icon('delete.png', get_lang('Delete')); ?>
            </a>
        <?php } else  { ?>
            <a href="<?php echo api_get_self(); ?>?id_session=<?php echo $id_session; ?>&course_code=<?php echo urlencode($course_code); ?>&sort=<?php echo $sort; ?>&action=add&idChecked[]=<?php echo $enreg['user_id']; ?>" onclick="javascript:if(!confirm('<?php echo get_lang('ConfirmYourChoice'); ?>')) return false;">
                <?php Display::display_icon('add.png', get_lang('Add'), array(), ICON_SIZE_SMALL); ?>
            </a>
        <?php }  ?>

        </td>
    </tr>
    <?php
        $i=$i ? 0 : 1;
}
unset($Users);
?>

</table>
<br />
<div align="left">
    <?php
    if($page) {
    ?>
    <a href="<?php echo api_get_self(); ?>?id_session=<?php echo $id_session; ?>&course_code=<?php echo urlencode($course_code); ?>&page=<?php echo $page-1; ?>&sort=<?php echo $sort; ?>"><?php echo get_lang('Previous'); ?></a>
    <?php
    } else {
        echo get_lang('Previous');
    }
    ?>
    |
    <?php
    if($nbr_results > $limit) {
    ?>
    <a href="<?php echo api_get_self(); ?>?id_session=<?php echo $id_session; ?>&course_code=<?php echo urlencode($course_code); ?>&page=<?php echo $page+1; ?>&sort=<?php echo $sort; ?>"><?php echo get_lang('Next'); ?></a>
    <?php
    } else {
        echo get_lang('Next');
    }
    ?>
</div>
<br />
<select name="action">
<option value="delete"><?php echo get_lang('UnsubscribeSelectedUsersFromSession');?></option>
<option value="add"><?php echo get_lang('AddUsers');?></option>
</select>
<button class="save" type="submit"> <?php echo get_lang('Ok'); ?></button>
</form>
<?php
Display::display_footer();
