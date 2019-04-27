<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Leica;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ExposureMode extends AbstractTag
{

    protected $Id = 1037;

    protected $Name = 'ExposureMode';

    protected $FullName = 'Panasonic::Leica5';

    protected $GroupName = 'Leica';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Leica';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Exposure Mode';

    protected $flag_Permanent = true;

    protected $MaxLength = 4;

    protected $Values = array(
        '0 0 0 0' => array(
            'Id' => '0 0 0 0',
            'Label' => 'Program AE',
        ),
        '1 0 0 0' => array(
            'Id' => '1 0 0 0',
            'Label' => 'Aperture-priority AE',
        ),
        '1 1 0 0' => array(
            'Id' => '1 1 0 0',
            'Label' => 'Aperture-priority AE (1)',
        ),
        '2 0 0 0' => array(
            'Id' => '2 0 0 0',
            'Label' => 'Shutter speed priority AE',
        ),
        '3 0 0 0' => array(
            'Id' => '3 0 0 0',
            'Label' => 'Manual',
        ),
    );

}
