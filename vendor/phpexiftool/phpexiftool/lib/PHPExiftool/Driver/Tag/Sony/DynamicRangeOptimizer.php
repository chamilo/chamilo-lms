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
class DynamicRangeOptimizer extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'DynamicRangeOptimizer';

    protected $FullName = 'Sony::Main';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Dynamic Range Optimizer';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Standard',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Advanced Auto',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Auto',
        ),
        4 => array(
            'Id' => 8,
            'Label' => 'Advanced Lv1',
        ),
        5 => array(
            'Id' => 9,
            'Label' => 'Advanced Lv2',
        ),
        6 => array(
            'Id' => 10,
            'Label' => 'Advanced Lv3',
        ),
        7 => array(
            'Id' => 11,
            'Label' => 'Advanced Lv4',
        ),
        8 => array(
            'Id' => 12,
            'Label' => 'Advanced Lv5',
        ),
        9 => array(
            'Id' => 16,
            'Label' => 'Lv1',
        ),
        10 => array(
            'Id' => 17,
            'Label' => 'Lv2',
        ),
        11 => array(
            'Id' => 18,
            'Label' => 'Lv3',
        ),
        12 => array(
            'Id' => 19,
            'Label' => 'Lv4',
        ),
        13 => array(
            'Id' => 20,
            'Label' => 'Lv5',
        ),
        14 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        15 => array(
            'Id' => 1,
            'Label' => 'Standard',
        ),
        16 => array(
            'Id' => 2,
            'Label' => 'Plus',
        ),
    );

}
