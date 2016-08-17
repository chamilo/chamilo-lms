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
class PictureProfile extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'PictureProfile';

    protected $FullName = 'mixed';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Picture Profile';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Standard/Neutral - Gamma Still (PP2)',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Portrait',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Night View/Portrait',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'B&W/Sepia',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Clear',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Deep',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Light',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Vivid',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Real',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Gamma Movie (PP1)',
        ),
        22 => array(
            'Id' => 22,
            'Label' => 'Gamma ITU709 (PP3)',
        ),
        23 => array(
            'Id' => 23,
            'Label' => 'ColorTone ITU709 (PP4)',
        ),
        24 => array(
            'Id' => 24,
            'Label' => 'Gamma Cine1 (PP5)',
        ),
        25 => array(
            'Id' => 25,
            'Label' => 'Gamma Cine2 (PP6)',
        ),
        26 => array(
            'Id' => 26,
            'Label' => 'Gamma Cine3',
        ),
        27 => array(
            'Id' => 27,
            'Label' => 'Gamma Cine4',
        ),
        28 => array(
            'Id' => 28,
            'Label' => 'Gamma S-Log2 (PP7)',
        ),
        29 => array(
            'Id' => 29,
            'Label' => 'Gamma ITU709(800%)',
        ),
    );

}
