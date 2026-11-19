<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\AccessUrlRelColorTheme;
use Chamilo\CoreBundle\Entity\AccessUrlRelCourse;
use Chamilo\CoreBundle\Entity\AccessUrlRelCourseCategory;
use Chamilo\CoreBundle\Entity\AccessUrlRelPlugin;
use Chamilo\CoreBundle\Entity\AccessUrlRelSession;
use Chamilo\CoreBundle\Entity\AccessUrlRelUser;
use Chamilo\CoreBundle\Entity\AccessUrlRelUserGroup;
use Chamilo\CoreBundle\Entity\Branch;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseCategory;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionCategory;
use Chamilo\CoreBundle\Entity\SettingsCurrent;
use Chamilo\CoreBundle\Entity\Ticket;
use Chamilo\CoreBundle\Entity\TicketPriority;
use Chamilo\CoreBundle\Entity\TicketProject;
use Chamilo\CoreBundle\Entity\TicketStatus;
use Chamilo\CoreBundle\Entity\UserAuthSource;
use Chamilo\CoreBundle\Framework\Container;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

/**
 * Class UrlManager
 * This library provides functions for the access_url management.
 * Include/require it in your code to use its functionality.
 */
class UrlManager
{
    /**
     * Creates a new url access.
     *
     * @author Julio Montoya <gugli100@gmail.com>,
     *
     * @param string $url         The URL of the site
     * @param string $description The description of the site
     * @param int    $active      is active or not
     */
    public static function add($url, $description, $active, bool $isLoginOnly = false): ?AccessUrl
    {
        $repo = Container::getAccessUrlRepository();

        if ($repo->exists($url)) {
            return null;
        }

        $accessUrl = new AccessUrl();
        $accessUrl
            ->setDescription($description)
            ->setActive($active)
            ->setUrl($url)
            ->setCreatedBy(api_get_user_id())
            ->setIsLoginOnly($isLoginOnly)
        ;

        $repo->create($accessUrl);

        return $accessUrl;
    }

    /**
     * Updates an URL access.
     *
     * @author Julio Montoya <gugli100@gmail.com>,
     *
     * @param int    $urlId       The url id
     * @param string $url
     * @param string $description The description of the site
     * @param int    $active      is active or not
     *
     * @return bool if success
     */
    public static function update($urlId, $url, $description, $active, bool $isLoginOnly = false)
    {
        $urlId = (int) $urlId;
        $active = (int) $active;

        $table = Database::get_main_table(TABLE_MAIN_ACCESS_URL);

        return Database::update(
            $table,
            [
                'url' => $url,
                'description' => $description,
                'active' => $active,
                'created_by' => api_get_user_id(),
                'tms' => api_get_utc_datetime(),
                'is_login_only' => $isLoginOnly,
            ],
            ['id = ?' => [$urlId]]
        );
    }

    /**
     * Deletes an url.
     *
     * @author Julio Montoya
     *
     * @param int $id url id
     *
     * @return bool true if success
     * */
    public static function delete($id)
    {
        $id = (int) $id;

        /*
         * $tableCourseCategory = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE_CATEGORY);
         * $sql = "DELETE FROM $tableCourseCategory WHERE access_url_id = ".$id;
         * Database::query($sql);
         */
        $em = Container::getEntityManager();

        // Rows whose access_url foreign key is RESTRICT and that would block deleting the URL.
        // Data that can live without a URL (branches, tickets and their project/priority/status dictionaries,
        // all nullable) is detached, not destroyed. Session categories cannot be detached (the entity requires a
        // URL), so they go, after their sessions are released from them.
        $detachEntities = [
            [Branch::class, 'url'],
            [Ticket::class, 'accessUrl'],
            [TicketProject::class, 'accessUrl'],
            [TicketPriority::class, 'accessUrl'],
            [TicketStatus::class, 'accessUrl'],
        ];
        foreach ($detachEntities as [$detachEntity, $property]) {
            $em
                ->createQuery("UPDATE $detachEntity e SET e.$property = NULL WHERE e.$property = :id")
                ->setParameter('id', $id)
                ->execute()
            ;
        }

        $em
            ->createQuery('UPDATE '.Session::class.' s SET s.category = NULL WHERE s.category IN (SELECT sc.id FROM '.SessionCategory::class.' sc WHERE sc.url = :id)')
            ->setParameter('id', $id)
            ->execute()
        ;

        $relEntities = [
            SessionCategory::class,
            AccessUrlRelPlugin::class,
            AccessUrlRelColorTheme::class,
            SettingsCurrent::class,
            AccessUrlRelCourse::class,
            AccessUrlRelCourseCategory::class,
            AccessUrlRelSession::class,
            AccessUrlRelUserGroup::class,
            AccessUrlRelUser::class,
            UserAuthSource::class,
        ];

        foreach ($relEntities as $relEntity) {
            $qb = $em->createQueryBuilder();

            $em
                ->createQueryBuilder()
                ->delete($relEntity, 'rel')
                ->where($qb->expr()->eq('rel.url', ':id'))
                ->setParameter('id', $id)
                ->getQuery()
                ->execute()
            ;
        }

        $qb = $em->createQueryBuilder();

        $em
            ->createQueryBuilder()
            ->delete(AccessUrl::class, 'au')
            ->where($qb->expr()->eq('au.id', ':id'))
            ->setParameter('id', $id)
            ->getQuery()
            ->execute()
        ;

        return true;
    }

