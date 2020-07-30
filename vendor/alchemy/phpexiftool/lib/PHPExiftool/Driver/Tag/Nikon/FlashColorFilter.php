<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Nikon;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class FlashColorFilter extends AbstractTag
{

    protected $Id = 16;

    protected $Name = 'FlashColorFilter';

    protected $FullName = 'Nikon::FlashInfo0103';

    protected $GroupName = 'Nikon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Nikon';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Flash Color Filter';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'FL-GL1',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'FL-GL2',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'TN-A1',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'TN-A2',
        ),
        65 => array(
            'Id' => 65,
            'Label' => 'Red',
        ),
        66 => array(
            'Id' => 66,
            'Label' => 'Blue',
        ),
        67 => array(
            'Id' => 67,
            'Label' => 'Yellow',
        ),
        68 => array(
            'Id' => 68,
            'Label' => 'Amber',
        ),
    );

}
