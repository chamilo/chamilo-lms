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
class MonochromeFilterEffect extends AbstractTag
{

    protected $Id = 115;

    protected $Name = 'MonochromeFilterEffect';

    protected $FullName = 'Pentax::Main';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Monochrome Filter Effect';

    protected $flag_Permanent = true;

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Green',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Yellow',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Orange',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Red',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Magenta',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Blue',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Cyan',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Infrared',
        ),
        65535 => array(
            'Id' => 65535,
            'Label' => 'None',
        ),
    );

}
