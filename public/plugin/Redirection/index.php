<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/config.php';

api_protect_admin_script();

header('Location: admin.php');
exit;
