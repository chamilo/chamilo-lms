<?php
/*
 * Created on 27 juil. 2006 by Elixir Interactive http://www.elixir-interactive.com
 */
 
 $langFile = array ('registration', 'index','trad4all','tracking');
 $cidReset=true;
 require ('../inc/global.inc.php');
 $nameTools= get_lang("MySpace");
 $this_section = "session_my_space";
 
 api_block_anonymous_users();
 Display :: display_header($nameTools);
 
  $tbl_user = Database :: get_main_table(MAIN_USER_TABLE);
  $tbl_course = Database :: get_main_table(MAIN_COURSE_TABLE);
  $tbl_course_user = Database :: get_main_table(MAIN_COURSE_USER_TABLE);
  $tbl_class = Database :: get_main_table(MAIN_CLASS_TABLE);
  $tbl_sessions = Database :: get_main_table(MAIN_SESSION_TABLE);
  $tbl_session_course = Database :: get_main_table(MAIN_SESSION_COURSE_TABLE);
  $tbl_session_user = Database :: get_main_table(MAIN_SESSION_USER_TABLE);
  $tbl_session_course_user = Database :: get_main_table(MAIN_SESSION_COURSE_USER_TABLE);
  $tbl_admin= Database :: get_main_table(MAIN_ADMIN_TABLE);
  
  
    
