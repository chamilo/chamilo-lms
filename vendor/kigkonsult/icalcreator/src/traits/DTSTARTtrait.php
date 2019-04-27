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
 * DTSTART property functions
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.23 - 2017-02-02
 */
trait DTSTARTtrait {
/**
 * @var array component property DTSTART value
 * @access protected
 */
  protected $dtstart = null;
/**
 * Return formatted output for calendar component property dtstart
 *
 * @return string
 */
  public function createDtstart() {
    if( empty( $this->dtstart ))
      return null;
    if( util::hasNodate( $this->dtstart ))
      return ( $this->getConfig( util::$ALLOWEMPTY )) ? util::createElement( util::$DTSTART ): null;
    if( in_array( $this->objName, util::$TZCOMPS ))
      unset( $this->dtstart[util::$LCvalue][util::$LCtz], $this->dtstart[util::$LCparams][util::$TZID] );
    return util::createElement( util::$DTSTART,
                                util::createParams( $this->dtstart[util::$LCparams] ),
                                util::date2strdate( $this->dtstart[util::$LCvalue],
                                                    util::isParamsValueSet( $this->dtstart, util::$DATE ) ? 3 : null ));
  }
/**
 * Set calendar component property dtstart
 *
 * @param mixed  $year
 * @param mixed  $month
 * @param int    $day
 * @param int    $hour
 * @param int    $min
 * @param int    $sec
 * @param string $tz
 * @param array  $params
 * @return bool
 */
  public function setDtstart( $year, $month=null, $day=null, $hour=null, $min=null, $sec=null, $tz=null, $params=null ) {
    if( empty( $year )) {
      if( $this->getConfig( util::$ALLOWEMPTY )) {
        $this->dtstart = [util::$LCvalue  => util::$EMPTYPROPERTY,
                          util::$LCparams => util::setParams( $params )];
        return true;
      }
      else
        return false;
    }
    if( false === ( $tzid = $this->getConfig( util::$TZID )))
      $tzid = null;
    $this->dtstart = util::setDate( $year, $month, $day, $hour, $min, $sec, $tz,
                                    $params, util::$DTSTART, $this->objName, $tzid);
    return true;
  }
}
