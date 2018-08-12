<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Sony;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class LensType2 extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'LensType2';

    protected $FullName = 'mixed';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'mixed';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Lens Type 2';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Unknown E-mount lens or other lens',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Sony LA-EA1 Adapter',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Sony LA-EA2 Adapter',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Sony LA-EA3 Adapter',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Sony LA-EA4 Adapter',
        ),
        44 => array(
            'Id' => 44,
            'Label' => 'Metabones Canon EF Smart Adapter',
        ),
        78 => array(
            'Id' => 78,
            'Label' => 'Metabones Canon EF Smart Adapter Mark III or Other Adapter',
        ),
        234 => array(
            'Id' => 234,
            'Label' => 'Metabones Canon EF Smart Adapter Mark IV',
        ),
        239 => array(
            'Id' => 239,
            'Label' => 'Metabones Canon EF Speed Booster',
        ),
        32784 => array(
            'Id' => 32784,
            'Label' => 'Sony E 16mm F2.8',
        ),
        32785 => array(
            'Id' => 32785,
            'Label' => 'Sony E 18-55mm F3.5-5.6 OSS',
        ),
        32786 => array(
            'Id' => 32786,
            'Label' => 'Sony E 55-210mm F4.5-6.3 OSS',
        ),
        32787 => array(
            'Id' => 32787,
            'Label' => 'Sony E 18-200mm F3.5-6.3 OSS',
        ),
        32788 => array(
            'Id' => 32788,
            'Label' => 'Sony E 30mm F3.5 Macro',
        ),
        32789 => array(
            'Id' => 32789,
            'Label' => 'Sony E 24mm F1.8 ZA',
        ),
        32790 => array(
            'Id' => 32790,
            'Label' => 'Sony E 50mm F1.8 OSS',
        ),
        32791 => array(
            'Id' => 32791,
            'Label' => 'Sony E 16-70mm F4 ZA OSS',
        ),
        32792 => array(
            'Id' => 32792,
            'Label' => 'Sony E 10-18mm F4 OSS',
        ),
        32793 => array(
            'Id' => 32793,
            'Label' => 'Sony E PZ 16-50mm F3.5-5.6 OSS',
        ),
        32794 => array(
            'Id' => 32794,
            'Label' => 'Sony FE 35mm F2.8 ZA',
        ),
        32795 => array(
            'Id' => 32795,
            'Label' => 'Sony FE 24-70mm F4 ZA OSS',
        ),
        32797 => array(
            'Id' => 32797,
            'Label' => 'Sony E 18-200mm F3.5-6.3 OSS LE',
        ),
        32798 => array(
            'Id' => 32798,
            'Label' => 'Sony E 20mm F2.8',
        ),
        32799 => array(
            'Id' => 32799,
            'Label' => 'Sony E 35mm F1.8 OSS',
        ),
        32800 => array(
            'Id' => 32800,
            'Label' => 'Sony E PZ 18-105mm F4 G OSS',
        ),
        32802 => array(
            'Id' => 32802,
            'Label' => 'Sony FE 90mm F2.8 Macro G OSS',
        ),
        32803 => array(
            'Id' => 32803,
            'Label' => 'Sony E 18-50mm F4-5.6',
        ),
        32807 => array(
            'Id' => 32807,
            'Label' => 'Sony E PZ 18-200mm F3.5-6.3 OSS',
        ),
        32808 => array(
            'Id' => 32808,
            'Label' => 'Sony FE 55mm F1.8 ZA',
        ),
        32810 => array(
            'Id' => 32810,
            'Label' => 'Sony FE 70-200mm F4 G OSS',
        ),
        32811 => array(
            'Id' => 32811,
            'Label' => 'Sony FE 16-35mm F4 ZA OSS',
        ),
        32813 => array(
            'Id' => 32813,
            'Label' => 'Sony FE 28-70mm F3.5-5.6 OSS',
        ),
        32814 => array(
            'Id' => 32814,
            'Label' => 'Sony FE 35mm F1.4 ZA',
        ),
        32815 => array(
            'Id' => 32815,
            'Label' => 'Sony FE 24-240mm F3.5-6.3 OSS',
        ),
        32816 => array(
            'Id' => 32816,
            'Label' => 'Sony FE 28mm F2',
        ),
        32817 => array(
            'Id' => 32817,
            'Label' => 'Sony FE PZ 28-135mm F4 G OSS',
        ),
        32826 => array(
            'Id' => 32826,
            'Label' => 'Sony FE 21mm F2.8 (SEL28F20 + SEL075UWC)',
        ),
        32827 => array(
            'Id' => 32827,
            'Label' => 'Sony FE 16mm F3.5 Fisheye (SEL28F20 + SEL057FEC)',
        ),
        49216 => array(
            'Id' => 49216,
            'Label' => 'Zeiss Batis 25mm F2',
        ),
        49217 => array(
            'Id' => 49217,
            'Label' => 'Zeiss Batis 85mm F1.8',
        ),
        49234 => array(
            'Id' => 49234,
            'Label' => 'Zeiss Loxia 21mm F2.8',
        ),
    );

}
