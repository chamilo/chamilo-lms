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
class MinFocusDistance extends AbstractTag
{

    protected $Id = 3;

    protected $Name = 'MinFocusDistance';

    protected $FullName = 'Pentax::LensData';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Min Focus Distance';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '0.13-0.19 m',
        ),
        8 => array(
            'Id' => 8,
            'Label' => '0.20-0.24 m',
        ),
        16 => array(
            'Id' => 16,
            'Label' => '0.25-0.28 m',
        ),
        24 => array(
            'Id' => 24,
            'Label' => '0.28-0.30 m',
        ),
        32 => array(
            'Id' => 32,
            'Label' => '0.35-0.38 m',
        ),
        40 => array(
            'Id' => 40,
            'Label' => '0.40-0.45 m',
        ),
        48 => array(
            'Id' => 48,
            'Label' => '0.49-0.50 m',
        ),
        56 => array(
            'Id' => 56,
            'Label' => '0.6 m',
        ),
        64 => array(
            'Id' => 64,
            'Label' => '0.7 m',
        ),
        72 => array(
            'Id' => 72,
            'Label' => '0.8-0.9 m',
        ),
        80 => array(
            'Id' => 80,
            'Label' => '1.0 m',
        ),
        88 => array(
            'Id' => 88,
            'Label' => '1.1-1.2 m',
        ),
        96 => array(
            'Id' => 96,
            'Label' => '1.4-1.5 m',
        ),
        104 => array(
            'Id' => 104,
            'Label' => '1.5 m',
        ),
        112 => array(
            'Id' => 112,
            'Label' => '2.0 m',
        ),
        120 => array(
            'Id' => 120,
            'Label' => '2.0-2.1 m',
        ),
        128 => array(
            'Id' => 128,
            'Label' => '2.1 m',
        ),
        136 => array(
            'Id' => 136,
            'Label' => '2.2-2.9 m',
        ),
        144 => array(
            'Id' => 144,
            'Label' => '3.0 m',
        ),
        152 => array(
            'Id' => 152,
            'Label' => '4-5 m',
        ),
        160 => array(
            'Id' => 160,
            'Label' => '5.6 m',
        ),
    );

}
