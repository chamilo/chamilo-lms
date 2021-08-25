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
 *  Parent class for calendar components
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.2.11 - 2015-03-31
 */
class calendarComponent extends iCalBase {
/**
 * @var string component type
 */
  public $objName   = null;
/**
 * @var int component number
 */
  public $cno       = 0;
/**
 * Constructor for calendar component object
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.20.23 - 2017-02-21
 */
  public function __construct() {
    static $BS = '\\';
    if( isset( $this->timezonetype ))
      $this->objName = $this->timezonetype;
    else {
      $className     =  get_called_class();
      $this->objName = substr( $className, strrpos( $className, $BS ) + 1 );
    }
    if( in_array( $this->objName, util::$VCOMPS ))
      $this->dtstamp = util::makeDtstamp();
  }
/**
 * Return unique instance number
 *
 * @return int
 */
  protected static function getObjectNo() {
    static $objectNo = 0;
    return ++$objectNo;
  }
/**
 * Delete component property value
 *
 * Return false at successfull removal of non-multiple property
 * Return false at successfull removal of last multiple property part
 * otherwise true (there is more to remove)
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.8.8 - 2011-03-15
 * @param mixed  $propName  bool false => X-property
 * @param int    $propix    specific property in case of multiply occurences
 * @return bool
 */
  public function deleteProperty( $propName=null, $propix=null ) {
    if( $this->notExistProp( $propName ))
      return false;
    $propName = strtoupper( $propName );
    if( in_array( $propName, util::$MPROPS2 )) {
      if( is_null( $propix ))
        $propix = ( isset( $this->propdelix[$propName] ) &&
                         ( util::$X_PROP != $propName ))
                ? $this->propdelix[$propName] + 2 : 1;
      $this->propdelix[$propName] = --$propix;
    }
    switch( $propName ) {
      case util::$ACTION:
        $this->action = null;
        return false;
        break;
      case util::$ATTACH:
        return util::deletePropertyM( $this->attach,
                                      $this->propdelix[$propName] );
        break;
      case util::$ATTENDEE:
        return util::deletePropertyM( $this->attendee,
                                      $this->propdelix[$propName] );
        break;
      case util::$CATEGORIES:
        return util::deletePropertyM( $this->categories,
                                      $this->propdelix[$propName] );
        break;
      case util::$CLASS:
        $this->class = null;
        return false;
        break;
      case util::$COMMENT:
        return util::deletePropertyM( $this->comment,
                                      $this->propdelix[$propName] );
        break;
      case util::$COMPLETED:
        $this->completed = null;
        return false;
        break;
      case util::$CONTACT:
        return util::deletePropertyM( $this->contact,
                                      $this->propdelix[$propName] );
        break;
      case util::$CREATED:
        $this->created = null;
        return false;
        break;
      case util::$DESCRIPTION:
        return util::deletePropertyM( $this->description,
                                      $this->propdelix[$propName] );
        break;
      case util::$DTEND:
        $this->dtend = null;
        return false;
        break;
      case util::$DTSTAMP:
        if( in_array( $this->objName, util::$LCSUBCOMPS ))
          return false;
        $this->dtstamp = null;
        return false;
        break;
      case util::$DTSTART:
        $this->dtstart = null;
        return false;
        break;
      case util::$DUE:
        $this->due = null;
        return false;
        break;
      case util::$DURATION:
        $this->duration = null;
        return false;
        break;
      case util::$EXDATE:
        return util::deletePropertyM( $this->exdate,
                                      $this->propdelix[$propName] );
        break;
      case util::$EXRULE:
        return util::deletePropertyM( $this->exrule,
                                      $this->propdelix[$propName] );
        break;
      case util::$FREEBUSY:
        return util::deletePropertyM( $this->freebusy,
                                      $this->propdelix[$propName] );
        break;
      case util::$GEO:
        $this->geo = null;
        return false;
        break;
      case util::$LAST_MODIFIED:
        $this->lastmodified = null;
        return false;
        break;
      case util::$LOCATION:
        $this->location = null;
        return false;
        break;
      case util::$ORGANIZER:
        $this->organizer = null;
        return false;
        break;
      case util::$PERCENT_COMPLETE:
        $this->percentcomplete = null;
        return false;
        break;
      case util::$PRIORITY:
        $this->priority = null;
        return false;
        break;
      case util::$RDATE:
        return util::deletePropertyM( $this->rdate,
                                      $this->propdelix[$propName] );
        break;
      case util::$RECURRENCE_ID:
        $this->recurrenceid = null;
        return false;
        break;
      case util::$RELATED_TO:
        return util::deletePropertyM( $this->relatedto,
                                      $this->propdelix[$propName] );
        break;
      case util::$REPEAT:
        $this->repeat = null;
        return false;
        break;
      case util::$REQUEST_STATUS:
        return util::deletePropertyM( $this->requeststatus,
                                      $this->propdelix[$propName] );
        break;
      case util::$RESOURCES:
        return util::deletePropertyM( $this->resources,
                                      $this->propdelix[$propName] );
        break;
      case util::$RRULE:
        return util::deletePropertyM( $this->rrule,
                                      $this->propdelix[$propName] );
        break;
      case util::$SEQUENCE:
        $this->sequence = null;
        return false;
        break;
      case util::$STATUS:
        $this->status = null;
        return false;
        break;
      case util::$SUMMARY:
        $this->summary = null;
        return false;
        break;
      case util::$TRANSP:
        $this->transp = null;
        return false;
        break;
      case util::$TRIGGER:
        $this->trigger = null;
        return false;
        break;
      case util::$TZID:
        $this->tzid = null;
        return false;
        break;
      case util::$TZNAME:
        return util::deletePropertyM( $this->tzname,
                                      $this->propdelix[$propName] );
        break;
      case util::$TZOFFSETFROM:
        $this->tzoffsetfrom = null;
        return false;
        break;
      case util::$TZOFFSETTO:
        $this->tzoffsetto = null;
        return false;
        break;
      case util::$TZURL:
        $this->tzurl = null;
        return false;
        break;
      case util::$UID:
        if( in_array( $this->objName, util::$LCSUBCOMPS ))
          return false;
        $this->uid = null;
        return false;
        break;
      case util::$URL:
        $this->url = null;
        return false;
        break;
      default:
        return parent::deleteXproperty( $propName,
                                        $this->xprop,
                                        $propix,
                                        $this->propdelix );
    }
    return true;
  }
/**
 * Return true if property NOT exists within component
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.5.1 - 2008-10-15
 * @param string $propName
 * @return bool
 */
  public function notExistProp( $propName ) {
    static $LASTMODIFIED    = 'lastmodified';
    static $PERCENTCOMPLETE = 'percentcomplete';
    static $RECURRENCEID    = 'recurrenceid';
    static $RELATEDTO       = 'relatedto';
    static $REQUESTSTATUS   = 'requeststatus';
    if( empty( $propName ))
      return false; // when deleting x-prop, an empty propName may be used=allowed
    switch( strtoupper( $propName )) {
      case util::$LAST_MODIFIED :
        if( ! property_exists( $this, $LASTMODIFIED ))
          return true;
        break;
      case util::$PERCENT_COMPLETE :
        if( ! property_exists( $this, $PERCENTCOMPLETE ))
          return true;
        break;
      case util::$RECURRENCE_ID :
        if( ! property_exists( $this, $RECURRENCEID ))
          return true;
        break;
      case util::$RELATED_TO :
        if( ! property_exists( $this, $RELATEDTO ))
          return true;
        break;
      case util::$REQUEST_STATUS :
        if( ! property_exists( $this, $REQUESTSTATUS ))
          return true;
        break;
      default :
        if( ! util::isXprefixed( $propName ) &&
            ! property_exists( $this, strtolower( $propName )))
          return true;
        break;
    }
    return false;
  }
/**
 * Return component property value/params
 *
 * Return array with keys VALUE/PARAMS rf arg $inclParam is true
 * If property has multiply values, consequtive function calls are needed
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.13 - 2015-03-29
 * @param string  $propName
 * @param int     $propix   specific property in case of multiply occurences
 * @param bool    $inclParam
 * @param bool    $specform
 * @return mixed
 */
  public function getProperty( $propName=null,
                               $propix=null,
                               $inclParam=false,
                               $specform=false ) {
    if( 0 == strcasecmp( util::$GEOLOCATION, $propName )) {
      if( false === ( $geo = $this->getProperty( util::$GEO )))
        return false;
      $loc = $this->getProperty( util::$LOCATION );
      $content = ( empty( $loc )) ? null : $loc . util::$SP1;
      return $content .
             utilGeo::geo2str2( $geo[utilGeo::$LATITUDE],
                                     utilGeo::$geoLatFmt ) .
             utilGeo::geo2str2( $geo[utilGeo::$LONGITUDE],
                                     utilGeo::$geoLongFmt ) . util::$L;
    }
    if( $this->notExistProp( $propName ))
      return false;
    $propName = ( $propName ) ? strtoupper( $propName ) : util::$X_PROP;
    if( in_array( $propName, util::$MPROPS2 )) {
      if( empty( $propix ))
        $propix = ( isset( $this->propix[$propName] ))
                ? $this->propix[$propName] + 2 : 1;
      $this->propix[$propName] = --$propix;
    }
    switch( $propName ) {
      case util::$ATTACH:
        util::recountMvalPropix( $this->attach, $propix );
        $this->propix[$propName] = $propix;
        if( ! isset( $this->attach[$propix] )) {
          unset( $this->propix[$propName] );
          return false;
        }
        return ( $inclParam ) ? $this->attach[$propix]
                              : $this->attach[$propix][util::$LCvalue];
        break;
      case util::$ATTENDEE:
        util::recountMvalPropix( $this->attendee, $propix );
        $this->propix[$propName] = $propix;
        if( ! isset( $this->attendee[$propix] )) {
          unset( $this->propix[$propName] );
          return false;
        }
        return ( $inclParam ) ? $this->attendee[$propix]
                              : $this->attendee[$propix][util::$LCvalue];
        break;
      case util::$CATEGORIES:
        util::recountMvalPropix( $this->categories, $propix );
        $this->propix[$propName] = $propix;
        if( ! isset( $this->categories[$propix] )) {
          unset( $this->propix[$propName] );
          return false;
        }
        return ( $inclParam ) ? $this->categories[$propix]
                              : $this->categories[$propix][util::$LCvalue];
        break;
      case util::$CLASS:
        if( isset( $this->class[util::$LCvalue] ))
          return ( $inclParam ) ? $this->class
                                : $this->class[util::$LCvalue];
        break;
      case util::$COMMENT:
        util::recountMvalPropix( $this->comment, $propix );
        $this->propix[$propName] = $propix;
        if( ! isset( $this->comment[$propix] )) {
          unset( $this->propix[$propName] );
          return false;
        }
        return ( $inclParam ) ? $this->comment[$propix]
                              : $this->comment[$propix][util::$LCvalue];
        break;
      case util::$COMPLETED:
        if( isset( $this->completed[util::$LCvalue] ))
          return ( $inclParam ) ? $this->completed
                                : $this->completed[util::$LCvalue];
        break;
      case util::$CONTACT:
        util::recountMvalPropix( $this->contact, $propix );
        $this->propix[$propName] = $propix;
        if( ! isset( $this->contact[$propix] )) {
          unset( $this->propix[$propName] );
          return false;
        }
        return ( $inclParam ) ? $this->contact[$propix]
                              : $this->contact[$propix][util::$LCvalue];
        break;
      case util::$CREATED:
        if( isset( $this->created[util::$LCvalue] ))
          return ( $inclParam ) ? $this->created
                                : $this->created[util::$LCvalue];
        break;
      case util::$DESCRIPTION:
        util::recountMvalPropix( $this->description, $propix );
        $this->propix[$propName] = $propix;
        if( ! isset( $this->description[$propix] )) {
          unset( $this->propix[$propName] );
          return false;
        }
        return ( $inclParam ) ? $this->description[$propix]
                              : $this->description[$propix][util::$LCvalue];
        break;
      case util::$DTEND:
        if( isset( $this->dtend[util::$LCvalue] ))
          return ( $inclParam ) ? $this->dtend
                                : $this->dtend[util::$LCvalue];
        break;
      case util::$DTSTAMP:
        if( in_array( $this->objName, util::$LCSUBCOMPS ))
          return false;
        if( ! isset( $this->dtstamp[util::$LCvalue] ))
          $this->dtstamp = util::makeDtstamp();
        return ( $inclParam ) ? $this->dtstamp
                              : $this->dtstamp[util::$LCvalue];
        break;
      case util::$DTSTART:
        if( isset( $this->dtstart[util::$LCvalue] ))
          return ( $inclParam ) ? $this->dtstart
                                : $this->dtstart[util::$LCvalue];
        break;
      case util::$DUE:
        if( isset( $this->due[util::$LCvalue] ))
          return ( $inclParam ) ? $this->due
                                : $this->due[util::$LCvalue];
        break;
      case util::$DURATION:
        if( ! isset( $this->duration[util::$LCvalue] ))
          return false;
        $value  = ( $specform &&
                    isset( $this->dtstart[util::$LCvalue] ) &&
                    isset( $this->duration[util::$LCvalue] ))
                    ? util::duration2date( $this->dtstart[util::$LCvalue], $this->duration[util::$LCvalue] )
                    : $this->duration[util::$LCvalue];
        $params = ( $specform &&
                    $inclParam &&
                    isset( $this->dtstart[util::$LCparams][util::$TZID] ))
                    ? array_merge((array) $this->duration[util::$LCparams], $this->dtstart[util::$LCparams] )
                    : $this->duration[util::$LCparams];
        return ( $inclParam ) ? [util::$LCvalue  => $value,
                                 util::$LCparams => $params]
                              : $value;
        break;
      case util::$EXDATE:
        util::recountMvalPropix( $this->exdate, $propix );
        $this->propix[$propName] = $propix;
        if( ! isset( $this->exdate[$propix] )) {
          unset( $this->propix[$propName] );
          return false;
        }
        return ( $inclParam ) ? $this->exdate[$propix]
                              : $this->exdate[$propix][util::$LCvalue];
        break;
      case util::$EXRULE:
        util::recountMvalPropix( $this->exrule, $propix );
        $this->propix[$propName] = $propix;
        if( ! isset( $this->exrule[$propix] )) {
          unset( $this->propix[$propName] );
          return false;
        }
        return ( $inclParam ) ? $this->exrule[$propix]
                              : $this->exrule[$propix][util::$LCvalue];
        break;
      case util::$FREEBUSY:
        util::recountMvalPropix( $this->freebusy, $propix );
        $this->propix[$propName] = $propix;
        if( ! isset( $this->freebusy[$propix] )) {
          unset( $this->propix[$propName] );
          return false;
        }
        return ( $inclParam ) ? $this->freebusy[$propix]
                              : $this->freebusy[$propix][util::$LCvalue];
        break;
      case util::$GEO:
        if( isset( $this->geo[util::$LCvalue] ))
          return ( $inclParam ) ? $this->geo
                                : $this->geo[util::$LCvalue];
        break;
      case util::$LAST_MODIFIED:
        if( isset( $this->lastmodified[util::$LCvalue] ))
          return ( $inclParam ) ? $this->lastmodified
                                : $this->lastmodified[util::$LCvalue];
        break;
      case util::$LOCATION:
        if( isset( $this->location[util::$LCvalue] ))
          return ( $inclParam ) ? $this->location
                                : $this->location[util::$LCvalue];
        break;
      case util::$ORGANIZER:
        if( isset( $this->organizer[util::$LCvalue] ))
          return ( $inclParam ) ? $this->organizer
                                : $this->organizer[util::$LCvalue];
        break;
      case util::$PERCENT_COMPLETE:
        if( isset( $this->percentcomplete[util::$LCvalue] ))
          return ( $inclParam ) ? $this->percentcomplete
                                : $this->percentcomplete[util::$LCvalue];
        break;
      case util::$PRIORITY:
        if( isset( $this->priority[util::$LCvalue] ))
          return ( $inclParam ) ? $this->priority
                                : $this->priority[util::$LCvalue];
        break;
      case util::$RDATE:
        util::recountMvalPropix( $this->rdate, $propix );
        $this->propix[$propName] = $propix;
        if( ! isset( $this->rdate[$propix] )) {
          unset( $this->propix[$propName] );
          return false;
        }
        return ( $inclParam ) ? $this->rdate[$propix]
                              : $this->rdate[$propix][util::$LCvalue];
        break;
      case util::$RECURRENCE_ID:
        if( isset( $this->recurrenceid[util::$LCvalue] ))
          return ( $inclParam ) ? $this->recurrenceid
                                : $this->recurrenceid[util::$LCvalue];
        break;
      case util::$RELATED_TO:
        util::recountMvalPropix( $this->relatedto, $propix );
        $this->propix[$propName] = $propix;
        if( ! isset( $this->relatedto[$propix] )) {
          unset( $this->propix[$propName] );
          return false;
        }
        return ( $inclParam ) ? $this->relatedto[$propix]
                              : $this->relatedto[$propix][util::$LCvalue];
        break;
      case util::$REQUEST_STATUS:
        util::recountMvalPropix( $this->requeststatus, $propix );
        $this->propix[$propName] = $propix;
        if( ! isset( $this->requeststatus[$propix] )) {
          unset( $this->propix[$propName] );
          return false;
        }
        return ( $inclParam ) ? $this->requeststatus[$propix]
                              : $this->requeststatus[$propix][util::$LCvalue];
        break;
      case util::$RESOURCES:
        util::recountMvalPropix( $this->resources, $propix );
        $this->propix[$propName] = $propix;
        if( ! isset( $this->resources[$propix] )) {
          unset( $this->propix[$propName] );
          return false;
        }
        return ( $inclParam ) ? $this->resources[$propix]
                              : $this->resources[$propix][util::$LCvalue];
        break;
      case util::$RRULE:
        util::recountMvalPropix( $this->rrule, $propix );
        $this->propix[$propName] = $propix;
        if( ! isset( $this->rrule[$propix] )) {
          unset( $this->propix[$propName] );
          return false;
        }
        return ( $inclParam ) ? $this->rrule[$propix]
                              : $this->rrule[$propix][util::$LCvalue];
        break;
      case util::$SEQUENCE:
        if( isset( $this->sequence[util::$LCvalue] ))
          return ( $inclParam ) ? $this->sequence
                                : $this->sequence[util::$LCvalue];
        break;
      case util::$STATUS:
        if( isset( $this->status[util::$LCvalue] ))
          return ( $inclParam ) ? $this->status
                                : $this->status[util::$LCvalue];
        break;
      case util::$SUMMARY:
        if( isset( $this->summary[util::$LCvalue] ))
          return ( $inclParam ) ? $this->summary
                                : $this->summary[util::$LCvalue];
        break;
      case util::$TRANSP:
        if( isset( $this->transp[util::$LCvalue] ))
          return ( $inclParam ) ? $this->transp
                                : $this->transp[util::$LCvalue];
        break;
      case util::$TZNAME:
        util::recountMvalPropix( $this->tzname, $propix );
        $this->propix[$propName] = $propix;
        if( ! isset( $this->tzname[$propix] )) {
          unset( $this->propix[$propName] );
          return false;
        }
        return ( $inclParam ) ? $this->tzname[$propix]
                              : $this->tzname[$propix][util::$LCvalue];
        break;
      case util::$UID:
        if( in_array( $this->objName, util::$LCSUBCOMPS ))
          return false;
        if( empty( $this->uid ))
          $this->uid = util::makeUid( $this->getConfig( util::$UNIQUE_ID ));
        return ( $inclParam ) ? $this->uid
                              : $this->uid[util::$LCvalue];
        break;
      case util::$URL:
        if( isset( $this->url[util::$LCvalue] ))
          return ( $inclParam ) ? $this->url : $this->url[util::$LCvalue];
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
          foreach( $this->xprop as $xpropkey => $xpropvalue ) {
            if( $propix == $xpropno )
              return ( $inclParam ) ? [$xpropkey,
                                       $this->xprop[$xpropkey]]
                                    : [$xpropkey,
                                       $this->xprop[$xpropkey][util::$LCvalue]];
            else
              $xpropno++;
          }
          return false; // not found ??
        }
    }
    return false;
  }
