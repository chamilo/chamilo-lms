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
 * Additional functions to use with vtimezone components
 *
 * Before calling the functions, set time zone 'GMT' ('date_default_timezone_set')!
 * @author Yitzchok Lavi <icalcreator@onebigsystem.com>
 *         adjusted for iCalcreator Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @version 1.0.2 - 2011-02-24
 */
namespace kigkonsult\iCalcreator;
use kigkonsult\iCalcreator\util\util;
/**
 * Returns array with the offset information
 *
 * From UTC for a (UTC) datetime/timestamp in the
 * timezone, according to the VTIMEZONE information in the input array.
 * @param array  $timezonesarray  output from function getTimezonesAsDateArrays (below)
 * @param string $tzid            time zone identifier
 * @param mixed  $timestamp       timestamp or a UTC datetime (in array format)
 * @return array                  time zone data with keys for $OFFSETHIS, $OFFSETSEC and $TZNAME
 */
function getTzOffsetForDate( $timezonesarray, $tzid, $timestamp ) {
  static $OFFSETHIS = 'offsetHis';
  static $OFFSETSEC = 'offsetSec';
  static $TZBEFORE  = 'tzbefore';
  static $TZAFTER   = 'tzafter';
  static $TZNAME    = 'tzname';
    if( is_array( $timestamp )) {
      $timestamp = gmmktime(
            $timestamp[util::$LCHOUR],
            $timestamp[util::$LCMIN],
            $timestamp[util::$LCSEC],
            $timestamp[util::$LCMONTH],
            $timestamp[util::$LCDAY],
            $timestamp[util::$LCYEAR]
            ) ;
    }
    $tzoffset = [];
    // something to return if all goes wrong (such as if $tzid doesn't find us an array of dates)
    $tzoffset[$OFFSETHIS] = '+0000';
    $tzoffset[$OFFSETSEC] = 0;
    $tzoffset[$TZNAME]    = '?';
    if( !isset( $timezonesarray[$tzid] ))
      return $tzoffset;
    $tzdatearray = $timezonesarray[$tzid];
    if ( is_array($tzdatearray) ) {
        sort($tzdatearray); // just in case
        if ( $timestamp < $tzdatearray[0][util::$LCTIMESTAMP] ) {
            // our date is before the first change
            $tzoffset[$OFFSETHIS]    = $tzdatearray[0][$TZBEFORE][$OFFSETHIS] ;
            $tzoffset[$OFFSETSEC]    = $tzdatearray[0][$TZBEFORE][$OFFSETSEC] ;
            $tzoffset[$TZNAME] = $tzdatearray[0][$TZBEFORE][$OFFSETHIS] ; // we don't know the tzname in this case
        } elseif ( $timestamp >= $tzdatearray[count($tzdatearray)-1][util::$LCTIMESTAMP] ) {
            // our date is after the last change (we do this so our scan can stop at the last record but one)
            $tzoffset[$OFFSETHIS]    = $tzdatearray[count($tzdatearray)-1][$TZAFTER][$OFFSETHIS] ;
            $tzoffset[$OFFSETSEC]    = $tzdatearray[count($tzdatearray)-1][$TZAFTER][$OFFSETSEC] ;
            $tzoffset[$TZNAME] = $tzdatearray[count($tzdatearray)-1][$TZAFTER][$TZNAME] ;
        } else {
            // our date somewhere in between
            // loop through the list of dates and stop at the one where the timestamp is before our date and the next one is after it
            // we don't include the last date in our loop as there isn't one after it to check
            for ( $i = 0 ; $i <= count($tzdatearray)-2 ; $i++ ) {
                if(( $timestamp >= $tzdatearray[$i][util::$LCTIMESTAMP] ) &&
                   ( $timestamp  < $tzdatearray[$i+1][util::$LCTIMESTAMP] )) {
                    $tzoffset[$OFFSETHIS] = $tzdatearray[$i][$TZAFTER][$OFFSETHIS] ;
                    $tzoffset[$OFFSETSEC] = $tzdatearray[$i][$TZAFTER][$OFFSETSEC] ;
                    $tzoffset[$TZNAME]    = $tzdatearray[$i][$TZAFTER][$TZNAME] ;
                    break;
                }
            }
        }
    }
    return $tzoffset;
}
/**
 * Return an array containing all the timezone data in the vcalendar object
 *
 * @param vcalendar $vcalendar  iCalcreator calendar instance
 * @return array    time zone transition timestamp,
                              array before(offsetHis, offsetSec),
                              array after(offsetHis, offsetSec, tzname)
                         based on the timezone data in the vcalendar object
 */
