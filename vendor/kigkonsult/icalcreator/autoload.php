<?php
/**
 * iCalcreator, a PHP rfc2445/rfc5545 solution.
 *
 * This file is a part of iCalcreator.
 *
 * Copyright (c) 2007-2017 Kjell-Inge Gustafsson, kigkonsult, All rights reserved
 * Link      http://kigkonsult.se/iCalcreator/index.php
 * Package   iCalcreator
 * Version   2.24
 * License   Subject matter of licence is the software iCalcreator.
 *           The above copyright, link, package and version notices,
 *           this licence notice and the [rfc5545] PRODID as implemented and
 *           invoked in iCalcreator shall be included in all copies or
 *           substantial portions of the iCalcreator.
 *           iCalcreator can be used either under the terms of
 *           a proprietary license, available at <https://kigkonsult.se/>
 *           or the GNU Affero General Public License, version 3:
 *           iCalcreator is free software: you can redistribute it and/or
 *           modify it under the terms of the GNU Affero General Public License
 *           as published by the Free Software Foundation, either version 3 of
 *           the License, or (at your option) any later version.
 *           iCalcreator is distributed in the hope that it will be useful,
 *           but WITHOUT ANY WARRANTY; without even the implied warranty of
 *           MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *           GNU Affero General Public License for more details.
 *           You should have received a copy of the GNU Affero General Public
 *           License along with this program.
 *           If not, see <http://www.gnu.org/licenses/>.
 */
/**
 * autoload.php
 *
 * iCalcreator package autoloader
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.23.16 - 2017-05-27
 */
/**
 *         Do NOT alter or remove the constant!!
 */
define( 'ICALCREATOR_VERSION', 'iCalcreator 2.24' );
/**
 * load iCalcreator src and support classes and traits
 */
spl_autoload_register(
  function( $class ) {
    static $SRC      = 'src';
    static $BS       = '\\';
    static $PHP      = '.php';
    static $PREFIX   = 'kigkonsult\\iCalcreator\\';
    static $BASEDIR  = null;
    if( is_null( $BASEDIR ))
      $BASEDIR       = __DIR__ . DIRECTORY_SEPARATOR . $SRC . DIRECTORY_SEPARATOR;
    if( 0 != strncmp( $PREFIX, $class, 23 ))
      return false;
    $class   = substr( $class, 23 );
    if( false !== strpos( $class, $BS ))
      $class = str_replace( $BS, DIRECTORY_SEPARATOR, $class );
    $file    = $BASEDIR . $class . $PHP;
    if( file_exists( $file )) {
      require $file;
      return true;
    }
    return false;
  }
);
/**
 * iCalcreator timezones add-on functionality functions, IF required?
 */
// include __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'iCal.tz.inc.php';
