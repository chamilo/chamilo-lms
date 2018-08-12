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
 * iCalcreator timezone management class
 *
 * Manages loosely coupled iCalcreator vcalendar (timezone) functions
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.23 - 2017-04-07
 */
class timezoneHandler {
  private static $FMTTIMESTAMP = '@%s';
  private static $OFFSET       = 'offset';
  private static $TIME         = 'time';
/**
 * Create a calendar timezone and standard/daylight components
 *
 * Result when 'Europe/Stockholm' and no from/to arguments is used as timezone:
 * BEGIN:VTIMEZONE
 * TZID:Europe/Stockholm
 * BEGIN:STANDARD
 * DTSTART:20101031T020000
 * TZOFFSETFROM:+0200
 * TZOFFSETTO:+0100
 * TZNAME:CET
 * END:STANDARD
 * BEGIN:DAYLIGHT
 * DTSTART:20100328T030000
 * TZOFFSETFROM:+0100
 * TZOFFSETTO:+0200
 * TZNAME:CEST
 * END:DAYLIGHT
 * END:VTIMEZONE
 *
 * Generates components for all transitions in a date range,
 *   based on contribution by Yitzchok Lavi <icalcreator@onebigsystem.com>
 * Additional changes jpirkey
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.23.20 - 2017-06-26
 * @param vcalendar $calendar  iCalcreator calendar instance
 * @param string    $timezone  valid timezone avveptable by PHP5 DateTimeZone
 * @param array     $xProp     *[x-propName => x-propValue]
 * @param int       $from      unix timestamp
 * @param int       $to        unix timestamp
 * @return bool
 * @static
 */
  public static function createTimezone( vcalendar $calendar, $timezone, $xProp=[], $from=null, $to=null ) {
    static $Y            = 'Y  ';
    static $YMD          = 'Ymd';
    static $T000000      = 'T000000';
    static $MINUS7MONTH  = '-7 month';
    static $YMD2         = 'Y-m-d';
    static $T235959      = 'T235959';
    static $PLUS18MONTH  = '+18 month';
    static $TS           = 'ts';
    static $YMDHIS3      = 'Y-m-d-H-i-s';
    static $SECONDS      = 'seconds';
    static $ABBR         = 'abbr';
    static $ISDST        = 'isdst';
    static $NOW          = 'now';
    static $YMDTHISO     = 'Y-m-d\TH:i:s O';
    if( empty( $timezone ))
      return false;
    if( ! empty( $from ) && ! is_int( $from ))
      return false;
    if( ! empty( $to )   && ! is_int( $to ))
      return false;
    try {
      $newTz             = new \DateTimeZone( $timezone );
      $utcTz             = new \DateTimeZone( util::$UTC );
    }
    catch( \Exception $e ) {
      return false;
    }
    if( empty( $from ) || empty( $to )) {
      $dates             = array_keys( $calendar->getProperty( util::$DTSTART ));
      if( empty( $dates ))
        $dates           = [date( $YMD )];
    }
    if( ! empty( $from )) {
      try {
        $timestamp       = sprintf( self::$FMTTIMESTAMP, $from );
        $dateFrom        = new \DateTime( $timestamp );      // set lowest date (UTC)
      }
      catch( \Exception $e ) {
        return false;
      }
    }
    else {
      try {
        $from            = reset( $dates );                 // set lowest date to the lowest dtstart date
        $dateFrom        = new \DateTime( $from . $T000000, $newTz );
        $dateFrom->modify( $MINUS7MONTH );                  // set $dateFrom to seven month before the lowest date
        $dateFrom->setTimezone( $utcTz );                   // convert local date to UTC
      }
      catch( \Exception $e ) {
        return false;
      }
    }
    $dateFromYmd         = $dateFrom->format( $YMD2 );
    if( ! empty( $to )) {
      try {
        $timestamp       = sprintf( self::$FMTTIMESTAMP, $to );
        $dateTo          = new \DateTime( $timestamp );     // set end date (UTC)
      }
      catch( \Exception $e ) {
        return false;
      }
    }
    else {
      try {
        $to              = end( $dates );                   // set highest date to the highest dtstart date
        $dateTo          = new \DateTime( $to . $T235959, $newTz );
      }
      catch( \Exception $e ) {
        return false;
      }
      $dateTo->modify( $PLUS18MONTH );                      // set $dateTo to 18 month after the highest date
      $dateTo->setTimezone( $utcTz );                       // convert local date to UTC
    }
    $dateToYmd           = $dateTo->format( $YMD2 );
    $transTemp           = [];
    $prevOffsetfrom      = 0;
    $stdIx  = $dlghtIx   = null;
    $prevTrans           = false;
    $transitions         = $newTz->getTransitions();
    foreach( $transitions as $tix => $trans ) {             // all transitions in date-time order!!
      if( 0 > (int) date( $Y, $trans[$TS] )) {              // skip negative year... but save offset
        $prevOffsetfrom  = $trans[self::$OFFSET];           // previous trans offset will be 'next' trans offsetFrom
        continue;
      }
      try {
        $timestamp       = sprintf( self::$FMTTIMESTAMP, $trans[$TS] );
        $date            = new \DateTime( $timestamp );     // set transition date (UTC)
      }
      catch( \Exception $e ) {
        return false;
      }
      $transDateYmd      = $date->format( $YMD2 );
      if( $transDateYmd < $dateFromYmd ) {
        $prevOffsetfrom  = $trans[self::$OFFSET];           // previous trans offset will be 'next' trans offsetFrom
        $prevTrans       = $trans;                          // we save it in case we don't find any that match
        $prevTrans[util::$TZOFFSETFROM] = ( 0 < $tix ) ? $transitions[$tix-1][self::$OFFSET] : 0;
        continue;
      }
      if( $transDateYmd > $dateToYmd )
        break;                                              // loop always (?) breaks here
      if( ! empty( $prevOffsetfrom ) || ( 0 == $prevOffsetfrom )) {
        $trans[util::$TZOFFSETFROM] = $prevOffsetfrom;      // i.e. set previous offsetto as offsetFrom
        $date->modify( $trans[util::$TZOFFSETFROM] . $SECONDS );    // convert utc date to local date
        $d               = explode( util::$MINUS, $date->format( $YMDHIS3 ));
        $trans[self::$TIME] = [util::$LCYEAR  => (int) $d[0], // set date to array
                               util::$LCMONTH => (int) $d[1], //  to ease up dtstart and (opt) rdate setting
                               util::$LCDAY   => (int) $d[2],
                               util::$LCHOUR  => (int) $d[3],
                               util::$LCMIN   => (int) $d[4],
                               util::$LCSEC   => (int) $d[5]];
      }
      $prevOffsetfrom    = $trans[self::$OFFSET];
      if( true !== $trans[$ISDST] ) {                       // standard timezone
        if( ! empty( $stdIx ) && isset( $transTemp[$stdIx][util::$TZOFFSETFROM] )     &&
           ( $transTemp[$stdIx][$ABBR]               == $trans[$ABBR] )               &&
           ( $transTemp[$stdIx][util::$TZOFFSETFROM] == $trans[util::$TZOFFSETFROM] ) &&
           ( $transTemp[$stdIx][self::$OFFSET]       == $trans[self::$OFFSET] )) {
          $transTemp[$stdIx][util::$RDATE][]          = $trans[self::$TIME];
          continue; // check for any repeating rdate's (in order)
        }
        $stdIx           = $tix;
      } // end standard timezone
      else {                                                // daylight timezone
        if( ! empty( $dlghtIx ) && isset( $transTemp[$dlghtIx][util::$TZOFFSETFROM] )   &&
           ( $transTemp[$dlghtIx][$ABBR]               == $trans[$ABBR] )               &&
           ( $transTemp[$dlghtIx][util::$TZOFFSETFROM] == $trans[util::$TZOFFSETFROM] ) &&
           ( $transTemp[$dlghtIx][self::$OFFSET]       == $trans[self::$OFFSET] )) {
          $transTemp[$dlghtIx][util::$RDATE][]          = $trans[self::$TIME];
          continue; // check for any repeating rdate's (in order)
        }
        $dlghtIx         = $tix;
      } // end daylight timezone
      $transTemp[$tix]   = $trans;
    } // end foreach( $transitions as $tix => $trans )
    $timezoneComp        = $calendar->newVtimezone();
    $timezoneComp->setproperty( util::$TZID, $timezone );
    if( ! empty( $xProp )) {
      foreach( $xProp as $xPropName => $xPropValue )
        if( util::isXprefixed( $xPropName ))
          $timezoneComp->setproperty( $xPropName, $xPropValue );
    }
    if( empty( $transTemp )) {      // if no match is found
      if( $prevTrans ) {            // we use the last transition (before startdate) for the tz info
        try {
          $timestamp     = sprintf( self::$FMTTIMESTAMP, $prevTrans[$TS] );
          $date          = new \DateTime( $timestamp );     // set transition date (UTC)
        }
        catch( \Exception $e ) {
          return false;
        }
        $date->modify( $prevTrans[util::$TZOFFSETFROM] . $SECONDS );// convert utc date to local date
        $d               = explode( util::$MINUS, $date->format( $YMDHIS3 )); // set arr-date to ease up dtstart setting
        $prevTrans[self::$TIME] = [util::$LCYEAR  => (int) $d[0],
                                   util::$LCMONTH => (int) $d[1],
                                   util::$LCDAY   => (int) $d[2],
                                   util::$LCHOUR  => (int) $d[3],
                                   util::$LCMIN   => (int) $d[4],
                                   util::$LCSEC   => (int) $d[5]];
        $transTemp[0] = $prevTrans;
      } // end if( $prevTrans )
      else {                        // or we use the timezone identifier to BUILD the standard tz info (?)
        try {
          $date          = new \DateTime( $NOW, $newTz );
        }
        catch( \Exception $e ) {
          return false;
        }
        $transTemp[0]    = [self::$TIME         => $date->format( $YMDTHISO ),
                            self::$OFFSET       => $date->format( util::$Z ),
                            util::$TZOFFSETFROM => $date->format( util::$Z ),
                            $ISDST              => false];
      }
    } // end if( empty( $transTemp ))
    foreach( $transTemp as $tix => $trans ) { // create standard/daylight subcomponents
      $subComp           = ( true !== $trans[$ISDST] )
                         ? $timezoneComp->newStandard()
                         : $timezoneComp->newDaylight();
      $subComp->setProperty( util::$DTSTART,  $trans[self::$TIME] );
//      $subComp->setProperty( 'x-utc-timestamp', $tix.' : '.$trans[$TS] );   // test ###
      if( ! empty( $trans[$ABBR] ))
        $subComp->setProperty( util::$TZNAME, $trans[$ABBR] );
      if( isset( $trans[util::$TZOFFSETFROM] ))
        $subComp->setProperty( util::$TZOFFSETFROM, self::offsetSec2His( $trans[util::$TZOFFSETFROM] ));
      $subComp->setProperty( util::$TZOFFSETTO,     self::offsetSec2His( $trans[self::$OFFSET] ));
      if( isset( $trans[util::$RDATE] ))
        $subComp->setProperty( util::$RDATE,  $trans[util::$RDATE] );
    }
    return true;
  }
/**
 * Return iCal offset [-/+]hhmm[ss] (string) from UTC offset seconds
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.23 - 2017-02-17
 * @param string $seconds
 * @return string
 * @static
 */
  public static function offsetSec2His( $seconds ) {
    static $FMT = '%02d';
    switch( substr( $seconds, 0, 1 )) {
      case util::$MINUS :
        $output = util::$MINUS;
        $seconds = substr( $seconds, 1 );
        break;
      case util::$PLUS :
        $output = util::$PLUS;
        $seconds = substr( $seconds, 1 );
        break;
      default :
        $output = util::$PLUS;
        break;
    }
    $output .= sprintf( $FMT, ((int) floor( $seconds / 3600 ))); // hour
    $seconds = $seconds % 3600;
    $output .= sprintf( $FMT, ((int) floor( $seconds / 60 )));   // min
    $seconds = $seconds % 60;
    if( 0 < $seconds )
      $output .= sprintf( $FMT, $seconds );                      // sec
    return $output;
  }
/**
 * Very basic conversion of a MS timezone to a PHP5 valid (Date-)timezone
 * matching (MS) UCT offset and time zone descriptors
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.23.8 - 2017-04-17
 * @param string $timezone     to convert
 * @return bool
 * @static
 */
  public static function ms2phpTZ( & $timezone ) {
    static $REPL1  = ['GMT', 'gmt', 'utc'];
    static $REPL2  = ['(', ')', '&', ',', '  '];
    static $PUTC   = '(UTC';
    static $ENDP   = ')';
    static $TIMEZONE_ID = 'timezone_id';
    if( empty( $timezone ))
      return false;
    $search = str_replace( util::$QQ, null, $timezone );
    $search = str_replace( $REPL1, util::$UTC, $search );
    if( $PUTC != substr( $search, 0, 4 ))
      return false;
    if( false === ( $pos = strpos( $search, $ENDP )))
      return false;
    $searchOffset = substr( $search, 4, ( $pos - 4 ));
    $searchOffset = util::tz2offset( str_replace( util::$COLON,
                                                  null,
                                                  $searchOffset ));
    while( util::$SP1 == $search[($pos+1)] )
      $pos += 1;
    $searchText   = trim( str_replace( $REPL2,
                                       util::$SP1,
                                       substr( $search, ( $pos + 1 ))));
    $searchWords  = explode( util::$SP1, $searchText );
    try {
      $timezoneAbbreviations = \DateTimeZone::listAbbreviations();
    }
    catch( \Exception $e ) {
      return false;
    }
    $hits = [];
    foreach( $timezoneAbbreviations as $name => $transitions ) {
      foreach( $transitions as $cnt => $transition ) {
        if( empty( $transition[self::$OFFSET] ) ||
            empty( $transition[$TIMEZONE_ID] )  ||
          ( $transition[self::$OFFSET] != $searchOffset ))
        continue;
        $cWords = explode( util::$L, $transition[$TIMEZONE_ID] );
        $cPrio   = $hitCnt = $rank = 0;
        foreach( $cWords as $cWord ) {
          if( empty( $cWord ))
            continue;
          $cPrio += 1;
          $sPrio  = 0;
          foreach( $searchWords as $sWord ) {
            if( empty( $sWord ) || ( self::$TIME == strtolower( $sWord )))
              continue;
            $sPrio += 1;
            if( strtolower( $cWord ) == strtolower( $sWord )) {
              $hitCnt += 1;
              $rank   += ( $cPrio + $sPrio );
            }
            else
              $rank += 10;
          }
        }
        if( 0 < $hitCnt ) {
          $hits[$rank][] = $transition[$TIMEZONE_ID];
        }
      } // end foreach( $transitions as $cnt => $transition )
    } // end foreach( $timezoneAbbreviations as $name => $transitions )
    if( empty( $hits ))
      return false;
    ksort( $hits );
    foreach( $hits as $rank => $tzs ) {
      if( ! empty( $tzs )) {
        $timezone = reset( $tzs );
        return true;
      }
    }
    return false;
  }
/**
 * Transforms a dateTime from a timezone to another
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.23 - 2017-03-04
 * @param mixed  $date    date to alter
 * @param string $tzFrom  PHP valid 'from' timezone
 * @param string $tzTo    PHP valid 'to' timezone, default util::$UTC
 * @param string $format  date output format, default 'Ymd\THis'
 * @return bool true on success, false on error
 * @static
 */
  public static function transformDateTime( & $date, $tzFrom, $tzTo=null, $format=null ) {
    static $YMDTHIS = 'Ymd\THis';
    if( is_null( $tzTo ))
      $tzTo    = util::$UTC;
    elseif( util::$Z == $tzTo )
      $tzTo = util::$UTC;
    if( is_null( $format ))
      $format  = $YMDTHIS;
    if( is_array( $date ) && isset( $date[util::$LCTIMESTAMP] )) {
      try {
        $timestamp = sprintf( self::$FMTTIMESTAMP, $date[util::$LCTIMESTAMP] );
        $d     = new \DateTime( $timestamp ); // set UTC date
        $newTz = new \DateTimeZone( $tzFrom );
        $d->setTimezone( $newTz );           // convert to 'from' date
      }
      catch( \Exception $e ) {
        return false;
      }
    }
    else {
      if( util::isArrayDate( $date )) {
        if( isset( $date[util::$LCtz] ))
          unset( $date[util::$LCtz] );
        $date  = util::date2strdate( util::chkDateArr( $date ));
      }
      if( util::$Z == substr( $date, -1 ))
        $date  = substr( $date, 0, ( strlen( $date ) - 2 ));
      try {
        $newTz = new \DateTimeZone( $tzFrom );
        $d     = new \DateTime( $date, $newTz );
      }
      catch( \Exception $e ) {
        return false;
      }
    }
    try {
      $newTz   = new \DateTimeZone( $tzTo );
      $d->setTimezone( $newTz );
    }
    catch( \Exception $e ) {
      return false;
    }
    $date = $d->format( $format );
    return true;
  }
}
