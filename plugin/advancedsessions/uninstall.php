<?php
/* For licensing terms, see /license.txt */
/**
 * Plugin Uninstallation
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.plugin.advancedSessions
 */
require_once __DIR__ . '/config.php';

AdvancedSessionsPlugin::create()->uninstall();
