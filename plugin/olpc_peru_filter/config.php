<?php
/* For licensing terms, see /license.txt */

/* OLPCPeruFilter parameters that will be registered in the course settings */

require_once '../../main/inc/global.inc.php';
require_once 'lib/olpc_peru_filter_plugin.class.php';

/**
 * Change these settings if your Squid files and directories are someplace else
 */
$blacklist_enabled_file = '/var/sqg/blacklists'; //the file with the current selection of blacklisted packs
$blacklists_dir = '/var/squidGuard/blacklists'; //the directory where we find subdirectories defining filtering groups
