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
use kigkonsult\iCalcreator\util\utilGeo;
use kigkonsult\iCalcreator\util\utilSelect;
use kigkonsult\iCalcreator\util\utilRedirect;
/**
 * vcalendar class
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.20 - 2017-01-29
 */
class vcalendar extends iCalBase {
  use traits\CALSCALEtrait,
      traits\METHODtrait,
      traits\PRODIDtrait,
      traits\VERSIONtrait;
/**
 *  @var property output formats, used by CALSCALE, METHOD, PRODID and VERSION
 *  @access private
 *  @static
 */
  private static $FMTICAL = "%s:%s\r\n";
/**
 * Constructor for calendar object
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.23 - 2017-03-16
 * @param array $config
 */
  public function __construct( $config = []) {
    $this->setConfig( util::$UNIQUE_ID, ( isset( $_SERVER[util::$SERVER_NAME] ))
                                ? gethostbyname( $_SERVER[util::$SERVER_NAME] )
                                : util::$LOCALHOST );
    $this->setConfig( util::initConfig( $config ));
  }
/**
 * Destructor
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.23 - 2017-03-18
 */
  public function __destruct() {
    if( ! empty( $this->components ))
      foreach( $this->components as $cix => $comp )
        $this->components[$cix]->__destruct();
    unset( $this->xprop,
           $this->components,
           $this->unparsed,
           $this->config,
           $this->propix,
           $this->compix,
           $this->propdelix );
    unset( $this->calscale,
           $this->method,
           $this->prodid,
           $this->version );
  }
/**
 * Return iCalcreator version
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.18.5 - 2013-08-29
 * @return string
 */
  public static function iCalcreatorVersion() {
    return trim( substr( ICALCREATOR_VERSION, strpos( ICALCREATOR_VERSION, util::$SP1 )));
  }
/**
 * Return vcalendar config value or * calendar components, false on not found
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.23 - 2017-02-28
 * @param mixed $config
 * @return mixed
 */
  public function getConfig( $config=null ) {
    static $VCALENDAR = 'vcalendar';
    static $YMDHIS = 'YmdHis';
    static $DOTICS = '.ics';
    static $DOTXML = '.xml';
    if( is_null( $config )) {
      $return = [];
      $return[util::$ALLOWEMPTY]  = $this->getConfig( util::$ALLOWEMPTY );
      $return[util::$DELIMITER]   = $this->getConfig( util::$DELIMITER );
      $return[util::$DIRECTORY]   = $this->getConfig( util::$DIRECTORY );
      $return[util::$FILENAME]    = $this->getConfig( util::$FILENAME );
      $return[util::$DIRFILE]     = $this->getConfig( util::$DIRFILE );
      $return[util::$FILESIZE]    = $this->getConfig( util::$FILESIZE );
      if( false !== ( $cfg        = $this->getConfig( util::$URL )))
        $return[util::$URL]       = $cfg;
      if(      false !== ( $cfg   = $this->getConfig( util::$LANGUAGE )))
        $return[util::$LANGUAGE]  = $cfg;
      if(      false !== ( $cfg   = $this->getConfig( util::$TZID )))
        $return[util::$TZID]      = $cfg;
      $return[util::$UNIQUE_ID]   = $this->getConfig( util::$UNIQUE_ID );
      return $return;
    }
    switch( strtoupper( $config )) {
      case util::$DELIMITER :
        if( isset( $this->config[util::$DELIMITER] ))
          return $this->config[util::$DELIMITER];
        break;
      case util::$DIRECTORY :
        if( ! isset( $this->config[util::$DIRECTORY] ))
          $this->config[util::$DIRECTORY] = util::$DOT;
        return $this->config[util::$DIRECTORY];
        break;
      case util::$DIRFILE :
        return $this->getConfig( util::$DIRECTORY ) .
               $this->getConfig( util::$DELIMITER ) .
               $this->getConfig( util::$FILENAME );
        break;
      case util::$FILEINFO :
        return [$this->getConfig( util::$DIRECTORY ),
                $this->getConfig( util::$FILENAME ),
                $this->getConfig( util::$FILESIZE )];
        break;
      case util::$FILENAME :
        if( ! isset( $this->config[util::$FILENAME] ))
          $this->config[util::$FILENAME] =
              date( $YMDHIS, intval( microtime( true ))) . $DOTICS;
        return $this->config[util::$FILENAME];
        break;
      case util::$FILESIZE :
        $size    = 0;
        if( empty( $this->config[util::$URL] )) {
          $dirfile = $this->getConfig( util::$DIRFILE );
          if( ! is_file( $dirfile ) || ( false === ( $size = filesize( $dirfile ))))
            $size = 0;
          clearstatcache();
        }
        return $size;
        break;
      case util::$UNIQUE_ID:
        if( isset( $this->config[util::$UNIQUE_ID] ))
          return $this->config[util::$UNIQUE_ID];
        break;
      case util::$URL :
        if( ! empty( $this->config[util::$URL] ))
          return $this->config[util::$URL];
        break;
      default :
        return parent::getConfig( $config );
    }
    return false;
  }
/**
 * General vcalendar set config
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.23 - 2017-02-02
 * @param mixed  $config
 * @param string $value
 * @param string $arg3 (dummy)
 * @return bool
 */
  public function setConfig( $config, $value=null, $arg3=null ) {
    static $PROTOCOLS    = ['HTTP://', 'WEBCAL://', 'webcal://'];
    static $PROTOHTTP    = 'http://';
    static $LCPROTOHTTPS = 'https://';
    static $UCPROTOHTTPS = 'HTTPS://';
    static $DOTICS       = '.ics';
    if( is_array( $config )) {
      $config  = array_change_key_case( $config, CASE_UPPER );
      if( isset( $config[util::$DELIMITER] )) {
        if( false === $this->setConfig( util::$DELIMITER,
                                        $config[util::$DELIMITER] ))
          return false;
        unset( $config[util::$DELIMITER] );
      }
      if( isset( $config[util::$DIRECTORY] )) {
        if( false === $this->setConfig( util::$DIRECTORY,
                                        $config[util::$DIRECTORY] ))
          return false;
        unset( $config[util::$DIRECTORY] );
      }
      foreach( $config as $cKey => $cValue ) {
        if( false === $this->setConfig( $cKey, $cValue ))
          return false;
      }
      return true;
    }
    $res    = false;
    switch( strtoupper( $config )) {
      case util::$DELIMITER :
        $this->config[util::$DELIMITER] = $value;
        return true;
        break;
      case util::$DIRECTORY :
        if( false === ( $value = realpath( rtrim( trim( $value ), $this->config[util::$DELIMITER] ))))
          return false;
        else {
            /* local directory */
          $this->config[util::$DIRECTORY] = $value;
          $this->config[util::$URL]       = null;
          return true;
        }
        break;
      case util::$FILENAME :
        $value   = trim( $value );
        $dirfile = $this->config[util::$DIRECTORY] .
                   $this->config[util::$DELIMITER] . $value;
        if( file_exists( $dirfile )) {
            /* local file exists */
          if( is_readable( $dirfile ) || is_writable( $dirfile )) {
            clearstatcache();
            $this->config[util::$FILENAME] = $value;
            return true;
          }
          else
            return false;
        }
        elseif( is_readable( $this->config[util::$DIRECTORY] ) ||
                is_writable( $this->config[util::$DIRECTORY] )) {
            /* read- or writable directory */
          clearstatcache();
          $this->config[util::$FILENAME] = $value;
          return true;
        }
        else
          return false;
        break;
      case util::$LANGUAGE : // set language for calendar component as defined in [RFC 1766]
        $value   = trim( $value );
        $this->config[util::$LANGUAGE] = $value;
        $this->makeProdid();
        $subcfg  = [util::$LANGUAGE => $value];
        $res     = true;
        break;
      case util::$UNIQUE_ID :
        $value   = trim( $value );
        $this->config[util::$UNIQUE_ID] = $value;
        $this->makeProdid();
        $subcfg  = [util::$UNIQUE_ID => $value];
        $res     = true;
        break;
      case util::$URL :
            /* remote file - URL */
        $value     = str_replace( $PROTOCOLS, $PROTOHTTP, trim( $value ));
        $value     = str_replace( $UCPROTOHTTPS, $LCPROTOHTTPS, trim( $value ));
        if(( $PROTOHTTP != substr( $value, 0, 7 )) &&
           ( $LCPROTOHTTPS != substr( $value, 0, 8 )))
          return false;
        $this->config[util::$DIRECTORY] = util::$DOT;
        $this->config[util::$URL] = $value;
        if( $DOTICS != strtolower( substr( $value, -4 )))
          unset( $this->config[util::$FILENAME] );
        else
          $this->config[util::$FILENAME] = basename( $value );
        return true;
        break;
      default:  // any unvalid config key.. .
        $res     = parent::setConfig( $config, $value );
    }
    if( ! $res )
      return false;
    if( isset( $subcfg ) && ! empty( $this->components )) {
      foreach( $subcfg as $cfgkey => $cfgValue ) {
        foreach( $this->components as $cix => $component ) {
          $res = $this->components[$cix]->setConfig( $cfgkey, $cfgValue, true );
          if( ! $res )
            break 2;
        }
      }
    }
    return $res;
  }
/**
 * Delete calendar property value
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.8.8 - 2011-03-15
 * @param mixed $propName  bool false => X-property
 * @param int   $propix    specific property in case of multiply occurences
 * @return bool true on successfull delete
 */
  public function deleteProperty( $propName=false, $propix=false ) {
    $propName = ( $propName ) ? strtoupper( $propName ) : util::$X_PROP;
    if( ! $propix )
      $propix = ( isset( $this->propdelix[$propName] ) &&
                       ( util::$X_PROP != $propName ))
              ? $this->propdelix[$propName] + 2
              : 1;
    $this->propdelix[$propName] = --$propix;
    switch( $propName ) {
      case util::$CALSCALE:
        $this->calscale = null;
        break;
      case util::$METHOD:
        $this->method = null;
        break;
      default:
        return parent::deleteXproperty( $propName,
                                        $this->xprop,
                                        $propix,
                                        $this->propdelix );
        break;
    }
    return true;
  }
/**
 * Return calendar property value/params
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.09 - 2015-03-29
 * @param string $propName
 * @param int    $propix    specific property in case of multiply occurences
 * @param bool   $inclParam
 * @return mixed
 */
  public function getProperty( $propName=false,
                               $propix=false,
                               $inclParam=false ) {
    static $RECURRENCE_ID_UID = 'RECURRENCE-ID-UID';
    static $R_UID             = 'R-UID';
    $propName = ( $propName ) ? strtoupper( $propName ) : util::$X_PROP;
    if( util::$X_PROP == $propName ) {
      if( empty( $propix ))
        $propix  = ( isset( $this->propix[$propName] ))
                 ? $this->propix[$propName] + 2
                 : 1;
      $this->propix[$propName] = --$propix;
    }
    switch( $propName ) {
      case util::$ATTENDEE:
      case util::$CATEGORIES:
      case util::$CONTACT:
      case util::$DTSTART:
      case util::$GEOLOCATION:
      case util::$LOCATION:
      case util::$ORGANIZER:
      case util::$PRIORITY:
      case util::$RESOURCES:
      case util::$STATUS:
      case util::$SUMMARY:
      case $RECURRENCE_ID_UID:
      case util::$RELATED_TO:
      case $R_UID:
      case util::$UID:
      case util::$URL:
        $output  = [];
        foreach( $this->components as $cix => $component) {
          if( ! in_array( $component->objName, util::$VCOMPS ))
            continue;
          if( in_array( $propName, util::$MPROPS1 )) {
            $component->getProperties( $propName, $output );
            continue;
          }
          elseif(( 3 < strlen( $propName )) &&
                 ( util::$UID == substr( $propName, -3 ))) {
            if( false !== ( $content = $component->getProperty( util::$RECURRENCE_ID )))
              $content = $component->getProperty( util::$UID );
          }
          elseif( util::$GEOLOCATION == $propName ) {
            if( false === ( $geo = $component->getProperty( util::$GEO )))
              continue;
            $loc = $component->getProperty( util::$LOCATION );
            $content = ( empty( $loc ))
                     ? null
                     : $loc . util::$SP1;
            $content .= utilGeo::geo2str2( $geo[utilGeo::$LATITUDE],
                                                utilGeo::$geoLatFmt ) .
                        utilGeo::geo2str2( $geo[utilGeo::$LONGITUDE],
                                                utilGeo::$geoLongFmt ) . utiL::$L;
          }
          elseif( false === ( $content = $component->getProperty( $propName )))
            continue;
          if(( false === $content ) || empty( $content ))
            continue;
          elseif( is_array( $content )) {
            if( isset( $content[util::$LCYEAR] )) {
              $key  = sprintf( util::$YMD, (int) $content[util::$LCYEAR],
                                           (int) $content[util::$LCMONTH],
                                           (int) $content[util::$LCDAY] );
              if( ! isset( $output[$key] ))
                $output[$key] = 1;
              else
                $output[$key] += 1;
            }
            else {
              foreach( $content as $partKey => $partValue ) {
                if( ! isset( $output[$partKey] ))
                  $output[$partKey]  = $partValue;
                else
                  $output[$partKey] += $partValue;
              }
            }
          } // end elseif( is_array( $content )) {
          elseif( ! isset( $output[$content] ))
            $output[$content] = 1;
          else
            $output[$content] += 1;
        } // end foreach( $this->components as $cix => $component)
        if( ! empty( $output ))
          ksort( $output );
        return $output;
        break;
      case util::$CALSCALE:
        return ( ! empty( $this->calscale )) ? $this->calscale : false;
        break;
      case util::$METHOD:
        return ( ! empty( $this->method )) ? $this->method : false;
        break;
      case util::$PRODID:
        if( empty( $this->prodid ))
          $this->makeProdid();
        return $this->prodid;
        break;
      case util::$VERSION:
        return ( ! empty( $this->version )) ? $this->version : false;
        break;
      default:
        if( $propName != util::$X_PROP ) {
          if( ! isset( $this->xprop[$propName] ))
            return false;
          return ( $inclParam ) ? [$propName,
                                   $this->xprop[$propName]]
                                : [$propName,
                                   $this->xprop[$propName][util::$LCvalue]];
        }
        else {
          if( empty( $this->xprop ))
            return false;
          $xpropno = 0;
          foreach( $this->xprop as $xpropKey => $xpropValue ) {
            if( $propix == $xpropno )
              return ( $inclParam ) ? [$xpropKey,
                                       $xpropValue]
                                    : [$xpropKey,
                                       $xpropValue[util::$LCvalue]];
            else
              $xpropno++;
          }
          unset( $this->propix[$propName] );
          return false; // not found ??
        }
    }
    return false;
  }
/**
 * General vcalendar set property method
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.23 - 2017-04-09
 * @param mixed $args variable number of function arguments,
 *                    first argument is ALWAYS component name,
 *                    second ALWAYS component value!
 * @return bool
 */
  public function setProperty () {
    $numargs    = func_num_args();
    if( 1 > $numargs )
      return false;
    $arglist    = func_get_args();
    switch( strtoupper( $arglist[0] )) {
      case util::$CALSCALE:
        return $this->setCalscale( $arglist[1] );
      case util::$METHOD:
        return $this->setMethod( $arglist[1] );
      case util::$VERSION:
        return $this->setVersion( $arglist[1] );
      default:
        if( ! isset( $arglist[1] ))
          $arglist[1] = null;
        if( ! isset( $arglist[2] ))
          $arglist[2] = null;
        return $this->setXprop( $arglist[0], $arglist[1], $arglist[2] );
    }
    return false;
  }
/**
 * Add calendar component to vcalendar
 *
 * alias to setComponent
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 1.x.x - 2007-04-24
 * @param object $component calendar component
 */
  public function addComponent( $component ) {
    $this->setComponent( $component );
    return true;
  }
/**
 * Return clone of calendar component
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.23.14 - 2017-05-02
 * @param mixed $arg1  ordno/component type/ component uid
 * @param mixed $arg2  ordno if arg1 = component type
 * @return calendarComponent on success, bool false on error
 */
  public function getComponent( $arg1=null, $arg2=null ) {
    static $INDEX = 'INDEX';
    $index = $argType = null;
    switch( true ) {
      case ( is_null( $arg1 )) : // first or next in component chain
        $argType = $INDEX;
        if( isset( $this->compix[$INDEX] ))
          $this->compix[$INDEX] = $this->compix[$INDEX] + 1;
        else
          $this->compix = [$INDEX => 1];
        $index   = $this->compix[$INDEX];
        break;
      case ( is_array( $arg1 )) : // [ *[propertyName => propertyValue] ]
        $arg2    = implode( util::$MINUS, array_keys( $arg1 ));
        if( isset( $this->compix[$arg2] ))
          $this->compix[$arg2] = $this->compix[$arg2] + 1;
        else
          $this->compix = [$arg2 => 1];
        $index   = $this->compix[$arg2];
        break;
      case ( ctype_digit( (string) $arg1 )) : // specific component in chain
        $argType = $INDEX;
        $index   = (int) $arg1;
        $this->compix = [];
        break;
      case ( in_array( strtolower( $arg1 ), util::$MCOMPS ) &&
                ( 0 != strcasecmp( $arg1,   util::$LCVALARM ))) : // object class name
        unset( $this->compix[$INDEX] );
        $argType = strtolower( $arg1 );
        if( is_null( $arg2 )) {
          if( isset( $this->compix[$argType] ))
            $this->compix[$argType] = $this->compix[$argType] + 1;
          else
            $this->compix = [$argType => 1];
          $index = $this->compix[$argType];
        }
        elseif( isset( $arg2 ) && ctype_digit( (string) $arg2 ))
          $index = (int) $arg2;
        break;
      case ( is_string( $arg1 )) : // assume UID as 1st argument
        if( is_null( $arg2 )) {
          if( isset( $this->compix[$arg1] ))
            $this->compix[$arg1] = $this->compix[$arg1] + 1;
          else
            $this->compix = [$arg1 => 1];
          $index = $this->compix[$arg1];
        }
        elseif( isset( $arg2 ) && ctype_digit( (string) $arg2 ))
          $index = (int) $arg2;
        break;
    } // end switch( true )
    if( isset( $index ))
      $index  -= 1;
    $ckeys = array_keys( $this->components );
    if( ! empty( $index ) && ( $index > end(  $ckeys )))
      return false;
    $cix1gC = 0;
    foreach( $ckeys as $cix ) {
      if( empty( $this->components[$cix] ))
        continue;
      if(( $INDEX == $argType ) && ( $index == $cix ))
        return clone $this->components[$cix];
      elseif( 0 == strcmp( $argType, $this->components[$cix]->objName )) {
        if( $index == $cix1gC )
          return clone $this->components[$cix];
        $cix1gC++;
      }
      elseif( is_array( $arg1 )) { // [ *[propertyName => propertyValue] ]
        $hit  = [];
        $arg1 = array_change_key_case( $arg1, CASE_UPPER );
        foreach( $arg1 as $pName => $pValue ) {
          if( ! in_array( $pName, util::$DATEPROPS ) &&
              ! in_array( $pName, util::$OTHERPROPS ))
            continue;
          if( in_array( $pName, util::$MPROPS1 )) { // multiple occurrence
            $propValues = [];
            $this->components[$cix]->getProperties( $pName, $propValues );
            $propValues = array_keys( $propValues );
            $hit[]      = ( in_array( $pValue, $propValues ));
            continue;
          } // end   if(.. .// multiple occurrence
          if( false === ( $value = $this->components[$cix]->getProperty( $pName ))) { // single occurrence
            $hit[] = false; // missing property
            continue;
          }
          if( util::$SUMMARY == $pName ) { // exists within (any case)
            $hit[] = ( false !== stripos( $value, $pValue )) ? true : false;
            continue;
          }
          if( in_array( $pName, util::$DATEPROPS )) {
            $valueDate = sprintf( util::$YMD, (int) $value[util::$LCYEAR],
                                              (int) $value[util::$LCMONTH],
                                              (int) $value[util::$LCDAY] );
            if( 8 < strlen( $pValue )) {
              if( isset( $value[util::$LCHOUR] )) {
                if( util::$T == substr( $pValue, 8, 1 ))
                  $pValue = str_replace( util::$T, null, $pValue );
                $valueDate .= sprintf( util::$HIS, (int) $value[util::$LCHOUR],
                                                   (int) $value[util::$LCMIN],
                                                   (int) $value[util::$LCSEC] );
              }
              else
                $pValue = substr( $pValue, 0, 8 );
            }
            $hit[] = ( $pValue == $valueDate ) ? true : false;
            continue;
          }
          elseif( !is_array( $value ))
            $value = [$value];
          foreach( $value as $part ) {
            $part = ( false !== strpos( $part, util::$COMMA ))
                  ? explode( util::$COMMA, $part ) : [$part];
            foreach( $part as $subPart ) {
              if( $pValue == $subPart ) {
                $hit[] = true;
                continue 3;
              }
            }
          } // end foreach( $value as $part )
          $hit[] = false; // no hit in property
        } // end  foreach( $arg1 as $pName => $pValue )
        if( in_array( true, $hit )) {
          if( $index == $cix1gC )
            return clone $this->components[$cix];
          $cix1gC++;
        }
      } // end elseif( is_array( $arg1 )) { // [ *[propertyName => propertyValue] ]
      elseif( ! $argType &&
            ( $arg1 == $this->components[$cix]->getProperty( util::$UID ))) {
        if( $index == $cix1gC )
          return clone $this->components[$cix];
        $cix1gC++;
      }
    } // end foreach( $ckeys as $cix )
            /* not found.. . */
    $this->compix = [];
    return false;
  }
/**
 * Return vevent object instance, vcalendar::newComponent() wrapper
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.23.20 - 2017-06-26
 * @return object
 */
  public function newVevent() {
    return $this->newComponent( util::$LCVEVENT );
  }
/**
 * Return vtodo object instance, vcalendar::newComponent() wrapper
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.23.20 - 2017-06-26
 * @return object
 */
  public function newVtodo() {
    return $this->newComponent( util::$LCVTODO );
  }
/**
 * Return vjournal object instance, vcalendar::newComponent() wrapper
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.23.20 - 2017-06-26
 * @return object
 */
  public function newVjournal() {
    return $this->newComponent( util::$LCVJOURNAL );
  }
/**
 * Return vfreebusy object instance, vcalendar::newComponent() wrapper
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.23.20 - 2017-06-26
 * @return object
 */
  public function newVfreebusy() {
    return $this->newComponent( util::$LCVFREEBUSY );
  }
/**
 * Return vtimezone object instance, vcalendar::newComponent() wrapper
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.23.20 - 2017-06-26
 * @return object
 */
  public function newVtimezone() {
    return $this->newComponent( util::$LCVTIMEZONE );
  }
/**
 * Replace calendar component in vcalendar
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.11 - 2015-03-21
 * @param object $component calendar component
 * @return bool
 */
  public function replaceComponent( $component  ) {
    if( in_array( $component->objName, util::$VCOMPS ))
      return $this->setComponent( $component,
                                  $component->getProperty( util::$UID ));
    if(( util::$LCVTIMEZONE != $component->objName ) ||
         ( false === ( $tzid = $component->getProperty( util::$TZID ))))
      return false;
    foreach( $this->components as $cix => $comp ) {
      if( util::$LCVTIMEZONE != $component->objName )
        continue;
      if( $tzid == $comp->getProperty( util::$TZID )) {
        $component->compix = [];
        $this->components[$cix] = $component;
        return true;
      }
    }
    return false;
  }
/**
 * Return selected components from calendar on date or selectOption basis
 *
 * DTSTART MUST be set for every component.
 * No date check.
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.23.3 - 2017-03-19
 * @param mixed $startY       (int) start Year,  default current Year
 *                       ALT. (obj) start date (datetime)
 *                       ALT. array selecOptions ( *[ <propName> => <uniqueValue> ] )
 * @param mixed $startM       (int) start Month, default current Month
 *                                ALT. (obj) end date (datetime)
 * @param int   $startD start Day,   default current Day
 * @param int   $endY   end   Year,  default $startY
 * @param int   $endM   end   Month, default $startM
 * @param int   $endD   end   Day,   default $startD
 * @param mixed $cType  calendar component type(-s), default false=all else string/array type(-s)
 * @param bool  $flat   false (default) => output : array[Year][Month][Day][]
 *                      true            => output : array[] (ignores split)
 * @param bool  $any    true (default) - select component(-s) that occurs within period
 *                      false          - only component(-s) that starts within period
 * @param bool  $split  true (default) - one component copy every DAY it occurs during the
 *                                       period (implies flat=false)
 *                      false          - one occurance of component only in output array
 * @return mixed   array on success, bool false on error
 */
  public function selectComponents( $startY=null, $startM=null, $startD=null,
                                    $endY=null,   $endM=null,   $endD=null,
                                    $cType=null,  $flat=null,   $any=null, $split=null ) {
    return utilSelect::selectComponents( $this,
                                         $startY, $startM, $startD,
                                         $endY,   $endM,   $endD,
                                         $cType,  $flat,   $any, $split );
  }
/**
 * Sort iCal compoments
 *
 * Ascending sort on properties (if exist) x-current-dtstart, dtstart,
 * x-current-dtend, dtend, x-current-due, due, duration, created, dtstamp, uid if called without arguments,
 * otherwise sorting on specific (argument) property values
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.23 - 2017-02-20
 * @param string $sortArg
 */
  public function sort( $sortArg=null ) {
    static $SORTER = ['kigkonsult\iCalcreator\vcalendarSortHandler', 'cmpfcn'];
    if( 2 > $this->countComponents())
      return;
    if( ! is_null( $sortArg )) {
      $sortArg   = strtoupper( $sortArg );
      if( ! in_array( $sortArg, util::$OTHERPROPS ) &&
          ( util::$DTSTAMP != $sortArg ))
        $sortArg = null;
    }
    foreach( $this->components as $cix => $component )
      vcalendarSortHandler::setSortArgs( $this->components[$cix], $sortArg );
    usort( $this->components, $SORTER );
  }
/**
 * Parse iCal text/file into vcalendar, components, properties and parameters
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.23.18 - 2017-06-14
 * @param mixed    $unparsedtext  strict rfc2445 formatted, single property string or array of property strings
 * @param resource $context       PHP resource context
 * @return bool    true on success, false on parse error
 */
  public function parse( $unparsedtext=false, $context=null ) {
    static $NLCHARS         = '\n';
    static $BEGIN_VCALENDAR = 'BEGIN:VCALENDAR';
    static $END_VCALENDAR   = 'END:VCALENDAR';
    static $ENDSHORTS       = ['END:VE', 'END:VF', 'END:VJ', 'END:VT'];
    static $BEGIN_VEVENT    = 'BEGIN:VEVENT';
    static $BEGIN_VFREEBUSY = 'BEGIN:VFREEBUSY';
    static $BEGIN_VJOURNAL  = 'BEGIN:VJOURNAL';
    static $BEGIN_VTODO     = 'BEGIN:VTODO';
    static $BEGIN_VTIMEZONE = 'BEGIN:VTIMEZONE';
    static $DBLS            = "\\";
    static $TRIMCHARS       = "\x00..\x1F";
    static $CALPROPNAMES    = null;
    static $VERSIONPRODID   = null;
    if( is_null( $CALPROPNAMES ))
      $CALPROPNAMES         = [util::$CALSCALE,
                               util::$METHOD,
                               util::$PRODID,
                               util::$VERSION];
    if( is_null( $VERSIONPRODID ))
      $VERSIONPRODID        = [util::$VERSION,
                               util::$PRODID];
    $arrParse = false;
    if( empty( $unparsedtext )) {
            /* directory+filename is set previously
               via setConfig url or directory+filename  */
      if(   false === ( $file = $this->getConfig( util::$URL ))) {
        if( false === ( $file = $this->getConfig( util::$DIRFILE )))
          return false;               /* err 1 */
        if( ! is_file( $file ))
          return false;               /* err 2 */
        if( ! is_readable( $file ))
          return false;               /* err 3 */
      }
      if( ! empty( $context ) && filter_var( $file, FILTER_VALIDATE_URL )) {
        If( false === ( $rows = file_get_contents( $file, false, $context )))
          return false;               /* err 6 */
      }
      elseif(  false === ( $rows = file_get_contents( $file )))
        return false;                 /* err 5 */
    } // end if( empty( $unparsedtext ))
    elseif( is_array( $unparsedtext )) {
      $rows = implode( $NLCHARS . util::$CRLF, $unparsedtext );
      $arrParse = true;
    }
    else
      $rows = $unparsedtext;
            /* fix line folding */
    $rows = util::convEolChar( $rows, util::$CRLF );
    if( $arrParse ) {
      foreach( $rows as $lix => $row )
        $rows[$lix] = util::trimTrailNL( $row );
    }
            /* skip leading (empty/invalid) lines
               (and remove leading BOM chars etc) */
    foreach( $rows as $lix => $row ) {
      if( false !== stripos( $row, $BEGIN_VCALENDAR )) {
        $rows[$lix] = $BEGIN_VCALENDAR;
        break;
      }
      unset( $rows[$lix] );
    }
    if( 3 > count( $rows ))           /* err 10 */
      return false;
            /* skip trailing empty lines and ensure an end row */
    $lix  = array_keys( $rows );
    $lix  = end( $lix );
    while( 3 < $lix ) {
      $tst = trim( $rows[$lix] );
      if(( $NLCHARS == $tst ) || empty( $tst )) {
        unset( $rows[$lix] );
        $lix--;
        continue;
      }
      if( false === stripos( $rows[$lix], $END_VCALENDAR ))
        $rows[] = $END_VCALENDAR;
      else
        $rows[$lix] = $END_VCALENDAR;
      break;
    }
    $comp    = $this;
    $calSync = $compSync = 0;
            /* identify components and update unparsed data for components */
    $compClosed = true; // used in case of missing END-comp-row
    $config = $this->getConfig();
    foreach( $rows as $lix => $row ) {
      switch( true ) {
        case ( 0 == strcasecmp( $BEGIN_VCALENDAR, substr( $row, 0, 15 ))) :
          $calSync++;
          break;
        case ( 0 == strcasecmp( $END_VCALENDAR, substr( $row, 0, 13 ))) :
          if( 0 < $compSync )
            $this->components[] = $comp;
          $compSync--;
          $calSync--;
          if( 0 != $calSync )
            return false;                 /* err 20 */
          break 2;
        case ( in_array( strtoupper( substr( $row, 0, 6 )), $ENDSHORTS )) :
          $this->components[] = $comp;
          $compSync--;
          $compClosed = true;
          break;
        case ( 0 == strcasecmp( $BEGIN_VEVENT, substr( $row, 0, 12 ))) :
          if( ! $compClosed ) {
            $this->components[] = $comp;
            $compSync--;
          }
          $comp = new vevent( $config );
          $compSync++;
          $compClosed = false;
          break;
        case ( 0 == strcasecmp( $BEGIN_VFREEBUSY, substr( $row, 0, 15 ))) :
          if( ! $compClosed ) {
            $this->components[] = $comp;
            $compSync--;
          }
          $comp = new vfreebusy( $config );
          $compSync++;
          $compClosed = false;
          break;
        case ( 0 == strcasecmp( $BEGIN_VJOURNAL, substr( $row, 0, 14 ))) :
          if( ! $compClosed ) {
            $this->components[] = $comp;
            $compSync--;
          }
          $comp = new vjournal( $config );
          $compSync++;
          $compClosed = false;
          break;
        case ( 0 == strcasecmp( $BEGIN_VTODO, substr( $row, 0, 11 ))) :
          if( ! $compClosed ) {
            $this->components[] = $comp;
            $compSync--;
          }
          $comp = new vtodo( $config );
          $compSync++;
          $compClosed = false;
          break;
        case ( 0 == strcasecmp( $BEGIN_VTIMEZONE, substr( $row, 0, 15 ))) :
          if( ! $compClosed ) {
            $this->components[] = $comp;
            $compSync--;
          }
          $comp = new vtimezone( $config );
          $compSync++;
          $compClosed = false;
          break;
        default : /* update component with unparsed data */
          $comp->unparsed[] = $row;
          break;
      } // switch( true )
    } // end foreach( $rows as $lix => $row )
            /* parse data for calendar (this) object */
    if( isset( $this->unparsed ) &&
     is_array( $this->unparsed ) &&
      ( count( $this->unparsed ) > 0 )) {
            /* concatenate property values spread over several rows */
      foreach( util::concatRows( $this->unparsed ) as $lx => $row ) {
            /* split property name  and  opt.params and value */
        list( $propName, $row ) = util::getPropName( $row );
        if( ! util::isXprefixed( $propName ) &&
         ! in_array( strtoupper( $propName ), $CALPROPNAMES ) && // skip non standard property names
           in_array( strtoupper( $propName ), $VERSIONPRODID ))  // ignore version/prodid properties
          continue;
            /* separate attributes from value */
        util::splitContent( $row, $propAttr );
            /* update Property */
        $this->setProperty( $propName,
                            util::strunrep( rtrim( $row, $TRIMCHARS )),
                            $propAttr );
      } // end foreach( $propRows as $lx => $row )
    } // end if( is_array( $this->unparsed.. .
    unset( $this->unparsed );
            /* parse Components */
    if( $this->countComponents() > 0 ) {
      foreach( $this->components as $ckey => $component ) {
        if( ! empty( $this->components[$ckey] ) &&
            ! empty( $this->components[$ckey]->unparsed )) {
          $this->components[$ckey]->parse();
        }
      }
    }
    else
      return false;                   /* err 91 or something.. . */
    return true;
  }
/**
 * Return formatted output for calendar object instance
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.07 - 2015-03-31
 * @return string
 */
  public function createCalendar() {
    static $BEGIN_VCALENDAR = "BEGIN:VCALENDAR\r\n";
    static $END_VCALENDAR   = "END:VCALENDAR\r\n";
    $calendar  = $BEGIN_VCALENDAR;
    $calendar .= $this->createVersion();
    $calendar .= $this->createProdid();
    $calendar .= $this->createCalscale();
    $calendar .= $this->createMethod();
    $calendar .= $this->createXprop();
    $config    = $this->getConfig();
    foreach( $this->components as $cix => $component ) {
      if( ! empty( $component )) {
        $this->components[$cix]->setConfig( $config, false, true );
        $calendar .= $this->components[$cix]->createComponent();
      }
    }
    return $calendar . $END_VCALENDAR;
  }
/**
 * Save calendar content in a file
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.5 - 2015-03-29
 * @return bool true on success, false on error
 */
  public function saveCalendar() {
    $output = $this->createCalendar();
    if( false === ( $dirfile = $this->getConfig( util::$URL )))
      $dirfile = $this->getConfig( util::$DIRFILE );
    return ( false === file_put_contents( $dirfile, $output, LOCK_EX )) ? false : true;
  }
/**
 * Return created, updated and/or parsed calendar,
 * sending a HTTP redirect header.
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.23.6 - 2017-04-13
 * @param bool $utf8Encode
 * @param bool $gzip
 * @param bool $cdType       true : Content-Disposition: attachment... (default), false : ...inline...
 * @return bool true on success, false on error
 */
  public function returnCalendar( $utf8Encode=false, $gzip=false, $cdType=true ) {
    return utilRedirect::returnCalendar( $this,
                                         $utf8Encode,
                                         $gzip,
                                         $cdType );
  }
/**
 * If recent version of calendar file exists (default one hour), an HTTP redirect header is sent
 * else false is returned.
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.23.6 - 2017-04-13
 * @param int  $timeout  default 3600 sec
 * @param bool $cdType   true : Content-Disposition: attachment... (default), false : ...inline...
 * @return bool true on success, false on error
 */
  public function useCachedCalendar( $timeout=3600, $cdType=true ) {
    return utilRedirect::useCachedCalendar( $this,
                                            $timeout,
                                            $cdType );
  }
}
