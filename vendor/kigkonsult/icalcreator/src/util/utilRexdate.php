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
 * iCalcreator EXDATE/RDATE support class
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.23 - 2017-04-04
 */
class utilRexdate {
/**
 * Check (EXDATE/RDATE) date(-time) and params arrays for an opt. timezone
 *
 * If it is a DATE-TIME or DATE, updates $parno and (opt) $params)
 * @param array $theDate    date to check
 * @param int   $parno      no of date parts (i.e. year, month.. .)
 * @param array $params     property parameters
 * @access private
 * @static
 */
  private static function chkDateCfg( $theDate, & $parno, & $params ) {
    $paramsValueIsDATE = util::isParamsValueSet( [util::$LCparams => $params],
                                                 util::$DATE );
    switch( true ) {
      case ( isset( $params[util::$TZID] )) :
        $parno = 6;
        break;
      case ( $paramsValueIsDATE ) :
        $params[util::$VALUE] = util::$DATE;
        $parno = 3;
        break;
      default:
        if( util::isParamsValueSet( [util::$LCparams => $params],
                                    util::$PERIOD )) {
          $params[util::$VALUE] = util::$PERIOD;
          $parno = 7;
        }
        switch( true ) {
          case ( is_array( $theDate )) :
            if( isset( $theDate[util::$LCTIMESTAMP] ))
              $tzid = ( isset( $theDate[util::$LCtz] ))
                    ? $theDate[util::$LCtz] : null;
            else
              $tzid = ( isset( $theDate[util::$LCtz] ))
                    ? $theDate[util::$LCtz] : ( 7 == count( $theDate )) ? end( $theDate ) : null;
            if( ! empty( $tzid )) {
              $parno = 7;
              if( ! util::isOffset( $tzid ))
                $params[util::$TZID] = $tzid; // save only timezone
            }
            elseif( ! $parno &&
                   ( 3 == count( $theDate )) &&
                   $paramsValueIsDATE )
              $parno = 3;
            else
              $parno = 6;
            break;
          default : // i.e. string
            $date = trim((string) $theDate );
            if( util::$Z == substr( $date, -1 ))
              $parno = 7; // UTC DATE-TIME
            elseif((( 8 == strlen( $date ) && ctype_digit( $date )) ||
                   ( 11 >= strlen( $date )))
                && $paramsValueIsDATE )
              $parno = 3;
            $date = util::strDate2ArrayDate( $date, $parno );
            unset( $date[util::$UNPARSEDTEXT] );
            if( ! empty( $date[util::$LCtz] )) {
              $parno = 7;
              if( ! util::isOffset( $date[util::$LCtz] ))
                $params[util::$TZID] = $date[util::$LCtz]; // save only timezone
            }
            elseif( empty( $parno ))
              $parno = 6;
        } // end switch( true )
        if( isset( $params[util::$TZID] ))
          $parno = 6;
        break;
    } // end switch( true )
    return true;
  }
/**
 * Return formatted output for calendar component property data value type recur
 *
 * @param array  $exdateData
 * @param bool   $allowEmpty
 * @return string
 * @static
 */
  public static function formatExdate( $exdateData, $allowEmpty ) {
    static $SORTER1 = ['kigkonsult\iCalcreator\vcalendarSortHandler',
                       'sortExdate1'];
    static $SORTER2 = ['kigkonsult\iCalcreator\vcalendarSortHandler',
                       'sortExdate2'];
    $output  = null;
    $exdates = [];
    foreach(( array_keys( $exdateData )) as $ex ) {
      $theExdate = $exdateData[$ex];
      if( empty( $theExdate[util::$LCvalue] )) {
        if( $allowEmpty )
          $output .= util::createElement( util::$EXDATE );
        continue;
      }
      if( 1 < count( $theExdate[util::$LCvalue] ))
        usort( $theExdate[util::$LCvalue], $SORTER1 );
      $exdates[] = $theExdate;
    }
    if( 1 < count( $exdates ))
      usort( $exdates, $SORTER2 );
    foreach(( array_keys( $exdates )) as $ex ) {
      $theExdate = $exdates[$ex];
      $content = $attributes = null;
      foreach(( array_keys( $theExdate[util::$LCvalue] )) as $eix ) {
        $exdatePart = $theExdate[util::$LCvalue][$eix];
        $parno = count( $exdatePart );
        $formatted = util::date2strdate( $exdatePart, $parno );
        if( isset( $theExdate[util::$LCparams][util::$TZID] ))
          $formatted = str_replace( util::$Z, null, $formatted);
        if( 0 < $eix ) {
          if( isset( $theExdate[util::$LCvalue][0][util::$LCtz] )) {
            if( ctype_digit( substr( $theExdate[util::$LCvalue][0][util::$LCtz], -4 )) ||
               ( util::$Z == $theExdate[util::$LCvalue][0][util::$LCtz] )) {
              if( util::$Z != substr( $formatted, -1 ))
                $formatted .= util::$Z;
            }
            else
              $formatted = str_replace( util::$Z, null, $formatted );
          }
          else
            $formatted = str_replace( util::$Z, null, $formatted );
        } // end if( 0 < $eix )
        $content  .= ( 0 < $eix ) ? util::$COMMA . $formatted : $formatted;
      } // end foreach(( array_keys( $theExdate[util::$LCvalue]...
      $output     .= util::createElement( util::$EXDATE,
                                          util::createParams( $theExdate[util::$LCparams] ),
                                          $content );
    } // end foreach(( array_keys( $exdates...
    return $output;
  }
/**
 * Return prepared calendar component property exdate input
 *
 * @param array   $exdates
 * @param array   $params
 * @return array
 * @static
 */
  public static function prepInputExdate( $exdates, $params=null ) {
    static $GMTUTCZARR = ['GMT', 'UTC', 'Z'];
    $input  = [util::$LCparams => util::setParams( $params,
                                                   util::$DEFAULTVALUEDATETIME )];
    $toZ = ( isset( $input[util::$LCparams][util::$TZID] ) &&
             in_array( strtoupper( $input[util::$LCparams][util::$TZID] ),
                       $GMTUTCZARR ))
         ? true : false;
            /* ev. check 1:st date and save ev. timezone **/
    self::chkDateCfg( reset( $exdates ), $parno, $input[util::$LCparams] );
    util::existRem( $input[util::$LCparams],
                    util::$VALUE,
                    util::$DATE_TIME ); // remove default parameter
    foreach(( array_keys( $exdates )) as $eix ) {
      $theExdate = $exdates[$eix];
      util::strDate2arr( $theExdate );
      if( util::isArrayTimestampDate( $theExdate )) {
        if(            isset( $theExdate[util::$LCtz] ) &&
           ! util::isOffset( $theExdate[util::$LCtz] )) {
          if( isset( $input[util::$LCparams][util::$TZID] ))
            $theExdate[util::$LCtz] = $input[util::$LCparams][util::$TZID];
          else
            $input[util::$LCparams][util::$TZID] = $theExdate[util::$LCtz];
        }
        $exdatea = util::timestamp2date( $theExdate, $parno );
      }
      elseif( is_array( $theExdate )) {
        $d = util::chkDateArr( $theExdate, $parno );
        if(         isset( $d[util::$LCtz] ) &&
             ( util::$Z != $d[util::$LCtz] ) &&
          util::isOffset( $d[util::$LCtz] )) {
          $strdate = sprintf( util::$YMDHISE, (int) $d[util::$LCYEAR],
                                              (int) $d[util::$LCMONTH],
                                              (int) $d[util::$LCDAY],
                                              (int) $d[util::$LCHOUR],
                                              (int) $d[util::$LCMIN],
                                              (int) $d[util::$LCSEC],
                                                    $d[util::$LCtz] );
          $exdatea = util::strDate2ArrayDate( $strdate, 7 );
          unset( $exdatea[util::$UNPARSEDTEXT] );
        }
        else
          $exdatea = $d;
      }
      elseif( 8 <= strlen( trim( $theExdate ))) { // ex. 2006-08-03 10:12:18
        $exdatea = util::strDate2ArrayDate( $theExdate, $parno );
        unset( $exdatea[util::$UNPARSEDTEXT] );
      }
      if( 3 == $parno )
        unset( $exdatea[util::$LCHOUR],
               $exdatea[util::$LCMIN],
               $exdatea[util::$LCSEC],
               $exdatea[util::$LCtz] );
      elseif( isset( $exdatea[util::$LCtz] ))
        $exdatea[util::$LCtz] = (string) $exdatea[util::$LCtz];
      if(  isset( $input[util::$LCparams][util::$TZID] )        ||
         ( isset( $exdatea[util::$LCtz] ) &&
          ! util::isOffset( $exdatea[util::$LCtz] ))            ||
         ( isset( $input[util::$LCvalue][0] ) &&
         ( ! isset( $input[util::$LCvalue][0][util::$LCtz] )))  ||
         ( isset( $input[util::$LCvalue][0][util::$LCtz] ) &&
          ! util::isOffset( $input[util::$LCvalue][0][util::$LCtz] )))
        unset( $exdatea[util::$LCtz] );
      if( $toZ ) // time zone Z
        $exdatea[util::$LCtz] = util::$Z;
      $input[util::$LCvalue][] = $exdatea;
    } // end foreach(( array_keys( $exdates...
    if( 0 >= count( $input[util::$LCvalue] ))
      return false;
    if( 3 == $parno ) {
      $input[util::$LCparams][util::$VALUE] = util::$DATE;
      unset( $input[util::$LCparams][util::$TZID] );
    }
    if( $toZ ) // time zone Z
      unset( $input[util::$LCparams][util::$TZID] );
    return $input;
  }
/**
 * Return formatted output for calendar component property rdate
 *
 * @param array  $rdateData
 * @param bool   $allowEmpty
 * @param string $objName
 * @return string
 * @static
 */
  public static function formatRdate( $rdateData, $allowEmpty, $objName ) {
    static $SORTER1 = ['kigkonsult\iCalcreator\vcalendarSortHandler',
                       'sortRdate1'];
    static $SORTER2 = ['kigkonsult\iCalcreator\vcalendarSortHandler',
                       'sortRdate2'];
    $utcTime = ( in_array( $objName, util::$TZCOMPS )) ? true : false;
    $output  = null;
    $rdates  = [];
    foreach(( array_keys( $rdateData )) as $rpix ) {
      $theRdate = $rdateData[$rpix];
      if( empty( $theRdate[util::$LCvalue] )) {
        if( $allowEmpty )
          $output .= util::createElement( util::$RDATE );
        continue;
      }
      if( $utcTime  )
        unset( $theRdate[util::$LCparams][util::$TZID] );
      if( 1 < count( $theRdate[util::$LCvalue] ))
        usort( $theRdate[util::$LCvalue], $SORTER1 );
      $rdates[] = $theRdate;
    }
    if( 1 < count( $rdates ))
      usort( $rdates, $SORTER2 );
    foreach(( array_keys( $rdates )) as $rpix ) {
      $theRdate = $rdates[$rpix];
      $attributes = util::createParams( $theRdate[util::$LCparams] );
      $cnt     = count( $theRdate[util::$LCvalue] );
      $content = null;
      $rno     = 1;
      foreach(( array_keys( $theRdate[util::$LCvalue] )) as $rix ) {
        $rdatePart = $theRdate[util::$LCvalue][$rix];
        $contentPart = null;
        if( is_array( $rdatePart ) &&
            util::isParamsValueSet( $theRdate, util::$PERIOD )) { // PERIOD
          if( $utcTime )
            unset( $rdatePart[0][util::$LCtz] );
          $formatted    = util::date2strdate( $rdatePart[0] ); // PERIOD part 1
          if( $utcTime || !empty( $theRdate[util::$LCparams][util::$TZID] ))
            $formatted  = str_replace( util::$Z, null, $formatted);
          $contentPart .= $formatted;
          $contentPart .= '/';
          $cnt2 = count( $rdatePart[1]);
          if( array_key_exists( util::$LCYEAR, $rdatePart[1] )) {
            if( array_key_exists( util::$LCHOUR, $rdatePart[1] ))
              $cnt2 = 7;                                      // date-time
            else
              $cnt2 = 3;                                      // date
          }
          elseif( array_key_exists( util::$LCWEEK, $rdatePart[1] ))  // duration
            $cnt2 = 5;
          if(( 7 == $cnt2 )   &&    // period=  -> date-time
              isset( $rdatePart[1][util::$LCYEAR] )  &&
              isset( $rdatePart[1][util::$LCMONTH] ) &&
              isset( $rdatePart[1][util::$LCDAY] )) {
            if( $utcTime )
              unset( $rdatePart[1][util::$LCtz] );
            $formatted = util::date2strdate( $rdatePart[1] ); // PERIOD part 2
            if( $utcTime || !empty( $theRdate[util::$LCparams][util::$TZID] ))
              $formatted  = str_replace( util::$Z, null, $formatted );
           $contentPart  .= $formatted;
          }
          else {                                  // period=  -> dur-time
            $contentPart .= util::duration2str( $rdatePart[1] );
          }
        } // PERIOD end
        else { // SINGLE date start
          if( $utcTime )
            unset( $rdatePart[util::$LCtz] );
          $parno        = ( util::isParamsValueSet( $theRdate, util::$DATE )) ? 3 : null;
          $formatted    = util::date2strdate( $rdatePart, $parno );
          if( $utcTime || !empty( $theRdate[util::$LCparams][util::$TZID] ))
            $formatted  = str_replace( util::$Z, null, $formatted);
          $contentPart .= $formatted;
        }
        $content   .= $contentPart;
        if( $rno < $cnt )
          $content .= util::$COMMA;
        $rno++;
      } // end foreach(( array_keys( $theRdate[util::$LCvalue]...
      $output      .= util::createElement( util::$RDATE,
                                           $attributes,
                                           $content );
    } // foreach(( array_keys( $rdates...
    return $output;
  }
/**
 * Return prepared calendar component property rdate input
 *
 * @param array  $rdates
 * @param array  $params
 * @param string $objName
 * @return array
 * @static
 */
  public static function prepInputRdate( $rdates, $params, $objName ) {
    static $PREFIXARR  = ['P', '+', '-'];
    static $GMTUTCZARR = ['GMT', 'UTC', 'Z'];
    $input = [util::$LCparams => util::setParams( $params,
                                                  util::$DEFAULTVALUEDATETIME )];
    if( in_array( $objName, util::$TZCOMPS )) {
      unset( $input[util::$LCparams][util::$TZID] );
      $input[util::$LCparams][util::$VALUE] = util::$DATE_TIME;
    }
    $toZ = ( isset( $params[util::$TZID] ) &&
             in_array( strtoupper( $params[util::$TZID] ),
                       $GMTUTCZARR ))
         ? true : false;
            /*  check if PERIOD, if not set */
    if(( ! isset( $input[util::$LCparams][util::$VALUE] ) ||
       ( ! util::isParamsValueSet( $input, util::$DATE ) &&
         ! util::isParamsValueSet( $input, util::$PERIOD ))) &&
          isset( $rdates[0] )    && is_array( $rdates[0] ) && ( 2 == count( $rdates[0] )) &&
          isset( $rdates[0][0] ) &&    isset( $rdates[0][1] ) && ! isset( $rdates[0][util::$LCTIMESTAMP] ) &&
    (( is_array( $rdates[0][0] ) &&  ( isset( $rdates[0][0][util::$LCTIMESTAMP] ) ||
                                      util::isArrayDate( $rdates[0][0] ))) ||
                                    ( is_string( $rdates[0][0] ) && ( 8 <= strlen( trim( $rdates[0][0] )))))  &&
     ( is_array( $rdates[0][1] ) || ( is_string( $rdates[0][1] ) && ( 3 <= strlen( trim( $rdates[0][1] ))))))
      $input[util::$LCparams][util::$VALUE] = util::$PERIOD;
            /* check 1:st date, upd. $parno (opt) and save opt. timezone **/
    $date  = reset( $rdates );
    if( isset( $input[util::$LCparams][util::$VALUE] ) &&
       ( util::$PERIOD == $input[util::$LCparams][util::$VALUE] )) // PERIOD
      $date  = reset( $date );
    self::chkDateCfg( $date, $parno, $input[util::$LCparams] );
    util::existRem( $input[util::$LCparams],
                    util::$VALUE,
                    util::$DATE_TIME ); // remove default
    foreach( $rdates as $rpix => $theRdate ) {
      $inputa = null;
      util::strDate2arr( $theRdate );
      if( is_array( $theRdate )) {
        if( isset( $input[util::$LCparams][util::$VALUE] ) &&
           ( util::$PERIOD == $input[util::$LCparams][util::$VALUE] )) { // PERIOD
          foreach( $theRdate as $rix => $rPeriod ) {
            util::strDate2arr( $theRdate );
            if( is_array( $rPeriod )) {
              if( util::isArrayTimestampDate( $rPeriod )) {    // timestamp
                if( isset( $rPeriod[util::$LCtz] ) &&
                    ! util::isOffset( $rPeriod[util::$LCtz] )) {
                  if( isset( $input[util::$LCparams][util::$TZID] ))
                    $rPeriod[util::$LCtz] = $input[util::$LCparams][util::$TZID];
                  else
                    $input[util::$LCparams][util::$TZID] = $rPeriod[util::$LCtz];
                }
                $inputab = util::timestamp2date( $rPeriod, $parno );
              } // end if( util::isArrayTimestampDate( $rPeriod ))
              elseif( util::isArrayDate( $rPeriod )) {
                $d = ( 3 < count ( $rPeriod ))
                   ? util::chkDateArr( $rPeriod, $parno )
                   : util::chkDateArr( $rPeriod, 6 );
                if(         isset( $d[util::$LCtz] ) &&
                     ( util::$Z != $d[util::$LCtz] ) &&
                  util::isOffset( $d[util::$LCtz] )) {
                  $inputab = util::strDate2ArrayDate( sprintf( util::$YMDHISE,
                                                               (int) $d[util::$LCYEAR],
                                                               (int) $d[util::$LCMONTH],
                                                               (int) $d[util::$LCDAY],
                                                               (int) $d[util::$LCHOUR],
                                                               (int) $d[util::$LCMIN],
                                                               (int) $d[util::$LCSEC],
                                                                     $d[util::$LCtz] ),
                                                      7 );
                  unset( $inputab[util::$UNPARSEDTEXT] );
                }
                else
                  $inputab = $d;
              } // end elseif( util::isArrayDate( $rPeriod ))
              elseif(( 1 == count( $rPeriod )) &&
                     ( 8 <= strlen( reset( $rPeriod )))) { // text-date
                $inputab   = util::strDate2ArrayDate( reset( $rPeriod ), $parno );
                unset( $inputab[util::$UNPARSEDTEXT] );
              }
              else                                               // array format duration
                $inputab   = util::duration2arr( $rPeriod );
            } // end if( is_array( $rPeriod ))
            elseif(( 3 <= strlen( trim( $rPeriod ))) &&          // string format duration
                   ( in_array( $rPeriod[0], $PREFIXARR ))) {
              if( 'P' != $rPeriod[0] )
                $rPeriod   = substr( $rPeriod, 1 );
              $inputab     = util::durationStr2arr( $rPeriod );
            }
            elseif( 8 <= strlen( trim( $rPeriod ))) {            // text date ex. 2006-08-03 10:12:18
              $inputab     = util::strDate2ArrayDate( $rPeriod, $parno );
              unset( $inputab[util::$UNPARSEDTEXT] );
            }
            if(( 0 == $rpix ) && ( 0 == $rix )) {
              if( isset( $inputab[util::$LCtz] ) &&
                  in_array( strtoupper( $inputab[util::$LCtz] ),
                            $GMTUTCZARR )) {
                $inputab[util::$LCtz] = util::$Z;
                $toZ = true;
              }
            }
            else {
              if( isset( $inputa[0][util::$LCtz] ) &&
                 ( util::$Z == $inputa[0][util::$LCtz] ) &&
                 isset( $inputab[util::$LCYEAR] ))
                $inputab[util::$LCtz] = util::$Z;
              else
                unset( $inputab[util::$LCtz] );
            }
            if( $toZ && isset( $inputab[util::$LCYEAR] ) )
              $inputab[util::$LCtz] = util::$Z;
            $inputa[]      = $inputab;
          } // end foreach( $theRdate as $rix => $rPeriod )
        } // PERIOD end
        elseif ( util::isArrayTimestampDate( $theRdate )) {    // timestamp
          if( isset( $theRdate[util::$LCtz] ) &&
             ! util::isOffset( $theRdate[util::$LCtz] )) {
            if( isset( $input[util::$LCparams][util::$TZID] ))
              $theRdate[util::$LCtz] = $input[util::$LCparams][util::$TZID];
            else
              $input[util::$LCparams][util::$TZID] = $theRdate[util::$LCtz];
          }
          $inputa = util::timestamp2date( $theRdate, $parno );
        }
        else {                                                                  // date[-time]
          $inputa = util::chkDateArr( $theRdate, $parno );
          if( isset( $inputa[util::$LCtz] ) &&
             ( util::$Z != $inputa[util::$LCtz] ) &&
               util::isOffset( $inputa[util::$LCtz] )) {
            $inputa  = util::strDate2ArrayDate( sprintf( util::$YMDHISE,
                                                         (int) $inputa[util::$LCYEAR],
                                                         (int) $inputa[util::$LCMONTH],
                                                         (int) $inputa[util::$LCDAY],
                                                         (int) $inputa[util::$LCHOUR],
                                                         (int) $inputa[util::$LCMIN],
                                                         (int) $inputa[util::$LCSEC],
                                                               $inputa[util::$LCtz] ),
                                                7 );
            unset( $inputa[util::$UNPARSEDTEXT] );
          }
        }
      } // end if( is_array( $theRdate ))
      elseif( 8 <= strlen( trim( $theRdate ))) {                 // text date ex. 2006-08-03 10:12:18
        $inputa       = util::strDate2ArrayDate( $theRdate, $parno );
        unset( $inputa[util::$UNPARSEDTEXT] );
        if( $toZ )
          $inputa[util::$LCtz] = util::$Z;
      }
      if( ! isset( $input[util::$LCparams][util::$VALUE] ) ||
         ( util::$PERIOD != $input[util::$LCparams][util::$VALUE] )) { // no PERIOD
        if(( 0 == $rpix ) && !$toZ )
          $toZ = ( isset( $inputa[util::$LCtz] ) &&
                   in_array( strtoupper( $inputa[util::$LCtz] ), $GMTUTCZARR ))
               ? true : false;
        if( $toZ )
          $inputa[util::$LCtz]    = util::$Z;
        if( 3 == $parno )
          unset( $inputa[util::$LCHOUR],
                 $inputa[util::$LCMIN],
                 $inputa[util::$LCSEC],
                 $inputa[util::$LCtz] );
        elseif( isset( $inputa[util::$LCtz] ))
          $inputa[util::$LCtz]    = (string) $inputa[util::$LCtz];
        if(  isset( $input[util::$LCparams][util::$TZID] ) ||
           ( isset( $input[util::$LCvalue][0] ) &&
            ( ! isset( $input[util::$LCvalue][0][util::$LCtz] ))))
          if( !$toZ )
            unset( $inputa[util::$LCtz] );
      }
      $input[util::$LCvalue][]    = $inputa;
    }
    if( 3 == $parno ) {
      $input[util::$LCparams][util::$VALUE] = util::$DATE;
      unset( $input[util::$LCparams][util::$TZID] );
    }
    if( $toZ )
      unset( $input[util::$LCparams][util::$TZID] );
    return $input;
  }
}
