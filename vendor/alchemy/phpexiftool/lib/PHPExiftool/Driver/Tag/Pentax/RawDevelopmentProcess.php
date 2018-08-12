<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Pentax;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class RawDevelopmentProcess extends AbstractTag
{

    protected $Id = 98;

    protected $Name = 'RawDevelopmentProcess';

    protected $FullName = 'Pentax::Main';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Raw Development Process';

    protected $flag_Permanent = true;

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => '1 (K10D,K200D,K2000,K-m)',
        ),
        3 => array(
            'Id' => 3,
            'Label' => '3 (K20D)',
        ),
        4 => array(
            'Id' => 4,
            'Label' => '4 (K-7)',
        ),
        5 => array(
            'Id' => 5,
            'Label' => '5 (K-x)',
        ),
        6 => array(
            'Id' => 6,
            'Label' => '6 (645D)',
        ),
        7 => array(
            'Id' => 7,
            'Label' => '7 (K-r)',
        ),
        8 => array(
            'Id' => 8,
            'Label' => '8 (K-5,K-5II,K-5IIs)',
        ),
        9 => array(
            'Id' => 9,
            'Label' => '9 (Q)',
        ),
        10 => array(
            'Id' => 10,
            'Label' => '10 (K-01,K-30)',
        ),
        11 => array(
            'Id' => 11,
            'Label' => '11 (Q10)',
        ),
        12 => array(
            'Id' => 12,
            'Label' => '12 (MX-1)',
        ),
        13 => array(
            'Id' => 13,
            'Label' => '13 (K-3,K-3II)',
        ),
        14 => array(
            'Id' => 14,
            'Label' => '14 (645Z)',
        ),
        15 => array(
            'Id' => 15,
            'Label' => '15 (K-S1,K-S2)',
        ),
    );

}
