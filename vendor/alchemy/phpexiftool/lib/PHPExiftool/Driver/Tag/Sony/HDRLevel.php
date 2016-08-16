<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Sony;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class HDRLevel extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'HDRLevel';

    protected $FullName = 'mixed';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'HDR Level';

    protected $flag_Permanent = true;

    protected $Values = array(
        33 => array(
            'Id' => 33,
            'Label' => '1 EV',
        ),
        34 => array(
            'Id' => 34,
            'Label' => '1.5 EV',
        ),
        35 => array(
            'Id' => 35,
            'Label' => '2 EV',
        ),
        36 => array(
            'Id' => 36,
            'Label' => '2.5 EV',
        ),
        37 => array(
            'Id' => 37,
            'Label' => '3 EV',
        ),
        38 => array(
            'Id' => 38,
            'Label' => '3.5 EV',
        ),
        39 => array(
            'Id' => 39,
            'Label' => '4 EV',
        ),
        40 => array(
            'Id' => 40,
            'Label' => '5 EV',
        ),
        41 => array(
            'Id' => 41,
            'Label' => '6 EV',
        ),
    );

}
