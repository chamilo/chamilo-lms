<?php

/**
 * This file is part of the PHPExiftool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Metadata;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Container for Metadatas
 *
 * @author      Romain Neutron - imprec@gmail.com
 * @license     http://opensource.org/licenses/MIT MIT
 */
class MetadataBag extends ArrayCollection
{

    /**
     * Returns all the elements which key matches the regexp
     *
     * @param  string      $regexp
     * @return MetadataBag
     */
    public function filterKeysByRegExp($regexp)
    {
        $partitions = $this->partition(function($key, $element) use ($regexp) {
            return preg_match($regexp, $key);
        });

        return array_shift($partitions);
    }
}
