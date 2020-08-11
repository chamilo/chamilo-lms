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
 * RECURRENCE-ID property functions
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.23 - 2017-02-05
 */
trait RECURRENCE_IDtrait {
/**
 * @var array component property RECURRENCE_ID value
 * @access protected
 */
  protected $recurrenceid = null;
/**
 * Return formatted output for calendar component property recurrence-id
 *
 * @return string
 */
  public function createRecurrenceid() {
    if( empty( $this->recurrenceid ))
      return null;
    if( empty( $this->recurrenceid[util::$LCvalue] ))
      return ( $this->getConfig( util::$ALLOWEMPTY )) ? util::createElement( util::$RECURRENCE_ID ) : null;
    return util::createElement( util::$RECURRENCE_ID,
                                util::createParams( $this->recurrenceid[util::$LCparams] ),
                                util::date2strdate( $this->recurrenceid[util::$LCvalue],
                                                    util::isParamsValueSet( $this->recurrenceid, util::$DATE ) ? 3 : null ));
  }
/**
 * Set calendar component property recurrence-id
 *
 * @param mixed   $year
 * @param mixed   $month
 * @param int     $day
 * @param int     $hour
 * @param int     $min
 * @param int     $sec
 * @param string  $tz
 * @param array   $params
 * @return bool
 */
  public function setRecurrenceid( $year, $month=null, $day=null,
                                   $hour=null, $min=null, $sec=null,
                                   $tz=null, $params=null ) {
    if( empty( $year )) {
      if( $this->getConfig( util::$ALLOWEMPTY )) {
        $this->recurrenceid = [util::$LCvalue  => util::$EMPTYPROPERTY,
                               util::$LCparams => null];
        return true;
      }
      else
        return false;
    }
    $this->recurrenceid = util::setDate( $year, $month, $day, $hour, $min, $sec, $tz,
                                         $params,
                                         null,
                                         null,
                                         $this->getConfig( util::$TZID ));
    return true;
  }
}
