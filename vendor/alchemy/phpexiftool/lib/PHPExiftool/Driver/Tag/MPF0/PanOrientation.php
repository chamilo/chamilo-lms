<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\MPF0;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class PanOrientation extends AbstractTag
{

    protected $Id = 45569;

    protected $Name = 'PanOrientation';

    protected $FullName = 'MPF::Main';

    protected $GroupName = 'MPF0';

    protected $g0 = 'MPF';

    protected $g1 = 'MPF0';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Pan Orientation';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '[unused]',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Start at top right',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Start at top left',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Start at bottom left',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Start at bottom right',
        ),
    );

}
