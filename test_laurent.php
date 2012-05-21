<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 *
 * @copyright (c) 2012 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht <laurent@opprecht.info>
 */
require_once 'main/inc/global.inc.php';
//require_once 'main/install/install.class.php';

$r = new AutoloadClassFinder();
$r();
echo $r;

