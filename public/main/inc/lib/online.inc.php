<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\AccessUrlRelUser;
use Chamilo\CoreBundle\Entity\TrackELogin;
use Chamilo\CoreBundle\Entity\TrackEOnline;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserAuthSource;
use Chamilo\CoreBundle\Entity\UserRelUser;
use Chamilo\CoreBundle\Framework\Container;
use ChamiloSession as Session;

/**
 * Code library for showing Who is online.
 *
 * @author Istvan Mandak, principal author
 * @author Denes Nagy, principal author
 * @author Bart Mollet
 * @author Roan Embrechts, cleaning and bugfixing
 */

/**
 * This function handles the logout and is called whenever there is a $_GET['logout'].
 *
 * @param int  $user_id
 * @param bool $logout_redirect
 *
 * @author Fernando P. García <fernando@develcuy.com>
 */
function online_logout($user_id = null, $logout_redirect = false)
{
    global $extAuthSource;

    if (empty($user_id)) {
        $user_id = isset($_GET['uid']) ? intval($_GET['uid']) : 0;
    }

    // Changing global chat status to offline
    if (api_is_global_chat_enabled()) {
        $chat = new Chat();
        $chat->setUserStatus(0);
    }

    $chat = new Chat();
    $chat->close();

    // selecting the last login of the user
    $entityManager = Container::getEntityManager();
    $lastConnections = $entityManager->createQueryBuilder()
        ->select('l.loginId AS login_id')
        ->from(TrackELogin::class, 'l')
        ->where('l.user = :userId')
        ->orderBy('l.loginDate', 'DESC')
        ->addOrderBy('l.loginId', 'DESC')
        ->setMaxResults(1)
        ->setParameter('userId', (int) $user_id)
        ->getQuery()
        ->getArrayResult()
    ;
    $i_id_last_connection = 0;
    if (count($lastConnections) > 0) {
        $i_id_last_connection = $lastConnections[0]['login_id'];
    }

    if (!isset($_SESSION['login_as']) && !empty($i_id_last_connection)) {
        $current_date = api_get_utc_datetime();
        $entityManager->createQueryBuilder()
            ->update(TrackELogin::class, 'l')
            ->set('l.logoutDate', ':logoutDate')
            ->where('l.loginId = :loginId')
            ->setParameter('logoutDate', $current_date)
            ->setParameter('loginId', (int) $i_id_last_connection)
            ->getQuery()
            ->execute()
        ;
    }
    $logInfo = [
        'tool' => 'logout',
        'tool_id' => 0,
        'tool_id_detail' => 0,
    ];
    Event::registerLog($logInfo);

    UserManager::loginDelete($user_id);

    //the following code enables the use of an external logout function.
    //example: define a $extAuthSource['ldap']['logout']="file.php" in configuration.php
    // then a function called ldap_logout() inside that file
    // (using *authent_name*_logout as the function name) and the following code
    // will find and execute it
    $uinfo = api_get_user_info($user_id);
    if (!in_array(UserAuthSource::PLATFORM, $uinfo['auth_sources']) && is_array($extAuthSource)) {
        $firstAuthSource = $uinfo['auth_sources'][0];

        if (is_array($extAuthSource[$firstAuthSource])) {
            $subarray = $extAuthSource[$firstAuthSource];
            if (!empty($subarray['logout']) && file_exists($subarray['logout'])) {
                require_once $subarray['logout'];
                $logout_function = $firstAuthSource.'_logout';
                if (function_exists($logout_function)) {
                    $logout_function($uinfo);
                }
            }
        }
    }

    // After logout redirect to
    $url = api_get_path(WEB_PATH).'index.php';

    if ($logout_redirect && 'true' == api_get_plugin_setting('azure_active_directory', 'enable')) {
        if ('azure_active_directory' === ChamiloSession::read('_user_auth_source')) {
            $activeDirectoryPlugin = AzureActiveDirectory::create();
            $azureLogout = $activeDirectoryPlugin->getUrl(AzureActiveDirectory::URL_TYPE_LOGOUT);
            if (!empty($azureLogout)) {
                $url = $azureLogout;
            }
        }
    }

    Session::erase('last_id');
    CourseChatUtils::exitChat($user_id);
    session_regenerate_id();
    Session::destroy();

    $pluginKeycloak = false; //Container::getPluginHelper()->isPluginEnabled('keycloak');
    if ($pluginKeycloak && in_array('keycloak', $uinfo['auth_sources'])) {
        $pluginUrl = api_get_path(WEB_PLUGIN_PATH).'keycloak/start.php?slo';
        header('Location: '.$pluginUrl);
        exit;
    }

    if ($logout_redirect) {
        header("Location: $url");
        exit;
    }
}

