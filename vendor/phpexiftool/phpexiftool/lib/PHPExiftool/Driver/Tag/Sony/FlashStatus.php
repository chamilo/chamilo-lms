<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
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
class FlashStatus extends AbstractTag
{

    protected $Id = 49;

    protected $Name = 'FlashStatus';

    protected $FullName = 'Sony::Tag9050';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Flash Status';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'No Flash present',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Flash Inhibited',
        ),
        64 => array(
            'Id' => 64,
            'Label' => 'Built-in Flash present',
        ),
        65 => array(
            'Id' => 65,
            'Label' => 'Built-in Flash Fired',
        ),
        66 => array(
            'Id' => 66,
            'Label' => 'Built-in Flash Inhibited',
        ),
        128 => array(
            'Id' => 128,
            'Label' => 'External Flash present',
        ),
        129 => array(
            'Id' => 129,
            'Label' => 'External Flash Fired',
        ),
    );

}
