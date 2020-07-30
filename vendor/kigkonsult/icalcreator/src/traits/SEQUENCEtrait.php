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
 * SEQUENCE property functions
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.23 - 2017-02-24
 */
trait SEQUENCEtrait {
/**
 * @var array component property SEQUENCE value
 * @access protected
 */
  protected $sequence = null;
/**
 * Return formatted output for calendar component property sequence
 *
 * @return string
 */
  public function createSequence() {
    if( ! isset( $this->sequence ) ||
        ( empty( $this->sequence ) && ! is_numeric( $this->sequence )))
      return null;
    if((    ! isset( $this->sequence[util::$LCvalue] ) ||
            ( empty( $this->sequence[util::$LCvalue] ) && ! is_numeric( $this->sequence[util::$LCvalue] ))) &&
    ( util::$ZERO != $this->sequence[util::$LCvalue] ))
      return ( $this->getConfig( util::$ALLOWEMPTY )) ? util::createElement( util::$SEQUENCE ) : null;
    return util::createElement( util::$SEQUENCE,
                                util::createParams( $this->sequence[util::$LCparams] ),
                                $this->sequence[util::$LCvalue] );
  }
/**
 * Set calendar component property sequence
 *
 * @param int    $value
 * @param array  $params
 */
  public function setSequence( $value=null, $params=null ) {
    if(( empty( $value ) && ! is_numeric( $value )) && ( util::$ZERO != $value ))
      $value = ( isset( $this->sequence[util::$LCvalue] ) &&
                 ( -1 < $this->sequence[util::$LCvalue] ))
             ? $this->sequence[util::$LCvalue] + 1
             : util::$ZERO;
    $this->sequence = [util::$LCvalue  => $value,
                       util::$LCparams => util::setParams( $params )];
    return true;
  }
}
