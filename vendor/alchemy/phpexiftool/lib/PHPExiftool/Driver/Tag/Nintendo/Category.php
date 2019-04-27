<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Nintendo;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Category extends AbstractTag
{

    protected $Id = 48;

    protected $Name = 'Category';

    protected $FullName = 'Nintendo::CameraInfo';

    protected $GroupName = 'Nintendo';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Nintendo';

    protected $g2 = 'Image';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Category';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '(none)',
        ),
        4096 => array(
            'Id' => 4096,
            'Label' => 'Mii',
        ),
        8192 => array(
            'Id' => 8192,
            'Label' => 'Man',
        ),
        16384 => array(
            'Id' => 16384,
            'Label' => 'Woman',
        ),
    );

}
