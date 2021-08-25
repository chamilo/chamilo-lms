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
class DriveMode extends AbstractTag
{

    protected $Id = 52;

    protected $Name = 'DriveMode';

    protected $FullName = 'Pentax::Main';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Drive Mode';

    protected $flag_Permanent = true;

    protected $MaxLength = 4;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Single-frame',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Continuous',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Continuous (Lo)',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Burst',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Continuous (Medium)',
        ),
        255 => array(
            'Id' => 255,
            'Label' => 'Video',
        ),
    );

}
