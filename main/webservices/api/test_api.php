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

/**
 * Class V2Test
 */
class V2Test extends TestCase
{
    const WEBSERVICE_USERNAME = 'admin';
    const WEBSERVICE_PASSWORD = 'admin';
    const RELATIVE_URI = 'webservices/api/v2.php';
    public $client;
    public $apiKey;

    /**
     * Initialises the HTTP client and retrieves the API key from the server
     *
     * @throws Exception when it cannot get the API key
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->client = new Client([
            'base_uri' => api_get_path(WEB_CODE_PATH),
        ]);

        $response = $this->client->post(self::RELATIVE_URI, [
            'form_params' => [
                'action' => 'authenticate',
                'username' => self::WEBSERVICE_USERNAME,
                'password' => self::WEBSERVICE_PASSWORD,
            ],
        ]);

        if (200 === $response->getStatusCode()) {
            $decodedResponse = json_decode($response->getBody()->getContents(), false, 3, JSON_THROW_ON_ERROR);
            if (is_object($decodedResponse)) {
                $this->apiKey = $decodedResponse->data->apiKey;
            } else {
                throw new Exception('The returned JSON document is not an object');
            }
        } else {
            throw new Exception($response->getReasonPhrase());
        }
    }

    /**
     * Posts an action request to the web server, asserts it returns a valid JSON-encoded response and decodes it
     * supplied parameters complete or override the generated base parameters username, api_key and action
     *
     * @param string    $action         name of the webservice action to call
     * @param array     $parameters     parameters to send with the request as an associative array
     * @return mixed                    the decoded response (usually an object with properties data, error, message)
     */
    protected function decodedResponse($action, $parameters = [])
    {
        $baseParams = [
            'username' => self::WEBSERVICE_USERNAME,
            'api_key' => $this->apiKey,
            'action' => $action,
        ];
        $response = $this->client->post(self::RELATIVE_URI, ['form_params' => array_merge($baseParams, $parameters)]);
        $this->assertNotNull($response);
        $this->assertSame(200, $response->getStatusCode());
        $decodedResponse = json_decode($response->getBody()->getContents());
        $this->assertNotNull($decodedResponse);
        return $decodedResponse;
    }

    /**
     * Posts an action request and assert the server returns an error message
     *
     * @param string    $action         name of the webservice action
     * @param array     $parameters     parameters to send with the request
     * @return string                   the error string returned by the webservice
     */
    protected function errorMessageString($action, $parameters = [])
    {
        $decodedResponse = $this->decodedResponse($action, $parameters);
        $this->assertIsObject($decodedResponse);
        $this->assertTrue(property_exists($decodedResponse, 'error'));
        $error = $decodedResponse->error;
        $this->assertIsBool(true, $error);
        $this->assertTrue($error, 'error is not true: '.print_r($decodedResponse, true));
        $this->assertTrue(property_exists($decodedResponse, 'message'));
        $message = $decodedResponse->message;
        $this->assertIsString($message);
        return $message;
    }

    /**
     * Posts an action request and assert the server returns a "data" array
     *
     * @param string    $action         name of the webservice action
     * @param array     $parameters     parameters to send with the request
     * @return array                    the "data" array returned by the webservice
     */
    protected function dataArray($action, $parameters = [])
    {
        $decodedResponse = $this->decodedResponse($action, $parameters);
        $this->assertIsObject($decodedResponse);
        $this->assertTrue(property_exists($decodedResponse, 'data'),
            'response data property is missing: '.print_r($decodedResponse, true));
        $data = $decodedResponse->data;
        $this->assertIsArray($data);
        return $data;
    }

    /**
     * Posts an action request and assert the server returns an array with a single element
     *
     * @param string    $action         name of the webservice action
     * @param array     $parameters     parameters to send with the request
     * @return mixed                    the unique element of the "data" array
     */
    protected function singleElementValue($action, $parameters = [])
    {
        $data = $this->dataArray($action, $parameters);
        $this->assertSame(1, count($data));
        return $data[0];
    }

    /**
     * Posts an action request and assert it returns an array with a single integer value
     *
     * @param string    $action         name of the webservice action
     * @param array     $parameters     parameters to send with the request
     * @return integer                  the integer value
     */
    protected function integer($action, $parameters = [])
    {
        $value = $this->singleElementValue($action, $parameters);
        $this->assertIsInt($value);
        return $value;
    }

