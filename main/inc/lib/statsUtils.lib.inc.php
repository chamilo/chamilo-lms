<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) various contributors

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
==============================================================================
*/
/**
==============================================================================
*	This is the statistic utility functions library for Dokeos.
*	Include/require it in your code to use its functionality.
*
*	@package dokeos.library
==============================================================================
*/

/*
==============================================================================
		FUNCTIONS
==============================================================================
*/

/**

 * @author Sebastien Piraux <piraux_seb@hotmail.com>
 * @param sql : a sql query (as a string)
 * @desc return one result from a sql query (1 single result)
 */
function getOneResult($sql)
{
	$query = @mysql_query($sql);

	if (mysql_errno())
	{
		echo "\n<!-- **** ".mysql_errno().": ".mysql_error()." In : $sql **** -->\n";
	}

	$res = @mysql_fetch_array($query);
	return $res[0];
}

/**

 * @author Sebastien Piraux <piraux_seb@hotmail.com>
 * @param sql : a sql query (as a string)
 * @desc Return many results of a query in a 1 column tab
 */
function getManyResults1Col($sql)
{
	$res = mysql_query($sql);

	if (mysql_errno())
	{
		echo "\n<!-- **** ".mysql_errno().": ".mysql_error()." In : $sql **** -->\n";
	}
	else
	{
		$i = 0;
		while ($resA = mysql_fetch_array($res))
		{
			$resu[$i++] = $resA[0];
		}
	}

	return $resu;
}

/**

 * @author Sebastien Piraux <piraux_seb@hotmail.com>
 * @param sql : a sql query (as a string)
 * @desc Return many results of a query
 */
function getManyResults2Col($sql)
{
	$res = mysql_query($sql);

	if (mysql_errno())
	{
		echo "\n<!-- **** ".mysql_errno().": ".mysql_error()." In : $sql **** -->\n";
	}
	else
	{
		$i = 0;
		while ($resA = mysql_fetch_array($res))
		{
			$resu[$i][0] = $resA[0];
			$resu[$i][1] = $resA[1];
			$i++;
		}
	}

	return $resu;
}

/**

 * @author Sebastien Piraux <piraux_seb@hotmail.com>
 * @param sql : a sql query (as a string)
 * @desc Return many results of a query in a 3 column tab
         in $resu[$i][0], $resu[$i][1],$resu[$i][2]
 */
function getManyResults3Col($sql)
{
	$res = mysql_query($sql);

	if (mysql_errno())
	{
		echo "\n<!-- **** ".mysql_errno().": ".mysql_error()." In : $sql **** -->\n";
	}
	else
	{
		$i = 0;
		while ($resA = mysql_fetch_array($res))
		{
			$resu[$i][0]=$resA[0];
			$resu[$i][1]=$resA[1];
			$resu[$i][2]=$resA[2];
			$i++;
		}
	}

	return $resu;
}

/**

 * @author Sebastien Piraux <piraux_seb@hotmail.com>
 * @param sql : a sql query (as a string)
 * @desc Return many results of a query in a X column tab
         in $resu[$i][0], $resu[$i][1],$resu[$i][2],...
         this function is more 'standard' but use a little
         more ressources
         So I encourage to use the dedicated for 1, 2 or 3
         columns of results
 */
function getManyResultsXCol($sql,$X)
{
	$res = mysql_query($sql);

	if (mysql_errno())
	{
		echo "\n<!-- **** ".mysql_errno().": ".mysql_error()." In : $sql **** -->\n";
	}
	else
	{
		$i = 0;
		while ($resA = mysql_fetch_array($res))
		{
			for($j = 0; $j < $X ; $j++)
			{
				$resu[$i][$j]=$resA[$j];
			}
			$i++;
		}
	}

	return $resu;
}
/**

 * @author Sebastien Piraux <piraux_seb@hotmail.com>
 * @param sql : a sql query (as a string)
 * @return hours_array
 * @desc        Return an assoc array.  Keys are the hours, values are
                the number of time this hours was found.
                key 'total' return the sum of all number of time hours
                appear
 */
function hoursTab($sql)
{
	$hours_array = array('total' => 0);
	$res = mysql_query($sql);

	if (mysql_errno())
	{
		echo "\n<!-- **** ".mysql_errno().": ".mysql_error()." In : $sql **** -->\n";
	}
	else
	{
		while($row = mysql_fetch_row($res))
		{
			$date_array = getdate($row[0]);

			if($date_array['hours'] == $last_hours)
			{
				$hours_array[$date_array['hours']]++;
			}
			else
			{
				$hours_array[$date_array['hours']] = 1;
				$last_hours = $date_array['hours'];
			}

			$hours_array['total']++;
		}
		mysql_free_result($res);
	}

	return $hours_array;
}

