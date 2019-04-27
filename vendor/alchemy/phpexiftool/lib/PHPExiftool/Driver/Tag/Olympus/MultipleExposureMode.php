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
class MultipleExposureMode extends AbstractTag
{

    protected $Id = 4124;

    protected $Name = 'MultipleExposureMode';

    protected $FullName = 'Olympus::ImageProcessing';

    protected $GroupName = 'Olympus';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Olympus';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Multiple Exposure Mode';

    protected $flag_Permanent = true;

    protected $MaxLength = 2;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'On (2 frames)',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'On (3 frames)',
        ),
    );

}
