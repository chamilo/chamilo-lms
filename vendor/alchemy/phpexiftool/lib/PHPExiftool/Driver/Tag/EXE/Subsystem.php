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
class Subsystem extends AbstractTag
{

    protected $Id = 44;

    protected $Name = 'Subsystem';

    protected $FullName = 'EXE::Main';

    protected $GroupName = 'EXE';

    protected $g0 = 'EXE';

    protected $g1 = 'EXE';

    protected $g2 = 'Other';

    protected $Type = 'int16u';

    protected $Writable = false;

    protected $Description = 'Subsystem';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Unknown',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Native',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Windows GUI',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Windows command line',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'OS/2 command line',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'POSIX command line',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Windows CE GUI',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'EFI application',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'EFI boot service',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'EFI runtime driver',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'EFI ROM',
        ),
        14 => array(
            'Id' => 14,
            'Label' => 'XBOX',
        ),
    );

}
