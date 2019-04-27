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
class ImageQuality extends AbstractTag
{

    protected $Id = 29;

    protected $Name = 'ImageQuality';

    protected $FullName = 'Sony::PMP';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Image Quality';

    protected $flag_Permanent = true;

    protected $Values = array(
        8 => array(
            'Id' => 8,
            'Label' => 'Snap Shot',
        ),
        23 => array(
            'Id' => 23,
            'Label' => 'Standard',
        ),
        51 => array(
            'Id' => 51,
            'Label' => 'Fine',
        ),
    );

}
