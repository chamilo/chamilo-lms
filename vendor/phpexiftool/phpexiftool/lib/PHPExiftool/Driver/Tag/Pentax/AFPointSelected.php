<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
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
class AFPointSelected extends AbstractTag
{

    protected $Id = 14;

    protected $Name = 'AFPointSelected';

    protected $FullName = 'Pentax::Main';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'AF Point Selected';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Upper-left',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Top',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Upper-right',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Left',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Mid-left',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Center',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Mid-right',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Right',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Lower-left',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Bottom',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Lower-right',
        ),
        65531 => array(
            'Id' => 65531,
            'Label' => 'AF Select',
        ),
        65532 => array(
            'Id' => 65532,
            'Label' => 'Face Detect AF',
        ),
        65533 => array(
            'Id' => 65533,
            'Label' => 'Automatic Tracking AF',
        ),
        65534 => array(
            'Id' => 65534,
            'Label' => 'Fixed Center',
        ),
        65535 => array(
            'Id' => 65535,
            'Label' => 'Auto',
        ),
    );

}
