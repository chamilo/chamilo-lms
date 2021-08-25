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
 * REQUEST-STATUS property functions
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.23 - 2017-02-19
 */
trait REQUEST_STATUStrait {
/**
 * @var array component property REQUEST-STATUS value
 * @access protected
 */
  protected $requeststatus = null;
/**
 * Return formatted output for calendar component property request-status
 *
 * @return string
 */
  public function createRequestStatus() {
    static $STATCODE = 'statcode';
    static $TEXT     = 'text';
    static $EXTDATA  = 'extdata';
    if( empty( $this->requeststatus ))
      return null;
    $output = null;
    $lang   = $this->getConfig( util::$LANGUAGE );
    foreach( $this->requeststatus as $rx => $rStat ) {
      if( empty( $rStat[util::$LCvalue][$STATCODE] )) {
        if( $this->getConfig( util::$ALLOWEMPTY ))
          $output .= util::createElement( util::$REQUEST_STATUS );
        continue;
      }
      $content     = number_format( (float) $rStat[util::$LCvalue][$STATCODE], 2, util::$DOT, null );
      $content    .= util::$SEMIC . util::strrep( $rStat[util::$LCvalue][$TEXT] );
      if( isset( $rStat[util::$LCvalue][$EXTDATA] ))
        $content  .= util::$SEMIC . util::strrep( $rStat[util::$LCvalue][$EXTDATA] );
      $output     .= util::createElement( util::$REQUEST_STATUS,
                                          util::createParams( $rStat[util::$LCparams],
                                                              [util::$LANGUAGE],
                                                              $lang ),
                                          $content );
    }
    return $output;
  }
/**
 * Set calendar component property request-status
 *
 * @param float    $statcode
 * @param string   $text
 * @param string   $extdata
 * @param array    $params
 * @param integer  $index
 * @return bool
 */
  public function setRequestStatus( $statcode, $text, $extdata=null, $params=null, $index=null ) {
    static $STATCODE = 'statcode';
    static $TEXT     = 'text';
    static $EXTDATA  = 'extdata';
    if( empty( $statcode ) || empty( $text )) {
      if( $this->getConfig( util::$ALLOWEMPTY ))
        $statcode = $text = util::$EMPTYPROPERTY;
      else
        return false;
    }
    $input = [$STATCODE => $statcode,
              $TEXT     => util::trimTrailNL( $text )];
    if( $extdata )
      $input[$EXTDATA] = util::trimTrailNL( $extdata );
    util::setMval( $this->requeststatus,
                   $input,
                   $params,
                   false,
                   $index );
    return true;
  }
}
