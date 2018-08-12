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
 * FREEBUSY property functions
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.23 - 2017-02-17
 */
trait FREEBUSYtrait {
/**
 * @var array component property FREEBUSY value
 * @access protected
 */
  protected $freebusy = null;
/**
 * @var FREEBUSY param keywords
 * @access protected
 * @static
 */
   protected static $LCFBTYPE         = 'fbtype';
   protected static $UCFBTYPE         = 'FBTYPE';
   protected static $FREEBUSYKEYS     = ['FREE', 'BUSY', 'BUSY-UNAVAILABLE', 'BUSY-TENTATIVE'];
   protected static $FREE             = 'FREE';
   protected static $BUSY             = 'BUSY';
/*
   protected static $BUSY_UNAVAILABLE = 'BUSY-UNAVAILABLE';
   protected static $BUSY_TENTATIVE   = 'BUSY-TENTATIVE';
*/
/**
 * Return formatted output for calendar component property freebusy
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.27 - 2013-07-05
 * @return string
 */
  public function createFreebusy() {
    static $FMT    = ';FBTYPE=%s';
    static $SORTER = ['kigkonsult\iCalcreator\vcalendarSortHandler', 'sortRdate1'];
    if( empty( $this->freebusy ))
      return null;
    $output = null;
    foreach( $this->freebusy as $fx => $freebusyPart ) {
      if( empty( $freebusyPart[util::$LCvalue] ) ||
        (( 1 == count( $freebusyPart[util::$LCvalue] )) &&
           isset( $freebusyPart[util::$LCvalue][self::$LCFBTYPE] ))) {
        if( $this->getConfig( util::$ALLOWEMPTY ))
          $output .= util::createElement( util::$FREEBUSY );
        continue;
      }
      $attributes = $content = null;
      if( isset( $freebusyPart[util::$LCvalue][self::$LCFBTYPE] )) {
          $attributes .= sprintf( $FMT, $freebusyPart[util::$LCvalue][self::$LCFBTYPE] );
        unset( $freebusyPart[util::$LCvalue][self::$LCFBTYPE] );
        $freebusyPart[util::$LCvalue] = array_values( $freebusyPart[util::$LCvalue] );
      }
      else
        $attributes .= sprintf( $FMT, self::$BUSY );
      $attributes .= util::createParams( $freebusyPart[util::$LCparams] );
      $fno        = 1;
      $cnt        = count( $freebusyPart[util::$LCvalue]);
      if( 1 < $cnt )
        usort( $freebusyPart[util::$LCvalue], $SORTER );
      foreach( $freebusyPart[util::$LCvalue] as $periodix => $freebusyPeriod ) {
        $formatted   = util::date2strdate( $freebusyPeriod[0] );
        $content .= $formatted;
        $content .= util::$L;
        $cnt2 = count( $freebusyPeriod[1]);
        if( array_key_exists( util::$LCYEAR, $freebusyPeriod[1] )) // date-time
          $cnt2 = 7;
        elseif( array_key_exists( util::$LCWEEK, $freebusyPeriod[1] )) // duration
          $cnt2 = 5;
        if(( 7 == $cnt2 )   &&    // period=  -> date-time
            isset( $freebusyPeriod[1][util::$LCYEAR] )  &&
            isset( $freebusyPeriod[1][util::$LCMONTH] ) &&
            isset( $freebusyPeriod[1][util::$LCDAY] )) {
          $content .= util::date2strdate( $freebusyPeriod[1] );
        }
        else {                                                     // period=  -> dur-time
          $content .= util::duration2str( $freebusyPeriod[1] );
        }
        if( $fno < $cnt )
          $content .= util::$COMMA;
        $fno++;
      } // end foreach( $freebusyPart[util::$LCvalue] as $periodix => $freebusyPeriod )
      $output .= util::createElement( util::$FREEBUSY,
                                      $attributes,
                                      $content );
    } // end foreach( $this->freebusy as $fx => $freebusyPart )
    return $output;
  }
/**
 * Set calendar component property freebusy
 *
 * @param string  $fbType
 * @param array   $fbValues
 * @param array   $params
 * @param integer $index
 * @return bool
 */
  public function setFreebusy( $fbType, $fbValues, $params=null, $index=null ) {
    static $PREFIXARR = ['P', '+', '-'];
    if( empty( $fbValues )) {
      if( $this->getConfig( util::$ALLOWEMPTY )) {
        util::setMval( $this->freebusy,
                       util::$EMPTYPROPERTY,
                       $params,
                       false,
                       $index );
        return true;
      }
      else
        return false;
    }
    $fbType = strtoupper( $fbType );
    if( ! in_array( $fbType, self::$FREEBUSYKEYS ) &&
        ! util::isXprefixed( $fbType ))
      $fbType = self::$BUSY;
    $input = [self::$LCFBTYPE => $fbType];
    foreach( $fbValues as $fbPeriod ) {               // periods => period
      if( empty( $fbPeriod ))
        continue;
      $freebusyPeriod = [];
      foreach( $fbPeriod as $fbMember ) {             // pairs => singlepart
        $freebusyPairMember = [];
        if( is_array( $fbMember )) {
          if( util::isArrayDate( $fbMember )) {       // date-time value
            $freebusyPairMember       = util::chkDateArr( $fbMember, 7 );
            $freebusyPairMember[util::$LCtz] = util::$Z;
          }
          elseif( util::isArrayTimestampDate( $fbMember )) { // timestamp value
            $freebusyPairMember       = util::timestamp2date( $fbMember[util::$LCTIMESTAMP], 7 );
            $freebusyPairMember[util::$LCtz] = util::$Z;
          }
          else {                                      // array format duration
            $freebusyPairMember = util::duration2arr( $fbMember );
          }
        }
        elseif(( 3 <= strlen( trim( $fbMember ))) &&  // string format duration
                        ( in_array( $fbMember{0}, $PREFIXARR ))) {
          $freebusyPairMember = util::durationStr2arr( $fbMember );
        }
        elseif( 8 <= strlen( trim( $fbMember ))) {    // text date ex. 2006-08-03 10:12:18
          $freebusyPairMember       = util::strDate2ArrayDate( $fbMember, 7 );
          unset( $freebusyPairMember[util::$UNPARSEDTEXT] );
          $freebusyPairMember[util::$LCtz] = util::$Z;
        }
        $freebusyPeriod[]   = $freebusyPairMember;
      }
      $input[]              = $freebusyPeriod;
    }
    util::setMval( $this->freebusy,
                   $input,
                   $params,
                   false,
                   $index );
    return true;
  }
}
