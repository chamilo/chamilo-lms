<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
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
class LensType extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'LensType';

    protected $FullName = 'mixed';

    protected $GroupName = 'Canon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Canon';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Lens Type';

    protected $flag_Permanent = true;

    protected $Values = array(
        '-1' => array(
            'Id' => '-1',
            'Label' => 'n/a',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Canon EF 50mm f/1.8',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Canon EF 28mm f/2.8',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Canon EF 135mm f/2.8 Soft',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Canon EF 35-105mm f/3.5-4.5 or Sigma Lens',
        ),
        '4.1' => array(
            'Id' => '4.1',
            'Label' => 'Sigma UC Zoom 35-135mm f/4-5.6',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Canon EF 35-70mm f/3.5-4.5',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Canon EF 28-70mm f/3.5-4.5 or Sigma or Tokina Lens',
        ),
        '6.1' => array(
            'Id' => '6.1',
            'Label' => 'Sigma 18-50mm f/3.5-5.6 DC',
        ),
        '6.2' => array(
            'Id' => '6.2',
            'Label' => 'Sigma 18-125mm f/3.5-5.6 DC IF ASP',
        ),
        '6.3' => array(
            'Id' => '6.3',
            'Label' => 'Tokina AF 193-2 19-35mm f/3.5-4.5',
        ),
        '6.4' => array(
            'Id' => '6.4',
            'Label' => 'Sigma 28-80mm f/3.5-5.6 II Macro',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Canon EF 100-300mm f/5.6L',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Canon EF 100-300mm f/5.6 or Sigma or Tokina Lens',
        ),
        '8.1' => array(
            'Id' => '8.1',
            'Label' => 'Sigma 70-300mm f/4-5.6 [APO] DG Macro',
        ),
        '8.2' => array(
            'Id' => '8.2',
            'Label' => 'Tokina AT-X 242 AF 24-200mm f/3.5-5.6',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Canon EF 70-210mm f/4',
        ),
        '9.1' => array(
            'Id' => '9.1',
            'Label' => 'Sigma 55-200mm f/4-5.6 DC',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Canon EF 50mm f/2.5 Macro or Sigma Lens',
        ),
        '10.1' => array(
            'Id' => '10.1',
            'Label' => 'Sigma 50mm f/2.8 EX',
        ),
        '10.2' => array(
            'Id' => '10.2',
            'Label' => 'Sigma 28mm f/1.8',
        ),
        '10.3' => array(
            'Id' => '10.3',
            'Label' => 'Sigma 105mm f/2.8 Macro EX',
        ),
        '10.4' => array(
            'Id' => '10.4',
            'Label' => 'Sigma 70mm f/2.8 EX DG Macro EF',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Canon EF 35mm f/2',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'Canon EF 15mm f/2.8 Fisheye',
        ),
        14 => array(
            'Id' => 14,
            'Label' => 'Canon EF 50-200mm f/3.5-4.5L',
        ),
        15 => array(
            'Id' => 15,
            'Label' => 'Canon EF 50-200mm f/3.5-4.5',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Canon EF 35-135mm f/3.5-4.5',
        ),
        17 => array(
            'Id' => 17,
            'Label' => 'Canon EF 35-70mm f/3.5-4.5A',
        ),
        18 => array(
            'Id' => 18,
            'Label' => 'Canon EF 28-70mm f/3.5-4.5',
        ),
        20 => array(
            'Id' => 20,
            'Label' => 'Canon EF 100-200mm f/4.5A',
        ),
        21 => array(
            'Id' => 21,
            'Label' => 'Canon EF 80-200mm f/2.8L',
        ),
        22 => array(
            'Id' => 22,
            'Label' => 'Canon EF 20-35mm f/2.8L or Tokina Lens',
        ),
        '22.1' => array(
            'Id' => '22.1',
            'Label' => 'Tokina AT-X 280 AF Pro 28-80mm f/2.8 Aspherical',
        ),
        23 => array(
            'Id' => 23,
            'Label' => 'Canon EF 35-105mm f/3.5-4.5',
        ),
        24 => array(
            'Id' => 24,
            'Label' => 'Canon EF 35-80mm f/4-5.6 Power Zoom',
        ),
        25 => array(
            'Id' => 25,
            'Label' => 'Canon EF 35-80mm f/4-5.6 Power Zoom',
        ),
        26 => array(
            'Id' => 26,
            'Label' => 'Canon EF 100mm f/2.8 Macro or Other Lens',
        ),
        '26.1' => array(
            'Id' => '26.1',
            'Label' => 'Cosina 100mm f/3.5 Macro AF',
        ),
        '26.2' => array(
            'Id' => '26.2',
            'Label' => 'Tamron SP AF 90mm f/2.8 Di Macro',
        ),
        '26.3' => array(
            'Id' => '26.3',
            'Label' => 'Tamron SP AF 180mm f/3.5 Di Macro',
        ),
        '26.4' => array(
            'Id' => '26.4',
            'Label' => 'Carl Zeiss Planar T* 50mm f/1.4',
        ),
        27 => array(
            'Id' => 27,
            'Label' => 'Canon EF 35-80mm f/4-5.6',
        ),
        28 => array(
            'Id' => 28,
            'Label' => 'Canon EF 80-200mm f/4.5-5.6 or Tamron Lens',
        ),
        '28.1' => array(
            'Id' => '28.1',
            'Label' => 'Tamron SP AF 28-105mm f/2.8 LD Aspherical IF',
        ),
        '28.2' => array(
            'Id' => '28.2',
            'Label' => 'Tamron SP AF 28-75mm f/2.8 XR Di LD Aspherical [IF] Macro',
        ),
        '28.3' => array(
            'Id' => '28.3',
            'Label' => 'Tamron AF 70-300mm f/4-5.6 Di LD 1:2 Macro',
        ),
        '28.4' => array(
            'Id' => '28.4',
            'Label' => 'Tamron AF Aspherical 28-200mm f/3.8-5.6',
        ),
        29 => array(
            'Id' => 29,
            'Label' => 'Canon EF 50mm f/1.8 II',
        ),
        30 => array(
            'Id' => 30,
            'Label' => 'Canon EF 35-105mm f/4.5-5.6',
        ),
        31 => array(
            'Id' => 31,
            'Label' => 'Canon EF 75-300mm f/4-5.6 or Tamron Lens',
        ),
        '31.1' => array(
            'Id' => '31.1',
            'Label' => 'Tamron SP AF 300mm f/2.8 LD IF',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'Canon EF 24mm f/2.8 or Sigma Lens',
        ),
        '32.1' => array(
            'Id' => '32.1',
            'Label' => 'Sigma 15mm f/2.8 EX Fisheye',
        ),
        33 => array(
            'Id' => 33,
            'Label' => 'Voigtlander or Carl Zeiss Lens',
        ),
        '33.1' => array(
            'Id' => '33.1',
            'Label' => 'Voigtlander Ultron 40mm f/2 SLII Aspherical',
        ),
        '33.2' => array(
            'Id' => '33.2',
            'Label' => 'Voigtlander Color Skopar 20mm f/3.5 SLII Aspherical',
        ),
        '33.3' => array(
            'Id' => '33.3',
            'Label' => 'Voigtlander APO-Lanthar 90mm f/3.5 SLII Close Focus',
        ),
        '33.4' => array(
            'Id' => '33.4',
            'Label' => 'Carl Zeiss Distagon T* 15mm f/2.8 ZE',
        ),
        '33.5' => array(
            'Id' => '33.5',
            'Label' => 'Carl Zeiss Distagon T* 18mm f/3.5 ZE',
        ),
        '33.6' => array(
            'Id' => '33.6',
            'Label' => 'Carl Zeiss Distagon T* 21mm f/2.8 ZE',
        ),
        '33.7' => array(
            'Id' => '33.7',
            'Label' => 'Carl Zeiss Distagon T* 25mm f/2 ZE',
        ),
        '33.8' => array(
            'Id' => '33.8',
            'Label' => 'Carl Zeiss Distagon T* 28mm f/2 ZE',
        ),
        '33.9' => array(
            'Id' => '33.9',
            'Label' => 'Carl Zeiss Distagon T* 35mm f/2 ZE',
        ),
        '33.10' => array(
            'Id' => '33.10',
            'Label' => 'Carl Zeiss Distagon T* 35mm f/1.4 ZE',
        ),
        '33.11' => array(
            'Id' => '33.11',
            'Label' => 'Carl Zeiss Planar T* 50mm f/1.4 ZE',
        ),
        '33.12' => array(
            'Id' => '33.12',
            'Label' => 'Carl Zeiss Makro-Planar T* 50mm f/2 ZE',
        ),
        '33.13' => array(
            'Id' => '33.13',
            'Label' => 'Carl Zeiss Makro-Planar T* 100mm f/2 ZE',
        ),
        '33.14' => array(
            'Id' => '33.14',
            'Label' => 'Carl Zeiss Apo-Sonnar T* 135mm f/2 ZE',
        ),
        35 => array(
            'Id' => 35,
            'Label' => 'Canon EF 35-80mm f/4-5.6',
        ),
        36 => array(
            'Id' => 36,
            'Label' => 'Canon EF 38-76mm f/4.5-5.6',
        ),
        37 => array(
            'Id' => 37,
            'Label' => 'Canon EF 35-80mm f/4-5.6 or Tamron Lens',
        ),
        '37.1' => array(
            'Id' => '37.1',
            'Label' => 'Tamron 70-200mm f/2.8 Di LD IF Macro',
        ),
        '37.2' => array(
            'Id' => '37.2',
            'Label' => 'Tamron AF 28-300mm f/3.5-6.3 XR Di VC LD Aspherical [IF] Macro Model A20',
        ),
        '37.3' => array(
            'Id' => '37.3',
            'Label' => 'Tamron SP AF 17-50mm f/2.8 XR Di II VC LD Aspherical [IF]',
        ),
        '37.4' => array(
            'Id' => '37.4',
            'Label' => 'Tamron AF 18-270mm f/3.5-6.3 Di II VC LD Aspherical [IF] Macro',
        ),
        38 => array(
            'Id' => 38,
            'Label' => 'Canon EF 80-200mm f/4.5-5.6',
        ),
        39 => array(
            'Id' => 39,
            'Label' => 'Canon EF 75-300mm f/4-5.6',
        ),
        40 => array(
            'Id' => 40,
            'Label' => 'Canon EF 28-80mm f/3.5-5.6',
        ),
        41 => array(
            'Id' => 41,
            'Label' => 'Canon EF 28-90mm f/4-5.6',
        ),
        42 => array(
            'Id' => 42,
            'Label' => 'Canon EF 28-200mm f/3.5-5.6 or Tamron Lens',
        ),
        '42.1' => array(
            'Id' => '42.1',
            'Label' => 'Tamron AF 28-300mm f/3.5-6.3 XR Di VC LD Aspherical [IF] Macro Model A20',
        ),
        43 => array(
            'Id' => 43,
            'Label' => 'Canon EF 28-105mm f/4-5.6',
        ),
        44 => array(
            'Id' => 44,
            'Label' => 'Canon EF 90-300mm f/4.5-5.6',
        ),
        45 => array(
            'Id' => 45,
            'Label' => 'Canon EF-S 18-55mm f/3.5-5.6 [II]',
        ),
        46 => array(
            'Id' => 46,
            'Label' => 'Canon EF 28-90mm f/4-5.6',
        ),
        47 => array(
            'Id' => 47,
            'Label' => 'Zeiss Milvus 35mm f/2 or 50mm f/2',
        ),
        '47.1' => array(
            'Id' => '47.1',
            'Label' => 'Zeiss Milvus 50mm f/2 Makro',
        ),
        48 => array(
            'Id' => 48,
            'Label' => 'Canon EF-S 18-55mm f/3.5-5.6 IS',
        ),
        49 => array(
            'Id' => 49,
            'Label' => 'Canon EF-S 55-250mm f/4-5.6 IS',
        ),
        50 => array(
            'Id' => 50,
            'Label' => 'Canon EF-S 18-200mm f/3.5-5.6 IS',
        ),
        51 => array(
            'Id' => 51,
            'Label' => 'Canon EF-S 18-135mm f/3.5-5.6 IS',
        ),
        52 => array(
            'Id' => 52,
            'Label' => 'Canon EF-S 18-55mm f/3.5-5.6 IS II',
        ),
        53 => array(
            'Id' => 53,
            'Label' => 'Canon EF-S 18-55mm f/3.5-5.6 III',
        ),
        54 => array(
            'Id' => 54,
            'Label' => 'Canon EF-S 55-250mm f/4-5.6 IS II',
        ),
        94 => array(
            'Id' => 94,
            'Label' => 'Canon TS-E 17mm f/4L',
        ),
        95 => array(
            'Id' => 95,
            'Label' => 'Canon TS-E 24.0mm f/3.5 L II',
        ),
        124 => array(
            'Id' => 124,
            'Label' => 'Canon MP-E 65mm f/2.8 1-5x Macro Photo',
        ),
        125 => array(
            'Id' => 125,
            'Label' => 'Canon TS-E 24mm f/3.5L',
        ),
        126 => array(
            'Id' => 126,
            'Label' => 'Canon TS-E 45mm f/2.8',
        ),
        127 => array(
            'Id' => 127,
            'Label' => 'Canon TS-E 90mm f/2.8',
        ),
        129 => array(
            'Id' => 129,
            'Label' => 'Canon EF 300mm f/2.8L',
        ),
        130 => array(
            'Id' => 130,
            'Label' => 'Canon EF 50mm f/1.0L',
        ),
        131 => array(
            'Id' => 131,
            'Label' => 'Canon EF 28-80mm f/2.8-4L or Sigma Lens',
        ),
        '131.1' => array(
            'Id' => '131.1',
            'Label' => 'Sigma 8mm f/3.5 EX DG Circular Fisheye',
        ),
        '131.2' => array(
            'Id' => '131.2',
            'Label' => 'Sigma 17-35mm f/2.8-4 EX DG Aspherical HSM',
        ),
        '131.3' => array(
            'Id' => '131.3',
            'Label' => 'Sigma 17-70mm f/2.8-4.5 DC Macro',
        ),
        '131.4' => array(
            'Id' => '131.4',
            'Label' => 'Sigma APO 50-150mm f/2.8 [II] EX DC HSM',
        ),
        '131.5' => array(
            'Id' => '131.5',
            'Label' => 'Sigma APO 120-300mm f/2.8 EX DG HSM',
        ),
        '131.6' => array(
            'Id' => '131.6',
            'Label' => 'Sigma 4.5mm f/2.8 EX DC HSM Circular Fisheye',
        ),
        '131.7' => array(
            'Id' => '131.7',
            'Label' => 'Sigma 70-200mm f/2.8 APO EX HSM',
        ),
        132 => array(
            'Id' => 132,
            'Label' => 'Canon EF 1200mm f/5.6L',
        ),
        134 => array(
            'Id' => 134,
            'Label' => 'Canon EF 600mm f/4L IS',
        ),
        135 => array(
            'Id' => 135,
            'Label' => 'Canon EF 200mm f/1.8L',
        ),
        136 => array(
            'Id' => 136,
            'Label' => 'Canon EF 300mm f/2.8L',
        ),
        137 => array(
            'Id' => 137,
            'Label' => 'Canon EF 85mm f/1.2L or Sigma or Tamron Lens',
        ),
        '137.1' => array(
            'Id' => '137.1',
            'Label' => 'Sigma 18-50mm f/2.8-4.5 DC OS HSM',
        ),
        '137.2' => array(
            'Id' => '137.2',
            'Label' => 'Sigma 50-200mm f/4-5.6 DC OS HSM',
        ),
        '137.3' => array(
            'Id' => '137.3',
            'Label' => 'Sigma 18-250mm f/3.5-6.3 DC OS HSM',
        ),
        '137.4' => array(
            'Id' => '137.4',
            'Label' => 'Sigma 24-70mm f/2.8 IF EX DG HSM',
        ),
        '137.5' => array(
            'Id' => '137.5',
            'Label' => 'Sigma 18-125mm f/3.8-5.6 DC OS HSM',
        ),
        '137.6' => array(
            'Id' => '137.6',
            'Label' => 'Sigma 17-70mm f/2.8-4 DC Macro OS HSM',
        ),
        '137.7' => array(
            'Id' => '137.7',
            'Label' => 'Sigma 17-50mm f/2.8 OS HSM',
        ),
        '137.8' => array(
            'Id' => '137.8',
            'Label' => 'Sigma 18-200mm f/3.5-6.3 DC OS HSM [II]',
        ),
        '137.9' => array(
            'Id' => '137.9',
            'Label' => 'Tamron AF 18-270mm f/3.5-6.3 Di II VC PZD',
        ),
        '137.10' => array(
            'Id' => '137.10',
            'Label' => 'Sigma 8-16mm f/4.5-5.6 DC HSM',
        ),
        '137.11' => array(
            'Id' => '137.11',
            'Label' => 'Tamron SP 17-50mm f/2.8 XR Di II VC',
        ),
        '137.12' => array(
            'Id' => '137.12',
            'Label' => 'Tamron SP 60mm f/2 Macro Di II',
        ),
        '137.13' => array(
            'Id' => '137.13',
            'Label' => 'Sigma 10-20mm f/3.5 EX DC HSM',
        ),
        '137.14' => array(
            'Id' => '137.14',
            'Label' => 'Tamron SP 24-70mm f/2.8 Di VC USD',
        ),
        '137.15' => array(
            'Id' => '137.15',
            'Label' => 'Sigma 18-35mm f/1.8 DC HSM',
        ),
        '137.16' => array(
            'Id' => '137.16',
            'Label' => 'Sigma 12-24mm f/4.5-5.6 DG HSM II',
        ),
        138 => array(
            'Id' => 138,
            'Label' => 'Canon EF 28-80mm f/2.8-4L',
        ),
        139 => array(
            'Id' => 139,
            'Label' => 'Canon EF 400mm f/2.8L',
        ),
        140 => array(
            'Id' => 140,
            'Label' => 'Canon EF 500mm f/4.5L',
        ),
        141 => array(
            'Id' => 141,
            'Label' => 'Canon EF 500mm f/4.5L',
        ),
        142 => array(
            'Id' => 142,
            'Label' => 'Canon EF 300mm f/2.8L IS',
        ),
        143 => array(
            'Id' => 143,
            'Label' => 'Canon EF 500mm f/4L IS',
        ),
        144 => array(
            'Id' => 144,
            'Label' => 'Canon EF 35-135mm f/4-5.6 USM',
        ),
        145 => array(
            'Id' => 145,
            'Label' => 'Canon EF 100-300mm f/4.5-5.6 USM',
        ),
        146 => array(
            'Id' => 146,
            'Label' => 'Canon EF 70-210mm f/3.5-4.5 USM',
        ),
        147 => array(
            'Id' => 147,
            'Label' => 'Canon EF 35-135mm f/4-5.6 USM',
        ),
        148 => array(
            'Id' => 148,
            'Label' => 'Canon EF 28-80mm f/3.5-5.6 USM',
        ),
        149 => array(
            'Id' => 149,
            'Label' => 'Canon EF 100mm f/2 USM',
        ),
        150 => array(
            'Id' => 150,
            'Label' => 'Canon EF 14mm f/2.8L or Sigma Lens',
        ),
        '150.1' => array(
            'Id' => '150.1',
            'Label' => 'Sigma 20mm EX f/1.8',
        ),
        '150.2' => array(
            'Id' => '150.2',
            'Label' => 'Sigma 30mm f/1.4 DC HSM',
        ),
        '150.3' => array(
            'Id' => '150.3',
            'Label' => 'Sigma 24mm f/1.8 DG Macro EX',
        ),
        '150.4' => array(
            'Id' => '150.4',
            'Label' => 'Sigma 28mm f/1.8 DG Macro EX',
        ),
        151 => array(
            'Id' => 151,
            'Label' => 'Canon EF 200mm f/2.8L',
        ),
        152 => array(
            'Id' => 152,
            'Label' => 'Canon EF 300mm f/4L IS or Sigma Lens',
        ),
        '152.1' => array(
            'Id' => '152.1',
            'Label' => 'Sigma 12-24mm f/4.5-5.6 EX DG ASPHERICAL HSM',
        ),
        '152.2' => array(
            'Id' => '152.2',
            'Label' => 'Sigma 14mm f/2.8 EX Aspherical HSM',
        ),
        '152.3' => array(
            'Id' => '152.3',
            'Label' => 'Sigma 10-20mm f/4-5.6',
        ),
        '152.4' => array(
            'Id' => '152.4',
            'Label' => 'Sigma 100-300mm f/4',
        ),
        153 => array(
            'Id' => 153,
            'Label' => 'Canon EF 35-350mm f/3.5-5.6L or Sigma or Tamron Lens',
        ),
        '153.1' => array(
            'Id' => '153.1',
            'Label' => 'Sigma 50-500mm f/4-6.3 APO HSM EX',
        ),
        '153.2' => array(
            'Id' => '153.2',
            'Label' => 'Tamron AF 28-300mm f/3.5-6.3 XR LD Aspherical [IF] Macro',
        ),
        '153.3' => array(
            'Id' => '153.3',
            'Label' => 'Tamron AF 18-200mm f/3.5-6.3 XR Di II LD Aspherical [IF] Macro Model A14',
        ),
        '153.4' => array(
            'Id' => '153.4',
            'Label' => 'Tamron 18-250mm f/3.5-6.3 Di II LD Aspherical [IF] Macro',
        ),
        154 => array(
            'Id' => 154,
            'Label' => 'Canon EF 20mm f/2.8 USM or Zeiss Lens',
        ),
        '154.1' => array(
            'Id' => '154.1',
            'Label' => 'Zeiss Milvus 21mm f/2.8',
        ),
        155 => array(
            'Id' => 155,
            'Label' => 'Canon EF 85mm f/1.8 USM',
        ),
        156 => array(
            'Id' => 156,
            'Label' => 'Canon EF 28-105mm f/3.5-4.5 USM or Tamron Lens',
        ),
        '156.1' => array(
            'Id' => '156.1',
            'Label' => 'Tamron SP 70-300mm f/4.0-5.6 Di VC USD',
        ),
        160 => array(
            'Id' => 160,
            'Label' => 'Canon EF 20-35mm f/3.5-4.5 USM or Tamron or Tokina Lens',
        ),
        '160.1' => array(
            'Id' => '160.1',
            'Label' => 'Tamron AF 19-35mm f/3.5-4.5',
        ),
        '160.2' => array(
            'Id' => '160.2',
            'Label' => 'Tokina AT-X 124 AF Pro DX 12-24mm f/4',
        ),
        '160.3' => array(
            'Id' => '160.3',
            'Label' => 'Tokina AT-X 107 AF DX 10-17mm f/3.5-4.5 Fisheye',
        ),
        '160.4' => array(
            'Id' => '160.4',
            'Label' => 'Tokina AT-X 116 AF Pro DX 11-16mm f/2.8',
        ),
        161 => array(
            'Id' => 161,
            'Label' => 'Canon EF 28-70mm f/2.8L or Sigma or Tamron Lens',
        ),
        '161.1' => array(
            'Id' => '161.1',
            'Label' => 'Sigma 24-70mm f/2.8 EX',
        ),
        '161.2' => array(
            'Id' => '161.2',
            'Label' => 'Sigma 28-70mm f/2.8 EX',
        ),
        '161.3' => array(
            'Id' => '161.3',
            'Label' => 'Sigma 24-60mm f/2.8 EX DG',
        ),
        '161.4' => array(
            'Id' => '161.4',
            'Label' => 'Tamron AF 17-50mm f/2.8 Di-II LD Aspherical',
        ),
        '161.5' => array(
            'Id' => '161.5',
            'Label' => 'Tamron 90mm f/2.8',
        ),
        '161.6' => array(
            'Id' => '161.6',
            'Label' => 'Tamron SP AF 17-35mm f/2.8-4 Di LD Aspherical IF',
        ),
        '161.7' => array(
            'Id' => '161.7',
            'Label' => 'Tamron SP AF 28-75mm f/2.8 XR Di LD Aspherical [IF] Macro',
        ),
        162 => array(
            'Id' => 162,
            'Label' => 'Canon EF 200mm f/2.8L',
        ),
        163 => array(
            'Id' => 163,
            'Label' => 'Canon EF 300mm f/4L',
        ),
        164 => array(
            'Id' => 164,
            'Label' => 'Canon EF 400mm f/5.6L',
        ),
        165 => array(
            'Id' => 165,
            'Label' => 'Canon EF 70-200mm f/2.8 L',
        ),
        166 => array(
            'Id' => 166,
            'Label' => 'Canon EF 70-200mm f/2.8 L + 1.4x',
        ),
        167 => array(
            'Id' => 167,
            'Label' => 'Canon EF 70-200mm f/2.8 L + 2x',
        ),
        168 => array(
            'Id' => 168,
            'Label' => 'Canon EF 28mm f/1.8 USM',
        ),
        169 => array(
            'Id' => 169,
            'Label' => 'Canon EF 17-35mm f/2.8L or Sigma Lens',
        ),
        '169.1' => array(
            'Id' => '169.1',
            'Label' => 'Sigma 18-200mm f/3.5-6.3 DC OS',
        ),
        '169.2' => array(
            'Id' => '169.2',
            'Label' => 'Sigma 15-30mm f/3.5-4.5 EX DG Aspherical',
        ),
        '169.3' => array(
            'Id' => '169.3',
            'Label' => 'Sigma 18-50mm f/2.8 Macro',
        ),
        '169.4' => array(
            'Id' => '169.4',
            'Label' => 'Sigma 50mm f/1.4 EX DG HSM',
        ),
        '169.5' => array(
            'Id' => '169.5',
            'Label' => 'Sigma 85mm f/1.4 EX DG HSM',
        ),
        '169.6' => array(
            'Id' => '169.6',
            'Label' => 'Sigma 30mm f/1.4 EX DC HSM',
        ),
        '169.7' => array(
            'Id' => '169.7',
            'Label' => 'Sigma 35mm f/1.4 DG HSM',
        ),
        170 => array(
            'Id' => 170,
            'Label' => 'Canon EF 200mm f/2.8L II',
        ),
        171 => array(
            'Id' => 171,
            'Label' => 'Canon EF 300mm f/4L',
        ),
        172 => array(
            'Id' => 172,
            'Label' => 'Canon EF 400mm f/5.6L or Sigma Lens',
        ),
        '172.1' => array(
            'Id' => '172.1',
            'Label' => 'Sigma 150-600mm f/5-6.3 DG OS HSM | S',
        ),
        173 => array(
            'Id' => 173,
            'Label' => 'Canon EF 180mm Macro f/3.5L or Sigma Lens',
        ),
        '173.1' => array(
            'Id' => '173.1',
            'Label' => 'Sigma 180mm EX HSM Macro f/3.5',
        ),
        '173.2' => array(
            'Id' => '173.2',
            'Label' => 'Sigma APO Macro 150mm f/2.8 EX DG HSM',
        ),
        174 => array(
            'Id' => 174,
            'Label' => 'Canon EF 135mm f/2L or Other Lens',
        ),
        '174.1' => array(
            'Id' => '174.1',
            'Label' => 'Sigma 70-200mm f/2.8 EX DG APO OS HSM',
        ),
        '174.2' => array(
            'Id' => '174.2',
            'Label' => 'Sigma 50-500mm f/4.5-6.3 APO DG OS HSM',
        ),
        '174.3' => array(
            'Id' => '174.3',
            'Label' => 'Sigma 150-500mm f/5-6.3 APO DG OS HSM',
        ),
        '174.4' => array(
            'Id' => '174.4',
            'Label' => 'Zeiss Milvus 100mm f/2 Makro',
        ),
        175 => array(
            'Id' => 175,
            'Label' => 'Canon EF 400mm f/2.8L',
        ),
        176 => array(
            'Id' => 176,
            'Label' => 'Canon EF 24-85mm f/3.5-4.5 USM',
        ),
        177 => array(
            'Id' => 177,
            'Label' => 'Canon EF 300mm f/4L IS',
        ),
        178 => array(
            'Id' => 178,
            'Label' => 'Canon EF 28-135mm f/3.5-5.6 IS',
        ),
        179 => array(
            'Id' => 179,
            'Label' => 'Canon EF 24mm f/1.4L',
        ),
        180 => array(
            'Id' => 180,
            'Label' => 'Canon EF 35mm f/1.4L or Other Lens',
        ),
        '180.1' => array(
            'Id' => '180.1',
            'Label' => 'Sigma 50mm f/1.4 DG HSM | A',
        ),
        '180.2' => array(
            'Id' => '180.2',
            'Label' => 'Sigma 24mm f/1.4 DG HSM | A',
        ),
        '180.3' => array(
            'Id' => '180.3',
            'Label' => 'Zeiss Milvus 50mm f/1.4',
        ),
        '180.4' => array(
            'Id' => '180.4',
            'Label' => 'Zeiss Milvus 85mm f/1.4',
        ),
        '180.5' => array(
            'Id' => '180.5',
            'Label' => 'Zeiss Otus 28mm f/1.4 ZE',
        ),
        181 => array(
            'Id' => 181,
            'Label' => 'Canon EF 100-400mm f/4.5-5.6L IS + 1.4x or Sigma Lens',
        ),
        '181.1' => array(
            'Id' => '181.1',
            'Label' => 'Sigma 150-600mm f/5-6.3 DG OS HSM | S + 1.4x',
        ),
        182 => array(
            'Id' => 182,
            'Label' => 'Canon EF 100-400mm f/4.5-5.6L IS + 2x or Sigma Lens',
        ),
        '182.1' => array(
            'Id' => '182.1',
            'Label' => 'Sigma 150-600mm f/5-6.3 DG OS HSM | S + 2x',
        ),
        183 => array(
            'Id' => 183,
            'Label' => 'Canon EF 100-400mm f/4.5-5.6L IS or Sigma Lens',
        ),
        '183.1' => array(
            'Id' => '183.1',
            'Label' => 'Sigma 150mm f/2.8 EX DG OS HSM APO Macro',
        ),
        '183.2' => array(
            'Id' => '183.2',
            'Label' => 'Sigma 105mm f/2.8 EX DG OS HSM Macro',
        ),
        '183.3' => array(
            'Id' => '183.3',
            'Label' => 'Sigma 180mm f/2.8 EX DG OS HSM APO Macro',
        ),
        '183.4' => array(
            'Id' => '183.4',
            'Label' => 'Sigma 150-600mm f/5-6.3 DG OS HSM | C',
        ),
        184 => array(
            'Id' => 184,
            'Label' => 'Canon EF 400mm f/2.8L + 2x',
        ),
        185 => array(
            'Id' => 185,
            'Label' => 'Canon EF 600mm f/4L IS',
        ),
        186 => array(
            'Id' => 186,
            'Label' => 'Canon EF 70-200mm f/4L',
        ),
        187 => array(
            'Id' => 187,
            'Label' => 'Canon EF 70-200mm f/4L + 1.4x',
        ),
        188 => array(
            'Id' => 188,
            'Label' => 'Canon EF 70-200mm f/4L + 2x',
        ),
        189 => array(
            'Id' => 189,
            'Label' => 'Canon EF 70-200mm f/4L + 2.8x',
        ),
        190 => array(
            'Id' => 190,
            'Label' => 'Canon EF 100mm f/2.8 Macro USM',
        ),
        191 => array(
            'Id' => 191,
            'Label' => 'Canon EF 400mm f/4 DO IS',
        ),
        193 => array(
            'Id' => 193,
            'Label' => 'Canon EF 35-80mm f/4-5.6 USM',
        ),
        194 => array(
            'Id' => 194,
            'Label' => 'Canon EF 80-200mm f/4.5-5.6 USM',
        ),
        195 => array(
            'Id' => 195,
            'Label' => 'Canon EF 35-105mm f/4.5-5.6 USM',
        ),
        196 => array(
            'Id' => 196,
            'Label' => 'Canon EF 75-300mm f/4-5.6 USM',
        ),
        197 => array(
            'Id' => 197,
            'Label' => 'Canon EF 75-300mm f/4-5.6 IS USM',
        ),
        198 => array(
            'Id' => 198,
            'Label' => 'Canon EF 50mm f/1.4 USM or Zeiss Lens',
        ),
        '198.1' => array(
            'Id' => '198.1',
            'Label' => 'Zeiss Otus 55mm f/1.4 ZE',
        ),
        '198.2' => array(
            'Id' => '198.2',
            'Label' => 'Zeiss Otus 85mm f/1.4 ZE',
        ),
        199 => array(
            'Id' => 199,
            'Label' => 'Canon EF 28-80mm f/3.5-5.6 USM',
        ),
        200 => array(
            'Id' => 200,
            'Label' => 'Canon EF 75-300mm f/4-5.6 USM',
        ),
        201 => array(
            'Id' => 201,
            'Label' => 'Canon EF 28-80mm f/3.5-5.6 USM',
        ),
        202 => array(
            'Id' => 202,
            'Label' => 'Canon EF 28-80mm f/3.5-5.6 USM IV',
        ),
        208 => array(
            'Id' => 208,
            'Label' => 'Canon EF 22-55mm f/4-5.6 USM',
        ),
        209 => array(
            'Id' => 209,
            'Label' => 'Canon EF 55-200mm f/4.5-5.6',
        ),
        210 => array(
            'Id' => 210,
            'Label' => 'Canon EF 28-90mm f/4-5.6 USM',
        ),
        211 => array(
            'Id' => 211,
            'Label' => 'Canon EF 28-200mm f/3.5-5.6 USM',
        ),
        212 => array(
            'Id' => 212,
            'Label' => 'Canon EF 28-105mm f/4-5.6 USM',
        ),
        213 => array(
            'Id' => 213,
            'Label' => 'Canon EF 90-300mm f/4.5-5.6 USM or Tamron Lens',
        ),
        '213.1' => array(
            'Id' => '213.1',
            'Label' => 'Tamron SP 150-600mm f/5-6.3 Di VC USD',
        ),
        '213.2' => array(
            'Id' => '213.2',
            'Label' => 'Tamron 16-300mm f/3.5-6.3 Di II VC PZD Macro',
        ),
        '213.3' => array(
            'Id' => '213.3',
            'Label' => 'Tamron SP 35mm f/1.8 Di VC USD',
        ),
        '213.4' => array(
            'Id' => '213.4',
            'Label' => 'Tamron SP 45mm f/1.8 Di VC USD',
        ),
        214 => array(
            'Id' => 214,
            'Label' => 'Canon EF-S 18-55mm f/3.5-5.6 USM',
        ),
        215 => array(
            'Id' => 215,
            'Label' => 'Canon EF 55-200mm f/4.5-5.6 II USM',
        ),
        217 => array(
            'Id' => 217,
            'Label' => 'Tamron AF 18-270mm f/3.5-6.3 Di II VC PZD',
        ),
        224 => array(
            'Id' => 224,
            'Label' => 'Canon EF 70-200mm f/2.8L IS',
        ),
        225 => array(
            'Id' => 225,
            'Label' => 'Canon EF 70-200mm f/2.8L IS + 1.4x',
        ),
        226 => array(
            'Id' => 226,
            'Label' => 'Canon EF 70-200mm f/2.8L IS + 2x',
        ),
        227 => array(
            'Id' => 227,
            'Label' => 'Canon EF 70-200mm f/2.8L IS + 2.8x',
        ),
        228 => array(
            'Id' => 228,
            'Label' => 'Canon EF 28-105mm f/3.5-4.5 USM',
        ),
        229 => array(
            'Id' => 229,
            'Label' => 'Canon EF 16-35mm f/2.8L',
        ),
        230 => array(
            'Id' => 230,
            'Label' => 'Canon EF 24-70mm f/2.8L',
        ),
        231 => array(
            'Id' => 231,
            'Label' => 'Canon EF 17-40mm f/4L',
        ),
        232 => array(
            'Id' => 232,
            'Label' => 'Canon EF 70-300mm f/4.5-5.6 DO IS USM',
        ),
        233 => array(
            'Id' => 233,
            'Label' => 'Canon EF 28-300mm f/3.5-5.6L IS',
        ),
        234 => array(
            'Id' => 234,
            'Label' => 'Canon EF-S 17-85mm f/4-5.6 IS USM or Tokina Lens',
        ),
        '234.1' => array(
            'Id' => '234.1',
            'Label' => 'Tokina AT-X 12-28mm f/4 PRO DX',
        ),
        235 => array(
            'Id' => 235,
            'Label' => 'Canon EF-S 10-22mm f/3.5-4.5 USM',
        ),
        236 => array(
            'Id' => 236,
            'Label' => 'Canon EF-S 60mm f/2.8 Macro USM',
        ),
        237 => array(
            'Id' => 237,
            'Label' => 'Canon EF 24-105mm f/4L IS',
        ),
        238 => array(
            'Id' => 238,
            'Label' => 'Canon EF 70-300mm f/4-5.6 IS USM',
        ),
        239 => array(
            'Id' => 239,
            'Label' => 'Canon EF 85mm f/1.2L II',
        ),
        240 => array(
            'Id' => 240,
            'Label' => 'Canon EF-S 17-55mm f/2.8 IS USM',
        ),
        241 => array(
            'Id' => 241,
            'Label' => 'Canon EF 50mm f/1.2L',
        ),
        242 => array(
            'Id' => 242,
            'Label' => 'Canon EF 70-200mm f/4L IS',
        ),
        243 => array(
            'Id' => 243,
            'Label' => 'Canon EF 70-200mm f/4L IS + 1.4x',
        ),
        244 => array(
            'Id' => 244,
            'Label' => 'Canon EF 70-200mm f/4L IS + 2x',
        ),
        245 => array(
            'Id' => 245,
            'Label' => 'Canon EF 70-200mm f/4L IS + 2.8x',
        ),
        246 => array(
            'Id' => 246,
            'Label' => 'Canon EF 16-35mm f/2.8L II',
        ),
        247 => array(
            'Id' => 247,
            'Label' => 'Canon EF 14mm f/2.8L II USM',
        ),
        248 => array(
            'Id' => 248,
            'Label' => 'Canon EF 200mm f/2L IS or Sigma Lens',
        ),
        '248.1' => array(
            'Id' => '248.1',
            'Label' => 'Sigma 24-35mm f/2 DG HSM | A',
        ),
        249 => array(
            'Id' => 249,
            'Label' => 'Canon EF 800mm f/5.6L IS',
        ),
        250 => array(
            'Id' => 250,
            'Label' => 'Canon EF 24mm f/1.4L II or Sigma Lens',
        ),
        '250.1' => array(
            'Id' => '250.1',
            'Label' => 'Sigma 20mm f/1.4 DG HSM | A',
        ),
        251 => array(
            'Id' => 251,
            'Label' => 'Canon EF 70-200mm f/2.8L IS II USM',
        ),
        252 => array(
            'Id' => 252,
            'Label' => 'Canon EF 70-200mm f/2.8L IS II USM + 1.4x',
        ),
        253 => array(
            'Id' => 253,
            'Label' => 'Canon EF 70-200mm f/2.8L IS II USM + 2x',
        ),
        254 => array(
            'Id' => 254,
            'Label' => 'Canon EF 100mm f/2.8L Macro IS USM',
        ),
        255 => array(
            'Id' => 255,
            'Label' => 'Sigma 24-105mm f/4 DG OS HSM | A or Other Sigma Lens',
        ),
        '255.1' => array(
            'Id' => '255.1',
            'Label' => 'Sigma 180mm f/2.8 EX DG OS HSM APO Macro',
        ),
        488 => array(
            'Id' => 488,
            'Label' => 'Canon EF-S 15-85mm f/3.5-5.6 IS USM',
        ),
        489 => array(
            'Id' => 489,
            'Label' => 'Canon EF 70-300mm f/4-5.6L IS USM',
        ),
        490 => array(
            'Id' => 490,
            'Label' => 'Canon EF 8-15mm f/4L Fisheye USM',
        ),
        491 => array(
            'Id' => 491,
            'Label' => 'Canon EF 300mm f/2.8L IS II USM',
        ),
        492 => array(
            'Id' => 492,
            'Label' => 'Canon EF 400mm f/2.8L IS II USM',
        ),
        493 => array(
            'Id' => 493,
            'Label' => 'Canon EF 500mm f/4L IS II USM or EF 24-105mm f4L IS USM',
        ),
        '493.1' => array(
            'Id' => '493.1',
            'Label' => 'Canon EF 24-105mm f/4L IS USM',
        ),
        494 => array(
            'Id' => 494,
            'Label' => 'Canon EF 600mm f/4.0L IS II USM',
        ),
        495 => array(
            'Id' => 495,
            'Label' => 'Canon EF 24-70mm f/2.8L II USM',
        ),
        496 => array(
            'Id' => 496,
            'Label' => 'Canon EF 200-400mm f/4L IS USM',
        ),
        499 => array(
            'Id' => 499,
            'Label' => 'Canon EF 200-400mm f/4L IS USM + 1.4x',
        ),
        502 => array(
            'Id' => 502,
            'Label' => 'Canon EF 28mm f/2.8 IS USM',
        ),
        503 => array(
            'Id' => 503,
            'Label' => 'Canon EF 24mm f/2.8 IS USM',
        ),
        504 => array(
            'Id' => 504,
            'Label' => 'Canon EF 24-70mm f/4L IS USM',
        ),
        505 => array(
            'Id' => 505,
            'Label' => 'Canon EF 35mm f/2 IS USM',
        ),
        506 => array(
            'Id' => 506,
            'Label' => 'Canon EF 400mm f/4 DO IS II USM',
        ),
        507 => array(
            'Id' => 507,
            'Label' => 'Canon EF 16-35mm f/4L IS USM',
        ),
        508 => array(
            'Id' => 508,
            'Label' => 'Canon EF 11-24mm f/4L USM',
        ),
        747 => array(
            'Id' => 747,
            'Label' => 'Canon EF 100-400mm f/4.5-5.6L IS II USM',
        ),
        750 => array(
            'Id' => 750,
            'Label' => 'Canon EF 35mm f/1.4L II USM',
        ),
        4142 => array(
            'Id' => 4142,
            'Label' => 'Canon EF-S 18-135mm f/3.5-5.6 IS STM',
        ),
        4143 => array(
            'Id' => 4143,
            'Label' => 'Canon EF-M 18-55mm f/3.5-5.6 IS STM or Tamron Lens',
        ),
        '4143.1' => array(
            'Id' => '4143.1',
            'Label' => 'Tamron 18-200mm F/3.5-6.3 Di III VC',
        ),
        4144 => array(
            'Id' => 4144,
            'Label' => 'Canon EF 40mm f/2.8 STM',
        ),
        4145 => array(
            'Id' => 4145,
            'Label' => 'Canon EF-M 22mm f/2 STM',
        ),
        4146 => array(
            'Id' => 4146,
            'Label' => 'Canon EF-S 18-55mm f/3.5-5.6 IS STM',
        ),
        4147 => array(
            'Id' => 4147,
            'Label' => 'Canon EF-M 11-22mm f/4-5.6 IS STM',
        ),
        4148 => array(
            'Id' => 4148,
            'Label' => 'Canon EF-S 55-250mm f/4-5.6 IS STM',
        ),
        4149 => array(
            'Id' => 4149,
            'Label' => 'Canon EF-M 55-200mm f/4.5-6.3 IS STM',
        ),
        4150 => array(
            'Id' => 4150,
            'Label' => 'Canon EF-S 10-18mm f/4.5-5.6 IS STM',
        ),
        4152 => array(
            'Id' => 4152,
            'Label' => 'Canon EF 24-105mm f/3.5-5.6 IS STM',
        ),
        4153 => array(
            'Id' => 4153,
            'Label' => 'Canon EF-M 15-45mm f/3.5-6.3 IS STM',
        ),
        4154 => array(
            'Id' => 4154,
            'Label' => 'Canon EF-S 24mm f/2.8 STM',
        ),
        4156 => array(
            'Id' => 4156,
            'Label' => 'Canon EF 50mm f/1.8 STM',
        ),
    );

}
