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
 * X-property functions
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.23 - 2017-02-02
 */
trait X_PROPtrait {
/**
 *  @var array component property X-property value
 *  @access protected
 */
  protected $xprop = null;
/**
 * Return formatted output for calendar/component property x-prop
 *
 * @return string
 */
  public function createXprop() {
    if( empty( $this->xprop ) || !is_array( $this->xprop ))
      return null;
    $output        = null;
    $lang          = $this->getConfig( util::$LANGUAGE );
    foreach( $this->xprop as $label => $xpropPart ) {
      if( ! isset( $xpropPart[util::$LCvalue]) ||
          ( empty( $xpropPart[util::$LCvalue] ) && ! is_numeric( $xpropPart[util::$LCvalue] ))) {
        if( $this->getConfig( util::$ALLOWEMPTY ))
          $output .= util::createElement( $label );
        continue;
      }
      if( is_array( $xpropPart[util::$LCvalue] )) {
        foreach( $xpropPart[util::$LCvalue] as $pix => $theXpart )
          $xpropPart[util::$LCvalue][$pix] = util::strrep( $theXpart );
        $xpropPart[util::$LCvalue]  = implode( util::$COMMA, $xpropPart[util::$LCvalue] );
      }
      else
        $xpropPart[util::$LCvalue] = util::strrep( $xpropPart[util::$LCvalue] );
      $output     .= util::createElement( $label,
                                          util::createParams( $xpropPart[util::$LCparams],
                                                              [util::$LANGUAGE],
                                                              $lang ),
                                          util::trimTrailNL( $xpropPart[util::$LCvalue] ));
    }
    return $output;
  }
/**
 * Set calendar property x-prop
 *
 * @param string $label
 * @param string $value
 * @param array $params optional
 * @return bool
 */
  public function setXprop( $label, $value, $params=false ) {
    if( empty( $label ) || ! util::isXprefixed( $label ))
      return false;
    if( empty( $value ) && ! is_numeric( $value )) {
      if( $this->getConfig( util::$ALLOWEMPTY ))
        $value     = util::$EMPTYPROPERTY;
      else
        return false;
    }
    $xprop         = [util::$LCvalue => $value];
    $xprop[util::$LCparams] = util::setParams( $params );
    if( ! is_array( $this->xprop ))
      $this->xprop = [];
    $this->xprop[strtoupper( $label )] = $xprop;
    return true;
  }
/**
 * Delete component property X-prop value
 *
 * @param string $propName
 * @param array  $xProp     component X-property
 * @param int    $propix    removal counter
 * @param array  $propdelix
 * @access protected
 * @static
 */
  protected static function deleteXproperty( $propName=null, & $xProp, & $propix, & $propdelix ) {
    $reduced = [];
    if( $propName != util::$X_PROP ) {
      if( ! isset( $xProp[$propName] )) {
        unset( $propdelix[$propName] );
        return false;
      }
      foreach( $xProp as $k => $xValue ) {
        if(( $k != $propName ) && ! empty( $xValue ))
          $reduced[$k] = $xValue;
      }
    }
    else {
      if( count( $xProp ) <= $propix ) {
        unset( $propdelix[$propName] );
        return false;
      }
      $xpropno = 0;
      foreach( $xProp as $xPropKey => $xPropValue ) {
        if( $propix != $xpropno )
          $reduced[$xPropKey] = $xPropValue;
        $xpropno++;
      }
    }
    $xProp = $reduced;
    if( empty( $xProp )) {
      $xProp = null;
      unset( $propdelix[$propName] );
      return false;
    }
    return true;
  }
}
