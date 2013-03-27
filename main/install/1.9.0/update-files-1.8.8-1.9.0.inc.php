<?php

/* For licensing terms, see /license.txt */

/**
 * Chamilo LMS
 *
 * @package chamilo.install
 */
if (defined('SYSTEM_INSTALLATION')) {
    $conf_dir = api_get_path(CONFIGURATION_PATH);
    $portfolio_conf_dist = $conf_dir . 'portfolio.conf.dist.php';
    $portfolio_conf = $conf_dir . 'portfolio.conf.dist.php';

    if (!file_exists($portfolio_conf)) {
        copy($portfolio_conf_dist, $portfolio_conf);
    }

	//Adds events.conf file
	if (! file_exists(api_get_path(CONFIGURATION_PATH).'events.conf.php')) {
		copy(api_get_path(CONFIGURATION_PATH).'events.conf.dist.php', api_get_path(CONFIGURATION_PATH).'events.conf.php');
	}
}