/**
 * Returns calendar property unique values
 *
 * For ATTENDEE, CATEGORIES, CONTACT, RELATED_TO or RESOURCES (keys)
 * and for each, number of occurrence (values)
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.11 - 2015-03-21
 * @param string  $propName
 * @param array   $output    incremented result array
 * @return array
 */
  public function getProperties( $propName, & $output ) {
    if( empty( $output ))
      $output = [];
    if( ! in_array( strtoupper( $propName ), util::$MPROPS1 ))
      return $output;
    while( false !== ( $content = $this->getProperty( $propName ))) {
      if( empty( $content ))
        continue;
      if( is_array( $content )) {
        foreach( $content as $part ) {
          if( false !== strpos( $part, util::$COMMA )) {
            $part = explode( util::$COMMA, $part );
            foreach( $part as $thePart ) {
              $thePart = trim( $thePart );
              if( ! empty( $thePart )) {
                if( ! isset( $output[$thePart] ))
                  $output[$thePart] = 1;
                else
                  $output[$thePart] += 1;
              }
            }
          }
          else {
            $part = trim( $part );
            if( ! isset( $output[$part] ))
              $output[$part] = 1;
            else
              $output[$part] += 1;
          }
        }
      } // end if( is_array( $content ))
      elseif( false !== strpos( $content, util::$COMMA )) {
        $content = explode( util::$COMMA, $content );
        foreach( $content as $thePart ) {
          $thePart = trim( $thePart );
          if( ! empty( $thePart )) {
            if( ! isset( $output[$thePart] ))
              $output[$thePart] = 1;
            else
              $output[$thePart] += 1;
          }
        }
      } // end elseif( false !== strpos( $content, util::$COMMA ))
      else {
        $content = trim( $content );
        if( ! empty( $content )) {
          if( ! isset( $output[$content] ))
            $output[$content] = 1;
          else
            $output[$content] += 1;
        }
      }
    }
    ksort( $output );
  }
