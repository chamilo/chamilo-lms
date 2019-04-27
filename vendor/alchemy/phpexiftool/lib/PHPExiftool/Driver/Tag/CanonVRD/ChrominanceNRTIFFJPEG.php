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
class ChrominanceNRTIFFJPEG extends AbstractTag
{

    protected $Id = 96;

    protected $Name = 'ChrominanceNR_TIFF_JPEG';

    protected $FullName = 'CanonVRD::Ver2';

    protected $GroupName = 'CanonVRD';

    protected $g0 = 'CanonVRD';

    protected $g1 = 'CanonVRD';

    protected $g2 = 'Image';

    protected $Type = 'int16s';

    protected $Writable = true;

    protected $Description = 'Chrominance NR TIFF JPEG';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        1 => array(
            'Id' => 33,
            'Label' => 'Low',
        ),
        2 => array(
            'Id' => 100,
            'Label' => 'High',
        ),
        3 => array(
            'Id' => 0,
            'Label' => 0,
        ),
        4 => array(
            'Id' => 16,
            'Label' => 1,
        ),
        5 => array(
            'Id' => 33,
            'Label' => 2,
        ),
        6 => array(
            'Id' => 50,
            'Label' => 3,
        ),
        7 => array(
            'Id' => 66,
            'Label' => 4,
        ),
        8 => array(
            'Id' => 83,
            'Label' => 5,
        ),
        9 => array(
            'Id' => 100,
            'Label' => 6,
        ),
        10 => array(
            'Id' => 116,
            'Label' => 7,
        ),
        11 => array(
            'Id' => 133,
            'Label' => 8,
        ),
        12 => array(
            'Id' => 150,
            'Label' => 9,
        ),
        13 => array(
            'Id' => 166,
            'Label' => 10,
        ),
        14 => array(
            'Id' => 167,
            'Label' => 11,
        ),
        15 => array(
            'Id' => 168,
            'Label' => 12,
        ),
        16 => array(
            'Id' => 169,
            'Label' => 13,
        ),
        17 => array(
            'Id' => 170,
            'Label' => 14,
        ),
        18 => array(
            'Id' => 171,
            'Label' => 15,
        ),
        19 => array(
            'Id' => 172,
            'Label' => 16,
        ),
        20 => array(
            'Id' => 173,
            'Label' => 17,
        ),
        21 => array(
            'Id' => 174,
            'Label' => 18,
        ),
        22 => array(
            'Id' => 175,
            'Label' => 19,
        ),
        23 => array(
            'Id' => 176,
            'Label' => 20,
        ),
    );

    protected $Index = 'mixed';

}
