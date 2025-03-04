<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\ServiceHelper\AccessUrlHelper;

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
     * @return array
     */
    public static function listZombies(
        $ceiling,
        $active_only = true,
        $from = 0,
        $count = 10,
        $column = 'user.firstname',
        $direction = 'desc'
    ) {
        $column = str_replace('user.', '', $column);
        if (empty($column)) {
            $column = 'firstname';
        }

        $validColumns = ['id', 'official_code', 'firstname', 'lastname', 'username', 'email', 'status', 'created_at', 'active', 'login_date'];
        if (!in_array($column, $validColumns)) {
            $column = 'firstname';
        }
        $ceiling = is_numeric($ceiling) ? (int) $ceiling : strtotime($ceiling);
        $ceiling = date('Y-m-d H:i:s', $ceiling);

        $user_table = Database::get_main_table(TABLE_MAIN_USER);
        $login_table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);

        /** @var AccessUrlHelper $accessUrlHelper */
        $accessUrlHelper = Container::$container->get(AccessUrlHelper::class);
        $accessUrl = $accessUrlHelper->getCurrent();

        $sql = 'SELECT
                    user.id,
                    user.official_code,
                    user.firstname,
                    user.lastname,
                    user.username,
                    user.email,
                    user.status,
                    user.created_at,
                    user.active,
                    access.login_date';

        if ($accessUrlHelper->isMultiple()) {
            $access_url_rel_user_table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
            $current_url_id = $accessUrl->getId();

            $sql .= " FROM $user_table as user, $login_table as access, $access_url_rel_user_table as url
                      WHERE
                        access.login_date = (SELECT MAX(a.login_date)
                                             FROM $login_table as a
                                             WHERE a.login_user_id = user.id
                                             ) AND
                        access.login_date <= '$ceiling' AND
                        user.id = access.login_user_id AND
                        url.user_id = user.id AND url.access_url_id=$current_url_id";
        } else {
            $sql .= " FROM $user_table as user, $login_table as access
                      WHERE
                        access.login_date = (SELECT MAX(a.login_date)
                                             FROM $login_table as a
                                             WHERE a.login_user_id = user.id
                                             ) AND
                        access.login_date <= '$ceiling' AND
                        user.id = access.login_user_id";
        }

        if ($active_only) {
            $sql .= ' AND user.active = 1';
        }

        $sql .= !str_contains($sql, 'WHERE') ? ' WHERE user.active <> '.USER_SOFT_DELETED : ' AND user.active <> '.USER_SOFT_DELETED;
        $column = str_replace('user.', '', $column);
        $sql .= " ORDER BY `$column` $direction";
        if (!is_null($from) && !is_null($count)) {
            $count = (int) $count;
            $from = (int) $from;
            $sql .= " LIMIT $from, $count ";
        }

        $result = Database::query($sql);

        $userInfo = Database::store_result($result, 'ASSOC');
        $userInfo['auth_sources'] = api_get_user_entity($userInfo['id'])->getAuthSourcesAuthentications($accessUrl);

        return $userInfo;
    }

    /**
     * @param $ceiling
     */
    public static function deactivate_zombies($ceiling)
    {
        $zombies = self::listZombies($ceiling);
        $ids = [];
        foreach ($zombies as $zombie) {
            $ids[] = $zombie['id'];
        }
        UserManager::deactivate_users($ids);
    }
}
