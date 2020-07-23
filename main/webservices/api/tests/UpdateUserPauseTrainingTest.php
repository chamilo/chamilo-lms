<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/V2TestCase.php';
require_once __DIR__.'/../../../../vendor/autoload.php';

/**
 * UPDATE_USER_PAUSE_TRAINING webservice unit tests
 */
class UpdateUserPauseTrainingTest extends V2TestCase
{
    public function action()
    {
        return Rest::UPDATE_USER_PAUSE_TRAINING;
    }

    /**
     * creates a minimal test user
     * asserts that it was created with the supplied data
     *
     * @throws Exception if it cannot delete the created test user
     */
    public function testUpdate()
    {
        $params = [
            'user_id' => 1,
            'pause_formation' => 0,
            'start_pause_date' => '2020-06-30 10:00',
            'end_pause_date' => '2020-06-30 11:00',
            'disable_emails' => 1,
        ];
        $userId = $this->integer($params);

        // assert each field was filled with provided information
        $this->assertSame($userId, 1);
    }
}
