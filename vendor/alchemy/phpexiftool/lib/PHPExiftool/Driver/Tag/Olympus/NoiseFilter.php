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
class NoiseFilter extends AbstractTag
{

    protected $Id = 1319;

    protected $Name = 'NoiseFilter';

    protected $FullName = 'Olympus::CameraSettings';

    protected $GroupName = 'Olympus';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Olympus';

    protected $g2 = 'Camera';

    protected $Type = 'int16s';

    protected $Writable = true;

    protected $Description = 'Noise Filter';

    protected $flag_Permanent = true;

    protected $MaxLength = 3;

    protected $Values = array(
        '-1 -2 1' => array(
            'Id' => '-1 -2 1',
            'Label' => 'Low',
        ),
        '-2 -2 1' => array(
            'Id' => '-2 -2 1',
            'Label' => 'Off',
        ),
        '0 -2 1' => array(
            'Id' => '0 -2 1',
            'Label' => 'Standard',
        ),
        '0 0 0' => array(
            'Id' => '0 0 0',
            'Label' => 'n/a',
        ),
        '1 -2 1' => array(
            'Id' => '1 -2 1',
            'Label' => 'High',
        ),
    );

}
