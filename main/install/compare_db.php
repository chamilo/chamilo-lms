<?php // $Id: compare_db.php 16653 2008-11-03 22:49:07Z ivantcholakov $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2006 Yannick Warnier <yannick.warnier@dokeos.com>
	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, 44 rue des palais, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
/**
==============================================================================
*	This script allows you to know which tables have been modified
*	between two versions of Dokeos databases.
*
*	@package dokeos.install
==============================================================================
*/

/**
 * Database configuration
 * INSTRUCTIONS
 * ============
 * Change these parameters to compare between an old and a new database install.
 * You will need to create a course called 'COURSE' on each side to be able to compare the
 * courses databases.
 * If you have given fancy names to your databases, you will need to modify these names 
 * in the two $bases_* variables definitions below.
 * Also, make sure about the prefix possibly used in front of the normal prefix for courses
 * databases (i.e. 'zPrefix_course' contains 'z' as additional prefix).
 */
$sql_server_new='localhost';
$sql_user_new='root';
$sql_pass_new='';
$prefix_new = 'dokeos180_';
$bases_new=array($prefix_new.'dokeos_main',$prefix_new.'dokeos_stats',$prefix_new.'dokeos_user','z'.$prefix_new.'COURSE',$prefix_new.'dokeos_scorm');
$db_new = mysql_connect($sql_server_new,$sql_user_new,$sql_pass_new) or die(mysql_error());

// The Dokeos system has not been designed to use special SQL modes that were introduced since MySQL 5
@mysql_query("set session sql_mode='';", $db_new);

$sql_server_old='localhost';
$sql_user_old='root';
$sql_pass_old='';
$prefix_old = 'dokeos160_';
$bases_old=array($prefix_old.'dokeos_main',$prefix_old.'dokeos_stats',$prefix_old.'dokeos_user',$prefix_old.'COURSE',$prefix_old.'dokeos_scorm');
$db_old = mysql_connect($sql_server_old,$sql_user_old,$sql_pass_old) or die(mysql_error());

// The Dokeos system has not been designed to use special SQL modes that were introduced since MySQL 5
@mysql_query("set session sql_mode='';", $db_old);

$field_details = array(0=>'Field',1=>'Type',2=>'Null',3=>'Key',4=>'Default',5=>'Extra');

/********************************/

$all_db_changes = array();

echo "<h1>Databases structure comparison script</h1>";