    /**
     * Posts an action request and assert it returns an array with a single boolean value
     *
     * @param string    $action         name of the webservice action
     * @param array     $parameters     parameters to send with the request
     * @return boolean                  the boolean value
     */
    protected function boolean($action, $parameters = [])
    {
        $value = $this->singleElementValue($action, $parameters);
        $this->assertIsBool($value);
        return $value;
    }

    /**
     * @throws Exception if it cannot delete the created test user
     */
    public function testCreateUser()
    {
        $ACTION = 'save_user';

        // create a user
        $EXTRA_FIELD_NAME = 'age';
        $EXTRA_FIELD_VALUE = '29';
        $ORIGINAL_VALUES = [
            'firstname' => 'Test User'.time(),
            'lastname' => 'Chamilo',
            'status' => 5, // student
            'email' => 'testuser@example.com',
            'loginname' => 'restuser'.time(),
            'password' => 'restuser',
            'original_user_id_name' => 'myplatform_user_id', // field to identify the user in the external system
            'original_user_id_value' => '1234', // ID for the user in the external system
            'language' => 'english',
            //'phone' => '',
            //'expiration_date' => '',
        ];
        $params = [];
        foreach($ORIGINAL_VALUES as $name => $value) {
            $params[strtolower($name)] = $value;
        }
        $params['extra'] =  [
            [ 'field_name' => $EXTRA_FIELD_NAME, 'field_value' => $EXTRA_FIELD_VALUE ],
        ];
        $userId = $this->integer($ACTION, $params);

        // compare each saved value to the original
        $user = UserManager::getManager()->find($userId);
        foreach($ORIGINAL_VALUES as $name => $value) {
            $methodName = 'get'.ucfirst($name);
            if ($name !== 'password' and method_exists($user, "$methodName")) {
                $this->assertSame($value, eval("return \$user->$methodName();"), $name);
            }
        }

        // assert user extra field value was saved
        $extraFieldValueModel = new ExtraFieldValue('user');
        $extraFieldValue = $extraFieldValueModel->get_values_by_handler_and_field_variable($userId, $EXTRA_FIELD_NAME);
        $this->assertNotFalse($extraFieldValue);
        $this->assertSame($EXTRA_FIELD_VALUE, $extraFieldValue['value']);

        // clean up
        UserManager::delete_user($userId);
    }

