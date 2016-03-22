<?php
/* For licensing terms, see /license.txt */

/**
*	This is the statistic utility functions library for Chamilo.
*	Include/require it in your code to use its functionality.
*	@package chamilo.library
*/
class StatsUtils
{
    /**
     * @author Sebastien Piraux <piraux_seb@hotmail.com>
     * @param sql : a sql query (as a string)
     * @desc return one result from a sql query (1 single result)
     */
    public static function getOneResult($sql)
    {
        $query = Database::query($sql);
        if ($query !== false) {
            $res = @Database::fetch_array($query, 'NUM');
        } else {
            $res = array();
        }
        return $res[0];
    }

    /**
     * @author Sebastien Piraux <piraux_seb@hotmail.com>
     * @param sql : a sql query (as a string)
     * @desc Return many results of a query in a 1 column tab
     */
    public static function getManyResults1Col($sql)
    {
        $res = Database::query($sql);
        if ($res !== false) {
            $i = 0;
            while ($resA = Database::fetch_array($res, 'NUM')) {
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
    public static function getManyResults2Col($sql)
    {
        $res = Database::query($sql);
        if ($res !== false) {
            $i = 0;
            while ($resA = Database::fetch_array($res, 'NUM')) {
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
     * in $resu[$i][0], $resu[$i][1],$resu[$i][2]
     */
    public static function getManyResults3Col($sql)
    {
        $res = Database::query($sql);
        if ($res !== false) {
            $i = 0;
            while ($resA = Database::fetch_array($res, 'NUM')) {
                $resu[$i][0] = $resA[0];
                $resu[$i][1] = $resA[1];
                $resu[$i][2] = $resA[2];
                $i++;
            }
        }

        return $resu;
    }

    /**
     * @author Sebastien Piraux <piraux_seb@hotmail.com>
     * @param sql : a sql query (as a string)
     * @desc Return many results of a query in a X column tab
     * in $resu[$i][0], $resu[$i][1],$resu[$i][2],...
     * this function is more 'standard' but use a little
     * more ressources
     * So I encourage to use the dedicated for 1, 2 or 3
     * columns of results
     */
    public static function getManyResultsXCol($sql, $X)
    {
        $res = Database::query($sql);
        if ($res !== false) {
            $i = 0;
            while ($resA = Database::fetch_array($res, 'NUM')) {
                for ($j = 0; $j < $X; $j++) {
                    $resu[$i][$j] = $resA[$j];
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
	 * the number of time this hours was found.
	 * key 'total' return the sum of all number of time hours
	 * appear
     */
    public static function hoursTab($sql)
    {
        $hours_array = array('total' => 0);
        $res = Database::query($sql);
        if ($res !== false) {
            $last_hours = -1;
            while ($row = Database::fetch_row($res)) {
                $date_array = getdate($row[0]);
                if ($date_array['hours'] == $last_hours) {
                    $hours_array[$date_array['hours']]++;
                } else {
                    $hours_array[$date_array['hours']] = 1;
                    $last_hours = $date_array['hours'];
                }
                $hours_array['total']++;
            }
            Database::free_result($res);
        }
        return $hours_array;
    }

    /**
     * @author Sebastien Piraux <piraux_seb@hotmail.com>
     * @param sql : a sql query (as a string)
     * @return days_array
     * @desc        Return an assoc array.  Keys are the days, values are
     * the number of time this hours was found.
     * key "total" return the sum of all number of time days
     * appear
     */
    public static function daysTab($sql)
    {
        $MonthsShort = api_get_months_short();
        $days_array = array('total' => 0);
        $res = Database::query($sql);
        if ($res !== false) {
            $last_day = -1;
            while ($row = Database::fetch_row($res)) {
                $date_array = getdate($row[0]);
                $display_date = $date_array['mday'] . ' ' . $MonthsShort[$date_array['mon'] - 1] . ' ' . $date_array['year'];
                if ($date_array['mday'] == $last_day) {
                    $days_array[$display_date]++;
                } else {
                    $days_array[$display_date] = 1;
                    $last_day = $display_date;
                }
                $days_array['total']++;
            }
            Database::free_result($res);
        }
        return $days_array;
    }

    /**
     * @author Sebastien Piraux <piraux_seb@hotmail.com>
     * @param sql : a sql query (as a string)
     * @return month_array
     * @desc        Return an assoc array.  Keys are the days, values are
     * the number of time this hours was found.
     * key "total" return the sum of all number of time days
     * appear
     */
    public static function monthTab($sql)
    {
        $MonthsLong = api_get_months_long();
        $month_array = array('total' => 0);
        $res = Database::query($sql);
        if ($res !== false) {
            // init tab with all months
            for ($i = 0; $i < 12; $i++) {
                $month_array[$MonthsLong[$i]] = 0;
            }
            while ($row = Database::fetch_row($res)) {
                $date_array = getdate($row[0]);
                $month_array[$MonthsLong[$date_array['mon'] - 1]]++;
                $month_array['total']++;
            }
            Database::free_result($res);
        }
        return $month_array;
    }

    /**
     * @author Sebastien Piraux <piraux_seb@hotmail.com>
     * @param period_array : an array provided by hoursTab($sql) or daysTab($sql)
     * @param periodTitle : title of the first column, type of period
     * @param linkOnPeriod :
     * @desc        Display a 4 column array
     * Columns are : hour of day, graph, number of hits and %
     * First line are titles
     * next are informations
     * Last is total number of hits
     */
    public static function makeHitsTable($period_array, $periodTitle, $linkOnPeriod = '???')
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
                    <b>" . get_lang('Hits') . "</b>
                </td>
                <td width='15%'>
                    <b>%</b>
                </td>
            </tr>
        ";
        $factor = 4;
        $maxSize = $factor * 100; //pixels
        while (list($periodPiece, $cpt) = each($period_array)) {
            if ($periodPiece != 'total') {
                $pourcent = round(100 * $cpt / $period_array['total']);
                $barwidth = $factor * $pourcent;
                echo "<tr>
                    <td align='center' width='15%'>";
                echo $periodPiece;
                echo "</td>
                    <td width='60%' style='padding-top: 3px;' align='center'>"
                    // display hitbar
                    . "<img src='".Display::returnIconPath('bar_1.gif')."' width='1' height='12' alt='$periodPiece : $cpt hits &ndash; $pourcent %' />";
                if ($pourcent != 0) {
                    echo "<img src='".Display::returnIconPath('bar_1u.gif')."' width='$barwidth' height='12' alt='$periodPiece : $cpt hits &ndash; $pourcent %' />";
                }
                // display 100% bar
                if ($pourcent != 100 && $pourcent != 0) {
                    echo "<img src='".Display::returnIconPath('bar_1m.gif')."' width='1' height='12' alt='$periodPiece : $cpt hits &ndash; $pourcent %' />";
                }
                if ($pourcent != 100) {
                    echo "<img src='".Display::returnIconPath('bar_1r.gif')."' width='" . ($maxSize - $barwidth) . "' height='12' alt='$periodPiece : $cpt hits &ndash; $pourcent %' />";
                }
                echo "<img src='".Display::returnIconPath('bar_1.gif')."' width='1' height='12' alt='$periodPiece : $cpt hits &ndash; $pourcent %' />
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
                    " . get_lang('Total') . "
                </td>
                <td align='right' width='60%'>
                    &nbsp;
                </td>
                <td align='center' width='10%'>
                    " . $period_array['total'] . "
                </td>
                <td width='15%'>
                    &nbsp;
                </td>
            </tr>
        ";
        echo "</table>";
    }
}
