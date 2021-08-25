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
namespace kigkonsult\iCalcreator\util;
use kigkonsult\iCalcreator\vcalendar;
use kigkonsult\iCalcreator\calendarComponent;
use kigkonsult\iCalcreator\vcalendarSortHandler;
use kigkonsult\iCalcreator\iCaldateTime;
/**
 * iCalcreator geo support class
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.23.6 - 2017-04-14
 */
class utilSelect {
/**
 * Return selected components from calendar on date or selectOption basis
 *
 * DTSTART MUST be set for every component.
 * No check of date.
 * @param object $calendar
 * @param mixed  $startY      (int) start Year,  default current Year
 *                       ALT. (obj) start date (datetime)
 *                       ALT. array selecOptions ( *[ <propName> => <uniqueValue> ] )
 * @param mixed  $startM      (int) start Month, default current Month
 *                       ALT. (obj) end date (datetime)
 * @param int    $startD start Day,   default current Day
 * @param int    $endY   end   Year,  default $startY
 * @param int    $endM   end   Month, default $startM
 * @param int    $endD   end   Day,   default $startD
 * @param mixed  $cType  calendar component type(-s), default false=all else string/array type(-s)
 * @param bool   $flat   false (default) => output : array[Year][Month][Day][]
 *                       true            => output : array[] (ignores split)
 * @param bool   $any    true (default) - select component(-s) that occurs within period
 *                       false          - only component(-s) that starts within period
 * @param bool   $split  true (default) - one component copy every DAY it occurs during the
 *                                        period (implies flat=false)
 *                       false          - one occurance of component only in output array
 * @return mixed array on success, bool false on error
 * @static
 */
  public static function selectComponents( vcalendar $calendar,
                                                     $startY=null,
                                                     $startM=null,
                                                     $startD=null,
                                                     $endY=null,
                                                     $endM=null,
                                                     $endD=null,
                                                     $cType=null,
                                                     $flat=null,
                                                     $any=null,
                                                     $split=null ) {
    static $Y           = 'Y';
    static $M           = 'm';
    static $D           = 'd';
    static $STRTOLOWER  = 'strtolower';
    static $P1D         = 'P1D';
    static $DTENDEXIST  = 'dtendExist';
    static $DUEEXIST    = 'dueExist';
    static $DURATIONEXIST = 'durationExist';
    static $ENDALLDAYEVENT = 'endAllDayEvent';
    static $MINUS1DAY   = '-1 day';
    static $RANGE       = 'RANGE';
    static $THISANDFUTURE = 'THISANDFUTURE';
    static $YMDHIS2     = 'Y-m-d H:i:s';
    static $PRA         = '%a';
    static $YMD2        = 'Y-m-d';
    static $DAYOFDAYS   = 'day %d of %d';
    static $SORTER      = ['kigkonsult\iCalcreator\vcalendarSortHandler',
                           'cmpfcn'];
            /* check  if empty calendar */
    if( 1 > $calendar->countComponents())
      return false;
    if( is_array( $startY ))
      return self::selectComponents2( $calendar, $startY );
            /* check default dates */
    if( util::isDateTimeClass( $startY ) &&
        util::isDateTimeClass( $startM )) {
      $endY      = $startM->format( $Y );
      $endM      = $startM->format( $M );
      $endD      = $startM->format( $D );
      $startD    = $startY->format( $D );
      $startM    = $startY->format( $M );
      $startY    = $startY->format( $Y );
    }
    else {
      if( empty( $startY )) $startY = date( $Y );
      if( empty( $startM )) $startM = date( $M );
      if( empty( $startD )) $startD = date( $D );
      if( empty( $endY ))   $endY   = $startY;
      if( empty( $endM ))   $endM   = $startM;
      if( empty( $endD ))   $endD   = $startD;
    }
            /* check component types */
    if( empty( $cType ))
      $cType     = util::$VCOMPS;
    else {
      if( ! is_array( $cType ))
        $cType   = [$cType];
      $cType     = array_map( $STRTOLOWER, $cType );
      foreach( $cType as $cix => $theType ) {
        if( ! in_array( $theType, util::$VCOMPS ))
          $cType[$cix] = util::$LCVEVENT;
      }
      $cType = array_unique( $cType );
    }
    $flat    = ( is_null( $flat ))  ? false : (bool) $flat; // defaults
    $any     = ( is_null( $any ))   ? true  : (bool) $any;
    $split   = ( is_null( $split )) ? true  : (bool) $split;
    if(( false === $flat ) && ( false === $any )) // invalid combination
      $split = false;
    if(( true === $flat ) && ( true === $split )) // invalid combination
      $split = false;
// echo " args={$startY}-{$startM}-{$startD} - {$endY}-{$endM}-{$endD}, flat={$flat}, any={$any}, split={$split}<br>\n"; $tcnt = 0;// test ###
            /* iterate components */
    $result      = [];
    $calendar->sort( util::$UID );
    $compUIDcmp  = null;
    $exdatelist  = $recurrIdList = [];
    $INTERVAL_P1D = new \DateInterval( $P1D );
// echo ' comp ix : ' . implode( ',', ( array_keys( $calendar->components ))) . "<br>\n"; // test ###
    $cix = -1;
    while( $component = $calendar->getComponent()) {
    $cix += 1;
      if( empty( $component ))
        continue;
            /* deselect unvalid type components */
      if( ! in_array( $component->objName, $cType ))
        continue;
      unset( $compStart, $compEnd );
            /* select start from dtstart or due if dtstart is missing */
      $prop = $component->getProperty( util::$DTSTART,
                                       false,
                                       true );
      if( empty( $prop ) &&
         ( $component->objName == util::$LCVTODO ) &&
         ( false === ( $prop = $component->getProperty( util::$DUE,
                                                        false,
                                                        true ))))
        continue;
      if( empty( $prop ))
        continue;
            /* get UID */
      $compUID   = $component->getProperty( util::$UID );
// echo 'START comp(' . $cix . ') ' . $component->objName . ', UID:' . $compUID . "<br>\n"; // test ###
      if( $compUIDcmp != $compUID ) {
        $compUIDcmp = $compUID;
        $exdatelist = $recurrIdList = [];
      }
// echo "#$cix".PHP_EOL.var_export( $component, true ) . "\n"; // test ###
      $compStart = iCaldateTime::factory( $prop[util::$LCvalue],
                                          $prop[util::$LCparams],
                                          $prop[util::$LCvalue] );
      $dtstartTz = $compStart->getTimezoneName();
      if( util::isParamsValueSet( $prop, util::$DATE ))
        $compStartHis = null;
      else {
        $his = $compStart->getTime();
        $compStartHis = sprintf( util::$HIS, (int) $his[0],
                                             (int) $his[1],
                                             (int) $his[2] );
      }
            /* get end date from dtend/due/duration properties */
      if( false !== ( $prop = $component->getProperty( util::$DTEND,
                                                       false,
                                                       true ))) {
        $compEnd = iCaldateTime::factory( $prop[util::$LCvalue],
                                          $prop[util::$LCparams],
                                          $prop[util::$LCvalue],
                                          $dtstartTz );
        $compEnd->SCbools[$DTENDEXIST] = true;
      }
      if( empty( $prop ) &&
         ( $component->objName == util::$LCVTODO ) &&
         ( false !== ( $prop = $component->getProperty( util::$DUE,
                                                        false,
                                                        true )))) {
        $compEnd = iCaldateTime::factory( $prop[util::$LCvalue],
                                          $prop[util::$LCparams],
                                          $prop[util::$LCvalue],
                                          $dtstartTz );
        $compEnd->SCbools[$DUEEXIST] = true;
      }
      if( empty( $prop ) &&
         ( false !== ( $prop = $component->getProperty( util::$DURATION,
                                                        false,
                                                        true,
                                                        true )))) {
        $compEnd = iCaldateTime::factory( $prop[util::$LCvalue],      // in dtend (array) format
                                          $prop[util::$LCparams],
                                          $prop[util::$LCvalue],
                                          $dtstartTz );
        $compEnd->SCbools[$DURATIONEXIST] = true;
      }
      if( ! empty( $prop ) && ! isset( $prop[util::$LCvalue][util::$LCHOUR] )) {
          /* a DTEND without time part denotes an end of an event that actually ends the day before,
             for an all-day event DTSTART=20071201 DTEND=20071202, taking place 20071201!!! */
        $compEnd->SCbools[$ENDALLDAYEVENT] = true;
        $compEnd->modify( $MINUS1DAY );
        $compEnd->setTime( 23, 59, 59 );
      }
      unset( $prop );
      if( empty( $compEnd )) {
        $compDuration = false;
        $compEnd = clone $compStart;
        $compEnd->setTime( 23, 59, 59 );                  // 23:59:59 the same day as start
      }
      else {
        if( $compEnd->format( $YMD2 ) < $compStart->format( $YMD2 )) { // MUST be after start date!!
          $compEnd = clone $compStart;
          $compEnd->setTime( 23, 59, 59 );                // 23:59:59 the same day as start or ???
        }
        $compDuration = $compStart->diff( $compEnd );     // DateInterval
      }
            /* check recurrence-id (note, a missing sequence is the same as sequence=0
               so don't test for sequence), to alter when hit in dtstart/recurlist */
      $recurrid   = null;
      if( false !== ( $prop = $component->getProperty( util::$RECURRENCE_ID,
                                                       false,
                                                       true ))) {
        $recurrid = iCaldateTime::factory( $prop[util::$LCvalue],
                                           $prop[util::$LCparams],
                                           $prop[util::$LCvalue],
                                           $dtstartTz );
        $rangeSet = ( isset( $prop[util::$LCparams][$RANGE] ) &&
                     ( $THISANDFUTURE == $prop[util::$LCparams][$RANGE] ))
                  ? true : false;
        $recurrIdList[$recurrid->key] = [clone $compStart,
                                         clone $compEnd,
                                         $compDuration,
                                         $rangeSet]; // change recur this day to new YmdHis/duration/range
// echo "adding comp no:$cix with date=".$compStart->format('Y-m-d H:i:s e')." to recurrIdList id={$recurrid->key}, newDate={$compStart->key}<br>\n"; // test ###
        unset( $prop );
        continue;                         // ignore any other props in the component
      } // end recurrence-id/sequence test
// else echo "comp no:$cix with date=".$compStart->format().", NO recurrence-id<br>\n"; // test ###
      ksort( $recurrIdList, SORT_STRING );
// echo 'recurrIdList='.implode(', ', array_keys( $recurrIdList ))."<br>\n"; // test ###
      $fcnStart  = clone $compStart;
      $fcnStart->setDate((int) $startY, (int) $startM, (int) $startD );
      $fcnStart->setTime( 0, 0, 0 );
      $fcnEnd    = clone $compEnd;
      $fcnEnd->setDate((int) $endY, (int) $endM, (int) $endD );
      $fcnEnd->setTime( 23, 59, 59 );
            /* make a list of optional exclude dates for component occurence
               from exrule and exdate */
      $workStart = clone $compStart;
      $workStart->sub( $compDuration ? $compDuration : $INTERVAL_P1D );
      $workEnd   = clone $fcnEnd;
      $workEnd->add(   $compDuration ? $compDuration : $INTERVAL_P1D  );
// echo 'compStart/end:'.$compStart->format( 'YmdHis e' ).' - ' . $compEnd->format( 'YmdHis e' )."<br>\n"; // test ###
// echo '.fcnStart/end:' .$fcnStart->format( 'YmdHis e' ).' - ' . $fcnEnd->format(  'YmdHis e' )."<br>\n"; // test ###
// echo 'workStart/end:'.$workStart->format( 'YmdHis e' ).' - ' . $workEnd->format( 'YmdHis e' )."<br>\n"; // test ###
      self::getAllEXRULEdates( $component, $exdatelist,
                               $dtstartTz, $compStart, $workStart, $workEnd,
                               $compStartHis );
      self::getAllEXDATEdates( $component, $exdatelist, $dtstartTz );
// echo 'exdatelist=' . implode(', ', array_keys( $exdatelist ))  ."<br>\n"; // test ###
            /* select only components within.. . */
      $xRecurrence  = 1;
      if(( ! $any && self::inScope( $compStart, $fcnStart,
                                    $compStart, $fcnEnd,  $compStart->dateFormat )) || // (dt)start within the period
         (   $any && self::inScope( $fcnEnd,    $compStart,
                                    $fcnStart,  $compEnd, $compStart->dateFormat ))) { // occurs within the period
            /* add the selected component (WITHIN valid dates) to output array */
        if( $flat ) { // any=true/false, ignores split
          if( empty( $recurrid ))
            $result[$compUID] = clone $component;         // copy original to output (but not anyone with recurrence-id)
        }
        elseif( $split ) { // split the original component
// echo 'split comp:' . $compStart->format( 'Ymd His e' ) . ', fcn:'.$fcnStart->format( 'Ymd His e' )."<br>\n"; // test ###
          if( $compStart->format( $YMDHIS2 ) < $fcnStart->format( $YMDHIS2 ))
            $rstart = clone $fcnStart;
          else
            $rstart = clone $compStart;
          if( $compEnd->format(   $YMDHIS2 ) > $fcnEnd->format( $YMDHIS2 ))
            $rend   = clone $fcnEnd;
          else
            $rend   = clone $compEnd;
// echo "going to test comp no:$cix, rstart=".$rstart->format( 'YmdHis e' )." (key={$rstart->key}), end=".$rend->format( 'YmdHis e' )."<br>\n"; // test ###
          if( ! isset( $exdatelist[$rstart->key] )) {     // not excluded in exrule/exdate
            if( isset( $recurrIdList[$rstart->key] )) {   // change start day to new YmdHis/duration
              $k        = $rstart->key;
// echo "recurrIdList HIT, key={$k}, recur Date=".$recurrIdList[$k][0]->key."<br>\n"; // test ###
              $rstart   = clone $recurrIdList[$k][0];
              $startHis = $rstart->getTime();
              $rend     = clone $rstart;
              if( false !== $recurrIdList[$k][2] )
                $rend->add( $recurrIdList[$k][2] );
              elseif( false !== $compDuration )
                $rend->add( $compDuration );
              $endHis   = $rend->getTime();
              unset( $recurrIdList[$k] );
            }
            else {
              $startHis = $compStart->getTime();
              $endHis   = $compEnd->getTime();
            }
//echo "_____testing comp no:$cix, rstart=".$rstart->format( 'YmdHis e' )." (key={$rstart->key}), end=".$rend->format( 'YmdHis e' )."<br>\n"; // test ###
            $cnt      = 0; // exclude any recurrence START date, found in exdatelist or recurrIdList but accept the reccurence-id comp itself
            $occurenceDays = 1 + (int) $rstart->diff( $rend )->format( $PRA );  // count the days (incl start day)
            while( $rstart->format( $YMD2 ) <= $rend->format( $YMD2 )) {
              $cnt += 1;
              if( 1 < $occurenceDays )
                $component->setProperty( util::$X_OCCURENCE,
                                         sprintf( $DAYOFDAYS, $cnt,
                                                              $occurenceDays ));
              if( 1 < $cnt )
                $rstart->setTime( 0, 0, 0 );
              else {
                $rstart->setTime( $startHis[0], $startHis[1], $startHis[2] );
                $exdatelist[$rstart->key] = $compDuration; // make sure to exclude start day from the recurrence pattern
              }
              $component->setProperty( util::$X_CURRENT_DTSTART,
                                       $rstart->format( $compStart->dateFormat ));
              $xY = (int) $rstart->format( $Y );
              $xM = (int) $rstart->format( $M );
              $xD = (int) $rstart->format( $D );
              if( false !== $compDuration ) {
                $propName = ( isset( $compEnd->SCbools[$DUEEXIST] ))
                          ? util::$X_CURRENT_DUE : util::$X_CURRENT_DTEND;
                if( $cnt < $occurenceDays )
                  $rstart->setTime( 23, 59, 59 );
                elseif(( $rstart->format( $YMD2 ) < $rend->format( $YMD2 )) &&
                       ( '00' == $endHis[0] ) &&
                       ( '00' == $endHis[1] ) &&
                       ( '00' == $endHis[2] )) // end exactly at midnight
                  $rstart->setTime( 24, 0, 0 );
                else
                  $rstart->setTime( $endHis[0], $endHis[1], $endHis[2] );
                $component->setProperty( $propName,
                                         $rstart->format( $compEnd->dateFormat ));
              }
              $result[$xY][$xM][$xD][$compUID] = clone $component;    // copy to output
              $rstart->add( $INTERVAL_P1D );
            } // end while(( $rstart->format( 'Ymd' ) < $rend->format( 'Ymd' ))
            unset( $cnt, $occurenceDays );
          } // end if( ! isset( $exdatelist[$rstart->key] ))
// else echo "skip no:$cix with date=".$compStart->format()."<br>\n"; // test ###
          unset( $rstart, $rend );
        } // end elseif( $split )   -  else use component date
        else { // !$flat && !$split, i.e. no flat array and DTSTART within period
          $tstart = ( isset( $recurrIdList[$compStart->key] ))
                  ? clone $recurrIdList[$compStart->key][0] : clone $compStart;
// echo "going to test comp no:$cix with checkDate={$compStart->key} with recurrIdList=".implode(',',array_keys($recurrIdList)); // test ###
          if( ! $any || ! isset( $exdatelist[$tstart->key] )) {  // exclude any recurrence date, found in exdatelist
// echo " and copied to output<br>\n"; // test ###
            $xY = (int) $tstart->format( $Y );
            $xM = (int) $tstart->format( $M );
            $xD = (int) $tstart->format( $D );
            $result[$xY][$xM][$xD][$compUID] = clone $component;      // copy to output
          }
          unset( $tstart );
        }
      } // end (dt)start within the period OR occurs within the period
            /* *************************************************************
               if 'any' components, check components with reccurrence rules, removing all excluding dates
               *********************************************************** */
      if( true === $any ) {
        $recurlist = [];
            /* make a list of optional repeating dates for component occurence, rrule, rdate */
        self::getAllRRULEdates( $component, $recurlist,
                                $dtstartTz, $compStart, $workStart, $workEnd,
                                $compStartHis, $exdatelist, $compDuration );
// echo 'recurlist rrule='   .implode(', ', array_keys( $recurlist ))   ."<br>\n"; // test ###
        $workStart    = clone $fcnStart;
        $workStart->sub( $compDuration ? $compDuration : $INTERVAL_P1D );
        self::getAllRDATEdates( $component, $recurlist,
                                $dtstartTz, $workStart, $fcnEnd, $compStart->dateFormat,
                                $exdatelist, $compStartHis, $compDuration );
        unset( $workStart, $rend );
        foreach( $recurrIdList as $rKey => $rVal ) { // check for recurrence-id, i.e. alter recur Ymd[His] and duration
          if( isset( $recurlist[$rKey] )) {
            unset( $recurlist[$rKey] );
            $recurlist[$rVal[0]->key] = ( false !== $rVal[2] )
                                      ? $rVal[2] : $compDuration;
// echo "alter recurfrom {$rKey} to {$rVal[0]->key} ";if(false!==$dur)echo " ({$dur->format( '%a days, %h-%i-%s' )})";echo "<br>\n"; // test ###
          }
        }
        ksort( $recurlist, SORT_STRING );
// echo 'recurlist rrule/rdate='   .implode(', ', array_keys( $recurlist ))   ."<br>\n"; // test ###
// echo 'recurrIdList='   .implode(', ', array_keys( $recurrIdList ))   ."<br>\n"; // test ###
            /* output all remaining components in recurlist */
        if( 0 < count( $recurlist )) {
          $component2  = clone $component;
          $compUID     = $component2->getProperty( util::$UID );
          $workStart   = clone $fcnStart;
          $workStart->sub( $compDuration ? $compDuration : $INTERVAL_P1D );
          $YmdOld      = null;
          foreach( $recurlist as $recurkey => $durvalue ) {
            if( $YmdOld == substr( $recurkey, 0, 8 ))     // skip overlapping recur the same day, i.e. RDATE before RRULE
              continue;
            $YmdOld    = substr( $recurkey, 0, 8 );
            $rstart    = clone $compStart;
            $rstart->setDate((int) substr( $recurkey,  0, 4 ),
                             (int) substr( $recurkey,  4, 2 ),
                             (int) substr( $recurkey,  6, 2 ));
            $rstart->setTime((int) substr( $recurkey,  8, 2 ),
                             (int) substr( $recurkey, 10, 2 ),
                             (int) substr( $recurkey, 12, 2 ));
// echo "recur start=".$rstart->format( 'Y-m-d H:i:s e' )."<br>\n"; // test ###;
           /* add recurring components within valid dates to output array, only start date set */
            if( $flat ) {
              if( ! isset( $result[$compUID] )) // only one comp
                $result[$compUID] = clone $component2;  // copy to output
            }
           /* add recurring components within valid dates to output array, split for each day */
            elseif( $split ) {
              $rend     = clone $rstart;
              if( false !== $durvalue )
                $rend->add( $durvalue );
              if( $rend->format( $YMD2 ) > $fcnEnd->format( $YMD2 ))
                $rend   = clone $fcnEnd;
              $endHis   = $rend->getTime();
// echo "recur 1={$recurkey}, start=".$rstart->format( 'YmdHis e' ).", end=".$rend->format( util::$YMDHISE );if($durvalue) echo ", duration=".$durvalue->format( '%a days, %h hours, %i min, %s sec' );echo "<br>\n"; // test ###
              $xRecurrence += 1;
              $cnt      = 0;
              $occurenceDays = 1 + (int) $rstart->diff( $rend )->format( $PRA );  // count the days (incl start day)
              while( $rstart->format( $YMD2 ) <= $rend->format( $YMD2 )) {   // iterate.. .
                $cnt   += 1;
                if( $rstart->format( $YMD2 ) < $fcnStart->format( $YMD2 )) { // date before dtstart
// echo "recur 3, start=".$rstart->format( 'YmdHis e' )." &gt;= fcnStart=".$fcnStart->format( 'YmdHis e' )."<br>\n"; // test ###
                  $rstart->add( $INTERVAL_P1D ); // cycle rstart to dtstart
                  $rstart->setTime( 0, 0, 0 );
                  continue;
                }
                elseif( 2 == $cnt )
                  $rstart->setTime( 0, 0, 0 );
                $component2->setProperty(      util::$X_RECURRENCE,
                                               $xRecurrence );
                if( 1 < $occurenceDays )
                  $component2->setProperty(    util::$X_OCCURENCE,
                                               sprintf( $DAYOFDAYS, $cnt, $occurenceDays ));
                else
                  $component2->deleteProperty( util::$X_OCCURENCE );
                $component2->setProperty(      util::$X_CURRENT_DTSTART,
                                               $rstart->format( $compStart->dateFormat ));
                $xY = (int) $rstart->format( $Y );
                $xM = (int) $rstart->format( $M );
                $xD = (int) $rstart->format( $D );
                $propName = ( isset( $compEnd->SCbools[$DUEEXIST] ))
                          ? util::$X_CURRENT_DUE : util::$X_CURRENT_DTEND;
                if( false !== $durvalue ) {
                  if( $cnt < $occurenceDays )
                    $rstart->setTime( 23, 59, 59 );
                  elseif(( $rstart->format( $YMD2 ) < $rend->format( $YMD2 )) &&
                         ( '00' == $endHis[0] ) && ( '00' == $endHis[1] ) && ( '00' == $endHis[2] )) // end exactly at midnight
                  $rstart->setTime( 24, 0, 0 );
                  else
                    $rstart->setTime( $endHis[0], $endHis[1], $endHis[2] );
                  $component2->setProperty( $propName,
                                            $rstart->format( $compEnd->dateFormat ));
// echo "checking date, (day {$cnt} of {$occurenceDays}), _end_=".$rstart->format( 'YmdHis e' )."<br>"; // test ###;
                }
                else
                  $component2->deleteProperty( $propName );
                $result[$xY][$xM][$xD][$compUID] = clone $component2;     // copy to output
                $rstart->add( $INTERVAL_P1D );
              } // end while( $rstart->format( 'Ymd' ) <= $rend->format( 'Ymd' ))
              unset( $rstart, $rend );
            } // end elseif( $split )
            elseif( $rstart->format( $YMD2 ) >= $fcnStart->format( $YMD2 )) {
              $xRecurrence += 1;                                            // date within period, flat=false && split=false => one comp every recur startdate
              $component2->setProperty( util::$X_RECURRENCE,
                                        $xRecurrence );
              $component2->setProperty( util::$X_CURRENT_DTSTART,
                                        $rstart->format( $compStart->dateFormat ));
              $propName   = ( isset( $compEnd->SCbools[$DUEEXIST] ))
                          ? util::$X_CURRENT_DUE : util::$X_CURRENT_DTEND;
              if( false !== $durvalue ) {
                $rstart->add( $durvalue );
                $component2->setProperty( $propName,
                                          $rstart->format( $compEnd->dateFormat ));
              }
              else
                $component2->deleteProperty( $propName );
              $xY = (int) $rstart->format( $Y );
              $xM = (int) $rstart->format( $M );
              $xD = (int) $rstart->format( $D );
              $result[$xY][$xM][$xD][$compUID] = clone $component2; // copy to output
            } // end elseif( $rstart >= $fcnStart )
            unset( $rstart );
          } // end foreach( $recurlist as $recurkey => $durvalue )
          unset( $component2, $xRecurrence, $compUID, $workStart, $rstart );
        } // end if( 0 < count( $recurlist ))
      } // end if( true === $any )
      unset( $component );
    } // end while( $component = $calendar->getComponent())
    if( 0 >= count( $result ))
      return false;
    elseif( ! $flat ) {
      foreach(  $result as $y => $yList ) {
        foreach( $yList as $m => $mList ) {
          foreach( $mList as $d => $dList ) {
            if( empty( $dList ))
                unset( $result[$y][$m][$d] );
            else {
              $result[$y][$m][$d] = array_values( $dList ); // skip tricky UID-index
              if( 1 < count( $result[$y][$m][$d] )) {
                foreach( $result[$y][$m][$d] as $cix => $d2List )   // sort
                  vcalendarSortHandler::setSortArgs( $result[$y][$m][$d][$cix] );
                usort( $result[$y][$m][$d], $SORTER );
              }
            }
          } // end foreach( $mList as $d => $dList )
          if( empty( $result[$y][$m] ))
              unset( $result[$y][$m] );
          else
            ksort( $result[$y][$m] );
        } // end foreach( $yList as $m => $mList )
        if( empty( $result[$y] ))
            unset( $result[$y] );
        else
          ksort( $result[$y] );
      } // end foreach(  $result as $y => $yList )
      if( empty( $result ))
          unset( $result );
      else
        ksort( $result );
    } // end elseif( !$flat )
    if( 0 >= count( $result ))
      return false;
    return $result;
  }
/**
 * Return bool true if dates are in scope
 *
 * @param iCaldateTime $start
 * @param iCaldateTime $scopeStart
 * @param iCaldateTime $end
 * @param iCaldateTime $scopeEnd
 * @param string       $format
 * @return bool
 * @access private
 * @static
 */
  private static function inScope( iCaldateTime $start,
                                   iCaldateTime $scopeStart,
                                   iCaldateTime $end,
                                   iCaldateTime $scopeEnd,
                                   $format ) {
    return (( $start->format( $format ) >= $scopeStart->format( $format )) &&
            ( $end->format(   $format ) <= $scopeEnd->format(   $format )));
  }
/**
 * Get all EXRULE dates (multiple values allowed)
 *
 * @param calendarComponent $component
 * @param array             $exdatelist
 * @param string            $dtstartTz
 * @param iCaldateTime      $compStart
 * @param iCaldateTime      $workStart
 * @param iCaldateTime      $workEnd
 * @param string            $compStartHis
 */
  private static function getAllEXRULEdates( calendarComponent $component,
                                             array & $exdatelist,
                                             $dtstartTz,
                                             iCaldateTime $compStart,
                                             iCaldateTime $workStart,
                                             iCaldateTime $workEnd,
                                             $compStartHis ) {
    while( false !== ( $prop = $component->getProperty( util::$EXRULE  ))) {
      $exdatelist2 = [];
      if( isset( $prop[util::$UNTIL][util::$LCHOUR] )) { // convert UNTIL date to DTSTART timezone
        $until = iCaldateTime::factory( $prop[util::$UNTIL],
                                        [util::$TZID => util::$UTC],
                                        null,
                                        $dtstartTz );
        $until = $until->format();
        util::strDate2arr( $until );
        $prop[util::$UNTIL] = $until;
      }
      utilRecur::recur2date( $exdatelist2,
                             $prop,
                             $compStart,
                             $workStart,
                             $workEnd );
      foreach( $exdatelist2 as $k => $v ) // point out exact every excluded ocurrence (incl. opt. His)
        $exdatelist[$k.$compStartHis] = $v;
      unset( $until, $exdatelist2 );
    }
    return true;
  }
/**
 * Get all EXDATE dates (multiple values allowed)
 *
 * @param calendarComponent $component
 * @param array             $exdatelist
 * @param string            $dtstartTz
 */
  private static function getAllEXDATEdates( calendarComponent $component,
                                             array & $exdatelist,
                                             $dtstartTz ) {
    while( false !== ( $prop = $component->getProperty( util::$EXDATE,
                                                        false,
                                                        true ))) {
      foreach( $prop[util::$LCvalue] as $exdate ) {
        $exdate  = iCaldateTime::factory( $exdate,
                                          $prop[util::$LCparams],
                                          $exdate,
                                          $dtstartTz );
        $exdatelist[$exdate->key] = true;
      } // end - foreach( $exdate as $exdate )
    }
    return true;
  }
/**
 * Update $recurlist all RRULE dates (multiple values allowed)
 *
 * @param calendarComponent $component
 * @param array             $recurlist
 * @param string            $dtstartTz
 * @param iCaldateTime      $compStart
 * @param iCaldateTime      $workStart
 * @param iCaldateTime      $workEnd
 * @param string $compStartHis
 * @param array  $exdatelist
 * @param object $compDuration
 */
  private static function getAllRRULEdates( calendarComponent $component,
                                            array & $recurlist,
                                            $dtstartTz,
                                            iCaldateTime $compStart,
                                            iCaldateTime$workStart,
                                            iCaldateTime $workEnd,
                                            $compStartHis,
                                            array $exdatelist,
                                            $compDuration ) {
    while( false !== ( $prop = $component->getProperty( util::$RRULE ))) {
      $recurlist2 = [];
      if( isset( $prop[util::$UNTIL][util::$LCHOUR] )) { // convert RRULE['UNTIL'] to the same timezone as DTSTART !!
        $until = iCaldateTime::factory( $prop[util::$UNTIL],
                                        [util::$TZID => util::$UTC],
                                        null,
                                        $dtstartTz );
        $until = $until->format();
        util::strDate2arr( $until );
        $prop[util::$UNTIL] = $until;
      }
      utilRecur::recur2date( $recurlist2,
                             $prop,
                             $compStart,
                             $workStart,
                             $workEnd );
      foreach( $recurlist2 as $recurkey => $recurvalue ) { // recurkey=Ymd
        $recurkey .= $compStartHis;                   // add opt His
        if( ! isset( $exdatelist[$recurkey] ))
          $recurlist[$recurkey] = $compDuration;      // DateInterval or false
      }
      unset( $prop, $until, $recurlist2 );
    }
    return true;
  }
/**
 * Update $recurlist with RDATE dates (multiple values allowed)
 *
 * @param calendarComponent $component
 * @param array             $recurlist
 * @param string            $dtstartTz
 * @param iCaldateTime      $workStart
 * @param iCaldateTime      $fcnEnd
 * @param string            $format
 * @param array             $exdatelist
 * @param string            $compStartHis
 * @param object            $compDuration
 */
  private static function getAllRDATEdates( calendarComponent $component,
                                            array & $recurlist,
                                            $dtstartTz,
                                            iCaldateTime $workStart,
                                            iCaldateTime $fcnEnd,
                                            $format,
                                            array $exdatelist,
                                            $compStartHis,
                                            $compDuration ) {
    while( false !== ( $prop = $component->getProperty( util::$RDATE,
                                                        false,
                                                        true ))) {
      $rdateFmt   = ( isset( $prop[util::$LCparams][util::$VALUE] ))
                           ? $prop[util::$LCparams][util::$VALUE]
                           : util::$DATE_TIME;
      $params     = $prop[util::$LCparams];
      $prop       = $prop[util::$LCvalue];
      foreach( $prop as $rix => $theRdate ) {
        if( util::$PERIOD == $rdateFmt ) {            // all days within PERIOD
          $rdate = iCaldateTime::factory( $theRdate[0],
                                          $params,
                                          $theRdate[0],
                                          $dtstartTz );
          if( ! self::inScope( $rdate, $workStart, $rdate, $fcnEnd, $format ) ||
                isset( $exdatelist[$rdate->key] ))
            continue;
          if( isset( $theRdate[1][util::$LCYEAR] ))   // date-date period end
            $recurlist[$rdate->key] = $rdate->diff( iCaldateTime::factory( $theRdate[1],
                                                                           $params,
                                                                           $theRdate[1],
                                                                           $dtstartTz ));
          else                                        // period duration
            $recurlist[$rdate->key] = new \DateInterval( util::duration2str( $theRdate[1] ));
        } // end if( util::$PERIOD == $rdateFmt )
        elseif( util::$DATE == $rdateFmt ) {          // single recurrence, DATE
          $rdate  = iCaldateTime::factory( $theRdate,
                                           array_merge( $params,
                                                        [util::$TZID => $dtstartTz] ),
                                           null,
                                           $dtstartTz );
          if( self::inScope( $rdate, $workStart, $rdate, $fcnEnd, $format ) &&
              ! isset( $exdatelist[$rdate->key] )) // set start date for recurrence + DateInterval/false (+opt His)
            $recurlist[$rdate->key.$compStartHis] = $compDuration;
        } // end DATE
        else { // start DATETIME
          $rdate = iCaldateTime::factory( $theRdate,
                                          $params,
                                          $theRdate,
                                          $dtstartTz );
          if( self::inScope( $rdate, $workStart, $rdate, $fcnEnd, $format ) &&
                    ! isset( $exdatelist[$rdate->key] ))
            $recurlist[$rdate->key] = $compDuration;  // set start datetime for recurrence DateInterval/false
        } // end DATETIME
      } // end foreach( $prop as $rix => $theRdate )
    }  // end while( false !== ( $prop = $component->getProperty( util::$RDATE, false, true )))
    return true;
  }
/**
 * Return array with selected components values from calendar based on specific property value(-s)
 *
 * @param vcalendar $calendar
 * @param array  $selectOptions (string) key => (mixed) value, (key=propertyName)
 * @return array
 * @access private
 * @static
 */
  private static function selectComponents2( vcalendar $calendar,
                                             array $selectOptions ) {
    $output = [];
    $selectOptions = array_change_key_case( $selectOptions, CASE_UPPER );
    while( $component3 = $calendar->getComponent()) {
      if( empty( $component3 ))
        continue;
      if( ! in_array( $component3->objName, util::$VCOMPS ))
        continue;
      $uid = $component3->getProperty( util::$UID );
      foreach( $selectOptions as $propName => $pValue ) {
        if( ! in_array( $propName, util::$OTHERPROPS ))
          continue;
        if( ! is_array( $pValue ))
          $pValue = [$pValue];
        if(( util::$UID == $propName ) && in_array( $uid, $pValue )) {
          $output[$uid][] = $component3;
          continue;
        }
        elseif( in_array( $propName, util::$MPROPS1 )) {
          $propValues = [];
          $component3->getProperties( $propName, $propValues );
          $propValues = array_keys( $propValues );
          foreach( $pValue as $theValue ) {
            if( in_array( $theValue, $propValues )) { //  && ! isset( $output[$uid] )) {
              $output[$uid][] = $component3;
              break;
            }
          }
          continue;
        } // end   elseif( // multiple occurrence?
        elseif( false === ( $d = $component3->getProperty( $propName ))) // single occurrence
          continue;
        if( is_array( $d )) {
          foreach( $d as $part ) {
            if( in_array( $part, $pValue ) && ! isset( $output[$uid] ))
              $output[$uid][] = $component3;
          }
        }
        elseif(( util::$SUMMARY == $propName ) && ! isset( $output[$uid] )) {
          foreach( $pValue as $pval ) {
            if( false !== stripos( $d, $pval )) {
              $output[$uid][] = $component3;
              break;
            }
          }
        }
        elseif( in_array( $d, $pValue ) && ! isset( $output[$uid] ))
          $output[$uid][] = $component3;
      } // end foreach( $selectOptions as $propName => $pValue )
    } // end while( $component3 = $calendar->getComponent()) {
    if( ! empty( $output )) {
      ksort( $output ); // uid order
      $output2 = [];
      foreach( $output as $uid => $uList ) {
        foreach( $uList as $cx => $uValue )
          $output2[] = $uValue;
      }
      $output = $output2;
    }
    return $output;
  }
}
