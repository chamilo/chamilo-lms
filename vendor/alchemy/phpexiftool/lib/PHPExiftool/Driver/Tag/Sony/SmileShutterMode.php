<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Sony;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class SmileShutterMode extends AbstractTag
{

    protected $Id = 39;

    protected $Name = 'SmileShutterMode';

    protected $FullName = 'Sony::CameraSettings3';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Smile Shutter Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        17 => array(
            'Id' => 17,
            'Label' => 'Slight Smile',
        ),
        18 => array(
            'Id' => 18,
            'Label' => 'Normal Smile',
        ),
        19 => array(
            'Id' => 19,
            'Label' => 'Big Smile',
        ),
    );

}
