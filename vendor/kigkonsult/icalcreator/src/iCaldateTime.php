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
 * iCalcreator::selectComponent dateTime support class
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.23.20 - 2017-02-07
 */
class iCaldateTime extends \DateTime {
/**
 * @var string default date[-time] format
 */
  public $dateFormat = 'Y-m-d H:i:s e';
/**
 * @var string default object instance date[-time] 'key'
 */
  public $key        = null;
/**
 * @var array date[-time] origin
 */
  public $SCbools    = [];
/**
 * Return time (His) array
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.23.20 - 2017-02-07
 * @return array
 */
  public function getTime() {
    static $H_I_S  = 'H:i:s';
    return explode( util::$COLON, $this->format( $H_I_S ));
  }
/**
 * Return the timezone name
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.7 - 2015-03-07
 * @return string
 */
  public function getTimezoneName() {
    $tz = $this->getTimezone();
    return $tz->getName();
  }
/**
 * Return formatted date
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.7 - 2015-03-07
 * @param string $format
 * @return string
 */
  public function format( $format=null ) {
    if( empty( $format ) && isset( $this->dateFormat ))
      $format = $this->dateFormat;
    return parent::format( $format );
  }
/**
 * Return iCaldateTime object instance based on date array and timezone(s)
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.23.20 - 2017-03-04
 * @param array  $date
 * @param array  $params
 * @param array  $tz
 * @param string $dtstartTz
 * @return object
 * @static
 */
  public static function factory( array $date, $params=null, $tz=null, $dtstartTz=null ) {
    static $YMDHIS = 'YmdHis';
    static $YMD    = 'Ymd';
    static $Y_M_D  = 'Y-m-d';
    if(     isset( $params[util::$TZID] ) && ! empty( $params[util::$TZID] ))
      $tz           = ( util::$Z == $params[util::$TZID] ) ? util::$UTC : $params[util::$TZID];
    elseif( isset( $tz[util::$LCtz] )     && ! empty( $tz[util::$LCtz] ))
      $tz           = ( util::$Z == $tz[util::$LCtz] )     ? util::$UTC : $tz[util::$LCtz];
    else
      $tz           = date_default_timezone_get();
    $strdate        = sprintf( util::$YMD, (int) $date[util::$LCYEAR],
                                           (int) $date[util::$LCMONTH],
                                           (int) $date[util::$LCDAY] );
    if( isset( $date[util::$LCHOUR] ))
      $strdate     .= util::$T . sprintf( util::$HIS, (int) $date[util::$LCHOUR],
                                                      (int) $date[util::$LCMIN],
                                                      (int) $date[util::$LCSEC] );
    try {
      $timezone     = new \DateTimeZone( $tz );
      $iCaldateTime = new iCaldateTime( $strdate, $timezone );
    }
    catch( \Exception $e ) {
      $iCaldateTime = new iCaldateTime( $strdate );
    }
    if( ! empty( $dtstartTz )) {
      if( util::$Z == $dtstartTz )
        $dtstartTz  = util::$UTC;
      if( $dtstartTz != $iCaldateTime->getTimezoneName()) { // set the same timezone as dtstart
        try {
          $timezone = new \DateTimeZone( $dtstartTz );
          $iCaldateTime->setTimezone( $timezone );
        }
        catch( \Exception $e ) {} // ??
      }
    }
    if( util::isParamsValueSet( [util::$LCparams => $params], util::$DATE )) {
      $iCaldateTime->dateFormat = $Y_M_D;
      $iCaldateTime->key        = $iCaldateTime->format( $YMD );
    }
    else
      $iCaldateTime->key        = $iCaldateTime->format( $YMDHIS );
    return $iCaldateTime;
  }
}
