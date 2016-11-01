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
class HDR extends AbstractTag
{

    protected $Id = 158;

    protected $Name = 'HDR';

    protected $FullName = 'Panasonic::Main';

    protected $GroupName = 'Panasonic';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Panasonic';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'HDR';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        100 => array(
            'Id' => 100,
            'Label' => '1 EV',
        ),
        200 => array(
            'Id' => 200,
            'Label' => '2 EV',
        ),
        300 => array(
            'Id' => 300,
            'Label' => '3 EV',
        ),
        32868 => array(
            'Id' => 32868,
            'Label' => '1 EV (Auto)',
        ),
        32968 => array(
            'Id' => 32968,
            'Label' => '2 EV (Auto)',
        ),
        33068 => array(
            'Id' => 33068,
            'Label' => '3 EV (Auto)',
        ),
    );

}
