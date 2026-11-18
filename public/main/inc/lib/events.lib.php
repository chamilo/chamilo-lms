<?php

/* See license terms in /license.txt */

use Chamilo\CoreBundle\Entity\Course as CourseEntity;
use Chamilo\CoreBundle\Entity\Session as SessionEntity;
use Chamilo\CoreBundle\Entity\TrackEAccessComplete;
use Chamilo\CoreBundle\Entity\TrackEAttempt;
use Chamilo\CoreBundle\Entity\TrackEAttemptQualify;
use Chamilo\CoreBundle\Entity\TrackECourseAccess;
use Chamilo\CoreBundle\Entity\TrackEDefault;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Entity\TrackEHotspot;
use Chamilo\CoreBundle\Entity\TrackELastaccess;
use Chamilo\CoreBundle\Entity\TrackELogin;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Helpers\ChamiloHelper;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CLpItemView;
use Chamilo\CourseBundle\Entity\CLpIvInteraction;
use Chamilo\CourseBundle\Entity\CLpIvObjective;
use Chamilo\CourseBundle\Entity\CLpView;
use ChamiloSession as Session;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\QueryBuilder;

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

        self::insertRow($table, [
            'login_user_id' => $userId,
            'user_ip' => api_get_real_ip(),
            'login_date' => $reallyNow,
            'logout_date' => $reallyNow,
        ]);

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

        // The unescaped name is what the (bound) lastaccess queries compare against.
        $rawTool = $tool;
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
        $affectedRows = Container::getEntityManager()->createQueryBuilder()
            ->update(TrackELastaccess::class, 'l')
            ->set('l.accessDate', ':now')
            ->where('l.accessUserId = :userId')
            ->andWhere('l.cId = :courseId')
            ->andWhere('l.accessTool = :tool')
            ->andWhere('l.sessionId = :sessionId')
            ->setParameter('now', $reallyNow)
            ->setParameter('userId', (int) $userId)
            ->setParameter('courseId', (int) $courseId)
            ->setParameter('tool', $rawTool)
            ->setParameter('sessionId', (int) $sessionId)
            ->getQuery()
            ->execute()
        ;

        // MySQL reports 0 affected rows when the date is unchanged (same second), so check the row exists
        // before inserting, or a duplicate lastaccess row would be created.
        if (0 == $affectedRows) {
            $existing = Container::getEntityManager()->createQueryBuilder()
                ->select('l.accessId')
                ->from(TrackELastaccess::class, 'l')
                ->where('l.accessUserId = :userId')
                ->andWhere('l.cId = :courseId')
                ->andWhere('l.accessTool = :tool')
                ->andWhere('l.sessionId = :sessionId')
                ->setParameter('userId', (int) $userId)
                ->setParameter('courseId', (int) $courseId)
                ->setParameter('tool', $rawTool)
                ->setParameter('sessionId', (int) $sessionId)
                ->setMaxResults(1)
                ->getQuery()
                ->getArrayResult()
            ;
            if ([] === $existing) {
                $params = [
                    'access_user_id' => $userId,
                    'c_id' => $courseId,
                    'access_tool' => $tool,
                    'access_date' => $reallyNow,
                    'session_id' => $sessionId,
                ];
                Database::insert($tableLastAccess, $params);
            }
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

        self::insertRow($table, [
            'upload_user_id' => $userId,
            'c_id' => $courseId,
            'upload_work_id' => $documentId,
            'upload_date' => $reallyNow,
            'session_id' => $sessionId,
        ]);

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
        self::insertRow($table, [
            'links_user_id' => $userId,
            'c_id' => $courseId,
            'links_link_id' => $linkId,
            'links_date' => $reallyNow,
            'session_id' => $sessionId,
        ]);

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
        }

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
        $questions = implode(',', $questionsList);

        Container::getEntityManager()->createQueryBuilder()
            ->update(TrackEExercise::class, 'e')
            ->set('e.quiz', ':exoId')
            ->set('e.score', ':score')
            ->set('e.maxScore', ':weighting')
            ->set('e.origLpId', ':learnpathId')
            ->set('e.origLpItemId', ':learnpathItemId')
            ->set('e.origLpItemViewId', ':learnpathItemViewId')
            ->set('e.exeDuration', ':duration')
            ->set('e.exeDate', ':endDate')
            ->set('e.status', ':status')
            ->set('e.questionsToCheck', ':remindList')
            ->set('e.dataTracking', ':questions')
            ->set('e.userIp', ':userIp')
            ->where('e.exeId = :exeId')
            ->setParameter('exoId', (int) $exoId)
            ->setParameter('score', $score)
            ->setParameter('weighting', $weighting)
            ->setParameter('learnpathId', (int) $learnpathId)
            ->setParameter('learnpathItemId', (int) $learnpathItemId)
            ->setParameter('learnpathItemViewId', (int) $learnpathItemViewId)
            ->setParameter('duration', (int) $duration)
            ->setParameter('endDate', $endDate)
            ->setParameter('status', $status)
            ->setParameter('remindList', $remindList)
            ->setParameter('questions', $questions)
            ->setParameter('userIp', api_get_real_ip())
            ->setParameter('exeId', (int) $exeId)
            ->getQuery()
            ->execute()
        ;

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
        $existingAttempts = Container::getEntityManager()->createQueryBuilder()
            ->select('IDENTITY(a.trackExercise) AS exe_id')
            ->from(TrackEAttempt::class, 'a')
            ->where('a.trackExercise = :exeId')
            ->andWhere('a.user = :userId')
            ->andWhere('a.questionId = :questionId')
            ->andWhere('a.position = :position')
            ->setParameter('exeId', (int) $exe_id)
            ->setParameter('userId', (int) $user_id)
            ->setParameter('questionId', (int) $question_id)
            ->setParameter('position', (int) $position)
            ->getQuery()
            ->getArrayResult()
        ;
        $attemptData = [];
        if ([] !== $existingAttempts) {
            $attemptData = $existingAttempts[0];
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
                $trackExercise = $em->find(TrackEExercise::class, $exe_id);
                $trackQualify = $repoTrackQualify->findOneBy(
                    [
                        'trackExercise' => $trackExercise,
                        'questionId' => $question_id,
                        'sessionId' => $session_id,
                    ]
                );
                if (null === $trackQualify) {
                    $trackQualify = new TrackEAttemptQualify();
                }
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
        $val = Container::getEntityManager()->createQueryBuilder()
            ->select('MAX(a.tms)')
            ->from(TrackEAttempt::class, 'a')
            ->where('a.trackExercise = :exeId')
            ->setParameter('exeId', (int) $exeId)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return $val !== null ? (string) $val : '';
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
        $rows = Container::getEntityManager()->createQueryBuilder()
            ->select('a.questionId AS question_id')
            ->from(TrackEAttempt::class, 'a')
            ->where('a.trackExercise = :exeId')
            ->orderBy('a.tms', 'DESC')
            ->setMaxResults(1)
            ->setParameter('exeId', (int) $exeId)
            ->getQuery()
            ->getArrayResult()
        ;
        if ([] !== $rows) {
            return $rows[0]['question_id'];
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
        $qb = Container::getEntityManager()->createQueryBuilder()
            ->select('COUNT(e.exeId)')
            ->from(TrackEExercise::class, 'e')
            ->where('e.quiz = :exerciseId')
            ->andWhere('e.user = :userId')
            ->andWhere("e.status <> 'incomplete'")
            ->andWhere('e.origLpId = :lpId')
            ->andWhere('e.origLpItemId = :lpItemId')
            ->andWhere('e.origLpItemViewId = :lpItemViewId')
            ->andWhere('e.course = :courseId')
            ->setParameter('exerciseId', (int) $exerciseId)
            ->setParameter('userId', (int) $user_id)
            ->setParameter('lpId', (int) $lp_id)
            ->setParameter('lpItemId', (int) $lp_item_id)
            ->setParameter('lpItemViewId', (int) $lp_item_view_id)
            ->setParameter('courseId', (int) api_get_course_int_id())
        ;
        self::restrictToSession($qb, api_get_session_id());

        return (int) $qb->getQuery()->getSingleScalarResult();
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
     * @return int The 1-based position, or 0 if the attempt is not in the queue
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
        // Select all matching attempts
        $qb = Container::getEntityManager()->createQueryBuilder()
            ->select('e.exeId AS exe_id')
            ->from(TrackEExercise::class, 'e')
            ->where('e.quiz = :exerciseId')
            ->andWhere('e.user = :userId')
            ->andWhere("e.status = ''")
            ->andWhere('e.origLpId = :lpId')
            ->andWhere('e.origLpItemId = :lpItemId')
            ->andWhere('e.origLpItemViewId = :lpItemViewId')
            ->andWhere('e.course = :courseId')
            ->orderBy('e.exeId', 'ASC')
            ->setParameter('exerciseId', (int) $exerciseId)
            ->setParameter('userId', (int) $user_id)
            ->setParameter('lpId', (int) $lp_id)
            ->setParameter('lpItemId', (int) $lp_item_id)
            ->setParameter('lpItemViewId', (int) $lp_item_view_id)
            ->setParameter('courseId', (int) api_get_course_int_id())
        ;
        self::restrictToSession($qb, api_get_session_id());

        // Scroll through them until we found ours, to locate its order in the queue
        $position = 1;
        foreach ($qb->getQuery()->getArrayResult() as $row) {
            if ((int) $row['exe_id'] === $exeId) {
                return $position;
            }
            $position++;
        }

        return 0;
    }

    /**
     * @param int   $user_id
     * @param int   $lp_id
     * @param array $course
     * @param int   $session_id
     * @param ?bool $disconnectExerciseResultsFromLp (Replace orig_lp_* variables by 0, the "not from a learning
     *                                               path" value: the columns are NOT NULL)
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
        if (empty($course) || empty($user_id)) {
            return false;
        }

        $course_id = $course['real_id'];

        if (empty($course_id)) {
            $course_id = api_get_course_int_id();
        }

        $em = Container::getEntityManager();

        // Make sure we have the exact lp_view_id
        $viewQb = $em->createQueryBuilder()
            ->select('v.iid AS iid')
            ->from(CLpView::class, 'v')
            ->where('v.course = :courseId')
            ->andWhere('v.user = :userId')
            ->andWhere('v.lp = :lpId')
            ->setParameter('courseId', (int) $course_id)
            ->setParameter('userId', (int) $user_id)
            ->setParameter('lpId', (int) $lp_id)
        ;
        self::restrictToSession($viewQb, $session_id, 'IDENTITY(v.session)', 'v.session');
        $views = $viewQb->getQuery()->getArrayResult();

        if ([] !== $views) {
            $lp_view_id = $views[0]['iid'];

            $em->createQueryBuilder()
                ->delete(CLpItemView::class, 'iv')
                ->where('iv.view = :viewId')
                ->setParameter('viewId', (int) $lp_view_id)
                ->getQuery()
                ->execute()
            ;

            $em->createQueryBuilder()
                ->delete(CLpIvInteraction::class, 'i')
                ->where('i.cId = :courseId')
                ->andWhere('i.lpIvId = :viewId')
                ->setParameter('courseId', (int) $course_id)
                ->setParameter('viewId', (int) $lp_view_id)
                ->getQuery()
                ->execute()
            ;

            $em->createQueryBuilder()
                ->delete(CLpIvObjective::class, 'o')
                ->where('o.cId = :courseId')
                ->andWhere('o.lpIvId = :viewId')
                ->setParameter('courseId', (int) $course_id)
                ->setParameter('viewId', (int) $lp_view_id)
                ->getQuery()
                ->execute()
            ;
        }

        if ('true' === api_get_setting('lp.lp_minimum_time')) {
            $em->createQueryBuilder()
                ->delete(TrackEAccessComplete::class, 't')
                ->where("t.tool = 'learnpath'")
                ->andWhere('t.cId = :courseId')
                ->andWhere('t.toolId = :lpId')
                ->andWhere('t.user = :userId')
                ->andWhere('t.sessionId = :sessionId')
                ->setParameter('courseId', (int) $course_id)
                ->setParameter('lpId', (int) $lp_id)
                ->setParameter('userId', (int) $user_id)
                ->setParameter('sessionId', (int) $session_id)
                ->getQuery()
                ->execute()
            ;
        }

        $exeQb = $em->createQueryBuilder()
            ->select('e.exeId AS exe_id')
            ->from(TrackEExercise::class, 'e')
            ->where('e.user = :userId')
            ->andWhere('e.course = :courseId')
            ->andWhere('e.origLpId = :lpId')
            ->setParameter('userId', (int) $user_id)
            ->setParameter('courseId', (int) $course_id)
            ->setParameter('lpId', (int) $lp_id)
        ;
        self::restrictToSession($exeQb, $session_id);
        $exeList = array_map(
            static fn (array $row): int => (int) $row['exe_id'],
            $exeQb->getQuery()->getArrayResult()
        );

        if (!empty($exeList) && count($exeList) > 0) {
            if ($disconnectExerciseResultsFromLp) {
                $em->createQueryBuilder()
                    ->update(TrackEExercise::class, 'e')
                    ->set('e.origLpId', '0')
                    ->set('e.origLpItemId', '0')
                    ->set('e.origLpItemViewId', '0')
                    ->where('e.exeId IN (:exeList)')
                    ->setParameter('exeList', $exeList)
                    ->getQuery()
                    ->execute()
                ;
            } else {
                $em->createQueryBuilder()
                    ->delete(TrackEExercise::class, 'e')
                    ->where('e.exeId IN (:exeList)')
                    ->setParameter('exeList', $exeList)
                    ->getQuery()
                    ->execute()
                ;

                $em->createQueryBuilder()
                    ->delete(TrackEAttempt::class, 'a')
                    ->where('a.trackExercise IN (:exeList)')
                    ->setParameter('exeList', $exeList)
                    ->getQuery()
                    ->execute()
                ;

                $em->createQueryBuilder()
                    ->delete(TrackEAttemptQualify::class, 'q')
                    ->where('q.trackExercise IN (:exeList)')
                    ->setParameter('exeList', $exeList)
                    ->getQuery()
                    ->execute()
                ;
            }
        }

        $em->createQueryBuilder()
            ->delete(CLpView::class, 'v')
            ->where('v.course = :courseId')
            ->andWhere('v.user = :userId')
            ->andWhere('v.lp = :lpId')
            ->andWhere('v.session = :sessionId')
            ->setParameter('courseId', (int) $course_id)
            ->setParameter('userId', (int) $user_id)
            ->setParameter('lpId', (int) $lp_id)
            ->setParameter('sessionId', (int) $session_id)
            ->getQuery()
            ->execute()
        ;

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
            $qb = Container::getEntityManager()->createQueryBuilder()
                ->select('e.exeId AS exe_id')
                ->from(TrackEExercise::class, 'e')
                ->where('e.user = :userId')
                ->andWhere('e.quiz = :exerciseId')
                ->andWhere('e.course = :courseId')
                ->andWhere("e.status = 'incomplete'")
                ->setParameter('userId', (int) $user_id)
                ->setParameter('exerciseId', (int) $exercise_id)
                ->setParameter('courseId', (int) $course_id)
            ;
            self::restrictToSession($qb, $session_id);
            $repo = Container::getTrackEExerciseRepository();
            foreach ($qb->getQuery()->getArrayResult() as $row) {
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
        $qb = self::exerciseRowsQueryBuilder()
            ->where("e.status = ''")
            ->andWhere('e.course = :courseId')
            ->andWhere('e.quiz = :exerciseId')
            ->andWhere('e.origLpId = 0')
            ->andWhere('e.origLpItemId = 0')
            ->orderBy('e.exeId', 'ASC')
            ->setParameter('courseId', (int) $courseId)
            ->setParameter('exerciseId', (int) $exercise_id)
        ;
        if (!empty($user_id)) {
            $qb->andWhere('e.user = :userId')->setParameter('userId', (int) $user_id);
        }
        self::restrictToSession($qb, $session_id);

        $list = [];
        foreach ($qb->getQuery()->getArrayResult() as $row) {
            $row = self::toLegacyRow($row);
            $list[$row['exe_id']] = $row;
            if ($load_question_list) {
                foreach (self::attemptRows((int) $row['exe_id']) as $row_q) {
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
        if ($get_count) {
            $qb = Container::getEntityManager()->createQueryBuilder()
                ->select('COUNT(e.exeId)')
                ->from(TrackEExercise::class, 'e')
            ;
        } else {
            $qb = self::exerciseRowsQueryBuilder()->orderBy('e.exeId', 'ASC');
        }
        $qb
            ->where("e.status = ''")
            ->andWhere('e.course = :courseId')
            ->andWhere('e.origLpId = 0')
            ->andWhere('e.origLpItemId = 0')
            ->setParameter('courseId', (int) $courseId)
        ;
        self::restrictToSession($qb, $session_id);

        if ($get_count) {
            return (int) $qb->getQuery()->getSingleScalarResult();
        }

        $list = [];
        foreach ($qb->getQuery()->getArrayResult() as $row) {
            $row = self::toLegacyRow($row);
            $list[$row['exe_id']] = $row;
        }

        return $list;
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
        $qb = self::exerciseRowsQueryBuilder()
            ->where("e.status = ''")
            ->andWhere('e.user = :userId')
            ->andWhere('e.course = :courseId')
            ->andWhere('e.origLpId = 0')
            ->andWhere('e.origLpItemId = 0')
            ->orderBy('e.exeId', 'ASC')
            ->setParameter('userId', (int) $user_id)
            ->setParameter('courseId', (int) $courseId)
        ;
        self::restrictToSession($qb, $session_id);

        $list = [];
        foreach ($qb->getQuery()->getArrayResult() as $row) {
            $row = self::toLegacyRow($row);
            $list[$row['exe_id']] = $row;
            foreach (self::attemptRows((int) $row['exe_id']) as $row_q) {
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
        $rows = self::exerciseRowsQueryBuilder()
            ->where('e.status = :status')
            ->andWhere('e.exeId = :exeId')
            ->setParameter('status', (string) $status)
            ->setParameter('exeId', (int) $exe_id)
            ->getQuery()
            ->getArrayResult()
        ;
        $list = [];
        if ([] !== $rows) {
            $row = self::toLegacyRow($rows[0]);

            //Checking if this attempt was revised by a teacher
            $row['attempt_revised'] = self::isAttemptRevised((int) $exe_id) ? 1 : 0;
            $list[$exe_id] = $row;
            foreach (self::attemptRows((int) $exe_id, 'tms') as $row_q) {
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
        if (!isset($lp_id)) {
            $lp_id = '0';
        }
        if (!isset($lp_item_id)) {
            $lp_item_id = '0';
        }

        // Only 'ASC' or 'DESC' ever reach orderBy().
        $order = 'desc' === strtolower((string) $order) ? 'DESC' : 'ASC';

        $qb = self::exerciseRowsQueryBuilder()
            ->where("e.status = ''")
            ->andWhere('e.user = :userId')
            ->andWhere('e.course = :courseId')
            ->andWhere('e.quiz = :exerciseId')
            ->andWhere('e.origLpId = :lpId')
            ->andWhere('e.origLpItemId = :lpItemId')
            ->orderBy('e.exeId', $order)
            ->setParameter('userId', (int) $user_id)
            ->setParameter('courseId', (int) $courseId)
            ->setParameter('exerciseId', (int) $exercise_id)
            ->setParameter('lpId', (int) $lp_id)
            ->setParameter('lpItemId', (int) $lp_item_id)
        ;
        self::restrictToSession($qb, $session_id);

        $list = [];
        foreach ($qb->getQuery()->getArrayResult() as $row) {
            $row = self::toLegacyRow($row);
            // Checking if this attempt was revised by a teacher
            $exeId = $row['exe_id'];
            $row['attempt_revised'] = self::isAttemptRevised((int) $exeId) ? 1 : 0;
            $row['total_percentage'] = $row['max_score'] > 0 ? ($row['score'] / $row['max_score']) * 100 : 0;
            $list[$row['exe_id']] = $row;
            foreach (self::attemptRows((int) $exeId) as $row_q) {
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
        $qb = Container::getEntityManager()->createQueryBuilder()
            ->select('COUNT(e.exeId)')
            ->from(TrackEExercise::class, 'e')
            ->where("e.status = ''")
            ->andWhere('e.user = :userId')
            ->andWhere('e.course = :courseId')
            ->andWhere('e.quiz = :exerciseId')
            ->andWhere('e.origLpId = 0')
            ->andWhere('e.origLpItemId = 0')
            ->setParameter('userId', (int) $user_id)
            ->setParameter('courseId', (int) $courseId)
            ->setParameter('exerciseId', (int) $exercise_id)
        ;
        self::restrictToSession($qb, $session_id);

        return (int) $qb->getQuery()->getSingleScalarResult();
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
        $qb = self::exerciseRowsQueryBuilder()
            ->where("e.status = ''")
            ->andWhere('e.course = :courseId')
            ->andWhere('e.quiz = :exerciseId')
            ->andWhere('e.origLpId = 0')
            ->andWhere('e.origLpItemId = 0')
            ->orderBy('e.exeId', 'ASC')
            ->setParameter('courseId', (int) $courseId)
            ->setParameter('exerciseId', (int) $exercise_id)
        ;
        self::restrictToSession($qb, $session_id);

        if (!empty($userId)) {
            $qb->andWhere('e.user = :userId')->setParameter('userId', (int) $userId);
        }

        $list = [];
        foreach ($qb->getQuery()->getArrayResult() as $row) {
            $row = self::toLegacyRow($row);
            $list[$row['exe_id']] = $row;
            $exeId = $row['exe_id'];
            foreach (self::attemptRows((int) $exeId) as $row_q) {
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
        $qb = self::exerciseRowsQueryBuilder()
            ->where("e.status = ''")
            ->andWhere('e.course = :courseId')
            ->andWhere('e.quiz = :exerciseId')
            ->andWhere('e.user = :userId')
            ->orderBy('e.exeId', 'ASC')
            ->setParameter('courseId', (int) $courseId)
            ->setParameter('exerciseId', (int) $exercise_id)
            ->setParameter('userId', (int) $user_id)
        ;
        self::restrictToSession($qb, $session_id);

        if ($skipLpResults) {
            $qb->andWhere('e.origLpId = 0')->andWhere('e.origLpItemId = 0');
        }

        $list = [];
        foreach ($qb->getQuery()->getArrayResult() as $row) {
            $row = self::toLegacyRow($row);
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
        $qb = self::exerciseRowsQueryBuilder()
            ->where("e.status = ''")
            ->andWhere('e.course = :courseId')
            ->andWhere('e.quiz = :exerciseId')
            ->andWhere('e.origLpId <> 0')
            ->andWhere('e.origLpItemId <> 0')
            ->setParameter('courseId', (int) $courseId)
            ->setParameter('exerciseId', (int) $exercise_id)
        ;
        self::restrictToSession($qb, $session_id);

        $list = [];
        foreach ($qb->getQuery()->getArrayResult() as $row) {
            $row = self::toLegacyRow($row);
            $exeId = $row['exe_id'];
            $list[$exeId] = $row;
            foreach (self::attemptRows((int) $exeId) as $row_q) {
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
        $rows = Container::getEntityManager()->createQueryBuilder()
            ->select(
                'i.iid AS iid',
                'IDENTITY(i.lp) AS lp_id',
                'IDENTITY(i.root) AS item_root',
                'IDENTITY(i.parent) AS parent_item_id',
                'i.title AS title',
                'i.itemType AS item_type',
                'i.ref AS ref',
                'i.description AS description',
                'i.path AS path',
                'i.minScore AS min_score',
                'i.maxScore AS max_score',
                'i.masteryScore AS mastery_score',
                'i.displayOrder AS display_order',
                'i.prerequisite AS prerequisite',
                'i.parameters AS parameters',
                'i.launchData AS launch_data',
                'i.maxTimeAllowed AS max_time_allowed',
                'i.terms AS terms',
                'i.searchDid AS search_did',
                'i.audio AS audio',
                'i.prerequisiteMinScore AS prerequisite_min_score',
                'i.prerequisiteMaxScore AS prerequisite_max_score',
                'i.previousItemId AS previous_item_id',
                'i.nextItemId AS next_item_id',
                'i.lvl AS lvl',
                'i.duration AS duration',
                'i.exportAllowed AS export_allowed'
            )
            ->from(CLpItem::class, 'i')
            ->where('i.lp = :lpId')
            ->andWhere("i.itemType = 'quiz'")
            ->orderBy('i.parent')
            ->addOrderBy('i.displayOrder', 'ASC')
            ->setParameter('lpId', (int) $lp_id)
            ->getQuery()
            ->getArrayResult()
        ;

        $list = [];
        foreach ($rows as $row) {
            // export_allowed is a tinyint(1) column: the raw row carried it as 0/1.
            $row['export_allowed'] = (int) $row['export_allowed'];
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
        $rows = Container::getEntityManager()->createQueryBuilder()
            ->select('a.teacherComment AS teacher_comment')
            ->from(TrackEAttempt::class, 'a')
            ->where('a.trackExercise = :exeId')
            ->andWhere('a.questionId = :questionId')
            ->orderBy('a.questionId', 'ASC')
            ->setMaxResults(1)
            ->setParameter('exeId', (int) $exe_id)
            ->setParameter('questionId', (int) $question_id)
            ->getQuery()
            ->getArrayResult()
        ;
        $comm = strval($rows[0]['teacher_comment'] ?? '');

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
        $list = [];
        foreach (self::attemptRows($exeId, 'position') as $row) {
            $list[$row['question_id']][] = $row;
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
        $rows = self::attemptRows($exeId, 'position', $questionId, 1);

        return $rows[0] ?? [];
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
        Container::getEntityManager()->createQueryBuilder()
            ->delete(TrackEAttempt::class, 'a')
            ->where('a.trackExercise = :exeId')
            ->andWhere('a.user = :userId')
            ->andWhere('a.questionId = :questionId')
            ->setParameter('exeId', (int) $exeId)
            ->setParameter('userId', (int) $user_id)
            ->setParameter('questionId', (int) $question_id)
            ->getQuery()
            ->execute()
        ;

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
        if (!isset($sessionId)) {
            $sessionId = api_get_session_id();
        }

        Container::getEntityManager()->createQueryBuilder()
            ->delete(TrackEHotspot::class, 'h')
            ->where('h.hotspotExeId = :exeId')
            ->andWhere('h.hotspotUserId = :userId')
            ->andWhere('h.course = :courseId')
            ->andWhere('h.hotspotQuestionId = :questionId')
            ->setParameter('exeId', (int) $exeId)
            ->setParameter('userId', (int) $user_id)
            ->setParameter('courseId', (int) $courseId)
            ->setParameter('questionId', (int) $question_id)
            ->getQuery()
            ->execute()
        ;
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

        $courseAccessId = self::insertRow($table, [
            'c_id' => $courseId,
            'user_ip' => api_get_real_ip(),
            'user_id' => $user_id,
            'login_course_date' => $loginDate,
            'logout_course_date' => $logoutDate,
            'counter' => $counter,
            'session_id' => $sessionId,
        ]);

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

        $rows = Container::getEntityManager()->createQueryBuilder()
            ->select('a.courseAccessId AS course_access_id', 'a.logoutCourseDate AS logout_course_date')
            ->from(TrackECourseAccess::class, 'a')
            ->where('a.cId = :courseId')
            ->andWhere('a.sessionId = :sessionId')
            ->andWhere('a.user = :userId')
            ->orderBy('a.loginCourseDate', 'DESC')
            ->setMaxResults(1)
            ->setParameter('courseId', (int) $courseId)
            ->setParameter('sessionId', (int) $sessionId)
            ->setParameter('userId', (int) $userId)
            ->getQuery()
            ->getArrayResult()
        ;

        // Save every 5 minutes by default
        $seconds = $minutes * 60;
        $maxSeconds = 3600; // Only update if max diff is one hour
        if ([] !== $rows) {
            $row = self::toLegacyRow($rows[0]);
            $id = $row['course_access_id'];
            $logout = $row['logout_course_date'];
            $now = time();
            $logout = api_strtotime($logout, 'UTC');
            if ($now - $logout > $seconds &&
                $now - $logout < $maxSeconds
            ) {
                $now = api_get_utc_datetime();
                self::touchCourseAccess((int) $id, $now);
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
            $rows = Container::getEntityManager()->createQueryBuilder()
                ->select('a.courseAccessId AS course_access_id')
                ->from(TrackECourseAccess::class, 'a')
                ->where('a.user = :userId')
                ->andWhere('a.cId = :courseId')
                ->andWhere('a.sessionId = :sessionId')
                ->andWhere('a.loginCourseDate > :time')
                ->orderBy('a.loginCourseDate', 'DESC')
                ->setMaxResults(1)
                ->setParameter('userId', $userId)
                ->setParameter('courseId', $courseId)
                ->setParameter('sessionId', $sessionId)
                ->setParameter('time', $time)
                ->getQuery()
                ->getArrayResult()
            ;
            $insert = false;
            if ([] !== $rows) {
                self::touchCourseAccess((int) $rows[0]['course_access_id'], $currentDate);
            } else {
                $insert = true;
            }

            if ($insert) {
                self::insertRow($tableCourseAccess, [
                    'c_id' => $courseId,
                    'user_ip' => api_get_real_ip(),
                    'user_id' => $userId,
                    'login_course_date' => $currentDate,
                    'logout_course_date' => $currentDate,
                    'counter' => 1,
                    'session_id' => $sessionId,
                ]);
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

        $originalVirtualTime = $virtualTime;
        $removed = false;

        $courseTrackingTable = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
        $platformTrackingTable = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);
        $em = Container::getEntityManager();

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
        // TODO(db-neutral): UNIX_TIMESTAMP() difference of two datetime columns (no DQL equivalent registered)
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
            $em->createQueryBuilder()
                ->delete(TrackECourseAccess::class, 'a')
                ->where('a.courseAccessId = :id')
                ->setParameter('id', (int) $courseAccessId)
                ->getQuery()
                ->execute()
            ;
            $removed = true;
        }
        // TODO(db-neutral): UNIX_TIMESTAMP() difference of two datetime columns (no DQL equivalent registered)
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
            $em->createQueryBuilder()
                ->delete(TrackELogin::class, 'l')
                ->where('l.loginId = :id')
                ->setParameter('id', (int) $loginAccessId)
                ->getQuery()
                ->execute()
            ;
            $removed = true;
        }

        if (Tracking::minimumTimeAvailable($sessionId, $courseId)) {
            foreach (['add_work_start_'.$workId => false, 'add_work_end_'.$workId => true] as $action => $flagsRemoval) {
                $found = $em->createQueryBuilder()
                    ->select('t.id AS id')
                    ->from(TrackEAccessComplete::class, 't')
                    ->where('t.tool = :tool')
                    ->andWhere('t.cId = :courseId')
                    ->andWhere('t.sessionId = :sessionId')
                    ->andWhere('t.user = :userId')
                    ->andWhere('t.actionDetails = :details')
                    ->andWhere('t.action = :action')
                    ->setMaxResults(1)
                    ->setParameter('tool', TOOL_STUDENTPUBLICATION)
                    ->setParameter('courseId', (int) $courseId)
                    ->setParameter('sessionId', (int) $sessionId)
                    ->setParameter('userId', (int) $userId)
                    ->setParameter('details', $originalVirtualTime)
                    ->setParameter('action', $action)
                    ->getQuery()
                    ->getArrayResult()
                ;
                if ([] !== $found) {
                    $em->createQueryBuilder()
                        ->delete(TrackEAccessComplete::class, 't')
                        ->where('t.id = :id')
                        ->setParameter('id', (int) $found[0]['id'])
                        ->getQuery()
                        ->execute()
                    ;
                    $removed = $removed || $flagsRemoval;
                }
            }
        }

        return $removed;
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
            $userSubscribed->getId(),
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

    /**
     * Inserts one row in a track_e_* table with bound parameters.
     *
     * @return int Number of inserted rows
     */
    private static function insertRow(string $table, array $values): int
    {
        $qb = Database::getManager()->getConnection()->createQueryBuilder()->insert($table);
        foreach ($values as $column => $value) {
            $qb->setValue($column, ':'.$column)->setParameter($column, $value);
        }

        return (int) $qb->executeStatement();
    }

    /**
     * Refreshes the end of a course access row and counts one more refresh.
     */
    private static function touchCourseAccess(int $courseAccessId, string $logoutDate): void
    {
        Container::getEntityManager()->createQueryBuilder()
            ->update(TrackECourseAccess::class, 'a')
            ->set('a.logoutCourseDate', ':logoutDate')
            ->set('a.counter', 'a.counter + 1')
            ->where('a.courseAccessId = :id')
            ->setParameter('logoutDate', $logoutDate)
            ->setParameter('id', $courseAccessId)
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * Mirrors what api_get_session_condition() used to append as SQL text: a session id of 0 also matches the
     * results stored without session (NULL).
     *
     * @param int|string|null $sessionId
     */
    private static function restrictToSession(
        QueryBuilder $qb,
        $sessionId,
        string $field = 'IDENTITY(e.session)',
        string $nullField = 'e.session'
    ): void {
        $sessionId = (int) $sessionId;
        if (empty($sessionId)) {
            $qb->andWhere("($field = 0 OR $nullField IS NULL)");

            return;
        }

        $qb->andWhere($field.' = :sessionId')->setParameter('sessionId', $sessionId);
    }

    /**
     * Every column of track_e_exercises, aliased to the snake_case keys the legacy callers read.
     */
    private static function exerciseRowsQueryBuilder(): QueryBuilder
    {
        return Container::getEntityManager()->createQueryBuilder()
            ->select(
                'e.exeId AS exe_id',
                'IDENTITY(e.user) AS exe_user_id',
                'IDENTITY(e.course) AS c_id',
                'IDENTITY(e.session) AS session_id',
                'IDENTITY(e.quiz) AS exe_exo_id',
                'e.exeDate AS exe_date',
                'e.score AS score',
                'e.maxScore AS max_score',
                'e.userIp AS user_ip',
                'e.status AS status',
                'e.dataTracking AS data_tracking',
                'e.startDate AS start_date',
                'e.stepsCounter AS steps_counter',
                'e.origLpId AS orig_lp_id',
                'e.origLpItemId AS orig_lp_item_id',
                'e.exeDuration AS exe_duration',
                'e.expiredTimeControl AS expired_time_control',
                'e.origLpItemViewId AS orig_lp_item_view_id',
                'e.questionsToCheck AS questions_to_check',
                'e.blockedCategories AS blocked_categories'
            )
            ->from(TrackEExercise::class, 'e')
        ;
    }

    /**
     * The track_e_attempt rows of one attempt, with the raw column names.
     *
     * @param string|null $order      'position' or 'tms' (ascending), null for the natural order
     * @param int|null    $questionId only the rows of this question
     */
    private static function attemptRows(
        int $exeId,
        ?string $order = null,
        ?int $questionId = null,
        ?int $limit = null
    ): array {
        $qb = Container::getEntityManager()->createQueryBuilder()
            ->select(
                'a.id AS id',
                'IDENTITY(a.trackExercise) AS exe_id',
                'IDENTITY(a.user) AS user_id',
                'a.questionId AS question_id',
                'a.answer AS answer',
                'a.teacherComment AS teacher_comment',
                'a.marks AS marks',
                'a.position AS position',
                'a.tms AS tms',
                'a.filename AS filename',
                'a.secondsSpent AS seconds_spent'
            )
            ->from(TrackEAttempt::class, 'a')
            ->where('a.trackExercise = :exeId')
            ->setParameter('exeId', $exeId)
        ;
        if (null !== $questionId) {
            $qb->andWhere('a.questionId = :questionId')->setParameter('questionId', $questionId);
        }
        if ('position' === $order) {
            $qb->orderBy('a.position', 'ASC');
        } elseif ('tms' === $order) {
            $qb->orderBy('a.tms', 'ASC');
        }
        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        return array_map([self::class, 'toLegacyRow'], $qb->getQuery()->getArrayResult());
    }

    /**
     * Whether a teacher revised (final, authored) the given exercise attempt.
     */
    private static function isAttemptRevised(int $exeId): bool
    {
        return [] !== Container::getEntityManager()->createQueryBuilder()
            ->select('q.id')
            ->from(TrackEAttemptQualify::class, 'q')
            ->where('q.author <> 0')
            ->andWhere('q.final = true')
            ->andWhere('q.trackExercise = :exeId')
            ->setMaxResults(1)
            ->setParameter('exeId', $exeId)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * The raw rows carried datetimes as 'Y-m-d H:i:s' strings: keep that shape.
     */
    private static function toLegacyRow(array $row): array
    {
        foreach ($row as $key => $value) {
            if ($value instanceof DateTimeInterface) {
                $row[$key] = $value->format('Y-m-d H:i:s');
            }
        }

        return $row;
    }
}
