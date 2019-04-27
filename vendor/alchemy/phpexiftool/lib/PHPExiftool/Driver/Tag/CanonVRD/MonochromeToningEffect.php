<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\CanonVRD;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class MonochromeToningEffect extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'MonochromeToningEffect';

    protected $FullName = 'mixed';

    protected $GroupName = 'CanonVRD';

    protected $g0 = 'CanonVRD';

    protected $g1 = 'CanonVRD';

    protected $g2 = 'Image';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Monochrome Toning Effect';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Sepia',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Blue',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Purple',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Green',
        ),
        5 => array(
            'Id' => '-2',
            'Label' => 'None',
        ),
        6 => array(
            'Id' => '-1',
            'Label' => 'Sepia',
        ),
        7 => array(
            'Id' => 0,
            'Label' => 'Blue',
        ),
        8 => array(
            'Id' => 1,
            'Label' => 'Purple',
        ),
        9 => array(
            'Id' => 2,
            'Label' => 'Green',
        ),
    );

}
