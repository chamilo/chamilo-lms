<?php
/* For licensing terms, see /license.txt */
/**
 * Initialization uninstall.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 *
 * @package chamilo.plugin.tour
 */
require_once __DIR__.'/config.php';

Tour::create()->uninstall();
