<?php
/* For licensing terms, see /license.txt */
/**
 * Initialization install.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 *
 * @package chamilo.plugin.createDrupalUser
 */
require_once __DIR__.'/config.php';

CreateDrupalUser::create()->install();
