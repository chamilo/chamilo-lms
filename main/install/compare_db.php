<?php // $Id: compare_db.php 10519 2006-12-18 17:12:37Z yannoo $
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


$sql_server_old='localhost';
$sql_user_old='root';
$sql_pass_old='';
$prefix_old = 'dokeos160_';
$bases_old=array($prefix_old.'dokeos_main',$prefix_old.'dokeos_stats',$prefix_old.'dokeos_user',$prefix_old.'COURSE',$prefix_old.'dokeos_scorm');
$db_old = mysql_connect($sql_server_old,$sql_user_old,$sql_pass_old) or die(mysql_error());

/********************************/

foreach($bases_new as $num_base=>$base)
{
	$modif_tables=array();
	$tables_db_new=array();
	$tables_db_old=array();
	$dump=array();

	$query_new="SHOW TABLES FROM ".$bases_new[$num_base];
	$result_new=mysql_query($query_new,$db_new);

	if($result_new)
	{
		$i=0;

		while($row_new=mysql_fetch_row($result_new))
		{
			$dump[$i]['table_name']=$row_new[0];
			$dump[$i]['fields']=array();

			$query_old="SHOW FIELDS FROM ".$bases_new[$num_base].".".$row_new[0];
			$result_old=mysql_query($query_old,$db_old) or die(mysql_error());

			$j=0;

			while($row_old=mysql_fetch_row($result_old))
			{
				$dump[$i]['fields'][$j][0]=$row_old[0];
				$dump[$i]['fields'][$j][1]=$row_old[1];
				$dump[$i]['fields'][$j][2]=$row_old[2];
				$dump[$i]['fields'][$j][3]=$row_old[3];
				$dump[$i]['fields'][$j][4]=$row_old[4];
				$dump[$i]['fields'][$j][5]=$row_old[5];

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
				$modif_tables[]=$table['table_name'];
			}
			else
			{
				$i=0;

				if(sizeof($table['fields']) != mysql_num_rows($result))
				{
					$modif_tables[]=$table['table_name'];
				}
				else
				{
					while($row=mysql_fetch_row($result))
					{
						$field_infos=$table['fields'][$i];

						foreach($row as $key=>$enreg)
						{
							if($row[$key] != $field_infos[$key])
							{
								$modif_tables[]=$table['table_name'];

								break;
							}
						}

						$i++;
					}
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
			$modif_tables[]=$enreg;
		}

		$modif_tables=array_unique($modif_tables);

		echo '<pre>'.print_r($modif_tables,true).'</pre>';
	}
}

mysql_close($db_new);
mysql_close($db_old);
?>