<?php // $Id: compare_db.php 9246 2006-09-25 13:24:53Z bmol $
/*
==============================================================================
	Dokeos - elearning and course management software

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

/**** Database configuration ****/
$mysql_server='localhost';
$mysql_user='root';
$mysql_pass='';

$bases1=array('dokeos16_main','dokeos16_stats','dokeos16_scorm','dokeos16_COURSE');
$bases2=array('dokeos154_main','dokeos154_stats','dokeos154_scorm','dokeos154_COURSE');
/********************************/

mysql_connect($mysql_server,$mysql_user,$mysql_pass) or die(mysql_error());

foreach($bases1 as $num_base=>$base)
{
	$modif_tables=array();
	$tables_db1=array();
	$tables_db2=array();
	$dump=array();

	$query1="SHOW TABLES FROM `$bases1[$num_base]`";
	$result1=mysql_query($query1);

	if($result1)
	{
		$i=0;

		while($row1=mysql_fetch_row($result1))
		{
			$dump[$i]['table_name']=$row1[0];
			$dump[$i]['fields']=array();

			$query2="SHOW FIELDS FROM `$bases1[$num_base]`.`$row1[0]`";
			$result2=mysql_query($query2) or die(mysql_error());

			$j=0;

			while($row2=mysql_fetch_row($result2))
			{
				$dump[$i]['fields'][$j][0]=$row2[0];
				$dump[$i]['fields'][$j][1]=$row2[1];
				$dump[$i]['fields'][$j][2]=$row2[2];
				$dump[$i]['fields'][$j][3]=$row2[3];
				$dump[$i]['fields'][$j][4]=$row2[4];
				$dump[$i]['fields'][$j][5]=$row2[5];

				$j++;
			}

			$i++;
		}

		foreach($dump as $table)
		{
			$query="SHOW FIELDS FROM `$bases2[$num_base]`.`".$table['table_name']."`";
			$result=mysql_query($query);

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

			$tables_db1[]=$table['table_name'];
		}

		$query="SHOW TABLES FROM `$bases2[$num_base]`";
		$result=mysql_query($query) or die(mysql_error());

		while($row=mysql_fetch_row($result))
		{
			$tables_db2[]=$row[0];
		}

		$diff=array_diff($tables_db2,$tables_db1);

		foreach($diff as $enreg)
		{
			$modif_tables[]=$enreg;
		}

		$modif_tables=array_unique($modif_tables);

		echo '<pre>'.print_r($modif_tables,true).'</pre>';
	}
}

mysql_close();
?>