/**
 * General component setProperty method
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.5.1 - 2008-11-05
 * @param mixed $args variable number of function arguments,
 *                    first argument is ALWAYS component name,
 *                    second ALWAYS component value!
 * @return bool
 */
  public function setProperty() {
    $numargs    = func_num_args();
    if( 1 > $numargs ) return false;
    $arglist    = func_get_args();
    if( $this->notExistProp( $arglist[0] ))
      return false;
    if( ! $this->getConfig( util::$ALLOWEMPTY ) &&
      ( ! isset( $arglist[1] ) || empty( $arglist[1] )))
      return false;
    $arglist[0] = strtoupper( $arglist[0] );
    for( $argix=$numargs; $argix < 12; $argix++ ) {
      if( ! isset( $arglist[$argix] ))
        $arglist[$argix] = null;
    }
    switch( $arglist[0] ) {
      case util::$ACTION:
        return $this->setAction(          $arglist[1],
                                          $arglist[2] );
      case util::$ATTACH:
        return $this->setAttach(          $arglist[1],
                                          $arglist[2],
                                          $arglist[3] );
      case util::$ATTENDEE:
        return $this->setAttendee(        $arglist[1],
                                          $arglist[2],
                                          $arglist[3] );
      case util::$CATEGORIES:
        return $this->setCategories(      $arglist[1],
                                          $arglist[2],
                                          $arglist[3] );
      case util::$CLASS:
        return $this->setClass(           $arglist[1],
                                          $arglist[2] );
      case util::$COMMENT:
        return $this->setComment(         $arglist[1],
                                          $arglist[2],
                                          $arglist[3] );
      case util::$COMPLETED:
        return $this->setCompleted(       $arglist[1],
                                          $arglist[2],
                                          $arglist[3],
                                          $arglist[4],
                                          $arglist[5],
                                          $arglist[6],
                                          $arglist[7] );
      case util::$CONTACT:
        return $this->setContact(         $arglist[1],
                                          $arglist[2],
                                          $arglist[3] );
      case util::$CREATED:
        return $this->setCreated(         $arglist[1],
                                          $arglist[2],
                                          $arglist[3],
                                          $arglist[4],
                                          $arglist[5],
                                          $arglist[6],
                                          $arglist[7] );
      case util::$DESCRIPTION:
        return $this->setDescription(     $arglist[1],
                                          $arglist[2],
                                          $arglist[3] );
      case util::$DTEND:
        return $this->setDtend(           $arglist[1],
                                          $arglist[2],
                                          $arglist[3],
                                          $arglist[4],
                                          $arglist[5],
                                          $arglist[6],
                                          $arglist[7],
                                          $arglist[8] );
      case util::$DTSTAMP:
        return $this->setDtstamp(         $arglist[1],
                                          $arglist[2],
                                          $arglist[3],
                                          $arglist[4],
                                          $arglist[5],
                                          $arglist[6],
                                          $arglist[7] );
      case util::$DTSTART:
        return $this->setDtstart(         $arglist[1],
                                          $arglist[2],
                                          $arglist[3],
                                          $arglist[4],
                                          $arglist[5],
                                          $arglist[6],
                                          $arglist[7],
                                          $arglist[8] );
      case util::$DUE:
        return $this->setDue(             $arglist[1],
                                          $arglist[2],
                                          $arglist[3],
                                          $arglist[4],
                                          $arglist[5],
                                          $arglist[6],
                                          $arglist[7],
                                          $arglist[8] );
      case util::$DURATION:
        return $this->setDuration(        $arglist[1],
                                          $arglist[2],
                                          $arglist[3],
                                          $arglist[4],
                                          $arglist[5],
                                          $arglist[6] );
      case util::$EXDATE:
        return $this->setExdate(          $arglist[1],
                                          $arglist[2],
                                          $arglist[3] );
      case util::$EXRULE:
        return $this->setExrule(          $arglist[1],
                                          $arglist[2],
                                          $arglist[3] );
      case util::$FREEBUSY:
        return $this->setFreebusy(        $arglist[1],
                                          $arglist[2],
                                          $arglist[3],
                                          $arglist[4] );
      case util::$GEO:
        return $this->setGeo(             $arglist[1],
                                          $arglist[2],
                                          $arglist[3] );
      case util::$LAST_MODIFIED:
        return $this->setLastModified(    $arglist[1],
                                          $arglist[2],
                                          $arglist[3],
                                          $arglist[4],
                                          $arglist[5],
                                          $arglist[6],
                                          $arglist[7] );
      case util::$LOCATION:
        return $this->setLocation(        $arglist[1],
                                          $arglist[2] );
      case util::$ORGANIZER:
        return $this->setOrganizer(       $arglist[1],
                                          $arglist[2] );
      case util::$PERCENT_COMPLETE:
        return $this->setPercentComplete( $arglist[1],
                                          $arglist[2] );
      case util::$PRIORITY:
        return $this->setPriority(        $arglist[1],
                                          $arglist[2] );
      case util::$RDATE:
        return $this->setRdate(           $arglist[1],
                                          $arglist[2],
                                          $arglist[3] );
      case util::$RECURRENCE_ID:
       return $this->setRecurrenceid(     $arglist[1],
                                          $arglist[2],
                                          $arglist[3],
                                          $arglist[4],
                                          $arglist[5],
                                          $arglist[6],
                                          $arglist[7],
                                          $arglist[8] );
      case util::$RELATED_TO:
        return $this->setRelatedTo(       $arglist[1],
                                          $arglist[2],
                                          $arglist[3] );
      case util::$REPEAT:
        return $this->setRepeat(          $arglist[1],
                                          $arglist[2] );
      case util::$REQUEST_STATUS:
        return $this->setRequestStatus(   $arglist[1],
                                          $arglist[2],
                                          $arglist[3],
                                          $arglist[4],
                                          $arglist[5] );
      case util::$RESOURCES:
        return $this->setResources(       $arglist[1],
                                          $arglist[2],
                                          $arglist[3] );
      case util::$RRULE:
        return $this->setRrule(           $arglist[1],
                                          $arglist[2],
                                          $arglist[3] );
      case util::$SEQUENCE:
        return $this->setSequence(        $arglist[1],
                                          $arglist[2] );
      case util::$STATUS:
        return $this->setStatus(          $arglist[1],
                                          $arglist[2] );
      case util::$SUMMARY:
        return $this->setSummary(         $arglist[1],
                                          $arglist[2] );
      case util::$TRANSP:
        return $this->setTransp(          $arglist[1],
                                          $arglist[2] );
      case util::$TRIGGER:
        return $this->setTrigger(         $arglist[1],
                                          $arglist[2],
                                          $arglist[3],
                                          $arglist[4],
                                          $arglist[5],
                                          $arglist[6],
                                          $arglist[7],
                                          $arglist[8],
                                          $arglist[9],
                                          $arglist[10],
                                          $arglist[11] );
      case util::$TZID:
        return $this->setTzid(            $arglist[1],
                                          $arglist[2] );
      case util::$TZNAME:
        return $this->setTzname(          $arglist[1],
                                          $arglist[2],
                                          $arglist[3] );
      case util::$TZOFFSETFROM:
        return $this->setTzoffsetfrom(    $arglist[1],
                                          $arglist[2] );
      case util::$TZOFFSETTO:
        return $this->setTzoffsetto(      $arglist[1],
                                          $arglist[2] );
      case util::$TZURL:
        return $this->setTzurl(           $arglist[1],
                                          $arglist[2] );
      case util::$UID:
        return $this->setUid(             $arglist[1],
                                          $arglist[2] );
      case util::$URL:
        return $this->setUrl(             $arglist[1],
                                          $arglist[2] );
      default:
        return $this->setXprop(           $arglist[0],
                                          $arglist[1],
                                          $arglist[2] );
    }
    return false;
  }
