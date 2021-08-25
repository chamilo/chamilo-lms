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
class CPUType extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'CPUType';

    protected $FullName = 'mixed';

    protected $GroupName = 'EXE';

    protected $g0 = 'EXE';

    protected $g1 = 'EXE';

    protected $g2 = 'Other';

    protected $Type = 'mixed';

    protected $Writable = false;

    protected $Description = 'CPU Type';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'AT&T WE 32100',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'SPARC',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'i386',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Motorola 68000',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Motorola 88000',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'i486',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'i860',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'MIPS R3000',
        ),
        9 => array(
            'Id' => 10,
            'Label' => 'MIPS R4000',
        ),
        10 => array(
            'Id' => 15,
            'Label' => 'HPPA',
        ),
        11 => array(
            'Id' => 18,
            'Label' => 'Sun v8plus',
        ),
        12 => array(
            'Id' => 20,
            'Label' => 'PowerPC',
        ),
        13 => array(
            'Id' => 21,
            'Label' => 'PowerPC 64-bit',
        ),
        14 => array(
            'Id' => 22,
            'Label' => 'IBM S/390',
        ),
        15 => array(
            'Id' => 23,
            'Label' => 'Cell BE SPU',
        ),
        16 => array(
            'Id' => 42,
            'Label' => 'SuperH',
        ),
        17 => array(
            'Id' => 43,
            'Label' => 'SPARC v9 64-bit',
        ),
        18 => array(
            'Id' => 46,
            'Label' => 'Renesas H8/300,300H,H8S',
        ),
        19 => array(
            'Id' => 50,
            'Label' => 'HP/Intel IA-64',
        ),
        20 => array(
            'Id' => 62,
            'Label' => 'AMD x86-64',
        ),
        21 => array(
            'Id' => 76,
            'Label' => 'Axis Communications 32-bit embedded processor',
        ),
        22 => array(
            'Id' => 87,
            'Label' => 'NEC v850',
        ),
        23 => array(
            'Id' => 88,
            'Label' => 'Renesas M32R',
        ),
        24 => array(
            'Id' => 21569,
            'Label' => 'Fujitsu FR-V',
        ),
        25 => array(
            'Id' => 36902,
            'Label' => 'Alpha',
        ),
        26 => array(
            'Id' => 36929,
            'Label' => 'm32r (old)',
        ),
        27 => array(
            'Id' => 36992,
            'Label' => 'v850 (old)',
        ),
        28 => array(
            'Id' => 41872,
            'Label' => 'S/390 (old)',
        ),
        29 => array(
            'Id' => '-1',
            'Label' => 'Any',
        ),
        30 => array(
            'Id' => 1,
            'Label' => 'VAX',
        ),
        31 => array(
            'Id' => 2,
            'Label' => 'ROMP',
        ),
        32 => array(
            'Id' => 4,
            'Label' => 'NS32032',
        ),
        33 => array(
            'Id' => 5,
            'Label' => 'NS32332',
        ),
        34 => array(
            'Id' => 6,
            'Label' => 'MC680x0',
        ),
        35 => array(
            'Id' => 7,
            'Label' => 'x86',
        ),
        36 => array(
            'Id' => 8,
            'Label' => 'MIPS',
        ),
        37 => array(
            'Id' => 9,
            'Label' => 'NS32532',
        ),
        38 => array(
            'Id' => 10,
            'Label' => 'MC98000',
        ),
        39 => array(
            'Id' => 11,
            'Label' => 'HPPA',
        ),
        40 => array(
            'Id' => 12,
            'Label' => 'ARM',
        ),
        41 => array(
            'Id' => 13,
            'Label' => 'MC88000',
        ),
        42 => array(
            'Id' => 14,
            'Label' => 'SPARC',
        ),
        43 => array(
            'Id' => 15,
            'Label' => 'i860 big endian',
        ),
        44 => array(
            'Id' => 16,
            'Label' => 'i860 little endian',
        ),
        45 => array(
            'Id' => 17,
            'Label' => 'RS6000',
        ),
        46 => array(
            'Id' => 18,
            'Label' => 'PowerPC',
        ),
        47 => array(
            'Id' => 255,
            'Label' => 'VEO',
        ),
    );

    protected $flag_List = false;

}
