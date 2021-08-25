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
class FlashIntensity extends AbstractTag
{

    protected $Id = 1029;

    protected $Name = 'FlashIntensity';

    protected $FullName = 'Olympus::CameraSettings';

    protected $GroupName = 'Olympus';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Olympus';

    protected $g2 = 'Camera';

    protected $Type = 'rational64s';

    protected $Writable = true;

    protected $Description = 'Flash Intensity';

    protected $flag_Permanent = true;

    protected $Values = array(
        'undef undef undef' => array(
            'Id' => 'undef undef undef',
            'Label' => 'n/a',
        ),
        'undef undef undef undef' => array(
            'Id' => 'undef undef undef undef',
            'Label' => 'n/a (x4)',
        ),
    );

}
