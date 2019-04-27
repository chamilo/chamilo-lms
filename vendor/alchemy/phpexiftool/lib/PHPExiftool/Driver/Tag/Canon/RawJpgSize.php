<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Canon;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class RawJpgSize extends AbstractTag
{

    protected $Id = 7;

    protected $Name = 'RawJpgSize';

    protected $FullName = 'Canon::FileInfo';

    protected $GroupName = 'Canon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Canon';

    protected $g2 = 'Image';

    protected $Type = 'int16s';

    protected $Writable = true;

    protected $Description = 'Raw Jpg Size';

    protected $flag_Permanent = true;

    protected $Values = array(
        '-1' => array(
            'Id' => '-1',
            'Label' => 'n/a',
        ),
        0 => array(
            'Id' => 0,
            'Label' => 'Large',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Medium',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Small',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Medium 1',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Medium 2',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Medium 3',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Postcard',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Widescreen',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Medium Widescreen',
        ),
        14 => array(
            'Id' => 14,
            'Label' => 'Small 1',
        ),
        15 => array(
            'Id' => 15,
            'Label' => 'Small 2',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Small 3',
        ),
        128 => array(
            'Id' => 128,
            'Label' => '640x480 Movie',
        ),
        129 => array(
            'Id' => 129,
            'Label' => 'Medium Movie',
        ),
        130 => array(
            'Id' => 130,
            'Label' => 'Small Movie',
        ),
        137 => array(
            'Id' => 137,
            'Label' => '1280x720 Movie',
        ),
        142 => array(
            'Id' => 142,
            'Label' => '1920x1080 Movie',
        ),
    );

}
