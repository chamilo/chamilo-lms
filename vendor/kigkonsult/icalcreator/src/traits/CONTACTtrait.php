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
 * CONTACT property functions
 *
 * @author Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @since 2.22.23 - 2017-02-02
 */
trait CONTACTtrait {
/**
 * @var array component property CONTACT value
 * @access protected
 */
  protected $contact = null;
/**
 * Return formatted output for calendar component property contact
 *
 * @return string
 */
  public function createContact() {
    if( empty( $this->contact ))
      return null;
    $output = null;
    $lang   = $this->getConfig( util::$LANGUAGE );
    foreach( $this->contact as $cx => $contact ) {
      if( ! empty( $contact[util::$LCvalue] ))
        $output .= util::createElement( util::$CONTACT,
                                        util::createParams( $contact[util::$LCparams],
                                                            util::$ALTRPLANGARR,
                                                            $lang ),
                                        util::strrep( $contact[util::$LCvalue] ));
      elseif( $this->getConfig( util::$ALLOWEMPTY ))
        $output .= util::createElement( util::$CONTACT );
    }
    return $output;
  }
/**
 * Set calendar component property contact
 *
 * @param string  $value
 * @param array   $params
 * @param integer $index
 * @return bool
 */
  public function setContact( $value, $params=null, $index=null ) {
    if( empty( $value )) {
      if( $this->getConfig( util::$ALLOWEMPTY ))
        $value = util::$EMPTYPROPERTY;
      else
        return false;
    }
    util::setMval( $this->contact,
                    util::trimTrailNL( $value ),
                    $params,
                    false,
                    $index );
    return true;
  }
}
