<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Canon;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ManualFlashOutput extends AbstractTag
{

    protected $Id = 41;

    protected $Name = 'ManualFlashOutput';

    protected $FullName = 'Canon::CameraSettings';

    protected $GroupName = 'Canon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Canon';

    protected $g2 = 'Camera';

    protected $Type = 'int16s';

    protected $Writable = true;

    protected $Description = 'Manual Flash Output';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'n/a',
        ),
        1280 => array(
            'Id' => 1280,
            'Label' => 'Full',
        ),
        1282 => array(
            'Id' => 1282,
            'Label' => 'Medium',
        ),
        1284 => array(
            'Id' => 1284,
            'Label' => 'Low',
        ),
        32767 => array(
            'Id' => 32767,
            'Label' => 'n/a',
        ),
    );

}
