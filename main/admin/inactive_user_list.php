<?php
/* For licensing terms, see /license.txt */
/**
    @author Andre Boivin base code
	@author Julio Montoya fixing lot of little details  
	@todo this script is not ready for a production use that's why I'm commenting the function delete_inactive_student
	
*	@package chamilo.admin
*	script pour effacer les user inactif depuis x temps
*/

// name of the language file that needs to be included
$language_file = array ('registration','admin');
$cidReset = true;
require_once '../inc/global.inc.php';

$tbl_stats_access 		= Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_ACCESS);
$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script(true);

/**
*	Make sure this function is protected because it does NOT check password!
*
*/

/**		INIT SECTION
*/
Display :: display_header($tool_name, "");

//On sélectionne les user élèves
$sql = "SELECT user_id FROM ".$table_user." user WHERE user.status= '5' ORDER by lastname " ;
$result = Database::query($sql);
	
while($row = Database::fetch_array($result)) {   
    $user_id = $row['user_id'];
    //  pour chaque élève, on trouve la dernière connexion
    //$last_connection_date = UserManager:: delete_inactive_student($user_id, 2, true);
}
    
/*  		FOOTER    */
Display :: display_footer();
