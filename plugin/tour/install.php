<?php

/* For licensing terms, see /license.txt */

/**
 * Initialization install
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.plugin.tour
 */
require_once dirname(__FILE__) . '/config.php';

Tour::create()->install();
