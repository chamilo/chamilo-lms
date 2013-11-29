<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
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
        1 => array(
            'Id' => 1,
            'Label' => 'Fired',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Bounce Flash',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Wide Flash Adapter',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'Dome Diffuser',
        ),
    );

}
