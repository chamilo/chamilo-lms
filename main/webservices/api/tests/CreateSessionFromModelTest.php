<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/V2TestCase.php';
require_once __DIR__.'/../../../../vendor/autoload.php';

/**
 * Class CreateSessionFromModelTest
 *
 * CREATE_SESSION_FROM_MODEL webservice unit tests
 */
class CreateSessionFromModelTest extends V2TestCase
{
    public function action()
    {
        return 'create_session_from_model';
    }

    /**
     * creates a session from a simple model session
     * asserts that it was created with the supplied data
     */
    public function testFromASimpleModel()
    {
        // create a model session
        $modelSessionId = SessionManager::create_session(
            'Model session'.time(),
            '2019-01-01 00:00',
            '2019-08-31 00:00',
            '2019-01-01 00:00',
            '2019-08-31 00:00',
            '2019-01-01 00:00',
            '2019-08-31 00:00',
            null,
            null
        );

        // call the webservice to create the new session from the model session,
        // and assert it returns an integer
        $name = 'New session'.time();
        $startDate = '2019-09-01 00:00:00';
        $endDate = '2019-12-31 00:00:00';

        $newSessionId = $this->integer(
            [
                'modelSessionId' => $modelSessionId,
                'sessionName' => $name,
                'startDate' => $startDate,
                'endDate' => $endDate,
            ]
        );

        // assert the session was created and given the returned session id
        $entityManager = Database::getManager();
        $repository = $entityManager->getRepository('ChamiloCoreBundle:Session');
        $newSession = $repository->find($newSessionId);
        $this->assertIsObject($newSession);

        // assert the new session got the right data
        $this->assertSame($name, $newSession->getName());
        // FIXME account for UTC / local timezone shift
        // $this->assertSame($endDate, $newSession->getDisplayEndDate());
        // $this->assertSame($startDate, $newSession->getAccessStartDate());
        // $this->assertSame($endDate, $newSession->getAccessEndDate());
        // $this->assertSame($startDate, $newSession->getCoachAccessStartDate());
        // $this->assertSame($endDate, $newSession->getCoachAccessEndDate());

        // clean up
        SessionManager::delete($modelSessionId);
        SessionManager::delete($newSessionId);
    }

    /**
     * creates a session from a model session subscribed to a promotion
     * asserts that the promotion is inherited by the new session
     */
    public function testFromAModelWithAPromotion()
    {
        // create a promotion
        $career = new Career();
        $careerId = $career->save(['name' => 'test career'.time()]);
        $promotion = new Promotion();
        $promotionId = $promotion->save(['career_id' => $careerId, 'name' => 'test promo'.time()]);

        // create a model session
        $modelSessionId = SessionManager::create_session(
            'Model session'.time(),
            '2019-01-01 00:00',
            '2019-08-31 00:00',
            '2019-01-01 00:00',
            '2019-08-31 00:00',
            '2019-01-01 00:00',
            '2019-08-31 00:00',
            null,
            null
        );

        // subscribe the model session to the promotion - the new session will inherit this too
        SessionManager::subscribe_sessions_to_promotion($promotionId, [$modelSessionId]);

        // call the webservice to create the new session from the model session,
        // and assert it returns an integer
        $newSessionId = $this->integer(
            [
                'modelSessionId' => $modelSessionId,
                'sessionName' => 'New session'.time(),
                'startDate' => '2019-09-01 00:00',
                'endDate' => '2019-12-31 00:00',
            ]
        );

        // assert both sessions are in the promotion
        $promotionSessions = SessionManager::get_all_sessions_by_promotion($promotionId);
        $this->assertSame(2, count($promotionSessions));
        $this->assertArrayHasKey($newSessionId, $promotionSessions);
        $this->assertArrayHasKey($modelSessionId, $promotionSessions);

        // clean up
        SessionManager::delete($modelSessionId);
        SessionManager::delete($newSessionId);
        $promotion->delete($promotionId);
        $career->delete($careerId);
    }

