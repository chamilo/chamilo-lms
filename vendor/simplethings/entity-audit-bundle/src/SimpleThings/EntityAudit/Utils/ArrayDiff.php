<?php
/*
 * (c) 2011 SimpleThings GmbH
 *
 * @package SimpleThings\EntityAudit
 * @author Benjamin Eberlei <eberlei@simplethings.de>
 * @link http://www.simplethings.de
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */

namespace SimpleThings\EntityAudit\Utils;

use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Creates a diff between 2 arrays.
 *
 * @author Tim Nagel <tim@nagel.com.au>
 */
class ArrayDiff
{
    public function diff($oldData, $newData)
    {
        $diff = array();

        $keys = array_keys($oldData + $newData);
        foreach ($keys as $field) {
            $old = array_key_exists($field, $oldData) ? $oldData[$field] : null;
            $new = array_key_exists($field, $newData) ? $newData[$field] : null;

            if ($old == $new) {
                $row = array('old' => '', 'new' => '', 'same' => $old);
            } else {
                $row = array('old' => $old, 'new' => $new, 'same' => '');
            }

            $diff[$field] = $row;
        }

        return $diff;
    }
}
