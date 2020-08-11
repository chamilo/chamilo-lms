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
 * iCalcreator attendee support class
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.23 - 2017-02-02
 */
class utilAttendee {
/**
 * Return string after a cal-address check, prefix mail address with MAILTO
 *
 * Acceps other prefix ftp://, http://, file://, gopher://, news:, nntp://, telnet://, wais://, prospero:// etc
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.23 - 2017-03-06
 * @param string $value
 * @param bool   $trimQuotes
 * @return string
 * @static
 * @TODO fix in util::splitContent() ??
 */
  public static function calAddressCheck( $value, $trimQuotes=true ) {
    static $MAILTOCOLON = 'MAILTO:';
    $value = trim( $value );
    if( $trimQuotes )
     $value = trim( $value, util::$QQ );
    switch( true ) {
      case( empty( $value )) :
        break;
      case( 0 == strcasecmp( $MAILTOCOLON, substr( $value, 0, 7 ))) :
        $value = $MAILTOCOLON . substr( $value, 7 ); // accept mailto:
        break;
      case( false !== ( $pos = strpos( substr( $value, 0, 9 ), util::$COLON ))) :
        break;                                       // accept (as is) from list above
      case( filter_var( $value, FILTER_VALIDATE_EMAIL )) :
        $value = $MAILTOCOLON . $value;              // accept mail address
        break;
      default :                                      // accept as is...
        break;
    }
    return $value;
  }
/**
 * Return formatted output for calendar component property attendee
 *
 * @param array $attendeeData
 * @param bool  $allowEmpty
 * @return string
 * @static
 */
  public static function formatAttendee( array $attendeeData, $allowEmpty ) {
    static $FMTQVALUE   = '"%s"';
    static $FMTKEYVALUE = ';%s=%s';
    static $FMTKEYEQ    = ';%s=';
    static $FMTDIREQ    = ';%s=%s%s%s';
    $output = null;
    foreach( $attendeeData as $ax => $attendeePart ) {
      if( empty( $attendeePart[util::$LCvalue] )) {
        if( $allowEmpty )
          $output .= util::createElement( util::$ATTENDEE );
        continue;
      }
      $attributes = $content = null;
      foreach( $attendeePart as $pLabel => $pValue ) {
        if( util::$LCvalue == $pLabel ) {
          $content        .= $pValue;
          continue;
        }
        if(( util::$LCparams != $pLabel ) ||
           ( ! is_array( $pValue )))
          continue;
        foreach( $pValue as $pLabel2 => $pValue2 ) { // fix (opt) quotes
          if( is_array( $pValue2 ) ||
              in_array( $pLabel2, util::$ATTENDEEPARKEYS ))
            continue; // DELEGATED-FROM, DELEGATED-TO, MEMBER
          if(( false !== strpos( $pValue2, util::$COLON )) ||
             ( false !== strpos( $pValue2, util::$SEMIC )) ||
             ( false !== strpos( $pValue2, util::$COMMA )))
            $pValue[$pLabel2] = sprintf( $FMTQVALUE, $pValue2 );
        }
            /* set attenddee parameters in rfc2445 order */
        if(     isset( $pValue[util::$CUTYPE] ))
          $attributes   .= sprintf( $FMTKEYVALUE,
                                    util::$CUTYPE,
                                    $pValue[util::$CUTYPE] );
        if( isset( $pValue[util::$MEMBER] ))
          $attributes   .= sprintf( $FMTKEYEQ,
                                    util::$MEMBER ) .
                           self::getQuotedListItems( $pValue[util::$MEMBER] );
        if( isset( $pValue[util::$ROLE] ))
          $attributes   .= sprintf( $FMTKEYVALUE,
                                    util::$ROLE,
                                    $pValue[util::$ROLE] );
        if( isset( $pValue[util::$PARTSTAT] ))
          $attributes   .= sprintf( $FMTKEYVALUE,
                                    util::$PARTSTAT,
                                    $pValue[util::$PARTSTAT] );
        if( isset( $pValue[util::$RSVP] ))
          $attributes   .= sprintf( $FMTKEYVALUE,
                                    util::$RSVP,
                                    $pValue[util::$RSVP] );
        if( isset( $pValue[util::$DELEGATED_TO] ))
          $attributes   .= sprintf( $FMTKEYEQ,
                                    util::$DELEGATED_TO ) .
                           self::getQuotedListItems( $pValue[util::$DELEGATED_TO] );
        if( isset( $pValue[util::$DELEGATED_FROM] ))
          $attributes   .= sprintf( $FMTKEYEQ,
                                    util::$DELEGATED_FROM ) .
                           self::getQuotedListItems( $pValue[util::$DELEGATED_FROM] );
        if( isset( $pValue[util::$SENT_BY] ))
          $attributes .= sprintf( $FMTKEYVALUE,
                                  util::$SENT_BY,
                                  $pValue[util::$SENT_BY] );
        if( isset( $pValue[util::$CN] ))
          $attributes .= sprintf( $FMTKEYVALUE,
                                  util::$CN,
                                  $pValue[util::$CN] );
        if( isset( $pValue[util::$DIR] )) {
          $delim = ( false === strpos( $pValue[util::$DIR], util::$QQ )) ? util::$QQ : null;
          $attributes .= sprintf( $FMTDIREQ, util::$DIR,
                                             $delim,
                                             $pValue[util::$DIR],
                                             $delim );
        }
        if( isset( $pValue[util::$LANGUAGE] ))
          $attributes .= sprintf( $FMTKEYVALUE,
                                  util::$LANGUAGE,
                                  $pValue[util::$LANGUAGE] );
        $xparams = [];
        foreach( $pValue as $pLabel2 => $pValue2 ) {
          if( ctype_digit( (string) $pLabel2 ))
            $xparams[]  = $pValue2;
          elseif( ! in_array( $pLabel2, util::$ATTENDEEPARALLKEYS ))
            $xparams[$pLabel2] = $pValue2;
        }
        if( empty( $xparams ))
          continue;
        ksort( $xparams, SORT_STRING );
        foreach( $xparams as $pLabel2 => $pValue2 ) {
          if( ctype_digit( (string) $pLabel2 ))
            $attributes .= util::$SEMIC . $pValue2; // ??
           else
            $attributes .= sprintf( $FMTKEYVALUE, $pLabel2,
                                                  $pValue2 );
        }
      } // end foreach( $attendeePart )) as $pLabel => $pValue )
      $output .= util::createElement( util::$ATTENDEE,
                                      $attributes,
                                      $content );
    } // end foreach( $attendeeData as $ax => $attendeePart )
    return $output;
  }
/**
 * Return string of comma-separated quoted array members
 *
 * @param array $list
 * @return string
 * @access private
 * @static
 */
  private static function getQuotedListItems( array $list ) {
    static $FMTQVALUE      = '"%s"';
    static $FMTCOMMAQVALUE = ',"%s"';
    $strList = null;
    foreach( $list as $x => $v )
      $strList .= ( 0 < $x )
                ? sprintf( $FMTCOMMAQVALUE, $v )
                : sprintf( $FMTQVALUE,      $v );
    return $strList;
  }
/**
 * Return formatted output for calendar component property attendee
 *
 * @param array  $params
 * @param string $objName
 * @param string $lang
 * @return array
 * @static
 */
  public static function prepAttendeeParams( $params, $objName, $lang ) {
    static $NONXPROPCOMPS = null;
    if( is_null( $NONXPROPCOMPS ))
      $NONXPROPCOMPS = [util::$LCVFREEBUSY, util::$LCVALARM];
    $params2 = [];
    if( is_array( $params )) {
      $optArr = [];
      $params = array_change_key_case( $params, CASE_UPPER );
      foreach( $params as $pLabel => $optParamValue ) {
        if( ! util::isXprefixed( $pLabel ) &&
           in_array( $objName, $NONXPROPCOMPS ))
          continue;
        switch( $pLabel ) {
          case util::$MEMBER:
          case util::$DELEGATED_TO:
          case util::$DELEGATED_FROM:
            if( ! is_array( $optParamValue ))
              $optParamValue  = [$optParamValue];
            foreach(( array_keys( $optParamValue )) as $px )
              $optArr[$pLabel][] = self::calAddressCheck( $optParamValue[$px] );
            break;
          default:
            if( util::$SENT_BY == $pLabel )
              $optParamValue  = self::calAddressCheck( $optParamValue );
            else
              $optParamValue  = trim( $optParamValue, util::$QQ );
            $params2[$pLabel] = $optParamValue;
            break;
        } // end switch( $pLabel.. .
      } // end foreach( $params as $pLabel => $optParamValue )
      foreach( $optArr as $pLabel => $pValue )
        $params2[$pLabel] = $pValue;
    } // end if( is_array($params ))
        // remove defaults
    util::existRem( $params2, util::$CUTYPE,   util::$INDIVIDUAL );
    util::existRem( $params2, util::$PARTSTAT, util::$NEEDS_ACTION );
    util::existRem( $params2, util::$ROLE,     util::$REQ_PARTICIPANT );
    util::existRem( $params2, util::$RSVP,     util::$false );
        // check language setting
    if( isset( $params2[util::$CN ] ) &&
      ! isset( $params2[util::$LANGUAGE ] ) &&
      ! empty( $lang ))
      $params2[util::$LANGUAGE ] = $lang;
    return $params2;
  }
}
