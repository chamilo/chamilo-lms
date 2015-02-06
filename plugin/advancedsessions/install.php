<?php
/* For licensing terms, see /license.txt */
/**
 * Plugin Installation
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.plugin.advancedSessions
 */
require_once __DIR__ . '/config.php';

AdvancedSessionsPlugin::create()->install();
