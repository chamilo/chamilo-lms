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
class CPUSubtype extends AbstractTag
{

    protected $Id = 4;

    protected $Name = 'CPUSubtype';

    protected $FullName = 'EXE::MachO';

    protected $GroupName = 'EXE';

    protected $g0 = 'EXE';

    protected $g1 = 'EXE';

    protected $g2 = 'Other';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'CPU Subtype';

    protected $flag_List = true;

    protected $Values = array(
        '1 0' => array(
            'Id' => '1 0',
            'Label' => 'VAX (all)',
        ),
        '1 1' => array(
            'Id' => '1 1',
            'Label' => 'VAX780',
        ),
        '1 2' => array(
            'Id' => '1 2',
            'Label' => 'VAX785',
        ),
        '1 3' => array(
            'Id' => '1 3',
            'Label' => 'VAX750',
        ),
        '1 4' => array(
            'Id' => '1 4',
            'Label' => 'VAX730',
        ),
        '1 5' => array(
            'Id' => '1 5',
            'Label' => 'UVAXI',
        ),
        '1 6' => array(
            'Id' => '1 6',
            'Label' => 'UVAXII',
        ),
        '1 7' => array(
            'Id' => '1 7',
            'Label' => 'VAX8200',
        ),
        '1 8' => array(
            'Id' => '1 8',
            'Label' => 'VAX8500',
        ),
        '1 9' => array(
            'Id' => '1 9',
            'Label' => 'VAX8600',
        ),
        '1 10' => array(
            'Id' => '1 10',
            'Label' => 'VAX8650',
        ),
        '1 11' => array(
            'Id' => '1 11',
            'Label' => 'VAX8800',
        ),
        '1 12' => array(
            'Id' => '1 12',
            'Label' => 'UVAXIII',
        ),
        '2 0' => array(
            'Id' => '2 0',
            'Label' => 'RT (all)',
        ),
        '2 1' => array(
            'Id' => '2 1',
            'Label' => 'RT PC',
        ),
        '2 2' => array(
            'Id' => '2 2',
            'Label' => 'RT APC',
        ),
        '2 3' => array(
            'Id' => '2 3',
            'Label' => 'RT 135',
        ),
        '4 0' => array(
            'Id' => '4 0',
            'Label' => 'NS32032 (all)',
        ),
        '4 1' => array(
            'Id' => '4 1',
            'Label' => 'NS32032 DPC (032 CPU)',
        ),
        '4 2' => array(
            'Id' => '4 2',
            'Label' => 'NS32032 SQT',
        ),
        '4 3' => array(
            'Id' => '4 3',
            'Label' => 'NS32032 APC FPU (32081)',
        ),
        '4 4' => array(
            'Id' => '4 4',
            'Label' => 'NS32032 APC FPA (Weitek)',
        ),
        '4 5' => array(
            'Id' => '4 5',
            'Label' => 'NS32032 XPC (532)',
        ),
        '5 0' => array(
            'Id' => '5 0',
            'Label' => 'NS32332 (all)',
        ),
        '5 1' => array(
            'Id' => '5 1',
            'Label' => 'NS32332 DPC (032 CPU)',
        ),
        '5 2' => array(
            'Id' => '5 2',
            'Label' => 'NS32332 SQT',
        ),
        '5 3' => array(
            'Id' => '5 3',
            'Label' => 'NS32332 APC FPU (32081)',
        ),
        '5 4' => array(
            'Id' => '5 4',
            'Label' => 'NS32332 APC FPA (Weitek)',
        ),
        '5 5' => array(
            'Id' => '5 5',
            'Label' => 'NS32332 XPC (532)',
        ),
        '6 1' => array(
            'Id' => '6 1',
            'Label' => 'MC680x0 (all)',
        ),
        '6 2' => array(
            'Id' => '6 2',
            'Label' => 'MC68040',
        ),
        '6 3' => array(
            'Id' => '6 3',
            'Label' => 'MC68030',
        ),
        '7 3' => array(
            'Id' => '7 3',
            'Label' => 'i386 (all)',
        ),
        '7 4' => array(
            'Id' => '7 4',
            'Label' => 'i486',
        ),
        '7 5' => array(
            'Id' => '7 5',
            'Label' => 'i586',
        ),
        '7 8' => array(
            'Id' => '7 8',
            'Label' => 'Pentium III',
        ),
        '7 9' => array(
            'Id' => '7 9',
            'Label' => 'Pentium M',
        ),
        '7 10' => array(
            'Id' => '7 10',
            'Label' => 'Pentium 4',
        ),
        '7 11' => array(
            'Id' => '7 11',
            'Label' => 'Itanium',
        ),
        '7 12' => array(
            'Id' => '7 12',
            'Label' => 'Xeon',
        ),
        '7 22' => array(
            'Id' => '7 22',
            'Label' => 'Pentium Pro',
        ),
        '7 24' => array(
            'Id' => '7 24',
            'Label' => 'Pentium III M',
        ),
        '7 26' => array(
            'Id' => '7 26',
            'Label' => 'Pentium 4 M',
        ),
        '7 27' => array(
            'Id' => '7 27',
            'Label' => 'Itanium 2',
        ),
        '7 28' => array(
            'Id' => '7 28',
            'Label' => 'Xeon MP',
        ),
        '7 40' => array(
            'Id' => '7 40',
            'Label' => 'Pentium III Xeon',
        ),
        '7 54' => array(
            'Id' => '7 54',
            'Label' => 'Pentium II M3',
        ),
        '7 86' => array(
            'Id' => '7 86',
            'Label' => 'Pentium II M5',
        ),
        '7 103' => array(
            'Id' => '7 103',
            'Label' => 'Celeron',
        ),
        '7 119' => array(
            'Id' => '7 119',
            'Label' => 'Celeron Mobile',
        ),
        '7 132' => array(
            'Id' => '7 132',
            'Label' => 'i486SX',
        ),
        '8 0' => array(
            'Id' => '8 0',
            'Label' => 'MIPS (all)',
        ),
        '8 1' => array(
            'Id' => '8 1',
            'Label' => 'MIPS R2300',
        ),
        '8 2' => array(
            'Id' => '8 2',
            'Label' => 'MIPS R2600',
        ),
        '8 3' => array(
            'Id' => '8 3',
            'Label' => 'MIPS R2800',
        ),
        '8 4' => array(
            'Id' => '8 4',
            'Label' => 'MIPS R2000a',
        ),
        '8 5' => array(
            'Id' => '8 5',
            'Label' => 'MIPS R2000',
        ),
        '8 6' => array(
            'Id' => '8 6',
            'Label' => 'MIPS R3000a',
        ),
        '8 7' => array(
            'Id' => '8 7',
            'Label' => 'MIPS R3000',
        ),
        '10 0' => array(
            'Id' => '10 0',
            'Label' => 'MC98000 (all)',
        ),
        '10 1' => array(
            'Id' => '10 1',
            'Label' => 'MC98601',
        ),
        '11 0' => array(
            'Id' => '11 0',
            'Label' => 'HPPA (all)',
        ),
        '11 1' => array(
            'Id' => '11 1',
            'Label' => 'HPPA 7100LC',
        ),
        '12 0' => array(
            'Id' => '12 0',
            'Label' => 'ARM (all)',
        ),
        '12 1' => array(
            'Id' => '12 1',
            'Label' => 'ARM A500 ARCH',
        ),
        '12 2' => array(
            'Id' => '12 2',
            'Label' => 'ARM A500',
        ),
        '12 3' => array(
            'Id' => '12 3',
            'Label' => 'ARM A440',
        ),
        '12 4' => array(
            'Id' => '12 4',
            'Label' => 'ARM M4',
        ),
        '12 5' => array(
            'Id' => '12 5',
            'Label' => 'ARM A680/V4T',
        ),
        '12 6' => array(
            'Id' => '12 6',
            'Label' => 'ARM V6',
        ),
        '12 7' => array(
            'Id' => '12 7',
            'Label' => 'ARM V5TEJ',
        ),
        '12 8' => array(
            'Id' => '12 8',
            'Label' => 'ARM XSCALE',
        ),
        '12 9' => array(
            'Id' => '12 9',
            'Label' => 'ARM V7',
        ),
        '13 0' => array(
            'Id' => '13 0',
            'Label' => 'MC88000 (all)',
        ),
        '13 1' => array(
            'Id' => '13 1',
            'Label' => 'MC88100',
        ),
        '13 2' => array(
            'Id' => '13 2',
            'Label' => 'MC88110',
        ),
        '14 0' => array(
            'Id' => '14 0',
            'Label' => 'SPARC (all)',
        ),
        '14 1' => array(
            'Id' => '14 1',
            'Label' => 'SUN 4/260',
        ),
        '14 2' => array(
            'Id' => '14 2',
            'Label' => 'SUN 4/110',
        ),
        '15 0' => array(
            'Id' => '15 0',
            'Label' => 'i860 (all)',
        ),
        '15 1' => array(
            'Id' => '15 1',
            'Label' => 'i860 860',
        ),
        '16 0' => array(
            'Id' => '16 0',
            'Label' => 'i860 little (all)',
        ),
        '16 1' => array(
            'Id' => '16 1',
            'Label' => 'i860 little',
        ),
        '17 0' => array(
            'Id' => '17 0',
            'Label' => 'RS6000 (all)',
        ),
        '17 1' => array(
            'Id' => '17 1',
            'Label' => 'RS6000',
        ),
        '18 0' => array(
            'Id' => '18 0',
            'Label' => 'PowerPC (all)',
        ),
        '18 1' => array(
            'Id' => '18 1',
            'Label' => 'PowerPC 601',
        ),
        '18 2' => array(
            'Id' => '18 2',
            'Label' => 'PowerPC 602',
        ),
        '18 3' => array(
            'Id' => '18 3',
            'Label' => 'PowerPC 603',
        ),
        '18 4' => array(
            'Id' => '18 4',
            'Label' => 'PowerPC 603e',
        ),
        '18 5' => array(
            'Id' => '18 5',
            'Label' => 'PowerPC 603ev',
        ),
        '18 6' => array(
            'Id' => '18 6',
            'Label' => 'PowerPC 604',
        ),
        '18 7' => array(
            'Id' => '18 7',
            'Label' => 'PowerPC 604e',
        ),
        '18 8' => array(
            'Id' => '18 8',
            'Label' => 'PowerPC 620',
        ),
        '18 9' => array(
            'Id' => '18 9',
            'Label' => 'PowerPC 750',
        ),
        '18 10' => array(
            'Id' => '18 10',
            'Label' => 'PowerPC 7400',
        ),
        '18 11' => array(
            'Id' => '18 11',
            'Label' => 'PowerPC 7450',
        ),
        '18 100' => array(
            'Id' => '18 100',
            'Label' => 'PowerPC 970',
        ),
        '255 1' => array(
            'Id' => '255 1',
            'Label' => 'VEO 1',
        ),
        '255 2' => array(
            'Id' => '255 2',
            'Label' => 'VEO 2',
        ),
    );

}