function is_coach(){
  	
  	global $tbl_session_course;
  	
	$sql="SELECT course_code FROM $tbl_session_course WHERE id_coach='".$_SESSION["_uid"]."'";

	$result=api_sql_query($sql);
	  
	if(mysql_num_rows($result)>0){
	    return true;	    
	}
	else{
		return false;
	}
  
}
  
  
  
 /*
 ===============================================================================
 	MAIN CODE
 ===============================================================================  
 */
 	
 	//Trainers
    if(api_is_platform_admin()){
		$sqlNbFormateurs = "SELECT COUNT(user_id)
					  		FROM $tbl_user
					  		WHERE status = 1 
					 	  ";
		$resultNbFormateurs = api_sql_query($sqlNbFormateurs);
		$a_nbFormateurs = mysql_fetch_array($resultNbFormateurs);
		$nbFormateurs = $a_nbFormateurs[0];
    }
  	
  	
  	//Coachs
  	$nbCoachs=0;
  	if(api_is_platform_admin()){
	  	$sqlNbCoachs = "SELECT COUNT(DISTINCT id_coach)
					  		FROM $tbl_session_course
					  		WHERE id_coach<>'0'	 
					 	  ";
		$resultNbCoachs = api_sql_query($sqlNbCoachs);
		$a_nbCoachs = mysql_fetch_array($resultNbCoachs);
		$nbCoachs = $a_nbCoachs[0];
  	}
  	
  	elseif($is_allowedCreateCourse){
  		
  		$a_coach=array();
  		
  		$sqlNbCours = "	SELECT course_code
						FROM $tbl_course_user
					  	WHERE user_id='$_uid' AND status='1'
					  ";
		$resultNbCours = api_sql_query($sqlNbCours);
		
		while($a_courses=mysql_fetch_array($resultNbCours)){
			
			$sql="SELECT DISTINCT id_coach FROM $tbl_session_course WHERE course_code='".$a_courses["course_code"]."'";
			
			$resultCoach = api_sql_query($sql);
			
			if(mysql_num_rows($resultCoach)>0){
				while($a_temp=mysql_fetch_array($resultCoach)){
					$a_coach[]=$a_temp["id_coach"];
				}
			}
			
		}
		
		$a_coach=array_unique($a_coach);
		$nbCoachs=count($a_coach);
  		
  	}


	
	//Nombre de stagiaires (cours dans lesquels il est coach ou formateurs)
	
	$nbStagiaire=0;
	$a_stagiaire_teacher=array();
	
	//La personne est admin
	if(api_is_platform_admin()){

		$sqlNbStagiaire = "	SELECT COUNT(user_id)
					  		FROM $tbl_user
					  		WHERE status = 5 
					 	  ";
		$resultNbStagiaire = api_sql_query($sqlNbStagiaire);
		$a_nbStagiaire = mysql_fetch_array($resultNbStagiaire);
		$nbStagiaire = $a_nbStagiaire[0];
	}
	
	else{
		
		//La personne a le statut de professeur
		if($is_allowedCreateCourse){

			$sqlNbStagiaire="SELECT DISTINCT srcru.id_user FROM $tbl_course_user as course_rel_user, $tbl_session_course_user as srcru " .
							"WHERE course_rel_user.user_id='$_uid' AND course_rel_user.status='1' AND course_rel_user.course_code=srcru.course_code";

			$resultNbStagiaire = api_sql_query($sqlNbStagiaire);
			
			while($a_temp = mysql_fetch_array($resultNbStagiaire)){
				$a_stagiaire_teacher[]=$a_temp[0];
			}

		}
		
		if(is_coach()){

			$a_stagiaire_coach=array();
			
			$sql="SELECT id_session, course_code FROM $tbl_session_course WHERE id_coach='$_uid'";

			$result=api_sql_query($sql);
			
			while($a_courses=mysql_fetch_array($result)){
				
		    	$course_code=$a_courses["course_code"];
		    	$id_session=$a_courses["id_session"];
		    	
		    	$sqlStudents = "SELECT distinct	srcru.id_user  
								FROM $tbl_session_course_user AS srcru 
								INNER JOIN $tbl_user as user 
									ON srcru.id_user = user.user_id 
									AND user.status = 5 
								WHERE course_code='$course_code' AND id_session='$id_session'";

				$q_students=api_sql_query($sqlStudents);
				
				while($a_temp=mysql_fetch_array($q_students)){
					$a_stagiaire_coach[]=$a_temp[0];
				}
				
		    }
		    $a_stagiaires=array_merge($a_stagiaire_teacher,$a_stagiaire_coach);
				
			$a_stagiaires=array_unique($a_stagiaires);
			
			$nbStagiaire=count($a_stagiaires);
		    
		}
		else{
			$nbStagiaire=count($a_stagiaire_teacher);
		}
		
	}
	
	//Nombre de cours
	
	//La personne est admin donc on compte le nombre total de cours
	if(api_is_platform_admin()){
	
		$sqlNbCours = "	SELECT COUNT(code)
						FROM $tbl_course
					  ";
		$resultNbCours = api_sql_query($sqlNbCours);
		$a_nbCours = mysql_fetch_array($resultNbCours);
		$nbCours = $a_nbCours[0];
		
	}
	
	else{
		
		$a_cours=array();
		
		//La personne a le statut de professeur	
		if($is_allowedCreateCourse){
			
			$sqlNbCours = "	SELECT DISTINCT course_code
							FROM $tbl_course_user
						  	WHERE user_id='$_uid' AND status='1'
						  ";
			$resultCours = api_sql_query($sqlNbCours);
			
			while($a_cours_teacher = mysql_fetch_array($resultCours)){
				$a_cours[]=$a_cours_teacher["course_code"];
			}
			
		}
		
		//La personne est coach
		if(is_coach()){
			
			$sqlNbCours = "	SELECT DISTINCT course_code 
							FROM $tbl_session_course 
						  	WHERE id_coach='$_uid' 
						  ";
			$resultCours = api_sql_query($sqlNbCours);
			
			while($a_cours_coach = mysql_fetch_array($resultCours)){
				$a_cours[]=$a_cours_coach["course_code"];
			}
			
		}
		$a_cours=array_unique($a_cours);
		$nbCours=count($a_cours);
		
	}
	
	
	//Nombre de sessions
	
	//La personne est admin donc on compte le nombre total de sessions
	if(api_is_platform_admin()){
	
		$sqlNbSessions = "	SELECT COUNT(id)
							FROM $tbl_sessions
						 ";
		$resultNbSessions = api_sql_query($sqlNbSessions);
		$a_nbSessions= mysql_fetch_array($resultNbSessions);
		$nbSessions = $a_nbSessions[0];
		
	}
	
	else{
		
		$a_sessions=array();
		
		if($is_allowedCreateCourse){
			
			$sqlNbSessions = "	SELECT DISTINCT id_session 
								FROM $tbl_session_course as session_course, $tbl_course_user as course_rel_user  
							  	WHERE session_course.course_code=course_rel_user.course_code AND course_rel_user.status='1' AND course_rel_user.user_id='$_uid' 
							  ";

			$resultNbSessions = api_sql_query($sqlNbSessions);
			
			while($a_temp = mysql_fetch_array($resultNbSessions)){
				$a_sessions[]=$a_temp["id_session"];
			}
			
		}
		
		if(is_coach()){
			$sqlNbSessions = "	SELECT DISTINCT id_session 
								FROM $tbl_session_course 
							  	WHERE id_coach='$_uid' 
							  ";

			$resultNbSessions = api_sql_query($sqlNbSessions);
			
			while($a_temp = mysql_fetch_array($resultNbSessions)){
				$a_sessions[]=$a_temp["id_session"];
			}
		
		}
		
		$a_sessions=array_unique($a_sessions);
		$nbSessions = count($a_sessions);
		
	}

	$sql_nb_admin="SELECT count(user_id) FROM $tbl_admin";
	$resultNbAdmin = api_sql_query($sql_nb_admin);
	$i_nb_admin=mysql_result($resultNbAdmin,0,0);
	
 ?>
 
 <?php
 if(api_is_platform_admin()){
	 echo '<div class="admin_section">
		<h4>
			<a href="teachers.php">'.get_lang('Trainers').' ('.$nbFormateurs.')</a>
		</h4>
	 </div>';
 }
 
 if(api_is_platform_admin() || $is_allowedCreateCourse){
	 echo '<div class="admin_section">
		<h4>
			<a href="coaches.php">'.get_lang("Tutor").' ('.$nbCoachs.')</a>
		</h4>
	 </div>';
 }
 ?>
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
