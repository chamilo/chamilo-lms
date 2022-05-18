<?php
/* For licensing terms, see /license.txt */

/**
 * ZombieQuery.
 *
 * @copyright (c) 2012 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht <laurent@opprecht.info>
 */
class ZombieManager
{
    public static function last_year()
    {
        $today = time();
        $day = date('j', $today);
        $month = date('n', $today);
        $year = date('Y', $today) - 1;

        return mktime(0, 0, 0, $month, $day, $year);
    }

    /**
     * Returns users whose last login is prior from $ceiling.
     *
     * @param int|string $ceiling     last login date
     * @param bool       $active_only if true returns only active users. Otherwise returns all users.
     *
     * @return ResultSet
     */
    public static function listZombies(
        $ceiling,
        $active_only = true,
        $from = 0,
        $count = 10,
        $column = 'user.firstname',
        $direction = 'desc'
    ) {
        if (empty($column)) {
            $column = 'user.firstname';
        }
        $ceiling = is_numeric($ceiling) ? (int) $ceiling : strtotime($ceiling);
        $ceiling = date('Y-m-d H:i:s', $ceiling);

        $user_table = Database::get_main_table(TABLE_MAIN_USER);
        $login_table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);

        $sql = 'SELECT
                    user.user_id,
                    user.official_code,
                    user.firstname,
                    user.lastname,
                    user.username,
                    user.auth_source,
                    user.email,
                    user.status,
                    user.registration_date,
                    user.active,
                    access.login_date';

        if (api_is_multiple_url_enabled()) {
            $access_url_rel_user_table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
            $current_url_id = api_get_current_access_url_id();

            $sql .= " FROM $user_table as user, $login_table as access, $access_url_rel_user_table as url
                      WHERE
                        access.login_date = (SELECT MAX(a.login_date)
                                             FROM $login_table as a
                                             WHERE a.login_user_id = user.user_id
                                             ) AND
                        access.login_date <= '$ceiling' AND
                        user.user_id = access.login_user_id AND
                        url.user_id = user.user_id AND url.access_url_id=$current_url_id";
        } else {
            $sql .= " FROM $user_table as user, $login_table as access
                      WHERE
                        access.login_date = (SELECT MAX(a.login_date)
                                             FROM $login_table as a
                                             WHERE a.login_user_id = user.user_id
                                             ) AND
                        access.login_date <= '$ceiling' AND
                        user.user_id = access.login_user_id";
        }

        if ($active_only) {
            $sql .= ' AND user.active = 1';
        }

        $sql .= " ORDER BY $column $direction";
        if (!is_null($from) && !is_null($count)) {
            $count = (int) $count;
            $from = (int) $from;
            $sql .= " LIMIT $from, $count ";
        }

        $result = Database::query($sql);

        return Database::store_result($result, 'ASSOC');
    }

    /**
     * @param $ceiling
     */
    public static function deactivate_zombies($ceiling)
    {
        $zombies = self::listZombies($ceiling);
        $ids = [];
        foreach ($zombies as $zombie) {
            $ids[] = $zombie['user_id'];
        }
        UserManager::deactivate_users($ids);
    }
}
