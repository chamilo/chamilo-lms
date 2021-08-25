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
 * PRODID property functions
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.23 - 2017-03-15
 */
trait PRODIDtrait {
/**
 * @var string calendar property PRODID
 * @access protected
 */
  protected $prodid = null;
/**
 * Return formatted output for calendar property prodid
 *
 * @return string
 */
  public function createProdid() {
    if( ! isset( $this->prodid ))
      $this->makeProdid();
    return util::createElement( util::$PRODID,
                                null,
                                $this->prodid );
  }
/**
 * Create default value for calendar prodid,
 * Do NOT alter or remove this method or the invoke of this method,
 * a licence violation.
 *
 * [rfc5545]
 * "Conformance: The property MUST be specified once in an iCalendar object.
 *  Description: The vendor of the implementation SHOULD assure that this
 *  is a globally unique identifier; using some technique such as an FPI
 *  value, as defined in [ISO 9070]."
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.20 - 2017-01-29
 */
  public function makeProdid() {
    static $FMT = '-//%s//NONSGML kigkonsult.se %s//%s';
    if( false !== ( $lang = $this->getConfig( util::$LANGUAGE )))
      $lang = strtoupper( $lang );
    else
      $lang = null;
    $this->prodid  = sprintf( $FMT, $this->getConfig( util::$UNIQUE_ID ),
                                    ICALCREATOR_VERSION,
                                    $lang );
  }
}
