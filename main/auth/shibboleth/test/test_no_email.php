<?php

/**
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

Shibboleth::session()->logout();
ShibbolethTest::helper()->setup_new_student_no_email();

require_once dirname(__FILE__) . '/../login.php';