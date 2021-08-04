<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/V2TestCase.php';
require_once __DIR__.'/../../../../vendor/autoload.php';

class GetCourseLpProgressTest extends V2TestCase
{
    public function action()
    {
        return 'course_lp_progress';
    }

    public function testCourseList()
    {
        $result = $this->dataArray();
        $this->assertIsArray($result);

        //$result = $this->dataArray();

        //$this->assertIsObject($result);
        //$this->assertObjectHasAttribute('courses', $result);

        //$this->assertSame(1, count($urls));

        // expect the web service to return false
        //$this->assertFalse($this->boolean(['loginname' => $loginName]));
    }
}
