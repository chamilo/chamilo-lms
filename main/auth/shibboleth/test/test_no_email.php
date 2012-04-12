<?php

/**
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info>, Nicolas Rod for the University of Geneva
 */
include_once(dirname(__FILE__) . '/../init.php');

if (!ShibbolethTest::is_enabled())
{
    echo 'This is not a test server';
    die;
}

Shibboleth::session()->logout();
ShibbolethTest::helper()->setup_new_student_no_email();

require_once dirname(__FILE__) . '/../login.php';