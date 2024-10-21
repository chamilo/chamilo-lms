<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

if (!api_is_global_platform_admin()) {
    exit('Please connect as a global administrator to access this page');
}

echo "In this version, the script allowing you to connect to the database from your Chamilo interface has been
deprecated/removed due to increasing reports about a possible vulnerability (which we agree with, in principle).
To use this feature, please download Adminer as one single PHP file from https://www.adminer.org/#download,
install it somewhere safe, unpredictable and or access-protected on your Chamilo server and load it from there.
Our apologies for the extra effort needed.";