    /**
     * Creates a session from a model session with a different URL
     * asserts that the new session has the called web server URL
     */
    public function testFromAModelWithADifferentURL()
    {
        if (!api_is_multiple_url_enabled()) {
            $this->markTestSkipped('needs multiple URL enabled');
        }

        // create a model session
        $modelSessionId = SessionManager::create_session(
            'Model session'.time(),
            '2019-01-01 00:00',
            '2019-08-31 00:00',
            '2019-01-01 00:00',
            '2019-08-31 00:00',
            '2019-01-01 00:00',
            '2019-08-31 00:00',
            null,
            null
        );

        // move the session to a non-standard URL
        $urlId = UrlManager::add('https://www.url.org/chamilo-lms/'.time(), 'Non-default URL', 1);
        UrlManager::update_urls_rel_session([$modelSessionId], $urlId);

        // call the webservice to create the new session from the model session,
        // and assert it returns an integer
        $newSessionId = $this->integer(
            [
                'modelSessionId' => $modelSessionId,
                'sessionName' => 'Name of the new session'.time(),
                'startDate' => '2019-09-01 00:00',
                'endDate' => '2019-12-31 00:00',
            ]
        );

        // assert the current url was set on the new session
        $urls = UrlManager::get_access_url_from_session($newSessionId);
        $this->assertSame(1, count($urls));
        $this->assertSame(api_get_current_access_url_id(), intval($urls[0]['access_url_id']));

        // clean up
        SessionManager::delete($modelSessionId);
        SessionManager::delete($newSessionId);
        UrlManager::delete($urlId);
    }

    /**
     * creates a session from a model session with courses
     * asserts that the new session inherits the same courses
     * @throws Exception
     */
    public function testFromAModelWithCourses()
    {
        // create a model session
        $modelSessionId = SessionManager::create_session(
            'Model session'.time(),
            '2019-01-01 00:00',
            '2019-08-31 00:00',
            '2019-01-01 00:00',
            '2019-08-31 00:00',
            '2019-01-01 00:00',
            '2019-08-31 00:00',
            null,
            null
        );

        // create courses and add them the the model session
        $courseCodes = ['course A'.time(), 'course B'.time(), 'course C'.time()];
        $courseList = [];
        $authorId = UserManager::get_user_id_from_username(self::WEBSERVICE_USERNAME);
        foreach ($courseCodes as $code) {
            $course = CourseManager::create_course(['code' => $code, 'title' => $code], $authorId);
            $courseList[] = $course['real_id'];
        }
        SessionManager::add_courses_to_session($modelSessionId, $courseList);

        // call the webservice to create the new session from the model session,
        // and assert it returns an integer
        $newSessionId = $this->integer(
            [
                'modelSessionId' => $modelSessionId,
                'sessionName' => 'Name of the new session'.time(),
                'startDate' => '2019-09-01 00:00',
                'endDate' => '2019-12-31 00:00',
            ]
        );

        // assert the new session inherited the model session courses
        $modelCourseList = array_keys(SessionManager::get_course_list_by_session_id($modelSessionId));
        $newCourseList = array_keys(SessionManager::get_course_list_by_session_id($newSessionId));
        $this->assertSame($modelCourseList, $newCourseList);

        // clean up
        foreach ($courseCodes as $code) {
            CourseManager::delete_course($code);
        }
        SessionManager::delete($modelSessionId);
        SessionManager::delete($newSessionId);
    }

