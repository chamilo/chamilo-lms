<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\QuickTime;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AudioVendorID extends AbstractTag
{

    protected $Id = 20;

    protected $Name = 'AudioVendorID';

    protected $FullName = 'QuickTime::AudioSampleDesc';

    protected $GroupName = 'QuickTime';

    protected $g0 = 'QuickTime';

    protected $g1 = 'QuickTime';

    protected $g2 = 'Audio';

    protected $Type = 'undef';

    protected $Writable = false;

    protected $Description = 'Audio Vendor ID';

    protected $MaxLength = 4;

    protected $Values = array(
        ' KD ' => array(
            'Id' => ' KD ',
            'Label' => 'Kodak',
        ),
        'AR.D' => array(
            'Id' => 'AR.D',
            'Label' => 'Parrot AR.Drone',
        ),
        'FFMP' => array(
            'Id' => 'FFMP',
            'Label' => 'FFmpeg',
        ),
        'GIC ' => array(
            'Id' => 'GIC ',
            'Label' => 'General Imaging Co.',
        ),
        'KMPI' => array(
            'Id' => 'KMPI',
            'Label' => 'Konica-Minolta',
        ),
        'NIKO' => array(
            'Id' => 'NIKO',
            'Label' => 'Nikon',
        ),
        'SMI ' => array(
            'Id' => 'SMI ',
            'Label' => 'Sorenson Media Inc.',
        ),
        'ZORA' => array(
            'Id' => 'ZORA',
            'Label' => 'Zoran Corporation',
        ),
        'appl' => array(
            'Id' => 'appl',
            'Label' => 'Apple',
        ),
        'fe20' => array(
            'Id' => 'fe20',
            'Label' => 'Olympus (fe20)',
        ),
        'kdak' => array(
            'Id' => 'kdak',
            'Label' => 'Kodak',
        ),
        'leic' => array(
            'Id' => 'leic',
            'Label' => 'Leica',
        ),
        'mino' => array(
            'Id' => 'mino',
            'Label' => 'Minolta',
        ),
        'niko' => array(
            'Id' => 'niko',
            'Label' => 'Nikon',
        ),
        'olym' => array(
            'Id' => 'olym',
            'Label' => 'Olympus',
        ),
        'pana' => array(
            'Id' => 'pana',
            'Label' => 'Panasonic',
        ),
        'pent' => array(
            'Id' => 'pent',
            'Label' => 'Pentax',
        ),
        'pr01' => array(
            'Id' => 'pr01',
            'Label' => 'Olympus (pr01)',
        ),
        'sany' => array(
            'Id' => 'sany',
            'Label' => 'Sanyo',
        ),
    );

}