//iterate through databases given above (checking from the 'new' database side)
foreach($bases_new as $num_base=>$base)
{
	//init tables lists for this database
	$modif_tables=array();
	$tables_db_new=array();
	$tables_db_old=array();
	$dump=array();
	
	//display current processed database
	echo "<h2>Now analysing differences between databases <em>$base</em> and <em>".$bases_old[$num_base]."</em></h2>";
	
	//get a list of tables for this database
	$query_new="SHOW TABLES FROM ".$bases_new[$num_base];
	$result_new=mysql_query($query_new,$db_new);

	if($result_new) //if there are tables in this database
	{
		$i=0;

		//as there are tables, process them one by one
		while($row_new=mysql_fetch_row($result_new))
		{
			$dump[$i]['table_name']=$row_new[0];
			$dump[$i]['fields']=array();

			$query_old="SHOW FIELDS FROM ".$bases_new[$num_base].".".$row_new[0];
			$result_old=mysql_query($query_old,$db_old) or die(mysql_error());

			$j=0;

			//get the fields details (numbered fields)
			while($row_old=mysql_fetch_row($result_old))
			{
				$dump[$i]['fields'][$j][0]=$row_old[0];
				$dump[$i]['fields'][$j][1]=$row_old[1];
				$dump[$i]['fields'][$j][2]=$row_old[2];
				$dump[$i]['fields'][$j][3]=$row_old[3];
				$dump[$i]['fields'][$j][4]=$row_old[4];
				$dump[$i]['fields'][$j][5]=$row_old[5];
				//get the field name in one special element of this array
				$dump[$i]['field_names'][$row_old[0]]=$j;

				$j++;
			}

			$i++;
		}

		foreach($dump as $table)
		{
			$query="SHOW FIELDS FROM ".$bases_old[$num_base].".".$table['table_name'];
			$result=mysql_query($query,$db_old);

			if(!$result)
			{
				$modif_tables[]='**'.$table['table_name'].'**';
			}
			else
			{
				$i=0;
				
				//check for removed, new or modified fields
				$fields_old = array();
				$fields_new = array();
				//list the new fields in a enumeration array
				foreach($table['field_names'] as $dummy_key=>$dummy_field){
					$fields_new[] = $dummy_key;
				}
				//list the old fields in an enumeration array and check if their corresponding
				//field in the new table is different (if any)
				$modif_fields = array();
				while($row_old = mysql_fetch_row($result)){
					$fields_old[] = $row_old[0];
					$modif_field = '';
					if(isset($table['fields'][$table['field_names'][$row_old[0]]])){
						$field_infos=$table['fields'][$table['field_names'][$row_old[0]]];
						foreach($row_old as $key=>$enreg)
						{
							//if the old field information of this kind doesn't match the new, record it
							if($row_old[$key] != $field_infos[$key])
							{
								$modif_field .='~+~'.$field_details[$key].'~+~,';
									break;
							}
						}
						//only record the whole stuff if the string is not empty
						if(strlen($modif_field)>0){
							$modif_fields[$row_old[0]] .= substr($modif_field,0,-1);
						}
					}
				}
				$new_fields = array_diff($fields_new,$fields_old);
				foreach($new_fields as $dummy=>$val){
					$new_fields[$dummy] = '++'.$val.'++';
				}
				$old_fields = array_diff($fields_old,$fields_new); 
				foreach($old_fields as $dummy=>$val){
					$old_fields[$dummy] = '--'.$val.'--';
				}
				if(count($old_fields)>0 or count($modif_fields)>0 or count($new_fields)>0 ){
					$modif_tables[]=array(
						'table'=>$table['table_name'],
						'old_fields'=>$old_fields,
						'changed_fields'=>$modif_fields,
						'new_fields'=>$new_fields,
					);
				}				
			}
			$tables_db_new[]=$table['table_name'];
		}

		$query="SHOW TABLES FROM ".$bases_old[$num_base];
		$result=mysql_query($query,$db_old) or die(mysql_error());

		while($row=mysql_fetch_row($result))
		{
			$tables_db_old[]=$row[0];
		}

		$diff=array_diff($tables_db_old,$tables_db_new);

		foreach($diff as $enreg)
		{
			$modif_tables[]='---'.$enreg.'---';
		}
		//$modif_tables=array_unique($modif_tables); //deprecated with the structure complexification
		
	}else{ //this database was removed in the new version
		$query="SHOW TABLES FROM ".$bases_old[$num_base];
		$result=mysql_query($query,$db_old) or die(mysql_error());

		while($row=mysql_fetch_row($result))
		{
			$tables_db_old[]=$row[0];
		}

		$diff=array_diff($tables_db_old,$tables_db_new);

		foreach($diff as $enreg)
		{
			$modif_tables[]='---'.$enreg.'---';
		}

		$modif_tables=array_unique($modif_tables);
		echo "<h3>This database has been removed!</h3>";	
	}
	echo "<h3>Differences between each table</h3>" .
			"- fields display under each table's name, <br>" .
			"- new tables are surrounded by '**', <br/>" .
			"- removed tables are surrounded by '---',<br/>" .
			"- new fields are surrounded by '++',<br/>" .
			"- removed fields are surrounded by '--',<br/>" .
			"- modified fields are surrounded by '~+~',<br/>";
	echo '<pre>'.print_r($modif_tables,true).'</pre>';
	$all_db_changes[$base] = $modif_tables;
}

mysql_close($db_new);
mysql_close($db_old);

echo "<h2>Generating SQL</h2>";
//going through all databases changes
foreach($all_db_changes as $base => $changes){
	echo "<h3>SQL for DB $base</h3>";
	foreach($changes as $table){
		if(is_array($table)){
			//we have a field-level difference
			$mytable = $table['table'];
			$myold = $table['old_fields'];
			$mychanged = $table['changed_fields'];
			$mynew = $table['new_fields'];
			foreach($myold as $myname){
				//column lost, display DROP command
				$myname = str_replace('--','',$myname);
				echo "ALTER TABLE ".$mytable." DROP ".$myname."<br/>";
			}
			foreach($mychanged as $myname=>$myprop){
				//field changed, display SET command
				$myprops = split(',',$myprop);
				$myprops_string = '';
				foreach($myprops as $myprop){
					$myprop = str_replace('~+~','',$myprop);
					$myprops_string .= $myprop." ";
				}
				echo "ALTER TABLE ".$mytable." CHANGE $myname $myname $myprops_string<br/>";
			}
			foreach($mynew as $myname){
				//column created, display ADD command
				$myname = str_replace('++','',$myname);
				echo "ALTER TABLE ".$mytable." ADD $myname...<br/>";
			}
		}else{
			//we have a table-level difference
			$open_tag = substr($table,0,2);
			switch($open_tag){
				case '**':
					//new table, display CREATE TABLE command
					$table = str_replace('**','',$table);
					echo "CREATE TABLE ".$table."();<br/>";
					break;
				case '--':
					//dropped table, display DROP TABLE command
					$table = str_replace('---','',$table);
					echo "DROP TABLE ".$table."();<br/>";
					break;
				default:
					echo "Unknown table problem: ".$table."<br/>";
					break;
			}
		}
	}
}
?>
