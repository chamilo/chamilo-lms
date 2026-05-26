<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\User;
use ChamiloSession as Session;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use Packback\Lti1p3\LtiMessageLaunch;
use Packback\Lti1p3\LtiOidcLogin;
use Packback\Lti1p3\LtiServiceConnector;
use Chamilo\PluginBundle\LtiProvider\Entity\Result;

/**
 * Class LtiProvider.
 */
class LtiProvider
{
    public static function create()
    {
        static $result = null;

        return $result ?: $result = new self();
    }

    private function getCache(): Lti13Cache
    {
        return new Lti13Cache();
    }

    private function getCookie(): Lti13Cookie
    {
        return new Lti13Cookie();
    }

    private function getDatabase(): Lti13Database
    {
        return new Lti13Database();
    }

    private function getServiceConnector(): LtiServiceConnector
    {
        return new LtiServiceConnector(
            $this->getCache(),
            new Client()
        );
    }

    /**
     * OIDC login and redirect.
     *
     * @throws \Packback\Lti1p3\OidcException
     */
    public function login(?array $request = null): void
    {
        JWT::$leeway = 5;

        $request ??= $_REQUEST;

        $launchUrl = Security::remove_XSS($request['target_link_uri'] ?? '');

        $login = new LtiOidcLogin(
            $this->getDatabase(),
            $this->getCache(),
            $this->getCookie()
        );

        $redirectUrl = $login->getRedirectUrl($launchUrl, $request);

        header('Location: '.$redirectUrl);
        exit;
    }

    /**
     * LTI Message Launch.
     */
    public function launch(bool $fromCache = false, ?string $launchId = null): LtiMessageLaunch
    {
        JWT::$leeway = 5;

        $database = $this->getDatabase();
        $cache = $this->getCache();
        $cookie = $this->getCookie();
        $serviceConnector = $this->getServiceConnector();

        if ($fromCache) {
            return LtiMessageLaunch::fromCache(
                (string) $launchId,
                $database,
                $cache,
                $cookie,
                $serviceConnector
            );
        }

        $launch = LtiMessageLaunch::new(
            $database,
            $cache,
            $cookie,
            $serviceConnector
        );

        return $launch->initialize($_REQUEST);
    }

    /**
     * It removes user and LP session.
     */
    public function logout(string $toolName = '')
    {
        Session::erase('_user');
        Session::erase('is_platformAdmin');
        Session::erase('is_allowedCreateCourse');
        Session::erase('_uid');

        if ('lp' === $toolName) {
            Session::erase('oLP');
            Session::erase('lpobject');
            Session::erase('scorm_view_id');
            Session::erase('scorm_item_id');
            Session::erase('exerciseResult');
            Session::erase('objExercise');
            Session::erase('questionList');
        }

        Session::erase('is_allowed_in_course');
        Session::erase('_real_cid');
        Session::erase('_cid');
        Session::erase('_course');
    }