/**
 * @param int $user_id
 *
 * @return bool
 */
function user_is_online($user_id)
{
    $access_url_id = api_get_current_access_url_id();
    $time_limit = api_get_setting('time_limit_whosonline');

    $online_time = time() - $time_limit * 60;
    $limit_date = api_get_utc_datetime($online_time);
    $user_id = (int) $user_id;

    $rows = Container::getEntityManager()->createQueryBuilder()
        ->select('track.loginUserId AS login_user_id', 'track.loginDate AS login_date')
        ->from(TrackEOnline::class, 'track')
        ->innerJoin(User::class, 'u', 'WITH', 'u.id = track.loginUserId')
        ->where('track.accessUrlId = :accessUrlId')
        ->andWhere('track.loginDate >= :limitDate')
        ->andWhere('u.id = :userId')
        ->setMaxResults(1)
        ->setParameter('accessUrlId', (int) $access_url_id)
        ->setParameter('limitDate', $limit_date)
        ->setParameter('userId', $user_id)
        ->getQuery()
        ->getArrayResult()
    ;

    return count($rows) > 0;
}

/**
 * Gives a list of people online now (and in the last $valid minutes).
 *
 * $column is one of login_date, login_user_id, login_id, user_ip, c_id, session_id, access_url_id or
 * picture_uri (the last one only outside the friends mode, where the user table is not joined). Any other
 * value is replaced by the default column (picture_uri, or login_date in friends mode).
 *
 * @param $from
 * @param $number_of_items
 * @param null $column
 * @param null $direction
 * @param null $time_limit
 * @param bool $friends
 *
 * @return array|bool For each line, a list of user IDs and login dates, or FALSE on error or empty results
 */
function who_is_online(
    $from,
    $number_of_items,
    $column = null,
    $direction = null,
    $time_limit = null,
    $friends = false
) {
    // Time limit in seconds?
    if (empty($time_limit)) {
        $time_limit = api_get_setting('time_limit_whosonline');
    } else {
        $time_limit = intval($time_limit);
    }

    $from = intval($from);
    $number_of_items = intval($number_of_items);

    $columns = [
        'login_id' => 'track.loginId',
        'login_user_id' => 'track.loginUserId',
        'login_date' => 'track.loginDate',
        'user_ip' => 'track.userIp',
        'c_id' => 'track.cId',
        'session_id' => 'track.sessionId',
        'access_url_id' => 'track.accessUrlId',
    ];
    if (!$friends) {
        $columns['picture_uri'] = 'u.pictureUri';
    }
    $defaultColumn = $friends ? 'login_date' : 'picture_uri';
    if (empty($column) || !is_string($column) || !isset($columns[$column])) {
        $column = $defaultColumn;
    }

    $direction = strtolower((string) $direction);
    if (empty($direction)) {
        $direction = 'DESC';
    } else {
        if (!in_array($direction, ['asc', 'desc'])) {
            $direction = 'DESC';
        }
    }

    $online_time = time() - $time_limit * 60;
    $current_date = api_get_utc_datetime($online_time);

    $qb = Container::getEntityManager()->createQueryBuilder()
        ->select('track.loginUserId AS login_user_id', 'track.loginDate AS login_date')
        ->from(TrackEOnline::class, 'track')
        ->where('track.loginDate >= :currentDate')
        ->setParameter('currentDate', $current_date)
    ;

    $access_url_id = -1;
    if (api_get_multiple_access_url()) {
        $access_url_id = api_get_current_access_url_id();
    }

    if ($friends) {
        $qb
            ->innerJoin(UserRelUser::class, 'rel', 'WITH', 'IDENTITY(rel.friend) = track.loginUserId')
            ->andWhere('IDENTITY(rel.friend) <> :currentUserId')
            ->andWhere('rel.relationType = :relationType')
            ->setParameter('currentUserId', (int) api_get_user_id())
            ->setParameter('relationType', UserRelUser::USER_RELATION_TYPE_FRIEND)
        ;
        if (-1 != $access_url_id) {
            // friends from social network is online (multi-URL: no "user_id = me" condition, as before)
            $qb
                ->distinct()
                ->andWhere('track.accessUrlId = :accessUrlId')
                ->setParameter('accessUrlId', (int) $access_url_id)
            ;
        } else {
            // who friends from social network is online
            $qb
                ->distinct()
                ->andWhere('IDENTITY(rel.user) = :currentUserId')
            ;
        }
    } else {
        $qb
            ->innerJoin(User::class, 'u', 'WITH', 'u.id = track.loginUserId')
            ->andWhere('u.active <> :softDeleted')
            ->andWhere('u.status <> :anonymous')
            ->setParameter('softDeleted', USER_SOFT_DELETED)
            ->setParameter('anonymous', ANONYMOUS)
        ;
        if (-1 != $access_url_id) {
            // all users online (multi-URL)
            $qb
                ->andWhere('track.accessUrlId = :accessUrlId')
                ->setParameter('accessUrlId', (int) $access_url_id)
            ;
        } else {
            $qb->distinct();
        }
    }

    $qb
        ->orderBy($columns[$column], strtoupper($direction))
        ->setFirstResult($from)
        ->setMaxResults($number_of_items)
    ;

    $users_online = [];
    foreach ($qb->getQuery()->getArrayResult() as $row) {
        $users_online[] = $row['login_user_id'];
    }

    return $users_online;
}

