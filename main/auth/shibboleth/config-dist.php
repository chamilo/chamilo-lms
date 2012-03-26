<?php

/**
 * Example of a config.php file. Not used. Configuration must appear in 
 * config.php.
 * 
 * By default set up the aai configuration.
 *
 * @copyright (c) 2012 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht <laurent@opprecht.info>, Nicolas Rod
 */
require_once dirname(__FILE__) . '/config/aai.class.php';

Shibboleth::set_config(aai::config());