<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Sony;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ExposureProgram extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'ExposureProgram';

    protected $FullName = 'mixed';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'mixed';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Exposure Program';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Program AE',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Aperture-priority AE',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Shutter speed priority AE',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Manual',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Auto',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'iAuto',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Superior Auto',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'iAuto+',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Portrait',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Landscape',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Twilight',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Twilight Portrait',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'Sunset',
        ),
        13 => array(
            'Id' => 14,
            'Label' => 'Action (High speed)',
        ),
        14 => array(
            'Id' => 16,
            'Label' => 'Sports',
        ),
        15 => array(
            'Id' => 17,
            'Label' => 'Handheld Night Shot',
        ),
        16 => array(
            'Id' => 18,
            'Label' => 'Anti Motion Blur',
        ),
        17 => array(
            'Id' => 19,
            'Label' => 'High Sensitivity',
        ),
        18 => array(
            'Id' => 21,
            'Label' => 'Beach',
        ),
        19 => array(
            'Id' => 22,
            'Label' => 'Snow',
        ),
        20 => array(
            'Id' => 23,
            'Label' => 'Fireworks',
        ),
        21 => array(
            'Id' => 26,
            'Label' => 'Underwater',
        ),
        22 => array(
            'Id' => 27,
            'Label' => 'Gourmet',
        ),
        23 => array(
            'Id' => 28,
            'Label' => 'Pet',
        ),
        24 => array(
            'Id' => 29,
            'Label' => 'Macro',
        ),
        25 => array(
            'Id' => 30,
            'Label' => 'Backlight Correction HDR',
        ),
        26 => array(
            'Id' => 33,
            'Label' => 'Sweep Panorama',
        ),
        27 => array(
            'Id' => 36,
            'Label' => 'Background Defocus',
        ),
        28 => array(
            'Id' => 37,
            'Label' => 'Soft Skin',
        ),
        29 => array(
            'Id' => 42,
            'Label' => '3D Image',
        ),
        30 => array(
            'Id' => 43,
            'Label' => 'Cont. Priority AE',
        ),
        31 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        32 => array(
            'Id' => 1,
            'Label' => 'Manual',
        ),
        33 => array(
            'Id' => 2,
            'Label' => 'Program AE',
        ),
        34 => array(
            'Id' => 3,
            'Label' => 'Aperture-priority AE',
        ),
        35 => array(
            'Id' => 4,
            'Label' => 'Shutter speed priority AE',
        ),
        36 => array(
            'Id' => 8,
            'Label' => 'Program Shift A',
        ),
        37 => array(
            'Id' => 9,
            'Label' => 'Program Shift S',
        ),
        38 => array(
            'Id' => 16,
            'Label' => 'Portrait',
        ),
        39 => array(
            'Id' => 17,
            'Label' => 'Sports',
        ),
        40 => array(
            'Id' => 18,
            'Label' => 'Sunset',
        ),
        41 => array(
            'Id' => 19,
            'Label' => 'Night Portrait',
        ),
        42 => array(
            'Id' => 20,
            'Label' => 'Landscape',
        ),
        43 => array(
            'Id' => 21,
            'Label' => 'Macro',
        ),
        44 => array(
            'Id' => 35,
            'Label' => 'Auto No Flash',
        ),
        45 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        46 => array(
            'Id' => 1,
            'Label' => 'Manual',
        ),
        47 => array(
            'Id' => 2,
            'Label' => 'Program AE',
        ),
        48 => array(
            'Id' => 3,
            'Label' => 'Aperture-priority AE',
        ),
        49 => array(
            'Id' => 4,
            'Label' => 'Shutter speed priority AE',
        ),
        50 => array(
            'Id' => 8,
            'Label' => 'Program Shift A',
        ),
        51 => array(
            'Id' => 9,
            'Label' => 'Program Shift S',
        ),
        52 => array(
            'Id' => 16,
            'Label' => 'Portrait',
        ),
        53 => array(
            'Id' => 17,
            'Label' => 'Sports',
        ),
        54 => array(
            'Id' => 18,
            'Label' => 'Sunset',
        ),
        55 => array(
            'Id' => 19,
            'Label' => 'Night Portrait',
        ),
        56 => array(
            'Id' => 20,
            'Label' => 'Landscape',
        ),
        57 => array(
            'Id' => 21,
            'Label' => 'Macro',
        ),
        58 => array(
            'Id' => 35,
            'Label' => 'Auto No Flash',
        ),
        59 => array(
            'Id' => 1,
            'Label' => 'Program AE',
        ),
        60 => array(
            'Id' => 2,
            'Label' => 'Aperture-priority AE',
        ),
        61 => array(
            'Id' => 3,
            'Label' => 'Shutter speed priority AE',
        ),
        62 => array(
            'Id' => 4,
            'Label' => 'Manual',
        ),
        63 => array(
            'Id' => 5,
            'Label' => 'Cont. Priority AE',
        ),
        64 => array(
            'Id' => 16,
            'Label' => 'Auto',
        ),
        65 => array(
            'Id' => 17,
            'Label' => 'Auto (no flash)',
        ),
        66 => array(
            'Id' => 18,
            'Label' => 'Auto+',
        ),
        67 => array(
            'Id' => 49,
            'Label' => 'Portrait',
        ),
        68 => array(
            'Id' => 50,
            'Label' => 'Landscape',
        ),
        69 => array(
            'Id' => 51,
            'Label' => 'Macro',
        ),
        70 => array(
            'Id' => 52,
            'Label' => 'Sports',
        ),
        71 => array(
            'Id' => 53,
            'Label' => 'Sunset',
        ),
        72 => array(
            'Id' => 54,
            'Label' => 'Night view',
        ),
        73 => array(
            'Id' => 55,
            'Label' => 'Night view/portrait',
        ),
        74 => array(
            'Id' => 56,
            'Label' => 'Handheld Night Shot',
        ),
        75 => array(
            'Id' => 57,
            'Label' => '3D Sweep Panorama',
        ),
        76 => array(
            'Id' => 64,
            'Label' => 'Auto 2',
        ),
        77 => array(
            'Id' => 65,
            'Label' => 'Auto 2 (no flash)',
        ),
        78 => array(
            'Id' => 80,
            'Label' => 'Sweep Panorama',
        ),
        79 => array(
            'Id' => 96,
            'Label' => 'Anti Motion Blur',
        ),
        80 => array(
            'Id' => 128,
            'Label' => 'Toy Camera',
        ),
        81 => array(
            'Id' => 129,
            'Label' => 'Pop Color',
        ),
        82 => array(
            'Id' => 130,
            'Label' => 'Posterization',
        ),
        83 => array(
            'Id' => 131,
            'Label' => 'Posterization B/W',
        ),
        84 => array(
            'Id' => 132,
            'Label' => 'Retro Photo',
        ),
        85 => array(
            'Id' => 133,
            'Label' => 'High-key',
        ),
        86 => array(
            'Id' => 134,
            'Label' => 'Partial Color Red',
        ),
        87 => array(
            'Id' => 135,
            'Label' => 'Partial Color Green',
        ),
        88 => array(
            'Id' => 136,
            'Label' => 'Partial Color Blue',
        ),
        89 => array(
            'Id' => 137,
            'Label' => 'Partial Color Yellow',
        ),
        90 => array(
            'Id' => 138,
            'Label' => 'High Contrast Monochrome',
        ),
        91 => array(
            'Id' => 241,
            'Label' => 'Landscape',
        ),
        92 => array(
            'Id' => 243,
            'Label' => 'Aperture-priority AE',
        ),
        93 => array(
            'Id' => 245,
            'Label' => 'Portrait',
        ),
        94 => array(
            'Id' => 246,
            'Label' => 'Auto',
        ),
        95 => array(
            'Id' => 247,
            'Label' => 'Program AE',
        ),
        96 => array(
            'Id' => 249,
            'Label' => 'Macro',
        ),
        97 => array(
            'Id' => 252,
            'Label' => 'Sunset',
        ),
        98 => array(
            'Id' => 253,
            'Label' => 'Sports',
        ),
        99 => array(
            'Id' => 255,
            'Label' => 'Manual',
        ),
        100 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        101 => array(
            'Id' => 1,
            'Label' => 'Manual',
        ),
        102 => array(
            'Id' => 2,
            'Label' => 'Program AE',
        ),
        103 => array(
            'Id' => 3,
            'Label' => 'Aperture-priority AE',
        ),
        104 => array(
            'Id' => 4,
            'Label' => 'Shutter speed priority AE',
        ),
        105 => array(
            'Id' => 8,
            'Label' => 'Program Shift A',
        ),
        106 => array(
            'Id' => 9,
            'Label' => 'Program Shift S',
        ),
        107 => array(
            'Id' => 16,
            'Label' => 'Portrait',
        ),
        108 => array(
            'Id' => 17,
            'Label' => 'Sports',
        ),
        109 => array(
            'Id' => 18,
            'Label' => 'Sunset',
        ),
        110 => array(
            'Id' => 19,
            'Label' => 'Night Portrait',
        ),
        111 => array(
            'Id' => 20,
            'Label' => 'Landscape',
        ),
        112 => array(
            'Id' => 21,
            'Label' => 'Macro',
        ),
        113 => array(
            'Id' => 35,
            'Label' => 'Auto No Flash',
        ),
        114 => array(
            'Id' => 1,
            'Label' => 'Program AE',
        ),
        115 => array(
            'Id' => 2,
            'Label' => 'Aperture-priority AE',
        ),
        116 => array(
            'Id' => 3,
            'Label' => 'Shutter speed priority AE',
        ),
        117 => array(
            'Id' => 4,
            'Label' => 'Manual',
        ),
        118 => array(
            'Id' => 5,
            'Label' => 'Cont. Priority AE',
        ),
        119 => array(
            'Id' => 16,
            'Label' => 'Auto',
        ),
        120 => array(
            'Id' => 17,
            'Label' => 'Auto (no flash)',
        ),
        121 => array(
            'Id' => 18,
            'Label' => 'Auto+',
        ),
        122 => array(
            'Id' => 49,
            'Label' => 'Portrait',
        ),
        123 => array(
            'Id' => 50,
            'Label' => 'Landscape',
        ),
        124 => array(
            'Id' => 51,
            'Label' => 'Macro',
        ),
        125 => array(
            'Id' => 52,
            'Label' => 'Sports',
        ),
        126 => array(
            'Id' => 53,
            'Label' => 'Sunset',
        ),
        127 => array(
            'Id' => 54,
            'Label' => 'Night view',
        ),
        128 => array(
            'Id' => 55,
            'Label' => 'Night view/portrait',
        ),
        129 => array(
            'Id' => 56,
            'Label' => 'Handheld Night Shot',
        ),
        130 => array(
            'Id' => 57,
            'Label' => '3D Sweep Panorama',
        ),
        131 => array(
            'Id' => 64,
            'Label' => 'Auto 2',
        ),
        132 => array(
            'Id' => 65,
            'Label' => 'Auto 2 (no flash)',
        ),
        133 => array(
            'Id' => 80,
            'Label' => 'Sweep Panorama',
        ),
        134 => array(
            'Id' => 96,
            'Label' => 'Anti Motion Blur',
        ),
        135 => array(
            'Id' => 128,
            'Label' => 'Toy Camera',
        ),
        136 => array(
            'Id' => 129,
            'Label' => 'Pop Color',
        ),
        137 => array(
            'Id' => 130,
            'Label' => 'Posterization',
        ),
        138 => array(
            'Id' => 131,
            'Label' => 'Posterization B/W',
        ),
        139 => array(
            'Id' => 132,
            'Label' => 'Retro Photo',
        ),
        140 => array(
            'Id' => 133,
            'Label' => 'High-key',
        ),
        141 => array(
            'Id' => 134,
            'Label' => 'Partial Color Red',
        ),
        142 => array(
            'Id' => 135,
            'Label' => 'Partial Color Green',
        ),
        143 => array(
            'Id' => 136,
            'Label' => 'Partial Color Blue',
        ),
        144 => array(
            'Id' => 137,
            'Label' => 'Partial Color Yellow',
        ),
        145 => array(
            'Id' => 138,
            'Label' => 'High Contrast Monochrome',
        ),
        146 => array(
            'Id' => 0,
            'Label' => 'Program AE',
        ),
        147 => array(
            'Id' => 1,
            'Label' => 'Aperture-priority AE',
        ),
        148 => array(
            'Id' => 2,
            'Label' => 'Shutter speed priority AE',
        ),
        149 => array(
            'Id' => 3,
            'Label' => 'Manual',
        ),
        150 => array(
            'Id' => 4,
            'Label' => 'Auto',
        ),
        151 => array(
            'Id' => 5,
            'Label' => 'iAuto',
        ),
        152 => array(
            'Id' => 6,
            'Label' => 'Superior Auto',
        ),
        153 => array(
            'Id' => 7,
            'Label' => 'iAuto+',
        ),
        154 => array(
            'Id' => 8,
            'Label' => 'Portrait',
        ),
        155 => array(
            'Id' => 9,
            'Label' => 'Landscape',
        ),
        156 => array(
            'Id' => 10,
            'Label' => 'Twilight',
        ),
        157 => array(
            'Id' => 11,
            'Label' => 'Twilight Portrait',
        ),
        158 => array(
            'Id' => 12,
            'Label' => 'Sunset',
        ),
        159 => array(
            'Id' => 14,
            'Label' => 'Action (High speed)',
        ),
        160 => array(
            'Id' => 16,
            'Label' => 'Sports',
        ),
        161 => array(
            'Id' => 17,
            'Label' => 'Handheld Night Shot',
        ),
        162 => array(
            'Id' => 18,
            'Label' => 'Anti Motion Blur',
        ),
        163 => array(
            'Id' => 19,
            'Label' => 'High Sensitivity',
        ),
        164 => array(
            'Id' => 21,
            'Label' => 'Beach',
        ),
        165 => array(
            'Id' => 22,
            'Label' => 'Snow',
        ),
        166 => array(
            'Id' => 23,
            'Label' => 'Fireworks',
        ),
        167 => array(
            'Id' => 26,
            'Label' => 'Underwater',
        ),
        168 => array(
            'Id' => 27,
            'Label' => 'Gourmet',
        ),
        169 => array(
            'Id' => 28,
            'Label' => 'Pet',
        ),
        170 => array(
            'Id' => 29,
            'Label' => 'Macro',
        ),
        171 => array(
            'Id' => 30,
            'Label' => 'Backlight Correction HDR',
        ),
        172 => array(
            'Id' => 33,
            'Label' => 'Sweep Panorama',
        ),
        173 => array(
            'Id' => 36,
            'Label' => 'Background Defocus',
        ),
        174 => array(
            'Id' => 37,
            'Label' => 'Soft Skin',
        ),
        175 => array(
            'Id' => 42,
            'Label' => '3D Image',
        ),
        176 => array(
            'Id' => 43,
            'Label' => 'Cont. Priority AE',
        ),
        177 => array(
            'Id' => 0,
            'Label' => 'Program AE',
        ),
        178 => array(
            'Id' => 1,
            'Label' => 'Aperture-priority AE',
        ),
        179 => array(
            'Id' => 2,
            'Label' => 'Shutter speed priority AE',
        ),
        180 => array(
            'Id' => 3,
            'Label' => 'Manual',
        ),
        181 => array(
            'Id' => 4,
            'Label' => 'Auto',
        ),
        182 => array(
            'Id' => 5,
            'Label' => 'iAuto',
        ),
        183 => array(
            'Id' => 6,
            'Label' => 'Superior Auto',
        ),
        184 => array(
            'Id' => 7,
            'Label' => 'iAuto+',
        ),
        185 => array(
            'Id' => 8,
            'Label' => 'Portrait',
        ),
        186 => array(
            'Id' => 9,
            'Label' => 'Landscape',
        ),
        187 => array(
            'Id' => 10,
            'Label' => 'Twilight',
        ),
        188 => array(
            'Id' => 11,
            'Label' => 'Twilight Portrait',
        ),
        189 => array(
            'Id' => 12,
            'Label' => 'Sunset',
        ),
        190 => array(
            'Id' => 14,
            'Label' => 'Action (High speed)',
        ),
        191 => array(
            'Id' => 16,
            'Label' => 'Sports',
        ),
        192 => array(
            'Id' => 17,
            'Label' => 'Handheld Night Shot',
        ),
        193 => array(
            'Id' => 18,
            'Label' => 'Anti Motion Blur',
        ),
        194 => array(
            'Id' => 19,
            'Label' => 'High Sensitivity',
        ),
        195 => array(
            'Id' => 21,
            'Label' => 'Beach',
        ),
        196 => array(
            'Id' => 22,
            'Label' => 'Snow',
        ),
        197 => array(
            'Id' => 23,
            'Label' => 'Fireworks',
        ),
        198 => array(
            'Id' => 26,
            'Label' => 'Underwater',
        ),
        199 => array(
            'Id' => 27,
            'Label' => 'Gourmet',
        ),
        200 => array(
            'Id' => 28,
            'Label' => 'Pet',
        ),
        201 => array(
            'Id' => 29,
            'Label' => 'Macro',
        ),
        202 => array(
            'Id' => 30,
            'Label' => 'Backlight Correction HDR',
        ),
        203 => array(
            'Id' => 33,
            'Label' => 'Sweep Panorama',
        ),
        204 => array(
            'Id' => 36,
            'Label' => 'Background Defocus',
        ),
        205 => array(
            'Id' => 37,
            'Label' => 'Soft Skin',
        ),
        206 => array(
            'Id' => 42,
            'Label' => '3D Image',
        ),
        207 => array(
            'Id' => 43,
            'Label' => 'Cont. Priority AE',
        ),
        208 => array(
            'Id' => 0,
            'Label' => 'Program AE',
        ),
        209 => array(
            'Id' => 1,
            'Label' => 'Aperture-priority AE',
        ),
        210 => array(
            'Id' => 2,
            'Label' => 'Shutter speed priority AE',
        ),
        211 => array(
            'Id' => 3,
            'Label' => 'Manual',
        ),
        212 => array(
            'Id' => 4,
            'Label' => 'Auto',
        ),
        213 => array(
            'Id' => 5,
            'Label' => 'iAuto',
        ),
        214 => array(
            'Id' => 6,
            'Label' => 'Superior Auto',
        ),
        215 => array(
            'Id' => 7,
            'Label' => 'iAuto+',
        ),
        216 => array(
            'Id' => 8,
            'Label' => 'Portrait',
        ),
        217 => array(
            'Id' => 9,
            'Label' => 'Landscape',
        ),
        218 => array(
            'Id' => 10,
            'Label' => 'Twilight',
        ),
        219 => array(
            'Id' => 11,
            'Label' => 'Twilight Portrait',
        ),
        220 => array(
            'Id' => 12,
            'Label' => 'Sunset',
        ),
        221 => array(
            'Id' => 14,
            'Label' => 'Action (High speed)',
        ),
        222 => array(
            'Id' => 16,
            'Label' => 'Sports',
        ),
        223 => array(
            'Id' => 17,
            'Label' => 'Handheld Night Shot',
        ),
        224 => array(
            'Id' => 18,
            'Label' => 'Anti Motion Blur',
        ),
        225 => array(
            'Id' => 19,
            'Label' => 'High Sensitivity',
        ),
        226 => array(
            'Id' => 21,
            'Label' => 'Beach',
        ),
        227 => array(
            'Id' => 22,
            'Label' => 'Snow',
        ),
        228 => array(
            'Id' => 23,
            'Label' => 'Fireworks',
        ),
        229 => array(
            'Id' => 26,
            'Label' => 'Underwater',
        ),
        230 => array(
            'Id' => 27,
            'Label' => 'Gourmet',
        ),
        231 => array(
            'Id' => 28,
            'Label' => 'Pet',
        ),
        232 => array(
            'Id' => 29,
            'Label' => 'Macro',
        ),
        233 => array(
            'Id' => 30,
            'Label' => 'Backlight Correction HDR',
        ),
        234 => array(
            'Id' => 33,
            'Label' => 'Sweep Panorama',
        ),
        235 => array(
            'Id' => 36,
            'Label' => 'Background Defocus',
        ),
        236 => array(
            'Id' => 37,
            'Label' => 'Soft Skin',
        ),
        237 => array(
            'Id' => 42,
            'Label' => '3D Image',
        ),
        238 => array(
            'Id' => 43,
            'Label' => 'Cont. Priority AE',
        ),
        239 => array(
            'Id' => 0,
            'Label' => 'Program AE',
        ),
        240 => array(
            'Id' => 1,
            'Label' => 'Aperture-priority AE',
        ),
        241 => array(
            'Id' => 2,
            'Label' => 'Shutter speed priority AE',
        ),
        242 => array(
            'Id' => 3,
            'Label' => 'Manual',
        ),
        243 => array(
            'Id' => 4,
            'Label' => 'Auto',
        ),
        244 => array(
            'Id' => 5,
            'Label' => 'iAuto',
        ),
        245 => array(
            'Id' => 6,
            'Label' => 'Superior Auto',
        ),
        246 => array(
            'Id' => 7,
            'Label' => 'iAuto+',
        ),
        247 => array(
            'Id' => 8,
            'Label' => 'Portrait',
        ),
        248 => array(
            'Id' => 9,
            'Label' => 'Landscape',
        ),
        249 => array(
            'Id' => 10,
            'Label' => 'Twilight',
        ),
        250 => array(
            'Id' => 11,
            'Label' => 'Twilight Portrait',
        ),
        251 => array(
            'Id' => 12,
            'Label' => 'Sunset',
        ),
        252 => array(
            'Id' => 14,
            'Label' => 'Action (High speed)',
        ),
        253 => array(
            'Id' => 16,
            'Label' => 'Sports',
        ),
        254 => array(
            'Id' => 17,
            'Label' => 'Handheld Night Shot',
        ),
        255 => array(
            'Id' => 18,
            'Label' => 'Anti Motion Blur',
        ),
        256 => array(
            'Id' => 19,
            'Label' => 'High Sensitivity',
        ),
        257 => array(
            'Id' => 21,
            'Label' => 'Beach',
        ),
        258 => array(
            'Id' => 22,
            'Label' => 'Snow',
        ),
        259 => array(
            'Id' => 23,
            'Label' => 'Fireworks',
        ),
        260 => array(
            'Id' => 26,
            'Label' => 'Underwater',
        ),
        261 => array(
            'Id' => 27,
            'Label' => 'Gourmet',
        ),
        262 => array(
            'Id' => 28,
            'Label' => 'Pet',
        ),
        263 => array(
            'Id' => 29,
            'Label' => 'Macro',
        ),
        264 => array(
            'Id' => 30,
            'Label' => 'Backlight Correction HDR',
        ),
        265 => array(
            'Id' => 33,
            'Label' => 'Sweep Panorama',
        ),
        266 => array(
            'Id' => 36,
            'Label' => 'Background Defocus',
        ),
        267 => array(
            'Id' => 37,
            'Label' => 'Soft Skin',
        ),
        268 => array(
            'Id' => 42,
            'Label' => '3D Image',
        ),
        269 => array(
            'Id' => 43,
            'Label' => 'Cont. Priority AE',
        ),
        270 => array(
            'Id' => 0,
            'Label' => 'Program AE',
        ),
        271 => array(
            'Id' => 1,
            'Label' => 'Aperture-priority AE',
        ),
        272 => array(
            'Id' => 2,
            'Label' => 'Shutter speed priority AE',
        ),
        273 => array(
            'Id' => 3,
            'Label' => 'Manual',
        ),
        274 => array(
            'Id' => 4,
            'Label' => 'Auto',
        ),
        275 => array(
            'Id' => 5,
            'Label' => 'iAuto',
        ),
        276 => array(
            'Id' => 6,
            'Label' => 'Superior Auto',
        ),
        277 => array(
            'Id' => 7,
            'Label' => 'iAuto+',
        ),
        278 => array(
            'Id' => 8,
            'Label' => 'Portrait',
        ),
        279 => array(
            'Id' => 9,
            'Label' => 'Landscape',
        ),
        280 => array(
            'Id' => 10,
            'Label' => 'Twilight',
        ),
        281 => array(
            'Id' => 11,
            'Label' => 'Twilight Portrait',
        ),
        282 => array(
            'Id' => 12,
            'Label' => 'Sunset',
        ),
        283 => array(
            'Id' => 14,
            'Label' => 'Action (High speed)',
        ),
        284 => array(
            'Id' => 16,
            'Label' => 'Sports',
        ),
        285 => array(
            'Id' => 17,
            'Label' => 'Handheld Night Shot',
        ),
        286 => array(
            'Id' => 18,
            'Label' => 'Anti Motion Blur',
        ),
        287 => array(
            'Id' => 19,
            'Label' => 'High Sensitivity',
        ),
        288 => array(
            'Id' => 21,
            'Label' => 'Beach',
        ),
        289 => array(
            'Id' => 22,
            'Label' => 'Snow',
        ),
        290 => array(
            'Id' => 23,
            'Label' => 'Fireworks',
        ),
        291 => array(
            'Id' => 26,
            'Label' => 'Underwater',
        ),
        292 => array(
            'Id' => 27,
            'Label' => 'Gourmet',
        ),
        293 => array(
            'Id' => 28,
            'Label' => 'Pet',
        ),
        294 => array(
            'Id' => 29,
            'Label' => 'Macro',
        ),
        295 => array(
            'Id' => 30,
            'Label' => 'Backlight Correction HDR',
        ),
        296 => array(
            'Id' => 33,
            'Label' => 'Sweep Panorama',
        ),
        297 => array(
            'Id' => 36,
            'Label' => 'Background Defocus',
        ),
        298 => array(
            'Id' => 37,
            'Label' => 'Soft Skin',
        ),
        299 => array(
            'Id' => 42,
            'Label' => '3D Image',
        ),
        300 => array(
            'Id' => 43,
            'Label' => 'Cont. Priority AE',
        ),
        301 => array(
            'Id' => 0,
            'Label' => 'Program AE',
        ),
        302 => array(
            'Id' => 1,
            'Label' => 'Aperture-priority AE',
        ),
        303 => array(
            'Id' => 2,
            'Label' => 'Shutter speed priority AE',
        ),
        304 => array(
            'Id' => 3,
            'Label' => 'Manual',
        ),
        305 => array(
            'Id' => 4,
            'Label' => 'Auto',
        ),
        306 => array(
            'Id' => 5,
            'Label' => 'iAuto',
        ),
        307 => array(
            'Id' => 6,
            'Label' => 'Superior Auto',
        ),
        308 => array(
            'Id' => 7,
            'Label' => 'iAuto+',
        ),
        309 => array(
            'Id' => 8,
            'Label' => 'Portrait',
        ),
        310 => array(
            'Id' => 9,
            'Label' => 'Landscape',
        ),
        311 => array(
            'Id' => 10,
            'Label' => 'Twilight',
        ),
        312 => array(
            'Id' => 11,
            'Label' => 'Twilight Portrait',
        ),
        313 => array(
            'Id' => 12,
            'Label' => 'Sunset',
        ),
        314 => array(
            'Id' => 14,
            'Label' => 'Action (High speed)',
        ),
        315 => array(
            'Id' => 16,
            'Label' => 'Sports',
        ),
        316 => array(
            'Id' => 17,
            'Label' => 'Handheld Night Shot',
        ),
        317 => array(
            'Id' => 18,
            'Label' => 'Anti Motion Blur',
        ),
        318 => array(
            'Id' => 19,
            'Label' => 'High Sensitivity',
        ),
        319 => array(
            'Id' => 21,
            'Label' => 'Beach',
        ),
        320 => array(
            'Id' => 22,
            'Label' => 'Snow',
        ),
        321 => array(
            'Id' => 23,
            'Label' => 'Fireworks',
        ),
        322 => array(
            'Id' => 26,
            'Label' => 'Underwater',
        ),
        323 => array(
            'Id' => 27,
            'Label' => 'Gourmet',
        ),
        324 => array(
            'Id' => 28,
            'Label' => 'Pet',
        ),
        325 => array(
            'Id' => 29,
            'Label' => 'Macro',
        ),
        326 => array(
            'Id' => 30,
            'Label' => 'Backlight Correction HDR',
        ),
        327 => array(
            'Id' => 33,
            'Label' => 'Sweep Panorama',
        ),
        328 => array(
            'Id' => 36,
            'Label' => 'Background Defocus',
        ),
        329 => array(
            'Id' => 37,
            'Label' => 'Soft Skin',
        ),
        330 => array(
            'Id' => 42,
            'Label' => '3D Image',
        ),
        331 => array(
            'Id' => 43,
            'Label' => 'Cont. Priority AE',
        ),
        332 => array(
            'Id' => 0,
            'Label' => 'Program AE',
        ),
        333 => array(
            'Id' => 1,
            'Label' => 'Aperture-priority AE',
        ),
        334 => array(
            'Id' => 2,
            'Label' => 'Shutter speed priority AE',
        ),
        335 => array(
            'Id' => 3,
            'Label' => 'Manual',
        ),
        336 => array(
            'Id' => 4,
            'Label' => 'Auto',
        ),
        337 => array(
            'Id' => 5,
            'Label' => 'iAuto',
        ),
        338 => array(
            'Id' => 6,
            'Label' => 'Superior Auto',
        ),
        339 => array(
            'Id' => 7,
            'Label' => 'iAuto+',
        ),
        340 => array(
            'Id' => 8,
            'Label' => 'Portrait',
        ),
        341 => array(
            'Id' => 9,
            'Label' => 'Landscape',
        ),
        342 => array(
            'Id' => 10,
            'Label' => 'Twilight',
        ),
        343 => array(
            'Id' => 11,
            'Label' => 'Twilight Portrait',
        ),
        344 => array(
            'Id' => 12,
            'Label' => 'Sunset',
        ),
        345 => array(
            'Id' => 14,
            'Label' => 'Action (High speed)',
        ),
        346 => array(
            'Id' => 16,
            'Label' => 'Sports',
        ),
        347 => array(
            'Id' => 17,
            'Label' => 'Handheld Night Shot',
        ),
        348 => array(
            'Id' => 18,
            'Label' => 'Anti Motion Blur',
        ),
        349 => array(
            'Id' => 19,
            'Label' => 'High Sensitivity',
        ),
        350 => array(
            'Id' => 21,
            'Label' => 'Beach',
        ),
        351 => array(
            'Id' => 22,
            'Label' => 'Snow',
        ),
        352 => array(
            'Id' => 23,
            'Label' => 'Fireworks',
        ),
        353 => array(
            'Id' => 26,
            'Label' => 'Underwater',
        ),
        354 => array(
            'Id' => 27,
            'Label' => 'Gourmet',
        ),
        355 => array(
            'Id' => 28,
            'Label' => 'Pet',
        ),
        356 => array(
            'Id' => 29,
            'Label' => 'Macro',
        ),
        357 => array(
            'Id' => 30,
            'Label' => 'Backlight Correction HDR',
        ),
        358 => array(
            'Id' => 33,
            'Label' => 'Sweep Panorama',
        ),
        359 => array(
            'Id' => 36,
            'Label' => 'Background Defocus',
        ),
        360 => array(
            'Id' => 37,
            'Label' => 'Soft Skin',
        ),
        361 => array(
            'Id' => 42,
            'Label' => '3D Image',
        ),
        362 => array(
            'Id' => 43,
            'Label' => 'Cont. Priority AE',
        ),
        363 => array(
            'Id' => 0,
            'Label' => 'Program AE',
        ),
        364 => array(
            'Id' => 1,
            'Label' => 'Aperture-priority AE',
        ),
        365 => array(
            'Id' => 2,
            'Label' => 'Shutter speed priority AE',
        ),
        366 => array(
            'Id' => 3,
            'Label' => 'Manual',
        ),
        367 => array(
            'Id' => 4,
            'Label' => 'Auto',
        ),
        368 => array(
            'Id' => 5,
            'Label' => 'iAuto',
        ),
        369 => array(
            'Id' => 6,
            'Label' => 'Superior Auto',
        ),
        370 => array(
            'Id' => 7,
            'Label' => 'iAuto+',
        ),
        371 => array(
            'Id' => 8,
            'Label' => 'Portrait',
        ),
        372 => array(
            'Id' => 9,
            'Label' => 'Landscape',
        ),
        373 => array(
            'Id' => 10,
            'Label' => 'Twilight',
        ),
        374 => array(
            'Id' => 11,
            'Label' => 'Twilight Portrait',
        ),
        375 => array(
            'Id' => 12,
            'Label' => 'Sunset',
        ),
        376 => array(
            'Id' => 14,
            'Label' => 'Action (High speed)',
        ),
        377 => array(
            'Id' => 16,
            'Label' => 'Sports',
        ),
        378 => array(
            'Id' => 17,
            'Label' => 'Handheld Night Shot',
        ),
        379 => array(
            'Id' => 18,
            'Label' => 'Anti Motion Blur',
        ),
        380 => array(
            'Id' => 19,
            'Label' => 'High Sensitivity',
        ),
        381 => array(
            'Id' => 21,
            'Label' => 'Beach',
        ),
        382 => array(
            'Id' => 22,
            'Label' => 'Snow',
        ),
        383 => array(
            'Id' => 23,
            'Label' => 'Fireworks',
        ),
        384 => array(
            'Id' => 26,
            'Label' => 'Underwater',
        ),
        385 => array(
            'Id' => 27,
            'Label' => 'Gourmet',
        ),
        386 => array(
            'Id' => 28,
            'Label' => 'Pet',
        ),
        387 => array(
            'Id' => 29,
            'Label' => 'Macro',
        ),
        388 => array(
            'Id' => 30,
            'Label' => 'Backlight Correction HDR',
        ),
        389 => array(
            'Id' => 33,
            'Label' => 'Sweep Panorama',
        ),
        390 => array(
            'Id' => 36,
            'Label' => 'Background Defocus',
        ),
        391 => array(
            'Id' => 37,
            'Label' => 'Soft Skin',
        ),
        392 => array(
            'Id' => 42,
            'Label' => '3D Image',
        ),
        393 => array(
            'Id' => 43,
            'Label' => 'Cont. Priority AE',
        ),
        394 => array(
            'Id' => 0,
            'Label' => 'Program AE',
        ),
        395 => array(
            'Id' => 1,
            'Label' => 'Aperture-priority AE',
        ),
        396 => array(
            'Id' => 2,
            'Label' => 'Shutter speed priority AE',
        ),
        397 => array(
            'Id' => 3,
            'Label' => 'Manual',
        ),
        398 => array(
            'Id' => 4,
            'Label' => 'Auto',
        ),
        399 => array(
            'Id' => 5,
            'Label' => 'iAuto',
        ),
        400 => array(
            'Id' => 6,
            'Label' => 'Superior Auto',
        ),
        401 => array(
            'Id' => 7,
            'Label' => 'iAuto+',
        ),
        402 => array(
            'Id' => 8,
            'Label' => 'Portrait',
        ),
        403 => array(
            'Id' => 9,
            'Label' => 'Landscape',
        ),
        404 => array(
            'Id' => 10,
            'Label' => 'Twilight',
        ),
        405 => array(
            'Id' => 11,
            'Label' => 'Twilight Portrait',
        ),
        406 => array(
            'Id' => 12,
            'Label' => 'Sunset',
        ),
        407 => array(
            'Id' => 14,
            'Label' => 'Action (High speed)',
        ),
        408 => array(
            'Id' => 16,
            'Label' => 'Sports',
        ),
        409 => array(
            'Id' => 17,
            'Label' => 'Handheld Night Shot',
        ),
        410 => array(
            'Id' => 18,
            'Label' => 'Anti Motion Blur',
        ),
        411 => array(
            'Id' => 19,
            'Label' => 'High Sensitivity',
        ),
        412 => array(
            'Id' => 21,
            'Label' => 'Beach',
        ),
        413 => array(
            'Id' => 22,
            'Label' => 'Snow',
        ),
        414 => array(
            'Id' => 23,
            'Label' => 'Fireworks',
        ),
        415 => array(
            'Id' => 26,
            'Label' => 'Underwater',
        ),
        416 => array(
            'Id' => 27,
            'Label' => 'Gourmet',
        ),
        417 => array(
            'Id' => 28,
            'Label' => 'Pet',
        ),
        418 => array(
            'Id' => 29,
            'Label' => 'Macro',
        ),
        419 => array(
            'Id' => 30,
            'Label' => 'Backlight Correction HDR',
        ),
        420 => array(
            'Id' => 33,
            'Label' => 'Sweep Panorama',
        ),
        421 => array(
            'Id' => 36,
            'Label' => 'Background Defocus',
        ),
        422 => array(
            'Id' => 37,
            'Label' => 'Soft Skin',
        ),
        423 => array(
            'Id' => 42,
            'Label' => '3D Image',
        ),
        424 => array(
            'Id' => 43,
            'Label' => 'Cont. Priority AE',
        ),
    );

    protected $Index = 'mixed';

}
