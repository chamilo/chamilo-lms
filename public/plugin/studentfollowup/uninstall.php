<?php
/* For licensing terms, see /license.txt */

if (!api_is_platform_admin()) {
    api_not_allowed(true);
}

StudentFollowUpPlugin::create()->uninstall();
