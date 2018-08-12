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
class HandlerClass extends AbstractTag
{

    protected $Id = 4;

    protected $Name = 'HandlerClass';

    protected $FullName = 'QuickTime::Handler';

    protected $GroupName = 'QuickTime';

    protected $g0 = 'QuickTime';

    protected $g1 = 'QuickTime';

    protected $g2 = 'Video';

    protected $Type = 'undef';

    protected $Writable = false;

    protected $Description = 'Handler Class';

    protected $MaxLength = 4;

    protected $Values = array(
        'dhlr' => array(
            'Id' => 'dhlr',
            'Label' => 'Data Handler',
        ),
        'mhlr' => array(
            'Id' => 'mhlr',
            'Label' => 'Media Handler',
        ),
    );

}
