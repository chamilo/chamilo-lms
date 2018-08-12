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
class LensType extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'LensType';

    protected $FullName = 'mixed';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Lens Type';

    protected $flag_Permanent = true;

    protected $MaxLength = 'mixed';

    protected $Values = array(
        '0 0' => array(
            'Id' => '0 0',
            'Label' => 'M-42 or No Lens',
        ),
        '1 0' => array(
            'Id' => '1 0',
            'Label' => 'K or M Lens',
        ),
        '2 0' => array(
            'Id' => '2 0',
            'Label' => 'A Series Lens',
        ),
        '3 0' => array(
            'Id' => '3 0',
            'Label' => 'Sigma',
        ),
        '3 17' => array(
            'Id' => '3 17',
            'Label' => 'smc PENTAX-FA SOFT 85mm F2.8',
        ),
        '3 18' => array(
            'Id' => '3 18',
            'Label' => 'smc PENTAX-F 1.7X AF ADAPTER',
        ),
        '3 19' => array(
            'Id' => '3 19',
            'Label' => 'smc PENTAX-F 24-50mm F4',
        ),
        '3 20' => array(
            'Id' => '3 20',
            'Label' => 'smc PENTAX-F 35-80mm F4-5.6',
        ),
        '3 21' => array(
            'Id' => '3 21',
            'Label' => 'smc PENTAX-F 80-200mm F4.7-5.6',
        ),
        '3 22' => array(
            'Id' => '3 22',
            'Label' => 'smc PENTAX-F FISH-EYE 17-28mm F3.5-4.5',
        ),
        '3 23' => array(
            'Id' => '3 23',
            'Label' => 'smc PENTAX-F 100-300mm F4.5-5.6 or Sigma Lens',
        ),
        '3 23.1' => array(
            'Id' => '3 23.1',
            'Label' => 'Sigma AF 28-300mm F3.5-5.6 DL IF',
        ),
        '3 23.2' => array(
            'Id' => '3 23.2',
            'Label' => 'Sigma AF 28-300mm F3.5-6.3 DG IF Macro',
        ),
        '3 23.3' => array(
            'Id' => '3 23.3',
            'Label' => 'Tokina 80-200mm F2.8 ATX-Pro',
        ),
        '3 24' => array(
            'Id' => '3 24',
            'Label' => 'smc PENTAX-F 35-135mm F3.5-4.5',
        ),
        '3 25' => array(
            'Id' => '3 25',
            'Label' => 'smc PENTAX-F 35-105mm F4-5.6 or Sigma or Tokina Lens',
        ),
        '3 25.1' => array(
            'Id' => '3 25.1',
            'Label' => 'Sigma AF 28-300mm F3.5-5.6 DL IF',
        ),
        '3 25.2' => array(
            'Id' => '3 25.2',
            'Label' => 'Sigma 55-200mm F4-5.6 DC',
        ),
        '3 25.3' => array(
            'Id' => '3 25.3',
            'Label' => 'Sigma AF 28-300mm F3.5-6.3 DL IF',
        ),
        '3 25.4' => array(
            'Id' => '3 25.4',
            'Label' => 'Sigma AF 28-300mm F3.5-6.3 DG IF Macro',
        ),
        '3 25.5' => array(
            'Id' => '3 25.5',
            'Label' => 'Tokina 80-200mm F2.8 ATX-Pro',
        ),
        '3 26' => array(
            'Id' => '3 26',
            'Label' => 'smc PENTAX-F* 250-600mm F5.6 ED[IF]',
        ),
        '3 27' => array(
            'Id' => '3 27',
            'Label' => 'smc PENTAX-F 28-80mm F3.5-4.5 or Tokina Lens',
        ),
        '3 27.1' => array(
            'Id' => '3 27.1',
            'Label' => 'Tokina AT-X Pro AF 28-70mm F2.6-2.8',
        ),
        '3 28' => array(
            'Id' => '3 28',
            'Label' => 'smc PENTAX-F 35-70mm F3.5-4.5 or Tokina Lens',
        ),
        '3 28.1' => array(
            'Id' => '3 28.1',
            'Label' => 'Tokina 19-35mm F3.5-4.5 AF',
        ),
        '3 28.2' => array(
            'Id' => '3 28.2',
            'Label' => 'Tokina AT-X AF 400mm F5.6',
        ),
        '3 29' => array(
            'Id' => '3 29',
            'Label' => 'PENTAX-F 28-80mm F3.5-4.5 or Sigma or Tokina Lens',
        ),
        '3 29.1' => array(
            'Id' => '3 29.1',
            'Label' => 'Sigma AF 18-125mm F3.5-5.6 DC',
        ),
        '3 29.2' => array(
            'Id' => '3 29.2',
            'Label' => 'Tokina AT-X PRO 28-70mm F2.6-2.8',
        ),
        '3 30' => array(
            'Id' => '3 30',
            'Label' => 'PENTAX-F 70-200mm F4-5.6',
        ),
        '3 31' => array(
            'Id' => '3 31',
            'Label' => 'smc PENTAX-F 70-210mm F4-5.6 or Tokina or Takumar Lens',
        ),
        '3 31.1' => array(
            'Id' => '3 31.1',
            'Label' => 'Tokina AF 730 75-300mm F4.5-5.6',
        ),
        '3 31.2' => array(
            'Id' => '3 31.2',
            'Label' => 'Takumar-F 70-210mm F4-5.6',
        ),
        '3 32' => array(
            'Id' => '3 32',
            'Label' => 'smc PENTAX-F 50mm F1.4',
        ),
        '3 33' => array(
            'Id' => '3 33',
            'Label' => 'smc PENTAX-F 50mm F1.7',
        ),
        '3 34' => array(
            'Id' => '3 34',
            'Label' => 'smc PENTAX-F 135mm F2.8 [IF]',
        ),
        '3 35' => array(
            'Id' => '3 35',
            'Label' => 'smc PENTAX-F 28mm F2.8',
        ),
        '3 36' => array(
            'Id' => '3 36',
            'Label' => 'Sigma 20mm F1.8 EX DG Aspherical RF',
        ),
        '3 38' => array(
            'Id' => '3 38',
            'Label' => 'smc PENTAX-F* 300mm F4.5 ED[IF]',
        ),
        '3 39' => array(
            'Id' => '3 39',
            'Label' => 'smc PENTAX-F* 600mm F4 ED[IF]',
        ),
        '3 40' => array(
            'Id' => '3 40',
            'Label' => 'smc PENTAX-F Macro 100mm F2.8',
        ),
        '3 41' => array(
            'Id' => '3 41',
            'Label' => 'smc PENTAX-F Macro 50mm F2.8 or Sigma Lens',
        ),
        '3 41.1' => array(
            'Id' => '3 41.1',
            'Label' => 'Sigma 50mm F2.8 Macro',
        ),
        '3 42' => array(
            'Id' => '3 42',
            'Label' => 'Sigma 300mm F2.8 EX DG APO IF',
        ),
        '3 44' => array(
            'Id' => '3 44',
            'Label' => 'Sigma or Tamron Lens (3 44)',
        ),
        '3 44.1' => array(
            'Id' => '3 44.1',
            'Label' => 'Sigma AF 10-20mm F4-5.6 EX DC',
        ),
        '3 44.2' => array(
            'Id' => '3 44.2',
            'Label' => 'Sigma 12-24mm F4.5-5.6 EX DG',
        ),
        '3 44.3' => array(
            'Id' => '3 44.3',
            'Label' => 'Sigma 17-70mm F2.8-4.5 DC Macro',
        ),
        '3 44.4' => array(
            'Id' => '3 44.4',
            'Label' => 'Sigma 18-50mm F3.5-5.6 DC',
        ),
        '3 44.5' => array(
            'Id' => '3 44.5',
            'Label' => 'Tamron 35-90mm F4 AF',
        ),
        '3 46' => array(
            'Id' => '3 46',
            'Label' => 'Sigma or Samsung Lens (3 46)',
        ),
        '3 46.1' => array(
            'Id' => '3 46.1',
            'Label' => 'Sigma APO 70-200mm F2.8 EX',
        ),
        '3 46.2' => array(
            'Id' => '3 46.2',
            'Label' => 'Sigma EX APO 100-300mm F4 IF',
        ),
        '3 46.3' => array(
            'Id' => '3 46.3',
            'Label' => 'Samsung/Schneider D-XENON 50-200mm F4-5.6 ED',
        ),
        '3 50' => array(
            'Id' => '3 50',
            'Label' => 'smc PENTAX-FA 28-70mm F4 AL',
        ),
        '3 51' => array(
            'Id' => '3 51',
            'Label' => 'Sigma 28mm F1.8 EX DG Aspherical Macro',
        ),
        '3 52' => array(
            'Id' => '3 52',
            'Label' => 'smc PENTAX-FA 28-200mm F3.8-5.6 AL[IF] or Tamron Lens',
        ),
        '3 52.1' => array(
            'Id' => '3 52.1',
            'Label' => 'Tamron AF LD 28-200mm F3.8-5.6 [IF] Aspherical (171D)',
        ),
        '3 53' => array(
            'Id' => '3 53',
            'Label' => 'smc PENTAX-FA 28-80mm F3.5-5.6 AL',
        ),
        '3 247' => array(
            'Id' => '3 247',
            'Label' => 'smc PENTAX-DA FISH-EYE 10-17mm F3.5-4.5 ED[IF]',
        ),
        '3 248' => array(
            'Id' => '3 248',
            'Label' => 'smc PENTAX-DA 12-24mm F4 ED AL[IF]',
        ),
        '3 250' => array(
            'Id' => '3 250',
            'Label' => 'smc PENTAX-DA 50-200mm F4-5.6 ED',
        ),
        '3 251' => array(
            'Id' => '3 251',
            'Label' => 'smc PENTAX-DA 40mm F2.8 Limited',
        ),
        '3 252' => array(
            'Id' => '3 252',
            'Label' => 'smc PENTAX-DA 18-55mm F3.5-5.6 AL',
        ),
        '3 253' => array(
            'Id' => '3 253',
            'Label' => 'smc PENTAX-DA 14mm F2.8 ED[IF]',
        ),
        '3 254' => array(
            'Id' => '3 254',
            'Label' => 'smc PENTAX-DA 16-45mm F4 ED AL',
        ),
        '3 255' => array(
            'Id' => '3 255',
            'Label' => 'Sigma Lens (3 255)',
        ),
        '3 255.1' => array(
            'Id' => '3 255.1',
            'Label' => 'Sigma 18-200mm F3.5-6.3 DC',
        ),
        '3 255.2' => array(
            'Id' => '3 255.2',
            'Label' => 'Sigma DL-II 35-80mm F4-5.6',
        ),
        '3 255.3' => array(
            'Id' => '3 255.3',
            'Label' => 'Sigma DL Zoom 75-300mm F4-5.6',
        ),
        '3 255.4' => array(
            'Id' => '3 255.4',
            'Label' => 'Sigma DF EX Aspherical 28-70mm F2.8',
        ),
        '3 255.5' => array(
            'Id' => '3 255.5',
            'Label' => 'Sigma AF Tele 400mm F5.6 Multi-coated',
        ),
        '3 255.6' => array(
            'Id' => '3 255.6',
            'Label' => 'Sigma 24-60mm F2.8 EX DG',
        ),
        '3 255.7' => array(
            'Id' => '3 255.7',
            'Label' => 'Sigma 70-300mm F4-5.6 Macro',
        ),
        '3 255.8' => array(
            'Id' => '3 255.8',
            'Label' => 'Sigma 55-200mm F4-5.6 DC',
        ),
        '3 255.9' => array(
            'Id' => '3 255.9',
            'Label' => 'Sigma 18-50mm F2.8 EX DC',
        ),
        '4 1' => array(
            'Id' => '4 1',
            'Label' => 'smc PENTAX-FA SOFT 28mm F2.8',
        ),
        '4 2' => array(
            'Id' => '4 2',
            'Label' => 'smc PENTAX-FA 80-320mm F4.5-5.6',
        ),
        '4 3' => array(
            'Id' => '4 3',
            'Label' => 'smc PENTAX-FA 43mm F1.9 Limited',
        ),
        '4 6' => array(
            'Id' => '4 6',
            'Label' => 'smc PENTAX-FA 35-80mm F4-5.6',
        ),
        '4 12' => array(
            'Id' => '4 12',
            'Label' => 'smc PENTAX-FA 50mm F1.4',
        ),
        '4 15' => array(
            'Id' => '4 15',
            'Label' => 'smc PENTAX-FA 28-105mm F4-5.6 [IF]',
        ),
        '4 16' => array(
            'Id' => '4 16',
            'Label' => 'Tamron AF 80-210mm F4-5.6 (178D)',
        ),
        '4 19' => array(
            'Id' => '4 19',
            'Label' => 'Tamron SP AF 90mm F2.8 (172E)',
        ),
        '4 20' => array(
            'Id' => '4 20',
            'Label' => 'smc PENTAX-FA 28-80mm F3.5-5.6',
        ),
        '4 21' => array(
            'Id' => '4 21',
            'Label' => 'Cosina AF 100-300mm F5.6-6.7',
        ),
        '4 22' => array(
            'Id' => '4 22',
            'Label' => 'Tokina 28-80mm F3.5-5.6',
        ),
        '4 23' => array(
            'Id' => '4 23',
            'Label' => 'smc PENTAX-FA 20-35mm F4 AL',
        ),
        '4 24' => array(
            'Id' => '4 24',
            'Label' => 'smc PENTAX-FA 77mm F1.8 Limited',
        ),
        '4 25' => array(
            'Id' => '4 25',
            'Label' => 'Tamron SP AF 14mm F2.8',
        ),
        '4 26' => array(
            'Id' => '4 26',
            'Label' => 'smc PENTAX-FA Macro 100mm F3.5 or Cosina Lens',
        ),
        '4 26.1' => array(
            'Id' => '4 26.1',
            'Label' => 'Cosina 100mm F3.5 Macro',
        ),
        '4 27' => array(
            'Id' => '4 27',
            'Label' => 'Tamron AF 28-300mm F3.5-6.3 LD Aspherical[IF] Macro (185D/285D)',
        ),
        '4 28' => array(
            'Id' => '4 28',
            'Label' => 'smc PENTAX-FA 35mm F2 AL',
        ),
        '4 29' => array(
            'Id' => '4 29',
            'Label' => 'Tamron AF 28-200mm F3.8-5.6 LD Super II Macro (371D)',
        ),
        '4 34' => array(
            'Id' => '4 34',
            'Label' => 'smc PENTAX-FA 24-90mm F3.5-4.5 AL[IF]',
        ),
        '4 35' => array(
            'Id' => '4 35',
            'Label' => 'smc PENTAX-FA 100-300mm F4.7-5.8',
        ),
        '4 36' => array(
            'Id' => '4 36',
            'Label' => 'Tamron AF 70-300mm F4-5.6 LD Macro 1:2',
        ),
        '4 37' => array(
            'Id' => '4 37',
            'Label' => 'Tamron SP AF 24-135mm F3.5-5.6 AD AL (190D)',
        ),
        '4 38' => array(
            'Id' => '4 38',
            'Label' => 'smc PENTAX-FA 28-105mm F3.2-4.5 AL[IF]',
        ),
        '4 39' => array(
            'Id' => '4 39',
            'Label' => 'smc PENTAX-FA 31mm F1.8 AL Limited',
        ),
        '4 41' => array(
            'Id' => '4 41',
            'Label' => 'Tamron AF 28-200mm Super Zoom F3.8-5.6 Aspherical XR [IF] Macro (A03)',
        ),
        '4 43' => array(
            'Id' => '4 43',
            'Label' => 'smc PENTAX-FA 28-90mm F3.5-5.6',
        ),
        '4 44' => array(
            'Id' => '4 44',
            'Label' => 'smc PENTAX-FA J 75-300mm F4.5-5.8 AL',
        ),
        '4 45' => array(
            'Id' => '4 45',
            'Label' => 'Tamron Lens (4 45)',
        ),
        '4 45.1' => array(
            'Id' => '4 45.1',
            'Label' => 'Tamron 28-300mm F3.5-6.3 Ultra zoom XR',
        ),
        '4 45.2' => array(
            'Id' => '4 45.2',
            'Label' => 'Tamron AF 28-300mm F3.5-6.3 XR Di LD Aspherical [IF] Macro',
        ),
        '4 46' => array(
            'Id' => '4 46',
            'Label' => 'smc PENTAX-FA J 28-80mm F3.5-5.6 AL',
        ),
        '4 47' => array(
            'Id' => '4 47',
            'Label' => 'smc PENTAX-FA J 18-35mm F4-5.6 AL',
        ),
        '4 49' => array(
            'Id' => '4 49',
            'Label' => 'Tamron SP AF 28-75mm F2.8 XR Di LD Aspherical [IF] Macro',
        ),
        '4 51' => array(
            'Id' => '4 51',
            'Label' => 'smc PENTAX-D FA 50mm F2.8 Macro',
        ),
        '4 52' => array(
            'Id' => '4 52',
            'Label' => 'smc PENTAX-D FA 100mm F2.8 Macro',
        ),
        '4 55' => array(
            'Id' => '4 55',
            'Label' => 'Samsung/Schneider D-XENOGON 35mm F2',
        ),
        '4 56' => array(
            'Id' => '4 56',
            'Label' => 'Samsung/Schneider D-XENON 100mm F2.8 Macro',
        ),
        '4 75' => array(
            'Id' => '4 75',
            'Label' => 'Tamron SP AF 70-200mm F2.8 Di LD [IF] Macro (A001)',
        ),
        '4 214' => array(
            'Id' => '4 214',
            'Label' => 'smc PENTAX-DA 35mm F2.4 AL',
        ),
        '4 229' => array(
            'Id' => '4 229',
            'Label' => 'smc PENTAX-DA 18-55mm F3.5-5.6 AL II',
        ),
        '4 230' => array(
            'Id' => '4 230',
            'Label' => 'Tamron SP AF 17-50mm F2.8 XR Di II',
        ),
        '4 231' => array(
            'Id' => '4 231',
            'Label' => 'smc PENTAX-DA 18-250mm F3.5-6.3 ED AL [IF]',
        ),
        '4 237' => array(
            'Id' => '4 237',
            'Label' => 'Samsung/Schneider D-XENOGON 10-17mm F3.5-4.5',
        ),
        '4 239' => array(
            'Id' => '4 239',
            'Label' => 'Samsung/Schneider D-XENON 12-24mm F4 ED AL [IF]',
        ),
        '4 242' => array(
            'Id' => '4 242',
            'Label' => 'smc PENTAX-DA* 16-50mm F2.8 ED AL [IF] SDM (SDM unused)',
        ),
        '4 243' => array(
            'Id' => '4 243',
            'Label' => 'smc PENTAX-DA 70mm F2.4 Limited',
        ),
        '4 244' => array(
            'Id' => '4 244',
            'Label' => 'smc PENTAX-DA 21mm F3.2 AL Limited',
        ),
        '4 245' => array(
            'Id' => '4 245',
            'Label' => 'Samsung/Schneider D-XENON 50-200mm F4-5.6',
        ),
        '4 246' => array(
            'Id' => '4 246',
            'Label' => 'Samsung/Schneider D-XENON 18-55mm F3.5-5.6',
        ),
        '4 247' => array(
            'Id' => '4 247',
            'Label' => 'smc PENTAX-DA FISH-EYE 10-17mm F3.5-4.5 ED[IF]',
        ),
        '4 248' => array(
            'Id' => '4 248',
            'Label' => 'smc PENTAX-DA 12-24mm F4 ED AL [IF]',
        ),
        '4 249' => array(
            'Id' => '4 249',
            'Label' => 'Tamron XR DiII 18-200mm F3.5-6.3 (A14)',
        ),
        '4 250' => array(
            'Id' => '4 250',
            'Label' => 'smc PENTAX-DA 50-200mm F4-5.6 ED',
        ),
        '4 251' => array(
            'Id' => '4 251',
            'Label' => 'smc PENTAX-DA 40mm F2.8 Limited',
        ),
        '4 252' => array(
            'Id' => '4 252',
            'Label' => 'smc PENTAX-DA 18-55mm F3.5-5.6 AL',
        ),
        '4 253' => array(
            'Id' => '4 253',
            'Label' => 'smc PENTAX-DA 14mm F2.8 ED[IF]',
        ),
        '4 254' => array(
            'Id' => '4 254',
            'Label' => 'smc PENTAX-DA 16-45mm F4 ED AL',
        ),
        '5 1' => array(
            'Id' => '5 1',
            'Label' => 'smc PENTAX-FA* 24mm F2 AL[IF]',
        ),
        '5 2' => array(
            'Id' => '5 2',
            'Label' => 'smc PENTAX-FA 28mm F2.8 AL',
        ),
        '5 3' => array(
            'Id' => '5 3',
            'Label' => 'smc PENTAX-FA 50mm F1.7',
        ),
        '5 4' => array(
            'Id' => '5 4',
            'Label' => 'smc PENTAX-FA 50mm F1.4',
        ),
        '5 5' => array(
            'Id' => '5 5',
            'Label' => 'smc PENTAX-FA* 600mm F4 ED[IF]',
        ),
        '5 6' => array(
            'Id' => '5 6',
            'Label' => 'smc PENTAX-FA* 300mm F4.5 ED[IF]',
        ),
        '5 7' => array(
            'Id' => '5 7',
            'Label' => 'smc PENTAX-FA 135mm F2.8 [IF]',
        ),
        '5 8' => array(
            'Id' => '5 8',
            'Label' => 'smc PENTAX-FA Macro 50mm F2.8',
        ),
        '5 9' => array(
            'Id' => '5 9',
            'Label' => 'smc PENTAX-FA Macro 100mm F2.8',
        ),
        '5 10' => array(
            'Id' => '5 10',
            'Label' => 'smc PENTAX-FA* 85mm F1.4 [IF]',
        ),
        '5 11' => array(
            'Id' => '5 11',
            'Label' => 'smc PENTAX-FA* 200mm F2.8 ED[IF]',
        ),
        '5 12' => array(
            'Id' => '5 12',
            'Label' => 'smc PENTAX-FA 28-80mm F3.5-4.7',
        ),
        '5 13' => array(
            'Id' => '5 13',
            'Label' => 'smc PENTAX-FA 70-200mm F4-5.6',
        ),
        '5 14' => array(
            'Id' => '5 14',
            'Label' => 'smc PENTAX-FA* 250-600mm F5.6 ED[IF]',
        ),
        '5 15' => array(
            'Id' => '5 15',
            'Label' => 'smc PENTAX-FA 28-105mm F4-5.6',
        ),
        '5 16' => array(
            'Id' => '5 16',
            'Label' => 'smc PENTAX-FA 100-300mm F4.5-5.6',
        ),
        '5 98' => array(
            'Id' => '5 98',
            'Label' => 'smc PENTAX-FA 100-300mm F4.5-5.6',
        ),
        '6 1' => array(
            'Id' => '6 1',
            'Label' => 'smc PENTAX-FA* 85mm F1.4 [IF]',
        ),
        '6 2' => array(
            'Id' => '6 2',
            'Label' => 'smc PENTAX-FA* 200mm F2.8 ED[IF]',
        ),
        '6 3' => array(
            'Id' => '6 3',
            'Label' => 'smc PENTAX-FA* 300mm F2.8 ED[IF]',
        ),
        '6 4' => array(
            'Id' => '6 4',
            'Label' => 'smc PENTAX-FA* 28-70mm F2.8 AL',
        ),
        '6 5' => array(
            'Id' => '6 5',
            'Label' => 'smc PENTAX-FA* 80-200mm F2.8 ED[IF]',
        ),
        '6 6' => array(
            'Id' => '6 6',
            'Label' => 'smc PENTAX-FA* 28-70mm F2.8 AL',
        ),
        '6 7' => array(
            'Id' => '6 7',
            'Label' => 'smc PENTAX-FA* 80-200mm F2.8 ED[IF]',
        ),
        '6 8' => array(
            'Id' => '6 8',
            'Label' => 'smc PENTAX-FA 28-70mm F4AL',
        ),
        '6 9' => array(
            'Id' => '6 9',
            'Label' => 'smc PENTAX-FA 20mm F2.8',
        ),
        '6 10' => array(
            'Id' => '6 10',
            'Label' => 'smc PENTAX-FA* 400mm F5.6 ED[IF]',
        ),
        '6 13' => array(
            'Id' => '6 13',
            'Label' => 'smc PENTAX-FA* 400mm F5.6 ED[IF]',
        ),
        '6 14' => array(
            'Id' => '6 14',
            'Label' => 'smc PENTAX-FA* Macro 200mm F4 ED[IF]',
        ),
        '7 0' => array(
            'Id' => '7 0',
            'Label' => 'smc PENTAX-DA 21mm F3.2 AL Limited',
        ),
        '7 58' => array(
            'Id' => '7 58',
            'Label' => 'smc PENTAX-D FA Macro 100mm F2.8 WR',
        ),
        '7 75' => array(
            'Id' => '7 75',
            'Label' => 'Tamron SP AF 70-200mm F2.8 Di LD [IF] Macro (A001)',
        ),
        '7 201' => array(
            'Id' => '7 201',
            'Label' => 'smc Pentax-DA L 50-200mm F4-5.6 ED WR',
        ),
        '7 202' => array(
            'Id' => '7 202',
            'Label' => 'smc PENTAX-DA L 18-55mm F3.5-5.6 AL WR',
        ),
        '7 203' => array(
            'Id' => '7 203',
            'Label' => 'HD PENTAX-DA 55-300mm F4-5.8 ED WR',
        ),
        '7 204' => array(
            'Id' => '7 204',
            'Label' => 'HD PENTAX-DA 15mm F4 ED AL Limited',
        ),
        '7 205' => array(
            'Id' => '7 205',
            'Label' => 'HD PENTAX-DA 35mm F2.8 Macro Limited',
        ),
        '7 206' => array(
            'Id' => '7 206',
            'Label' => 'HD PENTAX-DA 70mm F2.4 Limited',
        ),
        '7 207' => array(
            'Id' => '7 207',
            'Label' => 'HD PENTAX-DA 21mm F3.2 ED AL Limited',
        ),
        '7 208' => array(
            'Id' => '7 208',
            'Label' => 'HD PENTAX-DA 40mm F2.8 Limited',
        ),
        '7 212' => array(
            'Id' => '7 212',
            'Label' => 'smc PENTAX-DA 50mm F1.8',
        ),
        '7 213' => array(
            'Id' => '7 213',
            'Label' => 'smc PENTAX-DA 40mm F2.8 XS',
        ),
        '7 214' => array(
            'Id' => '7 214',
            'Label' => 'smc PENTAX-DA 35mm F2.4 AL',
        ),
        '7 216' => array(
            'Id' => '7 216',
            'Label' => 'smc PENTAX-DA L 55-300mm F4-5.8 ED',
        ),
        '7 217' => array(
            'Id' => '7 217',
            'Label' => 'smc PENTAX-DA 50-200mm F4-5.6 ED WR',
        ),
        '7 218' => array(
            'Id' => '7 218',
            'Label' => 'smc PENTAX-DA 18-55mm F3.5-5.6 AL WR',
        ),
        '7 220' => array(
            'Id' => '7 220',
            'Label' => 'Tamron SP AF 10-24mm F3.5-4.5 Di II LD Aspherical [IF]',
        ),
        '7 221' => array(
            'Id' => '7 221',
            'Label' => 'smc PENTAX-DA L 50-200mm F4-5.6 ED',
        ),
        '7 222' => array(
            'Id' => '7 222',
            'Label' => 'smc PENTAX-DA L 18-55mm F3.5-5.6',
        ),
        '7 223' => array(
            'Id' => '7 223',
            'Label' => 'Samsung/Schneider D-XENON 18-55mm F3.5-5.6 II',
        ),
        '7 224' => array(
            'Id' => '7 224',
            'Label' => 'smc PENTAX-DA 15mm F4 ED AL Limited',
        ),
        '7 225' => array(
            'Id' => '7 225',
            'Label' => 'Samsung/Schneider D-XENON 18-250mm F3.5-6.3',
        ),
        '7 226' => array(
            'Id' => '7 226',
            'Label' => 'smc PENTAX-DA* 55mm F1.4 SDM (SDM unused)',
        ),
        '7 227' => array(
            'Id' => '7 227',
            'Label' => 'smc PENTAX-DA* 60-250mm F4 [IF] SDM (SDM unused)',
        ),
        '7 228' => array(
            'Id' => '7 228',
            'Label' => 'Samsung 16-45mm F4 ED',
        ),
        '7 229' => array(
            'Id' => '7 229',
            'Label' => 'smc PENTAX-DA 18-55mm F3.5-5.6 AL II',
        ),
        '7 230' => array(
            'Id' => '7 230',
            'Label' => 'Tamron AF 17-50mm F2.8 XR Di-II LD (Model A16)',
        ),
        '7 231' => array(
            'Id' => '7 231',
            'Label' => 'smc PENTAX-DA 18-250mm F3.5-6.3 ED AL [IF]',
        ),
        '7 233' => array(
            'Id' => '7 233',
            'Label' => 'smc PENTAX-DA 35mm F2.8 Macro Limited',
        ),
        '7 234' => array(
            'Id' => '7 234',
            'Label' => 'smc PENTAX-DA* 300mm F4 ED [IF] SDM (SDM unused)',
        ),
        '7 235' => array(
            'Id' => '7 235',
            'Label' => 'smc PENTAX-DA* 200mm F2.8 ED [IF] SDM (SDM unused)',
        ),
        '7 236' => array(
            'Id' => '7 236',
            'Label' => 'smc PENTAX-DA 55-300mm F4-5.8 ED',
        ),
        '7 238' => array(
            'Id' => '7 238',
            'Label' => 'Tamron AF 18-250mm F3.5-6.3 Di II LD Aspherical [IF] Macro',
        ),
        '7 241' => array(
            'Id' => '7 241',
            'Label' => 'smc PENTAX-DA* 50-135mm F2.8 ED [IF] SDM (SDM unused)',
        ),
        '7 242' => array(
            'Id' => '7 242',
            'Label' => 'smc PENTAX-DA* 16-50mm F2.8 ED AL [IF] SDM (SDM unused)',
        ),
        '7 243' => array(
            'Id' => '7 243',
            'Label' => 'smc PENTAX-DA 70mm F2.4 Limited',
        ),
        '7 244' => array(
            'Id' => '7 244',
            'Label' => 'smc PENTAX-DA 21mm F3.2 AL Limited',
        ),
        '8 0' => array(
            'Id' => '8 0',
            'Label' => 'Sigma 50-150mm F2.8 II APO EX DC HSM',
        ),
        '8 3' => array(
            'Id' => '8 3',
            'Label' => 'Sigma AF 18-125mm F3.5-5.6 DC',
        ),
        '8 4' => array(
            'Id' => '8 4',
            'Label' => 'Sigma 50mm F1.4 EX DG HSM',
        ),
        '8 7' => array(
            'Id' => '8 7',
            'Label' => 'Sigma 24-70mm F2.8 IF EX DG HSM',
        ),
        '8 8' => array(
            'Id' => '8 8',
            'Label' => 'Sigma 18-250mm F3.5-6.3 DC OS HSM',
        ),
        '8 11' => array(
            'Id' => '8 11',
            'Label' => 'Sigma 10-20mm F3.5 EX DC HSM',
        ),
        '8 12' => array(
            'Id' => '8 12',
            'Label' => 'Sigma 70-300mm F4-5.6 DG OS',
        ),
        '8 13' => array(
            'Id' => '8 13',
            'Label' => 'Sigma 120-400mm F4.5-5.6 APO DG OS HSM',
        ),
        '8 14' => array(
            'Id' => '8 14',
            'Label' => 'Sigma 17-70mm F2.8-4.0 DC Macro OS HSM',
        ),
        '8 15' => array(
            'Id' => '8 15',
            'Label' => 'Sigma 150-500mm F5-6.3 APO DG OS HSM',
        ),
        '8 16' => array(
            'Id' => '8 16',
            'Label' => 'Sigma 70-200mm F2.8 EX DG Macro HSM II',
        ),
        '8 17' => array(
            'Id' => '8 17',
            'Label' => 'Sigma 50-500mm F4.5-6.3 DG OS HSM',
        ),
        '8 18' => array(
            'Id' => '8 18',
            'Label' => 'Sigma 8-16mm F4.5-5.6 DC HSM',
        ),
        '8 21' => array(
            'Id' => '8 21',
            'Label' => 'Sigma 17-50mm F2.8 EX DC OS HSM',
        ),
        '8 22' => array(
            'Id' => '8 22',
            'Label' => 'Sigma 85mm F1.4 EX DG HSM',
        ),
        '8 23' => array(
            'Id' => '8 23',
            'Label' => 'Sigma 70-200mm F2.8 APO EX DG OS HSM',
        ),
        '8 25' => array(
            'Id' => '8 25',
            'Label' => 'Sigma 17-50mm F2.8 EX DC HSM',
        ),
        '8 27' => array(
            'Id' => '8 27',
            'Label' => 'Sigma 18-200mm F3.5-6.3 II DC HSM',
        ),
        '8 28' => array(
            'Id' => '8 28',
            'Label' => 'Sigma 18-250mm F3.5-6.3 DC Macro HSM',
        ),
        '8 29' => array(
            'Id' => '8 29',
            'Label' => 'Sigma 35mm F1.4 DG HSM',
        ),
        '8 30' => array(
            'Id' => '8 30',
            'Label' => 'Sigma 17-70mm F2.8-4 DC Macro HSM Contemporary',
        ),
        '8 31' => array(
            'Id' => '8 31',
            'Label' => 'Sigma 18-35mm F1.8 DC HSM',
        ),
        '8 32' => array(
            'Id' => '8 32',
            'Label' => 'Sigma 30mm F1.4 DC HSM | A',
        ),
        '8 34' => array(
            'Id' => '8 34',
            'Label' => 'Sigma 18-300mm F3.5-6.3 DC Macro HSM',
        ),
        '8 59' => array(
            'Id' => '8 59',
            'Label' => 'HD PENTAX-D FA 150-450mm F4.5-5.6 ED DC AW',
        ),
        '8 60' => array(
            'Id' => '8 60',
            'Label' => 'HD PENTAX-D FA* 70-200mm F2.8 ED DC AW',
        ),
        '8 62' => array(
            'Id' => '8 62',
            'Label' => 'HD PENTAX-D FA 24-70mm F2.8 ED SDM WR',
        ),
        '8 198' => array(
            'Id' => '8 198',
            'Label' => 'smc PENTAX-DA L 18-50mm F4-5.6 DC WR RE',
        ),
        '8 199' => array(
            'Id' => '8 199',
            'Label' => 'HD PENTAX-DA 18-50mm F4-5.6 DC WR RE',
        ),
        '8 200' => array(
            'Id' => '8 200',
            'Label' => 'HD PENTAX-DA 16-85mm F3.5-5.6 ED DC WR',
        ),
        '8 209' => array(
            'Id' => '8 209',
            'Label' => 'HD PENTAX-DA 20-40mm F2.8-4 ED Limited DC WR',
        ),
        '8 210' => array(
            'Id' => '8 210',
            'Label' => 'smc PENTAX-DA 18-270mm F3.5-6.3 ED SDM',
        ),
        '8 211' => array(
            'Id' => '8 211',
            'Label' => 'HD PENTAX-DA 560mm F5.6 ED AW',
        ),
        '8 215' => array(
            'Id' => '8 215',
            'Label' => 'smc PENTAX-DA 18-135mm F3.5-5.6 ED AL [IF] DC WR',
        ),
        '8 226' => array(
            'Id' => '8 226',
            'Label' => 'smc PENTAX-DA* 55mm F1.4 SDM',
        ),
        '8 227' => array(
            'Id' => '8 227',
            'Label' => 'smc PENTAX-DA* 60-250mm F4 [IF] SDM',
        ),
        '8 232' => array(
            'Id' => '8 232',
            'Label' => 'smc PENTAX-DA 17-70mm F4 AL [IF] SDM',
        ),
        '8 234' => array(
            'Id' => '8 234',
            'Label' => 'smc PENTAX-DA* 300mm F4 ED [IF] SDM',
        ),
        '8 235' => array(
            'Id' => '8 235',
            'Label' => 'smc PENTAX-DA* 200mm F2.8 ED [IF] SDM',
        ),
        '8 241' => array(
            'Id' => '8 241',
            'Label' => 'smc PENTAX-DA* 50-135mm F2.8 ED [IF] SDM',
        ),
        '8 242' => array(
            'Id' => '8 242',
            'Label' => 'smc PENTAX-DA* 16-50mm F2.8 ED AL [IF] SDM',
        ),
        '8 255' => array(
            'Id' => '8 255',
            'Label' => 'Sigma Lens (8 255)',
        ),
        '8 255.1' => array(
            'Id' => '8 255.1',
            'Label' => 'Sigma 70-200mm F2.8 EX DG Macro HSM II',
        ),
        '8 255.2' => array(
            'Id' => '8 255.2',
            'Label' => 'Sigma 150-500mm F5-6.3 DG APO [OS] HSM',
        ),
        '8 255.3' => array(
            'Id' => '8 255.3',
            'Label' => 'Sigma 50-150mm F2.8 II APO EX DC HSM',
        ),
        '8 255.4' => array(
            'Id' => '8 255.4',
            'Label' => 'Sigma 4.5mm F2.8 EX DC HSM Circular Fisheye',
        ),
        '8 255.5' => array(
            'Id' => '8 255.5',
            'Label' => 'Sigma 50-200mm F4-5.6 DC OS',
        ),
        '8 255.6' => array(
            'Id' => '8 255.6',
            'Label' => 'Sigma 24-70mm F2.8 EX DG HSM',
        ),
        '9 0' => array(
            'Id' => '9 0',
            'Label' => '645 Manual Lens',
        ),
        '10 0' => array(
            'Id' => '10 0',
            'Label' => '645 A Series Lens',
        ),
        '11 1' => array(
            'Id' => '11 1',
            'Label' => 'smc PENTAX-FA 645 75mm F2.8',
        ),
        '11 2' => array(
            'Id' => '11 2',
            'Label' => 'smc PENTAX-FA 645 45mm F2.8',
        ),
        '11 3' => array(
            'Id' => '11 3',
            'Label' => 'smc PENTAX-FA* 645 300mm F4 ED [IF]',
        ),
        '11 4' => array(
            'Id' => '11 4',
            'Label' => 'smc PENTAX-FA 645 45-85mm F4.5',
        ),
        '11 5' => array(
            'Id' => '11 5',
            'Label' => 'smc PENTAX-FA 645 400mm F5.6 ED [IF]',
        ),
        '11 7' => array(
            'Id' => '11 7',
            'Label' => 'smc PENTAX-FA 645 Macro 120mm F4',
        ),
        '11 8' => array(
            'Id' => '11 8',
            'Label' => 'smc PENTAX-FA 645 80-160mm F4.5',
        ),
        '11 9' => array(
            'Id' => '11 9',
            'Label' => 'smc PENTAX-FA 645 200mm F4 [IF]',
        ),
        '11 10' => array(
            'Id' => '11 10',
            'Label' => 'smc PENTAX-FA 645 150mm F2.8 [IF]',
        ),
        '11 11' => array(
            'Id' => '11 11',
            'Label' => 'smc PENTAX-FA 645 35mm F3.5 AL [IF]',
        ),
        '11 12' => array(
            'Id' => '11 12',
            'Label' => 'smc PENTAX-FA 645 300mm F5.6 ED [IF]',
        ),
        '11 14' => array(
            'Id' => '11 14',
            'Label' => 'smc PENTAX-FA 645 55-110mm F5.6',
        ),
        '11 16' => array(
            'Id' => '11 16',
            'Label' => 'smc PENTAX-FA 645 33-55mm F4.5 AL',
        ),
        '11 17' => array(
            'Id' => '11 17',
            'Label' => 'smc PENTAX-FA 645 150-300mm F5.6 ED [IF]',
        ),
        '11 21' => array(
            'Id' => '11 21',
            'Label' => 'HD PENTAX-D FA 645 35mm F3.5 AL [IF]',
        ),
        '13 18' => array(
            'Id' => '13 18',
            'Label' => 'smc PENTAX-D FA 645 55mm F2.8 AL [IF] SDM AW',
        ),
        '13 19' => array(
            'Id' => '13 19',
            'Label' => 'smc PENTAX-D FA 645 25mm F4 AL [IF] SDM AW',
        ),
        '13 20' => array(
            'Id' => '13 20',
            'Label' => 'HD PENTAX-D FA 645 90mm F2.8 ED AW SR',
        ),
        '13 253' => array(
            'Id' => '13 253',
            'Label' => 'HD PENTAX-DA 645 28-45mm F4.5 ED AW SR',
        ),
        '21 0' => array(
            'Id' => '21 0',
            'Label' => 'Pentax Q Manual Lens',
        ),
        '21 1' => array(
            'Id' => '21 1',
            'Label' => '01 Standard Prime 8.5mm F1.9',
        ),
        '21 2' => array(
            'Id' => '21 2',
            'Label' => '02 Standard Zoom 5-15mm F2.8-4.5',
        ),
        '21 6' => array(
            'Id' => '21 6',
            'Label' => '06 Telephoto Zoom 15-45mm F2.8',
        ),
        '21 7' => array(
            'Id' => '21 7',
            'Label' => '07 Mount Shield 11.5mm F9',
        ),
        '21 8' => array(
            'Id' => '21 8',
            'Label' => '08 Wide Zoom 3.8-5.9mm F3.7-4',
        ),
        '22 3' => array(
            'Id' => '22 3',
            'Label' => '03 Fish-eye 3.2mm F5.6',
        ),
        '22 4' => array(
            'Id' => '22 4',
            'Label' => '04 Toy Lens Wide 6.3mm F7.1',
        ),
        '22 5' => array(
            'Id' => '22 5',
            'Label' => '05 Toy Lens Telephoto 18mm F8',
        ),
    );

}
