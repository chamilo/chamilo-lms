<?php

/* See license terms in /license.txt */

use Chamilo\CoreBundle\Component\Utils\ChamiloApi;
use Chamilo\CoreBundle\Entity\TrackELoginAttempt;
use ChamiloSession as Session;

/**
 * Class Event
 * Functions of this library are used to record informations when some kind
 * of event occur. Each event has his own types of informations then each event
 * use its own function.
 */
class Event
{
    /**
     * @author Sebastien Piraux <piraux_seb@hotmail.com>
     * @desc Record information for open event (when homepage is opened)
     */
    public static function open()
    {
        global $_configuration;
        global $TABLETRACK_OPEN;

        // @getHostByAddr($_SERVER['REMOTE_ADDR']) : will provide host and country information
        // $_SERVER['HTTP_USER_AGENT'] :  will provide browser and os information
        // $_SERVER['HTTP_REFERER'] : provide information about refering url
        if (isset($_SERVER['HTTP_REFERER'])) {
            $referer = Database::escape_string($_SERVER['HTTP_REFERER']);
        } else {
            $referer = '';
        }
        // record informations only if user comes from another site
        //if(!eregi($_configuration['root_web'],$referer))
        $pos = strpos($referer, $_configuration['root_web']);
        if ($pos === false && $referer != '') {
            $ip = api_get_real_ip();
            $remhost = @gethostbyaddr($ip);
            if ($remhost == $ip) {
                $remhost = "Unknown";
            } // don't change this
            $reallyNow = api_get_utc_datetime();
            $params = [
                'open_remote_host' => Database::escape_string($remhost),
                'open_agent' => $_SERVER['HTTP_USER_AGENT'],
                'open_referer' => $referer,
                'open_date' => $reallyNow,
            ];
            Database::insert($TABLETRACK_OPEN, $params);
        }

        return 1;
    }

    /**
     * @throws Exception
     */
    public static function eventLoginAttempt(string $username, bool $success = false)
    {
        if ((int) api_get_configuration_value('login_max_attempt_before_blocking_account') <= 0) {
            return;
        }

        $attempt = new TrackELoginAttempt();
        $attempt
            ->setUsername($username)
            ->setLoginDate(api_get_utc_datetime(null, false, true))
            ->setUserIp(api_get_real_ip())
            ->setSuccess($success)
        ;

        $em = Database::getManager();
        $em->persist($attempt);
        $em->flush();
    }

    /**
     * @author Sebastien Piraux <piraux_seb@hotmail.com> old code
     * @author Julio Montoya
     *
     * @param int $userId
     *
     * @return bool
     * @desc Record information for login event when an user identifies himself with username & password
     */
    public static function eventLogin($userId)
    {
        $userInfo = api_get_user_info($userId);
        $userId = (int) $userId;

        if (empty($userInfo)) {
            return false;
        }

        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);
        $reallyNow = api_get_utc_datetime();
        $userIp = Database::escape_string(api_get_real_ip());

        $sql = "INSERT INTO $table (login_user_id, user_ip, login_date, logout_date) VALUES
                    ($userId,
                    '$userIp',
                    '$reallyNow',
                    '$reallyNow'
                )";
        Database::query($sql);

        // Auto subscribe
        $status = 'student';
        if ($userInfo['status'] == SESSIONADMIN) {
            $status = 'sessionadmin';
        }
        if ($userInfo['status'] == COURSEMANAGER) {
            $status = 'teacher';
        }
        if ($userInfo['status'] == DRH) {
            $status = 'DRH';
        }

        $autoSubscribe = api_get_setting($status.'_autosubscribe');
        if ($autoSubscribe) {
            $autoSubscribe = explode('|', $autoSubscribe);
            foreach ($autoSubscribe as $code) {
                if (CourseManager::course_exists($code)) {
                    CourseManager::subscribeUser($userId, $code);
                }
            }
        }