/**
 * Parse data into component properties
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.23.5 - 2017-04-14
 * @param mixed $unparsedtext   strict rfc2445 formatted, single property string or array of strings
 * @return bool false if error occurs during parsing
 */
  public function parse( $unparsedtext=null ) {
    static $NLCHARS       = '\n';
    static $BEGIN         = 'BEGIN:';
    static $ENDALARM      = 'END:VALARM';
    static $ENDDAYLIGHT   = 'END:DAYLIGHT';
    static $ENDSTANDARD   = 'END:STANDARD';
    static $END           = 'END:';
    static $BEGINVALARM   = 'BEGIN:VALARM';
    static $BEGINSTANDARD = 'BEGIN:STANDARD';
    static $BEGINDAYLIGHT = 'BEGIN:DAYLIGHT';
    static $TEXTPROPS     = ['CATEGORIES',
                             'COMMENT',
                             'DESCRIPTION',
                             'SUMMARY'];
    static $X_            = 'X-';
    static $DBBS          = "\\";
    static $SS            = '/';
    static $EQ            = '=';
    if( ! empty( $unparsedtext )) {
      $arrParse = false;
      if( is_array( $unparsedtext )) {
        $unparsedtext = implode( $NLCHARS . util::$CRLF, $unparsedtext );
        $arrParse = true;
      }
      $rows   = util::convEolChar( $unparsedtext );
      if( $arrParse ) {
        foreach( $rows as $lix => $row )
          $rows[$lix] = util::trimTrailNL( $row );
      }
    }
    elseif( ! isset( $this->unparsed ))
      $rows = [];
    else
      $rows = $this->unparsed;
            /* skip leading (empty/invalid) lines */
    foreach( $rows as $lix => $row ) {
      if( false !== ( $pos = stripos( $row, $BEGIN ))) {
        $rows[$lix] = substr( $row, $pos );
        break;
      }
      $tst = trim( $row );
      if(( $NLCHARS == $tst ) || empty( $tst ))
        unset( $rows[$lix] );
    }
    $this->unparsed = [];
    $comp           = $this;
    $config         = $this->getConfig();
    $compSync = $subSync = 0;
    foreach( $rows as $lix => $row ) {
      switch( true ) {
        case ( 0 == strcasecmp( $ENDALARM, substr( $row, 0, 10 ))) :
          if( 1 != $subSync )
            return false;
          $this->components[]   = $comp;
          $subSync--;
          break;
        case ( 0 == strcasecmp( $ENDDAYLIGHT, substr( $row, 0, 12 ))) :
          if( 1 != $subSync )
            return false;
          $this->components[]   = $comp;
          $subSync--;
          break;
        case ( 0 == strcasecmp( $ENDSTANDARD, substr( $row, 0, 12 ))) :
          if( 1 != $subSync )
            return false;
          array_unshift( $this->components, $comp);
          $subSync--;
          break;
        case ( 0 == strcasecmp( $END, substr( $row, 0, 4 ))) :
          if( 1 != $compSync ) // end:<component>
            return false;
          if( 0 < $subSync )
            $this->components[] = $comp;
          $compSync--;
          break 2;  /* skip trailing empty lines */
        case ( 0 == strcasecmp( $BEGINVALARM, substr( $row, 0, 12 ))) :
          $comp = new valarm( $config );
          $subSync++;
          break;
        case ( 0 == strcasecmp( $BEGINSTANDARD, substr( $row, 0, 14 ))) :
          $comp = new vtimezone( util::$LCSTANDARD, $config );
          $subSync++;
          break;
        case ( 0 == strcasecmp( $BEGINDAYLIGHT, substr( $row, 0, 14 ))) :
          $comp = new vtimezone( util::$LCDAYLIGHT, $config );
          $subSync++;
          break;
        case ( 0 == strcasecmp( $BEGIN, substr( $row, 0, 6 ))) :
          $compSync++;         // begin:<component>
          break;
        default :
          $comp->unparsed[]   = $row;
          break;
      } // end switch( true )
    } // end foreach( $rows as $lix => $row )
    if( 0 < $subSync ) { // subcomp without END...
      $this->components[]   = $comp;
      unset( $comp );
    }
            /* concatenate property values spread over several lines */
    $this->unparsed = util::concatRows( $this->unparsed );
            /* parse each property 'line' */
    foreach( $this->unparsed as $lix => $row ) {
            /* get propname */
            /* split property name  and  opt.params and value */
      list( $propName, $row ) = util::getPropName( $row );
      if( util::isXprefixed( $propName )) {
        $propName2 = $propName;
        $propName  = $X_;
      }
      if( ! in_array( strtoupper( $propName ), util::$PROPNAMES ))
        continue; // skip non standard property names
            /* separate attributes from value */
      util::splitContent( $row, $propAttr );
      if(( $NLCHARS == strtolower( substr( $row, -2 ))) &&
           ! in_array( strtoupper( $propName ), $TEXTPROPS ) &&
           ( ! util::isXprefixed( $propName )))
        $row = util::trimTrailNL( $row );
            /* call setProperty( $propName.. . */
      switch( strtoupper( $propName )) {
        case util::$ATTENDEE :
          foreach( $propAttr as $pix => $attr ) {
            if( ! in_array( strtoupper( $pix ), util::$ATTENDEEPARKEYS ))
              continue;  // 'MEMBER', 'DELEGATED-TO', 'DELEGATED-FROM'
            $attr2 = explode( util::$COMMA, $attr );
              if( 1 < count( $attr2 ))
                $propAttr[$pix] = $attr2;
          }
          $this->setProperty( $propName, $row, $propAttr );
          break;
        case util::$CATEGORIES :
        case util::$RESOURCES :
          if( false !== strpos( $row, util::$COMMA )) {
            $content = util::commaSplit( $row );
            if( 1 < count( $content )) {
              foreach( $content as & $contentPart )
                $contentPart = util::strunrep( $contentPart );
              $this->setProperty( $propName, $content, $propAttr );
              break;
            }
            else
              $row = reset( $content );
          } // fall trough
        case util::$COMMENT :
        case util::$CONTACT :
        case util::$DESCRIPTION :
        case util::$LOCATION :
        case util::$SUMMARY :
          if( empty( $row ))
            $propAttr = null;
          $this->setProperty( $propName, util::strunrep( $row ), $propAttr );
          break;
        case util::$REQUEST_STATUS :
          $values    = explode( util::$SEMIC, $row, 3 );
          $values[1] = ( isset( $values[1] )) ? util::strunrep( $values[1] ) : null;
          $values[2] = ( isset( $values[2] )) ? util::strunrep( $values[2] ) : null;
          $this->setProperty( $propName
                            , $values[0]  // statcode
                            , $values[1]  // statdesc
                            , $values[2]  // extdata
                            , $propAttr );
          break;
        case util::$FREEBUSY :
          $class = get_called_class();
           if( ! isset( $class::$UCFBTYPE ))
            break; // freebusy-prop in a non-freebusy component??
          $fbtype = ( isset( $propAttr[$class::$UCFBTYPE] ))
                  ? $propAttr[$class::$UCFBTYPE] : null; // force default
          unset( $propAttr[$class::$UCFBTYPE] );
          $values = explode( util::$COMMA, $row );
          foreach( $values as $vix => $value ) {
            $value2 = explode( $SS, $value ); // '/'
            if( 1 < count( $value2 ))
              $values[$vix] = $value2;
          }
          $this->setProperty( $propName,
                              $fbtype,
                              $values,
                              $propAttr );
          break;
        case util::$GEO :
          $value = explode( util::$SEMIC, $row, 2 );
          if( 2 > count( $value ))
            $value[1] = null;
          $this->setProperty( $propName,
                              $value[0],
                              $value[1],
                              $propAttr );
          break;
        case util::$EXDATE :
          $values = ( empty( $row )) ? null : explode( util::$COMMA, $row );
          $this->setProperty( $propName,
                              $values,
                              $propAttr );
          break;
        case util::$RDATE :
          if( empty( $row )) {
            $this->setProperty( $propName,
                                $row,
                                $propAttr );
            break;
          }
          $values = explode( util::$COMMA, $row );
          foreach( $values as $vix => $value ) {
            $value2 = explode( $SS, $value );
            if( 1 < count( $value2 ))
              $values[$vix] = $value2;
          }
          $this->setProperty( $propName,
                              $values,
                              $propAttr );
          break;
        case util::$EXRULE :
        case util::$RRULE :
          $values = explode( util::$SEMIC, $row );
          $recur = [];
          foreach( $values as $value2 ) {
            if( empty( $value2 ))
              continue; // ;-char in end position ???
            $value3 = explode( $EQ, $value2, 2 );
            $rulelabel = strtoupper( $value3[0] );
            switch( $rulelabel ) {
              case util::$BYDAY: {
                $value4 = explode( util::$COMMA, $value3[1] );
                if( 1 < count( $value4 )) {
                  foreach( $value4 as $v5ix => $value5 ) {
                    $value6 = [];
                    $dayno = $dayname = null;
                    $value5 = trim( (string) $value5 );
                    if(( ctype_alpha( substr( $value5, -1 ))) &&
                       ( ctype_alpha( substr( $value5, -2, 1 )))) {
                      $dayname = substr( $value5, -2, 2 );
                      if( 2 < strlen( $value5 ))
                        $dayno = substr( $value5, 0, ( strlen( $value5 ) - 2 ));
                    }
                    if( $dayno )
                      $value6[] = $dayno;
                    if( $dayname )
                      $value6[util::$DAY] = $dayname;
                    $value4[$v5ix] = $value6;
                  }
                }
                else {
                  $value4 = [];
                  $dayno  = $dayname = null;
                  $value5 = trim( (string) $value3[1] );
                  if(( ctype_alpha( substr( $value5, -1 ))) &&
                     ( ctype_alpha( substr( $value5, -2, 1 )))) {
                      $dayname = substr( $value5, -2, 2 );
                    if( 2 < strlen( $value5 ))
                      $dayno = substr( $value5, 0, ( strlen( $value5 ) - 2 ));
                  }
                  if( $dayno )
                    $value4[] = $dayno;
                  if( $dayname )
                    $value4[util::$DAY] = $dayname;
                }
                $recur[$rulelabel] = $value4;
                break;
              }
              default: {
                $value4 = explode( util::$COMMA, $value3[1] );
                if( 1 < count( $value4 ))
                  $value3[1] = $value4;
                $recur[$rulelabel] = $value3[1];
                break;
              }
            } // end - switch $rulelabel
          } // end - foreach( $values.. .
          $this->setProperty( $propName,
                              $recur,
                              $propAttr );
          break;
        case $X_ :
          $propName = ( isset( $propName2 )) ? $propName2 : $propName;
          unset( $propName2 );
        case util::$ACTION :
        case util::$STATUS :
        case util::$TRANSP :
        case util::$UID :
        case util::$TZID :
        case util::$RELATED_TO :
        case util::$TZNAME :
          $row = util::strunrep( $row );
        default:
          $this->setProperty( $propName,
                              $row,
                              $propAttr );
          break;
      } // end  switch( $propName.. .
    } // end foreach( $this->unparsed as $lix => $row )
    unset( $this->unparsed );
    if( $this->countComponents() > 0 ) {
      foreach( $this->components as $ckey => $component ) {
        if( ! empty( $this->components[$ckey] ) &&
            ! empty( $this->components[$ckey]->unparsed )) {
          $this->components[$ckey]->parse();
        }
      }
    }
    return true;
  }
