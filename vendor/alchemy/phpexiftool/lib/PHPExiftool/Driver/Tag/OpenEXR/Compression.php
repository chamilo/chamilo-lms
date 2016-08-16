<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\OpenEXR;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Compression extends AbstractTag
{

    protected $Id = 'compression';

    protected $Name = 'Compression';

    protected $FullName = 'OpenEXR::Main';

    protected $GroupName = 'OpenEXR';

    protected $g0 = 'OpenEXR';

    protected $g1 = 'OpenEXR';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Compression';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'RLE',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'ZIPS',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'ZIP',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'PIZ',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'PXR24',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'B44',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'B44A',
        ),
    );

}
