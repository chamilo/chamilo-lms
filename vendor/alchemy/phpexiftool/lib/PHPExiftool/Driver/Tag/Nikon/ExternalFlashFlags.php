<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Nikon;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ExternalFlashFlags extends AbstractTag
{

    protected $Id = 8;

    protected $Name = 'ExternalFlashFlags';

    protected $FullName = 'mixed';

    protected $GroupName = 'Nikon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Nikon';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'External Flash Flags';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '(none)',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Fired',
        ),
        2 => array(
            'Id' => 4,
            'Label' => 'Bounce Flash',
        ),
        3 => array(
            'Id' => 16,
            'Label' => 'Wide Flash Adapter',
        ),
        4 => array(
            'Id' => 32,
            'Label' => 'Dome Diffuser',
        ),
        5 => array(
            'Id' => 1,
            'Label' => 'Fired',
        ),
        6 => array(
            'Id' => 4,
            'Label' => 'Bounce Flash',
        ),
        7 => array(
            'Id' => 16,
            'Label' => 'Wide Flash Adapter',
        ),
        8 => array(
            'Id' => 32,
            'Label' => 'Dome Diffuser',
        ),
        9 => array(
            'Id' => 1,
            'Label' => 'Fired',
        ),
        10 => array(
            'Id' => 4,
            'Label' => 'Bounce Flash',
        ),
        11 => array(
            'Id' => 16,
            'Label' => 'Wide Flash Adapter',
        ),
        12 => array(
            'Id' => 32,
            'Label' => 'Dome Diffuser',
        ),
    );

}
