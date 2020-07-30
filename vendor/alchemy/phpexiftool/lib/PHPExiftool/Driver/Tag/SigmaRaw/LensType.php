<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\SigmaRaw;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class LensType extends AbstractTag
{

    protected $Id = 'LENSMODEL';

    protected $Name = 'LensType';

    protected $FullName = 'SigmaRaw::Properties';

    protected $GroupName = 'SigmaRaw';

    protected $g0 = 'SigmaRaw';

    protected $g1 = 'SigmaRaw';

    protected $g2 = 'Camera';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Lens Type';

    protected $Values = array(
        16 => array(
            'Id' => 16,
            'Label' => 'Sigma 18-50mm F3.5-5.6 DC',
        ),
        103 => array(
            'Id' => 103,
            'Label' => 'Sigma 180mm F3.5 EX IF HSM APO Macro',
        ),
        104 => array(
            'Id' => 104,
            'Label' => 'Sigma 150mm F2.8 EX DG HSM APO Macro',
        ),
        105 => array(
            'Id' => 105,
            'Label' => 'Sigma 180mm F3.5 EX DG HSM APO Macro',
        ),
        106 => array(
            'Id' => 106,
            'Label' => 'Sigma 150mm F2.8 EX DG OS HSM APO Macro',
        ),
        107 => array(
            'Id' => 107,
            'Label' => 'Sigma 180mm F2.8 EX DG OS HSM APO Macro',
        ),
        129 => array(
            'Id' => 129,
            'Label' => 'Sigma 14mm F2.8 EX Aspherical',
        ),
        131 => array(
            'Id' => 131,
            'Label' => 'Sigma 17-70mm F2.8-4.5 DC Macro',
        ),
        134 => array(
            'Id' => 134,
            'Label' => 'Sigma 100-300mm F4 EX DG HSM APO',
        ),
        135 => array(
            'Id' => 135,
            'Label' => 'Sigma 120-300mm F2.8 EX DG HSM APO',
        ),
        136 => array(
            'Id' => 136,
            'Label' => 'Sigma 120-300mm F2.8 EX DG OS HSM APO',
        ),
        137 => array(
            'Id' => 137,
            'Label' => 'Sigma 120-300mm F2.8 DG OS HSM | S',
        ),
        143 => array(
            'Id' => 143,
            'Label' => 'Sigma 600mm F8 Mirror',
        ),
        145 => array(
            'Id' => 145,
            'Label' => 'Sigma Lens (145)',
        ),
        '145.1' => array(
            'Id' => '145.1',
            'Label' => 'Sigma 15-30mm F3.5-4.5 EX DG Aspherical',
        ),
        '145.2' => array(
            'Id' => '145.2',
            'Label' => 'Sigma 18-50mm F2.8 EX DG',
        ),
        '145.3' => array(
            'Id' => '145.3',
            'Label' => 'Sigma 20-40mm F2.8 EX DG',
        ),
        152 => array(
            'Id' => 152,
            'Label' => 'Sigma APO 800mm F5.6 EX DG HSM',
        ),
        165 => array(
            'Id' => 165,
            'Label' => 'Sigma 70-200mm F2.8 EX',
        ),
        169 => array(
            'Id' => 169,
            'Label' => 'Sigma 18-50mm F2.8 EX DC',
        ),
        183 => array(
            'Id' => 183,
            'Label' => 'Sigma 500mm F4.5 EX HSM APO',
        ),
        184 => array(
            'Id' => 184,
            'Label' => 'Sigma 500mm F4.5 EX DG HSM APO',
        ),
        194 => array(
            'Id' => 194,
            'Label' => 'Sigma 300mm F2.8 EX HSM APO',
        ),
        195 => array(
            'Id' => 195,
            'Label' => 'Sigma 300mm F2.8 EX DG HSM APO',
        ),
        200 => array(
            'Id' => 200,
            'Label' => 'Sigma 12-24mm F4.5-5.6 EX DG ASP HSM',
        ),
        201 => array(
            'Id' => 201,
            'Label' => 'Sigma 10-20mm F4-5.6 EX DC HSM',
        ),
        202 => array(
            'Id' => 202,
            'Label' => 'Sigma 10-20mm F3.5 EX DC HSM',
        ),
        203 => array(
            'Id' => 203,
            'Label' => 'Sigma 8-16mm F4.5-5.6 DC HSM',
        ),
        204 => array(
            'Id' => 204,
            'Label' => 'Sigma 12-24mm F4.5-5.6 DG HSM II',
        ),
        210 => array(
            'Id' => 210,
            'Label' => 'Sigma 18-35mm F1.8 DC HSM | A',
        ),
        256 => array(
            'Id' => 256,
            'Label' => 'Sigma 105mm F2.8 EX Macro',
        ),
        257 => array(
            'Id' => 257,
            'Label' => 'Sigma 105mm F2.8 EX DG Macro',
        ),
        258 => array(
            'Id' => 258,
            'Label' => 'Sigma 105mm F2.8 EX DG OS HSM Macro',
        ),
        270 => array(
            'Id' => 270,
            'Label' => 'Sigma 70mm F2.8 EX DG Macro',
        ),
        300 => array(
            'Id' => 300,
            'Label' => 'Sigma 30mm F1.4 EX DC HSM',
        ),
        301 => array(
            'Id' => 301,
            'Label' => 'Sigma 30mm F1.4 DC HSM | A',
        ),
        310 => array(
            'Id' => 310,
            'Label' => 'Sigma 50mm F1.4 EX DG HSM',
        ),
        311 => array(
            'Id' => 311,
            'Label' => 'Sigma 50mm F1.4 DG HSM | A',
        ),
        320 => array(
            'Id' => 320,
            'Label' => 'Sigma 85mm F1.4 EX DG HSM',
        ),
        330 => array(
            'Id' => 330,
            'Label' => 'Sigma 30mm F2.8 EX DN',
        ),
        340 => array(
            'Id' => 340,
            'Label' => 'Sigma 35mm F1.4 DG HSM',
        ),
        345 => array(
            'Id' => 345,
            'Label' => 'Sigma 50mm F2.8 EX Macro',
        ),
        346 => array(
            'Id' => 346,
            'Label' => 'Sigma 50mm F2.8 EX DG Macro',
        ),
        400 => array(
            'Id' => 400,
            'Label' => 'Sigma 9mm F2.8 EX DN',
        ),
        401 => array(
            'Id' => 401,
            'Label' => 'Sigma 24mm F1.4 DG HSM | A',
        ),
        411 => array(
            'Id' => 411,
            'Label' => 'Sigma 20mm F1.8 EX DG ASP RF',
        ),
        432 => array(
            'Id' => 432,
            'Label' => 'Sigma 24mm F1.8 EX DG ASP Macro',
        ),
        440 => array(
            'Id' => 440,
            'Label' => 'Sigma 28mm F1.8 EX DG ASP Macro',
        ),
        461 => array(
            'Id' => 461,
            'Label' => 'Sigma 14mm F2.8 EX ASP HSM',
        ),
        475 => array(
            'Id' => 475,
            'Label' => 'Sigma 15mm F2.8 EX Diagonal FishEye',
        ),
        476 => array(
            'Id' => 476,
            'Label' => 'Sigma 15mm F2.8 EX DG Diagonal Fisheye',
        ),
        477 => array(
            'Id' => 477,
            'Label' => 'Sigma 10mm F2.8 EX DC HSM Fisheye',
        ),
        483 => array(
            'Id' => 483,
            'Label' => 'Sigma 8mm F4 EX Circular Fisheye',
        ),
        484 => array(
            'Id' => 484,
            'Label' => 'Sigma 8mm F4 EX DG Circular Fisheye',
        ),
        485 => array(
            'Id' => 485,
            'Label' => 'Sigma 8mm F3.5 EX DG Circular Fisheye',
        ),
        486 => array(
            'Id' => 486,
            'Label' => 'Sigma 4.5mm F2.8 EX DC HSM Circular Fisheye',
        ),
        506 => array(
            'Id' => 506,
            'Label' => 'Sigma 70-300mm F4-5.6 APO Macro Super II',
        ),
        507 => array(
            'Id' => 507,
            'Label' => 'Sigma 70-300mm F4-5.6 DL Macro Super II',
        ),
        508 => array(
            'Id' => 508,
            'Label' => 'Sigma 70-300mm F4-5.6 DG APO Macro',
        ),
        509 => array(
            'Id' => 509,
            'Label' => 'Sigma 70-300mm F4-5.6 DG Macro',
        ),
        510 => array(
            'Id' => 510,
            'Label' => 'Sigma 17-35 F2.8-4 EX DG ASP',
        ),
        512 => array(
            'Id' => 512,
            'Label' => 'Sigma 15-30mm F3.5-4.5 EX DG ASP DF',
        ),
        513 => array(
            'Id' => 513,
            'Label' => 'Sigma 20-40mm F2.8 EX DG',
        ),
        519 => array(
            'Id' => 519,
            'Label' => 'Sigma 17-35 F2.8-4 EX ASP HSM',
        ),
        520 => array(
            'Id' => 520,
            'Label' => 'Sigma 100-300mm F4.5-6.7 DL',
        ),
        521 => array(
            'Id' => 521,
            'Label' => 'Sigma 18-50mm F3.5-5.6 DC Macro',
        ),
        527 => array(
            'Id' => 527,
            'Label' => 'Sigma 100-300mm F4 EX IF HSM',
        ),
        529 => array(
            'Id' => 529,
            'Label' => 'Sigma 120-300mm F2.8 EX HSM IF APO',
        ),
        547 => array(
            'Id' => 547,
            'Label' => 'Sigma 24-60mm F2.8 EX DG',
        ),
        548 => array(
            'Id' => 548,
            'Label' => 'Sigma 24-70mm F2.8 EX DG Macro',
        ),
        549 => array(
            'Id' => 549,
            'Label' => 'Sigma 28-70mm F2.8 EX DG',
        ),
        566 => array(
            'Id' => 566,
            'Label' => 'Sigma 70-200mm F2.8 EX IF APO',
        ),
        567 => array(
            'Id' => 567,
            'Label' => 'Sigma 70-200mm F2.8 EX IF HSM APO',
        ),
        568 => array(
            'Id' => 568,
            'Label' => 'Sigma 70-200mm F2.8 EX DG IF HSM APO',
        ),
        569 => array(
            'Id' => 569,
            'Label' => 'Sigma 70-200 F2.8 EX DG HSM APO Macro',
        ),
        571 => array(
            'Id' => 571,
            'Label' => 'Sigma 24-70mm F2.8 IF EX DG HSM',
        ),
        572 => array(
            'Id' => 572,
            'Label' => 'Sigma 70-300mm F4-5.6 DG OS',
        ),
        579 => array(
            'Id' => 579,
            'Label' => 'Sigma 70-200mm F2.8 EX DG HSM APO Macro',
        ),
        580 => array(
            'Id' => 580,
            'Label' => 'Sigma 18-50mm F2.8 EX DC',
        ),
        581 => array(
            'Id' => 581,
            'Label' => 'Sigma 18-50mm F2.8 EX DC Macro',
        ),
        582 => array(
            'Id' => 582,
            'Label' => 'Sigma 18-50mm F2.8 EX DC HSM Macro',
        ),
        583 => array(
            'Id' => 583,
            'Label' => 'Sigma 17-50mm F2.8 EX DC OS HSM',
        ),
        589 => array(
            'Id' => 589,
            'Label' => 'Sigma APO 70-200mm F2.8 EX DG OS HSM',
        ),
        595 => array(
            'Id' => 595,
            'Label' => 'Sigma 300-800mm F5.6 EX DG APO HSM',
        ),
        597 => array(
            'Id' => 597,
            'Label' => 'Sigma 200-500mm F2.8 APO EX DG',
        ),
        668 => array(
            'Id' => 668,
            'Label' => 'Sigma 17-70mm F2.8-4 DC Macro OS HSM',
        ),
        686 => array(
            'Id' => 686,
            'Label' => 'Sigma 50-200mm F4-5.6 DC OS HSM',
        ),
        691 => array(
            'Id' => 691,
            'Label' => 'Sigma 50-150mm F2.8 EX DC APO HSM II',
        ),
        692 => array(
            'Id' => 692,
            'Label' => 'Sigma APO 50-150mm F2.8 EX DC OS HSM',
        ),
        728 => array(
            'Id' => 728,
            'Label' => 'Sigma 120-400mm F4.5-5.6 DG APO OS HSM',
        ),
        737 => array(
            'Id' => 737,
            'Label' => 'Sigma 150-500mm F5-6.3 APO DG OS HSM',
        ),
        738 => array(
            'Id' => 738,
            'Label' => 'Sigma 50-500mm F4.5-6.3 APO DG OS HSM',
        ),
        824 => array(
            'Id' => 824,
            'Label' => 'Sigma 1.4X Teleconverter EX APO DG',
        ),
        853 => array(
            'Id' => 853,
            'Label' => 'Sigma 18-125mm F3.8-5.6 DC OS HSM',
        ),
        861 => array(
            'Id' => 861,
            'Label' => 'Sigma 18-50mm F2.8-4.5 DC OS HSM',
        ),
        876 => array(
            'Id' => 876,
            'Label' => 'Sigma 2.0X Teleconverter EX APO DG',
        ),
        880 => array(
            'Id' => 880,
            'Label' => 'Sigma 18-250mm F3.5-6.3 DC OS HSM',
        ),
        882 => array(
            'Id' => 882,
            'Label' => 'Sigma 18-200mm F3.5-6.3 II DC OS HSM',
        ),
        883 => array(
            'Id' => 883,
            'Label' => 'Sigma 18-250mm F3.5-6.3 DC Macro OS HSM',
        ),
        1003 => array(
            'Id' => 1003,
            'Label' => 'Sigma 19mm F2.8',
        ),
        1004 => array(
            'Id' => 1004,
            'Label' => 'Sigma 30mm F2.8',
        ),
        1005 => array(
            'Id' => 1005,
            'Label' => 'Sigma 50mm F2.8 Macro',
        ),
        1006 => array(
            'Id' => 1006,
            'Label' => 'Sigma 19mm F2.8',
        ),
        1007 => array(
            'Id' => 1007,
            'Label' => 'Sigma 30mm F2.8',
        ),
        1008 => array(
            'Id' => 1008,
            'Label' => 'Sigma 50mm F2.8 Macro',
        ),
        1009 => array(
            'Id' => 1009,
            'Label' => 'Sigma 14mm F4',
        ),
        8900 => array(
            'Id' => 8900,
            'Label' => 'Sigma 70-300mm F4-5.6 DG OS',
        ),
        '5A8' => array(
            'Id' => '5A8',
            'Label' => 'Sigma 70-300mm F4-5.6 APO DG Macro (Motorized)',
        ),
        '5A9' => array(
            'Id' => '5A9',
            'Label' => 'Sigma 70-300mm F4-5.6 DG Macro (Motorized)',
        ),
        'A100' => array(
            'Id' => 'A100',
            'Label' => 'Sigma 24-70mm F2.8 DG Macro',
        ),
    );

}