    /**
     * creates a session from a model session with extra fields
     * asserts that the new session inherits the extra fields
     * and that specifying other extra field values works
     */
    public function testFromAModelWithExtraFields()
    {
        // create a model session
        $modelSessionId = SessionManager::create_session(
            'Model session'.time(),
            '2019-01-01 00:00',
            '2019-08-31 00:00',
            '2019-01-01 00:00',
            '2019-08-31 00:00',
            '2019-01-01 00:00',
            '2019-08-31 00:00',
            null,
            null
        );

        // create an extra field and set its value in the model session
        // the new session will be given a different value in this field
        $extraFieldModel = new ExtraField('session');
        $firstExtraFieldName = 'extraField'.time();
        $firstExtraFieldNameForModelSession = 'extra field value for model';
        $firstExtraFieldNameForNewSession = 'extra field value for new session';
        $firstExtraFieldId = $extraFieldModel->save(
            [
                'field_type' => ExtraField::FIELD_TYPE_TEXT,
                'variable' => $firstExtraFieldName,
                'display_text' => $firstExtraFieldName,
                'visible_to_self' => 1,
                'visible_to_others' => 1,
                'changeable' => 1,
                'filter' => 1,
            ]
        );
        SessionManager::update_session_extra_field_value(
            $modelSessionId,
            $firstExtraFieldName,
            $firstExtraFieldNameForModelSession
        );

        // create a second extra field and set its value in the model session
        // the new session will inherit the same value in this field
        $secondExtraFieldName = 'secondExtraField'.time();
        $secondExtraFieldValue = 'second extra field value';
        $secondExtraFieldId = $extraFieldModel->save(
            [
                'field_type' => ExtraField::FIELD_TYPE_TEXT,
                'variable' => $secondExtraFieldName,
                'display_text' => $secondExtraFieldName,
                'visible_to_self' => 1,
                'visible_to_others' => 1,
                'changeable' => 1,
                'filter' => 1,
            ]
        );
        SessionManager::update_session_extra_field_value(
            $modelSessionId,
            $secondExtraFieldName,
            $secondExtraFieldValue
        );

        // call the webservice to create the new session from the model session,
        // specifying a different value in the first extra field
        // and assert it returns an integer
        $newSessionId = $this->integer(
            [
                'modelSessionId' => $modelSessionId,
                'sessionName' => 'Name of the new session'.time(),
                'startDate' => '2019-09-01 00:00',
                'endDate' => '2019-12-31 00:00',
                'extraFields' => [
                    $firstExtraFieldName => $firstExtraFieldNameForNewSession,
                ],
            ]
        );

        // assert the new session has its own new value in the first extra field
        $extraFieldValueModel = new ExtraFieldValue('session');
        $extraFieldValue = $extraFieldValueModel->get_values_by_handler_and_field_variable(
            $newSessionId,
            $firstExtraFieldName
        );
        $this->assertNotFalse($extraFieldValue);
        $this->assertSame($firstExtraFieldNameForNewSession, $extraFieldValue['value']);

        // assert the model session still has its own original value in the first extra field
        $extraFieldValue = $extraFieldValueModel->get_values_by_handler_and_field_variable(
            $modelSessionId,
            $firstExtraFieldName
        );
        $this->assertNotFalse($extraFieldValue);
        $this->assertSame($firstExtraFieldNameForModelSession, $extraFieldValue['value']);

        // assert the new session has inherited the model session value in the second extra field
        $extraFieldValue = $extraFieldValueModel->get_values_by_handler_and_field_variable(
            $newSessionId,
            $secondExtraFieldName
        );
        $this->assertNotFalse($extraFieldValue);
        $this->assertSame($secondExtraFieldValue, $extraFieldValue['value']);

        // assert the model session still has the same value in the second extra field
        $extraFieldValue = $extraFieldValueModel->get_values_by_handler_and_field_variable(
            $modelSessionId,
            $secondExtraFieldName
        );
        $this->assertNotFalse($extraFieldValue);
        $this->assertSame($secondExtraFieldValue, $extraFieldValue['value']);

        // clean up
        SessionManager::delete($modelSessionId);
        SessionManager::delete($newSessionId);
        $extraFieldModel->delete($firstExtraFieldId);
        $extraFieldModel->delete($secondExtraFieldId);
    }
}
