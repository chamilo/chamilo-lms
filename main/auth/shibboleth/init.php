<?php

/**
 * Initialize the Shibboleth authentication system. All scripts that can be directly
 * called must include this file
 *
 * @copyright (c) 2012 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht <laurent@opprecht.info>, Nicolas Rod
 */

$__dir = dirname(__FILE__) . '/';
include_once($__dir . '/../../inc/global.inc.php');

require_once $__dir . 'app/lib/shibboleth_config.class.php';
require_once $__dir . 'app/lib/shibboleth_session.class.php';
require_once $__dir . 'app/lib/store.class.php';
require_once $__dir . 'app/controller/shibboleth_controller.class.php';
require_once $__dir . 'app/model/shibboleth_store.class.php';
require_once $__dir . 'app/model/shibboleth_user.class.php';
require_once $__dir . 'app/model/user.class.php';
require_once $__dir . 'app/view/shibboleth_email_form.class.php';
require_once $__dir . 'app/view/shibboleth_status_request_form.class.php';
require_once $__dir . 'app/view/shibboleth_display.class.php';
require_once $__dir . 'app/shibboleth.class.php';
require_once $__dir . 'db/shibboleth_upgrade.class.php';

require_once $__dir . 'config.php';

if (api_get_setting('server_type') == 'test')
{
    include_once $__dir . '/test/shibboleth_test_helper.class.php';
    include_once $__dir . '/test/shibboleth_test.class.php';
}

$language_files[] = 'shibboleth';