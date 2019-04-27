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
namespace kigkonsult\iCalcreator\traits;
use kigkonsult\iCalcreator\util\util;
/**
 * TRIGGER property functions
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.23 - 2017-02-05
 */
trait TRIGGERtrait {
/**
 * @var array component property TRIGGER value
 * @access protected
 */
  protected $trigger = null;
/**
 * Return formatted output for calendar component property trigger
 *
 * @return string
 */
  public function createTrigger() {
    static $RELATEDSTART = 'relatedStart';
    static $BEFORE       = 'before';
    static $RELATED_END  = 'RELATED=END';
    if( empty( $this->trigger ))
      return null;
    if( empty( $this->trigger[util::$LCvalue] ))
      return ( $this->getConfig( util::$ALLOWEMPTY )) ? util::createElement( util::$TRIGGER ) : null;
    $content      = $attributes = null;
    if( isset( $this->trigger[util::$LCvalue][util::$LCYEAR] )   &&
        isset( $this->trigger[util::$LCvalue][util::$LCMONTH] )  &&
        isset( $this->trigger[util::$LCvalue][util::$LCDAY] ))
      $content   .= util::date2strdate( $this->trigger[util::$LCvalue] );
    else {
      if( true !== $this->trigger[util::$LCvalue][$RELATEDSTART] )
        $attributes .= util::$SEMIC . $RELATED_END;
      if( $this->trigger[util::$LCvalue][$BEFORE] )
        $content .= util::$MINUS;
      $content   .= util::duration2str( $this->trigger[util::$LCvalue] );
    }
    $attributes  .= util::createParams( $this->trigger[util::$LCparams] );
    return util::createElement( util::$TRIGGER,
                                $attributes,
                                $content );
  }
/**
 * Set calendar component property trigger
 *
 * @param mixed  $year
 * @param mixed  $month
 * @param int    $day
 * @param int    $week
 * @param int    $hour
 * @param int    $min
 * @param int    $sec
 * @param bool   $relatedStart
 * @param bool   $before
 * @param array  $params
 * @return bool
 */
  public function setTrigger( $year=null, $month=null, $day=null, $week=null, $hour=null, $min=null, $sec=null,
                              $relatedStart=null, $before=null, $params=null ) {
    static $PREFIXARR    = ['P', '+', '-'];
    static $P            = 'P';
    static $RELATEDSTART = 'relatedStart';
    static $BEFORE       = 'before';
    static $RELATED      = 'RELATED';
    static $END          = 'END';
    if( empty( $year ) &&
      ( empty( $month ) || is_array( $month )) &&
        empty( $day ) && empty( $week ) && empty( $hour ) && empty( $min ) && empty( $sec )) {
      if( $this->getConfig( util::$ALLOWEMPTY )) {
        $this->trigger = [util::$LCvalue  => util::$EMPTYPROPERTY,
                          util::$LCparams => util::setParams( $month )];
        return true;
      }
      else
        return false;
    }
    if( is_null( $relatedStart ))
      $relatedStart = true;
    if( is_null( $before ))
      $before       = true;
    switch( true ) {
      case( util::isArrayTimestampDate( $year )) : // timestamp UTC
        $params = util::setParams( $month );
        $date   = util::timestamp2date( $year, 7 );
        foreach( $date as $k => $v )
          $$k = $v;
        break;
      case( is_array( $year ) && ( is_array( $month ) || empty( $month ))) :
        $params = util::setParams( $month );
        if( ! ( array_key_exists( util::$LCYEAR,  $year ) &&   // exclude date-time
                array_key_exists( util::$LCMONTH, $year ) &&
                array_key_exists( util::$LCDAY,   $year ))) {  // when this must be a duration
          if( isset( $params[$RELATED] ) && ( 0 == strcasecmp( $END, $params[$RELATED] )))
            $relatedStart = false;
          else
            $relatedStart = ( array_key_exists( $RELATEDSTART, $year ) &&
                               ( true !== $year[$RELATEDSTART] )) ? false : true;
          $before         = ( array_key_exists( $BEFORE, $year ) &&
                               ( true !== $year[$BEFORE] ))       ? false : true;
        }
        $SSYY  = ( array_key_exists( util::$LCYEAR,  $year )) ? $year[util::$LCYEAR]  : null;
        $month = ( array_key_exists( util::$LCMONTH, $year )) ? $year[util::$LCMONTH] : null;
        $day   = ( array_key_exists( util::$LCDAY,   $year )) ? $year[util::$LCDAY]   : null;
        $week  = ( array_key_exists( util::$LCWEEK,  $year )) ? $year[util::$LCWEEK]  : null;
        $hour  = ( array_key_exists( util::$LCHOUR,  $year )) ? $year[util::$LCHOUR]  : 0; //null;
        $min   = ( array_key_exists( util::$LCMIN,   $year )) ? $year[util::$LCMIN]   : 0; //null;
        $sec   = ( array_key_exists( util::$LCSEC,   $year )) ? $year[util::$LCSEC]   : 0; //null;
        $year  = $SSYY;
        break;
      case( is_string( $year ) && ( is_array( $month ) || empty( $month ))) :  // duration or date in a string
        $params = util::setParams( $month );
        if( in_array( $year{0}, $PREFIXARR )) { // duration
          $relatedStart = ( isset( $params[$RELATED] ) && ( 0 == strcasecmp( $END, $params[$RELATED] ))) ? false : true;
          $before       = ( util::$MINUS  == $year[0] ) ? true : false;
          if(      $P  != $year[0] )
            $year       = substr( $year, 1 );
          $date         = util::durationStr2arr( $year);
        }
        else   // date
          $date    = util::strDate2ArrayDate( $year, 7 );
        unset( $year, $month, $day, $date[util::$UNPARSEDTEXT] );
        if( empty( $date ))
          $sec = 0;
        else
          foreach( $date as $k => $v )
            $$k = $v;
        break;
      default : // single values in function input parameters
        $params = util::setParams( $params );
        break;
    } // end switch( true )
    if( ! empty( $year ) && ! empty( $month ) && ! empty( $day )) { // date
      $params[util::$VALUE] = util::$DATE_TIME;
      $hour = ( $hour ) ? $hour : 0;
      $min  = ( $min  ) ? $min  : 0;
      $sec  = ( $sec  ) ? $sec  : 0;
      $this->trigger = [util::$LCparams => $params];
      $this->trigger[util::$LCvalue] = [util::$LCYEAR  => $year,
                                        util::$LCMONTH => $month,
                                        util::$LCDAY   => $day,
                                        util::$LCHOUR  => $hour,
                                        util::$LCMIN   => $min,
                                        util::$LCSEC   => $sec,
                                        util::$LCtz    => util::$Z];
      return true;
    }
    elseif(( empty( $year ) && empty( $month )) &&    // duration
        (( ! empty( $week ) || ( 0 == $week )) ||
         ( ! empty( $day )  || ( 0 == $day  )) ||
         ( ! empty( $hour ) || ( 0 == $hour )) ||
         ( ! empty( $min )  || ( 0 == $min  )) ||
         ( ! empty( $sec )  || ( 0 == $sec  )))) {
      unset( $params[$RELATED] );     // set at output creation (END only)
      unset( $params[util::$VALUE] ); // util::$DURATION default
      $this->trigger = [util::$LCparams => $params];
      $this->trigger[util::$LCvalue]  = [];
      if( ! empty( $week ))
        $this->trigger[util::$LCvalue][util::$LCWEEK] = $week;
      if( ! empty( $day  ))
        $this->trigger[util::$LCvalue][util::$LCDAY]  = $day;
      if( ! empty( $hour ))
        $this->trigger[util::$LCvalue][util::$LCHOUR] = $hour;
      if( ! empty( $min  ))
        $this->trigger[util::$LCvalue][util::$LCMIN]  = $min;
      if( ! empty( $sec  ))
        $this->trigger[util::$LCvalue][util::$LCSEC]  = $sec;
      if( empty( $this->trigger[util::$LCvalue] )) {
        $this->trigger[util::$LCvalue][util::$LCSEC] = 0;
        $before                        = false;
      }
      else
        $this->trigger[util::$LCvalue] = util::duration2arr( $this->trigger[util::$LCvalue] );
      $relatedStart = ( false !== $relatedStart ) ? true : false;
      $before       = ( false !== $before )       ? true : false;
      $this->trigger[util::$LCvalue][$RELATEDSTART] = $relatedStart;
      $this->trigger[util::$LCvalue][$BEFORE]       = $before;
      return true;
    }
    return false;
  }
}
