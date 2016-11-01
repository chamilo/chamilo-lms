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
class SequenceLength extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'SequenceLength';

    protected $FullName = 'mixed';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Sequence Length';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Continuous',
        ),
        1 => array(
            'Id' => 1,
            'Label' => '1 shot',
        ),
        2 => array(
            'Id' => 2,
            'Label' => '2 shots',
        ),
        3 => array(
            'Id' => 3,
            'Label' => '3 shots',
        ),
        4 => array(
            'Id' => 4,
            'Label' => '4 shots',
        ),
        5 => array(
            'Id' => 5,
            'Label' => '5 shots',
        ),
        6 => array(
            'Id' => 6,
            'Label' => '6 shots',
        ),
        7 => array(
            'Id' => 10,
            'Label' => '10 shots',
        ),
        8 => array(
            'Id' => 100,
            'Label' => 'Continuous - iSweep Panorama',
        ),
        9 => array(
            'Id' => 200,
            'Label' => 'Continuous - Sweep Panorama',
        ),
        10 => array(
            'Id' => 0,
            'Label' => 'Continuous',
        ),
        11 => array(
            'Id' => 1,
            'Label' => '1 file',
        ),
        12 => array(
            'Id' => 2,
            'Label' => '2 files',
        ),
        13 => array(
            'Id' => 3,
            'Label' => '3 files',
        ),
        14 => array(
            'Id' => 5,
            'Label' => '5 files',
        ),
        15 => array(
            'Id' => 10,
            'Label' => '10 files',
        ),
    );

}
