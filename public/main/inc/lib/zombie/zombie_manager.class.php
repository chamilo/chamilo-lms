<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;

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
        // Normalize/validate sorting column (whitelist)
        $column = str_replace('user.', '', (string) $column);
        if ('' === $column) {
            $column = 'firstname';
        }

        $validColumns = [
            'id',
            'official_code',
            'firstname',
            'lastname',
            'username',
            'email',
            'status',
            'created_at',
            'active',
            'login_date',
        ];

        if (!in_array($column, $validColumns, true)) {
            // Fallback to a safe default column.
            $column = 'firstname';
        }

        // Normalize sort direction
        $direction = strtoupper((string) $direction);
        $direction = ('ASC' === $direction) ? 'ASC' : 'DESC';

        // Normalize ceiling to a datetime string
        $ceilingTs = is_numeric($ceiling) ? (int) $ceiling : strtotime((string) $ceiling);
        if (false === $ceilingTs || $ceilingTs <= 0) {
            // If parsing fails, use "last year" as a safe default.
            $ceilingTs = (int) self::last_year();
        }
        $ceilingDate = date('Y-m-d H:i:s', $ceilingTs);

        $user_table = Database::get_main_table(TABLE_MAIN_USER);
        $login_table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);

        $accessUrlUtil = Container::getAccessUrlUtil();
        $accessUrl = $accessUrlUtil->getCurrent();

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

        if ($accessUrlUtil->isMultiple()) {
            $access_url_rel_user_table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
            $current_url_id = (int) $accessUrl->getId();

            $sql .= " FROM $user_table as user, $login_table as access, $access_url_rel_user_table as url
                  WHERE
                    access.login_date = (SELECT MAX(a.login_date)
                                         FROM $login_table as a
                                         WHERE a.login_user_id = user.id
                                         ) AND
                    access.login_date <= '$ceilingDate' AND
                    user.id = access.login_user_id AND
                    url.user_id = user.id AND url.access_url_id = $current_url_id";
        } else {
            $sql .= " FROM $user_table as user, $login_table as access
                  WHERE
                    access.login_date = (SELECT MAX(a.login_date)
                                         FROM $login_table as a
                                         WHERE a.login_user_id = user.id
                                         ) AND
                    access.login_date <= '$ceilingDate' AND
                    user.id = access.login_user_id";
        }

        if ($active_only) {
            $sql .= ' AND user.active = 1';
        }

        // Exclude soft-deleted users.
        $sql .= ' AND user.active <> '.USER_SOFT_DELETED;

        // Order by a whitelisted column.
        $sql .= " ORDER BY `$column` $direction";

        // Apply pagination only when both values are provided.
        if (null !== $from && null !== $count) {
            $from = (int) $from;
            $count = (int) $count;
            $sql .= " LIMIT $from, $count";
        }

        $result = Database::query($sql);

        if (Database::num_rows($result) === 0) {
            return [];
        }

        // store_result() returns an array of rows, not a single row.
        $items = Database::store_result($result, 'ASSOC');
        if (empty($items) || !is_array($items)) {
            // Defensive: unexpected result format.
            return [];
        }

        // For the paginated table view, enrich each row with auth sources.
        // For count() calls (from/count passed as null), skip enrichment for performance.
        if (null !== $from && null !== $count) {
            foreach ($items as &$row) {
                $userId = (int) ($row['id'] ?? 0);
                $row['auth_sources'] = '';

                if ($userId <= 0) {
                    // Defensive: missing id should not break the report.
                    continue;
                }

                $userEntity = api_get_user_entity($userId);
                if (!$userEntity) {
                    // Defensive: user entity could not be loaded.
                    continue;
                }

                $authSources = $userEntity->getAuthSourcesAuthentications($accessUrl);

                // Normalize the auth sources to a string for table rendering.
                if (is_array($authSources)) {
                    $authSources = implode(', ', $authSources);
                }

                $row['auth_sources'] = (string) $authSources;
            }
            unset($row);
        }

        return $items;
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
