<?php

/**
 * 
 * @see http://www.google.com/uds/solutions/dynamicfeed/index.html
 * 
 * @copyright (c) 2011 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht
 */


require_once api_get_path(LIBRARY_PATH) . '/plugin.class.php';
require_once dirname(__FILE__) . '/lib/rss_plugin.class.php';

$plugin_info = RssPlugin::create()->get_info();
