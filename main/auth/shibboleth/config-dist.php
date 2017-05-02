<?php

namespace Shibboleth;

/**
 * Example of a config.php file. Not used. Configuration must appear in 
 * config.php.
 * 
 * By default set up the aai configuration.
 *
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info>, Nicolas Rod for the University of Geneva
 */
require_once __DIR__.'/config/aai.class.php';

Shibboleth::set_config(aai::config());