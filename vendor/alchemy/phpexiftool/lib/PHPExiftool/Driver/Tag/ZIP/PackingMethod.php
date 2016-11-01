<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\ZIP;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class PackingMethod extends AbstractTag
{

    protected $Id = 18;

    protected $Name = 'PackingMethod';

    protected $FullName = 'ZIP::RAR';

    protected $GroupName = 'ZIP';

    protected $g0 = 'ZIP';

    protected $g1 = 'ZIP';

    protected $g2 = 'Other';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Packing Method';

    protected $Values = array(
        48 => array(
            'Id' => 48,
            'Label' => 'Stored',
        ),
        49 => array(
            'Id' => 49,
            'Label' => 'Fastest',
        ),
        50 => array(
            'Id' => 50,
            'Label' => 'Fast',
        ),
        51 => array(
            'Id' => 51,
            'Label' => 'Normal',
        ),
        52 => array(
            'Id' => 52,
            'Label' => 'Good Compression',
        ),
        53 => array(
            'Id' => 53,
            'Label' => 'Best Compression',
        ),
    );

}
