<?php
/* For licensing terms, see /license.txt */

/**
 * Test case for v2.php
 *
 * Using Guzzle' HTTP client to call the API endpoint and make requests.
 */

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../../vendor/autoload.php';
require_once __DIR__.'/../../inc/global.inc.php';


class V2Test extends TestCase
{
    const WEBSERVICE_USERNAME = 'admin';
    const WEBSERVICE_PASSWORD = 'admin';
    public $client;

    protected function setUp(): void
    {
        $this->client = new Client([
            'base_uri' => api_get_path(WEB_CODE_PATH).'webservices/api/v2.php',
        ]);
    }

    /**
     * Make a request to get the API key for admin user.
     *
     * @return string
     * @throws Exception
     *
     */
    public function testAuthenticate()
    {
        $response = $this->client->post('v2.php', [
            'form_params' => [
                'action' => 'authenticate',
                'username' => self::WEBSERVICE_USERNAME,
                'password' => self::WEBSERVICE_PASSWORD,
            ],
        ]);

        $this->assertSame(200, $response->getStatusCode(), 'Entry denied with code : ' . $response->getStatusCode());

        $jsonResponse = json_decode($response->getBody()->getContents());

        $this->assertSame(False, $jsonResponse->error, 'Authentication failed because : ' . $jsonResponse->message);
        $this->assertNotNull($jsonResponse->data);
        $this->assertIsObject($jsonResponse->data);
        $this->assertNotNull($jsonResponse->data->apiKey);
        $this->assertIsString($jsonResponse->data->apiKey);

        return $jsonResponse->data->apiKey;
    }

    /**
     * @param $apiKey
     *
     * @depends testAuthenticate
     * @throws Exception
     *
     */
    public function testCreateUser($apiKey)
    {
        $response = $this->client->post(
            'v2.php',
            [
                'form_params' => [
                    // data for the user who makes the request
                    'action' => 'save_user',
                    'username' => self::WEBSERVICE_USERNAME,
                    'api_key' => $apiKey,
                    // data for new user
                    'firstname' => 'Test User ',
                    'lastname' => 'Chamilo',
                    'status' => 5, // student
                    'email' => 'testuser@example.com',
                    'loginname' => 'restuser',
                    'password' => 'restuser',
                    'original_user_id_name' => 'myplatform_user_id', // field to identify the user in the external system
                    'original_user_id_value' => '1234', // ID for the user in the external system
                    'extra' => [
                        [
                            'field_name' => 'age',
                            'field_value' => 29,
                        ],
                    ],
                    'language' => 'english',
                    //'phone' => '',
                    //'expiration_date' => '',
                ],
            ]
        );

        $this->assertSame(200, $response->getStatusCode(), 'Entry denied with code : ' . $response->getStatusCode());

        $jsonResponse = json_decode($response->getBody()->getContents());

        $this->assertFalse($jsonResponse->error, 'User not created because : ' . $jsonResponse->message);
        $this->assertNotNull($jsonResponse->data);
        $this->assertIsArray($jsonResponse->data);
        $this->assertArrayHasKey(0, $jsonResponse->data);
        $this->assertIsInt($jsonResponse->data[0]);
        $userId = $jsonResponse->data[0];

        UserManager::delete_user($userId);
    }

