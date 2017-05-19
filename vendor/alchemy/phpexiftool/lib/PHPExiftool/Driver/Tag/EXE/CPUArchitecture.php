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
class CPUArchitecture extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'CPUArchitecture';

    protected $FullName = 'mixed';

    protected $GroupName = 'EXE';

    protected $g0 = 'EXE';

    protected $g1 = 'EXE';

    protected $g2 = 'Other';

    protected $Type = 'mixed';

    protected $Writable = false;

    protected $Description = 'CPU Architecture';

    protected $Values = array(
        0 => array(
            'Id' => 1,
            'Label' => '32 bit',
        ),
        1 => array(
            'Id' => 2,
            'Label' => '64 bit',
        ),
        'm68k' => array(
            'Id' => 'm68k',
            'Label' => 68000,
        ),
        'pwpc' => array(
            'Id' => 'pwpc',
            'Label' => 'PowerPC',
        ),
    );

    protected $MaxLength = 'mixed';

}
