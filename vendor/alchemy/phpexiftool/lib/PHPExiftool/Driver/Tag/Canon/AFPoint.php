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
class AFPoint extends AbstractTag
{

    protected $Id = 19;

    protected $Name = 'AFPoint';

    protected $FullName = 'Canon::CameraSettings';

    protected $GroupName = 'Canon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Canon';

    protected $g2 = 'Camera';

    protected $Type = 'int16s';

    protected $Writable = true;

    protected $Description = 'AF Point';

    protected $flag_Permanent = true;

    protected $Values = array(
        8197 => array(
            'Id' => 8197,
            'Label' => 'Manual AF point selection',
        ),
        12288 => array(
            'Id' => 12288,
            'Label' => 'None (MF)',
        ),
        12289 => array(
            'Id' => 12289,
            'Label' => 'Auto AF point selection',
        ),
        12290 => array(
            'Id' => 12290,
            'Label' => 'Right',
        ),
        12291 => array(
            'Id' => 12291,
            'Label' => 'Center',
        ),
        12292 => array(
            'Id' => 12292,
            'Label' => 'Left',
        ),
        16385 => array(
            'Id' => 16385,
            'Label' => 'Auto AF point selection',
        ),
        16390 => array(
            'Id' => 16390,
            'Label' => 'Face Detect',
        ),
    );

}
