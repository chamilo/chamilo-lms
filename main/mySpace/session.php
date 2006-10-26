<?php
/*
 * Created on 28 juil. 2006 by Elixir Interactive http://www.elixir-interactive.com
 */

 $nameTools= 'Sessions';
 $langFile = array ('registration', 'index','trad4all','tracking');
 $cidReset=true;
 require ('../inc/global.inc.php');
 
 $this_section = "session_my_space";
 
 api_block_anonymous_users();
  $interbreadcrumb[] = array ("url" => "index.php", "name" => get_lang('MySpace'));
 Display :: display_header($nameTools);

 
 
 $tbl_session = Database :: get_main_table(MAIN_SESSION_TABLE);
 /*
 ===============================================================================
 	MAIN CODE
 ===============================================================================  
 */

	$sqlSession = "	SELECT name
					FROM $tbl_session
					ORDER BY name ASC
				  ";
	$resultSession = api_sql_query($sqlSession);
	
	if(mysql_num_rows($resultSession)>0)
	{
		echo '<table class="data_table">
			 	<tr>
					<th>
						'.get_lang('Title').'
					</th>
				</tr>
          	 ';
		while($a_session = mysql_fetch_array($resultSession))
		{
			echo '<tr>
					<td>
				 ';
			echo		$a_session['name'];
			echo '	</td>
				  </tr>
				 ';
		}
		echo '</table>';
	}
	else
	{
		echo get_lang('NoSession');
	}
 
?>
