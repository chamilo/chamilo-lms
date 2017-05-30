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
class ISOExpansion2 extends AbstractTag
{

    protected $Id = 10;

    protected $Name = 'ISOExpansion2';

    protected $FullName = 'Nikon::ISOInfo';

    protected $GroupName = 'Nikon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Nikon';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'ISO Expansion 2';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        257 => array(
            'Id' => 257,
            'Label' => 'Hi 0.3',
        ),
        258 => array(
            'Id' => 258,
            'Label' => 'Hi 0.5',
        ),
        259 => array(
            'Id' => 259,
            'Label' => 'Hi 0.7',
        ),
        260 => array(
            'Id' => 260,
            'Label' => 'Hi 1.0',
        ),
        261 => array(
            'Id' => 261,
            'Label' => 'Hi 1.3',
        ),
        262 => array(
            'Id' => 262,
            'Label' => 'Hi 1.5',
        ),
        263 => array(
            'Id' => 263,
            'Label' => 'Hi 1.7',
        ),
        264 => array(
            'Id' => 264,
            'Label' => 'Hi 2.0',
        ),
        513 => array(
            'Id' => 513,
            'Label' => 'Lo 0.3',
        ),
        514 => array(
            'Id' => 514,
            'Label' => 'Lo 0.5',
        ),
        515 => array(
            'Id' => 515,
            'Label' => 'Lo 0.7',
        ),
        516 => array(
            'Id' => 516,
            'Label' => 'Lo 1.0',
        ),
    );

}
