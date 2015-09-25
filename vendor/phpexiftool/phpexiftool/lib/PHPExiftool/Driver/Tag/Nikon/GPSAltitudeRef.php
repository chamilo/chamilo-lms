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
class GPSAltitudeRef extends AbstractTag
{

    protected $Id = 18874373;

    protected $Name = 'GPSAltitudeRef';

    protected $FullName = 'Nikon::NCTG';

    protected $GroupName = 'Nikon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Nikon';

    protected $g2 = 'Camera';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'GPS Altitude Ref';

    protected $local_g2 = 'Location';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Above Sea Level',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Below Sea Level',
        ),
    );

}
