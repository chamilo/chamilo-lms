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
class MachineType extends AbstractTag
{

    protected $Id = 0;

    protected $Name = 'MachineType';

    protected $FullName = 'EXE::Main';

    protected $GroupName = 'EXE';

    protected $g0 = 'EXE';

    protected $g1 = 'EXE';

    protected $g2 = 'Other';

    protected $Type = 'int16u';

    protected $Writable = false;

    protected $Description = 'Machine Type';

    protected $Values = array(
        332 => array(
            'Id' => 332,
            'Label' => 'Intel 386 or later, and compatibles',
        ),
        333 => array(
            'Id' => 333,
            'Label' => 'Intel i860',
        ),
        354 => array(
            'Id' => 354,
            'Label' => 'MIPS R3000',
        ),
        358 => array(
            'Id' => 358,
            'Label' => 'MIPS little endian (R4000)',
        ),
        360 => array(
            'Id' => 360,
            'Label' => 'MIPS R10000',
        ),
        361 => array(
            'Id' => 361,
            'Label' => 'MIPS little endian WCI v2',
        ),
        387 => array(
            'Id' => 387,
            'Label' => 'Alpha AXP (old)',
        ),
        388 => array(
            'Id' => 388,
            'Label' => 'Alpha AXP',
        ),
        418 => array(
            'Id' => 418,
            'Label' => 'Hitachi SH3',
        ),
        419 => array(
            'Id' => 419,
            'Label' => 'Hitachi SH3 DSP',
        ),
        422 => array(
            'Id' => 422,
            'Label' => 'Hitachi SH4',
        ),
        424 => array(
            'Id' => 424,
            'Label' => 'Hitachi SH5',
        ),
        448 => array(
            'Id' => 448,
            'Label' => 'ARM little endian',
        ),
        450 => array(
            'Id' => 450,
            'Label' => 'Thumb',
        ),
        467 => array(
            'Id' => 467,
            'Label' => 'Matsushita AM33',
        ),
        496 => array(
            'Id' => 496,
            'Label' => 'PowerPC little endian',
        ),
        497 => array(
            'Id' => 497,
            'Label' => 'PowerPC with floating point support',
        ),
        512 => array(
            'Id' => 512,
            'Label' => 'Intel IA64',
        ),
        614 => array(
            'Id' => 614,
            'Label' => 'MIPS16',
        ),
        616 => array(
            'Id' => 616,
            'Label' => 'Motorola 68000 series',
        ),
        644 => array(
            'Id' => 644,
            'Label' => 'Alpha AXP 64-bit',
        ),
        870 => array(
            'Id' => 870,
            'Label' => 'MIPS with FPU',
        ),
        1126 => array(
            'Id' => 1126,
            'Label' => 'MIPS16 with FPU',
        ),
        3772 => array(
            'Id' => 3772,
            'Label' => 'EFI Byte Code',
        ),
        34404 => array(
            'Id' => 34404,
            'Label' => 'AMD AMD64',
        ),
        36929 => array(
            'Id' => 36929,
            'Label' => 'Mitsubishi M32R little endian',
        ),
        49390 => array(
            'Id' => 49390,
            'Label' => 'clr pure MSIL',
        ),
    );

}
