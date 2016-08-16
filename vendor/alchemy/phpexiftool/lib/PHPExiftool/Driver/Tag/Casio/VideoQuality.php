<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Casio;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class VideoQuality extends AbstractTag
{

    protected $Id = 16387;

    protected $Name = 'VideoQuality';

    protected $FullName = 'Casio::Type2';

    protected $GroupName = 'Casio';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Casio';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Video Quality';

    protected $flag_Permanent = true;

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Standard',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'HD (720p)',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Full HD (1080p)',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Low',
        ),
    );

}
