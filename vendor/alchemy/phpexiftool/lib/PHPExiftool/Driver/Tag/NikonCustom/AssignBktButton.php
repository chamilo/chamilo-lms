<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\NikonCustom;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AssignBktButton extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'AssignBktButton';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Assign Bkt Button';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Auto Bracketing',
        ),
        1 => array(
            'Id' => 8,
            'Label' => 'Multiple Exposure',
        ),
        2 => array(
            'Id' => 0,
            'Label' => 'Auto Bracketing',
        ),
        3 => array(
            'Id' => 1,
            'Label' => 'Multiple Exposure',
        ),
        4 => array(
            'Id' => 2,
            'Label' => 'HDR (high dynamic range)',
        ),
        5 => array(
            'Id' => 3,
            'Label' => 'None',
        ),
        6 => array(
            'Id' => 0,
            'Label' => 'Auto Bracketing',
        ),
        7 => array(
            'Id' => 1,
            'Label' => 'Multiple Exposure',
        ),
        8 => array(
            'Id' => 2,
            'Label' => 'HDR (high dynamic range)',
        ),
        9 => array(
            'Id' => 3,
            'Label' => 'None',
        ),
    );

}
