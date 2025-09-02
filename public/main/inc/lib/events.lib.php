<?php

/* See license terms in /license.txt */

use Chamilo\CoreBundle\Entity\Course as CourseEntity;
use Chamilo\CoreBundle\Entity\Session as SessionEntity;
use Chamilo\CoreBundle\Entity\TrackEAttemptQualify;
use Chamilo\CoreBundle\Entity\TrackEDefault;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Helpers\ChamiloHelper;
use ChamiloSession as Session;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;

/**
 * Class Event
 * Functions of this library are used to record information when some kind
 * of event occur. Each event has his own types of information then each event
 * use its own function.
 */
class Event
{
    /**
     * Record information for login event when a user identifies himself with username & password
     * @param int $userId
     *
     * @return bool
     *
     * @throws Exception
     * @author Julio Montoya
     * @author Sebastien Piraux <piraux_seb@hotmail.com> old code
     */
    public static function eventLogin(int $userId): bool
    {
        $userInfo = api_get_user_info($userId);

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

        $status = 'student';
        if (SESSIONADMIN == $userInfo['status']) {
            $status = 'sessionadmin';
        }
        if (COURSEMANAGER == $userInfo['status']) {
            $status = 'teacher';
        }
        if (DRH == $userInfo['status']) {
            $status = 'DRH';
        }

        // Auto subscribe
        $autoSubscribe = api_get_setting($status.'_autosubscribe');
        if ($autoSubscribe) {
            $autoSubscribe = explode('|', $autoSubscribe);
            foreach ($autoSubscribe as $code) {
                if (CourseManager::course_exists($code)) {
                    $courseInfo = api_get_course_info($code);
                    CourseManager::subscribeUser($userId, $courseInfo['real_id']);
                }
            }
        }

        return true;
    }

