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
class AFPointSelected2 extends AbstractTag
{

    protected $Id = 4;

    protected $Name = 'AFPointSelected2';

    protected $FullName = 'Pentax::CameraSettings';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'AF Point Selected 2';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Upper-left',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Top',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Upper-right',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Left',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Mid-left',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'Center',
        ),
        64 => array(
            'Id' => 64,
            'Label' => 'Mid-right',
        ),
        128 => array(
            'Id' => 128,
            'Label' => 'Right',
        ),
        256 => array(
            'Id' => 256,
            'Label' => 'Lower-left',
        ),
        512 => array(
            'Id' => 512,
            'Label' => 'Bottom',
        ),
        1024 => array(
            'Id' => 1024,
            'Label' => 'Lower-right',
        ),
    );

}
