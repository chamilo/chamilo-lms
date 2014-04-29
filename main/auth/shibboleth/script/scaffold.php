<?php

namespace Shibboleth;

/**
 * Scaffold script. Generates the required database models for the Shibboleth
 * plugin. 
 * 
 * Will only run when the server is a test server.
 * 
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info>, Nicolas Rod for the University of Geneva
 */
$dir = dirname(__FILE__);
include_once($dir . '/../init.php');
include_once($dir . '/../app/lib/scaffolder/scaffolder.class.php');

if (!ShibbolethTest::is_enabled())
{
    echo 'This is not a test server';
    die;
}

if (!Shibboleth::session()->is_logged_in())
{
    echo 'Not authorized';
    die;
}

$name = 'user';
$result = Scaffolder::instance()->scaffold($name);

file_put_contents("$dir/output/$name.class.php", $result);

header('content-type: text/plain');
echo $result;
