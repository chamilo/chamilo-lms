<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Ricoh;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ImageEffects extends AbstractTag
{

    protected $Id = 4112;

    protected $Name = 'ImageEffects';

    protected $FullName = 'Ricoh::Main';

    protected $GroupName = 'Ricoh';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Ricoh';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Image Effects';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Standard',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Vivid',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Black & White',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'B&W Toning Effect',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Setting 1',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Setting 2',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'High-contrast B&W',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Cross Process',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Positive Film',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'Bleach Bypass',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'Retro',
        ),
        15 => array(
            'Id' => 15,
            'Label' => 'Miniature',
        ),
        17 => array(
            'Id' => 17,
            'Label' => 'High Key',
        ),
    );

}
