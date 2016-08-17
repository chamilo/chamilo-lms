<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
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
class PentaxModelID extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'PentaxModelID';

    protected $FullName = 'mixed';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'int32u';

    protected $Writable = true;

    protected $Description = 'Pentax Model ID';

    protected $flag_Permanent = true;

    protected $Values = array(
        13 => array(
            'Id' => 13,
            'Label' => 'Optio 330/430',
        ),
        76070 => array(
            'Id' => 76070,
            'Label' => 'Optio 230',
        ),
        76120 => array(
            'Id' => 76120,
            'Label' => 'Optio 330GS',
        ),
        76130 => array(
            'Id' => 76130,
            'Label' => 'Optio 450/550',
        ),
        76140 => array(
            'Id' => 76140,
            'Label' => 'Optio S',
        ),
        76145 => array(
            'Id' => 76145,
            'Label' => 'Optio S V1.01',
        ),
        76180 => array(
            'Id' => 76180,
            'Label' => '*ist D',
        ),
        76210 => array(
            'Id' => 76210,
            'Label' => 'Optio 33L',
        ),
        76220 => array(
            'Id' => 76220,
            'Label' => 'Optio 33LF',
        ),
        76230 => array(
            'Id' => 76230,
            'Label' => 'Optio 33WR/43WR/555',
        ),
        76245 => array(
            'Id' => 76245,
            'Label' => 'Optio S4',
        ),
        76290 => array(
            'Id' => 76290,
            'Label' => 'Optio MX',
        ),
        76300 => array(
            'Id' => 76300,
            'Label' => 'Optio S40',
        ),
        76310 => array(
            'Id' => 76310,
            'Label' => 'Optio S4i',
        ),
        76340 => array(
            'Id' => 76340,
            'Label' => 'Optio 30',
        ),
        76370 => array(
            'Id' => 76370,
            'Label' => 'Optio S30',
        ),
        76390 => array(
            'Id' => 76390,
            'Label' => 'Optio 750Z',
        ),
        76400 => array(
            'Id' => 76400,
            'Label' => 'Optio SV',
        ),
        76405 => array(
            'Id' => 76405,
            'Label' => 'Optio SVi',
        ),
        76410 => array(
            'Id' => 76410,
            'Label' => 'Optio X',
        ),
        76430 => array(
            'Id' => 76430,
            'Label' => 'Optio S5i',
        ),
        76440 => array(
            'Id' => 76440,
            'Label' => 'Optio S50',
        ),
        76450 => array(
            'Id' => 76450,
            'Label' => '*ist DS',
        ),
        76470 => array(
            'Id' => 76470,
            'Label' => 'Optio MX4',
        ),
        76480 => array(
            'Id' => 76480,
            'Label' => 'Optio S5n',
        ),
        76490 => array(
            'Id' => 76490,
            'Label' => 'Optio WP',
        ),
        76540 => array(
            'Id' => 76540,
            'Label' => 'Optio S55',
        ),
        76560 => array(
            'Id' => 76560,
            'Label' => 'Optio S5z',
        ),
        76570 => array(
            'Id' => 76570,
            'Label' => '*ist DL',
        ),
        76580 => array(
            'Id' => 76580,
            'Label' => 'Optio S60',
        ),
        76590 => array(
            'Id' => 76590,
            'Label' => 'Optio S45',
        ),
        76600 => array(
            'Id' => 76600,
            'Label' => 'Optio S6',
        ),
        76620 => array(
            'Id' => 76620,
            'Label' => 'Optio WPi',
        ),
        76630 => array(
            'Id' => 76630,
            'Label' => 'BenQ DC X600',
        ),
        76640 => array(
            'Id' => 76640,
            'Label' => '*ist DS2',
        ),
        76642 => array(
            'Id' => 76642,
            'Label' => 'Samsung GX-1S',
        ),
        76650 => array(
            'Id' => 76650,
            'Label' => 'Optio A10',
        ),
        76670 => array(
            'Id' => 76670,
            'Label' => '*ist DL2',
        ),
        76672 => array(
            'Id' => 76672,
            'Label' => 'Samsung GX-1L',
        ),
        76700 => array(
            'Id' => 76700,
            'Label' => 'K100D',
        ),
        76701 => array(
            'Id' => 76701,
            'Label' => 'K110D',
        ),
        76706 => array(
            'Id' => 76706,
            'Label' => 'K100D Super',
        ),
        76720 => array(
            'Id' => 76720,
            'Label' => 'Optio T10/T20',
        ),
        76770 => array(
            'Id' => 76770,
            'Label' => 'Optio W10',
        ),
        76790 => array(
            'Id' => 76790,
            'Label' => 'Optio M10',
        ),
        76830 => array(
            'Id' => 76830,
            'Label' => 'K10D',
        ),
        76832 => array(
            'Id' => 76832,
            'Label' => 'Samsung GX10',
        ),
        76840 => array(
            'Id' => 76840,
            'Label' => 'Optio S7',
        ),
        76845 => array(
            'Id' => 76845,
            'Label' => 'Optio L20',
        ),
        76850 => array(
            'Id' => 76850,
            'Label' => 'Optio M20',
        ),
        76860 => array(
            'Id' => 76860,
            'Label' => 'Optio W20',
        ),
        76870 => array(
            'Id' => 76870,
            'Label' => 'Optio A20',
        ),
        76920 => array(
            'Id' => 76920,
            'Label' => 'Optio E30',
        ),
        76925 => array(
            'Id' => 76925,
            'Label' => 'Optio E35',
        ),
        76930 => array(
            'Id' => 76930,
            'Label' => 'Optio T30',
        ),
        76940 => array(
            'Id' => 76940,
            'Label' => 'Optio M30',
        ),
        76945 => array(
            'Id' => 76945,
            'Label' => 'Optio L30',
        ),
        76950 => array(
            'Id' => 76950,
            'Label' => 'Optio W30',
        ),
        76960 => array(
            'Id' => 76960,
            'Label' => 'Optio A30',
        ),
        76980 => array(
            'Id' => 76980,
            'Label' => 'Optio E40',
        ),
        76990 => array(
            'Id' => 76990,
            'Label' => 'Optio M40',
        ),
        76995 => array(
            'Id' => 76995,
            'Label' => 'Optio L40',
        ),
        76997 => array(
            'Id' => 76997,
            'Label' => 'Optio L36',
        ),
        77000 => array(
            'Id' => 77000,
            'Label' => 'Optio Z10',
        ),
        77010 => array(
            'Id' => 77010,
            'Label' => 'K20D',
        ),
        77012 => array(
            'Id' => 77012,
            'Label' => 'Samsung GX20',
        ),
        77020 => array(
            'Id' => 77020,
            'Label' => 'Optio S10',
        ),
        77030 => array(
            'Id' => 77030,
            'Label' => 'Optio A40',
        ),
        77040 => array(
            'Id' => 77040,
            'Label' => 'Optio V10',
        ),
        77050 => array(
            'Id' => 77050,
            'Label' => 'K200D',
        ),
        77060 => array(
            'Id' => 77060,
            'Label' => 'Optio S12',
        ),
        77070 => array(
            'Id' => 77070,
            'Label' => 'Optio E50',
        ),
        77080 => array(
            'Id' => 77080,
            'Label' => 'Optio M50',
        ),
        77090 => array(
            'Id' => 77090,
            'Label' => 'Optio L50',
        ),
        77100 => array(
            'Id' => 77100,
            'Label' => 'Optio V20',
        ),
        77120 => array(
            'Id' => 77120,
            'Label' => 'Optio W60',
        ),
        77130 => array(
            'Id' => 77130,
            'Label' => 'Optio M60',
        ),
        77160 => array(
            'Id' => 77160,
            'Label' => 'Optio E60/M90',
        ),
        77170 => array(
            'Id' => 77170,
            'Label' => 'K2000',
        ),
        77171 => array(
            'Id' => 77171,
            'Label' => 'K-m',
        ),
        77190 => array(
            'Id' => 77190,
            'Label' => 'Optio P70',
        ),
        77200 => array(
            'Id' => 77200,
            'Label' => 'Optio L70',
        ),
        77210 => array(
            'Id' => 77210,
            'Label' => 'Optio E70',
        ),
        77230 => array(
            'Id' => 77230,
            'Label' => 'X70',
        ),
        77240 => array(
            'Id' => 77240,
            'Label' => 'K-7',
        ),
        77260 => array(
            'Id' => 77260,
            'Label' => 'Optio W80',
        ),
        77290 => array(
            'Id' => 77290,
            'Label' => 'Optio P80',
        ),
        77300 => array(
            'Id' => 77300,
            'Label' => 'Optio WS80',
        ),
        77310 => array(
            'Id' => 77310,
            'Label' => 'K-x',
        ),
        77320 => array(
            'Id' => 77320,
            'Label' => '645D',
        ),
        77330 => array(
            'Id' => 77330,
            'Label' => 'Optio E80',
        ),
        77360 => array(
            'Id' => 77360,
            'Label' => 'Optio W90',
        ),
        77370 => array(
            'Id' => 77370,
            'Label' => 'Optio I-10',
        ),
        77380 => array(
            'Id' => 77380,
            'Label' => 'Optio H90',
        ),
        77390 => array(
            'Id' => 77390,
            'Label' => 'Optio E90',
        ),
        77400 => array(
            'Id' => 77400,
            'Label' => 'X90',
        ),
        77420 => array(
            'Id' => 77420,
            'Label' => 'K-r',
        ),
        77430 => array(
            'Id' => 77430,
            'Label' => 'K-5',
        ),
        77450 => array(
            'Id' => 77450,
            'Label' => 'Optio RS1000/RS1500',
        ),
        77460 => array(
            'Id' => 77460,
            'Label' => 'Optio RZ10',
        ),
        77470 => array(
            'Id' => 77470,
            'Label' => 'Optio LS1000',
        ),
        77500 => array(
            'Id' => 77500,
            'Label' => 'Optio WG-1 GPS',
        ),
        77520 => array(
            'Id' => 77520,
            'Label' => 'Optio S1',
        ),
        77540 => array(
            'Id' => 77540,
            'Label' => 'Q',
        ),
        77560 => array(
            'Id' => 77560,
            'Label' => 'K-01',
        ),
        77580 => array(
            'Id' => 77580,
            'Label' => 'Optio RZ18',
        ),
        77590 => array(
            'Id' => 77590,
            'Label' => 'Optio VS20',
        ),
        77610 => array(
            'Id' => 77610,
            'Label' => 'Optio WG-2 GPS',
        ),
        77640 => array(
            'Id' => 77640,
            'Label' => 'Optio LS465',
        ),
        77650 => array(
            'Id' => 77650,
            'Label' => 'K-30',
        ),
        77660 => array(
            'Id' => 77660,
            'Label' => 'X-5',
        ),
        77670 => array(
            'Id' => 77670,
            'Label' => 'Q10',
        ),
        77680 => array(
            'Id' => 77680,
            'Label' => 'K-5 II',
        ),
        77681 => array(
            'Id' => 77681,
            'Label' => 'K-5 II s',
        ),
        77690 => array(
            'Id' => 77690,
            'Label' => 'Q7',
        ),
        77700 => array(
            'Id' => 77700,
            'Label' => 'MX-1',
        ),
        77710 => array(
            'Id' => 77710,
            'Label' => 'WG-3 GPS',
        ),
        77720 => array(
            'Id' => 77720,
            'Label' => 'WG-3',
        ),
        77730 => array(
            'Id' => 77730,
            'Label' => 'WG-10',
        ),
        77750 => array(
            'Id' => 77750,
            'Label' => 'K-50',
        ),
        77760 => array(
            'Id' => 77760,
            'Label' => 'K-3',
        ),
        77770 => array(
            'Id' => 77770,
            'Label' => 'K-500',
        ),
        77790 => array(
            'Id' => 77790,
            'Label' => 'WG-4 GPS',
        ),
        77800 => array(
            'Id' => 77800,
            'Label' => 'WG-4',
        ),
        77830 => array(
            'Id' => 77830,
            'Label' => 'WG-20',
        ),
        77840 => array(
            'Id' => 77840,
            'Label' => '645Z',
        ),
        77850 => array(
            'Id' => 77850,
            'Label' => 'K-S1',
        ),
        77860 => array(
            'Id' => 77860,
            'Label' => 'K-S2',
        ),
        77870 => array(
            'Id' => 77870,
            'Label' => 'Q-S1',
        ),
        77910 => array(
            'Id' => 77910,
            'Label' => 'WG-30',
        ),
        77950 => array(
            'Id' => 77950,
            'Label' => 'WG-30W',
        ),
        77960 => array(
            'Id' => 77960,
            'Label' => 'WG-5 GPS',
        ),
        77980 => array(
            'Id' => 77980,
            'Label' => 'K-3 II',
        ),
    );

}
