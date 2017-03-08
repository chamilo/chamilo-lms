<?php
/* For licensing terms, see /license.txt */

/**
 *    This is the statistic utility functions library for Chamilo.
 *    Include/require it in your code to use its functionality.
 * @package chamilo.library
 * @deprecated
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
}
