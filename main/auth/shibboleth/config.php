<?php

/**
 * Shibboleth configuration. See /config/aai.php for an example.
 *
 * @copyright (c) 2012 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht <laurent@opprecht.info>, Nicolas Rod
 */
require_once dirname(__FILE__) . '/config/aai.class.php';

Shibboleth::set_config(aai::config());