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
class ModeDialPosition extends AbstractTag
{

    protected $Id = 20;

    protected $Name = 'ModeDialPosition';

    protected $FullName = 'Sony::ExtraInfo3';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Mode Dial Position';

    protected $flag_Permanent = true;

    protected $Index = 2;

    protected $Values = array(
        248 => array(
            'Id' => 248,
            'Label' => 'No Flash',
        ),
        249 => array(
            'Id' => 249,
            'Label' => 'Aperture-priority AE',
        ),
        250 => array(
            'Id' => 250,
            'Label' => 'SCN',
        ),
        251 => array(
            'Id' => 251,
            'Label' => 'Shutter speed priority AE',
        ),
        252 => array(
            'Id' => 252,
            'Label' => 'Auto',
        ),
        253 => array(
            'Id' => 253,
            'Label' => 'Program AE',
        ),
        254 => array(
            'Id' => 254,
            'Label' => 'Panorama',
        ),
        255 => array(
            'Id' => 255,
            'Label' => 'Manual',
        ),
    );

}