    /**
     * @throws Exception if it cannot add the test courses to the test model session
     */
    public function testCreateSessionFromModel()
    {
        $ACTION = 'create_session_from_model';

        // create a promotion
        $career = new Career();
        $careerId = $career->save([ 'name' => 'test career'.time() ]);
        $promotion = new Promotion();
        $promotionId = $promotion->save([ 'career_id' => $careerId, 'name' => 'test promo'.time() ]);

        // create a model session
        $modelSessionId = SessionManager::create_session(
            'Model session'.time(),
            '2019-01-01 00:00', '2019-08-31 00:00',
            '2019-01-01 00:00', '2019-08-31 00:00',
            '2019-01-01 00:00', '2019-08-31 00:00',
            null, null
        );

        // move the session to a non-standard URL
        if (api_is_multiple_url_enabled()) {
            $urlId = UrlManager::add('https://www.url.org/chamilo-lms/' . time(), 'Non-default URL', 1);
            UrlManager::update_urls_rel_session([$modelSessionId], $urlId);
        }

        // create an extra field and set its value in the model session
        // the new session will be given a different value in this field
        $extraFieldModel = new ExtraField('session');
        $EXTRA_FIELD_NAME = 'extraField'.time();
        $EXTRA_FIELD_VALUE_FOR_MODEL_SESSION = 'extra field value for model';
        $EXTRA_FIELD_VALUE_FOR_NEW_SESSION = 'extra field value for new session';
        $extraFieldId = $extraFieldModel->save([
            'field_type' => ExtraField::FIELD_TYPE_TEXT,
            'variable' => $EXTRA_FIELD_NAME,
            'display_text' => $EXTRA_FIELD_NAME,
            'visible_to_self' => 1,
            'visible_to_others' => 1,
            'changeable' => 1,
            'filter' => 1,
        ]);
        SessionManager::update_session_extra_field_value(
            $modelSessionId, $EXTRA_FIELD_NAME, $EXTRA_FIELD_VALUE_FOR_MODEL_SESSION);

        // create a second extra field and set its value in the model session
        // the new session will inherit the same value in this field
        $SECOND_EXTRA_FIELD_NAME = 'secondExtraField'.time();
        $SECOND_EXTRA_FIELD_VALUE = 'second extra field value';
        $secondExtraFieldId = $extraFieldModel->save([
            'field_type' => ExtraField::FIELD_TYPE_TEXT,
            'variable' => $SECOND_EXTRA_FIELD_NAME,
            'display_text' => $SECOND_EXTRA_FIELD_NAME,
            'visible_to_self' => 1,
            'visible_to_others' => 1,
            'changeable' => 1,
            'filter' => 1,
        ]);
        SessionManager::update_session_extra_field_value(
            $modelSessionId, $SECOND_EXTRA_FIELD_NAME, $SECOND_EXTRA_FIELD_VALUE);

        // subscribe the model session to the promotion - the new session will inherit this too
        SessionManager::subscribe_sessions_to_promotion($promotionId, [$modelSessionId]);

        // create courses and add them the the model session
        $COURSE_CODES = [ 'course A'.time(), 'course B'.time(), 'course C'.time() ];
        $courseList = [];
        $authorId = UserManager::get_user_id_from_username(self::WEBSERVICE_USERNAME);
        foreach($COURSE_CODES as $code) {
            $course = CourseManager::create_course(['code' => $code, 'title' => $code ], $authorId);
            $courseList[] = $course['real_id'];
        }
        SessionManager::add_courses_to_session($modelSessionId, $courseList);

        // call the webservice to create the new session from the model session,
        // specifying a different value in the first extra field
        // and assert it returns an integer
        $newSessionId = $this->integer($ACTION, [
                'modelSessionId' => $modelSessionId,
                'sessionName' => 'Name of the new session'.time(),
                'startDate' => '2019-09-01 00:00',
                'endDate' => '2019-12-31 00:00',
                'extraFields' => [
                    $EXTRA_FIELD_NAME => $EXTRA_FIELD_VALUE_FOR_NEW_SESSION
                ],
            ]
        );

        // assert both sessions are in the promotion
        $promotionSessions = SessionManager::get_all_sessions_by_promotion($promotionId);
        $this->assertSame(2, count($promotionSessions));
        $this->assertArrayHasKey($newSessionId, $promotionSessions);
        $this->assertArrayHasKey($modelSessionId, $promotionSessions);

        // assert the new session has its own new value in the first extra field
        $extraFieldValueModel = new ExtraFieldValue('session');
        $extraFieldValue = $extraFieldValueModel->get_values_by_handler_and_field_variable(
            $newSessionId, $EXTRA_FIELD_NAME);
        $this->assertNotFalse($extraFieldValue);
        $this->assertSame($EXTRA_FIELD_VALUE_FOR_NEW_SESSION, $extraFieldValue['value']);

        // assert the model session still has its own original value in the first extra field
        $extraFieldValue = $extraFieldValueModel->get_values_by_handler_and_field_variable(
            $modelSessionId, $EXTRA_FIELD_NAME);
        $this->assertNotFalse($extraFieldValue);
        $this->assertSame($EXTRA_FIELD_VALUE_FOR_MODEL_SESSION, $extraFieldValue['value']);

        // assert the new session has inherited the model session value in the second extra field
        $extraFieldValue = $extraFieldValueModel->get_values_by_handler_and_field_variable(
            $newSessionId, $SECOND_EXTRA_FIELD_NAME);
        $this->assertNotFalse($extraFieldValue);
        $this->assertSame($SECOND_EXTRA_FIELD_VALUE, $extraFieldValue['value']);

        // assert the model session still has the same value in the second extra field
        $extraFieldValue = $extraFieldValueModel->get_values_by_handler_and_field_variable(
            $modelSessionId, $SECOND_EXTRA_FIELD_NAME);
        $this->assertNotFalse($extraFieldValue);
        $this->assertSame($SECOND_EXTRA_FIELD_VALUE, $extraFieldValue['value']);

        // assert the new session inherited the model session courses
        $modelCourseList = array_keys(SessionManager::get_course_list_by_session_id($modelSessionId));
        $newCourseList = array_keys(SessionManager::get_course_list_by_session_id($newSessionId));
        $this->assertSame($modelCourseList, $newCourseList);

        // assert the current url was set on the new session
        if (api_is_multiple_url_enabled()) {
            $urls = UrlManager::get_access_url_from_session($newSessionId);
            $this->assertSame(1, count($urls));
            $this->assertSame(api_get_current_access_url_id(), intval($urls[0]['access_url_id']));
        }

        // clean up
        foreach($COURSE_CODES as $code) {
            CourseManager::delete_course($code);
        }
        SessionManager::delete($modelSessionId);
        SessionManager::delete($newSessionId);
        $promotion->delete($promotionId);
        $career->delete($careerId);
        $extraFieldModel->delete($extraFieldId);
        $extraFieldModel->delete($secondExtraFieldId);
        if (api_is_multiple_url_enabled()) {
            UrlManager::delete($urlId);
        }
    }

