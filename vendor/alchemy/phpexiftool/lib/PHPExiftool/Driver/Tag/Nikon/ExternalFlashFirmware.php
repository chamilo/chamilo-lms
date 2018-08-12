<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Nikon;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ExternalFlashFirmware extends AbstractTag
{

    protected $Id = 6;

    protected $Name = 'ExternalFlashFirmware';

    protected $FullName = 'mixed';

    protected $GroupName = 'Nikon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Nikon';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'External Flash Firmware';

    protected $flag_Permanent = true;

    protected $MaxLength = 2;

    protected $Values = array(
        '0 0' => array(
            'Id' => '0 0',
            'Label' => 'n/a',
        ),
        '1 1' => array(
            'Id' => '1 1',
            'Label' => '1.01 (SB-800 or Metz 58 AF-1)',
        ),
        '1 3' => array(
            'Id' => '1 3',
            'Label' => '1.03 (SB-800)',
        ),
        '2 1' => array(
            'Id' => '2 1',
            'Label' => '2.01 (SB-800)',
        ),
        '2 4' => array(
            'Id' => '2 4',
            'Label' => '2.04 (SB-600)',
        ),
        '2 5' => array(
            'Id' => '2 5',
            'Label' => '2.05 (SB-600)',
        ),
        '3 1' => array(
            'Id' => '3 1',
            'Label' => '3.01 (SU-800 Remote Commander)',
        ),
        '4 1' => array(
            'Id' => '4 1',
            'Label' => '4.01 (SB-400)',
        ),
        '4 2' => array(
            'Id' => '4 2',
            'Label' => '4.02 (SB-400)',
        ),
        '4 4' => array(
            'Id' => '4 4',
            'Label' => '4.04 (SB-400)',
        ),
        '5 1' => array(
            'Id' => '5 1',
            'Label' => '5.01 (SB-900)',
        ),
        '5 2' => array(
            'Id' => '5 2',
            'Label' => '5.02 (SB-900)',
        ),
        '6 1' => array(
            'Id' => '6 1',
            'Label' => '6.01 (SB-700)',
        ),
        '7 1' => array(
            'Id' => '7 1',
            'Label' => '7.01 (SB-910)',
        ),
    );

}
