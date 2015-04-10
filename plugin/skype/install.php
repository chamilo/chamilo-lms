<?php
/* For licensing terms, see /license.txt */
/**
 * Initialization install
 * @author Imanol Losada Oriol <imanol.losada@beeznest.com>
 * @package chamilo.plugin.skype
 */
require_once __DIR__ . '/config.php';

Skype::create()->install();
