<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\PhotoCD;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class CopyrightStatus extends AbstractTag
{

    protected $Id = 331;

    protected $Name = 'CopyrightStatus';

    protected $FullName = 'PhotoCD::Main';

    protected $GroupName = 'PhotoCD';

    protected $g0 = 'PhotoCD';

    protected $g1 = 'PhotoCD';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Copyright Status';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Restrictions apply',
        ),
        255 => array(
            'Id' => 255,
            'Label' => 'Not specified',
        ),
    );

}
