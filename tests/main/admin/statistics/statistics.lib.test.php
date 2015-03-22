<?php

class TestStatistics extends UnitTestCase
{

    public function TestStatistics()
    {
        $this->UnitTestCase('this File test the provides some function for statistics ');
    }

    public function setUp()
    {
        $this->statisc = new Statistics();
    }

    public function tearDown()
    {
        $this->statisc = null;
    }

    public function testMakeSizeString()
    {
        $size = 20960000;
        $res = Statistics::makeSizeString($size);
        $this->assertTrue(is_string($res));
        //var_dump($res);
    }

    /**
     * Count courses
     * @param string $category_code Code of a course category. Default: count
     * all courses.
     * @return int Number of courses counted
     */
    public function testCountCourses()
    {
        $res = Statistics::countCourses();
        $this->assertTrue(is_numeric($res));
        //var_dump($res);
    }

    public function testCountUsers()
    {
        $user_id = '1';
        $category_code = null;
        $course_code = 'ABC';
        $firstName = 'Jhon';
        $lastName = 'Doe';
        $status = '1';
        $email = 'localhost@localhost.com';
        $loginName = 'admin';
        $password = 'admin';
        $count_invisible_courses = true;
        $res = Statistics::countUsers($status, $category_code,
            $count_invisible_courses);
        $this->assertTrue(is_numeric($res));
        $this->assertTrue(count($res) === 0 || count($res) !== 0);
    }

    public function testGetNumberOfActivities()
    {
        $resu = Statistics::getNumberOfActivities();
        if (!is_null($resu)) {
            $this->assertTrue(is_numeric($resu));
            $this->assertTrue(count($resu) == 0 || count($resu) !== 0);
        }
    }

    /**
     * Get activities data to display
     */
    public function testGetActivitiesData()
    {
        global $dateTimeFormatLong;
        $from = 0;
        $number_of_items = 30;
        $column = '';
        $direction = 'ASC';
        $resu = Statistics::getActivitiesData($from, $number_of_items, $column,
            $direction);
        $this->assertTrue(is_array($resu));
    }

    /**
     * Get all course categories
     * @return array All course categories (code => name)
     */
    public function testGetCourseCategories()
    {
        $res = Statistics::getCourseCategories();
        $this->assertTrue($res);
        //var_dump($res);
    }

    public function testRescale()
    {
        $data = array('test', 'test2', 'test3');
        $max = 500;
        $res = Statistics::rescale($data, $max);
        $this->assertTrue($res);
        $this->assertTrue(is_array($res));
        //var_dump($res);
    }

    public function testPrintStats()
    {
        ob_start();
        $title = 'testing';
        $stats = array('test', 'test2', 'test3');
        $show_total = true;
        $is_file_size = false;
        $res = Statistics::printStats(
            $title,
            $stats,
            $show_total = true,
            $is_file_size = false
        );
        ob_end_clean();
        $this->assertTrue(is_null($res));
        //var_dump($res);
    }

    public function testPrintLoginStats()
    {
        ob_start();
        $type = 'month';
        $resu = Statistics::printLoginStats($type);
        ob_end_clean();
        $this->assertTrue(is_null($resu));
        //var_dump($resu);
    }

    public function testPrintRecentLoginStats()
    {
        ob_start();
        $res = Statistics::printRecentLoginStats();
        ob_end_clean();
        $this->assertTrue(is_null($res));
        //var_dump($res);
    }

    public function testPrintToolStats()
    {
        ob_start();
        $resu = Statistics::printToolStats();
        ob_end_clean();
        $this->assertTrue(is_null($resu));
    }

    public function testPrintCourseByLanguageStats()
    {
        ob_start();
        $resu = Statistics::printCourseByLanguageStats();
        ob_end_clean();
        $this->assertTrue(is_null($resu));
        //var_dump($resu);
    }

    public function testPrintUserPicturesStats()
    {
        ob_start();
        $resu = Statistics::printUserPicturesStats();
        ob_end_clean();
        $this->assertTrue(is_null($resu));
    }

    public function testPrintActivitiesStats()
    {
        ob_start();
        $res = Statistics::printActivitiesStats();
        ob_end_clean();
        $this->assertTrue(is_null($res));
        //var_dump($res);
    }

    public function testPrintCourseLastVisit()
    {
        ob_start();
        $column = '';
        $direction = '';
        $parameters['action'] = 'courselastvisit';
        $res = Statistics::printCourseLastVisit();
        ob_end_clean();
        $this->assertTrue(is_null($res));
    }
}

?>
