<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Panasonic;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AFAreaMode extends AbstractTag
{

    protected $Id = 15;

    protected $Name = 'AFAreaMode';

    protected $FullName = 'Panasonic::Main';

    protected $GroupName = 'Panasonic';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Panasonic';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'AF Area Mode';

    protected $flag_Permanent = true;

    protected $MaxLength = 2;

    protected $Values = array(
        '0 1' => array(
            'Id' => '0 1',
            'Label' => '9-area',
        ),
        '0 16' => array(
            'Id' => '0 16',
            'Label' => '3-area (high speed)',
        ),
        0 => array(
            'Id' => 16,
            'Label' => 'Normal?',
        ),
        '0 23' => array(
            'Id' => '0 23',
            'Label' => '23-area',
        ),
        '1 0' => array(
            'Id' => '1 0',
            'Label' => 'Spot Focusing',
        ),
        '1 1' => array(
            'Id' => '1 1',
            'Label' => '5-area',
        ),
        '16 0' => array(
            'Id' => '16 0',
            'Label' => '1-area',
        ),
        '16 16' => array(
            'Id' => '16 16',
            'Label' => '1-area (high speed)',
        ),
        '32 0' => array(
            'Id' => '32 0',
            'Label' => 'Tracking',
        ),
        '32 1' => array(
            'Id' => '32 1',
            'Label' => '3-area (left)?',
        ),
        '32 2' => array(
            'Id' => '32 2',
            'Label' => '3-area (center)?',
        ),
        '32 3' => array(
            'Id' => '32 3',
            'Label' => '3-area (right)?',
        ),
        '64 0' => array(
            'Id' => '64 0',
            'Label' => 'Face Detect',
        ),
        '128 0' => array(
            'Id' => '128 0',
            'Label' => 'Spot Focusing 2',
        ),
    );

    protected $Index = 'mixed';

}