    /**
     * Verify if user exists in provider platform, create if needed and login.
     */
    public function validateUser(array $launchData, string $courseCode, string $toolName): ?User
    {
        $logPrefix = '[LTI Provider validateUser]';

        if (empty($launchData)) {
            error_log($logPrefix.' Empty launch data.');

            return null;
        }

        if (empty($courseCode)) {
            error_log($logPrefix.' Empty course code.');

            return null;
        }

        $issuer = trim((string) ($launchData['iss'] ?? ''));
        $subject = trim((string) ($launchData['sub'] ?? ''));
        $authSource = defined('IMS_LTI_SOURCE') ? IMS_LTI_SOURCE : 'lti_provider';

        if ('' === $issuer || '' === $subject) {
            error_log($logPrefix.' Missing issuer or subject in launch data.');

            return null;
        }

        $username = md5($issuer.'_'.$subject);
        $email = trim((string) ($launchData['email'] ?? ''));

        $firstName = trim((string) ($launchData['given_name'] ?? ''));
        if ('' === $firstName) {
            $firstName = 'LTI';
        }

        $lastName = trim((string) ($launchData['family_name'] ?? ''));
        if ('' === $lastName) {
            $fallbackName = trim((string) ($launchData['name'] ?? ''));
            $lastName = '' !== $fallbackName ? $fallbackName : 'User';
        }

        $em = Database::getManager();

        $resolvedUser = null;
        $resolution = 'none';

        // 1. First try the stable LTI username.
        $userInfo = api_get_user_info_from_username($username, $authSource);

        if (!empty($userInfo['user_id'])) {
            $resolvedUser = $em->find(User::class, (int) $userInfo['user_id']);
            $resolution = 'username';
        }

        // 2. Fallback to email if username was not found.
        if (!$resolvedUser instanceof User && '' !== $email) {
            /** @var User|null $userByEmail */
            $userByEmail = $em
                ->getRepository(User::class)
                ->findOneBy(['email' => $email]);

            if ($userByEmail instanceof User) {
                $resolvedUser = $userByEmail;
                $resolution = 'email';
            }
        }

        // 3. Create a new shadow user only if no existing user was found.
        if (!$resolvedUser instanceof User) {
            if ('' === $email) {
                $email = $username.'@'.$authSource.'.local';
            }

            $password = api_generate_password();
            $createdUserId = UserManager::create_user(
                $firstName,
                $lastName,
                STUDENT,
                $email,
                $username,
                $password,
                '',
                '',
                '',
                '',
                [$authSource]
            );

            $createdUserId = (int) $createdUserId;

            if ($createdUserId <= 0) {
                error_log($logPrefix.' User creation failed: '.json_encode([
                        'email' => $email,
                        'username' => $username,
                    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

                return null;
            }

            $resolvedUser = $em->find(User::class, $createdUserId);

            if (!$resolvedUser instanceof User) {
                error_log($logPrefix.' Created user could not be reloaded from database: '.json_encode([
                        'user_id' => $createdUserId,
                    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

                return null;
            }

            $resolution = 'created';
        }

        $userId = (int) $resolvedUser->getId();

        // 4. Resolve the real course ID and ensure the user is subscribed.
        $courseInfo = api_get_course_info($courseCode);

        if (empty($courseInfo) || empty($courseInfo['real_id'])) {
            error_log($logPrefix.' Course info could not be resolved: '.json_encode([
                    'course_code' => $courseCode,
                    'user_id' => $userId,
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return null;
        }

        $courseId = (int) $courseInfo['real_id'];

        $isSubscribed = CourseManager::is_user_subscribed_in_course($userId, $courseCode);

        if (!$isSubscribed) {
            $subscribeResult = CourseManager::subscribeUser($userId, $courseId);
            $isSubscribed = CourseManager::is_user_subscribed_in_course($userId, $courseCode);
        }

        if (!$isSubscribed) {
            error_log($logPrefix.' User could not be subscribed to the course: '.json_encode([
                    'user_id' => $userId,
                    'course_code' => $courseCode,
                    'course_id' => $courseId,
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return null;
        }

        return $resolvedUser;
    }

    private function logScore(string $message, array $context = []): void
    {
        error_log('[LtiProvider score] '.$message.' | '.json_encode(
                $context,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            ));
    }

    public function publishScoreToPlatform(
        string $launchId,
        string $toolName,
        int $resultId,
        string $courseCode,
        ?int $userId = null,
        ?int $sessionId = null
    ): array {
        $launchId = trim($launchId);
        $toolName = trim($toolName);
        $courseCode = trim($courseCode);

        if ('' === $launchId) {
            throw new Exception('Missing launch id.');
        }

        if (!in_array($toolName, ['quiz', 'lp'], true)) {
            throw new Exception('Unsupported LTI tool.');
        }

        if ($resultId <= 0) {
            throw new Exception('Missing LTI result id.');
        }

        if ('' === $courseCode) {
            throw new Exception('Missing course code.');
        }

        $courseId = api_get_course_int_id($courseCode);

        if (empty($courseId)) {
            throw new Exception('Invalid course code.');
        }

        $userId ??= (int) api_get_user_id();
        $sessionId ??= (int) api_get_session_id();

        $this->logScore('Resolved incoming score request.', [
            'launch_id' => $launchId,
            'tool' => $toolName,
            'result_id' => $resultId,
            'course_code' => $courseCode,
            'course_id' => $courseId,
            'user_id' => $userId,
            'session_id' => $sessionId,
        ]);

        $launch = $this->launch(true, $launchId);

        if (!$launch->hasAgs()) {
            throw new Exception('Launch does not have AGS services.');
        }

        $score = 0.0;
        $weight = 100.0;
        $progress = 0;
        $duration = 0;
        $activityProgress = 'Completed';
        $gradingProgress = 'FullyGraded';
        $timestamp = gmdate(DATE_ATOM);

        if ('quiz' === $toolName) {
            $objExercise = new Exercise($courseId);
            $trackInfo = $objExercise->get_stat_track_exercise_info_by_exe_id($resultId);

            if (empty($trackInfo)) {
                throw new Exception('Quiz tracking result not found.');
            }

            $score = (float) ($trackInfo['score'] ?? $trackInfo['exe_result'] ?? 0);
            $weight = (float) ($trackInfo['max_score'] ?? $trackInfo['exe_weighting'] ?? 0);

            if ($weight <= 0) {
                $weight = max($score, 100.0);
            }

            $progress = 100;
            $duration = (int) ($trackInfo['exe_duration'] ?? 0);

            $this->logScore('Resolved quiz score.', [
                'track_info' => $trackInfo,
                'score' => $score,
                'weight' => $weight,
                'duration' => $duration,
            ]);
        } else {
            $lpProgress = learnpath::getProgress(
                $resultId,
                $userId,
                $courseId,
                $sessionId
            );

            $score = (float) $lpProgress;
            $weight = 100.0;
            $progress = max(0, min(100, (int) round($lpProgress)));
            $duration = 0;

            if ($progress >= 100) {
                $activityProgress = 'Completed';
                $gradingProgress = 'FullyGraded';
            } else {
                $activityProgress = 'InProgress';
                $gradingProgress = 'Pending';
            }

            $this->logScore('Resolved learning path score.', [
                'lp_id' => $resultId,
                'progress' => $lpProgress,
                'score' => $score,
                'weight' => $weight,
                'activity_progress' => $activityProgress,
                'grading_progress' => $gradingProgress,
            ]);
        }

        if ($score < 0) {
            $score = 0.0;
        }

        $launchData = $launch->getLaunchData();
        $agsUserId = (string) ($launchData['sub'] ?? '');

        if ('' === $agsUserId) {
            throw new Exception('Missing AGS user id from launch data.');
        }

        $this->logScore('Preparing AGS grade payload.', [
            'ags_user_id' => $agsUserId,
            'score' => $score,
            'weight' => $weight,
            'progress' => $progress,
            'duration' => $duration,
            'activity_progress' => $activityProgress,
            'grading_progress' => $gradingProgress,
            'timestamp' => $timestamp,
        ]);

        $grades = $launch->getAgs();

        $scoreGrade = Packback\Lti1p3\LtiGrade::new()
            ->setScoreGiven($score)
            ->setScoreMaximum($weight)
            ->setTimestamp($timestamp)
            ->setActivityProgress($activityProgress)
            ->setGradingProgress($gradingProgress)
            ->setUserId($agsUserId);

        $grades->putGrade($scoreGrade);

        $this->logScore('AGS grade sent successfully.', [
            'launch_id' => $launchId,
            'tool' => $toolName,
            'score' => $score,
            'weight' => $weight,
            'progress' => $progress,
            'ags_user_id' => $agsUserId,
        ]);

        LtiProviderPlugin::create()->saveResult([
            'score' => $score,
            'progress' => $progress,
            'duration' => $duration,
        ], $launchId);

        $this->logScore('Local LTI result updated.', [
            'launch_id' => $launchId,
            'score' => $score,
            'progress' => $progress,
            'duration' => $duration,
        ]);

        return [
            'launch_id' => $launchId,
            'tool' => $toolName,
            'score' => $score,
            'weight' => $weight,
            'progress' => $progress,
            'duration' => $duration,
            'activity_progress' => $activityProgress,
            'grading_progress' => $gradingProgress,
        ];
    }

    public function shouldPublishLpProgress(
        string $launchId,
        int $lpId,
        int $courseId,
        int $userId,
        int $sessionId,
        string $status = '',
        bool $finish = false,
        int $threshold = 5
    ): bool {
        if ('' === trim($launchId) || $lpId <= 0 || $courseId <= 0 || $userId <= 0) {
            return false;
        }

        $progress = (int) round(learnpath::getProgress($lpId, $userId, $courseId, $sessionId));

        $lastProgress = 0;
        $plugin = LtiProviderPlugin::create();
        $em = Database::getManager();

        /** @var Result|null $storedResult */
        $storedResult = $em->getRepository(Result::class)->findOneBy([
            'ltiLaunchId' => $launchId,
        ]);

        if ($storedResult instanceof Result) {
            $lastProgress = (int) $storedResult->getProgress();
        }

        $status = strtolower(trim($status));

        $shouldSend =
            $finish ||
            in_array($status, ['completed', 'passed'], true) ||
            $progress >= 100 ||
            $progress >= ($lastProgress + $threshold);

        $this->logScore('Evaluated LP progress publish condition.', [
            'launch_id' => $launchId,
            'lp_id' => $lpId,
            'course_id' => $courseId,
            'user_id' => $userId,
            'session_id' => $sessionId,
            'status' => $status,
            'finish' => $finish,
            'progress' => $progress,
            'last_progress' => $lastProgress,
            'threshold' => $threshold,
            'should_send' => $shouldSend,
        ]);

        return $shouldSend;
    }

    /**
     * Check if request is from LTI customer.
     */
    public function isLtiRequest($request, $session)
    {
        $isLti = false;

        if (isset($request['lti_message_hint'])) {
            $isLti = true;
        } elseif (isset($request['state'])) {
            $isLti = true;
        } elseif (isset($request['lti_launch_id']) && 'learnpath' === api_get_origin()) {
            $isLti = true;
        } elseif (isset($request['lti_launch_id'])) {
            $isLti = true;
        } elseif (isset($session['oLP']->lti_launch_id)) {
            $isLti = true;
        }

        return $isLti;
    }
}
