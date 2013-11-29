<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
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
class ContinuousDrive extends AbstractTag
{

    protected $Id = 5;

    protected $Name = 'ContinuousDrive';

    protected $FullName = 'Canon::CameraSettings';

    protected $GroupName = 'Canon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Canon';

    protected $g2 = 'Camera';

    protected $Type = 'int16s';

    protected $Writable = true;

    protected $Description = 'Continuous Drive';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Single',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Continuous',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Movie',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Continuous, Speed Priority',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Continuous, Low',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Continuous, High',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Silent Single',
        ),
    );

}
