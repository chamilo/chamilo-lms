<?php

/* For licensing terms, see /license.txt */

use PHPUnit\Framework\TestCase;

final class CourseManagerDatabaseStub
{
    public static $generalCoachId = '17';
    public static $userLookups = [];

    public static function escape_string($value)
    {
        return $value;
    }

    public static function get_main_table($table)
    {
        return $table;
    }

    public static function query($sql)
    {
        return $sql;
    }

    public static function fetch_array($resource)
    {
        return false;
    }

    public static function result($resource, $row, $field = '')
    {
        return self::$generalCoachId;
    }
}

final class CourseManagerTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testIncludesNumericStringGeneralCoachId(): void
    {
        define('TABLE_MAIN_SESSION_COURSE_USER', 'session_rel_course_rel_user');
        define('TABLE_MAIN_SESSION', 'session');
        class_alias(CourseManagerDatabaseStub::class, 'Database');

        function api_get_course_info($courseCode)
        {
            return ['real_id' => 42];
        }

        function api_get_user_info($userId)
        {
            CourseManagerDatabaseStub::$userLookups[] = $userId;

            return ['user_id' => (int) $userId];
        }

        require_once dirname(__DIR__, 2).'/main/inc/lib/course.lib.php';

        self::assertSame(
            [17 => ['user_id' => 17]],
            CourseManager::get_coach_list_from_course_code('COURSE', 9)
        );
        self::assertSame([17], CourseManagerDatabaseStub::$userLookups);

        CourseManagerDatabaseStub::$generalCoachId = 0;
        CourseManagerDatabaseStub::$userLookups = [];

        self::assertSame([], CourseManager::get_coach_list_from_course_code('COURSE', 9));
        self::assertSame([], CourseManagerDatabaseStub::$userLookups);
    }
}
