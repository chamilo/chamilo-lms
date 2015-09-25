<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\CanonVRD;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class MonochromeToningEffect extends AbstractTag
{

    protected $Id = 59;

    protected $Name = 'MonochromeToningEffect';

    protected $FullName = 'CanonVRD::Ver2';

    protected $GroupName = 'CanonVRD';

    protected $g0 = 'CanonVRD';

    protected $g1 = 'CanonVRD';

    protected $g2 = 'Image';

    protected $Type = 'int16s';

    protected $Writable = true;

    protected $Description = 'Monochrome Toning Effect';

    protected $Values = array(
        '-2' => array(
            'Id' => '-2',
            'Label' => 'None',
        ),
        '-1' => array(
            'Id' => '-1',
            'Label' => 'Sepia',
        ),
        0 => array(
            'Id' => 0,
            'Label' => 'Blue',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Purple',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Green',
        ),
    );

}
