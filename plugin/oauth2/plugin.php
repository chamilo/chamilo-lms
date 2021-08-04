<?php
/* For licensing terms, see /license.txt */
/**
 * @author Sébastien Ducoulombier <seb@ldd.fr>, inspired by Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 *
 * @package chamilo.plugin.oauth2
 */
/** @var OAuth2 $plugin_info */
$plugin_info = OAuth2::create()->get_info();

$plugin_info['templates'] = ['view/block.tpl'];
