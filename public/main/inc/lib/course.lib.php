<?php

/* For licensing terms, see /license.txt*/

use Chamilo\CoreBundle\Entity\AccessUrlRelSession;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\ExtraField as EntityExtraField;
use Chamilo\CoreBundle\Entity\SequenceResource;
use Chamilo\CoreBundle\Entity\Session as SessionEntity;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Repository\SequenceResourceRepository;
use Chamilo\CourseBundle\Component\CourseCopy\CourseBuilder;
use Chamilo\CourseBundle\Component\CourseCopy\CourseRestorer;
use Chamilo\CourseBundle\Entity\CGroup;
use ChamiloSession as Session;
use Doctrine\Common\Collections\Criteria;

/**
 * Class CourseManager.
 *
 * This is the course library for Chamilo.
 *
 * All main course functions should be placed here.
 *
 * There are probably some places left with the wrong code.
 */
class CourseManager
{
    public const MAX_COURSE_LENGTH_CODE = 40;
    /** This constant is used to show separate user names in the course
     * list (userportal), footer, etc */
    public const USER_SEPARATOR = ' |';

    /**
     * Creates a course.
     *
     * @param array $params      Columns in the main.course table.
     * @param int   $authorId    Optional.
     * @param int   $accessUrlId Optional.
     *
     * @return Course|null
     */
    public static function create_course($params, $authorId = 0, $accessUrlId = 0)
    {
        // Check portal limits
        $accessUrlId = !empty($accessUrlId) ? (int) $accessUrlId : api_get_current_access_url_id();
        $authorId = empty($authorId) ? api_get_user_id() : (int) $authorId;

        // @todo Check that this was move inside the CourseListener in a pre persist throw an Exception
        /*if (isset($_configuration[$accessUrlId]) && is_array($_configuration[$accessUrlId])) {
            $return = self::checkCreateCourseAccessUrlParam(
                $_configuration,
                $accessUrlId,
                'hosting_limit_courses',
                'PortalCoursesLimitReached'
            );
            if (false != $return) {
                return $return;
            }
            $return = self::checkCreateCourseAccessUrlParam(
                $_configuration,
                $accessUrlId,
                'hosting_limit_active_courses',
                'PortalActiveCoursesLimitReached'
            );
            if (false != $return) {
                return $return;
            }
        }*/

        if (empty($params['title'])) {
            return false;
        }

        if (empty($params['wanted_code'])) {
            $params['wanted_code'] = $params['title'];
            // Check whether the requested course code has already been occupied.
            $substring = api_substr($params['title'], 0, self::MAX_COURSE_LENGTH_CODE);
            if (false === $substring || empty($substring)) {
                return false;
            } else {
                $params['wanted_code'] = self::generate_course_code($substring);
            }
        }

        // Create the course keys
        $keys = AddCourse::define_course_keys($params['wanted_code']);
        $params['exemplary_content'] = $params['exemplary_content'] ?? false;

        if (count($keys)) {
            $params['code'] = $keys['currentCourseCode'];
            $params['visual_code'] = $keys['currentCourseId'];
            $params['directory'] = $keys['currentCourseRepository'];
            $courseInfo = api_get_course_info($params['code']);
            if (empty($courseInfo)) {
                $course = AddCourse::register_course($params, $accessUrlId);
                if (null !== $course) {
                    self::fillCourse($course, $params, $authorId);

                    return $course;
                }
            }
        }

        return null;
    }

    /**
     * Returns all the information of a given course code.
     *
     * @param string $course_code , the course code
     *
     * @return array with all the fields of the course table
     *
     * @deprecated Use api_get_course_info() instead
     *
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
     * @assert ('') === false
     */
    public static function get_course_information($course_code)
    {
        return Database::fetch_array(
            Database::query(
                "SELECT *, id as real_id FROM ".Database::get_main_table(TABLE_MAIN_COURSE)."
                WHERE code = '".Database::escape_string($course_code)."'"
            ),
            'ASSOC'
        );
    }

    /**
     * Returns a list of courses. Should work with quickform syntax.
     *
     * @param int    $from               Offset (from the 7th = '6'). Optional.
     * @param int    $howmany            Number of results we want. Optional.
     * @param string $orderby            The column we want to order it by. Optional, defaults to first column.
     * @param string $orderdirection     The direction of the order (ASC or DESC). Optional, defaults to ASC.
     * @param int    $visibility         the visibility of the course, or all by default
     * @param string $startwith          If defined, only return results for which the course *title* begins with this
     *                                   string
     * @param string $urlId              The Access URL ID, if using multiple URLs
     * @param bool   $alsoSearchCode     An extension option to indicate that we also want to search for course codes
     *                                   (not *only* titles)
     * @param array  $conditionsLike
     * @param array  $onlyThisCourseList
     *
     * @return array
     */
    public static function get_courses_list(
        $from = 0,
        $howmany = 0,
        $orderby = 'title',
        $orderdirection = 'ASC',
        $visibility = -1,
        $startwith = '',
        $urlId = null,
        $alsoSearchCode = false,
        $conditionsLike = [],
        $onlyThisCourseList = []
    ) {
        $courseTable = Database::get_main_table(TABLE_MAIN_COURSE);
        $sql = "SELECT course.*, course.id as real_id
                FROM $courseTable course  ";

        if (!empty($urlId)) {
            $table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
            $sql .= " INNER JOIN $table url ON (url.c_id = course.id) ";
        }

        $visibility = (int) $visibility;

        if (!empty($startwith)) {
            $sql .= "WHERE (title LIKE '".Database::escape_string($startwith)."%' ";
            if ($alsoSearchCode) {
                $sql .= "OR code LIKE '".Database::escape_string($startwith)."%' ";
            }
            $sql .= ') ';
            if (-1 !== $visibility) {
                $sql .= " AND visibility = $visibility ";
            }
        } else {
            $sql .= 'WHERE 1 ';
            if (-1 !== $visibility) {
                $sql .= " AND visibility = $visibility ";
            }
        }

        if (!empty($urlId)) {
            $urlId = (int) $urlId;
            $sql .= " AND access_url_id = $urlId";
        }

        if (!empty($onlyThisCourseList)) {
            $onlyThisCourseList = array_map('intval', $onlyThisCourseList);
            $onlyThisCourseList = implode("','", $onlyThisCourseList);
            $sql .= " AND course.id IN ('$onlyThisCourseList') ";
        }

        $allowedFields = [
            'title',
            'code',
        ];

        if (count($conditionsLike) > 0) {
            $sql .= ' AND ';
            $temp_conditions = [];
            foreach ($conditionsLike as $field => $value) {
                if (!in_array($field, $allowedFields)) {
                    continue;
                }
                $field = Database::escape_string($field);
                $value = Database::escape_string($value);
                $simple_like = false;
                if ($simple_like) {
                    $temp_conditions[] = $field." LIKE '$value%'";
                } else {
                    $temp_conditions[] = $field.' LIKE \'%'.$value.'%\'';
                }
            }
            $condition = ' AND ';
            if (!empty($temp_conditions)) {
                $sql .= implode(' '.$condition.' ', $temp_conditions);
            }
        }

        if (empty($orderby)) {
            $sql .= ' ORDER BY title ';
        } else {
            if (in_array($orderby, ['title'])) {
                $sql .= " ORDER BY `".Database::escape_string($orderby)."` ";
            } else {
                $sql .= ' ORDER BY title ';
            }
        }

        $orderdirection = strtoupper($orderdirection);
        if (!in_array($orderdirection, ['ASC', 'DESC'])) {
            $sql .= 'ASC';
        } else {
            $sql .= 'ASC' === $orderdirection ? 'ASC' : 'DESC';
        }

        $howmany = (int) $howmany;
        if (!empty($howmany)) {
            $sql .= ' LIMIT '.$howmany;
        } else {
            $sql .= ' LIMIT 1000000'; //virtually no limit
        }
        if (!empty($from)) {
            $from = intval($from);
            $sql .= ' OFFSET '.intval($from);
        } else {
            $sql .= ' OFFSET 0';
        }

        $data = [];
        $res = Database::query($sql);
        if (Database::num_rows($res) > 0) {
            while ($row = Database::fetch_array($res, 'ASSOC')) {
                $data[] = $row;
            }
        }

        return $data;
    }

    /**
     * Returns the status of a user in a course, which is COURSEMANAGER or STUDENT.
     *
     * @param int $userId
     * @param int $courseId
     *
     * @return int|bool the status of the user in that course (or false if the user is not in that course)
     */
    public static function getUserInCourseStatus($userId, $courseId)
    {
        $courseId = (int) $courseId;
        $userId = (int) $userId;
        if (empty($courseId)) {
            return false;
        }

        $result = Database::fetch_array(
            Database::query(
                "SELECT status FROM ".Database::get_main_table(TABLE_MAIN_COURSE_USER)."
                WHERE
                    c_id  = $courseId AND
                    user_id = $userId"
            )
        );

        if (empty($result['status'])) {
            return false;
        }

        return $result['status'];
    }

    /**
     * @param int $userId
     * @param int $courseId
     *
     * @return mixed
     */
    public static function getUserCourseInfo($userId, $courseId)
    {
        $sql = "SELECT * FROM ".Database::get_main_table(TABLE_MAIN_COURSE_USER)."
                WHERE
                    c_id  = ".intval($courseId)." AND
                    user_id = ".intval($userId);

        return Database::fetch_array(Database::query($sql));
    }

    /**
     * @param int  $userId
     * @param int  $courseId
     * @param bool $isTutor
     *
     * @return bool
     */
    public static function updateUserCourseTutor($userId, $courseId, $isTutor)
    {
        $table = Database::escape_string(TABLE_MAIN_COURSE_USER);

        $courseId = (int) $courseId;
        $isTutor = (int) $isTutor;

        $sql = "UPDATE $table SET is_tutor = '".$isTutor."'
			    WHERE
				    user_id = ".$userId." AND
				    c_id = ".$courseId;

        $result = Database::query($sql);

        if (Database::affected_rows($result) > 0) {
            return true;
        }

        return false;
    }

    /**
     * @param int $userId
     * @param int $courseId
     *
     * @return mixed
     */
    public static function get_tutor_in_course_status($userId, $courseId)
    {
        $userId = intval($userId);
        $courseId = intval($courseId);
        $result = Database::fetch_array(
            Database::query(
                "SELECT is_tutor
                FROM ".Database::get_main_table(TABLE_MAIN_COURSE_USER)."
                WHERE
                    c_id = $courseId AND
                    user_id = $userId"
            )
        );

        return $result['is_tutor'];
    }

    /**
     * Unsubscribe one or more users from a course.
     *
     * @param   mixed   user_id or an array with user ids
     * @param   string  course code
     * @param   int     session id
     *
     * @return bool
     *
     * @assert ('', '') === false
     */
    public static function unsubscribe_user($user_id, $course_code, $session_id = 0)
    {
        if (empty($user_id)) {
            return false;
        }
        if (!is_array($user_id)) {
            $user_id = [$user_id];
        }

        if (0 == count($user_id)) {
            return false;
        }

        if (!empty($session_id)) {
            $session_id = (int) $session_id;
        } else {
            $session_id = api_get_session_id();
        }

        if (empty($course_code)) {
            return false;
        }

        $userList = [];
        // Cleaning the $user_id variable
        if (is_array($user_id)) {
            $new_user_id_list = [];
            foreach ($user_id as $my_user_id) {
                $new_user_id_list[] = (int) $my_user_id;
            }
            $new_user_id_list = array_filter($new_user_id_list);
            $userList = $new_user_id_list;
            $user_ids = implode(',', $new_user_id_list);
        } else {
            $user_ids = (int) $user_id;
            $userList[] = $user_id;
        }

        $course_info = api_get_course_info($course_code);
        $course_id = $course_info['real_id'];

        // Unsubscribe user from all groups in the course.
        $sql = "DELETE FROM ".Database::get_course_table(TABLE_GROUP_USER)."
                WHERE c_id = $course_id AND user_id IN (".$user_ids.")";
        Database::query($sql);
        $sql = "DELETE FROM ".Database::get_course_table(TABLE_GROUP_TUTOR)."
                WHERE c_id = $course_id AND user_id IN (".$user_ids.")";
        Database::query($sql);

        // Erase user student publications (works) in the course - by André Boivin
        // @todo this should be handled by doctrine.
        /*if (!empty($userList)) {
            foreach ($userList as $userId) {
                // Getting all work from user
                $workList = getWorkPerUser($userId);
                if (!empty($workList)) {
                    foreach ($workList as $work) {
                        $work = $work['work'];
                        // Getting user results
                        if (!empty($work->user_results)) {
                            foreach ($work->user_results as $workSent) {
                                deleteWorkItem($workSent['id'], $course_info);
                            }
                        }
                    }
                }
            }
        }*/

        // Unsubscribe user from all blogs in the course.
        /*$sql = "DELETE FROM ".Database::get_course_table(TABLE_BLOGS_REL_USER)."
                WHERE c_id = $course_id AND user_id IN ($user_ids)";
        Database::query($sql);

        $sql = "DELETE FROM ".Database::get_course_table(TABLE_BLOGS_TASKS_REL_USER)."
                WHERE c_id = $course_id AND user_id IN ($user_ids)";
        Database::query($sql);*/

        // Deleting users in forum_notification and mailqueue course tables
        $sql = "DELETE FROM  ".Database::get_course_table(TABLE_FORUM_NOTIFICATION)."
                WHERE c_id = $course_id AND user_id IN ($user_ids)";
        Database::query($sql);

        $sql = "DELETE FROM ".Database::get_course_table(TABLE_FORUM_MAIL_QUEUE)."
                WHERE c_id = $course_id AND user_id IN ($user_ids)";
        Database::query($sql);

        // Unsubscribe user from the course.
        if (!empty($session_id)) {
            foreach ($userList as $uid) {
                SessionManager::unSubscribeUserFromCourseSession($uid, $course_id, $session_id);

                // check if a user is register in the session with other course
                $sql = "SELECT user_id FROM ".Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER)."
                        WHERE session_id = $session_id AND user_id = $uid";
                $rs = Database::query($sql);

                if (0 == Database::num_rows($rs)) {
                    SessionManager::unsubscribe_user_from_session($uid, $session_id);
                }
            }

            Event::addEvent(
                LOG_UNSUBSCRIBE_USER_FROM_COURSE,
                LOG_COURSE_CODE,
                $course_code,
                api_get_utc_datetime(),
                $user_id,
                $course_id,
                $session_id
            );
        } else {
            $sql = "DELETE FROM ".Database::get_main_table(TABLE_MAIN_COURSE_USER)."
                    WHERE
                        user_id IN ($user_ids) AND
                        relation_type <> ".COURSE_RELATION_TYPE_RRHH." AND
                        c_id = $course_id";
            Database::query($sql);

            // add event to system log
            $user_id = api_get_user_id();

            Event::addEvent(
                LOG_UNSUBSCRIBE_USER_FROM_COURSE,
                LOG_COURSE_CODE,
                $course_code,
                api_get_utc_datetime(),
                $user_id,
                $course_id
            );

            foreach ($userList as $userId) {
                $userInfo = api_get_user_info($userId);
                Event::addEvent(
                    LOG_UNSUBSCRIBE_USER_FROM_COURSE,
                    LOG_USER_OBJECT,
                    $userInfo,
                    api_get_utc_datetime(),
                    $user_id,
                    $course_id
                );
            }
        }
        if (api_get_configuration_value('catalog_course_subscription_in_user_s_session')) {
            // Also unlink the course from the users' currently accessible sessions
            /** @var Course $course */
            $course = Container::getCourseRepository()->findOneBy([
                'code' => $course_code,
            ]);
            if (is_null($course)) {
                return false;
            }
            /** @var Chamilo\CoreBundle\Entity\User $user */
            foreach (UserManager::getRepository()->matching(
                Criteria::create()->where(Criteria::expr()->in('id', $userList))
            ) as $user) {
                foreach ($user->getCurrentlyAccessibleSessions() as $session) {
                    $session->removeCourse($course);
                    // unsubscribe user from course within this session
                    SessionManager::unSubscribeUserFromCourseSession($user->getId(), $course->getId(), $session->getId());
                }
            }
            try {
                Database::getManager()->flush();
            } catch (\Doctrine\ORM\OptimisticLockException $exception) {
                Display::addFlash(
                    Display::return_message(
                        get_lang('InternalDatabaseError').': '.$exception->getMessage(),
                        'warning'
                    )
                );

                return false;
            }
        }

