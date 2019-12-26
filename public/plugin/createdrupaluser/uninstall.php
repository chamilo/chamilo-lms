<?php

/* For licensing terms, see /license.txt */
/**
 * Initialization uninstall.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
require_once __DIR__.'/config.php';

CreateDrupalUser::create()->uninstall();