/**
 * @param string $time_limit
 */
function who_is_online_count($time_limit = null, $friends = false)
{
    if (empty($time_limit)) {
        $time_limit = api_get_setting('time_limit_whosonline');
    } else {
        $time_limit = intval($time_limit);
    }
    $online_time = time() - $time_limit * 60;
    $current_date = api_get_utc_datetime($online_time);

    $access_url_id = -1;
    if (api_get_multiple_access_url()) {
        $access_url_id = api_get_current_access_url_id();
    }

    $qb = Container::getEntityManager()->createQueryBuilder()
        ->from(TrackEOnline::class, 'track')
        ->where('track.loginDate >= :currentDate')
        ->setParameter('currentDate', $current_date)
    ;

    if ($friends) {
        // who friends from social network is online (the multi-URL variant has no "user_id = me" condition)
        $qb
            ->select('COUNT(track.loginUserId)')
            ->innerJoin(UserRelUser::class, 'rel', 'WITH', 'IDENTITY(rel.friend) = track.loginUserId')
            ->andWhere('IDENTITY(rel.friend) <> :currentUserId')
            ->andWhere('rel.relationType = :relationType')
            ->setParameter('currentUserId', (int) api_get_user_id())
            ->setParameter('relationType', UserRelUser::USER_RELATION_TYPE_FRIEND)
        ;
        if (-1 != $access_url_id) {
            $qb
                ->andWhere('track.accessUrlId = :accessUrlId')
                ->setParameter('accessUrlId', (int) $access_url_id)
            ;
        } else {
            $qb->andWhere('IDENTITY(rel.user) = :currentUserId');
        }
    } else {
        // All users online
        $qb
            ->select('COUNT(track.loginId)')
            ->innerJoin(User::class, 'u', 'WITH', 'u.id = track.loginUserId')
            ->andWhere('u.active <> :softDeleted')
            ->andWhere('u.status <> :anonymous')
            ->setParameter('softDeleted', USER_SOFT_DELETED)
            ->setParameter('anonymous', ANONYMOUS)
        ;
        if (-1 != $access_url_id) {
            $qb
                ->andWhere('track.accessUrlId = :accessUrlId')
                ->setParameter('accessUrlId', (int) $access_url_id)
            ;
        }
    }

    return (int) $qb->getQuery()->getSingleScalarResult();
}

/**
 * Returns a list (array) of users who are online and in this course.
 *
 * @param    int User ID
 * @param    int Number of minutes
 * @param    string  Course code (could be empty, but then the function returns false)
 *
 * @return array Each line gives a user id and a login time
 */
