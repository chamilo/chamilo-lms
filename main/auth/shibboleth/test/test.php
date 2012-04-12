<?php

/**
 * Run unit tests. Server needs to be a test server to run those.
 * 
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info>, Nicolas Rod for the University of Geneva
 */
include_once(dirname(__FILE__) . '/../init.php');

if (!ShibbolethTest::is_enabled())
{
    echo 'This is not a test server';
    die;
}

echo 'Test started<br/>-------------------<br/>';

ShibbolethTest::test_new_teacher();
ShibbolethTest::test_new_student();
ShibbolethTest::test_update_teacher();
ShibbolethTest::test_new_student_multiple_givenname();
ShibbolethTest::test_new_no_affiliation_default();
ShibbolethTest::test_new_staff();
ShibbolethTest::test_new_infer_status_request();

echo '-------------------<br/>Done!';