    /**
     * @param $apiKey
     * @depends testAuthenticate
     * @throws Exception
     */
    public function testCreateSessionFromModel($apiKey)
    {
        $career = new Career();
        $careerId = $career->save([ 'name' => 'test career'.time() ]);
        $promotion = new Promotion();
        $promotionId = $promotion->save([ 'career_id' => $careerId, 'name' => 'test promo'.time() ]);
        $modelSessionId = SessionManager::create_session(
            'Model session'.time(),
            '2019-01-01 00:00', '2019-08-31 00:00',
            '2019-01-01 00:00', '2019-08-31 00:00',
            '2019-01-01 00:00', '2019-08-31 00:00',
            null, null
        );

        SessionManager::subscribe_sessions_to_promotion($promotionId, [$modelSessionId]);

        $courseCodes = ['course A'.time(), 'course B'.time(), 'course C'.time()];
        $courseList = [];
        foreach($courseCodes as $code) {
            $course = CourseManager::create_course(['code' => $code, 'title' => $code, 'wanted_code' => $code ], 1/*FIXME*/);
            $courseList[] = $course['real_id'];
        }
        SessionManager::add_courses_to_session($modelSessionId, $courseList);

        $response = $this->client->post(
            'v2.php',
            [
                'form_params' => [
                    'action' => 'create_session_from_model',
                    'username' => self::WEBSERVICE_USERNAME,
                    'api_key' => $apiKey,
                    'modelSessionId' => $modelSessionId,
                    'sessionName' => 'Name of the new session'.time(),
                    'startDate' => '2019-09-01 00:00',
                    'endDate' => '2019-12-31 00:00',
                    'extraFields' => [
                        [
                            'field_name' => 'description',
                            'field_value' => 'Description of the new session',
                        ],
                    ],
                ],
            ]
        );

        $this->assertSame(200, $response->getStatusCode(), 'Entry denied with code : ' . $response->getStatusCode());

        $jsonResponse = json_decode($response->getBody()->getContents());

        $this->assertFalse($jsonResponse->error, 'Session not created because : ' . $jsonResponse->message);
        $this->assertNotNull($jsonResponse->data);
        $this->assertIsArray($jsonResponse->data);
        $this->assertArrayHasKey(0, $jsonResponse->data);
        $this->assertIsInt($jsonResponse->data[0]);

        $newSessionId = $jsonResponse->data[0];

        $promotionSessions = SessionManager::get_all_sessions_by_promotion($promotionId);
        $this->assertArrayHasKey($newSessionId, $promotionSessions);
        $this->assertArrayHasKey($modelSessionId, $promotionSessions);

        $modelCourseList = array_keys(SessionManager::get_course_list_by_session_id($modelSessionId));
        $newCourseList = array_keys(SessionManager::get_course_list_by_session_id($newSessionId));
        $this->assertSame($modelCourseList, $newCourseList);

        foreach($courseCodes as $code) {
            CourseManager::delete_course($code);
        }
        SessionManager::delete($modelSessionId);
        SessionManager::delete($newSessionId);
        $promotion->delete($promotionId);
        $career->delete($careerId);
    }

    /**
     * @param $apiKey
     * @depends testAuthenticate
     * @throws Exception
     */
    public function testSubscribeUserToSessionFromUsername($apiKey)
    {
        $sessionId = SessionManager::create_session(
            'Session to subscribe'.time(),
            '2019-01-01 00:00', '2019-08-31 00:00',
            '2019-01-01 00:00', '2019-08-31 00:00',
            '2019-01-01 00:00', '2019-08-31 00:00',
            null, null
        );
        $loginName = 'tester'.time();
        $userId = UserManager::create_user('Tester', 'Tester', 5, 'tester@local', $loginName, 'xXxxXxxXX');
        $anotherUserId = UserManager::create_user('Tester Bis', 'Tester Bis', 5, 'testerbis@local', $loginName.'bis', 'xXxxXxxXX');
        SessionManager::subscribeUsersToSession($sessionId, [$anotherUserId]);

        $response = $this->client->post(
            'v2.php',
            [
                'form_params' => [
                    'action' => 'subscribe_user_to_session_from_username',
                    'username' => self::WEBSERVICE_USERNAME,
                    'api_key' => $apiKey,
                    'sessionId' => $sessionId,
                    'loginName' => $loginName,
                ],
            ]
        );

        $this->assertSame(200, $response->getStatusCode());

        $jsonResponse = json_decode($response->getBody()->getContents());

        $this->assertFalse($jsonResponse->error, $jsonResponse->error ? $jsonResponse->message : '');
        $this->assertNotNull($jsonResponse->data);
        $this->assertIsArray($jsonResponse->data);
        $this->assertArrayHasKey(0, $jsonResponse->data);
        $this->assertIsBool($jsonResponse->data[0]);
        $this->assertSame(True, $jsonResponse->data[0]);

        $sessionRelUsers = Database::getManager()->getRepository('ChamiloCoreBundle:SessionRelUser')->findBy([ 'session' => $sessionId ]);
        $this->assertSame(2, count($sessionRelUsers));

        UserManager::delete_users([ $userId, $anotherUserId ]);
        SessionManager::delete($sessionId);
    }

}