/**

 * @author Sebastien Piraux <piraux_seb@hotmail.com>
 * @param sql : a sql query (as a string)
 * @return days_array
 * @desc        Return an assoc array.  Keys are the days, values are
                the number of time this hours was found.
                key "total" return the sum of all number of time days
                appear
 */
function daysTab($sql)
{
	$MonthsShort = array(get_lang('JanuaryShort'), get_lang('FebruaryShort'), get_lang('MarchShort'), get_lang('AprilShort'), get_lang('MayShort'), get_lang('JuneShort'), get_lang('JulyShort'), get_lang('AugustShort'), get_lang('SeptemberShort'), get_lang('OctoberShort'), get_lang('NovemberShort'), get_lang('DecemberShort'));
	$days_array = array('total' => 0);
	$res = mysql_query($sql);

	if (mysql_errno())
	{
		echo "\n<!-- **** ".mysql_errno().": ".mysql_error()." In : $sql **** -->\n";
	}
	else
	{
		while($row = mysql_fetch_row($res))
		{
			$date_array = getdate($row[0]);
			$display_date = $date_array['mday'].' '.$MonthsShort[$date_array['mon']-1].' '.$date_array['year'];
			if ($date_array['mday'] == $last_day)
			{
				$days_array[$display_date]++;
			}
			else
			{
				$days_array[$display_date] = 1;
				$last_day = $display_date;
			}
			$days_array['total']++;
		}
		mysql_free_result($res);
	}

	return $days_array;
}

/**

 * @author Sebastien Piraux <piraux_seb@hotmail.com>
 * @param sql : a sql query (as a string)
 * @return month_array
 * @desc        Return an assoc array.  Keys are the days, values are
                the number of time this hours was found.
                key "total" return the sum of all number of time days
                appear
 */
function monthTab($sql)
{
	$MonthsLong = array (get_lang('JanuaryLong'), get_lang('FebruaryLong'), get_lang('MarchLong'), get_lang('AprilLong'), get_lang('MayLong'), get_lang('JuneLong'), get_lang('JulyLong'), get_lang('AugustLong'), get_lang('SeptemberLong'), get_lang('OctoberLong'), get_lang('NovemberLong'), get_lang('DecemberLong'));
    $month_array = array('total' => 0);
	$res = mysql_query($sql);

	if (mysql_errno())
	{
		echo "\n<!-- **** ".mysql_errno().": ".mysql_error()." In : $sql **** -->\n";
	}
	else
	{
		// init tab with all months
		for($i = 0; $i < 12; $i++)
		{
			$month_array[$MonthsLong[$i]] = 0;
		}

		while($row = mysql_fetch_row($res))
		{
			$date_array = getdate($row[0]);
			$month_array[$MonthsLong[$date_array['mon']-1]]++;
			$month_array['total']++;
		}
		mysql_free_result($res);
	}

	return $month_array;
}

/**

 * @author Sebastien Piraux <piraux_seb@hotmail.com>
 * @param period_array : an array provided by hoursTab($sql) or daysTab($sql)
 * @param periodTitle : title of the first column, type of period
 * @param linkOnPeriod :
 * @desc        Display a 4 column array
                Columns are : hour of day, graph, number of hits and %
                First line are titles
                next are informations
                Last is total number of hits
 */
function makeHitsTable($period_array, $periodTitle, $linkOnPeriod = '???')
{
	echo "<table width='100%' cellpadding='0' cellspacing='1' border='0' align=center class='minitext'>";
	// titles
	echo "<tr bgcolor='#E6E6E6' align='center'>
	        <td width='15%' >
	            <b>$periodTitle</b>
	        </td>
	        <td width='60%'>
	            &nbsp;
	        </td>
	        <td width='10%'>
	            <b>".get_lang('Hits')."</b>
	        </td>
	        <td width='15%'>
	            <b>%</b>
	        </td>
        </tr>
	";
	$factor = 4;
	$maxSize = $factor * 100; //pixels
	while(list($periodPiece, $cpt) = each($period_array))
	{
		if($periodPiece != 'total')
		{
			$pourcent = round(100 * $cpt / $period_array['total']);
			$barwidth = $factor * $pourcent ;
			echo "<tr>
				<td align='center' width='15%'>";
			echo $periodPiece;
			echo "</td>
				<td width='60%' style='padding-top: 3px;' align='center'>"
				// display hitbar
				."<img src='../img/bar_1.gif' width='1' height='12' alt='$periodPiece : $cpt hits &ndash; $pourcent %' />";
			if($pourcent != 0)
				echo "<img src='../img/bar_1u.gif' width='$barwidth' height='12' alt='$periodPiece : $cpt hits &ndash; $pourcent %' />";
				// display 100% bar
			if($pourcent != 100 && $pourcent != 0)
				echo "<img src='../img/bar_1m.gif' width='1' height='12' alt='$periodPiece : $cpt hits &ndash; $pourcent %' />";
			if($pourcent != 100)
				echo "<img src='../img/bar_1r.gif' width='".($maxSize-$barwidth)."' height='12' alt='$periodPiece : $cpt hits &ndash; $pourcent %' />";
			echo "<img src='../img/bar_1.gif' width='1' height='12' alt='$periodPiece : $cpt hits &ndash; $pourcent %' />
				</td>
				<td align='center' width='10%'>
					$cpt
				</td>
				<td align='center' width='15%'>
					$pourcent %
				</td>
				</tr>
			";
		}
	}
	echo "<tr bgcolor='#E6E6E6'>
	        <td width='15%' align='center'>
	            ".get_lang('Total')."
	        </td>
	        <td align='right' width='60%'>
	            &nbsp;
	        </td>
	        <td align='center' width='10%'>
	            ".$period_array['total']."
	        </td>
	        <td width='15%'>
                &nbsp;
	        </td>
	    </tr>
	";
	echo "</table>";
}

