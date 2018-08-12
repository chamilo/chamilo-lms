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
/**
 * iCalcreator redirect support class
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.23.6 - 2017-04-13
 */
class utilRedirect {
/**
 * HTTP headers
 *
 * @var array $headers
 * @access private
 * @static
 */
  private static $headers = ['Content-Encoding: gzip',
                             'Vary: *',
                             'Content-Length: %s',
                             'Content-Type: text/calendar; charset=utf-8',
                             'Content-Disposition: attachment; filename="%s"',
                             'Content-Disposition: inline; filename="%s"',
                             'Cache-Control: max-age=10'];
/**
 * Return created, updated and/or parsed calendar, sending a HTTP redirect header.
 *
 * @param vcalendar $calendar
 * @param bool      $utf8Encode
 * @param bool      $gzip
 * @param bool      $cdType       true : Content-Disposition: attachment... (default), false : ...inline...
 * @return bool true on success, false on error
 * @static
 */
  public static function returnCalendar( vcalendar $calendar,
                                         $utf8Encode=false,
                                         $gzip=false,
                                         $cdType=true ) {
    static $ICR = 'iCr';
    $filename = $calendar->getConfig( util::$FILENAME );
    $output   = $calendar->createCalendar();
    if( $utf8Encode )
      $output = utf8_encode( $output );
    $fsize    = null;
    if( $gzip ) {
      $output = gzencode( $output, 9 );
      $fsize  = strlen( $output );
      header( self::$headers[0] );
      header( self::$headers[1] );
    }
    else {
      if( false !== ( $temp = tempnam( sys_get_temp_dir(), $ICR ))) {
        if( false !== file_put_contents( $temp, $output ))
          $fsize = @filesize( $temp );
        unlink( $temp );
      }
    }
    if( ! empty( $fsize ))
      header( sprintf( self::$headers[2], $fsize ));
    header( self::$headers[3] );
    $cdType = ( $cdType ) ? 4 : 5;
    header( sprintf( self::$headers[$cdType], $filename ));
    header( self::$headers[6] );
    echo $output;
    return true;
  }
/**
 * If recent version of calendar file exists (default one hour), an HTTP redirect header is sent
 *
 * @param vcalendar $calendar
 * @param int       $timeout  default 3600 sec
 * @param bool      $cdType   true : Content-Disposition: attachment... (default), false : ...inline...
 * @return bool true on success, false on error
 * @static
 */
  public static function useCachedCalendar( vcalendar $calendar,
                                            $timeout=3600,
                                            $cdType=true ) {
    static $R = 'r';
    if( false === ( $dirfile = $calendar->getConfig( util::$URL )))
      $dirfile = $calendar->getConfig( util::$DIRFILE );
    if( ! is_file( $dirfile ) || ! is_readable( $dirfile ))
      return false;
    if( time() - filemtime( $dirfile ) > $timeout )
      return false;
    clearstatcache();
    $fsize     = @filesize( $dirfile );
    $filename  = $calendar->getConfig( util::$FILENAME );
    header( self::$headers[3] );
    if( ! empty( $fsize ))
      header( sprintf( self::$headers[2], $fsize ));
    $cdType    = ( $cdType ) ? 4 : 5;
    header( sprintf( self::$headers[$cdType], $filename ));
    header( self::$headers[6] );
    if( false === ( $fp = @fopen( $dirfile, $R )))
      return false;
    fpassthru( $fp );
    fclose( $fp );
    return true;
  }
}
