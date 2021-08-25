<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Olympus;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class LensType extends AbstractTag
{

    protected $Id = 513;

    protected $Name = 'LensType';

    protected $FullName = 'Olympus::Equipment';

    protected $GroupName = 'Olympus';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Olympus';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Lens Type';

    protected $flag_Permanent = true;

    protected $MaxLength = 6;

    protected $Values = array(
        '0 00 00' => array(
            'Id' => '0 00 00',
            'Label' => 'None',
        ),
        '0 01 00' => array(
            'Id' => '0 01 00',
            'Label' => 'Olympus Zuiko Digital ED 50mm F2.0 Macro',
        ),
        '0 01 01' => array(
            'Id' => '0 01 01',
            'Label' => 'Olympus Zuiko Digital 40-150mm F3.5-4.5',
        ),
        '0 01 10' => array(
            'Id' => '0 01 10',
            'Label' => 'Olympus M.Zuiko Digital ED 14-42mm F3.5-5.6',
        ),
        '0 02 00' => array(
            'Id' => '0 02 00',
            'Label' => 'Olympus Zuiko Digital ED 150mm F2.0',
        ),
        '0 02 10' => array(
            'Id' => '0 02 10',
            'Label' => 'Olympus M.Zuiko Digital 17mm F2.8 Pancake',
        ),
        '0 03 00' => array(
            'Id' => '0 03 00',
            'Label' => 'Olympus Zuiko Digital ED 300mm F2.8',
        ),
        '0 03 10' => array(
            'Id' => '0 03 10',
            'Label' => 'Olympus M.Zuiko Digital ED 14-150mm F4.0-5.6 [II]',
        ),
        '0 04 10' => array(
            'Id' => '0 04 10',
            'Label' => 'Olympus M.Zuiko Digital ED 9-18mm F4.0-5.6',
        ),
        '0 05 00' => array(
            'Id' => '0 05 00',
            'Label' => 'Olympus Zuiko Digital 14-54mm F2.8-3.5',
        ),
        '0 05 01' => array(
            'Id' => '0 05 01',
            'Label' => 'Olympus Zuiko Digital Pro ED 90-250mm F2.8',
        ),
        '0 05 10' => array(
            'Id' => '0 05 10',
            'Label' => 'Olympus M.Zuiko Digital ED 14-42mm F3.5-5.6 L',
        ),
        '0 06 00' => array(
            'Id' => '0 06 00',
            'Label' => 'Olympus Zuiko Digital ED 50-200mm F2.8-3.5',
        ),
        '0 06 01' => array(
            'Id' => '0 06 01',
            'Label' => 'Olympus Zuiko Digital ED 8mm F3.5 Fisheye',
        ),
        '0 06 10' => array(
            'Id' => '0 06 10',
            'Label' => 'Olympus M.Zuiko Digital ED 40-150mm F4.0-5.6',
        ),
        '0 07 00' => array(
            'Id' => '0 07 00',
            'Label' => 'Olympus Zuiko Digital 11-22mm F2.8-3.5',
        ),
        '0 07 01' => array(
            'Id' => '0 07 01',
            'Label' => 'Olympus Zuiko Digital 18-180mm F3.5-6.3',
        ),
        '0 07 10' => array(
            'Id' => '0 07 10',
            'Label' => 'Olympus M.Zuiko Digital ED 12mm F2.0',
        ),
        '0 08 01' => array(
            'Id' => '0 08 01',
            'Label' => 'Olympus Zuiko Digital 70-300mm F4.0-5.6',
        ),
        '0 08 10' => array(
            'Id' => '0 08 10',
            'Label' => 'Olympus M.Zuiko Digital ED 75-300mm F4.8-6.7',
        ),
        '0 09 10' => array(
            'Id' => '0 09 10',
            'Label' => 'Olympus M.Zuiko Digital 14-42mm F3.5-5.6 II',
        ),
        '0 10 01' => array(
            'Id' => '0 10 01',
            'Label' => 'Kenko Tokina Reflex 300mm F6.3 MF Macro',
        ),
        '0 10 10' => array(
            'Id' => '0 10 10',
            'Label' => 'Olympus M.Zuiko Digital ED 12-50mm F3.5-6.3 EZ',
        ),
        '0 11 10' => array(
            'Id' => '0 11 10',
            'Label' => 'Olympus M.Zuiko Digital 45mm F1.8',
        ),
        '0 12 10' => array(
            'Id' => '0 12 10',
            'Label' => 'Olympus M.Zuiko Digital ED 60mm F2.8 Macro',
        ),
        '0 13 10' => array(
            'Id' => '0 13 10',
            'Label' => 'Olympus M.Zuiko Digital 14-42mm F3.5-5.6 II R',
        ),
        '0 14 10' => array(
            'Id' => '0 14 10',
            'Label' => 'Olympus M.Zuiko Digital ED 40-150mm F4.0-5.6 R',
        ),
        '0 15 00' => array(
            'Id' => '0 15 00',
            'Label' => 'Olympus Zuiko Digital ED 7-14mm F4.0',
        ),
        '0 15 10' => array(
            'Id' => '0 15 10',
            'Label' => 'Olympus M.Zuiko Digital ED 75mm F1.8',
        ),
        '0 16 10' => array(
            'Id' => '0 16 10',
            'Label' => 'Olympus M.Zuiko Digital 17mm F1.8',
        ),
        '0 17 00' => array(
            'Id' => '0 17 00',
            'Label' => 'Olympus Zuiko Digital Pro ED 35-100mm F2.0',
        ),
        '0 18 00' => array(
            'Id' => '0 18 00',
            'Label' => 'Olympus Zuiko Digital 14-45mm F3.5-5.6',
        ),
        '0 18 10' => array(
            'Id' => '0 18 10',
            'Label' => 'Olympus M.Zuiko Digital ED 75-300mm F4.8-6.7 II',
        ),
        '0 19 10' => array(
            'Id' => '0 19 10',
            'Label' => 'Olympus M.Zuiko Digital ED 12-40mm F2.8 Pro',
        ),
        '0 20 00' => array(
            'Id' => '0 20 00',
            'Label' => 'Olympus Zuiko Digital 35mm F3.5 Macro',
        ),
        '0 20 10' => array(
            'Id' => '0 20 10',
            'Label' => 'Olympus M.Zuiko Digital ED 40-150mm F2.8 Pro',
        ),
        '0 21 10' => array(
            'Id' => '0 21 10',
            'Label' => 'Olympus M.Zuiko Digital ED 14-42mm F3.5-5.6 EZ',
        ),
        '0 22 00' => array(
            'Id' => '0 22 00',
            'Label' => 'Olympus Zuiko Digital 17.5-45mm F3.5-5.6',
        ),
        '0 22 10' => array(
            'Id' => '0 22 10',
            'Label' => 'Olympus M.Zuiko Digital 25mm F1.8',
        ),
        '0 23 00' => array(
            'Id' => '0 23 00',
            'Label' => 'Olympus Zuiko Digital ED 14-42mm F3.5-5.6',
        ),
        '0 23 10' => array(
            'Id' => '0 23 10',
            'Label' => 'Olympus M.Zuiko Digital ED 7-14mm F2.8 Pro',
        ),
        '0 24 00' => array(
            'Id' => '0 24 00',
            'Label' => 'Olympus Zuiko Digital ED 40-150mm F4.0-5.6',
        ),
        '0 24 10' => array(
            'Id' => '0 24 10',
            'Label' => 'Olympus M.Zuiko Digital ED 300mm F4.0 IS Pro',
        ),
        '0 25 10' => array(
            'Id' => '0 25 10',
            'Label' => 'Olympus M.Zuiko Digital ED 8mm F1.8 Fisheye Pro',
        ),
        '0 30 00' => array(
            'Id' => '0 30 00',
            'Label' => 'Olympus Zuiko Digital ED 50-200mm F2.8-3.5 SWD',
        ),
        '0 31 00' => array(
            'Id' => '0 31 00',
            'Label' => 'Olympus Zuiko Digital ED 12-60mm F2.8-4.0 SWD',
        ),
        '0 32 00' => array(
            'Id' => '0 32 00',
            'Label' => 'Olympus Zuiko Digital ED 14-35mm F2.0 SWD',
        ),
        '0 33 00' => array(
            'Id' => '0 33 00',
            'Label' => 'Olympus Zuiko Digital 25mm F2.8',
        ),
        '0 34 00' => array(
            'Id' => '0 34 00',
            'Label' => 'Olympus Zuiko Digital ED 9-18mm F4.0-5.6',
        ),
        '0 35 00' => array(
            'Id' => '0 35 00',
            'Label' => 'Olympus Zuiko Digital 14-54mm F2.8-3.5 II',
        ),
        '1 01 00' => array(
            'Id' => '1 01 00',
            'Label' => 'Sigma 18-50mm F3.5-5.6 DC',
        ),
        '1 01 10' => array(
            'Id' => '1 01 10',
            'Label' => 'Sigma 30mm F2.8 EX DN',
        ),
        '1 02 00' => array(
            'Id' => '1 02 00',
            'Label' => 'Sigma 55-200mm F4.0-5.6 DC',
        ),
        '1 02 10' => array(
            'Id' => '1 02 10',
            'Label' => 'Sigma 19mm F2.8 EX DN',
        ),
        '1 03 00' => array(
            'Id' => '1 03 00',
            'Label' => 'Sigma 18-125mm F3.5-5.6 DC',
        ),
        '1 03 10' => array(
            'Id' => '1 03 10',
            'Label' => 'Sigma 30mm F2.8 DN | A',
        ),
        '1 04 00' => array(
            'Id' => '1 04 00',
            'Label' => 'Sigma 18-125mm F3.5-5.6 DC',
        ),
        '1 04 10' => array(
            'Id' => '1 04 10',
            'Label' => 'Sigma 19mm F2.8 DN | A',
        ),
        '1 05 00' => array(
            'Id' => '1 05 00',
            'Label' => 'Sigma 30mm F1.4 EX DC HSM',
        ),
        '1 05 10' => array(
            'Id' => '1 05 10',
            'Label' => 'Sigma 60mm F2.8 DN | A',
        ),
        '1 06 00' => array(
            'Id' => '1 06 00',
            'Label' => 'Sigma APO 50-500mm F4.0-6.3 EX DG HSM',
        ),
        '1 07 00' => array(
            'Id' => '1 07 00',
            'Label' => 'Sigma Macro 105mm F2.8 EX DG',
        ),
        '1 08 00' => array(
            'Id' => '1 08 00',
            'Label' => 'Sigma APO Macro 150mm F2.8 EX DG HSM',
        ),
        '1 09 00' => array(
            'Id' => '1 09 00',
            'Label' => 'Sigma 18-50mm F2.8 EX DC Macro',
        ),
        '1 10 00' => array(
            'Id' => '1 10 00',
            'Label' => 'Sigma 24mm F1.8 EX DG Aspherical Macro',
        ),
        '1 11 00' => array(
            'Id' => '1 11 00',
            'Label' => 'Sigma APO 135-400mm F4.5-5.6 DG',
        ),
        '1 12 00' => array(
            'Id' => '1 12 00',
            'Label' => 'Sigma APO 300-800mm F5.6 EX DG HSM',
        ),
        '1 13 00' => array(
            'Id' => '1 13 00',
            'Label' => 'Sigma 30mm F1.4 EX DC HSM',
        ),
        '1 14 00' => array(
            'Id' => '1 14 00',
            'Label' => 'Sigma APO 50-500mm F4.0-6.3 EX DG HSM',
        ),
        '1 15 00' => array(
            'Id' => '1 15 00',
            'Label' => 'Sigma 10-20mm F4.0-5.6 EX DC HSM',
        ),
        '1 16 00' => array(
            'Id' => '1 16 00',
            'Label' => 'Sigma APO 70-200mm F2.8 II EX DG Macro HSM',
        ),
        '1 17 00' => array(
            'Id' => '1 17 00',
            'Label' => 'Sigma 50mm F1.4 EX DG HSM',
        ),
        '2 01 00' => array(
            'Id' => '2 01 00',
            'Label' => 'Leica D Vario Elmarit 14-50mm F2.8-3.5 Asph.',
        ),
        '2 01 10' => array(
            'Id' => '2 01 10',
            'Label' => 'Lumix G Vario 14-45mm F3.5-5.6 Asph. Mega OIS',
        ),
        '2 02 00' => array(
            'Id' => '2 02 00',
            'Label' => 'Leica D Summilux 25mm F1.4 Asph.',
        ),
        '2 02 10' => array(
            'Id' => '2 02 10',
            'Label' => 'Lumix G Vario 45-200mm F4.0-5.6 Mega OIS',
        ),
        '2 03 00' => array(
            'Id' => '2 03 00',
            'Label' => 'Leica D Vario Elmar 14-50mm F3.8-5.6 Asph. Mega OIS',
        ),
        '2 03 01' => array(
            'Id' => '2 03 01',
            'Label' => 'Leica D Vario Elmar 14-50mm F3.8-5.6 Asph.',
        ),
        '2 03 10' => array(
            'Id' => '2 03 10',
            'Label' => 'Lumix G Vario HD 14-140mm F4.0-5.8 Asph. Mega OIS',
        ),
        '2 04 00' => array(
            'Id' => '2 04 00',
            'Label' => 'Leica D Vario Elmar 14-150mm F3.5-5.6',
        ),
        '2 04 10' => array(
            'Id' => '2 04 10',
            'Label' => 'Lumix G Vario 7-14mm F4.0 Asph.',
        ),
        '2 05 10' => array(
            'Id' => '2 05 10',
            'Label' => 'Lumix G 20mm F1.7 Asph.',
        ),
        '2 06 10' => array(
            'Id' => '2 06 10',
            'Label' => 'Leica DG Macro-Elmarit 45mm F2.8 Asph. Mega OIS',
        ),
        '2 07 10' => array(
            'Id' => '2 07 10',
            'Label' => 'Lumix G Vario 14-42mm F3.5-5.6 Asph. Mega OIS',
        ),
        '2 08 10' => array(
            'Id' => '2 08 10',
            'Label' => 'Lumix G Fisheye 8mm F3.5',
        ),
        '2 09 10' => array(
            'Id' => '2 09 10',
            'Label' => 'Lumix G Vario 100-300mm F4.0-5.6 Mega OIS',
        ),
        '2 10 10' => array(
            'Id' => '2 10 10',
            'Label' => 'Lumix G 14mm F2.5 Asph.',
        ),
        '2 11 10' => array(
            'Id' => '2 11 10',
            'Label' => 'Lumix G 12.5mm F12 3D',
        ),
        '2 12 10' => array(
            'Id' => '2 12 10',
            'Label' => 'Leica DG Summilux 25mm F1.4 Asph.',
        ),
        '2 13 10' => array(
            'Id' => '2 13 10',
            'Label' => 'Lumix G X Vario PZ 45-175mm F4.0-5.6 Asph. Power OIS',
        ),
        '2 14 10' => array(
            'Id' => '2 14 10',
            'Label' => 'Lumix G X Vario PZ 14-42mm F3.5-5.6 Asph. Power OIS',
        ),
        '2 15 10' => array(
            'Id' => '2 15 10',
            'Label' => 'Lumix G X Vario 12-35mm F2.8 Asph. Power OIS',
        ),
        '2 16 10' => array(
            'Id' => '2 16 10',
            'Label' => 'Lumix G Vario 45-150mm F4.0-5.6 Asph. Mega OIS',
        ),
        '2 17 10' => array(
            'Id' => '2 17 10',
            'Label' => 'Lumix G X Vario 35-100mm F2.8 Power OIS',
        ),
        '2 18 10' => array(
            'Id' => '2 18 10',
            'Label' => 'Lumix G Vario 14-42mm F3.5-5.6 II Asph. Mega OIS',
        ),
        '2 19 10' => array(
            'Id' => '2 19 10',
            'Label' => 'Lumix G Vario 14-140mm F3.5-5.6 Asph. Power OIS',
        ),
        '2 20 10' => array(
            'Id' => '2 20 10',
            'Label' => 'Lumix G Vario 12-32mm F3.5-5.6 Asph. Mega OIS',
        ),
        '2 21 10' => array(
            'Id' => '2 21 10',
            'Label' => 'Leica DG Nocticron 42.5mm F1.2 Asph. Power OIS',
        ),
        '2 22 10' => array(
            'Id' => '2 22 10',
            'Label' => 'Leica DG Summilux 15mm F1.7 Asph.',
        ),
        '2 24 10' => array(
            'Id' => '2 24 10',
            'Label' => 'Lumix G Macro 30mm F2.8 Asph. Mega OIS',
        ),
        '2 25 10' => array(
            'Id' => '2 25 10',
            'Label' => 'Lumix G 42.5mm F1.7 Asph. Power OIS',
        ),
        '3 01 00' => array(
            'Id' => '3 01 00',
            'Label' => 'Leica D Vario Elmarit 14-50mm F2.8-3.5 Asph.',
        ),
        '3 02 00' => array(
            'Id' => '3 02 00',
            'Label' => 'Leica D Summilux 25mm F1.4 Asph.',
        ),
        '5 01 10' => array(
            'Id' => '5 01 10',
            'Label' => 'Tamron 14-150mm F3.5-5.8 Di III',
        ),
    );

}
