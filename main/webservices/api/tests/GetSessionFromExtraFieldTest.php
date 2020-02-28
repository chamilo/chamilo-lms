<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/V2TestCase.php';
require_once __DIR__.'/../../../../vendor/autoload.php';


/**
 * Class GetSessionFromExtraFieldTest
 *
 * GET_SESSION_FROM_EXTRA_FIELD webservice unit tests
 */
class GetSessionFromExtraFieldTest extends V2TestCase
{
    public function action()
    {
        return 'get_session_from_extra_field';
    }

    /**
     * creates two extra fields and 2 sessions
     * asserts that the sessions can be found one by one but not together
     *
     */
    public function test()
    {
        // create 2 extra fields
        $extraFieldModel = new ExtraField('session');
        $firstExtraFieldName = 'firstExtraField'.time();
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
        $secondExtraFieldName = 'secondExtraField'.time();
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

        // create 2 sessions
        $firstSessionId = SessionManager::create_session(
            'First session'.time(),
            '2019-01-01 00:00',
            '2019-08-31 00:00',
            '2019-01-01 00:00',
            '2019-08-31 00:00',
            '2019-01-01 00:00',
            '2019-08-31 00:00',
            null,
            null
        );
        $secondSessionId = SessionManager::create_session(
            'Second session'.time(),
            '2019-09-01 00:00',
            '2019-12-31 00:00',
            '2019-09-01 00:00',
            '2019-12-31 00:00',
            '2019-09-01 00:00',
            '2019-12-31 00:00',
            null,
            null
        );

        // assign unique distinct value in first field to each session
        SessionManager::update_session_extra_field_value($firstSessionId, $firstExtraFieldName, $firstSessionId);
        SessionManager::update_session_extra_field_value($secondSessionId, $firstExtraFieldName, $secondSessionId);

        // assign the same value in second field to all sessions
        $commonValue = 'common value';
        SessionManager::update_session_extra_field_value($firstSessionId, $secondExtraFieldName, $commonValue);
        SessionManager::update_session_extra_field_value($secondSessionId, $secondExtraFieldName, $commonValue);

        // assert that the correct session id is returned using each unique value
        $this->assertSame(
            $firstSessionId,
            $this->integer(
                [
                    'field_name' => $firstExtraFieldName,
                    'field_value' => $firstSessionId,
                ]
            )
        );
        $this->assertSame(
            $secondSessionId,
            $this->integer(
                [
                    'field_name' => $firstExtraFieldName,
                    'field_value' => $secondSessionId,
                ]
            )
        );

        // assert search for common value in second field generates the right error message
        $this->assertSame(
            get_lang('MoreThanOneSessionMatched'),
            $this->errorMessageString(
                [
                    'field_name' => $secondExtraFieldName,
                    'field_value' => $commonValue,
                ]
            )
        );

        // assert search for unknown value generates the right error message
        $this->assertSame(
            get_lang('NoSessionMatched'),
            $this->errorMessageString(
                [
                    'field_name' => $secondExtraFieldName,
                    'field_value' => 'non-existent value',
                ]
            )
        );

        // clean up
        SessionManager::delete($firstSessionId);
        SessionManager::delete($secondSessionId);
        $extraFieldModel->delete($firstExtraFieldId);
        $extraFieldModel->delete($secondExtraFieldId);
    }
}