        return true;
    }

    /**
     * @param int $sessionId
     *
     * @return bool
     */
    public static function isSessionLogNeedToBeSave($sessionId)
    {
        if (!empty($sessionId)) {
            $visibility = api_get_session_visibility($sessionId);
            if (!empty($visibility) && $visibility != SESSION_AVAILABLE) {
                $extraFieldValue = new ExtraFieldValue('session');
                $value = $extraFieldValue->get_values_by_handler_and_field_variable(
                    $sessionId,
                    'disable_log_after_session_ends'
                );
                if (!empty($value) && isset($value['value']) && (int) $value['value'] == 1) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @author Sebastien Piraux <piraux_seb@hotmail.com>
     * @desc Record information for access event for courses
     *
     * @return bool
     */
    public static function accessCourse()
    {
        if (Session::read('login_as')) {
            return false;
        }

        $TABLETRACK_ACCESS = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ACCESS);
        // For "what's new" notification
        $TABLETRACK_LASTACCESS = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LASTACCESS);

        $sessionId = api_get_session_id();
        $now = api_get_utc_datetime();
        $courseId = api_get_course_int_id();
        $userId = api_get_user_id();
        $ip = Database::escape_string(api_get_real_ip());

        if (self::isSessionLogNeedToBeSave($sessionId) === false) {
            return false;
        }

        if ($userId) {
            $userId = $userId;
        } else {
            $userId = '0'; // no one
        }
        $sql = "INSERT INTO $TABLETRACK_ACCESS  (user_ip, access_user_id, c_id, access_date, access_session_id)
                VALUES ('$ip', $userId, $courseId, '$now', $sessionId)";

        Database::query($sql);

        // added for "what's new" notification
        $sql = "UPDATE $TABLETRACK_LASTACCESS  SET access_date = '$now'
                WHERE
                  access_user_id = $userId AND
                  c_id = $courseId AND
                  access_tool IS NULL AND
                  access_session_id = $sessionId";
        $result = Database::query($sql);

        if (Database::affected_rows($result) == 0) {
            $sql = "INSERT INTO $TABLETRACK_LASTACCESS (access_user_id, c_id, access_date, access_session_id)
                    VALUES ($userId, $courseId, '$now', $sessionId)";
            Database::query($sql);
        }

        return true;
    }

    /**
     * @param string $tool name of the tool
     *
     * @author Sebastien Piraux <piraux_seb@hotmail.com>
     * @desc Record information for access event for tools
     *
     *  $tool can take this values :
     *  Links, Calendar, Document, Announcements,
     *  Group, Video, Works, Users, Exercises, Course Desc
     *  ...
     *  Values can be added if new modules are created (15char max)
     *  I encourage to use $nameTool as $tool when calling this function
     *
     * Functionality for "what's new" notification is added by Toon Van Hoecke
     *
     * @return bool
     */
    public static function event_access_tool($tool)
    {
        if (Session::read('login_as')) {
            return false;
        }

        $tool = Database::escape_string($tool);

        if (empty($tool)) {
            return false;
        }

        $courseInfo = api_get_course_info();
        $sessionId = api_get_session_id();
        $reallyNow = api_get_utc_datetime();
        $userId = api_get_user_id();

        if (empty($courseInfo)) {
            return false;
        }

        if (self::isSessionLogNeedToBeSave($sessionId) === false) {
            return false;
        }

        $courseId = $courseInfo['real_id'];

        $tableAccess = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ACCESS);
        //for "what's new" notification
        $tableLastAccess = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LASTACCESS);

        // record information
        // only if user comes from the course $_cid
        //if( eregi($_configuration['root_web'].$_cid,$_SERVER['HTTP_REFERER'] ) )
        //$pos = strpos($_SERVER['HTTP_REFERER'],$_configuration['root_web'].$_cid);
        $coursePath = isset($courseInfo['path']) ? $courseInfo['path'] : null;

        $pos = isset($_SERVER['HTTP_REFERER']) ? strpos(strtolower($_SERVER['HTTP_REFERER']), strtolower(api_get_path(WEB_COURSE_PATH).$coursePath)) : false;
        // added for "what's new" notification
        $pos2 = isset($_SERVER['HTTP_REFERER']) ? strpos(strtolower($_SERVER['HTTP_REFERER']), strtolower(api_get_path(WEB_PATH)."index")) : false;

        // end "what's new" notification
        if ($pos !== false || $pos2 !== false) {
            $params = [
                'access_user_id' => $userId,
                'c_id' => $courseId,
                'access_tool' => $tool,
                'access_date' => $reallyNow,
                'access_session_id' => $sessionId,
                'user_ip' => Database::escape_string(api_get_real_ip()),
            ];
            Database::insert($tableAccess, $params);
        }

        // "what's new" notification
        $sql = "UPDATE $tableLastAccess
                SET access_date = '$reallyNow'
                WHERE
                    access_user_id = $userId AND
                    c_id = $courseId AND
                    access_tool = '$tool' AND
                    access_session_id = $sessionId";
        $result = Database::query($sql);

        if (Database::affected_rows($result) == 0) {
            $params = [
                'access_user_id' => $userId,
                'c_id' => $courseId,
                'access_tool' => $tool,
                'access_date' => $reallyNow,
                'access_session_id' => $sessionId,
            ];
            Database::insert($tableLastAccess, $params);
        }

        return true;
    }

    /**
     * Record information for download event (when an user click to d/l a
     * document) it will be used in a redirection page.
     *
     * @param string $documentUrl
     *
     * @return int
     *
     * @author Sebastien Piraux <piraux_seb@hotmail.com>
     * @author Evie Embrechts (bug fixed: The user id is put in single quotes)
     */
    public static function event_download($documentUrl)
    {
        if (Session::read('login_as')) {
            return false;
        }

        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_DOWNLOADS);
        $documentUrl = Database::escape_string($documentUrl);

        $reallyNow = api_get_utc_datetime();
        $userId = api_get_user_id();
        $courseId = api_get_course_int_id();
        $sessionId = api_get_session_id();

        return Database::insert(
            $table,
            [
                'down_user_id' => $userId,
                'c_id' => $courseId,
                'down_doc_path' => $documentUrl,
                'down_date' => $reallyNow,
                'down_session_id' => $sessionId,
            ]
        );
    }

    /**
     * @param int $documentId of document (id in mainDb.document table)
     *
     * @author Sebastien Piraux <piraux_seb@hotmail.com>
     * @desc Record information for upload event
     * used in the works tool to record informations when
     * an user upload 1 work
     *
     * @return int
     */
    public static function event_upload($documentId)
    {
        if (Session::read('login_as')) {
            return false;
        }

        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_UPLOADS);
        $courseId = api_get_course_int_id();
        $reallyNow = api_get_utc_datetime();
        $userId = api_get_user_id();
        $documentId = (int) $documentId;
        $sessionId = api_get_session_id();

        $sql = "INSERT INTO $table
                ( upload_user_id,
                  c_id,
                  upload_cours_id,
                  upload_work_id,
                  upload_date,
                  upload_session_id
                )
                VALUES (
                 $userId,
                 $courseId,
                 '',
                 $documentId,
                 '$reallyNow',
                 $sessionId
                )";
        Database::query($sql);

        return 1;
    }

    /**
     * Record information for link event (when an user click on an added link)
     * it will be used in a redirection page.
     *
     * @param int $linkId (id in c_link table)
     *
     * @return int
     *
     * @author Sebastien Piraux <piraux_seb@hotmail.com>
     */
    public static function event_link($linkId)
    {
        if (Session::read('login_as')) {
            return false;
        }

        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LINKS);
        $reallyNow = api_get_utc_datetime();
        $userId = api_get_user_id();
        $courseId = api_get_course_int_id();
        $linkId = (int) $linkId;
        $sessionId = api_get_session_id();
        $sql = "INSERT INTO ".$table."
                    ( links_user_id,
                     c_id,
                     links_link_id,
                     links_date,
                     links_session_id
                    ) VALUES (
                     $userId,
                     $courseId,
                     $linkId,
                     '$reallyNow',
                     $sessionId
                    )";
        Database::query($sql);

        return 1;
    }

    /**
     * Update the TRACK_E_EXERCICES exercises.
     * Record result of user when an exercise was done.
     *
     * @param int    $exeId
     * @param int    $exoId
     * @param mixed  $score
     * @param int    $weighting
     * @param int    $sessionId
     * @param int    $learnpathId
     * @param int    $learnpathItemId
     * @param int    $learnpathItemViewId
     * @param int    $duration
     * @param array  $questionsList
     * @param string $status
     * @param array  $remindList
     * @param null   $endDate
     *
     * @return bool
     *
     * @author Sebastien Piraux <piraux_seb@hotmail.com>
     * @author Julio Montoya Armas <gugli100@gmail.com> Reworked 2010
     */
    public static function updateEventExercise(
        $exeId,
        $exoId,
        $score,
        $weighting,
        $sessionId,
        $learnpathId = 0,
        $learnpathItemId = 0,
        $learnpathItemViewId = 0,
        $duration = 0,
        $questionsList = [],
        $status = '',
        $remindList = [],
        $endDate = null
    ) {
        if (empty($exeId)) {
            return false;
        }
        if (!isset($status) || empty($status)) {
            $status = '';
        } else {
            $status = Database::escape_string($status);
        }

        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);

        if (!empty($questionsList)) {
            $questionsList = array_map('intval', $questionsList);
        }

        if (!empty($remindList)) {
            $remindList = array_map('intval', $remindList);
            $remindList = array_filter($remindList);
            $remindList = implode(",", $remindList);
        } else {
            $remindList = '';
        }

        if (empty($endDate)) {
            $endDate = api_get_utc_datetime();
        }
        $exoId = (int) $exoId;
        $sessionId = (int) $sessionId;
        $learnpathId = (int) $learnpathId;
        $learnpathItemId = (int) $learnpathItemId;
        $learnpathItemViewId = (int) $learnpathItemViewId;
        $duration = (int) $duration;
        $exeId = (int) $exeId;
        $score = Database::escape_string($score);
        $weighting = Database::escape_string($weighting);
        $questions = implode(',', $questionsList);
        $userIp = Database::escape_string(api_get_real_ip());

        $sql = "UPDATE $table SET
               exe_exo_id = $exoId,
               exe_result = '$score',
               exe_weighting = '$weighting',
               session_id = $sessionId,
               orig_lp_id = $learnpathId,
               orig_lp_item_id = $learnpathItemId,
               orig_lp_item_view_id = $learnpathItemViewId,
               exe_duration = $duration,
               exe_date = '$endDate',
               status = '$status',
               questions_to_check = '$remindList',
               data_tracking = '$questions',
               user_ip = '$userIp'
             WHERE exe_id = $exeId";
        Database::query($sql);

        //Deleting control time session track
        //ExerciseLib::exercise_time_control_delete($exo_id);
        return true;
    }

    /**
     * Record an event for this attempt at answering an exercise.
     *
     * @param float  $score             Score achieved
     * @param string $answer            Answer given
     * @param int    $question_id
     * @param int    $exe_id            Exercise attempt ID a.k.a exe_id (from track_e_exercise)
     * @param int    $position
     * @param int    $exercise_id       From c_quiz
     * @param bool   $updateResults
     * @param int    $duration          Time spent in seconds
     * @param string $fileName          Filename (for audio answers - using nanogong)
     * @param int    $user_id           The user who's going to get this score.
     * @param int    $course_id         Default value of null means "get from context".
     * @param int    $session_id        Default value of null means "get from context".
     * @param int    $learnpath_id      (from c_lp table). Default value of null means "get from context".
     * @param int    $learnpath_item_id (from the c_lp_item table). Default value of null means "get from context".
     *
     * @return bool Result of the insert query
     */
    public static function saveQuestionAttempt(
        $score,
        $answer,
        $question_id,
        $exe_id,
        $position,
        $exercise_id = 0,
        $updateResults = false,
        $questionDuration = 0,
        $fileName = null,
        $user_id = null,
        $course_id = null,
        $session_id = null,
        $learnpath_id = null,
        $learnpath_item_id = null
    ) {
        global $debug;
        $questionDuration = (int) $questionDuration;
        $question_id = (int) $question_id;
        $exe_id = (int) $exe_id;
        $position = (int) $position;
        $course_id = (int) $course_id;
        $now = api_get_utc_datetime();
        $recording = api_get_configuration_value('quiz_answer_extra_recording');

        // check user_id or get from context
        if (empty($user_id)) {
            $user_id = api_get_user_id();
            // anonymous
            if (empty($user_id)) {
                $user_id = api_get_anonymous_id();
            }
        }
        // check course_id or get from context
        if (empty($course_id)) {
            $course_id = api_get_course_int_id();
        }
        // check session_id or get from context
        $session_id = (int) $session_id;
        if (empty($session_id)) {
            $session_id = api_get_session_id();
        }
        // check learnpath_id or get from context
        if (empty($learnpath_id)) {
            global $learnpath_id;
        }
        // check learnpath_item_id or get from context
        if (empty($learnpath_item_id)) {
            global $learnpath_item_id;
        }

        $TBL_TRACK_ATTEMPT = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);

        if ($debug) {
            error_log("----- entering saveQuestionAttempt() function ------");
            error_log("answer: $answer");
            error_log("score: $score");
            error_log("question_id : $question_id");
            error_log("position: $position");
        }

        // Validation in case of fraud with active control time.
        if (!ExerciseLib::exercise_time_control_is_valid($exercise_id, $learnpath_id, $learnpath_item_id)) {
            if ($debug) {
                error_log("exercise_time_control_is_valid is false");
            }
            $score = 0;
            $answer = 0;
        }

        if (empty($question_id) || empty($exe_id) || empty($user_id)) {
            return false;
        }

        if (null === $answer) {
            $answer = '';
        }

        if (null === $score) {
            $score = 0;
        }

        $attempt = [
            'user_id' => $user_id,
            'question_id' => $question_id,
            'answer' => $answer,
            'marks' => $score,
            'c_id' => $course_id,
            'session_id' => $session_id,
            'position' => $position,
            'tms' => $now,
            'filename' => !empty($fileName) ? basename($fileName) : $fileName,
            'teacher_comment' => '',
        ];

        if (api_get_configuration_value('allow_time_per_question')) {
            $attempt['seconds_spent'] = $questionDuration;
        }

        // Check if attempt exists.
        $sql = "SELECT exe_id FROM $TBL_TRACK_ATTEMPT
                WHERE
                    c_id = $course_id AND
                    session_id = $session_id AND
                    exe_id = $exe_id AND
                    user_id = $user_id AND
                    question_id = $question_id AND
                    position = $position";
        $result = Database::query($sql);
        $attemptData = [];
        if (Database::num_rows($result)) {
            $attemptData = Database::fetch_array($result, 'ASSOC');
            if ($updateResults == false) {
                // The attempt already exist do not update use  update_event_exercise() instead
                return false;
            }
        } else {
            $attempt['exe_id'] = $exe_id;
        }

        if ($debug) {
            error_log("updateResults : $updateResults");
            error_log("Saving question attempt: ");
            error_log($sql);
        }

        $recording_table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT_RECORDING);
        if ($updateResults == false) {
            $attempt_id = Database::insert($TBL_TRACK_ATTEMPT, $attempt);
            if ($recording) {
                $attempt_recording = [
                    'exe_id' => $exe_id,
                    'question_id' => $question_id,
                    'answer' => $answer,
                    'marks' => $score,
                    'insert_date' => $now,
                    'session_id' => $session_id,
                ];
                Database::insert($recording_table, $attempt_recording);
            }
        } else {
            if (api_get_configuration_value('allow_time_per_question')) {
                $attempt['seconds_spent'] = $questionDuration + (int) $attemptData['seconds_spent'];
            }
            Database::update(
                $TBL_TRACK_ATTEMPT,
                $attempt,
                [
                    'exe_id = ? AND question_id = ? AND user_id = ? ' => [
                        $exe_id,
                        $question_id,
                        $user_id,
                    ],
                ]
            );

            if ($recording) {
                $attempt_recording = [
                    'exe_id' => $exe_id,
                    'question_id' => $question_id,
                    'answer' => $answer,
                    'marks' => $score,
                    'insert_date' => $now,
                    'session_id' => $session_id,
                ];

                Database::update(
                    $recording_table,
                    $attempt_recording,
                    [
                        'exe_id = ? AND question_id = ? AND session_id = ? ' => [
                            $exe_id,
                            $question_id,
                            $session_id,
                        ],
                    ]
                );
            }
            $attempt_id = $exe_id;
        }

        return $attempt_id;
    }

    /**
     * Record an hotspot spot for this attempt at answering an hotspot question.
     *
     * @param int    $exeId
     * @param int    $questionId    Question ID
     * @param int    $answerId      Answer ID
     * @param int    $correct
     * @param string $coords        Coordinates of this point (e.g. 123;324)
     * @param bool   $updateResults
     * @param int    $exerciseId
     *
     * @return bool Result of the insert query
     *
     * @uses \Course code and user_id from global scope $_cid and $_user
     */
    public static function saveExerciseAttemptHotspot(
        $exeId,
        $questionId,
        $answerId,
        $correct,
        $coords,
        $updateResults = false,
        $exerciseId = 0,
        $lpId = 0,
        $lpItemId = 0
    ) {
        $debug = false;

        if ($updateResults == false) {
            // Validation in case of fraud with activated control time
            if (!ExerciseLib::exercise_time_control_is_valid($exerciseId, $lpId, $lpItemId)) {
                if ($debug) {
                    error_log('Attempt is fraud');
                }
                $correct = 0;
            }
        }

        if (empty($exeId)) {
            if ($debug) {
                error_log('exe id is empty');
            }

            return false;
        }

        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_HOTSPOT);
        if ($updateResults) {
            if ($debug) {
                error_log("Insert hotspot results: exeId: $exeId correct: $correct");
            }
            $params = [
                'hotspot_correct' => $correct,
                'hotspot_coordinate' => $coords,
            ];
            Database::update(
                $table,
                $params,
                [
                    'hotspot_user_id = ? AND hotspot_exe_id = ? AND hotspot_question_id = ? AND hotspot_answer_id = ? ' => [
                        api_get_user_id(),
                        $exeId,
                        $questionId,
                        $answerId,
                    ],
                ]
            );
        } else {
            if ($debug) {
                error_log("Insert hotspot results: exeId: $exeId correct: $correct");
            }

            return Database::insert(
                $table,
                [
                    'hotspot_course_code' => api_get_course_id(),
                    'hotspot_user_id' => api_get_user_id(),
                    'c_id' => api_get_course_int_id(),
                    'hotspot_exe_id' => $exeId,
                    'hotspot_question_id' => $questionId,
                    'hotspot_answer_id' => $answerId,
                    'hotspot_correct' => $correct,
                    'hotspot_coordinate' => $coords,
                ]
            );
        }
    }

    /**
     * Records information for common (or admin) events (in the track_e_default table).
     *
     * @author Yannick Warnier <yannick.warnier@beeznest.com>
     *
     * @param string $event_type       Type of event
     * @param string $event_value_type Type of value
     * @param mixed  $event_value      Value (string, or array in the case of user info)
     * @param string $datetime         Datetime (UTC) (defaults to null)
     * @param int    $user_id          User ID (defaults to null)
     * @param int    $course_id        Course ID (defaults to null)
     * @param int    $sessionId        Session ID
     *
     * @return bool
     * @assert ('','','') === false
     */
    public static function addEvent(
        $event_type,
        $event_value_type,
        $event_value,
        $datetime = null,
        $user_id = null,
        $course_id = null,
        $sessionId = 0
    ) {
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_DEFAULT);

        if (empty($event_type)) {
            return false;
        }
        $event_type = Database::escape_string($event_type);
        $event_value_type = Database::escape_string($event_value_type);
        if (!empty($course_id)) {
            $course_id = (int) $course_id;
        } else {
            $course_id = api_get_course_int_id();
        }
        if (!empty($sessionId)) {
            $sessionId = (int) $sessionId;
        } else {
            $sessionId = api_get_session_id();
        }

        //Clean the user_info
        if ($event_value_type == LOG_USER_OBJECT) {
            if (is_array($event_value)) {
                unset($event_value['complete_name']);
                unset($event_value['complete_name_with_username']);
                unset($event_value['firstName']);
                unset($event_value['lastName']);
                unset($event_value['avatar_small']);
                unset($event_value['avatar']);
                unset($event_value['mail']);
                unset($event_value['password']);
                unset($event_value['last_login']);
                unset($event_value['picture_uri']);
                $event_value = serialize($event_value);
            }
        }
        // If event is an array then the $event_value_type should finish with
        // the suffix _array for example LOG_WORK_DATA = work_data_array
        if (is_array($event_value)) {
            $event_value = serialize($event_value);
        }

        $event_value = Database::escape_string($event_value);
        $sessionId = empty($sessionId) ? api_get_session_id() : (int) $sessionId;

        if (!isset($datetime)) {
            $datetime = api_get_utc_datetime();
        }

        $datetime = Database::escape_string($datetime);

        if (!isset($user_id)) {
            $user_id = api_get_user_id();
        }

        $params = [
            'default_user_id' => $user_id,
            'c_id' => $course_id,
            'default_date' => $datetime,
            'default_event_type' => $event_type,
            'default_value_type' => $event_value_type,
            'default_value' => $event_value,
            'session_id' => $sessionId,
        ];
        Database::insert($table, $params);

        return true;
    }

    public static function findUserSubscriptionToCourse(int $userId, int $courseId, int $sessionId = 0)
    {
        $tblTrackEDefault = Database::get_main_table(TABLE_STATISTIC_TRACK_E_DEFAULT);

        return Database::select(
            '*',
            $tblTrackEDefault,
            [
                'where' => [
                    'default_event_type = ? AND ' => LOG_SUBSCRIBE_USER_TO_COURSE,
                    'default_value_type = ? AND ' => LOG_USER_OBJECT,
                    'default_value LIKE ? AND ' => '%s:2:\\\\"id\\\\";i:'.$userId.'%',
                    'c_id = ? AND ' => $courseId,
                    'session_id = ?' => $sessionId,
                ],
            ],
            'first'
        );
    }

    /**
     * Get every email stored in the database.
     *
     * @deprecated
     *
     * @return array
     * @assert () !== false
     */
    public static function get_all_event_types()
    {
        global $event_config;

        $sql = 'SELECT etm.id, event_type_name, activated, language_id, message, subject, dokeos_folder
                FROM '.Database::get_main_table(TABLE_EVENT_EMAIL_TEMPLATE).' etm
                INNER JOIN '.Database::get_main_table(TABLE_MAIN_LANGUAGE).' l
                ON etm.language_id = l.id';

        $events_types = Database::store_result(Database::query($sql), 'ASSOC');

        $to_return = [];
        foreach ($events_types as $et) {
            $et['nameLangVar'] = $event_config[$et["event_type_name"]]["name_lang_var"];
            $et['descLangVar'] = $event_config[$et["event_type_name"]]["desc_lang_var"];
            $to_return[] = $et;
        }

        return $to_return;
    }

    /**
     * Get the users related to one event.
     *
     * @param string $event_name
     *
     * @return string
     */
    public static function get_event_users($event_name)
    {
        $event_name = Database::escape_string($event_name);
        $sql = 'SELECT user.user_id,  user.firstname, user.lastname
                FROM '.Database::get_main_table(TABLE_MAIN_USER).' user
                JOIN '.Database::get_main_table(TABLE_EVENT_TYPE_REL_USER).' relUser
                ON relUser.user_id = user.user_id
                WHERE user.status <> '.ANONYMOUS.' AND relUser.event_type_name = "'.$event_name.'"';
        $user_list = Database::store_result(Database::query($sql), 'ASSOC');

        return json_encode($user_list);
    }

    /**
     * @param int    $user_id
     * @param string $event_type
     *
     * @return array|bool
     */
    public static function get_events_by_user_and_type($user_id, $event_type)
    {
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_DEFAULT);
        $user_id = (int) $user_id;
        $event_type = Database::escape_string($event_type);

        $sql = "SELECT * FROM $table
                WHERE default_value_type = 'user_id' AND
                      default_value = $user_id AND
                      default_event_type = '$event_type'
                ORDER BY default_date ";
        $result = Database::query($sql);
        if ($result) {
            return Database::store_result($result, 'ASSOC');
        }

        return false;
    }

    /**
     * Save the new message for one event and for one language.
     *
     * @param string $event_name
     * @param array  $users
     * @param string $message
     * @param string $subject
     * @param string $event_message_language
     * @param int    $activated
     */
    public static function save_event_type_message(
        $event_name,
        $users,
        $message,
        $subject,
        $event_message_language,
        $activated
    ) {
        $event_name = Database::escape_string($event_name);
        $activated = (int) $activated;
        $event_message_language = Database::escape_string($event_message_language);

        // Deletes then re-adds the users linked to the event
        $sql = 'DELETE FROM '.Database::get_main_table(TABLE_EVENT_TYPE_REL_USER).'
                WHERE event_type_name = "'.$event_name.'"	';
        Database::query($sql);

        $eventTable = Database::get_main_table(TABLE_EVENT_TYPE_REL_USER);

        foreach ($users as $user) {
            $user = (int) $user;
            $sql = "INSERT INTO $eventTable (user_id,event_type_name)
                    VALUES($user,'$event_name')";
            Database::query($sql);
        }
        $language_id = api_get_language_id($event_message_language);
        // check if this template in this language already exists or not
        $eventMailTable = Database::get_main_table(TABLE_EVENT_EMAIL_TEMPLATE);
        $sql = "SELECT COUNT(id) as total
                FROM $eventMailTable
                WHERE event_type_name = '$event_name' AND language_id = $language_id";

        $sql = Database::store_result(Database::query($sql), 'ASSOC');

        $languageTable = Database::get_main_table(TABLE_MAIN_LANGUAGE);
        $message = Database::escape_string($message);
        $subject = Database::escape_string($subject);
        // if already exists, we update
        if ($sql[0]["total"] > 0) {
            $sql = "UPDATE $eventMailTable
                SET message = '$message',
                subject = '$subject',
                activated = $activated
                WHERE event_type_name = '$event_name' AND language_id = (
                    SELECT id FROM $languageTable
                    WHERE dokeos_folder = '$event_message_language'
                )";
            Database::query($sql);
        } else { // else we create a new record
            // gets the language_-_id
            $lang_id = "(SELECT id FROM $languageTable
                        WHERE dokeos_folder = '$event_message_language')";
            $lang_id = Database::store_result(Database::query($lang_id), 'ASSOC');
            $lang_id = $lang_id[0]['id'];

            if (!empty($lang_id[0]["id"])) {
                $sql = "INSERT INTO $eventMailTable (event_type_name, language_id, message, subject, activated)
                    VALUES ('$event_name', $lang_id, '$message', '$subject', $activated)";
                Database::query($sql);
            }
        }

        // set activated at every save
        $sql = "UPDATE $eventMailTable
                SET activated = $activated
                WHERE event_type_name = '$event_name'";
        Database::query($sql);
    }

    /**
     * Gets the last attempt of an exercise based in the exe_id.
     *
     * @param int $exeId
     *
     * @return mixed
     */
    public static function getLastAttemptDateOfExercise($exeId)
    {
        $exeId = (int) $exeId;
        $track_attempts = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
        $sql = "SELECT max(tms) as last_attempt_date
                FROM $track_attempts
                WHERE exe_id = $exeId";
        $rs_last_attempt = Database::query($sql);
        $row_last_attempt = Database::fetch_array($rs_last_attempt);
        $date = $row_last_attempt['last_attempt_date']; //Get the date of last attempt

        return $date;
    }

    /**
     * Gets the last attempt of an exercise based in the exe_id.
     *
     * @param int $exeId
     *
     * @return mixed
     */
    public static function getLatestQuestionIdFromAttempt($exeId)
    {
        $exeId = (int) $exeId;
        $track_attempts = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
        $sql = "SELECT question_id FROM $track_attempts
                WHERE exe_id = $exeId
                ORDER BY tms DESC
                LIMIT 1";
        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            $row = Database::fetch_array($result);

            return $row['question_id'];
        }

        return false;
    }

    /**
     * Gets how many attempts exists by user, exercise, learning path.
     *
     * @param   int user id
     * @param   int exercise id
     * @param   int lp id
     * @param   int lp item id
     * @param   int lp item view id
     *
     * @return int
     */
    public static function get_attempt_count(
        $user_id,
        $exerciseId,
        $lp_id,
        $lp_item_id,
        $lp_item_view_id
    ) {
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $user_id = (int) $user_id;
        $exerciseId = (int) $exerciseId;
        $lp_id = (int) $lp_id;
        $lp_item_id = (int) $lp_item_id;
        $lp_item_view_id = (int) $lp_item_view_id;
        $courseId = api_get_course_int_id();
        $sessionId = api_get_session_id();

        $sql = "SELECT count(*) as count
                FROM $table
                WHERE
                    exe_exo_id = $exerciseId AND
                    exe_user_id = $user_id AND
                    status != 'incomplete' AND
                    orig_lp_id = $lp_id AND
                    orig_lp_item_id = $lp_item_id AND
                    orig_lp_item_view_id = $lp_item_view_id AND
                    c_id = $courseId AND
                    session_id = $sessionId";

        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            $attempt = Database::fetch_array($result, 'ASSOC');

            return (int) $attempt['count'];
        }

        return 0;
    }

    public static function getAttemptPosition(
        $exeId,
        $user_id,
        $exerciseId,
        $lp_id,
        $lp_item_id,
        $lp_item_view_id
    ) {
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $user_id = (int) $user_id;
        $exerciseId = (int) $exerciseId;
        $lp_id = (int) $lp_id;
        $lp_item_id = (int) $lp_item_id;
        $lp_item_view_id = (int) $lp_item_view_id;
        $courseId = api_get_course_int_id();
        $sessionId = api_get_session_id();

        $sql = "SELECT exe_id
                FROM $table
                WHERE
                    exe_exo_id = $exerciseId AND
                    exe_user_id = $user_id AND
                    status = '' AND
                    orig_lp_id = $lp_id AND
                    orig_lp_item_id = $lp_item_id AND
                    orig_lp_item_view_id = $lp_item_view_id AND
                    c_id = $courseId AND
                    session_id = $sessionId
                ORDER by exe_id
                ";

        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            $position = 1;
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                if ($row['exe_id'] === $exeId) {
                    break;
                }
                $position++;
            }

            return $position;
        }

        return 0;
    }

    /**
     * @param $user_id
     * @param $exerciseId
     * @param $lp_id
     * @param $lp_item_id
     *
     * @return int
     */
    public static function get_attempt_count_not_finished(
        $user_id,
        $exerciseId,
        $lp_id,
        $lp_item_id
    ) {
        $stat_table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $user_id = (int) $user_id;
        $exerciseId = (int) $exerciseId;
        $lp_id = (int) $lp_id;
        $lp_item_id = (int) $lp_item_id;
        //$lp_item_view_id = (int) $lp_item_view_id;
        $courseId = api_get_course_int_id();
        $sessionId = api_get_session_id();

        $sql = "SELECT count(*) as count
                FROM $stat_table
                WHERE
                    exe_exo_id 			= $exerciseId AND
                    exe_user_id 		= $user_id AND
                    status 				!= 'incomplete' AND
                    orig_lp_id 			= $lp_id AND
                    orig_lp_item_id 	= $lp_item_id AND
                    c_id = $courseId AND
                    session_id = $sessionId";

        $query = Database::query($sql);
        if (Database::num_rows($query) > 0) {
            $attempt = Database::fetch_array($query, 'ASSOC');

            return (int) $attempt['count'];
        }

        return 0;
    }

    /**
     * @param int   $user_id
     * @param int   $lp_id
     * @param array $course
     * @param int   $session_id
     * @param bool  $disconnectExerciseResultsFromLp (Replace orig_lp_* variables to null)
     *
     * @return bool
     */
    public static function delete_student_lp_events(
        $user_id,
        $lp_id,
        $course,
        $session_id,
        $disconnectExerciseResultsFromLp = false
    ) {
        $lp_view_table = Database::get_course_table(TABLE_LP_VIEW);
        $lp_item_view_table = Database::get_course_table(TABLE_LP_ITEM_VIEW);
        $lpInteraction = Database::get_course_table(TABLE_LP_IV_INTERACTION);
        $lpObjective = Database::get_course_table(TABLE_LP_IV_OBJECTIVE);

        if (empty($course) || empty($user_id)) {
            return false;
        }

        $course_id = $course['real_id'];
        $user_id = (int) $user_id;
        $lp_id = (int) $lp_id;
        $session_id = (int) $session_id;

        if (empty($course_id)) {
            $course_id = api_get_course_int_id();
        }

        $track_e_exercises = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $track_attempts = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
        $recording_table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT_RECORDING);

        // Make sure we have the exact lp_view_id
        $sql = "SELECT id FROM $lp_view_table
                WHERE
                    c_id = $course_id AND
                    user_id = $user_id AND
                    lp_id = $lp_id AND
                    session_id = $session_id";
        $result = Database::query($sql);

        if (Database::num_rows($result)) {
            $view = Database::fetch_array($result, 'ASSOC');
            $lp_view_id = $view['id'];

            $sql = "DELETE FROM $lp_item_view_table
                    WHERE c_id = $course_id AND lp_view_id = $lp_view_id";
            Database::query($sql);

            $sql = "DELETE FROM $lpInteraction
                    WHERE c_id = $course_id AND lp_iv_id = $lp_view_id";
            Database::query($sql);

            $sql = "DELETE FROM $lpObjective
                    WHERE c_id = $course_id AND lp_iv_id = $lp_view_id";
            Database::query($sql);
        }

        if (api_get_configuration_value('lp_minimum_time')) {
            $sql = "DELETE FROM track_e_access_complete
                    WHERE
                        tool = 'learnpath' AND
                        c_id = $course_id AND
                        tool_id = $lp_id AND
                        user_id = $user_id AND
                        session_id = $session_id
                    ";
            Database::query($sql);
        }

        $sql = "SELECT exe_id FROM $track_e_exercises
                WHERE
                    exe_user_id = $user_id AND
                    session_id = $session_id AND
                    c_id = $course_id AND
                    orig_lp_id = $lp_id";
        $result = Database::query($sql);
        $exeList = [];
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            $exeList[] = $row['exe_id'];
        }

        if (!empty($exeList) && count($exeList) > 0) {
            $exeListString = implode(',', $exeList);
            if ($disconnectExerciseResultsFromLp) {
                $sql = "UPDATE $track_e_exercises
                        SET orig_lp_id = null,
                            orig_lp_item_id = null,
                            orig_lp_item_view_id = null
                        WHERE exe_id IN ($exeListString)";
                Database::query($sql);
            } else {
                $sql = "DELETE FROM $track_e_exercises
                        WHERE exe_id IN ($exeListString)";
                Database::query($sql);

                $sql = "DELETE FROM $track_attempts
                        WHERE exe_id IN ($exeListString)";
                Database::query($sql);

                $sql = "DELETE FROM $recording_table
                        WHERE exe_id IN ($exeListString)";
                Database::query($sql);
            }
        }

        $sql = "DELETE FROM $lp_view_table
                WHERE
                    c_id = $course_id AND
                    user_id = $user_id AND
                    lp_id= $lp_id AND
                    session_id = $session_id
            ";
        Database::query($sql);

        self::addEvent(
            LOG_LP_ATTEMPT_DELETE,
            LOG_LP_ID,
            $lp_id,
            null,
            null,
            $course_id,
            $session_id
        );

        return true;
    }

    /**
     * Delete all exercise attempts (included in LP or not).
     *
     * @param int user id
     * @param int exercise id
     * @param int $course_id
     * @param int session id
     */
    public static function delete_all_incomplete_attempts(
        $user_id,
        $exercise_id,
        $course_id,
        $session_id = 0
    ) {
        $user_id = (int) $user_id;
        $exercise_id = (int) $exercise_id;
        $course_id = (int) $course_id;
        $session_id = (int) $session_id;

        if (!empty($user_id) && !empty($exercise_id) && !empty($course_id)) {
            $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
            $sql = "DELETE FROM $table
                    WHERE
                        exe_user_id = $user_id AND
                        exe_exo_id = $exercise_id AND
                        c_id = $course_id AND
                        session_id = $session_id AND
                        status = 'incomplete' ";
            Database::query($sql);
            self::addEvent(
                LOG_EXERCISE_RESULT_DELETE,
                LOG_EXERCISE_AND_USER_ID,
                $exercise_id.'-'.$user_id,
                null,
                null,
                $course_id,
                $session_id
            );
        }
    }

    /**
     * Gets all exercise results (NO Exercises in LPs ) from a given exercise id, course, session.
     *
     * @param int $exercise_id
     * @param int $courseId
     * @param int $session_id
     *
     * @return array with the results
     */
    public static function get_all_exercise_results(
        $exercise_id,
        $courseId,
        $session_id = 0,
        $load_question_list = true,
        $user_id = null,
        $groupId = 0,
        $skipLpResults = true
    ) {
        $TABLETRACK_EXERCICES = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $TBL_TRACK_ATTEMPT = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
        $courseId = (int) $courseId;
        $exercise_id = (int) $exercise_id;
        $session_id = (int) $session_id;
        $user_id = (int) $user_id;
        $groupId = (int) $groupId;

        $user_condition = null;
        if (!empty($user_id)) {
            $user_condition = "AND exe_user_id = $user_id ";
        }

        $courseInfo = api_get_course_info_by_id($courseId);
        if (empty($courseInfo)) {
            return [];
        }

        $groupCondition = '';
        if (!empty($groupId)) {
            $users = GroupManager::get_users($groupId, null, null, null, false, $courseId);
            // Check users added.
            if (!empty($users)) {
                $usersToString = implode("', '", $users);
                $groupCondition = " AND exe_user_id IN ('$usersToString') ";
            } else {
                // No users found in the group, then return an empty array.
                return [];
            }
        }

        $skipLpQuery = "";
        if ($skipLpResults) {
            $skipLpQuery = " AND orig_lp_id = 0 AND orig_lp_item_id = 0 ";
        }

        $sql = "SELECT * FROM $TABLETRACK_EXERCICES
                WHERE
                    status = '' AND
                    c_id = $courseId AND
                    exe_exo_id = $exercise_id AND
                    session_id = $session_id
                    $skipLpQuery
                    $user_condition
                    $groupCondition
                ORDER BY exe_id";
        $res = Database::query($sql);
        $list = [];
        while ($row = Database::fetch_array($res, 'ASSOC')) {
            $list[$row['exe_id']] = $row;
            if ($load_question_list) {
                $sql = "SELECT * FROM $TBL_TRACK_ATTEMPT
                        WHERE exe_id = {$row['exe_id']}";
                $res_question = Database::query($sql);
                while ($row_q = Database::fetch_array($res_question, 'ASSOC')) {
                    $list[$row['exe_id']]['question_list'][$row_q['question_id']] = $row_q;
                }
            }
        }

        return $list;
    }

    /**
     * Gets all exercise results (NO Exercises in LPs ) from a given exercise id, course, session.
     *
     * @param int  $courseId
     * @param int  $session_id
     * @param bool $get_count
     *
     * @return array with the results
     */
    public static function get_all_exercise_results_by_course(
        $courseId,
        $session_id = 0,
        $get_count = true
    ) {
        $table_track_exercises = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $courseId = (int) $courseId;
        $session_id = (int) $session_id;

        $select = '*';
        if ($get_count) {
            $select = 'count(*) as count';
        }
        $sql = "SELECT $select FROM $table_track_exercises
                WHERE   status = ''  AND
                        c_id = $courseId AND
                        session_id = $session_id  AND
                        orig_lp_id = 0 AND
                        orig_lp_item_id = 0
                ORDER BY exe_id";
        $res = Database::query($sql);
        if ($get_count) {
            $row = Database::fetch_array($res, 'ASSOC');

            return $row['count'];
        } else {
            $list = [];
            while ($row = Database::fetch_array($res, 'ASSOC')) {
                $list[$row['exe_id']] = $row;
            }

            return $list;
        }
    }

    /**
     * Gets all exercise results (NO Exercises in LPs) from a given exercise id, course, session.
     *
     * @param int $user_id
     * @param int $courseId
     * @param int $session_id
     *
     * @return array with the results
     */
    public static function get_all_exercise_results_by_user(
        $user_id,
        $courseId,
        $session_id = 0
    ) {
        $table_track_exercises = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $table_track_attempt = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
        $courseId = (int) $courseId;
        $session_id = (int) $session_id;
        $user_id = (int) $user_id;

        $sql = "SELECT * FROM $table_track_exercises
                WHERE
                    status = '' AND
                    exe_user_id = $user_id AND
                    c_id = $courseId AND
                    session_id = $session_id AND
                    orig_lp_id = 0 AND
                    orig_lp_item_id = 0
                ORDER by exe_id";

        $res = Database::query($sql);
        $list = [];
        while ($row = Database::fetch_array($res, 'ASSOC')) {
            $list[$row['exe_id']] = $row;
            $sql = "SELECT * FROM $table_track_attempt
                    WHERE exe_id = {$row['exe_id']}";
            $res_question = Database::query($sql);
            while ($row_q = Database::fetch_array($res_question, 'ASSOC')) {
                $list[$row['exe_id']]['question_list'][$row_q['question_id']] = $row_q;
            }
        }

        return $list;
    }

    /**
     * Gets exercise results (NO Exercises in LPs) from a given exercise id, course, session.
     *
     * @param int    $exe_id attempt id
     * @param string $status
     *
     * @return array with the results
     */
    public static function get_exercise_results_by_attempt($exe_id, $status = null)
    {
        $table_track_exercises = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $table_track_attempt = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
        $table_track_attempt_recording = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT_RECORDING);
        $exe_id = (int) $exe_id;

        $status = Database::escape_string($status);

        $sql = "SELECT * FROM $table_track_exercises
                WHERE status = '$status' AND exe_id = $exe_id";

        $res = Database::query($sql);
        $list = [];
        if (Database::num_rows($res)) {
            $row = Database::fetch_array($res, 'ASSOC');

            //Checking if this attempt was revised by a teacher
            $sql_revised = "SELECT exe_id FROM $table_track_attempt_recording
                            WHERE author != '' AND exe_id = $exe_id
                            LIMIT 1";
            $res_revised = Database::query($sql_revised);
            $row['attempt_revised'] = 0;
            if (Database::num_rows($res_revised) > 0) {
                $row['attempt_revised'] = 1;
            }
            $list[$exe_id] = $row;
            $sql = "SELECT * FROM $table_track_attempt
                    WHERE exe_id = $exe_id
                    ORDER BY tms ASC";
            $res_question = Database::query($sql);
            while ($row_q = Database::fetch_array($res_question, 'ASSOC')) {
                $list[$exe_id]['question_list'][$row_q['question_id']] = $row_q;
            }
        }

        return $list;
    }

    /**
     * Gets exercise results (NO Exercises in LPs) from a given user, exercise id, course, session, lp_id, lp_item_id.
     *
     * @param   int     user id
     * @param   int     exercise id
     * @param   int     course id
     * @param   int     session id
     * @param   int     lp id
     * @param   int     lp item id
     * @param   string order asc or desc
     *
     * @return array with the results
     */
    public static function getExerciseResultsByUser(
        $user_id,
        $exercise_id,
        $courseId,
        $session_id = 0,
        $lp_id = 0,
        $lp_item_id = 0,
        $order = null
    ) {
        $table_track_exercises = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $table_track_attempt = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
        $table_track_attempt_recording = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT_RECORDING);
        $courseId = (int) $courseId;
        $exercise_id = (int) $exercise_id;
        $session_id = (int) $session_id;
        $user_id = (int) $user_id;
        $lp_id = (int) $lp_id;
        $lp_item_id = (int) $lp_item_id;

        if (!in_array(strtolower($order), ['asc', 'desc'])) {
            $order = 'asc';
        }

        $sql = "SELECT * FROM $table_track_exercises
                WHERE
                    status 			= '' AND
                    exe_user_id 	= $user_id AND
                    c_id 	        = $courseId AND
                    exe_exo_id 		= $exercise_id AND
                    session_id 		= $session_id AND
                    orig_lp_id 		= $lp_id AND
                    orig_lp_item_id = $lp_item_id
                ORDER by exe_id $order ";

        $res = Database::query($sql);
        $list = [];
        while ($row = Database::fetch_array($res, 'ASSOC')) {
            // Checking if this attempt was revised by a teacher
            $exeId = $row['exe_id'];
            $sql = "SELECT exe_id FROM $table_track_attempt_recording
                    WHERE author != '' AND exe_id = $exeId
                    LIMIT 1";
            $res_revised = Database::query($sql);
            $row['attempt_revised'] = 0;
            if (Database::num_rows($res_revised) > 0) {
                $row['attempt_revised'] = 1;
            }
            $row['total_percentage'] = 0;
            if (!empty($row['exe_weighting'])) {
                $row['total_percentage'] = ($row['exe_result'] / $row['exe_weighting']) * 100;
            }

            $list[$row['exe_id']] = $row;
            $sql = "SELECT * FROM $table_track_attempt
                    WHERE exe_id = $exeId";
            $res_question = Database::query($sql);
            while ($row_q = Database::fetch_array($res_question, 'ASSOC')) {
                $list[$row['exe_id']]['question_list'][$row_q['question_id']][] = $row_q;
            }
        }

        return $list;
    }

    /**
     * Count exercise attempts (NO Exercises in LPs ) from a given exercise id, course, session.
     *
     * @param int $user_id
     * @param int $exercise_id
     * @param int $courseId
     * @param int $session_id
     *
     * @return array with the results
     */
    public static function count_exercise_attempts_by_user(
        $user_id,
        $exercise_id,
        $courseId,
        $session_id = 0,
        $skipLpResults = true
    ) {
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $courseId = (int) $courseId;
        $exercise_id = (int) $exercise_id;
        $session_id = (int) $session_id;
        $user_id = (int) $user_id;

        $skipLpQuery = "";
        if ($skipLpResults) {
            $skipLpQuery = " AND orig_lp_id = 0 AND orig_lp_item_id = 0 ";
        }

        $sql = "SELECT count(*) as count
                FROM $table
                WHERE status = ''  AND
                    exe_user_id = $user_id AND
                    c_id = $courseId AND
                    exe_exo_id = $exercise_id AND
                    session_id = $session_id
                    $skipLpQuery
                ORDER BY exe_id";
        $res = Database::query($sql);
        $result = 0;
        if (Database::num_rows($res) > 0) {
            $row = Database::fetch_array($res, 'ASSOC');
            $result = $row['count'];
        }

        return $result;
    }

    /**
     * Gets all exercise BEST results attempts (NO Exercises in LPs)
     * from a given exercise id, course, session per user.
     *
     * @param int $exercise_id
     * @param int $courseId
     * @param int $session_id
     * @param int $userId
     *
     * @return array with the results
     *
     * @todo rename this function
     */
    public static function get_best_exercise_results_by_user(
        $exercise_id,
        $courseId,
        $session_id = 0,
        $userId = 0,
        $skipLpResults = true
    ) {
        $table_track_exercises = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $table_track_attempt = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
        $courseId = (int) $courseId;
        $exercise_id = (int) $exercise_id;
        $session_id = (int) $session_id;

        $skipLpQuery = "";
        if ($skipLpResults) {
            $skipLpQuery = " AND orig_lp_id = 0 AND orig_lp_item_id = 0 ";
        }

        $sql = "SELECT * FROM $table_track_exercises
                WHERE
                    status = '' AND
                    c_id = $courseId AND
                    exe_exo_id = $exercise_id AND
                    session_id = $session_id
                    $skipLpQuery";

        if (!empty($userId)) {
            $userId = (int) $userId;
            $sql .= " AND exe_user_id = $userId ";
        }
        $sql .= ' ORDER BY exe_id';

        $res = Database::query($sql);
        $list = [];
        while ($row = Database::fetch_array($res, 'ASSOC')) {
            $list[$row['exe_id']] = $row;
            $exeId = $row['exe_id'];
            $sql = "SELECT * FROM $table_track_attempt
                    WHERE exe_id = $exeId";
            $res_question = Database::query($sql);
            while ($row_q = Database::fetch_array($res_question, 'ASSOC')) {
                $list[$exeId]['question_list'][$row_q['question_id']] = $row_q;
            }
        }

        // Getting the best results of every student
        $best_score_return = [];
        foreach ($list as $student_result) {
            $user_id = $student_result['exe_user_id'];
            $current_best_score[$user_id] = $student_result['exe_result'];
            if (!isset($best_score_return[$user_id]['exe_result'])) {
                $best_score_return[$user_id] = $student_result;
            }

            if ($current_best_score[$user_id] > $best_score_return[$user_id]['exe_result']) {
                $best_score_return[$user_id] = $student_result;
            }
        }

        return $best_score_return;
    }

    /**
     * Get the last best result from all attempts in exercises per user (out of learning paths).
     *
     * @param int  $user_id
     * @param int  $exercise_id
     * @param int  $courseId
     * @param int  $session_id
     * @param bool $skipLpResults
     *
     * @return array
     */
    public static function get_best_attempt_exercise_results_per_user(
        $user_id,
        $exercise_id,
        $courseId,
        $session_id = 0,
        $skipLpResults = true
    ) {
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $courseId = (int) $courseId;
        $exercise_id = (int) $exercise_id;
        $session_id = (int) $session_id;
        $user_id = (int) $user_id;

        $sql = "SELECT * FROM $table
                WHERE
                    status = ''  AND
                    c_id = $courseId AND
                    exe_exo_id = $exercise_id AND
                    session_id = $session_id  AND
                    exe_user_id = $user_id
                ";

        if ($skipLpResults) {
            $sql .= ' AND
                orig_lp_id = 0 AND
                orig_lp_item_id = 0 ';
        }

        $sql .= ' ORDER BY exe_id ';

        $res = Database::query($sql);
        $list = [];
        while ($row = Database::fetch_array($res, 'ASSOC')) {
            $list[$row['exe_id']] = $row;
        }
        // Getting the best results of every student.
        $best_score_return = [];
        $best_score_return['exe_result'] = 0;

        foreach ($list as $result) {
            $current_best_score = $result;
            if ($current_best_score['exe_result'] > $best_score_return['exe_result']) {
                $best_score_return = $result;
            }
        }
        if (!isset($best_score_return['exe_weighting'])) {
            $best_score_return = [];
        }

        return $best_score_return;
    }

    /**
     * @param int $exercise_id
     * @param int $courseId
     * @param int $session_id
     *
     * @return mixed
     */
    public static function count_exercise_result_not_validated(
        $exercise_id,
        $courseId,
        $session_id = 0
    ) {
        $table_track_exercises = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $table_track_attempt = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT_RECORDING);
        $courseId = (int) $courseId;
        $session_id = (int) $session_id;
        $exercise_id = (int) $exercise_id;

        $sql = "SELECT count(e.exe_id) as count
                FROM $table_track_exercises e
                LEFT JOIN $table_track_attempt a
                ON e.exe_id = a.exe_id
                WHERE
                    exe_exo_id = $exercise_id AND
                    c_id = $courseId AND
                    e.session_id = $session_id  AND
                    orig_lp_id = 0 AND
                    marks IS NULL AND
                    status = '' AND
                    orig_lp_item_id = 0
                ORDER BY e.exe_id";
        $res = Database::query($sql);
        $row = Database::fetch_array($res, 'ASSOC');

        return $row['count'];
    }

    /**
     * Gets all exercise events from a Learning Path within a Course    nd Session.
     *
     * @param int $exercise_id
     * @param int $courseId
     * @param int $session_id
     *
     * @return array
     */
    public static function get_all_exercise_event_from_lp(
        $exercise_id,
        $courseId,
        $session_id = 0
    ) {
        $table_track_exercises = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $table_track_attempt = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
        $courseId = (int) $courseId;
        $exercise_id = (int) $exercise_id;
        $session_id = (int) $session_id;

        $sql = "SELECT * FROM $table_track_exercises
                WHERE
                    status = '' AND
                    c_id = $courseId AND
                    exe_exo_id = $exercise_id AND
                    session_id = $session_id AND
                    orig_lp_id !=0 AND
                    orig_lp_item_id != 0";

        $res = Database::query($sql);
        $list = [];
        while ($row = Database::fetch_array($res, 'ASSOC')) {
            $exeId = $row['exe_id'];
            $list[$exeId] = $row;
            $sql = "SELECT * FROM $table_track_attempt
                    WHERE exe_id = $exeId";
            $res_question = Database::query($sql);
            while ($row_q = Database::fetch_array($res_question, 'ASSOC')) {
                $list[$exeId]['question_list'][$row_q['question_id']] = $row_q;
            }
        }

        return $list;
    }

    /**
     * Get a list of all the exercises in a given learning path.
     *
     * @param int $lp_id
     * @param int $course_id This parameter is probably deprecated as lp_id now is a global iid
     *
     * @return array
     */
    public static function get_all_exercises_from_lp($lp_id, $course_id)
    {
        $lp_item_table = Database::get_course_table(TABLE_LP_ITEM);
        $course_id = (int) $course_id;
        $lp_id = (int) $lp_id;
        $sql = "SELECT * FROM $lp_item_table
                WHERE
                    c_id = $course_id AND
                    lp_id = $lp_id AND
                    item_type = 'quiz'
                ORDER BY parent_item_id, display_order";
        $res = Database::query($sql);

        $list = [];
        while ($row = Database::fetch_array($res, 'ASSOC')) {
            $list[] = $row;
        }

        return $list;
    }

    /**
     * This function gets the comments of an exercise.
     *
     * @param int $exe_id
     * @param int $question_id
     *
     * @return string the comment
     */
    public static function get_comments($exe_id, $question_id)
    {
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
        $exe_id = (int) $exe_id;
        $question_id = (int) $question_id;
        $sql = "SELECT teacher_comment
                FROM $table
                WHERE
                    exe_id = $exe_id AND
                    question_id = $question_id
                ORDER by question_id";
        $sqlres = Database::query($sql);
        $comm = strval(Database::result($sqlres, 0, 'teacher_comment'));
        $comm = trim($comm);

        return $comm;
    }

    /**
     * Get all the track_e_attempt records for a given
     * track_e_exercises.exe_id (pk).
     *
     * @param int $exeId The exe_id from an exercise attempt record
     *
     * @return array The complete records from track_e_attempt that match the given exe_id
     */
    public static function getAllExerciseEventByExeId($exeId)
    {
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
        $exeId = (int) $exeId;

        $sql = "SELECT * FROM $table
                WHERE exe_id = $exeId
                ORDER BY position";
        $res_question = Database::query($sql);
        $list = [];
        if (Database::num_rows($res_question)) {
            while ($row = Database::fetch_array($res_question, 'ASSOC')) {
                $list[$row['question_id']][] = $row;
            }
        }

        return $list;
    }

    public static function getQuestionAttemptByExeIdAndQuestion($exeId, $questionId)
    {
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
        $exeId = (int) $exeId;
        $questionId = (int) $questionId;

        $sql = "SELECT * FROM $table
                WHERE
                    exe_id = $exeId AND
                    question_id = $questionId
                ORDER BY position";
        $result = Database::query($sql);
        $attempt = [];
        if (Database::num_rows($result)) {
            $attempt = Database::fetch_array($result, 'ASSOC');
        }

        return $attempt;
    }

    /**
     * Delete one record from the track_e_attempt table (recorded quiz answer)
     * and register the deletion event (LOG_QUESTION_RESULT_DELETE) in
     * track_e_default.
     *
     * @param int $exeId       The track_e_exercises.exe_id (primary key)
     * @param int $user_id     The user who answered (already contained in exe_id)
     * @param int $courseId    The course in which it happened (already contained in exe_id)
     * @param int $session_id  The session in which it happened (already contained in exe_id)
     * @param int $question_id The c_quiz_question.iid
     */
    public static function delete_attempt(
        $exeId,
        $user_id,
        $courseId,
        $session_id,
        $question_id
    ) {
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);

        $exeId = (int) $exeId;
        $user_id = (int) $user_id;
        $courseId = (int) $courseId;
        $session_id = (int) $session_id;
        $question_id = (int) $question_id;

        $sql = "DELETE FROM $table
                WHERE
                    exe_id = $exeId AND
                    user_id = $user_id AND
                    c_id = $courseId AND
                    session_id = $session_id AND
                    question_id = $question_id ";
        Database::query($sql);

        self::addEvent(
            LOG_QUESTION_RESULT_DELETE,
            LOG_EXERCISE_ATTEMPT_QUESTION_ID,
            $exeId.'-'.$question_id,
            null,
            null,
            $courseId,
            $session_id
        );
    }

    /**
     * Delete one record from the track_e_hotspot table based on a given
     * track_e_exercises.exe_id.
     *
     * @param $exeId
     * @param $user_id
     * @param int $courseId
     * @param $question_id
     * @param int $sessionId
     */
    public static function delete_attempt_hotspot(
        $exeId,
        $user_id,
        $courseId,
        $question_id,
        $sessionId = null
    ) {
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_HOTSPOT);

        $exeId = (int) $exeId;
        $user_id = (int) $user_id;
        $courseId = (int) $courseId;
        $question_id = (int) $question_id;
        if (!isset($sessionId)) {
            $sessionId = api_get_session_id();
        }

        $sql = "DELETE FROM $table
                WHERE
                    hotspot_exe_id = $exeId AND
                    hotspot_user_id = $user_id AND
                    c_id = $courseId AND
                    hotspot_question_id = $question_id ";
        Database::query($sql);
        self::addEvent(
            LOG_QUESTION_RESULT_DELETE,
            LOG_EXERCISE_ATTEMPT_QUESTION_ID,
            $exeId.'-'.$question_id,
            null,
            null,
            $courseId,
            $sessionId
        );
    }

    /**
     * Registers in track_e_course_access when user logs in for the first time to a course.
     *
     * @param int $courseId  ID of the course
     * @param int $user_id   ID of the user
     * @param int $sessionId ID of the session (if any)
     *
     * @return bool
     */
    public static function eventCourseLogin($courseId, $user_id, $sessionId)
    {
        if (Session::read('login_as')) {
            return false;
        }

        $sessionId = (int) $sessionId;
        if (self::isSessionLogNeedToBeSave($sessionId) === false) {
            return false;
        }

        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
        $loginDate = $logoutDate = api_get_utc_datetime();

        // $counter represents the number of time this record has been refreshed
        $counter = 1;
        $courseId = (int) $courseId;
        $user_id = (int) $user_id;
        $ip = Database::escape_string(api_get_real_ip());

        $sql = "INSERT INTO $table(c_id, user_ip, user_id, login_course_date, logout_course_date, counter, session_id)
                VALUES($courseId, '$ip', $user_id, '$loginDate', '$logoutDate', $counter, $sessionId)";
        $courseAccessId = Database::query($sql);

        if ($courseAccessId) {
            // Course catalog stats modifications see #4191
            CourseManager::update_course_ranking(
                null,
                null,
                null,
                null,
                true,
                false
            );

            return true;
        }
    }

    /**
     * Updates the user - course - session every X minutes
     * In order to avoid.
     *
     * @param int $courseId
     * @param int $userId
     * @param int $sessionId
     * @param int $minutes
     *
     * @return bool
     */
    public static function eventCourseLoginUpdate(
        $courseId,
        $userId,
        $sessionId,
        $minutes = 5
    ) {
        if (Session::read('login_as')) {
            return false;
        }

        if (empty($courseId) || empty($userId)) {
            return false;
        }

        $sessionId = (int) $sessionId;

        if (self::isSessionLogNeedToBeSave($sessionId) === false) {
            return false;
        }

        $courseId = (int) $courseId;
        $userId = (int) $userId;

        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
        $sql = "SELECT course_access_id, logout_course_date
                FROM $table
                WHERE
                    c_id = $courseId AND
                    session_id = $sessionId AND
                    user_id = $userId
                ORDER BY login_course_date DESC
                LIMIT 1";

        $result = Database::query($sql);

        // Save every 5 minutes by default
        $seconds = $minutes * 60;
        $maxSeconds = 3600; // Only update if max diff is one hour
        if (Database::num_rows($result)) {
            $row = Database::fetch_array($result);
            $id = $row['course_access_id'];
            $logout = $row['logout_course_date'];
            $now = time();
            $logout = api_strtotime($logout, 'UTC');
            if ($now - $logout > $seconds &&
                $now - $logout < $maxSeconds
            ) {
                $now = api_get_utc_datetime();
                $sql = "UPDATE $table SET
                            logout_course_date = '$now',
                            counter = counter + 1
                        WHERE course_access_id = $id";
                Database::query($sql);
            }

            return true;
        }

        return false;
    }

    /**
     * Register the logout of the course (usually when logging out of the platform)
     * from the track_e_course_access table.
     *
     * @param array $logoutInfo Information stored by local.inc.php
     *                          before new context ['uid'=> x, 'cid'=>y, 'sid'=>z]
     *
     * @return bool
     */
    public static function courseLogout($logoutInfo)
    {
        if (Session::read('login_as')) {
            return false;
        }

        if (empty($logoutInfo['uid']) || empty($logoutInfo['cid'])) {
            return false;
        }

        $sessionLifetime = api_get_configuration_value('session_lifetime');
        /*
         * When $_configuration['session_lifetime'] is larger than ~100 hours
         * (in order to let users take exercises with no problems)
         * the function Tracking::get_time_spent_on_the_course() returns larger values (200h) due the condition:
         * login_course_date > now() - INTERVAL $session_lifetime SECOND
         */
        if (empty($sessionLifetime) || $sessionLifetime > 86400) {
            $sessionLifetime = 3600; // 1 hour
        }
        if (!empty($logoutInfo) && !empty($logoutInfo['cid'])) {
            $sessionId = 0;
            if (!empty($logoutInfo['sid'])) {
                $sessionId = (int) $logoutInfo['sid'];
            }

            if (self::isSessionLogNeedToBeSave($sessionId) === false) {
                return false;
            }

            $tableCourseAccess = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
            $userId = (int) $logoutInfo['uid'];
            $courseId = (int) $logoutInfo['cid'];

            $currentDate = api_get_utc_datetime();
            // UTC time
            $diff = time() - $sessionLifetime;
            $time = api_get_utc_datetime($diff);
            $sql = "SELECT course_access_id, logout_course_date
                    FROM $tableCourseAccess
                    WHERE
                        user_id = $userId AND
                        c_id = $courseId  AND
                        session_id = $sessionId AND
                        login_course_date > '$time'
                    ORDER BY login_course_date DESC
                    LIMIT 1";
            $result = Database::query($sql);
            $insert = false;
            if (Database::num_rows($result) > 0) {
                $row = Database::fetch_array($result, 'ASSOC');
                $courseAccessId = $row['course_access_id'];
                $sql = "UPDATE $tableCourseAccess SET
                                logout_course_date = '$currentDate',
                                counter = counter + 1
                            WHERE course_access_id = $courseAccessId";
                Database::query($sql);
            } else {
                $insert = true;
            }

            if ($insert) {
                $ip = Database::escape_string(api_get_real_ip());
                $sql = "INSERT INTO $tableCourseAccess (c_id, user_ip, user_id, login_course_date, logout_course_date, counter, session_id)
                        VALUES ($courseId, '$ip', $userId, '$currentDate', '$currentDate', 1, $sessionId)";
                Database::query($sql);
            }

            return true;
        }
    }

    /**
     * Register a "fake" time spent on the platform, for example to match the
     * estimated time he took to author an assignment/work, see configuration
     * setting considered_working_time.
     * This assumes there is already some connection of the student to the
     * course, otherwise he wouldn't be able to upload an assignment.
     * This works by creating a new record, copy of the current one, then
     * updating the current one to be just the considered_working_time and
     * end at the same second as the user connected to the course.
     *
     * @param int    $courseId    The course in which to add the time
     * @param int    $userId      The user for whom to add the time
     * @param int    $sessionId   The session in which to add the time (if any)
     * @param string $virtualTime The amount of time to be added,
     *                            in a hh:mm:ss format. If int, we consider it is expressed in hours.
     * @param int    $workId      Student publication id result
     *
     * @return true on successful insertion, false otherwise
     */
    public static function eventAddVirtualCourseTime(
        $courseId,
        $userId,
        $sessionId,
        $virtualTime,
        $workId
    ) {
        if (empty($virtualTime)) {
            return false;
        }

        $courseId = (int) $courseId;
        $userId = (int) $userId;
        $sessionId = (int) $sessionId;

        $logoutDate = api_get_utc_datetime();
        $loginDate = ChamiloApi::addOrSubTimeToDateTime(
            $virtualTime,
            $logoutDate,
            false
        );

        $ip = api_get_real_ip();
        $params = [
            'login_course_date' => $loginDate,
            'logout_course_date' => $logoutDate,
            'session_id' => $sessionId,
            'user_id' => $userId,
            'counter' => 0,
            'c_id' => $courseId,
            'user_ip' => $ip,
        ];
        $courseTrackingTable = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
        Database::insert($courseTrackingTable, $params);

        // Time should also be added to the track_e_login table so as to
        // affect total time on the platform
        $params = [
            'login_user_id' => $userId,
            'login_date' => $loginDate,
            'user_ip' => $ip,
            'logout_date' => $logoutDate,
        ];
        $platformTrackingTable = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);
        Database::insert($platformTrackingTable, $params);

        if (Tracking::minimumTimeAvailable($sessionId, $courseId)) {
            $workId = (int) $workId;
            $uniqueId = time();
            $logInfo = [
                'c_id' => $courseId,
                'session_id' => $sessionId,
                'tool' => TOOL_STUDENTPUBLICATION,
                'date_reg' => $loginDate,
                'action' => 'add_work_start_'.$workId,
                'action_details' => $virtualTime,
                'user_id' => $userId,
                'current_id' => $uniqueId,
            ];
            self::registerLog($logInfo);

            $logInfo = [
                'c_id' => $courseId,
                'session_id' => $sessionId,
                'tool' => TOOL_STUDENTPUBLICATION,
                'date_reg' => $logoutDate,
                'action' => 'add_work_end_'.$workId,
                'action_details' => $virtualTime,
                'user_id' => $userId,
                'current_id' => $uniqueId,
            ];
            self::registerLog($logInfo);
        }

        return true;
    }

    /**
     * Removes a "fake" time spent on the platform, for example to match the
     * estimated time he took to author an assignment/work, see configuration
     * setting considered_working_time.
     * This method should be called when something that generated a fake
     * time record is removed. Given the database link is weak (no real
     * relationship kept between the deleted item and this record), this
     * method just looks for the latest record that has the same time as the
     * item's fake time, is in the past and in this course+session. If such a
     * record cannot be found, it doesn't do anything.
     * The IP address is not considered a useful filter here.
     *
     * @param int    $courseId    The course in which to add the time
     * @param int    $userId      The user for whom to add the time
     * @param int    $sessionId   The session in which to add the time (if any)
     * @param string $virtualTime The amount of time to be added, in a hh:mm:ss format. If int, we consider it is expressed in hours.
     *
     * @return true on successful removal, false otherwise
     */
    public static function eventRemoveVirtualCourseTime(
        $courseId,
        $userId,
        $sessionId,
        $virtualTime,
        $workId
    ) {
        if (empty($virtualTime)) {
            return false;
        }

        $courseId = (int) $courseId;
        $userId = (int) $userId;
        $sessionId = (int) $sessionId;
        $originalVirtualTime = Database::escape_string($virtualTime);
        $workId = (int) $workId;

        $courseTrackingTable = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
        $platformTrackingTable = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);

        // Change $virtualTime format from hh:mm:ss to hhmmss which is the
        // format returned by SQL for a subtraction of two datetime values
        // @todo make sure this is portable between DBMSes
        if (preg_match('/:/', $virtualTime)) {
            [$h, $m, $s] = preg_split('/:/', $virtualTime);
            $virtualTime = $h * 3600 + $m * 60 + $s;
        } else {
            $virtualTime *= 3600;
        }

        // Get the current latest course connection register. We need that
        // record to re-use the data and create a new record.
        $sql = "SELECT course_access_id
                FROM $courseTrackingTable
                WHERE
                    user_id = $userId AND
                    c_id = $courseId  AND
                    session_id  = $sessionId AND
                    counter = 0 AND
                    (UNIX_TIMESTAMP(logout_course_date) - UNIX_TIMESTAMP(login_course_date)) = '$virtualTime'
                ORDER BY login_course_date DESC LIMIT 0,1";
        $result = Database::query($sql);

        // Ignore if we didn't find any course connection record in the last
        // hour. In this case it wouldn't be right to add a "fake" time record.
        if (Database::num_rows($result) > 0) {
            // Found the latest connection
            $row = Database::fetch_row($result);
            $courseAccessId = $row[0];
            $sql = "DELETE FROM $courseTrackingTable
                    WHERE course_access_id = $courseAccessId";
            Database::query($sql);
        }
        $sql = "SELECT login_id
                FROM $platformTrackingTable
                WHERE
                    login_user_id = $userId AND
                    (UNIX_TIMESTAMP(logout_date) - UNIX_TIMESTAMP(login_date)) = '$virtualTime'
                ORDER BY login_date DESC LIMIT 0,1";
        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            // Found the latest connection
            $row = Database::fetch_row($result);
            $loginAccessId = $row[0];
            $sql = "DELETE FROM $platformTrackingTable
                    WHERE login_id = $loginAccessId";
            Database::query($sql);
        }

        if (Tracking::minimumTimeAvailable($sessionId, $courseId)) {
            $sql = "SELECT id FROM track_e_access_complete
                    WHERE
                        tool = '".TOOL_STUDENTPUBLICATION."' AND
                        c_id = $courseId AND
                        session_id = $sessionId AND
                        user_id = $userId AND
                        action_details = '$originalVirtualTime' AND
                        action = 'add_work_start_$workId' ";
            $result = Database::query($sql);
            $result = Database::fetch_array($result);
            if ($result) {
                $sql = 'DELETE FROM track_e_access_complete WHERE id = '.$result['id'];
                Database::query($sql);
            }

            $sql = "SELECT id FROM track_e_access_complete
                    WHERE
                        tool = '".TOOL_STUDENTPUBLICATION."' AND
                        c_id = $courseId AND
                        session_id = $sessionId AND
                        user_id = $userId AND
                        action_details = '$originalVirtualTime' AND
                        action = 'add_work_end_$workId' ";
            $result = Database::query($sql);
            $result = Database::fetch_array($result);
            if ($result) {
                $sql = 'DELETE FROM track_e_access_complete WHERE id = '.$result['id'];
                Database::query($sql);
            }
        }

        return false;
    }

    /**
     * For the sake of cohesion, this function is a switch.
     * It's called by EventsDispatcher and fires the right function
     * with the right require_once.
     *
     * @deprecated
     *
     * @param string $event_name
     * @param array  $params
     */
    public static function event_send_mail($event_name, $params)
    {
        EventsMail::send_mail($event_name, $params);
    }

    /**
     * Filter EventEmailTemplate Filters see the main/inc/conf/events.conf.dist.php.
     */

    /**
     * Basic template event message filter (to be used by other filters as default).
     *
     * @deprecated
     *
     * @param array $values (passing by reference)     *
     *
     * @return bool True if everything is OK, false otherwise
     */
    public function event_send_mail_filter_func(&$values)
    {
        return true;
    }

    /**
     * user_registration - send_mail filter.
     *
     * @deprecated
     *
     * @param array $values (passing by reference)
     *
     * @return bool True if everything is OK, false otherwise
     */
    public function user_registration_event_send_mail_filter_func(&$values)
    {
        $res = self::event_send_mail_filter_func($values);
        // proper logic for this filter
        return $res;
    }

    /**
     * portal_homepage_edited - send_mail filter.
     *
     * @deprecated
     *
     * @param array $values (passing by reference)
     *
     * @return bool True if everything is OK, false otherwise
     */
    public function portal_homepage_edited_event_send_mail_filter_func(&$values)
    {
        $res = self::event_send_mail_filter_func($values);
        // proper logic for this filter
        return $res;
    }

    /**
     * Register the logout of the course (usually when logging out of the platform)
     * from the track_e_access_complete table.
     *
     * @param array $logInfo Information stored by local.inc.php
     *
     * @return bool
     */
    public static function registerLog($logInfo)
    {
        $sessionId = api_get_session_id();
        $courseId = api_get_course_int_id();

        if (isset($logInfo['c_id']) && !empty($logInfo['c_id'])) {
            $courseId = $logInfo['c_id'];
        }

        if (isset($logInfo['session_id']) && !empty($logInfo['session_id'])) {
            $sessionId = $logInfo['session_id'];
        }

        if (!Tracking::minimumTimeAvailable($sessionId, $courseId)) {
            return false;
        }

        if (self::isSessionLogNeedToBeSave($sessionId) === false) {
            return false;
        }

        $loginAs = (int) Session::read('login_as') === true;

        $logInfo['user_id'] = isset($logInfo['user_id']) ? $logInfo['user_id'] : api_get_user_id();
        $logInfo['date_reg'] = isset($logInfo['date_reg']) ? $logInfo['date_reg'] : api_get_utc_datetime();
        $logInfo['tool'] = !empty($logInfo['tool']) ? $logInfo['tool'] : '';
        $logInfo['tool_id'] = !empty($logInfo['tool_id']) ? (int) $logInfo['tool_id'] : 0;
        $logInfo['tool_id_detail'] = !empty($logInfo['tool_id_detail']) ? (int) $logInfo['tool_id_detail'] : 0;
        $logInfo['action'] = !empty($logInfo['action']) ? $logInfo['action'] : '';
        $logInfo['action_details'] = !empty($logInfo['action_details']) ? $logInfo['action_details'] : '';
        $logInfo['ip_user'] = api_get_real_ip();
        $logInfo['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        $logInfo['session_id'] = $sessionId;
        $logInfo['c_id'] = $courseId;
        $logInfo['ch_sid'] = session_id();
        $logInfo['login_as'] = $loginAs;
        $logInfo['info'] = !empty($logInfo['info']) ? $logInfo['info'] : '';
        $logInfo['url'] = $_SERVER['REQUEST_URI'];
        $logInfo['current_id'] = isset($logInfo['current_id']) ? $logInfo['current_id'] : Session::read('last_id', 0);

        $id = Database::insert('track_e_access_complete', $logInfo);
        if ($id && empty($logInfo['current_id'])) {
            Session::write('last_id', $id);
        }

        return true;
    }

    public static function getAttemptQuestionDuration($exeId, $questionId)
    {
        // Check current attempt.
        $questionAttempt = self::getQuestionAttemptByExeIdAndQuestion($exeId, $questionId);
        $alreadySpent = 0;
        if (!empty($questionAttempt) && $questionAttempt['seconds_spent']) {
            $alreadySpent = $questionAttempt['seconds_spent'];
        }
        $now = time();
        $questionStart = Session::read('question_start', []);
        if (!empty($questionStart) &&
            isset($questionStart[$questionId]) && !empty($questionStart[$questionId])
        ) {
            $time = $questionStart[$questionId];
        } else {
            $diff = 0;
            if (!empty($alreadySpent)) {
                $diff = $alreadySpent;
            }
            $time = $questionStart[$questionId] = $now - $diff;
            Session::write('question_start', $questionStart);
        }

        return $now - $time;
    }

    public static function logSubscribedUserInCourse(int $subscribedId, int $courseId)
    {
        $dateTime = api_get_utc_datetime();
        $registrantId = api_get_user_id();

        self::addEvent(
            LOG_SUBSCRIBE_USER_TO_COURSE,
            LOG_COURSE_CODE,
            api_get_course_entity($courseId)->getCode(),
            $dateTime,
            $registrantId,
            $courseId
        );

        self::addEvent(
            LOG_SUBSCRIBE_USER_TO_COURSE,
            LOG_USER_OBJECT,
            api_get_user_info($subscribedId),
            $dateTime,
            $registrantId,
            $courseId
        );
    }

    public static function logUserSubscribedInCourseSession(int $subscribedId, int $courseId, int $sessionId)
    {
        $dateTime = api_get_utc_datetime();
        $registrantId = api_get_user_id();

        self::addEvent(
            LOG_SESSION_ADD_USER_COURSE,
            LOG_USER_ID,
            $subscribedId,
            $dateTime,
            $registrantId,
            $courseId,
            $sessionId
        );
        self::addEvent(
            LOG_SUBSCRIBE_USER_TO_COURSE,
            LOG_COURSE_CODE,
            api_get_course_entity($courseId)->getCode(),
            $dateTime,
            $registrantId,
            $courseId,
            $sessionId
        );
        self::addEvent(
            LOG_SUBSCRIBE_USER_TO_COURSE,
            LOG_USER_OBJECT,
            api_get_user_info($subscribedId),
            $dateTime,
            $registrantId,
            $courseId,
            $sessionId
        );
    }
}
