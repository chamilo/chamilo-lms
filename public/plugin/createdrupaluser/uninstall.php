<?php
/* For licensing terms, see /license.txt */
/**
 * Initialization uninstall.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 *
 * @package chamilo.plugin.createDrupalUser
 */
require_once __DIR__.'/config.php';

CreateDrupalUser::create()->uninstall();
