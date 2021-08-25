<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\EXE;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ObjectFileType extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'ObjectFileType';

    protected $FullName = 'mixed';

    protected $GroupName = 'EXE';

    protected $g0 = 'EXE';

    protected $g1 = 'EXE';

    protected $g2 = 'Other';

    protected $Type = 'mixed';

    protected $Writable = false;

    protected $Description = 'Object File Type';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Relocatable file',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Executable file',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Shared object file',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Core file',
        ),
        5 => array(
            'Id' => '-1',
            'Label' => 'Static library',
        ),
        6 => array(
            'Id' => 1,
            'Label' => 'Relocatable object',
        ),
        7 => array(
            'Id' => 2,
            'Label' => 'Demand paged executable',
        ),
        8 => array(
            'Id' => 3,
            'Label' => 'Fixed VM shared library',
        ),
        9 => array(
            'Id' => 4,
            'Label' => 'Core',
        ),
        10 => array(
            'Id' => 5,
            'Label' => 'Preloaded executable',
        ),
        11 => array(
            'Id' => 6,
            'Label' => 'Dynamically bound shared library',
        ),
        12 => array(
            'Id' => 7,
            'Label' => 'Dynamic link editor',
        ),
        13 => array(
            'Id' => 8,
            'Label' => 'Dynamically bound bundle',
        ),
        14 => array(
            'Id' => 9,
            'Label' => 'Shared library stub for static linking',
        ),
        15 => array(
            'Id' => 0,
            'Label' => 'Unknown',
        ),
        16 => array(
            'Id' => 1,
            'Label' => 'Executable application',
        ),
        17 => array(
            'Id' => 2,
            'Label' => 'Dynamic link library',
        ),
        18 => array(
            'Id' => 3,
            'Label' => 'Driver',
        ),
        19 => array(
            'Id' => 4,
            'Label' => 'Font',
        ),
        20 => array(
            'Id' => 5,
            'Label' => 'VxD',
        ),
        21 => array(
            'Id' => 7,
            'Label' => 'Static library',
        ),
    );

}
