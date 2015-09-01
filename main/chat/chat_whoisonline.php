<?php
/* For licensing terms, see /license.txt */
/**
 *	Shows the list of connected users
 *
 *	@author Olivier Brouckaert
 *	@package chamilo.chat
 */

define('FRAME', 'online');

require_once '../inc/global.inc.php';

$course = api_get_course_id();
$courseInfo = api_get_course_info();
$group_id = api_get_group_id();
$session_id = api_get_session_id();
$user_id = api_get_user_id();
$group_condition = " AND to_group_id = '$group_id'";

$extra_condition = '';
if (!empty($group_id)) {
    $extra_condition = $group_condition;
}

if (!empty($course)) {
    $showPic = isset($_GET['showPic']) ? intval($_GET['showPic']) : null;
    $tbl_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
    $tbl_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
    $tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
    $tbl_session_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
    $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
    $tbl_chat_connected = Database::get_course_table(TABLE_CHAT_CONNECTED);

    $query = "SELECT username FROM $tbl_user WHERE user_id='".$user_id."'";
    $result = Database::query($query);

    list($pseudo_user) = Database::fetch_array($result);

	$isAllowed = !(empty($pseudo_user) || !$_cid);
	$isMaster = api_is_course_admin();
    $date_inter = api_get_utc_datetime(time() - 120);

    $users = array();
    $course_id = api_get_course_int_id();

    if (empty($session_id)) {
		$sql = "SELECT DISTINCT
		            t1.user_id,
		            username,
		            firstname,
		            lastname,
		            picture_uri,
		            email,
		            t3.status
                FROM $tbl_user t1, $tbl_chat_connected t2, $tbl_course_user t3
                WHERE
                    t2.c_id = $course_id AND
                    t1.user_id=t2.user_id AND
                    t3.user_id=t2.user_id AND
                    t3.relation_type<>".COURSE_RELATION_TYPE_RRHH." AND
                    t3.c_id = '".$courseInfo['real_id']."' AND
                    t2.last_connection>'".$date_inter."' $extra_condition
                ORDER BY username";

        $result = Database::query($sql);
        $users = Database::store_result($result);
	} else {
        $session_condition = api_get_session_condition($session_id, true, false, 't3.session_id');
		// select learners
		$query = "SELECT DISTINCT t1.user_id,username,firstname,lastname,picture_uri,email
                  FROM $tbl_user t1, $tbl_chat_connected t2, $tbl_session_course_user t3
		          WHERE
		          t2.c_id = $course_id AND
		          t1.user_id=t2.user_id AND t3.user_id=t2.user_id AND
		          t3.session_id = '".$session_id."' AND
		          t3.c_id = '".$courseInfo['real_id']."' AND
		          t2.last_connection>'".$date_inter."' $session_condition
		          ORDER BY username";
		$result = Database::query($query);
		while ($learner = Database::fetch_array($result)) {
            $users[$learner['user_id']] = $learner;
		}

		// select session coach
		$query = "SELECT DISTINCT t1.user_id,username,firstname,lastname,picture_uri,email
		          FROM $tbl_user t1,$tbl_chat_connected t2,$tbl_session t3
		          WHERE
					t2.c_id = $course_id AND
					t1.user_id=t2.user_id AND
					t3.id_coach=t2.user_id AND
					t3.id = '".$session_id."' AND
					t2.last_connection > '".$date_inter."'
					$extra_condition
				  ORDER BY username";
		$result = Database::query($query);
		if ($coach = Database::fetch_array($result)) {
			$users[$coach['user_id']] = $coach;
		}

		// select session course coach
		$query = "SELECT DISTINCT t1.user_id,username,firstname,lastname,picture_uri,email
				FROM $tbl_user t1,$tbl_chat_connected t2,$tbl_session_course_user t3
				WHERE
					t2.c_id = $course_id AND
					t1.user_id=t2.user_id
					AND t3.user_id =t2.user_id AND t3.status=2
					AND t3.session_id = '".$session_id."'
					AND t3.c_id = '".$course_id."'
					AND t2.last_connection>'".$date_inter."' $extra_condition
				ORDER BY username";

		$result = Database::query($query);
		$course_coaches = array();
		while ($coaches = Database::fetch_array($result)) {
			$users[$coaches['user_id']] = $coaches;
		}
	}
	require 'header_frame.inc.php';

	?>
	<div class="user-connected">
	<div id="user-online-scroll" class="user-online">
		<div class="title"><?php echo get_lang('Users'); ?> <?php echo get_lang('Connected'); ?></div>
		<div class="scrollbar"><div class="track"><div class="thumb"><div class="end"></div></div></div></div>
		<div class="viewport"><div id="hidden" class="overview">
		<ul class="profile list-group">
			<?php
            foreach ($users as & $user) {
                if (empty($session_id)) {
                    $status = $user['status'];
                } else {
                    $status = CourseManager::is_course_teacher($user['user_id'], api_get_course_id()) ? 1 : 5;
                }
                $fileUrl = UserManager::getUserPicture($user['user_id'], USER_IMAGE_SIZE_MEDIUM);
                $url_user_profile=api_get_path(WEB_CODE_PATH).'social/profile.php?u='.$user['user_id'].'&';
			?>
			<li class="list-group-item">
				<img src="<?php echo $fileUrl;?>" border="0" width="50" alt="" class="user-image-chat" />
				<div class="user-name">
					<a href="<?php echo $url_user_profile; ?>" target="_blank">
                        <?php echo api_get_person_name($user['firstname'], $user['lastname']); ?>
                    </a>
					<?php
                        if ($status == 1) {
                            echo Display::return_icon('teachers.gif', get_lang('Teacher'), array('height' => '18'));
                        } else {
                            echo Display::return_icon('students.gif', get_lang('Student'), array('height' => '18'));
                        }
					?>
				</div>
				<div class="user-email"><?php echo $user['username']; ?></div>
			</li>
			<?php
            }
            unset($users);
        ?>
		</ul>
	</div></div></div></div>
	<?php
}
require 'footer_frame.inc.php';
