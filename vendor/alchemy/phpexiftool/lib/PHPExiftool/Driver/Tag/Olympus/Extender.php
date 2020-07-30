<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Olympus;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Extender extends AbstractTag
{

    protected $Id = 769;

    protected $Name = 'Extender';

    protected $FullName = 'Olympus::Equipment';

    protected $GroupName = 'Olympus';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Olympus';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Extender';

    protected $flag_Permanent = true;

    protected $MaxLength = 6;

    protected $Values = array(
        '0 00' => array(
            'Id' => '0 00',
            'Label' => 'None',
        ),
        '0 04' => array(
            'Id' => '0 04',
            'Label' => 'Olympus Zuiko Digital EC-14 1.4x Teleconverter',
        ),
        '0 08' => array(
            'Id' => '0 08',
            'Label' => 'Olympus EX-25 Extension Tube',
        ),
        '0 10' => array(
            'Id' => '0 10',
            'Label' => 'Olympus Zuiko Digital EC-20 2.0x Teleconverter',
        ),
    );

}
