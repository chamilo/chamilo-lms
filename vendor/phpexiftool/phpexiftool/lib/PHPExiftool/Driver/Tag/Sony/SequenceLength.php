<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
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

    protected $Id = 34;

    protected $Name = 'SequenceLength';

    protected $FullName = 'Sony::Tag9400';

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
        5 => array(
            'Id' => 5,
            'Label' => '5 shots',
        ),
        6 => array(
            'Id' => 6,
            'Label' => '6 shots',
        ),
        10 => array(
            'Id' => 10,
            'Label' => '10 shots',
        ),
        100 => array(
            'Id' => 100,
            'Label' => 'Continuous - iSweep Panorama',
        ),
        200 => array(
            'Id' => 200,
            'Label' => 'Continuous - Sweep Panorama',
        ),
    );

}
