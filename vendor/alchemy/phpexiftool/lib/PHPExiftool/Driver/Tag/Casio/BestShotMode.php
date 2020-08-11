<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Casio;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class BestShotMode extends AbstractTag
{

    protected $Id = 12295;

    protected $Name = 'BestShotMode';

    protected $FullName = 'Casio::Type2';

    protected $GroupName = 'Casio';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Casio';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Best Shot Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Auto',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Portrait',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Scenery',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Portrait with Scenery',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Children',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Sports',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Pet',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Flower',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Natural Green',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Autumn Leaves',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Sundown',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'High Speed Night Scene',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'Night Scene Portrait',
        ),
        14 => array(
            'Id' => 14,
            'Label' => 'Fireworks',
        ),
        15 => array(
            'Id' => 15,
            'Label' => 'High Speed Anti Shake',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Multi-motion Image',
        ),
        17 => array(
            'Id' => 17,
            'Label' => 'High Speed Best Selection',
        ),
        18 => array(
            'Id' => 18,
            'Label' => 'Move Out CS',
        ),
        19 => array(
            'Id' => 19,
            'Label' => 'Move In CS',
        ),
        20 => array(
            'Id' => 20,
            'Label' => 'Pre-record Movie',
        ),
        21 => array(
            'Id' => 21,
            'Label' => 'For YouTube',
        ),
        22 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        23 => array(
            'Id' => 1,
            'Label' => 'Auto',
        ),
        24 => array(
            'Id' => 2,
            'Label' => 'Expression CS',
        ),
        25 => array(
            'Id' => 3,
            'Label' => 'Baby CS',
        ),
        26 => array(
            'Id' => 4,
            'Label' => 'Child CS',
        ),
        27 => array(
            'Id' => 5,
            'Label' => 'Pet CS',
        ),
        28 => array(
            'Id' => 6,
            'Label' => 'Sports CS',
        ),
        29 => array(
            'Id' => 7,
            'Label' => 'Child High Speed Movie',
        ),
        30 => array(
            'Id' => 8,
            'Label' => 'Pet High Speed Movie',
        ),
        31 => array(
            'Id' => 9,
            'Label' => 'Sports High Speed Movie',
        ),
        32 => array(
            'Id' => 10,
            'Label' => 'Lag Correction',
        ),
        33 => array(
            'Id' => 11,
            'Label' => 'High Speed Lighting',
        ),
        34 => array(
            'Id' => 12,
            'Label' => 'High Speed Night Scene',
        ),
        35 => array(
            'Id' => 13,
            'Label' => 'High Speed Night Scene and Portrait',
        ),
        36 => array(
            'Id' => 14,
            'Label' => 'High Speed Anti Shake',
        ),
        37 => array(
            'Id' => 15,
            'Label' => 'High Speed Best Selection',
        ),
        38 => array(
            'Id' => 16,
            'Label' => 'Portrait',
        ),
        39 => array(
            'Id' => 17,
            'Label' => 'Scenery',
        ),
        40 => array(
            'Id' => 18,
            'Label' => 'Portrait With Scenery',
        ),
        41 => array(
            'Id' => 19,
            'Label' => 'Flower',
        ),
        42 => array(
            'Id' => 20,
            'Label' => 'Natural Green',
        ),
        43 => array(
            'Id' => 21,
            'Label' => 'Autumn Leaves',
        ),
        44 => array(
            'Id' => 22,
            'Label' => 'Sundown',
        ),
        45 => array(
            'Id' => 23,
            'Label' => 'Fireworks',
        ),
        46 => array(
            'Id' => 24,
            'Label' => 'Multi-motion Image',
        ),
        47 => array(
            'Id' => 25,
            'Label' => 'Move Out CS',
        ),
        48 => array(
            'Id' => 26,
            'Label' => 'Move In CS',
        ),
        49 => array(
            'Id' => 27,
            'Label' => 'Pre-record Movie',
        ),
        50 => array(
            'Id' => 28,
            'Label' => 'For YouTube',
        ),
        51 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        52 => array(
            'Id' => 1,
            'Label' => 'Slow Motion Swing (behind)',
        ),
        53 => array(
            'Id' => 2,
            'Label' => 'Slow Motion Swing (front)',
        ),
        54 => array(
            'Id' => 3,
            'Label' => 'Self Slow Motion (behind)',
        ),
        55 => array(
            'Id' => 4,
            'Label' => 'Self Slow Motion (front)',
        ),
        56 => array(
            'Id' => 5,
            'Label' => 'Swing Burst',
        ),
        57 => array(
            'Id' => 6,
            'Label' => 'HDR',
        ),
        58 => array(
            'Id' => 7,
            'Label' => 'HDR Art',
        ),
        59 => array(
            'Id' => 8,
            'Label' => 'High Speed Night Scene',
        ),
        60 => array(
            'Id' => 9,
            'Label' => 'High Speed Night Scene and Portrait',
        ),
        61 => array(
            'Id' => 10,
            'Label' => 'High Speed Anti Shake',
        ),
        62 => array(
            'Id' => 11,
            'Label' => 'Multi SR Zoom',
        ),
        63 => array(
            'Id' => 12,
            'Label' => 'Blurred Background',
        ),
        64 => array(
            'Id' => 13,
            'Label' => 'Wide Shot',
        ),
        65 => array(
            'Id' => 14,
            'Label' => 'Slide Panorama',
        ),
        66 => array(
            'Id' => 15,
            'Label' => 'High Speed Best Selection',
        ),
        67 => array(
            'Id' => 16,
            'Label' => 'Lag Correction',
        ),
        68 => array(
            'Id' => 17,
            'Label' => 'High Speed CS',
        ),
        69 => array(
            'Id' => 18,
            'Label' => 'Child CS',
        ),
        70 => array(
            'Id' => 19,
            'Label' => 'Pet CS',
        ),
        71 => array(
            'Id' => 20,
            'Label' => 'Sports CS',
        ),
        72 => array(
            'Id' => 21,
            'Label' => 'Child High Speed Movie',
        ),
        73 => array(
            'Id' => 22,
            'Label' => 'Pet High Speed Movie',
        ),
        74 => array(
            'Id' => 23,
            'Label' => 'Sports High Speed Movie',
        ),
        75 => array(
            'Id' => 24,
            'Label' => 'Portrait',
        ),
        76 => array(
            'Id' => 25,
            'Label' => 'Scenery',
        ),
        77 => array(
            'Id' => 26,
            'Label' => 'Portrait with Scenery',
        ),
        78 => array(
            'Id' => 27,
            'Label' => 'Children',
        ),
        79 => array(
            'Id' => 28,
            'Label' => 'Sports',
        ),
        80 => array(
            'Id' => 29,
            'Label' => 'Candlelight Portrait',
        ),
        81 => array(
            'Id' => 30,
            'Label' => 'Party',
        ),
        82 => array(
            'Id' => 31,
            'Label' => 'Pet',
        ),
        83 => array(
            'Id' => 32,
            'Label' => 'Flower',
        ),
        84 => array(
            'Id' => 33,
            'Label' => 'Natural Green',
        ),
        85 => array(
            'Id' => 34,
            'Label' => 'Autumn Leaves',
        ),
        86 => array(
            'Id' => 35,
            'Label' => 'Soft Flowing Water',
        ),
        87 => array(
            'Id' => 36,
            'Label' => 'Splashing Water',
        ),
        88 => array(
            'Id' => 37,
            'Label' => 'Sundown',
        ),
        89 => array(
            'Id' => 38,
            'Label' => 'Fireworks',
        ),
        90 => array(
            'Id' => 39,
            'Label' => 'Food',
        ),
        91 => array(
            'Id' => 40,
            'Label' => 'Text',
        ),
        92 => array(
            'Id' => 41,
            'Label' => 'Collection',
        ),
        93 => array(
            'Id' => 42,
            'Label' => 'Auction',
        ),
        94 => array(
            'Id' => 43,
            'Label' => 'Pre-record Movie',
        ),
        95 => array(
            'Id' => 44,
            'Label' => 'For YouTube',
        ),
        96 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        97 => array(
            'Id' => 1,
            'Label' => 'Expression CS',
        ),
        98 => array(
            'Id' => 2,
            'Label' => 'Baby CS',
        ),
        99 => array(
            'Id' => 3,
            'Label' => 'Child CS',
        ),
        100 => array(
            'Id' => 4,
            'Label' => 'Pet CS',
        ),
        101 => array(
            'Id' => 5,
            'Label' => 'Sports CS',
        ),
        102 => array(
            'Id' => 6,
            'Label' => 'Child High Speed Movie',
        ),
        103 => array(
            'Id' => 7,
            'Label' => 'Pet High Speed Movie',
        ),
        104 => array(
            'Id' => 8,
            'Label' => 'Sports High Speed Movie',
        ),
        105 => array(
            'Id' => 9,
            'Label' => 'Lag Correction',
        ),
        106 => array(
            'Id' => 10,
            'Label' => 'High Speed Lighting',
        ),
        107 => array(
            'Id' => 11,
            'Label' => 'High Speed Night Scene',
        ),
        108 => array(
            'Id' => 12,
            'Label' => 'High Speed Night Scene and Portrait',
        ),
        109 => array(
            'Id' => 13,
            'Label' => 'High Speed Anti Shake',
        ),
        110 => array(
            'Id' => 14,
            'Label' => 'High Speed Best Selection',
        ),
        111 => array(
            'Id' => 15,
            'Label' => 'Portrait',
        ),
        112 => array(
            'Id' => 16,
            'Label' => 'Scenery',
        ),
        113 => array(
            'Id' => 17,
            'Label' => 'Portrait With Scenery',
        ),
        114 => array(
            'Id' => 18,
            'Label' => 'Flower',
        ),
        115 => array(
            'Id' => 19,
            'Label' => 'Natural Green',
        ),
        116 => array(
            'Id' => 20,
            'Label' => 'Autumn Leaves',
        ),
        117 => array(
            'Id' => 21,
            'Label' => 'Sundown',
        ),
        118 => array(
            'Id' => 22,
            'Label' => 'Fireworks',
        ),
        119 => array(
            'Id' => 23,
            'Label' => 'Multi-motion Image',
        ),
        120 => array(
            'Id' => 24,
            'Label' => 'Move Out CS',
        ),
        121 => array(
            'Id' => 25,
            'Label' => 'Move In CS',
        ),
        122 => array(
            'Id' => 26,
            'Label' => 'Pre-record Movie',
        ),
        123 => array(
            'Id' => 27,
            'Label' => 'For YouTube',
        ),
        124 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        125 => array(
            'Id' => 1,
            'Label' => 'Auto',
        ),
        126 => array(
            'Id' => 2,
            'Label' => 'Auto Best Shot',
        ),
        127 => array(
            'Id' => 3,
            'Label' => 'Dynamic Photo',
        ),
        128 => array(
            'Id' => 4,
            'Label' => 'Interval Snapshot',
        ),
        129 => array(
            'Id' => 5,
            'Label' => 'Interval Movie',
        ),
        130 => array(
            'Id' => 6,
            'Label' => 'Portrait',
        ),
        131 => array(
            'Id' => 7,
            'Label' => 'Scenery',
        ),
        132 => array(
            'Id' => 8,
            'Label' => 'Portrait with Scenery',
        ),
        133 => array(
            'Id' => 9,
            'Label' => 'Underwater',
        ),
        134 => array(
            'Id' => 10,
            'Label' => 'Beach',
        ),
        135 => array(
            'Id' => 11,
            'Label' => 'Snow',
        ),
        136 => array(
            'Id' => 12,
            'Label' => 'Children',
        ),
        137 => array(
            'Id' => 13,
            'Label' => 'Sports',
        ),
        138 => array(
            'Id' => 14,
            'Label' => 'Pet',
        ),
        139 => array(
            'Id' => 15,
            'Label' => 'Flower',
        ),
        140 => array(
            'Id' => 16,
            'Label' => 'Sundown',
        ),
        141 => array(
            'Id' => 17,
            'Label' => 'Night Scene',
        ),
        142 => array(
            'Id' => 18,
            'Label' => 'Night Scene Portrait',
        ),
        143 => array(
            'Id' => 19,
            'Label' => 'Fireworks',
        ),
        144 => array(
            'Id' => 20,
            'Label' => 'Food',
        ),
        145 => array(
            'Id' => 21,
            'Label' => 'For eBay',
        ),
        146 => array(
            'Id' => 22,
            'Label' => 'Multi-motion Image',
        ),
        147 => array(
            'Id' => 23,
            'Label' => 'Pre-record Movie',
        ),
        148 => array(
            'Id' => 24,
            'Label' => 'For YouTube',
        ),
        149 => array(
            'Id' => 25,
            'Label' => 'Voice Recording',
        ),
        150 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        151 => array(
            'Id' => 1,
            'Label' => 'Auto',
        ),
        152 => array(
            'Id' => 2,
            'Label' => 'Portrait',
        ),
        153 => array(
            'Id' => 3,
            'Label' => 'Scenery',
        ),
        154 => array(
            'Id' => 4,
            'Label' => 'Portrait with Scenery',
        ),
        155 => array(
            'Id' => 5,
            'Label' => 'Self-portrait (1 person)',
        ),
        156 => array(
            'Id' => 6,
            'Label' => 'Self-portrait (2 people)',
        ),
        157 => array(
            'Id' => 7,
            'Label' => 'Children',
        ),
        158 => array(
            'Id' => 8,
            'Label' => 'Sports',
        ),
        159 => array(
            'Id' => 9,
            'Label' => 'Candlelight Portrait',
        ),
        160 => array(
            'Id' => 10,
            'Label' => 'Party',
        ),
        161 => array(
            'Id' => 11,
            'Label' => 'Pet',
        ),
        162 => array(
            'Id' => 12,
            'Label' => 'Flower',
        ),
        163 => array(
            'Id' => 13,
            'Label' => 'Natural Green',
        ),
        164 => array(
            'Id' => 14,
            'Label' => 'Autumn Leaves',
        ),
        165 => array(
            'Id' => 15,
            'Label' => 'Soft Flowing Water',
        ),
        166 => array(
            'Id' => 16,
            'Label' => 'Splashing Water',
        ),
        167 => array(
            'Id' => 17,
            'Label' => 'Sundown',
        ),
        168 => array(
            'Id' => 18,
            'Label' => 'Night Scene',
        ),
        169 => array(
            'Id' => 19,
            'Label' => 'Night Scene Portrait',
        ),
        170 => array(
            'Id' => 20,
            'Label' => 'Fireworks',
        ),
        171 => array(
            'Id' => 21,
            'Label' => 'Food',
        ),
        172 => array(
            'Id' => 22,
            'Label' => 'Text',
        ),
        173 => array(
            'Id' => 23,
            'Label' => 'Collection',
        ),
        174 => array(
            'Id' => 24,
            'Label' => 'Auction',
        ),
        175 => array(
            'Id' => 25,
            'Label' => 'Backlight',
        ),
        176 => array(
            'Id' => 26,
            'Label' => 'Anti Shake',
        ),
        177 => array(
            'Id' => 27,
            'Label' => 'High Sensitivity',
        ),
        178 => array(
            'Id' => 28,
            'Label' => 'Underwater',
        ),
        179 => array(
            'Id' => 29,
            'Label' => 'Monochrome',
        ),
        180 => array(
            'Id' => 30,
            'Label' => 'Retro',
        ),
        181 => array(
            'Id' => 31,
            'Label' => 'Business Cards',
        ),
        182 => array(
            'Id' => 32,
            'Label' => 'White Board',
        ),
        183 => array(
            'Id' => 33,
            'Label' => 'Silent',
        ),
        184 => array(
            'Id' => 34,
            'Label' => 'Pre-record Movie',
        ),
        185 => array(
            'Id' => 35,
            'Label' => 'For YouTube',
        ),
        186 => array(
            'Id' => 36,
            'Label' => 'Voice Recording',
        ),
        187 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        188 => array(
            'Id' => 1,
            'Label' => 'Auto',
        ),
        189 => array(
            'Id' => 2,
            'Label' => 'Portrait',
        ),
        190 => array(
            'Id' => 3,
            'Label' => 'Scenery',
        ),
        191 => array(
            'Id' => 4,
            'Label' => 'Portrait with Scenery',
        ),
        192 => array(
            'Id' => 5,
            'Label' => 'Children',
        ),
        193 => array(
            'Id' => 6,
            'Label' => 'Sports',
        ),
        194 => array(
            'Id' => 7,
            'Label' => 'Candlelight Portrait',
        ),
        195 => array(
            'Id' => 8,
            'Label' => 'Party',
        ),
        196 => array(
            'Id' => 9,
            'Label' => 'Pet',
        ),
        197 => array(
            'Id' => 10,
            'Label' => 'Flower',
        ),
        198 => array(
            'Id' => 11,
            'Label' => 'Natural Green',
        ),
        199 => array(
            'Id' => 12,
            'Label' => 'Autumn Leaves',
        ),
        200 => array(
            'Id' => 13,
            'Label' => 'Soft Flowing Water',
        ),
        201 => array(
            'Id' => 14,
            'Label' => 'Splashing Water',
        ),
        202 => array(
            'Id' => 15,
            'Label' => 'Sundown',
        ),
        203 => array(
            'Id' => 16,
            'Label' => 'Night Scene',
        ),
        204 => array(
            'Id' => 17,
            'Label' => 'Night Scene Portrait',
        ),
        205 => array(
            'Id' => 18,
            'Label' => 'Fireworks',
        ),
        206 => array(
            'Id' => 19,
            'Label' => 'Food',
        ),
        207 => array(
            'Id' => 20,
            'Label' => 'Text',
        ),
        208 => array(
            'Id' => 21,
            'Label' => 'Collection',
        ),
        209 => array(
            'Id' => 22,
            'Label' => 'Auction',
        ),
        210 => array(
            'Id' => 23,
            'Label' => 'Backlight',
        ),
        211 => array(
            'Id' => 24,
            'Label' => 'Anti Shake',
        ),
        212 => array(
            'Id' => 25,
            'Label' => 'High Sensitivity',
        ),
        213 => array(
            'Id' => 26,
            'Label' => 'Monochrome',
        ),
        214 => array(
            'Id' => 27,
            'Label' => 'Retro',
        ),
        215 => array(
            'Id' => 28,
            'Label' => 'Twilight',
        ),
        216 => array(
            'Id' => 29,
            'Label' => 'Layout (2 images)',
        ),
        217 => array(
            'Id' => 30,
            'Label' => 'Layout (3 images)',
        ),
        218 => array(
            'Id' => 31,
            'Label' => 'Auto Framing',
        ),
        219 => array(
            'Id' => 32,
            'Label' => 'Old Photo',
        ),
        220 => array(
            'Id' => 33,
            'Label' => 'Business Cards',
        ),
        221 => array(
            'Id' => 34,
            'Label' => 'White Board',
        ),
        222 => array(
            'Id' => 35,
            'Label' => 'Silent',
        ),
        223 => array(
            'Id' => 36,
            'Label' => 'Short Movie',
        ),
        224 => array(
            'Id' => 37,
            'Label' => 'Past Movie',
        ),
        225 => array(
            'Id' => 38,
            'Label' => 'For YouTube',
        ),
        226 => array(
            'Id' => 39,
            'Label' => 'Voice Recording',
        ),
        227 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        228 => array(
            'Id' => 1,
            'Label' => 'Auto',
        ),
        229 => array(
            'Id' => 2,
            'Label' => 'Portrait',
        ),
        230 => array(
            'Id' => 3,
            'Label' => 'Scenery',
        ),
        231 => array(
            'Id' => 4,
            'Label' => 'Portrait with Scenery',
        ),
        232 => array(
            'Id' => 5,
            'Label' => 'Children',
        ),
        233 => array(
            'Id' => 6,
            'Label' => 'Sports',
        ),
        234 => array(
            'Id' => 7,
            'Label' => 'Candlelight Portrait',
        ),
        235 => array(
            'Id' => 8,
            'Label' => 'Party',
        ),
        236 => array(
            'Id' => 9,
            'Label' => 'Pet',
        ),
        237 => array(
            'Id' => 10,
            'Label' => 'Flower',
        ),
        238 => array(
            'Id' => 11,
            'Label' => 'Soft Flowing Water',
        ),
        239 => array(
            'Id' => 12,
            'Label' => 'Sundown',
        ),
        240 => array(
            'Id' => 13,
            'Label' => 'Night Scene',
        ),
        241 => array(
            'Id' => 14,
            'Label' => 'Night Scene Portrait',
        ),
        242 => array(
            'Id' => 15,
            'Label' => 'Fireworks',
        ),
        243 => array(
            'Id' => 16,
            'Label' => 'Food',
        ),
        244 => array(
            'Id' => 17,
            'Label' => 'Text',
        ),
        245 => array(
            'Id' => 18,
            'Label' => 'For eBay',
        ),
        246 => array(
            'Id' => 19,
            'Label' => 'Backlight',
        ),
        247 => array(
            'Id' => 20,
            'Label' => 'Anti Shake',
        ),
        248 => array(
            'Id' => 21,
            'Label' => 'High Sensitivity',
        ),
        249 => array(
            'Id' => 22,
            'Label' => 'For YouTube',
        ),
        250 => array(
            'Id' => 23,
            'Label' => 'Voice Recording',
        ),
        251 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        252 => array(
            'Id' => 1,
            'Label' => 'Auto',
        ),
        253 => array(
            'Id' => 2,
            'Label' => 'Movie',
        ),
        254 => array(
            'Id' => 3,
            'Label' => 'Portrait',
        ),
        255 => array(
            'Id' => 4,
            'Label' => 'Scenery',
        ),
        256 => array(
            'Id' => 5,
            'Label' => 'Children',
        ),
        257 => array(
            'Id' => 6,
            'Label' => 'Sports',
        ),
        258 => array(
            'Id' => 7,
            'Label' => 'Candlelight Portrait',
        ),
        259 => array(
            'Id' => 8,
            'Label' => 'Party',
        ),
        260 => array(
            'Id' => 9,
            'Label' => 'Pet',
        ),
        261 => array(
            'Id' => 10,
            'Label' => 'Flower',
        ),
        262 => array(
            'Id' => 11,
            'Label' => 'Soft Flowing Water',
        ),
        263 => array(
            'Id' => 12,
            'Label' => 'Sundown',
        ),
        264 => array(
            'Id' => 13,
            'Label' => 'Night Scene',
        ),
        265 => array(
            'Id' => 14,
            'Label' => 'Night Scene Portrait',
        ),
        266 => array(
            'Id' => 15,
            'Label' => 'Fireworks',
        ),
        267 => array(
            'Id' => 16,
            'Label' => 'Food',
        ),
        268 => array(
            'Id' => 17,
            'Label' => 'Text',
        ),
        269 => array(
            'Id' => 18,
            'Label' => 'Auction',
        ),
        270 => array(
            'Id' => 19,
            'Label' => 'Backlight',
        ),
        271 => array(
            'Id' => 20,
            'Label' => 'Anti Shake',
        ),
        272 => array(
            'Id' => 21,
            'Label' => 'High Sensitivity',
        ),
        273 => array(
            'Id' => 22,
            'Label' => 'For YouTube',
        ),
        274 => array(
            'Id' => 23,
            'Label' => 'Voice Recording',
        ),
        275 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        276 => array(
            'Id' => 1,
            'Label' => 'Auto',
        ),
        277 => array(
            'Id' => 2,
            'Label' => 'Portrait',
        ),
        278 => array(
            'Id' => 3,
            'Label' => 'Scenery',
        ),
        279 => array(
            'Id' => 4,
            'Label' => 'Portrait with Scenery',
        ),
        280 => array(
            'Id' => 5,
            'Label' => 'Pet',
        ),
        281 => array(
            'Id' => 6,
            'Label' => 'Self-portrait (1 person)',
        ),
        282 => array(
            'Id' => 7,
            'Label' => 'Self-portrait (2 people)',
        ),
        283 => array(
            'Id' => 8,
            'Label' => 'Flower',
        ),
        284 => array(
            'Id' => 9,
            'Label' => 'Food',
        ),
        285 => array(
            'Id' => 10,
            'Label' => 'Fashion Accessories',
        ),
        286 => array(
            'Id' => 11,
            'Label' => 'Magazine',
        ),
        287 => array(
            'Id' => 12,
            'Label' => 'Monochrome',
        ),
        288 => array(
            'Id' => 13,
            'Label' => 'Retro',
        ),
        289 => array(
            'Id' => 14,
            'Label' => 'Cross Filter',
        ),
        290 => array(
            'Id' => 15,
            'Label' => 'Pastel',
        ),
        291 => array(
            'Id' => 16,
            'Label' => 'Night Scene',
        ),
        292 => array(
            'Id' => 17,
            'Label' => 'Night Scene Portrait',
        ),
        293 => array(
            'Id' => 18,
            'Label' => 'Party',
        ),
        294 => array(
            'Id' => 19,
            'Label' => 'Sports',
        ),
        295 => array(
            'Id' => 20,
            'Label' => 'Children',
        ),
        296 => array(
            'Id' => 21,
            'Label' => 'Sundown',
        ),
        297 => array(
            'Id' => 22,
            'Label' => 'Fireworks',
        ),
        298 => array(
            'Id' => 23,
            'Label' => 'Underwater',
        ),
        299 => array(
            'Id' => 24,
            'Label' => 'Backlight',
        ),
        300 => array(
            'Id' => 25,
            'Label' => 'High Sensitivity',
        ),
        301 => array(
            'Id' => 26,
            'Label' => 'Auction',
        ),
        302 => array(
            'Id' => 27,
            'Label' => 'White Board',
        ),
        303 => array(
            'Id' => 28,
            'Label' => 'Pre-record Movie',
        ),
        304 => array(
            'Id' => 29,
            'Label' => 'For YouTube',
        ),
        305 => array(
            'Id' => 30,
            'Label' => 'Voice Recording',
        ),
        306 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        307 => array(
            'Id' => 1,
            'Label' => 'Auto',
        ),
        308 => array(
            'Id' => 2,
            'Label' => 'Auto Best Shot',
        ),
        309 => array(
            'Id' => 3,
            'Label' => 'Portrait',
        ),
        310 => array(
            'Id' => 4,
            'Label' => 'Scenery',
        ),
        311 => array(
            'Id' => 5,
            'Label' => 'Portrait with Scenery',
        ),
        312 => array(
            'Id' => 6,
            'Label' => 'Self-portrait (1 person)',
        ),
        313 => array(
            'Id' => 7,
            'Label' => 'Self-portrait (2 people)',
        ),
        314 => array(
            'Id' => 8,
            'Label' => 'Children',
        ),
        315 => array(
            'Id' => 9,
            'Label' => 'Sports',
        ),
        316 => array(
            'Id' => 10,
            'Label' => 'Candlelight Portrait',
        ),
        317 => array(
            'Id' => 11,
            'Label' => 'Party',
        ),
        318 => array(
            'Id' => 12,
            'Label' => 'Pet',
        ),
        319 => array(
            'Id' => 13,
            'Label' => 'Flower',
        ),
        320 => array(
            'Id' => 14,
            'Label' => 'Natural Green',
        ),
        321 => array(
            'Id' => 15,
            'Label' => 'Autumn Leaves',
        ),
        322 => array(
            'Id' => 16,
            'Label' => 'Soft Flowing Water',
        ),
        323 => array(
            'Id' => 17,
            'Label' => 'Splashing Water',
        ),
        324 => array(
            'Id' => 18,
            'Label' => 'Sundown',
        ),
        325 => array(
            'Id' => 19,
            'Label' => 'Night Scene',
        ),
        326 => array(
            'Id' => 20,
            'Label' => 'Night Scene Portrait',
        ),
        327 => array(
            'Id' => 21,
            'Label' => 'Fireworks',
        ),
        328 => array(
            'Id' => 22,
            'Label' => 'Food',
        ),
        329 => array(
            'Id' => 23,
            'Label' => 'Text',
        ),
        330 => array(
            'Id' => 24,
            'Label' => 'Collection',
        ),
        331 => array(
            'Id' => 25,
            'Label' => 'Auction',
        ),
        332 => array(
            'Id' => 26,
            'Label' => 'Backlight',
        ),
        333 => array(
            'Id' => 27,
            'Label' => 'Anti Shake',
        ),
        334 => array(
            'Id' => 28,
            'Label' => 'High Sensitivity',
        ),
        335 => array(
            'Id' => 29,
            'Label' => 'Underwater',
        ),
        336 => array(
            'Id' => 30,
            'Label' => 'Monochrome',
        ),
        337 => array(
            'Id' => 31,
            'Label' => 'Retro',
        ),
        338 => array(
            'Id' => 32,
            'Label' => 'Twilight',
        ),
        339 => array(
            'Id' => 33,
            'Label' => 'ID Photo',
        ),
        340 => array(
            'Id' => 34,
            'Label' => 'Business Cards',
        ),
        341 => array(
            'Id' => 35,
            'Label' => 'White Board',
        ),
        342 => array(
            'Id' => 36,
            'Label' => 'Silent',
        ),
        343 => array(
            'Id' => 37,
            'Label' => 'Pre-record Movie',
        ),
        344 => array(
            'Id' => 38,
            'Label' => 'For YouTube',
        ),
        345 => array(
            'Id' => 39,
            'Label' => 'Voice Recording',
        ),
        346 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        347 => array(
            'Id' => 1,
            'Label' => 'Portrait',
        ),
        348 => array(
            'Id' => 2,
            'Label' => 'Scenery',
        ),
        349 => array(
            'Id' => 3,
            'Label' => 'Portrait with Scenery',
        ),
        350 => array(
            'Id' => 4,
            'Label' => 'Children',
        ),
        351 => array(
            'Id' => 5,
            'Label' => 'Sports',
        ),
        352 => array(
            'Id' => 6,
            'Label' => 'Candlelight Portrait',
        ),
        353 => array(
            'Id' => 7,
            'Label' => 'Party',
        ),
        354 => array(
            'Id' => 8,
            'Label' => 'Pet',
        ),
        355 => array(
            'Id' => 9,
            'Label' => 'Flower',
        ),
        356 => array(
            'Id' => 10,
            'Label' => 'Natural Green',
        ),
        357 => array(
            'Id' => 11,
            'Label' => 'Soft Flowing Water',
        ),
        358 => array(
            'Id' => 12,
            'Label' => 'Splashing Water',
        ),
        359 => array(
            'Id' => 13,
            'Label' => 'Sundown',
        ),
        360 => array(
            'Id' => 14,
            'Label' => 'Night Scene',
        ),
        361 => array(
            'Id' => 15,
            'Label' => 'Night Scene Portrait',
        ),
        362 => array(
            'Id' => 16,
            'Label' => 'Fireworks',
        ),
        363 => array(
            'Id' => 17,
            'Label' => 'Food',
        ),
        364 => array(
            'Id' => 18,
            'Label' => 'Text',
        ),
        365 => array(
            'Id' => 19,
            'Label' => 'Collection',
        ),
        366 => array(
            'Id' => 20,
            'Label' => 'Backlight',
        ),
        367 => array(
            'Id' => 21,
            'Label' => 'Anti Shake',
        ),
        368 => array(
            'Id' => 22,
            'Label' => 'Pastel',
        ),
        369 => array(
            'Id' => 23,
            'Label' => 'Illustration',
        ),
        370 => array(
            'Id' => 24,
            'Label' => 'Cross Filter',
        ),
        371 => array(
            'Id' => 25,
            'Label' => 'Monochrome',
        ),
        372 => array(
            'Id' => 26,
            'Label' => 'Retro',
        ),
        373 => array(
            'Id' => 27,
            'Label' => 'Twilight',
        ),
        374 => array(
            'Id' => 28,
            'Label' => 'Old Photo',
        ),
        375 => array(
            'Id' => 29,
            'Label' => 'ID Photo',
        ),
        376 => array(
            'Id' => 30,
            'Label' => 'Business Cards',
        ),
        377 => array(
            'Id' => 31,
            'Label' => 'White Board',
        ),
        378 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        379 => array(
            'Id' => 1,
            'Label' => 'Portrait',
        ),
        380 => array(
            'Id' => 2,
            'Label' => 'Scenery',
        ),
        381 => array(
            'Id' => 3,
            'Label' => 'Night Scene',
        ),
        382 => array(
            'Id' => 4,
            'Label' => 'Fireworks',
        ),
        383 => array(
            'Id' => 5,
            'Label' => 'Backlight',
        ),
        384 => array(
            'Id' => 6,
            'Label' => 'Silent',
        ),
        385 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        386 => array(
            'Id' => 1,
            'Label' => 'Portrait',
        ),
        387 => array(
            'Id' => 2,
            'Label' => 'Scenery',
        ),
        388 => array(
            'Id' => 3,
            'Label' => 'Portrait with Scenery',
        ),
        389 => array(
            'Id' => 4,
            'Label' => 'Children',
        ),
        390 => array(
            'Id' => 5,
            'Label' => 'Sports',
        ),
        391 => array(
            'Id' => 6,
            'Label' => 'Candlelight Portrait',
        ),
        392 => array(
            'Id' => 7,
            'Label' => 'Party',
        ),
        393 => array(
            'Id' => 8,
            'Label' => 'Pet',
        ),
        394 => array(
            'Id' => 9,
            'Label' => 'Flower',
        ),
        395 => array(
            'Id' => 10,
            'Label' => 'Natural Green',
        ),
        396 => array(
            'Id' => 11,
            'Label' => 'Autumn Leaves',
        ),
        397 => array(
            'Id' => 12,
            'Label' => 'Soft Flowing Water',
        ),
        398 => array(
            'Id' => 13,
            'Label' => 'Splashing Water',
        ),
        399 => array(
            'Id' => 14,
            'Label' => 'Sundown',
        ),
        400 => array(
            'Id' => 15,
            'Label' => 'Night Scene',
        ),
        401 => array(
            'Id' => 16,
            'Label' => 'Night Scene Portrait',
        ),
        402 => array(
            'Id' => 17,
            'Label' => 'Fireworks',
        ),
        403 => array(
            'Id' => 18,
            'Label' => 'Food',
        ),
        404 => array(
            'Id' => 19,
            'Label' => 'Text',
        ),
        405 => array(
            'Id' => 20,
            'Label' => 'Collection',
        ),
        406 => array(
            'Id' => 21,
            'Label' => 'For eBay',
        ),
        407 => array(
            'Id' => 22,
            'Label' => 'Backlight',
        ),
        408 => array(
            'Id' => 23,
            'Label' => 'Anti Shake',
        ),
        409 => array(
            'Id' => 24,
            'Label' => 'High Sensitivity',
        ),
        410 => array(
            'Id' => 25,
            'Label' => 'Pastel',
        ),
        411 => array(
            'Id' => 26,
            'Label' => 'Illustration',
        ),
        412 => array(
            'Id' => 27,
            'Label' => 'Cross Filter',
        ),
        413 => array(
            'Id' => 28,
            'Label' => 'Monochrome',
        ),
        414 => array(
            'Id' => 29,
            'Label' => 'Retro',
        ),
        415 => array(
            'Id' => 30,
            'Label' => 'Twilight',
        ),
        416 => array(
            'Id' => 31,
            'Label' => 'ID Photo',
        ),
        417 => array(
            'Id' => 32,
            'Label' => 'Old Photo',
        ),
        418 => array(
            'Id' => 33,
            'Label' => 'Business Cards',
        ),
        419 => array(
            'Id' => 34,
            'Label' => 'White Board',
        ),
        420 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        421 => array(
            'Id' => 1,
            'Label' => 'Portrait',
        ),
        422 => array(
            'Id' => 2,
            'Label' => 'Scenery',
        ),
        423 => array(
            'Id' => 3,
            'Label' => 'Night Scene',
        ),
        424 => array(
            'Id' => 4,
            'Label' => 'Fireworks',
        ),
        425 => array(
            'Id' => 5,
            'Label' => 'Backlight',
        ),
        426 => array(
            'Id' => 6,
            'Label' => 'High Sensitivity',
        ),
        427 => array(
            'Id' => 7,
            'Label' => 'Silent',
        ),
        428 => array(
            'Id' => 8,
            'Label' => 'Short Movie',
        ),
        429 => array(
            'Id' => 9,
            'Label' => 'Past Movie',
        ),
        430 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        431 => array(
            'Id' => 1,
            'Label' => 'Auto',
        ),
        432 => array(
            'Id' => 2,
            'Label' => 'Movie',
        ),
        433 => array(
            'Id' => 3,
            'Label' => 'Portrait',
        ),
        434 => array(
            'Id' => 4,
            'Label' => 'Scenery',
        ),
        435 => array(
            'Id' => 5,
            'Label' => 'Portrait with Scenery',
        ),
        436 => array(
            'Id' => 6,
            'Label' => 'Children',
        ),
        437 => array(
            'Id' => 7,
            'Label' => 'Sports',
        ),
        438 => array(
            'Id' => 8,
            'Label' => 'Candlelight Portrait',
        ),
        439 => array(
            'Id' => 9,
            'Label' => 'Party',
        ),
        440 => array(
            'Id' => 10,
            'Label' => 'Pet',
        ),
        441 => array(
            'Id' => 11,
            'Label' => 'Flower',
        ),
        442 => array(
            'Id' => 12,
            'Label' => 'Natural Green',
        ),
        443 => array(
            'Id' => 13,
            'Label' => 'Autumn Leaves',
        ),
        444 => array(
            'Id' => 14,
            'Label' => 'Soft Flowing Water',
        ),
        445 => array(
            'Id' => 15,
            'Label' => 'Splashing Water',
        ),
        446 => array(
            'Id' => 16,
            'Label' => 'Sundown',
        ),
        447 => array(
            'Id' => 17,
            'Label' => 'Night Scene',
        ),
        448 => array(
            'Id' => 18,
            'Label' => 'Night Scene Portrait',
        ),
        449 => array(
            'Id' => 19,
            'Label' => 'Fireworks',
        ),
        450 => array(
            'Id' => 20,
            'Label' => 'Food',
        ),
        451 => array(
            'Id' => 21,
            'Label' => 'Text',
        ),
        452 => array(
            'Id' => 22,
            'Label' => 'Collection',
        ),
        453 => array(
            'Id' => 23,
            'Label' => 'For eBay',
        ),
        454 => array(
            'Id' => 24,
            'Label' => 'Backlight',
        ),
        455 => array(
            'Id' => 25,
            'Label' => 'Anti Shake',
        ),
        456 => array(
            'Id' => 26,
            'Label' => 'High Sensitivity',
        ),
        457 => array(
            'Id' => 27,
            'Label' => 'Underwater',
        ),
        458 => array(
            'Id' => 28,
            'Label' => 'Monochrome',
        ),
        459 => array(
            'Id' => 29,
            'Label' => 'Retro',
        ),
        460 => array(
            'Id' => 30,
            'Label' => 'Twilight',
        ),
        461 => array(
            'Id' => 31,
            'Label' => 'Layout (2 images)',
        ),
        462 => array(
            'Id' => 32,
            'Label' => 'Layout (3 images)',
        ),
        463 => array(
            'Id' => 33,
            'Label' => 'Auto Framing',
        ),
        464 => array(
            'Id' => 34,
            'Label' => 'ID Photo',
        ),
        465 => array(
            'Id' => 35,
            'Label' => 'Old Photo',
        ),
        466 => array(
            'Id' => 36,
            'Label' => 'Business Cards',
        ),
        467 => array(
            'Id' => 37,
            'Label' => 'White Board',
        ),
        468 => array(
            'Id' => 38,
            'Label' => 'Voice Recording',
        ),
        469 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        470 => array(
            'Id' => 1,
            'Label' => 'Auto',
        ),
        471 => array(
            'Id' => 2,
            'Label' => 'Movie',
        ),
        472 => array(
            'Id' => 3,
            'Label' => 'Portrait',
        ),
        473 => array(
            'Id' => 4,
            'Label' => 'Scenery',
        ),
        474 => array(
            'Id' => 5,
            'Label' => 'Portrait with Scenery',
        ),
        475 => array(
            'Id' => 6,
            'Label' => 'Children',
        ),
        476 => array(
            'Id' => 7,
            'Label' => 'Sports',
        ),
        477 => array(
            'Id' => 8,
            'Label' => 'Candlelight Portrait',
        ),
        478 => array(
            'Id' => 9,
            'Label' => 'Party',
        ),
        479 => array(
            'Id' => 10,
            'Label' => 'Pet',
        ),
        480 => array(
            'Id' => 11,
            'Label' => 'Flower',
        ),
        481 => array(
            'Id' => 12,
            'Label' => 'Natural Green',
        ),
        482 => array(
            'Id' => 13,
            'Label' => 'Autumn Leaves',
        ),
        483 => array(
            'Id' => 14,
            'Label' => 'Soft Flowing Water',
        ),
        484 => array(
            'Id' => 15,
            'Label' => 'Splashing Water',
        ),
        485 => array(
            'Id' => 16,
            'Label' => 'Sundown',
        ),
        486 => array(
            'Id' => 17,
            'Label' => 'Night Scene',
        ),
        487 => array(
            'Id' => 18,
            'Label' => 'Night Scene Portrait',
        ),
        488 => array(
            'Id' => 19,
            'Label' => 'Fireworks',
        ),
        489 => array(
            'Id' => 20,
            'Label' => 'Food',
        ),
        490 => array(
            'Id' => 21,
            'Label' => 'Text',
        ),
        491 => array(
            'Id' => 22,
            'Label' => 'Collection',
        ),
        492 => array(
            'Id' => 23,
            'Label' => 'For eBay',
        ),
        493 => array(
            'Id' => 24,
            'Label' => 'Backlight',
        ),
        494 => array(
            'Id' => 25,
            'Label' => 'Anti Shake',
        ),
        495 => array(
            'Id' => 26,
            'Label' => 'High Sensitivity',
        ),
        496 => array(
            'Id' => 27,
            'Label' => 'Underwater',
        ),
        497 => array(
            'Id' => 28,
            'Label' => 'Monochrome',
        ),
        498 => array(
            'Id' => 29,
            'Label' => 'Retro',
        ),
        499 => array(
            'Id' => 30,
            'Label' => 'Twilight',
        ),
        500 => array(
            'Id' => 31,
            'Label' => 'Layout (2 images)',
        ),
        501 => array(
            'Id' => 32,
            'Label' => 'Layout (3 images)',
        ),
        502 => array(
            'Id' => 33,
            'Label' => 'Auto Framing',
        ),
        503 => array(
            'Id' => 34,
            'Label' => 'ID Photo',
        ),
        504 => array(
            'Id' => 35,
            'Label' => 'Old Photo',
        ),
        505 => array(
            'Id' => 36,
            'Label' => 'Business Cards',
        ),
        506 => array(
            'Id' => 37,
            'Label' => 'White Board',
        ),
        507 => array(
            'Id' => 38,
            'Label' => 'Short Movie',
        ),
        508 => array(
            'Id' => 39,
            'Label' => 'Past Movie',
        ),
        509 => array(
            'Id' => 40,
            'Label' => 'For YouTube',
        ),
        510 => array(
            'Id' => 41,
            'Label' => 'Voice Recording',
        ),
        511 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        512 => array(
            'Id' => 1,
            'Label' => 'Portrait',
        ),
        513 => array(
            'Id' => 2,
            'Label' => 'Scenery',
        ),
        514 => array(
            'Id' => 3,
            'Label' => 'Portrait with Scenery',
        ),
        515 => array(
            'Id' => 4,
            'Label' => 'Children',
        ),
        516 => array(
            'Id' => 5,
            'Label' => 'Sports',
        ),
        517 => array(
            'Id' => 6,
            'Label' => 'Candlelight Portrait',
        ),
        518 => array(
            'Id' => 7,
            'Label' => 'Party',
        ),
        519 => array(
            'Id' => 8,
            'Label' => 'Pet',
        ),
        520 => array(
            'Id' => 9,
            'Label' => 'Flower',
        ),
        521 => array(
            'Id' => 10,
            'Label' => 'Natural Green',
        ),
        522 => array(
            'Id' => 11,
            'Label' => 'Autumn Leaves',
        ),
        523 => array(
            'Id' => 12,
            'Label' => 'Soft Flowing Water',
        ),
        524 => array(
            'Id' => 13,
            'Label' => 'Splashing Water',
        ),
        525 => array(
            'Id' => 14,
            'Label' => 'Sundown',
        ),
        526 => array(
            'Id' => 15,
            'Label' => 'Night Scene',
        ),
        527 => array(
            'Id' => 16,
            'Label' => 'Night Scene Portrait',
        ),
        528 => array(
            'Id' => 17,
            'Label' => 'Fireworks',
        ),
        529 => array(
            'Id' => 18,
            'Label' => 'Food',
        ),
        530 => array(
            'Id' => 19,
            'Label' => 'Text',
        ),
        531 => array(
            'Id' => 20,
            'Label' => 'Collection',
        ),
        532 => array(
            'Id' => 21,
            'Label' => 'Auction',
        ),
        533 => array(
            'Id' => 22,
            'Label' => 'Backlight',
        ),
        534 => array(
            'Id' => 23,
            'Label' => 'High Sensitivity',
        ),
        535 => array(
            'Id' => 24,
            'Label' => 'Underwater',
        ),
        536 => array(
            'Id' => 25,
            'Label' => 'Monochrome',
        ),
        537 => array(
            'Id' => 26,
            'Label' => 'Retro',
        ),
        538 => array(
            'Id' => 27,
            'Label' => 'Twilight',
        ),
        539 => array(
            'Id' => 28,
            'Label' => 'Layout (2 images)',
        ),
        540 => array(
            'Id' => 29,
            'Label' => 'Layout (3 images)',
        ),
        541 => array(
            'Id' => 30,
            'Label' => 'Auto Framing',
        ),
        542 => array(
            'Id' => 31,
            'Label' => 'ID Photo',
        ),
        543 => array(
            'Id' => 32,
            'Label' => 'Old Photo',
        ),
        544 => array(
            'Id' => 33,
            'Label' => 'Business Cards',
        ),
        545 => array(
            'Id' => 34,
            'Label' => 'White Board',
        ),
        546 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        547 => array(
            'Id' => 1,
            'Label' => 'Portrait',
        ),
        548 => array(
            'Id' => 2,
            'Label' => 'Scenery',
        ),
        549 => array(
            'Id' => 3,
            'Label' => 'Night Scene',
        ),
        550 => array(
            'Id' => 4,
            'Label' => 'Fireworks',
        ),
        551 => array(
            'Id' => 5,
            'Label' => 'Backlight',
        ),
        552 => array(
            'Id' => 6,
            'Label' => 'High Sensitivity',
        ),
        553 => array(
            'Id' => 7,
            'Label' => 'Silent',
        ),
        554 => array(
            'Id' => 8,
            'Label' => 'Short Movie',
        ),
        555 => array(
            'Id' => 9,
            'Label' => 'Past Movie',
        ),
        556 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        557 => array(
            'Id' => 1,
            'Label' => 'Auto',
        ),
        558 => array(
            'Id' => 2,
            'Label' => 'Premium Auto',
        ),
        559 => array(
            'Id' => 3,
            'Label' => 'Dynamic Photo',
        ),
        560 => array(
            'Id' => 4,
            'Label' => 'Portrait',
        ),
        561 => array(
            'Id' => 5,
            'Label' => 'Scenery',
        ),
        562 => array(
            'Id' => 6,
            'Label' => 'Portrait with Scenery',
        ),
        563 => array(
            'Id' => 7,
            'Label' => 'Children',
        ),
        564 => array(
            'Id' => 8,
            'Label' => 'Sports',
        ),
        565 => array(
            'Id' => 9,
            'Label' => 'Candlelight Portrait',
        ),
        566 => array(
            'Id' => 10,
            'Label' => 'Party',
        ),
        567 => array(
            'Id' => 11,
            'Label' => 'Pet',
        ),
        568 => array(
            'Id' => 12,
            'Label' => 'Flower',
        ),
        569 => array(
            'Id' => 13,
            'Label' => 'Natural Green',
        ),
        570 => array(
            'Id' => 14,
            'Label' => 'Autumn Leaves',
        ),
        571 => array(
            'Id' => 15,
            'Label' => 'Soft Flowing Water',
        ),
        572 => array(
            'Id' => 16,
            'Label' => 'Splashing Water',
        ),
        573 => array(
            'Id' => 17,
            'Label' => 'Sundown',
        ),
        574 => array(
            'Id' => 18,
            'Label' => 'Night Scene',
        ),
        575 => array(
            'Id' => 19,
            'Label' => 'Night Scene Portrait',
        ),
        576 => array(
            'Id' => 20,
            'Label' => 'Fireworks',
        ),
        577 => array(
            'Id' => 21,
            'Label' => 'Food',
        ),
        578 => array(
            'Id' => 22,
            'Label' => 'Text',
        ),
        579 => array(
            'Id' => 23,
            'Label' => 'Collection',
        ),
        580 => array(
            'Id' => 24,
            'Label' => 'For eBay',
        ),
        581 => array(
            'Id' => 25,
            'Label' => 'Backlight',
        ),
        582 => array(
            'Id' => 26,
            'Label' => 'High Sensitivity',
        ),
        583 => array(
            'Id' => 27,
            'Label' => 'Oil Painting',
        ),
        584 => array(
            'Id' => 28,
            'Label' => 'Crayon',
        ),
        585 => array(
            'Id' => 29,
            'Label' => 'Water Color',
        ),
        586 => array(
            'Id' => 30,
            'Label' => 'Monochrome',
        ),
        587 => array(
            'Id' => 31,
            'Label' => 'Retro',
        ),
        588 => array(
            'Id' => 32,
            'Label' => 'Twilight',
        ),
        589 => array(
            'Id' => 33,
            'Label' => 'Multi-motion Image',
        ),
        590 => array(
            'Id' => 34,
            'Label' => 'ID Photo',
        ),
        591 => array(
            'Id' => 35,
            'Label' => 'Business Cards',
        ),
        592 => array(
            'Id' => 36,
            'Label' => 'White Board',
        ),
        593 => array(
            'Id' => 37,
            'Label' => 'Silent',
        ),
        594 => array(
            'Id' => 38,
            'Label' => 'Pre-record Movie',
        ),
        595 => array(
            'Id' => 39,
            'Label' => 'For YouTube',
        ),
        596 => array(
            'Id' => 40,
            'Label' => 'Voice Recording',
        ),
        597 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        598 => array(
            'Id' => 1,
            'Label' => 'Auto',
        ),
        599 => array(
            'Id' => 2,
            'Label' => 'Premium Auto',
        ),
        600 => array(
            'Id' => 3,
            'Label' => 'Dynamic Photo',
        ),
        601 => array(
            'Id' => 4,
            'Label' => 'Portrait',
        ),
        602 => array(
            'Id' => 5,
            'Label' => 'Scenery',
        ),
        603 => array(
            'Id' => 6,
            'Label' => 'Portrait with Scenery',
        ),
        604 => array(
            'Id' => 7,
            'Label' => 'Children',
        ),
        605 => array(
            'Id' => 8,
            'Label' => 'Sports',
        ),
        606 => array(
            'Id' => 9,
            'Label' => 'Candlelight Portrait',
        ),
        607 => array(
            'Id' => 10,
            'Label' => 'Party',
        ),
        608 => array(
            'Id' => 11,
            'Label' => 'Pet',
        ),
        609 => array(
            'Id' => 12,
            'Label' => 'Flower',
        ),
        610 => array(
            'Id' => 13,
            'Label' => 'Natural Green',
        ),
        611 => array(
            'Id' => 14,
            'Label' => 'Autumn Leaves',
        ),
        612 => array(
            'Id' => 15,
            'Label' => 'Soft Flowing Water',
        ),
        613 => array(
            'Id' => 16,
            'Label' => 'Splashing Water',
        ),
        614 => array(
            'Id' => 17,
            'Label' => 'Sundown',
        ),
        615 => array(
            'Id' => 18,
            'Label' => 'Night Scene',
        ),
        616 => array(
            'Id' => 19,
            'Label' => 'Night Scene Portrait',
        ),
        617 => array(
            'Id' => 20,
            'Label' => 'Fireworks',
        ),
        618 => array(
            'Id' => 21,
            'Label' => 'Food',
        ),
        619 => array(
            'Id' => 22,
            'Label' => 'Text',
        ),
        620 => array(
            'Id' => 23,
            'Label' => 'Collection',
        ),
        621 => array(
            'Id' => 24,
            'Label' => 'Auction',
        ),
        622 => array(
            'Id' => 25,
            'Label' => 'Backlight',
        ),
        623 => array(
            'Id' => 26,
            'Label' => 'High Sensitivity',
        ),
        624 => array(
            'Id' => 27,
            'Label' => 'Oil Painting',
        ),
        625 => array(
            'Id' => 28,
            'Label' => 'Crayon',
        ),
        626 => array(
            'Id' => 29,
            'Label' => 'Water Color',
        ),
        627 => array(
            'Id' => 30,
            'Label' => 'Monochrome',
        ),
        628 => array(
            'Id' => 31,
            'Label' => 'Retro',
        ),
        629 => array(
            'Id' => 32,
            'Label' => 'Twilight',
        ),
        630 => array(
            'Id' => 33,
            'Label' => 'Multi-motion Image',
        ),
        631 => array(
            'Id' => 34,
            'Label' => 'ID Photo',
        ),
        632 => array(
            'Id' => 35,
            'Label' => 'Business Cards',
        ),
        633 => array(
            'Id' => 36,
            'Label' => 'White Board',
        ),
        634 => array(
            'Id' => 37,
            'Label' => 'Silent',
        ),
        635 => array(
            'Id' => 38,
            'Label' => 'Pre-record Movie',
        ),
        636 => array(
            'Id' => 39,
            'Label' => 'For YouTube',
        ),
        637 => array(
            'Id' => 40,
            'Label' => 'Voice Recording',
        ),
        638 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        639 => array(
            'Id' => 1,
            'Label' => 'Portrait',
        ),
        640 => array(
            'Id' => 2,
            'Label' => 'Scenery',
        ),
        641 => array(
            'Id' => 3,
            'Label' => 'Portrait With Scenery',
        ),
        642 => array(
            'Id' => 4,
            'Label' => 'Children',
        ),
        643 => array(
            'Id' => 5,
            'Label' => 'Sports',
        ),
        644 => array(
            'Id' => 6,
            'Label' => 'Night Scene',
        ),
        645 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        646 => array(
            'Id' => 1,
            'Label' => 'Child CS',
        ),
        647 => array(
            'Id' => 2,
            'Label' => 'Pet CS',
        ),
        648 => array(
            'Id' => 3,
            'Label' => 'Sports CS',
        ),
        649 => array(
            'Id' => 4,
            'Label' => 'Child High Speed Movie',
        ),
        650 => array(
            'Id' => 5,
            'Label' => 'Pet High Speed Movie',
        ),
        651 => array(
            'Id' => 6,
            'Label' => 'Sports High Speed Movie',
        ),
        652 => array(
            'Id' => 7,
            'Label' => 'Multi SR Zoom',
        ),
        653 => array(
            'Id' => 8,
            'Label' => 'Lag Correction',
        ),
        654 => array(
            'Id' => 9,
            'Label' => 'High Speed Night Scene',
        ),
        655 => array(
            'Id' => 10,
            'Label' => 'High Speed Night Scene and Portrait',
        ),
        656 => array(
            'Id' => 11,
            'Label' => 'High Speed Anti Shake',
        ),
        657 => array(
            'Id' => 12,
            'Label' => 'Portrait',
        ),
        658 => array(
            'Id' => 13,
            'Label' => 'Scenery',
        ),
        659 => array(
            'Id' => 14,
            'Label' => 'Portrait with Scenery',
        ),
        660 => array(
            'Id' => 15,
            'Label' => 'Children',
        ),
        661 => array(
            'Id' => 16,
            'Label' => 'Sports',
        ),
        662 => array(
            'Id' => 17,
            'Label' => 'Candlelight Portrait',
        ),
        663 => array(
            'Id' => 18,
            'Label' => 'Party',
        ),
        664 => array(
            'Id' => 19,
            'Label' => 'Pet',
        ),
        665 => array(
            'Id' => 20,
            'Label' => 'Flower',
        ),
        666 => array(
            'Id' => 21,
            'Label' => 'Natural Green',
        ),
        667 => array(
            'Id' => 22,
            'Label' => 'Autumn Leaves',
        ),
        668 => array(
            'Id' => 23,
            'Label' => 'Soft Flowing Water',
        ),
        669 => array(
            'Id' => 24,
            'Label' => 'Splashing Water',
        ),
        670 => array(
            'Id' => 25,
            'Label' => 'Sundown',
        ),
        671 => array(
            'Id' => 26,
            'Label' => 'Fireworks',
        ),
        672 => array(
            'Id' => 27,
            'Label' => 'Food',
        ),
        673 => array(
            'Id' => 28,
            'Label' => 'Text',
        ),
        674 => array(
            'Id' => 29,
            'Label' => 'Collection',
        ),
        675 => array(
            'Id' => 30,
            'Label' => 'For eBay',
        ),
        676 => array(
            'Id' => 31,
            'Label' => 'Pre-record Movie',
        ),
        677 => array(
            'Id' => 32,
            'Label' => 'For YouTube',
        ),
        678 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        679 => array(
            'Id' => 1,
            'Label' => 'High Speed Night Scene',
        ),
        680 => array(
            'Id' => 2,
            'Label' => 'High Speed Night Scene and Portrait',
        ),
        681 => array(
            'Id' => 3,
            'Label' => 'High Speed Anti Shake',
        ),
        682 => array(
            'Id' => 4,
            'Label' => 'Blurred Background',
        ),
        683 => array(
            'Id' => 5,
            'Label' => 'Wide Shot',
        ),
        684 => array(
            'Id' => 6,
            'Label' => 'High Speed Best Selection',
        ),
        685 => array(
            'Id' => 7,
            'Label' => 'Lag Correction',
        ),
        686 => array(
            'Id' => 8,
            'Label' => 'Child CS',
        ),
        687 => array(
            'Id' => 9,
            'Label' => 'Pet CS',
        ),
        688 => array(
            'Id' => 10,
            'Label' => 'Sports CS',
        ),
        689 => array(
            'Id' => 11,
            'Label' => 'Child High Speed Movie',
        ),
        690 => array(
            'Id' => 12,
            'Label' => 'Pet High Speed Movie',
        ),
        691 => array(
            'Id' => 13,
            'Label' => 'Sports High Speed Movie',
        ),
        692 => array(
            'Id' => 14,
            'Label' => 'Portrait',
        ),
        693 => array(
            'Id' => 15,
            'Label' => 'Scenery',
        ),
        694 => array(
            'Id' => 16,
            'Label' => 'Portrait with Scenery',
        ),
        695 => array(
            'Id' => 17,
            'Label' => 'Children',
        ),
        696 => array(
            'Id' => 18,
            'Label' => 'Sports',
        ),
        697 => array(
            'Id' => 19,
            'Label' => 'Candlelight Portrait',
        ),
        698 => array(
            'Id' => 20,
            'Label' => 'Party',
        ),
        699 => array(
            'Id' => 21,
            'Label' => 'Pet',
        ),
        700 => array(
            'Id' => 22,
            'Label' => 'Flower',
        ),
        701 => array(
            'Id' => 23,
            'Label' => 'Natural Green',
        ),
        702 => array(
            'Id' => 24,
            'Label' => 'Autumn Leaves',
        ),
        703 => array(
            'Id' => 25,
            'Label' => 'Soft Flowing Water',
        ),
        704 => array(
            'Id' => 26,
            'Label' => 'Splashing Water',
        ),
        705 => array(
            'Id' => 27,
            'Label' => 'Sundown',
        ),
        706 => array(
            'Id' => 28,
            'Label' => 'Fireworks',
        ),
        707 => array(
            'Id' => 29,
            'Label' => 'Food',
        ),
        708 => array(
            'Id' => 30,
            'Label' => 'Text',
        ),
        709 => array(
            'Id' => 31,
            'Label' => 'Collection',
        ),
        710 => array(
            'Id' => 32,
            'Label' => 'Auction',
        ),
        711 => array(
            'Id' => 33,
            'Label' => 'Pre-record Movie',
        ),
        712 => array(
            'Id' => 34,
            'Label' => 'For YouTube',
        ),
        713 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        714 => array(
            'Id' => 1,
            'Label' => 'People',
        ),
        715 => array(
            'Id' => 2,
            'Label' => 'Scenery',
        ),
        716 => array(
            'Id' => 3,
            'Label' => 'Flower',
        ),
        717 => array(
            'Id' => 4,
            'Label' => 'Night Scene',
        ),
        718 => array(
            'Id' => 5,
            'Label' => 'Soft Focus',
        ),
        719 => array(
            'Id' => 1,
            'Label' => 'High Speed Night Shot',
        ),
        720 => array(
            'Id' => 2,
            'Label' => 'Blurred Background',
        ),
        721 => array(
            'Id' => 3,
            'Label' => 'Toy Camera',
        ),
        722 => array(
            'Id' => 4,
            'Label' => 'Soft Focus',
        ),
        723 => array(
            'Id' => 5,
            'Label' => 'Light Tone',
        ),
        724 => array(
            'Id' => 6,
            'Label' => 'Pop',
        ),
        725 => array(
            'Id' => 7,
            'Label' => 'Sepia',
        ),
        726 => array(
            'Id' => 8,
            'Label' => 'Monochrome',
        ),
        727 => array(
            'Id' => 9,
            'Label' => 'Miniature',
        ),
        728 => array(
            'Id' => 10,
            'Label' => 'Wide Shot',
        ),
        729 => array(
            'Id' => 11,
            'Label' => 'High Speed Best Selection',
        ),
        730 => array(
            'Id' => 12,
            'Label' => 'Lag Correction',
        ),
        731 => array(
            'Id' => 13,
            'Label' => 'High Speed Night Scene',
        ),
        732 => array(
            'Id' => 14,
            'Label' => 'High Speed Night Scene and Portrait',
        ),
        733 => array(
            'Id' => 15,
            'Label' => 'High Speed Anti Shake',
        ),
        734 => array(
            'Id' => 16,
            'Label' => 'Portrait',
        ),
        735 => array(
            'Id' => 17,
            'Label' => 'Scenery',
        ),
        736 => array(
            'Id' => 18,
            'Label' => 'Portrait with Scenery',
        ),
        737 => array(
            'Id' => 19,
            'Label' => 'Children',
        ),
        738 => array(
            'Id' => 20,
            'Label' => 'Sports',
        ),
        739 => array(
            'Id' => 21,
            'Label' => 'Candlelight Portrait',
        ),
        740 => array(
            'Id' => 22,
            'Label' => 'Party',
        ),
        741 => array(
            'Id' => 23,
            'Label' => 'Pet',
        ),
        742 => array(
            'Id' => 24,
            'Label' => 'Flower',
        ),
        743 => array(
            'Id' => 25,
            'Label' => 'Natural Green',
        ),
        744 => array(
            'Id' => 26,
            'Label' => 'Autumn Leaves',
        ),
        745 => array(
            'Id' => 27,
            'Label' => 'Soft Flowing Water',
        ),
        746 => array(
            'Id' => 28,
            'Label' => 'Splashing Water',
        ),
        747 => array(
            'Id' => 29,
            'Label' => 'Sundown',
        ),
        748 => array(
            'Id' => 30,
            'Label' => 'Fireworks',
        ),
        749 => array(
            'Id' => 31,
            'Label' => 'Food',
        ),
        750 => array(
            'Id' => 32,
            'Label' => 'Text',
        ),
        751 => array(
            'Id' => 33,
            'Label' => 'Collection',
        ),
        752 => array(
            'Id' => 34,
            'Label' => 'Auction',
        ),
        753 => array(
            'Id' => 35,
            'Label' => 'Prerecord (Movie)',
        ),
        754 => array(
            'Id' => 36,
            'Label' => 'For YouTube',
        ),
    );

    protected $Index = 'mixed';

}
