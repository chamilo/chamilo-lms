<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
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
class ShakeReduction extends AbstractTag
{

    protected $Id = 1;

    protected $Name = 'ShakeReduction';

    protected $FullName = 'Pentax::SRInfo';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Shake Reduction';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'On',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Off (4)',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'On but Disabled',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'On (Video)',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'On (7)',
        ),
        15 => array(
            'Id' => 15,
            'Label' => 'On (15)',
        ),
        135 => array(
            'Id' => 135,
            'Label' => 'On (135)',
        ),
    );

}