/**
 * Return calendar component subcomponent from component container
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.23.14 - 2017-05-02
 * @param mixed $arg1  ordno/component type/ component uid
 * @param mixed $arg2  ordno if arg1 = component type
 * @return object
 */
  public function getComponent ( $arg1=null, $arg2=null ) {
    static $INDEX = 'INDEX';
    if( empty( $this->components ))
      return false;
    $index = $argType = null;
    switch( true ) {
      case ( is_null( $arg1 )) :
        $argType = $INDEX;
        $index   = $this->compix[$INDEX] = ( isset( $this->compix[$INDEX] ))
                                         ? $this->compix[$INDEX] + 1 : 1;
        break;
      case ( ctype_digit( (string) $arg1 )) :
        $argType = $INDEX;
        $index   = (int) $arg1;
        unset( $this->compix );
        break;
      case ( in_array( strtolower( $arg1 ), util::$LCSUBCOMPS )) : // class name
        unset( $this->compix[$INDEX] );
        $argType = strtolower( $arg1 );
        if( is_null( $arg2 ))
          $index = $this->compix[$argType] = ( isset( $this->compix[$argType] ))
                                           ? $this->compix[$argType] + 1 : 1;
        else
          $index = (int) $arg2;
        break;
    }
    $index    -= 1;
    $ckeys = array_keys( $this->components );
    if( ! empty( $index ) && ( $index > end(  $ckeys )))
      return false;
    $cix2gC = 0;
    foreach( $ckeys as $cix ) {
      if( empty( $this->components[$cix] ))
        continue;
      if(( $INDEX == $argType ) && ( $index == $cix ))
        return clone $this->components[$cix];
      elseif(( strcmp( $this->components[$cix]->objName, $argType ) == 0 ) ||
              ( isset( $this->components[$cix]->timezonetype ) &&
             ( strcmp( $this->components[$cix]->timezonetype, $argType ) == 0 ))) {
        if( $index == $cix2gC )
          return clone $this->components[$cix];
         $cix2gC++;
      }
    }
            /* not found.. . */
    $this->compix = [];
    return false;
  }
