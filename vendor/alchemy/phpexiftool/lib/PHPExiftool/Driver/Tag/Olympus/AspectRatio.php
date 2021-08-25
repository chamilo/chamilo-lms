<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Olympus;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AspectRatio extends AbstractTag
{

    protected $Id = 4370;

    protected $Name = 'AspectRatio';

    protected $FullName = 'Olympus::ImageProcessing';

    protected $GroupName = 'Olympus';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Olympus';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Aspect Ratio';

    protected $flag_Permanent = true;

    protected $MaxLength = 2;

    protected $Values = array(
        '1 1' => array(
            'Id' => '1 1',
            'Label' => '4:3',
        ),
        '1 4' => array(
            'Id' => '1 4',
            'Label' => '1:1',
        ),
        '2 1' => array(
            'Id' => '2 1',
            'Label' => '3:2 (RAW)',
        ),
        '2 2' => array(
            'Id' => '2 2',
            'Label' => '3:2',
        ),
        '3 1' => array(
            'Id' => '3 1',
            'Label' => '16:9 (RAW)',
        ),
        '3 3' => array(
            'Id' => '3 3',
            'Label' => '16:9',
        ),
        '4 1' => array(
            'Id' => '4 1',
            'Label' => '1:1 (RAW)',
        ),
        '4 4' => array(
            'Id' => '4 4',
            'Label' => '6:6',
        ),
        '5 5' => array(
            'Id' => '5 5',
            'Label' => '5:4',
        ),
        '6 6' => array(
            'Id' => '6 6',
            'Label' => '7:6',
        ),
        '7 7' => array(
            'Id' => '7 7',
            'Label' => '6:5',
        ),
        '8 8' => array(
            'Id' => '8 8',
            'Label' => '7:5',
        ),
        '9 1' => array(
            'Id' => '9 1',
            'Label' => '3:4 (RAW)',
        ),
        '9 9' => array(
            'Id' => '9 9',
            'Label' => '3:4',
        ),
    );

}
