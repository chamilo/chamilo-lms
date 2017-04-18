<?php

namespace Shibboleth;

/**
 * Initialize the Shibboleth authentication system. All scripts that can be directly
 * called must include this file
 *
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info>, Nicolas Rod for the University of Geneva
 */

$__dir = __DIR__.'/';
$no_redirection = true;  //no redirection in global.
include_once($__dir . '/../../inc/global.inc.php');

require_once $__dir . 'config.php';

if (api_get_setting('server_type') == 'test')
{
    include_once $__dir . '/test/shibboleth_test_helper.class.php';
    include_once $__dir . '/test/shibboleth_test.class.php';
}
