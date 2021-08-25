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
/**
 * iCalcreator XML (rfc6321) support class
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.20.23 - 2017-02-25
 */
class iCalXML {
  private static $vcalendar      = 'vcalendar';
  private static $calProps       = ['version',
                                    'prodid',
                                    'calscale',
                                    'method'];
  private static $properties     = 'properties';
  private static $PARAMETERS     = 'parameters';
  private static $components     = 'components';
  private static $text           = 'text';
  private static $binary         = 'binary';
  private static $uri            = 'uri';
  private static $date           = 'date';
  private static $date_time      = 'date-time';
  private static $fbtype         = 'fbtype';
  private static $FBTYPE         = 'FBTYPE';
  private static $period         = 'period';
  private static $rstatus        = 'rstatus';
  private static $unknown        = 'unknown';
  private static $recur          = 'recur';
  private static $cal_address    = 'cal-address';
  private static $integer        = 'integer';
  private static $relatedStart   = 'relatedStart';
  private static $RELATED        = 'RELATED';
  private static $END            = 'END';
  private static $utc_offset     = 'utc-offset';
  private static $altrep         = 'altrep';
  private static $dir            = 'dir';
  private static $delegated_from = 'delegated-from';
  private static $delegated_to   = 'delegated-to';
  private static $member         = 'member';
  private static $sent_by        = 'sent-by';
  private static $rsvp           = 'rsvp';
  private static $bysecond       = 'bysecond';
  private static $byminute       = 'byminute';
  private static $byhour         = 'byhour';
  private static $bymonthday     = 'bymonthday';
  private static $byyearday      = 'byyearday';
  private static $byweekno       = 'byweekno';
  private static $bymonth        = 'bymonth';
  private static $bysetpos       = 'bysetpos';
  private static $byday          = 'byday';
  private static $freq           = 'freq';
  private static $count          = 'count';
  private static $interval       = 'interval';
  private static $wkst           = 'wkst';
  private static $code           = 'code';
  private static $statcode       = 'statcode';
  private static $extdata        = 'extdata';
  private static $data           = 'data';
  private static $time           = 'time';
  private static $latitude       = 'latitude';
  private static $longitude      = 'longitude';
/**
 * Return iCal XML (rfc6321) output, using PHP SimpleXMLElement
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.18.1 - 2013-08-18
 * @param vcalendar $calendar   iCalcreator vcalendar instance reference
 * @return string
 * @static
 */
 public static function iCal2XML( vcalendar $calendar ) {
  static $YMDTHISZ = 'Ymd\THis\Z';
  static $XMLstart = '<?xml version="1.0" encoding="utf-8"?><icalendar xmlns="urn:ietf:params:xml:ns:icalendar-2.0"><!-- created %s using kigkonsult.se %s iCal2XMl (rfc6321) --></icalendar>';
            /** fix an SimpleXMLElement instance and create root element */
  $xml          = new \SimpleXMLElement( sprintf( $XMLstart, gmdate( $YMDTHISZ ),
                                                             ICALCREATOR_VERSION ));
  $vcalendar    = $xml->addChild( self::$vcalendar );
            /** fix calendar properties */
  $properties   = $vcalendar->addChild( self::$properties );
  foreach( self::$calProps as $calProp ) {
    if( false !== ( $content = $calendar->getProperty( $calProp )))
      self::addXMLchild( $properties,
                         $calProp,
                         self::$text,
                         $content );
  }
  while( false !== ( $content = $calendar->getProperty( false, false, true )))
    self::addXMLchild( $properties,
                       $content[0],
                       self::$unknown,
                       $content[1][util::$LCvalue],
                       $content[1][util::$LCparams] );
  $langCal = $calendar->getConfig( util::$LANGUAGE );
            /** prepare to fix components with properties */
  $components   = $vcalendar->addChild( self::$components );
            /** fix component properties */
  while( false !== ( $component = $calendar->getComponent())) {
    $compName   = $component->objName;
    $child      = $components->addChild( $compName );
    $properties = $child->addChild( self::$properties );
    $langComp   = $component->getConfig( util::$LANGUAGE );
    $props      = $component->getConfig( util::$SETPROPERTYNAMES );
    foreach( $props as $pix => $prop ) {
      switch( strtoupper( $prop )) {
        case util::$ATTACH:          // may occur multiple times, below
          while( false !== ( $content = $component->getProperty( $prop,
                                                                 false,
                                                                 true ))) {
            $type = ( util::isParamsValueSet( $content, util::$BINARY ))
                  ? self::$binary : self::$uri;
            unset( $content[util::$LCparams][util::$VALUE] );
            self::addXMLchild( $properties,
                               $prop,
                               $type,
                               $content[util::$LCvalue],
                               $content[util::$LCparams] );
          }
          break;
        case util::$ATTENDEE:
          while( false !== ( $content = $component->getProperty( $prop,
                                                                 false,
                                                                 true ))) {
            if( isset( $content[util::$LCparams][util::$CN] ) &&
              ! isset( $content[util::$LCparams][util::$LANGUAGE] )) {
              if( $langComp )
                $content[util::$LCparams][util::$LANGUAGE] = $langComp;
              elseif( $langCal )
                $content[util::$LCparams][util::$LANGUAGE] = $langCal;
            }
            self::addXMLchild( $properties,
                               $prop,
                               self::$cal_address,
                               $content[util::$LCvalue],
                               $content[util::$LCparams] );
          }
          break;
        case util::$EXDATE:
          while( false !== ( $content = $component->getProperty( $prop,
                                                                 false,
                                                                 true ))) {
            $type = ( util::isParamsValueSet( $content, util::$DATE ))
                  ? self::$date : self::$date_time;
            unset( $content[util::$LCparams][util::$VALUE] );
            self::addXMLchild( $properties,
                               $prop,
                               $type,
                               $content[util::$LCvalue],
                               $content[util::$LCparams] );
          }
          break;
        case util::$FREEBUSY:
          while( false !== ( $content = $component->getProperty( $prop,
                                                                 false,
                                                                 true ))) {
            if( is_array( $content ) &&
                isset( $content[util::$LCvalue][self::$fbtype] )) {
              $content[util::$LCparams][self::$FBTYPE] = $content[util::$LCvalue][self::$fbtype];
              unset( $content[util::$LCvalue][self::$fbtype] );
            }
            self::addXMLchild( $properties,
                               $prop,
                               self::$period,
                               $content[util::$LCvalue],
                               $content[util::$LCparams] );
          }
          break;
        case util::$REQUEST_STATUS:
          while( false !== ( $content = $component->getProperty( $prop,
                                                                 false,
                                                                 true ))) {
            if( ! isset( $content[util::$LCparams][util::$LANGUAGE] )) {
              if( $langComp )
                $content[util::$LCparams][util::$LANGUAGE] = $langComp;
              elseif( $langCal )
                $content[util::$LCparams][util::$LANGUAGE] = $langCal;
            }
            self::addXMLchild( $properties,
                               $prop,
                               self::$rstatus,
                               $content[util::$LCvalue],
                               $content[util::$LCparams] );
          }
          break;
        case util::$RDATE:
          while( false !== ( $content = $component->getProperty( $prop,
                                                                 false,
                                                                 true ))) {
            $type = self::$date_time;
            if(     util::isParamsValueSet( $content, util::$DATE ))
              $type = self::$date;
            elseif( util::isParamsValueSet( $content, util::$PERIOD ))
              $type = self::$period;
            unset( $content[util::$LCparams][util::$VALUE] );
            self::addXMLchild( $properties,
                               $prop,
                               $type,
                               $content[util::$LCvalue],
                               $content[util::$LCparams] );
          }
          break;
        case util::$CATEGORIES:
        case util::$COMMENT:
        case util::$CONTACT:
        case util::$DESCRIPTION:
        case util::$RELATED_TO:
        case util::$RESOURCES:
          while( false !== ( $content = $component->getProperty( $prop,
                                                                 false,
                                                                 true ))) {
            if(( util::$RELATED_TO != $prop ) &&
                ! isset( $content[util::$LCparams][util::$LANGUAGE] )) {
              if( $langComp )
                $content[util::$LCparams][util::$LANGUAGE] = $langComp;
              elseif( $langCal )
                $content[util::$LCparams][util::$LANGUAGE] = $langCal;
            }
            self::addXMLchild( $properties,
                               $prop,
                               self::$text,
                               $content[util::$LCvalue],
                               $content[util::$LCparams] );
          }
          break;
        case util::$X_PROP:
          while( false !== ( $content = $component->getProperty( $prop,
                                                                 false,
                                                                 true )))
            self::addXMLchild( $properties,
                               $content[0],
                               self::$unknown,
                               $content[1][util::$LCvalue],
                               $content[1][util::$LCparams] );
          break;
        case util::$CREATED:         // single occurence below, if set
        case util::$COMPLETED:
        case util::$DTSTAMP:
        case util::$LAST_MODIFIED:
        case util::$DTSTART:
        case util::$DTEND:
        case util::$DUE:
        case util::$RECURRENCE_ID:
          if( false !== ( $content = $component->getProperty( $prop,
                                                              false,
                                                              true ))) {
            $type = ( util::isParamsValueSet( $content, util::$DATE ))
                  ? self::$date : self::$date_time;
            unset( $content[util::$LCparams][util::$VALUE] );
            if(( isset( $content[util::$LCparams][util::$TZID] ) &&
                 empty( $content[util::$LCparams][util::$TZID] )) ||
              @is_null( $content[util::$LCparams][util::$TZID] ))
              unset( $content[util::$LCparams][util::$TZID] );
            self::addXMLchild( $properties,
                               $prop,
                               $type,
                               $content[util::$LCvalue],
                               $content[util::$LCparams] );
          }
          break;
        case util::$DURATION:
          if( false !== ( $content = $component->getProperty( $prop,
                                                              false,
                                                              true )))
            self::addXMLchild( $properties,
                               $prop,
                               strtolower( util::$DURATION ),
                               $content[util::$LCvalue],
                               $content[util::$LCparams] );
          break;
        case util::$EXRULE:
        case util::$RRULE:
          while( false !== ( $content = $component->getProperty( $prop,
                                                                 false,
                                                                 true )))
            self::addXMLchild( $properties,
                               $prop,
                               self::$recur,
                               $content[util::$LCvalue],
                               $content[util::$LCparams] );
          break;
        case util::$CLASS:
        case util::$LOCATION:
        case util::$STATUS:
        case util::$SUMMARY:
        case util::$TRANSP:
        case util::$TZID:
        case util::$UID:
          if( false !== ( $content = $component->getProperty( $prop,
                                                              false,
                                                              true ))) {
            if((( util::$LOCATION == $prop ) ||
                ( util::$SUMMARY == $prop )) &&
                 ! isset( $content[util::$LCparams][util::$LANGUAGE] )) {
              if( $langComp )
                $content[util::$LCparams][util::$LANGUAGE] = $langComp;
              elseif( $langCal )
                $content[util::$LCparams][util::$LANGUAGE] = $langCal;
            }
            self::addXMLchild( $properties,
                               $prop,
                               self::$text,
                               $content[util::$LCvalue],
                               $content[util::$LCparams] );
          }
          break;
        case util::$GEO:
          if( false !== ( $content = $component->getProperty( $prop,
                                                              false,
                                                              true )))
            self::addXMLchild( $properties,
                               $prop,
                               strtolower( util::$GEO ),
                               $content[util::$LCvalue],
                               $content[util::$LCparams] );
          break;
        case util::$ORGANIZER:
          if( false !== ( $content = $component->getProperty( $prop,
                                                              false,
                                                              true ))) {
            if( isset( $content[util::$LCparams][util::$CN] ) &&
              ! isset( $content[util::$LCparams][util::$LANGUAGE] )) {
              if( $langComp )
                $content[util::$LCparams][util::$LANGUAGE] = $langComp;
              elseif( $langCal )
                $content[util::$LCparams][util::$LANGUAGE] = $langCal;
            }
            self::addXMLchild( $properties,
                               $prop,
                               self::$cal_address,
                               $content[util::$LCvalue],
                               $content[util::$LCparams] );
          }
          break;
        case util::$PERCENT_COMPLETE:
        case util::$PRIORITY:
        case util::$SEQUENCE:
          if( false !== ( $content = $component->getProperty( $prop,
                                                              false,
                                                              true )))
            self::addXMLchild( $properties,
                               $prop,
                               self::$integer,
                               $content[util::$LCvalue],
                               $content[util::$LCparams] );
          break;
        case util::$TZURL:
        case util::$URL:
          if( false !== ( $content = $component->getProperty( $prop,
                                                              false,
                                                              true )))
            self::addXMLchild( $properties,
                               $prop,
                               self::$uri,
                               $content[util::$LCvalue],
                               $content[util::$LCparams] );
          break;
      } // end switch( $prop )
    } // end foreach( $props as $pix => $prop )
            /** fix subComponent properties, if any */
    while( false !== ( $subcomp = $component->getComponent())) {
      $subCompName  = $subcomp->objName;
      $child2       = $child->addChild( $subCompName );
      $properties   = $child2->addChild( self::$properties );
      $langComp     = $subcomp->getConfig( util::$LANGUAGE );
      $subCompProps = $subcomp->getConfig( util::$SETPROPERTYNAMES );
      foreach( $subCompProps as $pix2 => $prop ) {
        switch( strtoupper( $prop )) {
          case util::$ATTACH:          // may occur multiple times, below
            while( false !== ( $content = $subcomp->getProperty( $prop,
                                                                 false,
                                                                 true ))) {
              $type = ( util::isParamsValueSet( $content, util::$BINARY ))
                    ? self::$binary : self::$uri;
              unset( $content[util::$LCparams][util::$VALUE] );
              self::addXMLchild( $properties,
                                 $prop,
                                 $type,
                                 $content[util::$LCvalue],
                                 $content[util::$LCparams] );
            }
            break;
          case util::$ATTENDEE:
            while( false !== ( $content = $subcomp->getProperty( $prop,
                                                                 false,
                                                                 true ))) {
              if( isset( $content[util::$LCparams][util::$CN] ) &&
                ! isset( $content[util::$LCparams][util::$LANGUAGE] )) {
                if( $langComp )
                  $content[util::$LCparams][util::$LANGUAGE] = $langComp;
                elseif( $langCal )
                  $content[util::$LCparams][util::$LANGUAGE] = $langCal;
              }
              self::addXMLchild( $properties,
                                 $prop,
                                 self::$cal_address,
                                 $content[util::$LCvalue],
                                 $content[util::$LCparams] );
            }
            break;
          case util::$COMMENT:
          case util::$TZNAME:
            while( false !== ( $content = $subcomp->getProperty( $prop,
                                                                 false,
                                                                 true ))) {
              if( ! isset( $content[util::$LCparams][util::$LANGUAGE] )) {
                if( $langComp )
                  $content[util::$LCparams][util::$LANGUAGE] = $langComp;
                elseif( $langCal )
                  $content[util::$LCparams][util::$LANGUAGE] = $langCal;
              }
              self::addXMLchild( $properties,
                                 $prop,
                                 self::$text,
                                 $content[util::$LCvalue],
                                 $content[util::$LCparams] );
            }
            break;
          case util::$RDATE:
            while( false !== ( $content = $subcomp->getProperty( $prop,
                                                                 false,
                                                                 true ))) {
              $type = self::$date_time;
              if( isset( $content[util::$LCparams][util::$VALUE] )) {
                if(     util::isParamsValueSet( $content, util::$DATE ))
                  $type = self::$date;
                elseif( util::isParamsValueSet( $content, util::$PERIOD ))
                  $type = self::$period;
              }
              unset( $content[util::$LCparams][util::$VALUE] );
              self::addXMLchild( $properties,
                                 $prop,
                                 $type,
                                 $content[util::$LCvalue],
                                 $content[util::$LCparams] );
            }
            break;
          case util::$X_PROP:
            while( false !== ( $content = $subcomp->getProperty( $prop,
                                                                 false,
                                                                 true )))
              self::addXMLchild( $properties,
                                 $content[0],
                                 self::$unknown,
                                 $content[1][util::$LCvalue],
                                 $content[1][util::$LCparams] );
            break;
          case util::$ACTION:      // single occurence below, if set
          case util::$DESCRIPTION:
          case util::$SUMMARY:
            if( false !== ( $content = $subcomp->getProperty( $prop,
                                                              false,
                                                              true ))) {
              if(( util::$ACTION != $prop ) &&
                  ! isset( $content[util::$LCparams][util::$LANGUAGE] )) {
                if( $langComp )
                  $content[util::$LCparams][util::$LANGUAGE] = $langComp;
                elseif( $langCal )
                  $content[util::$LCparams][util::$LANGUAGE] = $langCal;
              }
              self::addXMLchild( $properties,
                                 $prop,
                                 self::$text,
                                 $content[util::$LCvalue],
                                 $content[util::$LCparams] );
            }
            break;
          case util::$DTSTART:
            if( false !== ( $content = $subcomp->getProperty( $prop,
                                                              false,
                                                              true ))) {
              unset( $content[util::$LCvalue][util::$LCtz],
                     $content[util::$LCparams][util::$VALUE] ); // always local time
              self::addXMLchild( $properties,
                                 $prop,
                                 self::$date_time,
                                 $content[util::$LCvalue],
                                 $content[util::$LCparams] );
            }
            break;
          case util::$DURATION:
            if( false !== ( $content = $subcomp->getProperty( $prop,
                                                              false,
                                                              true )))
              self::addXMLchild( $properties,
                                 $prop,
                                 strtolower( util::$DURATION ),
                                 $content[util::$LCvalue],
                                 $content[util::$LCparams] );
            break;
          case util::$REPEAT:
            if( false !== ( $content = $subcomp->getProperty( $prop,
                                                              false,
                                                              true )))
              self::addXMLchild( $properties,
                                 $prop,
                                 self::$integer,
                                 $content[util::$LCvalue],
                                 $content[util::$LCparams] );
            break;
          case util::$TRIGGER:
            if( false !== ( $content = $subcomp->getProperty( $prop,
                                                              false,
                                                              true ))) {
              if( isset( $content[util::$LCvalue][util::$LCYEAR] )   &&
                  isset( $content[util::$LCvalue][util::$LCMONTH] )  &&
                  isset( $content[util::$LCvalue][util::$LCDAY] ))
                $type = self::$date_time;
              else {
                $type = strtolower( util::$DURATION );
                if( ! isset( $content[util::$LCvalue][self::$relatedStart] ) ||
                  ( true !== $content[util::$LCvalue][self::$relatedStart] ))
                  $content[util::$LCparams][self::$RELATED] = self::$END;
              }
              self::addXMLchild( $properties,
                                 $prop,
                                 $type,
                                 $content[util::$LCvalue],
                                 $content[util::$LCparams] );
            }
            break;
          case util::$TZOFFSETFROM:
          case util::$TZOFFSETTO:
            if( false !== ( $content = $subcomp->getProperty( $prop,
                                                              false,
                                                              true )))
              self::addXMLchild( $properties,
                                 $prop,
                                 self::$utc_offset,
                                 $content[util::$LCvalue],
                                 $content[util::$LCparams] );
            break;
          case util::$RRULE:
            while( false !== ( $content = $subcomp->getProperty( $prop,
                                                                 false,
                                                                 true )))
              self::addXMLchild( $properties,
                                 $prop,
                                 self::$recur,
                                 $content[util::$LCvalue],
                                 $content[util::$LCparams] );
            break;
        } // switch( $prop )
      } // end foreach( $subCompProps as $pix2 => $prop )
    } // end while( false !== ( $subcomp = $component->getComponent()))
  } // end while( false !== ( $component = $calendar->getComponent()))
  return $xml->asXML();
 }
/**
 * Add XML (rfc6321) children to a SimpleXMLelement
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.23.8 - 2017-04-17
 * @param SimpleXMLElement $parent   a SimpleXMLelement node
 * @param string $name     new element node name
 * @param string $type     content type, subelement(-s) name
 * @param string $content  new subelement content
 * @param array  $params   new element 'attributes'
 * @access private
 * @static
 */
 private static function addXMLchild( \SimpleXMLElement & $parent, $name, $type, $content, $params=[] ) {
  static $FMTYMD    = '%04d-%02d-%02d';
  static $FMTYMDHIS = '%04d-%02d-%02dT%02d:%02d:%02d';
  static $PLUSMINUSARR = ['+', '-'];
  static $BOOLEAN   = 'boolean';
  static $UNTIL     = 'until';
  static $START     = 'start';
  static $END       = 'end';
  static $BEFORE    = 'before';
  static $SP0       = '';
            /** create new child node */
  $name  = strtolower( $name );
  $child = $parent->addChild( $name );
  if( ! empty( $params )) {
    $parameters = $child->addChild( self::$PARAMETERS );
    foreach( $params as $param => $parVal ) {
      if( util::$VALUE == $param ) {
        if( strtolower( $type ) != strtolower( $parVal ))
          $type = strtolower( $parVal );
        continue;
      }
      $param = strtolower( $param );
      if( util::isXprefixed( $param )) {
        $p1 = $parameters->addChild( $param );
        $p2 = $p1->addChild( self::$unknown, htmlspecialchars( $parVal ));
      }
      else {
        $p1 = $parameters->addChild( $param );
        switch( $param ) {
          case self::$altrep:
          case self::$dir:
            $ptype = self::$uri;
            break;
          case self::$delegated_from:
          case self::$delegated_to:
          case self::$member:
          case self::$sent_by:
            $ptype = self::$cal_address;
            break;
          case self::$rsvp:
            $ptype = $BOOLEAN;
            break ;
          default:
            $ptype = self::$text;
            break;
        }
        if( is_array( $parVal )) {
          foreach( $parVal as $pV )
            $p2 = $p1->addChild( $ptype, htmlspecialchars( $pV ));
        }
        else
          $p2 = $p1->addChild( $ptype, htmlspecialchars( $parVal ));
      }
    }
  } // end if( ! empty( $params ))
  if(( empty( $content ) && ( util::$ZERO != $content )) ||
       ( ! is_array( $content) &&
       ( util::$MINUS != $content[0] ) &&
       ( 0 > $content )))
    return;
            /** store content */
  switch( $type ) {
    case self::$binary:
      $v = $child->addChild( $type, $content );
      break;
    case $BOOLEAN:
      break;
    case self::$cal_address:
      $v = $child->addChild( $type, $content );
      break;
    case self::$date:
      if( array_key_exists( util::$LCYEAR, $content ))
        $content = [$content];
      foreach( $content as $date ) {
        $str = sprintf( $FMTYMD, (int) $date[util::$LCYEAR],
                                 (int) $date[util::$LCMONTH],
                                 (int) $date[util::$LCDAY] );
        $v = $child->addChild( $type, $str );
      }
      break;
    case self::$date_time:
      if( array_key_exists( util::$LCYEAR, $content ))
        $content = [$content];
      foreach( $content as $dt ) {
        if( ! isset( $dt[util::$LCHOUR] )) $dt[util::$LCHOUR] = 0;
        if( ! isset( $dt[util::$LCMIN] ))  $dt[util::$LCMIN]  = 0;
        if( ! isset( $dt[util::$LCSEC] ))  $dt[util::$LCSEC]  = 0;
        $str = sprintf( $FMTYMDHIS,  (int) $dt[util::$LCYEAR],
                                     (int) $dt[util::$LCMONTH],
                                     (int) $dt[util::$LCDAY],
                                     (int) $dt[util::$LCHOUR],
                                     (int) $dt[util::$LCMIN],
                                     (int) $dt[util::$LCSEC] );
        if(      isset( $dt[util::$LCtz] ) &&
          ( util::$Z == $dt[util::$LCtz] ))
          $str .= util::$Z;
        $v = $child->addChild( $type, $str );
      }
      break;
    case strtolower( util::$DURATION ):
      $output = (( strtolower( util::$TRIGGER ) == $name ) &&
                 ( false !== $content[$BEFORE] )) ? util::$MINUS : null;
      $v = $child->addChild( $type, $output . util::duration2str( $content ));
      break;
    case strtolower( util::$GEO ):
      if( ! empty( $content )) {
        $v1 = $child->addChild( utilGeo::$LATITUDE,
                                utilGeo::geo2str2( $content[utilGeo::$LATITUDE],
                                                            utilGeo::$geoLatFmt ));
        $v1 = $child->addChild( utilGeo::$LONGITUDE,
                                utilGeo::geo2str2( $content[utilGeo::$LONGITUDE],
                                                            utilGeo::$geoLongFmt ));
      }
      break;
    case self::$integer:
      $v = $child->addChild( $type, (string) $content );
      break;
    case self::$period:
      if( ! is_array( $content ))
        break;
      foreach( $content as $period ) {
        $v1 = $child->addChild( $type );
        $str = sprintf( $FMTYMDHIS, (int) $period[0][util::$LCYEAR],
                                    (int) $period[0][util::$LCMONTH],
                                    (int) $period[0][util::$LCDAY],
                                    (int) $period[0][util::$LCHOUR],
                                    (int) $period[0][util::$LCMIN],
                                    (int) $period[0][util::$LCSEC] );
        if(      isset( $period[0][util::$LCtz] ) &&
          ( util::$Z == $period[0][util::$LCtz] ))
          $str .= util::$Z;
        $v2 = $v1->addChild( $START, $str );
        if( array_key_exists( util::$LCYEAR, $period[1] )) {
          $str = sprintf( $FMTYMDHIS, (int) $period[1][util::$LCYEAR],
                                      (int) $period[1][util::$LCMONTH],
                                      (int) $period[1][util::$LCDAY],
                                      (int) $period[1][util::$LCHOUR],
                                      (int) $period[1][util::$LCMIN],
                                      (int) $period[1][util::$LCSEC] );
          if(       isset($period[1][util::$LCtz] ) &&
            ( util::$Z == $period[1][util::$LCtz] ))
            $str .= util::$Z;
          $v2 = $v1->addChild( $END, $str );
        }
        else
          $v2 = $v1->addChild( strtolower( util::$DURATION ),
                               util::duration2str( $period[1] ));
      }
      break;
    case self::$recur:
      $content = array_change_key_case( $content );
      foreach( $content as $rulelabel => $rulevalue ) {
        switch( $rulelabel ) {
          case $UNTIL:
            if( isset( $rulevalue[util::$LCHOUR] ))
              $str = sprintf( $FMTYMDHIS, (int) $rulevalue[util::$LCYEAR],
                                          (int) $rulevalue[util::$LCMONTH],
                                          (int) $rulevalue[util::$LCDAY],
                                          (int) $rulevalue[util::$LCHOUR],
                                          (int) $rulevalue[util::$LCMIN],
                                          (int) $rulevalue[util::$LCSEC] ) . util::$Z;
            else
              $str = sprintf( $FMTYMD,    (int) $rulevalue[util::$LCYEAR],
                                          (int) $rulevalue[util::$LCMONTH],
                                          (int) $rulevalue[util::$LCDAY] );
            $v = $child->addChild( $rulelabel, $str );
            break;
          case self::$bysecond:
          case self::$byminute:
          case self::$byhour:
          case self::$bymonthday:
          case self::$byyearday:
          case self::$byweekno:
          case self::$bymonth:
          case self::$bysetpos: {
            if( is_array( $rulevalue )) {
              foreach( $rulevalue as $vix => $valuePart )
                $v = $child->addChild( $rulelabel, $valuePart );
            }
            else
              $v = $child->addChild( $rulelabel, $rulevalue );
            break;
          }
          case self::$byday: {
            if( isset( $rulevalue[util::$DAY] )) {
              $str  = ( isset( $rulevalue[0] )) ? $rulevalue[0] : null;
              $str .= $rulevalue[util::$DAY];
              $p    = $child->addChild( $rulelabel, $str );
            }
            else {
              foreach( $rulevalue as $valuePart ) {
                if( isset( $valuePart[util::$DAY] )) {
                  $str  = ( isset( $valuePart[0] )) ? $valuePart[0] : null;
                  $str .= $valuePart[util::$DAY];
                  $p    = $child->addChild( $rulelabel, $str );
                }
                else
                  $p    = $child->addChild( $rulelabel, $valuePart );
              }
            }
            break;
          }
          case self::$freq:
          case self::$count:
          case self::$interval:
          case self::$wkst:
          default:
            $p = $child->addChild( $rulelabel, $rulevalue );
            break;
        } // end switch( $rulelabel )
      } // end foreach( $content as $rulelabel => $rulevalue )
      break;
    case self::$rstatus:
      $v = $child->addChild( self::$code,
                             number_format((float) $content[self::$statcode],
                                            2,
                                            util::$DOT,
                                            $SP0 ));
      $v = $child->addChild( strtolower( util::$DESCRIPTION ),
                             htmlspecialchars( $content[self::$text] ));
      if( isset( $content[self::$extdata] ))
        $v = $child->addChild( self::$data,
                               htmlspecialchars( $content[self::$extdata] ));
      break;
    case self::$text:
      if( ! is_array( $content ))
        $content = [$content];
      foreach( $content as $part )
        $v = $child->addChild( $type, htmlspecialchars( $part ));
      break;
    case self::$time:
      break;
    case self::$uri:
      $v = $child->addChild( $type, $content );
      break;
    case self::$utc_offset:
      if( in_array( $content[0], $PLUSMINUSARR )) {
        $str     = $content[0];
        $content = substr( $content, 1 );
      }
      else
        $str     = util::$PLUS;
      $str .= substr( $content, 0, 2 ) . util::$COLON . substr( $content, 2, 2 );
      if( 4 < strlen( $content ))
        $str .= util::$COLON . substr( $content, 4 );
      $v = $child->addChild( $type, $str );
      break;
    case self::$unknown:
    default:
      if( is_array( $content ))
        $content = implode( $content );
      $v = $child->addChild( self::$unknown, htmlspecialchars( $content ));
      break;
  }
 }
/**
 * Parse (rfc6321) XML file into iCalcreator instance
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.22 - 2013-06-18
 * @param  string $xmlfile
 * @param  array  $iCalcfg iCalcreator config array (opt)
 * @return mixediCalcreator instance or false on error
 * @static
 */
 public static function XMLfile2iCal( $xmlfile, $iCalcfg=[] ) {
  if( false === ( $xmlstr = file_get_contents( $xmlfile )))
    return false;
  return self::xml2iCal( $xmlstr, $iCalcfg );
 }
/**
 * Parse (rfc6321) XML string into iCalcreator instance, alias of XML2iCal
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.16.22 - 2013-06-18
 * @param  string $xmlstr
 * @param  array  $iCalcfg iCalcreator config array (opt)
 * @return mixed  iCalcreator instance or false on error
 * @static
 */
 public static function XMLstr2iCal( $xmlstr, $iCalcfg=[] ) {
  return self::XML2iCal( $xmlstr, $iCalcfg);
 }
/**
 * Parse (rfc6321) XML string into iCalcreator instance
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.20.23 - 2017-02-25
 * @param  string $xmlstr
 * @param  array  $iCalcfg iCalcreator config array (opt)
 * @return mixed  iCalcreator instance or false on error
 * @static
 */
 public static function XML2iCal( $xmlstr, $iCalcfg=[] ) {
  static $CRLF = ["\r\n", "\n\r", "\n", "\r"];
  $xmlstr  = str_replace( $CRLF, null, $xmlstr );
  $xml     = self::XMLgetTagContent1( $xmlstr, self::$vcalendar, $endIx );
  $iCal    = new vcalendar( $iCalcfg );
  self::XMLgetComps( $iCal, $xmlstr );
  return $iCal;
 }
/**
 * Parse (rfc6321) XML string into iCalcreator components
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.23.8 - 2017-04-14
 * @param object $iCal   iCalcreator vcalendar or component object instance
 * @param string $xml
 * @return object
 * @access private
 * @static
 */
 private static function XMLgetComps( $iCal, $xml ) {
  static $PROPSTAG   = '<properties>';
  static $COMPSTAG   = '<components>';
  $len     = strlen( $xml );
  $sx      = 0;
  while((( $sx + 12 ) < $len ) &&
        ( $PROPSTAG != substr( $xml, $sx, 12 )) &&
        ( $COMPSTAG != substr( $xml, $sx, 12 )))
    $sx   += 1;
  if(( $sx + 11 ) >= $len )
    return false;
  if( $PROPSTAG == substr( $xml, $sx, 12 )) {
    $xml2  = self::XMLgetTagContent1( $xml, self::$properties, $endIx );
    self::XMLgetProps( $iCal, $xml2 );
    $xml   = substr( $xml, $endIx );
  }
  if( $COMPSTAG == substr( $xml, 0, 12 ))
    $xml     = self::XMLgetTagContent1( $xml, self::$components, $endIx );
  while( ! empty( $xml )) {
    $xml2  = self::XMLgetTagContent2( $xml, $tagName, $endIx );
    if( in_array( strtolower( $tagName ), util::$ALLCOMPS ) &&
       ( false !== ( $subComp = $iCal->newComponent( $tagName ))))
      self::XMLgetComps( $subComp, $xml2 );
    $xml   = substr( $xml, $endIx);
  } // end while( ! empty( $xml ))
  return $iCal;
 }
/**
 * Parse (rfc6321) XML into iCalcreator properties
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.23.3 - 2017-03-19
 * @param  object $iCal iCalcreator calendar/component instance
 * @param  string $xml
 * @access private
 * @static
 */
 private static function XMLgetProps( $iCal, $xml) {
  static $PARAMENDTAG = '<parameters/>';
  static $PARAMTAG    = '<parameters>';
  static $DATETAGST   = '<date';
  static $PERIODTAG   = '<period>';
  while( ! empty( $xml )) {
    $xml2         = self::XMLgetTagContent2( $xml, $propName, $endIx );
    $propName     = strtoupper( $propName );
    if( empty( $xml2 ) && ( util::$ZERO != $xml2 )) {
      $iCal->setProperty( $propName );
      $xml        = substr( $xml, $endIx);
      continue;
    }
    $params       = [];
    if( $PARAMENDTAG == substr( $xml2, 0, 13 ))
      $xml2       = substr( $xml2, 13 );
    elseif( $PARAMTAG == substr( $xml2, 0, 12 )) {
      $xml3       = self::XMLgetTagContent1( $xml2, self::$PARAMETERS, $endIx2 );
      while( ! empty( $xml3 )) {
        $xml4     = self::XMLgetTagContent2( $xml3, $paramKey, $endIx3 );
        $pType    = false; // skip parameter valueType
        $paramKey = strtoupper( $paramKey );
        if( in_array( $paramKey, util::$ATTENDEEPARKEYS )) {
          while( ! empty( $xml4 )) {
            $paramValue = self::XMLgetTagContent1( $xml4, self::$cal_address, $endIx4 );
            if( ! isset( $params[$paramKey] ))
              $params[$paramKey]   = [$paramValue];
            else
              $params[$paramKey][] = $paramValue;
            $xml4     = substr( $xml4, $endIx4 );
          }
        } // end if( in_array( $paramKey, util::$ATTENDEEPARKEYS ))
        else {
          $paramValue = html_entity_decode( self::XMLgetTagContent2( $xml4, $pType, $endIx4 ));
          if( ! isset( $params[$paramKey] ))
            $params[$paramKey]  = $paramValue;
          else
            $params[$paramKey] .= util::$COMMA . $paramValue;
        }
        $xml3     = substr( $xml3, $endIx3 );
      }
      $xml2       = substr( $xml2, $endIx2 );
    } // end elseif
    $valueType    = false;
    $value        = ( ! empty( $xml2 ) || ( util::$ZERO == $xml2 ))
                  ? self::XMLgetTagContent2( $xml2, $valueType, $endIx3 ) : null;
    switch( $propName ) {
      case util::$CATEGORIES:
      case util::$RESOURCES:
        $tValue      = [];
        while( ! empty( $xml2 )) {
          $tValue[]  = html_entity_decode( self::XMLgetTagContent2( $xml2,
                                                                    $valueType,
                                                                    $endIx4 ));
          $xml2      = substr( $xml2, $endIx4 );
        }
        $value       = $tValue;
        break;
      case util::$EXDATE:   // multiple single-date(-times) may exist
      case util::$RDATE:
        if( self::$period != $valueType ) {
          if( self::$date == $valueType )
            $params[util::$VALUE] = util::$DATE;
          $t         = [];
          while( ! empty( $xml2 ) &&
                ( $DATETAGST == substr( $xml2, 0, 5 ))) {
            $t[]     = self::XMLgetTagContent2( $xml2,
                                                $pType,
                                                $endIx4 );
            $xml2    = substr( $xml2, $endIx4 );
          }
          $value = $t;
          break;
        }
      case util::$FREEBUSY:
        if( util::$RDATE == $propName )
          $params[util::$VALUE] = util::$PERIOD;
        $value       = [];
        while( ! empty( $xml2 ) &&
              ( $PERIODTAG == substr( $xml2, 0, 8 ))) {
          $xml3      = self::XMLgetTagContent1( $xml2,
                                                self::$period,
                                                $endIx4 ); // period
          $t         = [];
          while( ! empty( $xml3 )) {
            $t[]     = self::XMLgetTagContent2( $xml3,
                                                $pType,
                                                $endIx5 ); // start - end/duration
            $xml3    = substr( $xml3, $endIx5 );
          }
          $value[]   = $t;
          $xml2      = substr( $xml2, $endIx4 );
        }
        break;
      case util::$TZOFFSETTO:
      case util::$TZOFFSETFROM:
        $value       = str_replace( util::$COLON, null, $value );
        break;
      case util::$GEO:
        $tValue      = [utilGeo::$LATITUDE => $value];
        $tValue[utilGeo::$LONGITUDE] = self::XMLgetTagContent1( substr( $xml2, $endIx3 ),
                                                                utilGeo::$LONGITUDE,
                                                                $endIx3 );
        $value       = $tValue;
        break;
      case util::$EXRULE:
      case util::$RRULE:
        $tValue      = [$valueType => $value];
        $xml2        = substr( $xml2, $endIx3 );
        $valueType   = false;
        while( ! empty( $xml2 )) {
          $t         = self::XMLgetTagContent2( $xml2,
                                                $valueType,
                                                $endIx4 );
          switch( strtoupper( $valueType )) {
            case util::$FREQ:
            case util::$COUNT:
            case util::$UNTIL:
            case util::$INTERVAL:
            case util::$WKST:
              $tValue[$valueType] = $t;
              break;
            case util::$BYDAY:
              if( 2 == strlen( $t ))
                $tValue[$valueType][] = [util::$DAY => $t];
              else {
                $day = substr( $t, -2 );
                $key = substr( $t, 0, ( strlen( $t ) - 2 ));
                $tValue[$valueType][] = [$key, util::$DAY => $day];
              }
              break;
            default:
              $tValue[$valueType][] = $t;
          }
          $xml2      = substr( $xml2, $endIx4 );
        }
        $value       = $tValue;
        break;
      case util::$REQUEST_STATUS:
        $tValue      = [];
        while( ! empty( $xml2 )) {
          $t         = html_entity_decode( self::XMLgetTagContent2( $xml2,
                                                                    $valueType,
                                                                    $endIx4 ));
          $tValue[$valueType] = $t;
          $xml2      = substr( $xml2, $endIx4 );
        }
        if( ! empty( $tValue ))
          $value     = $tValue;
        else
          $value     = [self::$code                      => null,
                        strtolower( util::$DESCRIPTION ) => null];
        break;
      default:
        switch( $valueType ) {
          case self::$binary :
            $params[util::$VALUE] = util::$BINARY;
            break;
          case self::$date :
            $params[util::$VALUE] = util::$DATE;
            break;
          case self::$date_time :
            $params[util::$VALUE] = util::$DATE_TIME;
            break;
          case self::$text :
          case self::$unknown :
            $value = html_entity_decode( $value );
            break;
          default :
            if( util::isXprefixed( $propName ) &&
              ( self::$unknown != strtolower( $valueType )))
              $params[util::$VALUE] = strtoupper( $valueType );
            break;
        }
        break;
    } // end switch( $propName )
    if( util::$FREEBUSY == $propName ) {
      $fbtype        = $params[self::$FBTYPE];
      unset( $params[self::$FBTYPE] );
      $iCal->setProperty( $propName, $fbtype, $value, $params );
    }
    elseif( util::$GEO == $propName ) {
      $iCal->setProperty( $propName, $value[self::$latitude],
                                     $value[self::$longitude],
                                     $params );
    }
    elseif( util::$REQUEST_STATUS == $propName ) {
      if( ! isset( $value[self::$data] ))
        $value[self::$data] = false;
      $iCal->setProperty( $propName, $value[self::$code],
                                     $value[strtolower( util::$DESCRIPTION )],
                                     $value[self::$data], $params );
    }
    else {
      if( empty( $value ) && ( is_array( $value ) || ( util::$ZERO > $value )))
        $value = null;
      $iCal->setProperty( $propName, $value, $params );
    }
    $xml             = substr( $xml, $endIx);
  } // end while( ! empty( $xml ))
 }
/**
 * Fetch a specific XML tag content
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.23.8 - 2017-04-17
 * @param string $xml
 * @param string $tagName
 * @param int    $endIx
 * @return mixed
 * @access private
 * @static
 */
 private static function XMLgetTagContent1( $xml, $tagName, & $endIx=0 ) {
  static $FMT0 = '<%s>';
  static $FMT1 = '<%s />';
  static $FMT2 = '<%s/>';
  static $FMT3 = '</%s>';
  $tagName   = strtolower( $tagName );
  $strLen    = strlen( $tagName );
  $xmlLen    = strlen( $xml );
  $sx1       = 0;
  while( $sx1 < $xmlLen ) {
    if((( $sx1 + $strLen + 1 ) < $xmlLen ) &&
       ( sprintf( $FMT0, $tagName ) == strtolower( substr( $xml, $sx1, ( $strLen + 2 )))))
      break;
    if((( $sx1 + $strLen + 3 ) < $xmlLen ) &&
       ( sprintf( $FMT1, $tagName ) == strtolower( substr( $xml, $sx1, ( $strLen + 4 ))))) {
      $endIx = $strLen + 5;
      return null; // empty tag
    }
    if((( $sx1 + $strLen + 2 ) < $xmlLen ) &&
       ( sprintf( $FMT2, $tagName ) == strtolower( substr( $xml, $sx1, ( $strLen + 3 ))))) {
      $endIx = $strLen + 4;
      return null; // empty tag
    }
    $sx1    += 1;
  } // end while...
  if( false === substr( $xml, $sx1, 1 )) {
    $endIx   = ( empty( $sx )) ? 0 : $sx - 1; // ??
    return null;
  }
  $endTag    = sprintf( $FMT3, $tagName );
  if( false === ( $pos = stripos( $xml, $endTag ))) { // missing end tag??
    $endIx   = $xmlLen + 1;
    return null;
  }
  $endIx     = $pos + $strLen + 3;
  return substr( $xml, ( $sx1 + $strLen + 2 ), ( $pos - $sx1 - 2 - $strLen ));
 }
/**
 * Fetch next (unknown) XML tagname AND content
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.23.8 - 2017-04-17
 * @param string $xml
 * @param string $tagName
 * @param int $endIx
 * @return mixed
 * @access private
 * @static
 */
 private static function XMLgetTagContent2( $xml, & $tagName, & $endIx ) {
  static $LT          = '<';
  static $CMTSTART    = '<!--';
  static $EMPTYTAGEND = '/>';
  static $GT          = '>';
  static $DURATION    = 'duration';
  static $DURATIONTAG = '<duration>';
  static $DURENDTAG   = '</duration>';
  static $FMTTAG      = '</%s>';
  $xmlLen      = strlen( $xml );
  $endIx       = $xmlLen + 1; // just in case.. .
  $sx1         = 0;
  while( $sx1 < $xmlLen ) {
    if( $LT == substr( $xml, $sx1, 1 )) {
      if((( $sx1 + 3 ) < $xmlLen ) &&
         ( $CMTSTART == substr( $xml, $sx1, 4 ))) // skip comment
        $sx1  += 1;
      else
        break; // tagname start here
    }
    else
      $sx1    += 1;
  } // end while...
  $sx2         = $sx1;
  while( $sx2 < $xmlLen ) {
    if((( $sx2 + 1 ) < $xmlLen ) &&
       ( $EMPTYTAGEND == substr( $xml, $sx2, 2 ))) { // tag with no content
      $tagName = trim( substr( $xml, ( $sx1 + 1 ), ( $sx2 - $sx1 - 1 )));
      $endIx   = $sx2 + 2;
      return null;
    }
    if( $GT == substr( $xml, $sx2, 1 )) // tagname ends here
      break;
    $sx2      += 1;
  } // end while...
  $tagName     = substr( $xml, ( $sx1 + 1 ), ( $sx2 - $sx1 - 1 ));
  $endIx       = $sx2 + 1;
  if( $sx2 >= $xmlLen )
    return null;
  $strLen      = strlen( $tagName );
  if(( $DURATION == $tagName ) &&
     ( false !== ( $pos1 = stripos( $xml, $DURATIONTAG, $sx1+1  ))) &&
     ( false !== ( $pos2 = stripos( $xml, $DURENDTAG,   $pos1+1 ))) &&
     ( false !== ( $pos3 = stripos( $xml, $DURENDTAG,   $pos2+1 ))) &&
     ( $pos1 < $pos2 ) && ( $pos2 < $pos3 ))
    $pos = $pos3;
  elseif( false === ( $pos = stripos( $xml, sprintf( $FMTTAG, $tagName ), $sx2 )))
    return null;
  $endIx       = $pos + $strLen + 3;
  return substr( $xml, ( $sx1 + $strLen + 2 ), ( $pos - $strLen - 2 ));
 }
}
