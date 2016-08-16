<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\PhotoCD;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class SceneBalanceAlgorithmFilmID extends AbstractTag
{

    protected $Id = 325;

    protected $Name = 'SceneBalanceAlgorithmFilmID';

    protected $FullName = 'PhotoCD::Main';

    protected $GroupName = 'PhotoCD';

    protected $g0 = 'PhotoCD';

    protected $g1 = 'PhotoCD';

    protected $g2 = 'Image';

    protected $Type = 'int16u';

    protected $Writable = false;

    protected $Description = 'Scene Balance Algorithm Film ID';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => '3M ScotchColor AT 100',
        ),
        2 => array(
            'Id' => 2,
            'Label' => '3M ScotchColor AT 200',
        ),
        3 => array(
            'Id' => 3,
            'Label' => '3M ScotchColor HR2 400',
        ),
        7 => array(
            'Id' => 7,
            'Label' => '3M Scotch HR 200 Gen 2',
        ),
        9 => array(
            'Id' => 9,
            'Label' => '3M Scotch HR 400 Gen 2',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Agfa Agfacolor XRS 400 Gen 1',
        ),
        17 => array(
            'Id' => 17,
            'Label' => 'Agfa Agfacolor XRG/XRS 400',
        ),
        18 => array(
            'Id' => 18,
            'Label' => 'Agfa Agfacolor XRG/XRS 200',
        ),
        19 => array(
            'Id' => 19,
            'Label' => 'Agfa Agfacolor XRS 1000 Gen 2',
        ),
        20 => array(
            'Id' => 20,
            'Label' => 'Agfa Agfacolor XRS 400 Gen 2',
        ),
        21 => array(
            'Id' => 21,
            'Label' => 'Agfa Agfacolor XRS/XRC 100',
        ),
        26 => array(
            'Id' => 26,
            'Label' => 'Fuji Reala 100 (JAPAN)',
        ),
        27 => array(
            'Id' => 27,
            'Label' => 'Fuji Reala 100 Gen 1',
        ),
        28 => array(
            'Id' => 28,
            'Label' => 'Fuji Reala 100 Gen 2',
        ),
        29 => array(
            'Id' => 29,
            'Label' => 'Fuji SHR 400 Gen 2',
        ),
        30 => array(
            'Id' => 30,
            'Label' => 'Fuji Super HG 100',
        ),
        31 => array(
            'Id' => 31,
            'Label' => 'Fuji Super HG 1600 Gen 1',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'Fuji Super HG 200',
        ),
        33 => array(
            'Id' => 33,
            'Label' => 'Fuji Super HG 400',
        ),
        34 => array(
            'Id' => 34,
            'Label' => 'Fuji Super HG 100 Gen 2',
        ),
        35 => array(
            'Id' => 35,
            'Label' => 'Fuji Super HR 100 Gen 1',
        ),
        36 => array(
            'Id' => 36,
            'Label' => 'Fuji Super HR 100 Gen 2',
        ),
        37 => array(
            'Id' => 37,
            'Label' => 'Fuji Super HR 1600 Gen 2',
        ),
        38 => array(
            'Id' => 38,
            'Label' => 'Fuji Super HR 200 Gen 1',
        ),
        39 => array(
            'Id' => 39,
            'Label' => 'Fuji Super HR 200 Gen 2',
        ),
        40 => array(
            'Id' => 40,
            'Label' => 'Fuji Super HR 400 Gen 1',
        ),
        43 => array(
            'Id' => 43,
            'Label' => 'Fuji NSP 160S (Pro)',
        ),
        45 => array(
            'Id' => 45,
            'Label' => 'Kodak Kodacolor VR 100 Gen 2',
        ),
        47 => array(
            'Id' => 47,
            'Label' => 'Kodak Gold 400 Gen 3',
        ),
        55 => array(
            'Id' => 55,
            'Label' => 'Kodak Ektar 100 Gen 1',
        ),
        56 => array(
            'Id' => 56,
            'Label' => 'Kodak Ektar 1000 Gen 1',
        ),
        57 => array(
            'Id' => 57,
            'Label' => 'Kodak Ektar 125 Gen 1',
        ),
        58 => array(
            'Id' => 58,
            'Label' => 'Kodak Royal Gold 25 RZ',
        ),
        60 => array(
            'Id' => 60,
            'Label' => 'Kodak Gold 1600 Gen 1',
        ),
        61 => array(
            'Id' => 61,
            'Label' => 'Kodak Gold 200 Gen 2',
        ),
        62 => array(
            'Id' => 62,
            'Label' => 'Kodak Gold 400 Gen 2',
        ),
        65 => array(
            'Id' => 65,
            'Label' => 'Kodak Kodacolor VR 100 Gen 1',
        ),
        66 => array(
            'Id' => 66,
            'Label' => 'Kodak Kodacolor VR 1000 Gen 2',
        ),
        67 => array(
            'Id' => 67,
            'Label' => 'Kodak Kodacolor VR 1000 Gen 1',
        ),
        68 => array(
            'Id' => 68,
            'Label' => 'Kodak Kodacolor VR 200 Gen 1',
        ),
        69 => array(
            'Id' => 69,
            'Label' => 'Kodak Kodacolor VR 400 Gen 1',
        ),
        70 => array(
            'Id' => 70,
            'Label' => 'Kodak Kodacolor VR 200 Gen 2',
        ),
        71 => array(
            'Id' => 71,
            'Label' => 'Kodak Kodacolor VRG 100 Gen 1',
        ),
        72 => array(
            'Id' => 72,
            'Label' => 'Kodak Gold 100 Gen 2',
        ),
        73 => array(
            'Id' => 73,
            'Label' => 'Kodak Kodacolor VRG 200 Gen 1',
        ),
        74 => array(
            'Id' => 74,
            'Label' => 'Kodak Gold 400 Gen 1',
        ),
        87 => array(
            'Id' => 87,
            'Label' => 'Kodak Ektacolor Gold 160',
        ),
        88 => array(
            'Id' => 88,
            'Label' => 'Kodak Ektapress 1600 Gen 1 PPC',
        ),
        89 => array(
            'Id' => 89,
            'Label' => 'Kodak Ektapress Gold 100 Gen 1 PPA',
        ),
        90 => array(
            'Id' => 90,
            'Label' => 'Kodak Ektapress Gold 400 PPB-3',
        ),
        92 => array(
            'Id' => 92,
            'Label' => 'Kodak Ektar 25 Professional PHR',
        ),
        97 => array(
            'Id' => 97,
            'Label' => 'Kodak T-Max 100 Professional',
        ),
        98 => array(
            'Id' => 98,
            'Label' => 'Kodak T-Max 3200 Professional',
        ),
        99 => array(
            'Id' => 99,
            'Label' => 'Kodak T-Max 400 Professional',
        ),
        101 => array(
            'Id' => 101,
            'Label' => 'Kodak Vericolor 400 Prof VPH',
        ),
        102 => array(
            'Id' => 102,
            'Label' => 'Kodak Vericolor III Pro',
        ),
        121 => array(
            'Id' => 121,
            'Label' => 'Konika Konica Color SR-G 3200',
        ),
        122 => array(
            'Id' => 122,
            'Label' => 'Konika Konica Color Super SR100',
        ),
        123 => array(
            'Id' => 123,
            'Label' => 'Konika Konica Color Super SR 400',
        ),
        138 => array(
            'Id' => 138,
            'Label' => 'Kodak Gold Unknown',
        ),
        139 => array(
            'Id' => 139,
            'Label' => 'Kodak Unknown Neg A- Normal SBA',
        ),
        143 => array(
            'Id' => 143,
            'Label' => 'Kodak Ektar 100 Gen 2',
        ),
        147 => array(
            'Id' => 147,
            'Label' => 'Kodak Kodacolor CII',
        ),
        148 => array(
            'Id' => 148,
            'Label' => 'Kodak Kodacolor II',
        ),
        149 => array(
            'Id' => 149,
            'Label' => 'Kodak Gold Plus 200 Gen 3',
        ),
        150 => array(
            'Id' => 150,
            'Label' => 'Kodak Internegative +10% Contrast',
        ),
        151 => array(
            'Id' => 151,
            'Label' => 'Agfa Agfacolor Ultra 50',
        ),
        152 => array(
            'Id' => 152,
            'Label' => 'Fuji NHG 400',
        ),
        153 => array(
            'Id' => 153,
            'Label' => 'Agfa Agfacolor XRG 100',
        ),
        154 => array(
            'Id' => 154,
            'Label' => 'Kodak Gold Plus 100 Gen 3',
        ),
        155 => array(
            'Id' => 155,
            'Label' => 'Konika Konica Color Super SR200 Gen 1',
        ),
        156 => array(
            'Id' => 156,
            'Label' => 'Konika Konica Color SR-G 160',
        ),
        157 => array(
            'Id' => 157,
            'Label' => 'Agfa Agfacolor Optima 125',
        ),
        158 => array(
            'Id' => 158,
            'Label' => 'Agfa Agfacolor Portrait 160',
        ),
        162 => array(
            'Id' => 162,
            'Label' => 'Kodak Kodacolor VRG 400 Gen 1',
        ),
        163 => array(
            'Id' => 163,
            'Label' => 'Kodak Gold 200 Gen 1',
        ),
        164 => array(
            'Id' => 164,
            'Label' => 'Kodak Kodacolor VRG 100 Gen 2',
        ),
        174 => array(
            'Id' => 174,
            'Label' => 'Kodak Internegative +20% Contrast',
        ),
        175 => array(
            'Id' => 175,
            'Label' => 'Kodak Internegative +30% Contrast',
        ),
        176 => array(
            'Id' => 176,
            'Label' => 'Kodak Internegative +40% Contrast',
        ),
        184 => array(
            'Id' => 184,
            'Label' => 'Kodak TMax-100 D-76 CI = .40',
        ),
        185 => array(
            'Id' => 185,
            'Label' => 'Kodak TMax-100 D-76 CI = .50',
        ),
        186 => array(
            'Id' => 186,
            'Label' => 'Kodak TMax-100 D-76 CI = .55',
        ),
        187 => array(
            'Id' => 187,
            'Label' => 'Kodak TMax-100 D-76 CI = .70',
        ),
        188 => array(
            'Id' => 188,
            'Label' => 'Kodak TMax-100 D-76 CI = .80',
        ),
        189 => array(
            'Id' => 189,
            'Label' => 'Kodak TMax-100 TMax CI = .40',
        ),
        190 => array(
            'Id' => 190,
            'Label' => 'Kodak TMax-100 TMax CI = .50',
        ),
        191 => array(
            'Id' => 191,
            'Label' => 'Kodak TMax-100 TMax CI = .55',
        ),
        192 => array(
            'Id' => 192,
            'Label' => 'Kodak TMax-100 TMax CI = .70',
        ),
        193 => array(
            'Id' => 193,
            'Label' => 'Kodak TMax-100 TMax CI = .80',
        ),
        195 => array(
            'Id' => 195,
            'Label' => 'Kodak TMax-400 D-76 CI = .40',
        ),
        196 => array(
            'Id' => 196,
            'Label' => 'Kodak TMax-400 D-76 CI = .50',
        ),
        197 => array(
            'Id' => 197,
            'Label' => 'Kodak TMax-400 D-76 CI = .55',
        ),
        198 => array(
            'Id' => 198,
            'Label' => 'Kodak TMax-400 D-76 CI = .70',
        ),
        214 => array(
            'Id' => 214,
            'Label' => 'Kodak TMax-400 D-76 CI = .80',
        ),
        215 => array(
            'Id' => 215,
            'Label' => 'Kodak TMax-400 TMax CI = .40',
        ),
        216 => array(
            'Id' => 216,
            'Label' => 'Kodak TMax-400 TMax CI = .50',
        ),
        217 => array(
            'Id' => 217,
            'Label' => 'Kodak TMax-400 TMax CI = .55',
        ),
        218 => array(
            'Id' => 218,
            'Label' => 'Kodak TMax-400 TMax CI = .70',
        ),
        219 => array(
            'Id' => 219,
            'Label' => 'Kodak TMax-400 TMax CI = .80',
        ),
        224 => array(
            'Id' => 224,
            'Label' => '3M ScotchColor ATG 400/EXL 400',
        ),
        266 => array(
            'Id' => 266,
            'Label' => 'Agfa Agfacolor Optima 200',
        ),
        267 => array(
            'Id' => 267,
            'Label' => 'Konika Impressa 50',
        ),
        268 => array(
            'Id' => 268,
            'Label' => 'Polaroid Polaroid CP 200',
        ),
        269 => array(
            'Id' => 269,
            'Label' => 'Konika Konica Color Super SR200 Gen 2',
        ),
        270 => array(
            'Id' => 270,
            'Label' => 'ILFORD XP2 400',
        ),
        271 => array(
            'Id' => 271,
            'Label' => 'Polaroid Polaroid Color HD2 100',
        ),
        272 => array(
            'Id' => 272,
            'Label' => 'Polaroid Polaroid Color HD2 400',
        ),
        273 => array(
            'Id' => 273,
            'Label' => 'Polaroid Polaroid Color HD2 200',
        ),
        282 => array(
            'Id' => 282,
            'Label' => '3M ScotchColor ATG-1 200',
        ),
        284 => array(
            'Id' => 284,
            'Label' => 'Konika XG 400',
        ),
        307 => array(
            'Id' => 307,
            'Label' => 'Kodak Universal Reversal B/W',
        ),
        308 => array(
            'Id' => 308,
            'Label' => 'Kodak RPC Copy Film Gen 1',
        ),
        312 => array(
            'Id' => 312,
            'Label' => 'Kodak Universal E6',
        ),
        324 => array(
            'Id' => 324,
            'Label' => 'Kodak Gold Ultra 400 Gen 4',
        ),
        328 => array(
            'Id' => 328,
            'Label' => 'Fuji Super G 100',
        ),
        329 => array(
            'Id' => 329,
            'Label' => 'Fuji Super G 200',
        ),
        330 => array(
            'Id' => 330,
            'Label' => 'Fuji Super G 400 Gen 2',
        ),
        333 => array(
            'Id' => 333,
            'Label' => 'Kodak Universal K14',
        ),
        334 => array(
            'Id' => 334,
            'Label' => 'Fuji Super G 400 Gen 1',
        ),
        366 => array(
            'Id' => 366,
            'Label' => 'Kodak Vericolor HC 6329 VHC',
        ),
        367 => array(
            'Id' => 367,
            'Label' => 'Kodak Vericolor HC 4329 VHC',
        ),
        368 => array(
            'Id' => 368,
            'Label' => 'Kodak Vericolor L 6013 VPL',
        ),
        369 => array(
            'Id' => 369,
            'Label' => 'Kodak Vericolor L 4013 VPL',
        ),
        418 => array(
            'Id' => 418,
            'Label' => 'Kodak Ektacolor Gold II 400 Prof',
        ),
        430 => array(
            'Id' => 430,
            'Label' => 'Kodak Royal Gold 1000',
        ),
        431 => array(
            'Id' => 431,
            'Label' => 'Kodak Kodacolor VR 200 / 5093',
        ),
        432 => array(
            'Id' => 432,
            'Label' => 'Kodak Gold Plus 100 Gen 4',
        ),
        443 => array(
            'Id' => 443,
            'Label' => 'Kodak Royal Gold 100',
        ),
        444 => array(
            'Id' => 444,
            'Label' => 'Kodak Royal Gold 400',
        ),
        445 => array(
            'Id' => 445,
            'Label' => 'Kodak Universal E6 auto-balance',
        ),
        446 => array(
            'Id' => 446,
            'Label' => 'Kodak Universal E6 illum. corr.',
        ),
        447 => array(
            'Id' => 447,
            'Label' => 'Kodak Universal K14 auto-balance',
        ),
        448 => array(
            'Id' => 448,
            'Label' => 'Kodak Universal K14 illum. corr.',
        ),
        449 => array(
            'Id' => 449,
            'Label' => 'Kodak Ektar 100 Gen 3 SY',
        ),
        456 => array(
            'Id' => 456,
            'Label' => 'Kodak Ektar 25',
        ),
        457 => array(
            'Id' => 457,
            'Label' => 'Kodak Ektar 100 Gen 3 CX',
        ),
        458 => array(
            'Id' => 458,
            'Label' => 'Kodak Ektapress Plus 100 Prof PJA-1',
        ),
        459 => array(
            'Id' => 459,
            'Label' => 'Kodak Ektapress Gold II 100 Prof',
        ),
        460 => array(
            'Id' => 460,
            'Label' => 'Kodak Pro 100 PRN',
        ),
        461 => array(
            'Id' => 461,
            'Label' => 'Kodak Vericolor HC 100 Prof VHC-2',
        ),
        462 => array(
            'Id' => 462,
            'Label' => 'Kodak Prof Color Neg 100',
        ),
        463 => array(
            'Id' => 463,
            'Label' => 'Kodak Ektar 1000 Gen 2',
        ),
        464 => array(
            'Id' => 464,
            'Label' => 'Kodak Ektapress Plus 1600 Pro PJC-1',
        ),
        465 => array(
            'Id' => 465,
            'Label' => 'Kodak Ektapress Gold II 1600 Prof',
        ),
        466 => array(
            'Id' => 466,
            'Label' => 'Kodak Super Gold 1600 GF Gen 2',
        ),
        467 => array(
            'Id' => 467,
            'Label' => 'Kodak Kodacolor 100 Print Gen 4',
        ),
        468 => array(
            'Id' => 468,
            'Label' => 'Kodak Super Gold 100 Gen 4',
        ),
        469 => array(
            'Id' => 469,
            'Label' => 'Kodak Gold 100 Gen 4',
        ),
        470 => array(
            'Id' => 470,
            'Label' => 'Kodak Gold III 100 Gen 4',
        ),
        471 => array(
            'Id' => 471,
            'Label' => 'Kodak Funtime 100 FA',
        ),
        472 => array(
            'Id' => 472,
            'Label' => 'Kodak Funtime 200 FB',
        ),
        473 => array(
            'Id' => 473,
            'Label' => 'Kodak Kodacolor VR 200 Gen 4',
        ),
        474 => array(
            'Id' => 474,
            'Label' => 'Kodak Gold Super 200 Gen 4',
        ),
        475 => array(
            'Id' => 475,
            'Label' => 'Kodak Kodacolor 200 Print Gen 4',
        ),
        476 => array(
            'Id' => 476,
            'Label' => 'Kodak Super Gold 200 Gen 4',
        ),
        477 => array(
            'Id' => 477,
            'Label' => 'Kodak Gold 200 Gen 4',
        ),
        478 => array(
            'Id' => 478,
            'Label' => 'Kodak Gold III 200 Gen 4',
        ),
        479 => array(
            'Id' => 479,
            'Label' => 'Kodak Gold Ultra 400 Gen 5',
        ),
        480 => array(
            'Id' => 480,
            'Label' => 'Kodak Super Gold 400 Gen 5',
        ),
        481 => array(
            'Id' => 481,
            'Label' => 'Kodak Gold 400 Gen 5',
        ),
        482 => array(
            'Id' => 482,
            'Label' => 'Kodak Gold III 400 Gen 5',
        ),
        483 => array(
            'Id' => 483,
            'Label' => 'Kodak Kodacolor 400 Print Gen 5',
        ),
        484 => array(
            'Id' => 484,
            'Label' => 'Kodak Ektapress Plus 400 Prof PJB-2',
        ),
        485 => array(
            'Id' => 485,
            'Label' => 'Kodak Ektapress Gold II 400 Prof G5',
        ),
        486 => array(
            'Id' => 486,
            'Label' => 'Kodak Pro 400 PPF-2',
        ),
        487 => array(
            'Id' => 487,
            'Label' => 'Kodak Ektacolor Gold II 400 EGP-4',
        ),
        488 => array(
            'Id' => 488,
            'Label' => 'Kodak Ektacolor Gold 400 Prof EGP-4',
        ),
        489 => array(
            'Id' => 489,
            'Label' => 'Kodak Ektapress Gold II Multspd PJM',
        ),
        490 => array(
            'Id' => 490,
            'Label' => 'Kodak Pro 400 MC PMC',
        ),
        491 => array(
            'Id' => 491,
            'Label' => 'Kodak Vericolor 400 Prof VPH-2',
        ),
        492 => array(
            'Id' => 492,
            'Label' => 'Kodak Vericolor 400 Plus Prof VPH-2',
        ),
        493 => array(
            'Id' => 493,
            'Label' => 'Kodak Unknown Neg Product Code 83',
        ),
        505 => array(
            'Id' => 505,
            'Label' => 'Kodak Ektacolor Pro Gold 160 GPX',
        ),
        508 => array(
            'Id' => 508,
            'Label' => 'Kodak Royal Gold 200',
        ),
        517 => array(
            'Id' => 517,
            'Label' => 'Kodak 4050000000',
        ),
        519 => array(
            'Id' => 519,
            'Label' => 'Kodak Gold Plus 100 Gen 5',
        ),
        520 => array(
            'Id' => 520,
            'Label' => 'Kodak Gold 800 Gen 1',
        ),
        521 => array(
            'Id' => 521,
            'Label' => 'Kodak Gold Super 200 Gen 5',
        ),
        522 => array(
            'Id' => 522,
            'Label' => 'Kodak Ektapress Plus 200 Prof',
        ),
        523 => array(
            'Id' => 523,
            'Label' => 'Kodak 4050 E6 auto-balance',
        ),
        524 => array(
            'Id' => 524,
            'Label' => 'Kodak 4050 E6 ilum. corr.',
        ),
        525 => array(
            'Id' => 525,
            'Label' => 'Kodak 4050 K14',
        ),
        526 => array(
            'Id' => 526,
            'Label' => 'Kodak 4050 K14 auto-balance',
        ),
        527 => array(
            'Id' => 527,
            'Label' => 'Kodak 4050 K14 ilum. corr.',
        ),
        528 => array(
            'Id' => 528,
            'Label' => 'Kodak 4050 Reversal B&W',
        ),
        532 => array(
            'Id' => 532,
            'Label' => 'Kodak Advantix 200',
        ),
        533 => array(
            'Id' => 533,
            'Label' => 'Kodak Advantix 400',
        ),
        534 => array(
            'Id' => 534,
            'Label' => 'Kodak Advantix 100',
        ),
        535 => array(
            'Id' => 535,
            'Label' => 'Kodak Ektapress Multspd Prof PJM-2',
        ),
        536 => array(
            'Id' => 536,
            'Label' => 'Kodak Kodacolor VR 200 Gen 5',
        ),
        537 => array(
            'Id' => 537,
            'Label' => 'Kodak Funtime 200 FB Gen 2',
        ),
        538 => array(
            'Id' => 538,
            'Label' => 'Kodak Commercial 200',
        ),
        539 => array(
            'Id' => 539,
            'Label' => 'Kodak Royal Gold 25 Copystand',
        ),
        540 => array(
            'Id' => 540,
            'Label' => 'Kodak Kodacolor DA 100 Gen 5',
        ),
        545 => array(
            'Id' => 545,
            'Label' => 'Kodak Kodacolor VR 400 Gen 2',
        ),
        546 => array(
            'Id' => 546,
            'Label' => 'Kodak Gold 100 Gen 6',
        ),
        547 => array(
            'Id' => 547,
            'Label' => 'Kodak Gold 200 Gen 6',
        ),
        548 => array(
            'Id' => 548,
            'Label' => 'Kodak Gold 400 Gen 6',
        ),
        549 => array(
            'Id' => 549,
            'Label' => 'Kodak Royal Gold 100 Gen 2',
        ),
        550 => array(
            'Id' => 550,
            'Label' => 'Kodak Royal Gold 200 Gen 2',
        ),
        551 => array(
            'Id' => 551,
            'Label' => 'Kodak Royal Gold 400 Gen 2',
        ),
        552 => array(
            'Id' => 552,
            'Label' => 'Kodak Gold Max 800 Gen 2',
        ),
        554 => array(
            'Id' => 554,
            'Label' => 'Kodak 4050 E6 high contrast',
        ),
        555 => array(
            'Id' => 555,
            'Label' => 'Kodak 4050 E6 low saturation high contrast',
        ),
        556 => array(
            'Id' => 556,
            'Label' => 'Kodak 4050 E6 low saturation',
        ),
        557 => array(
            'Id' => 557,
            'Label' => 'Kodak Universal E-6 Low Saturation',
        ),
        558 => array(
            'Id' => 558,
            'Label' => 'Kodak T-Max T400 CN',
        ),
        563 => array(
            'Id' => 563,
            'Label' => 'Kodak Ektapress PJ100',
        ),
        564 => array(
            'Id' => 564,
            'Label' => 'Kodak Ektapress PJ400',
        ),
        565 => array(
            'Id' => 565,
            'Label' => 'Kodak Ektapress PJ800',
        ),
        567 => array(
            'Id' => 567,
            'Label' => 'Kodak Portra 160NC',
        ),
        568 => array(
            'Id' => 568,
            'Label' => 'Kodak Portra 160VC',
        ),
        569 => array(
            'Id' => 569,
            'Label' => 'Kodak Portra 400NC',
        ),
        570 => array(
            'Id' => 570,
            'Label' => 'Kodak Portra 400VC',
        ),
        575 => array(
            'Id' => 575,
            'Label' => 'Kodak Advantix 100-2',
        ),
        576 => array(
            'Id' => 576,
            'Label' => 'Kodak Advantix 200-2',
        ),
        577 => array(
            'Id' => 577,
            'Label' => 'Kodak Advantix Black & White + 400',
        ),
        578 => array(
            'Id' => 578,
            'Label' => 'Kodak Ektapress PJ800-2',
        ),
    );

}