    /**
     * Inserts one row of an access_url_rel_* join table and returns its new id, or null on failure.
     * DQL has no INSERT: the DBAL QueryBuilder keeps the raw query's error handling (Database::handleError).
     *
     * @param array<string, int|string> $values column => id
     */
    private static function insertRelation(string $table, array $values): ?int
    {
        $conn = Database::getManager()->getConnection();
        $qb = $conn->createQueryBuilder()->insert($table);
        foreach ($values as $column => $value) {
            $qb->setValue($column, ':'.$column)->setParameter($column, (int) $value);
        }

        try {
            $qb->executeStatement();
        } catch (Exception $e) {
            Database::handleError($e);

            return null;
        }

        return (int) $conn->lastInsertId();
    }

    /**
     * This function get the quantity of URLs.
     *
     * @author Julio Montoya
     *
     * @return int count of urls
     * */
    public static function url_count()
    {
        return (int) Container::getEntityManager()->createQueryBuilder()
            ->select('COUNT(u.id)')
            ->from(AccessUrl::class, 'u')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * Gets the id, url, description, and active status of ALL URLs.
     *
     * @author Julio Montoya
     *
     * @param int $urlId
     *
     * @return array
     * */
    public static function get_url_data_from_id($urlId)
    {
        $row = Container::getEntityManager()->createQueryBuilder()
            ->select('u.id AS id', 'u.url AS url', 'u.description AS description', 'u.active AS active')
            ->from(AccessUrl::class, 'u')
            ->where('u.id = :id')
            ->setParameter('id', (int) $urlId)
            ->getQuery()
            ->getArrayResult()[0] ?? null;

        // Database::fetch_array() semantics: named keys then numeric keys, empty array when there is no row.
        if (null === $row) {
            return [];
        }
        foreach ($row as $value) {
            $row[] = $value;
        }

        return $row;
    }

    /**
     * Gets the inner join of users and urls table.
     *
     * @author Julio Montoya
     *
     * @param int  access url id
     * @param string $order_by
     *
     * @return array Database::store_result of the result
     */
    public static function get_url_rel_user_data($urlId = 0, $order_by = null)
    {
        $urlId = (int) $urlId;
        $qb = Container::getEntityManager()->createQueryBuilder()
            ->select(
                'u.id AS user_id',
                'u.lastname AS lastname',
                'u.firstname AS firstname',
                'u.username AS username',
                'u.officialCode AS official_code',
                'IDENTITY(r.url) AS access_url_id'
            )
            ->from(AccessUrlRelUser::class, 'r')
            ->innerJoin('r.user', 'u')
            ->where('u.active <> :softDeleted')
            ->setParameter('softDeleted', USER_SOFT_DELETED)
        ;
        if (!empty($urlId)) {
            $qb->andWhere('IDENTITY(r.url) = :urlId')->setParameter('urlId', $urlId);
        }

        // $order_by used to be a raw SQL fragment; only the two orders the platform really uses are accepted.
        $lastNameFirst = ['u.lastname', 'u.firstname', 'u.username'];
        $firstNameFirst = ['u.firstname', 'u.lastname', 'u.username'];
        $orderFields = api_sort_by_first_name() ? $firstNameFirst : $lastNameFirst;
        if (!empty($order_by)) {
            $wanted = strtolower(preg_replace('/\s+/', ' ', trim((string) $order_by)));
            if ('order by lastname, firstname, username' === $wanted) {
                $orderFields = $lastNameFirst;
            } elseif ('order by firstname, lastname, username' === $wanted) {
                $orderFields = $firstNameFirst;
            }
        }
        foreach ($orderFields as $field) {
            $qb->addOrderBy($field, 'ASC');
        }

        return array_map(
            static function (array $row): array {
                $row['access_url_id'] = (int) $row['access_url_id'];

                return $row;
            },
            $qb->getQuery()->getArrayResult()
        );
    }

    /**
     * Gets the inner join of access_url and the course table.
     *
     * @author Julio Montoya
     *
     * @param int  access url id
     *
     * @return array Database::store_result of the result
     */
    public static function get_url_rel_course_data($urlId = 0)
    {
        $urlId = (int) $urlId;
        $qb = Container::getEntityManager()->createQueryBuilder()
            ->select('c.id AS id', 'IDENTITY(uc.course) AS c_id', 'c.title AS title', 'IDENTITY(uc.url) AS access_url_id')
            ->from(AccessUrlRelCourse::class, 'uc')
            ->innerJoin('uc.course', 'c')
            ->orderBy('c.title', 'ASC')
            ->addOrderBy('c.code', 'ASC')
        ;
        if (!empty($urlId)) {
            $qb->where('IDENTITY(uc.url) = :urlId')->setParameter('urlId', $urlId);
        }

        return array_map(
            static function (array $row): array {
                $row['c_id'] = (int) $row['c_id'];
                $row['access_url_id'] = (int) $row['access_url_id'];

                return $row;
            },
            $qb->getQuery()->getArrayResult()
        );
    }

    /**
     * Gets the number of rows with a specific course_code in access_url_rel_course table.
     *
     * @author Yoselyn Castillo
     *
     * @param int $courseId
     *
     * @return int Database::num_rows($res);
     */
    public static function getCountUrlRelCourse($courseId)
    {
        return (int) Container::getEntityManager()->createQueryBuilder()
            ->select('COUNT(r.id)')
            ->from(AccessUrlRelCourse::class, 'r')
            ->where('IDENTITY(r.course) = :courseId')
            ->setParameter('courseId', (int) $courseId)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * Gets the inner join of access_url and the session table.
     *
     * @author Julio Montoya
     *
     * @param int $urlId access url id
     *
     * @return array Database::store_result of the result
     */
    public static function get_url_rel_session_data($urlId = 0)
    {
        $urlId = (int) $urlId;
        $qb = Container::getEntityManager()->createQueryBuilder()
            ->select('s.id AS id', 's.title AS name', 'IDENTITY(r.url) AS access_url_id')
            ->from(AccessUrlRelSession::class, 'r')
            ->innerJoin('r.session', 's')
            ->orderBy('s.title', 'ASC')
            ->addOrderBy('s.id', 'ASC')
        ;
        if (!empty($urlId)) {
            $qb->where('IDENTITY(r.url) = :urlId')->setParameter('urlId', $urlId);
        }

        return array_map(
            static function (array $row): array {
                $row['access_url_id'] = (int) $row['access_url_id'];

                return $row;
            },
            $qb->getQuery()->getArrayResult()
        );
    }

    /**
     * Gets the inner join of access_url and the usergroup table.
     *
     * @author Julio Montoya
     *
     * @param int $urlId
     *
     * @return array Database::store_result of the result
     */
    public static function get_url_rel_usergroup_data($urlId = 0)
    {
        $urlId = (int) $urlId;
        $qb = Container::getEntityManager()->createQueryBuilder()
            ->select('g.id AS id', 'g.title AS title', 'IDENTITY(r.url) AS access_url_id')
            ->from(AccessUrlRelUserGroup::class, 'r')
            ->innerJoin('r.userGroup', 'g')
            ->orderBy('g.title', 'ASC')
        ;
        if (!empty($urlId)) {
            $qb->where('IDENTITY(r.url) = :urlId')->setParameter('urlId', $urlId);
        }

        return array_map(
            static function (array $row): array {
                $row['access_url_id'] = (int) $row['access_url_id'];

                return $row;
            },
            $qb->getQuery()->getArrayResult()
        );
    }

    /**
     * Gets the inner join of access_url and the usergroup table.
     *
     * @author Julio Montoya
     *
     * @param int $urlId
     *
     * @return array Database::store_result of the result
     */
    public static function getUrlRelCourseCategory($urlId = 0)
    {
        $urlId = (int) $urlId;
        $qb = Container::getEntityManager()->createQueryBuilder()
            ->select('c.id AS id', 'c.title AS title', 'IDENTITY(r.url) AS access_url_id')
            ->from(AccessUrlRelCourseCategory::class, 'r')
            ->innerJoin('r.courseCategory', 'c')
            ->where('c.parent IS NULL')
            ->orderBy('c.title', 'ASC')
        ;
        if (!empty($urlId)) {
            $qb->andWhere('IDENTITY(r.url) = :urlId')->setParameter('urlId', $urlId);
        }

        return array_map(
            static function (array $row): array {
                $row['access_url_id'] = (int) $row['access_url_id'];

                return $row;
            },
            $qb->getQuery()->getArrayResult()
        );
    }

    /**
     * Sets the status of an URL 1 or 0.
     *
     * @author Julio Montoya
     *
     * @param string $status lock || unlock
     * @param int url id
     * */
    public static function set_url_status($status, $urlId)
    {
        if ('lock' == $status) {
            $status_db = '0';
        }
        if ('unlock' == $status) {
            $status_db = '1';
        }
        if (('1' == $status_db || '0' == $status_db) && is_numeric($urlId)) {
            Container::getEntityManager()
                ->createQuery('UPDATE '.AccessUrl::class.' u SET u.active = :active WHERE u.id = :id')
                ->setParameter('active', (int) $status_db)
                ->setParameter('id', (int) $urlId)
                ->execute()
            ;
        }
    }

    /**
     * Checks the relationship between an URL and a User (return the num_rows).
     *
     * @author Julio Montoya
     *
     * @param int user id
     * @param int url id
     *
     * @return bool true if success
     * */
    public static function relation_url_user_exist($user_id, $urlId)
    {
        return (int) Container::getEntityManager()->createQueryBuilder()
            ->select('COUNT(r.id)')
            ->from(AccessUrlRelUser::class, 'r')
            ->where('IDENTITY(r.url) = :urlId')
            ->andWhere('IDENTITY(r.user) = :relId')
            ->setParameter('urlId', (int) $urlId)
            ->setParameter('relId', (int) $user_id)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * Checks the relationship between an URL and a Course (return the num_rows).
     *
     * @author Julio Montoya
     *
     * @param int $courseId
     * @param int $urlId
     *
     * @return bool true if success
     * */
    public static function relation_url_course_exist($courseId, $urlId)
    {
        return (int) Container::getEntityManager()->createQueryBuilder()
            ->select('COUNT(r.id)')
            ->from(AccessUrlRelCourse::class, 'r')
            ->where('IDENTITY(r.url) = :urlId')
            ->andWhere('IDENTITY(r.course) = :relId')
            ->setParameter('urlId', (int) $urlId)
            ->setParameter('relId', (int) $courseId)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * Checks the relationship between an URL and a UserGr
     * oup (return the num_rows).
     *
     * @author Julio Montoya
     *
     * @param int $userGroupId
     * @param int $urlId
     *
     * @return bool true if success
     * */
    public static function relationUrlUsergroupExist($userGroupId, $urlId)
    {
        return (int) Container::getEntityManager()->createQueryBuilder()
            ->select('COUNT(r.id)')
            ->from(AccessUrlRelUserGroup::class, 'r')
            ->where('IDENTITY(r.url) = :urlId')
            ->andWhere('IDENTITY(r.userGroup) = :relId')
            ->setParameter('urlId', (int) $urlId)
            ->setParameter('relId', (int) $userGroupId)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * Checks the relationship between an URL and a Session (return the num_rows).
     *
     * @author Julio Montoya
     *
     * @param int $sessionId
     * @param int $urlId
     *
     * @return bool true if success
     * */
    public static function relation_url_session_exist($sessionId, $urlId)
    {
        return (int) Container::getEntityManager()->createQueryBuilder()
            ->select('COUNT(r.id)')
            ->from(AccessUrlRelSession::class, 'r')
            ->where('IDENTITY(r.url) = :urlId')
            ->andWhere('IDENTITY(r.session) = :relId')
            ->setParameter('urlId', (int) $urlId)
            ->setParameter('relId', (int) $sessionId)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * Add a group of users into a group of URLs.
     *
     * @author Julio Montoya
     *
     * @param  array of user_ids
     * @param  array of url_ids
     *
     * @return array
     *
     * @throws Exception
     */
    public static function add_users_to_urls($user_list, $url_list): array
    {
        $result_array = [];

        if (is_array($user_list) && is_array($url_list)) {
            foreach ($url_list as $urlId) {
                foreach ($user_list as $user_id) {
                    $result = self::add_user_to_url($user_id, $urlId, true);

                    if ($result) {
                        $result_array[$urlId][$user_id] = 1;
                    } else {
                        $result_array[$urlId][$user_id] = 0;
                    }
                }
            }

            Database::getManager()->flush();

            return $result_array;
        }

        return [];
    }

    public static function remove_users_from_urls(array $userIds, array $urlIds): void
    {
        if (empty($userIds) || empty($urlIds)) {
            return;
        }

        $conn = Database::getManager()->getConnection();
        $placeholdersUsers = implode(',', array_fill(0, count($userIds), '?'));
        $placeholdersUrls = implode(',', array_fill(0, count($urlIds), '?'));

        $sql = "DELETE FROM access_url_rel_user
            WHERE user_id IN ($placeholdersUsers)
            AND access_url_id IN ($placeholdersUrls)";

        $conn->executeQuery($sql, array_merge($userIds, $urlIds));
    }

    /**
     * Add a group of courses into a group of URLs.
     *
     * @author Julio Montoya
     *
     * @param array $course_list of course ids
     * @param array $url_list    of url_ids
     *
     * @return array
     */
    public static function add_courses_to_urls($course_list, $url_list)
    {
        $table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
        $result_array = [];

        if (is_array($course_list) && is_array($url_list)) {
            foreach ($url_list as $urlId) {
                foreach ($course_list as $course_code) {
                    $courseInfo = api_get_course_info($course_code);
                    $courseId = $courseInfo['real_id'];

                    $count = self::relation_url_course_exist($courseId, $urlId);
                    if (0 == $count) {
                        $result = self::insertRelation($table, ['c_id' => $courseId, 'access_url_id' => $urlId]);
                        if (null !== $result) {
                            $result_array[$urlId][$course_code] = 1;
                        } else {
                            $result_array[$urlId][$course_code] = 0;
                        }
                    }
                }
            }
        }

        return $result_array;
    }

    /**
     * Add a group of user group into a group of URLs.
     *
     * @author Julio Montoya
     *
     * @param array $userGroupList of course ids
     * @param array $urlList       of url_ids
     *
     * @return array
     */
    public static function addUserGroupListToUrl($userGroupList, $urlList)
    {
        $resultArray = [];
        if (is_array($userGroupList) && is_array($urlList)) {
            foreach ($urlList as $urlId) {
                foreach ($userGroupList as $userGroupId) {
                    $count = self::relationUrlUsergroupExist(
                        $userGroupId,
                        $urlId
                    );
                    if (0 == $count) {
                        $result = self::addUserGroupToUrl($userGroupId, $urlId);
                        if ($result) {
                            $resultArray[$urlId][$userGroupId] = 1;
                        } else {
                            $resultArray[$urlId][$userGroupId] = 0;
                        }
                    }
                }
            }
        }

        return $resultArray;
    }

    /**
     * Remove a list of user groups from a list of URLs.
     *
     * @param array $userGroupList List of user group IDs
     * @param array $urlList       List of access_url IDs
     *
     * @return void
     */
    public static function removeUserGroupListFromUrl(array $userGroupList, array $urlList): void
    {
        if (empty($userGroupList) || empty($urlList)) {
            return;
        }

        $table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USERGROUP);

        $placeholdersGroups = implode(',', array_fill(0, count($userGroupList), '?'));
        $placeholdersUrls = implode(',', array_fill(0, count($urlList), '?'));

        $sql = "DELETE FROM $table
            WHERE usergroup_id IN ($placeholdersGroups)
            AND access_url_id IN ($placeholdersUrls)";

        $params = array_merge($userGroupList, $urlList);
        Database::getManager()->getConnection()->executeQuery($sql, $params);
    }

    /**
     * Add a group of user group into a group of URLs.
     *
     * @author Julio Montoya
     *
     * @param  array of course ids
     * @param  array of url_ids
     *
     * @return array
     */
    public static function addCourseCategoryListToUrl($courseCategoryList, $urlList)
    {
        $resultArray = [];
        if (is_array($courseCategoryList) && is_array($urlList)) {
            foreach ($urlList as $urlId) {
                foreach ($courseCategoryList as $categoryCourseId) {
                    $count = self::relationUrlCourseCategoryExist($categoryCourseId, $urlId);
                    if (0 == $count) {
                        $result = self::addCourseCategoryToUrl($categoryCourseId, $urlId);
                        if ($result) {
                            $resultArray[$urlId][$categoryCourseId] = 1;
                        } else {
                            $resultArray[$urlId][$categoryCourseId] = 0;
                        }
                    }
                }
            }
        }

        return $resultArray;
    }

    /**
     * Checks the relationship between an URL and a UserGr
     * oup (return the num_rows).
     *
     * @author Julio Montoya
     *
     * @param int $categoryCourseId
     * @param int $urlId
     *
     * @return bool true if success
     * */
    public static function relationUrlCourseCategoryExist($categoryCourseId, $urlId)
    {
        return (int) Container::getEntityManager()->createQueryBuilder()
            ->select('COUNT(r.id)')
            ->from(AccessUrlRelCourseCategory::class, 'r')
            ->where('IDENTITY(r.url) = :urlId')
            ->andWhere('IDENTITY(r.courseCategory) = :relId')
            ->setParameter('urlId', (int) $urlId)
            ->setParameter('relId', (int) $categoryCourseId)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * @param int $userGroupId
     * @param int $urlId
     *
     * @return int
     */
    public static function addUserGroupToUrl($userGroupId, $urlId)
    {
        $table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USERGROUP);

        return (int) self::insertRelation($table, ['usergroup_id' => $userGroupId, 'access_url_id' => $urlId]);
    }

    /**
     * @param int $categoryId
     * @param int $urlId
     *
     * @return int
     */
    public static function addCourseCategoryToUrl($categoryId, $urlId)
    {
        $exists = self::relationUrlCourseCategoryExist($categoryId, $urlId);
        if (empty($exists)) {
            $table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE_CATEGORY);

            return (int) self::insertRelation($table, ['course_category_id' => $categoryId, 'access_url_id' => $urlId]);
        }

        return 0;
    }

    /**
     * Add a group of sessions into a group of URLs.
     *
     * @author Julio Montoya
     *
     * @param array $session_list of session ids
     * @param array $url_list     of url_ids
     *
     * @return array
     * */
    public static function add_sessions_to_urls($session_list, $url_list)
    {
        $table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
        $result_array = [];

        if (is_array($session_list) && is_array($url_list)) {
            foreach ($url_list as $urlId) {
                foreach ($session_list as $session_id) {
                    $count = self::relation_url_session_exist($session_id, $urlId);

                    if (0 == $count) {
                        $result = self::insertRelation($table, ['session_id' => $session_id, 'access_url_id' => $urlId]);
                        if (null !== $result) {
                            $result_array[$urlId][$session_id] = 1;
                        } else {
                            $result_array[$urlId][$session_id] = 0;
                        }
                    }
                }
            }
        }

        return $result_array;
    }

    /**
     * Add a user into a url.
     *
     * @author Julio Montoya
     *
     * @param int $user_id
     * @param int $urlId
     *
     * @return bool true if success
     *
     * @throws Exception
     */
    public static function add_user_to_url($user_id, $urlId = 1, bool $addAuthSource = false)
    {
        $table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        if (empty($urlId)) {
            $urlId = 1;
        }
        $count = self::relation_url_user_exist($user_id, $urlId);
        $result = true;
        if (empty($count)) {
            $result = null !== self::insertRelation($table, ['user_id' => $user_id, 'access_url_id' => $urlId]);
        }

        if ($addAuthSource && $result) {
            /** @var AccessUrl|null $accessUrl */
            $accessUrl = Container::getAccessUrlRepository()->find($urlId);
            $user = api_get_user_entity($user_id);

            $authSources = $user->getAuthSourcesByUrl($accessUrl);

            if ($authSources->count() <= 0) {
                $firstAuthSource = $user->getAuthSources()->first();

                if ($firstAuthSource) {
                    $user->addAuthSourceByAuthentication(
                        $firstAuthSource->getAuthentication(),
                        $accessUrl
                    );

                    Database::getManager()->flush();
                }
            }
        }

        return $result;
    }

    /**
     * @param int $courseId
     * @param int $urlId
     *
     * @return resource
     */
    public static function add_course_to_url($courseId, $urlId = 1)
    {
        $table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
        if (empty($urlId)) {
            $urlId = 1;
        }
        $count = self::relation_url_course_exist($courseId, $urlId);
        if (empty($count)) {
            self::insertRelation($table, ['c_id' => $courseId, 'access_url_id' => $urlId]);
        }

        return true;
    }

    /**
     * Inserts a session to a URL (access_url_rel_session table).
     *
     * @param int $session_id Session ID
     * @param   int     URL ID
     *
     * @return bool True on success, false session already exists or insert failed
     */
    public static function add_session_to_url($session_id, $urlId = 1)
    {
        $table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
        $session_id = (int) $session_id;
        $urlId = (int) $urlId;

        if (empty($urlId)) {
            $urlId = 1;
        }
        $result = false;
        $count = self::relation_url_session_exist($session_id, $urlId);
        if (empty($count) && !empty($session_id)) {
            try {
                self::insertRelation($table, ['session_id' => $session_id, 'access_url_id' => $urlId]);
            } catch (Exception $e) {
                return false;
            }

            return true;
        }

        return $result;
    }

    /**
     * Deletes an url and user relationship.
     *
     * @author Julio Montoya
     *
     * @param int user id
     * @param int url id
     *
     * @return bool true if success
     * */
    public static function delete_url_rel_user($user_id, $urlId)
    {
        $result = true;
        if (!empty($user_id) && !empty($urlId)) {
            Container::getEntityManager()
                ->createQuery('DELETE FROM '.AccessUrlRelUser::class.' r WHERE r.user = :user AND r.url = :url')
                    ->setParameter('user', (int) $user_id)
                    ->setParameter('url', (int) $urlId)
                ->execute()
            ;
        }

        return $result;
    }

    /**
     * Deletes user from all portals.
     *
     * @author Julio Montoya
     *
     * @param int user id
     *
     * @return bool true if success
     * */
    public static function deleteUserFromAllUrls($userId)
    {
        $result = true;
        if (!empty($userId)) {
            Container::getEntityManager()
                ->createQuery('DELETE FROM '.AccessUrlRelUser::class.' r WHERE r.user = :user')
                    ->setParameter('user', (int) $userId)
                ->execute()
            ;
        }

        return $result;
    }

    /**
     * Deletes an url and course relationship.
     *
     * @author Julio Montoya
     *
     * @param int $courseId
     * @param int $urlId
     *
     * @return bool true if success
     * */
    public static function delete_url_rel_course($courseId, $urlId)
    {
        Container::getEntityManager()
            ->createQuery('DELETE FROM '.AccessUrlRelCourse::class.' r WHERE r.course = :course AND r.url = :url')
                ->setParameter('course', (int) $courseId)
                ->setParameter('url', (int) $urlId)
            ->execute()
        ;

        return true;
    }

    /**
     * Deletes an url and $userGroup relationship.
     *
     * @author Julio Montoya
     *
     * @param int $userGroupId
     * @param int $urlId
     *
     * @return bool true if success
     * */
    public static function delete_url_rel_usergroup($userGroupId, $urlId)
    {
        Container::getEntityManager()
            ->createQuery('DELETE FROM '.AccessUrlRelUserGroup::class.' r WHERE r.userGroup = :userGroup AND r.url = :url')
                ->setParameter('userGroup', (int) $userGroupId)
                ->setParameter('url', (int) $urlId)
            ->execute()
        ;

        return true;
    }

    /**
     * Deletes an url and $userGroup relationship.
     *
     * @author Julio Montoya
     *
     * @param int $userGroupId
     * @param int $urlId
     *
     * @return bool true if success
     * */
    public static function deleteUrlRelCourseCategory($userGroupId, $urlId)
    {
        Container::getEntityManager()
            ->createQuery('DELETE FROM '.AccessUrlRelCourseCategory::class.' r WHERE r.courseCategory = :category AND r.url = :url')
                ->setParameter('category', (int) $userGroupId)
                ->setParameter('url', (int) $urlId)
            ->execute()
        ;

        return true;
    }

    /**
     * Deletes an url and session relationship.
     *
     * @author Julio Montoya
     *
     * @param int $session_id
     * @param int $urlId
     *
     * @return bool true if success
     * */
    public static function delete_url_rel_session($session_id, $urlId)
    {
        Container::getEntityManager()
            ->createQuery('DELETE FROM '.AccessUrlRelSession::class.' r WHERE r.session = :session AND r.url = :url')
                ->setParameter('session', (int) $session_id)
                ->setParameter('url', (int) $urlId)
            ->execute()
        ;

        return true;
    }

    /**
     * Updates the access_url_rel_user table  with a given user list.
     *
     * @author Julio Montoya
     *
     * @param array $user_list
     * @param int   $urlId
     *
     * @return bool|array
     */
    public static function update_urls_rel_user($user_list, $urlId, bool $updateAuthSource = false)
    {
        $urlId = (int) $urlId;

        $existing_users = array_map(
            'intval',
            array_column(
                Container::getEntityManager()->createQueryBuilder()
                    ->select('IDENTITY(r.user) AS user_id')
                    ->from(AccessUrlRelUser::class, 'r')
                    ->where('IDENTITY(r.url) = :urlId')
                    ->setParameter('urlId', $urlId)
                    ->getQuery()
                    ->getArrayResult(),
                'user_id'
            )
        );

        // Adding users
        $users_added = [];
        foreach ($user_list as $user_id_to_add) {
            if (!in_array($user_id_to_add, $existing_users)) {
                $result = self::add_user_to_url($user_id_to_add, $urlId, $updateAuthSource);
                if ($result) {
                    $users_added[] = $user_id_to_add;
                }
            }
        }

        $users_deleted = [];
        // Deleting old users
        foreach ($existing_users as $user_id_to_delete) {
            if (!in_array($user_id_to_delete, $user_list)) {
                $result = self::delete_url_rel_user($user_id_to_delete, $urlId);
                if ($result) {
                    $users_deleted[] = $user_id_to_delete;
                }
            }
        }

        if (empty($users_added) && empty($users_deleted)) {
            return false;
        }

        return [
            'users_added' => $users_added,
            'users_deleted' => $users_deleted,
        ];
    }

    /**
     * Updates the access_url_rel_course table  with a given user list.
     *
     * @author Julio Montoya
     *
     * @param array $course_list
     * @param int   $urlId
     * */
    public static function update_urls_rel_course($course_list, $urlId)
    {
        $existing_courses = array_map(
            'intval',
            array_column(
                Container::getEntityManager()->createQueryBuilder()
                    ->select('IDENTITY(r.course) AS c_id')
                    ->from(AccessUrlRelCourse::class, 'r')
                    ->where('IDENTITY(r.url) = :urlId')
                    ->setParameter('urlId', (int) $urlId)
                    ->getQuery()
                    ->getArrayResult(),
                'c_id'
            )
        );

        // Adding courses
        foreach ($course_list as $courseId) {
            self::add_course_to_url($courseId, $urlId);
            CourseManager::update_course_ranking($courseId);
        }

        // Deleting old courses
        foreach ($existing_courses as $courseId) {
            if (!in_array($courseId, $course_list)) {
                self::delete_url_rel_course($courseId, $urlId);
                CourseManager::update_course_ranking($courseId);
            }
        }
    }

    /**
     * Updates the access_url_rel_course table  with a given user list.
     *
     * @author Julio Montoya
     *
     * @param array $userGroupList user list
     * @param int   $urlId
     * */
    public static function update_urls_rel_usergroup(array $userGroupList, int $urlId): void
    {
        if ($urlId <= 0) {
            return;
        }

        $existingItems = array_map(
            'intval',
            array_column(
                Container::getEntityManager()->createQueryBuilder()
                    ->select('IDENTITY(r.userGroup) AS usergroup_id')
                    ->from(AccessUrlRelUserGroup::class, 'r')
                    ->where('IDENTITY(r.url) = :urlId')
                    ->setParameter('urlId', $urlId)
                    ->getQuery()
                    ->getArrayResult(),
                'usergroup_id'
            )
        );

        foreach ($userGroupList as $userGroupId) {
            if (!in_array($userGroupId, $existingItems)) {
                self::addUserGroupToUrl($userGroupId, $urlId);
            }
        }

        foreach ($existingItems as $userGroupId) {
            if (!in_array($userGroupId, $userGroupList)) {
                self::delete_url_rel_usergroup($userGroupId, $urlId);
            }
        }
    }

    /**
     * Updates the access_url_rel_course_category table with a given list.
     *
     * @author Julio Montoya
     *
     * @param array $list  course category list
     * @param int   $urlId access_url_id
     */
    public static function updateUrlRelCourseCategory($list, $urlId)
    {
        $existingItems = array_map(
            'intval',
            array_column(
                Container::getEntityManager()->createQueryBuilder()
                    ->select('IDENTITY(r.courseCategory) AS course_category_id')
                    ->from(AccessUrlRelCourseCategory::class, 'r')
                    ->where('IDENTITY(r.url) = :urlId')
                    ->setParameter('urlId', (int) $urlId)
                    ->getQuery()
                    ->getArrayResult(),
                'course_category_id'
            )
        );

        // Adding
        foreach ($list as $id) {
            self::addCourseCategoryToUrl($id, $urlId);
            foreach (self::getCourseCategoryDescendantIds((int) $id) as $childId) {
                self::addCourseCategoryToUrl($childId, $urlId);
            }
        }

        // Deleting old items
        foreach ($existingItems as $id) {
            if (!in_array($id, $list)) {
                self::deleteUrlRelCourseCategory($id, $urlId);
                foreach (self::getCourseCategoryDescendantIds((int) $id) as $childId) {
                    self::deleteUrlRelCourseCategory($childId, $urlId);
                }
            }
        }
    }

    /**
     * Ids of every category below the given one (course_category.parent_id is an id, not a code).
     *
     * @return int[]
     */
    private static function getCourseCategoryDescendantIds(int $categoryId): array
    {
        $em = Container::getEntityManager();
        $descendants = [];
        $pending = [$categoryId];
        while (!empty($pending)) {
            $parentId = array_pop($pending);
            $rows = $em->createQueryBuilder()
                ->select('c.id AS id')
                ->from(CourseCategory::class, 'c')
                ->where('IDENTITY(c.parent) = :parentId')
                ->setParameter('parentId', $parentId)
                ->getQuery()
                ->getArrayResult()
            ;
            foreach ($rows as $row) {
                $childId = (int) $row['id'];
                if (!in_array($childId, $descendants, true)) {
                    $descendants[] = $childId;
                    $pending[] = $childId;
                }
            }
        }

        return $descendants;
    }

    /**
     * Updates the access_url_rel_session table with a given user list.
     *
     * @author Julio Montoya
     *
     * @param array $session_list
     * @param int   $urlId
     * */
    public static function update_urls_rel_session($session_list, $urlId)
    {
        $existing_sessions = array_map(
            'intval',
            array_column(
                Container::getEntityManager()->createQueryBuilder()
                    ->select('IDENTITY(r.session) AS session_id')
                    ->from(AccessUrlRelSession::class, 'r')
                    ->where('IDENTITY(r.url) = :urlId')
                    ->setParameter('urlId', (int) $urlId)
                    ->getQuery()
                    ->getArrayResult(),
                'session_id'
            )
        );

        // Adding users
        foreach ($session_list as $session) {
            if (!in_array($session, $existing_sessions)) {
                if (!empty($session) && !empty($urlId)) {
                    self::add_session_to_url($session, $urlId);
                }
            }
        }

        // Deleting old users
        foreach ($existing_sessions as $existing_session) {
            if (!in_array($existing_session, $session_list)) {
                if (!empty($existing_session) && !empty($urlId)) {
                    self::delete_url_rel_session(
                        $existing_session,
                        $urlId
                    );
                }
            }
        }
    }

    /**
     * @param int $user_id
     *
     * @return array
     */
    public static function get_access_url_from_user($user_id)
    {
        return array_map(
            static function (array $row): array {
                $row['access_url_id'] = (int) $row['access_url_id'];

                return $row;
            },
            Container::getEntityManager()->createQueryBuilder()
                ->select('u.url AS url', 'IDENTITY(r.url) AS access_url_id')
                ->from(AccessUrlRelUser::class, 'r')
                ->innerJoin('r.url', 'u')
                ->where('IDENTITY(r.user) = :userId')
                ->setParameter('userId', (int) $user_id)
                ->getQuery()
                ->getArrayResult()
        );
    }

    /**
     * @param int $courseId
     *
     * @return array
     */
    public static function get_access_url_from_course($courseId)
    {
        return array_map(
            static function (array $row): array {
                $row['access_url_id'] = (int) $row['access_url_id'];

                return $row;
            },
            Container::getEntityManager()->createQueryBuilder()
                ->select('u.url AS url', 'IDENTITY(c.url) AS access_url_id')
                ->from(AccessUrlRelCourse::class, 'c')
                ->innerJoin('c.url', 'u')
                ->where('IDENTITY(c.course) = :courseId')
                ->setParameter('courseId', (int) $courseId)
                ->getQuery()
                ->getArrayResult()
        );
    }

    public static function getCountAccessUrlFromCourse($courseId)
    {
        return (int) Container::getEntityManager()->createQueryBuilder()
            ->select('COUNT(u.id)')
            ->from(AccessUrlRelCourse::class, 'c')
            ->innerJoin('c.url', 'u')
            ->where('IDENTITY(c.course) = :courseId')
            ->setParameter('courseId', (int) $courseId)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * @param $sessionId
     *
     * @return array
     */
    public static function get_access_url_from_session($sessionId)
    {
        return array_map(
            static function (array $row): array {
                $row['access_url_id'] = (int) $row['access_url_id'];

                return $row;
            },
            Container::getEntityManager()->createQueryBuilder()
                ->select('u.url AS url', 'IDENTITY(r.url) AS access_url_id')
                ->from(AccessUrlRelSession::class, 'r')
                ->innerJoin('r.url', 'u')
                ->where('IDENTITY(r.session) = :sessionId')
                ->setParameter('sessionId', (int) $sessionId)
                ->getQuery()
                ->getArrayResult()
        );
    }

    /**
     * @param string $url
     *
     * @return bool|mixed|null
     */
    public static function get_url_id($url)
    {
        $row = Container::getEntityManager()->createQueryBuilder()
            ->select('u.id AS id')
            ->from(AccessUrl::class, 'u')
            ->where('u.url = :url')
            ->setParameter('url', (string) $url)
            ->setMaxResults(1)
            ->getQuery()
            ->getArrayResult()[0] ?? null;

        return null === $row ? null : $row['id'];
    }

    /**
     * @param string $needle
     *
     * @return XajaxResponse
     */
    public static function searchCourseCategoryAjax($needle)
    {
        $response = new xajaxResponse();
        $return = '';

        if (!empty($needle)) {
            // xajax send utf8 datas... datas in db can be non-utf8 datas
            $charset = api_get_system_encoding();
            $needle = api_convert_encoding($needle, $charset, 'utf-8');
            $needle = Database::escape_string($needle);
            // search courses where username or firstname or lastname begins likes $needle
            $sql = 'SELECT id, u.title
                    FROM '.Database::get_main_table(TABLE_MAIN_CATEGORY).' u
                    WHERE
                        u.title LIKE "'.$needle.'%" AND
                        (parent_id IS NULL or parent_id = 0)
                    ORDER BY u.title
                    LIMIT 11';
            $result = Database::query($sql);
            $i = 0;
            while ($data = Database::fetch_array($result)) {
                $i++;
                if ($i <= 10) {
                    $return .= '<a
                    href="javascript: void(0);"
                    onclick="javascript: add_user_to_url(\''.addslashes($data['id']).'\',\''.addslashes($data['title']).' \')">'.$data['title'].' </a><br />';
                } else {
                    $return .= '...<br />';
                }
            }
        }

        $response->addAssign(
            'ajax_list_courses',
            'innerHTML',
            api_utf8_encode($return)
        );

        return $response;
    }

    public static function remove_courses_from_urls(array $courseCodes, array $urlIds): void
    {
        if (empty($courseCodes) || empty($urlIds)) {
            return;
        }

        $conn = Database::getManager()->getConnection();
        $placeholdersCourses = implode(',', array_fill(0, count($courseCodes), '?'));
        $placeholdersUrls = implode(',', array_fill(0, count($urlIds), '?'));

        $sql = "DELETE FROM access_url_rel_course
            WHERE c_id IN (
                SELECT id FROM course WHERE code IN ($placeholdersCourses)
            )
            AND access_url_id IN ($placeholdersUrls)";

        $conn->executeQuery($sql, array_merge($courseCodes, $urlIds));
    }

    public static function searchCoursesByTitleOrCode(string $needle): array
    {
        $charset = api_get_system_encoding();
        $needle = api_convert_encoding($needle, $charset, 'utf-8');

        return Container::getEntityManager()->createQueryBuilder()
            ->select('c.id AS id', 'c.code AS code', 'c.title AS title')
            ->from(Course::class, 'c')
            ->where('c.title LIKE :needle OR c.code LIKE :needle')
            ->setParameter('needle', $needle.'%')
            ->orderBy('c.title', 'ASC')
            ->addOrderBy('c.code', 'ASC')
            ->setMaxResults(11)
            ->getQuery()
            ->getArrayResult()
        ;
    }
}