function who_is_online_in_this_course($from, $number_of_items, $uid, $time_limit, $course_code)
{
    if (empty($course_code)) {
        return false;
    }

    $time_limit = (int) $time_limit;
    if (empty($time_limit)) {
        $time_limit = api_get_setting('time_limit_whosonline');
    }

    $online_time = time() - $time_limit * 60;
    $current_date = api_get_utc_datetime($online_time);
    $courseInfo = api_get_course_info($course_code);
    if (empty($courseInfo['real_id'])) {
        return false;
    }
    $courseId = (int) $courseInfo['real_id'];

    $qb = Container::getEntityManager()->createQueryBuilder()
        ->select('o.loginUserId AS login_user_id', 'o.loginDate AS login_date')
        ->from(TrackEOnline::class, 'o')
        ->innerJoin(User::class, 'u', 'WITH', 'o.loginUserId = u.id')
        ->where('u.active <> :softDeleted')
        ->andWhere('u.status <> :anonymous')
        ->andWhere('o.cId = :courseId')
        ->andWhere('o.loginDate >= :currentDate')
        ->setFirstResult((int) $from)
        ->setMaxResults((int) $number_of_items)
        ->setParameter('softDeleted', USER_SOFT_DELETED)
        ->setParameter('anonymous', ANONYMOUS)
        ->setParameter('courseId', $courseId)
        ->setParameter('currentDate', $current_date)
    ;
    if (api_is_multiple_url_enabled()) {
        $qb
            ->innerJoin(AccessUrlRelUser::class, 'a', 'WITH', 'IDENTITY(a.user) = u.id')
            ->andWhere('IDENTITY(a.url) = :urlId')
            ->setParameter('urlId', (int) api_get_current_access_url_id())
        ;
    }

    $users_online = [];
    foreach ($qb->getQuery()->getArrayResult() as $row) {
        $users_online[] = $row['login_user_id'];
    }

    return $users_online;
}

/**
 * @param int    $uid
 * @param string $time_limit
 */
function who_is_online_in_this_course_count(
    $uid,
    $time_limit,
    $coursecode = null
) {
    if (empty($coursecode)) {
        return false;
    }
    $time_limit = (int) $time_limit;
    $online_time = time() - $time_limit * 60;
    $current_date = api_get_utc_datetime($online_time);
    $courseId = api_get_course_int_id($coursecode);

    if (empty($courseId)) {
        return false;
    }

    $qb = Container::getEntityManager()->createQueryBuilder()
        ->select('COUNT(o.loginUserId)')
        ->from(TrackEOnline::class, 'o')
        ->innerJoin(User::class, 'u', 'WITH', 'o.loginUserId = u.id')
        ->where('u.active <> :softDeleted')
        ->andWhere('u.status <> :anonymous')
        ->andWhere('o.cId = :courseId')
        ->andWhere('o.loginDate >= :currentDate')
        ->setParameter('softDeleted', USER_SOFT_DELETED)
        ->setParameter('anonymous', ANONYMOUS)
        ->setParameter('courseId', (int) $courseId)
        ->setParameter('currentDate', $current_date)
    ;
    if (api_is_multiple_url_enabled()) {
        $qb
            ->innerJoin(AccessUrlRelUser::class, 'a', 'WITH', 'IDENTITY(a.user) = u.id')
            ->andWhere('IDENTITY(a.url) = :urlId')
            ->setParameter('urlId', (int) api_get_current_access_url_id())
        ;
    }

    return (int) $qb->getQuery()->getSingleScalarResult();
}

/**
 * @param string $timeLimit
 * @param int    $sessionId
 *
 * @return bool
 */
function whoIsOnlineInThisSessionCount($timeLimit, $sessionId)
{
    $sessionId = (int) $sessionId;
    if (!$sessionId) {
        return 0;
    }

    $timeLimit = (int) $timeLimit;
    $online_time = time() - $timeLimit * 60;
    $current_date = api_get_utc_datetime($online_time);

    $qb = Container::getEntityManager()->createQueryBuilder()
        ->select('COUNT(o.loginUserId)')
        ->from(TrackEOnline::class, 'o')
        ->innerJoin(User::class, 'u', 'WITH', 'o.loginUserId = u.id')
        ->where('u.active <> :softDeleted')
        ->andWhere('u.status <> :anonymous')
        ->andWhere('o.sessionId = :sessionId')
        ->andWhere('o.loginDate >= :currentDate')
        ->setParameter('softDeleted', USER_SOFT_DELETED)
        ->setParameter('anonymous', ANONYMOUS)
        ->setParameter('sessionId', $sessionId)
        ->setParameter('currentDate', $current_date)
    ;
    if (api_is_multiple_url_enabled()) {
        $qb
            ->innerJoin(AccessUrlRelUser::class, 'a', 'WITH', 'IDENTITY(a.user) = u.id')
            ->andWhere('IDENTITY(a.url) = :urlId')
            ->setParameter('urlId', (int) api_get_current_access_url_id())
        ;
    }

    return (int) $qb->getQuery()->getSingleScalarResult();
}
