<?php

/* For licensing terms, see /license.txt */
/**
 * Initialization uninstall.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
require_once __DIR__.'/config.php';

api_protect_admin_script();

Tour::create()->uninstall();
