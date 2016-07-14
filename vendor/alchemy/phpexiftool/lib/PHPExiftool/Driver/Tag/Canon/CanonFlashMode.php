<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Canon;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class CanonFlashMode extends AbstractTag
{

    protected $Id = 4;

    protected $Name = 'CanonFlashMode';

    protected $FullName = 'Canon::CameraSettings';

    protected $GroupName = 'Canon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Canon';

    protected $g2 = 'Camera';

    protected $Type = 'int16s';

    protected $Writable = true;

    protected $Description = 'Canon Flash Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        '-1' => array(
            'Id' => '-1',
            'Label' => 'n/a',
        ),
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
            'Label' => 'On',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Red-eye reduction',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Slow-sync',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Red-eye reduction (Auto)',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Red-eye reduction (On)',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'External flash',
        ),
    );

}
