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
class BodyBatteryState extends AbstractTag
{

    protected $Id = '1.1';

    protected $Name = 'BodyBatteryState';

    protected $FullName = 'Pentax::BatteryInfo';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Body Battery State';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 16,
            'Label' => 'Empty or Missing',
        ),
        1 => array(
            'Id' => 32,
            'Label' => 'Almost Empty',
        ),
        2 => array(
            'Id' => 48,
            'Label' => 'Running Low',
        ),
        3 => array(
            'Id' => 64,
            'Label' => 'Full',
        ),
        4 => array(
            'Id' => 16,
            'Label' => 'Empty or Missing',
        ),
        5 => array(
            'Id' => 32,
            'Label' => 'Almost Empty',
        ),
        6 => array(
            'Id' => 48,
            'Label' => 'Running Low',
        ),
        7 => array(
            'Id' => 64,
            'Label' => 'Close to Full',
        ),
        8 => array(
            'Id' => 80,
            'Label' => 'Full',
        ),
    );

    protected $Index = 'mixed';

}