    /**
     * Check if we need to log a session access (based on visibility and extra field 'disable_log_after_session_ends')
     * @param int $sessionId
     *
     * @return bool
     */
    public static function isSessionLogNeedToBeSave(int $sessionId): bool
    {
        if (!empty($sessionId)) {
            $visibility = api_get_session_visibility($sessionId);
            if (!empty($visibility) && SESSION_AVAILABLE != $visibility) {
                $extraFieldValue = new ExtraFieldValue('session');
                $value = $extraFieldValue->get_values_by_handler_and_field_variable(
                    $sessionId,
                    'disable_log_after_session_ends'
                );
                if (!empty($value) && isset($value['value']) && 1 == (int) $value['value']) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Record information for access event for tools
     * @param string $tool name of the tool
     *
     * @return bool
     * @throws \Doctrine\DBAL\Exception
     * @throws \Doctrine\DBAL\Exception
     * @throws Exception
     * @author Sebastien Piraux <piraux_seb@hotmail.com>
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
     */
    public static function event_access_tool(string $tool): bool
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

        if (false === self::isSessionLogNeedToBeSave($sessionId)) {
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

        $pos = isset($_SERVER['HTTP_REFERER']) ? strpos(
            strtolower($_SERVER['HTTP_REFERER']),
            strtolower(api_get_path(WEB_COURSE_PATH).$coursePath)
        ) : false;
        // added for "what's new" notification
        $pos2 = isset($_SERVER['HTTP_REFERER']) ? strpos(
            strtolower($_SERVER['HTTP_REFERER']),
            strtolower(api_get_path(WEB_PATH)."index")
        ) : false;

        // end "what's new" notification
        if (false !== $pos || false !== $pos2) {
            $params = [
                'access_user_id' => $userId,
                'c_id' => $courseId,
                'access_tool' => $tool,
                'access_date' => $reallyNow,
                'session_id' => $sessionId,
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
                    session_id = $sessionId";
        $result = Database::query($sql);

        if (0 == Database::affected_rows($result)) {
            $params = [
                'access_user_id' => $userId,
                'c_id' => $courseId,
                'access_tool' => $tool,
                'access_date' => $reallyNow,
                'session_id' => $sessionId,
            ];
            Database::insert($tableLastAccess, $params);
        }

        return true;
    }

    /**
     * Record information for download event (when a user clicks to d/l a
     * document).
     *
     * @param string $documentUrl
     *
     * @return int
     *
     * @throws NotSupported
     * @author Evie Embrechts
     * @author Sebastien Piraux <piraux_seb@hotmail.com>
     */
    public static function event_download(string $documentUrl): int
    {
        if (Session::read('login_as')) {
            return 0;
        }

        $user = api_get_user_entity();
        $course = api_get_course_entity();

        return Container::getTrackEDownloadsRepository()
            ->saveDownload($user, $course->getFirstResourceLink(), $documentUrl)
        ;
    }

    /**
     * Record information of upload event.
     * Used in the works tool to record information when a user uploads 1 work.
     * @param int $documentId of document (id in mainDb.document table)
     *
     * @return int
     * @throws Exception
     * @author Sebastien Piraux <piraux_seb@hotmail.com>
     */
    public static function event_upload(int $documentId): int
    {
        if (Session::read('login_as')) {
            return 0;
        }

        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_UPLOADS);
        $courseId = api_get_course_int_id();
        $reallyNow = api_get_utc_datetime();
        $userId = api_get_user_id();
        $sessionId = api_get_session_id();

        $sql = "INSERT INTO $table
                ( upload_user_id,
                  c_id,
                  upload_work_id,
                  upload_date,
                  session_id
                )
                VALUES (
                 $userId,
                 $courseId,
                 $documentId,
                 '$reallyNow',
                 $sessionId
                )";
        Database::query($sql);

        return 1;
    }

    /**
     * Record information for link event (when a user clicks on an added link).
     *
     * @param int $linkId (id in c_link table)
     *
     * @return int
     *
     * @throws Exception
     * @author Sebastien Piraux <piraux_seb@hotmail.com>
     */
    public static function event_link(int $linkId): int
    {
        if (Session::read('login_as')) {
            return 0;
        }

        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LINKS);
        $reallyNow = api_get_utc_datetime();
        $userId = api_get_user_id();
        $courseId = api_get_course_int_id();
        $sessionId = api_get_session_id();
        $sql = "INSERT INTO ".$table."
                    ( links_user_id,
                     c_id,
                     links_link_id,
                     links_date,
                     session_id
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
     * @param float  $score
     * @param int    $weighting
     * @param int    $sessionId
     * @param ?int    $learnpathId
     * @param ?int    $learnpathItemId
     * @param ?int    $learnpathItemViewId
     * @param ?int    $duration
     * @param ?array  $questionsList
     * @param ?string $status
     * @param ?array  $remindList
     * @param ?string   $endDate
     *
     * @return bool
     *
     * @throws Exception
     * @author Sebastien Piraux <piraux_seb@hotmail.com>
     * @author Julio Montoya Armas <gugli100@gmail.com> Reworked 2010
     */
    public static function updateEventExercise(
        int $exeId,
        int $exoId,
        float $score,
        int $weighting,
        int $sessionId,
        ?int $learnpathId = 0,
        ?int $learnpathItemId = 0,
        ?int $learnpathItemViewId = 0,
        ?int $duration = 0,
        ?array $questionsList = [],
        ?string $status = '',
        ?array $remindList = [],
        ?string $endDate = null
    ):bool
    {
        if (empty($exeId)) {
            return false;
        }

        if (empty($status)) {
            $status = '';
        } else {
            $status = Database::escape_string($status);
        }

        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);

        if (!empty($questionsList)) {
            $questionsList = array_map('intval', $questionsList);
        }

        if (!empty($questionsList)) {
            $questionsList = array_map('intval', $questionsList);
            $questionsList = array_filter(
                $questionsList,
                function (int $qid) {
                    $q = Question::read($qid);
                    return $q && !in_array(
                            $q->type,
                            [PAGE_BREAK, MEDIA_QUESTION],
                            true
                        );
                }
            );
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
        $score = Database::escape_string($score);
        $weighting = Database::escape_string($weighting);
        $questions = implode(',', $questionsList);
        $userIp = Database::escape_string(api_get_real_ip());

        $sql = "UPDATE $table SET
               exe_exo_id = $exoId,
               score = '$score',
               max_score = '$weighting',
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

        return true;
    }

    /**
     * Record an event for this attempt at answering an exercise.
     * @param Exercise $exercise
     * @param float  $score Score achieved
     * @param string $answer Answer given
     * @param int    $question_id
     * @param int    $exe_id Exercise attempt ID a.k.a exe_id (from track_e_exercise)
     * @param int    $position
     * @param ?int    $exercise_id From c_quiz
     * @param ?bool   $updateResults
     * @param ?int    $questionDuration Time spent in seconds
     * @param ?string $fileName Filename (for audio answers - using nanogong)
     * @param ?int    $user_id The user who's going to get this score.
     * @param ?int    $course_id Default value of null means "get from context".
     * @param ?int    $session_id Default value of null means "get from context".
     * @param ?int    $learnpath_id (from c_lp table). Default value of null means "get from context".
     * @param ?int    $learnpath_item_id (from the c_lp_item table). Default value of null means "get from context".
     *
     * @return bool Result of the insert query
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public static function saveQuestionAttempt(
        Exercise $exercise,
        float $score,
                 $answer,
        int $question_id,
        int $exe_id,
        int $position,
        ?int $exercise_id = 0,
        ?bool $updateResults = false,
        ?int $questionDuration = 0,
        ?string $fileName = null,
        ?int $user_id = null,
        ?int $course_id = null,
        ?int $session_id = null,
        ?int $learnpath_id = null,
        ?int $learnpath_item_id = null
    ) {
        global $debug;
        $questionDuration = (int) $questionDuration;
        $now = api_get_utc_datetime();
        $recordingLog = ('true' === api_get_setting('exercise.quiz_answer_extra_recording'));

        // check user_id or get from context
        if (empty($user_id)) {
            $user_id = api_get_user_id();
            // anonymous
            if (empty($user_id)) {
                $user_id = api_get_anonymous_id();
            }
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
            error_log('----- entering saveQuestionAttempt() function ------');
            error_log("answer: $answer");
            error_log("score: $score");
            error_log("question_id : $question_id");
            error_log("position: $position");
        }

        //Validation in case of fraud with active control time
        if (!ExerciseLib::exercise_time_control_is_valid($exercise, $learnpath_id, $learnpath_item_id)) {
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
            'position' => $position,
            'tms' => $now,
            'filename' => !empty($fileName) ? basename($fileName) : $fileName,
            'teacher_comment' => '',
            'seconds_spent' => $questionDuration,
        ];

        // Check if attempt exists.
        $sql = "SELECT exe_id FROM $TBL_TRACK_ATTEMPT
                WHERE
                    exe_id = $exe_id AND
                    user_id = $user_id AND
                    question_id = $question_id AND
                    position = $position";
        $result = Database::query($sql);
        $attemptData = [];
        if (Database::num_rows($result)) {
            $attemptData = Database::fetch_assoc($result);
            if (!$updateResults) {
                //The attempt already exist do not update use  update_event_exercise() instead
                return false;
            }
        } else {
            $attempt['exe_id'] = $exe_id;
        }

        if ($debug) {
            error_log("updateResults : $updateResults");
            error_log('Saving question attempt:');
            error_log($sql);
        }

        $em = Database::getManager();
        if (!$updateResults) {
            $attempt_id = Database::insert($TBL_TRACK_ATTEMPT, $attempt);
            $trackExercise = $em->find(TrackEExercise::class, $exe_id);

            if ($recordingLog) {
                $recording = new TrackEAttemptQualify();
                $recording
                    ->setTrackExercise($trackExercise)
                    ->setQuestionId($question_id)
                    ->setAnswer($answer)
                    ->setMarks((int) $score)
                    ->setAuthor(api_get_user_id())
                    ->setSessionId($session_id)
                ;
                $em->persist($recording);
                $em->flush();
            }
        } else {
            if ('true' === api_get_setting('exercise.allow_time_per_question')) {
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

            if ($recordingLog) {
                $repoTrackQualify = $em->getRepository(TrackEAttemptQualify::class);
                $trackQualify = $repoTrackQualify->findBy(
                    [
                        'exeId' => $exe_id,
                        'questionId' => $question_id,
                        'sessionId' => $session_id,
                    ]
                );
                $trackExercise = $em->find(TrackEExercise::class, $exe_id);
                /** @var TrackEAttemptQualify $trackQualify */
                $trackQualify
                    ->setTrackExercise($trackExercise)
                    ->setQuestionId($question_id)
                    ->setAnswer($answer)
                    ->setMarks((int) $score)
                    ->setAuthor(api_get_user_id())
                    ->setSessionId($session_id)
                ;
                $em->persist($trackQualify);
                $em->flush();
            }
            $attempt_id = $exe_id;
        }

        return $attempt_id;
    }

    /**
     * Record a hotspot spot for this attempt at answering a hotspot question.
     *
     * @param Exercise $exercise
     * @param int      $exeId
     * @param int      $questionId Question ID
     * @param int      $answerId Answer ID
     * @param int      $correct
     * @param string   $coords Coordinates of this point (e.g. 123;324)
     * @param bool     $updateResults
     * @param ?int     $exerciseId Deprecated param
     * @param ?int     $lpId
     * @param ?int     $lpItemId
     *
     * @return int Result of the insert query, or 0 on error
     *
     * @throws \Doctrine\DBAL\Exception
     * @throws Exception
     * @uses Course code and user_id from global scope $_cid and $_user
     */
    public static function saveExerciseAttemptHotspot(
        Exercise $exercise,
        int $exeId,
        int $questionId,
        int $answerId,
        int $correct,
        string $coords,
        bool $updateResults = false,
        ?int $exerciseId = 0,
        ?int $lpId = 0,
        ?int $lpItemId = 0
    ): int
    {
        $debug = false;

        if (!$updateResults) {
            // Validation in case of fraud with activated control time
            if (!ExerciseLib::exercise_time_control_is_valid($exercise, $lpId, $lpItemId)) {
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

            return 0;
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
            $res = Database::update(
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
            if (!$res) {
                return 0;
            }

            return $res;
        } else {
            if ($debug) {
                error_log("Insert hotspot results: exeId: $exeId correct: $correct");
            }

            $res = Database::insert(
                $table,
                [
                    'hotspot_user_id' => api_get_user_id(),
                    'c_id' => api_get_course_int_id(),
                    'hotspot_exe_id' => $exeId,
                    'hotspot_question_id' => $questionId,
                    'hotspot_answer_id' => $answerId,
                    'hotspot_correct' => $correct,
                    'hotspot_coordinate' => $coords,
                ]
            );
            if (!$res) {
                return 0;
            }

            return $res;
        }
    }

    /**
     * Records information for common (or admin) events (in the track_e_default table).
     *
     * @param string  $event_type Type of event
     * @param string  $event_value_type Type of value
     * @param mixed   $event_value Value (string, or array in the case of user info)
     * @param ?string $datetime Datetime (UTC) (defaults to null)
     * @param ?int    $user_id User ID (defaults to null)
     * @param ?int    $course_id Course ID (defaults to null)
     * @param ?int    $sessionId Session ID
     *
     * @return bool
     * @assert ('','','') === false
     * @throws ORMException
     * @throws Exception
     * @author Yannick Warnier <yannick.warnier@beeznest.com>
     *
     */
    public static function addEvent(
        string $event_type,
        string $event_value_type,
        mixed $event_value,
        ?string $datetime = null,
        ?int $user_id = null,
        ?int $course_id = null,
        ?int $sessionId = 0
    ): bool
    {
        if (empty($event_type)) {
            return false;
        }
        if (empty($course_id)) {
            $course_id = api_get_course_int_id();
        }
        if (empty($sessionId)) {
            $sessionId = api_get_session_id();
        }

        // Clean the user_info
        if (LOG_USER_OBJECT == $event_value_type) {
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

            if ($event_value instanceof User) {
                $event_value = serialize(
                    [
                        'id' => $event_value->getId(),
                        'username' => $event_value->getUsername(),
                        'firstname' => $event_value->getFirstName(),
                        'lastname' => $event_value->getLastname(),
                    ]
                );
            }
        }
        // If event is an array then the $event_value_type should finish with
        // the suffix _array for example LOG_WORK_DATA = work_data_array
        if (is_array($event_value)) {
            $event_value = serialize($event_value);
        }

        $sessionId = empty($sessionId) ? api_get_session_id() : $sessionId;

        if (!isset($datetime)) {
            $datetime = api_get_utc_datetime();
        }

        if (!isset($user_id)) {
            $user_id = api_get_user_id();
        }

        $track = (new TrackEDefault())
            ->setDefaultUserId($user_id)
            ->setCId($course_id)
            ->setDefaultDate(new DateTime($datetime, new DateTimeZone('UTC')))
            ->setDefaultEventType($event_type)
            ->setDefaultValueType($event_value_type)
            ->setDefaultValue($event_value)
            ->setSessionId($sessionId);

        $em = Database::getManager();
        $em->persist($track);
        $em->flush();

        return true;
    }

    /**
     * Gets the last attempt of an exercise based in the exe_id.
     *
     * @param int $exeId
     *
     * @return string
     * @throws \Doctrine\DBAL\Exception
     * @throws Exception
     */
    public static function getLastAttemptDateOfExercise(int $exeId): string
    {
        $track_attempts = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
        $sql = "SELECT max(tms) as last_attempt_date
                FROM $track_attempts
                WHERE exe_id = $exeId";
        $rs_last_attempt = Database::query($sql);
        if (0 == Database::num_rows($rs_last_attempt)) {
            return '';
        }
        $row_last_attempt = Database::fetch_array($rs_last_attempt);

        return $row_last_attempt['last_attempt_date']; //Get the date of last attempt
    }

    /**
     * Gets the last attempt of an exercise based in the exe_id.
     *
     * @param int $exeId
     *
     * @return int
     * @throws \Doctrine\DBAL\Exception
     * @throws Exception
     */
    public static function getLatestQuestionIdFromAttempt(int $exeId): int
    {
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

        return 0;
    }

    /**
     * Gets how many attempts exists by user, exercise, learning path.
     *
     * @param int $user_id
     * @param int $exerciseId
     * @param int $lp_id
     * @param int $lp_item_id
     * @param int $lp_item_view_id
     *
     * @return int
     * @throws \Doctrine\DBAL\Exception
     * @throws Exception
     */
    public static function get_attempt_count(
        int $user_id,
        int $exerciseId,
        int $lp_id,
        int $lp_item_id,
        int $lp_item_view_id
    ): int
    {
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $courseId = api_get_course_int_id();
        $sessionId = api_get_session_id();
        $sessionCondition = api_get_session_condition($sessionId);
        $sql = "SELECT count(*) as count
                FROM $table
                WHERE
                    exe_exo_id = $exerciseId AND
                    exe_user_id = $user_id AND
                    status != 'incomplete' AND
                    orig_lp_id = $lp_id AND
                    orig_lp_item_id = $lp_item_id AND
                    orig_lp_item_view_id = $lp_item_view_id AND
                    c_id = $courseId
                    $sessionCondition";

        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            $attempt = Database::fetch_assoc($result);

            return (int) $attempt['count'];
        }

        return 0;
    }

    /**
     * Find the order (not the count) of the given attempt in the queue of attempts
     * @param int $exeId The attempt ID from track_e_exercises
     * @param int $user_id
     * @param int $exerciseId
     * @param int $lp_id
     * @param int $lp_item_id
     * @param int $lp_item_view_id
     *
     * @return int
     * @throws \Doctrine\DBAL\Exception
     * @throws Exception
     */
    public static function getAttemptPosition(
        int $exeId,
        int $user_id,
        int $exerciseId,
        int $lp_id,
        int $lp_item_id,
        int $lp_item_view_id
    ): int
    {
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $courseId = api_get_course_int_id();
        $sessionId = api_get_session_id();

        $sessionCondition = api_get_session_condition($sessionId);
        // Select all matching attempts
        $sql = "SELECT exe_id
                FROM $table
                WHERE
                    exe_exo_id = $exerciseId AND
                    exe_user_id = $user_id AND
                    status = '' AND
                    orig_lp_id = $lp_id AND
                    orig_lp_item_id = $lp_item_id AND
                    orig_lp_item_view_id = $lp_item_view_id AND
                    c_id = $courseId
                    $sessionCondition
                ORDER by exe_id
                ";

        $result = Database::query($sql);
        // Scroll through them until we found ours, to locate its order in the queue
        if (Database::num_rows($result) > 0) {
            $position = 1;
            while ($row = Database::fetch_assoc($result)) {
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
     * @param int   $user_id
     * @param int   $lp_id
     * @param array $course
     * @param int   $session_id
     * @param ?bool $disconnectExerciseResultsFromLp (Replace orig_lp_* variables to null)
     *
     * @return bool
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     * @throws ORMException
     */
    public static function delete_student_lp_events(
        int $user_id,
        int $lp_id,
        array $course,
        int $session_id,
        ?bool $disconnectExerciseResultsFromLp = false
    ): bool
    {
        $lp_view_table = Database::get_course_table(TABLE_LP_VIEW);
        $lp_item_view_table = Database::get_course_table(TABLE_LP_ITEM_VIEW);
        $lpInteraction = Database::get_course_table(TABLE_LP_IV_INTERACTION);
        $lpObjective = Database::get_course_table(TABLE_LP_IV_OBJECTIVE);

        if (empty($course) || empty($user_id)) {
            return false;
        }

        $course_id = $course['real_id'];

        if (empty($course_id)) {
            $course_id = api_get_course_int_id();
        }

        $track_e_exercises = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $track_attempts = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
        $tblTrackAttemptQualify = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT_QUALIFY);
        $sessionCondition = api_get_session_condition($session_id);
        // Make sure we have the exact lp_view_id
        $sql = "SELECT iid FROM $lp_view_table
                WHERE
                    c_id = $course_id AND
                    user_id = $user_id AND
                    lp_id = $lp_id
                    $sessionCondition";
        $result = Database::query($sql);

        if (Database::num_rows($result)) {
            $view = Database::fetch_assoc($result);
            $lp_view_id = $view['iid'];

            $sql = "DELETE FROM $lp_item_view_table
                    WHERE lp_view_id = $lp_view_id";
            Database::query($sql);

            $sql = "DELETE FROM $lpInteraction
                    WHERE c_id = $course_id AND lp_iv_id = $lp_view_id";
            Database::query($sql);

            $sql = "DELETE FROM $lpObjective
                    WHERE c_id = $course_id AND lp_iv_id = $lp_view_id";
            Database::query($sql);
        }

        if ('true' === api_get_setting('lp.lp_minimum_time')) {
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
        while ($row = Database::fetch_assoc($result)) {
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

                $sql = "DELETE FROM $tblTrackAttemptQualify
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
     * @param int  $user_id
     * @param int  $exercise_id
     * @param int  $course_id
     * @param ?int $session_id
     * @throws ORMException
     * @throws Exception
     */
    public static function delete_all_incomplete_attempts(
        int $user_id,
        int $exercise_id,
        int $course_id,
        ?int $session_id = 0
    ): void
    {
        if (!empty($user_id) && !empty($exercise_id) && !empty($course_id)) {
            $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
            $sessionCondition = api_get_session_condition($session_id);
            $sql = "SELECT exe_id FROM $table
                    WHERE
                        exe_user_id = $user_id AND
                        exe_exo_id = $exercise_id AND
                        c_id = $course_id AND
                        status = 'incomplete'
                        $sessionCondition
                        ";
            $result = Database::query($sql);
            $repo = Container::getTrackEExerciseRepository();
            while ($row = Database::fetch_assoc($result)) {
                $exeId = $row['exe_id'];
                /** @var TrackEExercise $track */
                $track = $repo->find($exeId);

                self::addEvent(
                    LOG_EXERCISE_RESULT_DELETE,
                    LOG_EXERCISE_AND_USER_ID,
                    ($track->getQuiz()?->getIid()).'-'.$track->getUser()->getId(),
                    null,
                    null,
                    $course_id,
                    $session_id
                );
                $repo->delete($track);
            }
        }
    }

    /**
     * Gets all exercise results (NO Exercises in LPs ) from a given exercise id, course, session.
     *
     * @param int $exercise_id
     * @param int $courseId
     * @param ?int $session_id
     * @param ?bool $load_question_list
     * @param ?int $user_id
     *
     * @return array with the results
     * @throws \Doctrine\DBAL\Exception
     * @throws Exception
     */
    public static function get_all_exercise_results(
        int $exercise_id,
        int $courseId,
        ?int $session_id = 0,
        ?bool $load_question_list = true,
        ?int $user_id = null
    ): array
    {
        $TABLETRACK_EXERCISES = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $TBL_TRACK_ATTEMPT = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);

        $user_condition = null;
        if (!empty($user_id)) {
            $user_condition = "AND exe_user_id = $user_id ";
        }
        $sessionCondition = api_get_session_condition($session_id);
        $sql = "SELECT * FROM $TABLETRACK_EXERCISES
                WHERE
                    status = ''  AND
                    c_id = $courseId AND
                    exe_exo_id = $exercise_id AND
                    orig_lp_id = 0 AND
                    orig_lp_item_id = 0
                    $user_condition
                    $sessionCondition
                ORDER BY exe_id";
        $res = Database::query($sql);
        $list = [];
        while ($row = Database::fetch_assoc($res)) {
            $list[$row['exe_id']] = $row;
            if ($load_question_list) {
                $sql = "SELECT * FROM $TBL_TRACK_ATTEMPT
                        WHERE exe_id = {$row['exe_id']}";
                $res_question = Database::query($sql);
                while ($row_q = Database::fetch_assoc($res_question)) {
                    $list[$row['exe_id']]['question_list'][$row_q['question_id']] = $row_q;
                }
            }
        }

        return $list;
    }

    /**
     * Gets all exercise results (NO Exercises in LPs ) from a given exercise id, course, session.
     *
     * @param int   $courseId
     * @param ?int  $session_id
     * @param ?bool $get_count
     *
     * @return mixed Array with the results or count if $get_count == true
     * @throws \Doctrine\DBAL\Exception
     * @throws Exception
     */
    public static function get_all_exercise_results_by_course(
        int $courseId,
        ?int $session_id = 0,
        ?bool $get_count = true
    ) {
        $table_track_exercises = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);

        $select = '*';
        if ($get_count) {
            $select = 'count(*) as count';
        }
        $sessionCondition = api_get_session_condition($session_id);
        $sql = "SELECT $select FROM $table_track_exercises
                WHERE   status = ''  AND
                        c_id = $courseId AND
                        orig_lp_id = 0 AND
                        orig_lp_item_id = 0
                        $sessionCondition
                ORDER BY exe_id";
        $res = Database::query($sql);
        if ($get_count) {
            $row = Database::fetch_assoc($res);

            return $row['count'];
        } else {
            $list = [];
            while ($row = Database::fetch_assoc($res)) {
                $list[$row['exe_id']] = $row;
            }

            return $list;
        }
    }

    /**
     * Gets all exercise results (NO Exercises in LPs) from a given exercise id, course, session.
     *
     * @param int  $user_id
     * @param int  $courseId
     * @param ?int $session_id
     *
     * @return array with the results
     * @throws \Doctrine\DBAL\Exception
     * @throws Exception
     */
    public static function get_all_exercise_results_by_user(
        int $user_id,
        int $courseId,
        ?int $session_id = 0
    ): array
    {
        $table_track_exercises = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $table_track_attempt = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);

        $sessionCondition = api_get_session_condition($session_id);
        $sql = "SELECT * FROM $table_track_exercises
                WHERE
                    status = '' AND
                    exe_user_id = $user_id AND
                    c_id = $courseId AND
                    orig_lp_id = 0 AND
                    orig_lp_item_id = 0
                    $sessionCondition
                ORDER by exe_id";

        $res = Database::query($sql);
        $list = [];
        while ($row = Database::fetch_assoc($res)) {
            $list[$row['exe_id']] = $row;
            $sql = "SELECT * FROM $table_track_attempt
                    WHERE exe_id = {$row['exe_id']}";
            $res_question = Database::query($sql);
            while ($row_q = Database::fetch_assoc($res_question)) {
                $list[$row['exe_id']]['question_list'][$row_q['question_id']] = $row_q;
            }
        }

        return $list;
    }

    /**
     * Gets exercise results (NO Exercises in LPs) from a given exercise id, course, session.
     *
     * @param int    $exe_id attempt id
     * @param ?string $status
     *
     * @return array with the results
     * @throws \Doctrine\DBAL\Exception
     * @throws Exception
     */
    public static function get_exercise_results_by_attempt(int $exe_id, ?string $status = null): array
    {
        $table_track_exercises = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $table_track_attempt = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
        $tblTrackAttemptQualify = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT_QUALIFY);

        $status = Database::escape_string($status);

        $sql = "SELECT * FROM $table_track_exercises
                WHERE status = '$status' AND exe_id = $exe_id";

        $res = Database::query($sql);
        $list = [];
        if (Database::num_rows($res)) {
            $row = Database::fetch_assoc($res);

            //Checking if this attempt was revised by a teacher
            $sql_revised = "SELECT exe_id FROM $tblTrackAttemptQualify
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
            while ($row_q = Database::fetch_assoc($res_question)) {
                $list[$exe_id]['question_list'][$row_q['question_id']] = $row_q;
            }
        }

        return $list;
    }

    /**
     * Gets exercise results (NO Exercises in LPs) from a given user, exercise id, course, session, lp_id, lp_item_id.
     *
     * @param int     $user_id
     * @param int     $exercise_id
     * @param int     $courseId
     * @param ?int    $session_id
     * @param ?int    $lp_id
     * @param ?int    $lp_item_id
     * @param ?string $order asc or desc
     *
     * @return array with the results
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public static function getExerciseResultsByUser(
        int $user_id,
        int $exercise_id,
        int $courseId,
        ?int $session_id = 0,
        ?int $lp_id = 0,
        ?int $lp_item_id = 0,
        ?string $order = null
    ): array
    {
        $table_track_exercises = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $table_track_attempt = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
        $tblTrackAttemptQualify = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT_QUALIFY);
        if (!isset($lp_id)) {
            $lp_id = '0';
        }
        if (!isset($lp_item_id)) {
            $lp_item_id = '0';
        }

        if (!in_array(strtolower($order), ['asc', 'desc'])) {
            $order = 'asc';
        }

        $sessionCondition = api_get_session_condition($session_id);

        $sql = "SELECT * FROM $table_track_exercises
                WHERE
                    status 			= '' AND
                    exe_user_id 	= $user_id AND
                    c_id 	        = $courseId AND
                    exe_exo_id 		= $exercise_id AND
                    orig_lp_id 		= $lp_id AND
                    orig_lp_item_id = $lp_item_id
                    $sessionCondition
                ORDER by exe_id $order ";
        $res = Database::query($sql);
        $list = [];
        while ($row = Database::fetch_assoc($res)) {
            // Checking if this attempt was revised by a teacher
            $exeId = $row['exe_id'];
            $sql = "SELECT exe_id FROM $tblTrackAttemptQualify
                    WHERE author != '' AND exe_id = $exeId
                    LIMIT 1";
            $res_revised = Database::query($sql);
            $row['attempt_revised'] = 0;
            if (Database::num_rows($res_revised) > 0) {
                $row['attempt_revised'] = 1;
            }
            $row['total_percentage'] = $row['max_score'] > 0 ? ($row['score'] / $row['max_score']) * 100 : 0;
            $list[$row['exe_id']] = $row;
            $sql = "SELECT * FROM $table_track_attempt
                    WHERE exe_id = $exeId";
            $res_question = Database::query($sql);
            while ($row_q = Database::fetch_assoc($res_question)) {
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
     * @return int with the results
     * @throws \Doctrine\DBAL\Exception
     * @throws Exception
     */
    public static function count_exercise_attempts_by_user(
        int $user_id,
        int $exercise_id,
        int $courseId,
        int $session_id = 0
    ): int {
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $sessionCondition = api_get_session_condition($session_id);
        $sql = "SELECT count(*) as count
                FROM $table
                WHERE status = ''  AND
                    exe_user_id = $user_id AND
                    c_id = $courseId AND
                    exe_exo_id = $exercise_id AND
                    orig_lp_id = 0 AND
                    orig_lp_item_id = 0
                    $sessionCondition
                ORDER BY exe_id";
        $res = Database::query($sql);
        $result = 0;
        if (Database::num_rows($res) > 0) {
            $row = Database::fetch_assoc($res);
            $result = $row['count'];
        }

        return $result;
    }

    /**
     * Gets all exercise BEST results attempts (NO Exercises in LPs)
     * from a given exercise id, course, session per user.
     *
     * @param int  $exercise_id
     * @param int  $courseId
     * @param ?int $session_id
     * @param ?int $userId
     *
     * @return array with the results
     *
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     * @todo rename this function
     */
    public static function get_best_exercise_results_by_user(
        int $exercise_id,
        int $courseId,
        ?int $session_id = 0,
        ?int $userId = 0
    ): array
    {
        $table_track_exercises = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $table_track_attempt = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
        $sessionCondition = api_get_session_condition($session_id);
        $sql = "SELECT * FROM $table_track_exercises
                WHERE
                    status = '' AND
                    c_id = $courseId AND
                    exe_exo_id = $exercise_id AND
                    orig_lp_id = 0 AND
                    orig_lp_item_id = 0
                    $sessionCondition
                ";

        if (!empty($userId)) {
            $sql .= " AND exe_user_id = $userId ";
        }
        $sql .= ' ORDER BY exe_id';

        $res = Database::query($sql);
        $list = [];
        while ($row = Database::fetch_assoc($res)) {
            $list[$row['exe_id']] = $row;
            $exeId = $row['exe_id'];
            $sql = "SELECT * FROM $table_track_attempt
                    WHERE exe_id = $exeId";
            $res_question = Database::query($sql);
            while ($row_q = Database::fetch_assoc($res_question)) {
                $list[$exeId]['question_list'][$row_q['question_id']] = $row_q;
            }
        }

        // Getting the best results of every student
        $best_score_return = [];
        foreach ($list as $student_result) {
            $user_id = $student_result['exe_user_id'];
            $current_best_score[$user_id] = $student_result['score'];
            if (!isset($best_score_return[$user_id]['score'])) {
                $best_score_return[$user_id] = $student_result;
            }

            if ($current_best_score[$user_id] > $best_score_return[$user_id]['score']) {
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
     * @param ?int  $session_id
     * @param ?bool $skipLpResults
     *
     * @return array
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public static function get_best_attempt_exercise_results_per_user(
        int $user_id,
        int $exercise_id,
        int $courseId,
        ?int $session_id = 0,
        ?bool $skipLpResults = true
    ):array
    {
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);

        $sessionCondition = api_get_session_condition($session_id);
        $sql = "SELECT * FROM $table
                WHERE
                    status = ''  AND
                    c_id = $courseId AND
                    exe_exo_id = $exercise_id AND
                    exe_user_id = $user_id
                    $sessionCondition
                ";

        if ($skipLpResults) {
            $sql .= ' AND
                    orig_lp_id = 0 AND
                orig_lp_item_id = 0 ';
        }

        $sql .= ' ORDER BY exe_id ';

        $res = Database::query($sql);
        $list = [];
        while ($row = Database::fetch_assoc($res)) {
            $list[$row['exe_id']] = $row;
        }
        //Getting the best results of every student
        $best_score_return = [];
        $best_score_return['score'] = 0;

        foreach ($list as $result) {
            $current_best_score = $result;
            if ($current_best_score['score'] > $best_score_return['score']) {
                $best_score_return = $result;
            }
        }
        if (!isset($best_score_return['max_score'])) {
            $best_score_return = [];
        }

        return $best_score_return;
    }

    /**
     * Gets all exercise events from a Learning Path within a Course    nd Session.
     *
     * @param int $exercise_id
     * @param int $courseId
     * @param ?int $session_id
     *
     * @return array
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public static function get_all_exercise_event_from_lp(
        int $exercise_id,
        int $courseId,
        ?int $session_id = 0
    ): array
    {
        $table_track_exercises = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $table_track_attempt = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
        $sessionCondition = api_get_session_condition($session_id);

        $sql = "SELECT * FROM $table_track_exercises
                WHERE
                    status = '' AND
                    c_id = $courseId AND
                    exe_exo_id = $exercise_id AND
                    orig_lp_id !=0 AND
                    orig_lp_item_id != 0
                    $sessionCondition
                    ";

        $res = Database::query($sql);
        $list = [];
        while ($row = Database::fetch_assoc($res)) {
            $exeId = $row['exe_id'];
            $list[$exeId] = $row;
            $sql = "SELECT * FROM $table_track_attempt
                    WHERE exe_id = $exeId";
            $res_question = Database::query($sql);
            while ($row_q = Database::fetch_assoc($res_question)) {
                $list[$exeId]['question_list'][$row_q['question_id']] = $row_q;
            }
        }

        return $list;
    }

    /**
     * Get a list of all the exercises in a given learning path.
     *
     * @param int $lp_id
     *
     * @return array
     * @throws \Doctrine\DBAL\Exception
     * @throws Exception
     */
    public static function get_all_exercises_from_lp(int $lp_id): array
    {
        $lp_item_table = Database::get_course_table(TABLE_LP_ITEM);
        $sql = "SELECT * FROM $lp_item_table
                WHERE
                    lp_id = $lp_id AND
                    item_type = 'quiz'
                ORDER BY parent_item_id, display_order";
        $res = Database::query($sql);

        $list = [];
        while ($row = Database::fetch_assoc($res)) {
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
     * @throws \Doctrine\DBAL\Exception
     * @throws Exception
     */
    public static function get_comments(int $exe_id, int $question_id): string
    {
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
        $sql = "SELECT teacher_comment
                FROM $table
                WHERE
                    exe_id = $exe_id AND
                    question_id = $question_id
                ORDER by question_id";
        $sqlResult = Database::query($sql);
        $comm = strval(Database::result($sqlResult, 0, 'teacher_comment'));

        return trim($comm);
    }

    /**
     * Get all the track_e_attempt records for a given
     * track_e_exercises.exe_id (pk).
     *
     * @param int $exeId The exe_id from an exercise attempt record
     *
     * @return array The complete records from track_e_attempt that match the given exe_id
     * @throws \Doctrine\DBAL\Exception
     * @throws Exception
     */
    public static function getAllExerciseEventByExeId(int $exeId): array
    {
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);

        $sql = "SELECT * FROM $table
                WHERE exe_id = $exeId
                ORDER BY position";
        $res_question = Database::query($sql);
        $list = [];
        if (Database::num_rows($res_question)) {
            while ($row = Database::fetch_assoc($res_question)) {
                $list[$row['question_id']][] = $row;
            }
        }

        return $list;
    }

    /**
     * Get a question attempt from track_e_attempt based on en exe_id and question_id
     * @param int $exeId
     * @param int $questionId
     * @return array
     * @throws \Doctrine\DBAL\Exception
     * @throws Exception
     */
    public static function getQuestionAttemptByExeIdAndQuestion(int $exeId, int $questionId): array
    {
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);

        $sql = "SELECT * FROM $table
                WHERE
                    exe_id = $exeId AND
                    question_id = $questionId
                ORDER BY position";
        $result = Database::query($sql);
        $attempt = [];
        if (Database::num_rows($result)) {
            $attempt = Database::fetch_assoc($result);
        }

        return $attempt;
    }

    /**
     * Delete one record from the track_e_attempt table (recorded quiz answer)
     * and register the deletion event (LOG_QUESTION_RESULT_DELETE) in
     * track_e_default.
     *
     * @param int $exeId The track_e_exercises.exe_id (primary key)
     * @param int $user_id The user who answered (already contained in exe_id)
     * @param int $courseId The course in which it happened (already contained in exe_id)
     * @param int $session_id The session in which it happened (already contained in exe_id)
     * @param int $question_id The c_quiz_question.iid
     * @throws ORMException
     * @throws Exception
     */
    public static function delete_attempt(
        int $exeId,
        int $user_id,
        int $courseId,
        int $session_id,
        int $question_id
    ): void
    {
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);

        $sql = "DELETE FROM $table
                WHERE
                    exe_id = $exeId AND
                    user_id = $user_id AND
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
     * @param int $exeId
     * @param int $user_id
     * @param int $courseId
     * @param int $question_id
     * @param ?int $sessionId
     * @throws ORMException
     * @throws Exception
     */
    public static function delete_attempt_hotspot(
        int $exeId,
        int $user_id,
        int $courseId,
        int $question_id,
        ?int $sessionId = null
    ): void
    {
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_HOTSPOT);

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
     * @param int $courseId ID of the course
     * @param int $user_id ID of the user
     * @param int $sessionId ID of the session (if any)
     *
     * @return bool
     * @throws Exception
     */
    public static function eventCourseLogin(int $courseId, int $user_id, int $sessionId): bool
    {
        if (Session::read('login_as')) {
            return false;
        }

        if (false === self::isSessionLogNeedToBeSave($sessionId)) {
            return false;
        }

        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
        $loginDate = $logoutDate = api_get_utc_datetime();

        // $counter represents the number of time this record has been refreshed
        $counter = 1;
        $ip = Database::escape_string(api_get_real_ip());

        $sql = "INSERT INTO $table(c_id, user_ip, user_id, login_course_date, logout_course_date, counter, session_id)
                VALUES($courseId, '$ip', $user_id, '$loginDate', '$logoutDate', $counter, $sessionId)";
        $courseAccessId = Database::query($sql);

        if ($courseAccessId) {
            // Course catalog stats modifications see #4191
            CourseManager::update_course_ranking();

            return true;
        }

        return false;
    }

    /**
     * Updates the user - course - session every X minutes
     *
     * @param int $courseId
     * @param int $userId
     * @param int $sessionId
     * @param ?int $minutes
     *
     * @return bool
     * @throws \Doctrine\DBAL\Exception
     * @throws Exception
     */
    public static function eventCourseLoginUpdate(
        int $courseId,
        int $userId,
        int $sessionId,
        ?int $minutes = 5
    ): bool
    {
        if (Session::read('login_as')) {
            return false;
        }

        if (empty($courseId) || empty($userId)) {
            return false;
        }

        if (false === self::isSessionLogNeedToBeSave($sessionId)) {
            return false;
        }

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
     * @throws \Doctrine\DBAL\Exception
     * @throws Exception
     */
    public static function courseLogout(array $logoutInfo): bool
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

            if (false === self::isSessionLogNeedToBeSave($sessionId)) {
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
                $row = Database::fetch_assoc($result);
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

        return false;
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
     * @param int    $courseId The course in which to add the time
     * @param int    $userId The user for whom to add the time
     * @param int    $sessionId The session in which to add the time (if any)
     * @param string $virtualTime The amount of time to be added,
     *                            in a hh:mm:ss format. If int, we consider it is expressed in hours.
     * @param int    $workId Student publication id result
     *
     * @return bool true on successful insertion, false otherwise
     * @throws \Doctrine\DBAL\Exception
     * @throws Exception
     */
    public static function eventAddVirtualCourseTime(
        int $courseId,
        int $userId,
        int $sessionId,
        string $virtualTime,
        int $workId
    ): bool
    {
        if (empty($virtualTime)) {
            return false;
        }

        $logoutDate = api_get_utc_datetime();
        $loginDate = ChamiloHelper::addOrSubTimeToDateTime(
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

        // Time should also be added to the track_e_login table to
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
     * @param int    $courseId The course in which to add the time
     * @param int    $userId The user for whom to add the time
     * @param int    $sessionId The session in which to add the time (if any)
     * @param string $virtualTime The amount of time to be added, in a hh:mm:ss format. If int, we consider it is
     *                            expressed in hours.
     * @param int $workId
     *
     * @return bool true on successful removal, false otherwise
     * @throws \Doctrine\DBAL\Exception
     * @throws Exception
     */
    public static function eventRemoveVirtualCourseTime(
        int $courseId,
        int $userId,
        int $sessionId,
        string $virtualTime,
        int $workId
    ):bool
    {
        if (empty($virtualTime)) {
            return false;
        }

        $originalVirtualTime = Database::escape_string($virtualTime);

        $courseTrackingTable = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
        $platformTrackingTable = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);

        // Change $virtualTime format from hh:mm:ss to hhmmss which is the
        // format returned by SQL for a subtraction of two datetime values
        // @todo make sure this is portable between DBMSes
        // @todo make sure this is portable between DBMSes
        if (preg_match('/:/', $virtualTime)) {
            [$h, $m, $s] = preg_split('/:/', $virtualTime);
            $virtualTime = (int) $h * 3600 + (int) $m * 60 + (int) $s;
        } else {
            $virtualTime = (int) $virtualTime * 3600;
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
     * Register the logout of the course (usually when logging out of the platform)
     * from the track_e_access_complete table.
     *
     * @param array $logInfo Information stored by local.inc.php
     *
     * @return bool
     * @throws \Doctrine\DBAL\Exception
     */
    public static function registerLog(array $logInfo): bool
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

        if (false === self::isSessionLogNeedToBeSave($sessionId)) {
            return false;
        }

        $loginAs = true === (int) Session::read('login_as');

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

    /**
     * Get the remaining time to answer a question when there is question-based timing in place ('time' field exists for question items)
     * @param int $exeId
     * @param int $questionId
     * @return int
     * @throws \Doctrine\DBAL\Exception
     */
    public static function getAttemptQuestionDuration(int $exeId, int $questionId): int
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
            !empty($questionStart[$questionId])
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

    /**
     * Wrapper to addEvent with event LOG_SUBSCRIBE_USER_TO_COURSE
     * @param User         $subscribedUser
     * @param CourseEntity $course
     * @return void
     * @throws ORMException
     */
    public static function logSubscribedUserInCourse(
        User $subscribedUser,
        CourseEntity $course
    ): void
    {
        $dateTime = api_get_utc_datetime();
        $registrantId = api_get_user_id();

        self::addEvent(
            LOG_SUBSCRIBE_USER_TO_COURSE,
            LOG_COURSE_CODE,
            $course->getCode(),
            $dateTime,
            $registrantId,
            $course->getId()
        );

        self::addEvent(
            LOG_SUBSCRIBE_USER_TO_COURSE,
            LOG_USER_OBJECT,
            api_get_user_info($subscribedUser->getId()),
            $dateTime,
            $registrantId,
            $course->getId()
        );
    }

    /**
     * Wrapper to addEvent with event LOG_SESSION_ADD_USER_COURSE and LOG_SUBSCRIBE_USER_TO_COURSE
     * @param User          $userSubscribed
     * @param CourseEntity  $course
     * @param SessionEntity $session
     * @return void
     * @throws ORMException
     */
    public static function logUserSubscribedInCourseSession(
        User $userSubscribed,
        CourseEntity $course,
        SessionEntity $session
    ): void
    {
        $dateTime = api_get_utc_datetime();
        $registrantId = api_get_user_id();

        self::addEvent(
            LOG_SESSION_ADD_USER_COURSE,
            LOG_USER_ID,
            $userSubscribed,
            $dateTime,
            $registrantId,
            $course->getId(),
            $session->getId()
        );
        self::addEvent(
            LOG_SUBSCRIBE_USER_TO_COURSE,
            LOG_COURSE_CODE,
            $course->getCode(),
            $dateTime,
            $registrantId,
            $course->getId(),
            $session->getId()
        );
        self::addEvent(
            LOG_SUBSCRIBE_USER_TO_COURSE,
            LOG_USER_OBJECT,
            api_get_user_info($userSubscribed->getId()),
            $dateTime,
            $registrantId,
            $course->getId(),
            $session->getId()
        );
    }
}
