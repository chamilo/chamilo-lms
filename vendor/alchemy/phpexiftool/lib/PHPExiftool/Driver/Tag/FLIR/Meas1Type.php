<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\FLIR;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Meas1Type extends AbstractTag
{

    protected $Id = 'Meas1Type';

    protected $Name = 'Meas1Type';

    protected $FullName = 'FLIR::MeasInfo';

    protected $GroupName = 'FLIR';

    protected $g0 = 'APP1';

    protected $g1 = 'FLIR';

    protected $g2 = 'Image';

    protected $Type = 'int16u';

    protected $Writable = false;

    protected $Description = 'Meas 1 Type';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Spot',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Area',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Ellipse',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Line',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Endpoint',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Alarm',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Unused',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Difference',
        ),
    );

}
