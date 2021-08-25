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
class MonochromeFilterEffect extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'MonochromeFilterEffect';

    protected $FullName = 'mixed';

    protected $GroupName = 'CanonVRD';

    protected $g0 = 'CanonVRD';

    protected $g1 = 'CanonVRD';

    protected $g2 = 'Image';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Monochrome Filter Effect';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Yellow',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Orange',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Red',
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
            'Label' => 'Yellow',
        ),
        7 => array(
            'Id' => 0,
            'Label' => 'Orange',
        ),
        8 => array(
            'Id' => 1,
            'Label' => 'Red',
        ),
        9 => array(
            'Id' => 2,
            'Label' => 'Green',
        ),
    );

}
