<?php
/**
 * This file is part of php-pens.
 *
 * php-pens is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * php-pens is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with php-pens.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * PENSRequestReceipt.
 *
 * Provides the PENSRequestReceipt class
 *
 * @author Guillaume Viguier-Just <guillaume@viguierjust.com>
 * @licence http://www.gnu.org/licenses/gpl.txt
 */
require_once __DIR__.'/pens_request.php';

/**
 * PENSRequestReceipt.
 *
 * PENS Request of type receipt
 *
 * @author Guillaume Viguier-Just <guillaume@viguierjust.com>
 * @licence http://www.gnu.org/licenses/gpl.txt
 */
class PENSRequestReceipt extends PENSRequest
{
    public function __construct($arguments)
    {
        parent::__construct($arguments);
        $this->setCommand('receipt');
    }
}
