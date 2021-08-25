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
 * iCalcreator utility/support class
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.11 - 2015-04-03
 */
class util {
/**
 *  @var string  iCal component (lowercase) names
 *  @static
 */
  public static $LCVTIMEZONE = 'vtimezone';
  public static $LCSTANDARD  = 'standard';
  public static $LCDAYLIGHT  = 'daylight';
  public static $LCVEVENT    = 'vevent';
  public static $LCVTODO     = 'vtodo';
  public static $LCVJOURNAL  = 'vjournal';
  public static $LCVFREEBUSY = 'vfreebusy';
  public static $LCVALARM    = 'valarm';
/**
 *  @var array  iCal component (lowercase) collections
 *  @static
 */
  public static $VCOMPS      = ['vevent', 'vtodo', 'vjournal', 'vfreebusy'];
  public static $MCOMPS      = ['vevent', 'vtodo', 'vjournal', 'vfreebusy', 'valarm', 'vtimezone'];
  public static $LCSUBCOMPS  = ['valarm', 'vtimezone', 'standard', 'daylight'];
  public static $TZCOMPS     = ['vtimezone', 'standard', 'daylight'];
  public static $ALLCOMPS    = ['vtimezone', 'standard', 'daylight', 'vevent', 'vtodo', 'vjournal', 'vfreebusy', 'valarm'];
/**
 *  @var string  iCal property names
 *  @static
 */
  public static $ACTION           = 'ACTION';
  public static $ATTACH           = 'ATTACH';
  public static $ATTENDEE         = 'ATTENDEE';
  public static $CALSCALE         = 'CALSCALE';
  public static $CATEGORIES       = 'CATEGORIES';
  public static $CLASS            = 'CLASS';
  public static $COMMENT          = 'COMMENT';
  public static $COMPLETED        = 'COMPLETED';
  public static $CONTACT          = 'CONTACT';
  public static $CREATED          = 'CREATED';
  public static $DESCRIPTION      = 'DESCRIPTION';
  public static $DTEND            = 'DTEND';
  public static $DTSTAMP          = 'DTSTAMP';
  public static $DTSTART          = 'DTSTART';
  public static $DUE              = 'DUE';
  public static $DURATION         = 'DURATION';
  public static $EXDATE           = 'EXDATE';
  public static $EXRULE           = 'EXRULE';
  public static $FREEBUSY         = 'FREEBUSY';
  public static $GEO              = 'GEO';
  public static $GEOLOCATION      = 'GEOLOCATION';
  public static $LAST_MODIFIED    = 'LAST-MODIFIED';
  public static $LOCATION         = 'LOCATION';
  public static $METHOD           = 'METHOD';
  public static $ORGANIZER        = 'ORGANIZER';
  public static $PERCENT_COMPLETE = 'PERCENT-COMPLETE';
  public static $PRIORITY         = 'PRIORITY';
  public static $PRODID           = 'PRODID';
  public static $RECURRENCE_ID    = 'RECURRENCE-ID';
  public static $RELATED_TO       = 'RELATED-TO';
  public static $REPEAT           = 'REPEAT';
  public static $REQUEST_STATUS   = 'REQUEST-STATUS';
  public static $RESOURCES        = 'RESOURCES';
  public static $RDATE            = 'RDATE';
  public static $RRULE            = 'RRULE';
  public static $SEQUENCE         = 'SEQUENCE';
  public static $STATUS           = 'STATUS';
  public static $SUMMARY          = 'SUMMARY';
  public static $TRANSP           = 'TRANSP';
  public static $TRIGGER          = 'TRIGGER';
  public static $TZID             = 'TZID';
  public static $TZNAME           = 'TZNAME';
  public static $TZOFFSETFROM     = 'TZOFFSETFROM';
  public static $TZOFFSETTO       = 'TZOFFSETTO';
  public static $TZURL            = 'TZURL';
  public static $UID              = 'UID';
  public static $URL              = 'URL';
  public static $VERSION          = 'VERSION';
  public static $X_PROP           = 'X-PROP';
/**
 *  @var string  vcalendar::selectComponents added x-property names
 *  @static
 */
  public static $X_CURRENT_DTSTART = 'X-CURRENT-DTSTART';
  public static $X_CURRENT_DTEND   = 'X-CURRENT-DTEND';
  public static $X_CURRENT_DUE     = 'X-CURRENT-DUE';
  public static $X_RECURRENCE      = 'X-RECURRENCE';
  public static $X_OCCURENCE       = 'X-OCCURENCE';
/**
 *  @var array  iCal component property collections
 *  @static
 */
  public static $PROPNAMES  = ['ACTION', 'ATTACH', 'ATTENDEE', 'CATEGORIES',
                               'CLASS', 'COMMENT', 'COMPLETED', 'CONTACT',
                               'CREATED', 'DESCRIPTION', 'DTEND', 'DTSTAMP',
                               'DTSTART', 'DUE', 'DURATION', 'EXDATE', 'EXRULE',
                               'FREEBUSY', 'GEO', 'LAST-MODIFIED', 'LOCATION',
                               'ORGANIZER', 'PERCENT-COMPLETE',  'PRIORITY',
                               'RECURRENCE-ID', 'RELATED-TO', 'REPEAT',
                               'REQUEST-STATUS', 'RESOURCES', 'RRULE', 'RDATE',
                               'SEQUENCE', 'STATUS', 'SUMMARY', 'TRANSP',
                               'TRIGGER',  'TZNAME', 'TZID', 'TZOFFSETFROM',
                               'TZOFFSETTO', 'TZURL', 'UID', 'URL', 'X-'];
  public static $DATEPROPS  = ['DTSTART', 'DTEND', 'DUE', 'CREATED', 'COMPLETED',
                               'DTSTAMP', 'LAST-MODIFIED', 'RECURRENCE-ID'];
  public static $OTHERPROPS = ['ATTENDEE', 'CATEGORIES', 'CONTACT', 'LOCATION',
                               'ORGANIZER', 'PRIORITY', 'RELATED-TO', 'RESOURCES',
                               'STATUS', 'SUMMARY', 'UID', 'URL'];
  public static $MPROPS1    = ['ATTENDEE', 'CATEGORIES', 'CONTACT',
                               'RELATED-TO', 'RESOURCES'];
  public static $MPROPS2    = ['ATTACH',   'ATTENDEE', 'CATEGORIES',
                               'COMMENT', 'CONTACT', 'DESCRIPTION',
                               'EXDATE', 'EXRULE', 'FREEBUSY', 'RDATE',
                               'RELATED-TO', 'RESOURCES', 'RRULE',
                               'REQUEST-STATUS', 'TZNAME', 'X-PROP'];
/**
 *  @var string  iCalcreator config keys
 *  @static
 */
  public static $ALLOWEMPTY   = 'ALLOWEMPTY';
  public static $COMPSINFO    = 'COMPSINFO';
  public static $DELIMITER    = 'DELIMITER';
  public static $DIRECTORY    = 'DIRECTORY';
  public static $FILENAME     = 'FILENAME';
  public static $DIRFILE      = 'DIRFILE';
  public static $FILESIZE     = 'FILESIZE';
  public static $FILEINFO     = 'FILEINFO';
  public static $LANGUAGE     = 'LANGUAGE';
  public static $PROPINFO     = 'PROPINFO';
  public static $SETPROPERTYNAMES = 'SETPROPERTYNAMES';
  public static $UNIQUE_ID    = 'UNIQUE_ID';
/**
 *  @var string  iCal date/time parameter key values
 *  @static
 */
  public static $DATE        = 'DATE';
  public static $PERIOD      = 'PERIOD';
  public static $DATE_TIME   = 'DATE-TIME';
  public static $DEFAULTVALUEDATETIME = ['VALUE' => 'DATE-TIME'];
  public static $T           = 'T';
  public static $Z           = 'Z';
  public static $UTC         = 'UTC';
  public static $GMT         = 'GMT';
  public static $LCYEAR      = 'year';
  public static $LCMONTH     = 'month';
  public static $LCDAY       = 'day';
  public static $LCHOUR      = 'hour';
  public static $LCMIN       = 'min';
  public static $LCSEC       = 'sec';
  public static $LCtz        = 'tz';
  public static $LCWEEK      = 'week';
  public static $LCTIMESTAMP = 'timestamp';
/**
 *  @var string  iCal ATTENDEE, ORGANIZER etc param keywords
 *  @static
 */
  public static $CUTYPE          = 'CUTYPE';
  public static $MEMBER          = 'MEMBER';
  public static $ROLE            = 'ROLE';
  public static $PARTSTAT        = 'PARTSTAT';
  public static $RSVP            = 'RSVP';
  public static $DELEGATED_TO    = 'DELEGATED-TO';
  public static $DELEGATED_FROM  = 'DELEGATED-FROM';
  public static $SENT_BY         = 'SENT-BY';
  public static $CN              = 'CN';
  public static $DIR             = 'DIR';
  public static $INDIVIDUAL      = 'INDIVIDUAL';
  public static $NEEDS_ACTION    = 'NEEDS-ACTION';
  public static $REQ_PARTICIPANT = 'REQ-PARTICIPANT';
  public static $false           = 'false';
/**
 *  @var array  iCal ATTENDEE, ORGANIZER etc param collections
 *  @static
 */
  public static $ATTENDEEPARKEYS    = ['DELEGATED-FROM', 'DELEGATED-TO', 'MEMBER'];
  public static $ATTENDEEPARALLKEYS = ['CUTYPE', 'MEMBER', 'ROLE', 'PARTSTAT',
                                       'RSVP', 'DELEGATED-TO', 'DELEGATED-FROM',
                                       'SENT-BY', 'CN', 'DIR', 'LANGUAGE'];
/**
 *  @var string  iCal RRULE, EXRULE etc param keywords
 *  @static
 */
  public static $FREQ        = 'FREQ';
  public static $UNTIL       = 'UNTIL';
  public static $COUNT       = 'COUNT';
  public static $INTERVAL    = 'INTERVAL';
  public static $WKST        = 'WKST';
  public static $BYMONTHDAY  = 'BYMONTHDAY';
  public static $BYYEARDAY   = 'BYYEARDAY';
  public static $BYWEEKNO    = 'BYWEEKNO';
  public static $BYMONTH     = 'BYMONTH';
  public static $BYSETPOS    = 'BYSETPOS';
  public static $BYDAY       = 'BYDAY';
  public static $DAY         = 'DAY';
/**
 *  @var string  misc. values
 *  @static
 */
  public static $ALTREP        = 'ALTREP';
  public static $ALTRPLANGARR  = ['ALTREP', 'LANGUAGE'];
  public static $VALUE         = 'VALUE';
  public static $BINARY        = 'BINARY';
  public static $LCvalue       = 'value';
  public static $LCparams      = 'params';
  public static $UNPARSEDTEXT  = 'unparsedtext';
  public static $SERVER_NAME   = 'SERVER_NAME';
  public static $LOCALHOST     = 'localhost';
  public static $EMPTYPROPERTY = '';
  public static $FMTBEGIN      = "BEGIN:%s\r\n";
  public static $FMTEND        = "END:%s\r\n";
  public static $CRLF          = "\r\n";
  public static $COMMA         = ',';
  public static $COLON         = ':';
  public static $QQ            = '"';
  public static $SEMIC         = ';';
  public static $MINUS         = '-';
  public static $PLUS          = '+';
  public static $SP1           = ' ';
  public static $ZERO          = '0';
  public static $DOT           = '.';
  public static $L             = '/';
  public static $YMDHISE       = '%04d-%02d-%02d %02d:%02d:%02d %s';
  public static $YMD           = '%04d%02d%02d';
  public static $HIS           = '%02d%02d%02d';
/**
 *  @var string  util date/datetime formats
 *  @access private
 *  @static
 */
  private static $YMDHIS3 = 'Y-m-d-H-i-s';
/**
 * Initiates configuration, set defaults
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.23 - 2017-03-11
 * @param array $config
 * @return array
 * @static
 */
  public static function initConfig( $config ) {
    $config        = array_change_key_case( $config, CASE_UPPER );
    if( ! isset( $config[self::$ALLOWEMPTY] ))
      $config[self::$ALLOWEMPTY] = true;
    if( ! isset( $config[self::$DELIMITER] ))
      $config[self::$DELIMITER]  = DIRECTORY_SEPARATOR;
    if( ! isset( $config[self::$DIRECTORY] ))
      $config[self::$DIRECTORY]  = self::$DOT;
    return $config;
  }
/**
 * Return formatted output for calendar component property
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.20 - 2017-01-30
 * @param string $label       property name
 * @param string $attributes  property attributes
 * @param string $content     property content
 * @return string
 * @static
 */
  public static function createElement( $label, $attributes=null, $content=null ) {
    $output    = strtoupper( $label );
    if( ! empty( $attributes ))
      $output .= trim( $attributes );
    $output   .= util::$COLON . $content;
    return self::size75( $output );
  }
/**
 * Return formatted output for calendar component property parameters
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.23 - 2017-01-29
 * @param array  $params
 * @param array  $ctrKeys
 * @param string $lang
 * @return string
 * @static
 */
  public static function createParams( $params=null, $ctrKeys=null, $lang=null ) {
    static $FMTFMTTYPE = ';FMTTYPE=%s%s';
    static $FMTKEQV    = '%s=%s';
    static $ENCODING   = 'ENCODING';
    static $FMTTYPE    = 'FMTTYPE';
    static $RANGE      = 'RANGE';
    static $RELTYPE    = 'RELTYPE';
    static $PARAMSARRAY = null;
    if( is_null( $PARAMSARRAY ))
      $PARAMSARRAY  = [self::$ALTREP,
                       self::$CN,
                       self::$DIR,
                       $ENCODING,
                       $FMTTYPE,
                       self::$LANGUAGE,
                       $RANGE,
                       $RELTYPE,
                       self::$SENT_BY,
                       self::$TZID,
                       self::$VALUE];
    static $FMTQ    = '"%s"';
    static $FMTQTD  = ';%s=%s%s%s';
    static $FMTCMN  = ';%s=%s';
    if( ! is_array( $params ))
      $params       = [];
    if( ! is_array( $ctrKeys ) || empty( $ctrKeys ))
      $ctrKeys      = [];
    if( empty( $params ) && empty( $ctrKeys ))
      return null;
    $attrLANG = $attr1 = $attr2 = null;
    $hasCNattrKey   = ( in_array( self::$CN,       $ctrKeys ));
    $hasLANGattrKey = ( in_array( self::$LANGUAGE, $ctrKeys ));
    $CNattrExist    = false;
    $xparams        = [];
    $params         = array_change_key_case( $params, CASE_UPPER );
    foreach( $params as $paramKey => $paramValue ) {
      if(( false !== strpos( $paramValue, self::$COLON )) ||
         ( false !== strpos( $paramValue, self::$SEMIC )) ||
         ( false !== strpos( $paramValue, self::$COMMA )))
        $paramValue = sprintf( $FMTQ, $paramValue );
      if( ctype_digit( (string) $paramKey )) {
        $xparams[]  = $paramValue;
        continue;
      }
      if( ! in_array( $paramKey, $PARAMSARRAY ))
        $xparams[$paramKey] = $paramValue;
      else
        $params[$paramKey]  = $paramValue;
    }
    ksort( $xparams, SORT_STRING );
    foreach( $xparams as $paramKey => $paramValue ) {
      $attr2       .= util::$SEMIC;
      $attr2       .= ( ctype_digit( (string) $paramKey ))
                    ? $paramValue
                    : sprintf( $FMTKEQV, $paramKey, $paramValue );
    }
    if( isset( $params[$FMTTYPE] ) &&
           ! in_array( $FMTTYPE, $ctrKeys )) {
      $attr1       .= sprintf( $FMTFMTTYPE, $params[$FMTTYPE],
                                            $attr2 );
      $attr2        = null;
    }
    if( isset( $params[$ENCODING] ) &&
           ! in_array( $ENCODING,   $ctrKeys )) {
      if( !empty( $attr2 )) {
        $attr1     .= $attr2;
        $attr2      = null;
      }
      $attr1       .= sprintf( $FMTCMN, $ENCODING,
                                        $params[$ENCODING] );
    }
    if( isset( $params[self::$VALUE] ) &&
           ! in_array( self::$VALUE,   $ctrKeys ))
      $attr1       .= sprintf( $FMTCMN, self::$VALUE,
                                        $params[self::$VALUE] );
    if( isset( $params[self::$TZID] ) &&
           ! in_array( self::$TZID,    $ctrKeys )) {
      $attr1       .= sprintf( $FMTCMN, self::$TZID,
                                        $params[self::$TZID] );
    }
    if( isset( $params[$RANGE] ) &&
           ! in_array( $RANGE,   $ctrKeys ))
      $attr1       .= sprintf( $FMTCMN, $RANGE,
                                        $params[$RANGE] );
    if( isset( $params[$RELTYPE] ) &&
           ! in_array( $RELTYPE, $ctrKeys ))
      $attr1       .= sprintf( $FMTCMN, $RELTYPE,
                                        $params[$RELTYPE] );
    if( isset( $params[self::$CN] ) &&
       $hasCNattrKey ) {
      $attr1        = sprintf( $FMTCMN, self::$CN,
                                        $params[self::$CN] );
      $CNattrExist  = true;
    }
    if( isset( $params[self::$DIR] ) &&
             in_array( self::$DIR, $ctrKeys )) {
      $delim        = ( false !== strpos( $params[self::$DIR], self::$QQ ))
                    ? null : self::$QQ;
      $attr1       .= sprintf( $FMTQTD, self::$DIR,
                                        $delim,
                                        $params[self::$DIR],
                                        $delim );
    }
    if( isset( $params[self::$SENT_BY] ) &&
             in_array( self::$SENT_BY,  $ctrKeys ))
      $attr1       .= sprintf( $FMTCMN, self::$SENT_BY,
                                        $params[self::$SENT_BY] );
    if( isset( $params[self::$ALTREP] ) &&
             in_array( self::$ALTREP, $ctrKeys )) {
      $delim        = ( false !== strpos( $params[self::$ALTREP], self::$QQ ))
                    ? null : self::$QQ;
      $attr1       .= sprintf( $FMTQTD, self::$ALTREP,
                                        $delim,
                                        $params[self::$ALTREP],
                                        $delim );
    }
    if( isset( $params[self::$LANGUAGE] ) && $hasLANGattrKey )
      $attrLANG    .= sprintf( $FMTCMN, self::$LANGUAGE,
                                        $params[self::$LANGUAGE] );
    elseif(( $CNattrExist || $hasLANGattrKey ) && ! empty( $lang ))
      $attrLANG    .= sprintf( $FMTCMN, self::$LANGUAGE,
                                        $lang );
    return $attr1 . $attrLANG . $attr2;
  }
/**
 * Return (conformed) iCal component property parameters
 *
 * Trim quoted values, default parameters may be set, if missing
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.23 - 2017-04-08
 * @param array $params
 * @param array $defaults
 * @return array
 * @static
 */
  public static function setParams( $params, $defaults=null ) {
    if( ! is_array( $params ))
      $params = [];
    $output   = [];
    $params   = array_change_key_case( $params, CASE_UPPER );
    foreach( $params as $paramKey => $paramValue ) {
      if( is_array( $paramValue )) {
        foreach( $paramValue as $pkey => $pValue )
          $paramValue[$pkey]  = trim( $pValue, util::$QQ );
      }
      else
        $paramValue = trim( $paramValue, util::$QQ );
      if( self::$VALUE == $paramKey )
        $output[self::$VALUE] = strtoupper( $paramValue );
      else
        $output[$paramKey] = $paramValue;
    } // end foreach
    if( is_array( $defaults ))
      $output = array_merge( array_change_key_case( $defaults, CASE_UPPER ),
                             $output );
    return ( 0 < count( $output )) ? $output : null;
  }
/**
 * Remove expected key/value from array and returns hitval (if found) else returns elseval
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.4.16 - 2008-11-08
 * @param array  $array    iCal property parameters
 * @param string $expkey   expected key
 * @param string $expval   expected value
 * @param int    $hitVal   return value if found
 * @param int    $elseVal  return value if not found
 * @param int    $preSet   return value if already preset
 * @return int
 * @static
 */
  public static function existRem( & $array,
                                     $expkey,
                                     $expval=false,
                                     $hitVal=null,
                                     $elseVal=null,
                                     $preSet=null ) {
    if( $preSet )
      return $preSet;
    if( ( 0 == count( $array )) || ! is_array( $array ))
      return $elseVal;
    foreach( $array as $key => $value ) {
      if( 0 == strcasecmp( $expkey, $key )) {
        if( ! $expval ||
          ( 0 == strcasecmp( $expval, $value ))) {
          unset( $array[$key] );
          return $hitVal;
        }
      }
    }
    return $elseVal;
  }
/**
 * Delete component property value, managing components with multiple occurencies
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.8.8 - 2011-03-15
 * @param array  $multiprop  component (multi-)property
 * @param int    $propix     removal counter
 * @return bool true
 * @static
 */
  public static function deletePropertyM( & $multiprop, & $propix ) {
    if( isset( $multiprop[$propix] ))
      unset( $multiprop[$propix] );
    if( empty( $multiprop )) {
      $multiprop = null;
      unset( $propix );
      return false;
    }
    return true;
  }
/**
 * Recount property propix, used at consecutive getProperty calls
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.23.8 - 2017-04-18
 * @param array  $prop     component (multi-)property
 * @param int    $propix   getter counter
 * @return bool true
 * @static
 */
  public static function recountMvalPropix( & $prop, & $propix ) {
    if( ! is_array( $prop ) || empty( $prop ))
      return false;
    $last = key( array_slice( $prop, -1, 1, TRUE ));
    while( ! isset( $prop[$propix] ) &&
                ( $last > $propix  ))
      $propix++;
    return true;
  }
/**
 * Check index and set (an indexed) content in a multiple value array
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.23 - 2017-04-08
 * @param array $valArr
 * @param mixed $value
 * @param array $params
 * @param array $defaults
 * @param int $index
 * @static
 */
  public static function setMval( & $valArr,
                                    $value,
                                    $params=null,
                                    $defaults=null,
                                    $index=null ) {
    if( ! is_array( $valArr ))
      $valArr = [];
    if( ! is_null( $params ))
      $params = self::setParams( $params, $defaults );
    if( is_null( $index )) { // i.e. next
      $valArr[] = [self::$LCvalue  => $value,
                   self::$LCparams => $params];
      return;
    }
    $index    = $index - 1;
    if( isset( $valArr[$index] )) { // replace
      $valArr[$index] = [self::$LCvalue  => $value,
                         self::$LCparams => $params];
      return;
    }
    $valArr[$index] = [self::$LCvalue  => $value,
                       self::$LCparams => $params];
    ksort( $valArr ); // order
    return true;
  }
/**
 * Return datestamp for calendar component object instance dtstamp
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.23 - 2017-02-17
 * @return array
 * @static
 */
  public static function makeDtstamp() {
    $date = explode( self::$MINUS,  gmdate( self::$YMDHIS3, time()));
    return [self::$LCvalue  => [self::$LCYEAR  => $date[0],
                                self::$LCMONTH => $date[1],
                                self::$LCDAY   => $date[2],
                                self::$LCHOUR  => $date[3],
                                self::$LCMIN   => $date[4],
                                self::$LCSEC   => $date[5],
                                self::$LCtz    => self::$Z],
            self::$LCparams => null];
  }
/**
 * Return an unique id for a calendar component object instance
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.23 - 2017-02-17
 * @param string $unique_id
 * @return array
 * @static
 */
  public static function makeUid( $unique_id ) {
    static $FMT     = '%s-%s@%s';
    static $TMDTHIS = 'Ymd\THisT';
    return [self::$LCvalue  => sprintf( $FMT, date( $TMDTHIS ),
                                              substr( microtime(), 2, 4) . self::getRandChars( 6 ),
                                              $unique_id ),
            self::$LCparams => null];
  }
/**
 * Return a random (and unique) sequence of characters
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.23 - 2017-02-18
 * @param int $cnt
 * @return string
 * @access private
 * @static
 */
  private static function getRandChars( $cnt ) {
    $cnt  = (int) floor( $cnt / 2 );
    $x    = 0;
    do {
      $randChars = bin2hex( openssl_random_pseudo_bytes( $cnt, $cStrong ));
      $x += 1;
    } while(( 3 > $x ) && ( false == $cStrong ));
    return $randChars;
  }
/**
 * Return true if a date property has NO date parts
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.23 - 2017-02-17
 * @param array  $content
 * @return bool
 * @static
 */
  public static function hasNodate( $content ) {
    return( ! isset( $content[self::$LCvalue][self::$LCYEAR] )  &&
            ! isset( $content[self::$LCvalue][self::$LCMONTH] ) &&
            ! isset( $content[self::$LCvalue][self::$LCDAY] )   &&
            ! isset( $content[self::$LCvalue][self::$LCHOUR] )  &&
            ! isset( $content[self::$LCvalue][self::$LCMIN] )   &&
            ! isset( $content[self::$LCvalue][self::$LCSEC] ));
  }
/**
 * Return true if property parameter VALUE is set to argument, otherwise false
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.23 - 2017-02-12
 * @param array  $content
 * @param string $arg
 * @return bool
 * @static
 */
  public static function isParamsValueSet( array $content, $arg ) {
    return (   isset( $content[self::$LCparams][self::$VALUE] ) &&
            ( $arg == $content[self::$LCparams][self::$VALUE] ));
  }
/**
 * Return bool true if name is X-prefixed
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.23 - 2017-02-17
 * @param string $name
 * @return bool
 * @static
 */
  public static function isXprefixed( $name ) {
    static $X_ = 'X-';
    return ( 0 == strcasecmp( $X_, substr( $name, 0, 2 )));
  }
/**
 * Return bool true if object class is a DateTime (sub-)class
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.23.5 - 2017-04-14
 * @param object $object
 * @return bool
 * @static
 */
  public static function isDateTimeClass( $object ) {
    static $DATETIMEobj     = 'DateTime';
    return ( is_object( $object ) &&
           ( 0 == strcasecmp( $DATETIMEobj, substr( get_class( $object ), -8 ))));
  }
/**
 * Return property name  and  opt.params and property value
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.23.8 - 2017-04-16
 * @param string $row
 * @return string
 * @static
 */
  public static function getPropName( $row ) {
    static $COLONSEMICARR = [':', ';'];
    $propName = null;
    $cix      = 0;
    $len      = strlen( $row );
    while( $cix < $len ) {
      if( in_array( $row[$cix], $COLONSEMICARR ))
        break;
      $propName .= $row[$cix];
      $cix++;
    } // end while...
    if( isset( $row[$cix] ))
      $row = substr( $row, $cix);
    else {
      $propName = self::trimTrailNL( $propName ); // property without colon and content
      $row      = null;
    }
    return [$propName, $row];
  }
/**
 * Return array from content split by '\,'
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.23.8 - 2017-04-16
 * @param string $content
 * @return array
 * @static
 */
  public static function commaSplit( $content ) {
    static $DBBS = "\\";
    $output      = [0 => null];
    $cix = $lix  = 0;
    $len         = strlen( $content );
    while( $lix < $len ) {
      if(( self::$COMMA  ==  $content[$lix] ) &&
                ( $DBBS  !=  $content[( $lix - 1 )]))
        $output[++$cix]   = null;
      else
        $output[$cix] .= $content[$lix];
      $lix++;
    }
    return array_filter( $output );
  }
/**
 * Return concatenated calendar rows, one row for each property
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.23 - 2017-02-17
 * @param array $rows
 * @return array
 * @static
 */
  public static function concatRows( $rows ) {
    $output = [];
    $cnt    = count( $rows );
    for( $i = 0; $i < $cnt; $i++ ) {
      $line = rtrim( $rows[$i], self::$CRLF );
      while(  isset( $rows[$i+1] ) &&
           !  empty( $rows[$i+1] ) &&
           ( self::$SP1 == $rows[$i+1]{0} ))
        $line .= rtrim( substr( $rows[++$i], 1 ), self::$CRLF );
      $output[] = $line;
    }
    return $output;
  }
/**
 * Return string with removed ical line folding
 *
 * Remove any line-endings that may include spaces or tabs
 * and convert all line endings (iCal default '\r\n'),
 * takes care of '\r\n', '\r' and '\n' and mixed '\r\n'+'\r', '\r\n'+'\n'
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.23 - 2017-03-01
 * @param string $text
 * @return string
 * @static
 */
  public static function convEolChar( & $text ) {
    static $BASEDELIM  = null;
    static $BASEDELIMs = null;
    static $EMPTYROW   = null;
    static $FMT        = '%1$s%2$75s%1$s';
    static $SP0        = '';
    static $CRLFs      = ["\r\n", "\n\r", "\n", "\r"];
    static $CRLFexts   = ["\r\n ", "\n\r\t"];
            /* fix dummy line separator etc */
    if( empty( $BASEDELIM )) {
      $BASEDELIM  = self::getRandChars( 16 );
      $BASEDELIMs = $BASEDELIM . $BASEDELIM;
      $EMPTYROW   = sprintf( $FMT, $BASEDELIM, $SP0 );
    }
            /* fix eol chars */
    $text = str_replace( $CRLFs, $BASEDELIM, $text );
            /* fix empty lines */
    $text = str_replace( $BASEDELIMs, $EMPTYROW, $text );
            /* fix line folding */
    $text = str_replace( $BASEDELIM, util::$CRLF, $text );
    $text = str_replace( $CRLFexts, null, $text );
            /* split in component/property lines */
    return explode( util::$CRLF, $text );
  }
/**
 * Return wrapped string with (byte oriented) line breaks at pos 75
 *
 * Lines of text SHOULD NOT be longer than 75 octets, excluding the line
 * break. Long content lines SHOULD be split into a multiple line
 * representations using a line "folding" technique. That is, a long
 * line can be split between any two characters by inserting a CRLF
 * immediately followed by a single linear white space character (i.e.,
 * SPACE, US-ASCII decimal 32 or HTAB, US-ASCII decimal 9). Any sequence
 * of CRLF followed immediately by a single linear white space character
 * is ignored (i.e., removed) when processing the content type.
 *
 * Edited 2007-08-26 by Anders Litzell, anders@litzell.se to fix bug where
 * the reserved expression "\n" in the arg $string could be broken up by the
 * folding of lines, causing ambiguity in the return string.
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.23 - 2017-03-01
 * @param string $string
 * @return string
 * @access private
 * @static
 * @link http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
 */
  private static function size75( $string ) {
    static $DBS      = '\\';
    static $LCN      = 'n';
    static $UCN      = 'N';
    static $SPBSLCN  = ' \n';
    static $SP1      = ' ';
    $tmp             = $string;
    $string          = null;
    $cCnt = $x       = 0;
    while( true ) {
      if( ! isset( $tmp[$x] )) {
        $string     .= util::$CRLF;        // loop breakes here
        break;
      }
      elseif(( 74    <= $cCnt ) &&
             ( $DBS  == $tmp[$x] ) &&
             (( $LCN == $tmp[$x+1] ) || ( $UCN == $tmp[$x+1] ))) {
        $string     .= util::$CRLF . $SPBSLCN; // don't break lines inside '\n'
        $x          += 2;
        if( ! isset( $tmp[$x] )) {
          $string   .= util::$CRLF;
          break;
        }
        $cCnt        = 3;
      }
      elseif( 75    <= $cCnt ) {
        $string     .= util::$CRLF . $SP1;
        $cCnt        = 1;
      }
      $byte          = ord( $tmp[$x] );
      $string       .= $tmp[$x];
      switch( true ) {
        case(( $byte >= 0x20 ) && ( $byte <= 0x7F )) :
          $cCnt     += 1;                 // characters U-00000000 - U-0000007F (same as ASCII)
          break;                          // add a one byte character
        case(( $byte & 0xE0) == 0xC0 ) :  // characters U-00000080 - U-000007FF, mask 110XXXXX
          if( isset( $tmp[$x+1] )) {
            $cCnt   += 1;
            $string .= $tmp[$x+1];
            $x      += 1;                 // add a two bytes character
          }
          break;
        case(( $byte & 0xF0 ) == 0xE0 ) : // characters U-00000800 - U-0000FFFF, mask 1110XXXX
          if( isset( $tmp[$x+2] )) {
            $cCnt   += 1;
            $string .= $tmp[$x+1] . $tmp[$x+2];
            $x      += 2;                 // add a three bytes character
          }
          break;
        case(( $byte & 0xF8 ) == 0xF0 ) : // characters U-00010000 - U-001FFFFF, mask 11110XXX
          if( isset( $tmp[$x+3] )) {
            $cCnt   += 1;
            $string .= $tmp[$x+1] . $tmp[$x+2] . $tmp[$x+3];
            $x      += 3;                 // add a four bytes character
          }
          break;
        case(( $byte & 0xFC ) == 0xF8 ) : // characters U-00200000 - U-03FFFFFF, mask 111110XX
          if( isset( $tmp[$x+4] )) {
            $cCnt   += 1;
            $string .= $tmp[$x+1] . $tmp[$x+2] . $tmp[$x+3] . $tmp[$x+4];
            $x      += 4;                 // add a five bytes character
          }
          break;
        case(( $byte & 0xFE ) == 0xFC ) : // characters U-04000000 - U-7FFFFFFF, mask 1111110X
          if( isset( $tmp[$x+5] )) {
            $cCnt   += 1;
            $string .= $tmp[$x+1] . $tmp[$x+2] . $tmp[$x+3] . $tmp[$x+4] . $tmp[$x+5];
            $x      += 5;                 // add a six bytes character
          }
          break;
        default:                          // add any other byte without counting up $cCnt
          break;
      } // end switch( true )
      $x            += 1;                 // next 'byte' to test
    } // end while( true )
    return $string;
  }
/**
 * Separate (string) to iCal property value and attributes
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.23.13 - 2017-05-02
 * @param string $line      property content
 * @param array  $propAttr  property parameters
 * @static
 * @TODO same as in util::calAddressCheck() ??
 */
  public static function splitContent( & $line, & $propAttr=null ) {
    static $CSS    = '://';
    static $MSTZ   = ['utc-', 'utc+', 'gmt-', 'gmt+'];
    static $PROTO3 = ['fax:', 'cid:', 'sms:', 'tel:', 'urn:'];
    static $PROTO4 = ['crid:', 'news:', 'pres:'];
    static $PROTO6 = ['mailto:'];
    static $EQ     = '=';
    $attr         = [];
    $attrix       = -1;
    $clen         = strlen( $line );
    $WithinQuotes = false;
    $len          = strlen( $line );
    $cix          = 0;
    while( $cix < $len ) {
      if(  ! $WithinQuotes  &&   ( self::$COLON == $line[$cix] )            &&
                                 ( substr( $line,$cix,  3 )   != $CSS )     &&
         ( ! in_array( strtolower( substr( $line,$cix - 6, 4 )), $MSTZ ))   &&
         ( ! in_array( strtolower( substr( $line,$cix - 3, 4 )), $PROTO3 )) &&
         ( ! in_array( strtolower( substr( $line,$cix - 4, 5 )), $PROTO4 )) &&
         ( ! in_array( strtolower( substr( $line,$cix - 6, 7 )), $PROTO6 ))) {
        $attrEnd = true;
        if(( $cix < ( $clen - 4 )) &&
             ctype_digit( substr( $line, $cix+1, 4 ))) { // an URI with a (4pos) portnr??
          for( $c2ix = $cix; 3 < $c2ix; $c2ix-- ) {
            if( $CSS == substr( $line, $c2ix - 2, 3 )) {
              $attrEnd = false;
              break; // an URI with a portnr!!
            }
          }
        }
        if( $attrEnd) {
          $line = substr( $line, ( $cix + 1 ));
          break;
        }
        $cix++;
      } // end if(  ! $WithinQuotes...
      if( self::$QQ    == $line[$cix] ) // '"'
        $WithinQuotes = ! $WithinQuotes;
      if( self::$SEMIC == $line[$cix] ) // ';'
        $attr[++$attrix] = null;
      else {
        if( 0 > $attrix )
          $attrix = 0;
        $attr[$attrix] .= $line[$cix];
      }
      $cix++;
    } // end while...
            /* make attributes in array format */
    $propAttr = [];
    foreach( $attr as $attribute ) {
      $attrsplit = explode( $EQ, $attribute, 2 );
      if( 1 < count( $attrsplit ))
        $propAttr[$attrsplit[0]] = $attrsplit[1];
    }
  }
/**
 * Special characters management output
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.23.8 - 2017-04-17
 * @param string $string
 * @return string
 * @static
 */
  public static function strrep( $string ) {
    static $BSLCN    = '\n';
    static $SPECCHAR = ['n', 'N', 'r', ',', ';'];
    static $DBS      = "\\";
    static $SQ       = "'";
    static $BSCOMMA  = '\,';
    static $BSSEMIC  = '\;';
    static $BSLCR    = "\r";
    static $QBSLCN   = "\n";
    static $BSUCN    = '\N';
    $string = (string) $string;
    $strLen = strlen( $string );
    $pos = 0;
    while( $pos < $strLen ) {
      if( false === ( $pos = strpos( $string, $DBS, $pos )))
        break;
      if( ! in_array( substr( $string, $pos, 1 ), $SPECCHAR )) {
        $string = substr( $string, 0, $pos ) . $DBS . substr( $string, ( $pos + 1 ));
        $pos += 1;
      }
      $pos += 1;
    }
    if( false !== strpos( $string, self::$QQ ))
      $string   = str_replace( self::$QQ,    $SQ,      $string);
    if( false !== strpos( $string, self::$COMMA ))
      $string   = str_replace( self::$COMMA, $BSCOMMA, $string);
    if( false !== strpos( $string, self::$SEMIC ))
      $string   = str_replace( self::$SEMIC, $BSSEMIC, $string);
    if( false !== strpos( $string, self::$CRLF ))
      $string   = str_replace( self::$CRLF,  $BSLCN,   $string);
    elseif( false !== strpos( $string, $BSLCR ))
      $string   = str_replace( $BSLCR,       $BSLCN,   $string);
    elseif( false !== strpos( $string, $QBSLCN ))
      $string   = str_replace( $QBSLCN,      $BSLCN,   $string);
    if( false !== strpos( $string, $BSUCN ))
      $string   = str_replace( $BSUCN,       $BSLCN,   $string);
    $string     = str_replace( self::$CRLF,  $BSLCN,   $string);
    return $string;
  }
/**
 * Special characters management input
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.2 - 2015-06-25
 * @param string $string
 * @return string
 * @static
 */
  public static function strunrep( $string ) {
    static $BS4     = '\\\\';
    static $BS2     = '\\';
    static $BSCOMMA = '\,';
    static $BSSEMIC = '\;';
    $string = str_replace( $BS4,     $BS2,         $string);
    $string = str_replace( $BSCOMMA, self::$COMMA, $string);
    $string = str_replace( $BSSEMIC, self::$SEMIC, $string);
    return $string;
  }
/**
 * Return string with trimmed trailing \n
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.23 - 2017-02-17
 * @param string $value
 * @return string
 * @static
 */
  public static function trimTrailNL( $value ) {
    static $NL = '\n';
    if( $NL == strtolower( substr( $value, -2 )))
      $value = substr( $value, 0, ( strlen( $value ) -2 ));
    return $value;
  }
/**
 * Return internal date (format) with parameters based on input date
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.11 - 2015-03-21
 * @param mixed  $year
 * @param mixed  $month
 * @param int    $day
 * @param int    $hour
 * @param int    $min
 * @param int    $sec
 * @param string $tz
 * @param array  $params
 * @param string $caller
 * @param string $objName
 * @param string $tzid
 * @return array
 * @static
 */
  public static function setDate( $year,
                                  $month=null,
                                  $day=null,
                                  $hour=null,
                                  $min=null,
                                  $sec=null,
                                  $tz=null,
                                  $params=null,
                                  $caller=null,
                                  $objName=null,
                                  $tzid=null ) {
    $input = $parno = null;
    $localtime = (( self::$DTSTART == $caller ) &&
                  in_array( $objName, self::$TZCOMPS )) ? true : false;
    self::strDate2arr( $year );
    if( self::isArrayDate( $year )) {
      $input[self::$LCvalue]  = self::chkDateArr( $year );
      if( 100 > $input[self::$LCvalue][self::$LCYEAR] )
        $input[self::$LCvalue][self::$LCYEAR] += 2000;
      if( $localtime )
        unset( $month[self::$VALUE], $month[self::$TZID] );
      elseif( ! isset( $month[self::$TZID] ) && isset( $tzid ))
        $month[self::$TZID] = $tzid;
      if(         isset( $input[self::$LCvalue][self::$LCtz] ) &&
        self::isOffset( $input[self::$LCvalue][self::$LCtz] ))
        unset( $month[self::$TZID] );
      elseif(   ! isset( $input[self::$LCvalue][self::$LCtz] ) &&
                  isset( $month[self::$TZID] ) &&
        self::isOffset( $month[self::$TZID] )) {
        $input[self::$LCvalue][self::$LCtz] = $month[self::$TZID];
        unset( $month[self::$TZID] );
      }
      $input[self::$LCparams] = self::setParams( $month,
                                                 self::$DEFAULTVALUEDATETIME );
      $hitval       = ( isset( $input[self::$LCvalue][self::$LCtz] )) ? 7 : 6;
      $parno        = self::existRem( $input[self::$LCparams],
                                      self::$VALUE,
                                      self::$DATE_TIME,
                                      $hitval );
      $parno        = self::existRem( $input[self::$LCparams],
                                      self::$VALUE,
                                      self::$DATE,
                                      3,
                                      count( $input[self::$LCvalue] ),
                                      $parno );
      if( 6 > $parno )
        unset( $input[self::$LCvalue][self::$LCtz],
               $input[self::$LCparams][self::$TZID],
               $tzid );
      if(( 6 <= $parno ) &&
            isset( $input[self::$LCvalue][self::$LCtz] ) &&
          ( self::$Z != $input[self::$LCvalue][self::$LCtz] ) &&
          self::isOffset( $input[self::$LCvalue][self::$LCtz] )) {
        $input[self::$LCvalue] = self::strDate2ArrayDate( sprintf( self::$YMDHISE,
                                                                   (int) $input[self::$LCvalue][self::$LCYEAR],
                                                                   (int) $input[self::$LCvalue][self::$LCMONTH],
                                                                   (int) $input[self::$LCvalue][self::$LCDAY],
                                                                   (int) $input[self::$LCvalue][self::$LCHOUR],
                                                                   (int) $input[self::$LCvalue][self::$LCMIN],
                                                                   (int) $input[self::$LCvalue][self::$LCSEC],
                                                                         $input[self::$LCvalue][self::$LCtz] ),
                                                          $parno );
        unset( $input[self::$LCvalue][self::$UNPARSEDTEXT],
               $input[self::$LCparams][self::$TZID] );
      }
      if(          isset( $input[self::$LCvalue][self::$LCtz] ) &&
        ! self::isOffset( $input[self::$LCvalue][self::$LCtz] )) {
        $input[self::$LCparams][self::$TZID] = $input[self::$LCvalue][self::$LCtz];
        unset( $input[self::$LCvalue][self::$LCtz] );
      }
    } // end if( self::isArrayDate( $year ))
    elseif( self::isArrayTimestampDate( $year )) {
      if( $localtime )
        unset( $month[self::$LCvalue], $month[self::$TZID] );
      $input[self::$LCparams] = self::setParams( $month,
                                                 self::$DEFAULTVALUEDATETIME );
      $parno        = self::existRem( $input[self::$LCparams],
                                      self::$VALUE,
                                      self::$DATE,
                                      3 );
      $hitval       = 7;
      $parno        = self::existRem( $input[self::$LCparams],
                                      self::$VALUE,
                                      self::$DATE_TIME,
                                      $hitval,
                                      $parno );
      if( isset( $year[self::$LCtz] ) && ! empty( $year[self::$LCtz] )) {
        if( !self::isOffset( $year[self::$LCtz] )) {
          $input[self::$LCparams][self::$TZID] = $year[self::$LCtz];
          unset( $year[self::$LCtz], $tzid );
        }
        else {
          if( isset( $input[self::$LCparams][self::$TZID] ) &&
            ! empty( $input[self::$LCparams][self::$TZID] )) {
            if( !self::isOffset( $input[self::$LCparams][self::$TZID] ))
              unset( $tzid );
            else
              unset( $input[self::$LCparams][self::$TZID]);
          }
          elseif( isset( $tzid ) && ! self::isOffset( $tzid ))
            $input[self::$LCparams][self::$TZID] = $tzid;
        }
      }
      elseif( isset( $input[self::$LCparams][self::$TZID] ) &&
            ! empty( $input[self::$LCparams][self::$TZID] )) {
        if( self::isOffset( $input[self::$LCparams][self::$TZID] )) {
          $year[self::$LCtz] = $input[self::$LCparams][self::$TZID];
          unset( $input[self::$LCparams][self::$TZID]);
          if( isset( $tzid ) &&
            ! empty( $tzid ) &&
            ! self::isOffset( $tzid ))
            $input[self::$LCparams][self::$TZID] = $tzid;
        }
      }
      elseif( isset( $tzid ) && ! empty( $tzid )) {
        if( self::isOffset( $tzid )) {
          $year[self::$LCtz] = $tzid;
          unset( $input[self::$LCparams][self::$TZID]);
        }
        else
          $input[self::$LCparams][self::$TZID] = $tzid;
      }
      $input[self::$LCvalue]  = self::timestamp2date( $year, $parno );
    } // end elseif( self::isArrayTimestampDate( $year ))
    elseif( 8 <= strlen( trim((string) $year ))) { // string ex. "2006-08-03 10:12:18 [[[+/-]1234[56]] / timezone]"
      if( $localtime )
        unset( $month[self::$LCvalue], $month[self::$TZID] );
      elseif( ! isset( $month[self::$TZID] ) && ! empty( $tzid ))
        $month[self::$TZID] = $tzid;
      $input[self::$LCparams] = self::setParams( $month,
                                                 self::$DEFAULTVALUEDATETIME );
      $parno        = self::existRem( $input[self::$LCparams],
                                      self::$VALUE,
                                      self::$DATE_TIME,
                                      7,
                                      $parno );
      $parno        = self::existRem( $input[self::$LCparams],
                                      self::$VALUE,
                                      self::$DATE,
                                      3,
                                      $parno,
                                      $parno );
      $input[self::$LCvalue]  = self::strDate2ArrayDate( $year, $parno );
      if( 3 == $parno )
        unset( $input[self::$LCvalue][self::$LCtz],
               $input[self::$LCparams][self::$TZID] );
      unset( $input[self::$LCvalue][self::$UNPARSEDTEXT] );
      if( isset( $input[self::$LCvalue][self::$LCtz] )) {
        if( self::isOffset( $input[self::$LCvalue][self::$LCtz] )) {
          $input[self::$LCvalue] = self::strDate2ArrayDate( sprintf( self::$YMDHISE,
                                                                     (int) $input[self::$LCvalue][self::$LCYEAR],
                                                                     (int) $input[self::$LCvalue][self::$LCMONTH],
                                                                     (int) $input[self::$LCvalue][self::$LCDAY],
                                                                     (int) $input[self::$LCvalue][self::$LCHOUR],
                                                                     (int) $input[self::$LCvalue][self::$LCMIN],
                                                                     (int) $input[self::$LCvalue][self::$LCSEC],
                                                                           $input[self::$LCvalue][self::$LCtz] ),
                                                            7 );
          unset( $input[self::$LCvalue][self::$UNPARSEDTEXT],
                 $input[self::$LCparams][self::$TZID] );
        }
        else {
          $input[self::$LCparams][self::$TZID] = $input[self::$LCvalue][self::$LCtz];
          unset( $input[self::$LCvalue][self::$LCtz] );
        }
      }
      elseif(    isset( $input[self::$LCparams][self::$TZID] ) &&
        self::isOffset( $input[self::$LCparams][self::$TZID] )) {
        $input[self::$LCvalue] = self::strDate2ArrayDate( sprintf( self::$YMDHISE,
                                                                   (int) $input[self::$LCvalue][self::$LCYEAR],
                                                                   (int) $input[self::$LCvalue][self::$LCMONTH],
                                                                   (int) $input[self::$LCvalue][self::$LCDAY],
                                                                   (int) $input[self::$LCvalue][self::$LCHOUR],
                                                                   (int) $input[self::$LCvalue][self::$LCMIN],
                                                                   (int) $input[self::$LCvalue][self::$LCSEC],
                                                                         $input[self::$LCparams][self::$TZID] ),
                                                          7 );
        unset( $input[self::$LCvalue][self::$UNPARSEDTEXT],
               $input[self::$LCparams][self::$TZID] );
      }
    } // end elseif( 8 <= strlen( trim((string) $year )))
    else { // using all (?) args
      if( 100 > $year )
        $year += 2000;
      if( is_array( $params ))
        $input[self::$LCparams] = self::setParams( $params,
                                                   self::$DEFAULTVALUEDATETIME );
      elseif( is_array( $tz )) {
        $input[self::$LCparams] = self::setParams( $tz,
                                                   self::$DEFAULTVALUEDATETIME );
        $tz = false;
      }
      elseif( is_array( $hour )) {
        $input[self::$LCparams] = self::setParams( $hour,
                                                   self::$DEFAULTVALUEDATETIME );
        $hour = $min = $sec = $tz = false;
      }
      if( $localtime )
        unset ( $input[self::$LCparams][self::$LCvalue],
                $input[self::$LCparams][self::$TZID] );
      elseif( ! isset( $tz ) &&
              ! isset( $input[self::$LCparams][self::$TZID] ) &&
              ! empty( $tzid ))
        $input[self::$LCparams][self::$TZID] = $tzid;
      elseif( isset( $tz ) && self::isOffset( $tz ))
        unset( $input[self::$LCparams][self::$TZID] );
      elseif(     isset( $input[self::$LCparams][self::$TZID] ) &&
        self::isOffset( $input[self::$LCparams][self::$TZID] )) {
        $tz         = $input[self::$LCparams][self::$TZID];
        unset( $input[self::$LCparams][self::$TZID] );
      }
      $parno        = self::existRem( $input[self::$LCparams],
                                      self::$VALUE,
                                      self::$DATE,
                                      3 );
      $hitval       = ( self::isOffset( $tz )) ? 7 : 6;
      $parno        = self::existRem( $input[self::$LCparams],
                                      self::$VALUE,
                                      self::$DATE_TIME,
                                      $hitval,
                                      $parno,
                                      $parno );
      $input[self::$LCvalue]  = [self::$LCYEAR  => $year,
                                 self::$LCMONTH => $month,
                                 self::$LCDAY   => $day];
      if( 3 != $parno ) {
        $input[self::$LCvalue][self::$LCHOUR] = ( $hour ) ? $hour : '0';
        $input[self::$LCvalue][self::$LCMIN]  = ( $min )  ? $min  : '0';
        $input[self::$LCvalue][self::$LCSEC]  = ( $sec )  ? $sec  : '0';
        if( ! empty( $tz ))
          $input[self::$LCvalue][self::$LCtz] = $tz;
        $strdate       = self::date2strdate( $input[self::$LCvalue], $parno );
        if( ! empty( $tz ) && !self::isOffset( $tz ))
          $strdate    .= ( self::$Z == $tz ) ? $tz : ' '.$tz;
        $input[self::$LCvalue] = self::strDate2ArrayDate( $strdate, $parno );
        unset( $input[self::$LCvalue][self::$UNPARSEDTEXT] );
        if( isset( $input[self::$LCvalue][self::$LCtz] )) {
          if( self::isOffset( $input[self::$LCvalue][self::$LCtz] )) {
            $input[self::$LCvalue] = self::strDate2ArrayDate( sprintf( self::$YMDHISE,
                                                                       (int) $input[self::$LCvalue][self::$LCYEAR],
                                                                       (int) $input[self::$LCvalue][self::$LCMONTH],
                                                                       (int) $input[self::$LCvalue][self::$LCDAY],
                                                                       (int) $input[self::$LCvalue][self::$LCHOUR],
                                                                       (int) $input[self::$LCvalue][self::$LCMIN],
                                                                       (int) $input[self::$LCvalue][self::$LCSEC],
                                                                             $input[self::$LCvalue][self::$LCtz] ),
                                                              7 );
            unset( $input[self::$LCvalue][self::$UNPARSEDTEXT],
                   $input[self::$LCparams][self::$TZID] );
          }
          else {
            $input[self::$LCparams][self::$TZID] = $input[self::$LCvalue][self::$LCtz];
            unset( $input[self::$LCvalue][self::$LCtz] );
          }
        }
        elseif( isset( $input[self::$LCparams][self::$TZID] ) &&
          self::isOffset( $input[self::$LCparams][self::$TZID] )) {
          $input[self::$LCvalue] = self::strDate2ArrayDate( sprintf( self::$YMDHISE,
                                                                     (int) $input[self::$LCvalue][self::$LCYEAR],
                                                                     (int) $input[self::$LCvalue][self::$LCMONTH],
                                                                     (int) $input[self::$LCvalue][self::$LCDAY],
                                                                     (int) $input[self::$LCvalue][self::$LCHOUR],
                                                                     (int) $input[self::$LCvalue][self::$LCMIN],
                                                                     (int) $input[self::$LCvalue][self::$LCSEC],
                                                                           $input[self::$LCparams][self::$TZID] ),
                                                            7 );
          unset( $input[self::$LCvalue][self::$UNPARSEDTEXT],
                 $input[self::$LCparams][self::$TZID] );
        }
      }
    } // end else (i.e. using all arguments)
    if(( 3 == $parno ) || self::isParamsValueSet( $input, self::$DATE )) {
      $input[self::$LCparams][self::$VALUE] = self::$DATE;
      unset( $input[self::$LCvalue][self::$LCHOUR],
             $input[self::$LCvalue][self::$LCMIN],
             $input[self::$LCvalue][self::$LCSEC],
             $input[self::$LCvalue][self::$LCtz],
             $input[self::$LCparams][self::$TZID] );
    }
    elseif( isset( $input[self::$LCparams][self::$TZID] )) {
      if(( 0 == strcasecmp( self::$UTC, $input[self::$LCparams][self::$TZID] )) ||
         ( 0 == strcasecmp( self::$GMT, $input[self::$LCparams][self::$TZID] ))) {
        $input[self::$LCvalue][self::$LCtz] = self::$Z;
        unset( $input[self::$LCparams][self::$TZID] );
      }
      else
        unset( $input[self::$LCvalue][self::$LCtz] );
    }
    elseif( isset( $input[self::$LCvalue][self::$LCtz] )) {
      if(( 0 == strcasecmp( self::$UTC, $input[self::$LCvalue][self::$LCtz] )) ||
         ( 0 == strcasecmp( self::$GMT, $input[self::$LCvalue][self::$LCtz] )))
        $input[self::$LCvalue][self::$LCtz] = self::$Z;
      if( self::$Z != $input[self::$LCvalue][self::$LCtz] ) {
        $input[self::$LCparams][self::$TZID] = $input[self::$LCvalue][self::$LCtz];
        unset( $input[self::$LCvalue][self::$LCtz] );
      }
      else
        unset( $input[self::$LCparams][self::$TZID] );
    }
    if( $localtime )
      unset( $input[self::$LCvalue][self::$LCtz], $input[self::$LCparams][self::$TZID] );
    return $input;
  }
/**
 * Return input (UTC) date to internal date with parameters
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.23 - 2017-02-17
 * @param mixed $year
 * @param mixed $month
 * @param int   $day
 * @param int   $hour
 * @param int   $min
 * @param int   $sec
 * @param array $params
 * @return array
 * @static
 */
  public static function setDate2( $year,
                                   $month=null,
                                   $day=null,
                                   $hour=null,
                                   $min=null,
                                   $sec=null,
                                   $params=null ) {
    $input = null;
    self::strDate2arr( $year );
    if( self::isArrayDate( $year )) {
      $input[self::$LCvalue]  = self::chkDateArr( $year, 7 );
      if( isset( $input[self::$LCvalue][self::$LCYEAR] ) &&
         ( 100 > $input[self::$LCvalue][self::$LCYEAR] ))
        $input[self::$LCvalue][self::$LCYEAR] += 2000;
      $input[self::$LCparams] = self::setParams( $month,
                                                 self::$DEFAULTVALUEDATETIME );
      if( isset( $input[self::$LCvalue][self::$LCtz] ) &&
          self::isOffset( $input[self::$LCvalue][self::$LCtz] ))
        $tzid = $input[self::$LCvalue][self::$LCtz];
      elseif( isset( $input[self::$LCparams][self::$TZID] ) &&
        self::isOffset( $input[self::$LCparams][self::$TZID] ))
        $tzid = $input[self::$LCparams][self::$TZID];
      else
        $tzid = null;
      if( ! empty( $tzid ) && ( self::$Z != $tzid ) && self::isOffset( $tzid )) {
        $input[self::$LCvalue] = self::strDate2ArrayDate( sprintf( self::$YMDHISE,
                                                                   (int) $input[self::$LCvalue][self::$LCYEAR],
                                                                   (int) $input[self::$LCvalue][self::$LCMONTH],
                                                                   (int) $input[self::$LCvalue][self::$LCDAY],
                                                                   (int) $input[self::$LCvalue][self::$LCHOUR],
                                                                   (int) $input[self::$LCvalue][self::$LCMIN],
                                                                   (int) $input[self::$LCvalue][self::$LCSEC],
                                                                         $tzid ),
                                                           7 );
        unset( $input[self::$LCvalue][self::$UNPARSEDTEXT] );
      }
    } // end if( self::isArrayDate( $year ))
    elseif( self::isArrayTimestampDate( $year )) {
      if( isset( $year[self::$LCtz] ) &&
         ! self::isOffset( $year[self::$LCtz] ))
        $year[self::$LCtz]    = self::$UTC;
      elseif( isset( $input[self::$LCparams][self::$TZID] ) &&
        self::isOffset( $input[self::$LCparams][self::$TZID] ))
        $year[self::$LCtz]    = $input[self::$LCparams][self::$TZID];
      else
        $year[self::$LCtz]    = self::$UTC;
      $input[self::$LCvalue]  = self::timestamp2date( $year, 7 );
      $input[self::$LCparams] = self::setParams( $month,
                                                 self::$DEFAULTVALUEDATETIME );
    } // end elseif( self::isArrayTimestampDate( $year ))
    elseif( 8 <= strlen( trim((string) $year ))) { // ex. 2006-08-03 10:12:18
      $input[self::$LCvalue]  = self::strDate2ArrayDate( $year, 7 );
      unset( $input[self::$LCvalue][self::$UNPARSEDTEXT] );
      $input[self::$LCparams] = self::setParams( $month,
                                                 self::$DEFAULTVALUEDATETIME );
      if(( ! isset( $input[self::$LCvalue][self::$LCtz] ) ||
             empty( $input[self::$LCvalue][self::$LCtz] )) &&
         isset( $input[self::$LCparams][self::$TZID] ) &&
         self::isOffset( $input[self::$LCparams][self::$TZID] )) {
        $input[self::$LCvalue] = self::strDate2ArrayDate( sprintf( self::$YMDHISE,
                                                                   (int) $input[self::$LCvalue][self::$LCYEAR],
                                                                   (int) $input[self::$LCvalue][self::$LCMONTH],
                                                                   (int) $input[self::$LCvalue][self::$LCDAY],
                                                                   (int) $input[self::$LCvalue][self::$LCHOUR],
                                                                   (int) $input[self::$LCvalue][self::$LCMIN],
                                                                   (int) $input[self::$LCvalue][self::$LCSEC],
                                                                         $input[self::$LCparams][self::$TZID] ),
                                                           7 );
        unset( $input[self::$LCvalue][self::$UNPARSEDTEXT] );
      }
    } // end elseif( 8 <= strlen( trim((string) $year )))
    else {
      if( 100 > $year )
        $year += 2000;
      $input[self::$LCvalue]  = [self::$LCYEAR  => $year,
                                 self::$LCMONTH => $month,
                                 self::$LCDAY   => $day,
                                 self::$LCHOUR  => $hour,
                                 self::$LCMIN   => $min,
                                 self::$LCSEC   => $sec];
      if(  isset( $tz ))
        $input[self::$LCvalue][self::$LCtz] = $tz;
      if(( isset( $tz ) && self::isOffset( $tz )) ||
         ( isset( $input[self::$LCparams][self::$TZID] ) &&
           self::isOffset( $input[self::$LCparams][self::$TZID] ))) {
          if( ! isset( $tz ) &&
            isset( $input[self::$LCparams][self::$TZID] ) &&
            self::isOffset( $input[self::$LCparams][self::$TZID] ))
            $input[self::$LCvalue][self::$LCtz] = $input[self::$LCparams][self::$TZID];
          unset( $input[self::$LCparams][self::$TZID] );
        $strdate        = self::date2strdate( $input[self::$LCvalue], 7 );
        $input[self::$LCvalue] = self::strDate2ArrayDate( $strdate, 7 );
        unset( $input[self::$LCvalue][self::$UNPARSEDTEXT] );
      }
      $input[self::$LCparams] = self::setParams( $params,
                                                 self::$DEFAULTVALUEDATETIME );
    } // end else
    unset( $input[self::$LCparams][self::$VALUE], $input[self::$LCparams][self::$TZID]  );
    if( ! isset( $input[self::$LCvalue][self::$LCHOUR] ))
      $input[self::$LCvalue][self::$LCHOUR] = 0;
    if( ! isset( $input[self::$LCvalue][self::$LCMIN] ))
      $input[self::$LCvalue][self::$LCMIN]  = 0;
    if( ! isset( $input[self::$LCvalue][self::$LCSEC] ))
      $input[self::$LCvalue][self::$LCSEC]  = 0;
    $input[self::$LCvalue][self::$LCtz] = self::$Z;
    return $input;
  }
/**
 * Return array (in internal format) for an input date-time/date array (keyed or unkeyed)
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.23 - 2017-02-19
 * @param array $datetime
 * @param int $parno  default null, 3: DATE(Ymd), 6: YmdHis, 7: YmdHis + offset/timezone
 * @return array
 * @static
 */
  public static function chkDateArr( $datetime, $parno=null ) {
    static $PLUS4ZERO  = '+0000';
    static $MINUS4ZERO = '-0000';
    static $PLUS6ZERO  = '+000000';
    static $MINUS6ZERO = '-000000';
    $output = [];
    if(( is_null( $parno ) || ( 6 <= $parno )) &&
         isset( $datetime[3] ) &&
       ! isset( $datetime[4] )) { // Y-m-d with tz
      $temp        = $datetime[3];
      $datetime[3] = $datetime[4] = $datetime[5] = 0;
      $datetime[6] = $temp;
    }
    foreach( $datetime as $dateKey => $datePart ) {
      switch ( $dateKey ) {
        case '0':
        case self::$LCYEAR :
          $output[self::$LCYEAR]  = $datePart;
          break;
        case '1':
        case self::$LCMONTH :
          $output[self::$LCMONTH] = $datePart;
          break;
        case '2':
        case self::$LCDAY :
          $output[self::$LCDAY]   = $datePart;
          break;
      }
      if( 3 != $parno ) {
        switch ( $dateKey ) {
          case '0':
          case '1':
          case '2':
            break;
          case '3':
          case self::$LCHOUR:
            $output[self::$LCHOUR]  = $datePart;
            break;
          case '4':
          case self::$LCMIN :
            $output[self::$LCMIN]   = $datePart;
            break;
          case '5':
          case self::$LCSEC :
            $output[self::$LCSEC]   = $datePart;
            break;
          case '6':
          case self::$LCtz  :
            $output[self::$LCtz]    = $datePart;
            break;
        }
      }
    }
    if( 3 != $parno ) {
      if( ! isset( $output[self::$LCHOUR] ))
        $output[self::$LCHOUR] = 0;
      if( ! isset( $output[self::$LCMIN]  ))
        $output[self::$LCMIN]  = 0;
      if( ! isset( $output[self::$LCSEC]  ))
        $output[self::$LCSEC]  = 0;
      if( isset( $output[self::$LCtz] ) &&
        (( $PLUS4ZERO  == $output[self::$LCtz] ) ||
         ( $MINUS4ZERO == $output[self::$LCtz] ) ||
         ( $PLUS6ZERO  == $output[self::$LCtz] ) ||
         ( $MINUS6ZERO == $output[self::$LCtz] )))
        $output[self::$LCtz]   = self::$Z;
    }
    return $output;
  }
/**
 * Return iCal formatted string for (internal array) date/date-time
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.23 - 2017-02-24
 * @param array   $datetime
 * @param int     $parno     default 6
 * @return string
 * @static
 */
  public static function date2strdate( $datetime, $parno=null ) {
    static $SECONDS = ' seconds';
    static $YMDYHIS = 'Ymd\THis';
    if( ! isset( $datetime[self::$LCYEAR] )  &&
        ! isset( $datetime[self::$LCMONTH] ) &&
        ! isset( $datetime[self::$LCDAY] )   &&
        ! isset( $datetime[self::$LCHOUR] )  &&
        ! isset( $datetime[self::$LCMIN] )   &&
        ! isset( $datetime[self::$LCSEC] ))
      return null;
    if( is_null( $parno ))
      $parno    = 6;
    $output     = null;
    foreach( $datetime as $dkey => & $dvalue ) {
      if( self::$LCtz != $dkey )
        $dvalue = (int) $dvalue;
    }
    $output     = sprintf( self::$YMD, $datetime[self::$LCYEAR],
                                       $datetime[self::$LCMONTH],
                                       $datetime[self::$LCDAY] );
    if( 3 == $parno )
      return $output;
    if( ! isset( $datetime[self::$LCHOUR] ))
      $datetime[self::$LCHOUR] = 0;
    if( ! isset( $datetime[self::$LCMIN] ))
      $datetime[self::$LCMIN]  = 0;
    if( ! isset( $datetime[self::$LCSEC] ))
      $datetime[self::$LCSEC]  = 0;
    $output    .= self::$T . sprintf( self::$HIS, $datetime[self::$LCHOUR],
                                                  $datetime[self::$LCMIN],
                                                  $datetime[self::$LCSEC] );
    if( isset( $datetime[self::$LCtz] )) {
      $datetime[self::$LCtz] = trim( $datetime[self::$LCtz] );
      if( ! empty( $datetime[self::$LCtz] )) {
        if( self::$Z  == $datetime[self::$LCtz] )
          $parno  = 7;
        elseif( self::isOffset( $datetime[self::$LCtz] )) {
          $parno  = 7;
          $offset = self::tz2offset( $datetime[self::$LCtz] );
          try {
            $timezone = new \DateTimeZone( self::$UTC );
            $d        = new \DateTime( $output, $timezone );
            if( 0 != $offset ) // adjust för offset
              $d->modify( $offset . $SECONDS );
            $output = $d->format( $YMDYHIS );
          }
          catch( \Exception $e ) {
            $output = date( $YMDYHIS, mktime( $datetime[self::$LCHOUR],
                                              $datetime[self::$LCMIN],
                                            ( $datetime[self::$LCSEC] - $offset ),
                                              $datetime[self::$LCMONTH],
                                              $datetime[self::$LCDAY],
                                              $datetime[self::$LCYEAR] ));
          }
        }
        if( 7 == $parno )
          $output .= self::$Z;
      } // end if( ! empty( $datetime[self::$LCtz] ))
    }
    return $output;
  }
/**
 * Return array (in internal format) for a (array) duration
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.19.4 - 2014-03-14
 * @param array $duration
 * @return array
 * @static
 */
  public static function duration2arr( $duration ) {
    $seconds        = 0;
    foreach( $duration as $durKey => $durValue ) {
      if( empty( $durValue )) continue;
      switch ( $durKey ) {
        case '0': case self::$LCWEEK:
          $seconds += (((int) $durValue ) * 60 * 60 * 24 * 7 );
          break;
        case '1': case self::$LCDAY:
          $seconds += (((int) $durValue ) * 60 * 60 * 24 );
          break;
        case '2': case self::$LCHOUR:
          $seconds += (((int) $durValue ) * 60 * 60 );
          break;
        case '3': case self::$LCMIN:
          $seconds += (((int) $durValue ) * 60 );
          break;
        case '4': case self::$LCSEC:
          $seconds +=   (int) $durValue;
          break;
      }
    }
    $output         = [];
    $output[self::$LCWEEK] = (int) floor( $seconds / ( 60 * 60 * 24 * 7 ));
    if(( 0 < $output[self::$LCWEEK] ) &&
       ( 0 == ( $seconds % ( 60 * 60 * 24 * 7 ))))
      return $output;
    unset( $output[self::$LCWEEK] );
    $output[self::$LCDAY]  = (int) floor( $seconds / ( 60 * 60 * 24 ));
    $seconds        =            ( $seconds % ( 60 * 60 * 24 ));
    $output[self::$LCHOUR] = (int) floor( $seconds / ( 60 * 60 ));
    $seconds        =            ( $seconds % ( 60 * 60 ));
    $output[self::$LCMIN]  = (int) floor( $seconds /   60 );
    $output[self::$LCSEC]  =            ( $seconds %   60 );
    if( empty( $output[self::$LCDAY] ))
      unset( $output[self::$LCDAY] );
    if(( 0 == $output[self::$LCHOUR] ) &&
       ( 0 == $output[self::$LCMIN] ) &&
       ( 0 == $output[self::$LCSEC] ))
      unset(  $output[self::$LCHOUR],
              $output[self::$LCMIN],
              $output[self::$LCSEC] );
    return $output;
  }
/**
 * Return datetime array (in internal format) for startdate + duration
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.11 - 2015-03-21
 * @param array   $startdate
 * @param array   $duration
 * @return array, date format
 * @static
 */
  public static function duration2date( $startdate, $duration ) {
    $dateOnly          = ( isset( $startdate[self::$LCHOUR] ) ||
                           isset( $startdate[self::$LCMIN] ) ||
                           isset( $startdate[self::$LCSEC] )) ? false : true;
    $startdate[self::$LCHOUR] = ( isset( $startdate[self::$LCHOUR] ))
                              ? $startdate[self::$LCHOUR] : 0;
    $startdate[self::$LCMIN]  = ( isset( $startdate[self::$LCMIN] ))
                              ? $startdate[self::$LCMIN]  : 0;
    $startdate[self::$LCSEC]  = ( isset( $startdate[self::$LCSEC] ))
                              ? $startdate[self::$LCSEC]  : 0;
    $dtend = 0;
    if(    isset( $duration[self::$LCWEEK] ))
      $dtend += ( $duration[self::$LCWEEK] * 7 * 24 * 60 * 60 );
    if(    isset( $duration[self::$LCDAY] ))
      $dtend += ( $duration[self::$LCDAY] * 24 * 60 * 60 );
    if(    isset( $duration[self::$LCHOUR] ))
      $dtend += ( $duration[self::$LCHOUR] * 60 *60 );
    if(    isset( $duration[self::$LCMIN] ))
      $dtend += ( $duration[self::$LCMIN] * 60 );
    if(    isset( $duration[self::$LCSEC] ))
      $dtend +=   $duration[self::$LCSEC];
    $date     = date( self::$YMDHIS3,
                      mktime((int) $startdate[self::$LCHOUR],
                             (int) $startdate[self::$LCMIN],
                             (int) ( $startdate[self::$LCSEC] + $dtend ),
                             (int) $startdate[self::$LCMONTH],
                             (int) $startdate[self::$LCDAY],
                             (int) $startdate[self::$LCYEAR] ));
    $d        = explode( self::$MINUS, $date );
    $dtend2   = [self::$LCYEAR  => $d[0],
                 self::$LCMONTH => $d[1],
                 self::$LCDAY   => $d[2],
                 self::$LCHOUR  => $d[3],
                 self::$LCMIN   => $d[4],
                 self::$LCSEC   => $d[5]];
    if( isset( $startdate[self::$LCtz] ))
      $dtend2[self::$LCtz]   = $startdate[self::$LCtz];
    if( $dateOnly &&
       (( 0 == $dtend2[self::$LCHOUR] ) &&
        ( 0 == $dtend2[self::$LCMIN] ) &&
        ( 0 == $dtend2[self::$LCSEC] )))
      unset( $dtend2[self::$LCHOUR],
             $dtend2[self::$LCMIN],
             $dtend2[self::$LCSEC] );
    return $dtend2;
  }
/**
 * Return an iCal formatted string from (internal array) duration
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.15.8 - 2012-10-30
 * @param array $duration, array( week, day, hour, min, sec )
 * @return string
 * @static
 */
  public static function duration2str( array $duration ) {
    static $P  = 'P';
    static $W  = 'W';
    static $D  = 'D';
    static $H  = 'H';
    static $OH = '0H';
    static $M  = 'M';
    static $OM = '0M';
    static $S  = 'S';
    static $OS = '0S';
    static $PT0H0M0S = 'PT0H0M0S';
    if( isset( $duration[self::$LCWEEK] ) ||
        isset( $duration[self::$LCDAY] )  ||
        isset( $duration[self::$LCHOUR] ) ||
        isset( $duration[self::$LCMIN] )  ||
        isset( $duration[self::$LCSEC] ))
       $ok = true;
    else
      return null;
    if( isset( $duration[self::$LCWEEK] ) &&
         ( 0 < $duration[self::$LCWEEK] ))
      return $P . $duration[self::$LCWEEK] . $W;
    $output = $P;
    if( isset($duration[self::$LCDAY] ) &&
        ( 0 < $duration[self::$LCDAY] ))
      $output .= $duration[self::$LCDAY] . $D;
    if(( isset( $duration[self::$LCHOUR]) &&
          ( 0 < $duration[self::$LCHOUR] )) ||
       ( isset( $duration[self::$LCMIN])  &&
          ( 0 < $duration[self::$LCMIN] ))  ||
       ( isset( $duration[self::$LCSEC])  &&
          ( 0 < $duration[self::$LCSEC] ))) {
      $output .= self::$T;
      $output .= ( isset( $duration[self::$LCHOUR]) &&
                    ( 0 < $duration[self::$LCHOUR] ))
               ? $duration[self::$LCHOUR] . $H : $OH;
      $output .= ( isset( $duration[self::$LCMIN])  &&
                    ( 0 < $duration[self::$LCMIN] ))
               ? $duration[self::$LCMIN]  . $M : $OM;
      $output .= ( isset( $duration[self::$LCSEC])  &&
                    ( 0 < $duration[self::$LCSEC] ))
               ? $duration[self::$LCSEC]  . $S : $OS;
    }
    if( $P == $output )
      $output = $PT0H0M0S;
    return $output;
  }
/**
 * Return array (in internal format) from string duration
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.23.8 - 2017-04-17
 * @param array $duration
 * @return array|bool  false on error
 * @static
 */
  public static function durationStr2arr( $duration ) {
    static $P  = 'P';
    static $Tt = ['t', 'T'];
    static $W  = 'W';
    static $D  = 'D';
    static $H  = 'H';
    static $M  = 'M';
    static $S  = 'S';
    $duration  = (string) trim( $duration );
    while( 0 != strcasecmp( $P, $duration[0] )) {
      if( 0 < strlen( $duration ))
        $duration = substr( $duration, 1 );
      else
        return false; // no leading P !?!?
    }
    $duration = substr( $duration, 1 ); // skip P
    $duration = str_replace( $Tt, null, $duration );
    $output = [];
    $val    = null;
    $durLen = strlen( $duration );
    for( $ix=0; $ix < $durLen; $ix++ ) {
      switch( strtoupper( $duration[$ix] )) {
       case $W :
         $output[self::$LCWEEK] = $val;
         $val    = null;
         break;
       case $D :
         $output[self::$LCDAY]  = $val;
         $val    = null;
         break;
       case $H :
         $output[self::$LCHOUR] = $val;
         $val    = null;
         break;
       case $M :
         $output[self::$LCMIN]  = $val;
         $val    = null;
         break;
       case $S :
         $output[self::$LCSEC]  = $val;
         $val    = null;
         break;
       default:
         if( ! ctype_digit( $duration[$ix] ))
           return false; // unknown duration control character  !?!?
         else
           $val .= $duration[$ix];
      }
    }
    return self::duration2arr( $output );
  }
/**
 * Return bool true if input contains a date/time (in array format)
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.24 - 2013-07-02
 * @param array $input
 * @return bool
 * @static
 */
  public static function isArrayDate( $input ) {
    if( ! is_array( $input ) ||
             isset( $input[self::$LCWEEK] ) ||
             isset( $input[self::$LCTIMESTAMP] ) ||
       ( 3 > count( $input )))
      return false;
    if( 7 == count( $input ))
      return true;
    if( isset( $input[self::$LCYEAR] ) &&
        isset( $input[self::$LCMONTH] ) &&
        isset( $input[self::$LCDAY] ))
      return checkdate( (int) $input[self::$LCMONTH],
                        (int) $input[self::$LCDAY],
                        (int) $input[self::$LCYEAR] );
    if( isset( $input[self::$LCDAY] )  ||
        isset( $input[self::$LCHOUR] ) ||
        isset( $input[self::$LCMIN] )  ||
        isset( $input[self::$LCSEC] ))
      return false;
    if(( 0 == $input[0] ) ||
       ( 0 == $input[1] ) ||
       ( 0 == $input[2] ))
      return false;
    if(( 1970 > $input[0] ) ||
         ( 12 < $input[1] ) ||
         ( 31 < $input[2] ))
      return false;
    if(( isset( $input[0] ) &&
         isset( $input[1] ) &&
         isset( $input[2] )) &&
         checkdate((int) $input[1],
                   (int) $input[2],
                   (int) $input[0] ))
      return true;
    $input = self::strDate2ArrayDate( $input[1] .
                                      self::$L .
                                      $input[2] .
                                      self::$L .
                                      $input[0], 3 ); //  m - d - Y
    if( isset( $input[self::$LCYEAR] ) &&
        isset( $input[self::$LCMONTH] ) &&
        isset( $input[self::$LCDAY] ))
      return checkdate( (int) $input[self::$LCMONTH],
                        (int) $input[self::$LCDAY],
                        (int) $input[self::$LCYEAR] );
    return false;
  }
/**
 * Return bool true if input array contains a (keyed) timestamp date
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.4.16 - 2008-10-18
 * @param array $input
 * @return bool
 * @static
 */
  public static function isArrayTimestampDate( $input ) {
    return ( is_array( $input ) && isset( $input[self::$LCTIMESTAMP] ));
  }
/**
 * Return bool true if input string contains (trailing) UTC/iCal offset
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.14.1 - 2012-09-21
 * @param string $input
 * @return bool
 * @static
 */
  public static function isOffset( $input ) {
    static $PLUSMINUSARR = ['+', '-'];
    static $ZERO4 = '0000';
    static $NINE4 = '9999';
    static $ZERO6 = '000000';
    static $NINE6 = '999999';
    $input         = trim( (string) $input );
    if( self::$Z == substr( $input, -1 ))
      return true;
    elseif((   5 <= strlen( $input )) &&
        ( in_array( substr( $input, -5, 1 ), $PLUSMINUSARR )) &&
        ( $ZERO4 <= substr( $input, -4 )) && ( $NINE4 >= substr( $input, -4 )))
      return true;
    elseif((   7 <= strlen( $input )) &&
        ( in_array( substr( $input, -7, 1 ), $PLUSMINUSARR )) &&
        ( $ZERO6 <= substr( $input, -6 )) && ( $NINE6 >= substr( $input, -6 )))
      return true;
    return false;
  }
/**
 * Convert a date from string to (internal, keyed) array format, return true on success
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.11.8 - 2012-01-27
 * @param mixed $date
 * @return bool, true on success
 * @static
 */
  public static function strDate2arr( & $date ) {
    static $ET = [' ', 't', 'T'];
    if( is_array( $date ))
      return false;
    if( 5 > strlen( (string) $date ))
      return false;
    $work = $date;
    if( 2 == substr_count( $work, self::$MINUS ))
      $work = str_replace( self::$MINUS, null, $work );
    if( 2 == substr_count( $work, self::$L ))
      $work = str_replace( self::$L, null, $work );
    if( ! ctype_digit( substr( $work, 0, 8 )))
      return false;
    $temp = [self::$LCYEAR  => (int) substr( $work,  0, 4 ),
             self::$LCMONTH => (int) substr( $work,  4, 2 ),
             self::$LCDAY   => (int) substr( $work,  6, 2 )];
    if( ! checkdate( $temp[self::$LCMONTH],
                     $temp[self::$LCDAY],
                     $temp[self::$LCYEAR] ))
      return false;
    if( 8 == strlen( $work )) {
      $date = $temp;
      return true;
    }
    if( in_array( $work[8], $ET ))
      $work =  substr( $work, 9 );
    elseif( ctype_digit( $work[8] ))
      $work = substr( $work, 8 );
    else
     return false;
    if( 2 == substr_count( $work, self::$COLON ))
      $work = str_replace( self::$COLON, null, $work );
    if( ! ctype_digit( substr( $work, 0, 4 )))
      return false;
    $temp[self::$LCHOUR]  = substr( $work, 0, 2 );
    $temp[self::$LCMIN]   = substr( $work, 2, 2 );
    if((( 0 > $temp[self::$LCHOUR] ) || ( $temp[self::$LCHOUR] > 23 )) ||
       (( 0 > $temp[self::$LCMIN] )  || ( $temp[self::$LCMIN]  > 59 )))
      return false;
    if( ctype_digit( substr( $work, 4, 2 ))) {
      $temp[self::$LCSEC] = substr( $work, 4, 2 );
      if((  0 > $temp[self::$LCSEC] ) || ( $temp[self::$LCSEC]  > 59 ))
        return false;
      $len = 6;
    }
    else {
      $temp[self::$LCSEC] = 0;
      $len = 4;
    }
    if( $len < strlen( $work))
      $temp[self::$LCtz] = trim( substr( $work, 6 ));
    $date = $temp;
    return true;
  }
/**
 * Return string date-time/date as array (in internal format, keyed)
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.23 - 2017-02-19
 * Modified to also return original string value by Yitzchok Lavi <icalcreator@onebigsystem.com>
 * @param string $datetime
 * @param int    $parno   default false
 * @param mixed  $wtz     default null
 * @return array
 * @static
 */
  public static function strDate2ArrayDate( $datetime,
                                            $parno=null,
                                            $wtz=null ) {
    static $SECONDS   = ' seconds';
    $unparseddatetime = $datetime;
    $datetime   = (string) trim( $datetime );
    $tz         = null;
    $offset     = 0;
    $tzSts      = false;
    $len        = strlen( $datetime );
    if( self::$Z == substr( $datetime, -1 )) {
      $tz       = self::$Z;
      $datetime = trim( substr( $datetime, 0, ( $len - 1 )));
      $tzSts    = true;
    }
    if( self::isOffset( substr( $datetime, -5, 5 ))) { // [+/-]NNNN offset
      $tz       = substr( $datetime, -5, 5 );
      $datetime = trim( substr( $datetime, 0, ($len - 5)));
    }
    elseif( self::isOffset( substr( $datetime, -7, 7 ))) { // [+/-]NNNNNN offset
      $tz       = substr( $datetime, -7, 7 );
      $datetime = trim( substr( $datetime, 0, ($len - 7)));
    }
    elseif( empty( $wtz ) &&
            ctype_digit( substr( $datetime, 0, 4 ))  &&
            ctype_digit( substr( $datetime, -2, 2 )) &&
            self::strDate2arr( $datetime )) {
      $output = $datetime;
      if( ! empty( $tz ))
        $output[self::$LCtz] = self::$Z;
      $output[self::$UNPARSEDTEXT] = $unparseddatetime;
      return $output;
    }
    else {
      $tx  = 0;  //  find any TRAILING timezone or offset
      $len = strlen( $datetime );
      for( $cx = -1; $cx > ( 9 - $len ); $cx-- ) {
        $char = substr( $datetime, $cx, 1 );
        if(( self::$SP1 == $char ) || ctype_digit( $char ))
          break;       // if exists, tz ends here.. . ?
        else
           $tx--;      // tz length counter
      }
      if( 0 > $tx ) {  // if any timezone or offset found
        $tz     = substr( $datetime, $tx );
        $datetime = trim( substr( $datetime, 0, $len + $tx ));
      }
      if((  ctype_digit( substr( $datetime,  0, 8 )) &&
          ( self::$T ==          $datetime[8] )      &&
            ctype_digit( substr( $datetime, -6, 6 ))) ||
          ( ctype_digit( substr( $datetime,  0, 14 ))))
        $tzSts  = true;
    }
    if( empty( $tz ) && ! empty( $wtz ))
      $tz       = $wtz;
    if( 3 == $parno )
      $tz       = null;
    if( ! empty( $tz )) { // tz set
      if(( self::$Z != $tz ) && ( self::isOffset( $tz ))) {
        $offset = (string) self::tz2offset( $tz ) * -1;
        $tz     = self::$UTC;
        $tzSts  = true;
      }
      elseif( ! empty( $wtz ))
        $tzSts  = true;
      $tz       = trim( $tz );
      if(( 0 == strcasecmp( self::$Z, $tz )) ||
         ( 0 == strcasecmp( self::$GMT, $tz )))
        $tz     = self::$UTC;
      if( 0 < substr_count( $datetime, self::$MINUS ))
        $datetime = str_replace( self::$MINUS, self::$L, $datetime );
      try {
        $timezone = new \DateTimeZone( $tz );
        $d        = new \DateTime( $datetime, $timezone );
        if( 0  != $offset )  // adjust for offset
          $d->modify( $offset . $SECONDS );
        $datestring = $d->format( self::$YMDHIS3 );
        unset( $d );
      }
      catch( \Exception $e ) {
        $datestring = date( self::$YMDHIS3, strtotime( $datetime ));
      }
    } // end if( ! empty( $tz ))
    else
      $datestring = date( self::$YMDHIS3, strtotime( $datetime ));
    if( self::$UTC == $tz )
      $tz         = self::$Z;
    $d            = explode( self::$MINUS, $datestring );
    $output       = [self::$LCYEAR  => $d[0],
                     self::$LCMONTH => $d[1],
                     self::$LCDAY   => $d[2]];
    if( ! empty( $parno ) || ( 3 != $parno )) { // parno is set to 6 or 7
      $output[self::$LCHOUR] = $d[3];
      $output[self::$LCMIN]  = $d[4];
      $output[self::$LCSEC]  = $d[5];
      if(( $tzSts || ( 7 == $parno )) && ! empty( $tz ))
        $output[self::$LCtz] = $tz;
    }
    // return original string in the array in case strtotime failed to make sense of it
    $output[self::$UNPARSEDTEXT] = $unparseddatetime;
    return $output;
  }
/**
 * Return string/array timestamp(+ offset/timezone (default UTC)) as array (in internal format, keyed).
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.11 - 2015-03-07
 * @param mixed   $timestamp
 * @param int     $parno
 * @param string  $wtz
 * @return array
 * @static
 */
  public static function timestamp2date( $timestamp, $parno=6, $wtz=null ) {
    static $FMTTIMESTAMP = '@%s';
    static $SPSEC        = ' seconds';
    if( is_array( $timestamp )) {
      $tz        = ( isset( $timestamp[self::$LCtz] ))
                 ? $timestamp[self::$LCtz] : $wtz;
      $timestamp = $timestamp[self::$LCTIMESTAMP];
    }
    $tz          = ( isset( $tz )) ? $tz : $wtz;
    $offset      = 0;
    if( empty( $tz ) ||
       ( self::$Z == $tz ) ||
       ( 0 == strcasecmp( self::$GMT, $tz )))
      $tz        = self::$UTC;
    elseif( self::isOffset( $tz )) {
      $offset    = self::tz2offset( $tz );
    }
    try {
      $timestamp = sprintf( $FMTTIMESTAMP, $timestamp );
      $d         = new \DateTime( $timestamp );     // set UTC date
      if(  0 != $offset )                           // adjust for offset
        $d->modify( $offset . $SPSEC );
      elseif( self::$UTC != $tz )
        $d->setTimezone( new \DateTimeZone( $tz )); // convert to local date
      $date      = $d->format( self::$YMDHIS3 );
    }
    catch( \Exception $e ) {
      $date      = date( self::$YMDHIS3, $timestamp );
    }
    $date        = explode( self::$MINUS, $date );
    $output      = [self::$LCYEAR  => $date[0],
                    self::$LCMONTH => $date[1],
                    self::$LCDAY   => $date[2]];
    if( 3 != $parno ) {
      $output[self::$LCHOUR] = $date[3];
      $output[self::$LCMIN]  = $date[4];
      $output[self::$LCSEC]  = $date[5];
      if(( self::$UTC == $tz ) || ( 0 == $offset ))
        $output[self::$LCtz] = self::$Z;
    }
    return $output;
  }
/**
 * Return seconds based on an offset, [+/-]HHmm[ss], used when correcting UTC to localtime or v.v.
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.23.8 - 2017-04-17
 * @param string $tz
 * @return integer
 * @static
 */
  public static function tz2offset( $tz ) {
    static $ZERO4 = '0000';
    static $NINE4 = '9999';
    static $ZERO2 = '00';
    static $NINE2 = '99';
    $tz           = trim( (string) $tz );
    $offset       = 0;
    if(((          5  != strlen( $tz )) &&
                 ( 7  != strlen( $tz )))          ||
      ((  self::$PLUS != $tz[0]  &&
       ( self::$MINUS != $tz[0] )))               ||
       ((      $ZERO4 >= substr( $tz, 1, 4 )) &&
             ( $NINE4 <  substr( $tz, 1, 4 )))    ||
                (( 7  == strlen( $tz )) &&
                 ( $ZERO2 > substr( $tz, 5, 2 )) &&
                 ( $NINE2 < substr( $tz, 5, 2 ))))
      return $offset;
    $hours2sec    = (int) substr( $tz, 1, 2 ) * 3600;
    $min2sec      = (int) substr( $tz, 3, 2 ) *   60;
    $sec          = ( 7  == strlen( $tz ))
                  ? (int) substr( $tz, -2 ) : $ZERO2;
    $offset       = $hours2sec + $min2sec + $sec;
    $offset       = ( self::$MINUS == $tz[0] )
                  ? $offset * -1 : $offset;
    return $offset;
  }
}