    public function testSubscribeUserToSessionFromUsername()
    {
        $ACTION = 'subscribe_user_to_session_from_username';

        // create a session
        $sessionId = SessionManager::create_session(
            'Session to subscribe'.time(),
            '2019-01-01 00:00', '2019-08-31 00:00',
            '2019-01-01 00:00', '2019-08-31 00:00',
            '2019-01-01 00:00', '2019-08-31 00:00',
            null, null
        );

        // create a user
        $loginName = 'tester'.time();
        $userId = UserManager::create_user('Tester', 'Tester', 5, 'tester@local', $loginName, 'xXxxXxxXX');

        // create another user and subscribe it to the session
        $anotherUserId = UserManager::create_user('Tester 2', 'Tester 2', 5, 'tester2@local', $loginName.'t2', 'xXxxX');
        SessionManager::subscribeUsersToSession($sessionId, [$anotherUserId]);

        // call the webservice to subscribe the first user to the session
        $subscribed = $this->boolean($ACTION, [ 'sessionId' => $sessionId, 'loginName' => $loginName ] );
        $this->assertTrue($subscribed);

        // assert we now have two users subscribed to the session
        $sessionRelUsers = Database::getManager()
            ->getRepository('ChamiloCoreBundle:SessionRelUser')
            ->findBy([ 'session' => $sessionId ]);
        $this->assertSame(2, count($sessionRelUsers));

        // clean up
        UserManager::delete_users([ $userId, $anotherUserId ]);
        SessionManager::delete($sessionId);
    }

    public function testGetSessionFromExtraField()
    {
        $ACTION = 'get_session_from_extra_field';

        // create 2 extra fields
        $extraFieldModel = new ExtraField('session');
        $FIRST_EXTRA_FIELD_NAME = 'firstExtraField'.time();
        $firstExtraFieldId = $extraFieldModel->save([
            'field_type' => ExtraField::FIELD_TYPE_TEXT,
            'variable' => $FIRST_EXTRA_FIELD_NAME,
            'display_text' => $FIRST_EXTRA_FIELD_NAME,
            'visible_to_self' => 1,
            'visible_to_others' => 1,
            'changeable' => 1,
            'filter' => 1,
        ]);
        $SECOND_EXTRA_FIELD_NAME = 'secondExtraField'.time();
        $secondExtraFieldId = $extraFieldModel->save([
            'field_type' => ExtraField::FIELD_TYPE_TEXT,
            'variable' => $SECOND_EXTRA_FIELD_NAME,
            'display_text' => $SECOND_EXTRA_FIELD_NAME,
            'visible_to_self' => 1,
            'visible_to_others' => 1,
            'changeable' => 1,
            'filter' => 1,
        ]);

        // create 2 sessions
        $firstSessionId = SessionManager::create_session(
            'First session'.time(),
            '2019-01-01 00:00', '2019-08-31 00:00',
            '2019-01-01 00:00', '2019-08-31 00:00',
            '2019-01-01 00:00', '2019-08-31 00:00',
            null, null
        );
        $secondSessionId = SessionManager::create_session(
            'Second session'.time(),
            '2019-09-01 00:00', '2019-12-31 00:00',
            '2019-09-01 00:00', '2019-12-31 00:00',
            '2019-09-01 00:00', '2019-12-31 00:00',
            null, null
        );

        // assign unique distinct value in first field to each session
        SessionManager::update_session_extra_field_value($firstSessionId, $FIRST_EXTRA_FIELD_NAME, $firstSessionId);
        SessionManager::update_session_extra_field_value($secondSessionId, $FIRST_EXTRA_FIELD_NAME, $secondSessionId);

        // assign the same value in second field to all sessions
        $COMMON_VALUE = 'common value';
        SessionManager::update_session_extra_field_value($firstSessionId, $SECOND_EXTRA_FIELD_NAME, $COMMON_VALUE);
        SessionManager::update_session_extra_field_value($secondSessionId, $SECOND_EXTRA_FIELD_NAME, $COMMON_VALUE);

        // assert that the correct session id is returned using each unique value
        $this->assertSame($firstSessionId,
            $this->integer($ACTION, [
                'field_name' => $FIRST_EXTRA_FIELD_NAME,
                'field_value' => $firstSessionId,
            ]));
        $this->assertSame($secondSessionId,
            $this->integer($ACTION, [
                'field_name' => $FIRST_EXTRA_FIELD_NAME,
                'field_value' => $secondSessionId,
            ]));

        // assert search for common value in second field generates the right error message
        $this->assertSame('MoreThanOneSessionMatched',
            $this->errorMessageString($ACTION, [
                'field_name' => $SECOND_EXTRA_FIELD_NAME,
                'field_value' => $COMMON_VALUE,
            ]));

        // assert search for unknown value generates the right error message
        $this->assertSame('NoSessionMatched', $this->errorMessageString($ACTION, [
            'field_name' => $SECOND_EXTRA_FIELD_NAME,
            'field_value' => 'non-existent value',
        ]));

        // clean up
        SessionManager::delete($firstSessionId);
        SessionManager::delete($secondSessionId);
        $extraFieldModel->delete($firstExtraFieldId);
        $extraFieldModel->delete($secondExtraFieldId);
    }

