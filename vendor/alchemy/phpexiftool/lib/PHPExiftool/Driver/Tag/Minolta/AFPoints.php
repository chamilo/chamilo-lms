<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Minolta;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AFPoints extends AbstractTag
{

    protected $Id = 16;

    protected $Name = 'AFPoints';

    protected $FullName = 'Minolta::CameraSettings7D';

    protected $GroupName = 'Minolta';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Minolta';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'AF Points';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '(none)',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Center',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Top',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Top-right',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Right',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Bottom-right',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'Bottom',
        ),
        64 => array(
            'Id' => 64,
            'Label' => 'Bottom-left',
        ),
        128 => array(
            'Id' => 128,
            'Label' => 'Left',
        ),
        256 => array(
            'Id' => 256,
            'Label' => 'Top-left',
        ),
    );

}
