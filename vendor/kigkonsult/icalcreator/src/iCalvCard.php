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
 * iCalcreator vCard support class
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.20 - 2017-01-30
 */
class iCalvCard {
/**
 * Convert single ATTENDEE, CONTACT or ORGANIZER (in email format) to vCard
 *
 * Returns vCard/true or if directory (if set) or file write is unvalid, false
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.23.8 - 2017-04-17
 * @param string  $email
 * @param string  $version    vCard version (default 2.1)
 * @param string  $directory  where to save vCards (default false)
 * @param string  $ext        vCard file extension (default 'vcf')
 * @return mixed  bool true (if directory set and save ok), string (if not), false on error
 * @static
 */
  public static function iCal2vCard( $email, $version=null, $directory=null, $ext=null ) {
    static $UCMAILTOCOLON = 'MAILTO:';
    static $CRLF     = "\r\n";
    static $FMTFN    = "FN:%s\r\n";
    static $FMTN     = 'N:%s';
    static $V2_1     = '2.1';
    static $AT       = '@';
    static $V4_0     = '4.0';
    static $FMTEMAIL = "EMAIL:%s\r\n";
    static $BEGINVCARD = "BEGIN:VCARD\r\n";
    static $FMTVERSION = "VERSION:%s\r\n";
    static $FMTPRODID  = "PRODID:-//kigkonsult.se %s\r\n";
    static $FMTREV   =  "REV:%s\r\n";
    static $YMDTHISZ = 'Ymd\THis\Z';
    static $ENDVCARD = "END:VCARD\r\n";
    static $EXPR     = '/[^a-z0-9.]/i';
    static $FMTFNAME = '%s%s%s.%s';
    static $EXTVCF   = 'vcf';
    if( empty( $version ))
      $version = $V2_1;
    if( false === ( $pos = strpos( $email, $AT )))
      return false;
    if( $directory ) {
      if( DIRECTORY_SEPARATOR != substr( $directory, ( 0 - strlen( DIRECTORY_SEPARATOR ))))
        $directory .= DIRECTORY_SEPARATOR;
      if( ! is_dir( $directory ) || ! is_writable( $directory ))
        return false;
    }
            /* prepare vCard */
    $email  = str_replace( $UCMAILTOCOLON, null, $email );
    $name   = $person = substr( $email, 0, $pos );
    if( ctype_upper( $name ) || ctype_lower( $name ))
      $name = [$name];
    else {
      if( false !== ( $pos = strpos( $name, util::$DOT ))) {
        $name = explode( util::$DOT, $name );
        foreach( $name as $k => $part )
          $name[$k] = ucfirst( $part );
      }
      else { // split camelCase
        $chars = $name;
        $name  = [$chars[0]];
        $k     = 0;
        $len   = strlen( $chars );
        $x     = 1;
        while( $x < $len ) {
          if( ctype_upper( $chars[$x] )) {
            $k += 1;
            $name[$k] = null;
          }
          $name[$k]  .= $chars[$x];
          $x++;
        }
      }
    }
    $FN     = sprintf( $FMTFN, implode( utiL::$SP1, $name ));
    $name   = array_reverse( $name );
    $N      = sprintf( $FMTN, array_shift( $name ));
    $scCnt  = 0;
    while( NULL != ( $part = array_shift( $name ))) {
      if(( $V4_0 != $version ) || ( 4 > $scCnt ))
        $scCnt += 1;
      $N   .= util::$SEMIC . $part;
    }
    while(( $V4_0 == $version ) && ( 4 > $scCnt )) {
      $N   .= util::$SEMIC;
      $scCnt += 1;
    }
    $N     .= $CRLF;
    $EMAIL  = sprintf( $FMTEMAIL, $email );
           /* create vCard */
    $vCard  = $BEGINVCARD;
    $vCard .= sprintf( $FMTVERSION, $version );
    $vCard .= sprintf( $FMTPRODID, ICALCREATOR_VERSION );
    $vCard .= $N;
    $vCard .= $FN;
    $vCard .= $EMAIL;
    $vCard .= sprintf( $FMTREV, gmdate( $YMDTHISZ ));
    $vCard .= $ENDVCARD;
            /* save each vCard as (unique) single file */
    if( ! empty( $directory )) {
      if( empty( $ext ))
        $ext = $EXTVCF;
      $fprfx = $directory.preg_replace( $EXPR, null, $email );
      $cnt   = 1;
      $dbl   = null;
      $fName = sprintf( $FMTFNAME, $fprfx, $dbl, $ext );
      while( is_file ( $fName )) {
        $cnt += 1;
        $dbl = $cnt;
        $fName = sprintf( $FMTFNAME, $fprfx, $dbl, $ext );
      }
      if( false === file_put_contents( $fName, $vCard ))
        return false;
      return true;
    }
            /* return vCard */
    else
      return $vCard;
  }
/**
 * Convert ATTENDEEs, CONTACTs and ORGANIZERs (in email format) to vCards
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.23.20 - 2017-02-20
 * @param vcalendar $calendar   iCalcreator vcalendar instance
 * @param string    $version    vCard version (default 2.1)
 * @param string    $directory  where to save vCards (default false)
 * @param string    $ext        vCard file extension (default 'vcf')
 * @return mixed    bool true (if directory set and save ok), string (if not), false on error
 * @static
 */
  public static function iCal2vCards( vcalendar $calendar, $version=null, $directory=null, $ext=null ) {
    static $vCardP = ['ATTENDEE', 'CONTACT', 'ORGANIZER'];
    static $AT     = '@';
    static $UCMAILTOCOLON = 'MAILTO:';
    $hits   = [];
    foreach( $vCardP as $prop ) {
      $hits2 = $calendar->getProperty( $prop );
      foreach( $hits2 as $propValue => $occCnt ) {
        if( false === ( $pos = strpos( $propValue, $AT )))
          continue;
        $propValue = str_replace( $UCMAILTOCOLON, null, $propValue );
        if( isset( $hits[$propValue] ))
          $hits[$propValue] += $occCnt;
        else
          $hits[$propValue]  = $occCnt;
      }
    }
    if( empty( $hits ))
      return false;
    ksort( $hits );
    $output   = null;
    foreach( $hits as $email => $skip ) {
      $res = self::iCal2vCard( $email, $version, $directory, $ext );
      if( ! empty( $directory ) && ! $res )
        return false;
      elseif( ! $res )
        return $res;
      else
        $output .= $res;
    }
    if( $directory )
      return true;
    return ( empty( $output )) ? false : $output;
  }
}
