<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Flash;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Compressed extends AbstractTag
{

    protected $Id = 'Compressed';

    protected $Name = 'Compressed';

    protected $FullName = 'Flash::Main';

    protected $GroupName = 'Flash';

    protected $g0 = 'Flash';

    protected $g1 = 'Flash';

    protected $g2 = 'Video';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Compressed';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => false,
        ),
        1 => array(
            'Id' => 1,
            'Label' => true,
        ),
    );

}
