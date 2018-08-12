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
/**
 * iCalcreator recur support class
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.23.18 - 2017-06-14
 */
class utilRecur {
/**
 * Static values for recurrence FREQuence
 * @access private
 * @static
 */
  private static $DAILY           = 'DAILY';
  private static $WEEKLY          = 'WEEKLY';
  private static $MONTHLY         = 'MONTHLY';
  private static $YEARLY          = 'YEARLY';
//private static $SECONDLY        = 'SECONDLY';
//private static $MINUTELY        = 'MINUTELY';
//private static $HOURLY          = 'HOURLY';
  private static $DAYNAMES        = ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'];
  private static $YEARCNT_UP      = 'yearcnt_up';
  private static $YEARCNT_DOWN    = 'yearcnt_down';
  private static $MONTHDAYNO_UP   = 'monthdayno_up';
  private static $MONTHDAYNO_DOWN = 'monthdayno_down';
  private static $MONTHCNT_DOWN   = 'monthcnt_down';
  private static $YEARDAYNO_UP    = 'yeardayno_up';
  private static $YEARDAYNO_DOWN  = 'yeardayno_down';
  private static $WEEKNO_UP       = 'weekno_up';
  private static $WEEKNO_DOWN     = 'weekno_down';
  private static $W               = 'W';
/**
 * Sort recur dates
 * @param array  $byDayA
 * @param array  $byDayB
 * @return int
 * @access private
 * @static
 */
  private static function recurBydaySort( $byDayA, $byDayB ) {
    static $days = ['SU' => 0,
                    'MO' => 1,
                    'TU' => 2,
                    'WE' => 3,
                    'TH' => 4,
                    'FR' => 5,
                    'SA' => 6];
    return ( $days[substr( $byDayA, -2 )] < $days[substr( $byDayB, -2 )] ) ? -1 : 1;
  }
/**
 * Return formatted output for calendar component property data value type recur
 *
 * @param string $recurlabel
 * @param array  $recurData
 * @param bool   $allowEmpty
 * @return string
 * @static
 */
  public static function formatRecur( $recurlabel, $recurData, $allowEmpty ) {
    static $FMTFREQEQ    = 'FREQ=%s';
    static $FMTDEFAULTEQ = ';%s=%s';
    static $FMTOTHEREQ   = ';%s=';
    static $RECURBYDAYSORTER = null;
    static $SP0          = '';
    if( is_null( $RECURBYDAYSORTER ))
      $RECURBYDAYSORTER = [get_class(), 'recurBydaySort'];
    if( empty( $recurData ))
      return null;
    $output = null;
    foreach( $recurData as $rx => $theRule ) {
      if( empty( $theRule[util::$LCvalue] )) {
        if( $allowEmpty )
          $output .= util::createElement( $recurlabel );
        continue;
      }
      $attributes = ( isset( $theRule[util::$LCparams] ))
                  ? util::createParams( $theRule[util::$LCparams] )
                  : null;
      $content1  = $content2  = null;
      foreach( $theRule[util::$LCvalue] as $ruleLabel => $ruleValue ) {
        $ruleLabel = strtoupper( $ruleLabel );
        switch( $ruleLabel ) {
          case util::$FREQ :
            $content1 .= sprintf( $FMTFREQEQ, $ruleValue );
            break;
          case util::$UNTIL :
            $parno     = ( isset( $ruleValue[util::$LCHOUR] )) ? 7 : 3;
            $content2 .= sprintf( $FMTDEFAULTEQ, util::$UNTIL,
                                                 util::date2strdate( $ruleValue,
                                                                     $parno ));
            break;
          case util::$COUNT :
          case util::$INTERVAL :
          case util::$WKST :
            $content2 .= sprintf( $FMTDEFAULTEQ, $ruleLabel, $ruleValue );
            break;
          case util::$BYDAY :
            $byday          = [$SP0];
            $bx             = 0;
            foreach( $ruleValue as $bix => $bydayPart ) {
              if( ! empty( $byday[$bx] ) &&   // new day
                  ! ctype_digit( substr( $byday[$bx], -1 )))
                $byday[++$bx] = $SP0;
              if( ! is_array( $bydayPart ))   // day without order number
                $byday[$bx] .= (string) $bydayPart;
              else {                          // day with order number
                foreach( $bydayPart as $bix2 => $bydayPart2 )
                  $byday[$bx] .= (string) $bydayPart2;
              }
            } // end foreach( $ruleValue as $bix => $bydayPart )
            if( 1 < count( $byday ))
              usort( $byday, $RECURBYDAYSORTER );
            $content2      .= sprintf( $FMTDEFAULTEQ, util::$BYDAY,
                                                      implode( util::$COMMA,
                                                               $byday ));
            break;
          default : // BYSECOND/BYMINUTE/BYHOUR/BYMONTHDAY/BYYEARDAY/BYWEEKNO/BYMONTH/BYSETPOS...
            if( is_array( $ruleValue )) {
              $content2 .= sprintf( $FMTOTHEREQ, $ruleLabel );
              $content2 .= implode( util::$COMMA, $ruleValue );
            }
            else
              $content2 .= sprintf( $FMTDEFAULTEQ, $ruleLabel, $ruleValue );
            break;
        } // end switch( $ruleLabel )
      } // end foreach( $theRule[util::$LCvalue] )) as $ruleLabel => $ruleValue )
      $output .= util::createElement( $recurlabel,
                                      $attributes,
                                      $content1 . $content2 );
    } // end foreach( $recurData as $rx => $theRule )
    return $output;
  }
/**
 * Convert input format for EXRULE and RRULE to internal format
 *
 * @param array $rexrule
 * @return array
 * @static
 */
  public static function setRexrule( $rexrule ) {
    static $BYSECOND = 'BYSECOND';
    static $BYMINUTE = 'BYMINUTE';
    static $BYHOUR   = 'BYHOUR';
    $input       = [];
    if( empty( $rexrule ))
      return $input;
    $rexrule     = array_change_key_case( $rexrule, CASE_UPPER );
    foreach( $rexrule as $rexruleLabel => $rexruleValue ) {
      if( util::$UNTIL != $rexruleLabel )
        $input[$rexruleLabel]   = $rexruleValue;
      else {
        util::strDate2arr( $rexruleValue );
        if( util::isArrayTimestampDate( $rexruleValue )) // timestamp, always date-time UTC
          $input[$rexruleLabel] = util::timestamp2date( $rexruleValue, 7, util::$UTC );
        elseif( util::isArrayDate( $rexruleValue )) { // date or UTC date-time
          $parno = ( isset( $rexruleValue[util::$LCHOUR] ) ||
                     isset( $rexruleValue[4] )) ? 7 : 3;
          $d = util::chkDateArr( $rexruleValue, $parno );
          if(( 3 < $parno ) &&
                       isset( $d[util::$LCtz] ) &&
                ( util::$Z != $d[util::$LCtz] ) &&
             util::isOffset( $d[util::$LCtz] )) {
            $input[$rexruleLabel] = util::strDate2ArrayDate( sprintf( util::$YMDHISE,
                                                                      (int) $d[util::$LCYEAR],
                                                                      (int) $d[util::$LCMONTH],
                                                                      (int) $d[util::$LCDAY],
                                                                      (int) $d[util::$LCHOUR],
                                                                      (int) $d[util::$LCMIN],
                                                                      (int) $d[util::$LCSEC],
                                                                            $d[util::$LCtz] ),
                                                             7 );
            unset( $input[$rexruleLabel][util::$UNPARSEDTEXT] );
          }
          else
           $input[$rexruleLabel] = $d;
        }
        elseif( 8 <= strlen( trim( $rexruleValue ))) { // ex. textual date-time 2006-08-03 10:12:18 => UTC
          $input[$rexruleLabel] = util::strDate2ArrayDate( $rexruleValue );
          unset( $input[$rexruleLabel][util::$UNPARSEDTEXT] );
        }
        if(( 3 < count( $input[$rexruleLabel] )) &&
               ! isset( $input[$rexruleLabel][util::$LCtz] ))
          $input[$rexruleLabel][util::$LCtz] = util::$Z;
      }
    } // end foreach( $rexrule as $rexruleLabel => $rexruleValue )
            /* set recurrence rule specification in rfc2445 order */
    $input2 = [];
    if( isset( $input[util::$FREQ] ))
      $input2[util::$FREQ]     = $input[util::$FREQ];
    if( isset( $input[util::$UNTIL] ))
      $input2[util::$UNTIL]    = $input[util::$UNTIL];
    elseif( isset( $input[util::$COUNT] ))
      $input2[util::$COUNT]    = $input[util::$COUNT];
    if( isset( $input[util::$INTERVAL] ))
      $input2[util::$INTERVAL] = $input[util::$INTERVAL];
    if( isset( $input[$BYSECOND] ))
      $input2[$BYSECOND]       = $input[$BYSECOND];
    if( isset( $input[$BYMINUTE] ))
      $input2[$BYMINUTE]       = $input[$BYMINUTE];
    if( isset( $input[$BYHOUR] ))
      $input2[$BYHOUR]         = $input[$BYHOUR];
    if( isset( $input[util::$BYDAY] )) {
      if( ! is_array( $input[util::$BYDAY] )) // ensure upper case.. .
        $input2[util::$BYDAY]  = strtoupper( $input[util::$BYDAY] );
      else {
        foreach( $input[util::$BYDAY] as $BYDAYx => $BYDAYv ) {
          if( 0 == strcasecmp( util::$DAY, $BYDAYx ))
             $input2[util::$BYDAY][util::$DAY] = strtoupper( $BYDAYv );
          elseif( ! is_array( $BYDAYv ))
             $input2[util::$BYDAY][$BYDAYx]  = $BYDAYv;
          else {
            foreach( $BYDAYv as $BYDAYx2 => $BYDAYv2 ) {
              if( 0 == strcasecmp( util::$DAY, $BYDAYx2 ))
                 $input2[util::$BYDAY][$BYDAYx][util::$DAY] = strtoupper( $BYDAYv2 );
              else
                 $input2[util::$BYDAY][$BYDAYx][$BYDAYx2]   = $BYDAYv2;
            }
          }
        }
      }
    } // end if( isset( $input[util::$BYDAY] ))
    if( isset( $input[util::$BYMONTHDAY] ))
      $input2[util::$BYMONTHDAY] = $input[util::$BYMONTHDAY];
    if( isset( $input[util::$BYYEARDAY] ))
      $input2[util::$BYYEARDAY]  = $input[util::$BYYEARDAY];
    if( isset( $input[util::$BYWEEKNO] ))
      $input2[util::$BYWEEKNO]   = $input[util::$BYWEEKNO];
    if( isset( $input[util::$BYMONTH] ))
      $input2[util::$BYMONTH]    = $input[util::$BYMONTH];
    if( isset( $input[util::$BYSETPOS] ))
      $input2[util::$BYSETPOS]   = $input[util::$BYSETPOS];
    if( isset( $input[util::$WKST] ))
      $input2[util::$WKST]       = $input[util::$WKST];
    return $input2;
  }
/**
 * Update array $result with dates based on a recur pattern
 *
 * If missing, UNTIL is set 1 year from startdate (emergency break)
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.11 - 2015-03-10
 * @param array $result    array to update, array([Y-m-d] => bool)
 * @param array $recur     pattern for recurrency (only value part, params ignored)
 * @param mixed $wdate     component start date, string / array / (datetime) obj
 * @param mixed $fcnStart  start date, string / array / (datetime) obj
 * @param mixed $fcnEnd    end date, string / array / (datetime) obj
 * @static
 * @todo BYHOUR, BYMINUTE, BYSECOND, WEEKLY at year end/start OR not at all
 */
  public static function recur2date( & $result,
                                       $recur,
                                       $wdate,
                                       $fcnStart,
                                       $fcnEnd=false ) {
    static $YEAR2DAYARR     = ['YEARLY', 'MONTHLY', 'WEEKLY', 'DAILY'];
    static $SU              = 'SU';
    self::reFormatDate( $wdate );
    $wdateYMD     = sprintf( util::$YMD, $wdate[util::$LCYEAR],
                                         $wdate[util::$LCMONTH],
                                         $wdate[util::$LCDAY] );
    $wdateHis     = sprintf( util::$HIS, $wdate[util::$LCHOUR],
                                         $wdate[util::$LCMIN],
                                         $wdate[util::$LCSEC] );
    $untilHis     = $wdateHis;
    self::reFormatDate( $fcnStart );
    $fcnStartYMD = sprintf( util::$YMD, $fcnStart[util::$LCYEAR],
                                        $fcnStart[util::$LCMONTH],
                                        $fcnStart[util::$LCDAY] );
    if( ! empty( $fcnEnd ))
      self::reFormatDate( $fcnEnd );
    else {
      $fcnEnd = $fcnStart;
      $fcnEnd[util::$LCYEAR] += 1;
    }
    $fcnEndYMD = sprintf( util::$YMD, $fcnEnd[util::$LCYEAR],
                                      $fcnEnd[util::$LCMONTH],
                                      $fcnEnd[util::$LCDAY] );
// echo "<b>recur _in_ comp</b> start ".implode('-',$wdate)." period start ".implode('-',$fcnStart)." period end ".implode('-',$fcnEnd)."<br>\n";
// echo 'recur='.str_replace( [PHP_EOL, ' '], null, var_export( $recur, true ))."<br> \n"; // test ###
    if( ! isset( $recur[util::$COUNT] ) &&
        ! isset( $recur[util::$UNTIL] ))
      $recur[util::$UNTIL] = $fcnEnd; // create break
    if( isset( $recur[util::$UNTIL] )) {
      foreach( $recur[util::$UNTIL] as $k => $v ) {
        if( ctype_digit( $v ))
          $recur[util::$UNTIL][$k] = (int) $v;
      }
      unset( $recur[util::$UNTIL][util::$LCtz] );
      if( $fcnEnd > $recur[util::$UNTIL] ) {
        $fcnEnd    = $recur[util::$UNTIL]; // emergency break
        $fcnEndYMD = sprintf( util::$YMD, $fcnEnd[util::$LCYEAR],
                                          $fcnEnd[util::$LCMONTH],
                                          $fcnEnd[util::$LCDAY] );
      }
      if( isset( $recur[util::$UNTIL][util::$LCHOUR] ))
        $untilHis  = sprintf( util::$HIS, $recur[util::$UNTIL][util::$LCHOUR],
                                          $recur[util::$UNTIL][util::$LCMIN],
                                          $recur[util::$UNTIL][util::$LCSEC] );
      else
        $untilHis  = sprintf( util::$HIS, 23, 59, 59 );
// echo 'recurUNTIL='.str_replace( [PHP_EOL, ' '], '', var_export( $recur['UNTIL'], true )).", untilHis={$untilHis}<br> \n"; // test ###
    } // end if( isset( $recur[util::$UNTIL] ))
// echo 'fcnEnd:'.$fcnEndYMD.$untilHis."<br>\n"; // test ###
    if( $wdateYMD > $fcnEndYMD ) {
// echo 'recur out of date, '.implode('-',$wdate).', end='.implode('-',$fcnEnd)."<br>\n"; // test ###
      return []; // nothing to do.. .
    }
    if( ! isset( $recur[util::$FREQ] )) // "MUST be specified.. ."
      $recur[util::$FREQ] = self::$DAILY; // ??
    $wkst         = ( isset( $recur[util::$WKST] ) &&
                    ( $SU == $recur[util::$WKST] )) ? 24*60*60 : 0; // ??
    if( ! isset( $recur[util::$INTERVAL] ))
      $recur[util::$INTERVAL] = 1;
    $recurCount   = ( ! isset( $recur[util::$BYSETPOS] )) ? 1 : 0; // DTSTART counts as the first occurrence
            /* find out how to step up dates and set index for interval count */
    $step = [];
    if( self::$YEARLY == $recur[util::$FREQ] )
      $step[util::$LCYEAR]  = 1;
    elseif( self::$MONTHLY == $recur[util::$FREQ] )
      $step[util::$LCMONTH] = 1;
    elseif( self::$WEEKLY == $recur[util::$FREQ] )
      $step[util::$LCDAY]   = 7;
    else
      $step[util::$LCDAY]   = 1;
    if( isset( $step[util::$LCYEAR] ) && isset( $recur[util::$BYMONTH] ))
      $step = [util::$LCMONTH => 1];
    if( empty( $step ) && isset( $recur[util::$BYWEEKNO] )) // ??
      $step = [util::$LCDAY => 7];
    if( isset( $recur[util::$BYYEARDAY] ) ||
        isset( $recur[util::$BYMONTHDAY] ) ||
        isset( $recur[util::$BYDAY] ))
      $step = [util::$LCDAY => 1];
    $intervalarr = [];
    if( 1 < $recur[util::$INTERVAL] ) {
      $intervalix = self::recurIntervalIx( $recur[util::$FREQ],
                                           $wdate,
                                           $wkst );
      $intervalarr = [$intervalix => 0];
    }
    if( isset( $recur[util::$BYSETPOS] )) { // save start date + weekno
      $bysetposymd1 = $bysetposymd2 = $bysetposw1 = $bysetposw2 = [];
      if( is_array( $recur[util::$BYSETPOS] )) {
        foreach( $recur[util::$BYSETPOS] as $bix => $bval )
          $recur[util::$BYSETPOS][$bix] = (int) $bval;
      }
      else
        $recur[util::$BYSETPOS] = [(int) $recur[util::$BYSETPOS]];
      if( self::$YEARLY == $recur[util::$FREQ] ) {
        $wdate[util::$LCMONTH] = $wdate[util::$LCDAY] = 1; // start from beginning of year
        $wdateYMD = sprintf( util::$YMD, $wdate[util::$LCYEAR],
                                         $wdate[util::$LCMONTH],
                                         $wdate[util::$LCDAY] );
        self::stepdate( $fcnEnd, $fcnEndYMD, [util::$LCYEAR => 1] ); // make sure to count last year
      }
      elseif( self::$MONTHLY == $recur[util::$FREQ] ) {
        $wdate[util::$LCDAY]   = 1; // start from beginning of month
        $wdateYMD = sprintf( util::$YMD, $wdate[util::$LCYEAR],
                                         $wdate[util::$LCMONTH],
                                         $wdate[util::$LCDAY] );
        self::stepdate( $fcnEnd, $fcnEndYMD, [util::$LCMONTH => 1] ); // make sure to count last month
      }
      else
        self::stepdate( $fcnEnd, $fcnEndYMD, $step); // make sure to count whole last period
// echo "BYSETPOS endDat =".implode('-',$fcnEnd).' step='.var_export($step,true)."<br>\n"; // test ######
      $bysetposWold = (int) date( self::$W,
                                  mktime( 0,
                                          0,
                                          $wkst,
                                          $wdate[util::$LCMONTH],
                                          $wdate[util::$LCDAY],
                                          $wdate[util::$LCYEAR] ));
      $bysetposYold = $wdate[util::$LCYEAR];
      $bysetposMold = $wdate[util::$LCMONTH];
      $bysetposDold = $wdate[util::$LCDAY];
    } // end if( isset( $recur[util::$BYSETPOS] ))
    else
      self::stepdate( $wdate, $wdateYMD, $step);
    $year_old      = null;
             /* MAIN LOOP */
    while( true ) {
// $txt = ( isset( $recur[util::$COUNT] )) ? '. recurCount : ' . $recurCount . ', COUNT : ' . $recur[util::$COUNT] : null; // test ###
// echo "recur start while:<b>{$wdateYMD}</b>, end:{$fcnEndYMD}{$txt}<br>\n"; // test ###
      if( $wdateYMD.$wdateHis > $fcnEndYMD.$untilHis )
        break;
      if( isset( $recur[util::$COUNT] ) &&
         ( $recurCount >= $recur[util::$COUNT] ))
        break;
      if( $year_old != $wdate[util::$LCYEAR] ) { // $year_old=null 1:st time
        $year_old    = $wdate[util::$LCYEAR];
        $daycnts     = self::initDayCnts( $wdate, $recur, $wkst );
      }

            /* check interval */
      if( 1 < $recur[util::$INTERVAL] ) {
            /* create interval index */
        $intervalix = self::recurIntervalIx( $recur[util::$FREQ],
                                             $wdate,
                                             $wkst );
            /* check interval */
        $currentKey = array_keys( $intervalarr );
        $currentKey = end( $currentKey ); // get last index
        if( $currentKey != $intervalix )
          $intervalarr = [$intervalix => ( $intervalarr[$currentKey] + 1 )];
        if(( $recur[util::$INTERVAL] != $intervalarr[$intervalix] ) &&
           ( 0 != $intervalarr[$intervalix] )) {
            /* step up date */
// echo "skip: ".implode('-',$wdate)." ix=$intervalix old=$currentKey interval=".$intervalarr[$intervalix]."<br>\n"; // test ###
          self::stepdate( $wdate, $wdateYMD, $step);
          continue;
        }
        else // continue within the selected interval
          $intervalarr[$intervalix] = 0;
// echo "cont: ".implode('-',$wdate)." ix=$intervalix old=$currentKey interval=".$intervalarr[$intervalix]."<br>\n"; // test ###
      } // endif( 1 < $recur['INTERVAL'] )
      $updateOK = true;
      if( $updateOK && isset( $recur[util::$BYMONTH] ))
        $updateOK = self::recurBYcntcheck( $recur[util::$BYMONTH],
                                           $wdate[util::$LCMONTH],
                                         ( $wdate[util::$LCMONTH] - 13 ));
      if( $updateOK && isset( $recur[util::$BYWEEKNO] ))
        $updateOK = self::recurBYcntcheck( $recur[util::$BYWEEKNO],
                                           $daycnts[$wdate[util::$LCMONTH]][$wdate[util::$LCDAY]][self::$WEEKNO_UP],
                                           $daycnts[$wdate[util::$LCMONTH]][$wdate[util::$LCDAY]][self::$WEEKNO_DOWN] );
      if( $updateOK && isset( $recur[util::$BYYEARDAY] ))
        $updateOK = self::recurBYcntcheck( $recur[util::$BYYEARDAY],
                                           $daycnts[$wdate[util::$LCMONTH]][$wdate[util::$LCDAY]][self::$YEARCNT_UP],
                                           $daycnts[$wdate[util::$LCMONTH]][$wdate[util::$LCDAY]][self::$YEARCNT_DOWN] );
      if( $updateOK && isset( $recur[util::$BYMONTHDAY] ))
        $updateOK = self::recurBYcntcheck( $recur[util::$BYMONTHDAY],
                                           $wdate[util::$LCDAY],
                                           $daycnts[$wdate[util::$LCMONTH]][$wdate[util::$LCDAY]][self::$MONTHCNT_DOWN] );
// echo "efter BYMONTHDAY: ".implode('-',$wdate).' status: '; echo ($updateOK) ? 'true' : 'false'; echo "<br>\n"; // test ######
      if( $updateOK && isset( $recur[util::$BYDAY] )) {
        $updateOK = false;
        $m = $wdate[util::$LCMONTH];
        $d = $wdate[util::$LCDAY];
        if( isset( $recur[util::$BYDAY][util::$DAY] )) { // single day, opt with year/month day order no
          $daynoexists = $daynosw = $daynamesw =  false;
          if( $recur[util::$BYDAY][util::$DAY] == $daycnts[$m][$d][util::$DAY] )
            $daynamesw = true;
          if( isset( $recur[util::$BYDAY][0] )) {
            $daynoexists = true;
            if(( isset( $recur[util::$FREQ] ) &&
                ( $recur[util::$FREQ] == self::$MONTHLY )) ||
               isset( $recur[util::$BYMONTH] ))
              $daynosw = self::recurBYcntcheck( $recur[util::$BYDAY][0],
                                                $daycnts[$m][$d][self::$MONTHDAYNO_UP],
                                                $daycnts[$m][$d][self::$MONTHDAYNO_DOWN] );
            elseif( isset( $recur[util::$FREQ] ) &&
                   ( $recur[util::$FREQ] == self::$YEARLY ))
              $daynosw = self::recurBYcntcheck( $recur[util::$BYDAY][0],
                                                $daycnts[$m][$d][self::$YEARDAYNO_UP],
                                                $daycnts[$m][$d][self::$YEARDAYNO_DOWN] );
          }
          if((   $daynoexists &&   $daynosw && $daynamesw ) ||
             ( ! $daynoexists && ! $daynosw && $daynamesw )) {
            $updateOK = true;
// echo "m=$m d=$d day=".$daycnts[$m][$d][util::$DAY]." yeardayno_up=".$daycnts[$m][$d][self::$YEARDAYNO_UP]." daynoexists:$daynoexists daynosw:$daynosw daynamesw:$daynamesw updateOK:$updateOK<br>\n"; // test ###
          }
// echo "m=$m d=$d day=".$daycnts[$m][$d][util::$DAY]." yeardayno_up=".$daycnts[$m][$d][self::$YEARDAYNO_UP]." daynoexists:$daynoexists daynosw:$daynosw daynamesw:$daynamesw updateOK:$updateOK<br>\n"; // test ###
        }
        else {
          foreach( $recur[util::$BYDAY] as $bydayvalue ) {
            $daynoexists = $daynosw = $daynamesw = false;
            if( isset( $bydayvalue[util::$DAY] ) &&
                     ( $bydayvalue[util::$DAY] == $daycnts[$m][$d][util::$DAY] ))
              $daynamesw = true;
            if( isset( $bydayvalue[0] )) {
              $daynoexists = true;
              if(( isset( $recur[util::$FREQ] ) &&
                  ( $recur[util::$FREQ] == self::$MONTHLY )) ||
                  isset( $recur[util::$BYMONTH] ))
                $daynosw = self::recurBYcntcheck( $bydayvalue['0'],
                                                  $daycnts[$m][$d][self::$MONTHDAYNO_UP],
                                                  $daycnts[$m][$d][self::$MONTHDAYNO_DOWN] );
              elseif( isset( $recur[util::$FREQ] ) &&
                    ( $recur[util::$FREQ] == self::$YEARLY ))
                $daynosw = self::recurBYcntcheck( $bydayvalue['0'],
                                                  $daycnts[$m][$d][self::$YEARDAYNO_UP],
                                                  $daycnts[$m][$d][self::$YEARDAYNO_DOWN] );
            }
// echo "daynoexists:$daynoexists daynosw:$daynosw daynamesw:$daynamesw<br>\n"; // test ###
            if((   $daynoexists &&   $daynosw && $daynamesw ) ||
               ( ! $daynoexists && ! $daynosw && $daynamesw )) {
              $updateOK = true;
              break;
            }
          }
        }
      }
// echo "efter BYDAY: ".implode('-',$wdate).' status: '; echo ($updateOK) ? 'true' : 'false'; echo "<br>\n"; // test ###
            /* check BYSETPOS */
      if( $updateOK ) {
        if( isset( $recur[util::$BYSETPOS] ) &&
          ( in_array( $recur[util::$FREQ], $YEAR2DAYARR))) {
          if( isset( $recur[self::$WEEKLY] )) {
            if( $bysetposWold == $daycnts[$wdate[util::$LCMONTH]][$wdate[util::$LCDAY]][self::$WEEKNO_UP] )
              $bysetposw1[] = $wdateYMD;
            else
              $bysetposw2[] = $wdateYMD;
          }
          else {
            if(( isset( $recur[util::$FREQ] ) &&
                ( self::$YEARLY == $recur[util::$FREQ] ) &&
                ( $bysetposYold == $wdate[util::$LCYEAR] ))      ||
               ( isset( $recur[util::$FREQ] ) &&
                ( self::$MONTHLY  == $recur[util::$FREQ] ) &&
                 (( $bysetposYold == $wdate[util::$LCYEAR] ) &&
                  ( $bysetposMold == $wdate[util::$LCMONTH] )))  ||
               ( isset( $recur[util::$FREQ] ) &&
                 ( self::$DAILY    == $recur[util::$FREQ] ) &&
                  (( $bysetposYold == $wdate[util::$LCYEAR] ) &&
                   ( $bysetposMold == $wdate[util::$LCMONTH]) &&
                   ( $bysetposDold == $wdate[util::$LCDAY] )))) {
// echo "bysetposymd1[]=".date('Y-m-d H:i:s',$wdatets)."<br>\n"; // test ###
              $bysetposymd1[] = $wdateYMD;
            }
            else {
// echo "bysetposymd2[]=".date('Y-m-d H:i:s',$wdatets)."<br>\n"; // test ###
              $bysetposymd2[] = $wdateYMD;
            }
          } // end else
        }
        else {
          if( checkdate((int) $wdate[util::$LCMONTH],
                        (int) $wdate[util::$LCDAY],
                        (int) $wdate[util::$LCYEAR] )) {
            /* update result array if BYSETPOS is not set */
            $recurCount++;
            if( $fcnStartYMD <= $wdateYMD ) { // only output within period
              $result[$wdateYMD] = true;
// echo "recur $wdateYMD, recurCount:{$recurCount}<br>\n"; // test ###
            }
          }
// else echo "recur, no date $wdateYMD<br>\n"; // test ###
          $updateOK = false;
        }
      }
            /* step up date */
      self::stepdate( $wdate, $wdateYMD, $step);
            /* check if BYSETPOS is set for updating result array */
      if( $updateOK && isset( $recur[util::$BYSETPOS] )) {
        $bysetpos       = false;
        if( isset( $recur[util::$FREQ] ) &&
           ( self::$YEARLY == $recur[util::$FREQ] ) &&
           ( $bysetposYold != $wdate[util::$LCYEAR] )) {
          $bysetpos     = true;
          $bysetposYold = $wdate[util::$LCYEAR];
        }
        elseif( isset( $recur[util::$FREQ] ) &&
          ( self::$MONTHLY == $recur[util::$FREQ] &&
          (( $bysetposYold != $wdate[util::$LCYEAR] ) ||
           ( $bysetposMold != $wdate[util::$LCMONTH] )))) {
          $bysetpos     = true;
          $bysetposYold = $wdate[util::$LCYEAR];
          $bysetposMold = $wdate[util::$LCMONTH];
        }
        elseif( isset( $recur[util::$FREQ] ) &&
          ( self::$WEEKLY  == $recur[util::$FREQ] )) {
          $weekno = (int) date( self::$W,
                                mktime( 0,
                                        0,
                                        $wkst,
                                        $wdate[util::$LCMONTH],
                                        $wdate[util::$LCDAY],
                                        $wdate[util::$LCYEAR] ));
          if( $bysetposWold != $weekno ) {
            $bysetposWold = $weekno;
            $bysetpos     = true;
          }
        }
        elseif( isset( $recur[util::$FREQ] ) &&
          ( self::$DAILY   == $recur[util::$FREQ] ) &&
          (( $bysetposYold != $wdate[util::$LCYEAR] )  ||
           ( $bysetposMold != $wdate[util::$LCMONTH] ) ||
           ( $bysetposDold != $wdate[util::$LCDAY] ))) {
          $bysetpos     = true;
          $bysetposYold = $wdate[util::$LCYEAR];
          $bysetposMold = $wdate[util::$LCMONTH];
          $bysetposDold = $wdate[util::$LCDAY];
        }
        if( $bysetpos ) {
          if( isset( $recur[util::$BYWEEKNO] )) {
            $bysetposarr1 = & $bysetposw1;
            $bysetposarr2 = & $bysetposw2;
          }
          else {
            $bysetposarr1 = & $bysetposymd1;
            $bysetposarr2 = & $bysetposymd2;
          }

          foreach( $recur[util::$BYSETPOS] as $ix ) {
            if( 0 > $ix ) // both positive and negative BYSETPOS allowed
              $ix = ( count( $bysetposarr1 ) + $ix + 1);
            $ix--;
            if( isset( $bysetposarr1[$ix] )) {
              if( $fcnStartYMD <= $bysetposarr1[$ix] ) { // only output within period
//                $testweekno = (int) date( $W, mktime( 0, 0, $wkst, (int) substr( $bysetposarr1[$ix], 4, 2 ), (int) substr( $bysetposarr1[$ix], 6, 2 ), (int) substr( $bysetposarr1[$ix], 0, 3 ))); // test ###
// echo " testYMD (weekno)=$bysetposarr1[$ix] ($testweekno)";   // test ###
                $result[$bysetposarr1[$ix]] = true;
              }
              $recurCount++;
            }
            if( isset( $recur[util::$COUNT] ) && ( $recurCount >= $recur[util::$COUNT] ))
              break;
          }
// echo "<br>\n"; // test ###
          $bysetposarr1 = $bysetposarr2;
          $bysetposarr2 = [];
        } // end if( $bysetpos )
      } // end if( $updateOK && isset( $recur['BYSETPOS'] ))
    } // end while( true )
// echo 'output='.str_replace( [PHP_EOL, ' '], '', var_export( $result, true ))."<br> \n"; // test ###
  }
/**
 * Checking BYDAY (etc) hits, recur2date help function
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.6.12 - 2011-01-03
 * @param array $BYvalue
 * @param int   $upValue
 * @param int   $downValue
 * @return bool
 * @access private
 * @static
 */
  private static function recurBYcntcheck( $BYvalue, $upValue, $downValue ) {
    if( is_array( $BYvalue ) &&
      ( in_array( $upValue, $BYvalue ) || in_array( $downValue, $BYvalue )))
      return true;
    elseif(( $BYvalue == $upValue ) || ( $BYvalue == $downValue ))
      return true;
    else
      return false;
  }
/**
 * (re-)Calculate internal index, recur2date help function
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.6.12 - 2011-01-03
 * @param string $freq
 * @param array  $date
 * @param int    $wkst
 * @return bool
 * @access private
 * @static
 */
  private static function recurIntervalIx( $freq, $date, $wkst ) {
            /* create interval index */
    switch( $freq ) {
      case self::$YEARLY :
        $intervalix = $date[util::$LCYEAR];
        break;
      case self::$MONTHLY :
        $intervalix = $date[util::$LCYEAR] . util::$MINUS . $date[util::$LCMONTH];
        break;
      case self::$WEEKLY :
        $intervalix = (int) date( self::$W,
                                  mktime( 0,
                                          0,
                                          $wkst,
                                          (int) $date[util::$LCMONTH],
                                          (int) $date[util::$LCDAY],
                                          (int) $date[util::$LCYEAR] ));
       break;
      case self::$DAILY :
           default:
        $intervalix = $date[util::$LCYEAR] .
                      util::$MINUS .
                      $date[util::$LCMONTH] .
                      util::$MINUS .
                      $date[util::$LCDAY];
        break;
    }
    return $intervalix;
  }
/**
 * Return updated date, array and timpstamp
 *
 * @param array  $date     date to step
 * @param string $dateYMD  date YMD
 * @param array  $step     default array( util::$LCDAY => 1 )
 * @return void
 * @access private
 * @static
 */
  private static function stepdate( & $date, & $dateYMD, $step=null ) {
    static $t = 't';
    if( is_null( $step ))
      $step   = [util::$LCDAY => 1];
    if( ! isset( $date[util::$LCHOUR] ))
      $date[util::$LCHOUR] = 0;
    if( ! isset( $date[util::$LCMIN] ))
      $date[util::$LCMIN]  = 0;
    if( ! isset( $date[util::$LCSEC] ))
      $date[util::$LCSEC]  = 0;
    if( isset( $step[util::$LCDAY] ))
      $mcnt        = date( $t,
                           mktime( (int) $date[util::$LCHOUR],
                                   (int) $date[util::$LCMIN],
                                   (int) $date[util::$LCSEC],
                                   (int) $date[util::$LCMONTH],
                                   (int) $date[util::$LCDAY],
                                   (int) $date[util::$LCYEAR] ));
    foreach( $step as $stepix => $stepvalue )
      $date[$stepix]            += $stepvalue;
    if( isset( $step[util::$LCMONTH] )) {
      if( 12 < $date[util::$LCMONTH] ) {
        $date[util::$LCYEAR]    += 1;
        $date[util::$LCMONTH]   -= 12;
      }
    }
    elseif( isset( $step[util::$LCDAY] )) {
      if( $mcnt < $date[util::$LCDAY] ) {
        $date[util::$LCDAY]     -= $mcnt;
        $date[util::$LCMONTH]   += 1;
        if( 12 < $date[util::$LCMONTH] ) {
          $date[util::$LCYEAR]  += 1;
          $date[util::$LCMONTH] -= 12;
        }
      }
    }
    $dateYMD       = sprintf( util::$YMD, (int) $date[util::$LCYEAR],
                                          (int) $date[util::$LCMONTH],
                                          (int) $date[util::$LCDAY] );
  }
/**
 * Return initiated $daycnts
 *
 * @param array $wdate
 * @param array $recur
 * @param int   $wkst
 * @return array
 * @access private
 * @static
 */
  private static function initDayCnts( array $wdate, array $recur, $wkst ) {
    $daycnts    = [];
    $yeardaycnt = [];
    $yeardays   = 0;
    foreach( self::$DAYNAMES as $dn )
      $yeardaycnt[$dn] = 0;
    for( $m = 1; $m <= 12; $m++ ) { // count up and update up-counters
      $daycnts[$m] = [];
      $weekdaycnt = [];
      foreach( self::$DAYNAMES as $dn )
        $weekdaycnt[$dn] = 0;
      $mcnt     = date( 't', mktime( 0, 0, 0, $m, 1, $wdate[util::$LCYEAR] ));
      for( $d   = 1; $d <= $mcnt; $d++ ) {
        $daycnts[$m][$d] = [];
        if( isset( $recur[util::$BYYEARDAY] )) {
          $yeardays++;
          $daycnts[$m][$d][self::$YEARCNT_UP] = $yeardays;
        }
        if( isset( $recur[util::$BYDAY] )) {
          $day    = date( 'w', mktime( 0, 0, 0, $m, $d, $wdate[util::$LCYEAR] ));
          $day    = self::$DAYNAMES[$day];
          $daycnts[$m][$d][util::$DAY] = $day;
          $weekdaycnt[$day]++;
          $daycnts[$m][$d][self::$MONTHDAYNO_UP] = $weekdaycnt[$day];
          $yeardaycnt[$day]++;
          $daycnts[$m][$d][self::$YEARDAYNO_UP] = $yeardaycnt[$day];
        }
        if( isset( $recur[util::$BYWEEKNO] ) ||
                 ( $recur[util::$FREQ] == self::$WEEKLY ))
          $daycnts[$m][$d][self::$WEEKNO_UP] = (int) date( self::$W,
                                                           mktime( 0,
                                                                   0,
                                                                   $wkst,
                                                                   $m,
                                                                   $d,
                                                                   $wdate[util::$LCYEAR] ));
      } // end for( $d   = 1; $d <= $mcnt; $d++ )
    } // end for( $m = 1; $m <= 12; $m++ )
    $daycnt = 0;
    $yeardaycnt = [];
    if( isset( $recur[util::$BYWEEKNO] ) ||
             ( $recur[util::$FREQ] == self::$WEEKLY )) {
      $weekno = null;
      for( $d=31; $d > 25; $d-- ) { // get last weekno for year
        if( ! $weekno )
          $weekno = $daycnts[12][$d][self::$WEEKNO_UP];
        elseif( $weekno < $daycnts[12][$d][self::$WEEKNO_UP] ) {
          $weekno = $daycnts[12][$d][self::$WEEKNO_UP];
          break;
        }
      }
    }
    for( $m = 12; $m > 0; $m-- ) { // count down and update down-counters
      $weekdaycnt = [];
      foreach( self::$DAYNAMES as $dn )
        $yeardaycnt[$dn] = $weekdaycnt[$dn] = 0;
      $monthcnt = 0;
      $mcnt     = date( 't', mktime( 0, 0, 0, $m, 1, $wdate[util::$LCYEAR] ));
      for( $d   = $mcnt; $d > 0; $d-- ) {
        if( isset( $recur[util::$BYYEARDAY] )) {
          $daycnt -= 1;
          $daycnts[$m][$d][self::$YEARCNT_DOWN] = $daycnt;
        }
        if( isset( $recur[util::$BYMONTHDAY] )) {
          $monthcnt -= 1;
          $daycnts[$m][$d][self::$MONTHCNT_DOWN] = $monthcnt;
        }
        if( isset( $recur[util::$BYDAY] )) {
          $day  = $daycnts[$m][$d][util::$DAY];
          $weekdaycnt[$day] -= 1;
          $daycnts[$m][$d][self::$MONTHDAYNO_DOWN] = $weekdaycnt[$day];
          $yeardaycnt[$day] -= 1;
          $daycnts[$m][$d][self::$YEARDAYNO_DOWN] = $yeardaycnt[$day];
        }
        if( isset( $recur[util::$BYWEEKNO] ) ||
                 ( $recur[util::$FREQ] == self::$WEEKLY ))
          $daycnts[$m][$d][self::$WEEKNO_DOWN] = ( $daycnts[$m][$d][self::$WEEKNO_UP] - $weekno - 1 );
      }
    } // end for( $m = 12; $m > 0; $m-- )
    return $daycnts;
  }
/**
 * Return a reformatted input date
 *
 * @param mixed $aDate
 * @access private
 * @static
 */
  private static function reFormatDate( & $aDate ) {
    static $YMDHIS2 = 'Y-m-d H:i:s';
    switch( true ) {
      case ( is_string( $aDate )) :
        util::strDate2arr( $aDate );
        break;
      case ( util::isDateTimeClass( $aDate )) :
        $aDate = $aDate->format( $YMDHIS2 );
        util::strDate2arr( $aDate );
        break;
      default :
        break;
    }
    foreach( $aDate as $k => $v ) {
      if( ctype_digit( $v ))
        $aDate[$k] = (int) $v;
    }
  }
}