    /**
     * @throws Exception when it cannot delete the test user
     */
    public function testUpdateUserFromUsername() {
        $ACTION = 'update_user_from_username';
        // create a user with initial data and extra field values
        $LOGIN_NAME = 'testUser'.time();
        $userId = UserManager::create_user(
            'Initial first name', 'Initial last name', 5,'initial.email@local', $LOGIN_NAME, 'xXxxXxxXX');

        // create an extra field and initialise its value for the user
        $extraFieldModel = new ExtraField('user');
        $EXTRA_FIELD_NAME = 'extraUserField'.time();
        $extraFieldId = $extraFieldModel->save([
            'field_type' => ExtraField::FIELD_TYPE_TEXT,
            'variable' => $EXTRA_FIELD_NAME,
            'display_text' => $EXTRA_FIELD_NAME,
            'visible_to_self' => 1,
            'visible_to_others' => 1,
            'changeable' => 1,
            'filter' => 1,
        ]);
        SessionManager::update_session_extra_field_value($userId, $EXTRA_FIELD_NAME, 'extra field initial value');

        // update user with new data and extra field data
        $NEW_DATA = [
            'firstname' => 'New first name'.time(),
            'lastname' => 'New last name',
            'status' => 1,
            'email' => 'new.address@local',
            //'original_user_id_name' => 'myplatform_user_id', // field to identify the user in the external system
            //'original_user_id_value' => '1234', // ID for the user in the external system
            //'language' => 'english',
            //'phone' => '',
            //'expiration_date' => '',
        ];
        $parameters = $NEW_DATA;
        $EXTRA_FIELD_NEW_VALUE = 'extra field value';
        $parameters['extra'] = [
            [ 'field_name' => $EXTRA_FIELD_NAME, 'field_value' => $EXTRA_FIELD_NEW_VALUE ],
        ];
        $parameters['loginName'] = $LOGIN_NAME;
        $updated = $this->boolean($ACTION, $parameters);
        $this->assertTrue($updated);

        // assert it reports an error with a non-existent login name
        $parameters['loginName'] = 'santaClaus';
        $this->assertSame('UserNotFound', $this->errorMessageString($ACTION, $parameters));

        // compare each saved value to the original
        $userManager = UserManager::getManager();
        /** @var User $user */
        $user = $userManager->find($userId);
        $userManager->reloadUser($user);
        foreach($NEW_DATA as $k => $v) {
            $kk = ucfirst($k);
            $this->assertSame($v, eval("return \$user->get$kk();"), $k);
        }

        // assert extra field values have been updated
        $extraFieldValueModel = new ExtraFieldValue('user');
        $extraFieldValue = $extraFieldValueModel->get_values_by_handler_and_field_variable($userId, $EXTRA_FIELD_NAME);
        $this->assertNotFalse($extraFieldValue);
        $this->assertSame($EXTRA_FIELD_NEW_VALUE, $extraFieldValue['value']);

        // clean up
        UserManager::delete_user($userId);
        $extraFieldModel->delete($extraFieldId);
    }
}
