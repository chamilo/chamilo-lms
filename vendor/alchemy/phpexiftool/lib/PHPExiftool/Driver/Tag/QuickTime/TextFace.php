<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\QuickTime;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class TextFace extends AbstractTag
{

    protected $Id = 6;

    protected $Name = 'TextFace';

    protected $FullName = 'QuickTime::TCMediaInfo';

    protected $GroupName = 'QuickTime';

    protected $g0 = 'QuickTime';

    protected $g1 = 'QuickTime';

    protected $g2 = 'Video';

    protected $Type = 'int16u';

    protected $Writable = false;

    protected $Description = 'Text Face';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Plain',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Bold',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Italic',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Underline',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Outline',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Shadow',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'Condense',
        ),
        64 => array(
            'Id' => 64,
            'Label' => 'Extend',
        ),
    );

}
