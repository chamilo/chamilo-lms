<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\SonyIDC;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class IDCCreativeStyle extends AbstractTag
{

    protected $Id = 32768;

    protected $Name = 'IDCCreativeStyle';

    protected $FullName = 'SonyIDC::Main';

    protected $GroupName = 'SonyIDC';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'SonyIDC';

    protected $g2 = 'Image';

    protected $Type = 'int32u';

    protected $Writable = true;

    protected $Description = 'IDC Creative Style';

    protected $flag_Permanent = true;

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Camera Setting',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Standard',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Real',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Vivid',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Adobe RGB',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'A100 Standard',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Neutral',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Portrait',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Landscape',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Clear',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Deep',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'Light',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'Sunset',
        ),
        14 => array(
            'Id' => 14,
            'Label' => 'Night View',
        ),
        15 => array(
            'Id' => 15,
            'Label' => 'Autumn Leaves',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'B&W',
        ),
        17 => array(
            'Id' => 17,
            'Label' => 'Sepia',
        ),
    );

}
