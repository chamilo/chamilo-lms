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
namespace kigkonsult\iCalcreator;
use kigkonsult\iCalcreator\util\util;
/**
 * iCalcreator VTIMEZONE component class
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.23 - 2017-02-01
 */
class vtimezone extends calendarComponent {
  use traits\COMMENTtrait,
      traits\DTSTARTtrait,
      traits\LAST_MODIFIEDtrait,
      traits\RDATEtrait,
      traits\RRULEtrait,
      traits\TZIDtrait,
      traits\TZNAMEtrait,
      traits\TZOFFSETFROMtrait,
      traits\TZOFFSETTOtrait,
      traits\TZURLtrait;
/**
 * @var string $timezonetype  vtimezone type value
 * @access protected
 */
  protected $timezonetype;
/**
 * Constructor for calendar component VTIMEZONE object
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.23 - 2017-02-01
 * @param mixed $timezonetype  default false ( STANDARD / DAYLIGHT )
 * @param array $config
 */
  public function __construct( $timezonetype=null, $config = []) {
    static $TZ = 'tz';
    if( is_array( $timezonetype )) {
      $config       = $timezonetype;
      $timezonetype = null;
    }
    $this->timezonetype = ( empty( $timezonetype ))
                        ? util::$LCVTIMEZONE : strtolower( $timezonetype );
    parent::__construct();
    $this->setConfig( util::initConfig( $config ));
    $prf = ( empty( $timezonetype )) ? $TZ : substr( $timezonetype, 0, 1 );
    $this->cno = $prf . parent::getObjectNo();
  }
/**
 * Destructor
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.23 - 2017-03-17
 */
  public function __destruct() {
    if( ! empty( $this->components ))
      foreach( $this->components as $cix => $component )
        $this->components[$cix]->__destruct();
    unset( $this->xprop,
           $this->components,
           $this->unparsed,
           $this->config,
           $this->propix,
           $this->compix,
           $this->propdelix );
    unset( $this->objName,
           $this->cno );
    unset( $this->comment,
           $this->dtstart,
           $this->lastmodified,
           $this->rdate,
           $this->rrule,
           $this->tzid,
           $this->tzname,
           $this->tzoffsetfrom,
           $this->tzoffsetto,
           $this->tzurl,
           $this->timezonetype );
  }
/**
 * Return formatted output for calendar component VTIMEZONE object instance
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.5.1 - 2008-10-25
 * @return string
 */
  public function createComponent() {
    $objectname = strtoupper(( isset( $this->timezonetype )) ? $this->timezonetype : $this->objName );
    $component  = sprintf( util::$FMTBEGIN, $objectname );
    $component .= $this->createTzid();
    $component .= $this->createLastModified();
    $component .= $this->createTzurl();
    $component .= $this->createDtstart();
    $component .= $this->createTzoffsetfrom();
    $component .= $this->createTzoffsetto();
    $component .= $this->createComment();
    $component .= $this->createRdate();
    $component .= $this->createRrule();
    $component .= $this->createTzname();
    $component .= $this->createXprop();
    $component .= $this->createSubComponent();
    return $component . sprintf( util::$FMTEND, $objectname );
  }
/**
 * Return vtimezone component property value/params
 *
 * If arg $inclParam, return array with keys VALUE/PARAMS
 * @param string  $propName
 * @param int     $propix   specific property in case of multiply occurences
 * @param bool    $inclParam
 * @param bool    $specform
 * @return mixed
 */
  public function getProperty( $propName=null,
                               $propix=null,
                               $inclParam=false,
                               $specform=false ) {
    switch( strtoupper( $propName )) {
      case util::$TZID:
        if( isset( $this->tzid[util::$LCvalue] ))
          return ( $inclParam ) ? $this->tzid
                                : $this->tzid[util::$LCvalue];
        break;
      case util::$TZOFFSETFROM:
        if( isset( $this->tzoffsetfrom[util::$LCvalue] ))
          return ( $inclParam ) ? $this->tzoffsetfrom
                                : $this->tzoffsetfrom[util::$LCvalue];
        break;
      case util::$TZOFFSETTO:
        if( isset( $this->tzoffsetto[util::$LCvalue] ))
          return ( $inclParam ) ? $this->tzoffsetto
                                : $this->tzoffsetto[util::$LCvalue];
        break;
      case util::$TZURL:
        if( isset( $this->tzurl[util::$LCvalue] ))
          return ( $inclParam ) ? $this->tzurl
                                : $this->tzurl[util::$LCvalue];
        break;
      default:
        return parent::getProperty( $propName,
                                    $propix,
                                    $inclParam,
                                    $specform );
        break;
    }
    return false;
  }
/**
 * Return timezone standard object instance, vtimezone::newComponent() wrapper
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.23.20 - 2017-06-26
 * @return object
 */
  public function newStandard() {
    return $this->newComponent( util::$LCSTANDARD );
  }
/**
 * Return timezone daylight object instance, vtimezone::newComponent() wrapper
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.23.20 - 2017-06-26
 * @return object
 */
  public function newDaylight() {
    return $this->newComponent( util::$LCDAYLIGHT );
  }
}
