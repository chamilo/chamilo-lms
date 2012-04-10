<?php

/**
 * @copyright (c) 2011 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht
 */

require_once api_get_path(LIBRARY_PATH) . '/plugin.class.php';
require_once dirname(__FILE__) . '/lib/static_plugin.class.php';

$plugin_info = StaticPlugin::create()->get_info();