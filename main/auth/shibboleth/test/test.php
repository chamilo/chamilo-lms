<?php

/**
 * Run unit tests. Server needs to be a test server to run those.
 * 
 * @copyright (c) 2012 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht <laurent@opprecht.info>
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



