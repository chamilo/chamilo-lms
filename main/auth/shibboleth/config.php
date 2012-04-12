<?php

/**
 * Shibboleth configuration. See /config/aai.php for an example.
 *
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info>, Nicolas Rod for the University of Geneva
 */
require_once dirname(__FILE__) . '/config/aai.class.php';

Shibboleth::set_config(aai::config());