/**

 * @author Sebastien Piraux <piraux_seb@hotmail.com>
 * @param array_of_results : a 2 columns array
 * @param title1 : string, title of the first column
 * @param title2 : string, title of the ... second column
 * @desc        display a 2 column tab from an array
                titles of columns are title1 and title2
 */
function buildTab2col($array_of_results, $title1, $title2)
{
	echo "<table cellpadding='2' cellspacing='1' border='1' align='center'>\n";
	echo "<tr>
	        <td bgcolor='#E6E6E6'>
	        $title1
	        </td>
	        <td bgcolor='#E6E6E6'>
	        $title2
	        </td>
	    </tr>\n";

	if (is_array($array_of_results))
	{
		for($j = 0 ; $j < count($array_of_results) ; $j++)
		{
			echo '<tr>';
			echo '<td bgcolor="#eeeeee">'.$array_of_results[$j][0].'</td>';
			echo '<td align="right">'.$array_of_results[$j][1].'</td>';
			echo "</tr>\n";
		}
	}
	else
	{
		echo '<tr>';
		echo '<td colspan="2" align="center">'.get_lang('NoResult').'</td>';
		echo "</tr>\n";
	}
	echo "</table>\n";
}

/**

 * @author Sebastien Piraux <piraux_seb@hotmail.com>
 * @param array_of_results : a 2 columns array
 * @desc        display a 2 column tab from an array
                this tab has no title
 */
function buildTab2ColNoTitle($array_of_results)
{
	echo "<table cellpadding='3' cellspacing='1' border='0' align='center'>\n";

	if (is_array($array_of_results))
	{
		for($j = 0 ; $j < count($array_of_results) ; $j++)
		{
			echo '<tr>';
			echo '<td bgcolor="#eeeeee">'.$array_of_results[$j][0].'</td>';
			echo '<td align="right">&nbsp;&nbsp;'.$array_of_results[$j][1].'</td>';
			echo "</tr>\n";
		}
	}
	else
	{
		echo '<tr>';
		echo '<td colspan="2" align="center">'.get_lang('NoResult').'</td>';
		echo "</tr>\n";
	}
	echo "</table>\n";
}

/**

 * @author Sebastien Piraux <piraux_seb@hotmail.com>
 * @param array_of_results : a 2 columns array
 * @desc        this function is used to display
                integrity errors in the platform
                if array_of_results is not an array there is
                no error, else errors are displayed
 */
function buildTabDefcon($array_of_results)
{
	echo "<table width='60%' cellpadding='2' cellspacing='1' border='0' align=center class='minitext'>\n";

	if (is_array($array_of_results))
	{
		// there are some strange cases...
		echo '<tr>';
		echo '<td colspan="2" align="center" bgcolor="#eeeeee"><font color="#ff0000">'.get_lang('Defcon').'</font></td>';
		echo "</tr>\n";

		for($j = 0 ; $j < count($array_of_results) ; $j++)
		{
			if($array_of_results[$j][0] == "")
			{
				$key = get_lang('NULLValue');
			}
			else
			{
				$key = $array_of_results[$j][0];
			}
			echo '<tr>';
			echo '<td width="70%" class="content">'.$key.'</td>';
			echo '<td width="30%" align="right">'.$array_of_results[$j][1].'</td>';
			echo "</tr>\n";
		}
	}
	else
	{
		// all right
		echo '<tr>';
		echo '<td colspan="2" align="center"><font color="#00ff00">'.get_lang('AllRight').'</font></td>';
		echo "</tr>\n";
	}
	echo "</table>\n";
}
?>