function getTimezonesAsDateArrays( $vcalendar ) {
    $timezonedata = [];
    while( $vtz = $vcalendar->getComponent( util::$LCVTIMEZONE )) {
        $tzid       = $vtz->getProperty('tzid');
        $alltzdates = [];
        while ( $vtzc = $vtz->getComponent( util::$LCSTANDARD )) {
            $newtzdates = expandTimezoneDates($vtzc);
            $alltzdates = array_merge($alltzdates, $newtzdates);
        }
        while ( $vtzc = $vtz->getComponent( util::$LCDAYLIGHT )) {
            $newtzdates = expandTimezoneDates($vtzc);
            $alltzdates = array_merge($alltzdates, $newtzdates);
        }
        sort($alltzdates);
        $timezonedata[$tzid] = $alltzdates;
    }
    return $timezonedata;
}
/**
 * Returns an array containing time zone data from vtimezone standard/daylight instances
 *
 * @param object $vtzc   an iCalcreator calendar standard/daylight instance
 * @return array         time zone data;
 *                         array before(offsetHis, offsetSec),
 *                         array after(offsetHis, offsetSec, tzname)
 * @todo fix quickfix...
 */
function expandTimezoneDates($vtzc) {
  static $OFFSETHIS = 'offsetHis';
  static $OFFSETSEC = 'offsetSec';
  static $TZBEFORE  = 'tzbefore';
  static $TZAFTER   = 'tzafter';
  static $TZNAME    = 'tzname';
  static $YEARLY    = 'YEARLY';
  static $FMTDATE   = '%04d%02d%02dT%02d%02d%02d';
  static $DAYNAMES  = ['SU' => 'Sunday',
                       'MO' => 'Monday',
                       'TU' => 'Tuesday',
                       'WE' => 'Wednesday',
                       'TH' => 'Thursday',
                       'FR' => 'Friday',
                       'SA' => 'Saturday'];
  static $MON        = 'mon';
  static $MDAY       = 'mday';
  static $HOURS      = 'hours';
  static $MINUTES    = 'minutes';
  static $SECONDS    = 'seconds';
  static $MINUS1WEEK = '-1 week';
  static $PLUS1MONTH = '+1 month';
  static $SP1WEEK    = ' week';
  static $SP1YEAR    = ' year';
  static $PLUS10YEAR = '+10 year';
    $tzdates = [];
    // prepare time zone "description" to attach to each change
    $tzbefore = [];
    $tzbefore[$OFFSETHIS] = $vtzc->getProperty(util::$TZOFFSETFROM) ;
    $tzbefore[$OFFSETSEC] = util::tz2offset($tzbefore[$OFFSETHIS]);
    if(( util::$MINUS != substr( (string) $tzbefore[$OFFSETSEC], 0, 1 )) &&
       ( util::$PLUS  != substr( (string) $tzbefore[$OFFSETSEC], 0, 1 )))
      $tzbefore[$OFFSETSEC] = util::$PLUS . $tzbefore[$OFFSETSEC];
    $tzafter = [];
    $tzafter[$OFFSETHIS]  = $vtzc->getProperty(util::$TZOFFSETTO) ;
    $tzafter[$OFFSETSEC]  = util::tz2offset($tzafter[$OFFSETHIS]);
    if(( util::$MINUS != substr( (string) $tzafter[$OFFSETSEC], 0, 1 )) &&
       ( util::$PLUS  != substr( (string) $tzafter[$OFFSETSEC], 0, 1 )))
      $tzafter[$OFFSETSEC] = util::$PLUS . $tzafter[$OFFSETSEC];
    if( false === ( $tzafter[$TZNAME] = $vtzc->getProperty(util::$TZNAME)))
      $tzafter[$TZNAME] = $tzafter[$OFFSETHIS];
    // find out where to start from
    $dtstart = $vtzc->getProperty(util::$DTSTART);
    $dtstarttimestamp = mktime( $dtstart[util::$LCHOUR],
                                $dtstart[util::$LCMIN],
                                $dtstart[util::$LCSEC],
                                $dtstart[util::$LCMONTH],
                                $dtstart[util::$LCDAY],
                                $dtstart[util::$LCYEAR] ) ;
    if( !isset( $dtstart[util::$UNPARSEDTEXT] )) // ??
      $dtstart[util::$UNPARSEDTEXT] = sprintf( $FMTDATE, $dtstart[util::$LCYEAR],
                                                         $dtstart[util::$LCMONTH],
                                                         $dtstart[util::$LCDAY],
                                                         $dtstart[util::$LCHOUR],
                                                         $dtstart[util::$LCMIN],
                                                         $dtstart[util::$LCSEC] );
    if ( $dtstarttimestamp == 0 ) {
        // it seems that the dtstart string may not have parsed correctly
        // let's set a timestamp starting from 1902, using the time part of the original string
        // so that the time will change at the right time of day
        // at worst we'll get midnight again
        $origdtstartsplit = explode('T',$dtstart[util::$UNPARSEDTEXT]) ;
        $dtstarttimestamp = strtotime('19020101',0);
        $dtstarttimestamp = strtotime($origdtstartsplit[1],$dtstarttimestamp);
    }
    // the date (in dtstart and opt RDATE/RRULE) is ALWAYS LOCAL (not utc!!), adjust from 'utc' to 'local' timestamp
    $diff  = -1 * $tzbefore[$OFFSETSEC];
    $dtstarttimestamp += $diff;
                // add this (start) change to the array of changes
    $tzdates[] = [util::$LCTIMESTAMP => $dtstarttimestamp,
                  $TZBEFORE  => $tzbefore,
                  $TZAFTER   => $tzafter];
    $datearray = getdate($dtstarttimestamp);
    // save original array to use time parts, because strtotime (used below) apparently loses the time
    $changetime = $datearray ;
    // generate dates according to an RRULE line
    $rrule = $vtzc->getProperty(util::$RRULE) ;
    if ( is_array($rrule) ) {
        if ( $rrule[util::$FREQ] == $YEARLY ) {
            // calculate transition dates starting from DTSTART
            $offsetchangetimestamp = $dtstarttimestamp;
            // calculate transition dates until 10 years in the future
            $stoptimestamp = strtotime($PLUS10YEAR,time());
            // if UNTIL is set, calculate until then (however far ahead)
            if ( isset( $rrule[util::$UNTIL] ) && ( $rrule[util::$UNTIL] != '' )) {
                $stoptimestamp = mktime(
                    $rrule[util::$UNTIL][util::$LCHOUR],
                    $rrule[util::$UNTIL][util::$LCMIN],
                    $rrule[util::$UNTIL][util::$LCSEC],
                    $rrule[util::$UNTIL][util::$LCMONTH],
                    $rrule[util::$UNTIL][util::$LCDAY],
                    $rrule[util::$UNTIL][util::$LCYEAR]
                    ) ;
            }
            $count = 0 ;
            $stopcount = isset( $rrule[util::$COUNT] ) ? $rrule[util::$COUNT] : 0 ;
            // repeat so long as we're between DTSTART and UNTIL, or we haven't prepared COUNT dates
            while( $offsetchangetimestamp < $stoptimestamp &&
                 ( $stopcount == 0 || $count < $stopcount ) ) {
                // break up the timestamp into its parts
                $datearray = getdate($offsetchangetimestamp);
                if ( isset( $rrule[util::$BYMONTH] ) && ( $rrule[util::$BYMONTH] != 0 )) {
                    // set the month
                    $datearray[$MON] = $rrule[util::$BYMONTH] ;
                }
                if ( isset( $rrule[util::$BYMONTHDAY] )) {         // start quickfix...
                    // set first found/specific day of month
                    $datearray[$MDAY] = ( is_array( $rrule[util::$BYMONTHDAY] ))
                                           ? reset( $rrule[util::$BYMONTHDAY] )
                                           :        $rrule[util::$BYMONTHDAY]; // end quickfix
                } elseif ( isset($rrule[util::$BYDAY]) && is_array($rrule[util::$BYDAY]) ) {  // update: 'isset...'
                    // find the Xth WKDAY in the month
                    // the starting point for this process is the first of the month set above
                    $datearray[$MDAY] = 1 ;
                    // turn $datearray as it is now back into a timestamp
                    $offsetchangetimestamp = mktime(
                        $datearray[$HOURS],
                        $datearray[$MINUTES],
                        $datearray[$SECONDS],
                        $datearray[$MON],
                        $datearray[$MDAY],
                        $datearray[util::$LCYEAR]
                            );
                    if ($rrule[util::$BYDAY][0] > 0) {
                        // to find Xth WKDAY in month, we find last WKDAY in month before
                        // we do that by finding first WKDAY in this month and going back one week
                        // then we add X weeks (below)
                        $offsetchangetimestamp = strtotime($DAYNAMES[$rrule[util::$BYDAY][util::$DAY]],$offsetchangetimestamp);
                        $offsetchangetimestamp = strtotime($MINUS1WEEK,$offsetchangetimestamp);
                    } else {
                        // to find Xth WKDAY before the end of the month, we find the first WKDAY in the following month
                        // we do that by going forward one month and going to WKDAY there
                        // then we subtract X weeks (below)
                        $offsetchangetimestamp = strtotime($PLUS1MONTH,$offsetchangetimestamp);
                        $offsetchangetimestamp = strtotime($DAYNAMES[$rrule[util::$BYDAY][util::$DAY]],$offsetchangetimestamp);
                    }
                    // now move forward or back the appropriate number of weeks, into the month we want
                    $offsetchangetimestamp = strtotime($rrule[util::$BYDAY][0] . $SP1WEEK,$offsetchangetimestamp);
                    $datearray = getdate($offsetchangetimestamp);
                }
                // convert the date parts back into a timestamp, setting the time parts according to the
                // original time data which we stored
                $offsetchangetimestamp = mktime(
                    $changetime[$HOURS],
                    $changetime[$MINUTES],
                    $changetime[$SECONDS] + $diff,
                    $datearray[$MON],
                    $datearray[$MDAY],
                    $datearray[util::$LCYEAR]
                        );
                // add this change to the array of changes
                $tzdates[] = [util::$LCTIMESTAMP => $offsetchangetimestamp,
                              $TZBEFORE  => $tzbefore,
                              $TZAFTER   => $tzafter];
                // update counters (timestamp and count)
                $offsetchangetimestamp = strtotime(util::$PLUS
                                       . (( isset( $rrule[util::$INTERVAL] ) && ( $rrule[util::$INTERVAL] != 0 ))
                                          ? $rrule[util::$INTERVAL] : 1 )
                                       . $SP1YEAR,$offsetchangetimestamp);
                $count += 1 ;
            }
        }
    }
    // generate dates according to RDATE lines
    while ($rdates = $vtzc->getProperty(util::$RDATE)) {
        if ( is_array($rdates) ) {

            foreach ( $rdates as $rdate ) {
                // convert the explicit change date to a timestamp
                $offsetchangetimestamp = mktime(
                        $rdate[util::$LCHOUR],
                        $rdate[util::$LCMIN],
                        $rdate[util::$LCSEC] + $diff,
                        $rdate[util::$LCMONTH],
                        $rdate[util::$LCDAY],
                        $rdate[util::$LCYEAR]
                        ) ;
                // add this change to the array of changes
                $tzdates[] = [util::$LCTIMESTAMP => $offsetchangetimestamp,
                              $TZBEFORE          => $tzbefore,
                              $TZAFTER           => $tzafter];
            }
        }
    }
    return $tzdates;
}