/**
 * Add calendar component as subcomponent to container for subcomponents
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 1.x.x - 2007-04-24
 * @param object $component calendar component
 */
  public function addSubComponent ( $component ) {
    $this->setComponent( $component );
    return true;
  }
/**
 * Return formatted output for subcomponents
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.21.11 - 2015-03-10
 * @return string
 */
  public function createSubComponent() {
    static $DATEKEY = '%04d%02d%02d%02d%02d%02d000';
    $output = null;
    if( util::$LCVTIMEZONE == $this->objName ) { // sort : standard, daylight, in dtstart order
      $stdarr = $dlarr = [];
      foreach( $this->components as $cix => $component ) {
        if( empty( $component ))
          continue;
        $dt  = $component->getProperty( util::$DTSTART );
        $key = (int) sprintf( $DATEKEY, (int) $dt[util::$LCYEAR],
                                        (int) $dt[util::$LCMONTH],
                                        (int) $dt[util::$LCDAY],
                                        (int) $dt[util::$LCHOUR],
                                        (int) $dt[util::$LCMIN],
                                        (int) $dt[util::$LCSEC] );
        if( util::$LCSTANDARD == $component->objName ) {
          while( isset( $stdarr[$key] ))
            $key += 1;
          $stdarr[$key] = $component;
        }
        elseif( util::$LCDAYLIGHT == $component->objName ) {
          while( isset( $dlarr[$key] ))
            $key += 1;
          $dlarr[$key] = $component;
        }
      } // end foreach(...
      $this->components = [];
      ksort( $stdarr, SORT_NUMERIC );
      foreach( $stdarr as $std )
        $this->components[] = $std;
      unset( $stdarr );
      ksort( $dlarr,  SORT_NUMERIC );
      foreach( $dlarr as $dl )
        $this->components[] = $dl;
      unset( $dlarr );
    } // end if( util::$LCVTIMEZONE == $this->objName )
    $config = $this->getConfig();
    foreach( $this->components as $cix => $component ) {
      if( empty( $component ))
        continue;
      $this->components[$cix]->setConfig( $config, false, true );
      $output .= $this->components[$cix]->createComponent();
    }
    return $output;
  }
}
