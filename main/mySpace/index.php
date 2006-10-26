<?php
/*
 * Created on 27 juil. 2006 by Elixir Interactive http://www.elixir-interactive.com
 */
 $nameTools= 'Mon espace';
 $langFile = array ('registration', 'index','trad4all','tracking');
 $cidReset=true;
 require ('../inc/global.inc.php');
 
 $this_section = "session_my_space";
 
 api_block_anonymous_users();
 Display :: display_header($nameTools);
 
  $tbl_user = Database :: get_main_table(MAIN_USER_TABLE);
  $tbl_course = Database :: get_main_table(MAIN_COURSE_TABLE);
  $tbl_class = Database :: get_main_table(MAIN_CLASS_TABLE);
  $tbl_sessions = Database :: get_main_table(MAIN_SESSION_TABLE);
  $tbl_session_course = Database :: get_main_table(MAIN_SESSION_COURSE_TABLE);
  $tbl_admin= Database :: get_main_table(MAIN_ADMIN_TABLE);
 /*
 ===============================================================================
 	MAIN CODE
 ===============================================================================  
 */
    
	$sqlNbFormateurs = "SELECT COUNT(user_id)
				  		FROM $tbl_user
				  		WHERE status = 1 
				 	  ";
	$resultNbFormateurs = api_sql_query($sqlNbFormateurs);
	$a_nbFormateurs = mysql_fetch_array($resultNbFormateurs);
	$nbFormateurs = $a_nbFormateurs[0];
  	
  	
  	
  	
  	$sqlNbCoachs = "SELECT COUNT(DISTINCT id_coach)
				  		FROM $tbl_session_course
				  		WHERE id_coach<>'0'	 
				 	  ";
	$resultNbCoachs = api_sql_query($sqlNbCoachs);
	$a_nbCoachs = mysql_fetch_array($resultNbCoachs);
	$nbCoachs = $a_nbCoachs[0];

	$sqlNbStagiaire = "	SELECT COUNT(user_id)
				  		FROM $tbl_user
				  		WHERE status = 5 
				 	  ";
	$resultNbStagiaire = api_sql_query($sqlNbStagiaire);
	$a_nbStagiaire = mysql_fetch_array($resultNbStagiaire);
	$nbStagiaire = $a_nbStagiaire[0];
	
	$sqlNbCours = "	SELECT COUNT(code)
					FROM $tbl_course
				  ";
	$resultNbCours = api_sql_query($sqlNbCours);
	$a_nbCours = mysql_fetch_array($resultNbCours);
	$nbCours = $a_nbCours[0];
	
	$sqlNbSessions = "	SELECT COUNT(id)
					FROM $tbl_sessions
				  ";
	$resultNbSessions = api_sql_query($sqlNbSessions);
	$a_nbSessions= mysql_fetch_array($resultNbSessions);
	$nbSessions = $a_nbSessions[0];

	$sql_nb_admin="SELECT count(user_id) FROM $tbl_admin";
	$resultNbAdmin = api_sql_query($sql_nb_admin);
	$i_nb_admin=mysql_result($resultNbAdmin,0,0);
 ?>
 
 <div class="admin_section">
	<h4>
		<?php echo "<a href='teachers.php'>".get_lang('Trainers')." (".$nbFormateurs.")</a>"; ?>
	</h4>
 </div>
 <div class="admin_section">
	<h4>
		<?php echo "<a href='coaches.php'>".get_lang('Tutor')." (".$nbCoachs.")</a>"; ?>
	</h4>
 </div>
 <div class="admin_section">
	<h4>
		<?php 
			echo "<a href='student.php'>".get_lang('Probationers').' ('.$nbStagiaire.')'."</a>"; 
		?>
	</h4>
 </div>
 <div class="admin_section">
	<h4>
		<?php echo "<a href='admin.php'>".get_lang('Administrators')." (".$i_nb_admin.")</a>"; ?>
	</h4>
 </div>
 <div class="admin_section">
	<h4>
		<?php echo "<a href='cours.php'>".get_lang('Course').' ('.$nbCours.')'."</a>"; ?>
	</h4>
 </div>
 <div class="admin_section">
	<h4>
		<?php echo "<a href='session.php'>".get_lang('Sessions').' ('.$nbSessions.')'."</a>"; ?>
	</h4>
 </div>
 <div class="admin_section">
	<h4>
		<?php echo get_lang('Tracks'); ?>
	</h4>
	<ul>
		<li>
			<a href="progression.php"><?php echo get_lang('Progression'); ?></a>
		</li>
		<li>
			<a href="reussite.php"><?php echo get_lang('Success'); ?></a>
		</li>
	</ul>
 </div>

 <?php
 
 
 /*
 ==============================================================================
		FOOTER
 ==============================================================================
 */

Display::display_footer();
?>
