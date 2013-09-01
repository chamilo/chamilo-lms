<?php
/**
 * This script fills the session_* tables with users relationships, 
 * taking into account an homogeneous distribution withing x buildings, y rooms
 * and z schedules. Not for use on production servers
 * @packacke chamilo.datafiller
 */
/**
 * Initialization - remove or comment first line to execute
 */
die('Please remove line '.__LINE__.' of this scripts if you are sure you know'."\n".'what you\'re doing'."\n");
require_once '../../main/inc/global.inc.php';
$t0 = time();
$user_offset = 10; //30 students per room
$user = 0;
$course_id = 1;
$session = 0;
$session_id = 0;
//$building_offset = 4; //number of buildings per UGEL - not active yet
$building = 0;
$room_offset = 4;
$room = 0;
$turn_offset = 4;
$turn = 0;

// Query caching
$sqlsc_header = "INSERT INTO ".TABLE_MAIN_SESSION_COURSE." (id_session, c_id, nbr_users) values ";
$sqlsu_header = "INSERT INTO ".TABLE_MAIN_SESSION_USER." (id_session, id_user) values ";
$sqlscu_header = "INSERT INTO ".TABLE_MAIN_SESSION_COURSE_USER." (id_session, c_id, id_user, visibility) values ";

$sqlsc = $sqlsc_header;
$sqlsu = $sqlsu_header;
$sqlscu = $sqlscu_header;

/**
 * Code core
 */
// Users range
$sql = "SELECT user_id FROM user WHERE username like 'user%'";
$res = Database::query($sql);
while ($rowu = Database::fetch_array($res)) {
  $user_id = $rowu['user_id'];
  echo "User id: $user_id\n";
  $user++;
  if ($user % $user_offset === 1) {
    // if we reached 30 students or so, switch turns
    $turn++;
    // we passed 30, create new session
    if ($turn % $turn_offset === 1) {
        //switch turn *and* room
        $turn = 1;
        $room++;
        if ($room % $room_offset === 1) {
            //switch room *and* building
            $room = 1;
            $building++;
        }
    }
    //flush insert buffer before inserting new session
    if ($session_id != 0) {
      $ressc = Database::query(substr($sqlsc,0,-1));
      $ressu = Database::query(substr($sqlsu,0,-1));
      $resscu = Database::query(substr($sqlscu,0,-1));
      $sqlsc = $sqlsc_header;
      $sqlsu = $sqlsu_header;
      $sqlscu = $sqlscu_header;
    }
    // now insert new session
    $params = array(
        'id_coach' => 1,
        'name' => 'Directivos - Local '.$building.' - Aula '.$room.' - Turno '.$turn,
        'nbr_courses' => 1,
        'nbr_users' => 30,
        'nbr_classes' => 0,
        'session_admin_id' => 1,
        'visibility' => SESSION_INVISIBLE,
        'session_category_id' => 0,
        'promotion_id' => 0,
        'display_start_date' => '2013-09-01 00:00:00',
        'display_end_date' => '2013-09-30 00:00:00',
        'access_start_date' => '2013-09-01 00:00:00',
        'access_end_date' => '2013-09-30 00:00:00',
        'coach_access_start_date' => '2013-09-01 00:00:00',
        'coach_access_end_date' => '2013-09-30 00:00:00' 
    );
    $session_id = SessionManager::add($params);
    if ($session_id === false) {
      die('Error inserting session with params '.print_r($params,1));
    }
    echo "New session: $session_id\n";
    $session++;
  }
  $sqlsc .= "($session_id, 1, 30),";
  $sqlsu .= "($session_id,$user_id),";
  $sqlscu .= "($session_id, 1, $user_id, 4),";
}
//flush the last pending bits
$ressc = Database::query(substr($sqlsc,0,-1));
$ressu = Database::query(substr($sqlsu,0,-1));
$resscu = Database::query(substr($sqlscu,0,-1));
echo "Inserted $user users in $session sessions, approximately $user_offset per session\n";
$tf = time() - $t0;
echo "Process took $tf for $user users\n";
