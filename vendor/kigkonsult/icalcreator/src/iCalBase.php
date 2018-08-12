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
 *         Do NOT alter or remove the constant!!
 */
if( ! defined( 'ICALCREATOR_VERSION' ))
  define( 'ICALCREATOR_VERSION', 'iCalcreator 2.24' );
/**
 * iCalcreator base class
 *
 * Properties and methods shared by vcalendar and calendarComponents
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.20 - 2017-01-30
 */
abstract class iCalBase {
  use traits\X_PROPtrait;
/**
 * @var array container for sub-components
 * @access protected
 */
  protected $components = [];
/**
 * @var array $unparsed  calendar/components in 'raw' text...
 * @access protected
 */
  protected $unparsed = null;
/**
 *  @var array $config  configuration
 *  @access protected
 */
  protected $config = [];
/**
 * @var int component index
 *  @access protected
 */
  protected $compix    = 0;
/**
 * @var array get multi property index
 *  @access protected
 */
  protected $propix    = [];
/**
 * @var array delete multi property index
 *  @access protected
 */
  protected $propdelix = [];
/**
 * __clone method
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.23.12 - 2017-04-20
 */
  public function __clone() {
    foreach( $this->components as $cix => $component )
      $this->components[$cix] = clone $component;
    if( isset( $this->compix ))
      $this->compix = [];
    if( isset( $this->propix ))
      $this->propix = [];
    if( isset( $this->propdelix ))
      $this->propdelix = [];
  }
/**
 * Return config value or info about subcomponents, false on not found
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.23 - 2017-02-02
 * @param mixed $config
 * @return mixed
 */
  public function getConfig( $config = false) {
    static $LCORDNO     = 'ordno';
    static $LCTYPE      = 'type';
    static $LCUID       = 'uid';
    static $LCPROPS     = 'props';
    static $LCSUB       = 'sub';
    if( empty( $config )) {
      $return = [];
      $return[util::$ALLOWEMPTY]  = $this->getConfig( util::$ALLOWEMPTY );
      if( false !== ( $lang       = $this->getConfig( util::$LANGUAGE )))
        $return[util::$LANGUAGE]  = $lang;
      $return[util::$TZID]        = $this->getConfig( util::$TZID );
      $return[util::$UNIQUE_ID]   = $this->getConfig( util::$UNIQUE_ID );
      return $return;
    }
    switch( strtoupper( $config )) {
      case util::$ALLOWEMPTY:
        if( isset( $this->config[util::$ALLOWEMPTY] ))
          return $this->config[util::$ALLOWEMPTY];
        break;
      case util::$COMPSINFO:
        unset( $this->compix );
        $info = [];
        if( ! empty( $this->components )) {
          foreach( $this->components as $cix => $component ) {
            if( empty( $component ))
              continue;
            $info[$cix][$LCORDNO] = $cix + 1;
            $info[$cix][$LCTYPE]  = $component->objName;
            $info[$cix][$LCUID]   = $component->getProperty( util::$UID );
            $info[$cix][$LCPROPS] = $component->getConfig( util::$PROPINFO );
            $info[$cix][$LCSUB]   = $component->getConfig( util::$COMPSINFO );
          }
        }
        return $info;
        break;
      case util::$LANGUAGE: // get language for calendar component as defined in [RFC 1766]
        if( isset( $this->config[util::$LANGUAGE] ))
          return $this->config[util::$LANGUAGE];
        break;
      case util::$PROPINFO:
        $output = [];
        if( ! in_array( $this->objName, util::$LCSUBCOMPS )) {
          if( empty( $this->uid ))
            $this->uid     = util::makeUid( $this->getConfig( util::$UNIQUE_ID ));

                                               $output[util::$UID]              = 1;
          if( empty( $this->dtstamp ))
            $this->dtstamp = util::makeDtstamp();
                                               $output[util::$DTSTAMP]          = 1;
        }
        if( ! empty( $this->summary ))         $output[util::$SUMMARY]          = 1;
        if( ! empty( $this->description ))     $output[util::$DESCRIPTION]      = count( $this->description );
        if( ! empty( $this->dtstart ))         $output[util::$DTSTART]          = 1;
        if( ! empty( $this->dtend ))           $output[util::$DTEND]            = 1;
        if( ! empty( $this->due ))             $output[util::$DUE]              = 1;
        if( ! empty( $this->duration ))        $output[util::$DURATION]         = 1;
        if( ! empty( $this->rrule ))           $output[util::$RRULE]            = count( $this->rrule );
        if( ! empty( $this->rdate ))           $output[util::$RDATE]            = count( $this->rdate );
        if( ! empty( $this->exdate ))          $output[util::$EXDATE]           = count( $this->exdate );
        if( ! empty( $this->exrule ))          $output[util::$EXRULE]           = count( $this->exrule );
        if( ! empty( $this->action ))          $output[util::$ACTION]           = 1;
        if( ! empty( $this->attach ))          $output[util::$ATTACH]           = count( $this->attach );
        if( ! empty( $this->attendee ))        $output[util::$ATTENDEE]         = count( $this->attendee );
        if( ! empty( $this->categories ))      $output[util::$CATEGORIES]       = count( $this->categories );
        if( ! empty( $this->class ))           $output[util::$CLASS]            = 1;
        if( ! empty( $this->comment ))         $output[util::$COMMENT]          = count( $this->comment );
        if( ! empty( $this->completed ))       $output[util::$COMPLETED]        = 1;
        if( ! empty( $this->contact ))         $output[util::$CONTACT]          = count( $this->contact );
        if( ! empty( $this->created ))         $output[util::$CREATED]          = 1;
        if( ! empty( $this->freebusy ))        $output[util::$FREEBUSY]         = count( $this->freebusy );
        if( ! empty( $this->geo ))             $output[util::$GEO]              = 1;
        if( ! empty( $this->lastmodified ))    $output[util::$LAST_MODIFIED]    = 1;
        if( ! empty( $this->location ))        $output[util::$LOCATION]         = 1;
        if( ! empty( $this->organizer ))       $output[util::$ORGANIZER]        = 1;
        if( ! empty( $this->percentcomplete )) $output[util::$PERCENT_COMPLETE] = 1;
        if( ! empty( $this->priority ))        $output[util::$PRIORITY]         = 1;
        if( ! empty( $this->recurrenceid ))    $output[util::$RECURRENCE_ID]    = 1;
        if( ! empty( $this->relatedto ))       $output[util::$RELATED_TO]       = count( $this->relatedto );
        if( ! empty( $this->repeat ))          $output[util::$REPEAT]           = 1;
        if( ! empty( $this->requeststatus ))   $output[util::$REQUEST_STATUS]   = count( $this->requeststatus );
        if( ! empty( $this->resources ))       $output[util::$RESOURCES]        = count( $this->resources );
        if( ! empty( $this->sequence ))        $output[util::$SEQUENCE]         = 1;
        if( ! empty( $this->status ))          $output[util::$STATUS]           = 1;
        if( ! empty( $this->transp ))          $output[util::$TRANSP]           = 1;
        if( ! empty( $this->trigger ))         $output[util::$TRIGGER]          = 1;
        if( ! empty( $this->tzid ))            $output[util::$TZID]             = 1;
        if( ! empty( $this->tzname ))          $output[util::$TZNAME]           = count( $this->tzname );
        if( ! empty( $this->tzoffsetfrom ))    $output[util::$TZOFFSETFROM]     = 1;
        if( ! empty( $this->tzoffsetto ))      $output[util::$TZOFFSETTO]       = 1;
        if( ! empty( $this->tzurl ))           $output[util::$TZURL]            = 1;
        if( ! empty( $this->url ))             $output[util::$URL]              = 1;
        if( ! empty( $this->xprop ))           $output[util::$X_PROP]           = count( $this->xprop );
        return $output;
        break;
      case util::$SETPROPERTYNAMES:
        return array_keys( $this->getConfig( util::$PROPINFO ));
        break;
      case util::$TZID:
        if( isset( $this->config[util::$TZID] ))
          return $this->config[util::$TZID];
        break;
      case util::$UNIQUE_ID:
        if( empty( $this->config[util::$UNIQUE_ID] ))
          $this->config[util::$UNIQUE_ID] = ( isset( $_SERVER[util::$SERVER_NAME] ))
                                          ? gethostbyname( $_SERVER[util::$SERVER_NAME] )
                                          : util::$LOCALHOST;
        return $this->config[util::$UNIQUE_ID];
        break;
    }
    return false;
  }
/**
 * General component config setting
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.23.12 - 2017-04-22
 * @param mixed   $config
 * @param string  $value
 * @param bool    $softUpdate
 * @return bool   true on success
 */
  public function setConfig( $config, $value=null, $softUpdate=null ) {
    if( is_null( $softUpdate ))
      $softUpdate = false;
    if( is_array( $config )) {
      $config  = array_change_key_case( $config, CASE_UPPER );
      foreach( $config as $cKey => $cValue ) {
        if( false === $this->setConfig( $cKey, $cValue, $softUpdate ))
          return false;
      }
      return true;
    }
    $res = false;
    switch( strtoupper( $config )) {
      case util::$ALLOWEMPTY:
        $this->config[util::$ALLOWEMPTY] = $value;
        $subcfg = [util::$ALLOWEMPTY => $value];
        $res    = true;
        break;
      case util::$LANGUAGE: // set language for component as defined in [RFC 1766]
        $value  = trim( $value );
        if( empty( $this->config[util::$LANGUAGE] ) || ! $softUpdate )
          $this->config[util::$LANGUAGE] = $value;
        $subcfg = [util::$LANGUAGE => $value];
        $res    = true;
        break;
      case util::$TZID:
        $this->config[util::$TZID] = trim( $value );
        $subcfg = [util::$TZID => trim( $value )];
        $res    = true;
        break;
      case util::$UNIQUE_ID:
        $value  = trim( $value );
        $this->config[util::$UNIQUE_ID] = $value;
        $subcfg = [util::$UNIQUE_ID => $value];
        $res    = true;
        break;
      default:  // any unvalid config key.. .
        return true;
    }
    if( ! $res )
      return false;
    if( isset( $subcfg ) && ! empty( $this->components )) {
      foreach( $subcfg as $cfgkey => $cfgvalue ) {
        foreach( $this->components as $cix => $component ) {
          $res = $this->components[$cix]->setConfig( $cfgkey, $cfgvalue, $softUpdate );
          if( ! $res )
            break 2;
        }
      }
    }
    return $res;
  }
/**
 * Return number of components
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.23.5 - 2017-04-13
 * @return int
 */
  public function countComponents() {
    return ( empty( $this->components )) ? 0 : count( $this->components );
  }
/**
 * Return new calendar component, included in calendar or component
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.20 - 2017-04-13
 * @param string $compType component type
 * @return calendarComponent
 */
  public function newComponent( $compType ) {
    $config = $this->getConfig();
    $ix     = ( empty( $this->components ))
            ? 0
            : key( array_slice( $this->components, -1, 1, TRUE )) + 1;
    switch( strtolower( $compType )) {
      case util::$LCVALARM :
        $this->components[$ix] = new valarm( $config );
        break;
      case util::$LCVEVENT :
        $this->components[$ix] = new vevent( $config );
        break;
      case util::$LCVTODO :
        $this->components[$ix] = new vtodo( $config );
        break;
      case util::$LCVJOURNAL :
        $this->components[$ix] = new vjournal( $config );
        break;
      case util::$LCVFREEBUSY :
        $this->components[$ix] = new vfreebusy( $config );
        break;
      case util::$LCVTIMEZONE :
        array_unshift( $this->components, new vtimezone( $config ));
        $ix = 0;
        break;
      case util::$LCSTANDARD :
        array_unshift( $this->components, new vtimezone( util::$LCSTANDARD, $config ));
        $ix = 0;
        break;
      case util::$LCDAYLIGHT :
        $this->components[$ix] = new vtimezone( util::$LCDAYLIGHT, $config );
        break;
      default:
        return false;
    }
    return $this->components[$ix];
  }
/**
 * Delete calendar subcomponent from component container
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.23.12 - 2017-05-06
 * @param mixed  $arg1 ordno / component type / component uid
 * @param mixed  $arg2 ordno if arg1 = component type
 * @return bool  true on success
 */
  public function deleteComponent( $arg1, $arg2=false  ) {
    static $INDEX = 'INDEX';
    if( ! isset( $this->components ))
      return false;
    $argType = $index = null;
    if ( ctype_digit( (string) $arg1 )) {
      $argType = $INDEX;
      $index   = (int) $arg1 - 1;
    }
    elseif( in_array( strtolower( $arg1 ), util::$ALLCOMPS )) {
      $argType = strtolower( $arg1 );
      $index   = ( ! empty( $arg2 ) && ctype_digit( (string) $arg2 )) ? (( int ) $arg2 - 1 ) : 0;
    }
    $cix2dC    = 0;
    $remove    = false;
    foreach( $this->components as $cix => $component ) {
      if(( $INDEX == $argType ) && ( $index == $cix )) {
        unset( $this->components[$cix] );
        $remove = true;
        break;
      }
      elseif( $argType == $component->objName ) {
        if( $index == $cix2dC ) {
          unset( $this->components[$cix] );
          $remove = true;
          break;
        }
        $cix2dC++;
      }
      elseif( ! $argType &&
            ( $arg1 == $component->getProperty( util::$UID ))) {
        unset( $this->components[$cix] );
        $remove = true;
        break;
      }
    } // end foreach( $this->components as $cix => $component )
    if( $remove ) {
      $this->components = array_filter( $this->components );
      return true;
    }
    return false;
  }
/**
 * Add calendar component as subcomponent to container for subcomponents
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.23.2 - 2015-03-18
 * @param object  $component calendarComponent
 * @param mixed   $arg1      ordno/component type/ component uid
 * @param mixed   $arg2      ordno if arg1 = component type
 * @return bool
 */
  public function setComponent( $component, $arg1=false, $arg2=false  ) {
    static $INDEX    = 'INDEX';
    if( ! isset( $this->components ))
      return false;
    $component->setConfig( $this->getConfig(), false, true );
    if( ! in_array( strtolower( $component->objName ), util::$LCSUBCOMPS )) {
            /* make sure dtstamp and uid is set */
      $component->getProperty( util::$DTSTAMP );
      $component->getProperty( util::$UID );
    }
    if( ! $arg1 ) { // plain insert, last in chain
      $this->components[] = clone $component;
      return true;
    }
    $argType = $index = null;
    if ( ctype_digit( (string) $arg1 )) { // index insert/replace
      $argType = $INDEX;
      $index   = (int) $arg1 - 1;
    }
    elseif( in_array( strtolower( $arg1 ), util::$MCOMPS )) {
      $argType = strtolower( $arg1 );
      $index = ( ctype_digit( (string) $arg2 )) ? ((int) $arg2) - 1 : 0;
    }
    // else if arg1 is set, arg1 must be an UID
    $cix2sC = 0;
    foreach( $this->components as $cix => $component2 ) {
      if( empty( $component2 ))
        continue;
      if(( $INDEX == $argType ) && ( $index == $cix )) { // index insert/replace
        $this->components[$cix] = clone $component;
        return true;
      }
      elseif( $argType == $component2->objName ) {       // component Type index insert/replace
        if( $index == $cix2sC ) {
          $this->components[$cix] = clone $component;
          return true;
        }
        $cix2sC++;
      }
      elseif( ! $argType && ( $arg1 == $component2->getProperty( util::$UID ))) {
        $this->components[$cix] = clone $component;      // UID insert/replace
        return true;
      }
    }
            /* arg1=index and not found.. . insert at index .. .*/
    if( $INDEX == $argType ) {
      $this->components[$index] = clone $component;
      ksort( $this->components, SORT_NUMERIC );
    }
    else    /* not found.. . insert last in chain anyway .. .*/
    $this->components[] = clone $component;
    return true;
  }
}