        return true;
    }

    /**
     * @param string $courseCode
     * @param int    $status
     *
     * @return bool
     */
    public static function autoSubscribeToCourse($courseCode, $status = STUDENT)
    {
        if (api_is_anonymous()) {
            return false;
        }

        $course = Container::getCourseRepository()->findOneBy(['code' => $courseCode]);

        if (null === $course) {
            return false;
        }

        $visibility = (int) $course->getVisibility();

        if (in_array($visibility, [
                Course::CLOSED,
                //Course::REGISTERED,
                Course::HIDDEN,
        ])) {
            Display::addFlash(
                Display::return_message(
                    get_lang('SubscribingNotAllowed'),
                    'warning'
        )
            );

            return false;
        }

        // Private course can allow auto subscription
        if (Course::REGISTERED === $visibility && false === $course->getSubscribe()) {
            Display::addFlash(
                Display::return_message(
                    get_lang('Subscribing not allowed'),
                    'warning'
                )
            );

            return false;
        }

        $userId = api_get_user_id();

        if (api_get_configuration_value('catalog_course_subscription_in_user_s_session')) {
            $user = api_get_user_entity($userId);
            $sessions = $user->getCurrentlyAccessibleSessions();
            if (empty($sessions)) {
                // user has no accessible session
                if ($user->getStudentSessions()) {
                    // user has ancient or future student session(s) but not available now
                    Display::addFlash(
                        Display::return_message(
                            get_lang('CanNotSubscribeToCourseUserSessionExpired'),
                            'warning'
                        )
                    );

                    return false;
                }
                // user has no session at all, create one starting now
                $numberOfDays = api_get_configuration_value('user_s_session_duration') ?: 3 * 365;
                try {
                    $duration = new DateInterval(sprintf('P%dD', $numberOfDays));
                } catch (Exception $exception) {
                    Display::addFlash(
                        Display::return_message(
                            get_lang('WrongNumberOfDays').': '.$numberOfDays.': '.$exception->getMessage(),
                            'warning'
                        )
                    );

                    return false;
                }
                $endDate = new DateTime();
                $endDate->add($duration);
                $session = new SessionEntity();
                $session->setName(
                    sprintf(get_lang('FirstnameLastnameCourses'), $user->getFirstname(), $user->getLastname())
                );
                $session->setAccessEndDate($endDate);
                $session->setCoachAccessEndDate($endDate);
                $session->setDisplayEndDate($endDate);
                $session->setSendSubscriptionNotification(false);
                $adminId = api_get_configuration_value('session_automatic_creation_user_id') ?: 1;
                $session->addSessionAdmin(api_get_user_entity($adminId));
                $session->addUserInSession(0, $user);
                Database::getManager()->persist($session);
                try {
                    Database::getManager()->flush();
                } catch (\Doctrine\ORM\OptimisticLockException $exception) {
                    Display::addFlash(
                        Display::return_message(
                            get_lang('InternalDatabaseError').': '.$exception->getMessage(),
                            'warning'
                        )
                    );

                    return false;
                }
                $accessUrlRelSession = new AccessUrlRelSession();
                $accessUrlRelSession->setUrl(api_get_url_entity());
                $accessUrlRelSession->setSession($session);
                Database::getManager()->persist($accessUrlRelSession);
                try {
                    Database::getManager()->flush();
                } catch (\Doctrine\ORM\OptimisticLockException $exception) {
                    Display::addFlash(
                        Display::return_message(
                            get_lang('InternalDatabaseError').': '.$exception->getMessage(),
                            'warning'
                        )
                    );

                    return false;
                }
            } else {
                // user has at least one accessible session, let's use it
                $session = $sessions[0];
            }
            // add chosen course to the user session
            $session->addCourse($course);
            Database::getManager()->persist($session);
            try {
                Database::getManager()->flush();
            } catch (\Doctrine\ORM\OptimisticLockException $exception) {
                Display::addFlash(
                    Display::return_message(
                        get_lang('InternalDatabaseError').': '.$exception->getMessage(),
                        'warning'
                    )
                );

                return false;
            }
            // subscribe user to course within this session
            SessionManager::subscribe_users_to_session_course([$userId], $session->getId(), $course->getCode());

            return true;
        }

        return self::subscribeUser($userId, $course->getId(), $status, 0);
    }

    /**
     * Subscribe a user to a course. No checks are performed here to see if
     * course subscription is allowed.
     *
     * @param int  $userId
     * @param int  $courseId
     * @param int  $status                 (STUDENT, COURSEMANAGER, COURSE_ADMIN, NORMAL_COURSE_MEMBER)
     * @param int  $sessionId
     * @param int  $userCourseCategoryId
     * @param bool $checkTeacherPermission
     *
     * @return bool True on success, false on failure
     *
     * @assert ('', '') === false
     */
    public static function subscribeUser(
        $userId,
        $courseId,
        $status = STUDENT,
        $sessionId = 0,
        $userCourseCategoryId = 0,
        $checkTeacherPermission = true
    ) {
        $userId = (int) $userId;
        $status = (int) $status;

        if (empty($userId) || empty($courseId)) {
            return false;
        }

        $course = api_get_course_entity($courseId);

        if (null === $course) {
            Display::addFlash(Display::return_message(get_lang('This course doesn\'t exist'), 'warning'));

            return false;
        }

        $user = api_get_user_entity($userId);

        if (null === $user) {
            Display::addFlash(Display::return_message(get_lang('This user doesn\'t exist'), 'warning'));

            return false;
        }

        $courseCode = $course->getCode();
        $userCourseCategoryId = (int) $userCourseCategoryId;
        $sessionId = empty($sessionId) ? api_get_session_id() : (int) $sessionId;
        $status = STUDENT === $status || COURSEMANAGER === $status ? $status : STUDENT;

        if (!empty($sessionId)) {
            SessionManager::subscribe_users_to_session_course(
                [$userId],
                $sessionId,
                $courseCode
            );

            return true;
        } else {
            // Check whether the user has not been already subscribed to the course.
            $sql = "SELECT * FROM ".Database::get_main_table(TABLE_MAIN_COURSE_USER)."
                    WHERE
                        user_id = $userId AND
                        relation_type <> ".COURSE_RELATION_TYPE_RRHH." AND
                        c_id = $courseId
                    ";
            if (Database::num_rows(Database::query($sql)) > 0) {
                Display::addFlash(Display::return_message(get_lang('Already registered in course'), 'warning'));

                return false;
            }

            if ($checkTeacherPermission && !api_is_course_admin()) {
                // Check in advance whether subscription is allowed or not for this course.
                if (SUBSCRIBE_NOT_ALLOWED === (int) $course->getSubscribe()) {
                    Display::addFlash(Display::return_message(get_lang('Subscription not allowed'), 'warning'));

                    return false;
                }
            }

            if (STUDENT === $status) {
                // Check if max students per course extra field is set
                $extraFieldValue = new ExtraFieldValue('course');
                $value = $extraFieldValue->get_values_by_handler_and_field_variable(
                    $courseId,
                    'max_subscribed_students'
                );
                if (!empty($value) && isset($value['value']) && '' !== $value['value']) {
                    $maxStudents = (int) $value['value'];
                    $count = self::get_user_list_from_course_code(
                        $courseCode,
                        0,
                        null,
                        null,
                        STUDENT,
                        true,
                        false
                    );

                    if ($count >= $maxStudents) {
                        Display::addFlash(
                            Display::return_message(
                                get_lang(
                                    'The maximum number of student has already been reached, it is not possible to subscribe more student.'
                                ),
                                'warning'
                            )
                        );

                        return false;
                    }
                }
            }

            $maxSort = api_max_sort_value(0, $userId) + 1;

            $insertId = self::insertUserInCourse(
                $user,
                $course,
                ['status' => $status, 'sort' => $maxSort, 'user_course_cat' => $userCourseCategoryId]
            );

            if ($insertId) {
                Display::addFlash(
                    Display::return_message(
                        sprintf(
                            get_lang('User %s has been registered to course %s'),
                            UserManager::formatUserFullName($user, true),
                            $course->getTitle()
                        )
                    )
                );

                $send = (int) api_get_course_setting('email_alert_to_teacher_on_new_user_in_course', $course);

                if (1 === $send) {
                    self::email_to_tutor(
                        $userId,
                        $courseId,
                        false
                    );
                } elseif (2 === $send) {
                    self::email_to_tutor(
                        $userId,
                        $courseId,
                        true
                    );
                }

                $subscribe = (int) api_get_course_setting('subscribe_users_to_forum_notifications', $course);
                if (1 === $subscribe) {
                    /*$forums = get_forums(0,  true, $sessionId);
                    foreach ($forums as $forum) {
                        set_notification('forum', $forum->getIid(), false, $userInfo, $courseInfo);
                    }*/
                }

                return true;
            }

            return false;
        }
    }

    /**
     * Get the course id based on the original id and field name in the
     * extra fields. Returns 0 if course was not found.
     *
     * @param string $original_course_id_value
     * @param string $original_course_id_name
     *
     * @return int Course id
     *
     * @assert ('', '') === false
     */
    public static function get_course_code_from_original_id(
        $original_course_id_value,
        $original_course_id_name
    ) {
        $t_cfv = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);
        $table_field = Database::get_main_table(TABLE_EXTRA_FIELD);
        $extraFieldType = EntityExtraField::COURSE_FIELD_TYPE;
        $original_course_id_value = Database::escape_string($original_course_id_value);
        $original_course_id_name = Database::escape_string($original_course_id_name);

        $sql = "SELECT item_id
                FROM $table_field cf
                INNER JOIN $t_cfv cfv
                ON cfv.field_id=cf.id
                WHERE
                    variable = '$original_course_id_name' AND
                    value = '$original_course_id_value' AND
                    cf.extra_field_type = $extraFieldType
                ";
        $res = Database::query($sql);
        $row = Database::fetch_object($res);
        if ($row) {
            return $row->item_id;
        } else {
            return 0;
        }
    }

    /**
     * Gets the course code from the course id. Returns null if course id was not found.
     *
     * @param int $id Course id
     *
     * @return string Course code
     * @assert ('') === false
     */
    public static function get_course_code_from_course_id($id)
    {
        $table = Database::get_main_table(TABLE_MAIN_COURSE);
        $id = intval($id);
        $sql = "SELECT code FROM $table WHERE id = $id ";
        $res = Database::query($sql);
        $row = Database::fetch_object($res);
        if ($row) {
            return $row->code;
        } else {
            return null;
        }
    }

    /**
     * Add the user $userId visibility to the course $courseCode in the catalogue.
     *
     * @author David Nos (https://github.com/dnos)
     *
     * @param int    $userId     the id of the user
     * @param string $courseCode the course code
     * @param int    $visible    (optional) The course visibility in the catalogue to the user (1=visible, 0=invisible)
     *
     * @return bool true if added succesfully, false otherwise
     */
    public static function addUserVisibilityToCourseInCatalogue(
        $userId,
        $courseCode,
        $visible = 1
    ) {
        $debug = false;
        $userTable = Database::get_main_table(TABLE_MAIN_USER);
        $courseUserTable = Database::get_main_table(TABLE_MAIN_COURSE_CATALOGUE_USER);
        $visible = (int) $visible;
        if (empty($userId) || empty($courseCode) || ($userId != strval(intval($userId)))) {
            return false;
        }

        $courseCode = Database::escape_string($courseCode);
        $courseInfo = api_get_course_info($courseCode);
        $courseId = $courseInfo['real_id'];

        // Check in advance whether the user has already been registered on the platform.
        $sql = "SELECT status FROM ".$userTable." WHERE user_id = $userId ";
        if (0 == Database::num_rows(Database::query($sql))) {
            return false; // The user has not been registered to the platform.
        }

        // Check whether the user has already been registered to the course visibility in the catalogue.
        $sql = "SELECT * FROM $courseUserTable
                WHERE
                    user_id = $userId AND
                    visible = $visible AND
                    c_id = $courseId";
        if (Database::num_rows(Database::query($sql)) > 0) {
            return true; // The visibility of the user to the course in the catalogue does already exist.
        }

        // Register the user visibility to course in catalogue.
        $params = [
            'user_id' => $userId,
            'c_id' => $courseId,
            'visible' => $visible,
        ];

        return Database::insert($courseUserTable, $params);
    }

    /**
     * Remove the user $userId visibility to the course $courseCode in the catalogue.
     *
     * @author David Nos (https://github.com/dnos)
     *
     * @param int    $userId     the id of the user
     * @param string $courseCode the course code
     * @param int    $visible    (optional) The course visibility in the catalogue to the user (1=visible, 0=invisible)
     *
     * @return bool true if removed succesfully or register not found, false otherwise
     */
    public static function removeUserVisibilityToCourseInCatalogue(
        $userId,
        $courseCode,
        $visible = 1
    ) {
        $courseUserTable = Database::get_main_table(TABLE_MAIN_COURSE_CATALOGUE_USER);

        if (empty($userId) || empty($courseCode) || ($userId != strval(intval($userId)))) {
            return false;
        }

        $courseCode = Database::escape_string($courseCode);
        $courseInfo = api_get_course_info($courseCode);
        $courseId = $courseInfo['real_id'];

        // Check whether the user has already been registered to the course visibility in the catalogue.
        $sql = "SELECT * FROM $courseUserTable
                WHERE
                    user_id = $userId AND
                    visible = $visible AND
                    c_id = $courseId";
        if (Database::num_rows(Database::query($sql)) > 0) {
            $cond = [
                'user_id = ? AND c_id = ? AND visible = ? ' => [
                    $userId,
                    $courseId,
                    $visible,
                ],
            ];

            return Database::delete($courseUserTable, $cond);
        } else {
            return true; // Register does not exist
        }
    }

    /**
     * @param string $code
     *
     * @return bool if there already are one or more courses
     *              with the same code OR visual_code (visualcode), false otherwise
     */
    public static function course_code_exists($code)
    {
        $code = Database::escape_string($code);
        $sql = "SELECT COUNT(*) as number
                FROM ".Database::get_main_table(TABLE_MAIN_COURSE)."
                WHERE code = '$code' OR visual_code = '$code'";
        $result = Database::fetch_array(Database::query($sql));

        return $result['number'] > 0;
    }

    /**
     * @param int    $userId
     * @param string $keyword
     *
     * @return array an array with the course info of all the courses (real and virtual)
     *               of which the current user is course admin
     */
    public static function get_course_list_of_user_as_course_admin($userId, $keyword = '')
    {
        $user = api_get_user_entity($userId);

        if (null === $user) {
            return [];
        }

        $url = api_get_url_entity();
        $user = api_get_user_entity($userId);
        $repo = Container::getUserRepository();

        return $repo->getCourses($user, $url, COURSEMANAGER, $keyword);
    }

    /**
     * @param int   $userId
     * @param array $courseInfo
     *
     * @return bool|null
     */
    public static function isUserSubscribedInCourseAsDrh($userId, $courseInfo)
    {
        $userId = intval($userId);

        if (!api_is_drh()) {
            return false;
        }

        if (empty($courseInfo) || empty($userId)) {
            return false;
        }

        $courseId = intval($courseInfo['real_id']);
        $table = Database::get_main_table(TABLE_MAIN_COURSE_USER);

        $sql = "SELECT * FROM $table
                WHERE
                    user_id = $userId AND
                    relation_type = ".COURSE_RELATION_TYPE_RRHH." AND
                    c_id = $courseId";

        $result = Database::fetch_array(Database::query($sql));

        if (!empty($result)) {
            // The user has been registered in this course.
            return true;
        }
    }

    /**
     * Check if user is subscribed inside a course.
     *
     * @param int    $userId
     * @param string $course_code  , if this parameter is null, it'll check for all courses
     * @param bool   $in_a_session True for checking inside sessions too, by default is not checked
     * @param int    $session_id
     *
     * @return bool $session_id true if the user is registered in the course, false otherwise
     */
    public static function is_user_subscribed_in_course(
        $userId,
        $course_code = null,
        $in_a_session = false,
        $session_id = 0
    ) {
        $userId = (int) $userId;
        $session_id = (int) $session_id;

        if (api_get_configuration_value('catalog_course_subscription_in_user_s_session')) {
            // with this option activated, only check whether the course is in one of the users' sessions
            $course = Container::getCourseRepository()->findOneBy([
                'code' => $course_code,
            ]);
            if (is_null($course)) {
                return false;
            }

            $user = api_get_user_entity($userId);
            if (is_null($user)) {
                return false;
            }
            foreach ($user->getStudentSessions() as $session) {
                if ($session->hasCourse($course)) {
                    return true;
                }
            }

            return false;
        }

        if (empty($session_id)) {
            $session_id = api_get_session_id();
        }

        $condition_course = '';
        if (isset($course_code)) {
            $courseInfo = api_get_course_info($course_code);
            if (empty($courseInfo)) {
                return false;
            }
            $courseId = $courseInfo['real_id'];
            $condition_course = " AND c_id = $courseId";
        }

        $sql = "SELECT * FROM ".Database::get_main_table(TABLE_MAIN_COURSE_USER)."
                WHERE
                    user_id = $userId AND
                    relation_type<>".COURSE_RELATION_TYPE_RRHH."
                    $condition_course ";

        $result = Database::fetch_array(Database::query($sql));

        if (!empty($result)) {
            // The user has been registered in this course.
            return true;
        }

        if (!$in_a_session) {
            // The user has not been registered in this course.
            return false;
        }

        $tableSessionCourseUser = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $sql = "SELECT 1 FROM $tableSessionCourseUser
                WHERE user_id = $userId AND session_id = $session_id $condition_course";

        if (Database::num_rows(Database::query($sql)) > 0) {
            return true;
        }

        $sql = "SELECT 1 FROM $tableSessionCourseUser
                WHERE user_id = $userId AND session_id = $session_id AND status = ".SessionEntity::COURSE_COACH." $condition_course";

        if (Database::num_rows(Database::query($sql)) > 0) {
            return true;
        }

        $sql = "SELECT s.id FROM ".Database::get_main_table(TABLE_MAIN_SESSION)." s
            INNER JOIN ".Database::get_main_table(TABLE_MAIN_SESSION_USER)." sru
                ON (sru.session_id = s.id AND sru.relation_type = ".SessionEntity::SESSION_COACH.")
            WHERE sru.user_id = $userId AND s.id = $session_id";

        if (Database::num_rows(Database::query($sql)) > 0) {
            return true;
        }

        return false;
    }

    /**
     * Is the user a teacher in the given course?
     *
     * @param int $userId
     * @param int $courseId
     *
     * @return bool if the user is a teacher in the course, false otherwise
     */
    public static function isCourseTeacher($userId, $courseId)
    {
        $userId = (int) $userId;
        $courseId = (int) $courseId;

        if (empty($courseId) || empty($userId)) {
            return false;
        }

        $sql = "SELECT status FROM ".Database::get_main_table(TABLE_MAIN_COURSE_USER)."
                WHERE c_id = $courseId AND user_id = $userId ";
        $result = Database::query($sql);

        if (Database::num_rows($result) > 0) {
            return 1 == Database::result($result, 0, 'status');
        }

        return false;
    }

    /**
     * Return user info array of all users registered in a course
     * This only returns the users that are registered in this actual course, not linked courses.
     *
     * @param string    $courseCode
     * @param int       $sessionId
     * @param string    $limit
     * @param string    $order_by         the field to order the users by.
     *                                    Valid values are 'lastname', 'firstname', 'username', 'email',
     *                                    'official_code' OR a part of a SQL statement that starts with ORDER BY ...
     * @param int|null  $filter_by_status if using the session_id: 0 or 2 (student, coach),
     *                                    if using session_id = 0 STUDENT or COURSEMANAGER
     * @param bool|null $return_count
     * @param bool      $add_reports
     * @param bool      $resumed_report
     * @param array     $extra_field
     * @param array     $courseCodeList
     * @param array     $userIdList
     * @param string    $filterByActive
     * @param array     $sessionIdList
     * @param string    $searchByKeyword
     *
     * @return array|int
     */
    public static function get_user_list_from_course_code(
        $courseCode,
        $sessionId = 0,
        $limit = null,
        $order_by = null,
        $filter_by_status = null,
        $return_count = null,
        $add_reports = false,
        $resumed_report = false,
        $extra_field = [],
        $courseCodeList = [],
        $userIdList = [],
        $filterByActive = null,
        $sessionIdList = [],
        $searchByKeyword = '',
        $options = []
    ) {
        $course_table = Database::get_main_table(TABLE_MAIN_COURSE);
        $sessionTable = Database::get_main_table(TABLE_MAIN_SESSION);

        $sessionId = (int) $sessionId;
        $courseCode = Database::escape_string($courseCode);
        $courseInfo = api_get_course_info($courseCode);
        $courseId = 0;
        if (!empty($courseInfo)) {
            $courseId = $courseInfo['real_id'];
        }
        $session = null;
        if (!empty($sessionId)) {
            $session = api_get_session_entity($sessionId);
        }
        $course = api_get_course_entity($courseId);
        $where = [];
        if (empty($order_by)) {
            $order_by = 'user.lastname, user.firstname';
            if (api_is_western_name_order()) {
                $order_by = 'user.firstname, user.lastname';
            }
        }

        // if the $order_by does not contain 'ORDER BY'
        // we have to check if it is a valid field that can be sorted on
        if (!strstr($order_by, 'ORDER BY')) {
            if (!empty($order_by)) {
                $order_by = "ORDER BY $order_by";
            } else {
                $order_by = '';
            }
        }

        $filter_by_status_condition = null;
        $sqlInjectWhere = '';
        $whereExtraField = '';
        $injectExtraFields = ' , ';
        $sqlInjectJoins = '';
        if (!empty($options)) {
            $extraFieldModel = new ExtraField('user');
            $conditions = $extraFieldModel->parseConditions($options, 'user');
            if (!empty($conditions)) {
                $injectExtraFields = $conditions['inject_extra_fields'];

                if (!empty($injectExtraFields)) {
                    $injectExtraFields = ', '.$injectExtraFields;
                } else {
                    $injectExtraFields = ' , ';
                }
                $sqlInjectJoins = $conditions['inject_joins'];
                $whereExtraField = $conditions['where'];
            }
        }

        if (!empty($sessionId) || !empty($sessionIdList)) {
            $sql = 'SELECT DISTINCT
                        user.id as user_id,
                        user.email,
                        session_course_user.status as status_session,
                        session_id,
                        user.*,
                        course.*,
                        course.id AS c_id
                         '.$injectExtraFields.'
                        session.name as session_name
                    ';
            if ($return_count) {
                $sql = ' SELECT COUNT(user.id) as count';
            }

            $sessionCondition = " session_course_user.session_id = $sessionId";
            if (!empty($sessionIdList)) {
                $sessionIdListToString = implode("','", array_map('intval', $sessionIdList));
                $sessionCondition = " session_course_user.session_id IN ('$sessionIdListToString') ";
            }

            $courseCondition = " course.id = $courseId";
            if (!empty($courseCodeList)) {
                $courseCodeListForSession = array_map(['Database', 'escape_string'], $courseCodeList);
                $courseCodeListForSession = implode("','", $courseCodeListForSession);
                $courseCondition = " course.code IN ('$courseCodeListForSession')  ";
            }

            $sql .= ' FROM '.Database::get_main_table(TABLE_MAIN_USER).' as user ';
            $sql .= " LEFT JOIN ".Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER)." as session_course_user
                      ON
                        user.id = session_course_user.user_id AND
                        $sessionCondition
                        INNER JOIN $course_table course
                        ON session_course_user.c_id = course.id AND
                        $courseCondition
                        INNER JOIN $sessionTable session
                        ON session_course_user.session_id = session.id
                    $sqlInjectJoins
                   ";
            $where[] = ' session_course_user.c_id IS NOT NULL ';

            // 2 = coach
            // 0 = student
            if (isset($filter_by_status)) {
                $filter_by_status = (int) $filter_by_status;
                $filter_by_status_condition = " session_course_user.status = $filter_by_status AND ";
            }
        } else {
            if ($return_count) {
                $sql = " SELECT COUNT(*) as count";
            } else {
                if (empty($courseCode)) {
                    $sql = 'SELECT DISTINCT
                                course.title,
                                course.code,
                                course.id AS c_id,
                                course_rel_user.status as status_rel,
                                user.id as user_id,
                                user.email,
                                course_rel_user.is_tutor
                                '.$injectExtraFields.'
                                user.*  ';
                } else {
                    $sql = 'SELECT DISTINCT
                                course_rel_user.status as status_rel,
                                user.id as user_id,
                                user.email,
                                course_rel_user.is_tutor
                                '.$injectExtraFields.'
                                user.*  ';
                }
            }

            $sql .= " FROM ".Database::get_main_table(TABLE_MAIN_USER)." as user
                      LEFT JOIN ".Database::get_main_table(TABLE_MAIN_COURSE_USER)." as course_rel_user
                      ON
                        user.id = course_rel_user.user_id AND
                        course_rel_user.relation_type <> ".COURSE_RELATION_TYPE_RRHH."
                       INNER JOIN $course_table course
                       ON (course_rel_user.c_id = course.id)
                       $sqlInjectJoins
                       ";

            if (!empty($courseId)) {
                $sql .= " AND course_rel_user.c_id = $courseId";
            }
            $where[] = ' course_rel_user.c_id IS NOT NULL ';

            if (isset($filter_by_status) && is_numeric($filter_by_status)) {
                $filter_by_status = (int) $filter_by_status;
                $filter_by_status_condition = " course_rel_user.status = $filter_by_status AND ";
            }
        }

        $multiple_access_url = api_get_multiple_access_url();
        if ($multiple_access_url) {
            $sql .= ' LEFT JOIN '.Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER).' au
                      ON (au.user_id = user.id) ';
        }

        $extraFieldWasAdded = false;
        if ($return_count && $resumed_report) {
            foreach ($extra_field as $extraField) {
                $extraFieldInfo = UserManager::get_extra_field_information_by_name($extraField);
                if (!empty($extraFieldInfo)) {
                    $fieldValuesTable = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);
                    $sql .= " LEFT JOIN $fieldValuesTable as ufv
                            ON (
                                user.id = ufv.item_id AND
                                (field_id = ".$extraFieldInfo['id']." OR field_id IS NULL)
                            )";
                    $extraFieldWasAdded = true;
                }
            }
        }

        $sql .= " WHERE
            $filter_by_status_condition
            ".implode(' OR ', $where);

        if ($multiple_access_url) {
            $current_access_url_id = api_get_current_access_url_id();
            $sql .= " AND (access_url_id =  $current_access_url_id ) ";
        }

        if ($return_count && $resumed_report && $extraFieldWasAdded) {
            $sql .= ' AND field_id IS NOT NULL GROUP BY value ';
        }

        if (!empty($courseCodeList)) {
            $courseCodeList = array_map(['Database', 'escape_string'], $courseCodeList);
            $courseCodeList = implode('","', $courseCodeList);
            if (empty($sessionIdList)) {
                $sql .= ' AND course.code IN ("'.$courseCodeList.'")';
            }
        }

        if (!empty($userIdList)) {
            $userIdList = array_map('intval', $userIdList);
            $userIdList = implode('","', $userIdList);
            $sql .= ' AND user.id IN ("'.$userIdList.'")';
        }

        if (isset($filterByActive)) {
            $filterByActive = (int) $filterByActive;
            $sql .= " AND user.active = $filterByActive";
        }

        if (!empty($searchByKeyword)) {
            $searchByKeyword = Database::escape_string($searchByKeyword);
            $sql .= " AND (
                        user.firstname LIKE '$searchByKeyword' OR
                        user.username LIKE '$searchByKeyword' OR
                        user.lastname LIKE '$searchByKeyword'
                    ) ";
        }

        $sql .= $whereExtraField;
        $sql .= " $order_by $limit";

        $rs = Database::query($sql);
        $users = [];

        $extra_fields = UserManager::get_extra_fields(
            0,
            100,
            null,
            null,
            true,
            true
        );

        $counter = 1;
        $count_rows = Database::num_rows($rs);

        if ($return_count && $resumed_report) {
            return $count_rows;
        }
        $table_user_field_value = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);
        $tableExtraField = Database::get_main_table(TABLE_EXTRA_FIELD);
        if ($count_rows) {
            while ($user = Database::fetch_array($rs)) {
                if ($return_count) {
                    return $user['count'];
                }

                $report_info = [];
                $user_info = $user;
                $user_info['status'] = $user['status'];
                if (isset($user['is_tutor'])) {
                    $user_info['is_tutor'] = $user['is_tutor'];
                }
                if (!empty($sessionId)) {
                    $user_info['status_session'] = $user['status_session'];
                }

                $sessionId = isset($user['session_id']) ? $user['session_id'] : 0;
                $sessionName = isset($user['session_name']) ? ' ('.$user['session_name'].') ' : '';

                if ($add_reports) {
                    if ($resumed_report) {
                        $extra = [];
                        if (!empty($extra_fields)) {
                            foreach ($extra_fields as $extra) {
                                if (in_array($extra['1'], $extra_field)) {
                                    $user_data = UserManager::get_extra_user_data_by_field(
                                        $user['user_id'],
                                        $extra['1']
                                    );
                                    break;
                                }
                            }
                        }

                        $row_key = '-1';
                        $name = '-';
                        if (!empty($extra)) {
                            if (!empty($user_data[$extra['1']])) {
                                $row_key = $user_data[$extra['1']];
                                $name = $user_data[$extra['1']];
                                $users[$row_key]['extra_'.$extra['1']] = $name;
                            }
                        }

                        if (empty($users[$row_key])) {
                            $users[$row_key] = [];
                        }

                        if (!array_key_exists('training_hours', $users[$row_key])) {
                            $users[$row_key]['training_hours'] = 0;
                        }

                        $users[$row_key]['training_hours'] += Tracking::get_time_spent_on_the_course(
                            $user['user_id'],
                            $courseId,
                            $sessionId
                        );

                        if (!array_key_exists('count_users', $users[$row_key])) {
                            $users[$row_key]['count_users'] = 0;
                        }

                        $users[$row_key]['count_users'] += $counter;

                        $registered_users_with_extra_field = self::getCountRegisteredUsersWithCourseExtraField(
                            $name,
                            $tableExtraField,
                            $table_user_field_value
                        );

                        $users[$row_key]['count_users_registered'] = $registered_users_with_extra_field;
                        $users[$row_key]['average_hours_per_user'] = $users[$row_key]['training_hours'] / $users[$row_key]['count_users'];

                        $category = Category:: load(
                            null,
                            null,
                            $courseCode,
                            null,
                            null,
                            $sessionId
                        );

                        if (!isset($users[$row_key]['count_certificates'])) {
                            $users[$row_key]['count_certificates'] = 0;
                        }

                        if (isset($category[0]) && $category[0]->is_certificate_available($user['user_id'])) {
                            $users[$row_key]['count_certificates']++;
                        }

                        foreach ($extra_fields as $extra) {
                            if ('ruc' === $extra['1']) {
                                continue;
                            }

                            if (!isset($users[$row_key][$extra['1']])) {
                                $user_data = UserManager::get_extra_user_data_by_field($user['user_id'], $extra['1']);
                                if (!empty($user_data[$extra['1']])) {
                                    $users[$row_key][$extra['1']] = $user_data[$extra['1']];
                                }
                            }
                        }
                    } else {
                        $report_info['course'] = $user['title'].$sessionName;
                        $report_info['user'] = api_get_person_name($user['firstname'], $user['lastname']);
                        $report_info['email'] = $user['email'];
                        $report_info['time'] = api_time_to_hms(
                            Tracking::get_time_spent_on_the_course(
                                $user['user_id'],
                                empty($user['c_id']) ? $courseId : $user['c_id'],
                                $sessionId
                            )
                        );

                        $category = Category:: load(
                            null,
                            null,
                            $courseCode,
                            null,
                            null,
                            $sessionId
                        );

                        $report_info['certificate'] = Display::label(get_lang('No'));
                        if (isset($category[0]) && $category[0]->is_certificate_available($user['user_id'])) {
                            $report_info['certificate'] = Display::label(get_lang('Yes'), 'success');
                        }

                        $progress = (int) Tracking::get_avg_student_progress(
                            $user['user_id'],
                            $course,
                            [],
                            $session
                        );

                        $report_info['progress_100'] = 100 == $progress ? Display::label(get_lang('Yes'), 'success') : Display::label(get_lang('No'));
                        $report_info['progress'] = $progress."%";
                        foreach ($extra_fields as $extra) {
                            $user_data = UserManager::get_extra_user_data_by_field($user['user_id'], $extra['1']);
                            $report_info[$extra['1']] = $user_data[$extra['1']];
                        }
                        $report_info['user_id'] = $user['user_id'];
                        $users[] = $report_info;
                    }
                } else {
                    $users[$user['user_id']] = $user_info;
                }
            }
        }

        return $users;
    }

    /**
     * @param bool  $resumed_report
     * @param array $extra_field
     * @param array $courseCodeList
     * @param array $userIdList
     * @param array $sessionIdList
     * @param array $options
     *
     * @return array|int
     */
    public static function get_count_user_list_from_course_code(
        $resumed_report = false,
        $extra_field = [],
        $courseCodeList = [],
        $userIdList = [],
        $sessionIdList = [],
        $options = []
    ) {
        return self::get_user_list_from_course_code(
            null,
            0,
            null,
            null,
            null,
            true,
            false,
            $resumed_report,
            $extra_field,
            $courseCodeList,
            $userIdList,
            null,
            $sessionIdList,
            null,
            $options
        );
    }

    /**
     * Gets subscribed users in a course or in a course/session.
     *
     * @param string $course_code
     * @param int    $session_id
     *
     * @return int
     */
    public static function get_users_count_in_course(
        $course_code,
        $session_id = 0,
        $status = null
    ) {
        // variable initialisation
        $session_id = (int) $session_id;
        $course_code = Database::escape_string($course_code);
        $tblUser = Database::get_main_table(TABLE_MAIN_USER);
        $tblSessionCourseUser = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tblCourseUser = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $tblUrlUser = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);

        $courseInfo = api_get_course_info($course_code);
        $courseId = $courseInfo['real_id'];

        $sql = "
            SELECT DISTINCT count(user.id) as count
            FROM $tblUser as user
        ";
        $where = [];
        if (!empty($session_id)) {
            $sql .= "
                LEFT JOIN $tblSessionCourseUser as session_course_user
                ON user.id = session_course_user.user_id
                    AND session_course_user.c_id = $courseId
                    AND session_course_user.session_id = $session_id
            ";

            $where[] = ' session_course_user.c_id IS NOT NULL ';
        } else {
            $sql .= "
                LEFT JOIN $tblCourseUser as course_rel_user
                    ON user.id = course_rel_user.user_id
                    AND course_rel_user.relation_type <> ".COURSE_RELATION_TYPE_RRHH."
                    AND course_rel_user.c_id = $courseId
            ";
            $where[] = ' course_rel_user.c_id IS NOT NULL ';
        }

        $multiple_access_url = api_get_multiple_access_url();
        if ($multiple_access_url) {
            $sql .= " LEFT JOIN $tblUrlUser au ON (au.user_id = user.id) ";
        }

        $sql .= ' WHERE '.implode(' OR ', $where);

        if ($multiple_access_url) {
            $current_access_url_id = api_get_current_access_url_id();
            $sql .= " AND (access_url_id =  $current_access_url_id ) ";
        }
        $rs = Database::query($sql);
        $count = 0;
        if (Database::num_rows($rs)) {
            $user = Database::fetch_array($rs);
            $count = $user['count'];
        }

        return $count;
    }

    /**
     * Get a list of coaches of a course and a session.
     *
     * @param string $course_code
     * @param int    $session_id
     * @param bool   $addGeneralCoach
     *
     * @return array List of users
     */
    public static function get_coach_list_from_course_code(
        $course_code,
        $session_id,
        $addGeneralCoach = true
    ) {
        if (empty($course_code) || empty($session_id)) {
            return [];
        }

        $course_code = Database::escape_string($course_code);
        $courseInfo = api_get_course_info($course_code);
        $courseId = $courseInfo['real_id'];
        $session_id = (int) $session_id;
        $users = [];

        // We get the coach for the given course in a given session.
        $sql = 'SELECT user_id FROM '.Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER).
               " WHERE session_id = $session_id AND c_id = $courseId AND status = ".SessionEntity::COURSE_COACH;
        $rs = Database::query($sql);
        while ($user = Database::fetch_array($rs)) {
            $userInfo = api_get_user_info($user['user_id']);
            if ($userInfo) {
                $users[$user['user_id']] = $userInfo;
            }
        }

        if ($addGeneralCoach) {
            $generalCoachesId = SessionManager::getGeneralCoachesIdForSession($session_id);

            foreach ($generalCoachesId as $session_id_coach) {
                $userInfo = api_get_user_info($session_id_coach);
                if ($userInfo) {
                    $users[$session_id_coach] = $userInfo;
                }
            }
        }

        return $users;
    }

    /**
     *  Return user info array of all users registered in a course
     *  This only returns the users that are registered in this actual course, not linked courses.
     *
     * @param string $course_code
     * @param bool   $with_session
     * @param int    $sessionId
     * @param string $date_from
     * @param string $date_to
     * @param bool   $includeInvitedUsers Whether include the invited users
     * @param int    $groupId
     * @param bool   $getCount
     * @param int    $start
     * @param int    $limit
     *
     * @return array with user id
     */
    public static function get_student_list_from_course_code(
        $course_code,
        $with_session = false,
        $sessionId = 0,
        $date_from = null,
        $date_to = null,
        $includeInvitedUsers = true,
        $groupId = 0,
        $getCount = false,
        $start = 0,
        $limit = 0
    ) {
        $userTable = Database::get_main_table(TABLE_MAIN_USER);
        $sessionId = (int) $sessionId;
        $courseInfo = api_get_course_info($course_code);
        if (empty($courseInfo)) {
            return [];
        }
        $courseId = $courseInfo['real_id'];
        $students = [];

        $limitCondition = '';
        if (isset($start) && isset($limit) && !empty($limit)) {
            $start = (int) $start;
            $limit = (int) $limit;
            $limitCondition = " LIMIT $start, $limit";
        }

        $select = '*';
        if ($getCount) {
            $select = 'count(u.id) as count';
        }

        if (empty($sessionId)) {
            if (empty($groupId)) {
                // students directly subscribed to the course
                $sql = "SELECT $select
                        FROM ".Database::get_main_table(TABLE_MAIN_COURSE_USER)." cu
                        INNER JOIN $userTable u
                        ON cu.user_id = u.id
                        WHERE c_id = $courseId AND cu.status = ".STUDENT;

                if (!$includeInvitedUsers) {
                    $sql .= " AND u.status != ".INVITEE;
                }
                $sql .= $limitCondition;
                $rs = Database::query($sql);

                if ($getCount) {
                    $row = Database::fetch_array($rs);

                    return (int) $row['count'];
                }

                while ($student = Database::fetch_array($rs)) {
                    $students[$student['user_id']] = $student;
                }
            } else {
                $students = GroupManager::get_users(
                    $groupId,
                    false,
                    $start,
                    $limit,
                    $getCount,
                    $courseInfo['real_id']
                );
                $students = array_flip($students);
            }
        }

        // students subscribed to the course through a session
        if ($with_session) {
            $joinSession = '';
            //Session creation date
            if (!empty($date_from) && !empty($date_to)) {
                $joinSession = "INNER JOIN ".Database::get_main_table(TABLE_MAIN_SESSION)." s";
            }

            $sql = "SELECT $select
                      FROM ".Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER)." scu
                      $joinSession
                      INNER JOIN $userTable u
                      ON scu.user_id = u.id
                      WHERE scu.c_id = $courseId AND scu.status = ".SessionEntity::STUDENT;

            if (!empty($date_from) && !empty($date_to)) {
                $date_from = Database::escape_string($date_from);
                $date_to = Database::escape_string($date_to);
                $sql .= " AND s.access_start_date >= '$date_from' AND s.access_end_date <= '$date_to'";
            }

            if (0 != $sessionId) {
                $sql .= " AND scu.session_id = $sessionId";
            }

            if (!$includeInvitedUsers) {
                $sql .= " AND u.status != ".INVITEE;
            }
            $sql .= $limitCondition;

            $rs = Database::query($sql);

            if ($getCount) {
                $row = Database::fetch_array($rs);

                return (int) $row['count'];
            }

            while ($student = Database::fetch_array($rs)) {
                $students[$student['user_id']] = $student;
            }
        }

        return $students;
    }

    /**
     * Return user info array of all teacher-users registered in a course
     * This only returns the users that are registered in this actual course, not linked courses.
     *
     * @param string $course_code
     *
     * @return array with user id
     */
    public static function get_teacher_list_from_course_code($course_code)
    {
        $courseInfo = api_get_course_info($course_code);
        $courseId = $courseInfo['real_id'];
        if (empty($courseId)) {
            return false;
        }

        $sql = "SELECT DISTINCT
                    u.id as user_id,
                    u.lastname,
                    u.firstname,
                    u.email,
                    u.username,
                    u.status
                FROM ".Database::get_main_table(TABLE_MAIN_COURSE_USER)." cu
                INNER JOIN ".Database::get_main_table(TABLE_MAIN_USER)." u
                ON (cu.user_id = u.id)
                WHERE
                    cu.c_id = $courseId AND
                    cu.status = 1 ";
        $rs = Database::query($sql);
        $teachers = [];
        while ($teacher = Database::fetch_array($rs)) {
            $teachers[$teacher['user_id']] = $teacher;
        }

        return $teachers;
    }

    /**
     * Return user info array of all teacher-users registered in a course
     * This only returns the users that are registered in this actual course, not linked courses.
     *
     * @param int  $courseId
     * @param bool $loadAvatars
     *
     * @return array with user id
     */
    public static function getTeachersFromCourse($courseId, $loadAvatars = true)
    {
        $courseId = (int) $courseId;

        if (empty($courseId)) {
            return false;
        }

        $sql = "SELECT DISTINCT
                    u.id as user_id,
                    u.lastname,
                    u.firstname,
                    u.email,
                    u.username,
                    u.status
                FROM ".Database::get_main_table(TABLE_MAIN_COURSE_USER)." cu
                INNER JOIN ".Database::get_main_table(TABLE_MAIN_USER)." u
                ON (cu.user_id = u.id)
                WHERE
                    cu.c_id = $courseId AND
                    cu.status = 1 ";
        $rs = Database::query($sql);
        $listTeachers = [];
        $teachers = [];
        $url = api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php?a=get_user_popup&course_id='.$courseId;
        while ($teacher = Database::fetch_array($rs)) {
            $teachers['id'] = $teacher['user_id'];
            $teachers['lastname'] = $teacher['lastname'];
            $teachers['firstname'] = $teacher['firstname'];
            $teachers['email'] = $teacher['email'];
            $teachers['username'] = $teacher['username'];
            $teachers['status'] = $teacher['status'];
            $teachers['fullname'] = api_get_person_name($teacher['firstname'], $teacher['lastname']);
            $teachers['avatar'] = '';
            /*if ($loadAvatars) {
                $userPicture = UserManager::getUserPicture($teacher['user_id'], USER_IMAGE_SIZE_SMALL);
                $teachers['avatar'] = $userPicture;
            }*/
            $teachers['url'] = $url.'&user_id='.$teacher['user_id'];
            $listTeachers[] = $teachers;
        }

        return $listTeachers;
    }

    /**
     * Returns a string list of teachers assigned to the given course.
     *
     * @param string $course_code
     * @param string $separator           between teachers names
     * @param bool   $add_link_to_profile Whether to add a link to the teacher's profile
     * @param bool   $orderList
     *
     * @return string List of teachers teaching the course
     */
    public static function getTeacherListFromCourseCodeToString(
        $course_code,
        $separator = self::USER_SEPARATOR,
        $add_link_to_profile = false,
        $orderList = false
    ) {
        $teacher_list = self::get_teacher_list_from_course_code($course_code);
        $html = '';
        $list = [];
        if (!empty($teacher_list)) {
            foreach ($teacher_list as $teacher) {
                $teacher_name = api_get_person_name(
                    $teacher['firstname'],
                    $teacher['lastname']
                );
                if ($add_link_to_profile) {
                    $url = api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php?a=get_user_popup&user_id='.$teacher['user_id'];
                    $teacher_name = Display::url(
                        $teacher_name,
                        $url,
                        [
                            'class' => 'ajax',
                            'data-title' => $teacher_name,
                        ]
                    );
                }
                $list[] = $teacher_name;
            }

            if (!empty($list)) {
                if (true === $orderList) {
                    $html .= '<ul class="user-teacher">';
                    foreach ($list as $teacher) {
                        $html .= '<li>';
                        $html .= Display::return_icon('teacher.png', '', null, ICON_SIZE_TINY);
                        $html .= ' '.$teacher;
                        $html .= '</li>';
                    }
                    $html .= '</ul>';
                } else {
                    $html .= array_to_string($list, $separator);
                }
            }
        }

        return $html;
    }

    /**
     * This function returns information about coachs from a course in session.
     *
     * @param int $session_id
     * @param int $courseId
     *
     * @return array containing user_id, lastname, firstname, username
     */
    public static function get_coachs_from_course($session_id = 0, $courseId = 0)
    {
        if (!empty($session_id)) {
            $session_id = intval($session_id);
        } else {
            $session_id = api_get_session_id();
        }

        if (!empty($courseId)) {
            $courseId = intval($courseId);
        } else {
            $courseId = api_get_course_int_id();
        }

        $tbl_user = Database::get_main_table(TABLE_MAIN_USER);
        $tbl_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

        $sql = "SELECT DISTINCT
                    u.id as user_id,
                    u.lastname,
                    u.firstname,
                    u.username
                FROM $tbl_user u
                INNER JOIN $tbl_session_course_user scu
                ON (u.id = scu.user_id)
                WHERE
                    scu.session_id = $session_id AND
                    scu.c_id = $courseId AND
                    scu.status = ".SessionEntity::COURSE_COACH;
        $rs = Database::query($sql);

        $coaches = [];
        if (Database::num_rows($rs) > 0) {
            while ($row = Database::fetch_array($rs)) {
                $completeName = api_get_person_name($row['firstname'], $row['lastname']);
                $coaches[] = $row + ['full_name' => $completeName];
            }
        }

        return $coaches;
    }

    /**
     * @param int    $session_id
     * @param int    $courseId
     * @param string $separator
     * @param bool   $add_link_to_profile
     * @param bool   $orderList
     *
     * @return string
     */
    public static function get_coachs_from_course_to_string(
        $session_id = 0,
        $courseId = 0,
        $separator = self::USER_SEPARATOR,
        $add_link_to_profile = false,
        $orderList = false
    ) {
        $coachList = self::get_coachs_from_course($session_id, $courseId);
        $course_coachs = [];
        if (!empty($coachList)) {
            foreach ($coachList as $coach_course) {
                $coach_name = $coach_course['full_name'];
                if ($add_link_to_profile) {
                    $url = api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php?a=get_user_popup&user_id='.$coach_course['user_id'].'&course_id='.$courseId.'&session_id='.$session_id;
                    $coach_name = Display::url(
                        $coach_name,
                        $url,
                        [
                            'class' => 'ajax',
                            'data-title' => $coach_name,
                        ]
                    );
                }
                $course_coachs[] = $coach_name;
            }
        }

        $html = '';
        if (!empty($course_coachs)) {
            if (true === $orderList) {
                $html .= '<ul class="user-coachs">';
                foreach ($course_coachs as $coachs) {
                    $html .= Display::tag(
                        'li',
                        Display::return_icon(
                            'teacher.png',
                            get_lang('Coach'),
                            null,
                            ICON_SIZE_TINY
                        ).' '.$coachs
                    );
                }
                $html .= '</ul>';
            } else {
                $html = array_to_string($course_coachs, $separator);
            }
        }

        return $html;
    }

    /**
     * Get the list of groups from the course.
     *
     * @param string $course_code
     * @param int    $session_id     Session ID (optional)
     * @param int    $getEmptyGroups get empty groups (optional)
     *
     * @return CGroup[]
     */
    public static function get_group_list_of_course(
        $course_code,
        $session_id = 0,
        $getEmptyGroups = 0
    ) {
        $course_info = api_get_course_info($course_code);

        if (empty($course_info)) {
            return [];
        }
        $course_id = $course_info['real_id'];

        if (empty($course_id)) {
            return [];
        }

        $repo = Container::getGroupRepository();

        $course = api_get_course_entity($course_info['real_id']);
        $session = api_get_session_entity($session_id);
        $qb = $repo->getResourcesByCourse($course, $session);
        $groups = $qb->getQuery()->getResult();
        $groupList = [];
        /** @var CGroup $group */
        foreach ($groups as $group) {
            if (0 === $getEmptyGroups) {
                if (!$group->hasMembers()) {
                    continue;
                }
            }
            $groupList[$group->getIid()] = $group;
        }

        /* 0 != $session_id ? $session_condition = ' WHERE g.session_id IN(1,'.intval($session_id).')' : $session_condition = ' WHERE g.session_id = 0';
         if (0 == $in_get_empty_group) {
             // get only groups that are not empty
             $sql = "SELECT DISTINCT g.iid, g.name
                     FROM ".Database::get_course_table(TABLE_GROUP)." AS g
                     INNER JOIN ".Database::get_course_table(TABLE_GROUP_USER)." gu
                     ON (g.iid = gu.group_id)
                     $session_condition
                     ORDER BY g.name";
         } else {
             // get all groups even if they are empty
             $sql = "SELECT g.iid, g.name
                     FROM ".Database::get_course_table(TABLE_GROUP)." AS g
                     $session_condition
                     AND c_id = $course_id";
         }

         $result = Database::query($sql);
         $groupList = [];
         while ($groupData = Database::fetch_array($result)) {
             $groupData['userNb'] = GroupManager::number_of_students($groupData['iid'], $course_id);
             $groupList[$groupData['iid']] = $groupData;
         }*/

        return $groupList;
    }

    /**
     * Delete a course
     * This function deletes a whole course-area from the platform. When the
     * given course is a virtual course, the database and directory will not be
     * deleted.
     * When the given course is a real course, also all virtual courses refering
     * to the given course will be deleted.
     * Considering the fact that we remove all traces of the course in the main
     * database, it makes sense to remove all tracking as well (if stats databases exist)
     * so that a new course created with this code would not use the remains of an older
     * course.
     *
     * @param string $code The code of the course to delete
     */
    public static function delete_course($code)
    {
        $table_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $table_session_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
        $table_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

        $table_stats_hotpots = Database::get_main_table(TABLE_STATISTIC_TRACK_E_HOTPOTATOES);
        $table_stats_attempt = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
        $table_stats_exercises = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $table_stats_access = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ACCESS);
        $table_stats_lastaccess = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LASTACCESS);
        $table_stats_course_access = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
        $table_stats_online = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ONLINE);
        $table_stats_downloads = Database::get_main_table(TABLE_STATISTIC_TRACK_E_DOWNLOADS);
        $table_stats_links = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LINKS);
        $table_stats_uploads = Database::get_main_table(TABLE_STATISTIC_TRACK_E_UPLOADS);

        if (empty($code)) {
            return false;
        }

        $courseRepo = Container::getCourseRepository();
        /** @var Course $course */
        $course = $courseRepo->findOneBy(['code' => $code]);

        if (null === $course) {
            return false;
        }

        $courseId = $course->getId();

        /** @var SequenceResourceRepository $repo */
        $repo = Database::getManager()->getRepository(SequenceResource::class);
        $sequenceResource = $repo->findRequirementForResource(
            $courseId,
            SequenceResource::COURSE_TYPE
        );

        if ($sequenceResource) {
            Display::addFlash(
                Display::return_message(
                    get_lang('ThereIsASequenceResourceLinkedToThisCourseYouNeedToDeleteItFirst'),
                    'error'
                )
            );

            return false;
        }

        $count = 0;
        if (api_is_multiple_url_enabled()) {
            $url_id = 1;
            if (-1 != api_get_current_access_url_id()) {
                $url_id = api_get_current_access_url_id();
            }
            UrlManager::delete_url_rel_course($courseId, $url_id);
            $count = UrlManager::getCountUrlRelCourse($courseId);
        }

        if (0 === $count) {
            // Cleaning group categories
            $groupCategories = GroupManager::get_categories($course);
            if (!empty($groupCategories)) {
                foreach ($groupCategories as $category) {
                    GroupManager::delete_category($category['iid'], $course->getCode());
                }
            }
            // Cleaning groups
            // @todo should be cleaned by the resource.
            /*$groups = GroupManager::get_groups($courseId);
            if (!empty($groups)) {
                foreach ($groups as $group) {
                    GroupManager::deleteGroup($group, $course['code']);
                }
            }*/

            $course_tables = AddCourse::get_course_tables();
            // Cleaning c_x tables
            if (!empty($courseId)) {
                foreach ($course_tables as $table) {
                    if ('document' === $table) {
                        // Table document will be deleted by Doctrine.
                        continue;
                    }
                    $table = Database::get_course_table($table);
                    //$sql = "DELETE FROM $table WHERE c_id = $courseId ";
                    //Database::query($sql);
                }
            }

            // Unsubscribe all users from the course
            $sql = "DELETE FROM $table_course_user WHERE c_id = $courseId";
            Database::query($sql);
            // Delete the course from the sessions tables
            $sql = "DELETE FROM $table_session_course WHERE c_id = $courseId";
            Database::query($sql);
            $sql = "DELETE FROM $table_session_course_user WHERE c_id = $courseId";
            Database::query($sql);

            // Delete from Course - URL
            // Already deleted because of entities.
            //$sql = "DELETE FROM $table_course_rel_url WHERE c_id = $courseId";
            //Database::query($sql);

            // Delete the course from the stats tables
            $sql = "DELETE FROM $table_stats_hotpots WHERE c_id = $courseId";
            Database::query($sql);
            $sql = "DELETE FROM $table_stats_attempt WHERE c_id = $courseId";
            Database::query($sql);
            $sql = "DELETE FROM $table_stats_exercises WHERE c_id = $courseId";
            Database::query($sql);
            $sql = "DELETE FROM $table_stats_access WHERE c_id = $courseId";
            Database::query($sql);
            $sql = "DELETE FROM $table_stats_lastaccess WHERE c_id = $courseId";
            Database::query($sql);
            $sql = "DELETE FROM $table_stats_course_access WHERE c_id = $courseId";
            Database::query($sql);
            $sql = "DELETE FROM $table_stats_online WHERE c_id = $courseId";
            Database::query($sql);
            // Do not delete rows from track_e_default as these include course
            // creation and other important things that do not take much space
            // but give information on the course history
            //$sql = "DELETE FROM $table_stats_default WHERE c_id = $courseId";
            //Database::query($sql);
            $sql = "DELETE FROM $table_stats_downloads WHERE c_id = $courseId";
            Database::query($sql);
            $sql = "DELETE FROM $table_stats_links WHERE c_id = $courseId";
            Database::query($sql);
            $sql = "DELETE FROM $table_stats_uploads WHERE c_id = $courseId";
            Database::query($sql);

            // Update ticket
            $table = Database::get_main_table(TABLE_TICKET_TICKET);
            $sql = "UPDATE $table SET course_id = NULL WHERE course_id = $courseId";
            Database::query($sql);

            $repo->deleteSequenceResource(
                $courseId,
                SequenceResource::COURSE_TYPE
            );

            // Class
            $table = Database::get_main_table(TABLE_USERGROUP_REL_COURSE);
            $sql = "DELETE FROM $table
                    WHERE course_id = $courseId";
            Database::query($sql);

            // Skills
            $table = Database::get_main_table(TABLE_MAIN_SKILL_REL_USER);
            $argumentation = Database::escape_string(
                sprintf(
                    get_lang('This skill was obtained through course %s which has been removed since then.'),
                    $course->getCode()
                )
            );
            $sql = "UPDATE $table SET course_id = NULL, session_id = NULL, argumentation = '$argumentation'
                    WHERE course_id = $courseId";
            Database::query($sql);

            // Should be deleted by doctrine
            //$sql = "DELETE FROM skill_rel_course WHERE c_id = $courseId";
            //Database::query($sql);

            // Deletes all groups, group-users, group-tutors information
            // To prevent fK mix up on some tables
            //GroupManager::deleteAllGroupsFromCourse($courseId);

            $appPlugin = new AppPlugin();
            $appPlugin->performActionsWhenDeletingItem('course', $courseId);

            //$repo = Container::getQuizRepository();
            //$repo->deleteAllByCourse($courseEntity);

            // Delete the course from the database
            $courseRepo->deleteCourse($course);

            // delete extra course fields
            $extraFieldValues = new ExtraFieldValue('course');
            $extraFieldValues->deleteValuesByItem($courseId);

            // Add event to system log
            Event::addEvent(
                LOG_COURSE_DELETE,
                LOG_COURSE_CODE,
                $code,
                api_get_utc_datetime(),
                api_get_user_id(),
                $courseId
            );

            return true;
        }
    }

    /**
     * Sort courses for a specific user ??
     *
     * @param int    $user_id     User ID
     * @param string $course_code Course code
     *
     * @return int Minimum course order
     *
     * @todo Review documentation
     */
    public static function userCourseSort($user_id, $course_code)
    {
        $user_id = (int) $user_id;

        if (empty($user_id) || empty($course_code)) {
            return 0;
        }

        $course_code = Database::escape_string($course_code);
        $TABLECOURSE = Database::get_main_table(TABLE_MAIN_COURSE);
        $TABLECOURSUSER = Database::get_main_table(TABLE_MAIN_COURSE_USER);

        $course_title = Database::result(
            Database::query(
                "SELECT title FROM $TABLECOURSE WHERE code = '$course_code'"
            ),
            0,
            0
        );
        if (false === $course_title) {
            $course_title = '';
        }

        $sql = "SELECT course.code as code, course.title as title, cu.sort as sort
                FROM $TABLECOURSUSER as cu, $TABLECOURSE as course
                WHERE   course.id = cu.c_id AND user_id = $user_id AND
                        cu.relation_type <> ".COURSE_RELATION_TYPE_RRHH." AND
                        user_course_cat = 0
                ORDER BY cu.sort";
        $result = Database::query($sql);

        $course_title_precedent = '';
        $counter = 0;
        $course_found = false;
        $course_sort = 1;

        if (Database::num_rows($result) > 0) {
            while ($courses = Database::fetch_array($result)) {
                if ('' == $course_title_precedent) {
                    $course_title_precedent = $courses['title'];
                }
                if (api_strcasecmp($course_title_precedent, $course_title) < 0) {
                    $course_found = true;
                    if (!empty($courses['sort'])) {
                        $course_sort = $courses['sort'];
                    }
                    if (0 == $counter) {
                        $sql = "UPDATE $TABLECOURSUSER
                                SET sort = sort+1
                                WHERE
                                    user_id= $user_id AND
                                    relation_type <> ".COURSE_RELATION_TYPE_RRHH."
                                    AND user_course_cat = 0
                                    AND sort > $course_sort";
                        $course_sort++;
                    } else {
                        $sql = "UPDATE $TABLECOURSUSER SET sort = sort+1
                                WHERE
                                    user_id= $user_id AND
                                    relation_type <> ".COURSE_RELATION_TYPE_RRHH." AND
                                    user_course_cat = 0 AND
                                    sort >= $course_sort";
                    }
                    Database::query($sql);
                    break;
                } else {
                    $course_title_precedent = $courses['title'];
                }
                $counter++;
            }

            // We must register the course in the beginning of the list
            if (!$course_found) {
                $course_sort = Database::result(
                    Database::query(
                        'SELECT min(sort) as min_sort FROM '.$TABLECOURSUSER.' WHERE user_id = "'.$user_id.'" AND user_course_cat="0"'
                    ),
                    0,
                    0
                );
                Database::query("UPDATE $TABLECOURSUSER SET sort = sort+1 WHERE user_id = $user_id AND user_course_cat = 0");
            }
        }

        return (int) $course_sort;
    }

    /**
     * check if course exists.
     *
     * @param string $courseCode
     *
     * @return int if exists, false else
     */
    public static function course_exists($courseCode)
    {
        $courseCode = Database::escape_string($courseCode);
        $sql = "SELECT 1 FROM ".Database::get_main_table(TABLE_MAIN_COURSE)."
                WHERE code = '$courseCode'";

        return Database::num_rows(Database::query($sql));
    }

    /**
     * Send an email to tutor after the auth-suscription of a student in your course.
     *
     * @author Carlos Vargas <carlos.vargas@dokeos.com>, Dokeos Latino
     *
     * @param int    $user_id            the id of the user
     * @param string $courseId           the course code
     * @param bool   $send_to_tutor_also
     *
     * @return false|null we return the message that is displayed when the action is successful
     */
    public static function email_to_tutor($user_id, $courseId, $send_to_tutor_also = false)
    {
        $user_id = (int) $user_id;
        $courseId = (int) $courseId;
        $information = api_get_course_info_by_id($courseId);
        $course_code = $information['code'];
        $student = api_get_user_info($user_id);

        $name_course = $information['title'];
        $sql = "SELECT * FROM ".Database::get_main_table(TABLE_MAIN_COURSE_USER)."
                WHERE c_id = $courseId";

        // TODO: Ivan: This is a mistake, please, have a look at it. Intention here is diffcult to be guessed.
        //if ($send_to_tutor_also = true)
        // Proposed change:
        if ($send_to_tutor_also) {
            $sql .= ' AND is_tutor = 1';
        } else {
            $sql .= ' AND status = 1';
        }

        $result = Database::query($sql);
        while ($row = Database::fetch_array($result)) {
            $tutor = api_get_user_info($row['user_id']);
            $emailto = $tutor['email'];
            $emailsubject = get_lang('New user in the course').': '.$name_course;
            $emailbody = get_lang('Dear').': '.api_get_person_name($tutor['firstname'], $tutor['lastname'])."\n";
            $emailbody .= get_lang('MessageNew user in the course').': '.$name_course."\n";
            $emailbody .= get_lang('Username').': '.$student['username']."\n";
            if (api_is_western_name_order()) {
                $emailbody .= get_lang('First name').': '.$student['firstname']."\n";
                $emailbody .= get_lang('Last name').': '.$student['lastname']."\n";
            } else {
                $emailbody .= get_lang('Last name').': '.$student['lastname']."\n";
                $emailbody .= get_lang('First name').': '.$student['firstname']."\n";
            }
            $emailbody .= get_lang('e-mail').': <a href="mailto:'.$student['email'].'">'.$student['email']."</a>\n\n";
            $recipient_name = api_get_person_name(
                $tutor['firstname'],
                $tutor['lastname'],
                null,
                PERSON_NAME_EMAIL_ADDRESS
            );
            $sender_name = api_get_person_name(
                api_get_setting('administratorName'),
                api_get_setting('administratorSurname'),
                null,
                PERSON_NAME_EMAIL_ADDRESS
            );
            $email_admin = api_get_setting('emailAdministrator');

            api_mail_html(
                $recipient_name,
                $emailto,
                $emailsubject,
                $emailbody,
                $sender_name,
                $email_admin
            );
        }
    }

    /**
     * @return array
     */
    public static function get_special_course_list()
    {
        $courseTable = Database::get_main_table(TABLE_MAIN_COURSE);
        $tbl_course_field = Database::get_main_table(TABLE_EXTRA_FIELD);
        $tbl_course_field_value = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);
        $tbl_url_course = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);

        $extraFieldType = EntityExtraField::COURSE_FIELD_TYPE;

        $courseList = [];
        // get course list auto-register

        $sql = "SELECT id FROM $tbl_course_field
                WHERE extra_field_type = $extraFieldType AND
                variable = 'special_course'";
        $result = Database::query($sql);
        $courseList = [];

        if (Database::num_rows($result) > 0) {
            $row = Database::fetch_assoc($result);
            // Get list of special courses (appear to all)
            // Note: The value is better indexed as string, so
            // using '1' instead of integer is more efficient
            $sql = "SELECT DISTINCT(item_id) as cid
                FROM $tbl_course_field_value
                WHERE field_id = ".$row['id']." AND value = '1'";
            $result = Database::query($sql);
            while ($row = Database::fetch_assoc($result)) {
                $courseList[] = $row['cid'];
            }
            if (count($courseList) < 1) {
                return $courseList;
            }
            if (api_get_multiple_access_url()) {
                //we filter the courses by the active URL
                $coursesSelect = '';
                if (1 == count($courseList)) {
                    $coursesSelect = $courseList[0];
                } else {
                    $coursesSelect = implode(',', $courseList);
                }
                $access_url_id = api_get_current_access_url_id();
                if (-1 != $access_url_id) {
                    $sql = "SELECT c_id FROM $tbl_url_course
                            WHERE access_url_id = $access_url_id
                            AND c_id IN ($coursesSelect)";
                    $result = Database::query($sql);
                    while ($row = Database::fetch_assoc($result)) {
                        $courseList[] = $row['c_id'];
                    }
                }
            }
        }

        return $courseList;
    }

    /**
     * Get the course codes that have been restricted in the catalogue, and if byUserId is set
     * then the courses that the user is allowed or not to see in catalogue.
     *
     * @param bool $allowed  Either if the courses have some users that are or are not allowed to see in catalogue
     * @param int  $byUserId if the courses are or are not allowed to see to the user
     *
     * @return array Course codes allowed or not to see in catalogue by some user or the user
     */
    public static function getCatalogCourseList($allowed = true, $byUserId = -1)
    {
        $courseTable = Database::get_main_table(TABLE_MAIN_COURSE);
        $tblCourseRelUserCatalogue = Database::get_main_table(TABLE_MAIN_COURSE_CATALOGUE_USER);
        $visibility = $allowed ? 1 : 0;

        // Restriction by user id
        $currentUserRestriction = '';

        $byUserId = (int) $byUserId;
        if ($byUserId > 0) {
            $currentUserRestriction = " AND tcruc.user_id = $byUserId ";
        }

        //we filter the courses from the URL
        $joinAccessUrl = '';
        $whereAccessUrl = '';
        if (api_get_multiple_access_url()) {
            $accessUrlId = api_get_current_access_url_id();
            if (-1 != $accessUrlId) {
                $tblUrlCourse = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
                $joinAccessUrl = "LEFT JOIN $tblUrlCourse url_rel_course
                                  ON url_rel_course.c_id = c.id ";
                $whereAccessUrl = " AND access_url_id = $accessUrlId ";
            }
        }

        // get course list auto-register
        $sql = "SELECT DISTINCT(c.code)
                FROM $tblCourseRelUserCatalogue tcruc
                INNER JOIN $courseTable c
                ON (c.id = tcruc.c_id) $joinAccessUrl
                WHERE tcruc.visible = $visibility $currentUserRestriction $whereAccessUrl";

        $result = Database::query($sql);
        $courseList = [];

        if (Database::num_rows($result) > 0) {
            while ($resultRow = Database::fetch_array($result)) {
                $courseList[] = $resultRow['code'];
            }
        }

        return $courseList;
    }

    /**
     * Get list of courses for a given user.
     *
     * @param int   $user_id
     * @param bool  $include_sessions                   Whether to include courses from session or not
     * @param bool  $adminGetsAllCourses                If the user is platform admin,
     *                                                  whether he gets all the courses or just his. Note: This does
     *                                                  *not* include all sessions
     * @param bool  $loadSpecialCourses
     * @param array $skipCourseList                     List of course ids to skip
     * @param bool  $useUserLanguageFilterIfAvailable
     * @param bool  $showCoursesSessionWithDifferentKey
     *
     * @return array List of codes and db name
     *
     * @author isaac flores paz
     */
    public static function get_courses_list_by_user_id(
        $user_id,
        $include_sessions = false,
        $adminGetsAllCourses = false,
        $loadSpecialCourses = true,
        $skipCourseList = [],
        $useUserLanguageFilterIfAvailable = true,
        $showCoursesSessionWithDifferentKey = false
    ) {
        $user_id = intval($user_id);
        $urlId = api_get_current_access_url_id();
        $course_list = [];
        $codes = [];

        $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $tbl_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $tableCourseUrl = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
        $tblCourseCategory = Database::get_main_table(TABLE_MAIN_CATEGORY);

        $languageCondition = '';
        $onlyInUserLanguage = api_get_configuration_value('my_courses_show_courses_in_user_language_only');
        if ($useUserLanguageFilterIfAvailable && $onlyInUserLanguage) {
            $userInfo = api_get_user_info(api_get_user_id());
            if (!empty($userInfo['language'])) {
                $languageCondition = " AND course.course_language = '".$userInfo['language']."' ";
            }
        }

        if ($adminGetsAllCourses && UserManager::is_admin($user_id)) {
            // get the whole courses list
            $sql = "SELECT DISTINCT(course.code), course.id as real_id, course.title
                    FROM $tbl_course course
                    INNER JOIN $tableCourseUrl url
                    ON (course.id = url.c_id)
                    WHERE
                        url.access_url_id = $urlId
                        $languageCondition
                ";
        } else {
            $withSpecialCourses = $withoutSpecialCourses = '';

            if ($loadSpecialCourses) {
                $specialCourseList = self::get_special_course_list();
                if (!empty($specialCourseList)) {
                    $specialCourseToString = '"'.implode('","', $specialCourseList).'"';
                    $withSpecialCourses = ' AND course.id IN ('.$specialCourseToString.')';
                    $withoutSpecialCourses = ' AND course.id NOT IN ('.$specialCourseToString.')';
                }

                if (!empty($withSpecialCourses)) {
                    $sql = "SELECT DISTINCT (course.code),
                            course.id as real_id,
                            course.title
                            FROM $tbl_course_user course_rel_user
                            LEFT JOIN $tbl_course course
                            ON course.id = course_rel_user.c_id
                            INNER JOIN $tableCourseUrl url
                            ON (course.id = url.c_id)
                            WHERE url.access_url_id = $urlId
                            $withSpecialCourses
                            $languageCondition
                            GROUP BY course.code
                            ORDER BY course.title, course_rel_user.sort ASC
                    ";
                    $result = Database::query($sql);
                    if (Database::num_rows($result) > 0) {
                        while ($result_row = Database::fetch_array($result, 'ASSOC')) {
                            $result_row['special_course'] = 1;
                            $course_list[] = $result_row;
                            $codes[] = $result_row['real_id'];
                        }
                    }
                }
            }

            // get course list not auto-register. Use Distinct to avoid multiple
            // entries when a course is assigned to a HRD (DRH) as watcher
            $sql = "SELECT
                        DISTINCT(course.code),
                        course.id as real_id,
                        course.title
                    FROM $tbl_course course
                    INNER JOIN $tbl_course_user cru
                    ON (course.id = cru.c_id)
                    INNER JOIN $tableCourseUrl url
                    ON (course.id = url.c_id)
                    WHERE
                        url.access_url_id = $urlId AND
                        cru.user_id = $user_id
                        $withoutSpecialCourses
                        $languageCondition
                    ORDER BY course.title
                    ";
        }
        $result = Database::query($sql);

        if (Database::num_rows($result)) {
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                if (!empty($skipCourseList)) {
                    if (in_array($row['real_id'], $skipCourseList)) {
                        continue;
                    }
                }
                $course_list[] = $row;
                $codes[] = $row['real_id'];
            }
        }

        if (true === $include_sessions) {
            $sql = "SELECT DISTINCT (c.code),
                        c.id as real_id,
                        s.id as session_id,
                        s.name as session_name
                    FROM ".Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER)." scu
                    INNER JOIN $tbl_course c
                    ON (scu.c_id = c.id)
                    INNER JOIN ".Database::get_main_table(TABLE_MAIN_SESSION)." s
                    ON (s.id = scu.session_id)
                    WHERE user_id = $user_id ";
            $r = Database::query($sql);
            while ($row = Database::fetch_array($r, 'ASSOC')) {
                if (!empty($skipCourseList)) {
                    if (in_array($row['real_id'], $skipCourseList)) {
                        continue;
                    }
                }

                if ($showCoursesSessionWithDifferentKey) {
                    $course_list[] = $row;
                } else {
                    if (!in_array($row['real_id'], $codes)) {
                        $course_list[] = $row;
                    }
                }
            }
        }

        return $course_list;
    }

    /**
     * Get course ID from a given course directory name.
     *
     * @param string $path Course directory (without any slash)
     *
     * @return string Course code, or false if not found
     */
    public static function getCourseCodeFromDirectory($path)
    {
        $path = Database::escape_string(str_replace('.', '', str_replace('/', '', $path)));
        $res = Database::query("SELECT code FROM ".Database::get_main_table(TABLE_MAIN_COURSE)."
                WHERE directory LIKE BINARY '$path'");
        if (false === $res) {
            return false;
        }
        if (1 != Database::num_rows($res)) {
            return false;
        }
        $row = Database::fetch_array($res);

        return $row['code'];
    }

    /**
     * Get course code(s) from visual code.
     *
     * @param   string  Visual code
     *
     * @return array List of codes for the given visual code
     */
    public static function get_courses_info_from_visual_code($code)
    {
        $result = [];
        $sql_result = Database::query("SELECT * FROM ".Database::get_main_table(TABLE_MAIN_COURSE)."
                WHERE visual_code = '".Database::escape_string($code)."'");
        while ($virtual_course = Database::fetch_array($sql_result)) {
            $result[] = $virtual_course;
        }

        return $result;
    }

    /**
     * Creates a new extra field for a given course.
     *
     * @param string $variable    Field's internal variable name
     * @param int    $fieldType   Field's type
     * @param string $displayText Field's language var name
     * @param string $default     Optional. The default value
     *
     * @return int New extra field ID
     */
    public static function create_course_extra_field($variable, $fieldType, $displayText, $default = '')
    {
        $extraField = new ExtraField('course');
        $params = [
            'variable' => $variable,
            'field_type' => $fieldType,
            'display_text' => $displayText,
            'default_value' => $default,
        ];

        return $extraField->save($params);
    }

    /**
     * Update course attributes. Will only update attributes with a non-empty value.
     * Note that you NEED to check that your attributes are valid before using this function.
     *
     * @param int Course id
     * @param array Associative array with field names as keys and field values as values
     *
     * @return Doctrine\DBAL\Driver\Statement|null True if update was successful, false otherwise
     */
    public static function update_attributes($id, $attributes)
    {
        $id = (int) $id;
        $table = Database::get_main_table(TABLE_MAIN_COURSE);
        $sql = "UPDATE $table SET ";
        $i = 0;
        foreach ($attributes as $name => $value) {
            if ('' != $value) {
                if ($i > 0) {
                    $sql .= ", ";
                }
                $sql .= " $name = '".Database::escape_string($value)."'";
                $i++;
            }
        }
        $sql .= " WHERE id = $id";

        return Database::query($sql);
    }

    /**
     * Update an extra field value for a given course.
     *
     * @param string $course_code Course code
     * @param string $variable    Field variable name
     * @param string $value       Optional. Default field value
     *
     * @return bool|int An integer when register a new extra field. And boolean when update the extrafield
     */
    public static function update_course_extra_field_value($course_code, $variable, $value = '')
    {
        $courseInfo = api_get_course_info($course_code);
        $courseId = $courseInfo['real_id'];

        $extraFieldValues = new ExtraFieldValue('course');
        $params = [
            'item_id' => $courseId,
            'variable' => $variable,
            'value' => $value,
        ];

        return $extraFieldValues->save($params);
    }

    /**
     * @param int $sessionId
     *
     * @return mixed
     */
    public static function get_session_category_id_by_session_id($sessionId)
    {
        if (empty($sessionId)) {
            return [];
        }
        $sessionId = intval($sessionId);
        $sql = 'SELECT sc.id session_category
                FROM '.Database::get_main_table(TABLE_MAIN_SESSION_CATEGORY).' sc
                INNER JOIN '.Database::get_main_table(TABLE_MAIN_SESSION).' s
                ON sc.id = s.session_category_id
                WHERE s.id = '.$sessionId;

        return Database::result(
            Database::query($sql),
            0,
            'session_category'
        );
    }

    /**
     * Gets the value of a course extra field. Returns null if it was not found.
     *
     * @param string $variable Name of the extra field
     * @param string $code     Course code
     *
     * @return string Value
     */
    public static function get_course_extra_field_value($variable, $code)
    {
        $courseInfo = api_get_course_info($code);
        $courseId = $courseInfo['real_id'];

        $extraFieldValues = new ExtraFieldValue('course');
        $result = $extraFieldValues->get_values_by_handler_and_field_variable($courseId, $variable);
        if (!empty($result['value'])) {
            return $result['value'];
        }

        return null;
    }

    /**
     * Gets extra field value data and formatted values of a course
     * for extra fields listed in configuration.php in my_course_course_extrafields_to_be_presented
     * (array of variables as value of key 'fields').
     *
     * @param $courseId  int The numeric identifier of the course
     *
     * @return array of data and formatted values as returned by ExtraField::getDataAndFormattedValues
     */
    public static function getExtraFieldsToBePresented($courseId)
    {
        $extraFields = [];
        $fields = api_get_configuration_sub_value('my_course_course_extrafields_to_be_presented/fields');
        if (!empty($fields) && is_array($fields)) {
            $extraFieldManager = new ExtraField('course');
            $dataAndFormattedValues = $extraFieldManager->getDataAndFormattedValues($courseId);
            foreach ($fields as $variable) {
                foreach ($dataAndFormattedValues as $value) {
                    if ($value['variable'] === $variable && !empty($value['value'])) {
                        $extraFields[] = $value;
                    }
                }
            }
        }

        return $extraFields;
    }

    /**
     * Lists details of the course description.
     *
     * @param array        The course description
     * @param string    The encoding
     * @param bool        If true is displayed if false is hidden
     *
     * @return string The course description in html
     */
    public static function get_details_course_description_html(
        $descriptions,
        $charset,
        $action_show = true
    ) {
        $data = null;
        if (isset($descriptions) && count($descriptions) > 0) {
            foreach ($descriptions as $description) {
                $data .= '<div class="sectiontitle">';
                if (api_is_allowed_to_edit() && $action_show) {
                    //delete
                    $data .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&action=delete&description_id='.$description->id.'" onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(
                        get_lang('Please confirm your choice'),
                                ENT_QUOTES,
                        $charset
                    )).'\')) return false;">';
                    $data .= Display::return_icon(
                        'delete.gif',
                        get_lang('Delete'),
                        ['style' => 'vertical-align:middle;float:right;']
                    );
                    $data .= '</a> ';
                    //edit
                    $data .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&description_id='.$description->id.'">';
                    $data .= Display::return_icon(
                        'edit.png',
                        get_lang('Edit'),
                        ['style' => 'vertical-align:middle;float:right; padding-right:4px;'],
                        ICON_SIZE_SMALL
                    );
                    $data .= '</a> ';
                }
                $data .= $description->title;
                $data .= '</div>';
                $data .= '<div class="sectioncomment">';
                $data .= Security::remove_XSS($description->content);
                $data .= '</div>';
            }
        } else {
            $data .= '<em>'.get_lang('There is no course description so far.').'</em>';
        }

        return $data;
    }

    /**
     * Returns the details of a course category.
     *
     * @param string $code Category code
     *
     * @return array Course category
     */
    public static function get_course_category($code)
    {
        $table = Database::get_main_table(TABLE_MAIN_CATEGORY);
        $code = Database::escape_string($code);
        $sql = "SELECT * FROM $table WHERE code = '$code'";

        return Database::fetch_array(Database::query($sql));
    }

    /**
     * Subscribes courses to human resource manager (Dashboard feature).
     *
     * @param int   $hr_manager_id Human Resource Manager id
     * @param array $courses_list  Courses code
     *
     * @return int
     */
    public static function subscribeCoursesToDrhManager($hr_manager_id, $courses_list)
    {
        $tbl_course_rel_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $tbl_course_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);

        $hr_manager_id = intval($hr_manager_id);
        $affected_rows = 0;

        //Deleting assigned courses to hrm_id
        if (api_is_multiple_url_enabled()) {
            $sql = "SELECT s.c_id FROM $tbl_course_rel_user s
                    INNER JOIN $tbl_course_rel_access_url a
                    ON (a.c_id = s.c_id)
                    WHERE
                        user_id = $hr_manager_id AND
                        relation_type = ".COURSE_RELATION_TYPE_RRHH." AND
                        access_url_id = ".api_get_current_access_url_id();
        } else {
            $sql = "SELECT c_id FROM $tbl_course_rel_user
                    WHERE user_id = $hr_manager_id AND relation_type = ".COURSE_RELATION_TYPE_RRHH;
        }
        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $sql = "DELETE FROM $tbl_course_rel_user
                        WHERE
                            c_id = {$row['c_id']} AND
                            user_id = $hr_manager_id AND
                            relation_type = ".COURSE_RELATION_TYPE_RRHH;
                Database::query($sql);
            }
        }

        // inserting new courses list
        if (is_array($courses_list)) {
            foreach ($courses_list as $course_code) {
                $courseInfo = api_get_course_info($course_code);
                $courseId = $courseInfo['real_id'];
                $sql = "INSERT IGNORE INTO $tbl_course_rel_user(c_id, user_id, status, relation_type)
                        VALUES($courseId, $hr_manager_id, ".DRH.", ".COURSE_RELATION_TYPE_RRHH.")";
                $result = Database::query($sql);
                if (Database::affected_rows($result)) {
                    $affected_rows++;
                }
            }
        }

        return $affected_rows;
    }

    /**
     * get courses followed by human resources manager.
     *
     * @param int    $user_id
     * @param int    $status
     * @param int    $from
     * @param int    $limit
     * @param string $column
     * @param string $direction
     * @param bool   $getCount
     *
     * @return array courses
     */
    public static function get_courses_followed_by_drh(
        $user_id,
        $status = DRH,
        $from = null,
        $limit = null,
        $column = null,
        $direction = null,
        $getCount = false
    ) {
        return self::getCoursesFollowedByUser(
            $user_id,
            $status,
            $from,
            $limit,
            $column,
            $direction,
            $getCount
        );
    }

    /**
     * get courses followed by user.
     *
     * @param int    $user_id
     * @param int    $status
     * @param int    $from
     * @param int    $limit
     * @param string $column
     * @param string $direction
     * @param bool   $getCount
     * @param string $keyword
     * @param int    $sessionId
     * @param bool   $showAllAssignedCourses
     *
     * @return array courses
     */
    public static function getCoursesFollowedByUser(
        $user_id,
        $status = null,
        $from = null,
        $limit = null,
        $column = null,
        $direction = null,
        $getCount = false,
        $keyword = null,
        $sessionId = 0,
        $showAllAssignedCourses = false
    ) {
        // Database Table Definitions
        $tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $tbl_course_rel_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $tbl_course_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
        $sessionId = (int) $sessionId;
        $user_id = (int) $user_id;
        $select = "SELECT DISTINCT c.*, c.id as real_id ";

        if ($getCount) {
            $select = "SELECT COUNT(DISTINCT c.id) as count";
        }

        $whereConditions = '';
        switch ($status) {
            case COURSEMANAGER:
                $whereConditions .= " AND cru.user_id = $user_id";
                if (!$showAllAssignedCourses) {
                    $whereConditions .= " AND cru.status = ".COURSEMANAGER;
                } else {
                    $whereConditions .= " AND relation_type = ".COURSE_RELATION_TYPE_COURSE_MANAGER;
                }
                break;
            case DRH:
                $whereConditions .= " AND
                    cru.user_id = $user_id AND
                    cru.status = ".DRH." AND
                    relation_type = '".COURSE_RELATION_TYPE_RRHH."'
                ";
                break;
        }

        $keywordCondition = null;
        if (!empty($keyword)) {
            $keyword = Database::escape_string($keyword);
            $keywordCondition = " AND (c.code LIKE '%$keyword%' OR c.title LIKE '%$keyword%' ) ";
        }

        $orderBy = null;
        $extraInnerJoin = null;

        if (!empty($sessionId)) {
            if (COURSEMANAGER == $status) {
                // Teacher of course or teacher inside session
                $whereConditions = " AND (cru.status = ".COURSEMANAGER." OR srcru.status = ".SessionEntity::COURSE_COACH.") ";
            }
            $courseList = SessionManager::get_course_list_by_session_id($sessionId);
            if (!empty($courseList)) {
                $courseListToString = implode("','", array_keys($courseList));
                $whereConditions .= " AND c.id IN ('".$courseListToString."')";
            }
            $tableSessionRelCourse = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
            $tableSessionRelCourseRelUser = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
            $orderBy = ' ORDER BY position';
            $extraInnerJoin = " INNER JOIN $tableSessionRelCourse src
                                ON (c.id = src.c_id AND src.session_id = $sessionId)
                                INNER JOIN $tableSessionRelCourseRelUser srcru
                                ON (src.session_id = srcru.session_id AND srcru.c_id = src.c_id)
                            ";
        }

        $whereConditions .= $keywordCondition;
        $sql = "$select
                FROM $tbl_course c
                INNER JOIN $tbl_course_rel_user cru
                ON (cru.c_id = c.id)
                INNER JOIN $tbl_course_rel_access_url a
                ON (a.c_id = c.id)
                $extraInnerJoin
                WHERE
                    access_url_id = ".api_get_current_access_url_id()."
                    $whereConditions
                $orderBy
                ";
        if (isset($from) && isset($limit)) {
            $from = intval($from);
            $limit = intval($limit);
            $sql .= " LIMIT $from, $limit";
        }

        $result = Database::query($sql);

        if ($getCount) {
            $row = Database::fetch_array($result);

            return $row['count'];
        }

        $courses = [];
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $courses[$row['code']] = $row;
            }
        }

        return $courses;
    }

    /**
     * check if a course is special (autoregister).
     *
     * @param int $courseId
     *
     * @return bool
     */
    public static function isSpecialCourse($courseId)
    {
        $extraFieldValue = new ExtraFieldValue('course');
        $result = $extraFieldValue->get_values_by_handler_and_field_variable(
            $courseId,
            'special_course'
        );

        if (!empty($result)) {
            if (1 == $result['value']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Display special courses (and only these) as several HTML divs of class userportal-course-item.
     *
     * Special courses are courses that stick on top of the list and are "auto-registerable"
     * in the sense that any user clicking them is registered as a student
     *
     * @param int  $user_id                          User id
     * @param bool $load_dirs                        Whether to show the document quick-loader or not
     * @param bool $useUserLanguageFilterIfAvailable
     *
     * @return array
     */
    public static function returnSpecialCourses(
        $user_id,
        $load_dirs = false,
        $useUserLanguageFilterIfAvailable = true
    ) {
        $user_id = (int) $user_id;
        $table = Database::get_main_table(TABLE_MAIN_COURSE);
        $specialCourseList = self::get_special_course_list();

        if (empty($specialCourseList)) {
            return [];
        }

        // Filter by language
        $languageCondition = '';
        $onlyInUserLanguage = api_get_configuration_value('my_courses_show_courses_in_user_language_only');
        if ($useUserLanguageFilterIfAvailable && $onlyInUserLanguage) {
            $userInfo = api_get_user_info(api_get_user_id());
            if (!empty($userInfo['language'])) {
                $languageCondition = " AND course_language = '".$userInfo['language']."' ";
            }
        }

        $sql = "SELECT
                    id,
                    code,
                    subscribe subscr,
                    unsubscribe unsubscr
                FROM $table
                WHERE
                    id IN ('".implode("','", $specialCourseList)."')
                    $languageCondition
                GROUP BY code";

        $rs_special_course = Database::query($sql);
        $number_of_courses = Database::num_rows($rs_special_course);
        $showCustomIcon = api_get_setting('course_images_in_courses_list');

        $courseList = [];
        if ($number_of_courses > 0) {
            while ($course = Database::fetch_array($rs_special_course)) {
                $course_info = api_get_course_info($course['code']);
                $courseId = $course_info['real_id'];
                if (Course::HIDDEN == $course_info['visibility']) {
                    continue;
                }

                $params = [];
                //Param (course_code) needed to get the student info in page "My courses"
                $params['course_code'] = $course['code'];
                $params['code'] = $course['code'];
                // Get notifications.
                $course_info['id_session'] = null;
                $courseUserInfo = self::getUserCourseInfo($user_id, $courseId);

                if (empty($courseUserInfo)) {
                    $course_info['status'] = STUDENT;
                } else {
                    $course_info['status'] = $courseUserInfo['status'];
                }

                $params['edit_actions'] = '';
                $params['document'] = '';
                if (api_is_platform_admin()) {
                    $params['edit_actions'] .= api_get_path(WEB_CODE_PATH).'course_info/infocours.php?cid='.$course['real_id'];
                    if ($load_dirs) {
                        $params['document'] = '<a id="document_preview_'.$courseId.'_0" class="document_preview btn btn-outline-secondary btn-sm" href="javascript:void(0);">'
                           .Display::returnFontAwesomeIcon('folder-open').'</a>';
                        $params['document'] .= Display::div('', ['id' => 'document_result_'.$courseId.'_0', 'class' => 'document_preview_container']);
                    }
                } else {
                    if (Course::CLOSED != $course_info['visibility'] && $load_dirs) {
                        $params['document'] = '<a id="document_preview_'.$courseId.'_0" class="document_preview btn btn-outline-secondary btn-sm" href="javascript:void(0);">'
                           .Display::returnFontAwesomeIcon('folder-open').'</a>';
                        $params['document'] .= Display::div('', ['id' => 'document_result_'.$courseId.'_0', 'class' => 'document_preview_container']);
                    }
                }

                $params['visibility'] = $course_info['visibility'];
                $params['status'] = $course_info['status'];
                $params['category'] = $course_info['categoryName'];
                $params['category_code'] = $course_info['categoryCode'];
                $params['icon'] = Display::return_icon(
                    'drawing-pin.png',
                    null,
                    null,
                    ICON_SIZE_LARGE,
                    null
                );

                if ('true' == api_get_setting('display_coursecode_in_courselist')) {
                    $params['code_course'] = '('.$course_info['visual_code'].')';
                }

                $params['title'] = $course_info['title'];
                $params['title_cut'] = $course_info['title'];
                $params['link'] = $course_info['course_public_url'].'?sid=0&autoreg=1';
                if ('true' === api_get_setting('display_teacher_in_courselist')) {
                    $params['teachers'] = self::getTeachersFromCourse(
                        $courseId,
                        true
                    );
                }

                $params['extrafields'] = CourseManager::getExtraFieldsToBePresented($course_info['real_id']);

                if ('true' === $showCustomIcon) {
                    $params['thumbnails'] = $course_info['course_image'];
                    $params['image'] = $course_info['course_image_large'];
                }

                $params['is_special_course'] = true;
                $courseList[] = $params;
            }
        }

        return $courseList;
    }

    /**
     *  Display courses inside a category (without special courses) as HTML dics of
     *  class userportal-course-item.
     *
     * @param int  $user_category_id                 User category id
     * @param bool $load_dirs                        Whether to show the document quick-loader or not
     * @param int  $user_id
     * @param bool $useUserLanguageFilterIfAvailable
     *
     * @return array
     */
    public static function returnCoursesCategories(
        $user_category_id,
        $load_dirs = false,
        $user_id = 0,
        $useUserLanguageFilterIfAvailable = true
    ) {
        $user_id = $user_id ? (int) $user_id : api_get_user_id();
        $user_category_id = (int) $user_category_id;

        // Table definitions
        $TABLECOURS = Database::get_main_table(TABLE_MAIN_COURSE);
        $TABLECOURSUSER = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $TABLE_ACCESS_URL_REL_COURSE = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
        $current_url_id = api_get_current_access_url_id();

        // Get course list auto-register
        $special_course_list = self::get_special_course_list();
        $without_special_courses = '';
        if (!empty($special_course_list)) {
            $without_special_courses = ' AND course.id NOT IN ("'.implode('","', $special_course_list).'")';
        }

        $userCategoryCondition = " (course_rel_user.user_course_cat = $user_category_id) ";
        if (empty($user_category_id)) {
            $userCategoryCondition = ' (course_rel_user.user_course_cat = 0 OR course_rel_user.user_course_cat IS NULL) ';
        }

        $languageCondition = '';
        $onlyInUserLanguage = api_get_configuration_value('my_courses_show_courses_in_user_language_only');
        if ($useUserLanguageFilterIfAvailable && $onlyInUserLanguage) {
            $userInfo = api_get_user_info(api_get_user_id());
            if (!empty($userInfo['language'])) {
                $languageCondition = " AND course.course_language = '".$userInfo['language']."' ";
            }
        }

        $sql = "SELECT DISTINCT
                    course.id,
                    course_rel_user.status status,
                    course.code as course_code,
                    user_course_cat,
                    course_rel_user.sort
                FROM $TABLECOURS course
                INNER JOIN $TABLECOURSUSER course_rel_user
                ON (course.id = course_rel_user.c_id)
                INNER JOIN $TABLE_ACCESS_URL_REL_COURSE url
                ON (url.c_id = course.id)
                WHERE
                    course_rel_user.user_id = $user_id AND
                    $userCategoryCondition
                    $without_special_courses
                    $languageCondition
                ";
        // If multiple URL access mode is enabled, only fetch courses
        // corresponding to the current URL.
        if (api_get_multiple_access_url() && -1 != $current_url_id) {
            $sql .= " AND access_url_id = $current_url_id";
        }
        // Use user's classification for courses (if any).
        $sql .= ' ORDER BY course_rel_user.user_course_cat, course_rel_user.sort ASC';
        $result = Database::query($sql);

        $showCustomIcon = api_get_setting('course_images_in_courses_list');
        // Browse through all courses.
        $courseAdded = [];
        $courseList = [];

        while ($row = Database::fetch_array($result)) {
            $course_info = api_get_course_info_by_id($row['id']);
            if (empty($course_info)) {
                continue;
            }

            if (isset($course_info['visibility']) &&
                Course::HIDDEN == $course_info['visibility']
            ) {
                continue;
            }

            // Skip if already in list
            if (in_array($course_info['real_id'], $courseAdded)) {
                continue;
            }
            $course_info['id_session'] = null;
            $course_info['status'] = $row['status'];
            $iconName = basename($course_info['course_image']);

            $params = [];
            //Param (course_code) needed to get the student process
            $params['course_code'] = $row['course_code'];
            $params['code'] = $row['course_code'];

            if ('true' === $showCustomIcon && 'course.png' != $iconName) {
                $params['thumbnails'] = $course_info['course_image'];
                $params['image'] = $course_info['course_image_large'];
            }

            $thumbnails = null;
            $image = null;
            if ('true' === $showCustomIcon && 'course.png' != $iconName) {
                $thumbnails = $course_info['course_image'];
                $image = $course_info['course_image_large'];
            } else {
                $image = Display::return_icon(
                    'session_default.png',
                    null,
                    null,
                    null,
                    null,
                    true
                );
            }

            $params['course_id'] = $course_info['real_id'];
            $params['edit_actions'] = '';
            $params['document'] = '';
            if (api_is_platform_admin()) {
                $params['edit_actions'] .= api_get_path(WEB_CODE_PATH).'course_info/infocours.php?cid='.$course_info['real_id'];
                if ($load_dirs) {
                    $params['document'] = '<a id="document_preview_'.$course_info['real_id'].'_0" class="document_preview btn btn-default btn-sm" href="javascript:void(0);">'
                               .Display::returnFontAwesomeIcon('folder-open').'</a>';
                    $params['document'] .= Display::div(
                        '',
                        [
                            'id' => 'document_result_'.$course_info['real_id'].'_0',
                            'class' => 'document_preview_container',
                        ]
                    );
                }
            }
            if ($load_dirs) {
                $params['document'] = '<a id="document_preview_'.$course_info['real_id'].'_0" class="document_preview btn btn-default btn-sm" href="javascript:void(0);">'
                    .Display::returnFontAwesomeIcon('folder-open').'</a>';
                $params['document'] .= Display::div(
                    '',
                    [
                        'id' => 'document_result_'.$course_info['real_id'].'_0',
                        'class' => 'document_preview_container',
                    ]
                );
            }

            $courseUrl = $course_info['course_public_url'].'?sid=0';
            $teachers = [];
            if ('true' === api_get_setting('display_teacher_in_courselist')) {
                $teachers = self::getTeachersFromCourse(
                    $course_info['real_id'],
                    true
                );
            }

            $params['status'] = $row['status'];
            if ('true' === api_get_setting('display_coursecode_in_courselist')) {
                $params['code_course'] = '('.$course_info['visual_code'].') ';
            }

            $params['current_user_is_teacher'] = false;
            /** @var array $teacher */
            foreach ($teachers as $teacher) {
                if ($teacher['id'] != $user_id) {
                    continue;
                }
                $params['current_user_is_teacher'] = true;
            }

            $params['visibility'] = $course_info['visibility'];
            $params['link'] = $courseUrl;
            $params['thumbnails'] = $thumbnails;
            $params['image'] = $image;
            $params['title'] = $course_info['title'];
            $params['title_cut'] = $params['title'];
            $params['category'] = $course_info['categoryName'];
            $params['category_code'] = $course_info['categoryCode'];
            $params['teachers'] = $teachers;
            $params['extrafields'] = CourseManager::getExtraFieldsToBePresented($course_info['real_id']);
            $params['real_id'] = $course_info['real_id'];

            if (api_get_configuration_value('enable_unsubscribe_button_on_my_course_page')
                && '1' === $course_info['unsubscribe']
            ) {
                $params['unregister_button'] = CoursesAndSessionsCatalog::return_unregister_button(
                    $course_info,
                    Security::get_existing_token(),
                    '',
                    ''
                );
            }

            $courseAdded[] = $course_info['real_id'];
            $courseList[] = $params;
        }

        return $courseList;
    }

    /**
     * Get the course id based on the original id and field name in the extra fields.
     * Returns 0 if course was not found.
     *
     * @param string $value    Original course code
     * @param string $variable Original field name
     *
     * @return array
     */
    public static function getCourseInfoFromOriginalId($value, $variable)
    {
        $extraFieldValue = new ExtraFieldValue('course');
        $result = $extraFieldValue->get_item_id_from_field_variable_and_field_value(
            $variable,
            $value
        );

        if (!empty($result)) {
            return api_get_course_info_by_id($result['item_id']);
        }

        return [];
    }

    /**
     * Display code for one specific course a logged in user is subscribed to.
     * Shows a link to the course, what's new icons...
     *
     * $my_course['d'] - course directory
     * $my_course['i'] - course title
     * $my_course['c'] - visual course code
     * $my_course['k']  - system course code
     *
     * @param   array       Course details
     * @param   int     Session ID
     * @param   string      CSS class to apply to course entry
     * @param   bool     Whether the session is supposedly accessible now
     * (not in the case it has passed and is in invisible/unaccessible mode)
     * @param bool      Whether to show the document quick-loader or not
     *
     * @return string The HTML to be printed for the course entry
     *
     * @version 1.0.3
     *
     * @todo refactor into different functions for database calls | logic | display
     * @todo replace single-character $my_course['d'] indices
     * @todo move code for what's new icons to a separate function to clear things up
     * @todo add a parameter user_id so that it is possible to show the
     * courselist of other users (=generalisation).
     * This will prevent having to write a new function for this.
     */
    public static function get_logged_user_course_html(
        $course,
        $session_id = 0,
        $class = 'courses',
        $session_accessible = true,
        $load_dirs = false
    ) {
        $now = date('Y-m-d h:i:s');
        $user_id = api_get_user_id();
        $course_info = api_get_course_info_by_id($course['real_id']);
        $course_visibility = (int) $course_info['visibility'];
        $allowUnsubscribe = api_get_configuration_value('enable_unsubscribe_button_on_my_course_page');

        if (Course::HIDDEN === $course_visibility) {
            return '';
        }

        $sessionInfo = [];
        if (!empty($session_id)) {
            $sessionInfo = api_get_session_info($session_id);
        }
        $userInCourseStatus = self::getUserInCourseStatus($user_id, $course_info['real_id']);
        $course_info['status'] = empty($session_id) ? $userInCourseStatus : STUDENT;
        $course_info['id_session'] = $session_id;

        $is_coach = api_is_coach($session_id, $course_info['real_id']);
        $isAdmin = api_is_platform_admin();

        // Display course entry.
        // Show a hyperlink to the course, unless the course is closed and user is not course admin.
        $session_url = '';
        $params = [];
        $params['icon'] = Display::return_icon(
            'session.png',
            null,
            [],
            ICON_SIZE_LARGE,
            null,
            true
        );
        $params['real_id'] = $course_info['real_id'];
        $params['visibility'] = $course_info['visibility'];

        $sessionCourseAvailable = false;
        if ($session_accessible) {
            if (Course::CLOSED != $course_visibility ||
                COURSEMANAGER == $userInCourseStatus
            ) {
                if (empty($course_info['id_session'])) {
                    $course_info['id_session'] = 0;
                }

                $sessionCourseStatus = api_get_session_visibility($session_id, $course_info['real_id']);

                if (in_array(
                    $sessionCourseStatus,
                    [SESSION_VISIBLE_READ_ONLY, SESSION_VISIBLE, SESSION_AVAILABLE]
                )) {
                    $sessionCourseAvailable = true;
                }

                if (COURSEMANAGER === $userInCourseStatus || $sessionCourseAvailable) {
                    $session_url = $course_info['course_public_url'].'?sid='.$course_info['id_session'];
                    $session_title = '<a title="'.$course_info['name'].'" href="'.$session_url.'">'.
                        $course_info['name'].'</a>';
                } else {
                    $session_title = $course_info['name'];
                }
            } else {
                $session_title =
                    $course_info['name'].' '.
                    Display::tag('span', get_lang('(the course is currently closed)'), ['class' => 'item_closed']);
            }
        } else {
            $session_title = $course_info['name'];
        }

        $thumbnails = null;
        $image = null;
        $showCustomIcon = api_get_setting('course_images_in_courses_list');
        $iconName = basename($course_info['course_image']);

        if ('true' === $showCustomIcon && 'course.png' != $iconName) {
            $thumbnails = $course_info['course_image'];
            $image = $course_info['course_image_large'];
        } else {
            $image = Display::return_icon(
                'session_default.png',
                null,
                null,
                null,
                null,
                true
            );
        }
        $params['thumbnails'] = $thumbnails;
        $params['image'] = $image;
        $params['html_image'] = '';
        if (!empty($thumbnails)) {
            $params['html_image'] = Display::img($thumbnails, $course_info['name'], ['class' => 'img-responsive']);
        } else {
            $params['html_image'] = Display::return_icon(
                'session.png',
                $course_info['name'],
                ['class' => 'img-responsive'],
                ICON_SIZE_LARGE,
                $course_info['name']
            );
        }
        $params['link'] = $session_url;
        $entityManager = Database::getManager();
        /** @var SequenceResourceRepository $repo */
        $repo = $entityManager->getRepository(SequenceResource::class);

        $sequences = $repo->getRequirements($course_info['real_id'], SequenceResource::COURSE_TYPE);
        $sequenceList = $repo->checkRequirementsForUser(
            $sequences,
            SequenceResource::COURSE_TYPE,
            $user_id,
            $session_id
        );
        $completed = $repo->checkSequenceAreCompleted($sequenceList);
        //var_dump($course_info['real_id'], $completed);
        $params['completed'] = $completed;
        $params['requirements'] = '';

        if ($isAdmin ||
            COURSEMANAGER === $userInCourseStatus ||
            $is_coach ||
            SessionManager::sessionHasSessionAdmin($session_id, $user_id)
        ) {
            $params['completed'] = true;
            $params['requirements'] = '';
        } else {
            if ($sequences && false === $completed) {
                $hasRequirements = false;
                foreach ($sequences as $sequence) {
                    if (!empty($sequence['requirements'])) {
                        $hasRequirements = true;
                        break;
                    }
                }
                if ($hasRequirements) {
                    $params['requirements'] = CoursesAndSessionsCatalog::getRequirements(
                    $course_info['real_id'],
                    SequenceResource::COURSE_TYPE,
                    false,
                        false,
                        $session_id
                );
                }
            }
        }

        $params['title'] = $session_title;
        $params['name'] = $course_info['name'];
        $params['edit_actions'] = '';
        $params['document'] = '';
        $params['category'] = $course_info['categoryName'];
        if (Course::CLOSED != $course_visibility &&
            false === $is_coach && $allowUnsubscribe && '1' === $course_info['unsubscribe']) {
            $params['unregister_button'] =
                CoursesAndSessionsCatalog::return_unregister_button(
                    ['code' => $course_info['code']],
                    Security::get_existing_token(),
                    '',
                    '',
                    $session_id
                );
        }

        if (Course::CLOSED != $course_visibility &&
            Course::HIDDEN != $course_visibility
        ) {
            if ($isAdmin) {
                $params['edit_actions'] .= api_get_path(WEB_CODE_PATH).'course_info/infocours.php?cidReq='.$course_info['code'];
                if ($load_dirs) {
                    $params['document'] .= '<a
                        id="document_preview_'.$course_info['real_id'].'_'.$session_id.'"
                        class="document_preview btn btn-default btn-sm"
                        href="javascript:void(0);">'.
                        Display::returnFontAwesomeIcon('folder-open').'</a>';
                    $params['document'] .= Display::div('', [
                        'id' => 'document_result_'.$course_info['real_id'].'_'.$session_id,
                        'class' => 'document_preview_container',
                    ]);
                }
            }
        }
        if ('true' === api_get_setting('display_teacher_in_courselist')) {
            $teacher_list = self::getTeachersFromCourse($course_info['real_id'], true);
            $course_coachs = self::get_coachs_from_course(
                $session_id,
                $course_info['real_id'],
                true
            );
            $params['teachers'] = $teacher_list;

            if ((STUDENT == $course_info['status'] && !empty($session_id)) ||
                ($is_coach && COURSEMANAGER != $course_info['status'])
            ) {
                $params['coaches'] = $course_coachs;
            }
        }
        $special = isset($course['special_course']) ? true : false;
        $params['title'] = $session_title;
        $params['special'] = $special;
        if ('true' === api_get_setting('display_coursecode_in_courselist')) {
            $params['visual_code'] = '('.$course_info['visual_code'].')';
        }
        $params['extra'] = '';
        $html = $params;

        $session_category_id = null;
        $active = false;
        if (!empty($session_id)) {
            $sessionCoachName = implode(
                ' - ',
                SessionManager::getGeneralCoachesNamesForSession($session_id)
            );

            $session_category_id = self::get_session_category_id_by_session_id($course_info['id_session']);

            if (
                '0000-00-00 00:00:00' === $sessionInfo['access_start_date'] ||
                empty($sessionInfo['access_start_date']) ||
                '0000-00-00' === $sessionInfo['access_start_date']
                ) {
                $sessionInfo['dates'] = '';
                if ('true' === api_get_setting('show_session_coach')) {
                    $sessionInfo['coach'] = get_lang('GeneralCoach').': '.$sessionCoachName;
                }
                $active = true;
            } else {
                $sessionInfo['dates'] = ' - '.
                    get_lang('From').' '.$sessionInfo['access_start_date'].' '.
                    get_lang('To').' '.$sessionInfo['access_end_date'];
                if ('true' === api_get_setting('show_session_coach')) {
                    $sessionInfo['coach'] = get_lang('GeneralCoach').': '.$sessionCoachName;
                }
                $date_start = $sessionInfo['access_start_date'];
                $date_end = $sessionInfo['access_end_date'];
                $active = !$date_end ? ($date_start <= $now) : ($date_start <= $now && $date_end >= $now);
            }
        }
        $user_course_category = '';
        if (isset($course_info['user_course_cat'])) {
            $user_course_category = $course_info['user_course_cat'];
        }
        $output = [
                $user_course_category,
                $html,
                $course_info['id_session'],
                $sessionInfo,
                'active' => $active,
                'session_category_id' => $session_category_id,
            ];

        if (SkillModel::isAllowed($user_id, false)) {
            $em = Database::getManager();
            $objUser = api_get_user_entity($user_id);
            $objCourse = api_get_course_entity($course['real_id']);
            $objSession = api_get_session_entity($session_id);
            $skill = $em->getRepository(\Chamilo\CoreBundle\Entity\Skill::class)->getLastByUser($objUser, $objCourse, $objSession);

            $output['skill'] = null;
            if ($skill) {
                $output['skill']['name'] = $skill->getName();
                $output['skill']['icon'] = $skill->getIcon();
            }
        }

        return $output;
    }

    /**
     * @param string $source_course_code
     * @param int    $source_session_id
     * @param string $destination_course_code
     * @param int    $destination_session_id
     * @param array  $params
     *
     * @return bool
     */
    public static function copy_course(
        $source_course_code,
        $source_session_id,
        $destination_course_code,
        $destination_session_id,
        $params = [],
        $withBaseContent = true
    ) {
        $course_info = api_get_course_info($source_course_code);

        if (!empty($course_info)) {
            $cb = new CourseBuilder('', $course_info);
            $course = $cb->build($source_session_id, $source_course_code, $withBaseContent);
            $restorer = new CourseRestorer($course);
            $restorer->skip_content = $params;
            $restorer->restore(
                $destination_course_code,
                $destination_session_id,
                true,
                $withBaseContent
            );

            return true;
        }

        return false;
    }

    /**
     * A simpler version of the copy_course, the function creates an empty course with an autogenerated course code.
     *
     * @param string $new_title new course title
     * @param string source course code
     * @param int source session id
     * @param int destination session id
     * @param array $params
     * @param bool  $copySessionContent
     *
     * @return Course|null
     */
    public static function copy_course_simple(
        $new_title,
        $source_course_code,
        $source_session_id = 0,
        $destination_session_id = 0,
        $params = [],
        $copySessionContent = false
    ) {
        $source_course_info = api_get_course_info($source_course_code);
        if (!empty($source_course_info)) {
            $new_course_code = self::generate_nice_next_course_code($source_course_code);
            if ($new_course_code) {
                $newCourse = self::create_course(
                    $new_title,
                    $new_course_code,
                    false
                );
                if (null !== $newCourse) {
                    $result = self::copy_course(
                        $source_course_code,
                        $source_session_id,
                        $newCourse->getCode(),
                        $destination_session_id,
                        $params,
                        true
                    );
                    if ($result) {
                        return $newCourse;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Creates a new course code based in a given code.
     *
     * @param string    wanted code
     * <code>    $wanted_code = 'curse' if there are in the DB codes like curse1 curse2 the function will return:
     * course3</code> if the course code doest not exist in the DB the same course code will be returned
     *
     * @return string wanted unused code
     */
    public static function generate_nice_next_course_code($wanted_code)
    {
        $course_code_ok = !self::course_code_exists($wanted_code);
        if (!$course_code_ok) {
            $wanted_code = self::generate_course_code($wanted_code);
            $table = Database::get_main_table(TABLE_MAIN_COURSE);
            $wanted_code = Database::escape_string($wanted_code);
            $sql = "SELECT count(id) as count
                    FROM $table
                    WHERE code LIKE '$wanted_code%'";
            $result = Database::query($sql);
            if (Database::num_rows($result) > 0) {
                $row = Database::fetch_array($result);
                $count = $row['count'] + 1;
                $wanted_code = $wanted_code.'_'.$count;
                $result = api_get_course_info($wanted_code);
                if (empty($result)) {
                    return $wanted_code;
                }
            }

            return false;
        }

        return $wanted_code;
    }

    /**
     * Gets the status of the users agreement in a course course-session.
     *
     * @param int    $user_id
     * @param string $course_code
     * @param int    $session_id
     *
     * @return bool
     */
    public static function is_user_accepted_legal($user_id, $course_code, $session_id = 0)
    {
        $user_id = (int) $user_id;
        $session_id = (int) $session_id;
        $course_code = Database::escape_string($course_code);

        $courseInfo = api_get_course_info($course_code);
        $courseId = $courseInfo['real_id'];

        // Course legal
        $enabled = api_get_plugin_setting('courselegal', 'tool_enable');

        if ('true' == $enabled) {
            require_once api_get_path(SYS_PLUGIN_PATH).'courselegal/config.php';
            $plugin = CourseLegalPlugin::create();

            return $plugin->isUserAcceptedLegal($user_id, $course_code, $session_id);
        }

        if (empty($session_id)) {
            $table = Database::get_main_table(TABLE_MAIN_COURSE_USER);
            $sql = "SELECT legal_agreement FROM $table
                    WHERE user_id = $user_id AND c_id = $courseId ";
            $result = Database::query($sql);
            if (Database::num_rows($result) > 0) {
                $result = Database::fetch_array($result);
                if (1 == $result['legal_agreement']) {
                    return true;
                }
            }

            return false;
        } else {
            $table = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
            $sql = "SELECT legal_agreement FROM $table
                    WHERE user_id = $user_id AND c_id = $courseId AND session_id = $session_id";
            $result = Database::query($sql);
            if (Database::num_rows($result) > 0) {
                $result = Database::fetch_array($result);
                if (1 == $result['legal_agreement']) {
                    return true;
                }
            }

            return false;
        }
    }

    /**
     * Saves the user-course legal agreement.
     *
     * @param   int user id
     * @param   string course code
     * @param   int session id
     *
     * @return bool
     */
    public static function save_user_legal($user_id, $courseInfo, $session_id = 0)
    {
        if (empty($courseInfo)) {
            return false;
        }
        $course_code = $courseInfo['code'];

        // Course plugin legal
        $enabled = api_get_plugin_setting('courselegal', 'tool_enable');
        if ('true' == $enabled) {
            require_once api_get_path(SYS_PLUGIN_PATH).'courselegal/config.php';
            $plugin = CourseLegalPlugin::create();

            return $plugin->saveUserLegal($user_id, $course_code, $session_id);
        }

        $user_id = (int) $user_id;
        $session_id = (int) $session_id;
        $courseId = $courseInfo['real_id'];

        if (empty($session_id)) {
            $table = Database::get_main_table(TABLE_MAIN_COURSE_USER);
            $sql = "UPDATE $table SET legal_agreement = '1'
                    WHERE user_id = $user_id AND c_id  = $courseId ";
            Database::query($sql);
        } else {
            $table = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
            $sql = "UPDATE  $table SET legal_agreement = '1'
                    WHERE user_id = $user_id AND c_id = $courseId AND session_id = $session_id";
            Database::query($sql);
        }

        return true;
    }

    /**
     * @param int $user_id
     * @param int $course_id
     * @param int $session_id
     * @param int $url_id
     *
     * @return bool
     */
    public static function get_user_course_vote($user_id, $course_id, $session_id = 0, $url_id = 0)
    {
        $table_user_course_vote = Database::get_main_table(TABLE_MAIN_USER_REL_COURSE_VOTE);
        $session_id = !isset($session_id) ? api_get_session_id() : intval($session_id);
        $url_id = empty($url_id) ? api_get_current_access_url_id() : intval($url_id);
        $user_id = intval($user_id);

        if (empty($user_id)) {
            return false;
        }

        $params = [
            'user_id' => $user_id,
            'c_id' => $course_id,
            'session_id' => $session_id,
            'url_id' => $url_id,
        ];

        $result = Database::select(
            'vote',
            $table_user_course_vote,
            [
                'where' => [
                    'user_id = ? AND c_id = ? AND session_id = ? AND url_id = ?' => $params,
                ],
            ],
            'first'
        );
        if (!empty($result)) {
            return $result['vote'];
        }

        return false;
    }

    /**
     * @param int $course_id
     * @param int $session_id
     * @param int $url_id
     *
     * @return array
     */
    public static function get_course_ranking(
        $course_id,
        $session_id = 0,
        $url_id = 0
    ) {
        $table_course_ranking = Database::get_main_table(TABLE_STATISTIC_TRACK_COURSE_RANKING);

        $session_id = empty($session_id) ? api_get_session_id() : intval($session_id);
        $url_id = empty($url_id) ? api_get_current_access_url_id() : intval($url_id);
        $now = api_get_utc_datetime();

        $params = [
            'c_id' => $course_id,
            'session_id' => $session_id,
            'url_id' => $url_id,
            'creation_date' => $now,
        ];

        $result = Database::select(
            'c_id, accesses, total_score, users',
            $table_course_ranking,
            ['where' => ['c_id = ? AND session_id = ? AND url_id = ?' => $params]],
            'first'
        );

        $point_average_in_percentage = 0;
        $point_average_in_star = 0;
        $users_who_voted = 0;

        if (!empty($result['users'])) {
            $users_who_voted = $result['users'];
            $point_average_in_percentage = round($result['total_score'] / $result['users'] * 100 / 5, 2);
            $point_average_in_star = round($result['total_score'] / $result['users'], 1);
        }

        $result['user_vote'] = false;
        if (!api_is_anonymous()) {
            $result['user_vote'] = self::get_user_course_vote(api_get_user_id(), $course_id, $session_id, $url_id);
        }

        $result['point_average'] = $point_average_in_percentage;
        $result['point_average_star'] = $point_average_in_star;
        $result['users_who_voted'] = $users_who_voted;

        return $result;
    }

    /**
     * Updates the course ranking.
     *
     * @param int   course id
     * @param int $session_id
     * @param int    url id
     * @param $points_to_add
     * @param bool $add_access
     * @param bool $add_user
     *
     * @return array
     */
    public static function update_course_ranking(
        $course_id = 0,
        $session_id = 0,
        $url_id = 0,
        $points_to_add = null,
        $add_access = true,
        $add_user = true
    ) {
        // Course catalog stats modifications see #4191
        $table_course_ranking = Database::get_main_table(TABLE_STATISTIC_TRACK_COURSE_RANKING);
        $now = api_get_utc_datetime();
        $course_id = empty($course_id) ? api_get_course_int_id() : intval($course_id);
        $session_id = empty($session_id) ? api_get_session_id() : intval($session_id);
        $url_id = empty($url_id) ? api_get_current_access_url_id() : intval($url_id);

        $params = [
            'c_id' => $course_id,
            'session_id' => $session_id,
            'url_id' => $url_id,
            'creation_date' => $now,
            'total_score' => 0,
            'users' => 0,
        ];

        $result = Database::select(
            'id, accesses, total_score, users',
            $table_course_ranking,
            ['where' => ['c_id = ? AND session_id = ? AND url_id = ?' => $params]],
            'first'
        );

        // Problem here every time we load the courses/XXXX/index.php course home page we update the access
        if (empty($result)) {
            if ($add_access) {
                $params['accesses'] = 1;
            }
            //The votes and users are empty
            if (isset($points_to_add) && !empty($points_to_add)) {
                $params['total_score'] = intval($points_to_add);
            }
            if ($add_user) {
                $params['users'] = 1;
            }
            $result = Database::insert($table_course_ranking, $params);
        } else {
            $my_params = [];

            if ($add_access) {
                $my_params['accesses'] = intval($result['accesses']) + 1;
            }
            if (isset($points_to_add) && !empty($points_to_add)) {
                $my_params['total_score'] = $result['total_score'] + $points_to_add;
            }
            if ($add_user) {
                $my_params['users'] = $result['users'] + 1;
            }

            if (!empty($my_params)) {
                $result = Database::update(
                    $table_course_ranking,
                    $my_params,
                    ['c_id = ? AND session_id = ? AND url_id = ?' => $params]
                );
            }
        }

        return $result;
    }

    /**
     * Add user vote to a course.
     *
     * @param   int user id
     * @param   int vote [1..5]
     * @param   int course id
     * @param   int session id
     * @param   int url id (access_url_id)
     *
     * @return false|string 'added', 'updated' or 'nothing'
     */
    public static function add_course_vote(
        $user_id,
        $vote,
        $course_id,
        $session_id = 0,
        $url_id = 0
    ) {
        $table_user_course_vote = Database::get_main_table(TABLE_MAIN_USER_REL_COURSE_VOTE);
        $course_id = empty($course_id) ? api_get_course_int_id() : intval($course_id);

        if (empty($course_id) || empty($user_id)) {
            return false;
        }

        if (!in_array($vote, [1, 2, 3, 4, 5])) {
            return false;
        }

        $session_id = empty($session_id) ? api_get_session_id() : intval($session_id);
        $url_id = empty($url_id) ? api_get_current_access_url_id() : intval($url_id);
        $vote = intval($vote);

        $params = [
            'user_id' => intval($user_id),
            'c_id' => $course_id,
            'session_id' => $session_id,
            'url_id' => $url_id,
            'vote' => $vote,
        ];

        $action_done = 'nothing';
        $result = Database::select(
            'id, vote',
            $table_user_course_vote,
            ['where' => ['user_id = ? AND c_id = ? AND session_id = ? AND url_id = ?' => $params]],
            'first'
        );

        if (empty($result)) {
            Database::insert($table_user_course_vote, $params);
            $points_to_add = $vote;
            $add_user = true;
            $action_done = 'added';
        } else {
            $my_params = ['vote' => $vote];
            $points_to_add = $vote - $result['vote'];
            $add_user = false;

            Database::update(
                $table_user_course_vote,
                $my_params,
                ['user_id = ? AND c_id = ? AND session_id = ? AND url_id = ?' => $params]
            );
            $action_done = 'updated';
        }

        // Current points
        if (!empty($points_to_add)) {
            self::update_course_ranking(
                $course_id,
                $session_id,
                $url_id,
                $points_to_add,
                false,
                $add_user
            );
        }

        return $action_done;
    }

    /**
     * Remove course ranking + user votes.
     *
     * @param int $course_id
     * @param int $session_id
     * @param int $url_id
     */
    public static function remove_course_ranking($course_id, $session_id, $url_id = null)
    {
        $table_course_ranking = Database::get_main_table(TABLE_STATISTIC_TRACK_COURSE_RANKING);
        $table_user_course_vote = Database::get_main_table(TABLE_MAIN_USER_REL_COURSE_VOTE);

        if (!empty($course_id) && isset($session_id)) {
            $url_id = empty($url_id) ? api_get_current_access_url_id() : intval($url_id);
            $params = [
                'c_id' => $course_id,
                'session_id' => $session_id,
                'url_id' => $url_id,
            ];
            Database::delete($table_course_ranking, ['c_id = ? AND session_id = ? AND url_id = ?' => $params]);
            Database::delete($table_user_course_vote, ['c_id = ? AND session_id = ? AND url_id = ?' => $params]);
        }
    }

    /**
     * Returns an array with the hottest courses.
     *
     * @param int $days  number of days
     * @param int $limit number of hottest courses
     *
     * @return array
     */
    public static function return_hot_courses($days = 30, $limit = 6)
    {
        if (api_is_invitee()) {
            return [];
        }

        $limit = (int) $limit;
        $userId = api_get_user_id();

        // Getting my courses
        $my_course_list = self::get_courses_list_by_user_id($userId);

        $codeList = [];
        foreach ($my_course_list as $course) {
            $codeList[$course['real_id']] = $course['real_id'];
        }

        if (api_is_drh()) {
            $courses = self::get_courses_followed_by_drh($userId);
            foreach ($courses as $course) {
                $codeList[$course['real_id']] = $course['real_id'];
            }
        }

        $table_course_access = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
        $table_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $table_course_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
        $urlId = api_get_current_access_url_id();
        //$table_course_access table uses the now() and interval ...
        $now = api_get_utc_datetime();
        $sql = "SELECT COUNT(course_access_id) course_count, a.c_id, visibility
                FROM $table_course c
                INNER JOIN $table_course_access a
                ON (c.id = a.c_id)
                INNER JOIN $table_course_url u
                ON u.c_id = c.id
                WHERE
                    u.access_url_id = $urlId AND
                    login_course_date <= '$now' AND
                    login_course_date > DATE_SUB('$now', INTERVAL $days DAY) AND
                    visibility <> ".Course::CLOSED." AND
                    visibility <> ".Course::HIDDEN."
                GROUP BY a.c_id
                ORDER BY course_count DESC
                LIMIT $limit
            ";

        $result = Database::query($sql);
        $courses = [];
        if (Database::num_rows($result)) {
            $courses = Database::store_result($result, 'ASSOC');
            $courses = self::processHotCourseItem($courses, $codeList);
        }

        return $courses;
    }

    /**
     * Returns an array with the "hand picked" popular courses.
     * Courses only appear in this list if their extra field 'popular_courses'
     * has been selected in the admin page of the course.
     *
     * @return array
     */
    public static function returnPopularCoursesHandPicked()
    {
        if (api_is_invitee()) {
            return [];
        }

        $userId = api_get_user_id();

        // Getting my courses
        $my_course_list = self::get_courses_list_by_user_id($userId);

        $codeList = [];
        foreach ($my_course_list as $course) {
            $codeList[$course['real_id']] = $course['real_id'];
        }

        if (api_is_drh()) {
            $courses = self::get_courses_followed_by_drh($userId);
            foreach ($courses as $course) {
                $codeList[$course['real_id']] = $course['real_id'];
            }
        }

        $courseTable = Database::get_main_table(TABLE_MAIN_COURSE);
        $tbl_course_field = Database::get_main_table(TABLE_EXTRA_FIELD);
        $tbl_course_field_value = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);

        //we filter the courses from the URL
        $join_access_url = $where_access_url = '';
        if (api_get_multiple_access_url()) {
            $access_url_id = api_get_current_access_url_id();
            if (-1 != $access_url_id) {
                $tbl_url_course = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
                $join_access_url = "LEFT JOIN $tbl_url_course url_rel_course
                ON url_rel_course.c_id = tcfv.item_id ";
                $where_access_url = " AND access_url_id = $access_url_id ";
            }
        }

        $extraFieldType = EntityExtraField::COURSE_FIELD_TYPE;

        // get course list auto-register
        $sql = "SELECT DISTINCT(c.id) AS c_id
                FROM $tbl_course_field_value tcfv
                INNER JOIN $tbl_course_field tcf
                ON tcfv.field_id =  tcf.id $join_access_url
                INNER JOIN $courseTable c
                ON (c.id = tcfv.item_id)
                WHERE
                    tcf.extra_field_type = $extraFieldType AND
                    tcf.variable = 'popular_courses' AND
                    tcfv.value = 1 AND
                    visibility <> ".Course::CLOSED." AND
                    visibility <> ".Course::HIDDEN." $where_access_url";

        $result = Database::query($sql);
        $courses = [];
        if (Database::num_rows($result)) {
            $courses = Database::store_result($result, 'ASSOC');
            $courses = self::processHotCourseItem($courses, $codeList);
        }

        return $courses;
    }

    /**
     * @param array $courses
     * @param array $codeList
     *
     * @return mixed
     */
    public static function processHotCourseItem($courses, $codeList = [])
    {
        $hotCourses = [];
        $ajax_url = api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=add_course_vote';
        $stok = Security::get_existing_token();
        $user_id = api_get_user_id();

        foreach ($courses as $courseId) {
            $course_info = api_get_course_info_by_id($courseId['c_id']);
            $courseCode = $course_info['code'];
            $categoryCode = !empty($course_info['categoryCode']) ? $course_info['categoryCode'] : "";
            $my_course = $course_info;
            $my_course['go_to_course_button'] = '';
            $my_course['register_button'] = '';

            $access_link = self::get_access_link_by_user(
                $user_id,
                $course_info,
                $codeList
            );

            $userRegisteredInCourse = self::is_user_subscribed_in_course($user_id, $course_info['code']);
            $userRegisteredInCourseAsTeacher = self::isCourseTeacher($user_id, $courseId['c_id']);
            $userRegistered = $userRegisteredInCourse && $userRegisteredInCourseAsTeacher;
            $my_course['is_course_student'] = $userRegisteredInCourse;
            $my_course['is_course_teacher'] = $userRegisteredInCourseAsTeacher;
            $my_course['is_registered'] = $userRegistered;
            $my_course['title_cut'] = cut($course_info['title'], 45);

            // Course visibility
            if ($access_link && in_array('register', $access_link)) {
                $my_course['register_button'] = Display::url(
                    get_lang('Subscribe').' '.
                    Display::returnFontAwesomeIcon('sign-in'),
                    api_get_path(WEB_COURSE_PATH).$course_info['path'].
                     '/index.php?action=subscribe&sec_token='.$stok,
                    [
                        'class' => 'btn btn-success btn-sm',
                        'title' => get_lang('Subscribe'),
                        'aria-label' => get_lang('Subscribe'),
                    ]
                );
            }

            if ($access_link && in_array('enter', $access_link) ||
                Course::OPEN_WORLD == $course_info['visibility']
            ) {
                $my_course['go_to_course_button'] = Display::url(
                    get_lang('Go to the course').' '.
                    Display::returnFontAwesomeIcon('share'),
                    api_get_path(WEB_COURSE_PATH).$course_info['path'].'/index.php',
                    [
                        'class' => 'btn btn-default btn-sm',
                        'title' => get_lang('Go to the course'),
                        'aria-label' => get_lang('Go to the course'),
                    ]
                );
            }

            if ($access_link && in_array('unsubscribe', $access_link)) {
                $my_course['unsubscribe_button'] = Display::url(
                    get_lang('Unsubscribe').' '.
                    Display::returnFontAwesomeIcon('sign-out'),
                    api_get_path(WEB_CODE_PATH).'auth/courses.php?action=unsubscribe&unsubscribe='.$courseCode
                    .'&sec_token='.$stok.'&category_code='.$categoryCode,
                    [
                        'class' => 'btn btn-danger btn-sm',
                        'title' => get_lang('Unsubscribe'),
                        'aria-label' => get_lang('Unsubscribe'),
                    ]
                );
            }

            // start buycourse validation
            // display the course price and buy button if the buycourses plugin is enabled and this course is configured
            $plugin = BuyCoursesPlugin::create();
            $isThisCourseInSale = $plugin->buyCoursesForGridCatalogValidator(
                $course_info['real_id'],
                BuyCoursesPlugin::PRODUCT_TYPE_COURSE
            );
            if ($isThisCourseInSale) {
                // set the price label
                $my_course['price'] = $isThisCourseInSale['html'];
                // set the Buy button instead register.
                if ($isThisCourseInSale['verificator'] && !empty($my_course['register_button'])) {
                    $my_course['register_button'] = $plugin->returnBuyCourseButton(
                        $course_info['real_id'],
                        BuyCoursesPlugin::PRODUCT_TYPE_COURSE
                    );
                }
            }
            // end buycourse validation

            // Description
            $my_course['description_button'] = self::returnDescriptionButton($course_info);
            $my_course['teachers'] = self::getTeachersFromCourse($course_info['real_id'], true);
            $point_info = self::get_course_ranking($course_info['real_id'], 0);
            $my_course['rating_html'] = '';
            if (false === api_get_configuration_value('hide_course_rating')) {
                $my_course['rating_html'] = Display::return_rating_system(
                    'star_'.$course_info['real_id'],
                    $ajax_url.'&course_id='.$course_info['real_id'],
                    $point_info
                );
            }
            $hotCourses[] = $my_course;
        }

        return $hotCourses;
    }

    public function totalSubscribedUsersInCourses($urlId)
    {
        $table_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $table_course_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
        $courseUsers = Database::get_main_table(TABLE_MAIN_COURSE_USER);

        $urlId = (int) $urlId;

        $sql = "SELECT count(cu.user_id) count
                FROM $courseUsers cu
                INNER JOIN $table_course_rel_access_url u
                ON cu.c_id = u.c_id
                WHERE
                    relation_type <> ".COURSE_RELATION_TYPE_RRHH." AND
                    u.access_url_id = $urlId AND
                    visibility <> ".Course::CLOSED." AND
                    visibility <> ".Course::HIDDEN."
                     ";

        $res = Database::query($sql);
        $row = Database::fetch_array($res);

        return $row['count'];
    }

    /**
     * Get courses count.
     *
     * @param int $access_url_id Access URL ID (optional)
     * @param int $visibility
     *
     * @return int Number of courses
     */
    public static function count_courses($access_url_id = null, $visibility = null)
    {
        $table_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $table_course_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
        $sql = "SELECT count(c.id) FROM $table_course c";
        if (!empty($access_url_id) && $access_url_id == intval($access_url_id)) {
            $sql .= ", $table_course_rel_access_url u
                    WHERE c.id = u.c_id AND u.access_url_id = $access_url_id";
            if (!empty($visibility)) {
                $visibility = intval($visibility);
                $sql .= " AND visibility = $visibility ";
            }
        } else {
            if (!empty($visibility)) {
                $visibility = intval($visibility);
                $sql .= " WHERE visibility = $visibility ";
            }
        }

        $res = Database::query($sql);
        $row = Database::fetch_row($res);

        return $row[0];
    }

    /**
     * Get active courses count.
     * Active = all courses except the ones with hidden visibility.
     *
     * @param int $urlId Access URL ID (optional)
     *
     * @return int Number of courses
     */
    public static function countActiveCourses($urlId = null)
    {
        $table_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $table_course_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
        $sql = "SELECT count(c.id) FROM $table_course c";
        if (!empty($urlId)) {
            $urlId = (int) $urlId;
            $sql .= ", $table_course_rel_access_url u
                    WHERE
                        c.id = u.c_id AND
                        u.access_url_id = $urlId AND
                        visibility <> ".Course::HIDDEN;
        } else {
            $sql .= " WHERE visibility <> ".Course::HIDDEN;
        }
        $res = Database::query($sql);
        $row = Database::fetch_row($res);

        return $row[0];
    }

    /**
     * Returns the SQL conditions to filter course only visible by the user in the catalogue.
     *
     * @param string $courseTableAlias Alias of the course table
     * @param bool   $hideClosed       Whether to hide closed and hidden courses
     * @param bool   $checkHidePrivate
     *
     * @return string SQL conditions
     */
    public static function getCourseVisibilitySQLCondition($courseTableAlias, $hideClosed = false, $checkHidePrivate = true)
    {
        $visibilityCondition = '';
        if ($checkHidePrivate) {
            $hidePrivateSetting = api_get_setting('course_catalog_hide_private');
            if ('true' === $hidePrivateSetting) {
                $visibilityCondition .= " AND $courseTableAlias.visibility <> ".Course::REGISTERED;
            }
        }
        if ($hideClosed) {
            $visibilityCondition .= " AND $courseTableAlias.visibility NOT IN (".Course::CLOSED.','.Course::HIDDEN.')';
        }

        // Check if course have users allowed to see it in the catalogue, then show only if current user is allowed to see it
        $currentUserId = api_get_user_id();
        $restrictedCourses = self::getCatalogCourseList(true);
        $allowedCoursesToCurrentUser = self::getCatalogCourseList(true, $currentUserId);
        if (!empty($restrictedCourses)) {
            $visibilityCondition .= ' AND ('.$courseTableAlias.'.code NOT IN ("'.implode('","', $restrictedCourses).'")';
            $visibilityCondition .= ' OR '.$courseTableAlias.'.code IN ("'.implode('","', $allowedCoursesToCurrentUser).'"))';
        }

        // Check if course have users denied to see it in the catalogue, then show only if current user is not denied to see it
        $restrictedCourses = self::getCatalogCourseList(false);
        $notAllowedCoursesToCurrentUser = self::getCatalogCourseList(false, $currentUserId);
        if (!empty($restrictedCourses)) {
            $visibilityCondition .= ' AND ('.$courseTableAlias.'.code NOT IN ("'.implode('","', $restrictedCourses).'")';
            $visibilityCondition .= ' OR '.$courseTableAlias.'.code NOT IN ("'.implode('","', $notAllowedCoursesToCurrentUser).'"))';
        }

        return $visibilityCondition;
    }

    /**
     * Return a link to go to the course, validating the visibility of the
     * course and the user status.
     *
     * @param int $uid User ID
     * @param array Course details array
     * @param array  List of courses to which the user is subscribed (if not provided, will be generated)
     *
     * @return mixed 'enter' for a link to go to the course or 'register' for a link to subscribe, or false if no access
     */
    public static function get_access_link_by_user($uid, $course, $user_courses = [])
    {
        if (empty($uid) || empty($course)) {
            return false;
        }

        if (empty($user_courses)) {
            // get the array of courses to which the user is subscribed
            $user_courses = self::get_courses_list_by_user_id($uid);
            foreach ($user_courses as $k => $v) {
                $user_courses[$k] = $v['real_id'];
            }
        }

        if (!isset($course['real_id']) && empty($course['real_id'])) {
            $course = api_get_course_info($course['code']);
        }

        if (Course::HIDDEN == $course['visibility']) {
            return [];
        }

        $is_admin = api_is_platform_admin_by_id($uid);
        $options = [];
        // Register button
        if (!api_is_anonymous($uid) &&
            (
            (Course::OPEN_WORLD == $course['visibility'] || Course::OPEN_PLATFORM == $course['visibility'])
                //$course['visibility'] == Course::REGISTERED && $course['subscribe'] == SUBSCRIBE_ALLOWED
            ) &&
            SUBSCRIBE_ALLOWED == $course['subscribe'] &&
            (!in_array($course['real_id'], $user_courses) || empty($user_courses))
        ) {
            $options[] = 'register';
        }

        $isLogin = !api_is_anonymous();

        // Go To Course button (only if admin, if course public or if student already subscribed)
        if ($is_admin ||
            Course::OPEN_WORLD == $course['visibility'] && empty($course['registration_code']) ||
            ($isLogin && Course::OPEN_PLATFORM == $course['visibility'] && empty($course['registration_code'])) ||
            (in_array($course['real_id'], $user_courses) && Course::CLOSED != $course['visibility'])
        ) {
            $options[] = 'enter';
        }

        if ($is_admin ||
            Course::OPEN_WORLD == $course['visibility'] && empty($course['registration_code']) ||
            ($isLogin && Course::OPEN_PLATFORM == $course['visibility'] && empty($course['registration_code'])) ||
            (in_array($course['real_id'], $user_courses) && Course::CLOSED != $course['visibility'])
        ) {
            $options[] = 'enter';
        }

        if (Course::HIDDEN != $course['visibility'] &&
            empty($course['registration_code']) &&
            UNSUBSCRIBE_ALLOWED == $course['unsubscribe'] &&
            $isLogin &&
            in_array($course['real_id'], $user_courses)
        ) {
            $options[] = 'unsubscribe';
        }

        return $options;
    }

    /**
     * @param array          $courseInfo
     * @param array          $teachers
     * @param bool           $deleteTeachersNotInList
     * @param bool           $editTeacherInSessions
     * @param bool           $deleteSessionTeacherNotInList
     * @param array          $teacherBackup
     * @param Monolog\Logger $logger
     *
     * @return false|null
     */
    public static function updateTeachers(
        $courseInfo,
        $teachers,
        $deleteTeachersNotInList = true,
        $editTeacherInSessions = false,
        $deleteSessionTeacherNotInList = false,
        $teacherBackup = [],
        $logger = null
    ) {
        if (!is_array($teachers)) {
            $teachers = [$teachers];
        }

        if (empty($courseInfo) || !isset($courseInfo['real_id'])) {
            return false;
        }

        $teachers = array_filter($teachers);
        $courseId = $courseInfo['real_id'];
        $course_code = $courseInfo['code'];

        $course_user_table = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $alreadyAddedTeachers = self::get_teacher_list_from_course_code($course_code);

        if ($deleteTeachersNotInList) {
            // Delete only teacher relations that doesn't match the selected teachers
            $cond = null;
            if (count($teachers) > 0) {
                foreach ($teachers as $key) {
                    $key = Database::escape_string($key);
                    $cond .= " AND user_id <> '".$key."'";
                }
            }

            // Recover user categories
            $sql = "SELECT * FROM $course_user_table
                    WHERE c_id = $courseId AND status = 1 AND relation_type = 0 ".$cond;
            $result = Database::query($sql);
            if (Database::num_rows($result)) {
                $teachersToDelete = Database::store_result($result, 'ASSOC');
                foreach ($teachersToDelete as $data) {
                    $userId = $data['user_id'];
                    $teacherBackup[$userId][$course_code] = $data;
                }
            }

            $sql = "DELETE FROM $course_user_table
                    WHERE c_id = $courseId AND status = 1 AND relation_type = 0 ".$cond;

            Database::query($sql);
        }

        if (count($teachers) > 0) {
            foreach ($teachers as $userId) {
                $userId = intval($userId);
                // We check if the teacher is already subscribed in this course
                $sql = "SELECT 1 FROM $course_user_table
                        WHERE user_id = $userId AND c_id = $courseId";
                $result = Database::query($sql);
                if (Database::num_rows($result)) {
                    $sql = "UPDATE $course_user_table
                            SET status = 1
                            WHERE c_id = $courseId AND user_id = $userId ";
                } else {
                    $userCourseCategory = '0';
                    if (isset($teacherBackup[$userId]) &&
                        isset($teacherBackup[$userId][$course_code])
                    ) {
                        $courseUserData = $teacherBackup[$userId][$course_code];
                        $userCourseCategory = $courseUserData['user_course_cat'];
                        if ($logger) {
                            $logger->debug("Recovering user_course_cat: $userCourseCategory");
                        }
                    }

                    $sql = "INSERT INTO $course_user_table SET
                            c_id = $courseId,
                            user_id = $userId,
                            status = 1,
                            is_tutor = 0,
                            sort = 0,
                            relation_type = 0,
                            user_course_cat = $userCourseCategory
                    ";
                }
                Database::query($sql);
            }
        }

        if ($editTeacherInSessions) {
            $sessions = SessionManager::get_session_by_course($courseId);
            if (!empty($sessions)) {
                if ($logger) {
                    $logger->debug("Edit teachers in sessions");
                }
                foreach ($sessions as $session) {
                    $sessionId = $session['id'];
                    // Remove old and add new
                    if ($deleteSessionTeacherNotInList) {
                        foreach ($teachers as $userId) {
                            if ($logger) {
                                $logger->debug("Set coach #$userId in session #$sessionId of course #$courseId ");
                            }
                            SessionManager::set_coach_to_course_session(
                                $userId,
                                $sessionId,
                                $courseId
                            );
                        }

                        $teachersToDelete = [];
                        if (!empty($alreadyAddedTeachers)) {
                            $teachersToDelete = array_diff(array_keys($alreadyAddedTeachers), $teachers);
                        }

                        if (!empty($teachersToDelete)) {
                            foreach ($teachersToDelete as $userId) {
                                if ($logger) {
                                    $logger->debug("Delete coach #$userId in session #$sessionId of course #$courseId ");
                                }
                                SessionManager::set_coach_to_course_session(
                                    $userId,
                                    $sessionId,
                                    $courseId,
                                    true
                                );
                            }
                        }
                    } else {
                        // Add new teachers only
                        foreach ($teachers as $userId) {
                            if ($logger) {
                                $logger->debug("Add coach #$userId in session #$sessionId of course #$courseId ");
                            }
                            SessionManager::set_coach_to_course_session(
                                $userId,
                                $sessionId,
                                $courseId
                            );
                        }
                    }
                }
            }
        }
    }

    /**
     * Course available settings variables see c_course_setting table.
     *
     * @return array
     */
    public static function getCourseSettingVariables(AppPlugin $appPlugin)
    {
        $pluginCourseSettings = $appPlugin->getAllPluginCourseSettings();
        $courseSettings = [
            // Get allow_learning_path_theme from table
            'allow_learning_path_theme',
            // Get allow_open_chat_window from table
            'allow_open_chat_window',
            'allow_public_certificates',
            // Get allow_user_edit_agenda from table
            'allow_user_edit_agenda',
            // Get allow_user_edit_announcement from table
            'allow_user_edit_announcement',
            // Get allow_user_image_forum from table
            'allow_user_image_forum',
            //Get allow show user list
            'allow_user_view_user_list',
            // Get course_theme from table
            'course_theme',
            //Get allow show user list
            'display_info_advance_inside_homecourse',
            'documents_default_visibility',
            // Get send_mail_setting (work)from table
            'email_alert_manager_on_new_doc',
            // Get send_mail_setting (work)from table
            'email_alert_manager_on_new_quiz',
            // Get send_mail_setting (dropbox) from table
            'email_alert_on_new_doc_dropbox',
            'email_alert_students_on_new_homework',
            // Get send_mail_setting (auth)from table
            'email_alert_to_teacher_on_new_user_in_course',
            'enable_lp_auto_launch',
            'enable_exercise_auto_launch',
            'enable_document_auto_launch',
            'pdf_export_watermark_text',
            'show_system_folders',
            'exercise_invisible_in_session',
            'enable_forum_auto_launch',
            'show_course_in_user_language',
            'email_to_teachers_on_new_work_feedback',
            'student_delete_own_publication',
            'hide_forum_notifications',
            'quiz_question_limit_per_day',
            'subscribe_users_to_forum_notifications',
        ];

        $courseModels = ExerciseLib::getScoreModels();
        if (!empty($courseModels)) {
            $courseSettings[] = 'score_model_id';
        }

        $allowLPReturnLink = api_get_setting('allow_lp_return_link');
        if ('true' === $allowLPReturnLink) {
            $courseSettings[] = 'lp_return_link';
        }

        if (!empty($pluginCourseSettings)) {
            $courseSettings = array_merge(
                $courseSettings,
                $pluginCourseSettings
            );
        }

        return $courseSettings;
    }

    /**
     * @param string       $variable
     * @param string|array $value
     * @param int          $courseId
     *
     * @return bool
     */
    public static function saveCourseConfigurationSetting(AppPlugin $appPlugin, $variable, $value, $courseId)
    {
        $settingList = self::getCourseSettingVariables($appPlugin);

        if (!in_array($variable, $settingList)) {
            return false;
        }

        $courseSettingTable = Database::get_course_table(TABLE_COURSE_SETTING);

        if (is_array($value)) {
            $value = implode(',', $value);
        }

        $settingFromDatabase = self::getCourseSetting($variable, $courseId);

        if (!empty($settingFromDatabase)) {
            // Update
            Database::update(
                $courseSettingTable,
                ['value' => $value],
                ['variable = ? AND c_id = ?' => [$variable, $courseId]]
            );

            if ($settingFromDatabase['value'] != $value) {
                Event::addEvent(
                    LOG_COURSE_SETTINGS_CHANGED,
                    $variable,
                    $settingFromDatabase['value']." -> $value"
                );
            }
        } else {
            // Create
            Database::insert(
                $courseSettingTable,
                [
                    'title' => $variable,
                    'value' => $value,
                    'c_id' => $courseId,
                    'variable' => $variable,
                ]
            );

            Event::addEvent(
                LOG_COURSE_SETTINGS_CHANGED,
                $variable,
                $value
            );
        }

        return true;
    }

    /**
     * Get course setting.
     *
     * @param string $variable
     * @param int    $courseId
     *
     * @return array
     */
    public static function getCourseSetting($variable, $courseId)
    {
        $courseSetting = Database::get_course_table(TABLE_COURSE_SETTING);
        $courseId = (int) $courseId;
        $variable = Database::escape_string($variable);
        $sql = "SELECT variable, value FROM $courseSetting
                WHERE c_id = $courseId AND variable = '$variable'";
        $result = Database::query($sql);

        return Database::fetch_array($result);
    }

    public static function saveSettingChanges($courseInfo, $params)
    {
        if (empty($courseInfo) || empty($params)) {
            return false;
        }

        $userId = api_get_user_id();
        $now = api_get_utc_datetime();

        foreach ($params as $name => $value) {
            $emptyValue = ' - ';
            if (isset($courseInfo[$name]) && $courseInfo[$name] != $value) {
                if ('' !== $courseInfo[$name]) {
                    $emptyValue = $courseInfo[$name];
                }

                $changedTo = $emptyValue.' -> '.$value;

                Event::addEvent(
                    LOG_COURSE_SETTINGS_CHANGED,
                    $name,
                    $changedTo,
                    $now,
                    $userId,
                    $courseInfo['real_id']
                );
            }
        }

        return true;
    }

    /**
     * Get information from the track_e_course_access table.
     *
     * @param int    $courseId
     * @param int    $sessionId
     * @param string $startDate
     * @param string $endDate
     *
     * @return array
     */
    public static function getCourseAccessPerCourseAndSession(
        $courseId,
        $sessionId,
        $startDate,
        $endDate
    ) {
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
        $courseId = (int) $courseId;
        $sessionId = (int) $sessionId;
        $startDate = Database::escape_string($startDate);
        $endDate = Database::escape_string($endDate);

        $sql = "SELECT * FROM $table
                WHERE
                    c_id = $courseId AND
                    session_id = $sessionId AND
                    login_course_date BETWEEN '$startDate' AND '$endDate'
                ";

        $result = Database::query($sql);

        return Database::store_result($result);
    }

    /**
     * Get login information from the track_e_course_access table, for any
     * course in the given session.
     *
     * @param int $sessionId
     * @param int $userId
     *
     * @return array
     */
    public static function getFirstCourseAccessPerSessionAndUser($sessionId, $userId)
    {
        $sessionId = (int) $sessionId;
        $userId = (int) $userId;

        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
        $sql = "SELECT * FROM $table
                WHERE session_id = $sessionId AND user_id = $userId
                ORDER BY login_course_date ASC
                LIMIT 1";

        $result = Database::query($sql);
        $courseAccess = [];
        if (Database::num_rows($result)) {
            $courseAccess = Database::fetch_array($result, 'ASSOC');
        }

        return $courseAccess;
    }

    /**
     * @param int  $courseId
     * @param int  $sessionId
     * @param bool $getAllSessions
     *
     * @return mixed
     */
    public static function getCountForum(
        $courseId,
        $sessionId = 0,
        $getAllSessions = false
    ) {
        $forum = Database::get_course_table(TABLE_FORUM);
        if ($getAllSessions) {
            $sql = "SELECT count(*) as count
                    FROM $forum f
                    WHERE f.c_id = %s";
        } else {
            $sql = "SELECT count(*) as count
                    FROM $forum f
                    WHERE f.c_id = %s and f.session_id = %s";
        }

        $sql = sprintf($sql, intval($courseId), intval($sessionId));
        $result = Database::query($sql);
        $row = Database::fetch_array($result);

        return $row['count'];
    }

    /**
     * @param int $userId
     * @param int $courseId
     * @param int $sessionId
     *
     * @return mixed
     */
    public static function getCountPostInForumPerUser(
        $userId,
        $courseId,
        $sessionId = 0
    ) {
        $forum = Database::get_course_table(TABLE_FORUM);
        $forum_post = Database::get_course_table(TABLE_FORUM_POST);

        $sql = "SELECT count(distinct post_id) as count
                FROM $forum_post p
                INNER JOIN $forum f
                ON f.forum_id = p.forum_id AND f.c_id = p.c_id
                WHERE p.poster_id = %s and f.session_id = %s and p.c_id = %s";

        $sql = sprintf(
            $sql,
            intval($userId),
            intval($sessionId),
            intval($courseId)
        );

        $result = Database::query($sql);
        $row = Database::fetch_array($result);

        return $row['count'];
    }

    /**
     * @param int $userId
     * @param int $courseId
     * @param int $sessionId
     *
     * @return mixed
     */
    public static function getCountForumPerUser(
        $userId,
        $courseId,
        $sessionId = 0
    ) {
        $forum = Database::get_course_table(TABLE_FORUM);
        $forum_post = Database::get_course_table(TABLE_FORUM_POST);

        $sql = "SELECT count(distinct f.forum_id) as count
                FROM $forum_post p
                INNER JOIN $forum f
                ON f.forum_id = p.forum_id AND f.c_id = p.c_id
                WHERE p.poster_id = %s and f.session_id = %s and p.c_id = %s";

        $sql = sprintf(
            $sql,
            intval($userId),
            intval($sessionId),
            intval($courseId)
        );

        $result = Database::query($sql);
        $row = Database::fetch_array($result);

        return $row['count'];
    }

    /**
     * Returns the course name from a given code.
     *
     * @param string $code
     *
     * @return string
     */
    public static function getCourseNameFromCode($code)
    {
        $tbl_main_categories = Database::get_main_table(TABLE_MAIN_COURSE);
        $code = Database::escape_string($code);
        $sql = "SELECT title
                FROM $tbl_main_categories
                WHERE code = '$code'";
        $result = Database::query($sql);
        if ($col = Database::fetch_array($result)) {
            return $col['title'];
        }
    }

    /**
     * Generates a course code from a course title.
     *
     * @todo Such a function might be useful in other places too. It might be moved in the CourseManager class.
     * @todo the function might be upgraded for avoiding code duplications (currently,
     * it might suggest a code that is already in use)
     *
     * @param string $title A course title
     *
     * @return string A proposed course code
     *                +
     * @assert (null,null) === false
     * @assert ('ABC_DEF', null) === 'ABCDEF'
     * @assert ('ABC09*^[%A', null) === 'ABC09A'
     */
    public static function generate_course_code($title)
    {
        return substr(
            preg_replace('/[^A-Z0-9]/', '', strtoupper(api_replace_dangerous_char($title))),
            0,
            self::MAX_COURSE_LENGTH_CODE
        );
    }

    /**
     * this function gets all the users of the course,
     * including users from linked courses.
     *
     * @param $filterByActive
     *
     * @return array
     */
    public static function getCourseUsers($filterByActive = null)
    {
        // This would return only the users from real courses:
        return self::get_user_list_from_course_code(
            api_get_course_id(),
            api_get_session_id(),
            null,
            null,
            null,
            null,
            false,
            false,
            [],
            [],
            [],
            $filterByActive
        );
    }

    /**
     * this function gets all the groups of the course,
     * not including linked courses.
     *
     * @return CGroup[]
     */
    public static function getCourseGroups()
    {
        $sessionId = api_get_session_id();
        $courseCode = api_get_course_id();
        if (0 != $sessionId) {
            $groupList = self::get_group_list_of_course(
                $courseCode,
                $sessionId,
                1
            );
        } else {
            $groupList = self::get_group_list_of_course(
                $courseCode,
                0,
                1
            );
        }

        return $groupList;
    }

    /**
     * @param FormValidator $form
     * @param array         $alreadySelected
     *
     * @return HTML_QuickForm_element
     */
    public static function addUserGroupMultiSelect(&$form, $alreadySelected, $addShortCut = false)
    {
        $userList = self::getCourseUsers(true);
        $groupList = self::getCourseGroups();

        $array = self::buildSelectOptions(
            $groupList,
            $userList,
            $alreadySelected
        );

        $result = [];
        foreach ($array as $content) {
            $result[$content['value']] = $content['content'];
        }

        $multiple = $form->addMultiSelect(
            'users',
            get_lang('Users'),
            $result,
            ['select_all_checkbox' => true, 'id' => 'users']
        );

        $sessionId = api_get_session_id();
        if ($addShortCut && empty($sessionId)) {
            $addStudents = [];
            foreach ($userList as $user) {
                if (STUDENT == $user['status_rel']) {
                    $addStudents[] = $user['user_id'];
                }
            }
            if (!empty($addStudents)) {
                $form->addHtml(
                    '<script>
                    $(function() {
                        $("#add_students").on("click", function() {
                            var addStudents = '.json_encode($addStudents).';
                            $.each(addStudents, function( index, value ) {
                                var option = $("#users option[value=\'USER:"+value+"\']");
                                if (option.val()) {
                                    $("#users_to").append(new Option(option.text(), option.val()))
                                    option.remove();
                                }
                            });

                            return false;
                        });
                    });
                    </script>'
                );

                $form->addLabel(
                    '',
                    Display::url(get_lang('Add learners'), '#', ['id' => 'add_students', 'class' => 'btn btn-primary'])
                );
            }
        }

        return $multiple;
    }

    /**
     * This function separates the users from the groups
     * users have a value USER:XXX (with XXX the groups id have a value
     *  GROUP:YYY (with YYY the group id).
     *
     * @param array $to Array of strings that define the type and id of each destination
     *
     * @return array Array of groups and users (each an array of IDs)
     */
    public static function separateUsersGroups($to)
    {
        $groupList = [];
        $userList = [];

        foreach ($to as $to_item) {
            if (!empty($to_item)) {
                $parts = explode(':', $to_item);
                $type = isset($parts[0]) ? $parts[0] : '';
                $id = isset($parts[1]) ? $parts[1] : '';

                switch ($type) {
                    case 'GROUP':
                        $groupList[] = (int) $id;
                        break;
                    case 'USER':
                        $userList[] = (int) $id;
                        break;
                }
            }
        }

        $send_to['groups'] = $groupList;
        $send_to['users'] = $userList;

        return $send_to;
    }

    /**
     * Shows the form for sending a message to a specific group or user.
     *
     * @return HTML_QuickForm_element
     */
    public static function addGroupMultiSelect(FormValidator $form, CGroup $group, $to = [])
    {
        $groupUsers = GroupManager::get_subscribed_users($group);
        $array = self::buildSelectOptions([$group], $groupUsers, $to);

        $result = [];
        foreach ($array as $content) {
            $result[$content['value']] = $content['content'];
        }

        return $form->addMultiSelect('users', get_lang('Users'), $result);
    }

    /**
     * this function shows the form for sending a message to a specific group or user.
     *
     * @param CGroup[] $groupList
     * @param array    $userList
     * @param array    $alreadySelected
     *
     * @return array
     */
    public static function buildSelectOptions($groupList = [], $userList = [], $alreadySelected = [])
    {
        if (empty($alreadySelected)) {
            $alreadySelected = [];
        }

        $result = [];
        // adding the groups to the select form
        if ($groupList) {
            foreach ($groupList as $thisGroup) {
                $groupId = $thisGroup->getIid();
                if (is_array($alreadySelected)) {
                    if (!in_array(
                        "GROUP:".$groupId,
                        $alreadySelected
                    )
                    ) {
                        $userCount = $thisGroup->getMembers()->count();

                        // $alreadySelected is the array containing the groups (and users) that are already selected
                        $userLabel = ($userCount > 0) ? get_lang('Users') : get_lang('user');
                        $userDisabled = ($userCount > 0) ? "" : "disabled=disabled";
                        $result[] = [
                            'disabled' => $userDisabled,
                            'value' => "GROUP:".$groupId,
                            // The space before "G" is needed in order to advmultiselect.php js puts groups first
                            'content' => " G: ".$thisGroup->getName()." - ".$userCount." ".$userLabel,
                        ];
                    }
                }
            }
        }

        // adding the individual users to the select form
        if ($userList) {
            foreach ($userList as $user) {
                if (is_array($alreadySelected)) {
                    if (!in_array(
                        "USER:".$user['user_id'],
                        $alreadySelected
                    )
                    ) {
                        // $alreadySelected is the array containing the users (and groups) that are already selected
                        $result[] = [
                            'value' => "USER:".$user['user_id'],
                            'content' => api_get_person_name($user['firstname'], $user['lastname']),
                        ];
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @return array a list (array) of all courses
     */
    public static function get_course_list()
    {
        $table = Database::get_main_table(TABLE_MAIN_COURSE);

        return Database::store_result(Database::query("SELECT *, id as real_id FROM $table"));
    }

    /**
     * Returns course code from a given gradebook category's id.
     *
     * @param int  Category ID
     *
     * @return string Course code
     */
    public static function get_course_by_category($category_id)
    {
        $category_id = (int) $category_id;
        $sql = 'SELECT c_id FROM '.Database::get_main_table(TABLE_MAIN_GRADEBOOK_CATEGORY).'
                WHERE id='.$category_id;
        $info = Database::fetch_array(Database::query($sql), 'ASSOC');

        return $info ? $info['c_id'] : false;
    }

    /**
     * This function gets all the courses that are not in a session.
     *
     * @param date Start date
     * @param date End date
     * @param bool $includeClosed Whether to include closed and hidden courses
     *
     * @return array Not-in-session courses
     */
    public static function getCoursesWithoutSession(
        $startDate = null,
        $endDate = null,
        $includeClosed = false
    ) {
        $dateConditional = ($startDate && $endDate) ?
            " WHERE session_id IN (SELECT id FROM ".Database::get_main_table(TABLE_MAIN_SESSION).
            " WHERE access_start_date = '$startDate' AND access_end_date = '$endDate')" : null;
        $visibility = ($includeClosed ? '' : 'visibility NOT IN (0, 4) AND ');

        $sql = "SELECT id, code, title
                FROM ".Database::get_main_table(TABLE_MAIN_COURSE)."
                WHERE $visibility code NOT IN (
                    SELECT DISTINCT course_code
                    FROM ".Database::get_main_table(TABLE_MAIN_SESSION_COURSE).$dateConditional."
                )
                ORDER BY id";

        $result = Database::query($sql);
        $courses = [];
        while ($row = Database::fetch_array($result)) {
            $courses[] = $row;
        }

        return $courses;
    }

    /**
     * Get list of courses based on users of a group for a group admin.
     *
     * @param int $userId The user id
     *
     * @return array
     */
    public static function getCoursesFollowedByGroupAdmin($userId)
    {
        $coursesList = [];
        $courseTable = Database::get_main_table(TABLE_MAIN_COURSE);
        $courseUserTable = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $userGroup = new UserGroupModel();
        $userIdList = $userGroup->getGroupUsersByUser($userId);

        if (empty($userIdList)) {
            return [];
        }

        $sql = "SELECT DISTINCT(c.id), c.title
                FROM $courseTable c
                INNER JOIN $courseUserTable cru ON c.id = cru.c_id
                WHERE (
                    cru.user_id IN (".implode(', ', $userIdList).")
                    AND cru.relation_type = 0
                )";

        if (api_is_multiple_url_enabled()) {
            $courseAccessUrlTable = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
            $accessUrlId = api_get_current_access_url_id();

            if (-1 != $accessUrlId) {
                $sql = "SELECT DISTINCT(c.id), c.title
                        FROM $courseTable c
                        INNER JOIN $courseUserTable cru ON c.id = cru.c_id
                        INNER JOIN $courseAccessUrlTable crau ON c.id = crau.c_id
                        WHERE crau.access_url_id = $accessUrlId
                            AND (
                            cru.id_user IN (".implode(', ', $userIdList).") AND
                            cru.relation_type = 0
                        )";
            }
        }

        $result = Database::query($sql);
        while ($row = Database::fetch_assoc($result)) {
            $coursesList[] = $row;
        }

        return $coursesList;
    }

    /**
     * Direct course link see #5299.
     *
     * You can send to your students an URL like this
     * http://chamilodev.beeznest.com/main/auth/inscription.php?c=ABC&e=3
     * Where "c" is the course code and "e" is the exercise Id, after a successful
     * registration the user will be sent to the course or exercise
     *
     * @param array $form_data
     *
     * @return array
     */
    public static function redirectToCourse($form_data)
    {
        $course_code_redirect = Session::read('course_redirect');
        $_user = api_get_user_info();
        $userId = api_get_user_id();

        if (!empty($course_code_redirect)) {
            $course_info = api_get_course_info($course_code_redirect);
            if (!empty($course_info)) {
                if (in_array(
                    $course_info['visibility'],
                    [Course::OPEN_PLATFORM, Course::OPEN_WORLD]
                )
                ) {
                    if (self::is_user_subscribed_in_course($userId, $course_info['code'])) {
                        $form_data['action'] = $course_info['course_public_url'];
                        $form_data['message'] = sprintf(get_lang('You have been registered to course %s'), $course_info['title']);
                        $form_data['button'] = Display::button(
                            'next',
                            get_lang('Go to the course', null, $_user['language']),
                            ['class' => 'btn btn-primary btn-large']
                        );

                        $exercise_redirect = (int) Session::read('exercise_redirect');
                        // Specify the course id as the current context does not
                        // hold a global $_course array
                        $objExercise = new Exercise($course_info['real_id']);
                        $result = $objExercise->read($exercise_redirect);

                        if (!empty($exercise_redirect) && !empty($result)) {
                            $form_data['action'] = api_get_path(WEB_CODE_PATH).
                                'exercise/overview.php?exerciseId='.$exercise_redirect.'&cid='.$course_info['real_id'];
                            $form_data['message'] .= '<br />'.get_lang('Go to the test');
                            $form_data['button'] = Display::button(
                                'next',
                                get_lang('Go', null, $_user['language']),
                                ['class' => 'btn btn-primary btn-large']
                            );
                        }

                        if (!empty($form_data['action'])) {
                            header('Location: '.$form_data['action']);
                            exit;
                        }
                    }
                }
            }
        }

        return $form_data;
    }

    /**
     * Return tab of params to display a course title in the My Courses tab
     * Check visibility, right, and notification icons, and load_dirs option
     * get html course params.
     *
     * @param $courseId
     * @param bool $loadDirs
     *
     * @return array with keys ['right_actions'] ['teachers'] ['notifications']
     */
    public static function getCourseParamsForDisplay($courseId, $loadDirs = false)
    {
        $userId = api_get_user_id();
        $courseId = intval($courseId);
        // Table definitions
        $TABLECOURS = Database::get_main_table(TABLE_MAIN_COURSE);
        $TABLECOURSUSER = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $TABLE_ACCESS_URL_REL_COURSE = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
        $current_url_id = api_get_current_access_url_id();

        // Get course list auto-register
        $special_course_list = self::get_special_course_list();

        $without_special_courses = '';
        if (!empty($special_course_list)) {
            $without_special_courses = ' AND course.id NOT IN ("'.implode('","', $special_course_list).'")';
        }

        //AND course_rel_user.relation_type<>".COURSE_RELATION_TYPE_RRHH."
        $sql = "SELECT
                    course.id,
                    course.title,
                    course.code,
                    course.subscribe subscr,
                    course.unsubscribe unsubscr,
                    course_rel_user.status status,
                    course_rel_user.sort sort,
                    course_rel_user.user_course_cat user_course_cat
                FROM
                $TABLECOURS course
                INNER JOIN $TABLECOURSUSER course_rel_user
                ON (course.id = course_rel_user.c_id)
                INNER JOIN $TABLE_ACCESS_URL_REL_COURSE url
                ON (url.c_id = course.id)
                WHERE
                    course.id = $courseId AND
                    course_rel_user.user_id = $userId
                    $without_special_courses
                ";

        // If multiple URL access mode is enabled, only fetch courses
        // corresponding to the current URL.
        if (api_get_multiple_access_url() && -1 != $current_url_id) {
            $sql .= " AND url.c_id = course.id AND access_url_id = $current_url_id";
        }
        // Use user's classification for courses (if any).
        $sql .= " ORDER BY course_rel_user.user_course_cat, course_rel_user.sort ASC";

        $result = Database::query($sql);

        // Browse through all courses. We can only have one course because
        // of the  course.id=".intval($courseId) in sql query
        $course = Database::fetch_array($result);
        $course_info = api_get_course_info_by_id($courseId);
        if (empty($course_info)) {
            return '';
        }

        //$course['id_session'] = null;
        $course_info['id_session'] = null;
        $course_info['status'] = $course['status'];

        // New code displaying the user's status in respect to this course.
        $status_icon = Display::return_icon(
            'blackboard.png',
            $course_info['title'],
            [],
            ICON_SIZE_LARGE
        );

        $params = [];
        $params['right_actions'] = '';

        if (api_is_platform_admin()) {
            if ($loadDirs) {
                $params['right_actions'] .= '<a id="document_preview_'.$course_info['real_id'].'_0" class="document_preview" href="javascript:void(0);">'.Display::return_icon('folder.png', get_lang('Documents'), ['align' => 'absmiddle'], ICON_SIZE_SMALL).'</a>';
                $params['right_actions'] .= '<a href="'.api_get_path(WEB_CODE_PATH).'course_info/infocours.php?cid='.$course['real_id'].'">'.
                    Display::return_icon('edit.png', get_lang('Edit'), ['align' => 'absmiddle'], ICON_SIZE_SMALL).
                    '</a>';
                $params['right_actions'] .= Display::div(
                    '',
                    [
                        'id' => 'document_result_'.$course_info['real_id'].'_0',
                        'class' => 'document_preview_container',
                    ]
                );
            } else {
                $params['right_actions'] .=
                    '<a class="btn btn-default btn-sm" title="'.get_lang('Edit').'" href="'.api_get_path(WEB_CODE_PATH).'course_info/infocours.php?cid='.$course['real_id'].'">'.
                    Display::returnFontAwesomeIcon('pencil').'</a>';
            }
        } else {
            if (Course::CLOSED != $course_info['visibility']) {
                if ($loadDirs) {
                    $params['right_actions'] .= '<a id="document_preview_'.$course_info['real_id'].'_0" class="document_preview" href="javascript:void(0);">'.
                        Display::return_icon('folder.png', get_lang('Documents'), ['align' => 'absmiddle'], ICON_SIZE_SMALL).'</a>';
                    $params['right_actions'] .= Display::div(
                        '',
                        [
                            'id' => 'document_result_'.$course_info['real_id'].'_0',
                            'class' => 'document_preview_container',
                        ]
                    );
                } else {
                    if (COURSEMANAGER == $course_info['status']) {
                        $params['right_actions'] .= '<a
                            class="btn btn-default btn-sm" title="'.get_lang('Edit').'" href="'.api_get_path(WEB_CODE_PATH).'course_info/infocours.php?cid='.$course['real_id'].'">'.
                            Display::returnFontAwesomeIcon('pencil').'</a>';
                    }
                }
            }
        }

        $course_title_url = '';
        if (Course::CLOSED != $course_info['visibility'] || COURSEMANAGER == $course['status']) {
            $course_title_url = api_get_path(WEB_COURSE_PATH).$course_info['path'].'/?id_session=0';
            $course_title = Display::url($course_info['title'], $course_title_url);
        } else {
            $course_title = $course_info['title'].' '.Display::tag(
                'span',
                get_lang('(the course is currently closed)'),
                ['class' => 'item_closed']
            );
        }

        // Start displaying the course block itself
        if ('true' === api_get_setting('display_coursecode_in_courselist')) {
            $course_title .= ' ('.$course_info['visual_code'].') ';
        }
        $teachers = '';
        if ('true' === api_get_setting('display_teacher_in_courselist')) {
            $teachers = self::getTeacherListFromCourseCodeToString(
                $course['code'],
                self::USER_SEPARATOR,
                true
            );
        }
        $params['link'] = $course_title_url;
        $params['icon'] = $status_icon;
        $params['title'] = $course_title;
        $params['teachers'] = $teachers;

        return $params;
    }

    /**
     * Get the course id based on the original id and field name in the extra fields.
     * Returns 0 if course was not found.
     *
     * @param string $original_course_id_value Original course id
     * @param string $original_course_id_name  Original field name
     *
     * @return int Course id
     */
    public static function get_course_id_from_original_id($original_course_id_value, $original_course_id_name)
    {
        $extraFieldValue = new ExtraFieldValue('course');
        $value = $extraFieldValue->get_item_id_from_field_variable_and_field_value(
            $original_course_id_name,
            $original_course_id_value
        );

        if ($value) {
            return $value['item_id'];
        }

        return 0;
    }

    /**
     * Helper function to create a default gradebook (if necessary) upon course creation.
     *
     * @param int    $modelId    The gradebook model ID
     * @param string $courseCode Course code
     */
    public static function createDefaultGradebook($modelId, $courseCode)
    {
        if ('true' === api_get_setting('gradebook_enable_grade_model')) {
            //Create gradebook_category for the new course and add
            // a gradebook model for the course
            if (isset($modelId) &&
                !empty($modelId) &&
                '-1' != $modelId
            ) {
                GradebookUtils::create_default_course_gradebook(
                    $courseCode,
                    $modelId
                );
            }
        }
    }

    /**
     * Helper function to check if there is a course template and, if so, to
     * copy the template as basis for the new course.
     *
     * @param string $courseCode     Course code
     * @param int    $courseTemplate 0 if no course template is defined
     */
    public static function useTemplateAsBasisIfRequired($courseCode, $courseTemplate)
    {
        $template = api_get_setting('course_creation_use_template');
        $teacherCanSelectCourseTemplate = 'true' === api_get_setting('teacher_can_select_course_template');
        $courseTemplate = isset($courseTemplate) ? intval($courseTemplate) : 0;

        $useTemplate = false;

        if ($teacherCanSelectCourseTemplate && $courseTemplate) {
            $useTemplate = true;
            $originCourse = api_get_course_info_by_id($courseTemplate);
        } elseif (!empty($template)) {
            $useTemplate = true;
            $originCourse = api_get_course_info_by_id($template);
        }

        if ($useTemplate) {
            // Include the necessary libraries to generate a course copy
            // Call the course copy object
            $originCourse['official_code'] = $originCourse['code'];
            $cb = new CourseBuilder(null, $originCourse);
            $course = $cb->build(null, $originCourse['code']);
            $cr = new CourseRestorer($course);
            $cr->set_file_option();
            $cr->restore($courseCode);
        }
    }

    /**
     * Helper method to get the number of users defined with a specific course extra field.
     *
     * @param string $name                 Field title
     * @param string $tableExtraFields     The extra fields table name
     * @param string $tableUserFieldValues The user extra field value table name
     *
     * @return int The number of users with this extra field with a specific value
     */
    public static function getCountRegisteredUsersWithCourseExtraField(
        $name,
        $tableExtraFields = '',
        $tableUserFieldValues = ''
    ) {
        if (empty($tableExtraFields)) {
            $tableExtraFields = Database::get_main_table(TABLE_EXTRA_FIELD);
        }
        if (empty($tableUserFieldValues)) {
            $tableUserFieldValues = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);
        }

        $registered_users_with_extra_field = 0;
        if (!empty($name) && '-' != $name) {
            $extraFieldType = EntityExtraField::COURSE_FIELD_TYPE;
            $name = Database::escape_string($name);
            $sql = "SELECT count(v.item_id) as count
                    FROM $tableUserFieldValues v
                    INNER JOIN $tableExtraFields f
                    ON (f.id = v.field_id)
                    WHERE value = '$name' AND extra_field_type = $extraFieldType";
            $result_count = Database::query($sql);
            if (Database::num_rows($result_count)) {
                $row_count = Database::fetch_array($result_count);
                $registered_users_with_extra_field = $row_count['count'];
            }
        }

        return $registered_users_with_extra_field;
    }

    /**
     * Get the course categories form a course list.
     *
     * @return array
     */
    public static function getCourseCategoriesFromCourseList(array $courseList)
    {
        $allCategories = array_column($courseList, 'category');
        $categories = array_unique($allCategories);

        sort($categories);

        return $categories;
    }

    /**
     * Display the description button of a course in the course catalog.
     *
     * @param array  $course
     * @param string $url
     *
     * @return string HTML string
     */
    public static function returnDescriptionButton($course, $url = '')
    {
        if (empty($course)) {
            return '';
        }

        $class = '';
        if ('true' === api_get_setting('show_courses_descriptions_in_catalog')) {
            $title = $course['title'];
            if (empty($url)) {
                $class = 'ajax';
                $url = api_get_path(WEB_CODE_PATH).
                    'inc/ajax/course_home.ajax.php?a=show_course_information&code='.$course['code'];
            } else {
                if (false !== strpos($url, 'ajax')) {
                    $class = 'ajax';
                }
            }

            return Display::url(
                Display::returnFontAwesomeIcon('info-circle', 'lg'),
                $url,
                [
                    'class' => "$class btn btn-default btn-sm",
                    'data-title' => $title,
                    'title' => get_lang('Description'),
                    'aria-label' => get_lang('Description'),
                    'data-size' => 'lg',
                ]
            );
        }

        return '';
    }

    /**
     * @return int
     */
    public static function getCountOpenCourses()
    {
        $visibility = [
            Course::REGISTERED,
            Course::OPEN_PLATFORM,
            Course::OPEN_WORLD,
        ];

        $table = Database::get_main_table(TABLE_MAIN_COURSE);
        $sql = "SELECT count(id) count
                FROM $table
                WHERE visibility IN (".implode(',', $visibility).")";
        $result = Database::query($sql);
        $row = Database::fetch_array($result);

        return (int) $row['count'];
    }

    /**
     * @return int
     */
    public static function getCountExercisesFromOpenCourse()
    {
        $visibility = [
            Course::REGISTERED,
            Course::OPEN_PLATFORM,
            Course::OPEN_WORLD,
        ];

        $table = Database::get_main_table(TABLE_MAIN_COURSE);
        $tableExercise = Database::get_course_table(TABLE_QUIZ_TEST);
        $sql = "SELECT count(e.iid) count
                FROM $table c
                INNER JOIN $tableExercise e
                ON (c.id = e.c_id)
                WHERE e.active <> -1 AND visibility IN (".implode(',', $visibility).")";
        $result = Database::query($sql);
        $row = Database::fetch_array($result);

        return (int) $row['count'];
    }

    /**
     * retrieves all the courses that the user has already subscribed to.
     *
     * @param int $user_id
     *
     * @return array an array containing all the information of the courses of the given user
     */
    public static function getCoursesByUserCourseCategory($user_id)
    {
        $course = Database::get_main_table(TABLE_MAIN_COURSE);
        $courseRelUser = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $avoidCoursesCondition = CoursesAndSessionsCatalog::getAvoidCourseCondition();
        $visibilityCondition = self::getCourseVisibilitySQLCondition('course', true);

        // Secondly we select the courses that are in a category (user_course_cat<>0) and
        // sort these according to the sort of the category
        $user_id = (int) $user_id;
        $sql = "SELECT
                    course.code k,
                    course.visual_code vc,
                    course.subscribe subscr,
                    course.unsubscribe unsubscr,
                    course.title i,
                    course.tutor_name t,
                    course.category_code cat,
                    course.directory dir,
                    course_rel_user.status status,
                    course_rel_user.sort sort,
                    course_rel_user.user_course_cat user_course_cat
                FROM $course course, $courseRelUser course_rel_user
                WHERE
                    course.id = course_rel_user.c_id AND
                    course_rel_user.relation_type <> ".COURSE_RELATION_TYPE_RRHH." AND
                    course_rel_user.user_id = '".$user_id."'
                    $avoidCoursesCondition
                    $visibilityCondition
                ORDER BY course_rel_user.sort ASC";

        $result = Database::query($sql);
        $courses = [];
        while ($row = Database::fetch_array($result, 'ASOC')) {
            $courses[] = [
                'code' => $row['k'],
                'visual_code' => $row['vc'],
                'title' => $row['i'],
                'directory' => $row['dir'],
                'status' => $row['status'],
                'tutor' => $row['t'],
                'subscribe' => $row['subscr'],
                'category' => $row['cat'],
                'unsubscribe' => $row['unsubscr'],
                'sort' => $row['sort'],
                'user_course_category' => $row['user_course_cat'],
            ];
        }

        return $courses;
    }

    /**
     * @param string $listType
     *
     * @return string
     */
    public static function getCourseListTabs($listType)
    {
        $tabs = [
            [
                'content' => get_lang('Standard list'),
                'url' => api_get_path(WEB_CODE_PATH).'admin/course_list.php',
            ],
            [
                'content' => get_lang('Management List'),
                'url' => api_get_path(WEB_CODE_PATH).'admin/course_list_admin.php',
            ],
        ];

        $default = 1;
        switch ($listType) {
            case 'simple':
                $default = 1;
                break;
            case 'admin':
                $default = 2;
                break;
        }

        return Display::tabsOnlyLink($tabs, $default);
    }

    public static function getUrlMarker($courseId)
    {
        if (UrlManager::getCountAccessUrlFromCourse($courseId) > 1) {
            return '&nbsp;'.Display::returnFontAwesomeIcon(
                'link',
                null,
                null,
                null,
                get_lang('CourseUsedInOtherURL')
            );
        }

        return '';
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public static function insertUserInCourse(User $user, Course $course, array $relationInfo = []): ?int
    {
        $relationInfo = array_merge(
            ['relation_type' => 0, 'status' => STUDENT, 'sort' => 0, 'user_course_cat' => 0],
            $relationInfo
        );

        $courseRelUser = (new CourseRelUser())
            ->setCourse($course)
            ->setUser($user)
            ->setStatus($relationInfo['status'])
            ->setSort($relationInfo['sort'])
            ->setUserCourseCat($relationInfo['user_course_cat']);

        $course->addUsers($courseRelUser);

        $em = Database::getManager();
        $em->persist($course);
        $em->flush();

        $insertId = $courseRelUser->getId();

        Event::logSubscribedUserInCourse($user, $course);

        return $insertId;
    }

    public static function addVisibilityOptions(FormValidator $form): void
    {
        $group = [];
        $group[] = $form->createElement(
            'radio',
            'visibility',
            get_lang('Course access'),
            get_lang('Public - access allowed for the whole world'),
            Course::OPEN_WORLD
        );
        $group[] = $form->createElement(
            'radio',
            'visibility',
            null,
            get_lang(' Open - access allowed for users registered on the platform'),
            Course::OPEN_PLATFORM
        );
        $group[] = $form->createElement(
            'radio',
            'visibility',
            null,
            get_lang('Private access (access authorized to group members only)'),
            Course::REGISTERED
        );
        $group[] = $form->createElement(
            'radio',
            'visibility',
            null,
            get_lang('Closed - the course is only accessible to the teachers'),
            Course::CLOSED
        );
        // The "hidden" visibility is only available to portal admins
        if (api_is_platform_admin()) {
            $group[] = $form->createElement(
                'radio',
                'visibility',
                null,
                get_lang('Hidden - Completely hidden to all users except the administrators'),
                Course::HIDDEN
            );
        }
        $form->addGroup($group, '', get_lang('Course access'));
    }

    /**
     * Check if a specific access-url-related setting is a problem or not.
     *
     * @param array  $_configuration The $_configuration array
     * @param int    $accessUrlId    The access URL ID
     * @param string $param
     * @param string $msgLabel
     *
     * @return bool|string
     */
    private static function checkCreateCourseAccessUrlParam($_configuration, $accessUrlId, $param, $msgLabel)
    {
        if (isset($_configuration[$accessUrlId][$param]) && $_configuration[$accessUrlId][$param] > 0) {
            $num = null;
            switch ($param) {
                case 'hosting_limit_courses':
            $num = self::count_courses($accessUrlId);
                    break;
                case 'hosting_limit_active_courses':
                    $num = self::countActiveCourses($accessUrlId);
                    break;
            }

            if ($num && $num >= $_configuration[$accessUrlId][$param]) {
                api_warn_hosting_contact($param);

                Display::addFlash(
                    Display::return_message($msgLabel)
                );

                return true;
            }
        }

        return false;
    }

    /**
     * Fill course with all necessary items.
     *
     * @param array $params   Parameters from the course creation form
     * @param int   $authorId
     */
    private static function fillCourse(Course $course, $params, $authorId = 0)
    {
        $authorId = empty($authorId) ? api_get_user_id() : (int) $authorId;

        AddCourse::fillCourse(
            $course,
            $params['exemplary_content'],
            $authorId
        );

        if (isset($params['gradebook_model_id'])) {
            self::createDefaultGradebook(
                $params['gradebook_model_id'],
                $course->getCode()
            );
        }

        // If parameter defined, copy the contents from a specific
        // template course into this new course
        if (isset($params['course_template'])) {
            self::useTemplateAsBasisIfRequired(
                $course->getCode(),
                $params['course_template']
            );
        }
        $params['course_code'] = $course->getCode();
        $params['item_id'] = $course->getId();

        $courseFieldValue = new ExtraFieldValue('course');
        //$courseFieldValue->saveFieldValues($params);
    }
}
