<?php

/**
 * Shibboleth login page. 
 * 
 * Actual authentication is provided by the Shibboleth Apache security module. 
 * Shibboleth must be properly installed and configured. Then this page must
 * be secured through an Apache security directive.
 * 
 * When Shibboleth is properly set up this page will only be available for 
 * authenticated users. The plugin ensure those people are created and logged in.
 *
 * @copyright (c) 2012 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht <laurent@opprecht.info>, Nicolas Rod
 */
include_once(dirname(__FILE__) . '/init.php');

/*
  ==============================================================================
  TEST SECTION
  ==============================================================================
 * 
 * @todo: Only for testing. Comment that out for production
 * 
 */
//Shibboleth::session()->logout();
//ShibbolethTest::helper()->setup_new_student_no_email();
//ShibbolethTest::helper()->setup_new_staff();
//ShibbolethTest::helper()->setup_new_teacher();
//ShibbolethTest::helper()->setup_new_student();

ShibbolethController::instance()->login();