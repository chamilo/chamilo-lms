<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Composite;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class LensID extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'LensID';

    protected $FullName = 'Composite';

    protected $GroupName = 'Composite';

    protected $g0 = 'Composite';

    protected $g1 = 'Composite';

    protected $g2 = 'Other';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Lens ID';

    protected $local_g2 = 'Camera';

    protected $Values = array(
        'RL1' => array(
            'Id' => 'RL1',
            'Label' => 'GR Lens A12 50mm F2.5 Macro',
        ),
        'RL2' => array(
            'Id' => 'RL2',
            'Label' => 'Ricoh Lens S10 24-70mm F2.5-4.4 VC',
        ),
        'RL3' => array(
            'Id' => 'RL3',
            'Label' => 'Ricoh Lens P10 28-300mm F3.5-5.6 VC',
        ),
        'RL5' => array(
            'Id' => 'RL5',
            'Label' => 'GR Lens A12 28mm F2.5',
        ),
        'RL6' => array(
            'Id' => 'RL6',
            'Label' => 'Ricoh Lens A16 24-85mm F3.5-5.5',
        ),
        'RL8' => array(
            'Id' => 'RL8',
            'Label' => 'Mount A12',
        ),
        '00 00 00 00 00 00 00 01' => array(
            'Id' => '00 00 00 00 00 00 00 01',
            'Label' => 'Manual Lens No CPU',
        ),
        '00 00 00 00 00 00 E1 12' => array(
            'Id' => '00 00 00 00 00 00 E1 12',
            'Label' => 'TC-17E II',
        ),
        '00 00 00 00 00 00 F1 0C' => array(
            'Id' => '00 00 00 00 00 00 F1 0C',
            'Label' => 'TC-14E [II] or Sigma APO Tele Converter 1.4x EX DG or Kenko Teleplus PRO 300 DG 1.4x',
        ),
        '00 00 00 00 00 00 F2 18' => array(
            'Id' => '00 00 00 00 00 00 F2 18',
            'Label' => 'TC-20E [II] or Sigma APO Tele Converter 2x EX DG or Kenko Teleplus PRO 300 DG 2.0x',
        ),
        '00 00 48 48 53 53 00 01' => array(
            'Id' => '00 00 48 48 53 53 00 01',
            'Label' => 'Loreo 40mm F11-22 3D Lens in a Cap 9005',
        ),
        '00 36 1C 2D 34 3C 00 06' => array(
            'Id' => '00 36 1C 2D 34 3C 00 06',
            'Label' => 'Tamron SP AF 11-18mm f/4.5-5.6 Di II LD Aspherical (IF) (A13)',
        ),
        '00 3C 1F 37 30 30 00 06' => array(
            'Id' => '00 3C 1F 37 30 30 00 06',
            'Label' => 'Tokina AT-X 124 AF PRO DX (AF 12-24mm f/4)',
        ),
        '00 3C 2B 44 30 30 00 06' => array(
            'Id' => '00 3C 2B 44 30 30 00 06',
            'Label' => 'Tokina AT-X 17-35 F4 PRO FX (AF 17-35mm f/4)',
        ),
        '00 3C 5C 80 30 30 00 0E' => array(
            'Id' => '00 3C 5C 80 30 30 00 0E',
            'Label' => 'Tokina AT-X 70-200 F4 FX VCM-S (AF 70-200mm f/4)',
        ),
        '00 3E 80 A0 38 3F 00 02' => array(
            'Id' => '00 3E 80 A0 38 3F 00 02',
            'Label' => 'Tamron SP AF 200-500mm f/5-6.3 Di LD (IF) (A08)',
        ),
        '00 3F 2D 80 2B 40 00 06' => array(
            'Id' => '00 3F 2D 80 2B 40 00 06',
            'Label' => 'Tamron AF 18-200mm f/3.5-6.3 XR Di II LD Aspherical (IF) (A14)',
        ),
        '00 3F 2D 80 2C 40 00 06' => array(
            'Id' => '00 3F 2D 80 2C 40 00 06',
            'Label' => 'Tamron AF 18-200mm f/3.5-6.3 XR Di II LD Aspherical (IF) Macro (A14)',
        ),
        '00 3F 80 A0 38 3F 00 02' => array(
            'Id' => '00 3F 80 A0 38 3F 00 02',
            'Label' => 'Tamron SP AF 200-500mm f/5-6.3 Di (A08)',
        ),
        '00 40 11 11 2C 2C 00 00' => array(
            'Id' => '00 40 11 11 2C 2C 00 00',
            'Label' => 'Samyang 8mm f/3.5 Fish-Eye',
        ),
        '00 40 18 2B 2C 34 00 06' => array(
            'Id' => '00 40 18 2B 2C 34 00 06',
            'Label' => 'Tokina AT-X 107 AF DX Fisheye (AF 10-17mm f/3.5-4.5)',
        ),
        '00 40 2A 72 2C 3C 00 06' => array(
            'Id' => '00 40 2A 72 2C 3C 00 06',
            'Label' => 'Tokina AT-X 16.5-135 DX (AF 16.5-135mm F3.5-5.6)',
        ),
        '00 40 2B 2B 2C 2C 00 02' => array(
            'Id' => '00 40 2B 2B 2C 2C 00 02',
            'Label' => 'Tokina AT-X 17 AF PRO (AF 17mm f/3.5)',
        ),
        '00 40 2D 2D 2C 2C 00 00' => array(
            'Id' => '00 40 2D 2D 2C 2C 00 00',
            'Label' => 'Carl Zeiss Distagon T* 3.5/18 ZF.2',
        ),
        '00 40 2D 80 2C 40 00 06' => array(
            'Id' => '00 40 2D 80 2C 40 00 06',
            'Label' => 'Tamron AF 18-200mm f/3.5-6.3 XR Di II LD Aspherical (IF) Macro (A14NII)',
        ),
        '00 40 2D 88 2C 40 00 06' => array(
            'Id' => '00 40 2D 88 2C 40 00 06',
            'Label' => 'Tamron AF 18-250mm f/3.5-6.3 Di II LD Aspherical (IF) Macro (A18NII)',
        ),
        '00 40 2D 88 2C 40 62 06' => array(
            'Id' => '00 40 2D 88 2C 40 62 06',
            'Label' => 'Tamron AF 18-250mm f/3.5-6.3 Di II LD Aspherical (IF) Macro (A18)',
        ),
        '00 40 31 31 2C 2C 00 00' => array(
            'Id' => '00 40 31 31 2C 2C 00 00',
            'Label' => 'Voigtlander Color Skopar 20mm F3.5 SLII Aspherical',
        ),
        '00 40 37 80 2C 3C 00 02' => array(
            'Id' => '00 40 37 80 2C 3C 00 02',
            'Label' => 'Tokina AT-X 242 AF (AF 24-200mm f/3.5-5.6)',
        ),
        '00 40 64 64 2C 2C 00 00' => array(
            'Id' => '00 40 64 64 2C 2C 00 00',
            'Label' => 'Voigtlander APO-Lanthar 90mm F3.5 SLII Close Focus',
        ),
        '00 44 60 98 34 3C 00 02' => array(
            'Id' => '00 44 60 98 34 3C 00 02',
            'Label' => 'Tokina AT-X 840 D (AF 80-400mm f/4.5-5.6)',
        ),
        '00 47 10 10 24 24 00 00' => array(
            'Id' => '00 47 10 10 24 24 00 00',
            'Label' => 'Fisheye Nikkor 8mm f/2.8 AiS',
        ),
        '00 47 25 25 24 24 00 02' => array(
            'Id' => '00 47 25 25 24 24 00 02',
            'Label' => 'Tamron SP AF 14mm f/2.8 Aspherical (IF) (69E)',
        ),
        '00 47 3C 3C 24 24 00 00' => array(
            'Id' => '00 47 3C 3C 24 24 00 00',
            'Label' => 'Nikkor 28mm f/2.8 AiS',
        ),
        '00 47 44 44 24 24 00 06' => array(
            'Id' => '00 47 44 44 24 24 00 06',
            'Label' => 'Tokina AT-X M35 PRO DX (AF 35mm f/2.8 Macro)',
        ),
        '00 47 53 80 30 3C 00 06' => array(
            'Id' => '00 47 53 80 30 3C 00 06',
            'Label' => 'Tamron AF 55-200mm f/4-5.6 Di II LD (A15)',
        ),
        '00 48 1C 29 24 24 00 06' => array(
            'Id' => '00 48 1C 29 24 24 00 06',
            'Label' => 'Tokina AT-X 116 PRO DX (AF 11-16mm f/2.8)',
        ),
        '00 48 29 3C 24 24 00 06' => array(
            'Id' => '00 48 29 3C 24 24 00 06',
            'Label' => 'Tokina AT-X 16-28 AF PRO FX (AF 16-28mm f/2.8)',
        ),
        '00 48 29 50 24 24 00 06' => array(
            'Id' => '00 48 29 50 24 24 00 06',
            'Label' => 'Tokina AT-X 165 PRO DX (AF 16-50mm f/2.8)',
        ),
        '00 48 32 32 24 24 00 00' => array(
            'Id' => '00 48 32 32 24 24 00 00',
            'Label' => 'Carl Zeiss Distagon T* 2.8/21 ZF.2',
        ),
        '00 48 37 5C 24 24 00 06' => array(
            'Id' => '00 48 37 5C 24 24 00 06',
            'Label' => 'Tokina AT-X 24-70 F2.8 PRO FX (AF 24-70mm f/2.8)',
        ),
        '00 48 3C 3C 24 24 00 00' => array(
            'Id' => '00 48 3C 3C 24 24 00 00',
            'Label' => 'Voigtlander Color Skopar 28mm F2.8 SL II',
        ),
        '00 48 3C 60 24 24 00 02' => array(
            'Id' => '00 48 3C 60 24 24 00 02',
            'Label' => 'Tokina AT-X 280 AF PRO (AF 28-80mm f/2.8)',
        ),
        '00 48 3C 6A 24 24 00 02' => array(
            'Id' => '00 48 3C 6A 24 24 00 02',
            'Label' => 'Tamron SP AF 28-105mm f/2.8 LD Aspherical IF (176D)',
        ),
        '00 48 50 50 18 18 00 00' => array(
            'Id' => '00 48 50 50 18 18 00 00',
            'Label' => 'Nikkor H 50mm f/2',
        ),
        '00 48 50 72 24 24 00 06' => array(
            'Id' => '00 48 50 72 24 24 00 06',
            'Label' => 'Tokina AT-X 535 PRO DX (AF 50-135mm f/2.8)',
        ),
        '00 48 5C 80 30 30 00 0E' => array(
            'Id' => '00 48 5C 80 30 30 00 0E',
            'Label' => 'Tokina AT-X 70-200 F4 FX VCM-S (AF 70-200mm f/4)',
        ),
        '00 48 5C 8E 30 3C 00 06' => array(
            'Id' => '00 48 5C 8E 30 3C 00 06',
            'Label' => 'Tamron AF 70-300mm f/4-5.6 Di LD Macro 1:2 (A17NII)',
        ),
        '00 48 68 68 24 24 00 00' => array(
            'Id' => '00 48 68 68 24 24 00 00',
            'Label' => 'Series E 100mm f/2.8',
        ),
        '00 48 80 80 30 30 00 00' => array(
            'Id' => '00 48 80 80 30 30 00 00',
            'Label' => 'Nikkor 200mm f/4 AiS',
        ),
        '00 49 30 48 22 2B 00 02' => array(
            'Id' => '00 49 30 48 22 2B 00 02',
            'Label' => 'Tamron SP AF 20-40mm f/2.7-3.5 (166D)',
        ),
        '00 4C 6A 6A 20 20 00 00' => array(
            'Id' => '00 4C 6A 6A 20 20 00 00',
            'Label' => 'Nikkor 105mm f/2.5 AiS',
        ),
        '00 4C 7C 7C 2C 2C 00 02' => array(
            'Id' => '00 4C 7C 7C 2C 2C 00 02',
            'Label' => 'Tamron SP AF 180mm f/3.5 Di Model (B01)',
        ),
        '00 53 2B 50 24 24 00 06' => array(
            'Id' => '00 53 2B 50 24 24 00 06',
            'Label' => 'Tamron SP AF 17-50mm f/2.8 XR Di II LD Aspherical (IF) (A16)',
        ),
        '00 54 2B 50 24 24 00 06' => array(
            'Id' => '00 54 2B 50 24 24 00 06',
            'Label' => 'Tamron SP AF 17-50mm f/2.8 XR Di II LD Aspherical (IF) (A16NII)',
        ),
        '00 54 3C 3C 18 18 00 00' => array(
            'Id' => '00 54 3C 3C 18 18 00 00',
            'Label' => 'Carl Zeiss Distagon T* 2/28 ZF.2',
        ),
        '00 54 44 44 0C 0C 00 00' => array(
            'Id' => '00 54 44 44 0C 0C 00 00',
            'Label' => 'Carl Zeiss Distagon T* 1.4/35 ZF.2',
        ),
        '00 54 44 44 18 18 00 00' => array(
            'Id' => '00 54 44 44 18 18 00 00',
            'Label' => 'Carl Zeiss Distagon T* 2/35 ZF.2',
        ),
        '00 54 48 48 18 18 00 00' => array(
            'Id' => '00 54 48 48 18 18 00 00',
            'Label' => 'Voigtlander Ultron 40mm F2 SLII Aspherical',
        ),
        '00 54 50 50 0C 0C 00 00' => array(
            'Id' => '00 54 50 50 0C 0C 00 00',
            'Label' => 'Carl Zeiss Planar T* 1.4/50 ZF.2',
        ),
        '00 54 50 50 18 18 00 00' => array(
            'Id' => '00 54 50 50 18 18 00 00',
            'Label' => 'Carl Zeiss Makro-Planar T* 2/50 ZF.2',
        ),
        '00 54 53 53 0C 0C 00 00' => array(
            'Id' => '00 54 53 53 0C 0C 00 00',
            'Label' => 'Zeiss Otus 1.4/55',
        ),
        '00 54 55 55 0C 0C 00 00' => array(
            'Id' => '00 54 55 55 0C 0C 00 00',
            'Label' => 'Voigtlander Nokton 58mm F1.4 SLII',
        ),
        '00 54 56 56 30 30 00 00' => array(
            'Id' => '00 54 56 56 30 30 00 00',
            'Label' => 'Coastal Optical Systems 60mm 1:4 UV-VIS-IR Macro Apo',
        ),
        '00 54 62 62 0C 0C 00 00' => array(
            'Id' => '00 54 62 62 0C 0C 00 00',
            'Label' => 'Carl Zeiss Planar T* 1.4/85 ZF.2',
        ),
        '00 54 68 68 18 18 00 00' => array(
            'Id' => '00 54 68 68 18 18 00 00',
            'Label' => 'Carl Zeiss Makro-Planar T* 2/100 ZF.2',
        ),
        '00 54 68 68 24 24 00 02' => array(
            'Id' => '00 54 68 68 24 24 00 02',
            'Label' => 'Tokina AT-X M100 AF PRO D (AF 100mm f/2.8 Macro)',
        ),
        '00 54 72 72 18 18 00 00' => array(
            'Id' => '00 54 72 72 18 18 00 00',
            'Label' => 'Carl Zeiss Apo Sonnar T* 2/135 ZF.2',
        ),
        '00 54 8E 8E 24 24 00 02' => array(
            'Id' => '00 54 8E 8E 24 24 00 02',
            'Label' => 'Tokina AT-X 300 AF PRO (AF 300mm f/2.8)',
        ),
        '00 57 50 50 14 14 00 00' => array(
            'Id' => '00 57 50 50 14 14 00 00',
            'Label' => 'Nikkor 50mm f/1.8 AI',
        ),
        '00 58 64 64 20 20 00 00' => array(
            'Id' => '00 58 64 64 20 20 00 00',
            'Label' => 'Soligor C/D Macro MC 90mm f/2.5',
        ),
        '01 00 00 00 00 00 02 00' => array(
            'Id' => '01 00 00 00 00 00 02 00',
            'Label' => 'TC-16A',
        ),
        '01 00 00 00 00 00 08 00' => array(
            'Id' => '01 00 00 00 00 00 08 00',
            'Label' => 'TC-16A',
        ),
        '01 54 62 62 0C 0C 00 00' => array(
            'Id' => '01 54 62 62 0C 0C 00 00',
            'Label' => 'Zeiss Otus 1.4/85',
        ),
        '01 58 50 50 14 14 02 00' => array(
            'Id' => '01 58 50 50 14 14 02 00',
            'Label' => 'AF Nikkor 50mm f/1.8',
        ),
        '01 58 50 50 14 14 05 00' => array(
            'Id' => '01 58 50 50 14 14 05 00',
            'Label' => 'AF Nikkor 50mm f/1.8',
        ),
        '02 2F 98 98 3D 3D 02 00' => array(
            'Id' => '02 2F 98 98 3D 3D 02 00',
            'Label' => 'Sigma APO 400mm F5.6',
        ),
        '02 34 A0 A0 44 44 02 00' => array(
            'Id' => '02 34 A0 A0 44 44 02 00',
            'Label' => 'Sigma APO 500mm F7.2',
        ),
        '02 37 5E 8E 35 3D 02 00' => array(
            'Id' => '02 37 5E 8E 35 3D 02 00',
            'Label' => 'Sigma 75-300mm F4.5-5.6 APO',
        ),
        '02 37 A0 A0 34 34 02 00' => array(
            'Id' => '02 37 A0 A0 34 34 02 00',
            'Label' => 'Sigma APO 500mm F4.5',
        ),
        '02 3A 37 50 31 3D 02 00' => array(
            'Id' => '02 3A 37 50 31 3D 02 00',
            'Label' => 'Sigma 24-50mm F4-5.6 UC',
        ),
        '02 3A 5E 8E 32 3D 02 00' => array(
            'Id' => '02 3A 5E 8E 32 3D 02 00',
            'Label' => 'Sigma 75-300mm F4.0-5.6',
        ),
        '02 3B 44 61 30 3D 02 00' => array(
            'Id' => '02 3B 44 61 30 3D 02 00',
            'Label' => 'Sigma 35-80mm F4-5.6',
        ),
        '02 3C B0 B0 3C 3C 02 00' => array(
            'Id' => '02 3C B0 B0 3C 3C 02 00',
            'Label' => 'Sigma APO 800mm F5.6',
        ),
        '02 3F 24 24 2C 2C 02 00' => array(
            'Id' => '02 3F 24 24 2C 2C 02 00',
            'Label' => 'Sigma 14mm F3.5',
        ),
        '02 3F 3C 5C 2D 35 02 00' => array(
            'Id' => '02 3F 3C 5C 2D 35 02 00',
            'Label' => 'Sigma 28-70mm F3.5-4.5 UC',
        ),
        '02 40 44 5C 2C 34 02 00' => array(
            'Id' => '02 40 44 5C 2C 34 02 00',
            'Label' => 'Exakta AF 35-70mm 1:3.5-4.5 MC',
        ),
        '02 40 44 73 2B 36 02 00' => array(
            'Id' => '02 40 44 73 2B 36 02 00',
            'Label' => 'Sigma 35-135mm F3.5-4.5 a',
        ),
        '02 40 5C 82 2C 35 02 00' => array(
            'Id' => '02 40 5C 82 2C 35 02 00',
            'Label' => 'Sigma APO 70-210mm F3.5-4.5',
        ),
        '02 42 44 5C 2A 34 02 00' => array(
            'Id' => '02 42 44 5C 2A 34 02 00',
            'Label' => 'AF Zoom-Nikkor 35-70mm f/3.3-4.5',
        ),
        '02 42 44 5C 2A 34 08 00' => array(
            'Id' => '02 42 44 5C 2A 34 08 00',
            'Label' => 'AF Zoom-Nikkor 35-70mm f/3.3-4.5',
        ),
        '02 46 37 37 25 25 02 00' => array(
            'Id' => '02 46 37 37 25 25 02 00',
            'Label' => 'Sigma 24mm F2.8 Super Wide II Macro',
        ),
        '02 46 3C 5C 25 25 02 00' => array(
            'Id' => '02 46 3C 5C 25 25 02 00',
            'Label' => 'Sigma 28-70mm F2.8',
        ),
        '02 46 5C 82 25 25 02 00' => array(
            'Id' => '02 46 5C 82 25 25 02 00',
            'Label' => 'Sigma 70-210mm F2.8 APO',
        ),
        '02 48 50 50 24 24 02 00' => array(
            'Id' => '02 48 50 50 24 24 02 00',
            'Label' => 'Sigma Macro 50mm F2.8',
        ),
        '02 48 65 65 24 24 02 00' => array(
            'Id' => '02 48 65 65 24 24 02 00',
            'Label' => 'Sigma Macro 90mm F2.8',
        ),
        '03 43 5C 81 35 35 02 00' => array(
            'Id' => '03 43 5C 81 35 35 02 00',
            'Label' => 'Soligor AF C/D Zoom UMCS 70-210mm 1:4.5',
        ),
        '03 48 5C 81 30 30 02 00' => array(
            'Id' => '03 48 5C 81 30 30 02 00',
            'Label' => 'AF Zoom-Nikkor 70-210mm f/4',
        ),
        '04 48 3C 3C 24 24 03 00' => array(
            'Id' => '04 48 3C 3C 24 24 03 00',
            'Label' => 'AF Nikkor 28mm f/2.8',
        ),
        '05 54 50 50 0C 0C 04 00' => array(
            'Id' => '05 54 50 50 0C 0C 04 00',
            'Label' => 'AF Nikkor 50mm f/1.4',
        ),
        '06 3F 68 68 2C 2C 06 00' => array(
            'Id' => '06 3F 68 68 2C 2C 06 00',
            'Label' => 'Cosina AF 100mm F3.5 Macro',
        ),
        '06 54 53 53 24 24 06 00' => array(
            'Id' => '06 54 53 53 24 24 06 00',
            'Label' => 'AF Micro-Nikkor 55mm f/2.8',
        ),
        '07 36 3D 5F 2C 3C 03 00' => array(
            'Id' => '07 36 3D 5F 2C 3C 03 00',
            'Label' => 'Cosina AF Zoom 28-80mm F3.5-5.6 MC Macro',
        ),
        '07 3E 30 43 2D 35 03 00' => array(
            'Id' => '07 3E 30 43 2D 35 03 00',
            'Label' => 'Soligor AF Zoom 19-35mm 1:3.5-4.5 MC',
        ),
        '07 40 2F 44 2C 34 03 02' => array(
            'Id' => '07 40 2F 44 2C 34 03 02',
            'Label' => 'Tamron AF 19-35mm f/3.5-4.5 (A10)',
        ),
        '07 40 30 45 2D 35 03 02' => array(
            'Id' => '07 40 30 45 2D 35 03 02',
            'Label' => 'Tamron AF 19-35mm f/3.5-4.5 (A10)',
        ),
        '07 40 3C 5C 2C 35 03 00' => array(
            'Id' => '07 40 3C 5C 2C 35 03 00',
            'Label' => 'Tokina AF 270 II (AF 28-70mm f/3.5-4.5)',
        ),
        '07 40 3C 62 2C 34 03 00' => array(
            'Id' => '07 40 3C 62 2C 34 03 00',
            'Label' => 'AF Zoom-Nikkor 28-85mm f/3.5-4.5',
        ),
        '07 46 2B 44 24 30 03 02' => array(
            'Id' => '07 46 2B 44 24 30 03 02',
            'Label' => 'Tamron SP AF 17-35mm f/2.8-4 Di LD Aspherical (IF) (A05)',
        ),
        '07 46 3D 6A 25 2F 03 00' => array(
            'Id' => '07 46 3D 6A 25 2F 03 00',
            'Label' => 'Cosina AF Zoom 28-105mm F2.8-3.8 MC',
        ),
        '07 47 3C 5C 25 35 03 00' => array(
            'Id' => '07 47 3C 5C 25 35 03 00',
            'Label' => 'Tokina AF 287 SD (AF 28-70mm f/2.8-4.5)',
        ),
        '07 48 3C 5C 24 24 03 00' => array(
            'Id' => '07 48 3C 5C 24 24 03 00',
            'Label' => 'Tokina AT-X 287 AF (AF 28-70mm f/2.8)',
        ),
        '08 40 44 6A 2C 34 04 00' => array(
            'Id' => '08 40 44 6A 2C 34 04 00',
            'Label' => 'AF Zoom-Nikkor 35-105mm f/3.5-4.5',
        ),
        '09 48 37 37 24 24 04 00' => array(
            'Id' => '09 48 37 37 24 24 04 00',
            'Label' => 'AF Nikkor 24mm f/2.8',
        ),
        '0A 48 8E 8E 24 24 03 00' => array(
            'Id' => '0A 48 8E 8E 24 24 03 00',
            'Label' => 'AF Nikkor 300mm f/2.8 IF-ED',
        ),
        '0A 48 8E 8E 24 24 05 00' => array(
            'Id' => '0A 48 8E 8E 24 24 05 00',
            'Label' => 'AF Nikkor 300mm f/2.8 IF-ED N',
        ),
        '0B 3E 3D 7F 2F 3D 0E 00' => array(
            'Id' => '0B 3E 3D 7F 2F 3D 0E 00',
            'Label' => 'Tamron AF 28-200mm f/3.8-5.6 (71D)',
        ),
        '0B 3E 3D 7F 2F 3D 0E 02' => array(
            'Id' => '0B 3E 3D 7F 2F 3D 0E 02',
            'Label' => 'Tamron AF 28-200mm f/3.8-5.6D (171D)',
        ),
        '0B 48 7C 7C 24 24 05 00' => array(
            'Id' => '0B 48 7C 7C 24 24 05 00',
            'Label' => 'AF Nikkor 180mm f/2.8 IF-ED',
        ),
        '0D 40 44 72 2C 34 07 00' => array(
            'Id' => '0D 40 44 72 2C 34 07 00',
            'Label' => 'AF Zoom-Nikkor 35-135mm f/3.5-4.5',
        ),
        '0E 48 5C 81 30 30 05 00' => array(
            'Id' => '0E 48 5C 81 30 30 05 00',
            'Label' => 'AF Zoom-Nikkor 70-210mm f/4',
        ),
        '0E 4A 31 48 23 2D 0E 02' => array(
            'Id' => '0E 4A 31 48 23 2D 0E 02',
            'Label' => 'Tamron SP AF 20-40mm f/2.7-3.5 (166D)',
        ),
        '0F 58 50 50 14 14 05 00' => array(
            'Id' => '0F 58 50 50 14 14 05 00',
            'Label' => 'AF Nikkor 50mm f/1.8 N',
        ),
        '10 3D 3C 60 2C 3C D2 02' => array(
            'Id' => '10 3D 3C 60 2C 3C D2 02',
            'Label' => 'Tamron AF 28-80mm f/3.5-5.6 Aspherical (177D)',
        ),
        '10 48 8E 8E 30 30 08 00' => array(
            'Id' => '10 48 8E 8E 30 30 08 00',
            'Label' => 'AF Nikkor 300mm f/4 IF-ED',
        ),
        '11 48 44 5C 24 24 08 00' => array(
            'Id' => '11 48 44 5C 24 24 08 00',
            'Label' => 'AF Zoom-Nikkor 35-70mm f/2.8',
        ),
        '12 36 5C 81 35 3D 09 00' => array(
            'Id' => '12 36 5C 81 35 3D 09 00',
            'Label' => 'Cosina AF Zoom 70-210mm F4.5-5.6 MC Macro',
        ),
        '12 36 69 97 35 42 09 00' => array(
            'Id' => '12 36 69 97 35 42 09 00',
            'Label' => 'Soligor AF Zoom 100-400mm 1:4.5-6.7 MC',
        ),
        '12 38 69 97 35 42 09 02' => array(
            'Id' => '12 38 69 97 35 42 09 02',
            'Label' => 'Promaster Spectrum 7 100-400mm F4.5-6.7',
        ),
        '12 39 5C 8E 34 3D 08 02' => array(
            'Id' => '12 39 5C 8E 34 3D 08 02',
            'Label' => 'Cosina AF Zoom 70-300mm F4.5-5.6 MC Macro',
        ),
        '12 3B 68 8D 3D 43 09 02' => array(
            'Id' => '12 3B 68 8D 3D 43 09 02',
            'Label' => 'Cosina AF Zoom 100-300mm F5.6-6.7 MC Macro',
        ),
        '12 3B 98 98 3D 3D 09 00' => array(
            'Id' => '12 3B 98 98 3D 3D 09 00',
            'Label' => 'Tokina AT-X 400 AF SD (AF 400mm f/5.6)',
        ),
        '12 3D 3C 80 2E 3C DF 02' => array(
            'Id' => '12 3D 3C 80 2E 3C DF 02',
            'Label' => 'Tamron AF 28-200mm f/3.8-5.6 AF Aspherical LD (IF) (271D)',
        ),
        '12 44 5E 8E 34 3C 09 00' => array(
            'Id' => '12 44 5E 8E 34 3C 09 00',
            'Label' => 'Tokina AF 730 (AF 75-300mm F4.5-5.6)',
        ),
        '12 48 5C 81 30 3C 09 00' => array(
            'Id' => '12 48 5C 81 30 3C 09 00',
            'Label' => 'AF Nikkor 70-210mm f/4-5.6',
        ),
        '12 4A 5C 81 31 3D 09 00' => array(
            'Id' => '12 4A 5C 81 31 3D 09 00',
            'Label' => 'Soligor AF C/D Auto Zoom+Macro 70-210mm 1:4-5.6 UMCS',
        ),
        '13 42 37 50 2A 34 0B 00' => array(
            'Id' => '13 42 37 50 2A 34 0B 00',
            'Label' => 'AF Zoom-Nikkor 24-50mm f/3.3-4.5',
        ),
        '14 48 60 80 24 24 0B 00' => array(
            'Id' => '14 48 60 80 24 24 0B 00',
            'Label' => 'AF Zoom-Nikkor 80-200mm f/2.8 ED',
        ),
        '14 48 68 8E 30 30 0B 00' => array(
            'Id' => '14 48 68 8E 30 30 0B 00',
            'Label' => 'Tokina AT-X 340 AF (AF 100-300mm f/4)',
        ),
        '14 54 60 80 24 24 0B 00' => array(
            'Id' => '14 54 60 80 24 24 0B 00',
            'Label' => 'Tokina AT-X 828 AF (AF 80-200mm f/2.8)',
        ),
        '15 4C 62 62 14 14 0C 00' => array(
            'Id' => '15 4C 62 62 14 14 0C 00',
            'Label' => 'AF Nikkor 85mm f/1.8',
        ),
        '17 3C A0 A0 30 30 0F 00' => array(
            'Id' => '17 3C A0 A0 30 30 0F 00',
            'Label' => 'Nikkor 500mm f/4 P ED IF',
        ),
        '17 3C A0 A0 30 30 11 00' => array(
            'Id' => '17 3C A0 A0 30 30 11 00',
            'Label' => 'Nikkor 500mm f/4 P ED IF',
        ),
        '18 40 44 72 2C 34 0E 00' => array(
            'Id' => '18 40 44 72 2C 34 0E 00',
            'Label' => 'AF Zoom-Nikkor 35-135mm f/3.5-4.5 N',
        ),
        '1A 54 44 44 18 18 11 00' => array(
            'Id' => '1A 54 44 44 18 18 11 00',
            'Label' => 'AF Nikkor 35mm f/2',
        ),
        '1B 44 5E 8E 34 3C 10 00' => array(
            'Id' => '1B 44 5E 8E 34 3C 10 00',
            'Label' => 'AF Zoom-Nikkor 75-300mm f/4.5-5.6',
        ),
        '1C 48 30 30 24 24 12 00' => array(
            'Id' => '1C 48 30 30 24 24 12 00',
            'Label' => 'AF Nikkor 20mm f/2.8',
        ),
        '1D 42 44 5C 2A 34 12 00' => array(
            'Id' => '1D 42 44 5C 2A 34 12 00',
            'Label' => 'AF Zoom-Nikkor 35-70mm f/3.3-4.5 N',
        ),
        '1E 54 56 56 24 24 13 00' => array(
            'Id' => '1E 54 56 56 24 24 13 00',
            'Label' => 'AF Micro-Nikkor 60mm f/2.8',
        ),
        '1E 5D 64 64 20 20 13 00' => array(
            'Id' => '1E 5D 64 64 20 20 13 00',
            'Label' => 'Tamron SP AF 90mm f/2.5 (52E)',
        ),
        '1F 54 6A 6A 24 24 14 00' => array(
            'Id' => '1F 54 6A 6A 24 24 14 00',
            'Label' => 'AF Micro-Nikkor 105mm f/2.8',
        ),
        '20 3C 80 98 3D 3D 1E 02' => array(
            'Id' => '20 3C 80 98 3D 3D 1E 02',
            'Label' => 'Tamron AF 200-400mm f/5.6 LD IF (75D)',
        ),
        '20 48 60 80 24 24 15 00' => array(
            'Id' => '20 48 60 80 24 24 15 00',
            'Label' => 'AF Zoom-Nikkor 80-200mm f/2.8 ED',
        ),
        '20 5A 64 64 20 20 14 00' => array(
            'Id' => '20 5A 64 64 20 20 14 00',
            'Label' => 'Tamron SP AF 90mm f/2.5 Macro (152E)',
        ),
        '21 40 3C 5C 2C 34 16 00' => array(
            'Id' => '21 40 3C 5C 2C 34 16 00',
            'Label' => 'AF Zoom-Nikkor 28-70mm f/3.5-4.5',
        ),
        '21 56 8E 8E 24 24 14 00' => array(
            'Id' => '21 56 8E 8E 24 24 14 00',
            'Label' => 'Tamron SP AF 300mm f/2.8 LD-IF (60E)',
        ),
        '22 48 72 72 18 18 16 00' => array(
            'Id' => '22 48 72 72 18 18 16 00',
            'Label' => 'AF DC-Nikkor 135mm f/2',
        ),
        '22 53 64 64 24 24 E0 02' => array(
            'Id' => '22 53 64 64 24 24 E0 02',
            'Label' => 'Tamron SP AF 90mm f/2.8 Macro 1:1 (72E)',
        ),
        '23 30 BE CA 3C 48 17 00' => array(
            'Id' => '23 30 BE CA 3C 48 17 00',
            'Label' => 'Zoom-Nikkor 1200-1700mm f/5.6-8 P ED IF',
        ),
        '24 44 60 98 34 3C 1A 02' => array(
            'Id' => '24 44 60 98 34 3C 1A 02',
            'Label' => 'Tokina AT-X 840 AF-II (AF 80-400mm f/4.5-5.6)',
        ),
        '24 48 60 80 24 24 1A 02' => array(
            'Id' => '24 48 60 80 24 24 1A 02',
            'Label' => 'AF Zoom-Nikkor 80-200mm f/2.8D ED',
        ),
        '24 54 60 80 24 24 1A 02' => array(
            'Id' => '24 54 60 80 24 24 1A 02',
            'Label' => 'Tokina AT-X 828 AF PRO (AF 80-200mm f/2.8)',
        ),
        '25 44 44 8E 34 42 1B 02' => array(
            'Id' => '25 44 44 8E 34 42 1B 02',
            'Label' => 'Tokina AF 353 (AF 35-300mm f/4.5-6.7)',
        ),
        '25 48 3C 5C 24 24 1B 02.1' => array(
            'Id' => '25 48 3C 5C 24 24 1B 02.1',
            'Label' => 'Tokina AT-X 270 AF PRO II (AF 28-70mm f/2.6-2.8)',
        ),
        '25 48 3C 5C 24 24 1B 02.2' => array(
            'Id' => '25 48 3C 5C 24 24 1B 02.2',
            'Label' => 'Tokina AT-X 287 AF PRO SV (AF 28-70mm f/2.8)',
        ),
        '25 48 44 5C 24 24 1B 02' => array(
            'Id' => '25 48 44 5C 24 24 1B 02',
            'Label' => 'AF Zoom-Nikkor 35-70mm f/2.8D',
        ),
        '25 48 44 5C 24 24 3A 02' => array(
            'Id' => '25 48 44 5C 24 24 3A 02',
            'Label' => 'AF Zoom-Nikkor 35-70mm f/2.8D',
        ),
        '25 48 44 5C 24 24 52 02' => array(
            'Id' => '25 48 44 5C 24 24 52 02',
            'Label' => 'AF Zoom-Nikkor 35-70mm f/2.8D',
        ),
        '26 3C 54 80 30 3C 1C 06' => array(
            'Id' => '26 3C 54 80 30 3C 1C 06',
            'Label' => 'Sigma 55-200mm F4-5.6 DC',
        ),
        '26 3C 5C 82 30 3C 1C 02' => array(
            'Id' => '26 3C 5C 82 30 3C 1C 02',
            'Label' => 'Sigma 70-210mm F4-5.6 UC-II',
        ),
        '26 3C 5C 8E 30 3C 1C 02' => array(
            'Id' => '26 3C 5C 8E 30 3C 1C 02',
            'Label' => 'Sigma 70-300mm F4-5.6 DG Macro',
        ),
        '26 3C 98 98 3C 3C 1C 02' => array(
            'Id' => '26 3C 98 98 3C 3C 1C 02',
            'Label' => 'Sigma APO Tele Macro 400mm F5.6',
        ),
        '26 3D 3C 80 2F 3D 1C 02' => array(
            'Id' => '26 3D 3C 80 2F 3D 1C 02',
            'Label' => 'Sigma 28-300mm F3.8-5.6 Aspherical',
        ),
        '26 3E 3C 6A 2E 3C 1C 02' => array(
            'Id' => '26 3E 3C 6A 2E 3C 1C 02',
            'Label' => 'Sigma 28-105mm F3.8-5.6 UC-III Aspherical IF',
        ),
        '26 40 27 3F 2C 34 1C 02' => array(
            'Id' => '26 40 27 3F 2C 34 1C 02',
            'Label' => 'Sigma 15-30mm F3.5-4.5 EX DG Aspherical DF',
        ),
        '26 40 2D 44 2B 34 1C 02' => array(
            'Id' => '26 40 2D 44 2B 34 1C 02',
            'Label' => 'Sigma 18-35mm F3.5-4.5 Aspherical',
        ),
        '26 40 2D 50 2C 3C 1C 06' => array(
            'Id' => '26 40 2D 50 2C 3C 1C 06',
            'Label' => 'Sigma 18-50mm F3.5-5.6 DC',
        ),
        '26 40 2D 70 2B 3C 1C 06' => array(
            'Id' => '26 40 2D 70 2B 3C 1C 06',
            'Label' => 'Sigma 18-125mm F3.5-5.6 DC',
        ),
        '26 40 2D 80 2C 40 1C 06' => array(
            'Id' => '26 40 2D 80 2C 40 1C 06',
            'Label' => 'Sigma 18-200mm F3.5-6.3 DC',
        ),
        '26 40 37 5C 2C 3C 1C 02' => array(
            'Id' => '26 40 37 5C 2C 3C 1C 02',
            'Label' => 'Sigma 24-70mm F3.5-5.6 Aspherical HF',
        ),
        '26 40 3C 5C 2C 34 1C 02' => array(
            'Id' => '26 40 3C 5C 2C 34 1C 02',
            'Label' => 'AF Zoom-Nikkor 28-70mm f/3.5-4.5D',
        ),
        '26 40 3C 60 2C 3C 1C 02' => array(
            'Id' => '26 40 3C 60 2C 3C 1C 02',
            'Label' => 'Sigma 28-80mm F3.5-5.6 Mini Zoom Macro II Aspherical',
        ),
        '26 40 3C 65 2C 3C 1C 02' => array(
            'Id' => '26 40 3C 65 2C 3C 1C 02',
            'Label' => 'Sigma 28-90mm F3.5-5.6 Macro',
        ),
        '26 40 3C 80 2B 3C 1C 02' => array(
            'Id' => '26 40 3C 80 2B 3C 1C 02',
            'Label' => 'Sigma 28-200mm F3.5-5.6 Compact Aspherical Hyperzoom Macro',
        ),
        '26 40 3C 80 2C 3C 1C 02' => array(
            'Id' => '26 40 3C 80 2C 3C 1C 02',
            'Label' => 'Sigma 28-200mm F3.5-5.6 Compact Aspherical Hyperzoom Macro',
        ),
        '26 40 3C 8E 2C 40 1C 02' => array(
            'Id' => '26 40 3C 8E 2C 40 1C 02',
            'Label' => 'Sigma 28-300mm F3.5-6.3 Macro',
        ),
        '26 40 7B A0 34 40 1C 02' => array(
            'Id' => '26 40 7B A0 34 40 1C 02',
            'Label' => 'Sigma APO 170-500mm F5-6.3 Aspherical RF',
        ),
        '26 41 3C 8E 2C 40 1C 02' => array(
            'Id' => '26 41 3C 8E 2C 40 1C 02',
            'Label' => 'Sigma 28-300mm F3.5-6.3 DG Macro',
        ),
        '26 44 73 98 34 3C 1C 02' => array(
            'Id' => '26 44 73 98 34 3C 1C 02',
            'Label' => 'Sigma 135-400mm F4.5-5.6 APO Aspherical',
        ),
        '26 48 11 11 30 30 1C 02' => array(
            'Id' => '26 48 11 11 30 30 1C 02',
            'Label' => 'Sigma 8mm F4 EX Circular Fisheye',
        ),
        '26 48 27 27 24 24 1C 02' => array(
            'Id' => '26 48 27 27 24 24 1C 02',
            'Label' => 'Sigma 15mm F2.8 EX Diagonal Fisheye',
        ),
        '26 48 2D 50 24 24 1C 06' => array(
            'Id' => '26 48 2D 50 24 24 1C 06',
            'Label' => 'Sigma 18-50mm F2.8 EX DC',
        ),
        '26 48 31 49 24 24 1C 02' => array(
            'Id' => '26 48 31 49 24 24 1C 02',
            'Label' => 'Sigma 20-40mm F2.8',
        ),
        '26 48 37 56 24 24 1C 02' => array(
            'Id' => '26 48 37 56 24 24 1C 02',
            'Label' => 'Sigma 24-60mm F2.8 EX DG',
        ),
        '26 48 3C 5C 24 24 1C 06' => array(
            'Id' => '26 48 3C 5C 24 24 1C 06',
            'Label' => 'Sigma 28-70mm F2.8 EX DG',
        ),
        '26 48 3C 5C 24 30 1C 02' => array(
            'Id' => '26 48 3C 5C 24 30 1C 02',
            'Label' => 'Sigma 28-70mm F2.8-4 DG',
        ),
        '26 48 3C 6A 24 30 1C 02' => array(
            'Id' => '26 48 3C 6A 24 30 1C 02',
            'Label' => 'Sigma 28-105mm F2.8-4 Aspherical',
        ),
        '26 48 8E 8E 30 30 1C 02' => array(
            'Id' => '26 48 8E 8E 30 30 1C 02',
            'Label' => 'Sigma APO Tele Macro 300mm F4',
        ),
        '26 54 2B 44 24 30 1C 02' => array(
            'Id' => '26 54 2B 44 24 30 1C 02',
            'Label' => 'Sigma 17-35mm F2.8-4 EX Aspherical',
        ),
        '26 54 37 5C 24 24 1C 02' => array(
            'Id' => '26 54 37 5C 24 24 1C 02',
            'Label' => 'Sigma 24-70mm F2.8 EX DG Macro',
        ),
        '26 54 37 73 24 34 1C 02' => array(
            'Id' => '26 54 37 73 24 34 1C 02',
            'Label' => 'Sigma 24-135mm F2.8-4.5',
        ),
        '26 54 3C 5C 24 24 1C 02' => array(
            'Id' => '26 54 3C 5C 24 24 1C 02',
            'Label' => 'Sigma 28-70mm F2.8 EX',
        ),
        '26 58 31 31 14 14 1C 02' => array(
            'Id' => '26 58 31 31 14 14 1C 02',
            'Label' => 'Sigma 20mm F1.8 EX DG Aspherical RF',
        ),
        '26 58 37 37 14 14 1C 02' => array(
            'Id' => '26 58 37 37 14 14 1C 02',
            'Label' => 'Sigma 24mm F1.8 EX DG Aspherical Macro',
        ),
        '26 58 3C 3C 14 14 1C 02' => array(
            'Id' => '26 58 3C 3C 14 14 1C 02',
            'Label' => 'Sigma 28mm F1.8 EX DG Aspherical Macro',
        ),
        '27 48 8E 8E 24 24 1D 02' => array(
            'Id' => '27 48 8E 8E 24 24 1D 02',
            'Label' => 'AF-I Nikkor 300mm f/2.8D IF-ED',
        ),
        '27 48 8E 8E 24 24 E1 02' => array(
            'Id' => '27 48 8E 8E 24 24 E1 02',
            'Label' => 'AF-I Nikkor 300mm f/2.8D IF-ED + TC-17E',
        ),
        '27 48 8E 8E 24 24 F1 02' => array(
            'Id' => '27 48 8E 8E 24 24 F1 02',
            'Label' => 'AF-I Nikkor 300mm f/2.8D IF-ED + TC-14E',
        ),
        '27 48 8E 8E 24 24 F2 02' => array(
            'Id' => '27 48 8E 8E 24 24 F2 02',
            'Label' => 'AF-I Nikkor 300mm f/2.8D IF-ED + TC-20E',
        ),
        '27 48 8E 8E 30 30 1D 02' => array(
            'Id' => '27 48 8E 8E 30 30 1D 02',
            'Label' => 'Tokina AT-X 304 AF (AF 300mm f/4.0)',
        ),
        '27 54 8E 8E 24 24 1D 02' => array(
            'Id' => '27 54 8E 8E 24 24 1D 02',
            'Label' => 'Tamron SP AF 300mm f/2.8 LD-IF (360E)',
        ),
        '28 3C A6 A6 30 30 1D 02' => array(
            'Id' => '28 3C A6 A6 30 30 1D 02',
            'Label' => 'AF-I Nikkor 600mm f/4D IF-ED',
        ),
        '28 3C A6 A6 30 30 E1 02' => array(
            'Id' => '28 3C A6 A6 30 30 E1 02',
            'Label' => 'AF-I Nikkor 600mm f/4D IF-ED + TC-17E',
        ),
        '28 3C A6 A6 30 30 F1 02' => array(
            'Id' => '28 3C A6 A6 30 30 F1 02',
            'Label' => 'AF-I Nikkor 600mm f/4D IF-ED + TC-14E',
        ),
        '28 3C A6 A6 30 30 F2 02' => array(
            'Id' => '28 3C A6 A6 30 30 F2 02',
            'Label' => 'AF-I Nikkor 600mm f/4D IF-ED + TC-20E',
        ),
        '2A 54 3C 3C 0C 0C 26 02' => array(
            'Id' => '2A 54 3C 3C 0C 0C 26 02',
            'Label' => 'AF Nikkor 28mm f/1.4D',
        ),
        '2B 3C 44 60 30 3C 1F 02' => array(
            'Id' => '2B 3C 44 60 30 3C 1F 02',
            'Label' => 'AF Zoom-Nikkor 35-80mm f/4-5.6D',
        ),
        '2C 48 6A 6A 18 18 27 02' => array(
            'Id' => '2C 48 6A 6A 18 18 27 02',
            'Label' => 'AF DC-Nikkor 105mm f/2D',
        ),
        '2D 48 80 80 30 30 21 02' => array(
            'Id' => '2D 48 80 80 30 30 21 02',
            'Label' => 'AF Micro-Nikkor 200mm f/4D IF-ED',
        ),
        '2E 48 5C 82 30 3C 22 02' => array(
            'Id' => '2E 48 5C 82 30 3C 22 02',
            'Label' => 'AF Nikkor 70-210mm f/4-5.6D',
        ),
        '2E 48 5C 82 30 3C 28 02' => array(
            'Id' => '2E 48 5C 82 30 3C 28 02',
            'Label' => 'AF Nikkor 70-210mm f/4-5.6D',
        ),
        '2F 40 30 44 2C 34 29 02.1' => array(
            'Id' => '2F 40 30 44 2C 34 29 02.1',
            'Label' => 'Tokina AF 235 II (AF 20-35mm f/3.5-4.5)',
        ),
        '2F 40 30 44 2C 34 29 02.2' => array(
            'Id' => '2F 40 30 44 2C 34 29 02.2',
            'Label' => 'Tokina AF 193 (AF 19-35mm f/3.5-4.5)',
        ),
        '2F 48 30 44 24 24 29 02.1' => array(
            'Id' => '2F 48 30 44 24 24 29 02.1',
            'Label' => 'AF Zoom-Nikkor 20-35mm f/2.8D IF',
        ),
        '2F 48 30 44 24 24 29 02.2' => array(
            'Id' => '2F 48 30 44 24 24 29 02.2',
            'Label' => 'Tokina AT-X 235 AF PRO (AF 20-35mm f/2.8)',
        ),
        '30 48 98 98 24 24 24 02' => array(
            'Id' => '30 48 98 98 24 24 24 02',
            'Label' => 'AF-I Nikkor 400mm f/2.8D IF-ED',
        ),
        '30 48 98 98 24 24 E1 02' => array(
            'Id' => '30 48 98 98 24 24 E1 02',
            'Label' => 'AF-I Nikkor 400mm f/2.8D IF-ED + TC-17E',
        ),
        '30 48 98 98 24 24 F1 02' => array(
            'Id' => '30 48 98 98 24 24 F1 02',
            'Label' => 'AF-I Nikkor 400mm f/2.8D IF-ED + TC-14E',
        ),
        '30 48 98 98 24 24 F2 02' => array(
            'Id' => '30 48 98 98 24 24 F2 02',
            'Label' => 'AF-I Nikkor 400mm f/2.8D IF-ED + TC-20E',
        ),
        '31 54 56 56 24 24 25 02' => array(
            'Id' => '31 54 56 56 24 24 25 02',
            'Label' => 'AF Micro-Nikkor 60mm f/2.8D',
        ),
        '32 53 64 64 24 24 35 02' => array(
            'Id' => '32 53 64 64 24 24 35 02',
            'Label' => 'Tamron SP AF 90mm f/2.8 [Di] Macro 1:1 (172E/272E)',
        ),
        '32 54 50 50 24 24 35 02' => array(
            'Id' => '32 54 50 50 24 24 35 02',
            'Label' => 'Sigma Macro 50mm F2.8 EX DG',
        ),
        '32 54 6A 6A 24 24 35 02.1' => array(
            'Id' => '32 54 6A 6A 24 24 35 02.1',
            'Label' => 'AF Micro-Nikkor 105mm f/2.8D',
        ),
        '32 54 6A 6A 24 24 35 02.2' => array(
            'Id' => '32 54 6A 6A 24 24 35 02.2',
            'Label' => 'Sigma Macro 105mm F2.8 EX DG',
        ),
        '33 48 2D 2D 24 24 31 02' => array(
            'Id' => '33 48 2D 2D 24 24 31 02',
            'Label' => 'AF Nikkor 18mm f/2.8D',
        ),
        '33 54 3C 5E 24 24 62 02' => array(
            'Id' => '33 54 3C 5E 24 24 62 02',
            'Label' => 'Tamron SP AF 28-75mm f/2.8 XR Di LD Aspherical (IF) Macro (A09)',
        ),
        '34 48 29 29 24 24 32 02' => array(
            'Id' => '34 48 29 29 24 24 32 02',
            'Label' => 'AF Fisheye Nikkor 16mm f/2.8D',
        ),
        '35 3C A0 A0 30 30 33 02' => array(
            'Id' => '35 3C A0 A0 30 30 33 02',
            'Label' => 'AF-I Nikkor 500mm f/4D IF-ED',
        ),
        '35 3C A0 A0 30 30 E1 02' => array(
            'Id' => '35 3C A0 A0 30 30 E1 02',
            'Label' => 'AF-I Nikkor 500mm f/4D IF-ED + TC-17E',
        ),
        '35 3C A0 A0 30 30 F1 02' => array(
            'Id' => '35 3C A0 A0 30 30 F1 02',
            'Label' => 'AF-I Nikkor 500mm f/4D IF-ED + TC-14E',
        ),
        '35 3C A0 A0 30 30 F2 02' => array(
            'Id' => '35 3C A0 A0 30 30 F2 02',
            'Label' => 'AF-I Nikkor 500mm f/4D IF-ED + TC-20E',
        ),
        '36 48 37 37 24 24 34 02' => array(
            'Id' => '36 48 37 37 24 24 34 02',
            'Label' => 'AF Nikkor 24mm f/2.8D',
        ),
        '37 48 30 30 24 24 36 02' => array(
            'Id' => '37 48 30 30 24 24 36 02',
            'Label' => 'AF Nikkor 20mm f/2.8D',
        ),
        '38 4C 62 62 14 14 37 02' => array(
            'Id' => '38 4C 62 62 14 14 37 02',
            'Label' => 'AF Nikkor 85mm f/1.8D',
        ),
        '3A 40 3C 5C 2C 34 39 02' => array(
            'Id' => '3A 40 3C 5C 2C 34 39 02',
            'Label' => 'AF Zoom-Nikkor 28-70mm f/3.5-4.5D',
        ),
        '3B 48 44 5C 24 24 3A 02' => array(
            'Id' => '3B 48 44 5C 24 24 3A 02',
            'Label' => 'AF Zoom-Nikkor 35-70mm f/2.8D N',
        ),
        '3C 48 60 80 24 24 3B 02' => array(
            'Id' => '3C 48 60 80 24 24 3B 02',
            'Label' => 'AF Zoom-Nikkor 80-200mm f/2.8D ED',
        ),
        '3D 3C 44 60 30 3C 3E 02' => array(
            'Id' => '3D 3C 44 60 30 3C 3E 02',
            'Label' => 'AF Zoom-Nikkor 35-80mm f/4-5.6D',
        ),
        '3E 48 3C 3C 24 24 3D 02' => array(
            'Id' => '3E 48 3C 3C 24 24 3D 02',
            'Label' => 'AF Nikkor 28mm f/2.8D',
        ),
        '3F 40 44 6A 2C 34 45 02' => array(
            'Id' => '3F 40 44 6A 2C 34 45 02',
            'Label' => 'AF Zoom-Nikkor 35-105mm f/3.5-4.5D',
        ),
        '41 48 7C 7C 24 24 43 02' => array(
            'Id' => '41 48 7C 7C 24 24 43 02',
            'Label' => 'AF Nikkor 180mm f/2.8D IF-ED',
        ),
        '42 54 44 44 18 18 44 02' => array(
            'Id' => '42 54 44 44 18 18 44 02',
            'Label' => 'AF Nikkor 35mm f/2D',
        ),
        '43 54 50 50 0C 0C 46 02' => array(
            'Id' => '43 54 50 50 0C 0C 46 02',
            'Label' => 'AF Nikkor 50mm f/1.4D',
        ),
        '44 44 60 80 34 3C 47 02' => array(
            'Id' => '44 44 60 80 34 3C 47 02',
            'Label' => 'AF Zoom-Nikkor 80-200mm f/4.5-5.6D',
        ),
        '45 3D 3C 60 2C 3C 48 02' => array(
            'Id' => '45 3D 3C 60 2C 3C 48 02',
            'Label' => 'Tamron AF 28-80mm f/3.5-5.6 Aspherical (177D)',
        ),
        '45 40 3C 60 2C 3C 48 02' => array(
            'Id' => '45 40 3C 60 2C 3C 48 02',
            'Label' => 'AF Zoom-Nikkor 28-80mm f/3.5-5.6D',
        ),
        '45 41 37 72 2C 3C 48 02' => array(
            'Id' => '45 41 37 72 2C 3C 48 02',
            'Label' => 'Tamron SP AF 24-135mm f/3.5-5.6 AD Aspherical (IF) Macro (190D)',
        ),
        '46 3C 44 60 30 3C 49 02' => array(
            'Id' => '46 3C 44 60 30 3C 49 02',
            'Label' => 'AF Zoom-Nikkor 35-80mm f/4-5.6D N',
        ),
        '47 42 37 50 2A 34 4A 02' => array(
            'Id' => '47 42 37 50 2A 34 4A 02',
            'Label' => 'AF Zoom-Nikkor 24-50mm f/3.3-4.5D',
        ),
        '48 38 1F 37 34 3C 4B 06' => array(
            'Id' => '48 38 1F 37 34 3C 4B 06',
            'Label' => 'Sigma 12-24mm F4.5-5.6 EX DG Aspherical HSM',
        ),
        '48 3C 19 31 30 3C 4B 06' => array(
            'Id' => '48 3C 19 31 30 3C 4B 06',
            'Label' => 'Sigma 10-20mm F4-5.6 EX DC HSM',
        ),
        '48 3C 50 A0 30 40 4B 02' => array(
            'Id' => '48 3C 50 A0 30 40 4B 02',
            'Label' => 'Sigma 50-500mm F4-6.3 EX APO RF HSM',
        ),
        '48 3C 8E B0 3C 3C 4B 02' => array(
            'Id' => '48 3C 8E B0 3C 3C 4B 02',
            'Label' => 'Sigma APO 300-800mm F5.6 EX DG HSM',
        ),
        '48 3C B0 B0 3C 3C 4B 02' => array(
            'Id' => '48 3C B0 B0 3C 3C 4B 02',
            'Label' => 'Sigma APO 800mm F5.6 EX HSM',
        ),
        '48 44 A0 A0 34 34 4B 02' => array(
            'Id' => '48 44 A0 A0 34 34 4B 02',
            'Label' => 'Sigma APO 500mm F4.5 EX HSM',
        ),
        '48 48 24 24 24 24 4B 02' => array(
            'Id' => '48 48 24 24 24 24 4B 02',
            'Label' => 'Sigma 14mm F2.8 EX Aspherical HSM',
        ),
        '48 48 2B 44 24 30 4B 06' => array(
            'Id' => '48 48 2B 44 24 30 4B 06',
            'Label' => 'Sigma 17-35mm F2.8-4 EX DG  Aspherical HSM',
        ),
        '48 48 68 8E 30 30 4B 02' => array(
            'Id' => '48 48 68 8E 30 30 4B 02',
            'Label' => 'Sigma APO 100-300mm F4 EX IF HSM',
        ),
        '48 48 76 76 24 24 4B 06' => array(
            'Id' => '48 48 76 76 24 24 4B 06',
            'Label' => 'Sigma APO Macro 150mm F2.8 EX DG HSM',
        ),
        '48 48 8E 8E 24 24 4B 02' => array(
            'Id' => '48 48 8E 8E 24 24 4B 02',
            'Label' => 'AF-S Nikkor 300mm f/2.8D IF-ED',
        ),
        '48 48 8E 8E 24 24 E1 02' => array(
            'Id' => '48 48 8E 8E 24 24 E1 02',
            'Label' => 'AF-S Nikkor 300mm f/2.8D IF-ED + TC-17E',
        ),
        '48 48 8E 8E 24 24 F1 02' => array(
            'Id' => '48 48 8E 8E 24 24 F1 02',
            'Label' => 'AF-S Nikkor 300mm f/2.8D IF-ED + TC-14E',
        ),
        '48 48 8E 8E 24 24 F2 02' => array(
            'Id' => '48 48 8E 8E 24 24 F2 02',
            'Label' => 'AF-S Nikkor 300mm f/2.8D IF-ED + TC-20E',
        ),
        '48 4C 7C 7C 2C 2C 4B 02' => array(
            'Id' => '48 4C 7C 7C 2C 2C 4B 02',
            'Label' => 'Sigma APO Macro 180mm F3.5 EX DG HSM',
        ),
        '48 4C 7D 7D 2C 2C 4B 02' => array(
            'Id' => '48 4C 7D 7D 2C 2C 4B 02',
            'Label' => 'Sigma APO Macro 180mm F3.5 EX DG HSM',
        ),
        '48 54 3E 3E 0C 0C 4B 06' => array(
            'Id' => '48 54 3E 3E 0C 0C 4B 06',
            'Label' => 'Sigma 30mm F1.4 EX DC HSM',
        ),
        '48 54 5C 80 24 24 4B 02' => array(
            'Id' => '48 54 5C 80 24 24 4B 02',
            'Label' => 'Sigma 70-200mm F2.8 EX APO IF HSM',
        ),
        '48 54 6F 8E 24 24 4B 02' => array(
            'Id' => '48 54 6F 8E 24 24 4B 02',
            'Label' => 'Sigma APO 120-300mm F2.8 EX DG HSM',
        ),
        '48 54 8E 8E 24 24 4B 02' => array(
            'Id' => '48 54 8E 8E 24 24 4B 02',
            'Label' => 'Sigma APO 300mm F2.8 EX DG HSM',
        ),
        '49 3C A6 A6 30 30 4C 02' => array(
            'Id' => '49 3C A6 A6 30 30 4C 02',
            'Label' => 'AF-S Nikkor 600mm f/4D IF-ED',
        ),
        '49 3C A6 A6 30 30 E1 02' => array(
            'Id' => '49 3C A6 A6 30 30 E1 02',
            'Label' => 'AF-S Nikkor 600mm f/4D IF-ED + TC-17E',
        ),
        '49 3C A6 A6 30 30 F1 02' => array(
            'Id' => '49 3C A6 A6 30 30 F1 02',
            'Label' => 'AF-S Nikkor 600mm f/4D IF-ED + TC-14E',
        ),
        '49 3C A6 A6 30 30 F2 02' => array(
            'Id' => '49 3C A6 A6 30 30 F2 02',
            'Label' => 'AF-S Nikkor 600mm f/4D IF-ED + TC-20E',
        ),
        '4A 40 11 11 2C 0C 4D 02' => array(
            'Id' => '4A 40 11 11 2C 0C 4D 02',
            'Label' => 'Samyang 8mm f/3.5 Fish-Eye CS',
        ),
        '4A 48 1E 1E 24 0C 4D 02' => array(
            'Id' => '4A 48 1E 1E 24 0C 4D 02',
            'Label' => 'Samyang 12mm f/2.8 ED AS NCS Fish-Eye',
        ),
        '4A 48 24 24 24 0C 4D 02' => array(
            'Id' => '4A 48 24 24 24 0C 4D 02',
            'Label' => 'Samyang AE 14mm f/2.8 ED AS IF UMC',
        ),
        '4A 54 29 29 18 0C 4D 02' => array(
            'Id' => '4A 54 29 29 18 0C 4D 02',
            'Label' => 'Samyang 16mm F2.0 ED AS UMC CS',
        ),
        '4A 54 62 62 0C 0C 4D 02' => array(
            'Id' => '4A 54 62 62 0C 0C 4D 02',
            'Label' => 'AF Nikkor 85mm f/1.4D IF',
        ),
        '4A 60 44 44 0C 0C 4D 02' => array(
            'Id' => '4A 60 44 44 0C 0C 4D 02',
            'Label' => 'Samyang 35mm f/1.4 AS UMC',
        ),
        '4A 60 62 62 0C 0C 4D 02' => array(
            'Id' => '4A 60 62 62 0C 0C 4D 02',
            'Label' => 'Samyang AE 85mm f/1.4 AS IF UMC',
        ),
        '4B 3C A0 A0 30 30 4E 02' => array(
            'Id' => '4B 3C A0 A0 30 30 4E 02',
            'Label' => 'AF-S Nikkor 500mm f/4D IF-ED',
        ),
        '4B 3C A0 A0 30 30 E1 02' => array(
            'Id' => '4B 3C A0 A0 30 30 E1 02',
            'Label' => 'AF-S Nikkor 500mm f/4D IF-ED + TC-17E',
        ),
        '4B 3C A0 A0 30 30 F1 02' => array(
            'Id' => '4B 3C A0 A0 30 30 F1 02',
            'Label' => 'AF-S Nikkor 500mm f/4D IF-ED + TC-14E',
        ),
        '4B 3C A0 A0 30 30 F2 02' => array(
            'Id' => '4B 3C A0 A0 30 30 F2 02',
            'Label' => 'AF-S Nikkor 500mm f/4D IF-ED + TC-20E',
        ),
        '4C 40 37 6E 2C 3C 4F 02' => array(
            'Id' => '4C 40 37 6E 2C 3C 4F 02',
            'Label' => 'AF Zoom-Nikkor 24-120mm f/3.5-5.6D IF',
        ),
        '4D 3E 3C 80 2E 3C 62 02' => array(
            'Id' => '4D 3E 3C 80 2E 3C 62 02',
            'Label' => 'Tamron AF 28-200mm F/3.8-5.6 XR Aspherical (IF) Macro (A03N)',
        ),
        '4D 40 3C 80 2C 3C 62 02' => array(
            'Id' => '4D 40 3C 80 2C 3C 62 02',
            'Label' => 'AF Zoom-Nikkor 28-200mm f/3.5-5.6D IF',
        ),
        '4D 41 3C 8E 2B 40 62 02' => array(
            'Id' => '4D 41 3C 8E 2B 40 62 02',
            'Label' => 'Tamron AF 28-300mm f/3.5-6.3 XR Di LD Aspherical (IF) (A061)',
        ),
        '4D 41 3C 8E 2C 40 62 02' => array(
            'Id' => '4D 41 3C 8E 2C 40 62 02',
            'Label' => 'Tamron AF 28-300mm f/3.5-6.3 XR LD Aspherical (IF) (185D)',
        ),
        '4E 48 72 72 18 18 51 02' => array(
            'Id' => '4E 48 72 72 18 18 51 02',
            'Label' => 'AF DC-Nikkor 135mm f/2D',
        ),
        '4F 40 37 5C 2C 3C 53 06' => array(
            'Id' => '4F 40 37 5C 2C 3C 53 06',
            'Label' => 'IX-Nikkor 24-70mm f/3.5-5.6',
        ),
        '50 48 56 7C 30 3C 54 06' => array(
            'Id' => '50 48 56 7C 30 3C 54 06',
            'Label' => 'IX-Nikkor 60-180mm f/4-5.6',
        ),
        '52 54 44 44 18 18 00 00' => array(
            'Id' => '52 54 44 44 18 18 00 00',
            'Label' => 'Zeiss Milvus 35mm f/2',
        ),
        '53 48 60 80 24 24 57 02' => array(
            'Id' => '53 48 60 80 24 24 57 02',
            'Label' => 'AF Zoom-Nikkor 80-200mm f/2.8D ED',
        ),
        '53 48 60 80 24 24 60 02' => array(
            'Id' => '53 48 60 80 24 24 60 02',
            'Label' => 'AF Zoom-Nikkor 80-200mm f/2.8D ED',
        ),
        '53 54 50 50 0C 0C 00 00' => array(
            'Id' => '53 54 50 50 0C 0C 00 00',
            'Label' => 'Zeiss Milvus 50mm f/1.4',
        ),
        '54 44 5C 7C 34 3C 58 02' => array(
            'Id' => '54 44 5C 7C 34 3C 58 02',
            'Label' => 'AF Zoom-Micro Nikkor 70-180mm f/4.5-5.6D ED',
        ),
        '54 44 5C 7C 34 3C 61 02' => array(
            'Id' => '54 44 5C 7C 34 3C 61 02',
            'Label' => 'AF Zoom-Micro Nikkor 70-180mm f/4.5-5.6D ED',
        ),
        '54 54 50 50 18 18 00 00' => array(
            'Id' => '54 54 50 50 18 18 00 00',
            'Label' => 'Zeiss Milvus 50mm f/2 Macro',
        ),
        '55 54 62 62 0C 0C 00 00' => array(
            'Id' => '55 54 62 62 0C 0C 00 00',
            'Label' => 'Zeiss Milvus 85mm f/1.4',
        ),
        '56 3C 5C 8E 30 3C 1C 02' => array(
            'Id' => '56 3C 5C 8E 30 3C 1C 02',
            'Label' => 'Sigma 70-300mm F4-5.6 APO Macro Super II',
        ),
        '56 48 5C 8E 30 3C 5A 02' => array(
            'Id' => '56 48 5C 8E 30 3C 5A 02',
            'Label' => 'AF Zoom-Nikkor 70-300mm f/4-5.6D ED',
        ),
        '56 54 68 68 18 18 00 00' => array(
            'Id' => '56 54 68 68 18 18 00 00',
            'Label' => 'Zeiss Milvus 100mm f/2 Macro',
        ),
        '59 48 98 98 24 24 5D 02' => array(
            'Id' => '59 48 98 98 24 24 5D 02',
            'Label' => 'AF-S Nikkor 400mm f/2.8D IF-ED',
        ),
        '59 48 98 98 24 24 E1 02' => array(
            'Id' => '59 48 98 98 24 24 E1 02',
            'Label' => 'AF-S Nikkor 400mm f/2.8D IF-ED + TC-17E',
        ),
        '59 48 98 98 24 24 F1 02' => array(
            'Id' => '59 48 98 98 24 24 F1 02',
            'Label' => 'AF-S Nikkor 400mm f/2.8D IF-ED + TC-14E',
        ),
        '59 48 98 98 24 24 F2 02' => array(
            'Id' => '59 48 98 98 24 24 F2 02',
            'Label' => 'AF-S Nikkor 400mm f/2.8D IF-ED + TC-20E',
        ),
        '5A 3C 3E 56 30 3C 5E 06' => array(
            'Id' => '5A 3C 3E 56 30 3C 5E 06',
            'Label' => 'IX-Nikkor 30-60mm f/4-5.6',
        ),
        '5B 44 56 7C 34 3C 5F 06' => array(
            'Id' => '5B 44 56 7C 34 3C 5F 06',
            'Label' => 'IX-Nikkor 60-180mm f/4.5-5.6',
        ),
        '5D 48 3C 5C 24 24 63 02' => array(
            'Id' => '5D 48 3C 5C 24 24 63 02',
            'Label' => 'AF-S Zoom-Nikkor 28-70mm f/2.8D IF-ED',
        ),
        '5E 48 60 80 24 24 64 02' => array(
            'Id' => '5E 48 60 80 24 24 64 02',
            'Label' => 'AF-S Zoom-Nikkor 80-200mm f/2.8D IF-ED',
        ),
        '5F 40 3C 6A 2C 34 65 02' => array(
            'Id' => '5F 40 3C 6A 2C 34 65 02',
            'Label' => 'AF Zoom-Nikkor 28-105mm f/3.5-4.5D IF',
        ),
        '60 40 3C 60 2C 3C 66 02' => array(
            'Id' => '60 40 3C 60 2C 3C 66 02',
            'Label' => 'AF Zoom-Nikkor 28-80mm f/3.5-5.6D',
        ),
        '61 44 5E 86 34 3C 67 02' => array(
            'Id' => '61 44 5E 86 34 3C 67 02',
            'Label' => 'AF Zoom-Nikkor 75-240mm f/4.5-5.6D',
        ),
        '63 48 2B 44 24 24 68 02' => array(
            'Id' => '63 48 2B 44 24 24 68 02',
            'Label' => 'AF-S Nikkor 17-35mm f/2.8D IF-ED',
        ),
        '64 00 62 62 24 24 6A 02' => array(
            'Id' => '64 00 62 62 24 24 6A 02',
            'Label' => 'PC Micro-Nikkor 85mm f/2.8D',
        ),
        '65 44 60 98 34 3C 6B 0A' => array(
            'Id' => '65 44 60 98 34 3C 6B 0A',
            'Label' => 'AF VR Zoom-Nikkor 80-400mm f/4.5-5.6D ED',
        ),
        '66 40 2D 44 2C 34 6C 02' => array(
            'Id' => '66 40 2D 44 2C 34 6C 02',
            'Label' => 'AF Zoom-Nikkor 18-35mm f/3.5-4.5D IF-ED',
        ),
        '67 48 37 62 24 30 6D 02' => array(
            'Id' => '67 48 37 62 24 30 6D 02',
            'Label' => 'AF Zoom-Nikkor 24-85mm f/2.8-4D IF',
        ),
        '67 54 37 5C 24 24 1C 02' => array(
            'Id' => '67 54 37 5C 24 24 1C 02',
            'Label' => 'Sigma 24-70mm F2.8 EX DG Macro',
        ),
        '68 42 3C 60 2A 3C 6E 06' => array(
            'Id' => '68 42 3C 60 2A 3C 6E 06',
            'Label' => 'AF Zoom-Nikkor 28-80mm f/3.3-5.6G',
        ),
        '69 47 5C 8E 30 3C 00 02' => array(
            'Id' => '69 47 5C 8E 30 3C 00 02',
            'Label' => 'Tamron AF 70-300mm f/4-5.6 Di LD Macro 1:2 (A17N)',
        ),
        '69 48 5C 8E 30 3C 6F 02' => array(
            'Id' => '69 48 5C 8E 30 3C 6F 02',
            'Label' => 'Tamron AF 70-300mm f/4-5.6 LD Macro 1:2 (572D/772D)',
        ),
        '69 48 5C 8E 30 3C 6F 06' => array(
            'Id' => '69 48 5C 8E 30 3C 6F 06',
            'Label' => 'AF Zoom-Nikkor 70-300mm f/4-5.6G',
        ),
        '6A 48 8E 8E 30 30 70 02' => array(
            'Id' => '6A 48 8E 8E 30 30 70 02',
            'Label' => 'AF-S Nikkor 300mm f/4D IF-ED',
        ),
        '6B 48 24 24 24 24 71 02' => array(
            'Id' => '6B 48 24 24 24 24 71 02',
            'Label' => 'AF Nikkor ED 14mm f/2.8D',
        ),
        '6D 48 8E 8E 24 24 73 02' => array(
            'Id' => '6D 48 8E 8E 24 24 73 02',
            'Label' => 'AF-S Nikkor 300mm f/2.8D IF-ED II',
        ),
        '6E 48 98 98 24 24 74 02' => array(
            'Id' => '6E 48 98 98 24 24 74 02',
            'Label' => 'AF-S Nikkor 400mm f/2.8D IF-ED II',
        ),
        '6F 3C A0 A0 30 30 75 02' => array(
            'Id' => '6F 3C A0 A0 30 30 75 02',
            'Label' => 'AF-S Nikkor 500mm f/4D IF-ED II',
        ),
        '70 3C A6 A6 30 30 76 02' => array(
            'Id' => '70 3C A6 A6 30 30 76 02',
            'Label' => 'AF-S Nikkor 600mm f/4D IF-ED II',
        ),
        '72 48 4C 4C 24 24 77 00' => array(
            'Id' => '72 48 4C 4C 24 24 77 00',
            'Label' => 'Nikkor 45mm f/2.8 P',
        ),
        '74 40 37 62 2C 34 78 06' => array(
            'Id' => '74 40 37 62 2C 34 78 06',
            'Label' => 'AF-S Zoom-Nikkor 24-85mm f/3.5-4.5G IF-ED',
        ),
        '75 40 3C 68 2C 3C 79 06' => array(
            'Id' => '75 40 3C 68 2C 3C 79 06',
            'Label' => 'AF Zoom-Nikkor 28-100mm f/3.5-5.6G',
        ),
        '76 58 50 50 14 14 7A 02' => array(
            'Id' => '76 58 50 50 14 14 7A 02',
            'Label' => 'AF Nikkor 50mm f/1.8D',
        ),
        '77 44 61 98 34 3C 7B 0E' => array(
            'Id' => '77 44 61 98 34 3C 7B 0E',
            'Label' => 'Sigma 80-400mm F4.5-5.6 EX OS',
        ),
        '77 48 5C 80 24 24 7B 0E' => array(
            'Id' => '77 48 5C 80 24 24 7B 0E',
            'Label' => 'AF-S VR Zoom-Nikkor 70-200mm f/2.8G IF-ED',
        ),
        '78 40 37 6E 2C 3C 7C 0E' => array(
            'Id' => '78 40 37 6E 2C 3C 7C 0E',
            'Label' => 'AF-S VR Zoom-Nikkor 24-120mm f/3.5-5.6G IF-ED',
        ),
        '79 40 11 11 2C 2C 1C 06' => array(
            'Id' => '79 40 11 11 2C 2C 1C 06',
            'Label' => 'Sigma 8mm F3.5 EX Circular Fisheye',
        ),
        '79 40 3C 80 2C 3C 7F 06' => array(
            'Id' => '79 40 3C 80 2C 3C 7F 06',
            'Label' => 'AF Zoom-Nikkor 28-200mm f/3.5-5.6G IF-ED',
        ),
        '79 48 3C 5C 24 24 1C 06' => array(
            'Id' => '79 48 3C 5C 24 24 1C 06',
            'Label' => 'Sigma 28-70mm F2.8 EX DG',
        ),
        '79 48 5C 5C 24 24 1C 06' => array(
            'Id' => '79 48 5C 5C 24 24 1C 06',
            'Label' => 'Sigma Macro 70mm F2.8 EX DG',
        ),
        '7A 3B 53 80 30 3C 4B 06' => array(
            'Id' => '7A 3B 53 80 30 3C 4B 06',
            'Label' => 'Sigma 55-200mm F4-5.6 DC HSM',
        ),
        '7A 3C 1F 37 30 30 7E 06.1' => array(
            'Id' => '7A 3C 1F 37 30 30 7E 06.1',
            'Label' => 'AF-S DX Zoom-Nikkor 12-24mm f/4G IF-ED',
        ),
        '7A 3C 1F 37 30 30 7E 06.2' => array(
            'Id' => '7A 3C 1F 37 30 30 7E 06.2',
            'Label' => 'Tokina AT-X 124 AF PRO DX II (AF 12-24mm f/4)',
        ),
        '7A 3C 1F 3C 30 30 7E 06' => array(
            'Id' => '7A 3C 1F 3C 30 30 7E 06',
            'Label' => 'Tokina AT-X 12-28 PRO DX (AF 12-28mm F/4)',
        ),
        '7A 40 2D 50 2C 3C 4B 06' => array(
            'Id' => '7A 40 2D 50 2C 3C 4B 06',
            'Label' => 'Sigma 18-50mm F3.5-5.6 DC HSM',
        ),
        '7A 40 2D 80 2C 40 4B 0E' => array(
            'Id' => '7A 40 2D 80 2C 40 4B 0E',
            'Label' => 'Sigma 18-200mm F3.5-6.3 DC OS HSM',
        ),
        '7A 47 2B 5C 24 34 4B 06' => array(
            'Id' => '7A 47 2B 5C 24 34 4B 06',
            'Label' => 'Sigma 17-70mm F2.8-4.5 DC Macro Asp. IF HSM',
        ),
        '7A 47 50 76 24 24 4B 06' => array(
            'Id' => '7A 47 50 76 24 24 4B 06',
            'Label' => 'Sigma 50-150mm F2.8 EX APO DC HSM',
        ),
        '7A 48 1C 29 24 24 7E 06' => array(
            'Id' => '7A 48 1C 29 24 24 7E 06',
            'Label' => 'Tokina AT-X 116 PRO DX II (AF 11-16mm f/2.8)',
        ),
        '7A 48 1C 30 24 24 7E 06' => array(
            'Id' => '7A 48 1C 30 24 24 7E 06',
            'Label' => 'Tokina AT-X 11-20 F2.8 PRO DX (AF 11-20mm f/2.8)',
        ),
        '7A 48 2B 5C 24 34 4B 06' => array(
            'Id' => '7A 48 2B 5C 24 34 4B 06',
            'Label' => 'Sigma 17-70mm F2.8-4.5 DC Macro Asp. IF HSM',
        ),
        '7A 48 2D 50 24 24 4B 06' => array(
            'Id' => '7A 48 2D 50 24 24 4B 06',
            'Label' => 'Sigma 18-50mm F2.8 EX DC Macro',
        ),
        '7A 48 5C 80 24 24 4B 06' => array(
            'Id' => '7A 48 5C 80 24 24 4B 06',
            'Label' => 'Sigma 70-200mm F2.8 EX APO DG Macro HSM II',
        ),
        '7A 54 6E 8E 24 24 4B 02' => array(
            'Id' => '7A 54 6E 8E 24 24 4B 02',
            'Label' => 'Sigma APO 120-300mm F2.8 EX DG HSM',
        ),
        '7B 48 80 98 30 30 80 0E' => array(
            'Id' => '7B 48 80 98 30 30 80 0E',
            'Label' => 'AF-S VR Zoom-Nikkor 200-400mm f/4G IF-ED',
        ),
        '7D 48 2B 53 24 24 82 06' => array(
            'Id' => '7D 48 2B 53 24 24 82 06',
            'Label' => 'AF-S DX Zoom-Nikkor 17-55mm f/2.8G IF-ED',
        ),
        '7F 40 2D 5C 2C 34 84 06' => array(
            'Id' => '7F 40 2D 5C 2C 34 84 06',
            'Label' => 'AF-S DX Zoom-Nikkor 18-70mm f/3.5-4.5G IF-ED',
        ),
        '7F 48 2B 5C 24 34 1C 06' => array(
            'Id' => '7F 48 2B 5C 24 34 1C 06',
            'Label' => 'Sigma 17-70mm F2.8-4.5 DC Macro Asp. IF',
        ),
        '7F 48 2D 50 24 24 1C 06' => array(
            'Id' => '7F 48 2D 50 24 24 1C 06',
            'Label' => 'Sigma 18-50mm F2.8 EX DC Macro',
        ),
        '80 48 1A 1A 24 24 85 06' => array(
            'Id' => '80 48 1A 1A 24 24 85 06',
            'Label' => 'AF DX Fisheye-Nikkor 10.5mm f/2.8G ED',
        ),
        '81 34 76 A6 38 40 4B 0E' => array(
            'Id' => '81 34 76 A6 38 40 4B 0E',
            'Label' => 'Sigma 150-600mm F5-6.3 DG OS HSM | S',
        ),
        '81 54 80 80 18 18 86 0E' => array(
            'Id' => '81 54 80 80 18 18 86 0E',
            'Label' => 'AF-S VR Nikkor 200mm f/2G IF-ED',
        ),
        '82 34 76 A6 38 40 4B 0E' => array(
            'Id' => '82 34 76 A6 38 40 4B 0E',
            'Label' => 'Sigma 150-600mm F5-6.3 DG OS HSM | C',
        ),
        '82 48 8E 8E 24 24 87 0E' => array(
            'Id' => '82 48 8E 8E 24 24 87 0E',
            'Label' => 'AF-S VR Nikkor 300mm f/2.8G IF-ED',
        ),
        '83 00 B0 B0 5A 5A 88 04' => array(
            'Id' => '83 00 B0 B0 5A 5A 88 04',
            'Label' => 'FSA-L2, EDG 65, 800mm F13 G',
        ),
        '88 54 50 50 0C 0C 4B 06' => array(
            'Id' => '88 54 50 50 0C 0C 4B 06',
            'Label' => 'Sigma 50mm F1.4 DG HSM | A',
        ),
        '89 3C 53 80 30 3C 8B 06' => array(
            'Id' => '89 3C 53 80 30 3C 8B 06',
            'Label' => 'AF-S DX Zoom-Nikkor 55-200mm f/4-5.6G ED',
        ),
        '8A 3C 37 6A 30 30 4B 0E' => array(
            'Id' => '8A 3C 37 6A 30 30 4B 0E',
            'Label' => 'Sigma 24-105mm F4 DG OS HSM',
        ),
        '8A 54 6A 6A 24 24 8C 0E' => array(
            'Id' => '8A 54 6A 6A 24 24 8C 0E',
            'Label' => 'AF-S VR Micro-Nikkor 105mm f/2.8G IF-ED',
        ),
        '8B 40 2D 80 2C 3C 8D 0E' => array(
            'Id' => '8B 40 2D 80 2C 3C 8D 0E',
            'Label' => 'AF-S DX VR Zoom-Nikkor 18-200mm f/3.5-5.6G IF-ED',
        ),
        '8B 40 2D 80 2C 3C FD 0E' => array(
            'Id' => '8B 40 2D 80 2C 3C FD 0E',
            'Label' => 'AF-S DX VR Zoom-Nikkor 18-200mm f/3.5-5.6G IF-ED [II]',
        ),
        '8B 4C 2D 44 14 14 4B 06' => array(
            'Id' => '8B 4C 2D 44 14 14 4B 06',
            'Label' => 'Sigma 18-35mm F1.8 DC HSM',
        ),
        '8C 40 2D 53 2C 3C 8E 06' => array(
            'Id' => '8C 40 2D 53 2C 3C 8E 06',
            'Label' => 'AF-S DX Zoom-Nikkor 18-55mm f/3.5-5.6G ED',
        ),
        '8D 44 5C 8E 34 3C 8F 0E' => array(
            'Id' => '8D 44 5C 8E 34 3C 8F 0E',
            'Label' => 'AF-S VR Zoom-Nikkor 70-300mm f/4.5-5.6G IF-ED',
        ),
        '8E 3C 2B 5C 24 30 4B 0E' => array(
            'Id' => '8E 3C 2B 5C 24 30 4B 0E',
            'Label' => 'Sigma 17-70mm F2.8-4 DC Macro OS HSM Contemporary',
        ),
        '8F 40 2D 72 2C 3C 91 06' => array(
            'Id' => '8F 40 2D 72 2C 3C 91 06',
            'Label' => 'AF-S DX Zoom-Nikkor 18-135mm f/3.5-5.6G IF-ED',
        ),
        '8F 48 2B 50 24 24 4B 0E' => array(
            'Id' => '8F 48 2B 50 24 24 4B 0E',
            'Label' => 'Sigma 17-50mm F2.8 EX DC OS HSM',
        ),
        '90 3B 53 80 30 3C 92 0E' => array(
            'Id' => '90 3B 53 80 30 3C 92 0E',
            'Label' => 'AF-S DX VR Zoom-Nikkor 55-200mm f/4-5.6G IF-ED',
        ),
        '90 40 2D 80 2C 40 4B 0E' => array(
            'Id' => '90 40 2D 80 2C 40 4B 0E',
            'Label' => 'Sigma 18-200mm F3.5-6.3 II DC OS HSM',
        ),
        '91 54 44 44 0C 0C 4B 06' => array(
            'Id' => '91 54 44 44 0C 0C 4B 06',
            'Label' => 'Sigma 35mm F1.4 DG HSM',
        ),
        '92 2C 2D 88 2C 40 4B 0E' => array(
            'Id' => '92 2C 2D 88 2C 40 4B 0E',
            'Label' => 'Sigma 18-250mm F3.5-6.3 DC Macro OS HSM',
        ),
        '92 48 24 37 24 24 94 06' => array(
            'Id' => '92 48 24 37 24 24 94 06',
            'Label' => 'AF-S Zoom-Nikkor 14-24mm f/2.8G ED',
        ),
        '93 48 37 5C 24 24 95 06' => array(
            'Id' => '93 48 37 5C 24 24 95 06',
            'Label' => 'AF-S Zoom-Nikkor 24-70mm f/2.8G ED',
        ),
        '94 40 2D 53 2C 3C 96 06' => array(
            'Id' => '94 40 2D 53 2C 3C 96 06',
            'Label' => 'AF-S DX Zoom-Nikkor 18-55mm f/3.5-5.6G ED II',
        ),
        '95 00 37 37 2C 2C 97 06' => array(
            'Id' => '95 00 37 37 2C 2C 97 06',
            'Label' => 'PC-E Nikkor 24mm f/3.5D ED',
        ),
        '95 4C 37 37 2C 2C 97 02' => array(
            'Id' => '95 4C 37 37 2C 2C 97 02',
            'Label' => 'PC-E Nikkor 24mm f/3.5D ED',
        ),
        '96 38 1F 37 34 3C 4B 06' => array(
            'Id' => '96 38 1F 37 34 3C 4B 06',
            'Label' => 'Sigma 12-24mm F4.5-5.6 II DG HSM',
        ),
        '96 48 98 98 24 24 98 0E' => array(
            'Id' => '96 48 98 98 24 24 98 0E',
            'Label' => 'AF-S VR Nikkor 400mm f/2.8G ED',
        ),
        '97 3C A0 A0 30 30 99 0E' => array(
            'Id' => '97 3C A0 A0 30 30 99 0E',
            'Label' => 'AF-S VR Nikkor 500mm f/4G ED',
        ),
        '97 48 6A 6A 24 24 4B 0E' => array(
            'Id' => '97 48 6A 6A 24 24 4B 0E',
            'Label' => 'Sigma Macro 105mm F2.8 EX DG OS HSM',
        ),
        '98 3C A6 A6 30 30 9A 0E' => array(
            'Id' => '98 3C A6 A6 30 30 9A 0E',
            'Label' => 'AF-S VR Nikkor 600mm f/4G ED',
        ),
        '98 48 50 76 24 24 4B 0E' => array(
            'Id' => '98 48 50 76 24 24 4B 0E',
            'Label' => 'Sigma 50-150mm F2.8 EX APO DC OS HSM',
        ),
        '99 40 29 62 2C 3C 9B 0E' => array(
            'Id' => '99 40 29 62 2C 3C 9B 0E',
            'Label' => 'AF-S DX VR Zoom-Nikkor 16-85mm f/3.5-5.6G ED',
        ),
        '99 48 76 76 24 24 4B 0E' => array(
            'Id' => '99 48 76 76 24 24 4B 0E',
            'Label' => 'Sigma APO Macro 150mm F2.8 EX DG OS HSM',
        ),
        '9A 40 2D 53 2C 3C 9C 0E' => array(
            'Id' => '9A 40 2D 53 2C 3C 9C 0E',
            'Label' => 'AF-S DX VR Zoom-Nikkor 18-55mm f/3.5-5.6G',
        ),
        '9B 00 4C 4C 24 24 9D 06' => array(
            'Id' => '9B 00 4C 4C 24 24 9D 06',
            'Label' => 'PC-E Micro Nikkor 45mm f/2.8D ED',
        ),
        '9B 54 4C 4C 24 24 9D 02' => array(
            'Id' => '9B 54 4C 4C 24 24 9D 02',
            'Label' => 'PC-E Micro Nikkor 45mm f/2.8D ED',
        ),
        '9B 54 62 62 0C 0C 4B 06' => array(
            'Id' => '9B 54 62 62 0C 0C 4B 06',
            'Label' => 'Sigma 85mm F1.4 EX DG HSM',
        ),
        '9C 48 5C 80 24 24 4B 0E' => array(
            'Id' => '9C 48 5C 80 24 24 4B 0E',
            'Label' => 'Sigma 70-200mm F2.8 EX DG OS HSM',
        ),
        '9C 54 56 56 24 24 9E 06' => array(
            'Id' => '9C 54 56 56 24 24 9E 06',
            'Label' => 'AF-S Micro Nikkor 60mm f/2.8G ED',
        ),
        '9D 00 62 62 24 24 9F 06' => array(
            'Id' => '9D 00 62 62 24 24 9F 06',
            'Label' => 'PC-E Micro Nikkor 85mm f/2.8D',
        ),
        '9D 48 2B 50 24 24 4B 0E' => array(
            'Id' => '9D 48 2B 50 24 24 4B 0E',
            'Label' => 'Sigma 17-50mm F2.8 EX DC OS HSM',
        ),
        '9D 54 62 62 24 24 9F 02' => array(
            'Id' => '9D 54 62 62 24 24 9F 02',
            'Label' => 'PC-E Micro Nikkor 85mm f/2.8D',
        ),
        '9E 38 11 29 34 3C 4B 06' => array(
            'Id' => '9E 38 11 29 34 3C 4B 06',
            'Label' => 'Sigma 8-16mm F4.5-5.6 DC HSM',
        ),
        '9E 40 2D 6A 2C 3C A0 0E' => array(
            'Id' => '9E 40 2D 6A 2C 3C A0 0E',
            'Label' => 'AF-S DX VR Zoom-Nikkor 18-105mm f/3.5-5.6G ED',
        ),
        '9F 37 50 A0 34 40 4B 0E' => array(
            'Id' => '9F 37 50 A0 34 40 4B 0E',
            'Label' => 'Sigma 50-500mm F4.5-6.3 DG OS HSM',
        ),
        '9F 58 44 44 14 14 A1 06' => array(
            'Id' => '9F 58 44 44 14 14 A1 06',
            'Label' => 'AF-S DX Nikkor 35mm f/1.8G',
        ),
        'A0 40 2D 74 2C 3C BB 0E' => array(
            'Id' => 'A0 40 2D 74 2C 3C BB 0E',
            'Label' => 'AF-S DX Nikkor 18-140mm f/3.5-5.6G ED VR',
        ),
        'A0 48 2A 5C 24 30 4B 0E' => array(
            'Id' => 'A0 48 2A 5C 24 30 4B 0E',
            'Label' => 'Sigma 17-70mm F2.8-4 DC Macro OS HSM',
        ),
        'A0 54 50 50 0C 0C A2 06' => array(
            'Id' => 'A0 54 50 50 0C 0C A2 06',
            'Label' => 'AF-S Nikkor 50mm f/1.4G',
        ),
        'A1 40 18 37 2C 34 A3 06' => array(
            'Id' => 'A1 40 18 37 2C 34 A3 06',
            'Label' => 'AF-S DX Nikkor 10-24mm f/3.5-4.5G ED',
        ),
        'A1 41 19 31 2C 2C 4B 06' => array(
            'Id' => 'A1 41 19 31 2C 2C 4B 06',
            'Label' => 'Sigma 10-20mm F3.5 EX DC HSM',
        ),
        'A1 54 55 55 0C 0C BC 06' => array(
            'Id' => 'A1 54 55 55 0C 0C BC 06',
            'Label' => 'AF-S Nikkor 58mm f/1.4G',
        ),
        'A2 40 2D 53 2C 3C BD 0E' => array(
            'Id' => 'A2 40 2D 53 2C 3C BD 0E',
            'Label' => 'AF-S DX VR Nikkor 18-55mm f/3.5-5.6G II',
        ),
        'A2 48 5C 80 24 24 A4 0E' => array(
            'Id' => 'A2 48 5C 80 24 24 A4 0E',
            'Label' => 'AF-S Nikkor 70-200mm f/2.8G ED VR II',
        ),
        'A3 3C 29 44 30 30 A5 0E' => array(
            'Id' => 'A3 3C 29 44 30 30 A5 0E',
            'Label' => 'AF-S Nikkor 16-35mm f/4G ED VR',
        ),
        'A3 3C 5C 8E 30 3C 4B 0E' => array(
            'Id' => 'A3 3C 5C 8E 30 3C 4B 0E',
            'Label' => 'Sigma 70-300mm F4-5.6 DG OS',
        ),
        'A4 40 2D 8E 2C 40 BF 0E' => array(
            'Id' => 'A4 40 2D 8E 2C 40 BF 0E',
            'Label' => 'AF-S DX Nikkor 18-300mm f/3.5-6.3G ED VR',
        ),
        'A4 47 2D 50 24 34 4B 0E' => array(
            'Id' => 'A4 47 2D 50 24 34 4B 0E',
            'Label' => 'Sigma 18-50mm F2.8-4.5 DC OS HSM',
        ),
        'A4 54 37 37 0C 0C A6 06' => array(
            'Id' => 'A4 54 37 37 0C 0C A6 06',
            'Label' => 'AF-S Nikkor 24mm f/1.4G ED',
        ),
        'A5 40 2D 88 2C 40 4B 0E' => array(
            'Id' => 'A5 40 2D 88 2C 40 4B 0E',
            'Label' => 'Sigma 18-250mm F3.5-6.3 DC OS HSM',
        ),
        'A5 40 3C 8E 2C 3C A7 0E' => array(
            'Id' => 'A5 40 3C 8E 2C 3C A7 0E',
            'Label' => 'AF-S Nikkor 28-300mm f/3.5-5.6G ED VR',
        ),
        'A5 4C 44 44 14 14 C0 06' => array(
            'Id' => 'A5 4C 44 44 14 14 C0 06',
            'Label' => 'AF-S Nikkor 35mm f/1.8G',
        ),
        'A6 48 37 5C 24 24 4B 06' => array(
            'Id' => 'A6 48 37 5C 24 24 4B 06',
            'Label' => 'Sigma 24-70mm F2.8 IF EX DG HSM',
        ),
        'A6 48 8E 8E 24 24 A8 0E' => array(
            'Id' => 'A6 48 8E 8E 24 24 A8 0E',
            'Label' => 'AF-S VR Nikkor 300mm f/2.8G IF-ED II',
        ),
        'A7 3C 53 80 30 3C C2 0E' => array(
            'Id' => 'A7 3C 53 80 30 3C C2 0E',
            'Label' => 'AF-S DX Nikkor 55-200mm f/4-5.6G ED VR II',
        ),
        'A7 49 80 A0 24 24 4B 06' => array(
            'Id' => 'A7 49 80 A0 24 24 4B 06',
            'Label' => 'Sigma APO 200-500mm F2.8 EX DG',
        ),
        'A7 4B 62 62 2C 2C A9 0E' => array(
            'Id' => 'A7 4B 62 62 2C 2C A9 0E',
            'Label' => 'AF-S DX Micro Nikkor 85mm f/3.5G ED VR',
        ),
        'A8 48 80 98 30 30 AA 0E' => array(
            'Id' => 'A8 48 80 98 30 30 AA 0E',
            'Label' => 'AF-S VR Zoom-Nikkor 200-400mm f/4G IF-ED II',
        ),
        'A8 48 8E 8E 30 30 C3 0E' => array(
            'Id' => 'A8 48 8E 8E 30 30 C3 0E',
            'Label' => 'AF-S Nikkor 300mm f/4E PF ED VR',
        ),
        'A8 48 8E 8E 30 30 C3 4E' => array(
            'Id' => 'A8 48 8E 8E 30 30 C3 4E',
            'Label' => 'AF-S Nikkor 300mm f/4E PF ED VR',
        ),
        'A9 4C 31 31 14 14 C4 06' => array(
            'Id' => 'A9 4C 31 31 14 14 C4 06',
            'Label' => 'AF-S Nikkor 20mm f/1.8G ED',
        ),
        'A9 54 80 80 18 18 AB 0E' => array(
            'Id' => 'A9 54 80 80 18 18 AB 0E',
            'Label' => 'AF-S Nikkor 200mm f/2G ED VR II',
        ),
        'AA 3C 37 6E 30 30 AC 0E' => array(
            'Id' => 'AA 3C 37 6E 30 30 AC 0E',
            'Label' => 'AF-S Nikkor 24-120mm f/4G ED VR',
        ),
        'AA 48 37 5C 24 24 C5 4E' => array(
            'Id' => 'AA 48 37 5C 24 24 C5 4E',
            'Label' => 'AF-S Nikkor 24-70mm f/2.8E ED VR',
        ),
        'AC 38 53 8E 34 3C AE 0E' => array(
            'Id' => 'AC 38 53 8E 34 3C AE 0E',
            'Label' => 'AF-S DX VR Nikkor 55-300mm f/4.5-5.6G ED',
        ),
        'AC 3C A6 A6 30 30 C7 4E' => array(
            'Id' => 'AC 3C A6 A6 30 30 C7 4E',
            'Label' => 'AF-S Nikkor 600mm f/4E FL ED VR',
        ),
        'AD 3C 2D 8E 2C 3C AF 0E' => array(
            'Id' => 'AD 3C 2D 8E 2C 3C AF 0E',
            'Label' => 'AF-S DX Nikkor 18-300mm f/3.5-5.6G ED VR',
        ),
        'AD 48 28 60 24 30 C8 4E' => array(
            'Id' => 'AD 48 28 60 24 30 C8 4E',
            'Label' => 'AF-S VR DX 16-80mm f/2.8-4.0E ED',
        ),
        'AE 3C 80 A0 3C 3C C9 0E' => array(
            'Id' => 'AE 3C 80 A0 3C 3C C9 0E',
            'Label' => 'AF-S Nikkor 200-500mm f/5.6E ED VR',
        ),
        'AE 3C 80 A0 3C 3C C9 4E' => array(
            'Id' => 'AE 3C 80 A0 3C 3C C9 4E',
            'Label' => 'AF-S Nikkor 200-500mm f/5.6E ED VR',
        ),
        'AE 54 62 62 0C 0C B0 06' => array(
            'Id' => 'AE 54 62 62 0C 0C B0 06',
            'Label' => 'AF-S Nikkor 85mm f/1.4G',
        ),
        'AF 4C 37 37 14 14 CC 06' => array(
            'Id' => 'AF 4C 37 37 14 14 CC 06',
            'Label' => 'AF-S Nikkor 24mm f/1.8G ED',
        ),
        'AF 54 44 44 0C 0C B1 06' => array(
            'Id' => 'AF 54 44 44 0C 0C B1 06',
            'Label' => 'AF-S Nikkor 35mm f/1.4G',
        ),
        'B0 4C 50 50 14 14 B2 06' => array(
            'Id' => 'B0 4C 50 50 14 14 B2 06',
            'Label' => 'AF-S Nikkor 50mm f/1.8G',
        ),
        'B1 48 48 48 24 24 B3 06' => array(
            'Id' => 'B1 48 48 48 24 24 B3 06',
            'Label' => 'AF-S DX Micro Nikkor 40mm f/2.8G',
        ),
        'B2 48 5C 80 30 30 B4 0E' => array(
            'Id' => 'B2 48 5C 80 30 30 B4 0E',
            'Label' => 'AF-S Nikkor 70-200mm f/4G ED VR',
        ),
        'B3 4C 62 62 14 14 B5 06' => array(
            'Id' => 'B3 4C 62 62 14 14 B5 06',
            'Label' => 'AF-S Nikkor 85mm f/1.8G',
        ),
        'B4 40 37 62 2C 34 B6 0E' => array(
            'Id' => 'B4 40 37 62 2C 34 B6 0E',
            'Label' => 'AF-S VR Zoom-Nikkor 24-85mm f/3.5-4.5G IF-ED',
        ),
        'B5 4C 3C 3C 14 14 B7 06' => array(
            'Id' => 'B5 4C 3C 3C 14 14 B7 06',
            'Label' => 'AF-S Nikkor 28mm f/1.8G',
        ),
        'B6 48 37 56 24 24 1C 02' => array(
            'Id' => 'B6 48 37 56 24 24 1C 02',
            'Label' => 'Sigma 24-60mm F2.8 EX DG',
        ),
        'B7 44 60 98 34 3C B9 0E' => array(
            'Id' => 'B7 44 60 98 34 3C B9 0E',
            'Label' => 'AF-S Nikkor 80-400mm f/4.5-5.6G ED VR',
        ),
        'B8 40 2D 44 2C 34 BA 06' => array(
            'Id' => 'B8 40 2D 44 2C 34 BA 06',
            'Label' => 'AF-S Nikkor 18-35mm f/3.5-4.5G ED',
        ),
        'CD 3D 2D 70 2E 3C 4B 0E' => array(
            'Id' => 'CD 3D 2D 70 2E 3C 4B 0E',
            'Label' => 'Sigma 18-125mm F3.8-5.6 DC OS HSM',
        ),
        'CE 34 76 A0 38 40 4B 0E' => array(
            'Id' => 'CE 34 76 A0 38 40 4B 0E',
            'Label' => 'Sigma 150-500mm F5-6.3 DG OS APO HSM',
        ),
        'CF 38 6E 98 34 3C 4B 0E' => array(
            'Id' => 'CF 38 6E 98 34 3C 4B 0E',
            'Label' => 'Sigma APO 120-400mm F4.5-5.6 DG OS HSM',
        ),
        'DC 48 19 19 24 24 4B 06' => array(
            'Id' => 'DC 48 19 19 24 24 4B 06',
            'Label' => 'Sigma 10mm F2.8 EX DC HSM Fisheye',
        ),
        'DE 54 50 50 0C 0C 4B 06' => array(
            'Id' => 'DE 54 50 50 0C 0C 4B 06',
            'Label' => 'Sigma 50mm F1.4 EX DG HSM',
        ),
        'E0 3C 5C 8E 30 3C 4B 06' => array(
            'Id' => 'E0 3C 5C 8E 30 3C 4B 06',
            'Label' => 'Sigma 70-300mm F4-5.6 APO DG Macro HSM',
        ),
        'E1 58 37 37 14 14 1C 02' => array(
            'Id' => 'E1 58 37 37 14 14 1C 02',
            'Label' => 'Sigma 24mm F1.8 EX DG Aspherical Macro',
        ),
        'E3 54 50 50 24 24 35 02' => array(
            'Id' => 'E3 54 50 50 24 24 35 02',
            'Label' => 'Sigma Macro 50mm F2.8 EX DG',
        ),
        'E5 54 6A 6A 24 24 35 02' => array(
            'Id' => 'E5 54 6A 6A 24 24 35 02',
            'Label' => 'Sigma Macro 105mm F2.8 EX DG',
        ),
        'E6 41 3C 8E 2C 40 1C 02' => array(
            'Id' => 'E6 41 3C 8E 2C 40 1C 02',
            'Label' => 'Sigma 28-300mm F3.5-6.3 DG Macro',
        ),
        'E8 4C 44 44 14 14 DF 0E' => array(
            'Id' => 'E8 4C 44 44 14 14 DF 0E',
            'Label' => 'Tamron SP 35mm f/1.8 VC',
        ),
        'E9 48 27 3E 24 24 DF 0E' => array(
            'Id' => 'E9 48 27 3E 24 24 DF 0E',
            'Label' => 'Tamron SP 15-30mm f/2.8 Di VC USD (A012)',
        ),
        'E9 54 37 5C 24 24 1C 02' => array(
            'Id' => 'E9 54 37 5C 24 24 1C 02',
            'Label' => 'Sigma 24-70mm F2.8 EX DG Macro',
        ),
        'EA 40 29 8E 2C 40 DF 0E' => array(
            'Id' => 'EA 40 29 8E 2C 40 DF 0E',
            'Label' => 'Tamron AF 16-300mm f/3.5-6.3 Di II VC PZD (B016)',
        ),
        'EA 48 27 27 24 24 1C 02' => array(
            'Id' => 'EA 48 27 27 24 24 1C 02',
            'Label' => 'Sigma 15mm F2.8 EX Diagonal Fisheye',
        ),
        'EB 40 76 A6 38 40 DF 0E' => array(
            'Id' => 'EB 40 76 A6 38 40 DF 0E',
            'Label' => 'Tamron SP AF 150-600mm f/5-6.3 VC USD (A011)',
        ),
        'ED 40 2D 80 2C 40 4B 0E' => array(
            'Id' => 'ED 40 2D 80 2C 40 4B 0E',
            'Label' => 'Sigma 18-200mm F3.5-6.3 DC OS HSM',
        ),
        'EE 48 5C 80 24 24 4B 06' => array(
            'Id' => 'EE 48 5C 80 24 24 4B 06',
            'Label' => 'Sigma 70-200mm F2.8 EX APO DG Macro HSM II',
        ),
        'F0 38 1F 37 34 3C 4B 06' => array(
            'Id' => 'F0 38 1F 37 34 3C 4B 06',
            'Label' => 'Sigma 12-24mm F4.5-5.6 EX DG Aspherical HSM',
        ),
        'F0 3F 2D 8A 2C 40 DF 0E' => array(
            'Id' => 'F0 3F 2D 8A 2C 40 DF 0E',
            'Label' => 'Tamron AF 18-270mm f/3.5-6.3 Di II VC PZD (B008)',
        ),
        'F1 44 A0 A0 34 34 4B 02' => array(
            'Id' => 'F1 44 A0 A0 34 34 4B 02',
            'Label' => 'Sigma APO 500mm F4.5 EX DG HSM',
        ),
        'F1 47 5C 8E 30 3C DF 0E' => array(
            'Id' => 'F1 47 5C 8E 30 3C DF 0E',
            'Label' => 'Tamron SP 70-300mm f/4-5.6 Di VC USD (A005)',
        ),
        'F3 48 68 8E 30 30 4B 02' => array(
            'Id' => 'F3 48 68 8E 30 30 4B 02',
            'Label' => 'Sigma APO 100-300mm F4 EX IF HSM',
        ),
        'F3 54 2B 50 24 24 84 0E' => array(
            'Id' => 'F3 54 2B 50 24 24 84 0E',
            'Label' => 'Tamron SP AF 17-50mm f/2.8 XR Di II VC LD Aspherical (IF) (B005)',
        ),
        'F4 54 56 56 18 18 84 06' => array(
            'Id' => 'F4 54 56 56 18 18 84 06',
            'Label' => 'Tamron SP AF 60mm f/2.0 Di II Macro 1:1 (G005)',
        ),
        'F5 40 2C 8A 2C 40 40 0E' => array(
            'Id' => 'F5 40 2C 8A 2C 40 40 0E',
            'Label' => 'Tamron AF 18-270mm f/3.5-6.3 Di II VC LD Aspherical (IF) Macro (B003)',
        ),
        'F5 48 76 76 24 24 4B 06' => array(
            'Id' => 'F5 48 76 76 24 24 4B 06',
            'Label' => 'Sigma APO Macro 150mm F2.8 EX DG HSM',
        ),
        'F6 3F 18 37 2C 34 84 06' => array(
            'Id' => 'F6 3F 18 37 2C 34 84 06',
            'Label' => 'Tamron SP AF 10-24mm f/3.5-4.5 Di II LD Aspherical (IF) (B001)',
        ),
        'F6 3F 18 37 2C 34 DF 06' => array(
            'Id' => 'F6 3F 18 37 2C 34 DF 06',
            'Label' => 'Tamron SP AF 10-24mm f/3.5-4.5 Di II LD Aspherical (IF) (B001)',
        ),
        'F6 48 2D 50 24 24 4B 06' => array(
            'Id' => 'F6 48 2D 50 24 24 4B 06',
            'Label' => 'Sigma 18-50mm F2.8 EX DC Macro',
        ),
        'F7 53 5C 80 24 24 40 06' => array(
            'Id' => 'F7 53 5C 80 24 24 40 06',
            'Label' => 'Tamron SP AF 70-200mm f/2.8 Di LD (IF) Macro (A001)',
        ),
        'F7 53 5C 80 24 24 84 06' => array(
            'Id' => 'F7 53 5C 80 24 24 84 06',
            'Label' => 'Tamron SP AF 70-200mm f/2.8 Di LD (IF) Macro (A001)',
        ),
        'F8 54 3E 3E 0C 0C 4B 06' => array(
            'Id' => 'F8 54 3E 3E 0C 0C 4B 06',
            'Label' => 'Sigma 30mm F1.4 EX DC HSM',
        ),
        'F8 54 64 64 24 24 DF 06' => array(
            'Id' => 'F8 54 64 64 24 24 DF 06',
            'Label' => 'Tamron SP AF 90mm f/2.8 Di Macro 1:1 (272NII)',
        ),
        'F8 55 64 64 24 24 84 06' => array(
            'Id' => 'F8 55 64 64 24 24 84 06',
            'Label' => 'Tamron SP AF 90mm f/2.8 Di Macro 1:1 (272NII)',
        ),
        'F9 3C 19 31 30 3C 4B 06' => array(
            'Id' => 'F9 3C 19 31 30 3C 4B 06',
            'Label' => 'Sigma 10-20mm F4-5.6 EX DC HSM',
        ),
        'F9 40 3C 8E 2C 40 40 0E' => array(
            'Id' => 'F9 40 3C 8E 2C 40 40 0E',
            'Label' => 'Tamron AF 28-300mm f/3.5-6.3 XR Di VC LD Aspherical (IF) Macro (A20)',
        ),
        'FA 54 3C 5E 24 24 84 06' => array(
            'Id' => 'FA 54 3C 5E 24 24 84 06',
            'Label' => 'Tamron SP AF 28-75mm f/2.8 XR Di LD Aspherical (IF) Macro (A09NII)',
        ),
        'FA 54 3C 5E 24 24 DF 06' => array(
            'Id' => 'FA 54 3C 5E 24 24 DF 06',
            'Label' => 'Tamron SP AF 28-75mm f/2.8 XR Di LD Aspherical (IF) Macro (A09NII)',
        ),
        'FA 54 6E 8E 24 24 4B 02' => array(
            'Id' => 'FA 54 6E 8E 24 24 4B 02',
            'Label' => 'Sigma APO 120-300mm F2.8 EX DG HSM',
        ),
        'FB 54 2B 50 24 24 84 06' => array(
            'Id' => 'FB 54 2B 50 24 24 84 06',
            'Label' => 'Tamron SP AF 17-50mm f/2.8 XR Di II LD Aspherical (IF) (A16NII)',
        ),
        'FB 54 8E 8E 24 24 4B 02' => array(
            'Id' => 'FB 54 8E 8E 24 24 4B 02',
            'Label' => 'Sigma APO 300mm F2.8 EX DG HSM',
        ),
        'FC 40 2D 80 2C 40 DF 06' => array(
            'Id' => 'FC 40 2D 80 2C 40 DF 06',
            'Label' => 'Tamron AF 18-200mm f/3.5-6.3 XR Di II LD Aspherical (IF) Macro (A14NII)',
        ),
        'FD 47 50 76 24 24 4B 06' => array(
            'Id' => 'FD 47 50 76 24 24 4B 06',
            'Label' => 'Sigma 50-150mm F2.8 EX APO DC HSM II',
        ),
        'FE 47 00 00 24 24 4B 06' => array(
            'Id' => 'FE 47 00 00 24 24 4B 06',
            'Label' => 'Sigma 4.5mm F2.8 EX DC HSM Circular Fisheye',
        ),
        'FE 48 37 5C 24 24 DF 0E' => array(
            'Id' => 'FE 48 37 5C 24 24 DF 0E',
            'Label' => 'Tamron SP 24-70mm f/2.8 Di VC USD (A007)',
        ),
        'FE 53 5C 80 24 24 84 06' => array(
            'Id' => 'FE 53 5C 80 24 24 84 06',
            'Label' => 'Tamron SP AF 70-200mm f/2.8 Di LD (IF) Macro (A001)',
        ),
        'FE 54 5C 80 24 24 DF 0E' => array(
            'Id' => 'FE 54 5C 80 24 24 DF 0E',
            'Label' => 'Tamron SP 70-200mm f/2.8 Di VC USD (A009)',
        ),
        'FE 54 64 64 24 24 DF 0E' => array(
            'Id' => 'FE 54 64 64 24 24 DF 0E',
            'Label' => 'Tamron SP 90mm f/2.8 Di VC USD Macro 1:1 (F004)',
        ),
        'FF 40 2D 80 2C 40 4B 06' => array(
            'Id' => 'FF 40 2D 80 2C 40 4B 06',
            'Label' => 'Sigma 18-200mm F3.5-6.3 DC',
        ),
    );

}
